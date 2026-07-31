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
            WHERE 1=1
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
     * Hapus/Batalkan kasbon.
     */
    public function delete(int $id): bool
    {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM kasbon WHERE id = ?");
        return $stmt->execute([$id]);
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
        // Jika melebihi, potong sejumlah sisa hutangnya saja untuk mencegah bug rollback.
        if ($amount > $currentRemaining) {
            $amount = $currentRemaining;
        }

        // Jika tidak ada yang dipotong (misal sisa hutang sudah 0), abaikan agar tidak ada log 0 Rupiah
        if ($amount <= 0) {
            return true;
        }

        $inTransaction = false;
        if (!$db->inTransaction()) {
            $db->beginTransaction();
            $inTransaction = true;
        }

        try {
            // 1. Simpan riwayat di debt_deductions
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

            // 3. Update master debts
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
     * Ringkasan statistik statistik kasbon (Total Aktif, Total Lunas, Karyawan Aktif Memiliki Kasbon).
     */
    public function getSummary(): array
    {
        $db = getDB();

        $totalActive = (float) $db->query("SELECT SUM(sisa_nominal) FROM kasbon WHERE status = 'active'")->fetchColumn();
        $totalPaid   = (float) $db->query("SELECT SUM(nominal) FROM potongan_kasbon")->fetchColumn();
        $empCount    = (int) $db->query("SELECT COUNT(DISTINCT id_karyawan) FROM kasbon WHERE status = 'active'")->fetchColumn();

        return [
            'total_active_remaining' => $totalActive,
            'total_paid_deductions'  => $totalPaid,
            'active_debtors_count'   => $empCount,
        ];
    }
}
