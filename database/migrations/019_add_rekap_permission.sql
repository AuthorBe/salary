-- ============================================================
-- Migration 019: Add Rekapitulasi Permission
-- Menambahkan izin 'rekap' ke tabel izin_peran untuk role:
-- Admin (1), Owner (2), dan Mandor (3).
-- ============================================================

INSERT INTO `izin_peran` (`id_peran`, `kunci_halaman`, `diizinkan`)
VALUES 
    (1, 'rekap', 1),
    (2, 'rekap', 1),
    (3, 'rekap', 1)
ON DUPLICATE KEY UPDATE `diizinkan` = VALUES(`diizinkan`);
