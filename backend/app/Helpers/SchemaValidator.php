<?php
namespace App\Helpers;

class SchemaValidator
{
    public static function validate(array $input, array $schema): array
    {
        $errors = [];
        $required = $schema['required'] ?? [];
        $properties = $schema['properties'] ?? [];

        foreach ($required as $field) {
            if (!array_key_exists($field, $input) || $input[$field] === null || $input[$field] === '') {
                $errors[$field][] = 'Campo obrigatório';
            }
        }

        foreach ($properties as $field => $rules) {
            if (!array_key_exists($field, $input) || $input[$field] === null) {
                continue;
            }

            $value = $input[$field];
            self::validateType($field, $value, $rules, $errors);
            self::validateStringRules($field, $value, $rules, $errors);
            self::validateNumericRules($field, $value, $rules, $errors);
            self::validateEnum($field, $value, $rules, $errors);
        }

        return $errors;
    }

    private static function validateType(string $field, mixed $value, array $rules, array &$errors): void
    {
        $type = $rules['type'] ?? null;
        if (!$type) {
            return;
        }

        $ok = match ($type) {
            'string' => is_string($value),
            'integer' => is_int($value) || (is_string($value) && preg_match('/^-?\d+$/', $value) === 1),
            'number' => is_numeric($value),
            'boolean' => is_bool($value),
            'array' => is_array($value),
            'object' => is_array($value),
            default => true,
        };

        if (!$ok) {
            $errors[$field][] = 'Tipo inválido';
        }
    }

    private static function validateStringRules(string $field, mixed $value, array $rules, array &$errors): void
    {
        if (!is_string($value)) {
            return;
        }

        if (isset($rules['minLength']) && mb_strlen($value) < (int)$rules['minLength']) {
            $errors[$field][] = 'Tamanho mínimo inválido';
        }

        if (isset($rules['maxLength']) && mb_strlen($value) > (int)$rules['maxLength']) {
            $errors[$field][] = 'Tamanho máximo inválido';
        }

        if (isset($rules['pattern']) && preg_match($rules['pattern'], $value) !== 1) {
            $errors[$field][] = 'Formato inválido';
        }

        if (($rules['format'] ?? null) === 'email' && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            $errors[$field][] = 'Email inválido';
        }
    }

    private static function validateNumericRules(string $field, mixed $value, array $rules, array &$errors): void
    {
        if (!is_numeric($value)) {
            return;
        }

        $numeric = (float)$value;
        if (isset($rules['minimum']) && $numeric < (float)$rules['minimum']) {
            $errors[$field][] = 'Valor abaixo do mínimo';
        }

        if (isset($rules['maximum']) && $numeric > (float)$rules['maximum']) {
            $errors[$field][] = 'Valor acima do máximo';
        }
    }

    private static function validateEnum(string $field, mixed $value, array $rules, array &$errors): void
    {
        if (!isset($rules['enum']) || !is_array($rules['enum'])) {
            return;
        }

        if (!in_array($value, $rules['enum'], true)) {
            $errors[$field][] = 'Valor fora do domínio permitido';
        }
    }
}
