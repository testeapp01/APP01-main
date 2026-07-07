<?php
namespace App\Controllers;

use PDO;
use App\Helpers\Response;

class ProductController
{
    private \App\Repositories\ProductRepository $repo;

    public function __construct(private PDO $pdo, ?\App\Repositories\ProductRepository $repo = null)
    {
        $this->repo = $repo ?? new \App\Repositories\ProductRepository($this->pdo);
    }


    public function index(): void
    {
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $per = isset($_GET['per_page']) ? max(1, (int)$_GET['per_page']) : 25;
        $q = isset($_GET['q']) ? trim($_GET['q']) : '';

        $offset = ($page - 1) * $per;

        $where = '';
        $params = [];
        if ($q !== '') {
            $where = 'WHERE nome LIKE :q';
            $params[':q'] = "%{$q}%";
        }

        try {
            $countSql = "SELECT COUNT(*) as total FROM produtos {$where}";
            $stmt = $this->pdo->prepare($countSql);
            $stmt->execute($params);
            $total = (int)$stmt->fetchColumn();

            $sql = "SELECT id, nome, tipo, unidade, custo_medio, status FROM produtos {$where} ORDER BY id DESC LIMIT :limit OFFSET :offset";
            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $k => $v) $stmt->bindValue($k, $v);
            $stmt->bindValue(':limit', $per, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Response::json(['items' => $items, 'total' => $total]);
        } catch (\Throwable $e) {
            Response::error('Falha ao listar produtos', 500);
        }
    }

    public function create(): void
    {
        $data = \App\Helpers\Request::body();
        $nome = isset($data['nome']) ? trim((string)$data['nome']) : '';
        if ($nome === '') {
            Response::error('Nome obrigatório', 400);
            return;
        }

        $custoMedio = isset($data['custo_medio']) ? (float)$data['custo_medio'] : 0;
        if ($custoMedio < 0) {
            Response::error('custo_medio não pode ser negativo', 400);
            return;
        }

        $status = in_array($data['status'] ?? 'ativo', ['ativo', 'inativo'], true) ? $data['status'] : 'ativo';

        try {
            $stmt = $this->pdo->prepare('INSERT INTO produtos (nome, tipo, unidade, custo_medio, status) VALUES (:nome, :tipo, :unidade, :custo_medio, :status)');
            $stmt->execute([
                'nome' => $nome,
                'tipo' => $data['tipo'] ?? null,
                'unidade' => $data['unidade'] ?? 'saco',
                'custo_medio' => $custoMedio,
                'status' => $status,
            ]);

            Response::json(['id' => (int)$this->pdo->lastInsertId()], 201);
        } catch (\Throwable $e) {
            Response::error('Falha ao salvar produto.', 500);
            return;
        }
    }

    public function update(int $id): void
    {
        $found = $this->repo->findById($id);
        if (!$found) {
            Response::error('Produto não encontrado', 404);
            return;
        }

        $data = \App\Helpers\Request::body();
        $nome = isset($data['nome']) ? trim((string)$data['nome']) : '';
        if ($nome === '') {
            Response::error('Nome obrigatório', 400);
            return;
        }

        $custoMedio = isset($data['custo_medio']) ? (float)$data['custo_medio'] : 0;
        if ($custoMedio < 0) {
            Response::error('custo_medio não pode ser negativo', 400);
            return;
        }

        $status = in_array($data['status'] ?? '', ['ativo', 'inativo'], true) ? $data['status'] : null;

        $statusSql = $status !== null ? ', status = :status' : '';
        $stmt = $this->pdo->prepare(
            "UPDATE produtos SET nome = :nome, tipo = :tipo, unidade = :unidade, custo_medio = :custo_medio{$statusSql} WHERE id = :id"
        );

        try {
            $params = [
                'id' => $id,
                'nome' => $nome,
                'tipo' => $data['tipo'] ?? null,
                'unidade' => $data['unidade'] ?? 'saco',
                'custo_medio' => $custoMedio,
            ];
            if ($status !== null) $params['status'] = $status;
            $stmt->execute($params);

            Response::json(['success' => true]);
        } catch (\Throwable $e) {
            Response::error('Falha ao atualizar produto.', 500);
            return;
        }
    }

    public function delete(int $id): void
    {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM produtos WHERE id = :id');
            $stmt->execute(['id' => $id]);

            if ($stmt->rowCount() < 1) {
                Response::error('Produto não encontrado', 404);
                return;
            }

            Response::noContent();
        } catch (\Throwable $e) {
            Response::error('Falha ao excluir produto', 500);
        }
    }
}
