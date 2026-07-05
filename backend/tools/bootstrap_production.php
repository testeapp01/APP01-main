<?php

declare(strict_types=1);

function normalizeDbEnvAliases(): void
{
    $map = [
        'DB_HOST' => ['DB_HOST', 'MYSQL_HOST'],
        'DB_PORT' => ['DB_PORT', 'MYSQL_PORT'],
        'DB_NAME' => ['DB_NAME', 'DB_DATABASE', 'MYSQL_DATABASE'],
        'DB_USER' => ['DB_USER', 'DB_USERNAME', 'MYSQL_USER'],
        'DB_PASS' => ['DB_PASS', 'DB_PASSWORD', 'MYSQL_PASSWORD'],
    ];

    foreach ($map as $target => $sources) {
        $current = getenv($target);
        if ($current !== false && $current !== '') {
            continue;
        }

        foreach ($sources as $source) {
            $value = getenv($source);
            if ($value !== false && $value !== '') {
                putenv($target . '=' . $value);
                $_ENV[$target] = $value;
                $_SERVER[$target] = $value;
                break;
            }
        }
    }
}

function runStep(string $label, string $command): void
{
    echo "[bootstrap] {$label}\n";
    passthru($command, $exitCode);
    if ($exitCode !== 0) {
        fwrite(STDERR, "[bootstrap] step failed: {$label} (exit {$exitCode})\n");
        exit($exitCode);
    }
}

function runStepWithRetry(string $label, string $command, int $attempts = 10, int $delaySeconds = 3): void
{
    $attempt = 1;
    while ($attempt <= $attempts) {
        echo "[bootstrap] {$label} (attempt {$attempt}/{$attempts})\n";
        passthru($command, $exitCode);
        if ($exitCode === 0) {
            return;
        }

        if ($attempt >= $attempts) {
            fwrite(STDERR, "[bootstrap] step failed after {$attempts} attempts: {$label} (exit {$exitCode})\n");
            exit($exitCode);
        }

        echo "[bootstrap] Waiting {$delaySeconds}s before retrying {$label}\n";
        sleep($delaySeconds);
        $attempt++;
    }
}

normalizeDbEnvAliases();

$php = escapeshellarg(PHP_BINARY);
$basePath = dirname(__DIR__);

runStepWithRetry('Ensuring database exists', $php . ' ' . escapeshellarg($basePath . '/tools/create_db.php'));
runStep('Applying pending migrations', $php . ' ' . escapeshellarg($basePath . '/tools/run_migrations.php'));

$adminPassword = getenv('ADMIN_PASSWORD');
if ($adminPassword !== false && trim($adminPassword) !== '') {
    $adminEmail = getenv('ADMIN_EMAIL') ?: 'admin@safrion.local';
    $adminName = getenv('ADMIN_NAME') ?: 'admin';

    putenv('ADMIN_PASSWORD=' . $adminPassword);
    runStep(
        'Ensuring production admin user',
        $php . ' ' . escapeshellarg($basePath . '/tools/ensure_admin_user.php')
        . ' --email=' . escapeshellarg($adminEmail)
        . ' --name=' . escapeshellarg($adminName)
    );
} else {
    echo "[bootstrap] Skipping admin bootstrap because ADMIN_PASSWORD is not configured.\n";
}

echo "[bootstrap] Production bootstrap completed successfully.\n";
