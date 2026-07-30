<?php
declare(strict_types=1);

namespace App\Models;

use PDO;
use Exception;

/**
 * Employee Model
 * Mengelola data karyawan dan relasi penggajiannya (bulanan/harian).
 */
class Employee
{
    /**
     * Ambil semua karyawan dengan setting gajinya.
     */
    public function getAll(): array
    {
        $db = getDB();
        $stmt = $db->query("
            SELECT e.*, 
                   m.gaji_pokok,
                   d.upah_harian
            FROM karyawan e
            LEFT JOIN pengaturan_gaji_bulanan m ON e.id = m.id_karyawan
            LEFT JOIN pengaturan_upah_harian d ON e.id = d.id_karyawan
            ORDER BY e.name ASC
        ");
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT e.*, 
                   m.gaji_pokok,
                   d.upah_harian
            FROM karyawan e
            LEFT JOIN pengaturan_gaji_bulanan m ON e.id = m.id_karyawan
            LEFT JOIN pengaturan_upah_harian d ON e.id = d.id_karyawan
            WHERE e.id = ?
        ");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Menyimpan data karyawan baru sekaligus setting gajinya (Transaksi DB).
     */
    public function create(array $data): bool
    {
        $db = getDB();
        $db->beginTransaction();

        try {
            // Insert utama
            $stmt = $db->prepare("
                INSERT INTO karyawan (name, tipe_gaji, uang_kehadiran_harian, tunjangan_bulanan, aktif)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['name'],
                $data['tipe_gaji'],
                $data['uang_kehadiran_harian'] ?? 0,
                $data['tunjangan_bulanan'] ?? 0,
                $data['aktif'] ?? 1
            ]);

            $employeeId = (int) $db->lastInsertId();

            // Insert sub-tabel sesuai tipe gaji
            if ($data['tipe_gaji'] === 'bulanan') {
                $stmtM = $db->prepare("INSERT INTO pengaturan_gaji_bulanan (id_karyawan, gaji_pokok) VALUES (?, ?)");
                $stmtM->execute([$employeeId, $data['gaji_pokok'] ?? 0]);
            }

            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Update data karyawan (Transaksi DB).
     */
    public function update(int $id, array $data): bool
    {
        $db = getDB();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare("
                UPDATE karyawan 
                SET name = ?, tipe_gaji = ?, uang_kehadiran_harian = ?, tunjangan_bulanan = ?, aktif = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $data['name'],
                $data['tipe_gaji'],
                $data['uang_kehadiran_harian'] ?? 0,
                $data['tunjangan_bulanan'] ?? 0,
                $data['aktif'] ?? 1,
                $id
            ]);

            $db->prepare("DELETE FROM pengaturan_gaji_bulanan WHERE id_karyawan = ?")->execute([$id]);
            $db->prepare("DELETE FROM pengaturan_upah_harian WHERE id_karyawan = ?")->execute([$id]);

            // Insert sub-tabel yang relevan dengan tipe baru
            if ($data['tipe_gaji'] === 'bulanan') {
                $stmtM = $db->prepare("REPLACE INTO pengaturan_gaji_bulanan (id_karyawan, gaji_pokok) VALUES (?, ?)");
                $stmtM->execute([$id, $data['gaji_pokok'] ?? 0]);
            } else {
                $db->prepare("DELETE FROM pengaturan_gaji_bulanan WHERE id_karyawan = ?")->execute([$id]);
            }

            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function delete(int $id): bool
    {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM karyawan WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
