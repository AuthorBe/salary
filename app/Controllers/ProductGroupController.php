<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\ProductGroup;

class ProductGroupController
{
    public function index(): void
    {
        checkPermission('product_groups');

        $model = new ProductGroup();
        $groups = $model->getAll();

        view('product_groups/index', [
            'title'     => 'Kelompok Harga – Salary',
            'pageTitle' => 'Kelompok Harga',
            'pageKey'   => 'product_groups',
            'groups'    => $groups
        ]);
    }

    public function form(): void
    {
        checkPermission('product_groups');

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $group = null;

        if ($id > 0) {
            $model = new ProductGroup();
            $group = $model->findById($id);
            if (!$group) {
                redirect('/product-groups');
            }
        }

        view('product_groups/form', [
            'title' => ($id > 0 ? 'Edit' : 'Tambah') . ' Kelompok Harga',
            'group' => $group
        ]);
    }

    public function store(): void
    {
        checkPermission('product_groups');
        validateCsrfToken();

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $name = trim($_POST['name'] ?? '');
        $price = parseRupiah($_POST['harga_per_bungkus'] ?? '');

        if ($name === '' || $price < 0) {
            $_SESSION['flash_error'] = 'Nama dan harga wajib diisi dengan benar.';
            redirect('/product-groups/form' . ($id > 0 ? '?id='.$id : ''));
        }

        $model = new ProductGroup();
        if ($id > 0) {
            $model->update($id, $name, $price);
            $_SESSION['flash_success'] = 'Kelompok harga berhasil diperbarui.';
        } else {
            $model->create($name, $price);
            $_SESSION['flash_success'] = 'Kelompok harga berhasil ditambahkan.';
        }

        redirect('/product-groups');
    }

    public function destroy(): void
    {
        checkPermission('product_groups');
        validateCsrfToken();

        $id = (int) ($_POST['id'] ?? 0);
        $model = new ProductGroup();
        
        try {
            $model->delete($id);
            $_SESSION['flash_success'] = 'Kelompok harga berhasil dihapus.';
        } catch (\PDOException $e) {
            // Constraint failed (masih dipakai di produk)
            $_SESSION['flash_error'] = 'Gagal menghapus! Kelompok ini masih digunakan oleh Produk.';
        }

        redirect('/product-groups');
    }
}
