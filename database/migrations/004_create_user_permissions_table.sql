-- ============================================================
-- Migration 004: Create user_permissions table
-- Overrides izin di tingkat user. (RBAC Lapis 2)
-- Menang dari role_permissions.
-- ============================================================

CREATE TABLE IF NOT EXISTS `izin_pengguna` (
    `id_pengguna`    INT UNSIGNED NOT NULL,
    `kunci_halaman`   VARCHAR(100) NOT NULL COMMENT 'Kunci halaman dari config/permissions.php',
    `diizinkan` TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id_pengguna`, `kunci_halaman`),
    FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
