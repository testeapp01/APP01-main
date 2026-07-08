<?php
namespace App\Repositories;

use PDO;

class PurchaseRepository
{
    private array $tableColumnsCache = [];

    public function __construct(private PDO $pdo)
    {
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM compras WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO compras (fornecedor_id, produto_id, motorista_id, quantidade, valor_unitario, tipo_comissao, valor_comissao, extra_por_saco, custo_total, comissao_total, custo_final_real, status, data_compra) VALUES (:fornecedor_id, :produto_id, :motorista_id, :quantidade, :valor_unitario, :tipo_comissao, :valor_comissao, :extra_por_saco, :custo_total, :comissao_total, :custo_final_real, :status, CURRENT_TIMESTAMP)');
        $stmt->execute([
            'fornecedor_id' => $data['fornecedor_id'],
            'produto_id' => $data['produto_id'],
            'motorista_id' => $data['motorista_id'],
            'quantidade' => $data['quantidade'],
            'valor_unitario' => $data['valor_unitario'],
            'tipo_comissao' => $data['tipo_comissao'],
            'valor_comissao' => $data['valor_comissao'],
            'extra_por_saco' => $data['extra_por_saco'],
            'custo_total' => $data['custo_total'],
            'comissao_total' => $data['comissao_total'],
            'custo_final_real' => $data['custo_final_real'],
            'status' => $data['status'] ?? 'AGUARDANDO',
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->pdo->prepare('UPDATE compras SET status = :status WHERE id = :id');
        return $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public function countHeaders(string $query = ''): int
    {
        [$where, $params] = $this->buildHeaderSearchFilter($query);

        $sql = "SELECT COUNT(*)
                FROM compras_cabecalho h
                LEFT JOIN fornecedores f ON f.id = h.fornecedor_id
                LEFT JOIN clientes cl ON cl.id = h.cliente_id
                LEFT JOIN motoristas m ON m.id = h.motorista_id
                {$where}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function findHeaderList(string $query, int $limit, int $offset): array
    {
        [$where, $params] = $this->buildHeaderSearchFilter($query);

        $statusSelect = $this->headerStatusSelect();
        $statusJoin = $this->headerStatusJoin();

        $sql = "SELECT
                    h.id,
                    h.tipo_operacao,
                    f.razao_social AS fornecedor,
                    cl.nome AS cliente,
                    m.nome AS motorista,
                    IFNULL(COUNT(c.id), 0) AS itens_count,
                    IFNULL(SUM(c.quantidade * c.valor_unitario), 0) AS valor_total,
                    {$statusSelect} AS status,
                    h.data_envio_prevista,
                    h.data_entrega_prevista
                FROM compras_cabecalho h
                LEFT JOIN fornecedores f ON f.id = h.fornecedor_id
                LEFT JOIN clientes cl ON cl.id = h.cliente_id
                LEFT JOIN motoristas m ON m.id = h.motorista_id
                {$statusJoin}
                LEFT JOIN compras c ON c.compra_cabecalho_id = h.id
                {$where}
                GROUP BY h.id, h.tipo_operacao, f.razao_social, cl.nome, m.nome, {$statusSelect}, h.data_envio_prevista, h.data_entrega_prevista
                ORDER BY h.id DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findHeaderById(int $id): ?array
    {
        if (!$this->hasComprasCabecalhoTable()) {
            return null;
        }

        $createdByColumn = $this->hasColumn('compras_cabecalho', 'created_by') ? 'h.created_by,' : '';
        $statusSelect = $this->headerStatusSelect();
        $statusJoin = $this->headerStatusJoin();

        $sql = "SELECT
                    h.id,
                    h.tipo_operacao,
                    h.fornecedor_id,
                    h.cliente_id,
                    h.motorista_id,
                    h.valor_total,
                    h.data_envio_prevista,
                    h.data_entrega_prevista,
                    {$createdByColumn}
                    {$statusSelect} AS status,
                    f.razao_social AS fornecedor,
                    cl.nome AS cliente,
                    m.nome AS motorista
                FROM compras_cabecalho h
                LEFT JOIN fornecedores f ON f.id = h.fornecedor_id
                LEFT JOIN clientes cl ON cl.id = h.cliente_id
                LEFT JOIN motoristas m ON m.id = h.motorista_id
                {$statusJoin}
                WHERE h.id = :id
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findItemsByHeaderId(int $headerId): array
    {
        $sql = "SELECT c.id, p.nome AS produto, c.quantidade, c.valor_unitario,
                    CASE WHEN UPPER(IFNULL(c.status, '')) = 'RECEBIDA' THEN 'RECEBIDA' ELSE 'AGUARDANDO' END AS status,
                    c.data_compra
                FROM compras c
                LEFT JOIN produtos p ON p.id = c.produto_id
                WHERE c.compra_cabecalho_id = :id
                ORDER BY c.id ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $headerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findHeaderHistoryById(int $headerId): array
    {
        if (!$this->hasTable('historico_status_compra')) {
            return [];
        }

        $historyJoin = $this->hasStatusCompraTable() ? 'LEFT JOIN status_compra sc ON sc.id = hsc.id_statuscompra' : '';
        $historyStatusSelect = $this->hasStatusCompraTable()
            ? "CASE WHEN sc.nome IS NOT NULL THEN UPPER(sc.nome) WHEN hsc.id_statuscompra = 2 THEN 'RECEBIDA' ELSE 'AGUARDANDO' END"
            : "CASE WHEN hsc.id_statuscompra = 2 THEN 'RECEBIDA' ELSE 'AGUARDANDO' END";

        $sql = "SELECT hsc.usuario_id, hsc.id_statuscompra,
                       {$historyStatusSelect} AS status,
                       u.name AS usuario_nome,
                       hsc.confirmado_em
                FROM historico_status_compra hsc
                {$historyJoin}
                LEFT JOIN users u ON u.id = hsc.usuario_id
                WHERE hsc.compra_cabecalho_id = :id
                ORDER BY hsc.confirmado_em DESC, hsc.id DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $headerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getHeaderStatus(int $id): ?string
    {
        if (!$this->hasComprasCabecalhoTable()) {
            return null;
        }

        $statusSelect = $this->headerStatusSelect();
        $statusJoin = $this->headerStatusJoin();

        $sql = "SELECT {$statusSelect} AS status FROM compras_cabecalho h {$statusJoin} WHERE h.id = :id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $status = $stmt->fetchColumn();
        return $status !== false ? (string)$status : null;
    }

    public function getStatusCompraIdByNome(string $nome): ?int
    {
        if (!$this->hasStatusCompraTable()) {
            return null;
        }

        $stmt = $this->pdo->prepare('SELECT id FROM status_compra WHERE UPPER(nome) = :nome LIMIT 1');
        $stmt->execute(['nome' => strtoupper($nome)]);
        $id = $stmt->fetchColumn();
        return $id !== false ? (int)$id : null;
    }

    public function findItemHeaderId(int $itemId): ?int
    {
        $stmt = $this->pdo->prepare('SELECT compra_cabecalho_id FROM compras WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $itemId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return isset($row['compra_cabecalho_id']) ? (int)$row['compra_cabecalho_id'] : null;
    }

    public function updateHeader(int $id, array $data): bool
    {
        $allowed = ['tipo_operacao', 'fornecedor_id', 'cliente_id', 'motorista_id', 'data_envio_prevista', 'data_entrega_prevista', 'status'];
        $set = [];
        $params = ['id' => $id];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data) && $this->hasColumn('compras_cabecalho', $field)) {
                $value = $field === 'status' ? $this->normalizeStatusCompraLabel($data[$field]) : $data[$field];
                $set[] = "{$field} = :{$field}";
                $params[$field] = $value;
                if ($field === 'status') {
                    $data[$field] = $value;
                }
            }
        }

        if (array_key_exists('status', $data) && $this->hasColumn('compras_cabecalho', 'id_statuscompra')) {
            $set[] = 'id_statuscompra = :id_statuscompra';
            $params['id_statuscompra'] = $this->statusCompraIdByNome($data['status'])
                ?? ($data['status'] === 'RECEBIDA' ? 2 : 1);
        }

        if (empty($set)) {
            return false;
        }

        $sql = 'UPDATE compras_cabecalho SET ' . implode(', ', $set) . ' WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function syncHeaderFieldsToItems(int $headerId, array $data): void
    {
        $syncParts = [];
        $syncParams = ['id' => $headerId];

        foreach (['tipo_operacao', 'fornecedor_id', 'cliente_id', 'motorista_id', 'data_envio_prevista', 'data_entrega_prevista', 'status'] as $field) {
            if (array_key_exists($field, $data) && $this->hasColumn('compras', $field)) {
                $syncParts[] = 'c.' . $field . ' = :s_' . $field;
                $syncParams['s_' . $field] = $data[$field];
            }
        }

        if (empty($syncParts)) {
            return;
        }

        $sqlSync = 'UPDATE compras c SET ' . implode(', ', $syncParts) . ' WHERE c.compra_cabecalho_id = :id';
        $stmt = $this->pdo->prepare($sqlSync);
        $stmt->execute($syncParams);
    }

    public function updateItem(int $id, array $data): bool
    {
        $allowed = ['tipo_operacao', 'fornecedor_id', 'cliente_id', 'motorista_id', 'produto_id', 'quantidade', 'valor_unitario', 'data_envio_prevista', 'data_entrega_prevista', 'status'];
        $set = [];
        $params = ['id' => $id];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data) && $this->hasColumn('compras', $field)) {
                $value = $field === 'status' ? $this->normalizeStatusCompraLabel($data[$field]) : $data[$field];
                $set[] = "{$field} = :{$field}";
                $params[$field] = $value;
            }
        }

        if (empty($set)) {
            return false;
        }

        $sql = 'UPDATE compras SET ' . implode(', ', $set) . ' WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function deleteItem(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM compras WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function countItemsByHeaderId(int $headerId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM compras WHERE compra_cabecalho_id = :id');
        $stmt->execute(['id' => $headerId]);
        return (int)$stmt->fetchColumn();
    }

    public function deleteHeaderById(int $headerId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM compras_cabecalho WHERE id = :id');
        return $stmt->execute(['id' => $headerId]);
    }

    public function recalculateHeaderTotal(int $headerId): void
    {
        if (!$this->hasColumn('compras_cabecalho', 'valor_total') || !$this->hasColumn('compras', 'compra_cabecalho_id')) {
            return;
        }

        $sumStmt = $this->pdo->prepare('SELECT IFNULL(SUM(quantidade * valor_unitario), 0) FROM compras WHERE compra_cabecalho_id = :id');
        $sumStmt->execute(['id' => $headerId]);
        $total = (float)$sumStmt->fetchColumn();

        $up = $this->pdo->prepare('UPDATE compras_cabecalho SET valor_total = :valor_total WHERE id = :id');
        $up->execute(['valor_total' => $total, 'id' => $headerId]);
    }

    private function buildHeaderSearchFilter(string $query): array
    {
        if ($query === '') {
            return ['', []];
        }

        return [
            "WHERE f.razao_social LIKE :q OR cl.nome LIKE :q OR m.nome LIKE :q OR EXISTS (
                SELECT 1 FROM compras cc
                LEFT JOIN produtos pp ON pp.id = cc.produto_id
                WHERE cc.compra_cabecalho_id = h.id AND pp.nome LIKE :q
            )",
            [':q' => "%{$query}%"],
        ];
    }

    private function headerStatusSelect(): string
    {
        if ($this->hasStatusCompraTable() && $this->hasColumn('compras_cabecalho', 'id_statuscompra')) {
            return "CASE
                            WHEN sc.nome IS NOT NULL THEN UPPER(sc.nome)
                            WHEN UPPER(IFNULL(h.status, '')) = 'RECEBIDA' THEN 'RECEBIDA'
                            ELSE 'AGUARDANDO'
                        END";
        }

        return "CASE WHEN UPPER(IFNULL(h.status, '')) = 'RECEBIDA' THEN 'RECEBIDA' ELSE 'AGUARDANDO' END";
    }

    private function headerStatusJoin(): string
    {
        if ($this->hasStatusCompraTable() && $this->hasColumn('compras_cabecalho', 'id_statuscompra')) {
            return 'LEFT JOIN status_compra sc ON sc.id = h.id_statuscompra';
        }

        return '';
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

    private function normalizeStatusCompraLabel(?string $status): string
    {
        $value = strtoupper(trim((string)$status));
        return $value === 'RECEBIDA' ? 'RECEBIDA' : 'AGUARDANDO';
    }

    private function hasComprasCabecalhoTable(): bool
    {
        return $this->hasTable('compras_cabecalho');
    }

    private function hasStatusCompraTable(): bool
    {
        return $this->hasTable('status_compra');
    }

    private function hasTable(string $table): bool
    {
        try {
            $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $stmt = $this->pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name = :table LIMIT 1");
                $stmt->execute(['table' => $table]);
                return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
            }

            $stmt = $this->pdo->query("SHOW TABLES LIKE '{$table}'");
            return (bool)$stmt->fetch(PDO::FETCH_NUM);
        } catch (\Throwable) {
            return false;
        }
    }

    private function hasColumn(string $table, string $column): bool
    {
        return in_array($column, $this->tableColumns($table), true);
    }

    private function tableColumns(string $table): array
    {
        if (isset($this->tableColumnsCache[$table])) {
            return $this->tableColumnsCache[$table];
        }

        if (!$this->hasTable($table)) {
            $this->tableColumnsCache[$table] = [];
            return [];
        }

        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $stmt = $this->pdo->query("PRAGMA table_info({$table})");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $columns = array_values(array_filter(array_map(static fn(array $row) => $row['name'] ?? null, $rows)));
        } else {
            $stmt = $this->pdo->query("SHOW COLUMNS FROM {$table}");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $columns = array_values(array_filter(array_map(static fn(array $row) => $row['Field'] ?? null, $rows)));
        }

        $this->tableColumnsCache[$table] = $columns;
        return $columns;
    }
}
