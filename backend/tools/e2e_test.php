<?php
// Simple end-to-end test against local API (http://127.0.0.1:8000)
function req($method, $path, $token = null, $body = null)
{
    $url = 'http://127.0.0.1:8000' . $path;
    $ch = curl_init($url);
    $headers = ['Accept: application/json'];
    if ($token) $headers[] = 'Authorization: Bearer ' . $token;
    if ($body !== null) {
        $payload = is_string($body) ? $body : json_encode($body);
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    }
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $resp = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    $data = null;
    if ($resp) {
        $data = json_decode($resp, true) ?: $resp;
    }
    return ['code' => $info['http_code'] ?? 0, 'body' => $data, 'raw' => $resp];
}

echo "E2E: starting against http://127.0.0.1:8000\n";

// 1) login
$login = req('POST', '/api/v1/auth/login', null, ['email' => 'admin@example.com', 'password' => 'secret']);
if ($login['code'] !== 200 || empty($login['body']['token'])) {
    echo "LOGIN failed (code={$login['code']}): " . ($login['raw'] ?? json_encode($login['body'])) . PHP_EOL;
    exit(1);
}
$token = $login['body']['token'];
echo "LOGIN OK, token length=" . strlen($token) . "\n";

// 2) create purchase
$purchasePayload = ['fornecedor_id' => 1, 'produto_id' => 1, 'motorista_id' => 1, 'quantidade' => 5, 'valor_unitario' => 4.0, 'tipo_comissao' => 'percentual', 'valor_comissao' => 5.0, 'extra_por_saco' => 0];
$c = req('POST', '/api/v1/compras', $token, $purchasePayload);
if ($c['code'] !== 201) { echo "CREATE PURCHASE failed: "; var_export($c); exit(1); }
$compraId = $c['body']['id'];
echo "Purchase created id={$compraId}\n";

// 3) receive purchase
$r = req('POST', '/api/v1/compras/receive', $token, ['compra_id' => $compraId]);
if ($r['code'] !== 200) { echo "RECEIVE failed: "; var_export($r); exit(1); }
echo "Purchase received: produto_id={$r['body']['produto_id']}, novo_estoque={$r['body']['novo_estoque']}\n";

// 4) create cliente for sale
$cli = req('POST', '/api/v1/clientes', $token, ['nome' => 'E2E Cliente', 'telefone' => '9999', 'cidade' => 'Cidade']);
if ($cli['code'] !== 200 && $cli['code'] !== 201) { echo "CREATE CLIENT failed: "; var_export($cli); exit(1); }
$clienteId = $cli['body']['id'] ?? ($cli['body'][0]['id'] ?? null) ?? 1;
echo "Cliente id={$clienteId}\n";

// 5) create sale (quantidade 2)
$sale = req('POST', '/api/v1/vendas', $token, ['cliente_id' => $clienteId, 'produto_id' => 1, 'quantidade' => 2, 'valor_unitario' => 8.0]);
if ($sale['code'] !== 201) { echo "CREATE SALE failed: "; var_export($sale); exit(1); }
$vendaId = $sale['body']['id'];
echo "Venda created id={$vendaId}\n";

// 6) deliver sale
$d = req('POST', '/api/v1/vendas/deliver', $token, ['venda_id' => $vendaId]);
if ($d['code'] !== 200) { echo "DELIVER failed: "; var_export($d); exit(1); }
echo "Venda entregue, novo_estoque={$d['body']['novo_estoque']}\n";

echo "E2E finished OK\n";
exit(0);
