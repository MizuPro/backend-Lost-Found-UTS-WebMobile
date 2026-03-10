<?php

require_once __DIR__ . '/../helpers/JwtHelper.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';

/**
 * AuthMiddleware
 * Memverifikasi JWT Bearer token dari header Authorization.
 * Menyimpan payload di $GLOBALS['auth_user'] agar bisa diakses controller.
 */
class AuthMiddleware
{
    public static function handle(): array
    {
        $token = JwtHelper::getBearerToken();

        if (!$token) {
            ResponseHelper::unauthorized('Token tidak ditemukan. Silakan login terlebih dahulu.');
        }

        $payload = JwtHelper::decode($token);

        if (!$payload) {
            ResponseHelper::unauthorized('Token tidak valid atau sudah kadaluarsa. Silakan login ulang.');
        }

        // Simpan data user ter-autentikasi ke global agar bisa diakses controller
        $GLOBALS['auth_user'] = $payload;

        return $payload;
    }

    /**
     * Ambil user yang sedang login (setelah handle() dipanggil)
     */
    public static function user(): ?array
    {
        return isset($GLOBALS['auth_user']) ? $GLOBALS['auth_user'] : null;
    }
}
