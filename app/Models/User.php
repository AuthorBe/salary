<?php
declare(strict_types=1);

namespace App\Models;

/**
 * User Model
 * Semua operasi database untuk tabel `users`.
 *
 * WAJIB: semua query pakai prepared statement — tidak ada raw query
 * dengan variabel langsung disisipkan ke string SQL.
 */
class User
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    /**
     * Cari user berdasarkan nama_pengguna untuk keperluan login.
     * Hanya return user yang aktif = 1.
     * Join ke tabel roles untuk dapat nama role sekalian.
     */
    public function findByUsername(string $username): array|false
    {
        $stmt = $this->db->prepare(
            'SELECT u.*, r.name AS role_name
             FROM pengguna u
             LEFT JOIN peran r ON r.id = u.id_peran
             WHERE LOWER(u.nama_pengguna) = LOWER(?) AND u.aktif = 1
             LIMIT 1'
        );
        $stmt->execute([trim($username)]);
        return $stmt->fetch();
    }

    /**
     * Cari user berdasarkan ID.
     */
    public function findById(int $id): array|false
    {
        $stmt = $this->db->prepare(
            'SELECT u.*, r.name AS role_name
             FROM pengguna u
             LEFT JOIN peran r ON r.id = u.id_peran
             WHERE u.id = ?
             LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Ambil semua user beserta role mereka, diurutkan berdasarkan nama.
     */
    public function getAll(): array
    {
        $stmt = $this->db->query(
            'SELECT u.*, r.name AS role_name
             FROM pengguna u
             LEFT JOIN peran r ON r.id = u.id_peran
             ORDER BY u.name ASC'
        );
        return $stmt->fetchAll();
    }

    /**
     * Buat user baru.
     * Password di-hash di sini — tidak pernah simpan plain text ke DB.
     *
     * @param string $name      Nama lengkap
     * @param string $username  Alfanumerik, min 3 karakter
     * @param string $password  Plain text — akan di-hash dengan bcrypt
     * @param int    $roleId    FK ke tabel roles
     * @return int              ID user yang baru dibuat
     */
    public function create(string $name, string $username, string $password, int $roleId): int
    {
        // Kenapa PASSWORD_BCRYPT? Cost factor default (10) sudah cukup aman
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $this->db->prepare(
            'INSERT INTO pengguna (name, nama_pengguna, kata_sandi, id_peran) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$name, $username, $passwordHash, $roleId]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Update data user.
     * Jika $password diisi, hash ulang. Jika kosong, biarkan hash lama.
     */
    public function update(int $id, string $name, string $username, int $roleId, int $isActive, string $password = ''): bool
    {
        // 🔒 PROTEKSI: User Developer (superuser=1) tidak boleh diedit via aplikasi
        $stmtCheck = $this->db->prepare("SELECT superuser FROM pengguna WHERE id = ?");
        $stmtCheck->execute([$id]);
        if ($stmtCheck->fetchColumn() == 1) {
            return false; // Tolak operasi update
        }

        if ($password !== '') {
            $stmt = $this->db->prepare(
                'UPDATE pengguna SET name = ?, nama_pengguna = ?, kata_sandi = ?, id_peran = ?, aktif = ? WHERE id = ?'
            );
            return $stmt->execute([$name, $username, password_hash($password, PASSWORD_BCRYPT), $roleId, $isActive, $id]);
        }

        $stmt = $this->db->prepare(
            'UPDATE pengguna SET name = ?, nama_pengguna = ?, id_peran = ?, aktif = ? WHERE id = ?'
        );
        return $stmt->execute([$name, $username, $roleId, $isActive, $id]);
    }

    /**
     * Cek apakah nama_pengguna sudah dipakai (untuk validasi saat create/update).
     *
     * @param int $excludeId  ID user yang dikecualikan (untuk mode edit)
     */
    public function usernameExists(string $username, int $excludeId = 0): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM pengguna WHERE LOWER(nama_pengguna) = LOWER(?) AND id != ?'
        );
        $stmt->execute([$username, $excludeId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Cek apakah name sudah dipakai (untuk mencegah duplikasi nama user).
     */
    public function nameExists(string $name, int $excludeId = 0): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM pengguna WHERE LOWER(name) = LOWER(?) AND id != ?'
        );
        $stmt->execute([$name, $excludeId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Hapus user (hard delete).
     * Jangan hapus admin pertama (id = 1).
     */
    public function delete(int $id): bool
    {
        // 🔒 PROTEKSI: User Developer (superuser=1) tidak boleh dihapus
        $stmtCheck = $this->db->prepare("SELECT superuser FROM pengguna WHERE id = ?");
        $stmtCheck->execute([$id]);
        if ($stmtCheck->fetchColumn() == 1) {
            return false;
        }

        if ($id === 1) {
            return false;
        }
        $stmt = $this->db->prepare('DELETE FROM pengguna WHERE id = ?');
        return $stmt->execute([$id]);
    }
}
