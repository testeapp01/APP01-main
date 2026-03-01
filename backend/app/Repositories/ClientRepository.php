<?php
namespace App\Repositories;

use PDO;

class ClientRepository
{
    private ?array $clientesColumnsCache = null;

    public function __construct(private PDO $pdo)
    {
    }

    private function clientesColumns(): array
    {
        if ($this->clientesColumnsCache !== null) {
            return $this->clientesColumnsCache;
        }

        $stmt = $this->pdo->query('SHOW COLUMNS FROM clientes');
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->clientesColumnsCache = array_values(array_filter(array_map(
            static fn(array $row) => $row['Field'] ?? null,
            $cols
        )));

        return $this->clientesColumnsCache;
    }

    private function hasColumn(string $column): bool
    {
        return in_array($column, $this->clientesColumns(), true);
    }

    public function all(): array
    {
        $wanted = ['id', 'nome', 'endereco', 'numero', 'complemento', 'bairro', 'cep', 'cpf_cnpj', 'telefone', 'email', 'uf', 'status', 'cidade'];
        $select = array_values(array_filter($wanted, fn(string $col) => $this->hasColumn($col)));

        if (empty($select)) {
            return [];
        }

        $stmt = $this->pdo->query('SELECT ' . implode(', ', $select) . ' FROM clientes ORDER BY id DESC');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(static function (array $row): array {
            return [
                'id' => isset($row['id']) ? (int)$row['id'] : null,
                'nome' => $row['nome'] ?? null,
                'endereco' => $row['endereco'] ?? null,
                'numero' => $row['numero'] ?? null,
                'complemento' => $row['complemento'] ?? null,
                'bairro' => $row['bairro'] ?? null,
                'cep' => $row['cep'] ?? null,
                'cpf_cnpj' => $row['cpf_cnpj'] ?? null,
                'telefone' => $row['telefone'] ?? null,
                'email' => $row['email'] ?? null,
                'uf' => $row['uf'] ?? null,
                'status' => isset($row['status']) ? (int)$row['status'] : 1,
                'cidade' => $row['cidade'] ?? null,
            ];
        }, $rows);
    }

    public function create(array $data): int
    {
        $possible = [
            'nome' => $data['nome'],
            'endereco' => $data['endereco'] ?? null,
            'numero' => $data['numero'] ?? null,
            'complemento' => $data['complemento'] ?? null,
            'bairro' => $data['bairro'] ?? null,
            'cep' => $data['cep'] ?? null,
            'cpf_cnpj' => $data['cpf_cnpj'] ?? null,
            'telefone' => $data['telefone'] ?? null,
            'email' => $data['email'] ?? null,
            'uf' => $data['uf'] ?? null,
            'status' => isset($data['status']) ? (int)$data['status'] : 1,
            'cidade' => $data['cidade'] ?? null,
        ];

        $insertData = array_filter(
            $possible,
            fn($value, $column) => $this->hasColumn((string)$column),
            ARRAY_FILTER_USE_BOTH
        );

        if (empty($insertData)) {
            throw new \RuntimeException('Tabela clientes sem colunas compatíveis para inserção.');
        }

        $columns = array_keys($insertData);
        $placeholders = array_map(static fn(string $column) => ':' . $column, $columns);
        $sql = 'INSERT INTO clientes (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($insertData);
        return (int)$this->pdo->lastInsertId();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM clientes WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
