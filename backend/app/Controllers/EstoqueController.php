<?php
namespace App\Controllers;

use PDO;
use App\Helpers\Request;

class EstoqueController
{
    public function __construct(private PDO $pdo)
    {
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

        $countStmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM movimentacoes_estoque me
             JOIN produtos p ON p.id = me.produto_id
             {$whereSql}"
        );
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $this->pdo->prepare(
            "SELECT me.id, me.produto_id, p.nome AS produto, p.unidade,
                    me.tipo AS tipo_movimento, me.quantidade, me.valor_unitario,
                    me.saldo_antes, me.saldo_depois,
                    me.referencia_id, me.referencia_tipo, me.observacao,
                    me.created_at AS data
             FROM movimentacoes_estoque me
             JOIN produtos p ON p.id = me.produto_id
             {$whereSql}
             ORDER BY me.created_at DESC, me.id DESC
             LIMIT :lim OFFSET :off"
        );
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['items' => $items, 'total' => $total]);
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

        $stmt = $this->pdo->prepare(
            "SELECT p.id, p.nome, p.unidade, p.status,
                    p.estoque_atual,
                    p.estoque_reservado,
                    GREATEST(0, p.estoque_atual - p.estoque_reservado) AS disponivel,
                    p.custo_medio
             FROM produtos p
             {$where}
             ORDER BY p.nome ASC"
        );
        $stmt->execute($params);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
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
            http_response_code(422);
            echo json_encode(['error' => 'produto_id e quantidade são obrigatórios e devem ser > 0']);
            return;
        }

        $allowed = ['ajuste_manual'];
        if (!in_array($tipo, $allowed, true)) {
            $tipo = 'ajuste_manual';
        }

        $prodStmt = $this->pdo->prepare('SELECT id, estoque_atual, custo_medio FROM produtos WHERE id = :id LIMIT 1');
        $prodStmt->execute(['id' => $produtoId]);
        $prod = $prodStmt->fetch(PDO::FETCH_ASSOC);

        if (!$prod) {
            http_response_code(404);
            echo json_encode(['error' => 'Produto não encontrado']);
            return;
        }

        $saldoAntes = (float)$prod['estoque_atual'];
        $isEntrada  = isset($data['direcao']) ? $data['direcao'] === 'entrada' : true;
        $delta      = $isEntrada ? $quantidade : -$quantidade;
        $saldoDepois = max(0, $saldoAntes + $delta);

        $this->pdo->beginTransaction();
        try {
            $upProd = $this->pdo->prepare('UPDATE produtos SET estoque_atual = :s WHERE id = :id');
            $upProd->execute(['s' => $saldoDepois, 'id' => $produtoId]);

            $authUser = $GLOBALS['AUTH_USER'] ?? [];
            $userId   = (int)($authUser['sub'] ?? 0) ?: null;

            $ins = $this->pdo->prepare(
                'INSERT INTO movimentacoes_estoque
                 (produto_id, tipo, quantidade, valor_unitario, saldo_antes, saldo_depois, observacao, usuario_id)
                 VALUES (:pid, :tipo, :qty, :vunit, :sa, :sd, :obs, :uid)'
            );
            $ins->execute([
                'pid'  => $produtoId,
                'tipo' => $tipo,
                'qty'  => $quantidade,
                'vunit'=> $valorUnit,
                'sa'   => $saldoAntes,
                'sd'   => $saldoDepois,
                'obs'  => $obs ?: null,
                'uid'  => $userId,
            ]);

            $this->pdo->commit();
            http_response_code(201);
            echo json_encode(['success' => true, 'saldo' => $saldoDepois]);
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Falha ao registrar movimentação']);
        }
    }

    /** DELETE /api/v1/estoque/{id} — apenas ajustes manuais podem ser removidos (soft) */
    public function delete(int $id): void
    {
        http_response_code(400);
        echo json_encode(['error' => 'Movimentações automáticas não podem ser excluídas. Registre um ajuste manual para corrigir.']);
    }
}
