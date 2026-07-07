<?php

namespace App\Services;

use App\Repositories\PurchaseReportRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PurchaseReportService
{
    public function __construct(private PurchaseReportRepository $repository)
    {
    }

    public function strategic(array $query): array
    {
        $page = max(1, (int)($query['page'] ?? 1));
        $perPage = min(200, max(5, (int)($query['per_page'] ?? 20)));
        $sortBy = (string)($query['sort_by'] ?? 'data_compra');
        $sortDir = strtoupper((string)($query['sort_dir'] ?? 'DESC'));

        $filters = $this->normalizeFilters($query);
        $report = $this->repository->fetchStrategicReport($filters, $page, $perPage, $sortBy, $sortDir);
        $kpis = $this->repository->fetchKpis($filters);
        $charts = $this->repository->fetchChartSeries($filters);
        $filterOptions = $this->repository->fetchFilterOptions();

        $total = (int)($report['total'] ?? 0);
        $pages = max(1, (int)ceil($total / $perPage));

        return [
            'filters' => $filters,
            'kpis' => $kpis,
            'charts' => $charts,
            'items' => $report['items'] ?? [],
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'pages' => $pages,
            ],
            'options' => $filterOptions,
            'index_suggestions' => $this->repository->indexSuggestions(),
        ];
    }

    public function export(string $format, array $query): array
    {
        $filters = $this->normalizeFilters($query);
        $report = $this->repository->fetchStrategicReport($filters, 1, 5000, 'data_compra', 'DESC');
        $rows = $report['items'] ?? [];

        if ($format === 'xlsx') {
            return $this->buildXlsxExport($rows);
        }

        return $this->buildCsvExport($rows);
    }

    private function normalizeFilters(array $query): array
    {
        return [
            'from' => $this->normalizeDate((string)($query['from'] ?? '')),
            'to' => $this->normalizeDate((string)($query['to'] ?? '')),
            'fornecedor_id' => $this->normalizeInt($query['fornecedor_id'] ?? null),
            'produto_id' => $this->normalizeInt($query['produto_id'] ?? null),
            'motorista_id' => $this->normalizeInt($query['motorista_id'] ?? null),
            'status' => $this->normalizeString((string)($query['status'] ?? '')),
            'uf' => strtoupper($this->normalizeString((string)($query['uf'] ?? ''))),
            'q' => $this->normalizeString((string)($query['q'] ?? '')),
        ];
    }

    private function normalizeDate(string $value): ?string
    {
        $value = trim($value);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        return $value;
    }

    private function normalizeInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        $int = (int)$value;

        return $int > 0 ? $int : null;
    }

    private function normalizeString(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function buildCsvExport(array $rows): array
    {
        $headers = $this->exportHeaders();
        $fp = fopen('php://temp', 'r+');
        fputcsv($fp, $headers, ';');

        foreach ($rows as $row) {
            fputcsv($fp, $this->mapExportRow($row), ';');
        }

        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);

        return [
            'contentType' => 'text/csv; charset=utf-8',
            'fileName' => 'relatorio-compras-estrategico.csv',
            'body' => "\xEF\xBB\xBF" . ($csv ?: ''),
        ];
    }

    private function buildXlsxExport(array $rows): array
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Compras Estratégico');

        $headers = $this->exportHeaders();
        foreach ($headers as $index => $header) {
            $sheet->setCellValueByColumnAndRow($index + 1, 1, $header);
        }

        $rowNumber = 2;
        foreach ($rows as $row) {
            $values = $this->mapExportRow($row);
            foreach ($values as $index => $value) {
                $sheet->setCellValueByColumnAndRow($index + 1, $rowNumber, $value);
            }
            $rowNumber++;
        }

        foreach (range(1, count($headers)) as $column) {
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $data = ob_get_clean();

        return [
            'contentType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'fileName' => 'relatorio-compras-estrategico.xlsx',
            'body' => $data ?: '',
        ];
    }

    private function exportHeaders(): array
    {
        return [
            'ID Compra',
            'ID Pedido',
            'Data Compra',
            'Fornecedor',
            'Produto(s)',
            'Motorista(s)',
            'Tipo Caminhão',
            'Quantidade Total',
            'Valor Unitário Médio',
            'Custo Total',
            'Comissão Total',
            'Custo Final Real',
            'Valor Total Agregado',
            'Itens',
            'Status',
            'Data Envio Prevista',
            'Data Entrega Prevista',
        ];
    }

    private function mapExportRow(array $row): array
    {
        return [
            (int)($row['compra_id'] ?? 0),
            (int)($row['compra_cabecalho_id'] ?? 0),
            (string)($row['data_compra'] ?? ''),
            (string)($row['fornecedor'] ?? ''),
            (string)($row['produto'] ?? ''),
            (string)($row['motorista'] ?? ''),
            (string)($row['tipo_caminhao'] ?? ''),
            (float)($row['quantidade'] ?? 0),
            (float)($row['valor_unitario'] ?? 0),
            (float)($row['custo_total'] ?? 0),
            (float)($row['comissao_total'] ?? 0),
            (float)($row['custo_final_real'] ?? 0),
            (float)($row['valor_total_agregado'] ?? 0),
            (int)($row['itens_count'] ?? 0),
            (string)($row['status_textual'] ?? ''),
            (string)($row['data_envio_prevista'] ?? ''),
            (string)($row['data_entrega_prevista'] ?? ''),
        ];
    }
}
