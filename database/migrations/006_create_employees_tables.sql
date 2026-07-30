-- 006_create_employees_tables.sql
-- Membuat tabel untuk data master karyawan dan pengaturannya

CREATE TABLE IF NOT EXISTS karyawan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    tipe_gaji ENUM('borongan', 'bulanan', 'harian') NOT NULL,
    uang_kehadiran_harian INT NOT NULL DEFAULT 0,
    tunjangan_bulanan INT NOT NULL DEFAULT 0,
    aktif TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pengaturan_gaji_bulanan (
    id_karyawan INT PRIMARY KEY,
    gaji_pokok INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_monthly_salary_employee FOREIGN KEY (id_karyawan) REFERENCES karyawan(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pengaturan_upah_harian (
    id_karyawan INT PRIMARY KEY,
    upah_harian INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_daily_rate_employee FOREIGN KEY (id_karyawan) REFERENCES karyawan(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
