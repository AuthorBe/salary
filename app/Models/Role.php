<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Role Model
 * Operasi database untuk tabel `roles` dan tabel permission yang terkait.
 */
class Role
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    /**
     * Ambil semua role, diurutkan berdasarkan ID.
     */
    public function getAll(): array
    {
        return $this->db->query('SELECT * FROM peran ORDER BY id ASC')->fetchAll();
    }

    /**
     * Cari role berdasarkan ID.
     */
    public function findById(int $id): array|false
    {
        $stmt = $this->db->prepare('SELECT * FROM peran WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Ambil semua permission untuk role tertentu.
     * Return: array asosiatif ['kunci_halaman' => diizinkan]
     *
     * Dipakai untuk: render halaman manajemen permission (Fase 1)
     * dan debugging/audit permission.
     */
    public function getPermissions(int $roleId): array
    {
        $stmt = $this->db->prepare(
            'SELECT kunci_halaman, diizinkan FROM izin_peran WHERE id_peran = ? ORDER BY kunci_halaman ASC'
        );
        $stmt->execute([$roleId]);
        // array_column: ubah array of rows → ['kunci_halaman' => diizinkan]
        return array_column($stmt->fetchAll(), 'diizinkan', 'kunci_halaman');
    }

    /**
     * Set permission untuk satu role + kunci_halaman.
     * Pakai INSERT ... ON DUPLICATE KEY UPDATE supaya bisa insert atau update sekaligus.
     */
    public function setPermission(int $roleId, string $pageKey, bool $isAllowed): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO izin_peran (id_peran, kunci_halaman, diizinkan)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE diizinkan = ?'
        );
        return $stmt->execute([$roleId, $pageKey, (int) $isAllowed, (int) $isAllowed]);
    }

    /**
     * Ambil semua role beserta jumlah user yang menggunakannya.
     * Dipakai di halaman list role.
     */
    public function getAllWithUserCount(): array
    {
        return $this->db->query(
            'SELECT r.*, COUNT(u.id) AS user_count
             FROM peran r
             LEFT JOIN pengguna u ON u.id_peran = r.id
             GROUP BY r.id
             ORDER BY r.id ASC'
        )->fetchAll();
    }

    /**
     * Buat role baru.
     *
     * @param string $name  Nama role, wajib unik
     * @return int          ID role yang baru dibuat
     * @throws \Exception   Jika nama sudah dipakai
     */
    public function create(string $name): int
    {
        // Cek duplikat nama
        $stmt = $this->db->prepare('SELECT id FROM peran WHERE name = ? LIMIT 1');
        $stmt->execute([trim($name)]);
        if ($stmt->fetch()) {
            throw new \Exception("Role dengan nama '{$name}' sudah ada.");
        }

        $stmt = $this->db->prepare('INSERT INTO peran (name) VALUES (?)');
        $stmt->execute([trim($name)]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Update nama role.
     *
     * @throws \Exception  Jika nama baru sudah dipakai role lain
     */
    public function update(int $id, string $name): void
    {
        // Cek duplikat nama (kecuali role itu sendiri)
        $stmt = $this->db->prepare('SELECT id FROM peran WHERE name = ? AND id != ? LIMIT 1');
        $stmt->execute([trim($name), $id]);
        if ($stmt->fetch()) {
            throw new \Exception("Nama role '{$name}' sudah digunakan role lain.");
        }

        $stmt = $this->db->prepare('UPDATE peran SET name = ? WHERE id = ?');
        $stmt->execute([trim($name), $id]);
    }

    /**
     * Hapus role — dengan 2 guard keamanan:
     *   1. Tidak bisa hapus jika ada user yang memakai role ini
     *   2. Tidak bisa hapus jika hanya tersisa 1 role
     *
     * @throws \Exception  Jika salah satu guard gagal
     */
    public function delete(int $id): void
    {
        // Guard 1: minimal harus ada lebih dari 1 role
        $totalRoles = (int) $this->db->query('SELECT COUNT(*) FROM peran')->fetchColumn();
        if ($totalRoles <= 1) {
            throw new \Exception('Tidak dapat menghapus role — minimal harus ada 1 role di sistem.');
        }

        // Guard 2: tidak bisa hapus jika masih dipakai user
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM pengguna WHERE id_peran = ?');
        $stmt->execute([$id]);
        $userCount = (int) $stmt->fetchColumn();
        if ($userCount > 0) {
            throw new \Exception("Tidak dapat menghapus role — masih ada {$userCount} user yang menggunakan role ini.");
        }

        // Hapus permissions role dulu (FK safety)
        $stmt = $this->db->prepare('DELETE FROM izin_peran WHERE id_peran = ?');
        $stmt->execute([$id]);

        // Hapus role
        $stmt = $this->db->prepare('DELETE FROM peran WHERE id = ?');
        $stmt->execute([$id]);
    }
}

