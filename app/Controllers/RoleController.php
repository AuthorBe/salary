<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Role;

/**
 * RoleController
 * CRUD untuk tabel roles + manajemen izin per role.
 *
 * Semua aksi dilindungi oleh checkPermission('users_roles').
 * Hanya superuser atau user dengan izin users_roles yang bisa mengakses.
 */
class RoleController
{
    /**
     * GET /roles — List semua role + jumlah user + tombol aksi.
     */
    public function index(): void
    {
        requireLogin();
        checkPermission('users_roles');

        $roleModel = new Role();
        $roles     = $roleModel->getAllWithUserCount();

        view('roles/index', [
            'title'     => 'Manajemen Role – Salary',
            'pageKey'   => 'users_roles',
            'pageTitle' => 'Manajemen Role',
            'roles'     => $roles,
        ]);
    }

    /**
     * GET /roles/form — Form tambah atau edit role.
     * ?id=X  → mode edit
     * tanpa ?id → mode tambah
     */
    public function form(): void
    {
        requireLogin();
        checkPermission('users_roles');

        $roleModel = new Role();
        $role      = null;

        $id = (int) ($_GET['id'] ?? 0);
        if ($id > 0) {
            $role = $roleModel->findById($id);
            if (!$role) {
                $_SESSION['flash_error'] = 'Role tidak ditemukan.';
                redirect('/roles');
            }
        }

        view('roles/form', [
            'title'     => $role ? 'Edit Role – Salary' : 'Tambah Role – Salary',
            'pageKey'   => 'users_roles',
            'pageTitle' => $role ? 'Edit Role' : 'Tambah Role',
            'role'      => $role,
        ]);
    }

    /**
     * POST /roles/store — Simpan role baru atau update role yang ada.
     */
    public function store(): void
    {
        requireLogin();
        checkPermission('users_roles');
        validateCsrfToken();

        $id   = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');

        if ($name === '') {
            $_SESSION['flash_error'] = 'Nama role tidak boleh kosong.';
            redirect($id ? '/roles/form?id=' . $id : '/roles/form');
        }

        $roleModel = new Role();

        try {
            if ($id > 0) {
                $roleModel->update($id, $name);
                $_SESSION['flash_success'] = "Role \"{$name}\" berhasil diperbarui.";
            } else {
                $roleModel->create($name);
                $_SESSION['flash_success'] = "Role \"{$name}\" berhasil ditambahkan.";
            }
        } catch (\Exception $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            redirect($id ? '/roles/form?id=' . $id : '/roles/form');
        }

        redirect('/roles');
    }

    /**
     * POST /roles/delete — Hapus role.
     * Guard: tidak bisa hapus jika masih dipakai user atau hanya 1 role tersisa.
     */
    public function destroy(): void
    {
        requireLogin();
        checkPermission('users_roles');
        validateCsrfToken();

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['flash_error'] = 'ID role tidak valid.';
            redirect('/roles');
        }

        $roleModel = new Role();
        $role      = $roleModel->findById($id);
        if (!$role) {
            $_SESSION['flash_error'] = 'Role tidak ditemukan.';
            redirect('/roles');
        }

        try {
            $roleModel->delete($id);
            $_SESSION['flash_success'] = "Role \"{$role['name']}\" berhasil dihapus.";
        } catch (\Exception $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }

        redirect('/roles');
    }

    /**
     * GET /roles/permissions?id_peran=X — Form kelola izin per role.
     */
    public function permissions(): void
    {
        requireLogin();
        checkPermission('users_roles');

        $roleId = (int) ($_GET['id_peran'] ?? 0);
        if ($roleId <= 0) {
            redirect('/roles');
        }

        $roleModel = new Role();
        $role      = $roleModel->findById($roleId);
        if (!$role) {
            $_SESSION['flash_error'] = 'Role tidak ditemukan.';
            redirect('/roles');
        }

        $allKeys         = require APP_ROOT . '/config/permissions.php';
        $rolePermissions = $roleModel->getPermissions($roleId);

        view('roles/permissions', [
            'title'           => "Izin Role: {$role['name']} – Salary",
            'pageKey'         => 'users_roles',
            'pageTitle'       => "Izin Role: {$role['name']}",
            'role'            => $role,
            'allKeys'         => $allKeys,
            'rolePermissions' => $rolePermissions,
        ]);
    }

    /**
     * POST /roles/permissions/store — Simpan izin per role.
     */
    public function storePermissions(): void
    {
        requireLogin();
        checkPermission('users_roles');
        validateCsrfToken();

        $roleId = (int) ($_POST['id_peran'] ?? 0);
        if ($roleId <= 0) {
            redirect('/roles');
        }

        $roleModel = new Role();
        $allKeys   = require APP_ROOT . '/config/permissions.php';

        foreach (array_keys($allKeys) as $pageKey) {
            $value = $_POST['permissions'][$pageKey] ?? 'deny';
            $roleModel->setPermission($roleId, $pageKey, $value === 'allow');
        }

        $_SESSION['flash_success'] = 'Izin role berhasil disimpan.';
        redirect('/roles/permissions?id_peran=' . $roleId);
    }
}
