<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

class Production
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    /**
     * Ambil data produksi berdasarkan tanggal.
     * Mengembalikan struktur array terkelompok: 
     * [id_karyawan] => [id_produk => kuantitas]
     */
    public function getByDate(string $date): array
    {
        $stmt = $this->db->prepare("
            SELECT id_karyawan, id_produk, kuantitas, kuantitas_bal
            FROM produksi
            WHERE date = ?
        ");
        $stmt->execute([$date]);
        
        $result = [];
        while ($row = $stmt->fetch()) {
            $empId = $row['id_karyawan'];
            if (!isset($result[$empId])) {
                $result[$empId] = [];
            }
            $result[$empId][$row['id_produk']] = [
                'kuantitas' => (int) $row['kuantitas'],
                'kuantitas_bal' => (int) $row['kuantitas_bal']
            ];
        }
        return $result;
    }

    /**
     * Simpan produksi borongan secara masal.
     * $data format: [id_karyawan => [id_produk => kuantitas]]
     * $balData format: [id_karyawan => [id_produk => kuantitas_bal]]
     */
    public function saveBulk(string $date, array $data, array $balData): void
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO produksi (id_karyawan, date, id_produk, kuantitas, kuantitas_bal)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE kuantitas = VALUES(kuantitas), kuantitas_bal = VALUES(kuantitas_bal)
            ");
            
            // Loop per employee
            foreach ($data as $empId => $products) {
                // Loop per product yang diinput employee tersebut
                foreach ($products as $productId => $quantity) {
                    $qty = (int) $quantity;
                    $balQty = isset($balData[$empId][$productId]) ? (int) $balData[$empId][$productId] : 0;
                    
                    // Jangan simpan kalau qty 0 dan bal 0 (mengurangi beban db)
                    if ($qty > 0 || $balQty > 0) {
                        $stmt->execute([$empId, $date, $productId, $qty, $balQty]);
                    } else {
                        // Jika ada data lama yang diset jadi 0, maka kita hapus
                        $stmtDel = $this->db->prepare("DELETE FROM produksi WHERE id_karyawan = ? AND date = ? AND id_produk = ?");
                        $stmtDel->execute([$empId, $date, $productId]);
                    }
                }
            }
            
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function checkLockStatus(string $date, int $employeeId): void
    {
        $stmt = $this->db->prepare("
            SELECT pg.status 
            FROM produksi p
            JOIN penggajian pg ON p.id_penggajian = pg.id
            WHERE p.id_karyawan = ? AND p.date = ? 
            LIMIT 1
        ");
        $stmt->execute([$employeeId, $date]);
        $status = $stmt->fetchColumn();

        if ($status) {
            if ($status === 'draft') {
                throw new \Exception("Data terkunci di Draft Payroll. Silakan hapus draft payroll tersebut terlebih dahulu.");
            } elseif ($status === 'approved') {
                throw new \Exception("Data sudah masuk ke Payroll yang disetujui (Final). Anda harus melakukan Batal Approve pada riwayat payroll jika benar-benar ingin mengubahnya.");
            } else {
                throw new \Exception("Data produksi pada tanggal ini sudah Terkunci karena telah masuk ke proses Penggajian (Payroll).");
            }
        }
    }

    /**
     * Simpan produksi untuk 1 karyawan tertentu di tanggal tertentu.
     * $items format: [['id_produk' => int, 'kuantitas' => int, 'kuantitas_bal' => int]]
     */
    public function saveEmployeeProduction(string $date, int $employeeId, array $items, bool $isOvertime = false): void
    {
        $this->db->beginTransaction();
        try {
            // PROTEKSI: Cek apakah data pada tanggal ini sudah dikunci (masuk payroll)
            $this->checkLockStatus($date, $employeeId);
            if ($isOvertime) {
                $stmtIns = $this->db->prepare("
                    INSERT INTO produksi (id_karyawan, date, id_produk, lembur_kuantitas, lembur_kuantitas_bal)
                    VALUES (?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                        lembur_kuantitas = lembur_kuantitas + VALUES(lembur_kuantitas), 
                        lembur_kuantitas_bal = lembur_kuantitas_bal + VALUES(lembur_kuantitas_bal)
                ");
            } else {
                $stmtIns = $this->db->prepare("
                    INSERT INTO produksi (id_karyawan, date, id_produk, kuantitas, kuantitas_bal)
                    VALUES (?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                        kuantitas = kuantitas + VALUES(kuantitas), 
                        kuantitas_bal = kuantitas_bal + VALUES(kuantitas_bal)
                ");
            }
            
            $stmtDelSingle = $this->db->prepare("DELETE FROM produksi WHERE id_karyawan = ? AND date = ? AND id_produk = ?");

            foreach ($items as $item) {
                $productId = (int) ($item['id_produk'] ?? 0);
                $qty       = (int) ($item['kuantitas'] ?? 0);
                $balQty    = (int) ($item['kuantitas_bal'] ?? 0);

                if ($productId > 0) {
                    if ($qty > 0 || $balQty > 0) {
                        $stmtIns->execute([$employeeId, $date, $productId, $qty, $balQty]);
                    } else {
                        $stmtDelSingle->execute([$employeeId, $date, $productId]);
                    }
                }
            }

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updateEmployeeProduction(string $date, int $employeeId, array $items): void
    {
        $this->db->beginTransaction();
        try {
            $this->checkLockStatus($date, $employeeId);
            
            // Delete all existing un-locked records for this date & employee
            $stmtDel = $this->db->prepare("DELETE FROM produksi WHERE id_karyawan = ? AND date = ?");
            $stmtDel->execute([$employeeId, $date]);

            $stmtIns = $this->db->prepare("
                INSERT INTO produksi (id_karyawan, date, id_produk, kuantitas, kuantitas_bal)
                VALUES (?, ?, ?, ?, ?)
            ");

            foreach ($items as $item) {
                $productId = (int) ($item['id_produk'] ?? 0);
                $qty       = (int) ($item['kuantitas'] ?? 0);
                $balQty    = (int) ($item['kuantitas_bal'] ?? 0);

                if ($productId > 0 && ($qty > 0 || $balQty > 0)) {
                    $stmtIns->execute([$employeeId, $date, $productId, $qty, $balQty]);
                }
            }

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Ambil rincian kuantitas produk per karyawan untuk form edit
     */
    public function getEmployeeProductionForEdit(string $date, int $employeeId): array
    {
        $stmt = $this->db->prepare("
            SELECT id_produk, SUM(kuantitas) as total_qty, SUM(kuantitas_bal) as total_bal
            FROM produksi
            WHERE id_karyawan = ? AND date = ?
            GROUP BY id_produk
        ");
        $stmt->execute([$employeeId, $date]);
        
        $result = [];
        while ($row = $stmt->fetch()) {
            $result[$row['id_produk']] = [
                'qty' => (int) $row['total_qty'],
                'bal' => (int) $row['total_bal']
            ];
        }
        return $result;
    }

    /**
     * Hapus semua catatan produksi karyawan tertentu pada tanggal tertentu.
     */
    public function deleteEmployeeProduction(string $date, int $employeeId): void
    {
        // PROTEKSI: Cek apakah data pada tanggal ini sudah dikunci
        $this->checkLockStatus($date, $employeeId);
        
        $stmt = $this->db->prepare("DELETE FROM produksi WHERE id_karyawan = ? AND date = ?");
        $stmt->execute([$employeeId, $date]);
    }

    /**
     * Ambil riwayat produksi dengan filter rentang tanggal dan opsional karyawan.
     * Mengembalikan data terkelompok per tanggal dan karyawan.
     */
    public function getHistory(string $startDate, string $endDate, int $employeeId = 0): array
    {
        $sql = "
            SELECT p.date, p.id_karyawan, k.name as nama_karyawan, p.id_produk, pr.name as nama_produk, p.kuantitas, p.kuantitas_bal
            FROM produksi p
            JOIN karyawan k ON p.id_karyawan = k.id
            JOIN produk pr ON p.id_produk = pr.id
            WHERE p.date >= ? AND p.date <= ?
        ";
        
        $params = [$startDate, $endDate];
        
        if ($employeeId > 0) {
            $sql .= " AND p.id_karyawan = ?";
            $params[] = $employeeId;
        }
        
        $sql .= " ORDER BY p.date DESC, k.name ASC, pr.name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $result = [];
        while ($row = $stmt->fetch()) {
            $date = $row['date'];
            $empId = $row['id_karyawan'];
            
            $key = $date . '_' . $empId;
            
            if (!isset($result[$key])) {
                $result[$key] = [
                    'date'          => $date,
                    'id_karyawan'   => $empId,
                    'nama_karyawan' => $row['nama_karyawan'],
                    'products'      => []
                ];
            }
            
            $result[$key]['products'][] = [
                'id_produk'     => $row['id_produk'],
                'nama_produk'   => $row['nama_produk'],
                'kuantitas'     => (int) $row['kuantitas'],
                'kuantitas_bal' => (int) $row['kuantitas_bal']
            ];
        }
        
        return array_values($result);
    }
}
