<?php
namespace App\Controllers;

use PDO;
use App\Helpers\Response;

class ProductController
{
    private \App\Repositories\ProductRepository $repo;

    public function __construct(private PDO $pdo, \App\Repositories\ProductRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(): void
    {
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $per = isset($_GET['per_page']) ? max(1, (int)$_GET['per_page']) : 25;
        $q = isset($_GET['q']) ? trim($_GET['q']) : '';
        $offset = ($page - 1) * $per;

        try {
            $total = $this->repo->count(['q' => $q]);
            $items = $this->repo->search(['q' => $q], $per, $offset);
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
            $id = $this->repo->create([
                'nome' => $nome,
                'tipo' => $data['tipo'] ?? null,
                'unidade' => $data['unidade'] ?? 'saco',
                'custo_medio' => $custoMedio,
                'status' => $status,
            ]);

            Response::json(['id' => $id], 201);
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

        try {
            $updated = $this->repo->update($id, [
                'nome' => $nome,
                'tipo' => $data['tipo'] ?? null,
                'unidade' => $data['unidade'] ?? 'saco',
                'custo_medio' => $custoMedio,
                'status' => $status,
            ]);

            if (!$updated) {
                Response::error('Produto não encontrado', 404);
                return;
            }

            Response::json(['success' => true]);
        } catch (\Throwable $e) {
            Response::error('Falha ao atualizar produto.', 500);
            return;
        }
    }

    public function delete(int $id): void
    {
        try {
            $deleted = $this->repo->delete($id);

            if (!$deleted) {
                Response::error('Produto não encontrado', 404);
                return;
            }

            Response::noContent();
        } catch (\Throwable $e) {
            Response::error('Falha ao excluir produto', 500);
        }
    }
}
