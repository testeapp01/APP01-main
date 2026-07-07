<?php
namespace App\Controllers;

use PDO;
use Firebase\JWT\JWT;
use App\Helpers\Request;
use App\Helpers\SchemaValidator;

class AuthController
{
    private ?string $lastLookupError = null;

    public function __construct(private PDO $pdo)
    {
    }

    public function login(): void
    {
        $data = Request::body();
        $errors = SchemaValidator::validate($data, [
            'required' => ['email', 'password'],
            'properties' => [
                'email' => ['type' => 'string', 'maxLength' => 255],
                'password' => ['type' => 'string', 'minLength' => 3, 'maxLength' => 255],
            ],
        ]);
        if (!empty($errors)) {
            http_response_code(422);
            echo json_encode(['error' => 'Payload inválido', 'details' => $errors]);
            return;
        }

        $login = trim((string)($data['email'] ?? ''));
        $password = (string)($data['password'] ?? '');

        if ($login === '' || $password === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Login e senha são obrigatórios']);
            return;
        }

        \App\Middlewares\RateLimitMiddleware::checkLoginAttempts($login);

        [$user, $queryFailed] = $this->findUserForLogin($login);
        if ($queryFailed) {
            http_response_code(500);
            echo json_encode(['error' => 'Não foi possível autenticar no momento.']);
            return;
        }

        if (!$user || !$this->passwordMatchesAndMaybeUpgrade((int)$user['id'], (string)($user['password'] ?? ''), $password)) {
            http_response_code(401);
            echo json_encode(['error' => 'Credenciais inválidas']);
            return;
        }

        $userRole = $this->normalizeRole($user['role'] ?? null);

        $payload = [
            'sub' => $user['id'],
            'name' => $user['name'],
            'role' => $userRole,
            'iat' => time(),
            'exp' => time() + (60 * 60 * 8),
        ];

        $secret = getenv('JWT_SECRET');
        if (!$secret || strlen($secret) < 32) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro de configuração do servidor']);
            return;
        }
        $jwt = JWT::encode($payload, $secret, 'HS256');

        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                 || (int)($_SERVER['SERVER_PORT'] ?? 80) === 443;

        setcookie('auth_token', $jwt, [
            'expires'  => time() + (60 * 60 * 8),
            'path'     => '/',
            'secure'   => $isSecure,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);

        echo json_encode(['token' => $jwt, 'user' => ['id' => $user['id'], 'name' => $user['name'], 'role' => $userRole]]);
    }

    public function logout(): void
    {
        setcookie('auth_token', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        echo json_encode(['message' => 'Logout realizado com sucesso']);
    }
    }

    public function me(array $tokenPayload): void
    {
        $userId = (int) ($tokenPayload['sub'] ?? 0);
        if ($userId <= 0) {
            http_response_code(401);
            echo json_encode(['error' => 'Sessão inválida']);
            return;
        }

        [$user, $queryFailed] = $this->findUserById($userId);
        if ($queryFailed) {
            http_response_code(500);
            echo json_encode(['error' => 'Não foi possível carregar os dados do usuário']);
            return;
        }

        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Usuário não encontrado']);
            return;
        }

        $user['role'] = $this->normalizeRole($user['role'] ?? ($tokenPayload['role'] ?? null));

        echo json_encode(['user' => $user]);
    }

    private function findUserForLogin(string $login): array
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT id, password, name, role FROM users WHERE email = :email LIMIT 1'
            );
            $stmt->execute(['email' => $login]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return [$user ?: null, false];
        } catch (\Throwable $e) {
            $this->lastLookupError = $e->getMessage();
            return [null, true];
        }
    }

    private function findUserById(int $userId): array
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT id, name, role FROM users WHERE id = :id LIMIT 1'
            );
            $stmt->execute(['id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return [$user ?: null, false];
        } catch (\Throwable $e) {
            $this->lastLookupError = $e->getMessage();
            return [null, true];
        }
    }

    private function normalizeRole(mixed $role): string
    {
        $normalized = strtolower(trim((string)$role));
        return $normalized !== '' ? $normalized : 'cliente';
    }

    private function passwordMatchesAndMaybeUpgrade(int $userId, string $storedPassword, string $inputPassword): bool
    {
        if ($storedPassword === '') {
            return false;
        }

        if (password_verify($inputPassword, $storedPassword)) {
            if (password_needs_rehash($storedPassword, PASSWORD_BCRYPT)) {
                $this->upgradePasswordHash($userId, $inputPassword);
            }
            return true;
        }

        return false;
    }

    private function upgradePasswordHash(int $userId, string $plainPassword): void
    {
        $hash = password_hash($plainPassword, PASSWORD_BCRYPT);
        if ($hash === false) {
            return;
        }

        try {
            $stmt = $this->pdo->prepare('UPDATE users SET password = :password WHERE id = :id');
            $stmt->execute(['password' => $hash, 'id' => $userId]);
        } catch (\Throwable) {
            // Keep login successful even if hash upgrade fails.
        }
    }
}
