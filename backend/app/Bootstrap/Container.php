<?php
namespace App\Bootstrap;

use PDO;

/**
 * Minimal container to centralize simple factories.
 * Expand this with closures or PSR-11 later.
 */
class Container
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Make a class instance. Currently supports controllers that accept a PDO in constructor.
     * @param class-string $class
     * @return object
     */
    public function make(string $class): object
    {
        // Simple known mappings to avoid reflection and keep DI explicit
        switch ($class) {
            case \App\Controllers\PurchaseController::class:
            case \App\Controllers\SalesController::class:
            case \App\Controllers\AuthController::class:
            case \App\Controllers\ClientController::class:
            case \App\Controllers\MotoristaController::class:
            case \App\Controllers\FornecedorController::class:
            case \App\Controllers\ProductController::class:
            case \App\Controllers\ReportsController::class:
            case \App\Controllers\EstoqueController::class:
            case \App\Controllers\QuebrasController::class:
            case \App\Controllers\LoteController::class:
            case \App\Controllers\TabelaPrecoController::class:
            case \App\Controllers\UserController::class:
                return new $class($this->pdo);
            default:
                // If it's a Repository or Service that expects PDO, pass PDO.
                if (str_contains($class, '\\Repositories\\') || str_contains($class, '\\Services\\')) {
                    return new $class($this->pdo);
                }

                // Fallback: attempt to instantiate with PDO
                return new $class($this->pdo);
        }
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}
