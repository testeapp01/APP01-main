<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use App\Controllers\UsuariosEmpresasController;

final class UsuariosEmpresasControllerTest extends TestCase
{
    private \PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE usuarios_empresas (id INTEGER PRIMARY KEY AUTOINCREMENT, usuario_id INTEGER, empresa_id INTEGER, role_empresa TEXT, status INTEGER, created_at DATETIME, deleted_at DATETIME)');
        $this->pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
        $this->pdo->exec('CREATE TABLE empresas (id INTEGER PRIMARY KEY AUTOINCREMENT, nome TEXT)');
        $this->pdo->exec("INSERT INTO users (id, name) VALUES (1, 'Admin')");
        $this->pdo->exec("INSERT INTO empresas (id, nome) VALUES (2, 'Empresa Matriz')");
    }

    public function testCreateAndIndex(): void
    {
        // Prepare input
        $payload = ['usuario_id' => 1, 'empresa_id' => 2, 'role_empresa' => 'admin', 'status' => 1];
        $GLOBALS['SANITIZED_INPUT'] = $payload;

        $controller = new UsuariosEmpresasController($this->pdo);

        // Capture output for create
        ob_start();
        $controller->create();
        $out = ob_get_clean();
        $this->assertStringContainsString('"id"', $out);

        // Now index should return the created row
        ob_start();
        $controller->index();
        $out2 = ob_get_clean();
        $this->assertStringContainsString('"usuario_id":1', $out2);
    }
}
