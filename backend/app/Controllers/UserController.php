<?php
namespace App\Controllers;

use PDO;
use App\Helpers\Request;
use App\Helpers\SchemaValidator;

class UserController
{
    public function __construct(private PDO $pdo)
    {
    }

    public function index(): void
    {
        $stmt = $this->pdo->query(
            'SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC'
        );
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

        echo json_encode($result);
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
            http_response_code(422);
            echo json_encode(['error' => 'Payload inválido', 'details' => $errors]);
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
            http_response_code(403);
            echo json_encode(['error' => 'Você não tem permissão para criar usuários com essa função.']);
            return;
        }

        $check = $this->pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $check->execute(['email' => $email]);
        if ($check->fetch()) {
            http_response_code(409);
            echo json_encode(['error' => 'Email já cadastrado.']);
            return;
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        if ($hash === false) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao processar senha.']);
            return;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO users (name, email, password, role, created_at) VALUES (:name, :email, :password, :role, NOW())'
        );
        $stmt->execute([
            'name'     => $nome,
            'email'    => $email,
            'password' => $hash,
            'role'     => $role,
        ]);

        $id = (int)$this->pdo->lastInsertId();
        http_response_code(201);
        echo json_encode(['id' => $id, 'nome' => $nome, 'email' => $email, 'role' => $role]);
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
            http_response_code(400);
            echo json_encode(['error' => 'Nenhum campo para atualizar.']);
            return;
        }

        $sql = 'UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Usuário não encontrado.']);
            return;
        }

        echo json_encode(['success' => true]);
    }

    public function delete(int $id): void
    {
        $authUser = $GLOBALS['AUTH_USER'] ?? [];
        if ((int)($authUser['sub'] ?? 0) === $id) {
            http_response_code(400);
            echo json_encode(['error' => 'Não é possível excluir o próprio usuário.']);
            return;
        }

        $stmt = $this->pdo->prepare('UPDATE users SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute(['id' => $id]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Usuário não encontrado.']);
            return;
        }

        echo json_encode(['success' => true]);
    }
}
