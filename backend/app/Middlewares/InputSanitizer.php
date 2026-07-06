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
                // Remove null bytes; HTML-escaping belongs at output layer, not here.
                $clean[$k] = str_replace("\0", '', trim($v));
            } else {
                $clean[$k] = $v;
            }
        }
        return $clean;
    }
}
