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
$dbName = envFirst(['DB_NAME', 'DB_DATABASE', 'MYSQL_DATABASE'], 'hortifrutnectar');
$appUser = envFirst(['DB_USER', 'DB_USERNAME', 'MYSQL_USER'], 'admin');
$appPass = envFirst(['DB_PASS', 'DB_PASSWORD', 'MYSQL_PASSWORD'], '');

$adminUser = envFirst(['DB_ADMIN_USER', 'MYSQL_ROOT_USER'], 'root');
$adminPass = envFirst(['DB_ADMIN_PASSWORD', 'MYSQL_ROOT_PASSWORD'], '');

if ($adminPass === '') {
    fwrite(STDOUT, "[grants] Skipping grants because DB_ADMIN_PASSWORD/MYSQL_ROOT_PASSWORD is not configured.\n");
    exit(0);
}

if ($appPass === '') {
    fwrite(STDERR, "[grants] App password is empty. Configure DB_PASS/DB_PASSWORD/MYSQL_PASSWORD first.\n");
    exit(1);
}

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};charset=utf8mb4",
        $adminUser,
        $adminPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $quotedDb = '`' . str_replace('`', '``', $dbName) . '`';
    $quotedUser = str_replace("'", "''", $appUser);
    $quotedPass = str_replace("'", "''", $appPass);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS {$quotedDb} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("CREATE USER IF NOT EXISTS '{$quotedUser}'@'%' IDENTIFIED BY '{$quotedPass}'");
    $pdo->exec("ALTER USER '{$quotedUser}'@'%' IDENTIFIED BY '{$quotedPass}'");
    $pdo->exec("GRANT ALL PRIVILEGES ON {$quotedDb}.* TO '{$quotedUser}'@'%'");
    $pdo->exec('FLUSH PRIVILEGES');

    fwrite(STDOUT, "[grants] Granted privileges for '{$appUser}' on {$dbName}.\n");
} catch (Throwable $e) {
    fwrite(STDERR, '[grants] failed: ' . $e->getMessage() . "\n");
    exit(1);
}
