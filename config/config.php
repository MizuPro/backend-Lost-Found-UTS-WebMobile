<?php

// ── Aplikasi ─────────────────────────────────────────────────────────────────
define('APP_NAME',    'CommuterLink Nusantara Lost & Found');
define('APP_VERSION', '1.0.0');
define('BASE_URL',    'http://localhost/backend-Lost-Found');

// ── Database ──────────────────────────────────────────────────────────────────
define('DB_HOST',     'localhost');
define('DB_PORT',     '9000');
define('DB_NAME',     'lost_found_db');
define('DB_USER',     'root');
define('DB_PASS',     '');
define('DB_CHARSET',  'utf8mb4');

// ── JWT ───────────────────────────────────────────────────────────────────────
define('JWT_SECRET',  'commuterlink_nusantara_secret_key_2024!');
define('JWT_EXPIRE',  86400); // 24 jam dalam detik

// ── Upload ────────────────────────────────────────────────────────────────────
define('UPLOAD_PATH', __DIR__ . '/../storage/uploads/');
define('UPLOAD_URL',  BASE_URL . '/storage/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/webp']);

// ── Role ─────────────────────────────────────────────────────────────────────
define('ROLE_PETUGAS',  'petugas');
define('ROLE_PELAPOR',  'pelapor');

