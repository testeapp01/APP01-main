<?php
namespace App\Controllers;

use PDO;
use App\Services\PurchaseCreationService;
use App\Services\PurchaseHeaderService;
use App\Services\OrderPdfService;
use App\Helpers\Request;
use App\Helpers\Response;

class PurchaseController
{
    private ?array $comprasColumnsCache = null;
    private ?array $comprasCabecalhoColumnsCache = null;
    private ?bool $hasComprasCabecalhoCache = null;
    private ?bool $hasStatusCompraCache = null;
    private ?bool $hasHistoricoStatusCompraCache = null;

    private PurchaseCreationService $creationService;
    private PurchaseHeaderService $headerService;
    private OrderPdfService $pdfService;

    public function __construct(private PDO $pdo, ?PurchaseCreationService $creationService = null, ?PurchaseHeaderService $headerService = null, ?OrderPdfService $pdfService = null)
    {
        $this->creationService = $creationService ?? new PurchaseCreationService($this->pdo);
        $this->headerService = $headerService ?? new PurchaseHeaderService($this->pdo);
        $this->pdfService = $pdfService ?? new OrderPdfService($this->pdo);
    }

    private function comprasColumns(): array
    {
        if ($this->comprasColumnsCache !== null) {
            return $this->comprasColumnsCache;
        }

        $cols = \App\Helpers\Schema::tableColumns($this->pdo, 'compras');
        $this->comprasColumnsCache = $cols;
        return $this->comprasColumnsCache;
    }

    private function hasComprasColumn(string $column): bool
    {
        return \App\Helpers\Schema::hasColumn($this->pdo, 'compras', $column);
    }

    private function comprasCabecalhoColumns(): array
    {
        if ($this->comprasCabecalhoColumnsCache !== null) {
            return $this->comprasCabecalhoColumnsCache;
        }

        if (!\App\Helpers\Schema::hasTable($this->pdo, 'compras_cabecalho')) {
            $this->comprasCabecalhoColumnsCache = [];
            return $this->comprasCabecalhoColumnsCache;
        }

        $cols = \App\Helpers\Schema::tableColumns($this->pdo, 'compras_cabecalho');
        $this->comprasCabecalhoColumnsCache = $cols;
        return $this->comprasCabecalhoColumnsCache;
    }

    private function hasComprasCabecalhoColumn(string $column): bool
    {
        return \App\Helpers\Schema::hasColumn($this->pdo, 'compras_cabecalho', $column);
    }

    private function hasComprasCabecalhoTable(): bool
    {
        if ($this->hasComprasCabecalhoCache !== null) {
            return $this->hasComprasCabecalhoCache;
        }

        $this->hasComprasCabecalhoCache = \App\Helpers\Schema::hasTable($this->pdo, 'compras_cabecalho');
        return $this->hasComprasCabecalhoCache;
    }

    private function hasStatusCompraTable(): bool
    {
        if ($this->hasStatusCompraCache !== null) {
            return $this->hasStatusCompraCache;
        }

        $this->hasStatusCompraCache = \App\Helpers\Schema::hasTable($this->pdo, 'status_compra');
        return $this->hasStatusCompraCache;
    }

    private function hasHistoricoStatusCompraTable(): bool
    {
        if ($this->hasHistoricoStatusCompraCache !== null) {
            return $this->hasHistoricoStatusCompraCache;
        }

        $this->hasHistoricoStatusCompraCache = \App\Helpers\Schema::hasTable($this->pdo, 'historico_status_compra');
        return $this->hasHistoricoStatusCompraCache;
    }

    private function normalizeStatusCompraLabel(?string $status): string
    {
        $value = strtoupper(trim((string)$status));
        return $value === 'RECEBIDA' ? 'RECEBIDA' : 'AGUARDANDO';
    }

    private function statusCompraIdByNome(string $nome): ?int
    {
        if (!$this->hasStatusCompraTable()) {
            return null;
        }

        $stmt = $this->pdo->prepare('SELECT id FROM status_compra WHERE UPPER(nome) = :nome LIMIT 1');
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

    private function currentHeaderStatusCompra(int $headerId): ?string
    {
        if (!$this->hasComprasCabecalhoTable()) {
            return null;
        }

        $stmt = $this->pdo->prepare('SELECT status FROM compras_cabecalho WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $headerId]);
        $status = $stmt->fetchColumn();

        if ($status === false) {
            return null;
        }

        return $this->normalizeStatusCompraLabel((string)$status);
    }

    private function canTransitionCompra(string $from, string $to): bool
    {
        if ($from === $to) {
            return true;
        }

        return $from === 'AGUARDANDO' && $to === 'RECEBIDA';
    }

    public function index(): void
    {
        if (!$this->hasComprasCabecalhoTable() || !$this->hasComprasColumn('compra_cabecalho_id')) {
            http_response_code(500);
            Response::json(['error' => 'Estrutura de compras por cabeçalho não disponível. Execute as migrations.']);
            return;
        }

        $this->indexHeaders();
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

        $statusSelect = "CASE
                            WHEN sc.nome IS NOT NULL THEN UPPER(sc.nome)
                            WHEN UPPER(IFNULL(h.status, '')) = 'RECEBIDA' THEN 'RECEBIDA'
                            ELSE 'AGUARDANDO'
                        END";
        $statusJoin = '';
        if (!($this->hasStatusCompraTable() && $this->hasComprasCabecalhoColumn('id_statuscompra'))) {
            $statusSelect = "CASE WHEN UPPER(IFNULL(h.status, '')) = 'RECEBIDA' THEN 'RECEBIDA' ELSE 'AGUARDANDO' END";
        } else {
            $statusJoin = 'LEFT JOIN status_compra sc ON sc.id = h.id_statuscompra';
        }

        $sql = "SELECT
                    h.id,
                    h.tipo_operacao,
                    f.razao_social AS fornecedor,
                    cl.nome AS cliente,
                    m.nome AS motorista,
                    IFNULL(COUNT(c.id), 0) AS itens_count,
                    IFNULL(SUM(c.quantidade * c.valor_unitario), 0) AS valor_total,
                    {$statusSelect} AS status,
                    h.data_envio_prevista,
                    h.data_entrega_prevista
                FROM compras_cabecalho h
                LEFT JOIN fornecedores f ON f.id = h.fornecedor_id
                LEFT JOIN clientes cl ON cl.id = h.cliente_id
                LEFT JOIN motoristas m ON m.id = h.motorista_id
                {$statusJoin}
                LEFT JOIN compras c ON c.compra_cabecalho_id = h.id
                {$where}
                GROUP BY h.id, h.tipo_operacao, f.razao_social, cl.nome, m.nome, {$statusSelect}, h.data_envio_prevista, h.data_entrega_prevista
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
        $data = Request::body();
        try {
            $result = $this->creationService->create($data);
            http_response_code(201);
            Response::json($result);
        } catch (\RuntimeException $e) {
            $code = (int)$e->getCode();
            http_response_code($code >= 400 ? $code : 400);
            Response::json(['error' => $e->getMessage()]);
        }
    }

    public function showHeader(int $id): void
    {
        if (!$this->hasComprasCabecalhoTable() || !$this->hasComprasColumn('compra_cabecalho_id')) {
            http_response_code(500);
            Response::json(['error' => 'Estrutura de compras por cabeçalho não disponível. Execute as migrations.']);
            return;
        }

        // IDOR protection: non-privileged users can only see their own records
        $authUser  = $GLOBALS['AUTH_USER'] ?? [];
        $userRole  = strtolower(trim((string)($authUser['role'] ?? '')));
        $userId    = (int)($authUser['sub'] ?? 0);
        $isPrivileged = in_array($userRole, ['admin', 'gerente'], true);

        $headerStatusSelect = "CASE
                                   WHEN sc.nome IS NOT NULL THEN UPPER(sc.nome)
                                   WHEN UPPER(IFNULL(h.status, '')) = 'RECEBIDA' THEN 'RECEBIDA'
                                   ELSE 'AGUARDANDO'
                               END";
        $headerStatusJoin = '';
        if (!($this->hasStatusCompraTable() && $this->hasComprasCabecalhoColumn('id_statuscompra'))) {
            $headerStatusSelect = "CASE WHEN UPPER(IFNULL(h.status, '')) = 'RECEBIDA' THEN 'RECEBIDA' ELSE 'AGUARDANDO' END";
        } else {
            $headerStatusJoin = 'LEFT JOIN status_compra sc ON sc.id = h.id_statuscompra';
        }

        $headerStmt = $this->pdo->prepare(
                "SELECT h.id, h.tipo_operacao, h.fornecedor_id, h.cliente_id, h.motorista_id, h.valor_total, {$headerStatusSelect} AS status, h.data_envio_prevista, h.data_entrega_prevista,
                    f.razao_social AS fornecedor, cl.nome AS cliente, m.nome AS motorista
             FROM compras_cabecalho h
             LEFT JOIN fornecedores f ON f.id = h.fornecedor_id
             LEFT JOIN clientes cl ON cl.id = h.cliente_id
             LEFT JOIN motoristas m ON m.id = h.motorista_id
             {$headerStatusJoin}
             WHERE h.id = :id
             LIMIT 1"
        );
        $headerStmt->execute(['id' => $id]);
        $header = $headerStmt->fetch(PDO::FETCH_ASSOC);

        if (!$header) {
            http_response_code(404);
            Response::json(['error' => 'Pedido de compra não encontrado']);
            return;
        }

        // Enforce ownership: non-privileged user can only see their own records (or pre-migration NULLs)
        $createdBy = isset($header['created_by']) ? (int)$header['created_by'] : null;
        if (!$isPrivileged && $createdBy !== null && $createdBy !== $userId) {
            http_response_code(403);
            Response::json(['error' => 'Acesso negado']);
            return;
        }

        $itemsStmt = $this->pdo->prepare(
              "SELECT c.id, p.nome AS produto, c.quantidade, c.valor_unitario,
                    CASE WHEN UPPER(IFNULL(c.status, '')) = 'RECEBIDA' THEN 'RECEBIDA' ELSE 'AGUARDANDO' END AS status,
                    c.data_compra
             FROM compras c
             LEFT JOIN produtos p ON p.id = c.produto_id
             WHERE c.compra_cabecalho_id = :id
               ORDER BY c.id ASC"
        );
        $itemsStmt->execute(['id' => $id]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

        $historico = [];
        if ($this->hasHistoricoStatusCompraTable()) {
            $historyJoin = $this->hasStatusCompraTable()
                ? 'LEFT JOIN status_compra sc ON sc.id = hsc.id_statuscompra'
                : '';
            $historyStatusSelect = $this->hasStatusCompraTable()
                ? "CASE WHEN sc.nome IS NOT NULL THEN UPPER(sc.nome) WHEN hsc.id_statuscompra = 2 THEN 'RECEBIDA' ELSE 'AGUARDANDO' END"
                : "CASE WHEN hsc.id_statuscompra = 2 THEN 'RECEBIDA' ELSE 'AGUARDANDO' END";
            $historyStmt = $this->pdo->prepare(
                "SELECT hsc.usuario_id, hsc.id_statuscompra,
                        {$historyStatusSelect} AS status,
                        u.name AS usuario_nome,
                        hsc.confirmado_em
                 FROM historico_status_compra hsc
                  LEFT JOIN users u ON u.id = hsc.usuario_id
                 {$historyJoin}
                 WHERE hsc.compra_cabecalho_id = :id
                 ORDER BY hsc.confirmado_em DESC, hsc.id DESC"
            );
            $historyStmt->execute(['id' => $id]);
            $historico = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        Response::json([
            'header' => $header,
            'items' => $items,
            'historico_statuscompra' => $historico,
        ]);
    }

    public function printHeaderPdf(int $id): void
    {
        if (!$this->hasComprasCabecalhoTable() || !$this->hasComprasColumn('compra_cabecalho_id')) {
            http_response_code(404);
            Response::json(['error' => 'Impressão de compra não disponível neste ambiente.']);
            return;
        }

        try {
            $pdf = $this->pdfService->renderPurchaseHeaderPdf($id);
            if ($pdf === null) {
                http_response_code(404);
                Response::json(['error' => 'Pedido de compra não encontrado']);
                return;
            }

            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="pedido-compra-' . $id . '.pdf"');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            echo $pdf;
        } catch (\Throwable $e) {
            http_response_code(500);
            Response::json(['error' => 'Não foi possível gerar o PDF de compra.']);
        }
    }

    public function updateHeader(int $id): void
    {
        if (!$this->hasComprasCabecalhoTable() || !$this->hasComprasColumn('compra_cabecalho_id')) {
            http_response_code(404);
            Response::json(['error' => 'Edição por cabeçalho não disponível neste ambiente.']);
            return;
        }

        $currentStatus = $this->currentHeaderStatusCompra($id);
        if ($currentStatus === null) {
            http_response_code(404);
            Response::json(['error' => 'Pedido de compra não encontrado']);
            return;
        }

        $data = Request::body();
        if ($currentStatus === 'RECEBIDA') {
            http_response_code(409);
            Response::json(['error' => 'Não é permitido editar pedido de compra recebido']);
            return;
        }

        if (array_key_exists('status', $data)) {
            $nextStatus = $this->normalizeStatusCompraLabel($data['status']);
            if (!$this->canTransitionCompra($currentStatus, $nextStatus)) {
                http_response_code(409);
                Response::json(['error' => 'Transição de status inválida para este pedido de compra']);
                return;
            }

            if ($currentStatus !== $nextStatus && $nextStatus === 'RECEBIDA') {
                http_response_code(409);
                Response::json(['error' => 'Use a confirmação de entrega para finalizar o pedido de compra']);
                return;
            }
        }

        $allowed = ['tipo_operacao', 'fornecedor_id', 'cliente_id', 'motorista_id', 'data_envio_prevista', 'data_entrega_prevista', 'status'];
        $set = [];
        $params = ['id' => $id];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $value = $field === 'status' ? $this->normalizeStatusCompraLabel($data[$field]) : $data[$field];
                $set[] = $field . ' = :' . $field;
                $params[$field] = $value;
                if ($field === 'status') {
                    $data[$field] = $value;
                }
            }
        }

        if (array_key_exists('status', $data) && $this->hasComprasCabecalhoColumn('id_statuscompra')) {
            $set[] = 'id_statuscompra = :id_statuscompra';
            $params['id_statuscompra'] = $this->statusCompraIdByNome($data['status'])
                ?? ($data['status'] === 'RECEBIDA' ? 2 : 1);
        }

        if (empty($set)) {
            http_response_code(400);
            Response::json(['error' => 'Nenhum campo válido para atualização']);
            return;
        }

        $stmt = $this->pdo->prepare('UPDATE compras_cabecalho SET ' . implode(', ', $set) . ' WHERE id = :id');
        $stmt->execute($params);

        $syncParts = [];
        $syncParams = ['id' => $id];
        foreach (['tipo_operacao', 'fornecedor_id', 'cliente_id', 'motorista_id', 'data_envio_prevista', 'data_entrega_prevista', 'status'] as $field) {
            if (array_key_exists($field, $data) && $this->hasComprasColumn($field)) {
                $syncParts[] = 'c.' . $field . ' = :s_' . $field;
                $syncParams['s_' . $field] = $data[$field];
            }
        }

        if (!empty($syncParts)) {
            $sqlSync = 'UPDATE compras c SET ' . implode(', ', $syncParts) . ' WHERE c.compra_cabecalho_id = :id';
            $syncStmt = $this->pdo->prepare($sqlSync);
            $syncStmt->execute($syncParams);
        }

        Response::json(['message' => 'Cabeçalho de compra atualizado']);
    }

    public function updateItem(int $id): void
    {
        $data = Request::body();
        $allowed = ['tipo_operacao', 'fornecedor_id', 'cliente_id', 'motorista_id', 'produto_id', 'quantidade', 'valor_unitario', 'data_envio_prevista', 'data_entrega_prevista', 'status'];
        $set = [];
        $params = ['id' => $id];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data) && $this->hasComprasColumn($field)) {
                $set[] = $field . ' = :' . $field;
                $params[$field] = $data[$field];
            }
        }

        if (empty($set)) {
            http_response_code(400);
            Response::json(['error' => 'Nenhum campo válido para atualização']);
            return;
        }

        $stmt = $this->pdo->prepare('UPDATE compras SET ' . implode(', ', $set) . ' WHERE id = :id');
        $stmt->execute($params);

        if ($this->hasComprasColumn('compra_cabecalho_id')) {
            $hStmt = $this->pdo->prepare('SELECT compra_cabecalho_id FROM compras WHERE id = :id LIMIT 1');
            $hStmt->execute(['id' => $id]);
            $row = $hStmt->fetch(PDO::FETCH_ASSOC);
            $headerId = (int)($row['compra_cabecalho_id'] ?? 0);
            if ($headerId > 0 && $this->hasComprasCabecalhoTable()) {
                $this->recalculateHeaderTotal($headerId);
            }
        }

        Response::json(['message' => 'Item de compra atualizado']);
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

    public function deleteItem(int $id): void
    {
        $headerId = 0;
        if ($this->hasComprasColumn('compra_cabecalho_id')) {
            $hStmt = $this->pdo->prepare('SELECT compra_cabecalho_id FROM compras WHERE id = :id LIMIT 1');
            $hStmt->execute(['id' => $id]);
            $row = $hStmt->fetch(PDO::FETCH_ASSOC);
            $headerId = (int)($row['compra_cabecalho_id'] ?? 0);
        }

        $stmt = $this->pdo->prepare('DELETE FROM compras WHERE id = :id');
        $stmt->execute(['id' => $id]);

        if ($headerId > 0 && $this->hasComprasCabecalhoTable()) {
            $countStmt = $this->pdo->prepare('SELECT COUNT(*) FROM compras WHERE compra_cabecalho_id = :id');
            $countStmt->execute(['id' => $headerId]);
            $left = (int)$countStmt->fetchColumn();
            if ($left === 0) {
                $delHeader = $this->pdo->prepare('DELETE FROM compras_cabecalho WHERE id = :id');
                $delHeader->execute(['id' => $headerId]);
            } else {
                $this->recalculateHeaderTotal($headerId);
            }
        }

        Response::json(['message' => 'Item de compra excluído']);
    }

    public function confirmHeaderDelivery(int $id): void
    {
        try {
            $result = $this->headerService->confirmHeaderDelivery($id, $this->currentUserId());
            Response::json($result);
        } catch (\RuntimeException $e) {
            $code = (int)$e->getCode();
            http_response_code($code >= 400 ? $code : 400);
            Response::json(['error' => $e->getMessage()]);
        }
    }

    public function confirmItemDelivery(int $id): void
    {
        try {
            $res = $this->headerService->confirmItemDelivery($id, $this->currentUserId());
            Response::json($res);
        } catch (\RuntimeException $e) {
            $code = (int)$e->getCode();
            http_response_code($code >= 400 ? $code : 400);
            Response::json(['error' => $e->getMessage()]);
        }
    }

    private function recalculateHeaderTotal(int $headerId): void
    {
        if (!$this->hasComprasCabecalhoTable()) {
            return;
        }

        $sumStmt = $this->pdo->prepare('SELECT IFNULL(SUM(quantidade * valor_unitario), 0) FROM compras WHERE compra_cabecalho_id = :id');
        $sumStmt->execute(['id' => $headerId]);
        $total = (float)$sumStmt->fetchColumn();

        $up = $this->pdo->prepare('UPDATE compras_cabecalho SET valor_total = :valor_total WHERE id = :id');
        $up->execute(['valor_total' => $total, 'id' => $headerId]);
    }

    public function receive(): void
    {
        $data = Request::body();
        $id = $data['compra_id'] ?? null;
        if (!$id) {
            http_response_code(400);
            Response::json(['error' => 'compra_id obrigatório']);
            return;
        }

        try {
            $res = $this->headerService->confirmItemReceiveById((int)$id);
            Response::json(['message' => 'Compra marcada como RECEBIDA'] + $res);
        } catch (\RuntimeException $e) {
            $code = (int)$e->getCode();
            http_response_code($code >= 400 ? $code : 400);
            Response::json(['error' => $e->getMessage()]);
        }
    }
}
