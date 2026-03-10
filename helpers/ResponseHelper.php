<?php

/**
 * ResponseHelper
 * Semua response API menggunakan format standar:
 * { "status": "success|error", "message": "...", "data": ... }
 */
class ResponseHelper
{
    /**
     * Kirim response sukses
     */
    public static function success($data = null, string $message = 'Berhasil', int $code = 200): void
    {
        http_response_code($code);
        echo json_encode([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Kirim response error
     */
    public static function error(string $message = 'Terjadi kesalahan', int $code = 400, $data = null): void
    {
        http_response_code($code);
        echo json_encode([
            'status'  => 'error',
            'message' => $message,
            'data'    => $data,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Response 401 Unauthorized
     */
    public static function unauthorized(string $message = 'Tidak terautentikasi. Silakan login terlebih dahulu.'): void
    {
        self::error($message, 401);
    }

    /**
     * Response 403 Forbidden
     */
    public static function forbidden(string $message = 'Akses ditolak. Anda tidak memiliki izin.'): void
    {
        self::error($message, 403);
    }

    /**
     * Response 404 Not Found
     */
    public static function notFound(string $message = 'Data tidak ditemukan.'): void
    {
        self::error($message, 404);
    }

    /**
     * Response 405 Method Not Allowed
     */
    public static function methodNotAllowed(): void
    {
        self::error('HTTP method tidak diizinkan.', 405);
    }

    /**
     * Response 422 Validation Error
     */
    public static function validationError(array $errors): void
    {
        self::error('Validasi gagal.', 422, $errors);
    }
}
