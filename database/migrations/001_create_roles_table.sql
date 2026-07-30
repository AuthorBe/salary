-- ============================================================
-- Migration 001: Create roles table
-- Menyimpan daftar role yang ada di aplikasi (Admin, Owner, dll).
--
-- Kenapa terpisah dari users?
-- Fleksibilitas: bisa tambah role baru tanpa ubah schema users.
-- ============================================================

CREATE TABLE IF NOT EXISTS `peran` (
    `id`         INT UNSIGNED     AUTO_INCREMENT PRIMARY KEY,
    `name`       VARCHAR(50)      NOT NULL UNIQUE COMMENT 'Nama role: Admin, Owner, dll',
    `created_at` TIMESTAMP        DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
