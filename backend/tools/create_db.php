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
$host = getenv('DB_HOST') ?: ($env['DB_HOST'] ?? '127.0.0.1');
$port = getenv('DB_PORT') ?: ($env['DB_PORT'] ?? '3306');
$db   = getenv('DB_DATABASE') ?: (getenv('DB_NAME') ?: ($env['DB_DATABASE'] ?? ($env['DB_NAME'] ?? 'hortifrutnectar')));
$user = getenv('DB_USERNAME') ?: (getenv('DB_USER') ?: ($env['DB_USERNAME'] ?? ($env['DB_USER'] ?? 'root')));
$pass = getenv('DB_PASSWORD') ?: (getenv('DB_PASS') ?: ($env['DB_PASSWORD'] ?? ($env['DB_PASS'] ?? '')));

echo "Connecting to {$host}:{$port} as {$user} to ensure database '{$db}'...\n";
try {
    $pdo = new PDO("mysql:host={$host};port={$port}", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database '{$db}' created or already exists.\n";
} catch (Exception $e) {
    echo "Failed to create database: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
