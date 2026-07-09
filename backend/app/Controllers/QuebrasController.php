<?php
namespace App\Controllers;

use App\Repositories\QuebraRepository;
use PDO;
use App\Helpers\Request;
use App\Helpers\Response;

class QuebrasController
{
    private QuebraRepository $repo;

    public function __construct(private PDO $pdo, QuebraRepository $repo)
    {
        $this->repo = $repo;
    }

    /** GET /api/v1/quebras */
    public function index(): void
    {
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(1, min(100, (int)($_GET['per_page'] ?? 50)));
        $offset  = ($page - 1) * $perPage;

        $total = $this->repo->countAll();
        $items = $this->repo->fetchAll($perPage, $offset);

        Response::json(['items' => $items, 'total' => $total]);
    }

    /** POST /api/v1/quebras — registra perda e desconta do estoque */
    public function create(): void
    {
        $data      = Request::body();
        $produtoId = (int)($data['produto_id'] ?? 0);
        $quantidade = (float)($data['quantidade'] ?? 0);
        $valorUnit = (float)($data['valor_unitario'] ?? 0);
        $tipo      = trim((string)($data['tipo'] ?? 'outro'));
        $obs       = trim((string)($data['observacao'] ?? ''));

        if ($produtoId <= 0 || $quantidade <= 0) {
            Response::error('produto_id e quantidade são obrigatórios e devem ser > 0', 422);
            return;
        }

        $allowedTipos = ['deterioracao','acidente','roubo','vencimento','qualidade','outro'];
        if (!in_array($tipo, $allowedTipos, true)) $tipo = 'outro';

        $prod = $this->repo->findProduto($produtoId);

        if (!$prod) {
            Response::error('Produto não encontrado', 404);
            return;
        }

        // Use custo_medio if valor_unitario not informed
        if ($valorUnit <= 0) {
            $valorUnit = (float)$prod['custo_medio'];
        }

        $saldoAntes  = (float)$prod['estoque_atual'];
        $saldoDepois = max(0, $saldoAntes - $quantidade);
        $valorTotal  = $quantidade * $valorUnit;

        $authUser = $GLOBALS['AUTH_USER'] ?? [];
        $userId   = (int)($authUser['sub'] ?? 0);

        if ($userId <= 0) {
            Response::error('Usuário não autenticado', 401);
            return;
        }

        $this->pdo->beginTransaction();
        try {
            $quebraId = $this->repo->insert([
                'produto_id' => $produtoId,
                'lote_id' => $data['lote_id'] ?? null,
                'quantidade' => $quantidade,
                'valor_unitario' => $valorUnit,
                'valor_total' => $valorTotal,
                'tipo' => $tipo,
                'observacao' => $obs ?: null,
                'usuario_id' => $userId,
            ]);

            $this->repo->updateProdutoEstoque($produtoId, $saldoDepois);

            $this->repo->insertMovimentacaoEstoque([
                'produto_id' => $produtoId,
                'tipo' => 'quebra',
                'quantidade' => $quantidade,
                'valor_unitario' => $valorUnit,
                'saldo_antes' => $saldoAntes,
                'saldo_depois' => $saldoDepois,
                'referencia_id' => $quebraId,
                'referencia_tipo' => 'quebra',
                'observacao' => $obs ?: null,
                'usuario_id' => $userId,
            ]);

            $this->repo->updateLoteQuantidade((int)$data['lote_id'], $quantidade);

            $this->pdo->commit();
            http_response_code(201);
            Response::json(['id' => $quebraId, 'valor_total' => $valorTotal, 'saldo_atual' => $saldoDepois]);
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            Response::error('Falha ao registrar quebra', 500);
        }
    }

    public function tableExists(string $tabela): bool
    {
        return $this->repo->tableExists($tabela);
    }
}
