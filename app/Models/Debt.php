<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Debt Model
 * Mengelola data Kasbon & Hutang Karyawan serta Riwayat Pemotongan/Cicilan.
 */
class Debt
{
    /**
     * Ambil semua data hutang beserta informasi nama karyawan.
     */
    public function getAll(array $filters = []): array
    {
        $db = getDB();
        $sql = "
            SELECT d.*, e.name AS employee_name, e.tipe_gaji, e.aktif AS employee_active
            FROM kasbon d
            JOIN karyawan e ON d.id_karyawan = e.id
            WHERE d.status != 'cancelled'
        ";
        $params = [];

        if (!empty($filters['id_karyawan'])) {
            $sql .= " AND d.id_karyawan = ?";
            $params[] = (int) $filters['id_karyawan'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND d.status = ?";
            $params[] = $filters['status'];
        }

        $sql .= " ORDER BY d.id DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Cari detail hutang berdasarkan ID.
     */
    public function findById(int $id): ?array
    {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT d.*, e.name AS employee_name, e.tipe_gaji
            FROM kasbon d
            JOIN karyawan e ON d.id_karyawan = e.id
            WHERE d.id = ?
        ");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Buat catatan kasbon/hutang baru.
     */
    public function create(
        int $employeeId,
        string $description,
        float $totalAmount,
        float $defaultDeduction,
        ?string $notes = null
    ): int {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO kasbon (id_karyawan, keterangan, total_nominal, potongan_bawaan, sisa_nominal, status, catatan)
            VALUES (?, ?, ?, ?, ?, 'active', ?)
        ");
        // sisa_nominal diawali persis seharga total_nominal
        $stmt->execute([
            $employeeId,
            $description,
            $totalAmount,
            $defaultDeduction,
            $totalAmount,
            $notes
        ]);

        return (int) $db->lastInsertId();
    }

    /**
     * Update informasi kasbon (deskripsi, potongan default, catatan).
     */
    public function update(int $id, string $description, float $defaultDeduction, ?string $notes = null): bool
    {
        $db = getDB();
        $stmt = $db->prepare("
            UPDATE kasbon
            SET keterangan = ?, potongan_bawaan = ?, catatan = ?
            WHERE id = ?
        ");
        return $stmt->execute([$description, $defaultDeduction, $notes, $id]);
    }

    /**
     * Hapus kasbon beserta seluruh potongan manual terkait secara aman.
     */
    public function delete(int $id): bool
    {
        $db = getDB();
        $inTransaction = false;
        if (!$db->inTransaction()) {
            $db->beginTransaction();
            $inTransaction = true;
        }

        try {
            $stmtD = $db->prepare("DELETE FROM potongan_kasbon WHERE id_kasbon = ?");
            $stmtD->execute([$id]);

            $stmt = $db->prepare("DELETE FROM kasbon WHERE id = ?");
            $res = $stmt->execute([$id]);

            if ($inTransaction) {
                $db->commit();
            }
            return $res;
        } catch (\Throwable $e) {
            if ($inTransaction && $db->inTransaction()) {
                try {
                    $db->rollBack();
                } catch (\Throwable $t) {}
            }
            throw $e;
        }
    }

    /**
     * Tambah pemotongan / cicilan (baik manual maupun otomatis dari payroll).
     */
    public function addDeduction(
        int $debtId,
        float $amount,
        string $deductionDate,
        string $type = 'manual',
        ?string $notes = null,
        ?int $payrollItemId = null
    ): bool {
        $db = getDB();
        
        $debt = $this->findById($debtId);
        if (!$debt || $debt['status'] === 'cancelled') {
            return false;
        }

        $currentRemaining = (float) $debt['sisa_nominal'];

        // Capping: Jangan sampai nominal potongan melebihi sisa hutang yang ada.
        if ($amount > $currentRemaining) {
            $amount = $currentRemaining;
        }

        if ($amount <= 0) {
            return true;
        }

        $inTransaction = false;
        if (!$db->inTransaction()) {
            $db->beginTransaction();
            $inTransaction = true;
        }

        try {
            // 1. Simpan riwayat di potongan_kasbon
            $stmtD = $db->prepare("
                INSERT INTO potongan_kasbon (id_kasbon, id_rincian_penggajian, nominal, tanggal_potongan, type, catatan)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmtD->execute([
                $debtId,
                $payrollItemId,
                $amount,
                $deductionDate,
                $type,
                $notes
            ]);

            // 2. Hitung sisa hutang baru
            $newRemaining = $currentRemaining - $amount;
            $newStatus = $debt['status'];

            if ($newRemaining <= 0) {
                $newRemaining = 0.0;
                $newStatus = 'paid_off';
            }

            // 3. Update master kasbon
            $stmtU = $db->prepare("
                UPDATE kasbon
                SET sisa_nominal = ?, status = ?
                WHERE id = ?
            ");
            $stmtU->execute([$newRemaining, $newStatus, $debtId]);

            if ($inTransaction) {
                $db->commit();
            }
            return true;
        } catch (\Throwable $e) {
            if ($inTransaction && $db->inTransaction()) {
                try {
                    $db->rollBack();
                } catch (\Throwable $t) {}
            }
            throw $e;
        }
    }

    /**
     * Ambil seluruh riwayat cicilan/potongan untuk 1 hutang.
     */
    public function getDeductionsByDebtId(int $debtId): array
    {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT * FROM potongan_kasbon
            WHERE id_kasbon = ?
            ORDER BY tanggal_potongan DESC, id DESC
        ");
        $stmt->execute([$debtId]);
        return $stmt->fetchAll();
    }

    /**
     * Ambil hutang aktif milik karyawan (diperlukan saat payroll).
     */
    public function getActiveDebtsByEmployeeId(int $employeeId): array
    {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT * FROM kasbon
            WHERE id_karyawan = ? AND status = 'active' AND sisa_nominal > 0
            ORDER BY id ASC
        ");
        $stmt->execute([$employeeId]);
        return $stmt->fetchAll();
    }

    /**
     * Ringkasan statistik kasbon (Total Aktif, Total Lunas, Karyawan Aktif Memiliki Kasbon).
     */
    public function getSummary(): array
    {
        $db = getDB();

        $totalActive = (float) $db->query("SELECT COALESCE(SUM(sisa_nominal), 0) FROM kasbon WHERE status = 'active'")->fetchColumn();
        $totalPaid   = (float) $db->query("SELECT COALESCE(SUM(pk.nominal), 0) FROM potongan_kasbon pk JOIN kasbon k ON pk.id_kasbon = k.id WHERE k.status != 'cancelled'")->fetchColumn();
        $empCount    = (int) $db->query("SELECT COUNT(DISTINCT id_karyawan) FROM kasbon WHERE status = 'active' AND sisa_nominal > 0")->fetchColumn();

        return [
            'total_active_remaining' => $totalActive,
            'total_paid_deductions'  => $totalPaid,
            'active_debtors_count'   => $empCount,
        ];
    }

    /**
     * Ambil daftar seluruh karyawan aktif beserta total sisa kasbon & jumlah kasbon aktif.
     */
    public function getEmployeesSummary(): array
    {
        $db = getDB();
        $sql = "
            SELECT 
                k.id,
                k.name,
                k.tipe_gaji,
                COALESCE(SUM(CASE WHEN d.status = 'active' THEN d.sisa_nominal ELSE 0 END), 0) AS total_sisa,
                COALESCE(SUM(CASE WHEN d.status != 'cancelled' THEN d.total_nominal ELSE 0 END), 0) AS total_pinjaman,
                COUNT(CASE WHEN d.status = 'active' AND d.sisa_nominal > 0 THEN 1 ELSE NULL END) AS active_debts_count,
                COUNT(CASE WHEN d.status != 'cancelled' AND d.id IS NOT NULL THEN 1 ELSE NULL END) AS total_debts_count
            FROM karyawan k
            LEFT JOIN kasbon d ON k.id = d.id_karyawan AND d.status != 'cancelled'
            WHERE k.aktif = 1
            GROUP BY k.id, k.name, k.tipe_gaji
            ORDER BY k.name ASC
        ";
        $stmt = $db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Ambil seluruh kasbon milik 1 karyawan beserta riwayat potongannya.
     */
    public function getDebtsWithDeductionsByEmployee(int $employeeId): array
    {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT d.*, 
                COALESCE((SELECT SUM(nominal) FROM potongan_kasbon pk WHERE pk.id_kasbon = d.id), 0) AS total_terbayar
            FROM kasbon d
            WHERE d.id_karyawan = ? AND d.status != 'cancelled'
            ORDER BY (CASE WHEN d.status = 'active' THEN 0 ELSE 1 END) ASC, d.id DESC
        ");
        $stmt->execute([$employeeId]);
        $debts = $stmt->fetchAll();

        foreach ($debts as &$debt) {
            $debt['deductions'] = $this->getDeductionsByDebtId((int)$debt['id']);
        }
        unset($debt);

        return $debts;
    }

    /**
     * Hapus potongan/cicilan manual dan kembalikan sisa_nominal ke kasbon terkait.
     */
    public function deleteDeduction(int $deductionId): bool
    {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM potongan_kasbon WHERE id = ?");
        $stmt->execute([$deductionId]);
        $deduction = $stmt->fetch();

        if (!$deduction || $deduction['type'] !== 'manual') {
            return false;
        }

        $debtId = (int)$deduction['id_kasbon'];
        $amount = (float)$deduction['nominal'];

        $inTransaction = false;
        if (!$db->inTransaction()) {
            $db->beginTransaction();
            $inTransaction = true;
        }

        try {
            // Hapus potongan
            $stmtDel = $db->prepare("DELETE FROM potongan_kasbon WHERE id = ?");
            $stmtDel->execute([$deductionId]);

            // Ambil data kasbon terkini
            $debt = $this->findById($debtId);
            if ($debt) {
                $newRemaining = (float)$debt['sisa_nominal'] + $amount;
                $newStatus = $newRemaining > 0 ? 'active' : $debt['status'];
                
                // Pastikan tidak melebihi total nominal
                if ($newRemaining > (float)$debt['total_nominal']) {
                    $newRemaining = (float)$debt['total_nominal'];
                }

                $stmtU = $db->prepare("
                    UPDATE kasbon
                    SET sisa_nominal = ?, status = ?
                    WHERE id = ?
                ");
                $stmtU->execute([$newRemaining, $newStatus, $debtId]);
            }

            if ($inTransaction) {
                $db->commit();
            }
            return true;
        } catch (\Throwable $e) {
            if ($inTransaction && $db->inTransaction()) {
                try {
                    $db->rollBack();
                } catch (\Throwable $t) {}
            }
            throw $e;
        }
    }
}
