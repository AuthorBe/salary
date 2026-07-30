<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Production;
use App\Models\Employee;
use App\Models\Product;
use App\Models\ProductGroup;

class ProductionController
{
    public function index(): void
    {
        checkPermission('production');

        $date = $_GET['date'] ?? date('Y-m-d');

        $empModel = new Employee();
        $employees = array_filter($empModel->getAll(), fn($e) => $e['tipe_gaji'] === 'borongan');

        $prodModel = new Product();
        $products = $prodModel->getAllWithGroup();

        $prodDataModel = new Production();
        $productions = $prodDataModel->getByDate($date);

        view('productions/index', [
            'title'       => 'Produksi Borongan – Salary',
            'pageTitle'   => 'Produksi Borongan',
            'pageKey'     => 'productions',
            'date'        => $date,
            'employees'   => $employees,
            'products'    => $products,
            'productions' => $productions
        ]);
    }

    public function loadForm(): void
    {
        checkPermission('production');
        
        $date = $_GET['date'] ?? date('Y-m-d');

        // Karyawan HANYA yang tipe borongan (baik aktif maupun non-aktif untuk render tabel riwayat)
        $empModel = new Employee();
        $employees = array_filter($empModel->getAll(), fn($e) => $e['tipe_gaji'] === 'borongan');

        // Ambil semua produk
        $prodModel = new Product();
        $products = $prodModel->getAllWithGroup();

        $prodDataModel = new Production();
        $productions = $prodDataModel->getByDate($date);

        view('productions/_form', [
            'date'        => $date,
            'employees'   => $employees,
            'products'    => $products,
            'productions' => $productions
        ], 'partials');
    }

    public function store(): void
    {
        checkPermission('production');
        validateCsrfToken();

        $date       = $_POST['date'] ?? '';
        $employeeId = (int) ($_POST['id_karyawan'] ?? 0);
        $itemsRaw   = $_POST['items'] ?? []; // Array of ['id_produk', 'kuantitas', 'kuantitas_bal']

        if (!$date) {
            echo renderAlert('danger', 'Tanggal tidak valid.');
            return;
        }

        if ($employeeId <= 0) {
            echo renderAlert('danger', 'Silakan pilih karyawan terlebih dahulu.');
            return;
        }

        // Agregasi dan bersihkan nilai input
        $cleanItemsMap = [];
        foreach ($itemsRaw as $item) {
            $pId = (int) ($item['id_produk'] ?? 0);
            if ($pId <= 0) continue;

            $qty = parseRupiah($item['kuantitas'] ?? '0');
            $bal = parseRupiah($item['kuantitas_bal'] ?? '0');

            if (!isset($cleanItemsMap[$pId])) {
                $cleanItemsMap[$pId] = [
                    'id_produk'   => $pId,
                    'kuantitas'     => 0,
                    'kuantitas_bal' => 0
                ];
            }
            
            $cleanItemsMap[$pId]['kuantitas'] += $qty;
            $cleanItemsMap[$pId]['kuantitas_bal'] += $bal;
        }
        
        $cleanItems = array_values($cleanItemsMap);

        if (empty($cleanItems)) {
            echo renderAlert('warning', 'Harap masukkan sekurang-kurangnya 1 produk dengan jumlah lebih dari 0.');
            return;
        }

        $prodModel = new Production();
        try {
            $db = getDB();
            $check = $db->prepare("SELECT COUNT(*) FROM produksi WHERE id_karyawan = ? AND date = ?");
            $check->execute([$employeeId, $date]);
            $isUpdate = $check->fetchColumn() > 0;

            $prodModel->saveEmployeeProduction($date, $employeeId, $cleanItems);
            
            $empModel = new Employee();
            $emp = $empModel->findById($employeeId);
            $empName = $emp ? $emp['name'] : 'Karyawan';

            $titleText = $isUpdate ? 'Diperbarui!' : 'Berhasil!';
            $descText = $isUpdate ? 'Data produksi <strong class="text-dark">%s</strong> tanggal <strong class="text-dark">%s</strong> berhasil ditambahkan ke riwayat.' : 'Data produksi <strong class="text-dark">%s</strong> tanggal <strong class="text-dark">%s</strong> telah tersimpan.';
            $sessionText = $isUpdate ? 'Data produksi %s berhasil ditambahkan ke riwayat.' : 'Data produksi %s berhasil disimpan.';

            $msg = sprintf(
                '<div class="position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center" style="z-index: 1055; background: rgba(0,0,0,0.5);" id="production-success-overlay" onclick="this.remove()">' .
                    '<div class="card border-0 shadow-lg" style="max-width: 400px; width: 90%%; animation: popIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);" onclick="event.stopPropagation()">' .
                        '<div class="card-body p-4 text-center">' .
                            '<div class="mb-3 text-success">' .
                                '<i class="bi bi-check-circle-fill" style="font-size: 4rem;"></i>' .
                            '</div>' .
                            '<h4 class="fw-bold mb-2">%s</h4>' .
                            '<p class="text-muted mb-4">' . $descText . '</p>' .
                            '<button type="button" class="btn btn-primary px-5 rounded-pill shadow-sm" onclick="document.getElementById(\'production-success-overlay\').remove()">Oke, Tutup</button>' .
                        '</div>' .
                    '</div>' .
                    '<style>@keyframes popIn { 0%% { opacity: 0; transform: scale(0.8); } 100%% { opacity: 1; transform: scale(1); } }</style>' .
                '</div>',
                $titleText,
                e($empName),
                e(formatTanggal($date))
            );
            
            if (isHtmx()) {
                echo $msg;
                $this->loadForm();
            } else {
                $_SESSION['flash_success'] = sprintf($sessionText, e($empName));
                redirect('/productions?date=' . urlencode($date));
            }
        } catch (\Exception $e) {
            $msg = renderAlert('danger', 'Gagal menyimpan produksi: ' . e($e->getMessage()), 5000);
            if (isHtmx()) {
                echo $msg;
            } else {
                $_SESSION['flash_error'] = 'Gagal menyimpan produksi: ' . e($e->getMessage());
                redirect('/productions?date=' . urlencode($date));
            }
        }
    }

    public function destroyEmployee(): void
    {
        checkPermission('production');
        validateCsrfToken();

        $date       = $_POST['date'] ?? date('Y-m-d');
        $employeeId = (int) ($_POST['id_karyawan'] ?? 0);
        $source     = $_POST['source'] ?? '';

        if ($date && $employeeId > 0) {
            try {
                $prodModel = new Production();
                $prodModel->deleteEmployeeProduction($date, $employeeId);

                if (isHtmx() && $source !== 'history') {
                    echo renderAlert('info', 'Catatan produksi karyawan berhasil dihapus.');
                } else {
                    $_SESSION['flash_success'] = 'Catatan produksi karyawan berhasil dihapus.';
                }
            } catch (\Exception $e) {
                if (isHtmx() && $source !== 'history') {
                    echo renderAlert('danger', 'Gagal menghapus: ' . e($e->getMessage()), 5000);
                } else {
                    $_SESSION['flash_error'] = 'Gagal menghapus: ' . e($e->getMessage());
                }
            }
        }

        if (isHtmx()) {
            if ($source === 'history') {
                // Return script to trigger htmx reload on history page
                echo "<script>window.location.reload();</script>";
            } else {
                $this->loadForm();
            }
        } else {
            if ($source === 'history') {
                redirect('/productions/history');
            } else {
                redirect('/productions?date=' . urlencode($date));
            }
        }
    }

    public function history(): void
    {
        checkPermission('production');

        $startDate  = $_GET['start_date'] ?? date('Y-m-01');
        $endDate    = $_GET['end_date'] ?? date('Y-m-t');
        $employeeId = (int) ($_GET['id_karyawan'] ?? 0);

        $empModel = new Employee();
        $employees = array_filter($empModel->getAll(), fn($e) => $e['tipe_gaji'] === 'borongan');

        $prodModel = new Production();
        $history = $prodModel->getHistory($startDate, $endDate, $employeeId);

        view('productions/history', [
            'title'       => 'Riwayat Produksi – Salary',
            'pageTitle'   => 'Riwayat Produksi',
            'pageKey'     => 'productions',
            'startDate'   => $startDate,
            'endDate'     => $endDate,
            'employeeId'  => $employeeId,
            'employees'   => $employees,
            'history'     => $history
        ]);
    }
}
