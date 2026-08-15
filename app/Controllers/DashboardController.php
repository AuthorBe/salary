<?php
declare(strict_types=1);

namespace App\Controllers;

/**
 * DashboardController
 * Menangani halaman dashboard utama dan halaman demo untuk testing RBAC di Fase 0.
 *
 * Setiap method: requireLogin() → checkPermission() → render view.
 * Pattern ini dipakai konsisten di SEMUA controller di fase berikutnya.
 */
class DashboardController
{
    /**
     * PWA Manifest (Dinamic route)
     */
    public function manifest(): void
    {
        header('Content-Type: application/manifest+json');
        
        $manifest = [
            "name" => appSetting('company_name', 'SalaryApp'),
            "short_name" => "Salary",
            "start_url" => url('/dashboard'),
            "display" => "standalone",
            "background_color" => "#ffffff",
            "theme_color" => "#0078d4",
            "icons" => [
                [
                    "src" => assetUrl('favicon/web-app-manifest-192x192.png'),
                    "sizes" => "192x192",
                    "type" => "image/png",
                    "purpose" => "maskable"
                ],
                [
                    "src" => assetUrl('favicon/web-app-manifest-512x512.png'),
                    "sizes" => "512x512",
                    "type" => "image/png",
                    "purpose" => "maskable"
                ]
            ]
        ];

        echo json_encode($manifest, JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Dashboard utama.
     * Memerlukan: login + permission 'dashboard'.
     */
    public function index(): void
    {
        requireLogin();
        checkPermission('dashboard');

        $today = date('Y-m-d');

        // 1. Data Karyawan
        $empModel = new \App\Models\Employee();
        $allEmployees = $empModel->getAll();
        $activeEmployees = array_filter($allEmployees, fn($e) => (bool) $e['aktif']);
        $totalEmployees = count($activeEmployees);

        $boronganCount = count(array_filter($activeEmployees, fn($e) => $e['tipe_gaji'] === 'borongan'));
        $bulananCount  = count(array_filter($activeEmployees, fn($e) => $e['tipe_gaji'] === 'bulanan'));

        // 2. Data Kehadiran Hari Ini
        $attModel = new \App\Models\Attendance();
        $todayAttendances = $attModel->getByDate($today);
        $presentTodayCount = 0;
        foreach ($todayAttendances as $attData) {
            if (isset($attData['hadir']) && $attData['hadir']) {
                $presentTodayCount++;
            }
        }

        // 3. Data Produksi Hari Ini (Termasuk Reguler + Lembur)
        $prodModel = new \App\Models\Production();
        $todayProductions = $prodModel->getByDate($today);
        $totalBungkusToday = 0;
        $totalBalToday = 0;
        foreach ($todayProductions as $empProds) {
            foreach ($empProds as $pData) {
                $totalBungkusToday += (int) ($pData['kuantitas'] ?? 0) + (int) ($pData['lembur_kuantitas'] ?? 0);
                $totalBalToday += (int) ($pData['kuantitas_bal'] ?? 0) + (int) ($pData['lembur_kuantitas_bal'] ?? 0);
            }
        }

        // 4. Data Produk
        $productModel = new \App\Models\Product();
        $allProducts = $productModel->getAllWithGroup();
        $totalProducts = count($allProducts);

        // 5. Perhitungan Siklus Pekan & Top Produksi (Modular Formula)
        $settingModel = new \App\Models\AppSetting();
        $weekStartDay = (int) $settingModel->get('week_start_day', '1');
        
        $todayObj = new \DateTime($today);
        $currentDayOfWeek = (int)$todayObj->format('w'); // 0 (Minggu) .. 6 (Sabtu)
        $diff = ($currentDayOfWeek - $weekStartDay + 7) % 7;

        $startThisWeek = (clone $todayObj)->modify("-{$diff} days");
        $endThisWeek = (clone $startThisWeek)->modify('+6 days');

        $startLastWeek = (clone $startThisWeek)->modify('-7 days');
        $endLastWeek = (clone $startThisWeek)->modify('-1 day');

        $start7Days = (clone $todayObj)->modify('-6 days');
        $end7Days = clone $todayObj;

        $startOfWeek = $startThisWeek->format('Y-m-d');
        $endOfWeek = $endThisWeek->format('Y-m-d');
        $startOfLastWeek = $startLastWeek->format('Y-m-d');
        $endOfLastWeek = $endLastWeek->format('Y-m-d');
        $startOf7Days = $start7Days->format('Y-m-d');
        $endOf7Days = $end7Days->format('Y-m-d');

        $db = getDB();
        
        $topEmployees = [];
        $topEmployeesLastWeek = [];
        $topEmployees7Days = [];

        try {
            $topQuery = "
                SELECT 
                    e.id,
                    e.name, 
                    e.tipe_gaji, 
                    SUM(COALESCE(p.kuantitas, 0) + COALESCE(p.lembur_kuantitas, 0)) as total_bungkus,
                    SUM(COALESCE(p.kuantitas_bal, 0) + COALESCE(p.lembur_kuantitas_bal, 0)) as total_bal
                FROM produksi p
                JOIN karyawan e ON p.id_karyawan = e.id
                WHERE p.date BETWEEN ? AND ?
                GROUP BY e.id, e.name, e.tipe_gaji
                HAVING total_bungkus > 0 OR total_bal > 0
                ORDER BY total_bungkus DESC, total_bal DESC
                LIMIT 5
            ";
            $stmt = $db->prepare($topQuery);
            
            if ($stmt->execute([$startOfWeek, $endOfWeek])) {
                $topEmployees = $stmt->fetchAll();
            }
            if ($stmt->execute([$startOfLastWeek, $endOfLastWeek])) {
                $topEmployeesLastWeek = $stmt->fetchAll();
            }
            if ($stmt->execute([$startOf7Days, $endOf7Days])) {
                $topEmployees7Days = $stmt->fetchAll();
            }
        } catch (\Exception $e) {}

        // 6. Data Log Aktivitas (Recent 5) dengan proteksi data lengkap & null safety
        $activities = [];
        try {
            $stmt = $db->query("
                (SELECT 'Kehadiran' as type, 
                        CONCAT('Kehadiran: ', COALESCE(e.name, 'Karyawan'), 
                               IF(a.hadir=1, ' Hadir', IF(a.catatan IS NOT NULL AND TRIM(a.catatan) != '', CONCAT(' Tidak Hadir (', a.catatan, ')'), ' Tidak Hadir (Alfa)'))) as keterangan, 
                        a.created_at
                 FROM absensi a 
                 LEFT JOIN karyawan e ON a.id_karyawan = e.id
                 ORDER BY a.created_at DESC
                 LIMIT 5)
                UNION ALL
                (SELECT 'Produksi' as type, 
                        CONCAT('Produksi: ', COALESCE(e.name, 'Karyawan'), ' - ', 
                               (COALESCE(p.kuantitas, 0) + COALESCE(p.lembur_kuantitas, 0)), ' bungkus',
                               IF(p.lembur_kuantitas > 0 AND p.kuantitas = 0, ' (Lembur)', IF(p.lembur_kuantitas > 0, ' (Termasuk Lembur)', '')),
                               ' (', COALESCE(pr.name, 'Produk'), ')') as keterangan, 
                        p.created_at
                 FROM produksi p 
                 LEFT JOIN karyawan e ON p.id_karyawan = e.id 
                 LEFT JOIN produk pr ON p.id_produk = pr.id
                 WHERE (p.kuantitas > 0 OR p.lembur_kuantitas > 0 OR p.kuantitas_bal > 0 OR p.lembur_kuantitas_bal > 0)
                 ORDER BY p.created_at DESC
                 LIMIT 5)
                UNION ALL
                (SELECT 'Kasbon' as type, 
                        CONCAT('Kasbon: ', COALESCE(e.name, 'Karyawan'), ' mencatat hutang baru') as keterangan, 
                        d.created_at
                 FROM kasbon d 
                 LEFT JOIN karyawan e ON d.id_karyawan = e.id
                 ORDER BY d.created_at DESC
                 LIMIT 5)
                ORDER BY created_at DESC
                LIMIT 5
            ");
            if ($stmt) {
                $activities = $stmt->fetchAll();
            }
        } catch (\Exception $e) {}

        // 7. Payroll Tertunda (Draft) - Menghitung total nominal riil dari rincian penggajian
        $pendingPayroll = null;
        try {
            $stmt = $db->query("
                SELECT pg.*, 
                       COALESCE(SUM(CASE WHEN pi.is_excluded = 0 THEN pi.gaji_bersih ELSE 0 END), 0) as total_nominal,
                       COUNT(CASE WHEN pi.is_excluded = 0 THEN pi.id ELSE NULL END) as total_karyawan
                FROM penggajian pg
                LEFT JOIN rincian_penggajian pi ON pg.id = pi.id_penggajian
                WHERE pg.status = 'draft'
                GROUP BY pg.id
                ORDER BY pg.id DESC
                LIMIT 1
            ");
            if ($stmt) {
                $pendingPayroll = $stmt->fetch();
            }
        } catch (\Exception $e) {}

        view('dashboard/index', [
            'title'                => 'Dashboard – Salary',
            'pageKey'              => 'dashboard',
            'pageTitle'            => 'Dashboard',
            'today'                => $today,
            'totalEmployees'       => $totalEmployees,
            'boronganCount'        => $boronganCount,
            'bulananCount'         => $bulananCount,
            'presentTodayCount'    => $presentTodayCount,
            'totalBungkusToday'    => $totalBungkusToday,
            'totalBalToday'        => $totalBalToday,
            'totalProducts'        => $totalProducts,
            'todayProductions'     => $todayProductions,
            'todayAttendances'     => $todayAttendances,
            'activeEmployees'      => $activeEmployees,
            'allProducts'          => $allProducts,
            'topEmployees'         => $topEmployees,
            'topEmployeesLastWeek' => $topEmployeesLastWeek,
            'topEmployees7Days'    => $topEmployees7Days,
            'weekPeriod'           => ['start' => $startOfWeek, 'end' => $endOfWeek],
            'lastWeekPeriod'       => ['start' => $startOfLastWeek, 'end' => $endOfLastWeek],
            'sevenDaysPeriod'      => ['start' => $startOf7Days, 'end' => $endOf7Days],
            'recentActivities'     => $activities,
            'pendingPayroll'       => $pendingPayroll,
        ]);
    }



    /**
     * Preview Halaman 403 (Access Denied).
     */
    public function accessDenied(): void
    {
        denyAccess();
    }
}

