<?php
namespace App\Controllers;

class ReportController
{
    public function index(): void
    {
        checkPermission('reports_owner');

        $db = getDB();
        try {
            $db->exec("ALTER TABLE penggajian ADD COLUMN name VARCHAR(255) DEFAULT NULL;");
        } catch (\Exception $e) {}

        // Ambil summary dari setiap payroll_runs yang di-approve
        // Menggunakan subquery agregasi dari rincian_penggajian
        $stmt = $db->query("
            SELECT 
                pr.id, pr.periode_awal, pr.periode_akhir, pr.type, pr.disetujui_pada, pr.name as run_name,
                COUNT(pi.id) as total_karyawan,
                SUM(pi.gaji_bersih) as total_pengeluaran,
                SUM(pi.total_potongan_kasbon + pi.potongan_lain) as total_potongan
            FROM penggajian pr
            LEFT JOIN rincian_penggajian pi ON pr.id = pi.id_penggajian
            WHERE pr.status = 'approved'
            GROUP BY pr.id
            ORDER BY pr.disetujui_pada DESC
        ");
        
        $reports = $stmt->fetchAll();

        // Chart Data (6 bulan terakhir / 6 run terakhir)
        $chartData = [];
        $chartLabels = [];
        $chartValues = [];
        foreach (array_slice(array_reverse($reports), -6) as $r) {
            $runName = $r['run_name'] ?: ('Run #' . $r['id']);
            $chartLabels[] = $runName . ' (' . date('d M', strtotime($r['periode_awal'])) . ')';
            $chartValues[] = (float)$r['total_pengeluaran'];
        }

        $pageGuide = '
            <p><strong>Laporan Owner</strong> dirancang khusus sebagai dasbor pantauan finansial (kesehatan keuangan) atas beban gaji karyawan.</p>
            <ul class="mb-0 text-muted">
                <li class="mb-2"><strong>Grafik Tren Pengeluaran:</strong> Menampilkan visualisasi naik/turunnya beban gaji pada 6 siklus periode terakhir. Sangat membantu untuk mendeteksi lonjakan biaya tak terduga.</li>
                <li class="mb-2"><strong>Tabel Rekapitulasi:</strong> Memuat rincian total karyawan, total hutang/potongan, dan total bersih yang dibayarkan ke karyawan per periodenya.</li>
                <li class="mb-2"><strong>Rekap Total:</strong> Tombol merah ini akan mengekspor Laporan Tabel Detil berformat PDF (A4 Lanskap) yang merinci nama-nama karyawan berserta seluruh nominal rinciannya di periode tersebut. Cocok untuk arsip fisik keuangan.</li>
                <li><strong>Cetak Slips:</strong> Tombol ini akan otomatis mengumpulkan dan mempaketkan RATUSAN slip gaji karyawan di periode itu menjadi 1 buah file PDF massal siap cetak (1 lembar per karyawan).</li>
            </ul>
        ';

        view('reports/index', [
            'title'       => 'Laporan Owner – Salary',
            'pageTitle'   => 'Laporan Owner / Dashboard Keuangan',
            'pageKey'     => 'reports_owner',
            'pageGuide'   => $pageGuide,
            'reports'     => $reports,
            'chartLabels' => json_encode($chartLabels),
            'chartValues' => json_encode($chartValues),
        ]);
    }

    public function exportPdf(): void
    {
        checkPermission('reports_owner');

        $runId = (int)($_GET['id'] ?? 0);
        $db = getDB();

        // Ambil info payroll run
        $stmtRun = $db->prepare("SELECT * FROM penggajian WHERE id = ? AND status = 'approved'");
        $stmtRun->execute([$runId]);
        $run = $stmtRun->fetch();

        if (!$run) {
            $_SESSION['flash_error'] = 'Data payroll tidak valid atau belum disetujui.';
            redirect('/reports');
            return;
        }

        // Ambil semua rincian
        $stmtItems = $db->prepare("
            SELECT pi.*, e.name as employee_name, e.tipe_gaji
            FROM rincian_penggajian pi
            JOIN karyawan e ON pi.id_karyawan = e.id
            WHERE pi.id_penggajian = ?
            ORDER BY e.name ASC
        ");
        $stmtItems->execute([$runId]);
        $items = $stmtItems->fetchAll();

        ob_start();
        include APP_ROOT . '/app/Views/reports/pdf_rekap.php';
        $html = ob_get_clean();

        $runName = $run['name'] ?: ('Run ' . $runId);
        $safeRunName = preg_replace('/[^A-Za-z0-9_\- ]/', '', $runName);
        $filename = 'Laporan Rekap ' . $safeRunName;
        
        // A4 Landscape lebih cocok untuk tabel lebar
        streamPdf($html, $filename, 'A4', 'landscape');
    }
}
