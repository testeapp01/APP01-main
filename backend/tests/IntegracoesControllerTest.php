<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use App\Controllers\IntegracoesController;

final class IntegracoesControllerTest extends TestCase
{
    private \PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE integracoes (id INTEGER PRIMARY KEY AUTOINCREMENT, nome TEXT, tipo TEXT, config TEXT, status TEXT, created_at DATETIME, deleted_at DATETIME)');
    }

    public function testCreateAndIndex(): void
    {
        $payload = ['nome' => 'Teste', 'tipo' => 'API', 'status' => 'ativo'];
        $GLOBALS['SANITIZED_INPUT'] = $payload;

        $controller = new IntegracoesController($this->pdo);

        ob_start();
        $controller->create();
        $out = ob_get_clean();
        $this->assertStringContainsString('"id"', $out);

        ob_start();
        $controller->index();
        $out2 = ob_get_clean();
        $this->assertStringContainsString('"nome":"Teste"', $out2);
    }
}
