<?php
namespace App\Controllers;

use App\Helpers\Response;
use App\Helpers\Request;
use App\Helpers\Schema;
use PDO;

class UsuariosEmpresasController
{
    public function __construct(private PDO $pdo)
    {
    }

    private function resolveTable(): ?string
    {
        $candidates = ['usuarios_empresas', 'usuarios_empresas_vinculos', 'user_companies'];
        foreach ($candidates as $t) {
            if (Schema::hasTable($this->pdo, $t)) {
                return $t;
            }
        }
        return null;
    }

    private function resolveUserTable(): ?string
    {
        foreach (['users','usuarios'] as $t) {
            if (Schema::hasTable($this->pdo, $t)) return $t;
        }
        return null;
    }

    private function resolveEmpresaTable(): ?string
    {
        foreach (['empresas','companies','empresa'] as $t) {
            if (Schema::hasTable($this->pdo, $t)) return $t;
        }
        return null;
    }

    private function existsInTable(string $table, string $idColumn, $id): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM ' . $table . ' WHERE ' . $idColumn . ' = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return (bool)$stmt->fetchColumn();
    }

    public function index(): void
    {
        $table = $this->resolveTable();
        if ($table === null) {
            Response::json([]);
            return;
        }

        $stmt = $this->pdo->query('SELECT * FROM ' . $table . ' WHERE deleted_at IS NULL ORDER BY id DESC');
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        Response::json($rows ?: []);
    }

    public function create(): void
    {
        $table = $this->resolveTable();
        if ($table === null) {
            Response::error('Recurso não disponível', 501);
            return;
        }

        $data = Request::body();
        $possible = ['usuario_id', 'empresa_id', 'role_empresa', 'status'];
        $cols = [];
        $params = [];
        foreach ($possible as $col) {
            if (Schema::hasColumn($this->pdo, $table, $col) && isset($data[$col])) {
                $cols[] = $col;
                $params[$col] = $data[$col];
            }
        }

        $errors = [];
        if (!isset($params['usuario_id']) || (string)$params['usuario_id'] === '') {
            $errors['usuario_id'] = 'Usuário é obrigatório';
        }
        if (!isset($params['empresa_id']) || (string)$params['empresa_id'] === '') {
            $errors['empresa_id'] = 'Empresa é obrigatória';
        }
        if (!isset($params['role_empresa']) || trim((string)$params['role_empresa']) === '') {
            $errors['role_empresa'] = 'Permissão na empresa é obrigatória';
        }

        if (!empty($errors)) {
            Response::error('Payload inválido', 422, ['details' => $errors]);
            return;
        }

        if (empty($cols)) {
            Response::error('Nenhuma coluna disponível para inserção', 500);
            return;
        }

        // Validate FK references if provided
        $userTable = $this->resolveUserTable();
        $empresaTable = $this->resolveEmpresaTable();
        if (isset($params['usuario_id']) && $userTable !== null) {
            if (!$this->existsInTable($userTable, 'id', $params['usuario_id'])) {
                Response::error('Payload inválido', 422, ['details' => ['usuario_id' => 'Usuário não encontrado']]);
                return;
            }
        }
        if (isset($params['empresa_id']) && $empresaTable !== null) {
            if (!$this->existsInTable($empresaTable, 'id', $params['empresa_id'])) {
                Response::error('Payload inválido', 422, ['details' => ['empresa_id' => 'Empresa não encontrada']]);
                return;
            }
        }

        $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $cols) . ') VALUES (' . implode(', ', array_map(fn($c) => ':' . $c, $cols)) . ')';
        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->execute($params);
            $id = (int)$this->pdo->lastInsertId();
            Response::json(['id' => $id], 201);
        } catch (\Throwable $e) {
            Response::error('Falha ao criar vínculo', 500);
        }
    }

    public function update(int $id): void
    {
        $table = $this->resolveTable();
        if ($table === null) {
            Response::error('Recurso não disponível', 501);
            return;
        }

        $data = Request::body();
        $sets = [];
        $params = ['id' => $id];
        foreach (['usuario_id','empresa_id','role_empresa','status'] as $col) {
            if (Schema::hasColumn($this->pdo, $table, $col) && isset($data[$col])) {
                $sets[] = $col . ' = :' . $col;
                $params[$col] = $data[$col];
            }
        }

        if (empty($sets)) {
            Response::error('Payload inválido', 422, ['details' => ['payload' => 'Nada para atualizar']]);
            return;
        }

        // Validate FK references on update if present
        $errors = [];
        $userTable = $this->resolveUserTable();
        $empresaTable = $this->resolveEmpresaTable();
        if (isset($params['usuario_id']) && $userTable !== null) {
            if (!$this->existsInTable($userTable, 'id', $params['usuario_id'])) {
                $errors['usuario_id'] = 'Usuário não encontrado';
            }
        }
        if (isset($params['empresa_id']) && $empresaTable !== null) {
            if (!$this->existsInTable($empresaTable, 'id', $params['empresa_id'])) {
                $errors['empresa_id'] = 'Empresa não encontrada';
            }
        }
        if (isset($params['role_empresa']) && trim((string)$params['role_empresa']) === '') {
            $errors['role_empresa'] = 'Permissão na empresa é obrigatória';
        }

        if (!empty($errors)) {
            Response::error('Payload inválido', 422, ['details' => $errors]);
            return;
        }

        $sql = 'UPDATE ' . $table . ' SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->execute($params);
            Response::json(['success' => true]);
        } catch (\Throwable $e) {
            Response::error('Falha ao atualizar vínculo', 500);
        }
    }

    public function delete(int $id): void
    {
        $table = $this->resolveTable();
        if ($table === null) {
            Response::error('Recurso não disponível', 501);
            return;
        }

        if (Schema::hasColumn($this->pdo, $table, 'deleted_at')) {
            $stmt = $this->pdo->prepare('UPDATE ' . $table . ' SET deleted_at = NOW() WHERE id = :id');
            $stmt->execute(['id' => $id]);
            Response::json(['success' => true]);
            return;
        }

        try {
            $stmt = $this->pdo->prepare('DELETE FROM ' . $table . ' WHERE id = :id');
            $stmt->execute(['id' => $id]);
            Response::json(['success' => true]);
        } catch (\Throwable $e) {
            Response::error('Falha ao remover vínculo', 500);
        }
    }
}
