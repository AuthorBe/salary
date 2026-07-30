-- ============================================================
-- Migration 003: Create role_permissions table
-- Menyimpan daftar izin default untuk masing-masing role.
-- ============================================================

CREATE TABLE IF NOT EXISTS `izin_peran` (
    `id_peran`    INT UNSIGNED NOT NULL,
    `kunci_halaman`   VARCHAR(100) NOT NULL COMMENT 'Kunci halaman dari config/permissions.php',
    `diizinkan` TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id_peran`, `kunci_halaman`),
    FOREIGN KEY (`id_peran`) REFERENCES `peran`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
