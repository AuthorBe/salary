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

    /**
     * Simpan produksi untuk 1 karyawan tertentu di tanggal tertentu.
     * $items format: [['id_produk' => int, 'kuantitas' => int, 'kuantitas_bal' => int]]
     */
    public function saveEmployeeProduction(string $date, int $employeeId, array $items): void
    {
        $this->db->beginTransaction();
        try {
            // PROTEKSI: Cek apakah data pada tanggal ini sudah dikunci (masuk payroll)
            $checkLock = $this->db->prepare("SELECT COUNT(*) FROM produksi WHERE id_karyawan = ? AND date = ? AND id_penggajian IS NOT NULL");
            $checkLock->execute([$employeeId, $date]);
            if ($checkLock->fetchColumn() > 0) {
                throw new \Exception("Data produksi pada tanggal ini sudah Terkunci karena telah masuk ke proses Penggajian (Payroll).");
            }
            $stmtIns = $this->db->prepare("
                INSERT INTO produksi (id_karyawan, date, id_produk, kuantitas, kuantitas_bal)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    kuantitas = kuantitas + VALUES(kuantitas), 
                    kuantitas_bal = kuantitas_bal + VALUES(kuantitas_bal)
            ");
            
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

    /**
     * Hapus semua catatan produksi karyawan tertentu pada tanggal tertentu.
     */
    public function deleteEmployeeProduction(string $date, int $employeeId): void
    {
        // PROTEKSI: Cek apakah data pada tanggal ini sudah dikunci
        $checkLock = $this->db->prepare("SELECT COUNT(*) FROM produksi WHERE id_karyawan = ? AND date = ? AND id_penggajian IS NOT NULL");
        $checkLock->execute([$employeeId, $date]);
        if ($checkLock->fetchColumn() > 0) {
            throw new \Exception("Data produksi pada tanggal ini tidak dapat dihapus karena sudah Terkunci oleh sistem Penggajian (Payroll).");
        }
        
        $stmt = $this->db->prepare("DELETE FROM produksi WHERE id_karyawan = ? AND date = ?");
        $stmt->execute([$employeeId, $date]);
    }
}
