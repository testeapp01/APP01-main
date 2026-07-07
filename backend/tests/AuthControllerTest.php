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

    private function makeUsersDb(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, email TEXT NOT NULL, password TEXT NOT NULL, role TEXT)');

        return $pdo;
    }

    public function testLoginSucceedsWithBcryptPassword(): void
    {
        $pdo = $this->makeUsersDb();
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)');
        $stmt->execute([
            'name' => 'Cliente Teste',
            'email' => 'cliente@example.com',
            'password' => password_hash('secret123', PASSWORD_BCRYPT),
            'role' => 'admin',
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
        $this->assertSame('admin', $payload['user']['role'] ?? null);
    }

    public function testLoginDefaultsRoleWhenMissing(): void
    {
        $pdo = $this->makeUsersDb();
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)');
        $stmt->execute([
            'name' => 'Cliente Sem Role',
            'email' => 'cliente2@example.com',
            'password' => password_hash('secret123', PASSWORD_BCRYPT),
            'role' => '',
        ]);

        $GLOBALS['SANITIZED_INPUT'] = [
            'email' => 'cliente2@example.com',
            'password' => 'secret123',
        ];

        ob_start();
        (new AuthController($pdo))->login();
        $raw = ob_get_clean();

        $this->assertSame(200, http_response_code());

        $payload = json_decode((string)$raw, true);
        $this->assertIsArray($payload);
        $this->assertSame('operador', $payload['user']['role'] ?? null);
    }

    public function testLoginUpgradesLegacyBcryptCost(): void
    {
        $pdo = $this->makeUsersDb();
        // Simulate a hash generated with a lower (outdated) cost so the
        // rehash-on-login path is exercised.
        $oldHash = password_hash('secret123', PASSWORD_BCRYPT, ['cost' => 4]);
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)');
        $stmt->execute([
            'name' => 'Cliente Legado',
            'email' => 'legado@example.com',
            'password' => $oldHash,
            'role' => 'operador',
        ]);

        $GLOBALS['SANITIZED_INPUT'] = [
            'email' => 'legado@example.com',
            'password' => 'secret123',
        ];

        ob_start();
        (new AuthController($pdo))->login();
        ob_get_clean();

        $this->assertSame(200, http_response_code());

        $stored = (string)$pdo->query('SELECT password FROM users WHERE email = "legado@example.com"')->fetchColumn();
        $this->assertNotSame($oldHash, $stored);
        $this->assertTrue(password_verify('secret123', $stored));
    }

    public function testLoginRejectsWrongPassword(): void
    {
        $pdo = $this->makeUsersDb();
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)');
        $stmt->execute([
            'name' => 'Cliente Teste',
            'email' => 'cliente3@example.com',
            'password' => password_hash('secret123', PASSWORD_BCRYPT),
            'role' => 'admin',
        ]);

        $GLOBALS['SANITIZED_INPUT'] = [
            'email' => 'cliente3@example.com',
            'password' => 'wrong-password',
        ];

        ob_start();
        (new AuthController($pdo))->login();
        $raw = ob_get_clean();

        $this->assertSame(401, http_response_code());
        $payload = json_decode((string)$raw, true);
        $this->assertSame('Credenciais inválidas', $payload['error'] ?? null);
    }

    public function testLoginRejectsUnknownEmailWithoutRevealingWhichPartFailed(): void
    {
        $pdo = $this->makeUsersDb();

        $GLOBALS['SANITIZED_INPUT'] = [
            'email' => 'nao-existe@example.com',
            'password' => 'anything123',
        ];

        ob_start();
        (new AuthController($pdo))->login();
        $raw = ob_get_clean();

        $this->assertSame(401, http_response_code());
        $payload = json_decode((string)$raw, true);
        $this->assertSame('Credenciais inválidas', $payload['error'] ?? null);
    }

    public function testLoginNeverAcceptsAPlainTextStoredPassword(): void
    {
        // Security regression guard: even if a row was inserted manually with
        // a plain-text password, login must NOT succeed. Only proper bcrypt
        // hashes (or hashes needing rehash) are accepted.
        $pdo = $this->makeUsersDb();
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)');
        $stmt->execute([
            'name' => 'Inserido Manualmente',
            'email' => 'manual@example.com',
            'password' => 'secret123',
            'role' => 'admin',
        ]);

        $GLOBALS['SANITIZED_INPUT'] = [
            'email' => 'manual@example.com',
            'password' => 'secret123',
        ];

        ob_start();
        (new AuthController($pdo))->login();
        $raw = ob_get_clean();

        $this->assertSame(401, http_response_code());
        $payload = json_decode((string)$raw, true);
        $this->assertSame('Credenciais inválidas', $payload['error'] ?? null);
    }
}
