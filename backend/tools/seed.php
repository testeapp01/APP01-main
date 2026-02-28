<?php
// Simple seeder for local development — reads backend/.env for DB credentials
function parseEnv($path)
{
    $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $data = [];
    if (!$lines) return $data;
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        [$k, $v] = explode('=', $line, 2);
        $data[trim($k)] = trim($v);
    }
    return $data;
}

$env = parseEnv(__DIR__ . '/../.env');
$isContainer = file_exists('/.dockerenv') || getenv('COOLIFY_RESOURCE_UUID') || getenv('KUBERNETES_SERVICE_HOST');

function resolveDbVar(array $envNames, array $envFile, array $envFileNames, string $default, bool $isContainer): string
{
    foreach ($envNames as $name) {
        $value = getenv($name);
        if ($value !== false && $value !== '') {
            return $value;
        }
    }

    if (!$isContainer) {
        foreach ($envFileNames as $name) {
            if (!empty($envFile[$name])) {
                return $envFile[$name];
            }
        }
    }

    return $default;
}

$host = resolveDbVar(['DB_HOST'], $env, ['DB_HOST'], '127.0.0.1', $isContainer);
$port = resolveDbVar(['DB_PORT'], $env, ['DB_PORT'], '3306', $isContainer);
$db   = resolveDbVar(['DB_DATABASE', 'DB_NAME'], $env, ['DB_DATABASE', 'DB_NAME'], 'hortifrutnectar', $isContainer);
$user = resolveDbVar(['DB_USERNAME', 'DB_USER'], $env, ['DB_USERNAME', 'DB_USER'], 'root', $isContainer);
$pass = resolveDbVar(['DB_PASSWORD', 'DB_PASS'], $env, ['DB_PASSWORD', 'DB_PASS'], '', $isContainer);

if ($isContainer && ($host === '127.0.0.1' || $user === 'root' || $db === 'hortifrutnectar')) {
    echo "Container mode detected. Ensure DB_HOST, DB_PORT, DB_NAME/DB_DATABASE, DB_USER/DB_USERNAME and DB_PASS/DB_PASSWORD are set in environment.\n";
}

echo "Using DB: $user@{$host}:{$port}/{$db}\n";

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Exception $e) {
    echo "Could not connect to DB: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// Helper: insert if not exists
function insertIfNotExists(PDO $pdo, $table, $checkColumn, $checkValue, $data)
{
    $stmt = $pdo->prepare("SELECT id FROM {$table} WHERE {$checkColumn} = :v LIMIT 1");
    $stmt->execute([':v' => $checkValue]);
    if ($stmt->fetch()) {
        echo "Skipping existing {$table} ({$checkValue})\n";
        return;
    }
    $cols = array_keys($data);
    $place = array_map(fn($c) => ':' . $c, $cols);
    $sql = "INSERT INTO {$table} (" . implode(',', $cols) . ") VALUES (" . implode(',', $place) . ")";
    $stmt = $pdo->prepare($sql);
    foreach ($data as $k => $v) $stmt->bindValue(':' . $k, $v);
    $stmt->execute();
    echo "Inserted into {$table}: {$checkValue}\n";
}

// Seed users
$hash = password_hash('secret', PASSWORD_BCRYPT);
insertIfNotExists($pdo, 'users', 'email', 'admin@example.com', [
    'name' => 'Admin',
    'email' => 'admin@example.com',
    'password' => $hash,
    'role' => 'admin',
    'created_at' => date('Y-m-d H:i:s'),
]);

// Seed fornecedor (columns from migration: razao_social, cnpj, telefone, cidade)
insertIfNotExists($pdo, 'fornecedores', 'razao_social', 'Fornecedor Exemplo', [
    'razao_social' => 'Fornecedor Exemplo',
    'cnpj' => '00.000.000/0001-00',
    'telefone' => '0000-0000',
    'cidade' => 'Cidade Exemplo',
]);

// Seed motorista (migration columns: nome, cpf, telefone, comissao_padrao_tipo, comissao_padrao_valor, extra_por_saco_padrao)
insertIfNotExists($pdo, 'motoristas', 'nome', 'Motorista Exemplo', [
    'nome' => 'Motorista Exemplo',
    'cpf' => '000.000.000-00',
    'telefone' => '0000-0000',
    'comissao_padrao_tipo' => 'percentual',
    'comissao_padrao_valor' => 5.00,
    'extra_por_saco_padrao' => 0.00,
]);

// Seed produto (migration columns: nome, tipo, unidade, estoque_atual, custo_medio)
insertIfNotExists($pdo, 'produtos', 'nome', 'Banana', [
    'nome' => 'Banana',
    'tipo' => 'Fruta',
    'unidade' => 'kg',
    'estoque_atual' => 100,
    'custo_medio' => 2.50,
]);

echo "Seeding complete. Admin credentials: admin@example.com / secret\n";
