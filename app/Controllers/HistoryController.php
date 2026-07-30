<?php
namespace App\Controllers;

use App\Models\PayrollRun;
use App\Models\Employee;

class HistoryController
{
    public function index(): void
    {
        checkPermission('payroll_history');

        $db = getDB();
        
        $employee_id = $_GET['employee_id'] ?? '';
        $run_id = $_GET['run_id'] ?? '';
        $type = $_GET['type'] ?? '';

        // Base query for approved items
        $query = "
            SELECT 
                pi.id, pi.gaji_bersih, pi.hari_hadir, pi.total_uang_kehadiran, 
                pi.total_upah_produksi, pi.total_potongan_kasbon,
                e.name as employee_name, e.tipe_gaji,
                pr.periode_awal, pr.periode_akhir, pr.type as run_type, pr.id as run_id, pr.disetujui_pada, pr.name as run_name
            FROM rincian_penggajian pi
            JOIN penggajian pr ON pi.id_penggajian = pr.id
            JOIN karyawan e ON pi.id_karyawan = e.id
            WHERE pr.status = 'approved'
        ";
        
        $params = [];

        if ($employee_id !== '') {
            $query .= " AND pi.id_karyawan = ?";
            $params[] = $employee_id;
        }

        if ($run_id !== '') {
            $query .= " AND pr.id = ?";
            $params[] = $run_id;
        }

        if ($type !== '') {
            $query .= " AND e.tipe_gaji = ?";
            $params[] = $type;
        }

        $query .= " ORDER BY pr.disetujui_pada DESC, e.name ASC";

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $histories = $stmt->fetchAll();

        // Get dropdown data
        $empStmt = $db->query("SELECT id, name FROM karyawan ORDER BY name ASC");
        $employees = $empStmt->fetchAll();

        $runStmt = $db->query("SELECT id, type, periode_awal, periode_akhir, name FROM penggajian WHERE status = 'approved' ORDER BY id DESC");
        $runs = $runStmt->fetchAll();

        $pageGuide = '
            <p>Di halaman <strong>Riwayat Gaji</strong>, Anda dapat menelusuri seluruh data penggajian yang telah disetujui (Approved) secara permanen.</p>
            <ul class="mb-0 text-muted">
                <li class="mb-2">Gunakan fitur <strong>Filter Pencarian</strong> di atas untuk menemukan slip berdasarkan Karyawan, Periode (Run ID), atau Tipe Gaji (Bulanan/Borongan).</li>
                <li class="mb-2">Kolom <strong>Run #ID</strong> menunjukkan dari siklus penggajian mana data tersebut berasal.</li>
                <li>Klik tombol <span class="badge bg-danger">Slip</span> pada setiap baris untuk mengunduh slip gaji individu tersebut dalam format PDF ukuran A5 yang siap cetak.</li>
            </ul>
        ';

        view('history/index', [
            'title'     => 'Riwayat Gaji – Salary',
            'pageTitle' => 'Riwayat Gaji',
            'pageKey'   => 'payroll_history',
            'pageGuide' => $pageGuide,
            'histories' => $histories,
            'employees' => $employees,
            'runs'      => $runs,
            'filters'   => [
                'employee_id' => $employee_id,
                'run_id'      => $run_id,
                'type'        => $type,
            ]
        ]);
    }

    public function downloadSlip(): void
    {
        checkPermission('payroll_history');

        $id = (int)($_GET['id'] ?? 0);
        
        $db = getDB();
        $stmt = $db->prepare("
            SELECT 
                pi.*,
                e.name as employee_name, e.tipe_gaji,
                pr.periode_awal, pr.periode_akhir, pr.type as run_type, pr.disetujui_pada, pr.name as run_name
            FROM rincian_penggajian pi
            JOIN penggajian pr ON pi.id_penggajian = pr.id
            JOIN karyawan e ON pi.id_karyawan = e.id
            WHERE pi.id = ? AND pr.status = 'approved'
        ");
        $stmt->execute([$id]);
        $data = $stmt->fetch();

        if (!$data) {
            $_SESSION['flash_error'] = 'Data riwayat tidak ditemukan atau belum disetujui.';
            redirect('/history');
            return;
        }

        ob_start();
        include APP_ROOT . '/app/Views/history/pdf_slip.php';
        $html = ob_get_clean();

        $runName = $data['run_name'] ?: ('Run ' . $data['id_penggajian']);
        $safeRunName = preg_replace('/[^A-Za-z0-9_\- ]/', '', $runName);
        $safeEmpName = preg_replace('/[^A-Za-z0-9_\- ]/', '', $data['employee_name']);
        $filename = 'Slip ' . $safeEmpName . ' - ' . $safeRunName;
        
        streamPdf($html, $filename, 'A4', 'portrait');
    }

    public function downloadSlipsBatch(): void
    {
        checkPermission('payroll_history');

        $run_id = (int)($_GET['run_id'] ?? 0);
        
        $db = getDB();

        // Cek validasi run_id
        $stmtRun = $db->prepare("SELECT * FROM penggajian WHERE id = ? AND status = 'approved'");
        $stmtRun->execute([$run_id]);
        $run = $stmtRun->fetch();

        if (!$run) {
            $_SESSION['flash_error'] = 'Periode penggajian tidak ditemukan atau belum disetujui.';
            redirect('/reports'); // Kembali ke laporan atau history
            return;
        }

        $stmt = $db->prepare("
            SELECT 
                pi.*,
                e.name as employee_name, e.tipe_gaji,
                pr.periode_awal, pr.periode_akhir, pr.type as run_type, pr.disetujui_pada, pr.name as run_name
            FROM rincian_penggajian pi
            JOIN penggajian pr ON pi.id_penggajian = pr.id
            JOIN karyawan e ON pi.id_karyawan = e.id
            WHERE pr.id = ? AND pr.status = 'approved'
            ORDER BY e.name ASC
        ");
        $stmt->execute([$run_id]);
        $items = $stmt->fetchAll();

        if (empty($items)) {
            $_SESSION['flash_error'] = 'Tidak ada slip gaji untuk periode ini.';
            redirect('/reports');
            return;
        }

        ob_start();
        include APP_ROOT . '/app/Views/history/pdf_slips_batch.php';
        $html = ob_get_clean();

        $runName = $run['name'] ?: ('Run ' . $run_id);
        $safeRunName = preg_replace('/[^A-Za-z0-9_\- ]/', '', $runName);
        $filename = 'Batch Slip ' . $safeRunName;
        
        streamPdf($html, $filename, 'A4', 'portrait');
    }
}

