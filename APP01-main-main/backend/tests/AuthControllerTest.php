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

    public function testLoginAcceptsPlainTextPasswordAndUpgradesHash(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, email TEXT NOT NULL, password TEXT NOT NULL, role TEXT)');

        $plainPassword = 'secret123';
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)');
        $stmt->execute([
            'name' => 'Cliente Teste',
            'email' => 'cliente@example.com',
            'password' => $plainPassword,
            'role' => 'admin',
        ]);

        $GLOBALS['SANITIZED_INPUT'] = [
            'email' => 'cliente@example.com',
            'password' => $plainPassword,
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

        $stored = (string)$pdo->query('SELECT password FROM users WHERE email = "cliente@example.com"')->fetchColumn();
        $this->assertNotSame($plainPassword, $stored);
        $this->assertTrue(password_verify($plainPassword, $stored));
    }

    public function testLoginWorksWhenUsersTableHasNoRoleColumn(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, email TEXT NOT NULL, password TEXT NOT NULL)');

        $stmt = $pdo->prepare('INSERT INTO users (name, email, password) VALUES (:name, :email, :password)');
        $stmt->execute([
            'name' => 'Cliente Sem Role',
            'email' => 'cliente2@example.com',
            'password' => password_hash('secret123', PASSWORD_BCRYPT),
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
        $this->assertSame('cliente', $payload['user']['role'] ?? null);
    }

    public function testLoginAcceptsMd5AndUpgradesHash(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, email TEXT NOT NULL, password TEXT NOT NULL, role TEXT)');

        $plainPassword = 'secret123';
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)');
        $stmt->execute([
            'name' => 'Cliente MD5',
            'email' => 'cliente-md5@example.com',
            'password' => md5($plainPassword),
            'role' => 'operador',
        ]);

        $GLOBALS['SANITIZED_INPUT'] = [
            'email' => 'cliente-md5@example.com',
            'password' => $plainPassword,
        ];

        ob_start();
        (new AuthController($pdo))->login();
        $raw = ob_get_clean();

        $this->assertSame(200, http_response_code());

        $payload = json_decode((string)$raw, true);
        $this->assertIsArray($payload);
        $this->assertSame('operador', $payload['user']['role'] ?? null);

        $stored = (string)$pdo->query('SELECT password FROM users WHERE email = "cliente-md5@example.com"')->fetchColumn();
        $this->assertNotSame(md5($plainPassword), $stored);
        $this->assertTrue(password_verify($plainPassword, $stored));
    }

    public function testLoginWorksWithPortugueseUsersColumns(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, nome TEXT NOT NULL, email TEXT NOT NULL, senha TEXT NOT NULL, perfil TEXT)');

        $plainPassword = 'secret123';
        $stmt = $pdo->prepare('INSERT INTO users (nome, email, senha, perfil) VALUES (:nome, :email, :senha, :perfil)');
        $stmt->execute([
            'nome' => 'Usuario Legado',
            'email' => 'legado@example.com',
            'senha' => $plainPassword,
            'perfil' => 'operador',
        ]);

        $GLOBALS['SANITIZED_INPUT'] = [
            'email' => 'legado@example.com',
            'password' => $plainPassword,
        ];

        ob_start();
        (new AuthController($pdo))->login();
        $raw = ob_get_clean();

        $this->assertSame(200, http_response_code());
        $payload = json_decode((string)$raw, true);
        $this->assertIsArray($payload);
        $this->assertSame('Usuario Legado', $payload['user']['name'] ?? null);
        $this->assertSame('operador', $payload['user']['role'] ?? null);

        $stored = (string)$pdo->query('SELECT senha FROM users WHERE email = "legado@example.com"')->fetchColumn();
        $this->assertTrue(password_verify($plainPassword, $stored));
    }

    public function testLoginWorksWithLegacyUserIdAndPasswdColumns(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE users (user_id INTEGER PRIMARY KEY AUTOINCREMENT, usuario TEXT NOT NULL, passwd TEXT NOT NULL, tipo TEXT)');

        $plainPassword = 'secret123';
        $stmt = $pdo->prepare('INSERT INTO users (usuario, passwd, tipo) VALUES (:usuario, :passwd, :tipo)');
        $stmt->execute([
            'usuario' => 'legacyuser',
            'passwd' => $plainPassword,
            'tipo' => 'admin',
        ]);

        $GLOBALS['SANITIZED_INPUT'] = [
            'email' => 'legacyuser',
            'password' => $plainPassword,
        ];

        ob_start();
        (new AuthController($pdo))->login();
        $raw = ob_get_clean();

        $this->assertSame(200, http_response_code());
        $payload = json_decode((string)$raw, true);
        $this->assertIsArray($payload);
        $this->assertSame('legacyuser', $payload['user']['name'] ?? null);
        $this->assertSame('admin', $payload['user']['role'] ?? null);

        $stored = (string)$pdo->query('SELECT passwd FROM users WHERE usuario = "legacyuser"')->fetchColumn();
        $this->assertTrue(password_verify($plainPassword, $stored));
    }

    public function testLoginWorksWhenUsersTableIsNamedUsuarios(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE usuarios (id INTEGER PRIMARY KEY AUTOINCREMENT, nome TEXT NOT NULL, email TEXT NOT NULL, senha TEXT NOT NULL, perfil TEXT)');

        $plainPassword = 'secret123';
        $stmt = $pdo->prepare('INSERT INTO usuarios (nome, email, senha, perfil) VALUES (:nome, :email, :senha, :perfil)');
        $stmt->execute([
            'nome' => 'Usuario Alternativo',
            'email' => 'alt@example.com',
            'senha' => $plainPassword,
            'perfil' => 'admin',
        ]);

        $GLOBALS['SANITIZED_INPUT'] = [
            'email' => 'alt@example.com',
            'password' => $plainPassword,
        ];

        ob_start();
        (new AuthController($pdo))->login();
        $raw = ob_get_clean();

        $this->assertSame(200, http_response_code());
        $payload = json_decode((string)$raw, true);
        $this->assertIsArray($payload);
        $this->assertSame('Usuario Alternativo', $payload['user']['name'] ?? null);
        $this->assertSame('admin', $payload['user']['role'] ?? null);
    }
}
