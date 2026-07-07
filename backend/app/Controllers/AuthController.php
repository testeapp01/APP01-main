<?php
namespace App\Controllers;

use PDO;
use Firebase\JWT\JWT;
use App\Helpers\Request;
use App\Helpers\SchemaValidator;
use App\Logger\Logger;

class AuthController
{
    private const USERS_TABLE = 'users';
    private const TOKEN_TTL_SECONDS = 60 * 60 * 8; // 8 hours

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

        $email = trim((string)($data['email'] ?? ''));
        $password = (string)($data['password'] ?? '');

        if ($email === '' || $password === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Login e senha são obrigatórios']);
            return;
        }

        try {
            $stmt = $this->pdo->prepare(
                'SELECT id, name, email, password, role FROM ' . self::USERS_TABLE . ' WHERE email = :email LIMIT 1'
            );
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            Logger::get()->error('auth_lookup_failed', ['message' => $e->getMessage()]);
            http_response_code(500);
            echo json_encode(['error' => 'Não foi possível autenticar no momento.']);
            return;
        }

        // Always run password_verify, even when no user is found, so the
        // response time doesn't reveal whether the e-mail exists (timing attack).
        $storedHash = $user['password'] ?? '$2y$10$invalidinvalidinvalidinvalidinvalidinvalidinvalidinva';
        $passwordOk = password_verify($password, $storedHash);

        if (!$user || !$passwordOk) {
            http_response_code(401);
            echo json_encode(['error' => 'Credenciais inválidas']);
            return;
        }

        if (password_needs_rehash($storedHash, PASSWORD_BCRYPT)) {
            $this->upgradePasswordHash((int)$user['id'], $password);
        }

        $role = $this->normalizeRole($user['role'] ?? null);

        $payload = [
            'sub' => (int)$user['id'],
            'name' => $user['name'],
            'role' => $role,
            'iat' => time(),
            'exp' => time() + self::TOKEN_TTL_SECONDS,
        ];

        $secret = $this->jwtSecret();
        $jwt = JWT::encode($payload, $secret, 'HS256');

        echo json_encode([
            'token' => $jwt,
            'user' => [
                'id' => (int)$user['id'],
                'name' => $user['name'],
                'role' => $role,
            ],
        ]);
    }

    public function me(array $tokenPayload): void
    {
        $userId = (int)($tokenPayload['sub'] ?? 0);
        if ($userId <= 0) {
            http_response_code(401);
            echo json_encode(['error' => 'Sessão inválida']);
            return;
        }

        try {
            $stmt = $this->pdo->prepare(
                'SELECT id, name, email, role FROM ' . self::USERS_TABLE . ' WHERE id = :id LIMIT 1'
            );
            $stmt->execute(['id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            Logger::get()->error('auth_me_failed', ['message' => $e->getMessage()]);
            http_response_code(500);
            echo json_encode(['error' => 'Não foi possível carregar os dados do usuário']);
            return;
        }

        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Usuário não encontrado']);
            return;
        }

        $user['id'] = (int)$user['id'];
        $user['role'] = $this->normalizeRole($user['role'] ?? ($tokenPayload['role'] ?? null));

        echo json_encode(['user' => $user]);
    }

    private function normalizeRole(mixed $role): string
    {
        $normalized = strtolower(trim((string)$role));
        return $normalized !== '' ? $normalized : 'operador';
    }

    private function upgradePasswordHash(int $userId, string $plainPassword): void
    {
        $hash = password_hash($plainPassword, PASSWORD_BCRYPT);
        if ($hash === false) {
            return;
        }

        try {
            $stmt = $this->pdo->prepare(
                'UPDATE ' . self::USERS_TABLE . ' SET password = :password WHERE id = :id'
            );
            $stmt->execute(['password' => $hash, 'id' => $userId]);
        } catch (\Throwable $e) {
            // Login already succeeded; a failed hash upgrade shouldn't break the request.
            Logger::get()->error('auth_rehash_failed', ['message' => $e->getMessage()]);
        }
    }

    private function jwtSecret(): string
    {
        $secret = getenv('JWT_SECRET') ?: '';
        if ($secret === '' || $secret === 'CHANGE_ME') {
            Logger::get()->error('jwt_secret_not_configured');
            throw new \RuntimeException('JWT secret não configurado. Defina JWT_SECRET no ambiente.');
        }

        return $secret;
    }
}
