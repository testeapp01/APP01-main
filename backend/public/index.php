<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

set_exception_handler(static function (\Throwable $e): void {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
    }
    error_log('[UNCAUGHT] ' . $e::class . ': ' . $e->getMessage());
    echo json_encode(['error' => 'Erro interno. Tente novamente.']);
});

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    error_log("[PHP ERROR][$severity] $message in $file:$line");
    return false;
});

use Dotenv\Dotenv;
use App\Database\Connection;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\InputSanitizer;
use App\Middlewares\RateLimitMiddleware;
use App\Middlewares\SecureHeadersMiddleware;
use App\Controllers\AuthController;
use App\Controllers\PurchaseController;
use App\Controllers\SalesController;
use App\Controllers\ReportsController;
use App\Controllers\ClientController;
use App\Controllers\MotoristaController;
use App\Controllers\ProductController;
use App\Controllers\FornecedorController;


// Load environment variables (compatible with modern vlucas/phpdotenv)
Dotenv::createImmutable(__DIR__ . '/..')->load();

$pdo = Connection::getPdo();

header('Content-Type: application/json; charset=utf-8');

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
$authUser = null;

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
header('Access-Control-Allow-Headers: Authorization, Content-Type, Accept, Origin, X-Requested-With');

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// apply secure headers first
SecureHeadersMiddleware::apply();

// rate limit (global)
RateLimitMiddleware::check(120, 60);

// sanitize input body JSON if present
$raw = file_get_contents('php://input');
if ($raw) {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $_SANITIZED = InputSanitizer::sanitizeArray($decoded);
        // replace php://input usage - controllers read php://input, so set a global
        $GLOBALS['SANITIZED_INPUT'] = $_SANITIZED;
    }
}

// public routes
if ($uri === '/api/v1/auth/login' && $method === 'POST') {
    (new AuthController($pdo))->login();
    exit;
}

// protect all API routes except login
if (str_starts_with($uri, '/api/v1/')) {
    $isPublic = ($uri === '/api/v1/auth/login' && $method === 'POST');
    if (!$isPublic) {
        $authUser = AuthMiddleware::authenticate();
    }
}

if ($uri === '/api/v1/auth/me' && $method === 'GET') {
    (new AuthController($pdo))->me((array) $authUser);
    exit;
}

// compras
if ($uri === '/api/v1/compras' && $method === 'POST') {
    (new PurchaseController($pdo))->create();
    exit;
}

if ($uri === '/api/v1/compras/receive' && $method === 'POST') {
    (new PurchaseController($pdo))->receive();
    exit;
}

// list compras (GET) - supports query params ?page=&per_page=&q=
if ($uri === '/api/v1/compras' && $method === 'GET') {
    (new PurchaseController($pdo))->index();
    exit;
}

if (preg_match('#^/api/v1/compras/cabecalhos/(\d+)$#', $uri, $matches) && $method === 'GET') {
    (new PurchaseController($pdo))->showHeader((int)$matches[1]);
    exit;
}

if (preg_match('#^/api/v1/compras/cabecalhos/(\d+)$#', $uri, $matches) && in_array($method, ['PUT', 'PATCH'], true)) {
    (new PurchaseController($pdo))->updateHeader((int)$matches[1]);
    exit;
}

if (preg_match('#^/api/v1/compras/cabecalhos/(\d+)$#', $uri, $matches) && $method === 'DELETE') {
    (new PurchaseController($pdo))->deleteHeader((int)$matches[1]);
    exit;
}

if (preg_match('#^/api/v1/compras/cabecalhos/(\d+)/confirmar-entrega$#', $uri, $matches) && $method === 'POST') {
    (new PurchaseController($pdo))->confirmHeaderDelivery((int)$matches[1]);
    exit;
}

if (preg_match('#^/api/v1/compras/(\d+)$#', $uri, $matches) && in_array($method, ['PUT', 'PATCH'], true)) {
    (new PurchaseController($pdo))->updateItem((int)$matches[1]);
    exit;
}

if (preg_match('#^/api/v1/compras/(\d+)$#', $uri, $matches) && $method === 'DELETE') {
    (new PurchaseController($pdo))->deleteItem((int)$matches[1]);
    exit;
}

if (preg_match('#^/api/v1/compras/(\d+)/confirmar-entrega$#', $uri, $matches) && $method === 'POST') {
    (new PurchaseController($pdo))->confirmItemDelivery((int)$matches[1]);
    exit;
}

// vendas (placeholder)
if ($uri === '/api/v1/vendas' && $method === 'POST') {
    (new SalesController($pdo))->create();
    exit;
}

if ($uri === '/api/v1/vendas/deliver' && $method === 'POST') {
    (new SalesController($pdo))->deliver();
    exit;
}

// list vendas (GET) - supports query params ?page=&per_page=&q=
if ($uri === '/api/v1/vendas' && $method === 'GET') {
    (new SalesController($pdo))->index();
    exit;
}

if (preg_match('#^/api/v1/vendas/cabecalhos/(\d+)$#', $uri, $matches) && $method === 'GET') {
    (new SalesController($pdo))->showHeader((int)$matches[1]);
    exit;
}

// clientes
if ($uri === '/api/v1/clientes' && $method === 'GET') {
    (new ClientController($pdo))->index();
    exit;
}

if ($uri === '/api/v1/clientes' && $method === 'POST') {
    (new ClientController($pdo))->create();
    exit;
}

if (preg_match('#^/api/v1/clientes/(\d+)$#', $uri, $matches) && $method === 'DELETE') {
    (new ClientController($pdo))->delete((int)$matches[1]);
    exit;
}

// motoristas
if ($uri === '/api/v1/motoristas' && $method === 'GET') {
    (new MotoristaController($pdo))->index();
    exit;
}

if ($uri === '/api/v1/tipos-caminhao' && $method === 'GET') {
    (new MotoristaController($pdo))->listTiposCaminhao();
    exit;
}

// fornecedores
if ($uri === '/api/v1/fornecedores' && $method === 'GET') {
    (new FornecedorController($pdo))->index();
    exit;
}

if ($uri === '/api/v1/fornecedores' && $method === 'POST') {
    (new FornecedorController($pdo))->create();
    exit;
}

if (preg_match('#^/api/v1/fornecedores/(\d+)$#', $uri, $matches) && $method === 'DELETE') {
    (new FornecedorController($pdo))->delete((int)$matches[1]);
    exit;
}

if ($uri === '/api/v1/motoristas' && $method === 'POST') {
    (new MotoristaController($pdo))->create();
    exit;
}

if (preg_match('#^/api/v1/motoristas/(\d+)$#', $uri, $matches) && in_array($method, ['PUT', 'PATCH'], true)) {
    (new MotoristaController($pdo))->update((int)$matches[1]);
    exit;
}

if (preg_match('#^/api/v1/motoristas/(\d+)$#', $uri, $matches) && $method === 'DELETE') {
    (new MotoristaController($pdo))->delete((int)$matches[1]);
    exit;
}

// produtos
if ($uri === '/api/v1/produtos' && $method === 'GET') {
    (new ProductController($pdo))->index();
    exit;
}

if ($uri === '/api/v1/produtos' && $method === 'POST') {
    (new ProductController($pdo))->create();
    exit;
}

if (preg_match('#^/api/v1/produtos/(\d+)$#', $uri, $matches) && in_array($method, ['PUT', 'PATCH'], true)) {
    (new ProductController($pdo))->update((int)$matches[1]);
    exit;
}

if (preg_match('#^/api/v1/produtos/(\d+)$#', $uri, $matches) && $method === 'DELETE') {
    (new ProductController($pdo))->delete((int)$matches[1]);
    exit;
}

// relatorios (placeholder)
if ($uri === '/api/v1/relatorios' && $method === 'GET') {
    (new ReportsController($pdo))->index();
    exit;
}

if ($uri === '/api/v1/relatorios/dashboard' && $method === 'GET') {
    (new ReportsController($pdo))->dashboard();
    exit;
}

http_response_code(404);
echo json_encode(['error' => 'Not found']);
