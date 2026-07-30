-- ============================================================
-- Migration 002: Create users table
-- Menyimpan akun login karyawan/admin.
--
-- Catatan penting:
--   - nama_pengguna: alfanumerik saja (sesuai PRD), bukan email
--   - kata_sandi: WAJIB bcrypt (kata_sandi PHP), tidak pernah plain text
--   - aktif: untuk soft-disable akun tanpa hapus data
--   - id_peran: FK ke roles — 1 user hanya bisa 1 role
-- ============================================================

CREATE TABLE IF NOT EXISTS `pengguna` (
    `id`            INT UNSIGNED     AUTO_INCREMENT PRIMARY KEY,
    `name`          VARCHAR(100)     NOT NULL       COMMENT 'Nama lengkap user',
    `nama_pengguna`      VARCHAR(50)      NOT NULL UNIQUE COMMENT 'Alfanumerik, min 3 karakter',
    `kata_sandi` VARCHAR(255)     NOT NULL       COMMENT 'Bcrypt hash — TIDAK PERNAH plain text',
    `id_peran`       INT UNSIGNED            COMMENT 'FK ke tabel roles, NULL untuk superuser',
    `aktif`     TINYINT(1)       NOT NULL DEFAULT 1 COMMENT '1=aktif, 0=nonaktif/diblokir',
    `superuser`  TINYINT(1)       NOT NULL DEFAULT 0 COMMENT 'Developer flag: bypass all permissions',
    `created_at`    TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP        DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (`id_peran`) REFERENCES `peran`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
