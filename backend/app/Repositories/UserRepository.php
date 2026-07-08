<?php
namespace App\Repositories;

use PDO;

class UserRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function all(): array
    {
        $stmt = $this->pdo->query('SELECT id, name, email, role, created_at FROM users WHERE deleted_at IS NULL ORDER BY created_at DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, name, email, role, created_at FROM users WHERE id = :id AND deleted_at IS NULL LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, name, email, role, password FROM users WHERE email = :email AND deleted_at IS NULL LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO users (name, email, password, role, created_at) VALUES (:name, :email, :password, :role, NOW())');
        $stmt->execute([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $fields): bool
    {
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

        $stmt = $this->pdo->prepare('UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    public function updatePassword(int $id, string $password): bool
    {
        $stmt = $this->pdo->prepare('UPDATE users SET password = :password WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute(['password' => $password, 'id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('UPDATE users SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
