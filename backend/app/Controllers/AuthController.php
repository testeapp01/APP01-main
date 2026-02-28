<?php
namespace App\Controllers;

use PDO;
use Firebase\JWT\JWT;
use App\Helpers\Request;

class AuthController
{
    public function __construct(private PDO $pdo)
    {
    }

    public function login(): void
    {
        $data = Request::body();
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        try {
            $stmt = $this->pdo->prepare('SELECT id, password, name, role FROM users WHERE email = :email LIMIT 1');
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Base de usuários não está pronta. Execute migrations e seed.']);
            return;
        }

        if (!$user || !password_verify($password, $user['password'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Credenciais inválidas']);
            return;
        }

        $payload = [
            'sub' => $user['id'],
            'name' => $user['name'],
            'role' => $user['role'],
            'iat' => time(),
            'exp' => time() + (60 * 60 * 8),
        ];

        $secret = getenv('JWT_SECRET') ?: 'CHANGE_ME';
        $jwt = JWT::encode($payload, $secret, 'HS256');

        echo json_encode(['token' => $jwt, 'user' => ['id' => $user['id'], 'name' => $user['name'], 'role' => $user['role']]]);
    }

    public function me(array $tokenPayload): void
    {
        $userId = (int) ($tokenPayload['sub'] ?? 0);
        if ($userId <= 0) {
            http_response_code(401);
            echo json_encode(['error' => 'Sessão inválida']);
            return;
        }

        $stmt = $this->pdo->prepare('SELECT id, name, role, email FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Usuário não encontrado']);
            return;
        }

        echo json_encode(['user' => $user]);
    }
}
