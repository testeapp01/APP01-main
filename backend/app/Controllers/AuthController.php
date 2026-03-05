<?php
namespace App\Controllers;

use PDO;
use Firebase\JWT\JWT;
use App\Helpers\Request;
use App\Helpers\SchemaValidator;

class AuthController
{
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
            $stmt = $this->pdo->prepare(
                'SELECT id, password, name, role
                 FROM users
                 WHERE email = :login
                    OR name = :login
                    OR SUBSTRING_INDEX(email, "@", 1) = :login
                 LIMIT 1'
            );
            $stmt->execute(['login' => $login]);
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

        $stmt = $this->pdo->prepare('SELECT id, name, role FROM users WHERE id = :id LIMIT 1');
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
