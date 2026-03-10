<?php
namespace App\Helpers;

class Request
{
    public static function body(): array
    {
        if (isset($GLOBALS['SANITIZED_INPUT']) && is_array($GLOBALS['SANITIZED_INPUT'])) {
            return $GLOBALS['SANITIZED_INPUT'];
        }
        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
