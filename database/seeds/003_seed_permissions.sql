-- ============================================================
-- Seed 003: Permissions
-- Memberikan akses default ke role tertentu.
--
-- Admin (ID 1) mendapat semua akses.
-- Owner (ID 2) mendapat dashboard, laporan owner, dan rekapitulasi.
-- Mandor (ID 3) mendapat absensi, produksi, lembur, kasbon, dan rekapitulasi.
-- ============================================================

INSERT INTO `izin_peran` (`id_peran`, `kunci_halaman`, `diizinkan`) VALUES
-- Admin
(1, 'dashboard', 1),
(1, 'employees', 1),
(1, 'product_groups', 1),
(1, 'products', 1),
(1, 'users_roles', 1),
(1, 'app_settings', 1),
(1, 'attendance', 1),
(1, 'production', 1),
(1, 'overtime', 1),
(1, 'debts', 1),
(1, 'savings', 1),
(1, 'payroll', 1),
(1, 'payroll_history', 1),
(1, 'reports_owner', 1),
(1, 'rekap', 1),

-- Owner
(2, 'dashboard', 1),
(2, 'reports_owner', 1),
(2, 'rekap', 1),

-- Mandor
(3, 'dashboard', 1),
(3, 'attendance', 1),
(3, 'production', 1),
(3, 'overtime', 1),
(3, 'debts', 1),
(3, 'rekap', 1)
ON DUPLICATE KEY UPDATE `diizinkan` = VALUES(`diizinkan`);
