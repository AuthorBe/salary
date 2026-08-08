<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\PenarikanGaji;
use App\Models\Employee;

class PenarikanGajiController
{
    public function index(): void
    {
        checkPermission('payroll');

        $model = new PenarikanGaji();
        $empModel = new Employee();

        $statusFilter = $_GET['status'] ?? '';
        $startDate = $_GET['start_date'] ?? '';
        $endDate = $_GET['end_date'] ?? '';
        $filters = [];
        if ($statusFilter) {
            $filters['status'] = $statusFilter;
        }
        if ($startDate) {
            $filters['start_date'] = $startDate;
        }
        if ($endDate) {
            $filters['end_date'] = $endDate;
        }

        $penarikan = $model->getAll($filters);
        $karyawan = $empModel->getAllActive();
        
        // Filter out non-bulanan
        $karyawanBulanan = array_filter($karyawan, function($k) {
            return $k['tipe_gaji'] === 'bulanan';
        });

        view('payroll/penarikan', [
            'title' => 'Penarikan Gaji Bulanan',
            'pageTitle' => 'Penarikan Gaji Bulanan',
            'pageKey' => 'penarikan_gaji',
            'penarikan' => $penarikan,
            'karyawan' => $karyawanBulanan,
            'statusFilter' => $statusFilter,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
    }

    public function store(): void
    {
        checkPermission('payroll');
        validateCsrfToken();

        $id_karyawan = (int)($_POST['id_karyawan'] ?? 0);
        $tanggal = trim($_POST['tanggal'] ?? '');
        $nominal = (float)parseRupiah($_POST['nominal'] ?? '0');
        $keterangan = trim($_POST['keterangan'] ?? '');

        if (!$id_karyawan || !$tanggal || $nominal <= 0) {
            $_SESSION['flash_error'] = 'Karyawan, Tanggal, dan Nominal wajib diisi dengan benar!';
            redirect('/payroll/penarikan');
            return;
        }

        $model = new PenarikanGaji();
        $limitInfo = $model->getAvailableLimit($id_karyawan, $tanggal);

        if (empty($limitInfo['limit']) && $limitInfo['total_pemasukan'] <= 0) {
            $_SESSION['flash_error'] = 'Karyawan tidak valid atau belum diatur Gaji Pokoknya!';
            redirect('/payroll/penarikan');
            return;
        }

        if ($nominal > $limitInfo['limit']) {
            $limitRp = number_format($limitInfo['limit'], 0, ',', '.');
            $_SESSION['flash_error'] = "Nominal melebihi batas maksimal penarikan bulan ini (Maksimal Rp $limitRp).";
            redirect('/payroll/penarikan');
            return;
        }

        try {
            $model->store([
                'id_karyawan' => $id_karyawan,
                'tanggal' => $tanggal,
                'nominal' => $nominal,
                'keterangan' => $keterangan
            ]);
            $_SESSION['flash_success'] = 'Penarikan gaji berhasil dicatat!';
        } catch (\Exception $e) {
            $_SESSION['flash_error'] = 'Gagal menyimpan data: ' . $e->getMessage();
        }

        redirect('/payroll/penarikan');
    }

    public function destroy(): void
    {
        checkPermission('payroll');
        validateCsrfToken();

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            $_SESSION['flash_error'] = 'ID Penarikan tidak valid!';
            redirect('/payroll/penarikan');
            return;
        }

        $model = new PenarikanGaji();
        if ($model->delete($id)) {
            $_SESSION['flash_success'] = 'Penarikan gaji berhasil dihapus!';
        } else {
            $_SESSION['flash_error'] = 'Data penarikan gagal dihapus. Mungkin sudah diproses dalam Payroll.';
        }

        redirect('/payroll/penarikan');
    }

    public function exportPdf(): void
    {
        checkPermission('payroll');

        $model = new PenarikanGaji();

        $statusFilter = $_GET['status'] ?? '';
        $startDate = $_GET['start_date'] ?? '';
        $endDate = $_GET['end_date'] ?? '';
        
        $filters = [];
        if ($statusFilter) {
            $filters['status'] = $statusFilter;
        }
        if ($startDate) {
            $filters['start_date'] = $startDate;
        }
        if ($endDate) {
            $filters['end_date'] = $endDate;
        }

        $penarikan = $model->getAll($filters);

        ob_start();
        include APP_ROOT . '/app/Views/payroll/penarikan_pdf.php';
        $html = ob_get_clean();

        $filename = 'Laporan_Penarikan_Gaji';
        if ($startDate && $endDate) {
            $filename .= '_' . $startDate . '_sd_' . $endDate;
        } elseif ($startDate) {
            $filename .= '_Dari_' . $startDate;
        } elseif ($endDate) {
            $filename .= '_Sampai_' . $endDate;
        }
        
        if ($statusFilter) {
            $statusText = $statusFilter === 'pending' ? 'Belum_Payroll' : 'Sudah_Payroll';
            $filename .= '_' . $statusText;
        }
        
        if (!$startDate && !$endDate && !$statusFilter) {
            $filename .= '_Semua_' . date('Ymd');
        }

        streamPdf($html, $filename, 'A4', 'portrait');
    }
}
