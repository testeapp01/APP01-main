<?php
namespace App\Repositories;

use PDO;

class SalesRepository
{
    private ?array $vendasColumnsCache = null;

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
        $possible = [
            'cliente_id' => $data['cliente_id'],
            'produto_id' => $data['produto_id'],
            'quantidade' => $data['quantidade'],
            'valor_unitario' => $data['valor_unitario'],
            'receita_total' => $data['receita_total'],
            'custo_proporcional' => $data['custo_proporcional'],
            'lucro_bruto' => $data['lucro_bruto'],
            'margem_percentual' => $data['margem_percentual'],
            'status' => $data['status'] ?? 'ORCAMENTO',
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
