<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Saving;
use App\Models\SavingTransaction;
use App\Models\Employee;

class SavingController
{
    public function index(): void
    {
        checkPermission('savings');

        $savingModel = new Saving();
        $savings = $savingModel->getAll();

        $summary = [
            'total_saldo' => 0,
            'karyawan_menabung' => 0,
        ];

        $bulanan = [];
        $borongan = [];

        foreach ($savings as $s) {
            $saldo = (float) $s['saldo'];
            $summary['total_saldo'] += $saldo;
            if ($saldo > 0) {
                $summary['karyawan_menabung']++;
            }

            if ($s['tipe_gaji'] === 'bulanan') {
                $bulanan[] = $s;
            } else {
                $borongan[] = $s;
            }
        }

        view('savings/index', [
            'title'      => 'Tabungan Karyawan – Salary',
            'pageTitle'  => 'Tabungan Karyawan',
            'pageKey'    => 'savings',
            'summary'    => $summary,
            'bulanan'    => $bulanan,
            'borongan'   => $borongan,
        ]);
    }

    public function store(): void
    {
        checkPermission('savings');
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid method']);
            return;
        }

        $employeeId = isset($_POST['id_karyawan']) ? (int)$_POST['id_karyawan'] : 0;
        $type = $_POST['tipe'] ?? ''; // 'deposit' or 'withdrawal'
        $amountStr = $_POST['jumlah'] ?? '0';
        $amount = (float)str_replace(['Rp', '.', ' '], '', $amountStr);
        $date = $_POST['tanggal'] ?? date('Y-m-d');
        $notes = $_POST['keterangan'] ?? null;

        if ($employeeId <= 0 || !in_array($type, ['deposit', 'withdrawal']) || $amount <= 0) {
            echo json_encode(['success' => false, 'message' => 'Data input tidak valid.']);
            return;
        }

        $savingModel = new Saving();
        $transModel = new SavingTransaction();

        $currentBalance = $savingModel->getBalance($employeeId);

        if ($type === 'withdrawal' && $amount > $currentBalance) {
            echo json_encode(['success' => false, 'message' => 'Saldo tabungan tidak mencukupi untuk ditarik.']);
            return;
        }

        $success = $transModel->create($employeeId, $type, $amount, 'manual', $date, null, $notes);

        if ($success) {
            $adjustAmount = $type === 'deposit' ? $amount : -$amount;
            $savingModel->adjustBalance($employeeId, $adjustAmount);
            
            $newBalance = $savingModel->getBalance($employeeId);
            echo json_encode([
                'success' => true, 
                'message' => 'Transaksi berhasil disimpan.',
                'new_balance' => $newBalance
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan ke database.']);
        }
    }

    public function getHistory(): void
    {
        checkPermission('savings');
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

        $transModel = new SavingTransaction();
        $transactions = $transModel->getByEmployeeId($employeeId);

        echo json_encode([
            'success' => true,
            'employee' => ['id' => $employee['id'], 'name' => $employee['name']],
            'transactions' => $transactions
        ]);
    }

    public function update(): void
    {
        checkPermission('savings');
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid method']);
            return;
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $amountStr = $_POST['jumlah'] ?? '0';
        $newAmount = (float)str_replace(['Rp', '.', ' '], '', $amountStr);
        $notes = $_POST['keterangan'] ?? null;

        if ($id <= 0 || $newAmount <= 0) {
            echo json_encode(['success' => false, 'message' => 'Data input tidak valid.']);
            return;
        }

        $transModel = new SavingTransaction();
        $savingModel = new Saving();

        $transaction = $transModel->findById($id);
        if (!$transaction) {
            echo json_encode(['success' => false, 'message' => 'Transaksi tidak ditemukan.']);
            return;
        }

        if ($transaction['sumber'] !== 'manual') {
            echo json_encode(['success' => false, 'message' => 'Hanya transaksi manual yang bisa diubah.']);
            return;
        }

        $oldAmount = (float) $transaction['jumlah'];
        $type = $transaction['tipe'];
        $employeeId = (int) $transaction['id_karyawan'];

        // Cek validasi saldo
        $currentBalance = $savingModel->getBalance($employeeId);
        if ($type === 'withdrawal') {
            // Saldo semu (kembalikan dulu yang lama)
            $virtualBalance = $currentBalance + $oldAmount;
            if ($newAmount > $virtualBalance) {
                echo json_encode(['success' => false, 'message' => 'Saldo tidak mencukupi (termasuk saldo sebelum transaksi ini).']);
                return;
            }
        } else if ($type === 'deposit' && $newAmount < $oldAmount) {
            // Jika menurunkan nilai setoran, cek apakah saldo cukup untuk dipotong selisihnya
            $diff = $oldAmount - $newAmount;
            if ($diff > $currentBalance) {
                echo json_encode(['success' => false, 'message' => 'Gagal mengubah: Saldo akan menjadi negatif karena karyawan sudah menarik sebagian tabungannya.']);
                return;
            }
        }

        $success = $transModel->update($id, $newAmount, $notes);
        if ($success) {
            // Rollback old amount, apply new amount
            if ($type === 'deposit') {
                $savingModel->adjustBalance($employeeId, -$oldAmount); // undo old
                $savingModel->adjustBalance($employeeId, $newAmount);  // apply new
            } else {
                // withdrawal
                $savingModel->adjustBalance($employeeId, $oldAmount); // undo old (add back)
                $savingModel->adjustBalance($employeeId, -$newAmount); // apply new (subtract)
            }

            $newBalance = $savingModel->getBalance($employeeId);
            echo json_encode([
                'success' => true,
                'message' => 'Transaksi berhasil diubah.',
                'new_balance' => $newBalance
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal mengubah transaksi.']);
        }
    }

    public function delete(): void
    {
        checkPermission('savings');
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid method']);
            return;
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID Transaksi tidak valid.']);
            return;
        }

        $transModel = new SavingTransaction();
        $savingModel = new Saving();

        $transaction = $transModel->findById($id);
        if (!$transaction) {
            echo json_encode(['success' => false, 'message' => 'Transaksi tidak ditemukan.']);
            return;
        }

        if ($transaction['sumber'] !== 'manual') {
            echo json_encode(['success' => false, 'message' => 'Hanya transaksi manual yang bisa dihapus.']);
            return;
        }

        $amount = (float) $transaction['jumlah'];
        $type = $transaction['tipe'];
        $employeeId = (int) $transaction['id_karyawan'];

        // Cek apakah kalau hapus deposit, saldo malah jadi minus
        if ($type === 'deposit') {
            $currentBalance = $savingModel->getBalance($employeeId);
            if ($amount > $currentBalance) {
                echo json_encode(['success' => false, 'message' => 'Gagal menghapus: Saldo akan menjadi negatif (sudah ada penarikan setelah ini).']);
                return;
            }
        }

        $success = $transModel->delete($id);
        if ($success) {
            // Rollback saldo
            if ($type === 'deposit') {
                $savingModel->adjustBalance($employeeId, -$amount);
            } else {
                $savingModel->adjustBalance($employeeId, $amount);
            }

            $newBalance = $savingModel->getBalance($employeeId);
            echo json_encode([
                'success' => true,
                'message' => 'Transaksi berhasil dihapus.',
                'new_balance' => $newBalance
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus transaksi.']);
        }
    }
}
