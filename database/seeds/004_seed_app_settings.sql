-- ============================================================
-- Seed 004: App Settings
-- ============================================================

INSERT IGNORE INTO `app_settings` (`setting_key`, `setting_value`, `description`) VALUES
('company_name', 'Bintang Harapan', 'Nama perusahaan untuk laporan'),
('week_start_day', '1', 'Hari mulai mingguan (0=Minggu, 1=Senin, dst)'),
('timezone', 'Asia/Jakarta', 'Zona waktu standar aplikasi');
