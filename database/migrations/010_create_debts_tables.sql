CREATE TABLE IF NOT EXISTS `kasbon` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `id_karyawan` INT NOT NULL,
    `keterangan` VARCHAR(255) NULL,
    `total_nominal` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    `potongan_bawaan` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    `sisa_nominal` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    `status` ENUM('active', 'paid_off', 'cancelled') NOT NULL DEFAULT 'active',
    `catatan` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_debt_employee FOREIGN KEY (`id_karyawan`) REFERENCES `karyawan`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `potongan_kasbon` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `id_kasbon` INT NOT NULL,
    `id_rincian_penggajian` INT NULL DEFAULT NULL,
    `nominal` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    `tanggal_potongan` DATE NOT NULL,
    `type` ENUM('payroll', 'manual') NOT NULL DEFAULT 'manual',
    `catatan` VARCHAR(255) NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_deduction_debt FOREIGN KEY (`id_kasbon`) REFERENCES `kasbon`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
