-- ============================================================
--  Migration: Add soft delete & catatan_selesai to barang_temuan
--  File  : migration_add_found_items_soft_delete.sql
--  Date  : 2026-03-10
-- ============================================================

USE `lost_found_db`;

-- ── Modify ENUM status: tambah nilai 'selesai' ──────────────────────────
ALTER TABLE `barang_temuan`
MODIFY COLUMN `status` ENUM('tersimpan','dicocokkan','diserahkan','selesai')
NOT NULL DEFAULT 'tersimpan';

-- ── Add catatan_selesai column ──────────────────────────────────────────
ALTER TABLE `barang_temuan`
ADD COLUMN `catatan_selesai` TEXT NULL DEFAULT NULL
AFTER `foto_path`;

-- ── Add deleted_at column untuk soft delete ─────────────────────────────
ALTER TABLE `barang_temuan`
ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL
AFTER `updated_at`;

-- ── Add index untuk soft delete query ───────────────────────────────────
ALTER TABLE `barang_temuan`
ADD INDEX `idx_bt_deleted_at` (`deleted_at`);

-- ============================================================
--  Verifikasi struktur tabel
-- ============================================================
-- DESCRIBE `barang_temuan`;
-- SHOW COLUMNS FROM `barang_temuan`;

