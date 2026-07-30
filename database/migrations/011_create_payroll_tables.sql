CREATE TABLE IF NOT EXISTS penggajian (
    id INT AUTO_INCREMENT PRIMARY KEY,
    periode_awal DATE NOT NULL,
    periode_akhir DATE NOT NULL,
    type ENUM('weekly', 'monthly') NOT NULL,
    status ENUM('draft', 'approved') NOT NULL DEFAULT 'draft',
    disetujui_pada DATETIME NULL,
    disetujui_oleh INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (disetujui_oleh) REFERENCES pengguna(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rincian_penggajian (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_penggajian INT NOT NULL,
    id_karyawan INT NOT NULL,
    gaji_pokok DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    hari_hadir INT NOT NULL DEFAULT 0,
    total_uang_kehadiran DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    total_upah_produksi DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    tunjangan_bulanan DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    tunjangan_lain DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    catatan_tunjangan_lain VARCHAR(255) NULL,
    total_potongan_kasbon DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    potongan_lain DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    catatan_potongan_lain VARCHAR(255) NULL,
    nominal_pembulatan DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    gaji_bersih DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    rincian_json TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_penggajian) REFERENCES penggajian(id) ON DELETE CASCADE,
    FOREIGN KEY (id_karyawan) REFERENCES karyawan(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add foreign keys to attendances, productions, debt_deductions
ALTER TABLE absensi 
ADD COLUMN id_penggajian INT NULL AFTER date,
ADD FOREIGN KEY (id_penggajian) REFERENCES penggajian(id) ON DELETE SET NULL;

ALTER TABLE produksi
ADD COLUMN id_penggajian INT NULL AFTER date,
ADD FOREIGN KEY (id_penggajian) REFERENCES penggajian(id) ON DELETE SET NULL;

ALTER TABLE potongan_kasbon
ADD COLUMN id_rincian_penggajian INT NULL AFTER id_kasbon,
ADD FOREIGN KEY (id_rincian_penggajian) REFERENCES rincian_penggajian(id) ON DELETE SET NULL;
