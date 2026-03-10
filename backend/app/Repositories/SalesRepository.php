<?php
namespace App\Repositories;

use PDO;

class SalesRepository
{
    private ?array $vendasColumnsCache = null;
    private ?array $vendasStatusEnumValuesCache = null;

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
}
