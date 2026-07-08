<?php
namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\SchemaValidator;
use App\Helpers\Response;
use App\Repositories\UserRepository;
use PDO;

class UserController
{
    private UserRepository $repo;

    public function __construct(private PDO $pdo, UserRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(): void
    {
        try {
            $rows = $this->repo->all();
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
            if ($this->repo->findByEmail($email) !== null) {
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
            $id = $this->repo->create([
                'name' => $nome,
                'email' => $email,
                'password' => $hash,
                'role' => $role,
            ]);

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

        $fields = [];
        if ($nome !== null && $nome !== '') {
            $fields['name'] = $nome;
        }
        if ($role !== null) {
            $fields['role'] = $role;
        }
        if (isset($data['password']) && trim((string)$data['password']) !== '') {
            $hash = password_hash((string)$data['password'], PASSWORD_BCRYPT);
            if ($hash !== false) {
                $fields['password'] = $hash;
            }
        }

        if (empty($fields)) {
            Response::error('Nenhum campo para atualizar.', 400);
            return;
        }

        try {
            $updated = $this->repo->update($id, $fields);
            if (!$updated) {
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
            $deleted = $this->repo->delete($id);
            if (!$deleted) {
                Response::error('Usuário não encontrado.', 404);
                return;
            }

            Response::json(['success' => true]);
        } catch (\Throwable $e) {
            Response::error('Erro ao excluir usuário', 500);
        }
    }
}
