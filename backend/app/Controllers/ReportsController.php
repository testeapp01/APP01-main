<?php
namespace App\Controllers;

use PDO;

class ReportsController
{
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
        $stmt = $this->pdo->query('SELECT IFNULL(SUM(receita_total),0) as faturamento FROM vendas');
        $fat = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt2 = $this->pdo->query('SELECT IFNULL(SUM(comissao_total),0) as total_comissao FROM compras');
        $com = $stmt2->fetch(PDO::FETCH_ASSOC);

        echo json_encode(['faturamento_total' => (float)$fat['faturamento'], 'comissao_total_paga' => (float)$com['total_comissao']]);
    }

    public function dashboard(): void
    {
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

        $salesStmt = $this->pdo->prepare(
            'SELECT
                COUNT(*) AS sales_count,
                IFNULL(SUM(quantidade * valor_unitario), 0) AS sales_total,
                IFNULL(AVG(quantidade * valor_unitario), 0) AS average_ticket
             FROM vendas
             WHERE data_venda >= :from_date'
        );
        $salesStmt->execute(['from_date' => $fromDate]);
        $salesAgg = $salesStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $purchasesStmt = $this->pdo->prepare(
            'SELECT
                COUNT(*) AS purchases_count,
                IFNULL(SUM((quantidade * valor_unitario) + IFNULL(comissao_total, 0)), 0) AS purchases_total
             FROM compras
             WHERE data_compra >= :from_date'
        );
        $purchasesStmt->execute(['from_date' => $fromDate]);
        $purchasesAgg = $purchasesStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $clientsTotal = (int)($this->pdo->query('SELECT COUNT(*) FROM clientes')->fetchColumn() ?: 0);
        $productsTotal = (int)($this->pdo->query('SELECT COUNT(*) FROM produtos')->fetchColumn() ?: 0);

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

        $line = $this->buildLineSeries($fromDate, $groupBy, $metric);
        $pie = $this->buildPieSeries($fromDate);

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

    private function buildLineSeries(string $fromDate, string $groupBy, string $metric): array
    {
        if ($groupBy === 'month') {
            $periodExpr = "DATE_FORMAT(%s, '%Y-%m')";
            $labelExpr = "DATE_FORMAT(%s, '%m/%Y')";
        } else {
            $periodExpr = "DATE(%s)";
            $labelExpr = "DATE_FORMAT(%s, '%d/%m')";
        }

        $sales = $this->queryTimeSeries(
            sprintf($periodExpr, 'data_venda'),
            sprintf($labelExpr, 'data_venda'),
            'vendas',
            '(quantidade * valor_unitario)',
            'data_venda',
            $fromDate
        );

        $purchases = $this->queryTimeSeries(
            sprintf($periodExpr, 'data_compra'),
            sprintf($labelExpr, 'data_compra'),
            'compras',
            '((quantidade * valor_unitario) + IFNULL(comissao_total, 0))',
            'data_compra',
            $fromDate
        );

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
        string $dateColumn,
        string $fromDate
    ): array {
        $sql = "SELECT
                    {$periodExpr} AS bucket,
                    {$labelExpr} AS label,
                    IFNULL(SUM({$valueExpr}), 0) AS total
                FROM {$table}
                WHERE {$dateColumn} >= :from_date
                GROUP BY bucket, label
                ORDER BY bucket ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['from_date' => $fromDate]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

    private function buildPieSeries(string $fromDate): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT status, COUNT(*) AS total
             FROM vendas
             WHERE data_venda >= :from_date
             GROUP BY status
             ORDER BY total DESC'
        );
        $stmt->execute(['from_date' => $fromDate]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
}
