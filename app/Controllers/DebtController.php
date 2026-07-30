<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Debt;
use App\Models\Employee;

class DebtController
{
    public function index(): void
    {
        checkPermission('debts');

        $empId  = isset($_GET['id_karyawan']) && $_GET['id_karyawan'] !== '' ? (int)$_GET['id_karyawan'] : null;
        $status = $_GET['status'] ?? null;

        $filters = [];
        if ($empId) $filters['id_karyawan'] = $empId;
        if ($status) $filters['status'] = $status;

        $debtModel = new Debt();
        $debts     = $debtModel->getAll($filters);
        $summary   = $debtModel->getSummary();

        $empModel  = new Employee();
        $employees = $empModel->getAll();
        $activeEmployees = array_filter($employees, fn($e) => (bool)$e['aktif']);

        // Ambil riwayat jika ada request detail_id khusus
        $selectedDebt = null;
        $deductions   = [];
        if (isset($_GET['detail_id'])) {
            $detailId     = (int) $_GET['detail_id'];
            $selectedDebt = $debtModel->findById($detailId);
            if ($selectedDebt) {
                $deductions = $debtModel->getDeductionsByDebtId($detailId);
            }
        }

        view('debts/index', [
            'title'        => 'Kasbon & Hutang Karyawan – Salary',
            'pageTitle'    => 'Kasbon Karyawan',
            'pageKey'      => 'debts',
            'debts'        => $debts,
            'summary'      => $summary,
            'employees'    => $employees,
            'activeEmployees'=> $activeEmployees,
            'selectedEmp'  => $empId,
            'selectedStatus' => $status,
            'selectedDebt' => $selectedDebt,
            'deductions'   => $deductions
        ]);
    }

    public function form(): void
    {
        checkPermission('debts');

        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        $debtModel = new Debt();
        $debt = $id ? $debtModel->findById($id) : null;

        $empModel = new Employee();
        $activeEmployees = array_filter($empModel->getAll(), fn($e) => (bool)$e['aktif']);

        view('debts/_form', [
            'debt'            => $debt,
            'activeEmployees' => $activeEmployees
        ], 'partials');
    }

    public function store(): void
    {
        checkPermission('debts');
        validateCsrfToken();

        $id               = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null;
        $employeeId       = (int)($_POST['id_karyawan'] ?? 0);
        $description      = trim($_POST['keterangan'] ?? '');
        $totalAmount      = (float)str_replace('.', '', $_POST['total_nominal'] ?? '');
        $defaultDeduction = (float)str_replace('.', '', $_POST['potongan_bawaan'] ?? '');
        $notes            = trim($_POST['catatan'] ?? '');

        if (!$id && ($employeeId <= 0 || $totalAmount <= 0)) {
            $_SESSION['flash_error'] = 'Karyawan dan Nominal Total Kasbon wajib diisi dengan benar!';
            redirect('/debts');
            return;
        }

        if ($description === '') {
            $_SESSION['flash_error'] = 'Deskripsi kasbon wajib diisi!';
            redirect('/debts');
            return;
        }

        if ($defaultDeduction <= 0) {
            $defaultDeduction = $totalAmount; // Default jika kosong, potong sekaligus
        }

        $debtModel = new Debt();

        if ($id) {
            $debtModel->update($id, $description, $defaultDeduction, $notes);
            $_SESSION['flash_success'] = 'Data kasbon berhasil diperbarui!';
        } else {
            $debtModel->create($employeeId, $description, $totalAmount, $defaultDeduction, $notes);
            $_SESSION['flash_success'] = 'Kasbon baru berhasil dicatat!';
        }

        redirect('/debts');
    }

    public function payManual(): void
    {
        checkPermission('debts');
        validateCsrfToken();

        $debtId        = (int)($_POST['id_kasbon'] ?? 0);
        $amount        = (float)str_replace('.', '', $_POST['nominal'] ?? '');
        $deductionDate = $_POST['tanggal_potongan'] ?? date('Y-m-d');
        $notes         = trim($_POST['catatan'] ?? 'Pembayaran tunai/manual');

        if ($debtId <= 0 || $amount <= 0) {
            $_SESSION['flash_error'] = 'Nominal pembayaran tidak valid!';
            redirect('/debts');
            return;
        }

        $debtModel = new Debt();
        $debt = $debtModel->findById($debtId);
        
        if (!$debt || $debt['status'] === 'cancelled') {
            $_SESSION['flash_error'] = 'Data kasbon tidak valid atau sudah dibatalkan!';
            redirect('/debts');
            return;
        }

        if ($amount > (float)$debt['sisa_nominal']) {
            $_SESSION['flash_error'] = 'Gagal: Nominal pembayaran (' . formatRupiah((int)$amount) . ') melebihi sisa hutang (' . formatRupiah((int)$debt['sisa_nominal']) . ')!';
            redirect('/debts?detail_id=' . $debtId);
            return;
        }

        $success   = $debtModel->addDeduction($debtId, $amount, $deductionDate, 'manual', $notes);

        if ($success) {
            $_SESSION['flash_success'] = 'Pembayaran cicilan kasbon berhasil dicatat!';
        } else {
            $_SESSION['flash_error'] = 'Gagal mencatat pembayaran kasbon!';
        }

        redirect('/debts?detail_id=' . $debtId);
    }

    public function destroy(): void
    {
        checkPermission('debts');
        validateCsrfToken();

        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $debtModel = new Debt();
            $debtModel->delete($id);
            $_SESSION['flash_success'] = 'Data kasbon berhasil dihapus!';
        }

        redirect('/debts');
    }
}
