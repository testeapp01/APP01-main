<?php
// Create the application database if missing. Uses .env values if present.
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

echo "Connecting to {$host}:{$port} as {$user} to ensure database '{$db}'...\n";
try {
    $pdo = new PDO("mysql:host={$host};port={$port}", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database '{$db}' created or already exists.\n";
} catch (Exception $e) {
    echo "Failed to create database: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
