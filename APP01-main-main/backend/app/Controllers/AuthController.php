<?php
namespace App\Controllers;

use PDO;
use Firebase\JWT\JWT;
use App\Helpers\Request;
use App\Helpers\SchemaValidator;

class AuthController
{
    private string $resolvedIdColumn = 'id';
    private string $resolvedPasswordColumn = 'password';

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
        $attempts = [
            ['id' => 'id', 'password' => 'password', 'email' => 'email', 'name' => 'name', 'role' => 'role'],
            ['id' => 'id', 'password' => 'password', 'email' => 'email', 'name' => 'name', 'role' => null],
            ['id' => 'id', 'password' => 'password', 'email' => 'email', 'name' => 'username', 'role' => 'role'],
            ['id' => 'id', 'password' => 'senha', 'email' => 'email', 'name' => 'nome', 'role' => 'perfil'],
            ['id' => 'id', 'password' => 'senha', 'email' => 'email', 'name' => 'nome', 'role' => null],
            ['id' => 'id', 'password' => 'senha', 'email' => 'usuario', 'name' => 'nome', 'role' => 'perfil'],
            ['id' => 'id', 'password' => 'password', 'email' => 'email', 'name' => 'email', 'role' => null],
        ];

        $hadSqlError = false;
        foreach ($attempts as $attempt) {
            try {
                [$where, $params] = $this->buildLoginWhere(
                    $attempt['email'],
                    $attempt['name'],
                    $login
                );

                $idSelect = $this->quotedColumn($attempt['id']);
                $passwordSelect = $this->quotedColumn($attempt['password']);
                $nameSelect = $this->quotedColumn($attempt['name']);
                $roleSelect = $attempt['role'] !== null
                    ? $this->quotedColumn($attempt['role'])
                    : "'cliente'";

                $stmt = $this->pdo->prepare(
                    'SELECT ' . $idSelect . ' AS id, ' . $passwordSelect . ' AS password, ' . $nameSelect . ' AS name, ' . $roleSelect . ' AS role
                     FROM users
                     WHERE ' . $where . '
                     LIMIT 1'
                );
                $stmt->execute($params);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    $this->resolvedIdColumn = $attempt['id'];
                    $this->resolvedPasswordColumn = $attempt['password'];
                    return [$user, false];
                }
            } catch (\Throwable) {
                $hadSqlError = true;
            }
        }

        return [null, $hadSqlError];
    }

    private function findUserById(int $userId): array
    {
        $attempts = [
            ['id' => 'id', 'name' => 'name', 'role' => 'role'],
            ['id' => 'id', 'name' => 'name', 'role' => null],
            ['id' => 'id', 'name' => 'username', 'role' => 'role'],
            ['id' => 'id', 'name' => 'nome', 'role' => 'perfil'],
            ['id' => 'id', 'name' => 'nome', 'role' => null],
            ['id' => 'id', 'name' => 'email', 'role' => null],
        ];

        $hadSqlError = false;
        foreach ($attempts as $attempt) {
            try {
                $idSelect = $this->quotedColumn($attempt['id']);
                $nameSelect = $this->quotedColumn($attempt['name']);
                $roleSelect = $attempt['role'] !== null
                    ? $this->quotedColumn($attempt['role'])
                    : "'cliente'";

                $stmt = $this->pdo->prepare(
                    'SELECT ' . $idSelect . ' AS id, ' . $nameSelect . ' AS name, ' . $roleSelect . ' AS role
                     FROM users
                     WHERE ' . $idSelect . ' = :id
                     LIMIT 1'
                );
                $stmt->execute(['id' => $userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    return [$user, false];
                }
            } catch (\Throwable) {
                $hadSqlError = true;
            }
        }

        return [null, $hadSqlError];
    }

    private function buildLoginWhere(string $emailColumn, string $nameColumn, string $login): array
    {
        $emailSelect = $this->quotedColumn($emailColumn);
        $nameSelect = $this->quotedColumn($nameColumn);

        $conditions = [
            $emailSelect . ' = :login',
        ];

        if ($nameColumn !== $emailColumn) {
            $conditions[] = $nameSelect . ' = :login';
        }

        $params = ['login' => $login];
        if (!str_contains($login, '@') && $emailColumn === 'email') {
            $conditions[] = $emailSelect . ' LIKE :login_prefix';
            $params['login_prefix'] = $login . '@%';
        }

        return [implode(' OR ', $conditions), $params];
    }

    private function quotedColumn(string $column): string
    {
        return '`' . str_replace('`', '', $column) . '`';
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
            $stmt = $this->pdo->prepare(
                'UPDATE users SET ' . $this->quotedColumn($this->resolvedPasswordColumn) . ' = :password WHERE ' . $this->quotedColumn($this->resolvedIdColumn) . ' = :id'
            );
            $stmt->execute([
                'password' => $hash,
                'id' => $userId,
            ]);
        } catch (\Throwable) {
            // Keep login successful even if hash upgrade fails.
        }
    }
}
