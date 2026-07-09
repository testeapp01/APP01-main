<?php
namespace App\Helpers;

class Response
{
    public static function json(mixed $data, int $status = 200): void
    {
        if ($status === 200) {
            $currentStatus = http_response_code() ?: 200;
            if ($currentStatus !== 200) {
                $status = $currentStatus;
            }

            if (is_array($data) && array_key_exists('error', $data)) {
                $status = max($status, 400);
            }
            if (is_object($data) && property_exists($data, 'error')) {
                $status = max($status, 400);
            }
        }

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
