<?php
namespace App\Repositories;

use App\Helpers\Schema;
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

        $this->clientesColumnsCache = Schema::tableColumns($this->pdo, 'clientes');
        return $this->clientesColumnsCache;
    }

    private function hasColumn(string $column): bool
    {
        return in_array($column, $this->clientesColumns(), true);
    }

    private function onlyDigits(?string $value): string
    {
        return preg_replace('/\D/', '', (string)($value ?? '')) ?? '';
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
        $this->ensureStatusColumnType();

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
            'status' => isset($data['status']) ? (string)$data['status'] : '1',
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
        $placeholders = array_map(static function (string $column): string {
            return $column === 'status' ? 'CAST(:' . $column . ' AS TEXT)' : ':' . $column;
        }, $columns);
        $sql = 'INSERT INTO clientes (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($insertData);
        return (int)$this->pdo->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $wanted = ['id', 'nome', 'endereco', 'numero', 'complemento', 'bairro', 'cep', 'cpf_cnpj', 'telefone', 'email', 'uf', 'status', 'cidade'];
        $select = array_values(array_filter($wanted, fn(string $col) => $this->hasColumn($col)));

        if (empty($select)) {
            return null;
        }

        $stmt = $this->pdo->prepare('SELECT ' . implode(', ', $select) . ' FROM clientes WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

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
    }

    public function update(int $id, array $data): bool
    {
        $this->ensureStatusColumnType();

        $updateData = [];

        if (array_key_exists('nome', $data)) {
            $updateData['nome'] = $data['nome'];
        }
        if (array_key_exists('endereco', $data)) {
            $updateData['endereco'] = $data['endereco'];
        }
        if (array_key_exists('numero', $data)) {
            $updateData['numero'] = $data['numero'];
        }
        if (array_key_exists('complemento', $data)) {
            $updateData['complemento'] = $data['complemento'];
        }
        if (array_key_exists('bairro', $data)) {
            $updateData['bairro'] = $data['bairro'];
        }
        if (array_key_exists('cep', $data)) {
            $updateData['cep'] = $data['cep'];
        }
        if (array_key_exists('cpf_cnpj', $data)) {
            $updateData['cpf_cnpj'] = $data['cpf_cnpj'];
        }
        if (array_key_exists('telefone', $data)) {
            $updateData['telefone'] = $data['telefone'];
        }
        if (array_key_exists('email', $data)) {
            $updateData['email'] = $data['email'];
        }
        if (array_key_exists('uf', $data)) {
            $updateData['uf'] = $data['uf'];
        }
        if (array_key_exists('status', $data)) {
            $updateData['status'] = isset($data['status']) ? (string)$data['status'] : '1';
        }
        if (array_key_exists('cidade', $data)) {
            $updateData['cidade'] = $data['cidade'];
        }

        if (empty($updateData)) {
            return false;
        }

        $filtered = [];
        foreach ($updateData as $column => $value) {
            if ($this->hasColumn((string)$column)) {
                $filtered[(string)$column] = $value;
            }
        }

        if (empty($filtered)) {
            return false;
        }

        $setClauses = array_map(static function (string $column): string {
            return $column === 'status' ? $column . ' = CAST(:' . $column . ' AS TEXT)' : $column . ' = :' . $column;
        }, array_keys($filtered));
        $filtered['id'] = $id;

        $stmt = $this->pdo->prepare('UPDATE clientes SET ' . implode(', ', $setClauses) . ' WHERE id = :id');
        return $stmt->execute($filtered);
    }

    private function ensureStatusColumnType(): void
    {
        if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'sqlite') {
            return;
        }

        $stmt = $this->pdo->query("PRAGMA table_info(clientes)");
        $columns = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        $statusInfo = null;
        foreach ($columns as $column) {
            if (($column['name'] ?? '') === 'status') {
                $statusInfo = $column;
                break;
            }
        }

        if ($statusInfo === null) {
            return;
        }

        $declaredType = strtolower((string)($statusInfo['type'] ?? ''));
        if ($declaredType === 'text' || $declaredType === 'varchar' || $declaredType === 'varchar(255)') {
            return;
        }

        $this->pdo->exec('ALTER TABLE clientes RENAME TO clientes_old');
        $this->pdo->exec('CREATE TABLE clientes (id INTEGER PRIMARY KEY AUTOINCREMENT, nome TEXT NOT NULL, endereco TEXT, numero TEXT, complemento TEXT, bairro TEXT, cep TEXT, cpf_cnpj TEXT, telefone TEXT, email TEXT, uf TEXT, status TEXT, cidade TEXT, deleted_at TEXT)');
        $this->pdo->exec('INSERT INTO clientes (id, nome, endereco, numero, complemento, bairro, cep, cpf_cnpj, telefone, email, uf, status, cidade, deleted_at) SELECT id, nome, endereco, numero, complemento, bairro, cep, cpf_cnpj, telefone, email, uf, status, cidade, deleted_at FROM clientes_old');
        $this->pdo->exec('DROP TABLE clientes_old');
        $this->clientesColumnsCache = null;
    }

    public function hasCpfCnpj(string $documentoDigits, ?int $excludeId = null): bool
    {
        if (!$this->hasColumn('cpf_cnpj')) {
            return false;
        }

        $sql = 'SELECT cpf_cnpj FROM clientes WHERE cpf_cnpj IS NOT NULL AND cpf_cnpj <> ""';
        $params = [];
        if ($excludeId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $excludeId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $current = $this->onlyDigits($row['cpf_cnpj'] ?? '');
            if ($current !== '' && $current === $documentoDigits) {
                return true;
            }
        }

        return false;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('UPDATE clientes SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL');
        return $stmt->execute(['id' => $id]);
    }
}
