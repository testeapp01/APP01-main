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
$host = $env['DB_HOST'] ?? '127.0.0.1';
$port = $env['DB_PORT'] ?? '3306';
$db   = $env['DB_DATABASE'] ?? 'hortifrutnectar';
$user = $env['DB_USERNAME'] ?? 'root';
$pass = $env['DB_PASSWORD'] ?? '';

echo "Running migrations on {$db}...\n";
try {
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Exception $e) {
    echo "DB connect failed: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

$migration = __DIR__ . '/../database/migrations/001_create_tables.sql';
if (!file_exists($migration)) {
    echo "Migration file not found: {$migration}\n";
    exit(1);
}

$sql = file_get_contents($migration);
try {
    $pdo->exec($sql);
    echo "Migrations applied.\n";
} catch (Exception $e) {
    echo "Migration error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
