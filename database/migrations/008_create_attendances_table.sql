CREATE TABLE IF NOT EXISTS `absensi` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `id_karyawan` INT NOT NULL,
    `date` DATE NOT NULL,
    `hadir` TINYINT(1) NOT NULL DEFAULT 1,
    `catatan` VARCHAR(255) NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_attendance_employee FOREIGN KEY (`id_karyawan`) REFERENCES `karyawan`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_employee_date` (`id_karyawan`, `date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
