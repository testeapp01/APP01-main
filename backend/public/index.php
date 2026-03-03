<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Database\Connection;
use App\Logger\Logger;
use App\Observability\Metrics;
use App\Routing\Router;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\InputSanitizer;
use App\Middlewares\RateLimitMiddleware;
use App\Middlewares\SecureHeadersMiddleware;
use App\Middlewares\CorrelationIdMiddleware;
use App\Controllers\AuthController;
use App\Controllers\PurchaseController;
use App\Controllers\SalesController;
use App\Controllers\ReportsController;
use App\Controllers\ClientController;
use App\Controllers\MotoristaController;
use App\Controllers\ProductController;
use App\Controllers\FornecedorController;

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

Metrics::boot();

set_exception_handler(static function (\Throwable $e): void {
    Metrics::increment('http_errors_total');
    Metrics::increment('http_5xx_total');
    Logger::get()->error('uncaught_exception', [
        'exception' => $e::class,
        'message' => $e->getMessage(),
    ]);

    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
    }

    echo json_encode(['error' => 'Erro interno. Tente novamente.']);
});

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    Logger::get()->error('php_error', [
        'severity' => $severity,
        'message' => $message,
        'file' => $file,
        'line' => $line,
    ]);

    return false;
});

Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();

$pdo = Connection::getPdo();

header('Content-Type: application/json; charset=utf-8');

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOriginsRaw = getenv('CORS_ALLOWED_ORIGINS') ?: '';
$allowedOrigins = array_filter(array_map('trim', explode(',', $allowedOriginsRaw)));

if ($origin !== '') {
    if (empty($allowedOrigins) || in_array($origin, $allowedOrigins, true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Vary: Origin');
        header('Access-Control-Allow-Credentials: true');
    }
}

header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type, Accept, Origin, X-Requested-With, X-Correlation-ID');

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

SecureHeadersMiddleware::apply();
CorrelationIdMiddleware::apply();
RateLimitMiddleware::check(120, 60);

$raw = file_get_contents('php://input');
if ($raw) {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $GLOBALS['SANITIZED_INPUT'] = InputSanitizer::sanitizeArray($decoded);
    }
}

Metrics::increment('http_requests_total');

$router = new Router();

$publicRoute = static function () use ($uri, $method): bool {
    return ($uri === '/api/v1/auth/login' && $method === 'POST')
        || ($uri === '/api/v1/health' && $method === 'GET')
        || ($uri === '/api/v1/metrics' && $method === 'GET');
};

$ensureAuth = static function () use ($publicRoute): ?array {
    if (!$publicRoute()) {
        $authUser = AuthMiddleware::authenticate();
        $GLOBALS['AUTH_USER'] = $authUser;
        return $authUser;
    }

    return null;
};

$router->map('GET', '/api/v1/health', static function () use ($pdo): void {
    $started = microtime(true);
    $dbOk = true;
    $dbError = null;
    try {
        $pdo->query('SELECT 1');
    } catch (\Throwable $e) {
        $dbOk = false;
        $dbError = $e->getMessage();
    }

    $status = $dbOk ? 'ok' : 'degraded';
    http_response_code($dbOk ? 200 : 503);
    echo json_encode([
        'status' => $status,
        'correlation_id' => $GLOBALS['CORRELATION_ID'] ?? null,
        'latency_ms' => (int)((microtime(true) - $started) * 1000),
        'checks' => [
            'database' => [
                'ok' => $dbOk,
                'error' => $dbError,
            ],
        ],
    ]);
});

$router->map('GET', '/api/v1/metrics', static function (): void {
    echo json_encode(Metrics::snapshot());
});

$router->map('POST', '/api/v1/auth/login', static function () use ($pdo): void {
    (new AuthController($pdo))->login();
});

$router->map('GET', '/api/v1/auth/me', static function () use ($pdo, $ensureAuth): void {
    $authUser = $ensureAuth() ?? [];
    (new AuthController($pdo))->me((array)$authUser);
});

$router->map('POST', '/api/v1/compras', static function () use ($pdo, $ensureAuth): void {
    $ensureAuth();
    (new PurchaseController($pdo))->create();
});
$router->map('POST', '/api/v1/compras/receive', static function () use ($pdo, $ensureAuth): void {
    $ensureAuth();
    (new PurchaseController($pdo))->receive();
});
$router->map('GET', '/api/v1/compras', static function () use ($pdo, $ensureAuth): void {
    $ensureAuth();
    (new PurchaseController($pdo))->index();
});
$router->map('GET', '/api/v1/compras/cabecalhos/{id}', static function (array $params) use ($pdo, $ensureAuth): void {
    $ensureAuth();
    (new PurchaseController($pdo))->showHeader((int)($params['id'] ?? 0));
});
$router->map('GET', '/api/v1/compras/cabecalhos/{id}/pdf', static function (array $params) use ($pdo, $ensureAuth): void {
    $ensureAuth();
    (new PurchaseController($pdo))->printHeaderPdf((int)($params['id'] ?? 0));
});
$router->map(['PUT', 'PATCH'], '/api/v1/compras/cabecalhos/{id}', static function (array $params) use ($pdo, $ensureAuth): void {
    $ensureAuth();
    (new PurchaseController($pdo))->updateHeader((int)($params['id'] ?? 0));
});
$router->map('DELETE', '/api/v1/compras/cabecalhos/{id}', static function (array $params) use ($pdo, $ensureAuth): void {
    $ensureAuth();
    (new PurchaseController($pdo))->deleteHeader((int)($params['id'] ?? 0));
});
$router->map('POST', '/api/v1/compras/cabecalhos/{id}/confirmar-entrega', static function (array $params) use ($pdo, $ensureAuth): void {
    $ensureAuth();
    (new PurchaseController($pdo))->confirmHeaderDelivery((int)($params['id'] ?? 0));
});

$router->map('POST', '/api/v1/vendas', static function () use ($pdo, $ensureAuth): void {
    $ensureAuth();
    (new SalesController($pdo))->create();
});
$router->map('POST', '/api/v1/vendas/deliver', static function () use ($pdo, $ensureAuth): void {
    $ensureAuth();
    (new SalesController($pdo))->deliver();
});
$router->map('GET', '/api/v1/vendas', static function () use ($pdo, $ensureAuth): void {
    $ensureAuth();
    (new SalesController($pdo))->index();
});
$router->map('GET', '/api/v1/vendas/cabecalhos/{id}', static function (array $params) use ($pdo, $ensureAuth): void {
    $ensureAuth();
    (new SalesController($pdo))->showHeader((int)($params['id'] ?? 0));
});
$router->map('GET', '/api/v1/vendas/cabecalhos/{id}/pdf', static function (array $params) use ($pdo, $ensureAuth): void {
    $ensureAuth();
    (new SalesController($pdo))->printHeaderPdf((int)($params['id'] ?? 0));
});
$router->map('DELETE', '/api/v1/vendas/cabecalhos/{id}', static function (array $params) use ($pdo, $ensureAuth): void {
    $ensureAuth();
    (new SalesController($pdo))->deleteHeader((int)($params['id'] ?? 0));
});

$router->map('GET', '/api/v1/clientes', static function () use ($pdo, $ensureAuth): void {
    $ensureAuth();
    (new ClientController($pdo))->index();
});
$router->map('POST', '/api/v1/clientes', static function () use ($pdo, $ensureAuth): void {
    $ensureAuth();
    (new ClientController($pdo))->create();
});
$router->map('DELETE', '/api/v1/clientes/{id}', static function (array $params) use ($pdo, $ensureAuth): void {
    $ensureAuth();
    (new ClientController($pdo))->delete((int)($params['id'] ?? 0));
});

$router->map('GET', '/api/v1/motoristas', static function () use ($pdo, $ensureAuth): void {
    $ensureAuth();
    (new MotoristaController($pdo))->index();
});
$router->map('GET', '/api/v1/tipos-caminhao', static function () use ($pdo, $ensureAuth): void {
    $ensureAuth();
    (new MotoristaController($pdo))->listTiposCaminhao();
});
$router->map('POST', '/api/v1/motoristas', static function () use ($pdo, $ensureAuth): void {
    $ensureAuth();
    (new MotoristaController($pdo))->create();
});
$router->map(['PUT', 'PATCH'], '/api/v1/motoristas/{id}', static function (array $params) use ($pdo, $ensureAuth): void {
    $ensureAuth();
    (new MotoristaController($pdo))->update((int)($params['id'] ?? 0));
});
$router->map('DELETE', '/api/v1/motoristas/{id}', static function (array $params) use ($pdo, $ensureAuth): void {
    $ensureAuth();
    (new MotoristaController($pdo))->delete((int)($params['id'] ?? 0));
});

$router->map('GET', '/api/v1/fornecedores', static function () use ($pdo, $ensureAuth): void {
    $ensureAuth();
    (new FornecedorController($pdo))->index();
});
$router->map('POST', '/api/v1/fornecedores', static function () use ($pdo, $ensureAuth): void {
    $ensureAuth();
    (new FornecedorController($pdo))->create();
});
$router->map('DELETE', '/api/v1/fornecedores/{id}', static function (array $params) use ($pdo, $ensureAuth): void {
    $ensureAuth();
    (new FornecedorController($pdo))->delete((int)($params['id'] ?? 0));
});

$router->map('GET', '/api/v1/produtos', static function () use ($pdo, $ensureAuth): void {
    $ensureAuth();
    (new ProductController($pdo))->index();
});
$router->map('POST', '/api/v1/produtos', static function () use ($pdo, $ensureAuth): void {
    $ensureAuth();
    (new ProductController($pdo))->create();
});
$router->map(['PUT', 'PATCH'], '/api/v1/produtos/{id}', static function (array $params) use ($pdo, $ensureAuth): void {
    $ensureAuth();
    (new ProductController($pdo))->update((int)($params['id'] ?? 0));
});
$router->map('DELETE', '/api/v1/produtos/{id}', static function (array $params) use ($pdo, $ensureAuth): void {
    $ensureAuth();
    (new ProductController($pdo))->delete((int)($params['id'] ?? 0));
});

$router->map('GET', '/api/v1/relatorios', static function () use ($pdo, $ensureAuth): void {
    $ensureAuth();
    (new ReportsController($pdo))->index();
});
$router->map('GET', '/api/v1/relatorios/dashboard', static function () use ($pdo, $ensureAuth): void {
    $ensureAuth();
    (new ReportsController($pdo))->dashboard();
});
$router->map('GET', '/api/v1/relatorios/compras', static function () use ($pdo, $ensureAuth): void {
    $ensureAuth();
    (new ReportsController($pdo))->strategicPurchases();
});
$router->map('GET', '/api/v1/relatorios/compras/export', static function () use ($pdo, $ensureAuth): void {
    $ensureAuth();
    (new ReportsController($pdo))->exportStrategicPurchases();
});

if (!$router->dispatch($method, $uri)) {
    Metrics::increment('http_errors_total');
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
}
