<?php
namespace App\Controllers;

use App\Repositories\SalesRepository;
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

    private SalesRepository $repo;
    private SalesCreationService $creationService;
    private SalesHeaderService $headerService;
    private OrderPdfService $pdfService;

    public function __construct(private PDO $pdo, SalesRepository $repo, SalesCreationService $creationService, SalesHeaderService $headerService, OrderPdfService $pdfService)
    {
        $this->repo = $repo;
        $this->creationService = $creationService;
        $this->headerService = $headerService;
        $this->pdfService = $pdfService;
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

        $total = $this->repo->countSimpleSales($q);
        $items = $this->repo->findSimpleSales($q, $per, $offset);

        Response::json(['items' => $items, 'total' => $total]);
    }

    private function indexHeaders(): void
    {
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $per = isset($_GET['per_page']) ? max(1, (int)$_GET['per_page']) : 25;
        $q = isset($_GET['q']) ? trim($_GET['q']) : '';
        $offset = ($page - 1) * $per;

        $hasStatusPedidoTable = $this->hasStatusPedidoTable();
        $hasIdStatusPedidoColumn = $this->hasVendasCabecalhoColumn('id_statuspedido');

        $total = $this->repo->countHeaderList($q, $hasStatusPedidoTable, $hasIdStatusPedidoColumn);
        $items = $this->repo->findHeaderList($q, $per, $offset, $hasStatusPedidoTable, $hasIdStatusPedidoColumn);

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

        $hasStatusPedidoTable = $this->hasStatusPedidoTable();
        $hasIdStatusPedidoColumn = $this->hasVendasCabecalhoColumn('id_statuspedido');

        $header = $this->repo->findHeaderById($id, $hasStatusPedidoTable, $hasIdStatusPedidoColumn);

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

        $hasStatusPedidoTable = $this->hasStatusPedidoTable();
        $items = $this->repo->findHeaderItemsById($id);
        $historico = $this->repo->findHeaderHistoryById($id, $hasStatusPedidoTable);

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
