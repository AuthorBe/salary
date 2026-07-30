ALTER TABLE produksi ADD COLUMN lembur_kuantitas INT NOT NULL DEFAULT 0 AFTER kuantitas_bal;
ALTER TABLE produksi ADD COLUMN lembur_kuantitas_bal INT NOT NULL DEFAULT 0 AFTER lembur_kuantitas;
ALTER TABLE rincian_penggajian ADD COLUMN total_upah_lembur DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER total_upah_produksi;
