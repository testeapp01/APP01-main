<?php
namespace App\Controllers;

use PDO;
use App\Helpers\Request;
use App\Helpers\SchemaValidator;
use App\Helpers\Response;

class UserController
{
    public function __construct(private PDO $pdo)
    {
    }

    public function index(): void
    {
        try {
            $stmt = $this->pdo->query('SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC');
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $result = array_map(static function (array $u): array {
                return [
                    'id'         => $u['id'],
                    'nome'       => $u['name'],
                    'email'      => $u['email'],
                    'role'       => $u['role'],
                    'status'     => true,
                    'created_at' => $u['created_at'],
                ];
            }, $rows);

            Response::json($result);
        } catch (\Throwable $e) {
            Response::error('Erro ao buscar usuários', 500);
        }
    }

    public function create(): void
    {
        $data = Request::body();
        $errors = SchemaValidator::validate($data, [
            'required' => ['nome', 'email', 'password'],
            'properties' => [
                'nome'     => ['type' => 'string', 'minLength' => 2, 'maxLength' => 150],
                'email'    => ['type' => 'string', 'format' => 'email', 'maxLength' => 150],
                'password' => ['type' => 'string', 'minLength' => 6, 'maxLength' => 255],
                'role'     => ['type' => 'string', 'maxLength' => 50],
            ],
        ]);

        if (!empty($errors)) {
            Response::error('Payload inválido', 422, ['details' => $errors]);
            return;
        }

        $email = strtolower(trim((string)($data['email'] ?? '')));
        $nome  = trim((string)($data['nome'] ?? ''));
        $role  = trim((string)($data['role'] ?? 'operador'));
        $password = (string)($data['password'] ?? '');

        // Role hierarchy: admin can create any role; gerente can create up to vendedor
        $callerRole = strtolower(trim((string)(($GLOBALS['AUTH_USER'] ?? [])['role'] ?? '')));
        $allowedByAdmin   = ['admin', 'gerente', 'suporte', 'vendedor', 'operador'];
        $allowedByGerente = ['suporte', 'vendedor', 'operador'];
        $allowedRoles = $callerRole === 'admin' ? $allowedByAdmin : $allowedByGerente;

        if (!in_array($role, $allowedRoles, true)) {
            Response::error('Você não tem permissão para criar usuários com essa função.', 403);
            return;
        }

        try {
            $check = $this->pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
            $check->execute(['email' => $email]);
            if ($check->fetch()) {
                Response::error('Email já cadastrado.', 409);
                return;
            }
        } catch (\Throwable $e) {
            Response::error('Erro ao verificar email', 500);
            return;
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        if ($hash === false) {
            Response::error('Erro ao processar senha.', 500);
            return;
        }

        try {
            $stmt = $this->pdo->prepare('INSERT INTO users (name, email, password, role, created_at) VALUES (:name, :email, :password, :role, NOW())');
            $stmt->execute([
                'name'     => $nome,
                'email'    => $email,
                'password' => $hash,
                'role'     => $role,
            ]);

            $id = (int)$this->pdo->lastInsertId();
            Response::json(['id' => $id, 'nome' => $nome, 'email' => $email, 'role' => $role], 201);
        } catch (\Throwable $e) {
            Response::error('Erro ao criar usuário', 500);
        }
    }

    public function update(int $id): void
    {
        $data = Request::body();

        $nome  = isset($data['nome'])  ? trim((string)$data['nome'])  : null;
        $role  = isset($data['role'])  ? trim((string)$data['role'])  : null;

        if ($role !== null) {
            $allowed = ['admin', 'gerente', 'suporte', 'vendedor', 'operador'];
            if (!in_array($role, $allowed, true)) {
                $role = 'operador';
            }
        }

        $sets  = [];
        $params = ['id' => $id];

        if ($nome !== null && $nome !== '') {
            $sets[] = 'name = :name';
            $params['name'] = $nome;
        }
        if ($role !== null) {
            $sets[] = 'role = :role';
            $params['role'] = $role;
        }
        if (isset($data['password']) && trim((string)$data['password']) !== '') {
            $hash = password_hash((string)$data['password'], PASSWORD_BCRYPT);
            if ($hash !== false) {
                $sets[] = 'password = :password';
                $params['password'] = $hash;
            }
        }

        if (empty($sets)) {
            Response::error('Nenhum campo para atualizar.', 400);
            return;
        }

        try {
            $sql = 'UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = :id';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            if ($stmt->rowCount() === 0) {
                Response::error('Usuário não encontrado.', 404);
                return;
            }

            Response::json(['success' => true]);
        } catch (\Throwable $e) {
            Response::error('Erro ao atualizar usuário', 500);
        }
    }

    public function delete(int $id): void
    {
        $authUser = $GLOBALS['AUTH_USER'] ?? [];
        if ((int)($authUser['sub'] ?? 0) === $id) {
            Response::error('Não é possível excluir o próprio usuário.', 400);
            return;
        }

        try {
            $stmt = $this->pdo->prepare('UPDATE users SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL');
            $stmt->execute(['id' => $id]);

            if ($stmt->rowCount() === 0) {
                Response::error('Usuário não encontrado.', 404);
                return;
            }

            Response::json(['success' => true]);
        } catch (\Throwable $e) {
            Response::error('Erro ao excluir usuário', 500);
        }
    }
}
