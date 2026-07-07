<?php

$apiBase = rtrim(getenv('E2E_API_BASE') ?: 'http://127.0.0.1:8000', '/');

function requestApi(string $method, string $path, ?string $token = null, ?array $body = null): array
{
    global $apiBase;

    $url = $apiBase . $path;
    $ch = curl_init($url);
    $headers = ['Accept: application/json'];

    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }

    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $resp = curl_exec($ch);
    $error = curl_error($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);

    return [
        'code' => (int)($info['http_code'] ?? 0),
        'raw' => $resp,
        'body' => is_string($resp) ? (json_decode($resp, true) ?: $resp) : null,
        'error' => $error,
    ];
}

function printResult(string $name, array $res): void
{
    $body = is_array($res['body']) ? json_encode($res['body'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : (string)($res['raw'] ?? '');
    echo sprintf("%s => code=%d body=%s\n", $name, $res['code'], $body);
}

echo "Validating add flows against {$apiBase}\n";

$login = requestApi('POST', '/api/v1/auth/login', null, [
    'email' => 'admin@example.com',
    'password' => 'secret',
]);

printResult('login', $login);
if (($login['code'] ?? 0) !== 200 || empty($login['body']['token'])) {
    echo "Login failed. Aborting.\n";
    exit(1);
}

$token = (string)$login['body']['token'];
$suffix = (string)time();

$flows = [
    'fornecedor_add' => ['POST', '/api/v1/fornecedores', [
        'razao_social' => 'E2E Fornecedor ' . $suffix,
        'telefone' => '11999999999',
        'cidade' => 'São Paulo',
        'status' => true,
    ]],
    'produto_add' => ['POST', '/api/v1/produtos', [
        'nome' => 'E2E Produto ' . $suffix,
        'tipo' => 'horti',
        'unidade' => 'saco',
        'estoque_atual' => 0,
        'custo_medio' => 0,
    ]],
    'motorista_add' => ['POST', '/api/v1/motoristas', [
        'nome' => 'E2E Motorista ' . $suffix,
        'telefone' => '11999999999',
        'TpCaminhao' => 1,
        'status' => true,
    ]],
    'cliente_add' => ['POST', '/api/v1/clientes', [
        'nome' => 'E2E Cliente ' . $suffix,
        'telefone' => '11999999999',
        'cidade' => 'São Paulo',
        'status' => true,
    ]],
    'compra_add' => ['POST', '/api/v1/compras', [
        'fornecedor_id' => 1,
        'motorista_id' => 1,
        'tipo_operacao' => 'revenda',
        'tipo_comissao' => 'percentual',
        'valor_comissao' => 5.0,
        'extra_por_saco' => 0,
        'items' => [
            [
                'produto_id' => 1,
                'quantidade' => 1,
                'valor_unitario' => 4.0,
            ],
        ],
    ]],
    'venda_add' => ['POST', '/api/v1/vendas', [
        'cliente_id' => 1,
        'produto_id' => 1,
        'quantidade' => 1,
        'valor_unitario' => 8.0,
    ]],
];

$failed = false;
foreach ($flows as $name => [$method, $path, $payload]) {
    $res = requestApi($method, $path, $token, $payload);
    printResult($name, $res);

    if (!in_array($res['code'], [200, 201], true)) {
        $failed = true;
    }
}

if ($failed) {
    echo "One or more add flows failed.\n";
    exit(1);
}

echo "All add flows are OK.\n";
exit(0);
