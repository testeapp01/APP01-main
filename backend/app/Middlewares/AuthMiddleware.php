<?php
namespace App\Middlewares;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthMiddleware
{
    public static function authenticate(): ?array
    {
        $headers = getallheaders();
        $auth = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        if (!$auth) {
            http_response_code(401);
            echo json_encode(['error' => 'Token não fornecido']);
            exit;
        }

        if (str_starts_with($auth, 'Bearer ')) {
            $token = substr($auth, 7);
        } else {
            $token = $auth;
        }

        try {
            $secret = getenv('JWT_SECRET');
            if (!$secret || strlen($secret) < 32) {
                http_response_code(500);
                echo json_encode(['error' => 'Erro de configuração do servidor']);
                exit;
            }
            $decoded = (array) JWT::decode($token, new Key($secret, 'HS256'));
            return $decoded;
        } catch (\Throwable $e) {
            http_response_code(401);
            echo json_encode(['error' => 'Token inválido ou expirado']);
            exit;
        }
    }
}
