<?php
namespace App\Middlewares;

use App\Helpers\Response;
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
            Response::error('Token não fornecido', 401);
            exit;
        }

        try {
            $secret = getenv('JWT_SECRET');
            if (!$secret || strlen($secret) < 32) {
                Response::error('Erro de configuração do servidor', 500);
                exit;
            }
            $decoded = (array) JWT::decode($token, new Key($secret, 'HS256'));
            return $decoded;
        } catch (\Throwable $e) {
            Response::error('Token inválido ou expirado', 401);
            exit;
        }
    }
}
