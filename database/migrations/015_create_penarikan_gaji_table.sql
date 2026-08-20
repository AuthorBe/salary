CREATE TABLE IF NOT EXISTS penarikan_gaji (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_karyawan INT NOT NULL,
    id_penggajian INT NULL,
    tanggal DATE NOT NULL,
    nominal DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    keterangan VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_karyawan) REFERENCES karyawan(id) ON DELETE CASCADE,
    FOREIGN KEY (id_penggajian) REFERENCES penggajian(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE rincian_penggajian 
ADD COLUMN total_penarikan_gaji DECIMAL(15, 2) NOT NULL DEFAULT 0.00 AFTER total_potongan_kasbon;
