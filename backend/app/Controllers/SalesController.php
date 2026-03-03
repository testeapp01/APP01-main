<?php
namespace App\Controllers;

use App\Repositories\SalesRepository;
use App\Repositories\ProductRepository;
use App\Services\SalesService;
use PDO;

class SalesController
{
    private ?array $vendasColumnsCache = null;
    private ?array $vendasCabecalhoColumnsCache = null;
    private ?array $historicoStatusPedidoColumnsCache = null;
    private ?bool $hasVendasCabecalhoCache = null;
    private ?bool $hasStatusPedidoCache = null;
    private ?bool $hasHistoricoStatusPedidoCache = null;

    public function __construct(private PDO $pdo)
    {
    }

    private function vendasColumns(): array
    {
        if ($this->vendasColumnsCache !== null) {
            return $this->vendasColumnsCache;
        }

        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $stmt = $this->pdo->query('PRAGMA table_info(vendas)');
            $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->vendasColumnsCache = array_values(array_filter(array_map(
                static fn(array $row) => $row['name'] ?? null,
                $cols
            )));

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

    private function vendasCabecalhoColumns(): array
    {
        if ($this->vendasCabecalhoColumnsCache !== null) {
            return $this->vendasCabecalhoColumnsCache;
        }

        if (!$this->hasVendasCabecalhoTable()) {
            $this->vendasCabecalhoColumnsCache = [];
            return $this->vendasCabecalhoColumnsCache;
        }

        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $stmt = $this->pdo->query('PRAGMA table_info(vendas_cabecalho)');
            $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->vendasCabecalhoColumnsCache = array_values(array_filter(array_map(
                static fn(array $row) => $row['name'] ?? null,
                $cols
            )));
        } else {
            $stmt = $this->pdo->query('SHOW COLUMNS FROM vendas_cabecalho');
            $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->vendasCabecalhoColumnsCache = array_values(array_filter(array_map(
                static fn(array $row) => $row['Field'] ?? null,
                $cols
            )));
        }

        return $this->vendasCabecalhoColumnsCache;
    }

    private function hasVendasCabecalhoColumn(string $column): bool
    {
        return in_array($column, $this->vendasCabecalhoColumns(), true);
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

    private function hasStatusPedidoTable(): bool
    {
        if ($this->hasStatusPedidoCache !== null) {
            return $this->hasStatusPedidoCache;
        }

        try {
            $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $stmt = $this->pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name='status_pedido' LIMIT 1");
                $stmt->execute();
                $this->hasStatusPedidoCache = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
                return $this->hasStatusPedidoCache;
            }

            $stmt = $this->pdo->query("SHOW TABLES LIKE 'status_pedido'");
            $this->hasStatusPedidoCache = (bool)$stmt->fetch(PDO::FETCH_NUM);
            return $this->hasStatusPedidoCache;
        } catch (\Throwable $e) {
            $this->hasStatusPedidoCache = false;
            return false;
        }
    }

    private function hasHistoricoStatusPedidoTable(): bool
    {
        if ($this->hasHistoricoStatusPedidoCache !== null) {
            return $this->hasHistoricoStatusPedidoCache;
        }

        try {
            $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $stmt = $this->pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name='historico_status_pedido' LIMIT 1");
                $stmt->execute();
                $this->hasHistoricoStatusPedidoCache = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
                return $this->hasHistoricoStatusPedidoCache;
            }

            $stmt = $this->pdo->query("SHOW TABLES LIKE 'historico_status_pedido'");
            $this->hasHistoricoStatusPedidoCache = (bool)$stmt->fetch(PDO::FETCH_NUM);
            return $this->hasHistoricoStatusPedidoCache;
        } catch (\Throwable $e) {
            $this->hasHistoricoStatusPedidoCache = false;
            return false;
        }
    }

    private function historicoStatusPedidoColumns(): array
    {
        if ($this->historicoStatusPedidoColumnsCache !== null) {
            return $this->historicoStatusPedidoColumnsCache;
        }

        if (!$this->hasHistoricoStatusPedidoTable()) {
            $this->historicoStatusPedidoColumnsCache = [];
            return $this->historicoStatusPedidoColumnsCache;
        }

        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $stmt = $this->pdo->query('PRAGMA table_info(historico_status_pedido)');
            $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->historicoStatusPedidoColumnsCache = array_values(array_filter(array_map(
                static fn(array $row) => $row['name'] ?? null,
                $cols
            )));
        } else {
            $stmt = $this->pdo->query('SHOW COLUMNS FROM historico_status_pedido');
            $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->historicoStatusPedidoColumnsCache = array_values(array_filter(array_map(
                static fn(array $row) => $row['Field'] ?? null,
                $cols
            )));
        }

        return $this->historicoStatusPedidoColumnsCache;
    }

    private function hasHistoricoStatusPedidoColumn(string $column): bool
    {
        return in_array($column, $this->historicoStatusPedidoColumns(), true);
    }

    private function normalizeStatusPedidoLabel(?string $status): string
    {
        $value = strtoupper(trim((string)$status));
        return $value === 'ENTREGUE' ? 'ENTREGUE' : 'AGUARDANDO';
    }

    private function statusPedidoIdByNome(string $nome): ?int
    {
        if (!$this->hasStatusPedidoTable()) {
            return null;
        }

        $stmt = $this->pdo->prepare('SELECT id FROM status_pedido WHERE UPPER(nome) = :nome LIMIT 1');
        $stmt->execute(['nome' => strtoupper($nome)]);
        $id = $stmt->fetchColumn();

        return $id !== false ? (int)$id : null;
    }

    private function currentUserId(): ?int
    {
        $auth = $GLOBALS['AUTH_USER'] ?? null;
        if (!is_array($auth) || !isset($auth['sub'])) {
            return null;
        }

        $userId = (int)$auth['sub'];
        return $userId > 0 ? $userId : null;
    }

    private function currentHeaderStatusPedido(int $headerId): ?string
    {
        if (!$this->hasVendasCabecalhoTable()) {
            return null;
        }

        $stmt = $this->pdo->prepare('SELECT status FROM vendas_cabecalho WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $headerId]);
        $status = $stmt->fetchColumn();

        if ($status === false) {
            return null;
        }

        return $this->normalizeStatusPedidoLabel((string)$status);
    }

    private function canTransitionPedido(string $from, string $to): bool
    {
        if ($from === $to) {
            return true;
        }

        return $from === 'AGUARDANDO' && $to === 'ENTREGUE';
    }

    private function buildSalesSnapshot(int $headerId): ?string
    {
        try {
            $headerStmt = $this->pdo->prepare('SELECT id, valor_total, status FROM vendas_cabecalho WHERE id = :id LIMIT 1');
            $headerStmt->execute(['id' => $headerId]);
            $header = $headerStmt->fetch(PDO::FETCH_ASSOC);
            if (!$header) {
                return null;
            }

            $itemsStmt = $this->pdo->prepare(
                'SELECT v.id, v.produto_id, v.quantidade, v.valor_unitario, v.status, p.estoque_atual, p.custo_medio
                 FROM vendas v
                 LEFT JOIN produtos p ON p.id = v.produto_id
                 WHERE v.venda_cabecalho_id = :id
                 ORDER BY v.id ASC'
            );
            $itemsStmt->execute(['id' => $headerId]);
            $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

            return json_encode([
                'header' => [
                    'id' => (int)$header['id'],
                    'valor_total' => (float)($header['valor_total'] ?? 0),
                    'status' => $this->normalizeStatusPedidoLabel((string)($header['status'] ?? '')),
                ],
                'items' => $items,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function registrarHistoricoStatus(int $headerId, ?int $usuarioId, int $statusId, ?string $snapshotJson = null): void
    {
        if (!$this->hasHistoricoStatusPedidoTable()) {
            return;
        }

        if ($this->hasHistoricoStatusPedidoColumn('snapshot_json')) {
            $sql = 'INSERT INTO historico_status_pedido (venda_cabecalho_id, usuario_id, id_statuspedido, snapshot_json, confirmado_em)
                VALUES (:venda_cabecalho_id, :usuario_id, :id_statuspedido, :snapshot_json, CURRENT_TIMESTAMP)';
        } else {
            $sql = 'INSERT INTO historico_status_pedido (venda_cabecalho_id, usuario_id, id_statuspedido, confirmado_em)
                VALUES (:venda_cabecalho_id, :usuario_id, :id_statuspedido, CURRENT_TIMESTAMP)';
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue('venda_cabecalho_id', $headerId, PDO::PARAM_INT);
        if ($usuarioId !== null) {
            $stmt->bindValue('usuario_id', $usuarioId, PDO::PARAM_INT);
        } else {
            $stmt->bindValue('usuario_id', null, PDO::PARAM_NULL);
        }
        $stmt->bindValue('id_statuspedido', $statusId, PDO::PARAM_INT);
        if ($this->hasHistoricoStatusPedidoColumn('snapshot_json')) {
            if ($snapshotJson !== null) {
                $stmt->bindValue('snapshot_json', $snapshotJson, PDO::PARAM_STR);
            } else {
                $stmt->bindValue('snapshot_json', null, PDO::PARAM_NULL);
            }
        }
        $stmt->execute();
    }

    private function marcarCabecalhoComoEntregue(int $headerId, ?int $usuarioId): void
    {
        $statusId = $this->statusPedidoIdByNome('ENTREGUE') ?? 2;

        if ($this->hasVendasCabecalhoColumn('id_statuspedido')) {
            $up = $this->pdo->prepare('UPDATE vendas_cabecalho SET status = :status, id_statuspedido = :id_statuspedido WHERE id = :id');
            $up->execute([
                'status' => 'ENTREGUE',
                'id_statuspedido' => $statusId,
                'id' => $headerId,
            ]);
        } else {
            $up = $this->pdo->prepare('UPDATE vendas_cabecalho SET status = :status WHERE id = :id');
            $up->execute(['status' => 'ENTREGUE', 'id' => $headerId]);
        }

        $snapshot = $this->buildSalesSnapshot($headerId);
        $this->registrarHistoricoStatus($headerId, $usuarioId, $statusId, $snapshot);
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
        $sql = "SELECT v.id, c.nome AS cliente, p.nome AS produto, v.quantidade, v.valor_unitario,
                   CASE WHEN UPPER(IFNULL(v.status, '')) = 'ENTREGUE' THEN 'ENTREGUE' ELSE 'AGUARDANDO' END AS status,
                   v.data_venda, {$envioCol}, {$entregaCol}
            FROM vendas v
            LEFT JOIN clientes c ON v.cliente_id = c.id
            LEFT JOIN produtos p ON v.produto_id = p.id
            {$where}
            ORDER BY v.id DESC
            LIMIT :limit OFFSET :offset";
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

        $statusSelect = "CASE
                            WHEN sp.nome IS NOT NULL THEN UPPER(sp.nome)
                            WHEN UPPER(IFNULL(h.status, '')) = 'ENTREGUE' THEN 'ENTREGUE'
                            ELSE 'AGUARDANDO'
                        END";
        $statusJoin = '';
        if (!($this->hasStatusPedidoTable() && $this->hasVendasCabecalhoColumn('id_statuspedido'))) {
            $statusSelect = "CASE WHEN UPPER(IFNULL(h.status, '')) = 'ENTREGUE' THEN 'ENTREGUE' ELSE 'AGUARDANDO' END";
        } else {
            $statusJoin = 'LEFT JOIN status_pedido sp ON sp.id = h.id_statuspedido';
        }

        $sql = "SELECT
                    h.id,
                    h.tipo,
                    c.nome AS cliente,
                    IFNULL(COUNT(v.id), 0) AS itens_count,
                    IFNULL(h.valor_total, 0) AS valor_total,
                    {$statusSelect} AS status,
                    h.data_inicio_prevista AS data_envio_prevista,
                    h.data_fim_prevista AS data_entrega_prevista
                FROM vendas_cabecalho h
                LEFT JOIN clientes c ON c.id = h.cliente_id
                {$statusJoin}
                LEFT JOIN vendas v ON v.venda_cabecalho_id = h.id
                {$where}
                GROUP BY h.id, h.tipo, c.nome, h.valor_total, {$statusSelect}, h.data_inicio_prevista, h.data_fim_prevista
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
        $statusPedido = $this->normalizeStatusPedidoLabel($data['status'] ?? null);
        $data['status'] = $statusPedido;
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
                        $statusPedido
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
                        'status' => $statusPedido,
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
                    $statusPedido
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

        $headerStatusSelect = "CASE
                                   WHEN sp.nome IS NOT NULL THEN UPPER(sp.nome)
                                   WHEN UPPER(IFNULL(h.status, '')) = 'ENTREGUE' THEN 'ENTREGUE'
                                   ELSE 'AGUARDANDO'
                               END";
        $headerStatusJoin = '';
        if (!($this->hasStatusPedidoTable() && $this->hasVendasCabecalhoColumn('id_statuspedido'))) {
            $headerStatusSelect = "CASE WHEN UPPER(IFNULL(h.status, '')) = 'ENTREGUE' THEN 'ENTREGUE' ELSE 'AGUARDANDO' END";
        } else {
            $headerStatusJoin = 'LEFT JOIN status_pedido sp ON sp.id = h.id_statuspedido';
        }

        $headerSql = "SELECT h.id, h.tipo, h.valor_total, {$headerStatusSelect} AS status,
                             h.data_inicio_prevista, h.data_fim_prevista, c.nome AS cliente
                      FROM vendas_cabecalho h
                      LEFT JOIN clientes c ON c.id = h.cliente_id
                      {$headerStatusJoin}
                      WHERE h.id = :id
                      LIMIT 1";
        $headerStmt = $this->pdo->prepare($headerSql);
        $headerStmt->execute(['id' => $id]);
        $header = $headerStmt->fetch(PDO::FETCH_ASSOC);

        if (!$header) {
            http_response_code(404);
            echo json_encode(['error' => 'Pedido não encontrado']);
            return;
        }

        $itemsStmt = $this->pdo->prepare(
              "SELECT v.id, p.nome AS produto, v.quantidade, v.valor_unitario,
                    CASE WHEN UPPER(IFNULL(v.status, '')) = 'ENTREGUE' THEN 'ENTREGUE' ELSE 'AGUARDANDO' END AS status,
                    v.data_venda
             FROM vendas v
             LEFT JOIN produtos p ON p.id = v.produto_id
             WHERE v.venda_cabecalho_id = :id
               ORDER BY v.id ASC"
        );
        $itemsStmt->execute(['id' => $id]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

        $historico = [];
        if ($this->hasHistoricoStatusPedidoTable()) {
            $historyJoin = $this->hasStatusPedidoTable()
                ? 'LEFT JOIN status_pedido sp ON sp.id = hsp.id_statuspedido'
                : '';
            $historyStatusSelect = $this->hasStatusPedidoTable()
                ? "CASE WHEN sp.nome IS NOT NULL THEN UPPER(sp.nome) WHEN hsp.id_statuspedido = 2 THEN 'ENTREGUE' ELSE 'AGUARDANDO' END"
                : "CASE WHEN hsp.id_statuspedido = 2 THEN 'ENTREGUE' ELSE 'AGUARDANDO' END";

            $historySql = "SELECT hsp.usuario_id, hsp.id_statuspedido,
                                  {$historyStatusSelect} AS status,
                              u.name AS usuario_nome,
                                  hsp.confirmado_em
                           FROM historico_status_pedido hsp
                          LEFT JOIN users u ON u.id = hsp.usuario_id
                           {$historyJoin}
                           WHERE hsp.venda_cabecalho_id = :id
                           ORDER BY hsp.confirmado_em DESC, hsp.id DESC";
            $historyStmt = $this->pdo->prepare($historySql);
            $historyStmt->execute(['id' => $id]);
            $historico = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        echo json_encode([
            'header' => $header,
            'items' => $items,
            'historico_statuspedido' => $historico,
        ]);
    }

    public function deliver(): void
    {
        $data = \App\Helpers\Request::body();
        $headerId = $data['venda_cabecalho_id'] ?? null;
        $currentUserId = $this->currentUserId();

        if ($headerId && $this->hasVendasCabecalhoTable()) {
            try {
                $salesRepo = new SalesRepository($this->pdo);
                $productRepo = new ProductRepository($this->pdo);
                $service = new SalesService($salesRepo, $productRepo);

                $currentStatus = $this->currentHeaderStatusPedido((int)$headerId);
                if ($currentStatus === null) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Pedido não encontrado']);
                    return;
                }
                if (!$this->canTransitionPedido($currentStatus, 'ENTREGUE')) {
                    if ($currentStatus === 'ENTREGUE') {
                        echo json_encode(['message' => 'Pedido já entregue']);
                        return;
                    }
                    http_response_code(409);
                    echo json_encode(['error' => 'Transição de status inválida para este pedido']);
                    return;
                }

                $this->pdo->beginTransaction();

                $stmt = $this->pdo->prepare('SELECT id FROM vendas WHERE venda_cabecalho_id = :id AND status <> :status');
                $stmt->execute(['id' => (int)$headerId, 'status' => 'ENTREGUE']);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (!$rows) {
                    if ($this->pdo->inTransaction()) {
                        $this->pdo->rollBack();
                    }
                    echo json_encode(['message' => 'Pedido já entregue']);
                    return;
                }

                $novoEstoque = null;
                foreach ($rows as $row) {
                    $res = $service->confirmDelivery((int)$row['id']);
                    $novoEstoque = $res['novo_estoque'] ?? $novoEstoque;
                }

                $this->marcarCabecalhoComoEntregue((int)$headerId, $currentUserId);
                $this->pdo->commit();

                echo json_encode(['message' => 'ENTREGUE', 'novo_estoque' => $novoEstoque]);
                return;
            } catch (\Throwable $e) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
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

            $this->pdo->beginTransaction();
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
                        $this->marcarCabecalhoComoEntregue($hid, $currentUserId);
                    }
                }
            }

            $this->pdo->commit();

            echo json_encode($res);
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            http_response_code(400);
            error_log('[SalesController::deliver] ' . $e->getMessage());
            echo json_encode(['error' => 'Não foi possível confirmar a entrega.']);
        }
    }

    public function deleteHeader(int $id): void
    {
        if (!$this->hasVendasCabecalhoTable() || !$this->hasVendasColumn('venda_cabecalho_id')) {
            http_response_code(404);
            echo json_encode(['error' => 'Exclusão por cabeçalho não disponível neste ambiente.']);
            return;
        }

        $currentStatus = $this->currentHeaderStatusPedido($id);
        if ($currentStatus === null) {
            http_response_code(404);
            echo json_encode(['error' => 'Pedido não encontrado']);
            return;
        }

        if ($currentStatus === 'ENTREGUE') {
            http_response_code(409);
            echo json_encode(['error' => 'Não é permitido excluir pedido entregue']);
            return;
        }

        try {
            $this->pdo->beginTransaction();

            $delItems = $this->pdo->prepare('DELETE FROM vendas WHERE venda_cabecalho_id = :id');
            $delItems->execute(['id' => $id]);

            $delHeader = $this->pdo->prepare('DELETE FROM vendas_cabecalho WHERE id = :id');
            $delHeader->execute(['id' => $id]);

            $this->pdo->commit();
            echo json_encode(['message' => 'Pedido excluído com sucesso']);
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            http_response_code(500);
            echo json_encode(['error' => 'Não foi possível excluir o pedido.']);
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
        $statusNormalizado = $this->normalizeStatusPedidoLabel($status);
        $params = [
            'tipo' => $tipo,
            'cliente_id' => $clienteId,
            'valor_total' => $valorTotal,
            'data_inicio_prevista' => $dataInicio,
            'data_fim_prevista' => $dataFim,
            'status' => $statusNormalizado,
        ];

        if ($this->hasVendasCabecalhoColumn('id_statuspedido')) {
            $statusId = $this->statusPedidoIdByNome($statusNormalizado)
                ?? ($statusNormalizado === 'ENTREGUE' ? 2 : 1);
            $stmt = $this->pdo->prepare(
                'INSERT INTO vendas_cabecalho (tipo, cliente_id, valor_total, data_inicio_prevista, data_fim_prevista, status, id_statuspedido)
                 VALUES (:tipo, :cliente_id, :valor_total, :data_inicio_prevista, :data_fim_prevista, :status, :id_statuspedido)'
            );
            $params['id_statuspedido'] = $statusId;
        } else {
            $stmt = $this->pdo->prepare(
                'INSERT INTO vendas_cabecalho (tipo, cliente_id, valor_total, data_inicio_prevista, data_fim_prevista, status)
                 VALUES (:tipo, :cliente_id, :valor_total, :data_inicio_prevista, :data_fim_prevista, :status)'
            );
        }

        $stmt->execute($params);

        return (int)$this->pdo->lastInsertId();
    }
}
