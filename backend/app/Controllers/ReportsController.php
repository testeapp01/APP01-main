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
}
