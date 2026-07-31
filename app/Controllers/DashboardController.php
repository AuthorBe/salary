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

        // 3. Data Produksi Hari Ini
        $prodModel = new \App\Models\Production();
        $todayProductions = $prodModel->getByDate($today);
        $totalBungkusToday = 0;
        $totalBalToday = 0;
        foreach ($todayProductions as $empProds) {
            foreach ($empProds as $pData) {
                $totalBungkusToday += (int) ($pData['kuantitas'] ?? 0);
                $totalBalToday += (int) ($pData['kuantitas_bal'] ?? 0);
            }
        }

        // 4. Data Produk
        $productModel = new \App\Models\Product();
        $allProducts = $productModel->getAllWithGroup();
        $totalProducts = count($allProducts);

        $settingModel = new \App\Models\AppSetting();
        $weekStartDay = (int) $settingModel->get('week_start_day', '1');
        $weekEndDay = (int) $settingModel->get('week_end_day', '0');
        $map = [0 => 'sunday', 1 => 'monday', 2 => 'tuesday', 3 => 'wednesday', 4 => 'thursday', 5 => 'friday', 6 => 'saturday'];
        
        $endDayName = $map[$weekEndDay];
        $startDayName = $map[$weekStartDay];

        $todayStr = date('l');
        $endThisWeek = new \DateTime();
        if (strtolower($todayStr) !== $endDayName) {
            $endThisWeek->modify("next " . $endDayName);
        }
        
        $startThisWeek = clone $endThisWeek;
        if ($weekStartDay === $weekEndDay) {
            $startThisWeek->modify('-6 days');
        } else {
            $startThisWeek->modify("last " . $startDayName);
        }
        
        $startOfWeek = $startThisWeek->format('Y-m-d');
        $endOfWeek = $endThisWeek->format('Y-m-d');
        $db = getDB();
        
        $topEmployees = [];
        try {
            $stmt = $db->prepare("
                SELECT e.name, e.tipe_gaji, SUM(p.kuantitas) as total_bungkus 
                FROM produksi p
                JOIN karyawan e ON p.id_karyawan = e.id
                WHERE p.date BETWEEN ? AND ?
                GROUP BY p.id_karyawan
                ORDER BY total_bungkus DESC
                LIMIT 5
            ");
            if ($stmt->execute([$startOfWeek, $endOfWeek])) {
                $topEmployees = $stmt->fetchAll();
            }
        } catch (\Exception $e) {}

        // 6. Data Log Aktivitas (Recent 5)
        $activities = [];
        try {
            $stmt = $db->query("
                (SELECT 'Kehadiran' as type, CONCAT('Kehadiran: ', e.name, IF(a.hadir=1, ' Hadir', CONCAT(' Tidak Hadir (', IFNULL(a.catatan, ''), ')'))) as keterangan, a.created_at
                 FROM absensi a JOIN karyawan e ON a.id_karyawan = e.id)
                UNION ALL
                (SELECT 'Produksi' as type, CONCAT('Produksi: ', e.name, ' - ', p.kuantitas, ' bungkus (', pr.name, ')') as keterangan, p.created_at
                 FROM produksi p JOIN karyawan e ON p.id_karyawan = e.id JOIN produk pr ON p.id_produk = pr.id)
                UNION ALL
                (SELECT 'Kasbon' as type, CONCAT('Kasbon: ', e.name, ' mencatat hutang baru') as keterangan, d.created_at
                 FROM kasbon d JOIN karyawan e ON d.id_karyawan = e.id)
                ORDER BY created_at DESC
                LIMIT 5
            ");
            if ($stmt) {
                $activities = $stmt->fetchAll();
            }
        } catch (\Exception $e) {}

        // 7. Payroll Tertunda (Draft)
        $pendingPayroll = null;
        try {
            $stmt = $db->query("SELECT * FROM penggajian WHERE status = 'draft' ORDER BY id DESC LIMIT 1");
            if ($stmt) {
                $pendingPayroll = $stmt->fetch();
            }
        } catch (\Exception $e) {}

        view('dashboard/index', [
            'title'              => 'Dashboard – Salary',
            'pageKey'            => 'dashboard',
            'pageTitle'          => 'Dashboard',
            'today'              => $today,
            'totalEmployees'     => $totalEmployees,
            'boronganCount'      => $boronganCount,
            'bulananCount'       => $bulananCount,
            'presentTodayCount'  => $presentTodayCount,
            'totalBungkusToday'  => $totalBungkusToday,
            'totalBalToday'      => $totalBalToday,
            'totalProducts'      => $totalProducts,
            'todayProductions'   => $todayProductions,
            'todayAttendances'   => $todayAttendances,
            'activeEmployees'    => $activeEmployees,
            'allProducts'        => $allProducts,
            'topEmployees'       => $topEmployees,
            'recentActivities'   => $activities,
            'pendingPayroll'     => $pendingPayroll,
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

