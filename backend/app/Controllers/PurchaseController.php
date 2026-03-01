<?php
namespace App\Controllers;

use PDO;
use App\Services\CommissionService;
use App\Helpers\Request;

class PurchaseController
{
    public function __construct(private PDO $pdo)
    {
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
            $where = 'WHERE f.razao_social LIKE :q OR p.nome LIKE :q';
            $params[':q'] = "%{$q}%";
        }

        $countSql = "SELECT COUNT(*) as total FROM compras c LEFT JOIN fornecedores f ON c.fornecedor_id = f.id LEFT JOIN produtos p ON c.produto_id = p.id {$where}";
        $stmt = $this->pdo->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $sql = "SELECT c.id, f.razao_social AS fornecedor, p.nome AS produto, c.quantidade, c.valor_unitario, c.status, c.data_compra, c.data_envio_prevista, c.data_entrega_prevista FROM compras c LEFT JOIN fornecedores f ON c.fornecedor_id = f.id LEFT JOIN produtos p ON c.produto_id = p.id {$where} ORDER BY c.id DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit', $per, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['items' => $items, 'total' => $total]);
    }

    public function create(): void
    {
        $data = Request::body();

        // basic validation
        $required = ['fornecedor_id', 'produto_id', 'motorista_id', 'quantidade', 'valor_unitario'];
        foreach ($required as $f) {
            if (empty($data[$f]) && $data[$f] !== 0) {
                http_response_code(400);
                echo json_encode(['error' => "Campo {$f} obrigatório"]);
                return;
            }
        }

        // must have motorista
        if (empty($data['motorista_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Não é possível finalizar compra sem motorista definido']);
            return;
        }

        $quantidade = (float)$data['quantidade'];
        $valorUnitario = (float)$data['valor_unitario'];

        $tipoComissao = $data['tipo_comissao'] ?? null; // 'percentual'|'fixa'|null
        $valorComissao = isset($data['valor_comissao']) ? (float)$data['valor_comissao'] : null;
        $extraPorSaco = isset($data['extra_por_saco']) ? (float)$data['extra_por_saco'] : 0.0;

        $calcs = CommissionService::calculate($quantidade, $valorUnitario, $tipoComissao, $valorComissao, $extraPorSaco);

        // If frontend posted fornecedor_id / produto_id already, use them directly.
        // Otherwise, attempt to resolve from provided names (razao_social / nome_produto) — optional.
        if (empty($data['fornecedor_id']) && !empty($data['razao_social'])) {
            $stmt = $this->pdo->prepare('SELECT id FROM fornecedores WHERE razao_social = :razao LIMIT 1');
            $stmt->execute([':razao' => $data['razao_social']]);
            $forn = $stmt->fetch();
            if ($forn) $data['fornecedor_id'] = $forn['id'];
        }

        if (empty($data['produto_id']) && !empty($data['nome_produto'])) {
            $stmt = $this->pdo->prepare('SELECT id FROM produtos WHERE nome = :nome LIMIT 1');
            $stmt->execute([':nome' => $data['nome_produto']]);
            $prod = $stmt->fetch();
            if ($prod) $data['produto_id'] = $prod['id'];
        }

        $stmt = $this->pdo->prepare('INSERT INTO compras (fornecedor_id, produto_id, motorista_id, quantidade, valor_unitario, tipo_comissao, valor_comissao, extra_por_saco, custo_total, comissao_total, custo_final_real, status, data_compra, data_envio_prevista, data_entrega_prevista) VALUES (:fornecedor_id, :produto_id, :motorista_id, :quantidade, :valor_unitario, :tipo_comissao, :valor_comissao, :extra_por_saco, :custo_total, :comissao_total, :custo_final_real, :status, NOW(), :data_envio_prevista, :data_entrega_prevista)');

        $stmt->execute([
            'fornecedor_id' => $data['fornecedor_id'],
            'produto_id' => $data['produto_id'],
            'motorista_id' => $data['motorista_id'],
            'quantidade' => $quantidade,
            'valor_unitario' => $valorUnitario,
            'tipo_comissao' => $tipoComissao,
            'valor_comissao' => $valorComissao,
            'extra_por_saco' => $extraPorSaco,
            'custo_total' => $calcs['valor_total'],
            'comissao_total' => $calcs['comissao_total'],
            'custo_final_real' => $calcs['custo_final_real'],
            'status' => 'NEGOCIADA',
            'data_envio_prevista' => !empty($data['data_envio_prevista']) ? $data['data_envio_prevista'] : null,
            'data_entrega_prevista' => !empty($data['data_entrega_prevista']) ? $data['data_entrega_prevista'] : null,
        ]);

        $id = (int)$this->pdo->lastInsertId();
        http_response_code(201);
        echo json_encode(['id' => $id, 'status' => 'NEGOCIADA', 'calcs' => $calcs]);
    }

    public function receive(): void
    {
        $data = Request::body();
        $id = $data['compra_id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'compra_id obrigatório']);
            return;
        }

        // fetch compra
        $stmt = $this->pdo->prepare('SELECT * FROM compras WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $compra = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$compra) {
            http_response_code(404);
            echo json_encode(['error' => 'Compra não encontrada']);
            return;
        }

        if ($compra['status'] === 'RECEBIDA') {
            echo json_encode(['message' => 'Compra já recebida']);
            return;
        }

        // update compra status
        $u = $this->pdo->prepare('UPDATE compras SET status = :status WHERE id = :id');
        $u->execute(['status' => 'RECEBIDA', 'id' => $id]);

        // update estoque and custo médio ponderado
        $produtoId = $compra['produto_id'];
        $quantidade = (float)$compra['quantidade'];
        $valorUnitario = (float)$compra['valor_unitario'];
        $comissaoTotal = (float)$compra['comissao_total'];

        // get current stock
        $s = $this->pdo->prepare('SELECT estoque_atual, custo_medio FROM produtos WHERE id = :id LIMIT 1');
        $s->execute(['id' => $produtoId]);
        $prod = $s->fetch(PDO::FETCH_ASSOC);

        $oldQty = $prod ? (float)$prod['estoque_atual'] : 0.0;
        $oldCost = $prod ? (float)$prod['custo_medio'] : 0.0;

        $valorTotalCompra = ($quantidade * $valorUnitario) + $comissaoTotal;

        $newQty = $oldQty + $quantidade;
        $newCost = 0.0;
        if ($newQty > 0) {
            $newCost = (($oldQty * $oldCost) + $valorTotalCompra) / $newQty;
        }

        $up = $this->pdo->prepare('UPDATE produtos SET estoque_atual = :estoque, custo_medio = :custo WHERE id = :id');
        $up->execute(['estoque' => $newQty, 'custo' => $newCost, 'id' => $produtoId]);

        echo json_encode(['message' => 'Compra marcada como RECEBIDA e estoque atualizado', 'produto_id' => $produtoId, 'novo_estoque' => $newQty, 'novo_custo_medio' => $newCost]);
    }
}
