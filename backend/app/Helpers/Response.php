<?php
namespace App\Helpers;

class Response
{
    public static function json(mixed $data, int $status = 200): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code($status);
        }
        echo json_encode($data);
    }

    public static function error(string $message, int $status = 400, array $extra = []): void
    {
        $payload = ['error' => $message];
        if ($extra) $payload = array_merge($payload, $extra);
        self::json($payload, $status);
    }

    public static function noContent(): void
    {
        if (!headers_sent()) {
            http_response_code(204);
        }
    }
}
