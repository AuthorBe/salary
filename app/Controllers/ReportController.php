<?php
namespace App\Controllers;

class ReportController
{
    /**
     * Hitung bulan mayoritas (modus) berdasarkan tanggal terbanyak dalam rentang payroll.
     * Contoh: 25 Jul - 1 Ags (7 hari Juli, 1 hari Ags) => '2026-07'
     */
    protected function getMajorityMonth(?string $startDate, ?string $endDate): string
    {
        if (empty($startDate) || empty($endDate)) {
            return date('Y-m');
        }

        try {
            $start = new \DateTime($startDate);
            $end = new \DateTime($endDate);
            
            if ($start > $end) {
                return $start->format('Y-m');
            }
            
            $monthCounts = [];
            $curr = clone $start;
            while ($curr <= $end) {
                $ym = $curr->format('Y-m');
                $monthCounts[$ym] = ($monthCounts[$ym] ?? 0) + 1;
                $curr->modify('+1 day');
            }
            
            arsort($monthCounts);
            $first = array_key_first($monthCounts);
            return $first ? (string)$first : $start->format('Y-m');
        } catch (\Throwable $e) {
            return date('Y-m');
        }
    }

    public function index(): void
    {
        checkPermission('reports_owner');

        $db = getDB();
        try {
            $db->exec("ALTER TABLE penggajian ADD COLUMN name VARCHAR(255) DEFAULT NULL;");
        } catch (\Exception $e) {}

        // 1. Ambil seluruh run payroll yang di-approve beserta rincian per batch
        $stmtBatches = $db->query("
            SELECT 
                pr.id,
                pr.name,
                pr.type,
                pr.periode_awal,
                pr.periode_akhir,
                pr.disetujui_pada,
                COUNT(DISTINCT CASE WHEN pi.is_excluded = 0 THEN pi.id_karyawan ELSE NULL END) as total_karyawan,
                SUM(CASE WHEN pi.is_excluded = 0 THEN pi.gaji_bersih ELSE 0 END) as total_pengeluaran,
                SUM(CASE WHEN pi.is_excluded = 0 THEN (pi.total_potongan_kasbon + pi.potongan_lain + COALESCE(pi.total_penarikan_gaji, 0) + COALESCE(pi.potongan_tabungan, 0)) ELSE 0 END) as total_potongan
            FROM penggajian pr
            JOIN rincian_penggajian pi ON pr.id = pi.id_penggajian
            WHERE pr.status = 'approved'
            GROUP BY pr.id
            ORDER BY pr.periode_akhir DESC, pr.id DESC
        ");
        $rawApprovedRuns = $stmtBatches ? $stmtBatches->fetchAll() : [];

        // 2. Ambil mapping karyawan unik per payroll untuk akurasi hitung total_karyawan per bulan
        $stmtEmpMapping = $db->query("
            SELECT pr.id as id_penggajian, pr.periode_awal, pr.periode_akhir, pi.id_karyawan
            FROM penggajian pr
            JOIN rincian_penggajian pi ON pr.id = pi.id_penggajian
            WHERE pr.status = 'approved' AND pi.is_excluded = 0
        ");
        $empMappings = $stmtEmpMapping ? $stmtEmpMapping->fetchAll() : [];

        $monthlyUniqueEmployees = [];
        foreach ($empMappings as $em) {
            $mMonth = $this->getMajorityMonth($em['periode_awal'], $em['periode_akhir']);
            $monthlyUniqueEmployees[$mMonth][$em['id_karyawan']] = true;
        }

        // 3. Kelompokkan batch payroll berdasarkan Bulan Mayoritas (Modus Tanggal Terbanyak)
        $allApprovedRuns = [];
        $monthlyBatches = [];
        $reportsMap = [];

        foreach ($rawApprovedRuns as $batch) {
            $assignedMonth = $this->getMajorityMonth($batch['periode_awal'], $batch['periode_akhir']);
            $batch['bulan'] = $assignedMonth;
            $allApprovedRuns[] = $batch;

            if (!isset($reportsMap[$assignedMonth])) {
                $reportsMap[$assignedMonth] = [
                    'bulan'             => $assignedMonth,
                    'total_batch'       => 0,
                    'total_karyawan'    => 0,
                    'total_pengeluaran' => 0,
                    'total_potongan'    => 0,
                ];
            }

            $reportsMap[$assignedMonth]['total_batch']++;
            $reportsMap[$assignedMonth]['total_pengeluaran'] += (float)$batch['total_pengeluaran'];
            $reportsMap[$assignedMonth]['total_potongan'] += (float)$batch['total_potongan'];
            $monthlyBatches[$assignedMonth][] = $batch;
        }

        // Set total karyawan unik per bulan
        foreach ($reportsMap as $mKey => &$rData) {
            $rData['total_karyawan'] = count($monthlyUniqueEmployees[$mKey] ?? []);
        }
        unset($rData);

        // Urutkan laporan bulanan dari bulan terbaru
        krsort($reportsMap);
        $reports = array_values($reportsMap);

        // 4. Chart Data (6 bulan terakhir)
        $chartLabels = [];
        $chartValues = [];
        foreach (array_slice(array_reverse($reports), -6) as $r) {
            $bulanName = date('M Y', strtotime($r['bulan'] . '-01'));
            $chartLabels[] = $bulanName;
            $chartValues[] = (float)$r['total_pengeluaran'];
        }

        $pageGuide = '
            <div class="mb-3">
                <p class="text-dark fw-medium mb-1">Selamat datang di <strong>Laporan Finansial Owner</strong>!</p>
                <p class="text-muted small mb-0">Halaman ini dirancang khusus sebagai dasbor kendali eksekutif untuk menganalisis, mengelompokkan, dan mengekspor rekapitulasi pengeluaran gaji karyawan dengan akurat.</p>
            </div>

            <div class="d-flex flex-column gap-2.5">
                <div class="p-3 rounded-3 bg-light border">
                    <div class="fw-bold text-dark small mb-1">
                        <i class="bi bi-calendar-event text-primary me-1.5"></i> 1. Aturan Bulan Mayoritas (Modus Hari)
                    </div>
                    <div class="text-secondary small" style="line-height: 1.5;">
                        Batch payroll yang melintasi dua bulan (misal: <em>25 Juli – 1 Agustus</em>) otomatis dikelompokkan ke bulan dengan jumlah hari terbanyak (7 hari Juli vs 1 hari Agustus = masuk ke <strong>Bulan Juli</strong>).
                    </div>
                </div>

                <div class="p-3 rounded-3 bg-light border">
                    <div class="fw-bold text-dark small mb-1">
                        <i class="bi bi-calendar3 text-info me-1.5"></i> 2. Mode Rekapitulasi Bulanan
                    </div>
                    <div class="text-secondary small" style="line-height: 1.5;">
                        Menampilkan total beban pengeluaran per bulan kalender. Klik pada baris bulan untuk membuka dropdown rincian siklus penggajian. Anda bisa mencetak <strong>Seluruh Bulan</strong> atau <strong>Batch Pilihan</strong> saja.
                    </div>
                </div>

                <div class="p-3 rounded-3 bg-light border">
                    <div class="fw-bold text-dark small mb-1">
                        <i class="bi bi-sliders text-warning me-1.5"></i> 3. Mode Laporan Kustom & Multi-Batch
                    </div>
                    <div class="text-secondary small" style="line-height: 1.5;">
                        Memberi kebebasan penuh untuk menggabungkan batch payroll pilihan Anda secara fleksibel menggunakan filter rentang tanggal, pencarian nama, dan live counter KPI.
                    </div>
                </div>

                <div class="p-3 rounded-3 bg-light border">
                    <div class="fw-bold text-dark small mb-1">
                        <i class="bi bi-file-earmark-pdf text-danger me-1.5"></i> 4. Cetak PDF Rekap & Lampiran Absensi
                    </div>
                    <div class="text-secondary small" style="line-height: 1.5;">
                        Laporan PDF otomatis menyesuaikan daftar batch, rincian per karyawan, rekap nominal kasbon/tabungan, serta lampiran absensi harian yang sinkron dengan periode batch yang Anda cetak.
                    </div>
                </div>
            </div>
        ';

        // 4. Chart Data
        // A. Per Bulan (6 bulan terakhir)
        $chartMonthlyLabels = [];
        $chartMonthlyValues = [];
        foreach (array_slice(array_reverse($reports), -6) as $r) {
            $bulanName = date('M Y', strtotime($r['bulan'] . '-01'));
            $chartMonthlyLabels[] = $bulanName;
            $chartMonthlyValues[] = (float)$r['total_pengeluaran'];
        }

        // B. Per Batch Payroll (10 batch terakhir secara kronologis)
        $chartBatchLabels = [];
        $chartBatchValues = [];
        $recentRuns = array_reverse(array_slice($allApprovedRuns, 0, 10));
        foreach ($recentRuns as $run) {
            $tName = $run['type'] === 'weekly' ? 'Borongan' : ($run['type'] === 'monthly' ? 'Bulanan' : 'Campuran');
            $label = !empty($run['name']) ? $run['name'] : ('Batch #' . $run['id'] . ' (' . $tName . ')');
            $chartBatchLabels[] = $label;
            $chartBatchValues[] = (float)$run['total_pengeluaran'];
        }

        view('reports/index', [
            'title'               => 'Laporan Owner – Salary',
            'pageTitle'           => 'Laporan Owner / Dashboard Keuangan',
            'pageKey'             => 'reports_owner',
            'pageGuide'           => $pageGuide,
            'reports'             => $reports,
            'monthlyBatches'      => $monthlyBatches,
            'allApprovedRuns'     => $allApprovedRuns,
            'chartMonthlyLabels'  => json_encode($chartMonthlyLabels),
            'chartMonthlyValues'  => json_encode($chartMonthlyValues),
            'chartBatchLabels'    => json_encode($chartBatchLabels),
            'chartBatchValues'    => json_encode($chartBatchValues),
        ]);
    }

    public function exportPdfBulan(): void
    {
        checkPermission('reports_owner');

        $db = getDB();
        $payrollIds = [];
        $bulanParam = trim($_GET['bulan'] ?? '');
        $startDateParam = trim($_GET['start_date'] ?? '');
        $endDateParam = trim($_GET['end_date'] ?? '');
        $idsParam = $_GET['payroll_ids'] ?? null;

        // 1. Cek jika diberikan list ID spesifik (bisa string koma atau array)
        if (!empty($idsParam)) {
            $rawIds = is_array($idsParam) ? $idsParam : explode(',', (string)$idsParam);
            $payrollIds = array_values(array_filter(array_map('intval', $rawIds), fn($id) => $id > 0));
        }

        // 2. Jika tidak ada list ID, cek parameter bulan (YYYY-MM) menggunakan aturan Bulan Mayoritas
        if (empty($payrollIds) && $bulanParam !== '') {
            if (preg_match('/^\d{4}-\d{2}$/', $bulanParam)) {
                $stmt = $db->query("SELECT id, periode_awal, periode_akhir FROM penggajian WHERE status = 'approved'");
                $allRuns = $stmt ? $stmt->fetchAll() : [];
                foreach ($allRuns as $run) {
                    if ($this->getMajorityMonth($run['periode_awal'], $run['periode_akhir']) === $bulanParam) {
                        $payrollIds[] = (int)$run['id'];
                    }
                }
            }
        }

        // 3. Jika tidak ada bulan, cek rentang tanggal kustom (start_date & end_date)
        if (empty($payrollIds) && $startDateParam !== '' && $endDateParam !== '') {
            $stmt = $db->prepare("SELECT id FROM penggajian WHERE status = 'approved' AND (periode_awal <= ? AND periode_akhir >= ?)");
            $stmt->execute([$endDateParam, $startDateParam]);
            $payrollIds = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        }

        if (empty($payrollIds)) {
            $_SESSION['flash_error'] = 'Tidak ada data payroll yang ditemukan atau dipilih untuk dicetak.';
            redirect('/reports');
            return;
        }

        // Ambil header data payroll runs terpilih
        $placeholders = implode(',', array_fill(0, count($payrollIds), '?'));
        $stmtRuns = $db->prepare("
            SELECT id, name, type, periode_awal, periode_akhir, disetujui_pada 
            FROM penggajian 
            WHERE id IN ($placeholders) AND status = 'approved'
            ORDER BY periode_awal ASC, id ASC
        ");
        $stmtRuns->execute($payrollIds);
        $selectedRuns = $stmtRuns->fetchAll();

        if (empty($selectedRuns)) {
            $_SESSION['flash_error'] = 'Data payroll tidak ditemukan.';
            redirect('/reports');
            return;
        }

        // Tentukan rentang tanggal aktual dan daftar batch
        $allStarts = array_column($selectedRuns, 'periode_awal');
        $allEnds = array_column($selectedRuns, 'periode_akhir');
        $minStart = min($allStarts);
        $maxEnd = max($allEnds);

        $batchTitles = [];
        foreach ($selectedRuns as $r) {
            $typeName = $r['type'] === 'weekly' ? 'Borongan' : ($r['type'] === 'monthly' ? 'Bulanan' : 'Campuran');
            $bName = !empty($r['name']) ? $r['name'] : 'Gaji ' . $typeName . ' (' . formatTanggalShort($r['periode_awal']) . ' – ' . formatTanggalShort($r['periode_akhir']) . ')';
            $batchTitles[] = $bName;
        }

        // Tentukan Judul Laporan
        if ($bulanParam !== '' && preg_match('/^\d{4}-\d{2}$/', $bulanParam)) {
            $bulanName = date('F Y', strtotime($bulanParam . '-01'));
            $reportTitle = 'REKAPITULASI PEMBAYARAN GAJI BULANAN';
            $periodLabel = $bulanName . ' (' . count($selectedRuns) . ' Batch)';
            $filename = 'Rekap Gajian Bulanan ' . $bulanName;
        } else {
            $bulanName = date('d M Y', strtotime($minStart)) . ' – ' . date('d M Y', strtotime($maxEnd));
            $reportTitle = 'REKAPITULASI PENGGAJIAN EKSEKUTIF';
            $periodLabel = formatTanggal($minStart) . ' s.d. ' . formatTanggal($maxEnd) . ' (' . count($selectedRuns) . ' Batch)';
            $filename = 'Rekap Penggajian ' . date('Ymd', strtotime($minStart)) . '-' . date('Ymd', strtotime($maxEnd));
        }

        // Ambil agregasi rincian penggajian karyawan dari payroll yang dipilih
        $stmtItems = $db->prepare("
            SELECT 
                e.id as id_karyawan,
                e.name as employee_name, 
                e.tipe_gaji,
                SUM(CASE WHEN pi.is_excluded = 0 THEN pi.gaji_pokok ELSE 0 END) as gaji_pokok,
                SUM(CASE WHEN pi.is_excluded = 0 THEN pi.hari_hadir ELSE 0 END) as hari_hadir,
                SUM(CASE WHEN pi.is_excluded = 0 THEN pi.total_uang_kehadiran ELSE 0 END) as total_uang_kehadiran,
                SUM(CASE WHEN pi.is_excluded = 0 THEN pi.total_upah_produksi ELSE 0 END) as total_upah_produksi,
                SUM(CASE WHEN pi.is_excluded = 0 THEN pi.total_upah_lembur ELSE 0 END) as total_upah_lembur,
                SUM(CASE WHEN pi.is_excluded = 0 THEN pi.tunjangan_bulanan ELSE 0 END) as tunjangan_bulanan,
                SUM(CASE WHEN pi.is_excluded = 0 THEN pi.tunjangan_lain ELSE 0 END) as tunjangan_lain,
                SUM(CASE WHEN pi.is_excluded = 0 THEN pi.nominal_pembulatan ELSE 0 END) as nominal_pembulatan,
                SUM(CASE WHEN pi.is_excluded = 0 THEN pi.penarikan_tabungan ELSE 0 END) as penarikan_tabungan,
                SUM(CASE WHEN pi.is_excluded = 0 THEN pi.total_potongan_kasbon ELSE 0 END) as total_potongan_kasbon,
                SUM(CASE WHEN pi.is_excluded = 0 THEN pi.total_penarikan_gaji ELSE 0 END) as total_penarikan_gaji,
                SUM(CASE WHEN pi.is_excluded = 0 THEN pi.potongan_lain ELSE 0 END) as potongan_lain,
                SUM(CASE WHEN pi.is_excluded = 0 THEN pi.potongan_tabungan ELSE 0 END) as potongan_tabungan,
                SUM(CASE WHEN pi.is_excluded = 0 THEN pi.gaji_bersih ELSE 0 END) as gaji_bersih
            FROM rincian_penggajian pi
            JOIN penggajian pr ON pi.id_penggajian = pr.id
            JOIN karyawan e ON pi.id_karyawan = e.id
            WHERE pr.status = 'approved' 
              AND pr.id IN ($placeholders)
              AND pi.is_excluded = 0
            GROUP BY e.id, e.name, e.tipe_gaji
            ORDER BY e.tipe_gaji ASC, e.name ASC
        ");
        $stmtItems->execute($payrollIds);
        $items = $stmtItems->fetchAll();

        // Ambil absensi sesuai rentang tanggal riil dari payroll yang terpilih
        $stmtAbsensi = $db->prepare("
            SELECT id_karyawan, date, hadir, telat, catatan
            FROM absensi
            WHERE date >= ? AND date <= ?
        ");
        $stmtAbsensi->execute([$minStart, $maxEnd]);
        $absensiRaw = $stmtAbsensi->fetchAll();

        $attendanceData = [];
        foreach ($absensiRaw as $ab) {
            $attendanceData[$ab['id_karyawan']][$ab['date']] = [
                'hadir'   => (int)$ab['hadir'],
                'telat'   => (int)$ab['telat'],
                'catatan' => $ab['catatan']
            ];
        }

        $startDateStr = $minStart;
        $endDateStr = $maxEnd;
        $batchListStr = implode(' • ', $batchTitles);

        ob_start();
        include APP_ROOT . '/app/Views/reports/pdf_rekap_bulanan.php';
        $html = ob_get_clean();

        // A4 Landscape lebih cocok untuk tabel lebar
        streamPdf($html, $filename, 'A4', 'landscape');
    }
}

