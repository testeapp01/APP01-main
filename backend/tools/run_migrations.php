<?php
// Run SQL migrations from the migrations folder using DB settings from .env
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

if ($isContainer && (
    getenv('DB_HOST') === false ||
    getenv('DB_PORT') === false ||
    (getenv('DB_NAME') === false && getenv('DB_DATABASE') === false) ||
    (getenv('DB_USER') === false && getenv('DB_USERNAME') === false) ||
    (getenv('DB_PASS') === false && getenv('DB_PASSWORD') === false)
)) {
    echo "Container mode detected. Ensure DB_HOST, DB_PORT, DB_NAME/DB_DATABASE, DB_USER/DB_USERNAME and DB_PASS/DB_PASSWORD are set in environment.\n";
}

echo "Running migrations on {$db}...\n";
try {
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Exception $e) {
    echo "DB connect failed: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

$migrationFiles = glob(__DIR__ . '/../database/migrations/*.sql');
sort($migrationFiles);

if (!$migrationFiles) {
    echo "No migration files found in backend/database/migrations\n";
    exit(1);
}

foreach ($migrationFiles as $migration) {
    $sql = file_get_contents($migration);
    if ($sql === false) {
        echo "Could not read migration file: {$migration}\n";
        exit(1);
    }

    $sanitized = preg_replace('/^\s*CREATE\s+DATABASE\b.*?;\s*$/im', '', $sql);
    $sanitized = preg_replace('/^\s*USE\s+`?[^`\s;]+`?\s*;\s*$/im', '', $sanitized);

    try {
        $pdo->exec($sanitized);
        echo "Applied: " . basename($migration) . "\n";
    } catch (Exception $e) {
        echo "Migration error in " . basename($migration) . ": " . $e->getMessage() . PHP_EOL;
        exit(1);
    }
}

echo "Migrations applied.\n";
