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
                return new \App\Controllers\PurchaseController($this->pdo);
            case \App\Controllers\SalesController::class:
                return new \App\Controllers\SalesController($this->pdo);
            default:
                // Fallback: try to instantiate with PDO
                return new $class($this->pdo);
        }
    }
}
