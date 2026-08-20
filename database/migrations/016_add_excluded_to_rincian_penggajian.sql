ALTER TABLE rincian_penggajian ADD COLUMN is_excluded TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE rincian_penggajian ADD COLUMN catatan_pengecualian TEXT NULL;
