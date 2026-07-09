<?php
namespace App\Repositories;

use App\Helpers\Schema;
use PDO;

class UserRepository
{
    private ?string $tableName = null;
    private ?array $columnsCache = null;

    public function __construct(private PDO $pdo)
    {
    }

    public function all(): array
    {
        $table = $this->resolveTableName();
        if (!Schema::hasTable($this->pdo, $table)) {
            return [];
        }

        $idColumn = $this->resolveColumn(['id', 'user_id']);
        $nameColumn = $this->resolveColumn(['name', 'nome', 'usuario']);
        $emailColumn = $this->resolveColumn(['email', 'usuario', 'login']);
        $roleColumn = $this->resolveColumn(['role', 'perfil', 'tipo']);
        $createdAtColumn = $this->resolveColumn(['created_at', 'createdAt']);

        $stmt = $this->pdo->query('SELECT ' . implode(', ', array_filter([$idColumn, $nameColumn, $emailColumn, $roleColumn, $createdAtColumn])) . ' FROM ' . $table . ' WHERE deleted_at IS NULL ORDER BY ' . ($createdAtColumn ?? $idColumn ?? 'id') . ' DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findById(int $id): ?array
    {
        $table = $this->resolveTableName();
        if (!Schema::hasTable($this->pdo, $table)) {
            return null;
        }

        $idColumn = $this->resolveColumn(['id', 'user_id']);
        $stmt = $this->pdo->prepare('SELECT * FROM ' . $table . ' WHERE ' . ($idColumn ?? 'id') . ' = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->normalizeUserRow($row, $table) : null;
    }

    public function findByEmail(string $email): ?array
    {
        $table = $this->resolveTableName();
        if (!Schema::hasTable($this->pdo, $table)) {
            return null;
        }

        $identifierColumn = $this->resolveColumn(['email', 'usuario', 'username', 'login']);
        if ($identifierColumn === null) {
            return null;
        }

        $stmt = $this->pdo->prepare('SELECT * FROM ' . $table . ' WHERE ' . $identifierColumn . ' = :value LIMIT 1');
        $stmt->execute(['value' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->normalizeUserRow($row, $table) : null;
    }

    public function create(array $data): int
    {
        $table = $this->resolveTableName();
        $nameColumn = $this->resolveColumn(['name', 'nome']);
        $emailColumn = $this->resolveColumn(['email', 'usuario']);
        $passwordColumn = $this->resolveColumn(['password', 'senha', 'passwd']);
        $roleColumn = $this->resolveColumn(['role', 'perfil', 'tipo']);
        $columns = [];
        $params = [];
        if ($nameColumn !== null) { $columns[] = $nameColumn; $params[$nameColumn] = $data['name']; }
        if ($emailColumn !== null) { $columns[] = $emailColumn; $params[$emailColumn] = $data['email']; }
        if ($passwordColumn !== null) { $columns[] = $passwordColumn; $params[$passwordColumn] = $data['password']; }
        if ($roleColumn !== null) { $columns[] = $roleColumn; $params[$roleColumn] = $data['role']; }
        if ($this->resolveColumn(['created_at', 'createdAt']) !== null) { $columns[] = $this->resolveColumn(['created_at', 'createdAt']); }
        $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $columns) . ')' . ' VALUES (' . implode(', ', array_map(static fn($c) => ':' . $c, $columns)) . ')';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $fields): bool
    {
        $table = $this->resolveTableName();
        $sets = [];
        $params = ['id' => $id];

        if (isset($fields['name'])) {
            $sets[] = 'name = :name';
            $params['name'] = $fields['name'];
        }
        if (isset($fields['email'])) {
            $sets[] = 'email = :email';
            $params['email'] = $fields['email'];
        }
        if (isset($fields['password'])) {
            $sets[] = 'password = :password';
            $params['password'] = $fields['password'];
        }
        if (isset($fields['role'])) {
            $sets[] = 'role = :role';
            $params['role'] = $fields['role'];
        }

        if (empty($sets)) {
            return false;
        }

        $stmt = $this->pdo->prepare('UPDATE ' . $table . ' SET ' . implode(', ', $sets) . ' WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    public function updatePassword(int $id, string $password): bool
    {
        $table = $this->resolveTableName();
        $passwordColumn = $this->resolveColumn(['password', 'senha', 'passwd']);
        $column = $passwordColumn ?? 'password';
        $idColumn = $this->resolveColumn(['id', 'user_id']) ?? 'id';
        $conditions = [$idColumn . ' = :id'];
        if (Schema::hasColumn($this->pdo, $table, 'deleted_at')) {
            $conditions[] = 'deleted_at IS NULL';
        }

        $stmt = $this->pdo->prepare('UPDATE ' . $table . ' SET ' . $column . ' = :password WHERE ' . implode(' AND ', $conditions));
        return $stmt->execute(['password' => $password, 'id' => $id]);
    }

    public function delete(int $id): bool
    {
        $table = $this->resolveTableName();
        $stmt = $this->pdo->prepare('UPDATE ' . $table . ' SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    private function resolveTableName(): string
    {
        if ($this->tableName !== null) {
            return $this->tableName;
        }

        foreach (['users', 'usuarios'] as $candidate) {
            if (Schema::hasTable($this->pdo, $candidate)) {
                $this->tableName = $candidate;
                return $candidate;
            }
        }

        $this->tableName = 'users';
        return $this->tableName;
    }

    private function resolveColumn(array $candidates): ?string
    {
        $columns = $this->resolveColumns();
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $columns, true)) {
                return $candidate;
            }
        }

        return null;
    }

    private function resolveColumns(): array
    {
        if ($this->columnsCache !== null) {
            return $this->columnsCache;
        }

        $table = $this->resolveTableName();
        $this->columnsCache = Schema::tableColumns($this->pdo, $table);
        return $this->columnsCache;
    }

    private function normalizeUserRow(array $row, string $table): array
    {
        $idColumn = $this->resolveColumn(['id', 'user_id']);
        $nameColumn = $this->resolveColumn(['name', 'nome', 'usuario']);
        $emailColumn = $this->resolveColumn(['email', 'usuario', 'login']);
        $roleColumn = $this->resolveColumn(['role', 'perfil', 'tipo']);
        $passwordColumn = $this->resolveColumn(['password', 'senha', 'passwd']);

        return [
            'id' => isset($row[$idColumn ?? 'id']) ? (int)$row[$idColumn ?? 'id'] : null,
            'name' => $row[$nameColumn ?? 'name'] ?? $row[$nameColumn ?? 'nome'] ?? $row['usuario'] ?? null,
            'email' => $row[$emailColumn ?? 'email'] ?? $row['usuario'] ?? null,
            'role' => $row[$roleColumn ?? 'role'] ?? null,
            'password' => $row[$passwordColumn ?? 'password'] ?? null,
        ];
    }
}
