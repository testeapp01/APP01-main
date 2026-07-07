<?php
namespace App\Controllers;

use PDO;
use App\Helpers\Request;

class LoteController
{
    public function __construct(private PDO $pdo)
    {
    }

    /** GET /api/v1/lotes?produto_id=X — lista lotes (por produto, FEFO) */
    public function index(): void
    {
        $produtoId = isset($_GET['produto_id']) ? (int)$_GET['produto_id'] : 0;
        $status    = trim($_GET['status'] ?? '');

        $where  = [];
        $params = [];

        if ($produtoId > 0) {
            $where[]           = 'l.produto_id = :pid';
            $params[':pid']    = $produtoId;
        }

        if ($status !== '') {
            $allowed = ['ativo','quarentena','vencido','descartado'];
            if (in_array($status, $allowed, true)) {
                $where[]          = 'l.status = :status';
                $params[':status'] = $status;
            }
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $this->pdo->prepare(
            "SELECT l.*, p.nome AS produto_nome, p.unidade,
                    f.razao_social AS fornecedor_nome,
                    DATEDIFF(l.data_validade, CURDATE()) AS dias_para_vencer
             FROM lotes l
             JOIN produtos p ON p.id = l.produto_id
             LEFT JOIN fornecedores f ON f.id = l.fornecedor_id
             {$whereSql}
             ORDER BY l.data_validade ASC, l.id ASC"
        );
        $stmt->execute($params);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /** POST /api/v1/lotes — cria um novo lote */
    public function create(): void
    {
        $data = Request::body();

        $produtoId    = (int)($data['produto_id'] ?? 0);
        $dataValidade = trim((string)($data['data_validade'] ?? ''));
        $quantidade   = (float)($data['quantidade_entrada'] ?? 0);

        if ($produtoId <= 0 || $dataValidade === '' || $quantidade <= 0) {
            http_response_code(422);
            echo json_encode(['error' => 'produto_id, data_validade e quantidade_entrada são obrigatórios']);
            return;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO lotes
             (produto_id, fornecedor_id, compra_cabecalho_id, codigo_lote, data_validade,
              data_colheita, origem, quantidade_entrada, quantidade_atual, custo_unitario, status)
             VALUES (:pid, :fid, :cid, :cod, :dv, :dc, :orig, :qe, :qa, :cu, :st)'
        );
        $stmt->execute([
            'pid'  => $produtoId,
            'fid'  => $data['fornecedor_id'] ?? null,
            'cid'  => $data['compra_cabecalho_id'] ?? null,
            'cod'  => $data['codigo_lote'] ?? null,
            'dv'   => $dataValidade,
            'dc'   => $data['data_colheita'] ?? null,
            'orig' => $data['origem'] ?? null,
            'qe'   => $quantidade,
            'qa'   => $quantidade,
            'cu'   => (float)($data['custo_unitario'] ?? 0),
            'st'   => 'ativo',
        ]);

        http_response_code(201);
        echo json_encode(['id' => (int)$this->pdo->lastInsertId()]);
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
            http_response_code(400);
            echo json_encode(['error' => 'Nenhum campo para atualizar']);
            return;
        }

        $stmt = $this->pdo->prepare('UPDATE lotes SET ' . implode(', ', $sets) . ' WHERE id = :id');
        $stmt->execute($params);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Lote não encontrado']);
            return;
        }

        echo json_encode(['success' => true]);
    }
}
