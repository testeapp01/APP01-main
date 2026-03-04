<?php
namespace App\Controllers;

use App\Repositories\PurchaseReportRepository;
use App\Services\PurchaseReportService;
use PDO;

class ReportsController
{
    private array $columnsCache = [];

    public function __construct(private PDO $pdo)
    {
    }

    public function index(): void
    {
        // Very small demo of KPIs: faturamento total and total comissoes in period
        $query = $_GET['q'] ?? null;
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;

        // For demo, return simple aggregated numbers
        $fat = ['faturamento' => 0];
        $com = ['total_comissao' => 0];

        try {
            $stmt = $this->pdo->query('SELECT IFNULL(SUM(receita_total),0) as faturamento FROM vendas');
            $fat = $stmt->fetch(PDO::FETCH_ASSOC) ?: $fat;
        } catch (\Throwable $e) {
        }

        try {
            $stmt2 = $this->pdo->query('SELECT IFNULL(SUM(comissao_total),0) as total_comissao FROM compras');
            $com = $stmt2->fetch(PDO::FETCH_ASSOC) ?: $com;
        } catch (\Throwable $e) {
        }

        echo json_encode(['faturamento_total' => (float)$fat['faturamento'], 'comissao_total_paga' => (float)$com['total_comissao']]);
    }

    public function dashboard(): void
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');

        $period = $_GET['period'] ?? '30d';
        $metric = $_GET['metric'] ?? 'sales';
        $groupBy = $_GET['groupBy'] ?? '';

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
        } catch (\Throwable $e) {
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
        } catch (\Throwable $e) {
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
            $purchaseValueExpr,
            $purchaseDateColumn
        );
        $pie = $this->buildPieSeries($fromDate, $salesTable, $salesDateColumn, $salesStatusColumn);

        echo json_encode([
            'filters' => [
                'period' => $period,
                'metric' => $metric,
                'groupBy' => $groupBy,
                'fromDate' => $fromDate,
            ],
            'cards' => $cards,
            'line' => $line,
            'pie' => $pie,
        ]);
    }

    public function strategicPurchases(): void
    {
        try {
            $service = new PurchaseReportService(new PurchaseReportRepository($this->pdo));
            $payload = $service->strategic($_GET);
            echo json_encode($payload);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Não foi possível montar o relatório estratégico de compras.']);
        }
    }

    public function exportStrategicPurchases(): void
    {
        $format = strtolower((string)($_GET['format'] ?? 'csv'));
        if (!in_array($format, ['csv', 'xlsx'], true)) {
            http_response_code(400);
            echo json_encode(['error' => 'Formato de exportação inválido.']);
            return;
        }

        try {
            $service = new PurchaseReportService(new PurchaseReportRepository($this->pdo));
            $export = $service->export($format, $_GET);

            header('Content-Type: ' . $export['contentType']);
            header('Content-Disposition: attachment; filename="' . $export['fileName'] . '"');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            echo $export['body'];
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Não foi possível exportar o relatório estratégico de compras.']);
        }
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
    ): array
    {
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
        foreach (array_keys($sales) as $k) {
            $labels[$k] = $k;
        }
        foreach (array_keys($purchases) as $k) {
            $labels[$k] = $k;
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
        } catch (\Throwable $e) {
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
        } catch (\Throwable $e) {
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

    private function safeCount(string $table): int
    {
        try {
            return (int)($this->pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn() ?: 0);
        } catch (\Throwable $e) {
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

    private function hasColumn(string $table, string $column): bool
    {
        return in_array($column, $this->tableColumns($table), true);
    }

    private function tableColumns(string $table): array
    {
        if (isset($this->columnsCache[$table])) {
            return $this->columnsCache[$table];
        }

        try {
            $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $stmt = $this->pdo->query("PRAGMA table_info({$table})");
                $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
                $this->columnsCache[$table] = array_values(array_filter(array_map(
                    static fn(array $row) => $row['name'] ?? null,
                    $rows
                )));
            } else {
                $stmt = $this->pdo->query("SHOW COLUMNS FROM {$table}");
                $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
                $this->columnsCache[$table] = array_values(array_filter(array_map(
                    static fn(array $row) => $row['Field'] ?? null,
                    $rows
                )));
            }
        } catch (\Throwable $e) {
            $this->columnsCache[$table] = [];
        }

        return $this->columnsCache[$table];
    }
}
