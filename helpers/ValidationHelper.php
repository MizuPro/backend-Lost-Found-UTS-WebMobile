<?php

/**
 * ValidationHelper
 * Utility untuk validasi & sanitasi input
 */
class ValidationHelper
{
    /**
     * Cek field yang wajib ada di array input
     * Return: array error (kosong = valid)
     */
    public static function required(array $data, array $fields): array
    {
        $errors = [];
        foreach ($fields as $field) {
            if (!isset($data[$field]) || trim((string)$data[$field]) === '') {
                $errors[$field] = "Field '{$field}' wajib diisi.";
            }
        }
        return $errors;
    }

    /**
     * Validasi format email
     */
    public static function email(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validasi panjang minimum string
     */
    public static function minLength(string $value, int $min): bool
    {
        return mb_strlen($value) >= $min;
    }

    /**
     * Validasi panjang maksimum string
     */
    public static function maxLength(string $value, int $max): bool
    {
        return mb_strlen($value) <= $max;
    }

    /**
     * Validasi nilai ada di dalam array pilihan
     */
    public static function inArray($value, array $allowed): bool
    {
        return in_array($value, $allowed, true);
    }

    /**
     * Sanitasi string (trim + strip tags)
     */
    public static function sanitizeString(string $value): string
    {
        return strip_tags(trim($value));
    }

    /**
     * Sanitasi semua string dalam array
     */
    public static function sanitizeAll(array $data): array
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            $sanitized[$key] = is_string($value) ? self::sanitizeString($value) : $value;
        }
        return $sanitized;
    }

    /**
     * Ambil JSON body dari request
     */
    public static function getJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if (empty($raw)) {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Ambil input (JSON body atau form-data / $_POST)
     */
    public static function getInput(): array
    {
        $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
        if (strpos($contentType, 'application/json') !== false) {
            return self::getJsonBody();
        }
        return array_merge($_POST, $_GET);
    }
}
