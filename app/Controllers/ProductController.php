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

        $ids = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ids']) && is_array($_POST['ids'])) {
            $ids = array_map('intval', $_POST['ids']);
        } elseif (isset($_GET['id'])) {
            $ids = [(int)$_GET['id']];
        }

        $products = [];
        if (!empty($ids)) {
            $model = new Product();
            foreach ($ids as $id) {
                if ($id > 0) {
                    $prod = $model->findById($id);
                    if ($prod) {
                        $products[] = $prod;
                    }
                }
            }
        }

        $failedRows = $_SESSION['failed_rows'] ?? null;
        unset($_SESSION['failed_rows']);

        if ($failedRows) {
            $products = $failedRows;
        }

        $groupModel = new ProductGroup();
        $groups = $groupModel->getAll();

        view('products/form', [
            'title' => (!empty($products) && !empty($products[0]['id']) ? 'Edit' : 'Tambah') . ' Produk',
            'products' => $products,
            'groups' => $groups
        ]);
    }

    public function store(): void
    {
        checkPermission('products');
        validateCsrfToken();

        $rows = $_POST['products'] ?? [];
        if (!is_array($rows)) {
            redirect('/products');
            return;
        }

        $model = new Product();
        $successCount = 0;
        $failedRows = [];

        foreach ($rows as $index => $row) {
            $id = isset($row['id']) ? (int) $row['id'] : 0;
            $name = trim($row['name'] ?? '');
            $priceGroupId = (int) ($row['id_kelompok_harga'] ?? 0);

            // Ignore totally empty rows dynamically added but untouched
            if ($id === 0 && $name === '' && $priceGroupId === 0) {
                continue;
            }

            if ($name === '' || $priceGroupId <= 0) {
                $failedRows[] = [
                    'id' => $id,
                    'name' => $name,
                    'id_kelompok_harga' => $priceGroupId,
                    'error' => 'Nama produk dan kelompok harga wajib diisi.'
                ];
                continue;
            }

            if ($model->nameExists($name, $id)) {
                $failedRows[] = [
                    'id' => $id,
                    'name' => $name,
                    'id_kelompok_harga' => $priceGroupId,
                    'error' => "Nama '{$name}' sudah digunakan."
                ];
                continue;
            }

            try {
                if ($id > 0) {
                    $model->update($id, $name, $priceGroupId);
                } else {
                    $model->create($name, $priceGroupId);
                }
                $successCount++;
            } catch (\Exception $e) {
                $failedRows[] = [
                    'id' => $id,
                    'name' => $name,
                    'id_kelompok_harga' => $priceGroupId,
                    'error' => 'Gagal menyimpan: ' . $e->getMessage()
                ];
            }
        }

        if (count($failedRows) > 0) {
            $_SESSION['flash_error'] = "Berhasil menyimpan $successCount data. Gagal menyimpan " . count($failedRows) . " data.";
            $_SESSION['failed_rows'] = $failedRows;
            redirect('/products/form'); 
        } else {
            $_SESSION['flash_success'] = "Berhasil menyimpan $successCount produk.";
            redirect('/products');
        }
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
