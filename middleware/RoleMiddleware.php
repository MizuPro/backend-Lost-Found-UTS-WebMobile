<?php

require_once __DIR__ . '/../helpers/ResponseHelper.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

/**
 * RoleMiddleware
 * Mengecek apakah user yang login memiliki role yang diizinkan.
 * Harus dipanggil SETELAH AuthMiddleware::handle()
 */
class RoleMiddleware
{
    /**
     * @param string|array $allowedRoles  Role yang diizinkan, misal: 'petugas' atau ['petugas', 'pelapor']
     */
    public static function handle($allowedRoles): void
    {
        $user = AuthMiddleware::user();

        if (!$user) {
            ResponseHelper::unauthorized();
        }

        $allowedRoles = (array) $allowedRoles;

        if (!in_array($user['role'], $allowedRoles, true)) {
            ResponseHelper::forbidden(
                'Akses ditolak. Halaman ini hanya untuk: ' . implode(', ', $allowedRoles) . '.'
            );
        }
    }
}
