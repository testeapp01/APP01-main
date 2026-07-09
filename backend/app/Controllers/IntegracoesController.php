<?php
namespace App\Controllers;

use App\Helpers\Response;
use App\Helpers\Request;
use App\Helpers\Schema;
use PDO;

class IntegracoesController
{
    public function __construct(private PDO $pdo)
    {
    }

    private function resolveTable(): ?string
    {
        $candidates = ['integracoes', 'integrations'];
        foreach ($candidates as $t) {
            if (Schema::hasTable($this->pdo, $t)) {
                return $t;
            }
        }
        return null;
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
        // Basic validation
        $nome = trim((string)($data['nome'] ?? ''));
        if ($nome === '') {
            Response::error('Nome da integração é obrigatório', 400);
            return;
        }

        $possible = ['nome', 'tipo', 'status', 'config'];
        $cols = [];
        $params = [];
        foreach ($possible as $col) {
            if (Schema::hasColumn($this->pdo, $table, $col) && isset($data[$col])) {
                $cols[] = $col;
                $params[$col] = $data[$col];
            }
        }

        if (empty($cols)) {
            Response::error('Nenhuma coluna disponível para inserção', 500);
            return;
        }

        // Validate status if present
        if (isset($params['status'])) {
            $s = strtolower(trim((string)$params['status']));
            if (!in_array($s, ['ativo','inativo'], true)) {
                Response::error('Status inválido', 400);
                return;
            }
            $params['status'] = $s;
        }

        // If config column exists and is provided, ensure it's valid JSON (store as string)
        if (isset($params['config'])) {
            if (is_array($params['config'])) {
                $params['config'] = json_encode($params['config']);
            } else {
                // validate JSON string
                json_decode((string)$params['config']);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Response::error('Configuração inválida: JSON esperado', 400);
                    return;
                }
            }
        }

        $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $cols) . ') VALUES (' . implode(', ', array_map(fn($c) => ':' . $c, $cols)) . ')';
        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->execute($params);
            $id = (int)$this->pdo->lastInsertId();
            Response::json(['id' => $id], 201);
        } catch (\Throwable $e) {
            Response::error('Falha ao criar integração', 500);
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
        foreach (['nome','tipo','status','config'] as $col) {
            if (Schema::hasColumn($this->pdo, $table, $col) && isset($data[$col])) {
                $sets[] = $col . ' = :' . $col;
                $params[$col] = $data[$col];
            }
        }

        if (empty($sets)) {
            Response::error('Nada para atualizar', 400);
            return;
        }

        // Validate provided fields
        if (isset($params['nome']) && trim((string)$params['nome']) === '') {
            Response::error('Nome da integração é obrigatório', 400);
            return;
        }
        if (isset($params['status'])) {
            $s = strtolower(trim((string)$params['status']));
            if (!in_array($s, ['ativo','inativo'], true)) {
                Response::error('Status inválido', 400);
                return;
            }
            $params['status'] = $s;
        }
        if (isset($params['config'])) {
            if (is_array($params['config'])) {
                $params['config'] = json_encode($params['config']);
            } else {
                json_decode((string)$params['config']);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Response::error('Configuração inválida: JSON esperado', 400);
                    return;
                }
            }
        }

        $sql = 'UPDATE ' . $table . ' SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->execute($params);
            Response::json(['success' => true]);
        } catch (\Throwable $e) {
            Response::error('Falha ao atualizar integração', 500);
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
            Response::error('Falha ao remover integração', 500);
        }
    }
}
