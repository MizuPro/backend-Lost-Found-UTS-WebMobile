<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

// Helpers
require_once __DIR__ . '/../helpers/ResponseHelper.php';
require_once __DIR__ . '/../helpers/JwtHelper.php';
require_once __DIR__ . '/../helpers/ValidationHelper.php';

// Middleware
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/RoleMiddleware.php';

// Models
require_once __DIR__ . '/../models/UserModel.php';

/**
 * AuthController
 * Menangani: register, login, logout, me (profil user login)
 */
class AuthController
{
    /** @var UserModel */
    private $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    // ── POST /api/auth/register ───────────────────────────────────────────────
    public function register(): void
    {
        $input = ValidationHelper::getInput();
        $input = ValidationHelper::sanitizeAll($input);

        // Validasi field wajib
        $errors = ValidationHelper::required($input, ['name', 'email', 'password', 'role']);
        if (!empty($errors)) {
            ResponseHelper::validationError($errors);
        }

        // Validasi format email
        if (!ValidationHelper::email($input['email'])) {
            ResponseHelper::validationError(['email' => 'Format email tidak valid.']);
        }

        // Validasi panjang password
        if (!ValidationHelper::minLength($input['password'], 8)) {
            ResponseHelper::validationError(['password' => 'Password minimal 8 karakter.']);
        }

        // Validasi role
        if (!ValidationHelper::inArray($input['role'], [ROLE_PETUGAS, ROLE_PELAPOR])) {
            ResponseHelper::validationError([
                'role' => 'Role harus berupa "' . ROLE_PETUGAS . '" atau "' . ROLE_PELAPOR . '".'
            ]);
        }

        // Cek email sudah dipakai
        if ($this->userModel->emailExists($input['email'])) {
            ResponseHelper::error('Email sudah terdaftar. Gunakan email lain.', 409);
        }

        // Hash password
        $passwordHash = password_hash($input['password'], PASSWORD_BCRYPT);

        // Simpan user
        $userId = $this->userModel->create(
            $input['name'],
            $input['email'],
            $passwordHash,
            $input['role']
        );

        $user = $this->userModel->findById($userId);

        ResponseHelper::success(
            ['user' => $user],
            'Registrasi berhasil.',
            201
        );
    }

    // ── POST /api/auth/login ──────────────────────────────────────────────────
    public function login(): void
    {
        $input = ValidationHelper::getInput();
        $input = ValidationHelper::sanitizeAll($input);

        // Validasi field wajib
        $errors = ValidationHelper::required($input, ['email', 'password']);
        if (!empty($errors)) {
            ResponseHelper::validationError($errors);
        }

        // Cari user
        $user = $this->userModel->findByEmail($input['email']);

        if (!$user || !password_verify($input['password'], $user['password'])) {
            ResponseHelper::error('Email atau password salah.', 401);
        }

        // Buat JWT token
        $token = JwtHelper::encode([
            'user_id' => $user['id'],
            'name'    => $user['name'],
            'email'   => $user['email'],
            'role'    => $user['role'],
        ]);

        // Hapus password dari response
        unset($user['password']);

        ResponseHelper::success([
            'token'      => $token,
            'token_type' => 'Bearer',
            'expires_in' => JWT_EXPIRE,
            'user'       => $user,
        ], 'Login berhasil.');
    }

    // ── POST /api/auth/logout ─────────────────────────────────────────────────
    // JWT stateless: client cukup hapus tokennya.
    // Endpoint ini hanya untuk memberikan konfirmasi.
    public function logout(): void
    {
        ResponseHelper::success(null, 'Logout berhasil. Silakan hapus token Anda.');
    }

    // ── GET /api/auth/me ──────────────────────────────────────────────────────
    public function me(): void
    {
        $authUser = isset($GLOBALS['auth_user']) ? $GLOBALS['auth_user'] : null;

        if (!$authUser) {
            ResponseHelper::unauthorized();
        }

        $user = $this->userModel->findById((int) $authUser['user_id']);

        if (!$user) {
            ResponseHelper::notFound('User tidak ditemukan.');
        }

        ResponseHelper::success(['user' => $user], 'Data profil berhasil diambil.');
    }

    // ── PUT /api/auth/profile ─────────────────────────────────────────────────
    public function updateProfile(): void
    {
        $authUser = isset($GLOBALS['auth_user']) ? $GLOBALS['auth_user'] : null;

        if (!$authUser) {
            ResponseHelper::unauthorized();
        }

        $input = ValidationHelper::getInput();
        $input = ValidationHelper::sanitizeAll($input);

        $errors = ValidationHelper::required($input, ['name', 'email']);
        if (!empty($errors)) {
            ResponseHelper::validationError($errors);
        }

        if (!ValidationHelper::email($input['email'])) {
            ResponseHelper::validationError(['email' => 'Format email tidak valid.']);
        }

        // Cek email tidak bentrok dengan user lain
        $existing = $this->userModel->findByEmail($input['email']);
        if ($existing && (int)$existing['id'] !== (int)$authUser['user_id']) {
            ResponseHelper::error('Email sudah digunakan oleh akun lain.', 409);
        }

        $this->userModel->updateProfile((int)$authUser['user_id'], $input['name'], $input['email']);

        $user = $this->userModel->findById((int)$authUser['user_id']);

        ResponseHelper::success(['user' => $user], 'Profil berhasil diperbarui.');
    }

    // ── PUT /api/auth/change-password ─────────────────────────────────────────
    public function changePassword(): void
    {
        $authUser = isset($GLOBALS['auth_user']) ? $GLOBALS['auth_user'] : null;

        if (!$authUser) {
            ResponseHelper::unauthorized();
        }

        $input = ValidationHelper::getInput();

        $errors = ValidationHelper::required($input, ['current_password', 'new_password']);
        if (!empty($errors)) {
            ResponseHelper::validationError($errors);
        }

        if (!ValidationHelper::minLength($input['new_password'], 8)) {
            ResponseHelper::validationError(['new_password' => 'Password baru minimal 8 karakter.']);
        }

        // Ambil data user lengkap dengan password hash
        $userBasic = $this->userModel->findById((int)$authUser['user_id']);
        $user      = $this->userModel->findByEmail($userBasic['email']);

        if (!password_verify($input['current_password'], $user['password'])) {
            ResponseHelper::error('Password saat ini salah.', 401);
        }

        $newHash = password_hash($input['new_password'], PASSWORD_BCRYPT);
        $this->userModel->updatePassword((int)$authUser['user_id'], $newHash);

        ResponseHelper::success(null, 'Password berhasil diubah. Silakan login ulang.');
    }
}

