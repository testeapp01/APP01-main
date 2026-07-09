<?php
namespace App\Controllers;

use App\Repositories\TabelaPrecoRepository;
use PDO;
use App\Helpers\Request;
use App\Helpers\Response;

class TabelaPrecoController
{
    private TabelaPrecoRepository $repo;

    public function __construct(private PDO $pdo, TabelaPrecoRepository $repo)
    {
        $this->repo = $repo;
    }

    /** GET /api/v1/tabelas-preco */
    public function index(): void
    {
        Response::json($this->repo->all());
    }

    /** POST /api/v1/tabelas-preco */
    public function create(): void
    {
        $data = Request::body();
        $nome = trim((string)($data['nome'] ?? ''));

        if ($nome === '') {
            Response::error('Nome é obrigatório', 422);
            return;
        }

        $tipo      = trim((string)($data['tipo'] ?? 'padrao'));
        $desconto  = (float)($data['desconto_percentual'] ?? 0);
        $ativa     = isset($data['ativa']) ? (int)(bool)$data['ativa'] : 1;

        $allowed = ['atacado','varejo','especial','padrao'];
        if (!in_array($tipo, $allowed, true)) $tipo = 'padrao';

        $id = $this->repo->create([
            'nome' => $nome,
            'tipo' => $tipo,
            'desconto_percentual' => $desconto,
            'ativa' => $ativa,
        ]);

        http_response_code(201);
        Response::json(['id' => $id]);
    }

    /** PUT /api/v1/tabelas-preco/{id} */
    public function update(int $id): void
    {
        $data  = Request::body();
        $sets  = [];
        $params = ['id' => $id];

        if (isset($data['nome']) && trim($data['nome']) !== '') {
            $sets[] = 'nome = :nome';
            $params['nome'] = trim($data['nome']);
        }
        if (isset($data['desconto_percentual'])) {
            $sets[] = 'desconto_percentual = :desc';
            $params['desc'] = (float)$data['desconto_percentual'];
        }
        if (isset($data['ativa'])) {
            $sets[] = 'ativa = :ativa';
            $params['ativa'] = (int)(bool)$data['ativa'];
        }

        if (empty($sets)) {
            Response::error('Nenhum campo para atualizar', 400);
            return;
        }

        $updated = $this->repo->update($id, $params);

        if (!$updated) {
            Response::error('Tabela de preço não encontrada', 404);
            return;
        }

        Response::json(['success' => true]);
    }
}
