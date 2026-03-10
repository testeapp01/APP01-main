<?php
namespace App\Controllers;

use PDO;
use Firebase\JWT\JWT;
use App\Helpers\Request;
use App\Helpers\SchemaValidator;

class AuthController
{
    private ?bool $usersHasRoleColumnCache = null;

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

        try {
            $roleSelect = $this->usersHasRoleColumn() ? 'role' : "'cliente' AS role";
            $conditions = [
                'email = :login',
                'name = :login',
            ];
            $params = ['login' => $login];

            if (!str_contains($login, '@')) {
                $conditions[] = 'email LIKE :login_prefix';
                $params['login_prefix'] = $login . '@%';
            }

            $stmt = $this->pdo->prepare(
                'SELECT id, password, name, ' . $roleSelect . '
                 FROM users
                 WHERE ' . implode(' OR ', $conditions) . '
                 LIMIT 1'
            );
            $stmt->execute($params);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
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

        $secret = getenv('JWT_SECRET') ?: 'CHANGE_ME';
        $jwt = JWT::encode($payload, $secret, 'HS256');

        echo json_encode(['token' => $jwt, 'user' => ['id' => $user['id'], 'name' => $user['name'], 'role' => $userRole]]);
    }

    public function me(array $tokenPayload): void
    {
        $userId = (int) ($tokenPayload['sub'] ?? 0);
        if ($userId <= 0) {
            http_response_code(401);
            echo json_encode(['error' => 'Sessão inválida']);
            return;
        }

        try {
            $roleSelect = $this->usersHasRoleColumn() ? 'role' : "'cliente' AS role";
            $stmt = $this->pdo->prepare('SELECT id, name, ' . $roleSelect . ' FROM users WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
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

    private function usersHasRoleColumn(): bool
    {
        if ($this->usersHasRoleColumnCache !== null) {
            return $this->usersHasRoleColumnCache;
        }

        try {
            $check = $this->pdo->query('SELECT role FROM users WHERE 1 = 0');
            $this->usersHasRoleColumnCache = $check !== false;
        } catch (\Throwable) {
            $this->usersHasRoleColumnCache = false;
        }

        return $this->usersHasRoleColumnCache;
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

        // Backward compatibility: users manually inserted with plain text password.
        if (hash_equals($storedPassword, $inputPassword)) {
            $this->upgradePasswordHash($userId, $inputPassword);
            return true;
        }

        // Legacy compatibility: hashes generated manually in database.
        if (preg_match('/^[a-f0-9]{32}$/i', $storedPassword) === 1 && hash_equals(strtolower($storedPassword), md5($inputPassword))) {
            $this->upgradePasswordHash($userId, $inputPassword);
            return true;
        }

        if (preg_match('/^[a-f0-9]{40}$/i', $storedPassword) === 1 && hash_equals(strtolower($storedPassword), sha1($inputPassword))) {
            $this->upgradePasswordHash($userId, $inputPassword);
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
            $stmt->execute([
                'password' => $hash,
                'id' => $userId,
            ]);
        } catch (\Throwable) {
            // Keep login successful even if hash upgrade fails.
        }
    }
}
