<?php

declare(strict_types=1);

/**
 * Production reset utility (destructive):
 * - drops and recreates the configured database
 * - reapplies all SQL migrations
 * - creates a single admin user
 */

function parseEnv(string $path): array
{
    $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $data = [];

    if (!$lines) {
        return $data;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || strpos($line, '=') === false) {
            continue;
        }

        [$k, $v] = explode('=', $line, 2);
        $data[trim($k)] = trim($v);
    }

    return $data;
}

function resolveDbVar(array $envNames, array $envFile, array $envFileNames, string $default): string
{
    foreach ($envNames as $name) {
        $value = getenv($name);
        if ($value !== false && $value !== '') {
            return $value;
        }
    }

    foreach ($envFileNames as $name) {
        if (!empty($envFile[$name])) {
            return (string)$envFile[$name];
        }
    }

    return $default;
}

function splitSqlStatements(string $sql): array
{
    $parts = preg_split('/;\s*(?:\r?\n|$)/', $sql);
    if (!is_array($parts)) {
        return [];
    }

    $statements = [];
    foreach ($parts as $part) {
        $stmt = trim($part);
        if ($stmt !== '') {
            $statements[] = $stmt;
        }
    }

    return $statements;
}

function expandAddColumnIfNotExists(string $sql): string
{
    return preg_replace_callback(
        '/ALTER\s+TABLE\s+`?([a-zA-Z0-9_]+)`?\s+(.*?);/is',
        function ($matches) {
            $table = $matches[1];
            $body = trim($matches[2]);

            if (stripos($body, 'ADD COLUMN IF NOT EXISTS') === false) {
                return $matches[0];
            }

            $clauses = preg_split('/,\s*(?=ADD\s+COLUMN\s+IF\s+NOT\s+EXISTS\b)/i', $body);
            if (!is_array($clauses) || count($clauses) === 0) {
                return $matches[0];
            }

            $expandedStatements = [];
            foreach ($clauses as $clause) {
                $normalizedClause = preg_replace(
                    '/^\s*ADD\s+COLUMN\s+IF\s+NOT\s+EXISTS\s+/i',
                    'ADD COLUMN ',
                    trim($clause)
                );

                if (!is_string($normalizedClause) || trim($normalizedClause) === '') {
                    continue;
                }

                $expandedStatements[] = "ALTER TABLE `{$table}` {$normalizedClause};";
            }

            return implode("\n", $expandedStatements);
        },
        $sql
    ) ?? $sql;
}

function isIgnorableSchemaError(Exception $e, string $statement): bool
{
    if (!($e instanceof PDOException)) {
        return false;
    }

    $errorInfoCode = isset($e->errorInfo[1]) ? (int)$e->errorInfo[1] : null;
    $message = $e->getMessage();

    $isDuplicateColumn = $errorInfoCode === 1060
        || stripos($message, 'Duplicate column name') !== false
        || stripos($message, '1060') !== false;

    $isDuplicateKey = $errorInfoCode === 1061
        || stripos($message, 'Duplicate key name') !== false
        || stripos($message, '1061') !== false;

    $isMissingColumnOnDrop = $errorInfoCode === 1091
        || stripos($message, "Can't DROP") !== false
        || stripos($message, 'check that column/key exists') !== false
        || stripos($message, '1091') !== false;

    if ($isDuplicateColumn) {
        return (bool) preg_match('/\bALTER\s+TABLE\b[\s\S]*\bADD\s+COLUMN\b/i', $statement);
    }

    if ($isDuplicateKey) {
        return (bool) preg_match('/\b(ALTER\s+TABLE|CREATE\s+(UNIQUE\s+)?INDEX)\b[\s\S]*\b(UNIQUE|KEY|INDEX)\b/i', $statement);
    }

    if ($isMissingColumnOnDrop) {
        return (bool) preg_match('/\bALTER\s+TABLE\b[\s\S]*\bDROP\s+COLUMN\b/i', $statement);
    }

    return false;
}

function executeMigrationSql(PDO $pdo, string $sql): void
{
    $compatSql = stripos($sql, 'ADD COLUMN IF NOT EXISTS') === false
        ? $sql
        : expandAddColumnIfNotExists($sql);

    $statements = splitSqlStatements($compatSql);
    foreach ($statements as $statement) {
        try {
            $pdo->exec($statement);
        } catch (Exception $e) {
            if (isIgnorableSchemaError($e, $statement)) {
                continue;
            }

            throw $e;
        }
    }
}

function parseOption(array $argv, string $name, ?string $default = null): ?string
{
    foreach ($argv as $arg) {
        if (str_starts_with($arg, "--{$name}=")) {
            return substr($arg, strlen("--{$name}="));
        }
    }

    return $default;
}

$email = parseOption($argv, 'email', 'vallejosefrancisco@gmail.com');
$password = parseOption($argv, 'password', 'CHICO123');
$name = parseOption($argv, 'name', 'Administrador');

if ($email === null || $password === null || $name === null || trim($email) === '' || trim($password) === '' || trim($name) === '') {
    fwrite(STDERR, "Usage: php tools/reset_production.php [--email=...] [--password=...] [--name=...]\n");
    exit(1);
}

$env = parseEnv(__DIR__ . '/../.env');
$host = resolveDbVar(['DB_HOST'], $env, ['DB_HOST'], '127.0.0.1');
$port = resolveDbVar(['DB_PORT'], $env, ['DB_PORT'], '3306');
$db = resolveDbVar(['DB_DATABASE', 'DB_NAME'], $env, ['DB_DATABASE', 'DB_NAME'], 'hortifrut');
$user = resolveDbVar(['DB_USERNAME', 'DB_USER'], $env, ['DB_USERNAME', 'DB_USER'], 'root');
$pass = resolveDbVar(['DB_PASSWORD', 'DB_PASS'], $env, ['DB_PASSWORD', 'DB_PASS'], '');

echo "Resetting database '{$db}' on {$host}:{$port}...\n";

try {
    $serverPdo = new PDO(
        "mysql:host={$host};port={$port};charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $serverPdo->exec("DROP DATABASE IF EXISTS `{$db}`");
    $serverPdo->exec("CREATE DATABASE `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $migrationFiles = glob(__DIR__ . '/../database/migrations/*.sql');
    sort($migrationFiles);

    if (!$migrationFiles) {
        throw new RuntimeException('No migration files found in backend/database/migrations');
    }

    foreach ($migrationFiles as $migration) {
        $sql = file_get_contents($migration);
        if ($sql === false) {
            throw new RuntimeException('Could not read migration file: ' . $migration);
        }

        $sanitized = preg_replace('/^\s*CREATE\s+DATABASE\b.*?;\s*$/im', '', $sql);
        $sanitized = preg_replace('/^\s*USE\s+`?[^`\s;]+`?\s*;\s*$/im', '', (string)$sanitized);
        executeMigrationSql($pdo, (string)$sanitized);
        echo 'Applied: ' . basename($migration) . "\n";
    }

    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    if ($passwordHash === false) {
        throw new RuntimeException('Could not hash admin password.');
    }

    $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role, created_at) VALUES (:name, :email, :password, :role, NOW())');
    $stmt->execute([
        'name' => $name,
        'email' => $email,
        'password' => $passwordHash,
        'role' => 'admin',
    ]);

    echo "\nReset finished successfully.\n";
    echo "Admin: {$email}\n";
    echo "Password: {$password}\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Reset failed: ' . $e->getMessage() . "\n");
    exit(1);
}
