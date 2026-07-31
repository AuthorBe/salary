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

        // Ambil summary per bulan dari payroll_runs yang di-approve
        // Menggunakan subquery agregasi dari rincian_penggajian
        $stmt = $db->query("
            SELECT 
                DATE_FORMAT(pr.periode_akhir, '%Y-%m') as bulan,
                COUNT(DISTINCT pi.id_karyawan) as total_karyawan,
                SUM(pi.gaji_bersih) as total_pengeluaran,
                SUM(pi.total_potongan_kasbon + pi.potongan_lain) as total_potongan
            FROM penggajian pr
            JOIN rincian_penggajian pi ON pr.id = pi.id_penggajian
            WHERE pr.status = 'approved'
            GROUP BY DATE_FORMAT(pr.periode_akhir, '%Y-%m')
            ORDER BY bulan DESC
        ");
        
        $reports = $stmt->fetchAll();

        // Chart Data (6 bulan terakhir)
        $chartData = [];
        $chartLabels = [];
        $chartValues = [];
        foreach (array_slice(array_reverse($reports), -6) as $r) {
            $bulanName = date('M Y', strtotime($r['bulan'] . '-01'));
            $chartLabels[] = $bulanName;
            $chartValues[] = (float)$r['total_pengeluaran'];
        }

        $pageGuide = '
            <p><strong>Laporan Owner</strong> dirancang khusus sebagai dasbor pantauan finansial (kesehatan keuangan) atas beban gaji karyawan.</p>
            <ul class="mb-0 text-muted">
                <li class="mb-2"><strong>Grafik Tren Pengeluaran:</strong> Menampilkan visualisasi naik/turunnya beban gaji pada 6 bulan terakhir. Sangat membantu untuk mendeteksi lonjakan biaya tak terduga.</li>
                <li class="mb-2"><strong>Tabel Rekapitulasi:</strong> Memuat rincian total karyawan unik, total hutang/potongan, dan total bersih yang dibayarkan ke karyawan per bulannya.</li>
                <li><strong>Cetak Laporan:</strong> Tombol merah ini akan mengekspor Laporan Tabel Detil berformat PDF (A4 Lanskap) yang merinci nama-nama karyawan berserta seluruh nominal rinciannya di bulan tersebut yang dikelompokkan berdasarkan tipe karyawan.</li>
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

    public function exportPdfBulan(): void
    {
        checkPermission('reports_owner');

        $bulan = $_GET['bulan'] ?? '';
        if (!preg_match('/^\d{4}-\d{2}$/', $bulan)) {
            $_SESSION['flash_error'] = 'Format bulan tidak valid.';
            redirect('/reports');
            return;
        }

        $db = getDB();

        // Ambil semua rincian yang disetujui di bulan ini, kelompokkan per karyawan
        $stmtItems = $db->prepare("
            SELECT 
                e.id as id_karyawan,
                e.name as employee_name, 
                e.tipe_gaji,
                SUM(pi.gaji_pokok) as gaji_pokok,
                SUM(pi.hari_hadir) as hari_hadir,
                SUM(pi.total_uang_kehadiran) as total_uang_kehadiran,
                SUM(pi.total_upah_produksi) as total_upah_produksi,
                SUM(pi.total_upah_lembur) as total_upah_lembur,
                SUM(pi.tunjangan_bulanan) as tunjangan_bulanan,
                SUM(pi.tunjangan_lain) as tunjangan_lain,
                SUM(pi.total_potongan_kasbon) as total_potongan_kasbon,
                SUM(pi.potongan_lain) as potongan_lain,
                SUM(pi.gaji_bersih) as gaji_bersih
            FROM rincian_penggajian pi
            JOIN penggajian pr ON pi.id_penggajian = pr.id
            JOIN karyawan e ON pi.id_karyawan = e.id
            WHERE pr.status = 'approved' 
              AND DATE_FORMAT(pr.periode_akhir, '%Y-%m') = ?
            GROUP BY e.id, e.name, e.tipe_gaji
            ORDER BY e.tipe_gaji ASC, e.name ASC
        ");
        $stmtItems->execute([$bulan]);
        $items = $stmtItems->fetchAll();

        if (empty($items)) {
            $_SESSION['flash_error'] = 'Tidak ada data penggajian untuk bulan tersebut.';
            redirect('/reports');
            return;
        }

        $bulanName = date('F Y', strtotime($bulan . '-01'));

        ob_start();
        include APP_ROOT . '/app/Views/reports/pdf_rekap_bulanan.php';
        $html = ob_get_clean();

        $filename = 'Rekap Gajian Bulanan ' . $bulanName;
        
        // A4 Landscape lebih cocok untuk tabel lebar
        streamPdf($html, $filename, 'A4', 'landscape');
    }
}
