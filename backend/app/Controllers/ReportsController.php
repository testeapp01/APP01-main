<?php
namespace App\Controllers;

use App\Services\PurchaseReportService;
use App\Helpers\Response;

class ReportsController
{
    private PurchaseReportService $reportService;

    public function __construct(PurchaseReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index(): void
    {
        try {
            $payload = $this->reportService->summary($_GET);
            Response::json($payload);
        } catch (\Throwable $e) {
            Response::error('Não foi possível carregar os indicadores do relatório.', 500);
        }
    }

    public function dashboard(): void
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');

        try {
            $payload = $this->reportService->dashboard($_GET);
            Response::json($payload);
        } catch (\Throwable $e) {
            Response::error('Não foi possível montar o dashboard.', 500);
        }
    }

    public function strategicPurchases(): void
    {
        try {
            $payload = $this->reportService->strategic($_GET);
            Response::json($payload);
        } catch (\Throwable $e) {
            Response::error('Não foi possível montar o relatório estratégico de compras.', 500);
        }
    }

    public function exportStrategicPurchases(): void
    {
        $format = strtolower((string)($_GET['format'] ?? 'csv'));
        if (!in_array($format, ['csv', 'xlsx'], true)) {
            http_response_code(400);
            Response::json(['error' => 'Formato de exportação inválido.']);
            return;
        }

        try {
            $export = $this->reportService->export($format, $_GET);

            header('Content-Type: ' . $export['contentType']);
            header('Content-Disposition: attachment; filename="' . $export['fileName'] . '"');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            echo $export['body'];
        } catch (\Throwable $e) {
            Response::error('Não foi possível exportar o relatório estratégico de compras.', 500);
        }
    }

    /** GET /api/v1/relatorios/abc — Curva ABC de produtos por receita */
    public function abc(): void
    {
        $dias = max(1, (int)($_GET['dias'] ?? 90));

        try {
            $payload = $this->reportService->abc($dias);
            Response::json($payload);
        } catch (\Throwable $e) {
            Response::json(['items' => [], 'total_receita' => 0, 'dias' => $dias]);
        }
    }
}
