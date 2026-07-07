<?php

namespace App\Repositories;

use PDO;

class PurchaseReportRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function fetchStrategicReport(array $filters, int $page, int $perPage, string $sortBy, string $sortDir): array
    {
        [$whereSql, $params] = $this->buildWhereClause($filters);
        $baseSql = $this->buildGroupedBaseSql($whereSql);

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM ({$baseSql}) report_base");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $sortMap = [
            'compra_id' => 'report_base.compra_id',
            'data_compra' => 'report_base.data_compra',
            'fornecedor' => 'report_base.fornecedor',
            'produto' => 'report_base.produto',
            'motorista' => 'report_base.motorista',
            'tipo_caminhao' => 'report_base.tipo_caminhao',
            'quantidade' => 'report_base.quantidade',
            'valor_unitario' => 'report_base.valor_unitario',
            'custo_total' => 'report_base.custo_total',
            'comissao_total' => 'report_base.comissao_total',
            'custo_final_real' => 'report_base.custo_final_real',
            'status_textual' => 'report_base.status_textual',
            'data_envio_prevista' => 'report_base.data_envio_prevista',
            'data_entrega_prevista' => 'report_base.data_entrega_prevista',
            'itens_count' => 'report_base.itens_count',
            'valor_total_agregado' => 'report_base.valor_total_agregado',
        ];

        $sortExpr = $sortMap[$sortBy] ?? $sortMap['data_compra'];
        $direction = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';
        $offset = max(0, ($page - 1) * $perPage);

        $rowsSql = "SELECT *
                    FROM ({$baseSql}) report_base
                    ORDER BY {$sortExpr} {$direction}, report_base.compra_id DESC
                    LIMIT :limit OFFSET :offset";
        $rowsStmt = $this->pdo->prepare($rowsSql);
        foreach ($params as $key => $value) {
            $rowsStmt->bindValue($key, $value);
        }
        $rowsStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $rowsStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $rowsStmt->execute();
        $rows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $timelines = $this->fetchStatusTimeline(array_values(array_filter(array_map(
            static fn(array $row) => isset($row['compra_cabecalho_id']) ? (int)$row['compra_cabecalho_id'] : 0,
            $rows
        ))));

        foreach ($rows as &$row) {
            $headerId = (int)($row['compra_cabecalho_id'] ?? 0);
            $row['status_timeline'] = $headerId > 0 ? ($timelines[$headerId] ?? []) : [];
        }

        return ['items' => $rows, 'total' => $total];
    }

    public function fetchKpis(array $filters): array
    {
        [$whereSql, $params] = $this->buildWhereClause($filters);
        $baseSql = $this->buildGroupedBaseSql($whereSql);

        $stmt = $this->pdo->prepare(
            "SELECT
                IFNULL(SUM(k.custo_total), 0) AS soma_custo_total,
                IFNULL(SUM(k.comissao_total), 0) AS soma_comissao_total,
                IFNULL(SUM(k.custo_final_real), 0) AS soma_custo_final_real,
                IFNULL(AVG(k.custo_total), 0) AS ticket_medio,
                IFNULL(AVG(CASE WHEN k.data_entrega_prevista IS NOT NULL THEN DATEDIFF(k.data_entrega_prevista, DATE(k.data_compra)) END), 0) AS prazo_medio_dias
             FROM ({$baseSql}) k"
        );
        $stmt->execute($params);
        $base = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $produtoStmt = $this->pdo->prepare(
            "SELECT p.nome AS produto, IFNULL(SUM(c.quantidade), 0) AS total_quantidade
             FROM compras c
             LEFT JOIN compras_cabecalho cc ON cc.id = c.compra_cabecalho_id
             LEFT JOIN fornecedores f ON f.id = COALESCE(cc.fornecedor_id, c.fornecedor_id)
             LEFT JOIN produtos p ON p.id = c.produto_id
             LEFT JOIN motoristas m ON m.id = COALESCE(cc.motorista_id, c.motorista_id)
             LEFT JOIN status_compra sc ON sc.id = cc.id_statuscompra
             {$whereSql}
             GROUP BY p.id, p.nome
             ORDER BY total_quantidade DESC
             LIMIT 1"
        );
        $produtoStmt->execute($params);
        $produtoTop = $produtoStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $fornecedorStmt = $this->pdo->prepare(
            "SELECT MAX(f.razao_social) AS fornecedor, IFNULL(SUM(c.custo_final_real), 0) AS total_financeiro
             FROM compras c
             LEFT JOIN compras_cabecalho cc ON cc.id = c.compra_cabecalho_id
             LEFT JOIN fornecedores f ON f.id = COALESCE(cc.fornecedor_id, c.fornecedor_id)
             LEFT JOIN produtos p ON p.id = c.produto_id
             LEFT JOIN motoristas m ON m.id = COALESCE(cc.motorista_id, c.motorista_id)
             LEFT JOIN status_compra sc ON sc.id = cc.id_statuscompra
             {$whereSql}
             GROUP BY COALESCE(cc.fornecedor_id, c.fornecedor_id)
             ORDER BY total_financeiro DESC
             LIMIT 1"
        );
        $fornecedorStmt->execute($params);
        $fornecedorTop = $fornecedorStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'soma_custo_total' => (float)($base['soma_custo_total'] ?? 0),
            'soma_comissao_total' => (float)($base['soma_comissao_total'] ?? 0),
            'soma_custo_final_real' => (float)($base['soma_custo_final_real'] ?? 0),
            'ticket_medio' => (float)($base['ticket_medio'] ?? 0),
            'prazo_medio_dias' => (float)($base['prazo_medio_dias'] ?? 0),
            'produto_mais_comprado' => [
                'nome' => (string)($produtoTop['produto'] ?? 'Sem dados'),
                'quantidade' => (float)($produtoTop['total_quantidade'] ?? 0),
            ],
            'fornecedor_maior_volume' => [
                'nome' => (string)($fornecedorTop['fornecedor'] ?? 'Sem dados'),
                'total_financeiro' => (float)($fornecedorTop['total_financeiro'] ?? 0),
            ],
        ];
    }

    public function fetchChartSeries(array $filters): array
    {
        [$whereSql, $params] = $this->buildWhereClause($filters);
        $baseSql = $this->buildGroupedBaseSql($whereSql);

        $lineStmt = $this->pdo->prepare(
            "SELECT DATE_FORMAT(c.data_compra, '%Y-%m') AS bucket,
                    DATE_FORMAT(c.data_compra, '%m/%Y') AS label,
                    IFNULL(SUM(c.custo_final_real), 0) AS total
             FROM compras c
             LEFT JOIN compras_cabecalho cc ON cc.id = c.compra_cabecalho_id
             LEFT JOIN fornecedores f ON f.id = COALESCE(cc.fornecedor_id, c.fornecedor_id)
             LEFT JOIN produtos p ON p.id = c.produto_id
             LEFT JOIN motoristas m ON m.id = COALESCE(cc.motorista_id, c.motorista_id)
             LEFT JOIN status_compra sc ON sc.id = cc.id_statuscompra
             {$whereSql}
             GROUP BY bucket, label
             ORDER BY bucket ASC"
        );
        $lineStmt->execute($params);
        $lineRows = $lineStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $barStmt = $this->pdo->prepare(
            "SELECT COALESCE(report_base.fornecedor, 'Não informado') AS fornecedor,
                    IFNULL(SUM(report_base.custo_final_real), 0) AS total
             FROM ({$baseSql}) report_base
             GROUP BY report_base.fornecedor
             ORDER BY total DESC
             LIMIT 10"
        );
        $barStmt->execute($params);
        $barRows = $barStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $pieStmt = $this->pdo->prepare(
            "SELECT report_base.status_textual AS status,
                    COUNT(*) AS total
             FROM ({$baseSql}) report_base
             GROUP BY report_base.status_textual
             ORDER BY total DESC"
        );
        $pieStmt->execute($params);
        $pieRows = $pieStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return [
            'line' => [
                'labels' => array_values(array_map(static fn(array $r) => (string)$r['label'], $lineRows)),
                'data' => array_values(array_map(static fn(array $r) => (float)$r['total'], $lineRows)),
                'datasetLabel' => 'Evolução mensal de custo final',
            ],
            'bar' => [
                'labels' => array_values(array_map(static fn(array $r) => (string)$r['fornecedor'], $barRows)),
                'data' => array_values(array_map(static fn(array $r) => (float)$r['total'], $barRows)),
                'datasetLabel' => 'Fornecedor x volume financeiro',
            ],
            'pie' => [
                'labels' => array_values(array_map(static fn(array $r) => (string)$r['status'], $pieRows)),
                'data' => array_values(array_map(static fn(array $r) => (int)$r['total'], $pieRows)),
            ],
        ];
    }

    public function fetchFilterOptions(): array
    {
        $fornecedores = $this->pdo->query('SELECT id, razao_social FROM fornecedores ORDER BY razao_social ASC')->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $produtos = $this->pdo->query('SELECT id, nome FROM produtos ORDER BY nome ASC')->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $motoristas = $this->pdo->query('SELECT id, nome FROM motoristas ORDER BY nome ASC')->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $statusRows = $this->pdo->query(
            "SELECT UPPER(nome) AS status FROM status_compra
             UNION
             SELECT DISTINCT UPPER(status) AS status FROM compras_cabecalho
             UNION
             SELECT DISTINCT UPPER(status) AS status FROM compras
             ORDER BY status"
        )->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $ufs = $this->pdo->query(
            "SELECT uf FROM (
                SELECT TRIM(UPPER(uf)) AS uf FROM fornecedores
                UNION
                SELECT TRIM(UPPER(uf)) AS uf FROM motoristas
             ) x
             WHERE uf IS NOT NULL AND uf <> ''
             ORDER BY uf"
        )->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return [
            'fornecedores' => $fornecedores,
            'produtos' => $produtos,
            'motoristas' => $motoristas,
            'status' => array_values(array_map(static fn(array $row) => (string)$row['status'], $statusRows)),
            'ufs' => array_values(array_map(static fn(array $row) => (string)$row['uf'], $ufs)),
        ];
    }

    public function indexSuggestions(): array
    {
        return [
            'compras' => [
                'idx_compras_data_compra (data_compra)',
                'idx_compras_produto_data (produto_id, data_compra)',
                'idx_compras_fornecedor_data (fornecedor_id, data_compra)',
                'idx_compras_motorista_data (motorista_id, data_compra)',
                'idx_compras_status_data (status, data_compra)',
            ],
            'compras_cabecalho' => [
                'idx_compras_cabecalho_status_data (status, data_entrega_prevista)',
                'idx_compras_cabecalho_fornecedor (fornecedor_id)',
            ],
            'historico_status_compra' => [
                'idx_historico_status_compra_cabecalho_data (compra_cabecalho_id, confirmado_em)',
            ],
        ];
    }

    private function fetchStatusTimeline(array $headerIds): array
    {
        if (empty($headerIds)) {
            return [];
        }

        $placeholders = [];
        $params = [];
        foreach (array_values(array_unique($headerIds)) as $index => $id) {
            $key = ':id' . $index;
            $placeholders[] = $key;
            $params[$key] = $id;
        }

        $sql = "SELECT hsc.compra_cabecalho_id,
                       COALESCE(UPPER(sc.nome), CASE WHEN hsc.id_statuscompra = 2 THEN 'RECEBIDA' ELSE 'AGUARDANDO' END) AS status,
                       hsc.confirmado_em,
                       u.name AS usuario
                FROM historico_status_compra hsc
                LEFT JOIN status_compra sc ON sc.id = hsc.id_statuscompra
                LEFT JOIN users u ON u.id = hsc.usuario_id
                WHERE hsc.compra_cabecalho_id IN (" . implode(', ', $placeholders) . ")
                ORDER BY hsc.compra_cabecalho_id ASC, hsc.confirmado_em DESC, hsc.id DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $timeline = [];
        foreach ($rows as $row) {
            $headerId = (int)$row['compra_cabecalho_id'];
            $timeline[$headerId][] = [
                'status' => (string)($row['status'] ?? ''),
                'confirmado_em' => (string)($row['confirmado_em'] ?? ''),
                'usuario' => (string)($row['usuario'] ?? 'Sistema'),
            ];
        }

        return $timeline;
    }

    private function buildGroupedBaseSql(string $whereSql): string
    {
        return "SELECT
                    COALESCE(cc.id, c.id) AS compra_grupo_id,
                    cc.id AS compra_cabecalho_id,
                    MIN(c.id) AS compra_id,
                    MIN(c.data_compra) AS data_compra,
                    MAX(f.razao_social) AS fornecedor,
                    GROUP_CONCAT(DISTINCT p.nome ORDER BY p.nome SEPARATOR ' • ') AS produto,
                    GROUP_CONCAT(DISTINCT m.nome ORDER BY m.nome SEPARATOR ' • ') AS motorista,
                    MAX(tc.nome) AS tipo_caminhao,
                    IFNULL(SUM(c.quantidade), 0) AS quantidade,
                    IFNULL(AVG(c.valor_unitario), 0) AS valor_unitario,
                    IFNULL(SUM(c.custo_total), 0) AS custo_total,
                    IFNULL(SUM(c.comissao_total), 0) AS comissao_total,
                    IFNULL(SUM(c.custo_final_real), 0) AS custo_final_real,
                    COALESCE(MAX(UPPER(sc.nome)), MAX(UPPER(cc.status)), MAX(UPPER(c.status)), 'AGUARDANDO') AS status_textual,
                    MAX(COALESCE(cc.data_envio_prevista, c.data_envio_prevista)) AS data_envio_prevista,
                    MAX(COALESCE(cc.data_entrega_prevista, c.data_entrega_prevista)) AS data_entrega_prevista,
                    COUNT(*) AS itens_count,
                    IFNULL(SUM(c.quantidade * c.valor_unitario), 0) AS valor_total_agregado
                FROM compras c
                LEFT JOIN compras_cabecalho cc ON cc.id = c.compra_cabecalho_id
                LEFT JOIN fornecedores f ON f.id = COALESCE(cc.fornecedor_id, c.fornecedor_id)
                LEFT JOIN produtos p ON p.id = c.produto_id
                LEFT JOIN motoristas m ON m.id = COALESCE(cc.motorista_id, c.motorista_id)
                LEFT JOIN tipos_caminhao tc ON tc.id = m.TpCaminhao
                LEFT JOIN status_compra sc ON sc.id = cc.id_statuscompra
                {$whereSql}
                GROUP BY COALESCE(cc.id, c.id), cc.id";
    }

    private function buildWhereClause(array $filters): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['from'])) {
            $conditions[] = 'c.data_compra >= :from_date';
            $params[':from_date'] = $filters['from'] . ' 00:00:00';
        }
        if (!empty($filters['to'])) {
            $conditions[] = 'c.data_compra <= :to_date';
            $params[':to_date'] = $filters['to'] . ' 23:59:59';
        }
        if (!empty($filters['fornecedor_id'])) {
            $conditions[] = 'COALESCE(cc.fornecedor_id, c.fornecedor_id) = :fornecedor_id';
            $params[':fornecedor_id'] = (int)$filters['fornecedor_id'];
        }
        if (!empty($filters['produto_id'])) {
            $conditions[] = 'c.produto_id = :produto_id';
            $params[':produto_id'] = (int)$filters['produto_id'];
        }
        if (!empty($filters['motorista_id'])) {
            $conditions[] = 'COALESCE(cc.motorista_id, c.motorista_id) = :motorista_id';
            $params[':motorista_id'] = (int)$filters['motorista_id'];
        }
        if (!empty($filters['status'])) {
            $conditions[] = 'UPPER(COALESCE(sc.nome, cc.status, c.status)) = :status';
            $params[':status'] = strtoupper((string)$filters['status']);
        }
        if (!empty($filters['uf'])) {
            $conditions[] = 'UPPER(COALESCE(NULLIF(f.uf, \'\'), NULLIF(m.uf, \'\'))) = :uf';
            $params[':uf'] = strtoupper((string)$filters['uf']);
        }
        if (!empty($filters['q'])) {
            $conditions[] = '(f.razao_social LIKE :term OR p.nome LIKE :term OR m.nome LIKE :term OR UPPER(COALESCE(sc.nome, cc.status, c.status)) LIKE :term_u)';
            $params[':term'] = '%' . $filters['q'] . '%';
            $params[':term_u'] = '%' . strtoupper((string)$filters['q']) . '%';
        }

        $whereSql = empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);

        return [$whereSql, $params];
    }
}
