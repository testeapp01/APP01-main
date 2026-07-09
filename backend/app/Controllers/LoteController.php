<?php
namespace App\Controllers;

use App\Repositories\LoteRepository;
use PDO;
use App\Helpers\Request;
use App\Helpers\Response;

class LoteController
{
    private LoteRepository $repo;

    public function __construct(private PDO $pdo, LoteRepository $repo)
    {
        $this->repo = $repo;
    }

    /** GET /api/v1/lotes?produto_id=X — lista lotes (por produto, FEFO) */
    public function index(): void
    {
        $produtoId = isset($_GET['produto_id']) ? (int)$_GET['produto_id'] : 0;
        $status    = trim($_GET['status'] ?? '');

        if ($status !== '') {
            $allowed = ['ativo','quarentena','vencido','descartado'];
            if (!in_array($status, $allowed, true)) {
                $status = '';
            }
        }

        Response::json($this->repo->fetchAll([
            'produto_id' => $produtoId,
            'status' => $status !== '' ? $status : null,
        ]));
    }

    /** POST /api/v1/lotes — cria um novo lote */
    public function create(): void
    {
        $data = Request::body();

        $produtoId    = (int)($data['produto_id'] ?? 0);
        $dataValidade = trim((string)($data['data_validade'] ?? ''));
        $quantidade   = (float)($data['quantidade_entrada'] ?? 0);

        if ($produtoId <= 0 || $dataValidade === '' || $quantidade <= 0) {
            Response::error('produto_id, data_validade e quantidade_entrada são obrigatórios', 422);
            return;
        }

        $id = $this->repo->create([
            'produto_id' => $produtoId,
            'fornecedor_id' => $data['fornecedor_id'] ?? null,
            'compra_cabecalho_id' => $data['compra_cabecalho_id'] ?? null,
            'codigo_lote' => $data['codigo_lote'] ?? null,
            'data_validade' => $dataValidade,
            'data_colheita' => $data['data_colheita'] ?? null,
            'origem' => $data['origem'] ?? null,
            'quantidade_entrada' => $quantidade,
            'custo_unitario' => (float)($data['custo_unitario'] ?? 0),
            'status' => 'ativo',
        ]);

        http_response_code(201);
        Response::json(['id' => $id]);
    }

    /** PUT /api/v1/lotes/{id} — atualiza status ou quantidade atual */
    public function update(int $id): void
    {
        $data = Request::body();

        $sets   = [];
        $params = ['id' => $id];

        if (isset($data['status'])) {
            $allowed = ['ativo','quarentena','vencido','descartado'];
            if (in_array($data['status'], $allowed, true)) {
                $sets[]          = 'status = :status';
                $params['status'] = $data['status'];
            }
        }

        if (isset($data['quantidade_atual'])) {
            $sets[]                    = 'quantidade_atual = :qa';
            $params['qa']              = (float)$data['quantidade_atual'];
        }

        if (isset($data['data_validade'])) {
            $sets[]                   = 'data_validade = :dv';
            $params['dv']             = $data['data_validade'];
        }

        if (empty($sets)) {
            Response::error('Nenhum campo para atualizar', 400);
            return;
        }

        $updated = $this->repo->update($id, $data);

        if (!$updated) {
            Response::error('Lote não encontrado', 404);
            return;
        }

        Response::json(['success' => true]);
    }
}
