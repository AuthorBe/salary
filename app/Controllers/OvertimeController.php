<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Employee;
use App\Models\Product;
use App\Models\Production;
use App\Models\Attendance;

class OvertimeController
{
    public function index(): void
    {
        checkPermission('overtime');

        $date = $_GET['date'] ?? date('Y-m-d');

        $empModel = new Employee();
        // Ambil semua karyawan yang aktif saja
        $employees = array_filter($empModel->getAll(), fn($e) => $e['aktif'] == 1);

        $prodModel = new Product();
        $products = $prodModel->getAllWithGroup();

        $attModel = new Attendance();
        $attendances = $attModel->getByDate($date);

        view('overtime/form', [
            'title'       => 'Input Lembur – Salary',
            'pageTitle'   => 'Input Lembur',
            'pageKey'     => 'overtime',
            'date'        => $date,
            'employees'   => $employees,
            'products'    => $products,
            'attendances' => $attendances,
        ]);
    }

    public function store(): void
    {
        checkPermission('overtime');
        validateCsrfToken();

        $date       = trim($_POST['date'] ?? '');
        $tipeInput  = trim($_POST['tipe_input'] ?? '');

        if (!$date || !$tipeInput) {
            $_SESSION['flash_error'] = 'Tanggal dan Tipe Input wajib diisi.';
            redirect('/overtime?date=' . urlencode($date) . ($tipeInput ? '&tab=' . urlencode($tipeInput) : ''));
            return;
        }

        try {
            if ($tipeInput === 'borongan') {
                $employeeId = (int) ($_POST['id_karyawan'] ?? 0);
                if ($employeeId <= 0) {
                    throw new \Exception('Pilih Karyawan Borongan terlebih dahulu.');
                }

                // Parsing input produk
                $itemsRaw = $_POST['items'] ?? [];
                $cleanItemsMap = [];
                foreach ($itemsRaw as $item) {
                    $pId = (int) ($item['id_produk'] ?? 0);
                    if ($pId <= 0) continue;

                    $qty = parseRupiah($item['kuantitas'] ?? '0');
                    $bal = parseRupiah($item['kuantitas_bal'] ?? '0');

                    if ($qty <= 0 && $bal <= 0) continue; // Jangan simpan jika tidak ada qty

                    if (!isset($cleanItemsMap[$pId])) {
                        $cleanItemsMap[$pId] = [
                            'id_produk'     => $pId,
                            'kuantitas'     => 0,
                            'kuantitas_bal' => 0
                        ];
                    }
                    $cleanItemsMap[$pId]['kuantitas'] += $qty;
                    $cleanItemsMap[$pId]['kuantitas_bal'] += $bal;
                }
                
                $cleanItems = array_values($cleanItemsMap);

                if (empty($cleanItems)) {
                    throw new \Exception('Harap masukkan sekurang-kurangnya 1 produk dengan jumlah lebih dari 0.');
                }

                $prodModel = new Production();
                $prodModel->saveEmployeeProduction($date, $employeeId, $cleanItems, true);
                
                $_SESSION['flash_success'] = 'Data Produksi Borongan berhasil disimpan via form Lembur.';
                
            } else if ($tipeInput === 'bulanan') {
                $bulananItems = $_POST['bulanan_items'] ?? [];
                
                $empModel = new Employee();
                $lemburMassal = [];
                $hasSelectedEmp = false;

                foreach ($bulananItems as $item) {
                    $empId = (int)($item['id_karyawan'] ?? 0);
                    if ($empId > 0) {
                        $hasSelectedEmp = true;
                        $nominal = parseRupiah($item['nominal'] ?? '0');
                        if ($nominal <= 0) {
                            $emp = $empModel->findById($empId);
                            $empName = $emp['name'] ?? 'karyawan';
                            throw new \Exception("Nominal lembur untuk $empName harus lebih besar dari Rp 0.");
                        }
                        if (!isset($lemburMassal[$empId])) {
                            $lemburMassal[$empId] = 0;
                        }
                        $lemburMassal[$empId] += $nominal;
                    }
                }

                if (!$hasSelectedEmp || empty($lemburMassal)) {
                    throw new \Exception('Harap pilih sekurang-kurangnya 1 karyawan bulanan.');
                }

                $attModel = new Attendance();
                $existing = $attModel->getByDate($date);
                
                $count = 0;
                foreach ($lemburMassal as $empId => $nominal) {
                    $existingNominal = $existing[$empId]['lembur_nominal'] ?? 0;
                    $finalNominal = $existingNominal + $nominal;
                    if ($finalNominal < 0) {
                        $finalNominal = 0;
                    }
                    
                    // Simpan nominal lembur dan pertahankan status kehadiran/telat yang ada
                    $attModel->saveMonthlyOvertime($empId, $date, $finalNominal);
                    $count++;
                }
                
                $_SESSION['flash_success'] = "Berhasil memproses lembur massal untuk $count karyawan bulanan.";
            } else {
                throw new \Exception('Tipe input tidak valid.');
            }
        } catch (\Exception $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }

        redirect('/overtime?date=' . urlencode($date) . ($tipeInput ? '&tab=' . urlencode($tipeInput) : ''));
    }
}
