<?php
namespace App\Controllers;

use App\Services\SalesCreationService;
use App\Services\SalesHeaderService;
use App\Services\OrderPdfService;
use App\Helpers\Response;
use PDO;

class SalesController
{
    private ?array $vendasColumnsCache = null;
    private ?array $vendasCabecalhoColumnsCache = null;
    private ?bool $hasVendasCabecalhoCache = null;
    private ?bool $hasStatusPedidoCache = null;
    private ?bool $hasHistoricoStatusPedidoCache = null;

    private SalesCreationService $creationService;
    private SalesHeaderService $headerService;
    private OrderPdfService $pdfService;

    public function __construct(private PDO $pdo, ?SalesCreationService $creationService = null, ?SalesHeaderService $headerService = null, ?OrderPdfService $pdfService = null)
    {
        $this->creationService = $creationService ?? new SalesCreationService($this->pdo);
        $this->headerService = $headerService ?? new SalesHeaderService($this->pdo);
        $this->pdfService = $pdfService ?? new OrderPdfService($this->pdo);
    }

    private function vendasColumns(): array
    {
        if ($this->vendasColumnsCache !== null) {
            return $this->vendasColumnsCache;
        }

        $cols = \App\Helpers\Schema::tableColumns($this->pdo, 'vendas');
        $this->vendasColumnsCache = $cols;
        return $this->vendasColumnsCache;
    }

    private function hasVendasColumn(string $column): bool
    {
        return \App\Helpers\Schema::hasColumn($this->pdo, 'vendas', $column);
    }

    private function vendasCabecalhoColumns(): array
    {
        if ($this->vendasCabecalhoColumnsCache !== null) {
            return $this->vendasCabecalhoColumnsCache;
        }

        if (!\App\Helpers\Schema::hasTable($this->pdo, 'vendas_cabecalho')) {
            $this->vendasCabecalhoColumnsCache = [];
            return $this->vendasCabecalhoColumnsCache;
        }

        $cols = \App\Helpers\Schema::tableColumns($this->pdo, 'vendas_cabecalho');
        $this->vendasCabecalhoColumnsCache = $cols;
        return $this->vendasCabecalhoColumnsCache;
    }

    private function hasVendasCabecalhoColumn(string $column): bool
    {
        return \App\Helpers\Schema::hasColumn($this->pdo, 'vendas_cabecalho', $column);
    }

    private function hasVendasCabecalhoTable(): bool
    {
        if ($this->hasVendasCabecalhoCache !== null) {
            return $this->hasVendasCabecalhoCache;
        }

        $this->hasVendasCabecalhoCache = \App\Helpers\Schema::hasTable($this->pdo, 'vendas_cabecalho');
        return $this->hasVendasCabecalhoCache;
    }

    private function hasStatusPedidoTable(): bool
    {
        if ($this->hasStatusPedidoCache !== null) {
            return $this->hasStatusPedidoCache;
        }

        $this->hasStatusPedidoCache = \App\Helpers\Schema::hasTable($this->pdo, 'status_pedido');
        return $this->hasStatusPedidoCache;
    }

    private function hasHistoricoStatusPedidoTable(): bool
    {
        if ($this->hasHistoricoStatusPedidoCache !== null) {
            return $this->hasHistoricoStatusPedidoCache;
        }

        $this->hasHistoricoStatusPedidoCache = \App\Helpers\Schema::hasTable($this->pdo, 'historico_status_pedido');
        return $this->hasHistoricoStatusPedidoCache;
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

        Response::json(['items' => $items, 'total' => $total]);
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

        Response::json(['items' => $items, 'total' => $total]);
    }

    public function create(): void
    {
        $data = \App\Helpers\Request::body();
        try {
            $result = $this->creationService->create($data);
            http_response_code(201);
            Response::json($result);
        } catch (\Throwable $e) {
            $statusCode = (int)$e->getCode();
            if ($statusCode < 400 || $statusCode > 599) {
                $statusCode = 400;
            }

            $message = trim((string)$e->getMessage());
            if ($message === '') {
                $message = 'Não foi possível processar a venda.';
            }

            http_response_code($statusCode);
            error_log('[SalesController::create] ' . $e->getMessage());
            Response::json(['error' => $message]);
        }
    }

    public function showHeader(int $id): void
    {
        if (!$this->hasVendasCabecalhoTable()) {
            http_response_code(404);
            Response::json(['error' => 'Cabeçalho de venda não disponível neste ambiente.']);
            return;
        }

        // IDOR protection
        $authUser    = $GLOBALS['AUTH_USER'] ?? [];
        $userRole    = strtolower(trim((string)($authUser['role'] ?? '')));
        $userId      = (int)($authUser['sub'] ?? 0);
        $isPrivileged = in_array($userRole, ['admin', 'gerente'], true);

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
            Response::json(['error' => 'Pedido não encontrado']);
            return;
        }

        // Enforce ownership
        $createdBy = isset($header['created_by']) ? (int)$header['created_by'] : null;
        if (!$isPrivileged && $createdBy !== null && $createdBy !== $userId) {
            http_response_code(403);
            Response::json(['error' => 'Acesso negado']);
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

        Response::json([
            'header' => $header,
            'items' => $items,
            'historico_statuspedido' => $historico,
        ]);
    }

    public function printHeaderPdf(int $id): void
    {
        if (!$this->hasVendasCabecalhoTable()) {
            http_response_code(404);
            Response::json(['error' => 'Impressão de venda não disponível neste ambiente.']);
            return;
        }

        try {
            $pdf = $this->pdfService->renderSalesHeaderPdf($id);
            if ($pdf === null) {
                http_response_code(404);
                Response::json(['error' => 'Pedido de venda não encontrado']);
                return;
            }

            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="pedido-venda-' . $id . '.pdf"');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            echo $pdf;
        } catch (\Throwable $e) {
            http_response_code(500);
            Response::json(['error' => 'Não foi possível gerar o PDF de venda.']);
        }
    }

    public function deliver(): void
    {
        $data = \App\Helpers\Request::body();
        $headerId = $data['venda_cabecalho_id'] ?? null;
        $workflow = $this->headerService;

        if ($headerId && $this->hasVendasCabecalhoTable()) {
            try {
                $result = $workflow->deliverHeader((int)$headerId, $this->currentUserId());
                Response::json($result);
                return;
            } catch (\RuntimeException $e) {
                $code = (int)$e->getCode();
                if ($code === 404 || $code === 409) {
                    http_response_code($code);
                    Response::json(['error' => $e->getMessage()]);
                    return;
                }
                http_response_code(400);
                error_log('[SalesController::deliverHeader] ' . $e->getMessage());
                Response::json(['error' => 'Não foi possível confirmar a entrega do pedido.']);
                return;
            }
        }

        $id = $data['venda_id'] ?? null;
        if (!$id) {
            http_response_code(400);
            Response::json(['error' => 'venda_id obrigatório']);
            return;
        }

        try {
            $res = $workflow->deliverItem((int)$id, $this->currentUserId());
            Response::json($res);
        } catch (\RuntimeException $e) {
            http_response_code(400);
            error_log('[SalesController::deliver] ' . $e->getMessage());
            Response::json(['error' => 'Não foi possível confirmar a entrega.']);
        }
    }

    public function deleteHeader(int $id): void
    {
        try {
            $result = $this->headerService->deleteHeader($id);
            Response::json($result);
        } catch (\RuntimeException $e) {
            $code = (int)$e->getCode();
            http_response_code($code >= 400 ? $code : 500);
            Response::json(['error' => $e->getMessage()]);
        }
    }

}
