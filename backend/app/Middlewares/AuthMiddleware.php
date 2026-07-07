<?php
namespace App\Middlewares;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthMiddleware
{
    public static function authenticate(): ?array
    {
        // Prefer httpOnly cookie; fall back to Authorization header (API/mobile)
        $token = $_COOKIE['auth_token'] ?? null;

        if (!$token) {
            $headers = getallheaders();
            $auth = $headers['Authorization'] ?? $headers['authorization'] ?? null;
            if ($auth) {
                $token = str_starts_with($auth, 'Bearer ') ? substr($auth, 7) : $auth;
            }
        }

        if (!$token) {
            http_response_code(401);
            echo json_encode(['error' => 'Token não fornecido']);
            exit;
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
