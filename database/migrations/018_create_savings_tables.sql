-- Migration: 018_create_savings_tables.sql
-- Description: Create tables for Tabungan feature and update rincian_penggajian

-- 1. Create tabungan table
CREATE TABLE IF NOT EXISTS `tabungan` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `id_karyawan` INT NOT NULL UNIQUE,
    `saldo` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`id_karyawan`) REFERENCES `karyawan`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Create transaksi_tabungan table
CREATE TABLE IF NOT EXISTS `transaksi_tabungan` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `id_karyawan` INT NOT NULL,
    `tipe` ENUM('deposit', 'withdrawal') NOT NULL,
    `jumlah` DECIMAL(15, 2) NOT NULL,
    `sumber` ENUM('payroll', 'manual') NOT NULL,
    `id_rincian_penggajian` INT NULL,
    `tanggal` DATE NOT NULL,
    `keterangan` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`id_karyawan`) REFERENCES `karyawan`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`id_rincian_penggajian`) REFERENCES `rincian_penggajian`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Modify rincian_penggajian table
ALTER TABLE `rincian_penggajian`
ADD COLUMN `potongan_tabungan` DECIMAL(15, 2) NOT NULL DEFAULT 0.00 AFTER `total_potongan_kasbon`,
ADD COLUMN `penarikan_tabungan` DECIMAL(15, 2) NOT NULL DEFAULT 0.00 AFTER `potongan_tabungan`;
