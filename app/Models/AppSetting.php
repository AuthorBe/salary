<?php
declare(strict_types=1);

namespace App\Models;

/**
 * AppSetting Model
 * Mengelola konfigurasi global aplikasi (disimpan di database).
 */
class AppSetting
{
    /**
     * Ambil semua setting dalam format key => value
     */
    public function getAll(): array
    {
        $db = getDB();
        $stmt = $db->query("SELECT kunci_pengaturan, nilai_pengaturan FROM pengaturan_aplikasi");
        $results = $stmt->fetchAll();
        
        $settings = [];
        foreach ($results as $row) {
            $settings[$row['kunci_pengaturan']] = $row['nilai_pengaturan'];
        }
        
        return $settings;
    }

    /**
     * Ambil satu setting berdasarkan key. Return default jika tidak ada.
     */
    public function get(string $key, $default = null): ?string
    {
        $db = getDB();
        $stmt = $db->prepare("SELECT nilai_pengaturan FROM pengaturan_aplikasi WHERE kunci_pengaturan = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        
        if ($result) {
            return $result['nilai_pengaturan'];
        }
        
        return $default;
    }

    /**
     * Update atau Insert setting.
     */
    public function set(string $key, string $value): void
    {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO pengaturan_aplikasi (kunci_pengaturan, nilai_pengaturan) 
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE nilai_pengaturan = VALUES(nilai_pengaturan), updated_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([$key, $value]);
    }
}
