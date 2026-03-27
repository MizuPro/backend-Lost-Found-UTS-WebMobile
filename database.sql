-- ============================================================
--  CommuterLink Nusantara — Lost & Found Database
--  File  : database.sql
--  Dibuat: 2026
-- ============================================================

CREATE DATABASE IF NOT EXISTS `lost_found_db`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `lost_found_db`;

-- ── Tabel: users ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
    `id`         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(100)    NOT NULL,
    `email`      VARCHAR(150)    NOT NULL UNIQUE,
    `password`   VARCHAR(255)    NOT NULL,
    `role`       ENUM('petugas','pelapor') NOT NULL DEFAULT 'pelapor',
    `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_email` (`email`),
    INDEX `idx_role`  (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Tabel: barang_temuan ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS `barang_temuan` (
    `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `petugas_id`      INT UNSIGNED    NOT NULL,
    `nama_barang`     VARCHAR(150)    NOT NULL,
    `deskripsi`       TEXT,
    `lokasi`          VARCHAR(200)    NOT NULL,
    `waktu_temuan`    DATETIME        NOT NULL,
    `foto_path`       VARCHAR(255)    DEFAULT NULL,
    `catatan_selesai` TEXT            DEFAULT NULL,
    `status`          ENUM('tersimpan','dicocokkan','diserahkan','selesai') NOT NULL DEFAULT 'tersimpan',
    `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`      DATETIME        DEFAULT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_bt_petugas` FOREIGN KEY (`petugas_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
    INDEX `idx_bt_status`  (`status`),
    INDEX `idx_bt_lokasi`  (`lokasi`),
    INDEX `idx_bt_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Tabel: laporan_kehilangan ─────────────────────────────────
CREATE TABLE IF NOT EXISTS `laporan_kehilangan` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `pelapor_id`    INT UNSIGNED    NOT NULL,
    `nama_barang`   VARCHAR(150)    NOT NULL,
    `deskripsi`     TEXT,
    `lokasi`        VARCHAR(200)    NOT NULL,
    `waktu_hilang`  DATETIME        NOT NULL,
    `status`        ENUM('menunggu','dicocokkan','selesai','ditutup') NOT NULL DEFAULT 'menunggu',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_lk_pelapor` FOREIGN KEY (`pelapor_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
    INDEX `idx_lk_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Tabel: pencocokan ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `pencocokan` (
    `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `barang_temuan_id`  INT UNSIGNED    NOT NULL,
    `laporan_id`        INT UNSIGNED    NOT NULL,
    `petugas_id`        INT UNSIGNED    NOT NULL,
    `status`            ENUM('pending','diverifikasi','selesai','dibatalkan') NOT NULL DEFAULT 'pending',
    `catatan`           TEXT,
    `waktu_serah`       DATETIME        DEFAULT NULL,
    `created_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_p_barang`   FOREIGN KEY (`barang_temuan_id`) REFERENCES `barang_temuan`(`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_p_laporan`  FOREIGN KEY (`laporan_id`)       REFERENCES `laporan_kehilangan`(`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_p_petugas`  FOREIGN KEY (`petugas_id`)       REFERENCES `users`(`id`) ON DELETE RESTRICT,
    INDEX `idx_p_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Tabel: jadwal_pengambilan ─────────────────────────────────
CREATE TABLE IF NOT EXISTS `jadwal_pengambilan` (
    `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `match_id`           INT UNSIGNED NOT NULL,
    `pelapor_id`         INT UNSIGNED NOT NULL,
    `petugas_id`         INT UNSIGNED DEFAULT NULL,
    `waktu_jadwal`       DATETIME NOT NULL,
    `lokasi_pengambilan` VARCHAR(200) NOT NULL,
    `catatan`            TEXT,
    `status`             ENUM('menunggu_persetujuan','disetujui','ditolak','dibatalkan','selesai') NOT NULL DEFAULT 'menunggu_persetujuan',
    `completed_at`       DATETIME DEFAULT NULL,
    `created_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_jp_match` FOREIGN KEY (`match_id`) REFERENCES `pencocokan`(`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_jp_pelapor` FOREIGN KEY (`pelapor_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_jp_petugas` FOREIGN KEY (`petugas_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_jp_match` (`match_id`),
    INDEX `idx_jp_pelapor` (`pelapor_id`),
    INDEX `idx_jp_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Tabel: chat_rooms ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `chat_rooms` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `firebase_room_id` VARCHAR(50) NOT NULL UNIQUE,
    `petugas_id` INT NOT NULL,
    `pelapor_id` INT NOT NULL,
    `laporan_id` INT NOT NULL,
    `status` ENUM('aktif', 'selesai') DEFAULT 'aktif',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`petugas_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`pelapor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`laporan_id`) REFERENCES `laporan_kehilangan`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Data Seed: akun petugas default ──────────────────────────
-- Password: petugas123 (sudah di-hash bcrypt)
INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES
('Admin Petugas',   'petugas@commuterlink.id',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'petugas'),
('Budi Santoso',    'budi@gmail.com',            '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'pelapor');
-- Kedua akun seed memakai password: "password"
