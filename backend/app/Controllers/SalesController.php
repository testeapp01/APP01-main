<?php
namespace App\Controllers;

use App\Repositories\SalesRepository;
use App\Repositories\ProductRepository;
use App\Services\SalesService;
use PDO;

class SalesController
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
            $where = 'WHERE c.nome LIKE :q OR p.nome LIKE :q';
            $params[':q'] = "%{$q}%";
        }

        // total
        $countSql = "SELECT COUNT(*) as total FROM vendas v LEFT JOIN clientes c ON v.cliente_id = c.id LEFT JOIN produtos p ON v.produto_id = p.id {$where}";
        $stmt = $this->pdo->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $sql = "SELECT v.id, c.nome AS cliente, p.nome AS produto, v.quantidade, v.valor_unitario, v.status FROM vendas v LEFT JOIN clientes c ON v.cliente_id = c.id LEFT JOIN produtos p ON v.produto_id = p.id {$where} ORDER BY v.id DESC LIMIT :limit OFFSET :offset";
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
        $data = \App\Helpers\Request::body();
        try {
            $salesRepo = new SalesRepository($this->pdo);
            $productRepo = new ProductRepository($this->pdo);
            $service = new SalesService($salesRepo, $productRepo);
            // normalize cliente_id
            if (empty($data['cliente_id']) && !empty($data['cliente'])) {
                $stmt = $this->pdo->prepare('SELECT id FROM clientes WHERE nome = :nome LIMIT 1');
                $stmt->execute([':nome' => $data['cliente']]);
                $cli = $stmt->fetch();
                if ($cli) $data['cliente_id'] = $cli['id'];
            }

            if (empty($data['cliente_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'cliente_id obrigatório']);
                return;
            }

            // Normalize produto_id: accept produto_id directly or try to resolve by name
            if (empty($data['produto_id'])) {
                $prodName = $data['produto'] ?? ($data['nome_produto'] ?? null);
                if ($prodName) {
                    $stmt = $this->pdo->prepare('SELECT id FROM produtos WHERE nome = :nome LIMIT 1');
                    $stmt->execute([':nome' => $prodName]);
                    $produto = $stmt->fetch();
                    if ($produto) $data['produto_id'] = $produto['id'];
                }
            }

            if (empty($data['produto_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'produto_id ou nome_produto obrigatório']);
                return;
            }
            $id = $service->createSale($data);
            // fetch created record
            $sale = (new SalesRepository($this->pdo))->findById((int)$id);
            http_response_code(201);
            echo json_encode(['id' => $id, 'sale' => $sale]);
        } catch (\Throwable $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function deliver(): void
    {
        $data = \App\Helpers\Request::body();
        $id = $data['venda_id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'venda_id obrigatório']);
            return;
        }

        try {
            $salesRepo = new SalesRepository($this->pdo);
            $productRepo = new ProductRepository($this->pdo);
            $service = new SalesService($salesRepo, $productRepo);
            $res = $service->confirmDelivery((int)$id);
            echo json_encode($res);
        } catch (\Throwable $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
