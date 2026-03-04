<?php
namespace App\Services;

use App\Repositories\ProductRepository;
use App\Repositories\SalesRepository;
use PDO;

class SalesHeaderService
{
    private ?array $vendasColumnsCache = null;
    private ?array $vendasCabecalhoColumnsCache = null;
    private ?array $historicoStatusPedidoColumnsCache = null;
    private ?bool $hasVendasCabecalhoCache = null;
    private ?bool $hasStatusPedidoCache = null;
    private ?bool $hasHistoricoStatusPedidoCache = null;

    public function __construct(private PDO $pdo)
    {
    }

    public function deliverHeader(int $headerId, ?int $currentUserId): array
    {
        if (!$this->hasVendasCabecalhoTable()) {
            throw new \RuntimeException('Pedido não encontrado', 404);
        }

        $currentStatus = $this->currentHeaderStatusPedido($headerId);
        if ($currentStatus === null) {
            throw new \RuntimeException('Pedido não encontrado', 404);
        }

        if (!$this->canTransitionPedido($currentStatus, 'ENTREGUE')) {
            if ($currentStatus === 'ENTREGUE') {
                return ['message' => 'Pedido já entregue'];
            }
            throw new \RuntimeException('Transição de status inválida para este pedido', 409);
        }

        try {
            $salesService = new SalesService(
                new SalesRepository($this->pdo),
                new ProductRepository($this->pdo)
            );

            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare('SELECT id FROM vendas WHERE venda_cabecalho_id = :id AND status <> :status');
            $stmt->execute(['id' => $headerId, 'status' => 'ENTREGUE']);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!$rows) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
                return ['message' => 'Pedido já entregue'];
            }

            foreach ($rows as $row) {
                $salesService->confirmDelivery((int)$row['id']);
            }

            $this->marcarCabecalhoComoEntregue($headerId, $currentUserId);
            $this->pdo->commit();

            return ['message' => 'ENTREGUE'];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw new \RuntimeException('Não foi possível confirmar a entrega do pedido.', 400);
        }
    }

    public function deliverItem(int $saleId, ?int $currentUserId): array
    {
        try {
            $salesService = new SalesService(
                new SalesRepository($this->pdo),
                new ProductRepository($this->pdo)
            );

            $this->pdo->beginTransaction();
            $res = $salesService->confirmDelivery($saleId);

            if ($this->hasVendasCabecalhoTable() && $this->hasVendasColumn('venda_cabecalho_id')) {
                $hs = $this->pdo->prepare('SELECT venda_cabecalho_id FROM vendas WHERE id = :id LIMIT 1');
                $hs->execute(['id' => $saleId]);
                $headerRef = $hs->fetch(PDO::FETCH_ASSOC);
                $hid = isset($headerRef['venda_cabecalho_id']) ? (int)$headerRef['venda_cabecalho_id'] : 0;
                if ($hid > 0) {
                    $pendingStmt = $this->pdo->prepare('SELECT COUNT(*) FROM vendas WHERE venda_cabecalho_id = :id AND status <> :status');
                    $pendingStmt->execute(['id' => $hid, 'status' => 'ENTREGUE']);
                    $pending = (int)$pendingStmt->fetchColumn();
                    if ($pending === 0) {
                        $this->marcarCabecalhoComoEntregue($hid, $currentUserId);
                    }
                }
            }

            $this->pdo->commit();

            return $res;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw new \RuntimeException('Não foi possível confirmar a entrega.', 400);
        }
    }

    public function deleteHeader(int $id): array
    {
        if (!$this->hasVendasCabecalhoTable() || !$this->hasVendasColumn('venda_cabecalho_id')) {
            throw new \RuntimeException('Exclusão por cabeçalho não disponível neste ambiente.', 404);
        }

        $currentStatus = $this->currentHeaderStatusPedido($id);
        if ($currentStatus === null) {
            throw new \RuntimeException('Pedido não encontrado', 404);
        }

        if ($currentStatus === 'ENTREGUE') {
            throw new \RuntimeException('Não é permitido excluir pedido entregue', 409);
        }

        try {
            $this->pdo->beginTransaction();

            $delItems = $this->pdo->prepare('DELETE FROM vendas WHERE venda_cabecalho_id = :id');
            $delItems->execute(['id' => $id]);

            $delHeader = $this->pdo->prepare('DELETE FROM vendas_cabecalho WHERE id = :id');
            $delHeader->execute(['id' => $id]);

            $this->pdo->commit();
            return ['message' => 'Pedido excluído com sucesso'];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw new \RuntimeException('Não foi possível excluir o pedido.', 500);
        }
    }

    private function vendasColumns(): array
    {
        if ($this->vendasColumnsCache !== null) {
            return $this->vendasColumnsCache;
        }

        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $stmt = $this->pdo->query('PRAGMA table_info(vendas)');
            $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->vendasColumnsCache = array_values(array_filter(array_map(
                static fn(array $row) => $row['name'] ?? null,
                $cols
            )));

            return $this->vendasColumnsCache;
        }

        $stmt = $this->pdo->query('SHOW COLUMNS FROM vendas');
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->vendasColumnsCache = array_values(array_filter(array_map(
            static fn(array $row) => $row['Field'] ?? null,
            $cols
        )));

        return $this->vendasColumnsCache;
    }

    private function hasVendasColumn(string $column): bool
    {
        return in_array($column, $this->vendasColumns(), true);
    }

    private function vendasCabecalhoColumns(): array
    {
        if ($this->vendasCabecalhoColumnsCache !== null) {
            return $this->vendasCabecalhoColumnsCache;
        }

        if (!$this->hasVendasCabecalhoTable()) {
            $this->vendasCabecalhoColumnsCache = [];
            return $this->vendasCabecalhoColumnsCache;
        }

        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $stmt = $this->pdo->query('PRAGMA table_info(vendas_cabecalho)');
            $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->vendasCabecalhoColumnsCache = array_values(array_filter(array_map(
                static fn(array $row) => $row['name'] ?? null,
                $cols
            )));
        } else {
            $stmt = $this->pdo->query('SHOW COLUMNS FROM vendas_cabecalho');
            $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->vendasCabecalhoColumnsCache = array_values(array_filter(array_map(
                static fn(array $row) => $row['Field'] ?? null,
                $cols
            )));
        }

        return $this->vendasCabecalhoColumnsCache;
    }

    private function hasVendasCabecalhoColumn(string $column): bool
    {
        return in_array($column, $this->vendasCabecalhoColumns(), true);
    }

    private function hasVendasCabecalhoTable(): bool
    {
        if ($this->hasVendasCabecalhoCache !== null) {
            return $this->hasVendasCabecalhoCache;
        }

        try {
            $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $stmt = $this->pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name='vendas_cabecalho' LIMIT 1");
                $stmt->execute();
                $this->hasVendasCabecalhoCache = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
                return $this->hasVendasCabecalhoCache;
            }

            $stmt = $this->pdo->query("SHOW TABLES LIKE 'vendas_cabecalho'");
            $this->hasVendasCabecalhoCache = (bool)$stmt->fetch(PDO::FETCH_NUM);
            return $this->hasVendasCabecalhoCache;
        } catch (\Throwable $e) {
            $this->hasVendasCabecalhoCache = false;
            return false;
        }
    }

    private function hasStatusPedidoTable(): bool
    {
        if ($this->hasStatusPedidoCache !== null) {
            return $this->hasStatusPedidoCache;
        }

        try {
            $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $stmt = $this->pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name='status_pedido' LIMIT 1");
                $stmt->execute();
                $this->hasStatusPedidoCache = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
                return $this->hasStatusPedidoCache;
            }

            $stmt = $this->pdo->query("SHOW TABLES LIKE 'status_pedido'");
            $this->hasStatusPedidoCache = (bool)$stmt->fetch(PDO::FETCH_NUM);
            return $this->hasStatusPedidoCache;
        } catch (\Throwable $e) {
            $this->hasStatusPedidoCache = false;
            return false;
        }
    }

    private function hasHistoricoStatusPedidoTable(): bool
    {
        if ($this->hasHistoricoStatusPedidoCache !== null) {
            return $this->hasHistoricoStatusPedidoCache;
        }

        try {
            $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $stmt = $this->pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name='historico_status_pedido' LIMIT 1");
                $stmt->execute();
                $this->hasHistoricoStatusPedidoCache = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
                return $this->hasHistoricoStatusPedidoCache;
            }

            $stmt = $this->pdo->query("SHOW TABLES LIKE 'historico_status_pedido'");
            $this->hasHistoricoStatusPedidoCache = (bool)$stmt->fetch(PDO::FETCH_NUM);
            return $this->hasHistoricoStatusPedidoCache;
        } catch (\Throwable $e) {
            $this->hasHistoricoStatusPedidoCache = false;
            return false;
        }
    }

    private function historicoStatusPedidoColumns(): array
    {
        if ($this->historicoStatusPedidoColumnsCache !== null) {
            return $this->historicoStatusPedidoColumnsCache;
        }

        if (!$this->hasHistoricoStatusPedidoTable()) {
            $this->historicoStatusPedidoColumnsCache = [];
            return $this->historicoStatusPedidoColumnsCache;
        }

        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $stmt = $this->pdo->query('PRAGMA table_info(historico_status_pedido)');
            $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->historicoStatusPedidoColumnsCache = array_values(array_filter(array_map(
                static fn(array $row) => $row['name'] ?? null,
                $cols
            )));
        } else {
            $stmt = $this->pdo->query('SHOW COLUMNS FROM historico_status_pedido');
            $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->historicoStatusPedidoColumnsCache = array_values(array_filter(array_map(
                static fn(array $row) => $row['Field'] ?? null,
                $cols
            )));
        }

        return $this->historicoStatusPedidoColumnsCache;
    }

    private function hasHistoricoStatusPedidoColumn(string $column): bool
    {
        return in_array($column, $this->historicoStatusPedidoColumns(), true);
    }

    private function normalizeStatusPedidoLabel(?string $status): string
    {
        $value = strtoupper(trim((string)$status));
        return $value === 'ENTREGUE' ? 'ENTREGUE' : 'AGUARDANDO';
    }

    private function statusPedidoIdByNome(string $nome): ?int
    {
        if (!$this->hasStatusPedidoTable()) {
            return null;
        }

        $stmt = $this->pdo->prepare('SELECT id FROM status_pedido WHERE UPPER(nome) = :nome LIMIT 1');
        $stmt->execute(['nome' => strtoupper($nome)]);
        $id = $stmt->fetchColumn();

        return $id !== false ? (int)$id : null;
    }

    private function currentHeaderStatusPedido(int $headerId): ?string
    {
        if (!$this->hasVendasCabecalhoTable()) {
            return null;
        }

        $stmt = $this->pdo->prepare('SELECT status FROM vendas_cabecalho WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $headerId]);
        $status = $stmt->fetchColumn();

        if ($status === false) {
            return null;
        }

        return $this->normalizeStatusPedidoLabel((string)$status);
    }

    private function canTransitionPedido(string $from, string $to): bool
    {
        if ($from === $to) {
            return true;
        }

        return $from === 'AGUARDANDO' && $to === 'ENTREGUE';
    }

    private function buildSalesSnapshot(int $headerId): ?string
    {
        try {
            $headerStmt = $this->pdo->prepare('SELECT id, valor_total, status FROM vendas_cabecalho WHERE id = :id LIMIT 1');
            $headerStmt->execute(['id' => $headerId]);
            $header = $headerStmt->fetch(PDO::FETCH_ASSOC);
            if (!$header) {
                return null;
            }

            $itemsStmt = $this->pdo->prepare(
                'SELECT v.id, v.produto_id, v.quantidade, v.valor_unitario, v.status
                 FROM vendas v
                 WHERE v.venda_cabecalho_id = :id
                 ORDER BY v.id ASC'
            );
            $itemsStmt->execute(['id' => $headerId]);
            $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

            return json_encode([
                'header' => [
                    'id' => (int)$header['id'],
                    'valor_total' => (float)($header['valor_total'] ?? 0),
                    'status' => $this->normalizeStatusPedidoLabel((string)($header['status'] ?? '')),
                ],
                'items' => $items,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function registrarHistoricoStatus(int $headerId, ?int $usuarioId, int $statusId, ?string $snapshotJson = null): void
    {
        if (!$this->hasHistoricoStatusPedidoTable()) {
            return;
        }

        if ($this->hasHistoricoStatusPedidoColumn('snapshot_json')) {
            $sql = 'INSERT INTO historico_status_pedido (venda_cabecalho_id, usuario_id, id_statuspedido, snapshot_json, confirmado_em)
                VALUES (:venda_cabecalho_id, :usuario_id, :id_statuspedido, :snapshot_json, CURRENT_TIMESTAMP)';
        } else {
            $sql = 'INSERT INTO historico_status_pedido (venda_cabecalho_id, usuario_id, id_statuspedido, confirmado_em)
                VALUES (:venda_cabecalho_id, :usuario_id, :id_statuspedido, CURRENT_TIMESTAMP)';
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue('venda_cabecalho_id', $headerId, PDO::PARAM_INT);
        if ($usuarioId !== null) {
            $stmt->bindValue('usuario_id', $usuarioId, PDO::PARAM_INT);
        } else {
            $stmt->bindValue('usuario_id', null, PDO::PARAM_NULL);
        }
        $stmt->bindValue('id_statuspedido', $statusId, PDO::PARAM_INT);
        if ($this->hasHistoricoStatusPedidoColumn('snapshot_json')) {
            if ($snapshotJson !== null) {
                $stmt->bindValue('snapshot_json', $snapshotJson, PDO::PARAM_STR);
            } else {
                $stmt->bindValue('snapshot_json', null, PDO::PARAM_NULL);
            }
        }
        $stmt->execute();
    }

    private function marcarCabecalhoComoEntregue(int $headerId, ?int $usuarioId): void
    {
        $statusId = $this->statusPedidoIdByNome('ENTREGUE') ?? 2;

        if ($this->hasVendasCabecalhoColumn('id_statuspedido')) {
            $up = $this->pdo->prepare('UPDATE vendas_cabecalho SET status = :status, id_statuspedido = :id_statuspedido WHERE id = :id');
            $up->execute([
                'status' => 'ENTREGUE',
                'id_statuspedido' => $statusId,
                'id' => $headerId,
            ]);
        } else {
            $up = $this->pdo->prepare('UPDATE vendas_cabecalho SET status = :status WHERE id = :id');
            $up->execute(['status' => 'ENTREGUE', 'id' => $headerId]);
        }

        $snapshot = $this->buildSalesSnapshot($headerId);
        $this->registrarHistoricoStatus($headerId, $usuarioId, $statusId, $snapshot);
    }
}