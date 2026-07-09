<?php
namespace App\Controllers;

use App\Repositories\EstoqueRepository;
use PDO;
use App\Helpers\Request;
use App\Helpers\Response;

class EstoqueController
{
    private EstoqueRepository $repo;

    public function __construct(private PDO $pdo, EstoqueRepository $repo)
    {
        $this->repo = $repo;
    }

    /** GET /api/v1/estoque — movimentações com nome do produto */
    public function index(): void
    {
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(1, min(200, (int)($_GET['per_page'] ?? 50)));
        $offset  = ($page - 1) * $perPage;
        $tipo    = $_GET['tipo'] ?? '';
        $q       = trim($_GET['q'] ?? '');

        $where  = [];
        $params = [];

        if ($tipo !== '') {
            $allowed = ['entrada_compra','saida_venda','ajuste_manual','quebra','reserva','cancelamento_reserva'];
            if (in_array($tipo, $allowed, true)) {
                $where[]           = 'me.tipo = :tipo';
                $params[':tipo']   = $tipo;
            }
        }

        if ($q !== '') {
            $where[]        = 'p.nome LIKE :q';
            $params[':q']   = "%{$q}%";
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $filters = ['tipo' => $tipo !== '' ? $tipo : null, 'q' => $q];
        $total = $this->repo->countMovimentacoes($filters);
        $items = $this->repo->fetchMovimentacoes($filters, $perPage, $offset);

        Response::json(['items' => $items, 'total' => $total]);
    }

    /** GET /api/v1/estoque/saldos — saldo atual por produto */
    public function saldos(): void
    {
        $q      = trim($_GET['q'] ?? '');
        $where  = "WHERE p.deleted_at IS NULL";
        $params = [];

        if ($q !== '') {
            $where         .= ' AND p.nome LIKE :q';
            $params[':q']   = "%{$q}%";
        }

        Response::json($this->repo->fetchSaldos($q));
    }

    /** POST /api/v1/estoque — ajuste manual de estoque */
    public function create(): void
    {
        $data       = Request::body();
        $produtoId  = (int)($data['produto_id'] ?? 0);
        $quantidade = (float)($data['quantidade'] ?? 0);
        $tipo       = trim((string)($data['tipo_movimento'] ?? 'ajuste_manual'));
        $obs        = trim((string)($data['observacao'] ?? ''));
        $valorUnit  = (float)($data['valor_unitario'] ?? 0);

        if ($produtoId <= 0 || $quantidade <= 0) {
            Response::error('produto_id e quantidade são obrigatórios e devem ser > 0', 422);
            return;
        }

        $allowed = ['ajuste_manual'];
        if (!in_array($tipo, $allowed, true)) {
            $tipo = 'ajuste_manual';
        }

        $prod = $this->repo->findProduto($produtoId);

        if (!$prod) {
            Response::error('Produto não encontrado', 404);
            return;
        }

        $saldoAntes = (float)$prod['estoque_atual'];
        $isEntrada  = isset($data['direcao']) ? $data['direcao'] === 'entrada' : true;
        $delta      = $isEntrada ? $quantidade : -$quantidade;
        $saldoDepois = $saldoAntes + $delta;

        if (!$isEntrada && $saldoDepois < 0) {
            Response::error('Estoque insuficiente para saída', 422);
            return;
        }

        $this->pdo->beginTransaction();
        try {
            $this->repo->updateProdutoEstoque($produtoId, $saldoDepois);

            $authUser = $GLOBALS['AUTH_USER'] ?? [];
            $userId   = (int)($authUser['sub'] ?? 0) ?: null;

            $this->repo->insertMovimentacao([
                'produto_id' => $produtoId,
                'tipo' => $tipo,
                'quantidade' => $quantidade,
                'valor_unitario' => $valorUnit,
                'saldo_antes' => $saldoAntes,
                'saldo_depois' => $saldoDepois,
                'observacao' => $obs ?: null,
                'usuario_id' => $userId,
            ]);

            $this->pdo->commit();
            http_response_code(201);
            Response::json(['success' => true, 'saldo' => $saldoDepois]);
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            Response::error('Falha ao registrar movimentação', 500);
        }
    }

    /** DELETE /api/v1/estoque/{id} — apenas ajustes manuais podem ser removidos (soft) */
    public function delete(int $id): void
    {
        Response::error('Movimentações automáticas não podem ser excluídas. Registre um ajuste manual para corrigir.', 400);
    }
}
