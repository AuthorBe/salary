<?php
namespace App\Models;

class PayrollItem
{
    /**
     * Get items by payroll run ID
     */
    public function getByRunId(int $runId): array
    {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT pi.*, e.name as employee_name, e.tipe_gaji
            FROM rincian_penggajian pi
            JOIN karyawan e ON pi.id_karyawan = e.id
            WHERE pi.id_penggajian = ?
            ORDER BY e.name ASC
        ");
        $stmt->execute([$runId]);
        return $stmt->fetchAll();
    }

    /**
     * Find payroll item by ID
     */
    public function findById(int $id): ?array
    {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT pi.*, e.name as employee_name, e.tipe_gaji
            FROM rincian_penggajian pi
            JOIN karyawan e ON pi.id_karyawan = e.id
            WHERE pi.id = ?
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
