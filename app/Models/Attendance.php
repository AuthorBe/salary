<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

class Attendance
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    /**
     * Ambil data kehadiran berdasarkan tanggal.
     * Mengembalikan array dengan key = id_karyawan.
     */
    public function getByDate(string $date): array
    {
        $stmt = $this->db->prepare("
            SELECT id_karyawan, hadir, catatan
            FROM absensi
            WHERE date = ?
        ");
        $stmt->execute([$date]);
        
        $result = [];
        while ($row = $stmt->fetch()) {
            $result[$row['id_karyawan']] = [
                'hadir' => (bool) $row['hadir'],
                'catatan'      => $row['catatan'] ?? ''
            ];
        }
        return $result;
    }

    /**
     * Simpan kehadiran (bulk) menggunakan INSERT ... ON DUPLICATE KEY UPDATE.
     * Karyawan tidak hadir yang keterangannya kosong akan otomatis diberi catatan 'Alfa'.
     */
    public function saveBulk(string $date, array $employeeIds, array $presentIds, array $notesMap = []): void
    {
        $this->db->beginTransaction();
        try {
            // PROTEKSI: Cek apakah data pada tanggal ini sudah dikunci (masuk payroll)
            $checkLock = $this->db->prepare("SELECT COUNT(*) FROM absensi WHERE date = ? AND id_penggajian IS NOT NULL");
            $checkLock->execute([$date]);
            if ($checkLock->fetchColumn() > 0) {
                throw new \Exception("Data absensi pada tanggal ini sudah Terkunci karena telah masuk ke proses Penggajian (Payroll).");
            }
            $stmt = $this->db->prepare("
                INSERT INTO absensi (id_karyawan, date, hadir, catatan)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    hadir = VALUES(hadir),
                    catatan = VALUES(catatan)
            ");
            
            foreach ($employeeIds as $empId) {
                $isPresent = in_array($empId, $presentIds) ? 1 : 0;
                $rawNotes  = trim((string)($notesMap[$empId] ?? ''));
                
                if ($isPresent === 1) {
                    $notes = null; // Karyawan hadir
                } else {
                    // Karyawan tidak hadir: jika keterangan kosong, otomatis diisi 'Alfa'
                    $notes = ($rawNotes === '') ? 'Alfa' : $rawNotes;
                }
                
                $stmt->execute([$empId, $date, $isPresent, $notes]);
            }
            
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
