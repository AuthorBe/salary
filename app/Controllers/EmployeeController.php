<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Employee;

class EmployeeController
{
    public function index(): void
    {
        checkPermission('employees');

        $model = new Employee();
        $employees = $model->getAll();

        view('employees/index', [
            'title'     => 'Data Karyawan – Salary',
            'pageTitle' => 'Data Karyawan',
            'pageKey'   => 'employees',
            'employees' => $employees
        ]);
    }

    public function form(): void
    {
        checkPermission('employees');

        $ids = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ids']) && is_array($_POST['ids'])) {
            $ids = array_map('intval', $_POST['ids']);
        } elseif (isset($_GET['id'])) {
            $ids = [(int)$_GET['id']];
        }

        $employees = [];
        if (!empty($ids)) {
            $model = new Employee();
            foreach ($ids as $id) {
                if ($id > 0) {
                    $emp = $model->findById($id);
                    if ($emp) {
                        $employees[] = $emp;
                    }
                }
            }
        }

        $failedRows = $_SESSION['failed_rows'] ?? null;
        unset($_SESSION['failed_rows']);

        if ($failedRows) {
            $employees = $failedRows;
        }

        view('employees/form', [
            'title' => (!empty($employees) && !empty($employees[0]['id']) ? 'Edit' : 'Tambah') . ' Karyawan',
            'employees' => $employees
        ]);
    }

    public function store(): void
    {
        checkPermission('employees');
        validateCsrfToken();

        $rows = $_POST['employees'] ?? [];
        if (!is_array($rows)) {
            redirect('/employees');
            return;
        }

        $model = new Employee();
        $successCount = 0;
        $failedRows = [];

        foreach ($rows as $index => $row) {
            $id = isset($row['id']) ? (int) $row['id'] : 0;
            
            $data = [
                'id' => $id,
                'name' => trim($row['name'] ?? ''),
                'tipe_gaji' => $row['tipe_gaji'] ?? 'borongan',
                'uang_kehadiran_harian' => parseRupiah($row['uang_kehadiran_harian'] ?? ''),
                'tunjangan_bulanan' => parseRupiah($row['tunjangan_bulanan'] ?? ''),
                'aktif' => isset($row['aktif']) ? 1 : 0,
                'gaji_pokok' => parseRupiah($row['gaji_pokok'] ?? ''),
            ];

            // Ignore totally empty rows dynamically added but untouched
            if ($id === 0 && $data['name'] === '' && $data['uang_kehadiran_harian'] === 0 && $data['tunjangan_bulanan'] === 0 && $data['gaji_pokok'] === 0) {
                continue;
            }

            if ($data['name'] === '' || !in_array($data['tipe_gaji'], ['borongan', 'bulanan'])) {
                $data['error'] = 'Nama dan Tipe Gaji wajib diisi dengan benar.';
                $failedRows[] = $data;
                continue;
            }

            if ($model->nameExists($data['name'], $id)) {
                $data['error'] = "Nama '{$data['name']}' sudah digunakan.";
                $failedRows[] = $data;
                continue;
            }

            try {
                if ($id > 0) {
                    $model->update($id, $data);
                } else {
                    $model->create($data);
                }
                $successCount++;
            } catch (\Exception $e) {
                $data['error'] = 'Gagal menyimpan: ' . $e->getMessage();
                $failedRows[] = $data;
            }
        }

        if (count($failedRows) > 0) {
            $_SESSION['flash_error'] = "Berhasil menyimpan $successCount data. Gagal menyimpan " . count($failedRows) . " data.";
            $_SESSION['failed_rows'] = $failedRows;
            redirect('/employees/form'); 
        } else {
            $_SESSION['flash_success'] = "Berhasil menyimpan $successCount data karyawan.";
            redirect('/employees');
        }
    }

    public function destroy(): void
    {
        checkPermission('employees');
        validateCsrfToken();

        $id = (int) ($_POST['id'] ?? 0);
        $model = new Employee();
        
        try {
            $model->delete($id);
            $_SESSION['flash_success'] = 'Karyawan berhasil dihapus.';
        } catch (\PDOException $e) {
            $_SESSION['flash_error'] = 'Gagal menghapus! Karyawan ini memiliki riwayat kehadiran atau penggajian. Cukup non-aktifkan saja statusnya.';
        }

        redirect('/employees');
    }
}
