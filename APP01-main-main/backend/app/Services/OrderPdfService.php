<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use PDO;

class OrderPdfService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function renderPurchaseHeaderPdf(int $headerId): ?string
    {
        $header = $this->fetchPurchaseHeader($headerId);
        if (!$header) {
            return null;
        }

        $items = $this->fetchPurchaseItems($headerId);
        $timeline = $this->fetchPurchaseTimeline($headerId);
        $html = $this->buildPurchaseHtml($header, $items, $timeline);
        $orientation = $this->resolveOrientation($items);

        return $this->renderPdf($html, $orientation, 'Pedido de Compra #' . (int)$header['id']);
    }

    public function renderSalesHeaderPdf(int $headerId): ?string
    {
        $header = $this->fetchSalesHeader($headerId);
        if (!$header) {
            return null;
        }

        $items = $this->fetchSalesItems($headerId);
        $timeline = $this->fetchSalesTimeline($headerId);
        $html = $this->buildSalesHtml($header, $items, $timeline);
        $orientation = $this->resolveOrientation($items);

        return $this->renderPdf($html, $orientation, 'Pedido de Venda #' . (int)$header['id']);
    }

    private function fetchPurchaseHeader(int $headerId): ?array
    {
        $sql = "SELECT
                    h.id,
                    h.tipo_operacao,
                    h.valor_total,
                    UPPER(COALESCE(sc.nome, h.status, 'AGUARDANDO')) AS status,
                    h.data_envio_prevista,
                    h.data_entrega_prevista,
                    f.razao_social AS fornecedor,
                    f.cnpj AS fornecedor_cnpj,
                    f.telefone AS fornecedor_telefone,
                    f.email AS fornecedor_email,
                    f.endereco AS fornecedor_endereco,
                    f.numero AS fornecedor_numero,
                    f.complemento AS fornecedor_complemento,
                    f.bairro AS fornecedor_bairro,
                    f.cidade AS fornecedor_cidade,
                    f.uf AS fornecedor_uf,
                    f.cep AS fornecedor_cep,
                    cl.nome AS cliente,
                    cl.telefone AS cliente_telefone,
                    cl.email AS cliente_email,
                    cl.endereco AS cliente_endereco,
                    cl.numero AS cliente_numero,
                    cl.complemento AS cliente_complemento,
                    cl.bairro AS cliente_bairro,
                    cl.cidade AS cliente_cidade,
                    cl.uf AS cliente_uf,
                    cl.cep AS cliente_cep,
                    m.nome AS motorista,
                    m.telefone AS motorista_telefone,
                    tc.nome AS tipo_caminhao
                FROM compras_cabecalho h
                LEFT JOIN fornecedores f ON f.id = h.fornecedor_id
                LEFT JOIN clientes cl ON cl.id = h.cliente_id
                LEFT JOIN motoristas m ON m.id = h.motorista_id
                LEFT JOIN tipos_caminhao tc ON tc.id = m.TpCaminhao
                LEFT JOIN status_compra sc ON sc.id = h.id_statuscompra
                WHERE h.id = :id
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $headerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    private function fetchPurchaseItems(int $headerId): array
    {
        $sql = "SELECT
                    c.id,
                    p.nome AS produto,
                    c.quantidade,
                    c.valor_unitario,
                    c.custo_total,
                    c.comissao_total,
                    c.custo_final_real,
                    UPPER(COALESCE(c.status, 'AGUARDANDO')) AS status,
                    c.data_compra
                FROM compras c
                LEFT JOIN produtos p ON p.id = c.produto_id
                WHERE c.compra_cabecalho_id = :id
                ORDER BY c.id ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $headerId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function fetchPurchaseTimeline(int $headerId): array
    {
        try {
            $sql = "SELECT
                        COALESCE(UPPER(sc.nome), CASE WHEN hsc.id_statuscompra = 2 THEN 'RECEBIDA' ELSE 'AGUARDANDO' END) AS status,
                        hsc.confirmado_em,
                        COALESCE(u.name, 'Sistema') AS usuario
                    FROM historico_status_compra hsc
                    LEFT JOIN status_compra sc ON sc.id = hsc.id_statuscompra
                    LEFT JOIN users u ON u.id = hsc.usuario_id
                    WHERE hsc.compra_cabecalho_id = :id
                    ORDER BY hsc.confirmado_em DESC, hsc.id DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['id' => $headerId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable) {
            return [];
        }
    }

    private function fetchSalesHeader(int $headerId): ?array
    {
        $sql = "SELECT
                    h.id,
                    h.tipo,
                    h.valor_total,
                    UPPER(COALESCE(sp.nome, h.status, 'AGUARDANDO')) AS status,
                    h.data_inicio_prevista,
                    h.data_fim_prevista,
                    cl.nome AS cliente,
                    cl.telefone AS cliente_telefone,
                    cl.email AS cliente_email,
                    cl.endereco AS cliente_endereco,
                    cl.numero AS cliente_numero,
                    cl.complemento AS cliente_complemento,
                    cl.bairro AS cliente_bairro,
                    cl.cidade AS cliente_cidade,
                    cl.uf AS cliente_uf,
                    cl.cep AS cliente_cep
                FROM vendas_cabecalho h
                LEFT JOIN clientes cl ON cl.id = h.cliente_id
                LEFT JOIN status_pedido sp ON sp.id = h.id_statuspedido
                WHERE h.id = :id
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $headerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    private function fetchSalesItems(int $headerId): array
    {
        $sql = "SELECT
                    v.id,
                    p.nome AS produto,
                    v.quantidade,
                    v.valor_unitario,
                    v.receita_total,
                    v.custo_proporcional,
                    v.lucro_bruto,
                    UPPER(COALESCE(v.status, 'AGUARDANDO')) AS status,
                    v.data_venda
                FROM vendas v
                LEFT JOIN produtos p ON p.id = v.produto_id
                WHERE v.venda_cabecalho_id = :id
                ORDER BY v.id ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $headerId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function fetchSalesTimeline(int $headerId): array
    {
        try {
            $sql = "SELECT
                        COALESCE(UPPER(sp.nome), CASE WHEN hsp.id_statuspedido = 2 THEN 'ENTREGUE' ELSE 'AGUARDANDO' END) AS status,
                        hsp.confirmado_em,
                        COALESCE(u.name, 'Sistema') AS usuario
                    FROM historico_status_pedido hsp
                    LEFT JOIN status_pedido sp ON sp.id = hsp.id_statuspedido
                    LEFT JOIN users u ON u.id = hsp.usuario_id
                    WHERE hsp.venda_cabecalho_id = :id
                    ORDER BY hsp.confirmado_em DESC, hsp.id DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['id' => $headerId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable) {
            return [];
        }
    }

    private function renderPdf(string $html, string $orientation = 'portrait', string $documentLabel = 'Documento'): string
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', $orientation === 'landscape' ? 'landscape' : 'portrait');
        $dompdf->render();

        $canvas = $dompdf->getCanvas();
        $fontMetrics = $dompdf->getFontMetrics();
        $font = $fontMetrics->getFont('DejaVu Sans', 'normal');
        $pageWidth = $canvas->get_width();
        $pageHeight = $canvas->get_height();
        $color = [0.39, 0.45, 0.55];

        $canvas->page_text(36, $pageHeight - 24, $documentLabel . ' • Emitido pela Safrion', $font, 8, $color);
        $canvas->page_text($pageWidth - 130, $pageHeight - 24, 'Página {PAGE_NUM} de {PAGE_COUNT}', $font, 8, $color);

        return $dompdf->output();
    }

    private function resolveOrientation(array $items): string
    {
        return count($items) > 10 ? 'landscape' : 'portrait';
    }

    private function baseCss(): string
    {
        return '
            body { font-family: DejaVu Sans, Arial, sans-serif; color: #0f172a; margin: 0; font-size: 12px; }
            .page { padding: 26px; }
            .header { border-bottom: 2px solid #10b981; padding-bottom: 12px; margin-bottom: 18px; }
            .brand { display: flex; align-items: center; gap: 12px; }
            .logo { width: 140px; height: auto; }
            .title { font-size: 22px; font-weight: bold; color: #047857; margin: 0; }
            .subtitle { color: #64748b; font-size: 11px; margin-top: 4px; }
            .meta { margin-top: 10px; color: #334155; font-size: 11px; }
            .badge { display: inline-block; background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; border-radius: 20px; padding: 3px 10px; font-size: 10px; font-weight: bold; }
            .grid { width: 100%; border-collapse: separate; border-spacing: 10px; margin-bottom: 10px; }
            .card { border: 1px solid #e2e8f0; border-radius: 12px; padding: 10px; vertical-align: top; }
            .card h3 { margin: 0 0 8px 0; font-size: 12px; color: #0f766e; }
            .label { color: #64748b; font-size: 10px; }
            .value { color: #0f172a; font-size: 11px; margin-bottom: 5px; }
            .section-title { margin: 14px 0 8px 0; font-size: 13px; color: #065f46; font-weight: bold; }
            table.items { width: 100%; border-collapse: collapse; }
            table.items th { background: #f0fdf4; color: #065f46; font-size: 10px; padding: 7px; border: 1px solid #d1fae5; text-align: left; }
            table.items td { font-size: 10px; padding: 6px; border: 1px solid #e2e8f0; }
            .text-right { text-align: right; }
            .totals { width: 100%; margin-top: 8px; border-collapse: collapse; }
            .totals td { border: 1px solid #d1d5db; padding: 6px; font-size: 11px; }
            .footer { margin-top: 16px; color: #64748b; font-size: 9px; border-top: 1px solid #e2e8f0; padding-top: 8px; }
            .timeline-item { border: 1px solid #e2e8f0; border-radius: 8px; padding: 6px 8px; margin-bottom: 6px; }
            .timeline-status { font-size: 10px; font-weight: bold; color: #065f46; }
            .timeline-meta { font-size: 9px; color: #64748b; margin-top: 2px; }
        ';
    }

    private function buildPurchaseHtml(array $header, array $items, array $timeline): string
    {
        $logo = $this->logoDataUri();
        $fornecedorEndereco = $this->formatAddress([
            $header['fornecedor_endereco'] ?? null,
            $header['fornecedor_numero'] ?? null,
            $header['fornecedor_complemento'] ?? null,
            $header['fornecedor_bairro'] ?? null,
            $header['fornecedor_cidade'] ?? null,
            $header['fornecedor_uf'] ?? null,
            $header['fornecedor_cep'] ?? null,
        ]);
        $clienteEndereco = $this->formatAddress([
            $header['cliente_endereco'] ?? null,
            $header['cliente_numero'] ?? null,
            $header['cliente_complemento'] ?? null,
            $header['cliente_bairro'] ?? null,
            $header['cliente_cidade'] ?? null,
            $header['cliente_uf'] ?? null,
            $header['cliente_cep'] ?? null,
        ]);

        $entrega = $clienteEndereco !== '-' ? $clienteEndereco : $fornecedorEndereco;
        $rows = $this->rowsPurchaseItems($items);
        $timelineHtml = $this->timelineHtml($timeline);

        return '<html><head><meta charset="utf-8"><style>' . $this->baseCss() . '</style></head><body><div class="page">
            <div class="header">
                <div class="brand">
                    ' . ($logo ? '<img class="logo" src="' . $logo . '" alt="Logo">' : '') . '
                    <div>
                        <p class="title">Pedido de Compra #' . (int)$header['id'] . '</p>
                        <div class="subtitle">Relatório oficial de compra • Safrion</div>
                    </div>
                </div>
                <div class="meta">Emitido em: ' . $this->e($this->formatDateTime(date('Y-m-d H:i:s'))) . ' • <span class="badge">' . $this->e($header['status'] ?? 'AGUARDANDO') . '</span></div>
            </div>

            <table class="grid"><tr>
                <td class="card" width="50%">
                    <h3>Fornecedor</h3>
                    <div class="label">Razão social</div><div class="value">' . $this->e($header['fornecedor'] ?? '-') . '</div>
                    <div class="label">CNPJ</div><div class="value">' . $this->e($header['fornecedor_cnpj'] ?? '-') . '</div>
                    <div class="label">Contato</div><div class="value">' . $this->e($header['fornecedor_telefone'] ?? '-') . ' • ' . $this->e($header['fornecedor_email'] ?? '-') . '</div>
                    <div class="label">Endereço</div><div class="value">' . $this->e($fornecedorEndereco) . '</div>
                </td>
                <td class="card" width="50%">
                    <h3>Entrega e Operação</h3>
                    <div class="label">Tipo de operação</div><div class="value">' . $this->e($header['tipo_operacao'] ?? '-') . '</div>
                    <div class="label">Cliente destino</div><div class="value">' . $this->e($header['cliente'] ?? '-') . '</div>
                    <div class="label">Endereço de entrega</div><div class="value">' . $this->e($entrega) . '</div>
                    <div class="label">Previsão</div><div class="value">Envio: ' . $this->e($this->formatDate($header['data_envio_prevista'] ?? null)) . ' • Entrega: ' . $this->e($this->formatDate($header['data_entrega_prevista'] ?? null)) . '</div>
                    <div class="label">Motorista</div><div class="value">' . $this->e($header['motorista'] ?? '-') . ' (' . $this->e($header['tipo_caminhao'] ?? '-') . ')</div>
                </td>
            </tr></table>

            <div class="section-title">Itens do pedido</div>
            <table class="items">
                <thead><tr><th>Produto</th><th class="text-right">Qtd</th><th class="text-right">Valor Unit.</th><th class="text-right">Subtotal</th><th>Status</th></tr></thead>
                <tbody>' . $rows . '</tbody>
            </table>

            <table class="totals">
                <tr><td><strong>Total do pedido</strong></td><td class="text-right"><strong>' . $this->e($this->money($header['valor_total'] ?? 0)) . '</strong></td></tr>
            </table>

            <div class="section-title">Acompanhamento</div>
            ' . $timelineHtml . '

            <div class="footer">Documento gerado automaticamente pelo sistema Safrion.</div>
        </div></body></html>';
    }

    private function buildSalesHtml(array $header, array $items, array $timeline): string
    {
        $logo = $this->logoDataUri();
        $clienteEndereco = $this->formatAddress([
            $header['cliente_endereco'] ?? null,
            $header['cliente_numero'] ?? null,
            $header['cliente_complemento'] ?? null,
            $header['cliente_bairro'] ?? null,
            $header['cliente_cidade'] ?? null,
            $header['cliente_uf'] ?? null,
            $header['cliente_cep'] ?? null,
        ]);
        $rows = $this->rowsSalesItems($items);
        $timelineHtml = $this->timelineHtml($timeline);

        return '<html><head><meta charset="utf-8"><style>' . $this->baseCss() . '</style></head><body><div class="page">
            <div class="header">
                <div class="brand">
                    ' . ($logo ? '<img class="logo" src="' . $logo . '" alt="Logo">' : '') . '
                    <div>
                        <p class="title">Pedido de Venda #' . (int)$header['id'] . '</p>
                        <div class="subtitle">Relatório oficial de venda • Safrion</div>
                    </div>
                </div>
                <div class="meta">Emitido em: ' . $this->e($this->formatDateTime(date('Y-m-d H:i:s'))) . ' • <span class="badge">' . $this->e($header['status'] ?? 'AGUARDANDO') . '</span></div>
            </div>

            <table class="grid"><tr>
                <td class="card" width="50%">
                    <h3>Cliente</h3>
                    <div class="label">Nome</div><div class="value">' . $this->e($header['cliente'] ?? '-') . '</div>
                    <div class="label">Contato</div><div class="value">' . $this->e($header['cliente_telefone'] ?? '-') . ' • ' . $this->e($header['cliente_email'] ?? '-') . '</div>
                    <div class="label">Endereço de entrega</div><div class="value">' . $this->e($clienteEndereco) . '</div>
                </td>
                <td class="card" width="50%">
                    <h3>Previsão e operação</h3>
                    <div class="label">Tipo</div><div class="value">' . $this->e($header['tipo'] ?? '-') . '</div>
                    <div class="label">Previsão</div><div class="value">Envio: ' . $this->e($this->formatDate($header['data_inicio_prevista'] ?? null)) . ' • Entrega: ' . $this->e($this->formatDate($header['data_fim_prevista'] ?? null)) . '</div>
                    <div class="label">Valor total</div><div class="value"><strong>' . $this->e($this->money($header['valor_total'] ?? 0)) . '</strong></div>
                </td>
            </tr></table>

            <div class="section-title">Itens do pedido</div>
            <table class="items">
                <thead><tr><th>Produto</th><th class="text-right">Qtd</th><th class="text-right">Valor Unit.</th><th class="text-right">Subtotal</th><th>Status</th></tr></thead>
                <tbody>' . $rows . '</tbody>
            </table>

            <div class="section-title">Acompanhamento</div>
            ' . $timelineHtml . '

            <div class="footer">Documento gerado automaticamente pelo sistema Safrion.</div>
        </div></body></html>';
    }

    private function rowsPurchaseItems(array $items): string
    {
        if (!$items) {
            return '<tr><td colspan="5">Nenhum item encontrado.</td></tr>';
        }

        $rows = '';
        foreach ($items as $item) {
            $subtotal = ((float)($item['quantidade'] ?? 0)) * ((float)($item['valor_unitario'] ?? 0));
            $rows .= '<tr>
                <td>' . $this->e($item['produto'] ?? '-') . '</td>
                <td class="text-right">' . $this->e($this->number($item['quantidade'] ?? 0)) . '</td>
                <td class="text-right">' . $this->e($this->money($item['valor_unitario'] ?? 0)) . '</td>
                <td class="text-right">' . $this->e($this->money($subtotal)) . '</td>
                <td>' . $this->e($item['status'] ?? '-') . '</td>
            </tr>';
        }

        return $rows;
    }

    private function rowsSalesItems(array $items): string
    {
        if (!$items) {
            return '<tr><td colspan="5">Nenhum item encontrado.</td></tr>';
        }

        $rows = '';
        foreach ($items as $item) {
            $subtotal = (float)($item['receita_total'] ?? 0);
            if ($subtotal <= 0) {
                $subtotal = ((float)($item['quantidade'] ?? 0)) * ((float)($item['valor_unitario'] ?? 0));
            }
            $rows .= '<tr>
                <td>' . $this->e($item['produto'] ?? '-') . '</td>
                <td class="text-right">' . $this->e($this->number($item['quantidade'] ?? 0)) . '</td>
                <td class="text-right">' . $this->e($this->money($item['valor_unitario'] ?? 0)) . '</td>
                <td class="text-right">' . $this->e($this->money($subtotal)) . '</td>
                <td>' . $this->e($item['status'] ?? '-') . '</td>
            </tr>';
        }

        return $rows;
    }

    private function timelineHtml(array $timeline): string
    {
        if (!$timeline) {
            return '<div class="timeline-item"><div class="timeline-meta">Sem atualizações de status registradas.</div></div>';
        }

        $latest = $timeline[0] ?? null;
        $status = (string)($latest['status'] ?? '-');
        $when = $this->formatDateTime($latest['confirmado_em'] ?? null);

        return '<div class="timeline-item"><div class="timeline-status">Status atual: ' . $this->e($status) . '</div><div class="timeline-meta">Atualizado em ' . $this->e($when) . '</div></div>';
    }

    private function logoDataUri(): ?string
    {
        $logoPath = dirname(__DIR__, 3) . '/frontend/public/brand-logo.svg';
        if (!is_file($logoPath)) {
            return null;
        }

        $raw = @file_get_contents($logoPath);
        if ($raw === false || $raw === '') {
            return null;
        }

        return 'data:image/svg+xml;base64,' . base64_encode($raw);
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function formatDate(?string $value): string
    {
        if (!$value) {
            return '-';
        }
        $date = date_create($value);
        if (!$date) {
            return $value;
        }

        return date_format($date, 'd/m/Y');
    }

    private function formatDateTime(?string $value): string
    {
        if (!$value) {
            return '-';
        }
        $date = date_create($value);
        if (!$date) {
            return $value;
        }

        return date_format($date, 'd/m/Y H:i');
    }

    private function money(float|int|string|null $value): string
    {
        return 'R$ ' . number_format((float)$value, 2, ',', '.');
    }

    private function number(float|int|string|null $value): string
    {
        return number_format((float)$value, 4, ',', '.');
    }

    private function formatAddress(array $parts): string
    {
        $filtered = array_values(array_filter(array_map(static function ($part) {
            $trimmed = trim((string)($part ?? ''));
            return $trimmed === '' ? null : $trimmed;
        }, $parts)));

        return $filtered ? implode(', ', $filtered) : '-';
    }
}
