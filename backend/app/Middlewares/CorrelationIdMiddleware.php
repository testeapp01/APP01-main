<?php
namespace App\Middlewares;

class CorrelationIdMiddleware
{
    public static function apply(): string
    {
        $incoming = $_SERVER['HTTP_X_CORRELATION_ID'] ?? null;
        $id = is_string($incoming) && trim($incoming) !== ''
            ? trim($incoming)
            : bin2hex(random_bytes(8));

        $GLOBALS['CORRELATION_ID'] = $id;
        header('X-Correlation-ID: ' . $id);

        return $id;
    }
}
