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

        $debtModel = new Debt();
        $employeesData = $debtModel->getEmployeesSummary();
        $summary = $debtModel->getSummary();

        $bulanan = [];
        $borongan = [];

        foreach ($employeesData as $emp) {
            if ($emp['tipe_gaji'] === 'bulanan') {
                $bulanan[] = $emp;
            } else {
                $borongan[] = $emp;
            }
        }

        $empModel = new Employee();
        $allEmployees = $empModel->getAll();
        $activeEmployees = array_filter($allEmployees, fn($e) => (bool)$e['aktif']);

        view('debts/index', [
            'title'           => 'Kasbon & Hutang Karyawan – Salary',
            'pageTitle'       => 'Kasbon Karyawan',
            'pageKey'         => 'debts',
            'summary'         => $summary,
            'bulanan'         => $bulanan,
            'borongan'        => $borongan,
            'activeEmployees' => $activeEmployees,
        ]);
    }

    public function getHistory(): void
    {
        checkPermission('debts');
        header('Content-Type: application/json');

        $employeeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($employeeId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID Karyawan tidak valid']);
            return;
        }

        $empModel = new Employee();
        $employee = $empModel->findById($employeeId);

        if (!$employee) {
            echo json_encode(['success' => false, 'message' => 'Karyawan tidak ditemukan']);
            return;
        }

        $debtModel = new Debt();
        $debts = $debtModel->getDebtsWithDeductionsByEmployee($employeeId);

        $totalSisa = 0.0;
        $totalPinjaman = 0.0;
        $totalTerbayar = 0.0;
        $activeCount = 0;

        foreach ($debts as $d) {
            $totalPinjaman += (float)$d['total_nominal'];
            $totalTerbayar += (float)$d['total_terbayar'];
            if ($d['status'] === 'active' && (float)$d['sisa_nominal'] > 0) {
                $totalSisa += (float)$d['sisa_nominal'];
                $activeCount++;
            }
        }

        echo json_encode([
            'success'  => true,
            'employee' => [
                'id'        => $employee['id'],
                'name'      => $employee['name'],
                'tipe_gaji' => $employee['tipe_gaji']
            ],
            'summary'  => [
                'total_sisa'     => $totalSisa,
                'total_pinjaman' => $totalPinjaman,
                'total_terbayar' => $totalTerbayar,
                'active_count'   => $activeCount
            ],
            'debts'    => $debts
        ]);
    }

    public function getActiveList(): void
    {
        checkPermission('debts');
        header('Content-Type: application/json');

        $employeeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($employeeId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID Karyawan tidak valid']);
            return;
        }

        $debtModel = new Debt();
        $debts = $debtModel->getActiveDebtsByEmployeeId($employeeId);

        echo json_encode([
            'success' => true,
            'debts'   => $debts
        ]);
    }

    public function form(): void
    {
        checkPermission('debts');

        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        $presetEmpId = isset($_GET['id_karyawan']) ? (int)$_GET['id_karyawan'] : null;

        $debtModel = new Debt();
        $debt = $id ? $debtModel->findById($id) : null;

        $empModel = new Employee();
        $presetEmployee = $presetEmpId ? $empModel->findById($presetEmpId) : null;
        $activeEmployees = array_filter($empModel->getAll(), fn($e) => (bool)$e['aktif']);

        view('debts/_form', [
            'debt'            => $debt,
            'presetEmpId'     => $presetEmpId,
            'presetEmployee'  => $presetEmployee,
            'activeEmployees' => $activeEmployees
        ], 'partials');
    }

    public function store(): void
    {
        checkPermission('debts');
        validateCsrfToken();

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        $id               = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null;
        $employeeId       = (int)($_POST['id_karyawan'] ?? 0);
        $description      = trim($_POST['keterangan'] ?? '');
        $totalAmount      = (float)preg_replace('/[^\d]/', '', (string)($_POST['total_nominal'] ?? '0'));
        $defaultDeduction = (float)preg_replace('/[^\d]/', '', (string)($_POST['potongan_bawaan'] ?? '0'));
        $notes            = trim($_POST['catatan'] ?? '');

        if ($description === '') {
            $msg = 'Deskripsi kasbon wajib diisi!';
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $msg]);
                return;
            }
            $_SESSION['flash_error'] = $msg;
            redirect('/debts');
            return;
        }

        $debtModel = new Debt();

        if ($id) {
            $existing = $debtModel->findById($id);
            if (!$existing) {
                $msg = 'Data kasbon tidak ditemukan!';
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $msg]);
                    return;
                }
                $_SESSION['flash_error'] = $msg;
                redirect('/debts');
                return;
            }

            if ($defaultDeduction <= 0) {
                $defaultDeduction = (float)$existing['potongan_bawaan'];
            }

            $employeeId = (int)$existing['id_karyawan'];
            $debtModel->update($id, $description, $defaultDeduction, $notes);
            $msg = 'Data kasbon berhasil diperbarui!';
        } else {
            if ($employeeId <= 0 || $totalAmount <= 0) {
                $msg = 'Karyawan dan Nominal Total Kasbon wajib diisi dengan benar!';
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $msg]);
                    return;
                }
                $_SESSION['flash_error'] = $msg;
                redirect('/debts');
                return;
            }

            if ($defaultDeduction <= 0) {
                $defaultDeduction = $totalAmount;
            }

            $debtModel->create($employeeId, $description, $totalAmount, $defaultDeduction, $notes);
            $msg = 'Kasbon baru berhasil dicatat!';
        }

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => $msg, 'employee_id' => $employeeId]);
            return;
        }

        $_SESSION['flash_success'] = $msg;
        redirect('/debts');
    }

    public function payManual(): void
    {
        checkPermission('debts');
        validateCsrfToken();

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        $debtId        = (int)($_POST['id_kasbon'] ?? 0);
        $amount        = (float)preg_replace('/[^\d]/', '', (string)($_POST['nominal'] ?? '0'));
        $deductionDate = $_POST['tanggal_potongan'] ?? date('Y-m-d');
        $notes         = trim($_POST['catatan'] ?? 'Pembayaran tunai/manual');

        if ($debtId <= 0 || $amount <= 0) {
            $msg = 'Nominal pembayaran tidak valid!';
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $msg]);
                return;
            }
            $_SESSION['flash_error'] = $msg;
            redirect('/debts');
            return;
        }

        $debtModel = new Debt();
        $debt = $debtModel->findById($debtId);

        if (!$debt || $debt['status'] === 'cancelled') {
            $msg = 'Data kasbon tidak valid atau sudah dibatalkan!';
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $msg]);
                return;
            }
            $_SESSION['flash_error'] = $msg;
            redirect('/debts');
            return;
        }

        if ($amount > (float)$debt['sisa_nominal']) {
            $msg = 'Gagal: Nominal pembayaran (' . formatRupiah((int)$amount) . ') melebihi sisa hutang (' . formatRupiah((int)$debt['sisa_nominal']) . ')!';
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $msg]);
                return;
            }
            $_SESSION['flash_error'] = $msg;
            redirect('/debts');
            return;
        }

        $success = $debtModel->addDeduction($debtId, $amount, $deductionDate, 'manual', $notes);

        if ($success) {
            $msg = 'Pembayaran cicilan kasbon berhasil dicatat!';
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => $msg, 'employee_id' => $debt['id_karyawan']]);
                return;
            }
            $_SESSION['flash_success'] = $msg;
        } else {
            $msg = 'Gagal mencatat pembayaran kasbon!';
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $msg]);
                return;
            }
            $_SESSION['flash_error'] = $msg;
        }

        redirect('/debts');
    }

    public function deleteDeduction(): void
    {
        checkPermission('debts');
        validateCsrfToken();

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        $deductionId = (int)($_POST['id'] ?? 0);
        if ($deductionId <= 0) {
            $msg = 'ID potongan tidak valid!';
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $msg]);
                return;
            }
            $_SESSION['flash_error'] = $msg;
            redirect('/debts');
            return;
        }

        $debtModel = new Debt();
        $success = $debtModel->deleteDeduction($deductionId);

        if ($success) {
            $msg = 'Riwayat pembayaran berhasil dihapus dan saldo kasbon disesuaikan kembali.';
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => $msg]);
                return;
            }
            $_SESSION['flash_success'] = $msg;
        } else {
            $msg = 'Gagal menghapus riwayat pembayaran atau pembayaran dibuat otomatis oleh Payroll.';
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $msg]);
                return;
            }
            $_SESSION['flash_error'] = $msg;
        }

        redirect('/debts');
    }

    public function destroy(): void
    {
        checkPermission('debts');
        validateCsrfToken();

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $msg = 'ID kasbon tidak valid!';
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $msg]);
                return;
            }
            $_SESSION['flash_error'] = $msg;
            redirect('/debts');
            return;
        }

        $debtModel = new Debt();
        $debt = $debtModel->findById($id);

        if (!$debt) {
            $msg = 'Data kasbon tidak ditemukan!';
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $msg]);
                return;
            }
            $_SESSION['flash_error'] = $msg;
            redirect('/debts');
            return;
        }

        $deductions = $debtModel->getDeductionsByDebtId($id);
        if (!empty($deductions)) {
            $msg = 'Gagal dihapus: Kasbon ini sudah memiliki riwayat cicilan/potongan! Hapus terlebih dahulu cicilannya jika ingin menghapus kasbon ini.';
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $msg]);
                return;
            }
            $_SESSION['flash_error'] = $msg;
            redirect('/debts');
            return;
        }

        $debtModel->delete($id);
        $msg = 'Data kasbon berhasil dihapus!';

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => $msg, 'employee_id' => $debt['id_karyawan']]);
            return;
        }

        $_SESSION['flash_success'] = $msg;
        redirect('/debts');
    }
}
