CREATE TABLE IF NOT EXISTS `produksi` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `id_karyawan` INT NOT NULL,
    `date` DATE NOT NULL,
    `id_produk` INT NOT NULL,
    `kuantitas` INT NOT NULL DEFAULT 0,
    `kuantitas_bal` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_production_employee FOREIGN KEY (`id_karyawan`) REFERENCES `karyawan`(`id`) ON DELETE CASCADE,
    CONSTRAINT fk_production_product FOREIGN KEY (`id_produk`) REFERENCES `produk`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_employee_date_product` (`id_karyawan`, `date`, `id_produk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
