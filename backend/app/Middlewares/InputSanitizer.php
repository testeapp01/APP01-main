<?php
namespace App\Middlewares;

class InputSanitizer
{
    /**
     * Trims incoming string fields. Does NOT HTML-escape values: this API
     * returns JSON (not HTML), SQL access always goes through parameterized
     * queries, the Vue frontend auto-escapes interpolated text, and the PDF
     * generator (OrderPdfService) does its own escaping right before building
     * HTML. HTML-escaping here used to corrupt stored data (e.g. "A & B Ltda"
     * became "A &amp; B Ltda" permanently in the database) without adding any
     * real protection.
     */
    public static function sanitizeArray(array $data): array
    {
        $clean = [];
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $clean[$k] = self::sanitizeArray($v);
            } elseif (is_string($v)) {
                $clean[$k] = trim($v);
            } else {
                $clean[$k] = $v;
            }
        }
        return $clean;
    }
}
