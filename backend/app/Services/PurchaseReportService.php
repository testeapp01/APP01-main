<?php

namespace App\Services;

use App\Helpers\Schema;
use App\Repositories\PurchaseReportRepository;
use PDO;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PurchaseReportService
{
    private array $columnsCache = [];

    public function __construct(private PDO $pdo, private PurchaseReportRepository $repository)
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

    public function summary(array $query): array
    {
        $fat = ['faturamento' => 0];
        $com = ['total_comissao' => 0];

        try {
            $stmt = $this->pdo->query('SELECT IFNULL(SUM(receita_total),0) as faturamento FROM vendas');
            $fat = $stmt->fetch(PDO::FETCH_ASSOC) ?: $fat;
        } catch (\Throwable) {
            $fat = $fat;
        }

        try {
            $stmt = $this->pdo->query('SELECT IFNULL(SUM(comissao_total),0) as total_comissao FROM compras');
            $com = $stmt->fetch(PDO::FETCH_ASSOC) ?: $com;
        } catch (\Throwable) {
            $com = $com;
        }

        return [
            'faturamento_total' => (float)($fat['faturamento'] ?? 0),
            'comissao_total_paga' => (float)($com['total_comissao'] ?? 0),
        ];
    }

    public function dashboard(array $query): array
    {
        $period = $query['period'] ?? '30d';
        $metric = $query['metric'] ?? 'sales';
        $groupBy = $query['groupBy'] ?? '';

        $daysMap = [
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            '180d' => 180,
            '365d' => 365,
        ];

        $days = $daysMap[$period] ?? 30;
        $fromDate = (new \DateTimeImmutable())->modify("-{$days} days")->format('Y-m-d 00:00:00');

        if ($groupBy === '') {
            $groupBy = $days <= 90 ? 'day' : 'month';
        }
        if (!in_array($groupBy, ['day', 'month'], true)) {
            $groupBy = 'day';
        }
        if (!in_array($metric, ['sales', 'purchases', 'profit'], true)) {
            $metric = 'sales';
        }

        $salesTable = 'vendas';
        $salesValueExpr = '(quantidade * valor_unitario)';
        $salesDateColumn = $this->resolveDateColumn('vendas', ['data_venda', 'created_at']);
        $salesStatusColumn = 'status';

        if ($this->hasColumn('vendas_cabecalho', 'valor_total')) {
            $salesTable = 'vendas_cabecalho';
            $salesValueExpr = 'IFNULL(valor_total, 0)';
            $salesDateColumn = $this->resolveDateColumn('vendas_cabecalho', ['data_inicio_prevista', 'data_fim_prevista', 'created_at']);
            $salesStatusColumn = $this->hasColumn('vendas_cabecalho', 'status') ? 'status' : '';
        }

        $purchasesTable = 'compras';
        $purchaseValueExpr = $this->hasColumn('compras', 'comissao_total')
            ? '((quantidade * valor_unitario) + IFNULL(comissao_total, 0))'
            : '(quantidade * valor_unitario)';
        $purchaseDateColumn = $this->resolveDateColumn('compras', ['data_compra', 'created_at']);

        if ($this->hasColumn('compras_cabecalho', 'valor_total')) {
            $purchasesTable = 'compras_cabecalho';
            $purchaseValueExpr = 'IFNULL(valor_total, 0)';
            $purchaseDateColumn = $this->resolveDateColumn('compras_cabecalho', ['data_envio_prevista', 'data_entrega_prevista', 'created_at']);
        }

        $salesAgg = [];
        try {
            $salesSql = "SELECT
                    COUNT(*) AS sales_count,
                    IFNULL(SUM({$salesValueExpr}), 0) AS sales_total,
                    IFNULL(AVG({$salesValueExpr}), 0) AS average_ticket
                 FROM {$salesTable}";
            $salesParams = [];
            if ($salesDateColumn !== null) {
                $salesSql .= " WHERE {$salesDateColumn} >= :from_date";
                $salesParams['from_date'] = $fromDate;
            }

            $salesStmt = $this->pdo->prepare($salesSql);
            $salesStmt->execute($salesParams);
            $salesAgg = $salesStmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable) {
            $salesAgg = ['sales_count' => 0, 'sales_total' => 0, 'average_ticket' => 0];
        }

        $purchasesAgg = [];
        try {
            $purchasesSql = "SELECT
                    COUNT(*) AS purchases_count,
                    IFNULL(SUM({$purchaseValueExpr}), 0) AS purchases_total
                  FROM {$purchasesTable}";
            $purchaseParams = [];
            if ($purchaseDateColumn !== null) {
                $purchasesSql .= " WHERE {$purchaseDateColumn} >= :from_date";
                $purchaseParams['from_date'] = $fromDate;
            }

            $purchasesStmt = $this->pdo->prepare($purchasesSql);
            $purchasesStmt->execute($purchaseParams);
            $purchasesAgg = $purchasesStmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable) {
            $purchasesAgg = ['purchases_count' => 0, 'purchases_total' => 0];
        }

        $clientsTotal = $this->safeCount('clientes');
        $productsTotal = $this->safeCount('produtos');

        $salesTotal = (float)($salesAgg['sales_total'] ?? 0);
        $purchasesTotal = (float)($purchasesAgg['purchases_total'] ?? 0);

        $cards = [
            'sales_total' => round($salesTotal, 2),
            'purchases_total' => round($purchasesTotal, 2),
            'estimated_profit' => round($salesTotal - $purchasesTotal, 2),
            'sales_count' => (int)($salesAgg['sales_count'] ?? 0),
            'purchases_count' => (int)($purchasesAgg['purchases_count'] ?? 0),
            'average_ticket' => round((float)($salesAgg['average_ticket'] ?? 0), 2),
            'clients_total' => $clientsTotal,
            'products_total' => $productsTotal,
        ];

        $line = $this->buildLineSeries(
            $fromDate,
            $groupBy,
            $metric,
            $salesTable,
            $salesValueExpr,
            $salesDateColumn,
            $purchasesTable,
            $purchaseDateColumn,
            $purchaseValueExpr
        );
        $pie = $this->buildPieSeries($fromDate, $salesTable, $salesDateColumn, $salesStatusColumn);

        return [
            'filters' => [
                'period' => $period,
                'metric' => $metric,
                'groupBy' => $groupBy,
                'fromDate' => $fromDate,
            ],
            'cards' => $cards,
            'line' => $line,
            'pie' => $pie,
        ];
    }

    public function abc(int $dias): array
    {
        $from = date('Y-m-d', strtotime("-{$dias} days"));

        try {
            $stmt = $this->pdo->prepare(
                "SELECT v.produto_id, p.nome AS produto, p.unidade,
                        SUM(v.receita_total)  AS receita_total,
                        SUM(v.quantidade)     AS quantidade_vendida,
                        COUNT(DISTINCT v.id)  AS num_vendas
                 FROM vendas v
                 JOIN produtos p ON p.id = v.produto_id
                 WHERE v.status = 'ENTREGUE'
                   AND v.data_venda >= :from
                 GROUP BY v.produto_id, p.nome, p.unidade
                 ORDER BY receita_total DESC"
            );
            $stmt->execute(['from' => $from]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            return ['items' => [], 'total_receita' => 0, 'dias' => $dias];
        }

        $totalReceita = array_sum(array_column($rows, 'receita_total'));
        $acumulado = 0;
        $resultado = [];

        foreach ($rows as $row) {
            $receita = (float)$row['receita_total'];
            $percentual = $totalReceita > 0 ? ($receita / $totalReceita) * 100 : 0;
            $acumulado += $percentual;

            $classe = 'C';
            if ($acumulado <= 80) {
                $classe = 'A';
            } elseif ($acumulado <= 95) {
                $classe = 'B';
            }

            $resultado[] = [
                'produto_id' => (int)$row['produto_id'],
                'produto' => $row['produto'],
                'unidade' => $row['unidade'],
                'receita_total' => round($receita, 2),
                'quantidade_vendida' => (float)$row['quantidade_vendida'],
                'num_vendas' => (int)$row['num_vendas'],
                'percentual' => round($percentual, 2),
                'acumulado' => round($acumulado, 2),
                'classe' => $classe,
            ];
        }

        return ['items' => $resultado, 'total_receita' => round($totalReceita, 2), 'dias' => $dias];
    }

    private function safeCount(string $table): int
    {
        try {
            return (int)($this->pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn() ?: 0);
        } catch (\Throwable) {
            return 0;
        }
    }

    private function resolveDateColumn(string $table, array $preferred): ?string
    {
        foreach ($preferred as $column) {
            if ($this->hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    private function buildLineSeries(
        string $fromDate,
        string $groupBy,
        string $metric,
        string $salesTable,
        string $salesValueExpr,
        ?string $salesDateColumn,
        string $purchasesTable,
        ?string $purchaseDateColumn,
        string $purchaseValueExpr
    ): array {
        if ($groupBy === 'month') {
            $periodExpr = "DATE_FORMAT(%s, '%Y-%m')";
            $labelExpr = "DATE_FORMAT(%s, '%m/%Y')";
        } else {
            $periodExpr = "DATE(%s)";
            $labelExpr = "DATE_FORMAT(%s, '%d/%m')";
        }

        $sales = [];
        if ($salesDateColumn !== null) {
            $sales = $this->queryTimeSeries(
                sprintf($periodExpr, $salesDateColumn),
                sprintf($labelExpr, $salesDateColumn),
                $salesTable,
                $salesValueExpr,
                $salesDateColumn,
                $fromDate
            );
        }

        $purchases = [];
        if ($purchaseDateColumn !== null) {
            $purchases = $this->queryTimeSeries(
                sprintf($periodExpr, $purchaseDateColumn),
                sprintf($labelExpr, $purchaseDateColumn),
                $purchasesTable,
                $purchaseValueExpr,
                $purchaseDateColumn,
                $fromDate
            );
        }

        $labels = [];
        foreach (array_keys($sales) as $bucket) {
            $labels[$bucket] = $bucket;
        }
        foreach (array_keys($purchases) as $bucket) {
            $labels[$bucket] = $bucket;
        }
        ksort($labels);

        $finalLabels = [];
        $data = [];

        foreach (array_keys($labels) as $bucket) {
            $salesValue = (float)($sales[$bucket]['value'] ?? 0);
            $purchasesValue = (float)($purchases[$bucket]['value'] ?? 0);
            $finalLabels[] = $sales[$bucket]['label'] ?? ($purchases[$bucket]['label'] ?? $bucket);

            if ($metric === 'purchases') {
                $data[] = round($purchasesValue, 2);
            } elseif ($metric === 'profit') {
                $data[] = round($salesValue - $purchasesValue, 2);
            } else {
                $data[] = round($salesValue, 2);
            }
        }

        $datasetLabel = match ($metric) {
            'purchases' => 'Compras',
            'profit' => 'Lucro estimado',
            default => 'Vendas',
        };

        return [
            'labels' => $finalLabels,
            'datasetLabel' => $datasetLabel,
            'data' => $data,
        ];
    }

    private function queryTimeSeries(
        string $periodExpr,
        string $labelExpr,
        string $table,
        string $valueExpr,
        ?string $dateColumn,
        string $fromDate
    ): array {
        $sql = "SELECT
                    {$periodExpr} AS bucket,
                    {$labelExpr} AS label,
                    IFNULL(SUM({$valueExpr}), 0) AS total
                FROM {$table}
                %s
                GROUP BY bucket, label
                ORDER BY bucket ASC";
        $whereClause = '';
        $params = [];
        if ($dateColumn !== null) {
            $whereClause = "WHERE {$dateColumn} >= :from_date";
            $params['from_date'] = $fromDate;
        }
        $sql = sprintf($sql, $whereClause);

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            return [];
        }

        $series = [];
        foreach ($rows as $row) {
            $bucket = (string)($row['bucket'] ?? '');
            if ($bucket === '') {
                continue;
            }
            $series[$bucket] = [
                'label' => (string)($row['label'] ?? $bucket),
                'value' => (float)($row['total'] ?? 0),
            ];
        }

        return $series;
    }

    private function buildPieSeries(string $fromDate, string $salesTable, ?string $salesDateColumn, string $statusColumn): array
    {
        if ($statusColumn === '' || !$this->hasColumn($salesTable, $statusColumn)) {
            return [
                'labels' => ['Sem dados'],
                'data' => [1],
            ];
        }

        try {
            $sql = "SELECT {$statusColumn} AS status, COUNT(*) AS total
                 FROM {$salesTable}";
            $params = [];
            if ($salesDateColumn !== null) {
                $sql .= " WHERE {$salesDateColumn} >= :from_date";
                $params['from_date'] = $fromDate;
            }
            $sql .= ' GROUP BY status
                 ORDER BY total DESC';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            $rows = [];
        }

        if (!$rows) {
            return [
                'labels' => ['Sem dados'],
                'data' => [1],
            ];
        }

        $labels = [];
        $data = [];
        foreach ($rows as $row) {
            $labels[] = (string)($row['status'] ?? 'Indefinido');
            $data[] = (int)($row['total'] ?? 0);
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    private function hasColumn(string $table, string $column): bool
    {
        return in_array($column, $this->tableColumns($table), true);
    }

    private function tableColumns(string $table): array
    {
        if (isset($this->columnsCache[$table])) {
            return $this->columnsCache[$table];
        }

        $cols = Schema::tableColumns($this->pdo, $table);
        $this->columnsCache[$table] = $cols;
        return $this->columnsCache[$table];
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
