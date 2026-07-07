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
use App\Middlewares\AuthorizationMiddleware;
use App\Controllers\AuthController;
use App\Controllers\PurchaseController;
use App\Controllers\SalesController;
use App\Controllers\ReportsController;
use App\Controllers\ClientController;
use App\Controllers\MotoristaController;
use App\Controllers\ProductController;
use App\Controllers\FornecedorController;
use App\Controllers\UserController;
use App\Controllers\EstoqueController;
use App\Controllers\QuebrasController;
use App\Controllers\LoteController;
use App\Controllers\TabelaPrecoController;
use App\Bootstrap\Container;

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

$container = new Container($pdo);

header('Content-Type: application/json; charset=utf-8');

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOriginsRaw = getenv('CORS_ALLOWED_ORIGINS') ?: '';
$allowedOrigins = array_values(array_filter(array_map('trim', explode(',', $allowedOriginsRaw))));

if ($origin !== '') {
    if (!empty($allowedOrigins) && in_array($origin, $allowedOrigins, true)) {
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
        || ($uri === '/api/v1/auth/logout' && $method === 'POST');
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
    $opsToken = getenv('OPS_TOKEN') ?: '';
    $provided  = $_SERVER['HTTP_X_OPS_TOKEN'] ?? ($_GET['ops_token'] ?? '');
    if ($opsToken === '' || !hash_equals($opsToken, (string) $provided)) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        return;
    }

    $started = microtime(true);
    $dbOk    = true;
    try {
        $pdo->query('SELECT 1');
    } catch (\Throwable) {
        $dbOk = false;
    }

    $status = $dbOk ? 'ok' : 'degraded';
    http_response_code($dbOk ? 200 : 503);
    echo json_encode([
        'status'         => $status,
        'correlation_id' => $GLOBALS['CORRELATION_ID'] ?? null,
        'latency_ms'     => (int) ((microtime(true) - $started) * 1000),
        'checks'         => ['database' => ['ok' => $dbOk]],
    ]);
});

$router->map('GET', '/api/v1/metrics', static function (): void {
    $opsToken = getenv('OPS_TOKEN') ?: '';
    $provided  = $_SERVER['HTTP_X_OPS_TOKEN'] ?? ($_GET['ops_token'] ?? '');
    if ($opsToken === '' || !hash_equals($opsToken, (string) $provided)) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        return;
    }
    echo json_encode(Metrics::snapshot());
});

$router->map('POST', '/api/v1/auth/login', static function () use ($pdo): void {
    (new AuthController($pdo))->login();
});

$router->map('POST', '/api/v1/auth/logout', static function () use ($pdo): void {
    (new AuthController($pdo))->logout();
});

$router->map('GET', '/api/v1/auth/me', static function () use ($pdo, $ensureAuth): void {
    $authUser = $ensureAuth() ?? [];
    (new AuthController($pdo))->me((array)$authUser);
});

$router->map('POST', '/api/v1/compras', static function () use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    AuthorizationMiddleware::requireRole(...AuthorizationMiddleware::SENIOR);
    $container->make(PurchaseController::class)->create();
});
$router->map('POST', '/api/v1/compras/receive', static function () use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    AuthorizationMiddleware::requireRole(...AuthorizationMiddleware::SENIOR);
    $container->make(PurchaseController::class)->receive();
});
$router->map('GET', '/api/v1/compras', static function () use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    $container->make(PurchaseController::class)->index();
});
$router->map('GET', '/api/v1/compras/cabecalhos/{id}', static function (array $params) use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    $container->make(PurchaseController::class)->showHeader((int)($params['id'] ?? 0));
});
$router->map('GET', '/api/v1/compras/cabecalhos/{id}/pdf', static function (array $params) use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    $container->make(PurchaseController::class)->printHeaderPdf((int)($params['id'] ?? 0));
});
$router->map(['PUT', 'PATCH'], '/api/v1/compras/cabecalhos/{id}', static function (array $params) use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    AuthorizationMiddleware::requireRole(...AuthorizationMiddleware::SENIOR);
    $container->make(PurchaseController::class)->updateHeader((int)($params['id'] ?? 0));
});
$router->map('DELETE', '/api/v1/compras/cabecalhos/{id}', static function (array $params) use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    AuthorizationMiddleware::requireRole(...AuthorizationMiddleware::ADMIN_ONLY);
    $container->make(PurchaseController::class)->deleteHeader((int)($params['id'] ?? 0));
});
$router->map('POST', '/api/v1/compras/cabecalhos/{id}/confirmar-entrega', static function (array $params) use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    $container->make(PurchaseController::class)->confirmHeaderDelivery((int)($params['id'] ?? 0));
});

$router->map('POST', '/api/v1/vendas', static function () use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    $container->make(SalesController::class)->create();
});
$router->map('POST', '/api/v1/vendas/deliver', static function () use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    $container->make(SalesController::class)->deliver();
});
$router->map('GET', '/api/v1/vendas', static function () use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    $container->make(SalesController::class)->index();
});
$router->map('GET', '/api/v1/vendas/cabecalhos/{id}', static function (array $params) use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    $container->make(SalesController::class)->showHeader((int)($params['id'] ?? 0));
});
$router->map('GET', '/api/v1/vendas/cabecalhos/{id}/pdf', static function (array $params) use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    $container->make(SalesController::class)->printHeaderPdf((int)($params['id'] ?? 0));
});
$router->map('DELETE', '/api/v1/vendas/cabecalhos/{id}', static function (array $params) use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    AuthorizationMiddleware::requireRole(...AuthorizationMiddleware::ADMIN_ONLY);
    $container->make(SalesController::class)->deleteHeader((int)($params['id'] ?? 0));
});

$router->map('GET', '/api/v1/clientes', static function () use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    $container->make(ClientController::class)->index();
});
$router->map('POST', '/api/v1/clientes', static function () use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    AuthorizationMiddleware::requireRole(...AuthorizationMiddleware::MANAGERS);
    $container->make(ClientController::class)->create();
});
$router->map('DELETE', '/api/v1/clientes/{id}', static function (array $params) use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    AuthorizationMiddleware::requireRole(...AuthorizationMiddleware::ADMIN_ONLY);
    $container->make(ClientController::class)->delete((int)($params['id'] ?? 0));
});

$router->map('GET', '/api/v1/motoristas', static function () use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    $container->make(MotoristaController::class)->index();
});
$router->map('GET', '/api/v1/tipos-caminhao', static function () use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    $container->make(MotoristaController::class)->listTiposCaminhao();
});
$router->map('POST', '/api/v1/motoristas', static function () use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    AuthorizationMiddleware::requireRole(...AuthorizationMiddleware::MANAGERS);
    $container->make(MotoristaController::class)->create();
});
$router->map(['PUT', 'PATCH'], '/api/v1/motoristas/{id}', static function (array $params) use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    AuthorizationMiddleware::requireRole(...AuthorizationMiddleware::MANAGERS);
    $container->make(MotoristaController::class)->update((int)($params['id'] ?? 0));
});
$router->map('DELETE', '/api/v1/motoristas/{id}', static function (array $params) use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    AuthorizationMiddleware::requireRole(...AuthorizationMiddleware::ADMIN_ONLY);
    $container->make(MotoristaController::class)->delete((int)($params['id'] ?? 0));
});

$router->map('GET', '/api/v1/fornecedores', static function () use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    $container->make(FornecedorController::class)->index();
});
$router->map('POST', '/api/v1/fornecedores', static function () use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    AuthorizationMiddleware::requireRole(...AuthorizationMiddleware::MANAGERS);
    $container->make(FornecedorController::class)->create();
});
$router->map('DELETE', '/api/v1/fornecedores/{id}', static function (array $params) use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    AuthorizationMiddleware::requireRole(...AuthorizationMiddleware::ADMIN_ONLY);
    $container->make(FornecedorController::class)->delete((int)($params['id'] ?? 0));
});

$router->map('GET', '/api/v1/produtos', static function () use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    $container->make(ProductController::class)->index();
});
$router->map('POST', '/api/v1/produtos', static function () use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    AuthorizationMiddleware::requireRole(...AuthorizationMiddleware::SENIOR);
    $container->make(ProductController::class)->create();
});
$router->map(['PUT', 'PATCH'], '/api/v1/produtos/{id}', static function (array $params) use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    AuthorizationMiddleware::requireRole(...AuthorizationMiddleware::SENIOR);
    $container->make(ProductController::class)->update((int)($params['id'] ?? 0));
});
$router->map('DELETE', '/api/v1/produtos/{id}', static function (array $params) use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    AuthorizationMiddleware::requireRole(...AuthorizationMiddleware::ADMIN_ONLY);
    $container->make(ProductController::class)->delete((int)($params['id'] ?? 0));
});

$router->map('GET', '/api/v1/relatorios', static function () use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    AuthorizationMiddleware::requireRole(...AuthorizationMiddleware::MANAGERS);
    $container->make(ReportsController::class)->index();
});
$router->map('GET', '/api/v1/relatorios/dashboard', static function () use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    AuthorizationMiddleware::requireRole(...AuthorizationMiddleware::MANAGERS);
    $container->make(ReportsController::class)->dashboard();
});
$router->map('GET', '/api/v1/relatorios/compras', static function () use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    AuthorizationMiddleware::requireRole(...AuthorizationMiddleware::MANAGERS);
    $container->make(ReportsController::class)->strategicPurchases();
});
$router->map('GET', '/api/v1/relatorios/compras/export', static function () use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    RateLimitMiddleware::check(5, 60);
    AuthorizationMiddleware::requireRole(...AuthorizationMiddleware::MANAGERS);
    $container->make(ReportsController::class)->exportStrategicPurchases();
});

$router->map('GET', '/api/v1/relatorios/abc', static function () use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    $container->make(ReportsController::class)->abc();
});

// === ESTOQUE ===
$router->map('GET', '/api/v1/estoque', static function () use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    $container->make(EstoqueController::class)->index();
});
$router->map('GET', '/api/v1/estoque/saldos', static function () use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    $container->make(EstoqueController::class)->saldos();
});
$router->map('POST', '/api/v1/estoque', static function () use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    AuthorizationMiddleware::requireRole(...AuthorizationMiddleware::MANAGERS);
    $container->make(EstoqueController::class)->create();
});
$router->map('DELETE', '/api/v1/estoque/{id}', static function (array $params) use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    $container->make(EstoqueController::class)->delete((int)($params['id'] ?? 0));
});

// === QUEBRAS ===
$router->map('GET', '/api/v1/quebras', static function () use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    $container->make(QuebrasController::class)->index();
});
$router->map('POST', '/api/v1/quebras', static function () use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    AuthorizationMiddleware::requireRole(...AuthorizationMiddleware::MANAGERS);
    $container->make(QuebrasController::class)->create();
});

// === LOTES ===
$router->map('GET', '/api/v1/lotes', static function () use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    $container->make(LoteController::class)->index();
});
$router->map('POST', '/api/v1/lotes', static function () use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    AuthorizationMiddleware::requireRole(...AuthorizationMiddleware::MANAGERS);
    $container->make(LoteController::class)->create();
});
$router->map(['PUT', 'PATCH'], '/api/v1/lotes/{id}', static function (array $params) use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    AuthorizationMiddleware::requireRole(...AuthorizationMiddleware::MANAGERS);
    $container->make(LoteController::class)->update((int)($params['id'] ?? 0));
});

// === HISTÓRICO DE PREÇO ===
$router->map('GET', '/api/v1/produtos/{id}/historico-precos', static function (array $params) use ($pdo, $ensureAuth): void {
    $ensureAuth();
    $pid = (int)($params['id'] ?? 0);
    $stmt = $pdo->prepare(
        'SELECT hpp.id, hpp.valor_unitario, hpp.data_referencia, f.razao_social AS fornecedor
         FROM historico_preco_produto hpp
         LEFT JOIN fornecedores f ON f.id = hpp.fornecedor_id
         WHERE hpp.produto_id = :pid
         ORDER BY hpp.data_referencia DESC
         LIMIT 90'
    );
    $stmt->execute(['pid' => $pid]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
});

// === TABELAS DE PREÇO ===
$router->map('GET', '/api/v1/tabelas-preco', static function () use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    $container->make(TabelaPrecoController::class)->index();
});
$router->map('POST', '/api/v1/tabelas-preco', static function () use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    AuthorizationMiddleware::requireRole(...AuthorizationMiddleware::ADMIN_ONLY);
    $container->make(TabelaPrecoController::class)->create();
});
$router->map(['PUT', 'PATCH'], '/api/v1/tabelas-preco/{id}', static function (array $params) use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    AuthorizationMiddleware::requireRole(...AuthorizationMiddleware::ADMIN_ONLY);
    $container->make(TabelaPrecoController::class)->update((int)($params['id'] ?? 0));
});

$router->map('GET', '/api/v1/usuarios', static function () use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    AuthorizationMiddleware::requireRole(...AuthorizationMiddleware::ADMIN_ONLY);
    $container->make(UserController::class)->index();
});
$router->map('POST', '/api/v1/usuarios', static function () use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    AuthorizationMiddleware::requireRole(...AuthorizationMiddleware::ADMIN_ONLY);
    $container->make(UserController::class)->create();
});
$router->map(['PUT', 'PATCH'], '/api/v1/usuarios/{id}', static function (array $params) use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    AuthorizationMiddleware::requireRole(...AuthorizationMiddleware::ADMIN_ONLY);
    $container->make(UserController::class)->update((int)($params['id'] ?? 0));
});
$router->map('DELETE', '/api/v1/usuarios/{id}', static function (array $params) use ($pdo, $ensureAuth, $container): void {
    $ensureAuth();
    AuthorizationMiddleware::requireRole(...AuthorizationMiddleware::ADMIN_ONLY);
    $container->make(UserController::class)->delete((int)($params['id'] ?? 0));
});

if (!$router->dispatch($method, $uri)) {
    Metrics::increment('http_errors_total');
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
}
