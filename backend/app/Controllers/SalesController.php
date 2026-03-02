<?php
namespace App\Controllers;

use App\Repositories\SalesRepository;
use App\Repositories\ProductRepository;
use App\Services\SalesService;
use PDO;

class SalesController
{
    private ?array $vendasColumnsCache = null;
    private ?bool $hasVendasCabecalhoCache = null;

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

    private function hasVendasCabecalhoTable(): bool
    {
        if ($this->hasVendasCabecalhoCache !== null) {
            return $this->hasVendasCabecalhoCache;
        }

        try {
            $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $stmt = $this->pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name='vendas_cabecalho' LIMIT 1");
                $stmt->execute();
                $this->hasVendasCabecalhoCache = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
                return $this->hasVendasCabecalhoCache;
            }

            $stmt = $this->pdo->query("SHOW TABLES LIKE 'vendas_cabecalho'");
            $this->hasVendasCabecalhoCache = (bool)$stmt->fetch(PDO::FETCH_NUM);
            return $this->hasVendasCabecalhoCache;
        } catch (\Throwable $e) {
            $this->hasVendasCabecalhoCache = false;
            return false;
        }
    }

    public function index(): void
    {
        if ($this->hasVendasCabecalhoTable()) {
            $this->indexHeaders();
            return;
        }

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

    private function indexHeaders(): void
    {
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $per = isset($_GET['per_page']) ? max(1, (int)$_GET['per_page']) : 25;
        $q = isset($_GET['q']) ? trim($_GET['q']) : '';
        $offset = ($page - 1) * $per;

        $where = '';
        $params = [];
        if ($q !== '') {
            $where = "WHERE c.nome LIKE :q OR EXISTS (
                SELECT 1
                FROM vendas vv
                LEFT JOIN produtos pp ON pp.id = vv.produto_id
                WHERE vv.venda_cabecalho_id = h.id AND pp.nome LIKE :q
            )";
            $params[':q'] = "%{$q}%";
        }

        $countSql = "SELECT COUNT(*)
                     FROM vendas_cabecalho h
                     LEFT JOIN clientes c ON c.id = h.cliente_id
                     {$where}";
        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $sql = "SELECT
                    h.id,
                    h.tipo,
                    c.nome AS cliente,
                    IFNULL(COUNT(v.id), 0) AS itens_count,
                    IFNULL(h.valor_total, 0) AS valor_total,
                    h.status,
                    h.data_inicio_prevista AS data_envio_prevista,
                    h.data_fim_prevista AS data_entrega_prevista
                FROM vendas_cabecalho h
                LEFT JOIN clientes c ON c.id = h.cliente_id
                LEFT JOIN vendas v ON v.venda_cabecalho_id = h.id
                {$where}
                GROUP BY h.id, h.tipo, c.nome, h.valor_total, h.status, h.data_inicio_prevista, h.data_fim_prevista
                ORDER BY h.id DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
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

            $tipoCabecalho = 'venda';

            $data['data_envio_prevista'] = !empty($data['data_envio_prevista']) ? $data['data_envio_prevista'] : null;
            $data['data_entrega_prevista'] = !empty($data['data_entrega_prevista']) ? $data['data_entrega_prevista'] : null;

            $createdIds = [];
            if (!empty($data['items']) && is_array($data['items'])) {
                $valorTotalCabecalho = 0.0;
                foreach ($data['items'] as $item) {
                    if (!empty($item['produto_id'])) {
                        $valorTotalCabecalho += (float)($item['quantidade'] ?? 0) * (float)($item['valor_unitario'] ?? 0);
                    }
                }

                $cabecalhoId = null;
                if ($this->hasVendasCabecalhoTable() && $this->hasVendasColumn('venda_cabecalho_id')) {
                    $cabecalhoId = $this->createHeader(
                        (int)$data['cliente_id'],
                        $tipoCabecalho,
                        $valorTotalCabecalho,
                        $data['data_envio_prevista'],
                        $data['data_entrega_prevista'],
                        $data['status'] ?? 'ORCAMENTO'
                    );
                }

                foreach ($data['items'] as $item) {
                    if (empty($item['produto_id'])) {
                        continue;
                    }
                    $saleData = [
                        'venda_cabecalho_id' => $cabecalhoId,
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
                echo json_encode(['id' => $createdIds[0], 'ids' => $createdIds, 'cabecalho_id' => $cabecalhoId]);
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

            $cabecalhoId = null;
            if ($this->hasVendasCabecalhoTable() && $this->hasVendasColumn('venda_cabecalho_id')) {
                $cabecalhoId = $this->createHeader(
                    (int)$data['cliente_id'],
                    $tipoCabecalho,
                    (float)($data['quantidade'] ?? 0) * (float)($data['valor_unitario'] ?? 0),
                    $data['data_envio_prevista'],
                    $data['data_entrega_prevista'],
                    $data['status'] ?? 'ORCAMENTO'
                );
                $data['venda_cabecalho_id'] = $cabecalhoId;
            }

            $id = $service->createSale($data);
            // fetch created record
            $sale = (new SalesRepository($this->pdo))->findById((int)$id);
            http_response_code(201);
            echo json_encode(['id' => $id, 'sale' => $sale, 'cabecalho_id' => $cabecalhoId]);
        } catch (\Throwable $e) {
            http_response_code(400);
            error_log('[SalesController::create] ' . $e->getMessage());
            echo json_encode(['error' => 'Não foi possível processar a venda.']);
        }
    }

    public function showHeader(int $id): void
    {
        if (!$this->hasVendasCabecalhoTable()) {
            http_response_code(404);
            echo json_encode(['error' => 'Cabeçalho de venda não disponível neste ambiente.']);
            return;
        }

        $headerStmt = $this->pdo->prepare(
            'SELECT h.id, h.tipo, h.valor_total, h.status, h.data_inicio_prevista, h.data_fim_prevista, c.nome AS cliente
             FROM vendas_cabecalho h
             LEFT JOIN clientes c ON c.id = h.cliente_id
             WHERE h.id = :id
             LIMIT 1'
        );
        $headerStmt->execute(['id' => $id]);
        $header = $headerStmt->fetch(PDO::FETCH_ASSOC);

        if (!$header) {
            http_response_code(404);
            echo json_encode(['error' => 'Pedido não encontrado']);
            return;
        }

        $itemsStmt = $this->pdo->prepare(
            'SELECT v.id, p.nome AS produto, v.quantidade, v.valor_unitario, v.status, v.data_venda
             FROM vendas v
             LEFT JOIN produtos p ON p.id = v.produto_id
             WHERE v.venda_cabecalho_id = :id
             ORDER BY v.id ASC'
        );
        $itemsStmt->execute(['id' => $id]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'header' => $header,
            'items' => $items,
        ]);
    }

    public function deliver(): void
    {
        $data = \App\Helpers\Request::body();
        $headerId = $data['venda_cabecalho_id'] ?? null;

        if ($headerId && $this->hasVendasCabecalhoTable()) {
            try {
                $salesRepo = new SalesRepository($this->pdo);
                $productRepo = new ProductRepository($this->pdo);
                $service = new SalesService($salesRepo, $productRepo);

                $stmt = $this->pdo->prepare('SELECT id FROM vendas WHERE venda_cabecalho_id = :id AND status <> :status');
                $stmt->execute(['id' => (int)$headerId, 'status' => 'ENTREGUE']);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (!$rows) {
                    echo json_encode(['message' => 'Pedido já entregue']);
                    return;
                }

                $novoEstoque = null;
                foreach ($rows as $row) {
                    $res = $service->confirmDelivery((int)$row['id']);
                    $novoEstoque = $res['novo_estoque'] ?? $novoEstoque;
                }

                $up = $this->pdo->prepare('UPDATE vendas_cabecalho SET status = :status WHERE id = :id');
                $up->execute(['status' => 'ENTREGUE', 'id' => (int)$headerId]);

                echo json_encode(['message' => 'ENTREGUE', 'novo_estoque' => $novoEstoque]);
                return;
            } catch (\Throwable $e) {
                http_response_code(400);
                error_log('[SalesController::deliverHeader] ' . $e->getMessage());
                echo json_encode(['error' => 'Não foi possível confirmar a entrega do pedido.']);
                return;
            }
        }

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

            if ($this->hasVendasCabecalhoTable() && $this->hasVendasColumn('venda_cabecalho_id')) {
                $hs = $this->pdo->prepare('SELECT venda_cabecalho_id FROM vendas WHERE id = :id LIMIT 1');
                $hs->execute(['id' => (int)$id]);
                $headerRef = $hs->fetch(PDO::FETCH_ASSOC);
                $hid = isset($headerRef['venda_cabecalho_id']) ? (int)$headerRef['venda_cabecalho_id'] : 0;
                if ($hid > 0) {
                    $pendingStmt = $this->pdo->prepare('SELECT COUNT(*) FROM vendas WHERE venda_cabecalho_id = :id AND status <> :status');
                    $pendingStmt->execute(['id' => $hid, 'status' => 'ENTREGUE']);
                    $pending = (int)$pendingStmt->fetchColumn();
                    if ($pending === 0) {
                        $up = $this->pdo->prepare('UPDATE vendas_cabecalho SET status = :status WHERE id = :id');
                        $up->execute(['status' => 'ENTREGUE', 'id' => $hid]);
                    }
                }
            }

            echo json_encode($res);
        } catch (\Throwable $e) {
            http_response_code(400);
            error_log('[SalesController::deliver] ' . $e->getMessage());
            echo json_encode(['error' => 'Não foi possível confirmar a entrega.']);
        }
    }

    private function createHeader(
        int $clienteId,
        string $tipo,
        float $valorTotal,
        ?string $dataInicio,
        ?string $dataFim,
        string $status
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO vendas_cabecalho (tipo, cliente_id, valor_total, data_inicio_prevista, data_fim_prevista, status)
             VALUES (:tipo, :cliente_id, :valor_total, :data_inicio_prevista, :data_fim_prevista, :status)'
        );

        $stmt->execute([
            'tipo' => $tipo,
            'cliente_id' => $clienteId,
            'valor_total' => $valorTotal,
            'data_inicio_prevista' => $dataInicio,
            'data_fim_prevista' => $dataFim,
            'status' => $status,
        ]);

        return (int)$this->pdo->lastInsertId();
    }
}
