<?php

declare(strict_types=1);

/**
 * Ensures a specific admin user exists and its password matches the desired value.
 * This script is non-destructive and can be run safely in production.
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

function parseOption(array $argv, string $name, ?string $default = null): ?string
{
    foreach ($argv as $arg) {
        if (str_starts_with($arg, "--{$name}=")) {
            return substr($arg, strlen("--{$name}="));
        }
    }

    return $default;
}

$email = parseOption($argv, 'email', 'admin@safrion.local');
$password = parseOption($argv, 'password', 'guivalle');
$name = parseOption($argv, 'name', 'admin');

if ($email === null || $password === null || $name === null || trim($email) === '' || trim($password) === '' || trim($name) === '') {
    fwrite(STDERR, "Usage: php tools/ensure_admin_user.php [--email=...] [--password=...] [--name=...]\n");
    exit(1);
}

$env = parseEnv(__DIR__ . '/../.env');
$host = resolveDbVar(['DB_HOST'], $env, ['DB_HOST'], '127.0.0.1');
$port = resolveDbVar(['DB_PORT'], $env, ['DB_PORT'], '3306');
$db = resolveDbVar(['DB_DATABASE', 'DB_NAME'], $env, ['DB_DATABASE', 'DB_NAME'], 'hortifrut');
$user = resolveDbVar(['DB_USERNAME', 'DB_USER'], $env, ['DB_USERNAME', 'DB_USER'], 'root');
$pass = resolveDbVar(['DB_PASSWORD', 'DB_PASS'], $env, ['DB_PASSWORD', 'DB_PASS'], '');

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $hash = password_hash($password, PASSWORD_BCRYPT);
    if ($hash === false) {
        throw new RuntimeException('Could not hash password.');
    }

    $find = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $find->execute(['email' => trim($email)]);
    $existing = $find->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $update = $pdo->prepare('UPDATE users SET name = :name, password = :password, role = :role WHERE id = :id');
        $update->execute([
            'name' => trim($name),
            'password' => $hash,
            'role' => 'admin',
            'id' => (int)$existing['id'],
        ]);
        echo "Updated existing admin user: {$email}\n";
    } else {
        $insert = $pdo->prepare('INSERT INTO users (name, email, password, role, created_at) VALUES (:name, :email, :password, :role, NOW())');
        $insert->execute([
            'name' => trim($name),
            'email' => trim($email),
            'password' => $hash,
            'role' => 'admin',
        ]);
        echo "Created admin user: {$email}\n";
    }

    echo "Credentials ready: {$email} / {$password}\n";
    if (str_contains($email, '@')) {
        $loginAlias = explode('@', $email, 2)[0];
        echo "Login alias available: {$loginAlias}\n";
    }
} catch (Throwable $e) {
    fwrite(STDERR, 'Failed to ensure admin user: ' . $e->getMessage() . "\n");
    exit(1);
}
