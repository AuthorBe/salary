<?php
namespace App\Models;

class PayrollRun
{
    /**
     * Get all payroll runs
     */
    public function getAll(): array
    {
        $db = getDB();
        $stmt = $db->query("
            SELECT pr.*, u.name as approved_by_name
            FROM penggajian pr
            LEFT JOIN pengguna u ON pr.disetujui_oleh = u.id
            ORDER BY pr.id DESC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Find payroll run by ID
     */
    public function findById(int $id): ?array
    {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT pr.*, u.name as approved_by_name
            FROM penggajian pr
            LEFT JOIN pengguna u ON pr.disetujui_oleh = u.id
            WHERE pr.id = ?
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Delete payroll run
     */
    public function delete(int $id): bool
    {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM penggajian WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
