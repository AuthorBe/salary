-- ============================================================
-- Seed 004: App Settings (Pengaturan Aplikasi)
-- ============================================================

INSERT IGNORE INTO `pengaturan_aplikasi` (`kunci_pengaturan`, `nilai_pengaturan`, `keterangan`) VALUES
('company_name', 'Bintang Harapan', 'Nama perusahaan untuk laporan'),
('week_start_day', '1', 'Hari mulai mingguan (0=Minggu, 1=Senin, dst)'),
('timezone', 'Asia/Jakarta', 'Zona waktu standar aplikasi');
