<?php
declare(strict_types=1);

use App\Controllers\AuthController;
use PHPUnit\Framework\TestCase;

final class AuthControllerTest extends TestCase
{
    private ?string $previousJwtSecret = null;

    protected function setUp(): void
    {
        parent::setUp();

        $secret = getenv('JWT_SECRET');
        $this->previousJwtSecret = $secret === false ? null : (string)$secret;
        putenv('JWT_SECRET=test-secret');

        unset($GLOBALS['SANITIZED_INPUT']);
        http_response_code(200);
    }

    protected function tearDown(): void
    {
        if ($this->previousJwtSecret === null) {
            putenv('JWT_SECRET');
        } else {
            putenv('JWT_SECRET=' . $this->previousJwtSecret);
        }

        unset($GLOBALS['SANITIZED_INPUT']);
        http_response_code(200);

        parent::tearDown();
    }

    public function testLoginWorksWhenUsersTableHasNoRoleColumn(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, email TEXT NOT NULL, password TEXT NOT NULL)');

        $stmt = $pdo->prepare('INSERT INTO users (name, email, password) VALUES (:name, :email, :password)');
        $stmt->execute([
            'name' => 'Cliente Teste',
            'email' => 'cliente@example.com',
            'password' => password_hash('secret123', PASSWORD_BCRYPT),
        ]);

        $GLOBALS['SANITIZED_INPUT'] = [
            'email' => 'cliente@example.com',
            'password' => 'secret123',
        ];

        ob_start();
        (new AuthController($pdo))->login();
        $raw = ob_get_clean();

        $this->assertSame(200, http_response_code());

        $payload = json_decode((string)$raw, true);
        $this->assertIsArray($payload);
        $this->assertNotEmpty($payload['token'] ?? null);
        $this->assertSame('Cliente Teste', $payload['user']['name'] ?? null);
        $this->assertSame('cliente', $payload['user']['role'] ?? null);
    }

    public function testMeReturnsFallbackRoleWhenUsersTableHasNoRoleColumn(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, email TEXT NOT NULL, password TEXT NOT NULL)');

        $stmt = $pdo->prepare('INSERT INTO users (name, email, password) VALUES (:name, :email, :password)');
        $stmt->execute([
            'name' => 'Cliente Teste',
            'email' => 'cliente@example.com',
            'password' => password_hash('secret123', PASSWORD_BCRYPT),
        ]);

        ob_start();
        (new AuthController($pdo))->me(['sub' => 1]);
        $raw = ob_get_clean();

        $this->assertSame(200, http_response_code());

        $payload = json_decode((string)$raw, true);
        $this->assertIsArray($payload);
        $this->assertSame('Cliente Teste', $payload['user']['name'] ?? null);
        $this->assertSame('cliente', $payload['user']['role'] ?? null);
    }
}
