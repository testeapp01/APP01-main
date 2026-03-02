<?php
namespace App\Controllers;

use App\Repositories\SalesRepository;
use App\Repositories\ProductRepository;
use App\Services\SalesService;
use PDO;

class SalesController
{
    private ?array $vendasColumnsCache = null;

    public function __construct(private PDO $pdo)
    {
    }

    private function vendasColumns(): array
    {
        if ($this->vendasColumnsCache !== null) {
            return $this->vendasColumnsCache;
        }

        $stmt = $this->pdo->query('SHOW COLUMNS FROM vendas');
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->vendasColumnsCache = array_values(array_filter(array_map(
            static fn(array $row) => $row['Field'] ?? null,
            $cols
        )));

        return $this->vendasColumnsCache;
    }

    private function hasVendasColumn(string $column): bool
    {
        return in_array($column, $this->vendasColumns(), true);
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

        $envioCol = $this->hasVendasColumn('data_envio_prevista') ? 'v.data_envio_prevista' : 'NULL AS data_envio_prevista';
        $entregaCol = $this->hasVendasColumn('data_entrega_prevista') ? 'v.data_entrega_prevista' : 'NULL AS data_entrega_prevista';
        $sql = "SELECT v.id, c.nome AS cliente, p.nome AS produto, v.quantidade, v.valor_unitario, v.status, v.data_venda, {$envioCol}, {$entregaCol} FROM vendas v LEFT JOIN clientes c ON v.cliente_id = c.id LEFT JOIN produtos p ON v.produto_id = p.id {$where} ORDER BY v.id DESC LIMIT :limit OFFSET :offset";
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

            $data['data_envio_prevista'] = !empty($data['data_envio_prevista']) ? $data['data_envio_prevista'] : null;
            $data['data_entrega_prevista'] = !empty($data['data_entrega_prevista']) ? $data['data_entrega_prevista'] : null;

            $createdIds = [];
            if (!empty($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    if (empty($item['produto_id'])) {
                        continue;
                    }
                    $saleData = [
                        'cliente_id' => $data['cliente_id'],
                        'produto_id' => (int)$item['produto_id'],
                        'quantidade' => (float)($item['quantidade'] ?? 0),
                        'valor_unitario' => (float)($item['valor_unitario'] ?? 0),
                        'status' => $data['status'] ?? 'ORCAMENTO',
                        'data_envio_prevista' => $data['data_envio_prevista'],
                        'data_entrega_prevista' => $data['data_entrega_prevista'],
                    ];
                    $createdIds[] = $service->createSale($saleData);
                }

                if (empty($createdIds)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Adicione ao menos um item válido com produto_id']);
                    return;
                }

                http_response_code(201);
                echo json_encode(['id' => $createdIds[0], 'ids' => $createdIds]);
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
            error_log('[SalesController::create] ' . $e->getMessage());
            echo json_encode(['error' => 'Não foi possível processar a venda.']);
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
            error_log('[SalesController::deliver] ' . $e->getMessage());
            echo json_encode(['error' => 'Não foi possível confirmar a entrega.']);
        }
    }
}
