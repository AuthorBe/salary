-- ============================================================
-- Migration 005: Create app_settings table
-- Menyimpan konfigurasi global aplikasi.
-- ============================================================

CREATE TABLE IF NOT EXISTS `pengaturan_aplikasi` (
    `kunci_pengaturan`   VARCHAR(50)  NOT NULL PRIMARY KEY,
    `nilai_pengaturan` TEXT         NULL,
    `keterangan`   VARCHAR(255) NULL,
    `updated_at`    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
