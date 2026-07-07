<?php
namespace App\Controllers;

use PDO;
use App\Helpers\Request;
use App\Helpers\Response;

class QuebrasController
{
    public function __construct(private PDO $pdo)
    {
    }

    /** GET /api/v1/quebras */
    public function index(): void
    {
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(1, min(100, (int)($_GET['per_page'] ?? 50)));
        $offset  = ($page - 1) * $perPage;

        $countStmt = $this->pdo->query('SELECT COUNT(*) FROM quebras');
        $total     = (int)$countStmt->fetchColumn();

        $stmt = $this->pdo->prepare(
            "SELECT q.id, q.produto_id, p.nome AS produto, p.unidade,
                    q.lote_id, q.quantidade, q.valor_unitario,
                    q.valor_total, q.tipo, q.observacao,
                    q.created_at, u.name AS usuario
             FROM quebras q
             JOIN produtos p ON p.id = q.produto_id
             LEFT JOIN users u ON u.id = q.usuario_id
             ORDER BY q.created_at DESC
             LIMIT :lim OFFSET :off"
        );
        $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();

        Response::json(['items' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'total' => $total]);
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
            http_response_code(422);
            Response::json(['error' => 'produto_id e quantidade são obrigatórios e devem ser > 0']);
            return;
        }

        $allowedTipos = ['deterioracao','acidente','roubo','vencimento','qualidade','outro'];
        if (!in_array($tipo, $allowedTipos, true)) $tipo = 'outro';

        $prodStmt = $this->pdo->prepare('SELECT id, estoque_atual, custo_medio FROM produtos WHERE id = :id LIMIT 1');
        $prodStmt->execute(['id' => $produtoId]);
        $prod = $prodStmt->fetch(PDO::FETCH_ASSOC);

        if (!$prod) {
            http_response_code(404);
            Response::json(['error' => 'Produto não encontrado']);
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
            http_response_code(401);
            Response::json(['error' => 'Usuário não autenticado']);
            return;
        }

        $this->pdo->beginTransaction();
        try {
            $insQ = $this->pdo->prepare(
                'INSERT INTO quebras (produto_id, lote_id, quantidade, valor_unitario, valor_total, tipo, observacao, usuario_id)
                 VALUES (:pid, :lid, :qty, :vunit, :vtot, :tipo, :obs, :uid)'
            );
            $insQ->execute([
                'pid'  => $produtoId,
                'lid'  => $data['lote_id'] ?? null,
                'qty'  => $quantidade,
                'vunit'=> $valorUnit,
                'vtot' => $valorTotal,
                'tipo' => $tipo,
                'obs'  => $obs ?: null,
                'uid'  => $userId,
            ]);
            $quebraId = (int)$this->pdo->lastInsertId();

            // Update produto estoque_atual
            $upProd = $this->pdo->prepare('UPDATE produtos SET estoque_atual = :s WHERE id = :id');
            $upProd->execute(['s' => $saldoDepois, 'id' => $produtoId]);

            // Register in movimentacoes_estoque
            if ($this->tabelaExiste('movimentacoes_estoque')) {
                $insMov = $this->pdo->prepare(
                    'INSERT INTO movimentacoes_estoque
                     (produto_id, tipo, quantidade, valor_unitario, saldo_antes, saldo_depois, referencia_id, referencia_tipo, observacao, usuario_id)
                     VALUES (:pid, :tipo, :qty, :vunit, :sa, :sd, :rid, :rtp, :obs, :uid)'
                );
                $insMov->execute([
                    'pid'  => $produtoId,
                    'tipo' => 'quebra',
                    'qty'  => $quantidade,
                    'vunit'=> $valorUnit,
                    'sa'   => $saldoAntes,
                    'sd'   => $saldoDepois,
                    'rid'  => $quebraId,
                    'rtp'  => 'quebra',
                    'obs'  => $obs ?: null,
                    'uid'  => $userId,
                ]);
            }

            // Update lote if informed
            if (!empty($data['lote_id']) && $this->tabelaExiste('lotes')) {
                $upLote = $this->pdo->prepare(
                    'UPDATE lotes SET quantidade_atual = GREATEST(0, quantidade_atual - :qty) WHERE id = :id'
                );
                $upLote->execute(['qty' => $quantidade, 'id' => (int)$data['lote_id']]);
            }

            $this->pdo->commit();
            http_response_code(201);
            Response::json(['id' => $quebraId, 'valor_total' => $valorTotal, 'saldo_atual' => $saldoDepois]);
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            http_response_code(500);
            Response::json(['error' => 'Falha ao registrar quebra']);
        }
    }

    private function tabelaExiste(string $tabela): bool
    {
        try {
            $stmt = $this->pdo->prepare("SELECT 1 FROM {$tabela} LIMIT 1");
            $stmt->execute();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
