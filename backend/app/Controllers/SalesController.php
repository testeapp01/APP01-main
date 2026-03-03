<?php
namespace App\Controllers;

use App\Services\SalesCreationService;
use App\Services\SalesHeaderService;
use PDO;

class SalesController
{
    private ?array $vendasColumnsCache = null;
    private ?array $vendasCabecalhoColumnsCache = null;
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

    private function currentUserId(): ?int
    {
        $auth = $GLOBALS['AUTH_USER'] ?? null;
        if (!is_array($auth) || !isset($auth['sub'])) {
            return null;
        }

        $userId = (int)$auth['sub'];
        return $userId > 0 ? $userId : null;
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
        try {
            $service = new SalesCreationService($this->pdo);
            $result = $service->create($data);
            http_response_code(201);
            echo json_encode($result);
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
        $workflow = new SalesHeaderService($this->pdo);

        if ($headerId && $this->hasVendasCabecalhoTable()) {
            try {
                $result = $workflow->deliverHeader((int)$headerId, $this->currentUserId());
                echo json_encode($result);
                return;
            } catch (\RuntimeException $e) {
                $code = (int)$e->getCode();
                if ($code === 404 || $code === 409) {
                    http_response_code($code);
                    echo json_encode(['error' => $e->getMessage()]);
                    return;
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
            $res = $workflow->deliverItem((int)$id, $this->currentUserId());
            echo json_encode($res);
        } catch (\RuntimeException $e) {
            http_response_code(400);
            error_log('[SalesController::deliver] ' . $e->getMessage());
            echo json_encode(['error' => 'Não foi possível confirmar a entrega.']);
        }
    }

    public function deleteHeader(int $id): void
    {
        try {
            $workflow = new SalesHeaderService($this->pdo);
            $result = $workflow->deleteHeader($id);
            echo json_encode($result);
        } catch (\RuntimeException $e) {
            $code = (int)$e->getCode();
            http_response_code($code >= 400 ? $code : 500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

}
