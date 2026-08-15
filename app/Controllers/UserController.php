<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Models\Role;

class UserController
{
    public function index(): void
    {
        checkPermission('users_roles');

        $userModel = new User();
        $users = $userModel->getAll();

        view('users/index', [
            'title'     => 'Manajemen Pengguna – Salary',
            'pageTitle' => 'Manajemen Pengguna',
            'pageKey'   => 'users',
            'users'     => $users
        ]);
    }

    public function form(): void
    {
        checkPermission('users_roles');

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $user = null;

        if ($id > 0) {
            $userModel = new User();
            $user = $userModel->findById($id);
            if (!$user) {
                redirect('/users');
                return;
            }
            if (!empty($user['superuser'])) {
                $_SESSION['flash_error'] = 'Profil Developer tidak dapat diedit melalui antarmuka ini demi keamanan.';
                redirect('/users');
                return;
            }
        }

        $roleModel = new Role();
        $roles = $roleModel->getAll();

        view('users/form', [
            'title' => ($id > 0 ? 'Edit' : 'Tambah') . ' User',
            'user' => $user,
            'roles' => $roles
        ]);
    }

    public function store(): void
    {
        checkPermission('users_roles');
        validateCsrfToken();

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $name = trim($_POST['name'] ?? '');
        $username = trim($_POST['nama_pengguna'] ?? '');
        $password = $_POST['password'] ?? '';
        $roleId = (int) ($_POST['id_peran'] ?? 0);
        $isActive = isset($_POST['aktif']) ? 1 : 0;

        // Proteksi self-lockout: Pengguna tidak boleh menonaktifkan akun sendiri
        if ($id > 0 && $id === (int)($_SESSION['user_id'] ?? 0) && !$isActive) {
            $isActive = 1;
        }

        $userModel = new User();

        if ($id > 0) {
            $existingUser = $userModel->findById($id);
            if ($existingUser && !empty($existingUser['superuser'])) {
                $_SESSION['flash_error'] = 'User Developer (Superuser) tidak dapat diedit demi keamanan sistem.';
                redirect('/users');
                return;
            }
        }

        if ($name === '' || $username === '' || $roleId <= 0) {
            $_SESSION['flash_error'] = 'Nama, Username, dan Role wajib diisi.';
            redirect('/users/form' . ($id > 0 ? '?id='.$id : ''));
        }

        // Validasi format nama_pengguna (Alfanumerik, min 3 karakter)
        if (!preg_match('/^[a-zA-Z0-9_]{3,}$/', $username)) {
            $_SESSION['flash_error'] = 'Username harus minimal 3 karakter, hanya huruf, angka, dan underscore.';
            redirect('/users/form' . ($id > 0 ? '?id='.$id : ''));
        }

        if ($userModel->usernameExists($username, $id)) {
            $_SESSION['flash_error'] = 'Username sudah dipakai oleh user lain.';
            redirect('/users/form' . ($id > 0 ? '?id='.$id : ''));
        }

        if ($userModel->nameExists($name, $id)) {
            $_SESSION['flash_error'] = "Gagal: Nama user '{$name}' sudah terdaftar. Silakan gunakan nama lain.";
            redirect('/users/form' . ($id > 0 ? '?id='.$id : ''));
        }

        if ($id > 0) {
            // Edit mode (password opsional)
            $userModel->update($id, $name, $username, $roleId, $isActive, $password);
            $_SESSION['flash_success'] = 'User berhasil diperbarui.';
        } else {
            // Tambah mode (password wajib)
            if ($password === '') {
                $_SESSION['flash_error'] = 'Password wajib diisi untuk user baru.';
                redirect('/users/form');
            }
            $userModel->create($name, $username, $password, $roleId);
            $_SESSION['flash_success'] = 'User berhasil ditambahkan.';
        }

        redirect('/users');
    }

    public function destroy(): void
    {
        checkPermission('users_roles');
        validateCsrfToken();

        $id = (int) ($_POST['id'] ?? 0);
        $userModel = new User();
        
        if ($id === 1 || $id === (int) ($_SESSION['user_id'] ?? 0)) {
            $_SESSION['flash_error'] = 'Tidak dapat menghapus user utama atau diri sendiri.';
            redirect('/users');
            return;
        }

        $user = $userModel->findById($id);
        if ($user && !empty($user['superuser'])) {
            $_SESSION['flash_error'] = 'User Developer (Superuser) tidak dapat dihapus.';
            redirect('/users');
            return;
        }

        try {
            $userModel->delete($id);
            $_SESSION['flash_success'] = 'User berhasil dihapus.';
        } catch (\PDOException $e) {
            $_SESSION['flash_error'] = 'Gagal menghapus user ini (mungkin ada data transaksi yang berelasi). Non-aktifkan saja statusnya.';
        }

        redirect('/users');
    }

    public function permissions(): void
    {
        checkPermission('users_roles');

        $id = (int) ($_GET['id'] ?? 0);
        $userModel = new User();
        $user = $userModel->findById($id);

        if (!$user) {
            redirect('/users');
            return;
        }

        if (!empty($user['superuser'])) {
            $_SESSION['flash_error'] = 'User Developer memiliki akses absolut dan tidak perlu disetel izin khususnya.';
            redirect('/users');
            return;
        }

        // Ambil daftar role default dari role user ini
        $roleModel = new Role();
        $rolePermissions = $roleModel->getPermissions((int) ($user['id_peran'] ?? 0));

        // Ambil user override
        $db = getDB();
        $stmt = $db->prepare('SELECT kunci_halaman, diizinkan FROM izin_pengguna WHERE id_pengguna = ?');
        $stmt->execute([$id]);
        $userOverrides = array_column($stmt->fetchAll(), 'diizinkan', 'kunci_halaman');

        // Daftar semua kunci resmi
        $allKeys = require APP_ROOT . '/config/permissions.php';

        view('users/permissions', [
            'title' => 'Override Izin Akses - ' . $user['name'],
            'user' => $user,
            'rolePermissions' => $rolePermissions,
            'userOverrides' => $userOverrides,
            'allKeys' => $allKeys
        ]);
    }

    public function storePermissions(): void
    {
        checkPermission('users_roles');
        validateCsrfToken();

        $userId = (int) ($_POST['id_pengguna'] ?? 0);
        
        $userModel = new User();
        $user = $userModel->findById($userId);
        if ($user && !empty($user['superuser'])) {
            $_SESSION['flash_error'] = 'Tidak diizinkan mengubah izin User Developer.';
            redirect('/users');
            return;
        }

        $db = getDB();
        $db->beginTransaction();

        try {
            // Hapus semua override lama untuk user ini
            $stmt = $db->prepare('DELETE FROM izin_pengguna WHERE id_pengguna = ?');
            $stmt->execute([$userId]);

            // Insert override baru (hanya yang tidak "inherit")
            $overrides = $_POST['override'] ?? [];
            if (!empty($overrides)) {
                $stmt = $db->prepare('INSERT INTO izin_pengguna (id_pengguna, kunci_halaman, diizinkan) VALUES (?, ?, ?)');
                foreach ($overrides as $key => $val) {
                    if ($val === 'allow') {
                        $stmt->execute([$userId, $key, 1]);
                    } elseif ($val === 'deny') {
                        $stmt->execute([$userId, $key, 0]);
                    }
                }
            }

            $db->commit();
            $_SESSION['flash_success'] = 'Hak akses khusus berhasil disimpan.';
        } catch (\Exception $e) {
            $db->rollBack();
            $_SESSION['flash_error'] = 'Terjadi kesalahan sistem.';
        }

        redirect('/users/permissions?id=' . $userId);
    }
}
