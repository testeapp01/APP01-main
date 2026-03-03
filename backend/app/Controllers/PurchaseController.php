<?php
namespace App\Controllers;

use PDO;
use App\Services\CommissionService;
use App\Helpers\Request;

class PurchaseController
{
    private ?array $comprasColumnsCache = null;
    private ?bool $hasComprasCabecalhoCache = null;

    public function __construct(private PDO $pdo)
    {
    }

    private function comprasColumns(): array
    {
        if ($this->comprasColumnsCache !== null) {
            return $this->comprasColumnsCache;
        }

        $stmt = $this->pdo->query('SHOW COLUMNS FROM compras');
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->comprasColumnsCache = array_values(array_filter(array_map(
            static fn(array $row) => $row['Field'] ?? null,
            $cols
        )));

        return $this->comprasColumnsCache;
    }

    private function hasComprasColumn(string $column): bool
    {
        return in_array($column, $this->comprasColumns(), true);
    }

    private function hasComprasCabecalhoTable(): bool
    {
        if ($this->hasComprasCabecalhoCache !== null) {
            return $this->hasComprasCabecalhoCache;
        }

        try {
            $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $stmt = $this->pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name='compras_cabecalho' LIMIT 1");
                $stmt->execute();
                $this->hasComprasCabecalhoCache = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
                return $this->hasComprasCabecalhoCache;
            }

            $stmt = $this->pdo->query("SHOW TABLES LIKE 'compras_cabecalho'");
            $this->hasComprasCabecalhoCache = (bool)$stmt->fetch(PDO::FETCH_NUM);
            return $this->hasComprasCabecalhoCache;
        } catch (\Throwable $e) {
            $this->hasComprasCabecalhoCache = false;
            return false;
        }
    }

    public function index(): void
    {
        if ($this->hasComprasCabecalhoTable() && $this->hasComprasColumn('compra_cabecalho_id')) {
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
            $where = 'WHERE f.razao_social LIKE :q OR p.nome LIKE :q OR cl.nome LIKE :q OR m.nome LIKE :q';
            $params[':q'] = "%{$q}%";
        }

        $countSql = "SELECT COUNT(*) as total FROM compras c LEFT JOIN fornecedores f ON c.fornecedor_id = f.id LEFT JOIN produtos p ON c.produto_id = p.id LEFT JOIN clientes cl ON c.cliente_id = cl.id LEFT JOIN motoristas m ON c.motorista_id = m.id {$where}";
        $stmt = $this->pdo->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $envioCol = $this->hasComprasColumn('data_envio_prevista') ? 'c.data_envio_prevista' : 'NULL AS data_envio_prevista';
        $entregaCol = $this->hasComprasColumn('data_entrega_prevista') ? 'c.data_entrega_prevista' : 'NULL AS data_entrega_prevista';
        $tipoOperacaoCol = $this->hasComprasColumn('tipo_operacao') ? 'c.tipo_operacao' : "'revenda' AS tipo_operacao";
        $clienteCol = $this->hasComprasColumn('cliente_id') ? 'cl.nome AS cliente' : 'NULL AS cliente';
        $sql = "SELECT c.id, f.razao_social AS fornecedor, {$clienteCol}, m.nome AS motorista, p.nome AS produto, {$tipoOperacaoCol}, c.quantidade, c.valor_unitario, c.status, c.data_compra, {$envioCol}, {$entregaCol} FROM compras c LEFT JOIN fornecedores f ON c.fornecedor_id = f.id LEFT JOIN produtos p ON c.produto_id = p.id LEFT JOIN clientes cl ON c.cliente_id = cl.id LEFT JOIN motoristas m ON c.motorista_id = m.id {$where} ORDER BY c.id DESC LIMIT :limit OFFSET :offset";
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
            $where = "WHERE f.razao_social LIKE :q OR cl.nome LIKE :q OR m.nome LIKE :q OR EXISTS (
                SELECT 1 FROM compras cc
                LEFT JOIN produtos pp ON pp.id = cc.produto_id
                WHERE cc.compra_cabecalho_id = h.id AND pp.nome LIKE :q
            )";
            $params[':q'] = "%{$q}%";
        }

        $countSql = "SELECT COUNT(*)
                     FROM compras_cabecalho h
                     LEFT JOIN fornecedores f ON f.id = h.fornecedor_id
                     LEFT JOIN clientes cl ON cl.id = h.cliente_id
                     LEFT JOIN motoristas m ON m.id = h.motorista_id
                     {$where}";
        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $sql = "SELECT
                    h.id,
                    h.tipo_operacao,
                    f.razao_social AS fornecedor,
                    cl.nome AS cliente,
                    m.nome AS motorista,
                    IFNULL(COUNT(c.id), 0) AS itens_count,
                    IFNULL(SUM(c.quantidade * c.valor_unitario), 0) AS valor_total,
                    h.status,
                    h.data_envio_prevista,
                    h.data_entrega_prevista
                FROM compras_cabecalho h
                LEFT JOIN fornecedores f ON f.id = h.fornecedor_id
                LEFT JOIN clientes cl ON cl.id = h.cliente_id
                LEFT JOIN motoristas m ON m.id = h.motorista_id
                LEFT JOIN compras c ON c.compra_cabecalho_id = h.id
                {$where}
                GROUP BY h.id, h.tipo_operacao, f.razao_social, cl.nome, m.nome, h.status, h.data_envio_prevista, h.data_entrega_prevista
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
        $data = Request::body();

        if (!empty($data['items']) && is_array($data['items']) && $this->hasComprasCabecalhoTable() && $this->hasComprasColumn('compra_cabecalho_id')) {
            $this->createWithHeader($data);
            return;
        }

        // basic validation
        $required = ['fornecedor_id', 'produto_id', 'quantidade', 'valor_unitario'];
        foreach ($required as $f) {
            if (empty($data[$f]) && $data[$f] !== 0) {
                http_response_code(400);
                echo json_encode(['error' => "Campo {$f} obrigatório"]);
                return;
            }
        }

        $tipoOperacao = ($data['tipo_operacao'] ?? (($data['tipo'] ?? 'revenda') === 'venda' ? 'venda' : 'revenda'));

        if ($tipoOperacao === 'venda' && empty($data['motorista_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Selecione um motorista para compras do tipo venda']);
            return;
        }

        if ($tipoOperacao === 'venda' && empty($data['cliente_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Selecione um cliente para compras do tipo venda']);
            return;
        }

        $quantidade = (float)$data['quantidade'];
        $valorUnitario = (float)$data['valor_unitario'];
        if ($quantidade <= 0 || $valorUnitario <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'quantidade e valor_unitario devem ser maiores que zero']);
            return;
        }

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

        $possible = [
            'fornecedor_id' => $data['fornecedor_id'],
            'produto_id' => $data['produto_id'],
            'motorista_id' => !empty($data['motorista_id']) ? (int)$data['motorista_id'] : null,
            'tipo_operacao' => $tipoOperacao,
            'cliente_id' => !empty($data['cliente_id']) ? (int)$data['cliente_id'] : null,
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
        ];

        $columns = array_values(array_filter(array_keys($possible), fn(string $column) => $this->hasComprasColumn($column)));
        $valuesSql = [];
        $params = [];

        foreach ($columns as $column) {
            $valuesSql[] = ':' . $column;
            $params[$column] = $possible[$column];
        }

        if ($this->hasComprasColumn('data_compra')) {
            $columns[] = 'data_compra';
            $valuesSql[] = 'NOW()';
        }

        try {
            $stmt = $this->pdo->prepare('INSERT INTO compras (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $valuesSql) . ')');
            $stmt->execute($params);
        } catch (\PDOException $e) {
            $mysqlCode = isset($e->errorInfo[1]) ? (int)$e->errorInfo[1] : 0;
            if ($mysqlCode === 1452) {
                http_response_code(400);
                echo json_encode(['error' => 'Fornecedor, produto ou motorista inválido.']);
                return;
            }

            http_response_code(500);
            echo json_encode(['error' => 'Falha ao salvar compra.']);
            return;
        }

        $id = (int)$this->pdo->lastInsertId();
        http_response_code(201);
        echo json_encode(['id' => $id, 'status' => 'NEGOCIADA', 'calcs' => $calcs]);
    }

    private function createWithHeader(array $data): void
    {
        if (empty($data['fornecedor_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'fornecedor_id obrigatório']);
            return;
        }

        $tipoOperacao = ($data['tipo_operacao'] ?? (($data['tipo'] ?? 'revenda') === 'venda' ? 'venda' : 'revenda'));
        if ($tipoOperacao === 'venda' && empty($data['cliente_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Selecione um cliente para compras do tipo venda']);
            return;
        }
        if ($tipoOperacao === 'venda' && empty($data['motorista_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Selecione um motorista para compras do tipo venda']);
            return;
        }

        $validItems = [];
        $valorTotalCabecalho = 0.0;
        foreach (($data['items'] ?? []) as $item) {
            if (empty($item['produto_id'])) {
                continue;
            }
            $qtd = (float)($item['quantidade'] ?? 0);
            $vu = (float)($item['valor_unitario'] ?? 0);
            if ($qtd <= 0 || $vu <= 0) {
                continue;
            }
            $validItems[] = [
                'produto_id' => (int)$item['produto_id'],
                'quantidade' => $qtd,
                'valor_unitario' => $vu,
            ];
            $valorTotalCabecalho += $qtd * $vu;
        }

        if (empty($validItems)) {
            http_response_code(400);
            echo json_encode(['error' => 'Adicione ao menos um item válido com produto_id, quantidade e valor_unitario']);
            return;
        }

        $status = $data['status'] ?? 'NEGOCIADA';
        $dataEnvio = !empty($data['data_envio_prevista']) ? $data['data_envio_prevista'] : null;
        $dataEntrega = !empty($data['data_entrega_prevista']) ? $data['data_entrega_prevista'] : null;

        $cabecalhoId = $this->createHeader(
            (int)$data['fornecedor_id'],
            $tipoOperacao,
            !empty($data['cliente_id']) ? (int)$data['cliente_id'] : null,
            !empty($data['motorista_id']) ? (int)$data['motorista_id'] : null,
            $valorTotalCabecalho,
            $dataEnvio,
            $dataEntrega,
            $status
        );

        $createdIds = [];
        foreach ($validItems as $item) {
            $calcs = CommissionService::calculate(
                $item['quantidade'],
                $item['valor_unitario'],
                $data['tipo_comissao'] ?? null,
                isset($data['valor_comissao']) ? (float)$data['valor_comissao'] : null,
                isset($data['extra_por_saco']) ? (float)$data['extra_por_saco'] : 0.0
            );

            $possible = [
                'compra_cabecalho_id' => $cabecalhoId,
                'fornecedor_id' => (int)$data['fornecedor_id'],
                'produto_id' => $item['produto_id'],
                'motorista_id' => !empty($data['motorista_id']) ? (int)$data['motorista_id'] : null,
                'tipo_operacao' => $tipoOperacao,
                'cliente_id' => !empty($data['cliente_id']) ? (int)$data['cliente_id'] : null,
                'quantidade' => $item['quantidade'],
                'valor_unitario' => $item['valor_unitario'],
                'tipo_comissao' => $data['tipo_comissao'] ?? null,
                'valor_comissao' => isset($data['valor_comissao']) ? (float)$data['valor_comissao'] : null,
                'extra_por_saco' => isset($data['extra_por_saco']) ? (float)$data['extra_por_saco'] : 0.0,
                'custo_total' => $calcs['valor_total'],
                'comissao_total' => $calcs['comissao_total'],
                'custo_final_real' => $calcs['custo_final_real'],
                'status' => $status,
                'data_envio_prevista' => $dataEnvio,
                'data_entrega_prevista' => $dataEntrega,
            ];

            $columns = array_values(array_filter(array_keys($possible), fn(string $column) => $this->hasComprasColumn($column)));
            $valuesSql = [];
            $params = [];

            foreach ($columns as $column) {
                $valuesSql[] = ':' . $column;
                $params[$column] = $possible[$column];
            }

            if ($this->hasComprasColumn('data_compra')) {
                $columns[] = 'data_compra';
                $valuesSql[] = 'NOW()';
            }

            try {
                $stmt = $this->pdo->prepare('INSERT INTO compras (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $valuesSql) . ')');
                $stmt->execute($params);
            } catch (\PDOException $e) {
                $mysqlCode = isset($e->errorInfo[1]) ? (int)$e->errorInfo[1] : 0;
                if ($mysqlCode === 1452) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Fornecedor, produto, cliente ou motorista inválido.']);
                    return;
                }

                http_response_code(500);
                echo json_encode(['error' => 'Falha ao salvar itens da compra.']);
                return;
            }

            $createdIds[] = (int)$this->pdo->lastInsertId();
        }

        http_response_code(201);
        echo json_encode([
            'id' => $createdIds[0] ?? null,
            'ids' => $createdIds,
            'cabecalho_id' => $cabecalhoId,
            'status' => $status,
        ]);
    }

    private function createHeader(
        int $fornecedorId,
        string $tipoOperacao,
        ?int $clienteId,
        ?int $motoristaId,
        float $valorTotal,
        ?string $dataEnvio,
        ?string $dataEntrega,
        string $status
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO compras_cabecalho (tipo_operacao, fornecedor_id, cliente_id, motorista_id, valor_total, data_envio_prevista, data_entrega_prevista, status)
             VALUES (:tipo_operacao, :fornecedor_id, :cliente_id, :motorista_id, :valor_total, :data_envio_prevista, :data_entrega_prevista, :status)'
        );

        $stmt->execute([
            'tipo_operacao' => $tipoOperacao,
            'fornecedor_id' => $fornecedorId,
            'cliente_id' => $clienteId,
            'motorista_id' => $motoristaId,
            'valor_total' => $valorTotal,
            'data_envio_prevista' => $dataEnvio,
            'data_entrega_prevista' => $dataEntrega,
            'status' => $status,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function showHeader(int $id): void
    {
        if (!$this->hasComprasCabecalhoTable() || !$this->hasComprasColumn('compra_cabecalho_id')) {
            http_response_code(404);
            echo json_encode(['error' => 'Cabeçalho de compra não disponível neste ambiente.']);
            return;
        }

        $headerStmt = $this->pdo->prepare(
            'SELECT h.id, h.tipo_operacao, h.valor_total, h.status, h.data_envio_prevista, h.data_entrega_prevista,
                    f.razao_social AS fornecedor, cl.nome AS cliente, m.nome AS motorista
             FROM compras_cabecalho h
             LEFT JOIN fornecedores f ON f.id = h.fornecedor_id
             LEFT JOIN clientes cl ON cl.id = h.cliente_id
             LEFT JOIN motoristas m ON m.id = h.motorista_id
             WHERE h.id = :id
             LIMIT 1'
        );
        $headerStmt->execute(['id' => $id]);
        $header = $headerStmt->fetch(PDO::FETCH_ASSOC);

        if (!$header) {
            http_response_code(404);
            echo json_encode(['error' => 'Pedido de compra não encontrado']);
            return;
        }

        $itemsStmt = $this->pdo->prepare(
            'SELECT c.id, p.nome AS produto, c.quantidade, c.valor_unitario, c.status, c.data_compra
             FROM compras c
             LEFT JOIN produtos p ON p.id = c.produto_id
             WHERE c.compra_cabecalho_id = :id
             ORDER BY c.id ASC'
        );
        $itemsStmt->execute(['id' => $id]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'header' => $header,
            'items' => $items,
        ]);
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
