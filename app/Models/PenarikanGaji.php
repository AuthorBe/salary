<?php
declare(strict_types=1);

namespace App\Models;

/**
 * PenarikanGaji Model
 * Mengelola data Penarikan Gaji untuk Karyawan Bulanan.
 */
class PenarikanGaji
{
    /**
     * Ambil semua data penarikan beserta informasi karyawan.
     */
    public function getAll(array $filters = []): array
    {
        $db = getDB();
        $sql = "
            SELECT p.*, e.name AS employee_name
            FROM penarikan_gaji p
            JOIN karyawan e ON p.id_karyawan = e.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['id_karyawan'])) {
            $sql .= " AND p.id_karyawan = ?";
            $params[] = (int) $filters['id_karyawan'];
        }

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'pending') {
                $sql .= " AND p.id_penggajian IS NULL";
            } elseif ($filters['status'] === 'applied') {
                $sql .= " AND p.id_penggajian IS NOT NULL";
            }
        }

        if (!empty($filters['start_date'])) {
            $sql .= " AND p.tanggal >= ?";
            $params[] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $sql .= " AND p.tanggal <= ?";
            $params[] = $filters['end_date'];
        }

        $sql .= " ORDER BY p.tanggal DESC, p.id DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Dapatkan sisa limit penarikan untuk seorang karyawan di bulan tertentu.
     * Limit = Gaji Pokok + Tunjangan Bulanan + (Kehadiran bulan ini * Uang Kehadiran) - Penarikan yang sudah ada di bulan yang sama.
     */
    public function getAvailableLimit(int $empId, string $date = null): array
    {
        $db = getDB();
        if ($date === null) {
            $date = date('Y-m-d');
        }
        $month = date('m', strtotime($date));
        $year = date('Y', strtotime($date));

        // 1. Dapatkan info karyawan (join ke pengaturan_gaji_bulanan jika ada)
        $stmtEmp = $db->prepare("
            SELECT k.uang_kehadiran_harian, m.gaji_pokok, k.tunjangan_bulanan
            FROM karyawan k
            LEFT JOIN pengaturan_gaji_bulanan m ON k.id = m.id_karyawan
            WHERE k.id = ? AND k.tipe_gaji = 'bulanan'
        ");
        $stmtEmp->execute([$empId]);
        $emp = $stmtEmp->fetch();

        if (!$emp) {
            return [
                'limit' => 0,
                'gaji_pokok' => 0,
                'tunjangan' => 0,
                'total_uang_kehadiran' => 0,
                'total_pemasukan' => 0,
                'sudah_ditarik' => 0
            ];
        }

        $gajiPokok = (float)$emp['gaji_pokok'];
        $tunjangan = (float)$emp['tunjangan_bulanan'];
        $uangHadir = (float)$emp['uang_kehadiran_harian'];

        // 2. Hitung jumlah kehadiran di bulan ini
        $stmtAtt = $db->prepare("
            SELECT COUNT(*) FROM absensi 
            WHERE id_karyawan = ? AND hadir = 1 
            AND MONTH(date) = ? AND YEAR(date) = ?
        ");
        $stmtAtt->execute([$empId, $month, $year]);
        $daysHadir = (int)$stmtAtt->fetchColumn();

        $totalHadir = $daysHadir * $uangHadir;
        
        $totalPemasukan = $gajiPokok + $tunjangan + $totalHadir;

        // 3. Hitung penarikan yang sudah dilakukan di bulan ini
        $stmtPenarikan = $db->prepare("
            SELECT SUM(nominal) FROM penarikan_gaji 
            WHERE id_karyawan = ? 
            AND MONTH(tanggal) = ? AND YEAR(tanggal) = ?
        ");
        $stmtPenarikan->execute([$empId, $month, $year]);
        $sudahDitarik = (float)$stmtPenarikan->fetchColumn();

        $sisaLimit = $totalPemasukan - $sudahDitarik;

        return [
            'limit' => max(0, $sisaLimit),
            'gaji_pokok' => $gajiPokok,
            'tunjangan' => $tunjangan,
            'total_uang_kehadiran' => $totalHadir,
            'total_pemasukan' => $totalPemasukan,
            'sudah_ditarik' => $sudahDitarik
        ];
    }

    /**
     * Buat penarikan baru
     */
    public function store(array $data): void
    {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO penarikan_gaji (id_karyawan, tanggal, nominal, keterangan)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['id_karyawan'],
            $data['tanggal'],
            $data['nominal'],
            $data['keterangan'] ?? null
        ]);
    }

    /**
     * Hapus penarikan
     */
    public function delete(int $id): bool
    {
        $db = getDB();
        // Hanya bisa hapus jika belum ditarik oleh payroll (id_penggajian IS NULL)
        $stmt = $db->prepare("DELETE FROM penarikan_gaji WHERE id = ? AND id_penggajian IS NULL");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Ambil data penarikan yang belum diproses untuk seorang karyawan
     */
    public function getPendingByEmployee(int $empId, string $periodEnd): array
    {
        $db = getDB();
        // Ambil yang id_penggajian IS NULL dan tanggal <= periode_akhir
        $stmt = $db->prepare("
            SELECT * FROM penarikan_gaji
            WHERE id_karyawan = ? AND id_penggajian IS NULL AND tanggal <= ?
            ORDER BY tanggal ASC
        ");
        $stmt->execute([$empId, $periodEnd]);
        return $stmt->fetchAll();
    }
}
