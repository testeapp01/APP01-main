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
        executeMigrationSql($pdo, $sanitized);
        echo "Applied: " . basename($migration) . "\n";
    } catch (Exception $e) {
        echo "Migration error in " . basename($migration) . ": " . $e->getMessage() . PHP_EOL;
        exit(1);
    }
}

echo "Migrations applied.\n";
