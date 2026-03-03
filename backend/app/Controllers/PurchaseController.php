<?php
namespace App\Controllers;

use PDO;
use App\Services\CommissionService;
use App\Helpers\Request;

class PurchaseController
{
    private ?array $comprasColumnsCache = null;
    private ?bool $hasComprasCabecalhoCache = null;

    public function __construct(private PDO $pdo)
    {
    }

    private function comprasColumns(): array
    {
        if ($this->comprasColumnsCache !== null) {
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

    public function index(): void
    {
        if (!$this->hasComprasCabecalhoTable() || !$this->hasComprasColumn('compra_cabecalho_id')) {
            http_response_code(500);
            echo json_encode(['error' => 'Estrutura de compras por cabeçalho não disponível. Execute as migrations.']);
            return;
        }

        $this->indexHeaders();
    }

    private function indexHeaders(): void
    {
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $per = isset($_GET['per_page']) ? max(1, (int)$_GET['per_page']) : 25;
        $q = isset($_GET['q']) ? trim($_GET['q']) : '';
        $offset = ($page - 1) * $per;

        $where = '';
        $params = [];
        if ($q !== '') {
            $where = "WHERE f.razao_social LIKE :q OR cl.nome LIKE :q OR m.nome LIKE :q OR EXISTS (
                SELECT 1 FROM compras cc
                LEFT JOIN produtos pp ON pp.id = cc.produto_id
                WHERE cc.compra_cabecalho_id = h.id AND pp.nome LIKE :q
            )";
            $params[':q'] = "%{$q}%";
        }

        $countSql = "SELECT COUNT(*)
                     FROM compras_cabecalho h
                     LEFT JOIN fornecedores f ON f.id = h.fornecedor_id
                     LEFT JOIN clientes cl ON cl.id = h.cliente_id
                     LEFT JOIN motoristas m ON m.id = h.motorista_id
                     {$where}";
        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $sql = "SELECT
                    h.id,
                    h.tipo_operacao,
                    f.razao_social AS fornecedor,
                    cl.nome AS cliente,
                    m.nome AS motorista,
                    IFNULL(COUNT(c.id), 0) AS itens_count,
                    IFNULL(SUM(c.quantidade * c.valor_unitario), 0) AS valor_total,
                    h.status,
                    h.data_envio_prevista,
                    h.data_entrega_prevista
                FROM compras_cabecalho h
                LEFT JOIN fornecedores f ON f.id = h.fornecedor_id
                LEFT JOIN clientes cl ON cl.id = h.cliente_id
                LEFT JOIN motoristas m ON m.id = h.motorista_id
                LEFT JOIN compras c ON c.compra_cabecalho_id = h.id
                {$where}
                GROUP BY h.id, h.tipo_operacao, f.razao_social, cl.nome, m.nome, h.status, h.data_envio_prevista, h.data_entrega_prevista
                ORDER BY h.id DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $per, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['items' => $items, 'total' => $total]);
    }

    public function create(): void
    {
        $data = Request::body();

        if (!$this->hasComprasCabecalhoTable() || !$this->hasComprasColumn('compra_cabecalho_id')) {
            http_response_code(500);
            echo json_encode(['error' => 'Estrutura de compras por cabeçalho não disponível. Execute as migrations.']);
            return;
        }

        if (empty($data['items']) || !is_array($data['items'])) {
            http_response_code(400);
            echo json_encode(['error' => 'items obrigatório']);
            return;
        }

        $this->createWithHeader($data);
    }

    private function createWithHeader(array $data): void
    {
        if (empty($data['fornecedor_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'fornecedor_id obrigatório']);
            return;
        }

        $tipoOperacao = ($data['tipo_operacao'] ?? (($data['tipo'] ?? 'revenda') === 'venda' ? 'venda' : 'revenda'));
        if ($tipoOperacao === 'venda' && empty($data['cliente_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Selecione um cliente para compras do tipo venda']);
            return;
        }
        if ($tipoOperacao === 'venda' && empty($data['motorista_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Selecione um motorista para compras do tipo venda']);
            return;
        }

        $validItems = [];
        $valorTotalCabecalho = 0.0;
        foreach (($data['items'] ?? []) as $item) {
            if (empty($item['produto_id'])) {
                continue;
            }
            $qtd = (float)($item['quantidade'] ?? 0);
            $vu = (float)($item['valor_unitario'] ?? 0);
            if ($qtd <= 0 || $vu <= 0) {
                continue;
            }
            $validItems[] = [
                'produto_id' => (int)$item['produto_id'],
                'quantidade' => $qtd,
                'valor_unitario' => $vu,
            ];
            $valorTotalCabecalho += $qtd * $vu;
        }

        if (empty($validItems)) {
            http_response_code(400);
            echo json_encode(['error' => 'Adicione ao menos um item válido com produto_id, quantidade e valor_unitario']);
            return;
        }

        $status = $data['status'] ?? 'NEGOCIADA';
        $dataEnvio = !empty($data['data_envio_prevista']) ? $data['data_envio_prevista'] : null;
        $dataEntrega = !empty($data['data_entrega_prevista']) ? $data['data_entrega_prevista'] : null;

        $cabecalhoId = $this->createHeader(
            (int)$data['fornecedor_id'],
            $tipoOperacao,
            !empty($data['cliente_id']) ? (int)$data['cliente_id'] : null,
            !empty($data['motorista_id']) ? (int)$data['motorista_id'] : null,
            $valorTotalCabecalho,
            $dataEnvio,
            $dataEntrega,
            $status
        );

        $createdIds = [];
        foreach ($validItems as $item) {
            $calcs = CommissionService::calculate(
                $item['quantidade'],
                $item['valor_unitario'],
                $data['tipo_comissao'] ?? null,
                isset($data['valor_comissao']) ? (float)$data['valor_comissao'] : null,
                isset($data['extra_por_saco']) ? (float)$data['extra_por_saco'] : 0.0
            );

            $possible = [
                'compra_cabecalho_id' => $cabecalhoId,
                'fornecedor_id' => (int)$data['fornecedor_id'],
                'produto_id' => $item['produto_id'],
                'motorista_id' => !empty($data['motorista_id']) ? (int)$data['motorista_id'] : null,
                'tipo_operacao' => $tipoOperacao,
                'cliente_id' => !empty($data['cliente_id']) ? (int)$data['cliente_id'] : null,
                'quantidade' => $item['quantidade'],
                'valor_unitario' => $item['valor_unitario'],
                'tipo_comissao' => $data['tipo_comissao'] ?? null,
                'valor_comissao' => isset($data['valor_comissao']) ? (float)$data['valor_comissao'] : null,
                'extra_por_saco' => isset($data['extra_por_saco']) ? (float)$data['extra_por_saco'] : 0.0,
                'custo_total' => $calcs['valor_total'],
                'comissao_total' => $calcs['comissao_total'],
                'custo_final_real' => $calcs['custo_final_real'],
                'status' => $status,
                'data_envio_prevista' => $dataEnvio,
                'data_entrega_prevista' => $dataEntrega,
            ];

            $columns = array_values(array_filter(array_keys($possible), fn(string $column) => $this->hasComprasColumn($column)));
            $valuesSql = [];
            $params = [];

            foreach ($columns as $column) {
                $valuesSql[] = ':' . $column;
                $params[$column] = $possible[$column];
            }

            if ($this->hasComprasColumn('data_compra')) {
                $columns[] = 'data_compra';
                $valuesSql[] = 'NOW()';
            }

            try {
                $stmt = $this->pdo->prepare('INSERT INTO compras (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $valuesSql) . ')');
                $stmt->execute($params);
            } catch (\PDOException $e) {
                $mysqlCode = isset($e->errorInfo[1]) ? (int)$e->errorInfo[1] : 0;
                if ($mysqlCode === 1452) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Fornecedor, produto, cliente ou motorista inválido.']);
                    return;
                }

                http_response_code(500);
                echo json_encode(['error' => 'Falha ao salvar itens da compra.']);
                return;
            }

            $createdIds[] = (int)$this->pdo->lastInsertId();
        }

        http_response_code(201);
        echo json_encode([
            'id' => $createdIds[0] ?? null,
            'ids' => $createdIds,
            'cabecalho_id' => $cabecalhoId,
            'status' => $status,
        ]);
    }

    private function createHeader(
        int $fornecedorId,
        string $tipoOperacao,
        ?int $clienteId,
        ?int $motoristaId,
        float $valorTotal,
        ?string $dataEnvio,
        ?string $dataEntrega,
        string $status
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO compras_cabecalho (tipo_operacao, fornecedor_id, cliente_id, motorista_id, valor_total, data_envio_prevista, data_entrega_prevista, status)
             VALUES (:tipo_operacao, :fornecedor_id, :cliente_id, :motorista_id, :valor_total, :data_envio_prevista, :data_entrega_prevista, :status)'
        );

        $stmt->execute([
            'tipo_operacao' => $tipoOperacao,
            'fornecedor_id' => $fornecedorId,
            'cliente_id' => $clienteId,
            'motorista_id' => $motoristaId,
            'valor_total' => $valorTotal,
            'data_envio_prevista' => $dataEnvio,
            'data_entrega_prevista' => $dataEntrega,
            'status' => $status,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function showHeader(int $id): void
    {
        if (!$this->hasComprasCabecalhoTable() || !$this->hasComprasColumn('compra_cabecalho_id')) {
            http_response_code(500);
            echo json_encode(['error' => 'Estrutura de compras por cabeçalho não disponível. Execute as migrations.']);
            return;
        }

        $headerStmt = $this->pdo->prepare(
                'SELECT h.id, h.tipo_operacao, h.fornecedor_id, h.cliente_id, h.motorista_id, h.valor_total, h.status, h.data_envio_prevista, h.data_entrega_prevista,
                    f.razao_social AS fornecedor, cl.nome AS cliente, m.nome AS motorista
             FROM compras_cabecalho h
             LEFT JOIN fornecedores f ON f.id = h.fornecedor_id
             LEFT JOIN clientes cl ON cl.id = h.cliente_id
             LEFT JOIN motoristas m ON m.id = h.motorista_id
             WHERE h.id = :id
             LIMIT 1'
        );
        $headerStmt->execute(['id' => $id]);
        $header = $headerStmt->fetch(PDO::FETCH_ASSOC);

        if (!$header) {
            http_response_code(404);
            echo json_encode(['error' => 'Pedido de compra não encontrado']);
            return;
        }

        $itemsStmt = $this->pdo->prepare(
            'SELECT c.id, p.nome AS produto, c.quantidade, c.valor_unitario, c.status, c.data_compra
             FROM compras c
             LEFT JOIN produtos p ON p.id = c.produto_id
             WHERE c.compra_cabecalho_id = :id
             ORDER BY c.id ASC'
        );
        $itemsStmt->execute(['id' => $id]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'header' => $header,
            'items' => $items,
        ]);
    }

    public function updateHeader(int $id): void
    {
        if (!$this->hasComprasCabecalhoTable() || !$this->hasComprasColumn('compra_cabecalho_id')) {
            http_response_code(404);
            echo json_encode(['error' => 'Edição por cabeçalho não disponível neste ambiente.']);
            return;
        }

        $data = Request::body();
        $allowed = ['tipo_operacao', 'fornecedor_id', 'cliente_id', 'motorista_id', 'data_envio_prevista', 'data_entrega_prevista', 'status'];
        $set = [];
        $params = ['id' => $id];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $set[] = $field . ' = :' . $field;
                $params[$field] = $data[$field];
            }
        }

        if (empty($set)) {
            http_response_code(400);
            echo json_encode(['error' => 'Nenhum campo válido para atualização']);
            return;
        }

        $stmt = $this->pdo->prepare('UPDATE compras_cabecalho SET ' . implode(', ', $set) . ' WHERE id = :id');
        $stmt->execute($params);

        $syncParts = [];
        $syncParams = ['id' => $id];
        foreach (['tipo_operacao', 'fornecedor_id', 'cliente_id', 'motorista_id', 'data_envio_prevista', 'data_entrega_prevista', 'status'] as $field) {
            if (array_key_exists($field, $data) && $this->hasComprasColumn($field)) {
                $syncParts[] = 'c.' . $field . ' = :s_' . $field;
                $syncParams['s_' . $field] = $data[$field];
            }
        }

        if (!empty($syncParts)) {
            $sqlSync = 'UPDATE compras c SET ' . implode(', ', $syncParts) . ' WHERE c.compra_cabecalho_id = :id';
            $syncStmt = $this->pdo->prepare($sqlSync);
            $syncStmt->execute($syncParams);
        }

        echo json_encode(['message' => 'Cabeçalho de compra atualizado']);
    }

    public function updateItem(int $id): void
    {
        $data = Request::body();
        $allowed = ['tipo_operacao', 'fornecedor_id', 'cliente_id', 'motorista_id', 'produto_id', 'quantidade', 'valor_unitario', 'data_envio_prevista', 'data_entrega_prevista', 'status'];
        $set = [];
        $params = ['id' => $id];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data) && $this->hasComprasColumn($field)) {
                $set[] = $field . ' = :' . $field;
                $params[$field] = $data[$field];
            }
        }

        if (empty($set)) {
            http_response_code(400);
            echo json_encode(['error' => 'Nenhum campo válido para atualização']);
            return;
        }

        $stmt = $this->pdo->prepare('UPDATE compras SET ' . implode(', ', $set) . ' WHERE id = :id');
        $stmt->execute($params);

        if ($this->hasComprasColumn('compra_cabecalho_id')) {
            $hStmt = $this->pdo->prepare('SELECT compra_cabecalho_id FROM compras WHERE id = :id LIMIT 1');
            $hStmt->execute(['id' => $id]);
            $row = $hStmt->fetch(PDO::FETCH_ASSOC);
            $headerId = (int)($row['compra_cabecalho_id'] ?? 0);
            if ($headerId > 0 && $this->hasComprasCabecalhoTable()) {
                $this->recalculateHeaderTotal($headerId);
            }
        }

        echo json_encode(['message' => 'Item de compra atualizado']);
    }

    public function deleteHeader(int $id): void
    {
        if (!$this->hasComprasCabecalhoTable() || !$this->hasComprasColumn('compra_cabecalho_id')) {
            http_response_code(404);
            echo json_encode(['error' => 'Exclusão por cabeçalho não disponível neste ambiente.']);
            return;
        }

        try {
            $this->pdo->beginTransaction();
            $delItems = $this->pdo->prepare('DELETE FROM compras WHERE compra_cabecalho_id = :id');
            $delItems->execute(['id' => $id]);

            $delHeader = $this->pdo->prepare('DELETE FROM compras_cabecalho WHERE id = :id');
            $delHeader->execute(['id' => $id]);

            $this->pdo->commit();
            echo json_encode(['message' => 'Compra excluída com sucesso']);
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            http_response_code(500);
            echo json_encode(['error' => 'Falha ao excluir compra']);
        }
    }

    public function deleteItem(int $id): void
    {
        $headerId = 0;
        if ($this->hasComprasColumn('compra_cabecalho_id')) {
            $hStmt = $this->pdo->prepare('SELECT compra_cabecalho_id FROM compras WHERE id = :id LIMIT 1');
            $hStmt->execute(['id' => $id]);
            $row = $hStmt->fetch(PDO::FETCH_ASSOC);
            $headerId = (int)($row['compra_cabecalho_id'] ?? 0);
        }

        $stmt = $this->pdo->prepare('DELETE FROM compras WHERE id = :id');
        $stmt->execute(['id' => $id]);

        if ($headerId > 0 && $this->hasComprasCabecalhoTable()) {
            $countStmt = $this->pdo->prepare('SELECT COUNT(*) FROM compras WHERE compra_cabecalho_id = :id');
            $countStmt->execute(['id' => $headerId]);
            $left = (int)$countStmt->fetchColumn();
            if ($left === 0) {
                $delHeader = $this->pdo->prepare('DELETE FROM compras_cabecalho WHERE id = :id');
                $delHeader->execute(['id' => $headerId]);
            } else {
                $this->recalculateHeaderTotal($headerId);
            }
        }

        echo json_encode(['message' => 'Item de compra excluído']);
    }

    public function confirmHeaderDelivery(int $id): void
    {
        if (!$this->hasComprasCabecalhoTable() || !$this->hasComprasColumn('compra_cabecalho_id')) {
            http_response_code(404);
            echo json_encode(['error' => 'Confirmação por cabeçalho não disponível neste ambiente.']);
            return;
        }

        $stmt = $this->pdo->prepare('SELECT id FROM compras WHERE compra_cabecalho_id = :id AND status <> :status');
        $stmt->execute(['id' => $id, 'status' => 'RECEBIDA']);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$rows) {
            echo json_encode(['message' => 'Compra já confirmada como recebida']);
            return;
        }

        $novoEstoque = null;
        foreach ($rows as $row) {
            $res = $this->confirmItemReceiveById((int)$row['id']);
            $novoEstoque = $res['novo_estoque'] ?? $novoEstoque;
        }

        $up = $this->pdo->prepare('UPDATE compras_cabecalho SET status = :status WHERE id = :id');
        $up->execute(['status' => 'RECEBIDA', 'id' => $id]);

        echo json_encode(['message' => 'Entrega confirmada com sucesso', 'novo_estoque' => $novoEstoque]);
    }

    public function confirmItemDelivery(int $id): void
    {
        try {
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
                        $up = $this->pdo->prepare('UPDATE compras_cabecalho SET status = :status WHERE id = :id');
                        $up->execute(['status' => 'RECEBIDA', 'id' => $hid]);
                    }
                }
            }

            echo json_encode(['message' => 'Entrega confirmada com sucesso', 'novo_estoque' => $res['novo_estoque'] ?? null]);
        } catch (\RuntimeException $e) {
            $code = (int)$e->getCode();
            http_response_code($code >= 400 ? $code : 400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    private function recalculateHeaderTotal(int $headerId): void
    {
        if (!$this->hasComprasCabecalhoTable()) {
            return;
        }

        $sumStmt = $this->pdo->prepare('SELECT IFNULL(SUM(quantidade * valor_unitario), 0) FROM compras WHERE compra_cabecalho_id = :id');
        $sumStmt->execute(['id' => $headerId]);
        $total = (float)$sumStmt->fetchColumn();

        $up = $this->pdo->prepare('UPDATE compras_cabecalho SET valor_total = :valor_total WHERE id = :id');
        $up->execute(['valor_total' => $total, 'id' => $headerId]);
    }

    private function confirmItemReceiveById(int $id): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM compras WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $compra = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$compra) {
            throw new \RuntimeException('Compra não encontrada', 404);
        }

        if (($compra['status'] ?? '') === 'RECEBIDA') {
            return ['message' => 'Compra já recebida'];
        }

        $u = $this->pdo->prepare('UPDATE compras SET status = :status WHERE id = :id');
        $u->execute(['status' => 'RECEBIDA', 'id' => $id]);

        $produtoId = (int)$compra['produto_id'];
        $quantidade = (float)$compra['quantidade'];
        $valorUnitario = (float)$compra['valor_unitario'];
        $comissaoTotal = (float)($compra['comissao_total'] ?? 0);

        $s = $this->pdo->prepare('SELECT estoque_atual, custo_medio FROM produtos WHERE id = :id LIMIT 1');
        $s->execute(['id' => $produtoId]);
        $prod = $s->fetch(PDO::FETCH_ASSOC);

        $oldQty = $prod ? (float)$prod['estoque_atual'] : 0.0;
        $oldCost = $prod ? (float)$prod['custo_medio'] : 0.0;

        $valorTotalCompra = ($quantidade * $valorUnitario) + $comissaoTotal;
        $newQty = $oldQty + $quantidade;
        $newCost = 0.0;
        if ($newQty > 0) {
            $newCost = (($oldQty * $oldCost) + $valorTotalCompra) / $newQty;
        }

        $up = $this->pdo->prepare('UPDATE produtos SET estoque_atual = :estoque, custo_medio = :custo WHERE id = :id');
        $up->execute(['estoque' => $newQty, 'custo' => $newCost, 'id' => $produtoId]);

        return [
            'produto_id' => $produtoId,
            'novo_estoque' => $newQty,
            'novo_custo_medio' => $newCost,
        ];
    }

    public function receive(): void
    {
        $data = Request::body();
        $id = $data['compra_id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'compra_id obrigatório']);
            return;
        }

        try {
            $res = $this->confirmItemReceiveById((int)$id);
            echo json_encode(['message' => 'Compra marcada como RECEBIDA e estoque atualizado'] + $res);
        } catch (\RuntimeException $e) {
            $code = (int)$e->getCode();
            http_response_code($code >= 400 ? $code : 400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
