<?php
namespace App\Services;

use App\Repositories\ProductRepository;
use App\Repositories\PurchaseRepository;
use PDO;

class PurchaseHeaderService
{
    private ?array $comprasColumnsCache = null;
    private ?array $comprasCabecalhoColumnsCache = null;
    private ?array $historicoStatusCompraColumnsCache = null;
    private ?bool $hasComprasCabecalhoCache = null;
    private ?bool $hasStatusCompraCache = null;
    private ?bool $hasHistoricoStatusCompraCache = null;

    public function __construct(private PDO $pdo)
    {
    }

    public function confirmHeaderDelivery(int $id, ?int $currentUserId): array
    {
        if (!$this->hasComprasCabecalhoTable() || !$this->hasComprasColumn('compra_cabecalho_id')) {
            throw new \RuntimeException('Confirmação por cabeçalho não disponível neste ambiente.', 404);
        }

        $currentStatus = $this->currentHeaderStatusCompra($id);
        if ($currentStatus === null) {
            throw new \RuntimeException('Pedido de compra não encontrado', 404);
        }

        if (!$this->canTransitionCompra($currentStatus, 'RECEBIDA')) {
            if ($currentStatus === 'RECEBIDA') {
                return ['message' => 'Compra já confirmada como recebida'];
            }
            throw new \RuntimeException('Transição de status inválida para este pedido de compra', 409);
        }

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare('SELECT id FROM compras WHERE compra_cabecalho_id = :id AND status <> :status');
            $stmt->execute(['id' => $id, 'status' => 'RECEBIDA']);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!$rows) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
                return ['message' => 'Compra já confirmada como recebida'];
            }

            $novoEstoque = null;
            foreach ($rows as $row) {
                $res = $this->confirmItemReceiveById((int)$row['id']);
                $novoEstoque = $res['novo_estoque'] ?? $novoEstoque;
            }

            $this->marcarCabecalhoComoRecebido($id, $currentUserId);
            $this->pdo->commit();

            return ['message' => 'Entrega confirmada com sucesso', 'novo_estoque' => $novoEstoque];
        } catch (\RuntimeException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw new \RuntimeException('Não foi possível confirmar a entrega do pedido de compra.', 400);
        }
    }

    public function deleteHeader(int $id): array
    {
        if (!$this->hasComprasCabecalhoTable() || !$this->hasComprasColumn('compra_cabecalho_id')) {
            throw new \RuntimeException('Exclusão por cabeçalho não disponível neste ambiente.', 404);
        }

        $currentStatus = $this->currentHeaderStatusCompra($id);
        if ($currentStatus === null) {
            throw new \RuntimeException('Pedido de compra não encontrado', 404);
        }

        if ($currentStatus === 'RECEBIDA') {
            throw new \RuntimeException('Não é permitido excluir pedido de compra recebido', 409);
        }

        try {
            $this->pdo->beginTransaction();

            $delItems = $this->pdo->prepare('DELETE FROM compras WHERE compra_cabecalho_id = :id');
            $delItems->execute(['id' => $id]);

            $delHeader = $this->pdo->prepare('DELETE FROM compras_cabecalho WHERE id = :id');
            $delHeader->execute(['id' => $id]);

            $this->pdo->commit();
            return ['message' => 'Compra excluída com sucesso'];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw new \RuntimeException('Falha ao excluir compra', 500);
        }
    }

    public function confirmItemReceiveById(int $id): array
    {
        $purchaseService = new PurchaseService(
            new PurchaseRepository($this->pdo),
            new ProductRepository($this->pdo)
        );

        try {
            return $purchaseService->receivePurchase($id);
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'Compra não encontrada') {
                throw new \RuntimeException('Compra não encontrada', 404);
            }
            throw $e;
        }
    }

    public function confirmItemDelivery(int $id, ?int $currentUserId): array
    {
        try {
            $this->pdo->beginTransaction();
            $res = $this->confirmItemReceiveById($id);

            if ($this->hasComprasColumn('compra_cabecalho_id') && $this->hasComprasCabecalhoTable()) {
                $hs = $this->pdo->prepare('SELECT compra_cabecalho_id FROM compras WHERE id = :id LIMIT 1');
                $hs->execute(['id' => $id]);
                $headerRef = $hs->fetch(PDO::FETCH_ASSOC);
                $hid = isset($headerRef['compra_cabecalho_id']) ? (int)$headerRef['compra_cabecalho_id'] : 0;
                if ($hid > 0) {
                    $pendingStmt = $this->pdo->prepare('SELECT COUNT(*) FROM compras WHERE compra_cabecalho_id = :id AND status <> :status');
                    $pendingStmt->execute(['id' => $hid, 'status' => 'RECEBIDA']);
                    $pending = (int)$pendingStmt->fetchColumn();
                    if ($pending === 0) {
                        $this->marcarCabecalhoComoRecebido($hid, $currentUserId);
                    }
                }
            }

            $this->pdo->commit();
            return ['message' => 'Entrega confirmada com sucesso', 'novo_estoque' => $res['novo_estoque'] ?? null];
        } catch (\RuntimeException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw new \RuntimeException('Não foi possível confirmar a entrega do item de compra.', 400);
        }
    }

    private function comprasColumns(): array
    {
        if ($this->comprasColumnsCache !== null) {
            return $this->comprasColumnsCache;
        }

        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $stmt = $this->pdo->query('PRAGMA table_info(compras)');
            $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->comprasColumnsCache = array_values(array_filter(array_map(
                static fn(array $row) => $row['name'] ?? null,
                $cols
            )));

            return $this->comprasColumnsCache;
        }

        $stmt = $this->pdo->query('SHOW COLUMNS FROM compras');
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->comprasColumnsCache = array_values(array_filter(array_map(
            static fn(array $row) => $row['Field'] ?? null,
            $cols
        )));

        return $this->comprasColumnsCache;
    }

    private function hasComprasColumn(string $column): bool
    {
        return in_array($column, $this->comprasColumns(), true);
    }

    private function comprasCabecalhoColumns(): array
    {
        if ($this->comprasCabecalhoColumnsCache !== null) {
            return $this->comprasCabecalhoColumnsCache;
        }

        if (!$this->hasComprasCabecalhoTable()) {
            $this->comprasCabecalhoColumnsCache = [];
            return $this->comprasCabecalhoColumnsCache;
        }

        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $stmt = $this->pdo->query('PRAGMA table_info(compras_cabecalho)');
            $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->comprasCabecalhoColumnsCache = array_values(array_filter(array_map(
                static fn(array $row) => $row['name'] ?? null,
                $cols
            )));
        } else {
            $stmt = $this->pdo->query('SHOW COLUMNS FROM compras_cabecalho');
            $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->comprasCabecalhoColumnsCache = array_values(array_filter(array_map(
                static fn(array $row) => $row['Field'] ?? null,
                $cols
            )));
        }

        return $this->comprasCabecalhoColumnsCache;
    }

    private function hasComprasCabecalhoColumn(string $column): bool
    {
        return in_array($column, $this->comprasCabecalhoColumns(), true);
    }

    private function hasComprasCabecalhoTable(): bool
    {
        if ($this->hasComprasCabecalhoCache !== null) {
            return $this->hasComprasCabecalhoCache;
        }

        try {
            $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $stmt = $this->pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name='compras_cabecalho' LIMIT 1");
                $stmt->execute();
                $this->hasComprasCabecalhoCache = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
                return $this->hasComprasCabecalhoCache;
            }

            $stmt = $this->pdo->query("SHOW TABLES LIKE 'compras_cabecalho'");
            $this->hasComprasCabecalhoCache = (bool)$stmt->fetch(PDO::FETCH_NUM);
            return $this->hasComprasCabecalhoCache;
        } catch (\Throwable $e) {
            $this->hasComprasCabecalhoCache = false;
            return false;
        }
    }

    private function hasStatusCompraTable(): bool
    {
        if ($this->hasStatusCompraCache !== null) {
            return $this->hasStatusCompraCache;
        }

        try {
            $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $stmt = $this->pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name='status_compra' LIMIT 1");
                $stmt->execute();
                $this->hasStatusCompraCache = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
                return $this->hasStatusCompraCache;
            }

            $stmt = $this->pdo->query("SHOW TABLES LIKE 'status_compra'");
            $this->hasStatusCompraCache = (bool)$stmt->fetch(PDO::FETCH_NUM);
            return $this->hasStatusCompraCache;
        } catch (\Throwable $e) {
            $this->hasStatusCompraCache = false;
            return false;
        }
    }

    private function hasHistoricoStatusCompraTable(): bool
    {
        if ($this->hasHistoricoStatusCompraCache !== null) {
            return $this->hasHistoricoStatusCompraCache;
        }

        try {
            $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $stmt = $this->pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name='historico_status_compra' LIMIT 1");
                $stmt->execute();
                $this->hasHistoricoStatusCompraCache = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
                return $this->hasHistoricoStatusCompraCache;
            }

            $stmt = $this->pdo->query("SHOW TABLES LIKE 'historico_status_compra'");
            $this->hasHistoricoStatusCompraCache = (bool)$stmt->fetch(PDO::FETCH_NUM);
            return $this->hasHistoricoStatusCompraCache;
        } catch (\Throwable $e) {
            $this->hasHistoricoStatusCompraCache = false;
            return false;
        }
    }

    private function historicoStatusCompraColumns(): array
    {
        if ($this->historicoStatusCompraColumnsCache !== null) {
            return $this->historicoStatusCompraColumnsCache;
        }

        if (!$this->hasHistoricoStatusCompraTable()) {
            $this->historicoStatusCompraColumnsCache = [];
            return $this->historicoStatusCompraColumnsCache;
        }

        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $stmt = $this->pdo->query('PRAGMA table_info(historico_status_compra)');
            $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->historicoStatusCompraColumnsCache = array_values(array_filter(array_map(
                static fn(array $row) => $row['name'] ?? null,
                $cols
            )));
        } else {
            $stmt = $this->pdo->query('SHOW COLUMNS FROM historico_status_compra');
            $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->historicoStatusCompraColumnsCache = array_values(array_filter(array_map(
                static fn(array $row) => $row['Field'] ?? null,
                $cols
            )));
        }

        return $this->historicoStatusCompraColumnsCache;
    }

    private function hasHistoricoStatusCompraColumn(string $column): bool
    {
        return in_array($column, $this->historicoStatusCompraColumns(), true);
    }

    private function normalizeStatusCompraLabel(?string $status): string
    {
        $value = strtoupper(trim((string)$status));
        return $value === 'RECEBIDA' ? 'RECEBIDA' : 'AGUARDANDO';
    }

    private function statusCompraIdByNome(string $nome): ?int
    {
        if (!$this->hasStatusCompraTable()) {
            return null;
        }

        $stmt = $this->pdo->prepare('SELECT id FROM status_compra WHERE UPPER(nome) = :nome LIMIT 1');
        $stmt->execute(['nome' => strtoupper($nome)]);
        $id = $stmt->fetchColumn();

        return $id !== false ? (int)$id : null;
    }

    private function currentHeaderStatusCompra(int $headerId): ?string
    {
        if (!$this->hasComprasCabecalhoTable()) {
            return null;
        }

        $stmt = $this->pdo->prepare('SELECT status FROM compras_cabecalho WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $headerId]);
        $status = $stmt->fetchColumn();

        if ($status === false) {
            return null;
        }

        return $this->normalizeStatusCompraLabel((string)$status);
    }

    private function canTransitionCompra(string $from, string $to): bool
    {
        if ($from === $to) {
            return true;
        }

        return $from === 'AGUARDANDO' && $to === 'RECEBIDA';
    }

    private function buildPurchaseSnapshot(int $headerId): ?string
    {
        try {
            $headerStmt = $this->pdo->prepare('SELECT id, valor_total, status FROM compras_cabecalho WHERE id = :id LIMIT 1');
            $headerStmt->execute(['id' => $headerId]);
            $header = $headerStmt->fetch(PDO::FETCH_ASSOC);
            if (!$header) {
                return null;
            }

            $itemsStmt = $this->pdo->prepare(
                'SELECT c.id, c.produto_id, c.quantidade, c.valor_unitario, c.comissao_total, c.status, p.estoque_atual, p.custo_medio
                 FROM compras c
                 LEFT JOIN produtos p ON p.id = c.produto_id
                 WHERE c.compra_cabecalho_id = :id
                 ORDER BY c.id ASC'
            );
            $itemsStmt->execute(['id' => $headerId]);
            $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

            return json_encode([
                'header' => [
                    'id' => (int)$header['id'],
                    'valor_total' => (float)($header['valor_total'] ?? 0),
                    'status' => $this->normalizeStatusCompraLabel((string)($header['status'] ?? '')),
                ],
                'items' => $items,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function registrarHistoricoStatusCompra(int $headerId, ?int $usuarioId, int $statusId, ?string $snapshotJson = null): void
    {
        if (!$this->hasHistoricoStatusCompraTable()) {
            return;
        }

        if ($this->hasHistoricoStatusCompraColumn('snapshot_json')) {
            $stmt = $this->pdo->prepare(
                'INSERT INTO historico_status_compra (compra_cabecalho_id, usuario_id, id_statuscompra, snapshot_json, confirmado_em)
                   VALUES (:compra_cabecalho_id, :usuario_id, :id_statuscompra, :snapshot_json, CURRENT_TIMESTAMP)'
            );
        } else {
            $stmt = $this->pdo->prepare(
                'INSERT INTO historico_status_compra (compra_cabecalho_id, usuario_id, id_statuscompra, confirmado_em)
                   VALUES (:compra_cabecalho_id, :usuario_id, :id_statuscompra, CURRENT_TIMESTAMP)'
            );
        }
        $stmt->bindValue('compra_cabecalho_id', $headerId, PDO::PARAM_INT);
        if ($usuarioId !== null) {
            $stmt->bindValue('usuario_id', $usuarioId, PDO::PARAM_INT);
        } else {
            $stmt->bindValue('usuario_id', null, PDO::PARAM_NULL);
        }
        $stmt->bindValue('id_statuscompra', $statusId, PDO::PARAM_INT);
        if ($this->hasHistoricoStatusCompraColumn('snapshot_json')) {
            if ($snapshotJson !== null) {
                $stmt->bindValue('snapshot_json', $snapshotJson, PDO::PARAM_STR);
            } else {
                $stmt->bindValue('snapshot_json', null, PDO::PARAM_NULL);
            }
        }
        $stmt->execute();
    }

    private function marcarCabecalhoComoRecebido(int $headerId, ?int $usuarioId): void
    {
        $statusId = $this->statusCompraIdByNome('RECEBIDA') ?? 2;

        if ($this->hasComprasCabecalhoColumn('id_statuscompra')) {
            $up = $this->pdo->prepare('UPDATE compras_cabecalho SET status = :status, id_statuscompra = :id_statuscompra WHERE id = :id');
            $up->execute([
                'status' => 'RECEBIDA',
                'id_statuscompra' => $statusId,
                'id' => $headerId,
            ]);
        } else {
            $up = $this->pdo->prepare('UPDATE compras_cabecalho SET status = :status WHERE id = :id');
            $up->execute(['status' => 'RECEBIDA', 'id' => $headerId]);
        }

        $snapshot = $this->buildPurchaseSnapshot($headerId);
        $this->registrarHistoricoStatusCompra($headerId, $usuarioId, $statusId, $snapshot);
    }
}