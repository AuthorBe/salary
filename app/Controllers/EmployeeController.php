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

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $employee = null;

        if ($id > 0) {
            $model = new Employee();
            $employee = $model->findById($id);
            if (!$employee) {
                redirect('/employees');
            }
        }

        view('employees/form', [
            'title' => ($id > 0 ? 'Edit' : 'Tambah') . ' Karyawan',
            'employee' => $employee
        ]);
    }

    public function store(): void
    {
        checkPermission('employees');
        validateCsrfToken();

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'tipe_gaji' => $_POST['tipe_gaji'] ?? 'borongan',
            'uang_kehadiran_harian' => parseRupiah($_POST['uang_kehadiran_harian'] ?? ''),
            'tunjangan_bulanan' => parseRupiah($_POST['tunjangan_bulanan'] ?? ''),
            'aktif' => isset($_POST['aktif']) ? 1 : 0,
            
            // Komponen spesifik
            'gaji_pokok' => parseRupiah($_POST['gaji_pokok'] ?? ''),
            'upah_harian' => parseRupiah($_POST['upah_harian'] ?? ''),
        ];

        if ($data['name'] === '' || !in_array($data['tipe_gaji'], ['borongan', 'bulanan', 'harian'])) {
            $_SESSION['flash_error'] = 'Nama dan Tipe Gaji wajib diisi dengan benar.';
            redirect('/employees/form' . ($id > 0 ? '?id='.$id : ''));
        }

        $model = new Employee();
        
        try {
            if ($id > 0) {
                $model->update($id, $data);
                $_SESSION['flash_success'] = 'Karyawan berhasil diperbarui.';
            } else {
                $model->create($data);
                $_SESSION['flash_success'] = 'Karyawan berhasil ditambahkan.';
            }
        } catch (\Exception $e) {
            $_SESSION['flash_error'] = 'Gagal menyimpan data karyawan: ' . $e->getMessage();
            redirect('/employees/form' . ($id > 0 ? '?id='.$id : ''));
        }

        redirect('/employees');
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
