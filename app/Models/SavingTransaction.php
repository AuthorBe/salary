<?php
declare(strict_types=1);

namespace App\Models;

class SavingTransaction
{
    public function getByEmployeeId(int $employeeId): array
    {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT tt.*, k.name as employee_name
            FROM transaksi_tabungan tt
            JOIN karyawan k ON tt.id_karyawan = k.id
            WHERE tt.id_karyawan = ?
            ORDER BY tt.tanggal DESC, tt.id DESC
        ");
        $stmt->execute([$employeeId]);
        return $stmt->fetchAll();
    }

    public function create(int $employeeId, string $type, float $amount, string $source, string $date, ?int $payrollItemId = null, ?string $notes = null): bool
    {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO transaksi_tabungan (id_karyawan, tipe, jumlah, sumber, tanggal, id_rincian_penggajian, keterangan)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$employeeId, $type, $amount, $source, $date, $payrollItemId, $notes]);
    }

    public function update(int $id, float $newAmount, ?string $notes): bool
    {
        $db = getDB();
        $stmt = $db->prepare("UPDATE transaksi_tabungan SET jumlah = ?, keterangan = ? WHERE id = ?");
        return $stmt->execute([$newAmount, $notes, $id]);
    }

    public function findById(int $id): ?array
    {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM transaksi_tabungan WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function delete(int $id): bool
    {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM transaksi_tabungan WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
