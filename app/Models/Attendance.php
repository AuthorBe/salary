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
            SELECT id_karyawan, hadir, telat, ambil_uang, catatan, lembur_nominal
            FROM absensi
            WHERE date = ?
        ");
        $stmt->execute([$date]);
        
        $result = [];
        while ($row = $stmt->fetch()) {
            $result[$row['id_karyawan']] = [
                'hadir'          => (bool) $row['hadir'],
                'telat'          => (bool) $row['telat'],
                'ambil_uang'     => (bool) ($row['ambil_uang'] ?? false),
                'catatan'        => $row['catatan'] ?? '',
                'lembur_nominal' => (int) ($row['lembur_nominal'] ?? 0)
            ];
        }
        return $result;
    }

    /**
     * Simpan kehadiran (bulk) menggunakan INSERT ... ON DUPLICATE KEY UPDATE.
     * Karyawan tidak hadir yang keterangannya kosong akan otomatis diberi catatan 'Alfa'.
     */
    public function saveBulk(string $date, array $employeeIds, array $presentIds, array $notesMap = [], array $telatIds = [], array $ambilUangIds = []): void
    {
        $inTransaction = $this->db->inTransaction();
        if (!$inTransaction) {
            $this->db->beginTransaction();
        }
        try {
            // PROTEKSI: Cek apakah data karyawan yang disubmit pada tanggal ini sudah dikunci (masuk payroll)
            if (count($employeeIds) > 0) {
                $placeholders = implode(',', array_fill(0, count($employeeIds), '?'));
                $checkLock = $this->db->prepare("SELECT COUNT(*) FROM absensi WHERE date = ? AND id_karyawan IN ($placeholders) AND id_penggajian IS NOT NULL");
                $params = array_merge([$date], $employeeIds);
                $checkLock->execute($params);
                if ($checkLock->fetchColumn() > 0) {
                    throw new \Exception("Sebagian atau seluruh data absensi karyawan ini sudah masuk ke proses Penggajian (Payroll) sehingga terkunci.");
                }
            }
            $stmt = $this->db->prepare("
                INSERT INTO absensi (id_karyawan, date, hadir, telat, ambil_uang, catatan)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    hadir = VALUES(hadir),
                    telat = VALUES(telat),
                    ambil_uang = VALUES(ambil_uang),
                    catatan = VALUES(catatan)
            ");
            
            foreach ($employeeIds as $empId) {
                $isPresent = in_array($empId, $presentIds) ? 1 : 0;
                $rawNotes  = trim((string)($notesMap[$empId] ?? ''));
                // Telat dan Ambil Uang hanya valid jika karyawan hadir
                $isTelat   = ($isPresent === 1 && in_array($empId, $telatIds)) ? 1 : 0;
                $isAmbilUang = ($isPresent === 1 && in_array($empId, $ambilUangIds)) ? 1 : 0;
                
                if ($isPresent === 1) {
                    $notes = null; // Karyawan hadir
                } else {
                    // Karyawan tidak hadir: jika keterangan kosong, otomatis diisi 'Alfa'
                    $notes = ($rawNotes === '') ? 'Alfa' : $rawNotes;
                }
                
                $stmt->execute([$empId, $date, $isPresent, $isTelat, $isAmbilUang, $notes]);
            }
            
            if (!$inTransaction) {
                $this->db->commit();
            }
        } catch (\Exception $e) {
            if (!$inTransaction) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Simpan kehadiran dan lembur untuk satu karyawan (dipakai di Input Lembur).
     */
    public function saveSingle(int $employeeId, string $date, int $isPresent, string $notes, int $lemburNominal): void
    {
        // PROTEKSI: Cek apakah data pada tanggal ini sudah dikunci (masuk payroll)
        $checkLock = $this->db->prepare("SELECT COUNT(*) FROM absensi WHERE date = ? AND id_karyawan = ? AND id_penggajian IS NOT NULL");
        $checkLock->execute([$date, $employeeId]);
        if ($checkLock->fetchColumn() > 0) {
            throw new \Exception("Data absensi pada tanggal ini sudah Terkunci karena telah masuk ke proses Penggajian (Payroll).");
        }

        if ($isPresent === 1) {
            $notes = null;
        } else {
            if (trim($notes) === '') {
                $notes = 'Alfa';
            }
        }

        $stmt = $this->db->prepare("
            INSERT INTO absensi (id_karyawan, date, hadir, catatan, lembur_nominal)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                hadir = VALUES(hadir),
                catatan = VALUES(catatan),
                lembur_nominal = VALUES(lembur_nominal)
        ");
        $stmt->execute([$employeeId, $date, $isPresent, $notes, $lemburNominal]);
    }

    /**
     * Simpan nominal lembur bulanan untuk satu karyawan (dipakai di Input Lembur Bulanan).
     * Mempertahankan data absensi (hadir, telat, catatan) yang sudah ada sebelumnya.
     */
    public function saveMonthlyOvertime(int $employeeId, string $date, int $lemburNominal): void
    {
        // PROTEKSI: Cek apakah data pada tanggal ini sudah dikunci (masuk payroll)
        $checkLock = $this->db->prepare("SELECT COUNT(*) FROM absensi WHERE date = ? AND id_karyawan = ? AND id_penggajian IS NOT NULL");
        $checkLock->execute([$date, $employeeId]);
        if ($checkLock->fetchColumn() > 0) {
            throw new \Exception("Data absensi pada tanggal ini sudah Terkunci karena telah masuk ke proses Penggajian (Payroll).");
        }

        // Cek apakah sudah ada data absensi untuk karyawan ini di tanggal ini
        $stmtCheck = $this->db->prepare("SELECT hadir, telat, catatan FROM absensi WHERE id_karyawan = ? AND date = ?");
        $stmtCheck->execute([$employeeId, $date]);
        $existing = $stmtCheck->fetch();

        if ($existing) {
            // Jika sudah ada record: jika sebelumnya tidak hadir (hadir = 0), ubah ke hadir = 1 dan bersihkan catatan alfa/izin.
            // Jika sebelumnya sudah hadir, pertahankan status telat & catatan yang ada.
            $hadir = 1;
            $catatan = ($existing['hadir'] == 1) ? $existing['catatan'] : null;
            $telat = (int) $existing['telat'];

            $stmt = $this->db->prepare("
                UPDATE absensi 
                SET hadir = ?, telat = ?, catatan = ?, lembur_nominal = ?
                WHERE id_karyawan = ? AND date = ?
            ");
            $stmt->execute([$hadir, $telat, $catatan, $lemburNominal, $employeeId, $date]);
        } else {
            // Jika belum ada record absensi: buat record baru dengan hadir = 1
            $stmt = $this->db->prepare("
                INSERT INTO absensi (id_karyawan, date, hadir, telat, catatan, lembur_nominal)
                VALUES (?, ?, 1, 0, NULL, ?)
            ");
            $stmt->execute([$employeeId, $date, $lemburNominal]);
        }
    }
}
