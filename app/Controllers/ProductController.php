<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Product;
use App\Models\ProductGroup;

class ProductController
{
    public function index(): void
    {
        checkPermission('products');

        $model = new Product();
        $products = $model->getAllWithGroup();

        view('products/index', [
            'title'     => 'Data Produk – Salary',
            'pageTitle' => 'Data Produk',
            'pageKey'   => 'products',
            'products'  => $products
        ]);
    }

    public function form(): void
    {
        checkPermission('products');

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $product = null;

        if ($id > 0) {
            $model = new Product();
            $product = $model->findById($id);
            if (!$product) {
                redirect('/products');
            }
        }

        $groupModel = new ProductGroup();
        $groups = $groupModel->getAll();

        view('products/form', [
            'title' => ($id > 0 ? 'Edit' : 'Tambah') . ' Produk',
            'product' => $product,
            'groups' => $groups
        ]);
    }

    public function store(): void
    {
        checkPermission('products');
        validateCsrfToken();

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $name = trim($_POST['name'] ?? '');
        $priceGroupId = (int) ($_POST['id_kelompok_harga'] ?? 0);

        if ($name === '' || $priceGroupId <= 0) {
            $_SESSION['flash_error'] = 'Nama produk dan kelompok harga wajib diisi.';
            redirect('/products/form' . ($id > 0 ? '?id='.$id : ''));
        }

        $model = new Product();

        if ($model->nameExists($name, $id)) {
            $_SESSION['flash_error'] = "Gagal: Nama produk '{$name}' sudah digunakan. Silakan gunakan nama lain.";
            redirect('/products/form' . ($id > 0 ? '?id='.$id : ''));
            return;
        }

        if ($id > 0) {
            $model->update($id, $name, $priceGroupId);
            $_SESSION['flash_success'] = 'Produk berhasil diperbarui.';
        } else {
            $model->create($name, $priceGroupId);
            $_SESSION['flash_success'] = 'Produk berhasil ditambahkan.';
        }

        redirect('/products');
    }

    public function destroy(): void
    {
        checkPermission('products');
        validateCsrfToken();

        $id = (int) ($_POST['id'] ?? 0);
        $model = new Product();
        
        try {
            $model->delete($id);
            $_SESSION['flash_success'] = 'Produk berhasil dihapus.';
        } catch (\PDOException $e) {
            $_SESSION['flash_error'] = 'Gagal menghapus! Produk ini mungkin masih digunakan dalam data produksi.';
        }

        redirect('/products');
    }
}
