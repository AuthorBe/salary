<?php
declare(strict_types=1);

namespace App\Models;

class Saving
{
    public function getAll(): array
    {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT k.id, k.name, k.tipe_gaji, COALESCE(t.saldo, 0) as saldo
            FROM karyawan k
            LEFT JOIN tabungan t ON k.id = t.id_karyawan
            WHERE k.aktif = 1
            ORDER BY k.name ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findByEmployeeId(int $employeeId): ?array
    {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM tabungan WHERE id_karyawan = ?");
        $stmt->execute([$employeeId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getBalance(int $employeeId): float
    {
        $db = getDB();
        $stmt = $db->prepare("SELECT saldo FROM tabungan WHERE id_karyawan = ?");
        $stmt->execute([$employeeId]);
        $saldo = $stmt->fetchColumn();
        
        if ($saldo === false) {
            $stmt = $db->prepare("INSERT IGNORE INTO tabungan (id_karyawan, saldo) VALUES (?, 0)");
            $stmt->execute([$employeeId]);
            return 0.0;
        }
        return (float) $saldo;
    }

    public function adjustBalance(int $employeeId, float $amount): void
    {
        $db = getDB();
        $this->getBalance($employeeId); // Pastikan record ada
        
        $stmt = $db->prepare("UPDATE tabungan SET saldo = saldo + ? WHERE id_karyawan = ?");
        $stmt->execute([$amount, $employeeId]);
    }
}
