<?php

declare(strict_types=1);

function envFirst(array $names, string $default = ''): string
{
    foreach ($names as $name) {
        $value = getenv($name);
        if ($value !== false && trim((string)$value) !== '') {
            return (string)$value;
        }
    }

    return $default;
}

$host = envFirst(['DB_HOST', 'MYSQL_HOST'], 'db');
$port = envFirst(['DB_PORT', 'MYSQL_PORT'], '3306');
$db = envFirst(['DB_NAME', 'DB_DATABASE', 'MYSQL_DATABASE'], 'hortifrutnectar');
$user = envFirst(['DB_USER', 'DB_USERNAME', 'MYSQL_USER'], 'admin');
$pass = envFirst(['DB_PASS', 'DB_PASSWORD', 'MYSQL_PASSWORD'], '');

echo "[verify] target env -> {$host}:{$port} / db={$db} / user={$user}\n";

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $runtimeHost = (string)$pdo->query('SELECT @@hostname')->fetchColumn();
    $runtimePort = (string)$pdo->query('SELECT @@port')->fetchColumn();
    $runtimeDb = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
    $currentUser = (string)$pdo->query('SELECT CURRENT_USER()')->fetchColumn();

    echo "[verify] connected runtime -> host={$runtimeHost} port={$runtimePort} db={$runtimeDb} current_user={$currentUser}\n";

    $stmt = $pdo->query('SHOW TABLES');
    $tables = $stmt->fetchAll(PDO::FETCH_NUM);

    echo '[verify] tables (' . count($tables) . "):\n";
    foreach ($tables as $row) {
        echo ' - ' . (string)$row[0] . "\n";
    }
} catch (Throwable $e) {
    fwrite(STDERR, '[verify] failed: ' . $e->getMessage() . "\n");
    exit(1);
}
