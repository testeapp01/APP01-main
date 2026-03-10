<?php
namespace App\Middlewares;

class InputSanitizer
{
    public static function sanitizeArray(array $data): array
    {
        $clean = [];
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $clean[$k] = self::sanitizeArray($v);
            } elseif (is_string($v)) {
                $s = trim($v);
                $s = htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $clean[$k] = $s;
            } else {
                $clean[$k] = $v;
            }
        }
        return $clean;
    }
}
