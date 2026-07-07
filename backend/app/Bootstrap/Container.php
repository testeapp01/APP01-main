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
    /** @var array<string, object> */
    private array $instances = [];

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
                return new \App\Controllers\PurchaseController(
                    $this->pdo,
                    $this->get(\App\Services\PurchaseCreationService::class),
                    $this->get(\App\Services\PurchaseHeaderService::class),
                    $this->get(\App\Services\OrderPdfService::class)
                );
            case \App\Controllers\SalesController::class:
                return new \App\Controllers\SalesController(
                    $this->pdo,
                    $this->get(\App\Services\SalesCreationService::class),
                    $this->get(\App\Services\SalesHeaderService::class),
                    $this->get(\App\Services\OrderPdfService::class)
                );
            case \App\Controllers\AuthController::class:
                return new \App\Controllers\AuthController($this->pdo);
            case \App\Controllers\ClientController::class:
                return new \App\Controllers\ClientController($this->pdo, $this->get(\App\Repositories\ClientRepository::class));
            case \App\Controllers\MotoristaController::class:
                return new \App\Controllers\MotoristaController($this->pdo, $this->get(\App\Repositories\MotoristaRepository::class));
            case \App\Controllers\FornecedorController::class:
                return new \App\Controllers\FornecedorController($this->pdo, $this->get(\App\Repositories\FornecedorRepository::class));
            case \App\Controllers\ProductController::class:
                return new \App\Controllers\ProductController($this->pdo, $this->get(\App\Repositories\ProductRepository::class));
            case \App\Controllers\ReportsController::class:
            case \App\Controllers\EstoqueController::class:
            case \App\Controllers\QuebrasController::class:
            case \App\Controllers\LoteController::class:
            case \App\Controllers\TabelaPrecoController::class:
            case \App\Controllers\UserController::class:
                return new $class($this->pdo);
            case \App\Helpers\Schema::class:
                return $this->get(\App\Helpers\Schema::class);
            default:
                // If it's a Repository or Service that expects PDO, pass PDO.
                if (str_contains($class, '\\Repositories\\') || str_contains($class, '\\Services\\')) {
                    return $this->get($class);
                }

                // Fallback: attempt to instantiate with PDO
                return $this->get($class);
        }
    }

    /**
     * Resolve and cache an instance for a given class name.
     * Tries to instantiate common Services/Repositories/Helpers with PDO.
     * @param class-string $class
     */
    public function get(string $class): object
    {
        if (isset($this->instances[$class])) {
            return $this->instances[$class];
        }

        // explicit factories for classes that need more than PDO in future
        switch ($class) {
            case \App\Services\PurchaseCreationService::class:
                $inst = new \App\Services\PurchaseCreationService($this->pdo);
                break;
            case \App\Services\PurchaseHeaderService::class:
                $inst = new \App\Services\PurchaseHeaderService($this->pdo);
                break;
            case \App\Services\OrderPdfService::class:
                $inst = new \App\Services\OrderPdfService($this->pdo);
                break;
            case \App\Services\SalesCreationService::class:
                $inst = new \App\Services\SalesCreationService($this->pdo);
                break;
            case \App\Services\SalesHeaderService::class:
                $inst = new \App\Services\SalesHeaderService($this->pdo);
                break;
            case \App\Helpers\Schema::class:
                $inst = new \App\Helpers\Schema($this->pdo);
                break;
            default:
                if (class_exists($class)) {
                    // Most Repositories and Services accept PDO in constructor
                    $inst = new $class($this->pdo);
                } else {
                    throw new \RuntimeException("Container cannot resolve class: {$class}");
                }
        }

        $this->instances[$class] = $inst;
        return $inst;
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}
