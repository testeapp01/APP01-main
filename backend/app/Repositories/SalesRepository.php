<?php
namespace App\Repositories;

use PDO;

class SalesRepository
{
    private ?array $vendasColumnsCache = null;
    private ?array $vendasStatusEnumValuesCache = null;
    private array $tableColumnsCache = [];
    private ?bool $vendasCabecalhoTableCache = null;
    private ?bool $statusPedidoTableCache = null;

    public function __construct(private PDO $pdo)
    {
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

    private function hasColumn(string $column): bool
    {
        return in_array($column, $this->vendasColumns(), true);
    }

    public function create(array $data): int
    {
        $status = $this->normalizeVendaStatusForSchema((string)($data['status'] ?? 'AGUARDANDO'));

        $possible = [
            'venda_cabecalho_id' => $data['venda_cabecalho_id'] ?? null,
            'cliente_id' => $data['cliente_id'],
            'produto_id' => $data['produto_id'],
            'quantidade' => $data['quantidade'],
            'valor_unitario' => $data['valor_unitario'],
            'receita_total' => $data['receita_total'],
            'custo_proporcional' => $data['custo_proporcional'],
            'lucro_bruto' => $data['lucro_bruto'],
            'margem_percentual' => $data['margem_percentual'],
            'status' => $status,
            'data_envio_prevista' => $data['data_envio_prevista'] ?? null,
            'data_entrega_prevista' => $data['data_entrega_prevista'] ?? null,
        ];

        $columns = array_values(array_filter(array_keys($possible), fn(string $column) => $this->hasColumn($column)));
        $valuesSql = [];
        $params = [];

        foreach ($columns as $column) {
            $valuesSql[] = ':' . $column;
            $params[$column] = $possible[$column];
        }

        if ($this->hasColumn('data_venda')) {
            $columns[] = 'data_venda';
            $valuesSql[] = 'CURRENT_TIMESTAMP';
        }

        $stmt = $this->pdo->prepare('INSERT INTO vendas (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $valuesSql) . ')');
        $stmt->execute($params);
        return (int)$this->pdo->lastInsertId();
    }

    private function normalizeVendaStatusForSchema(string $status): string
    {
        $normalized = strtoupper(trim($status));
        if ($normalized === 'ENTREGUE') {
            return 'ENTREGUE';
        }

        $allowed = $this->vendasStatusEnumValues();
        if (empty($allowed)) {
            return 'AGUARDANDO';
        }

        if (in_array('AGUARDANDO', $allowed, true)) {
            return 'AGUARDANDO';
        }

        if (in_array('ORCAMENTO', $allowed, true)) {
            return 'ORCAMENTO';
        }

        if (in_array('CONFIRMADA', $allowed, true)) {
            return 'CONFIRMADA';
        }

        return $allowed[0] ?? 'AGUARDANDO';
    }

    private function vendasStatusEnumValues(): array
    {
        if ($this->vendasStatusEnumValuesCache !== null) {
            return $this->vendasStatusEnumValuesCache;
        }

        $this->vendasStatusEnumValuesCache = [];
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            return $this->vendasStatusEnumValuesCache;
        }

        try {
            $stmt = $this->pdo->query("SHOW COLUMNS FROM vendas LIKE 'status'");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $type = (string)($row['Type'] ?? '');
            if (preg_match("/^enum\\((.+)\\)$/i", $type, $m) !== 1) {
                return $this->vendasStatusEnumValuesCache;
            }

            $parts = str_getcsv($m[1], ',', "'", "\\");
            $this->vendasStatusEnumValuesCache = array_values(array_filter(array_map(
                static fn(string $value): string => strtoupper(trim($value)),
                $parts
            )));
        } catch (\Throwable $e) {
            $this->vendasStatusEnumValuesCache = [];
        }

        return $this->vendasStatusEnumValuesCache;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM vendas WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->pdo->prepare('UPDATE vendas SET status = :status WHERE id = :id');
        return $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public function countSimpleSales(string $query): int
    {
        [$where, $params] = $this->buildSimpleSaleFilter($query);

        $sql = "SELECT COUNT(*) as total FROM vendas v LEFT JOIN clientes c ON v.cliente_id = c.id LEFT JOIN produtos p ON v.produto_id = p.id {$where}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function findSimpleSales(string $query, int $limit, int $offset): array
    {
        [$where, $params] = $this->buildSimpleSaleFilter($query);

        $envioCol = $this->hasColumn('data_envio_prevista') ? 'v.data_envio_prevista' : 'NULL AS data_envio_prevista';
        $entregaCol = $this->hasColumn('data_entrega_prevista') ? 'v.data_entrega_prevista' : 'NULL AS data_entrega_prevista';

        $sql = "SELECT v.id, c.nome AS cliente, p.nome AS produto, v.quantidade, v.valor_unitario,
                   CASE WHEN UPPER(IFNULL(v.status, '')) = 'ENTREGUE' THEN 'ENTREGUE' ELSE 'AGUARDANDO' END AS status,
                   v.data_venda, {$envioCol}, {$entregaCol}
            FROM vendas v
            LEFT JOIN clientes c ON v.cliente_id = c.id
            LEFT JOIN produtos p ON v.produto_id = p.id
            {$where}
            ORDER BY v.id DESC
            LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countHeaderList(string $query, bool $hasStatusPedidoTable, bool $hasIdStatusPedidoColumn): int
    {
        [$where, $params] = $this->buildHeaderSearchFilter($query);

        $sql = "SELECT COUNT(*)
                     FROM vendas_cabecalho h
                     LEFT JOIN clientes c ON c.id = h.cliente_id
                     {$where}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function findHeaderList(string $query, int $limit, int $offset, bool $hasStatusPedidoTable, bool $hasIdStatusPedidoColumn): array
    {
        [$where, $params] = $this->buildHeaderSearchFilter($query);
        $statusSelect = $this->headerStatusSelect($hasStatusPedidoTable, $hasIdStatusPedidoColumn);
        $statusJoin = $this->headerStatusJoin($hasStatusPedidoTable, $hasIdStatusPedidoColumn);

        $sql = "SELECT
                    h.id,
                    h.tipo,
                    c.nome AS cliente,
                    IFNULL(COUNT(v.id), 0) AS itens_count,
                    IFNULL(h.valor_total, 0) AS valor_total,
                    {$statusSelect} AS status,
                    h.data_inicio_prevista AS data_envio_prevista,
                    h.data_fim_prevista AS data_entrega_prevista
                FROM vendas_cabecalho h
                LEFT JOIN clientes c ON c.id = h.cliente_id
                {$statusJoin}
                LEFT JOIN vendas v ON v.venda_cabecalho_id = h.id
                {$where}
                GROUP BY h.id, h.tipo, c.nome, h.valor_total, {$statusSelect}, h.data_inicio_prevista, h.data_fim_prevista
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

    public function findHeaderById(int $id, bool $hasStatusPedidoTable, bool $hasIdStatusPedidoColumn): ?array
    {
        $createdByColumn = $this->hasColumn('created_by') ? 'h.created_by,' : '';
        $statusSelect = $this->headerStatusSelect($hasStatusPedidoTable, $hasIdStatusPedidoColumn);
        $statusJoin = $this->headerStatusJoin($hasStatusPedidoTable, $hasIdStatusPedidoColumn);

        $sql = "SELECT
                    h.id,
                    h.tipo,
                    h.valor_total,
                    h.data_inicio_prevista,
                    h.data_fim_prevista,
                    {$createdByColumn}
                    {$statusSelect} AS status,
                    c.nome AS cliente
                FROM vendas_cabecalho h
                LEFT JOIN clientes c ON c.id = h.cliente_id
                {$statusJoin}
                WHERE h.id = :id
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findHeaderItemsById(int $headerId): array
    {
        $sql = "SELECT v.id, p.nome AS produto, v.quantidade, v.valor_unitario,
                    CASE WHEN UPPER(IFNULL(v.status, '')) = 'ENTREGUE' THEN 'ENTREGUE' ELSE 'AGUARDANDO' END AS status,
                    v.data_venda
             FROM vendas v
             LEFT JOIN produtos p ON p.id = v.produto_id
             WHERE v.venda_cabecalho_id = :id
               ORDER BY v.id ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $headerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findHeaderHistoryById(int $headerId, bool $hasStatusPedidoTable): array
    {
        $historyJoin = $hasStatusPedidoTable ? 'LEFT JOIN status_pedido sp ON sp.id = hsp.id_statuspedido' : '';
        $historyStatusSelect = $hasStatusPedidoTable
            ? "CASE WHEN sp.nome IS NOT NULL THEN UPPER(sp.nome) WHEN hsp.id_statuspedido = 2 THEN 'ENTREGUE' ELSE 'AGUARDANDO' END"
            : "CASE WHEN hsp.id_statuspedido = 2 THEN 'ENTREGUE' ELSE 'AGUARDANDO' END";

        $sql = "SELECT hsp.usuario_id, hsp.id_statuspedido,
                       {$historyStatusSelect} AS status,
                       u.name AS usuario_nome,
                       hsp.confirmado_em
                FROM historico_status_pedido hsp
                LEFT JOIN users u ON u.id = hsp.usuario_id
                {$historyJoin}
                WHERE hsp.venda_cabecalho_id = :id
                ORDER BY hsp.confirmado_em DESC, hsp.id DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $headerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function buildSimpleSaleFilter(string $query): array
    {
        if ($query === '') {
            return ['', []];
        }

        return [
            'WHERE c.nome LIKE :q OR p.nome LIKE :q',
            [':q' => "%{$query}%"],
        ];
    }

    private function buildHeaderSearchFilter(string $query): array
    {
        if ($query === '') {
            return ['', []];
        }

        return [
            "WHERE c.nome LIKE :q OR EXISTS (
                SELECT 1
                FROM vendas vv
                LEFT JOIN produtos pp ON pp.id = vv.produto_id
                WHERE vv.venda_cabecalho_id = h.id AND pp.nome LIKE :q
            )",
            [':q' => "%{$query}%"],
        ];
    }

    private function headerStatusSelect(bool $hasStatusPedidoTable, bool $hasIdStatusPedidoColumn): string
    {
        if ($hasStatusPedidoTable && $hasIdStatusPedidoColumn) {
            return "CASE
                            WHEN sp.nome IS NOT NULL THEN UPPER(sp.nome)
                            WHEN UPPER(IFNULL(h.status, '')) = 'ENTREGUE' THEN 'ENTREGUE'
                            ELSE 'AGUARDANDO'
                        END";
        }

        return "CASE WHEN UPPER(IFNULL(h.status, '')) = 'ENTREGUE' THEN 'ENTREGUE' ELSE 'AGUARDANDO' END";
    }

    private function headerStatusJoin(bool $hasStatusPedidoTable, bool $hasIdStatusPedidoColumn): string
    {
        if ($hasStatusPedidoTable && $hasIdStatusPedidoColumn) {
            return 'LEFT JOIN status_pedido sp ON sp.id = h.id_statuspedido';
        }

        return '';
    }

    private function hasColumn(string $column): bool
    {
        return in_array($column, $this->vendasColumns(), true);
    }
}
