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

        $ids = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ids']) && is_array($_POST['ids'])) {
            $ids = array_map('intval', $_POST['ids']);
        } elseif (isset($_GET['id'])) {
            $ids = [(int)$_GET['id']];
        }

        $groups = [];
        if (!empty($ids)) {
            $model = new ProductGroup();
            foreach ($ids as $id) {
                if ($id > 0) {
                    $group = $model->findById($id);
                    if ($group) {
                        $groups[] = $group;
                    }
                }
            }
        }

        $failedRows = $_SESSION['failed_rows'] ?? null;
        unset($_SESSION['failed_rows']);

        if ($failedRows) {
            $groups = $failedRows;
        }

        view('product_groups/form', [
            'title' => (!empty($groups) && !empty($groups[0]['id']) ? 'Edit' : 'Tambah') . ' Kelompok Harga',
            'groups' => $groups
        ]);
    }

    public function store(): void
    {
        checkPermission('product_groups');
        validateCsrfToken();

        $rows = $_POST['groups'] ?? [];
        if (!is_array($rows)) {
            redirect('/product-groups');
            return;
        }

        $model = new ProductGroup();
        $successCount = 0;
        $failedRows = [];

        foreach ($rows as $index => $row) {
            $id = isset($row['id']) ? (int) $row['id'] : 0;
            $name = trim($row['name'] ?? '');
            $price = parseRupiah($row['harga_per_bungkus'] ?? '');

            // Ignore totally empty rows dynamically added but untouched
            if ($id === 0 && $name === '' && $price === 0) {
                continue;
            }

            if ($name === '' || $price < 0) {
                $failedRows[] = [
                    'id' => $id,
                    'name' => $name,
                    'harga_per_bungkus' => $price,
                    'error' => 'Nama dan harga wajib diisi dengan benar.'
                ];
                continue;
            }

            if ($model->nameExists($name, $id)) {
                $failedRows[] = [
                    'id' => $id,
                    'name' => $name,
                    'harga_per_bungkus' => $price,
                    'error' => "Nama '{$name}' sudah digunakan."
                ];
                continue;
            }

            try {
                if ($id > 0) {
                    $model->update($id, $name, $price);
                } else {
                    $model->create($name, $price);
                }
                $successCount++;
            } catch (\Exception $e) {
                $failedRows[] = [
                    'id' => $id,
                    'name' => $name,
                    'harga_per_bungkus' => $price,
                    'error' => 'Gagal menyimpan: ' . $e->getMessage()
                ];
            }
        }

        if (count($failedRows) > 0) {
            $_SESSION['flash_error'] = "Berhasil menyimpan $successCount data. Gagal menyimpan " . count($failedRows) . " data.";
            $_SESSION['failed_rows'] = $failedRows;
            redirect('/product-groups/form'); 
        } else {
            $_SESSION['flash_success'] = "Berhasil menyimpan $successCount kelompok harga.";
            redirect('/product-groups');
        }
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
