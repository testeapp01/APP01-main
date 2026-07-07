<?php
namespace App\Middlewares;

use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Logger\Logger;

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

        $secret = getenv('JWT_SECRET') ?: '';
        if ($secret === '' || $secret === 'CHANGE_ME') {
            Logger::get()->error('jwt_secret_not_configured');
            http_response_code(500);
            echo json_encode(['error' => 'Erro interno de autenticação.']);
            exit;
        }

        try {
            $decoded = (array) JWT::decode($token, new Key($secret, 'HS256'));
            return $decoded;
        } catch (ExpiredException) {
            http_response_code(401);
            echo json_encode(['error' => 'Sessão expirada. Faça login novamente.', 'code' => 'token_expired']);
            exit;
        } catch (\Throwable $e) {
            // Never echo $e->getMessage() to the client: it can leak details
            // about the secret/library internals. Log it server-side instead.
            Logger::get()->error('jwt_decode_failed', ['message' => $e->getMessage()]);
            http_response_code(401);
            echo json_encode(['error' => 'Token inválido', 'code' => 'token_invalid']);
            exit;
        }
    }
}
