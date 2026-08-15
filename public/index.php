<?php
declare(strict_types=1);

/**
 * public/index.php — Front Controller / Entry Point
 *
 * Semua request HTTP masuk ke sini (via .htaccess).
 * Tugas file ini:
 *   1. Bootstrap: define konstanta, load .env, set timezone, mulai session
 *   2. Autoload class App\* dari /app/
 *   3. Load global helpers
 *   4. Parse URL → dispatch ke Controller yang sesuai
 */

// ── 1. Bootstrap ──────────────────────────────────────────────────────────
define('APP_ROOT', dirname(__DIR__));

// Load .env (sebelum config apapun)
require_once APP_ROOT . '/config/env.php';

// Set timezone Indonesia — SEMUA kalkulasi waktu pakai WIB
date_default_timezone_set('Asia/Jakarta');

// Error display berdasarkan mode debug
if (($_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG')) === 'true') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

// Mulai session dengan nama unik agar tidak bentrok dengan app localhost lain
if (session_status() === PHP_SESSION_NONE) {
    session_name('SALARY_SESSION');
    session_start();

    // Security: Prevent browser caching of dynamic PHP pages
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
}

// Load konfigurasi database (daftarkan fungsi getDB())
require_once APP_ROOT . '/config/database.php';

// Load Composer Autoloader
if (file_exists(APP_ROOT . '/vendor/autoload.php')) {
    require_once APP_ROOT . '/vendor/autoload.php';
}

// ── 2. Autoloader ─────────────────────────────────────────────────────────
// Konversi App\Controllers\AuthController → /app/Controllers/AuthController.php
spl_autoload_register(function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        $relativePath = str_replace('\\', '/', substr($class, 4)); // Hapus 'App\'
        $file         = APP_ROOT . '/app/' . $relativePath . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

// ── 3. Global Helpers ─────────────────────────────────────────────────────
require_once APP_ROOT . '/app/Helpers/helpers.php';
require_once APP_ROOT . '/app/Helpers/pdf_helper.php';

// Set timezone dinamis dari setelan aplikasi (fallback: Asia/Jakarta)
date_default_timezone_set(appSetting('timezone', 'Asia/Jakarta'));

// ── 4. Router ─────────────────────────────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Strip Base Path dari URI secara dinamis supaya router bekerja di subdirektori (localhost/salary) maupun VHost (salary.test)
$basePath = getAppBasePath();
if ($basePath !== '' && str_starts_with($uri, $basePath)) {
    $uri = substr($uri, strlen($basePath));
} elseif (str_starts_with($uri, '/salary')) {
    $uri = substr($uri, 8);
}

// Normalisasi URI
$uri = '/' . ltrim($uri ?: '/', '/');
// Hapus trailing slash (kecuali root '/')
if ($uri !== '/' && str_ends_with($uri, '/')) {
    $uri = rtrim($uri, '/');
}

/**
 * Tabel Route.
 * Format: 'METHOD /path' => [ControllerClass::class, 'methodName']
 *
 * Fase 0: hanya auth + dashboard + demo pages.
 * Fase berikutnya akan ditambahkan di sini.
 */
$routes = [
    // ── Auth ──────────────────────────────────────────────────────────────
    'GET /login'         => [\App\Controllers\AuthController::class,     'showLogin'],
    'POST /login'        => [\App\Controllers\AuthController::class,     'processLogin'],
    'GET /logout'        => [\App\Controllers\AuthController::class,     'logout'],
    'POST /logout'       => [\App\Controllers\AuthController::class,     'logout'],

    // ── Error Pages ───────────────────────────────────────────────────────
    'GET /403'           => [\App\Controllers\DashboardController::class, 'accessDenied'],

    // ── Dashboard & PWA ───────────────────────────────────────────────────
    'GET /'              => [\App\Controllers\DashboardController::class, 'index'],
    'GET /dashboard'     => [\App\Controllers\DashboardController::class, 'index'],
    'GET /manifest.json' => [\App\Controllers\DashboardController::class, 'manifest'],


    // ── Phase 1: Data Master ──────────────────────────────────────────────
    // Karyawan
    'GET /employees'             => [\App\Controllers\EmployeeController::class, 'index'],
    'GET /employees/form'        => [\App\Controllers\EmployeeController::class, 'form'],
    'POST /employees/form'       => [\App\Controllers\EmployeeController::class, 'form'],
    'POST /employees/store'      => [\App\Controllers\EmployeeController::class, 'store'],
    'POST /employees/delete'     => [\App\Controllers\EmployeeController::class, 'destroy'],

    // Kelompok Harga
    'GET /product-groups'        => [\App\Controllers\ProductGroupController::class, 'index'],
    'GET /product-groups/form'   => [\App\Controllers\ProductGroupController::class, 'form'],
    'POST /product-groups/form'  => [\App\Controllers\ProductGroupController::class, 'form'],
    'POST /product-groups/store' => [\App\Controllers\ProductGroupController::class, 'store'],
    'POST /product-groups/delete'=> [\App\Controllers\ProductGroupController::class, 'destroy'],

    // Produk
    'GET /products'              => [\App\Controllers\ProductController::class, 'index'],
    'GET /products/form'         => [\App\Controllers\ProductController::class, 'form'],
    'POST /products/form'        => [\App\Controllers\ProductController::class, 'form'],
    'POST /products/store'       => [\App\Controllers\ProductController::class, 'store'],
    'POST /products/delete'      => [\App\Controllers\ProductController::class, 'destroy'],

    // User & Role
    'GET /users'                 => [\App\Controllers\UserController::class, 'index'],
    'GET /users/form'            => [\App\Controllers\UserController::class, 'form'],
    'POST /users/store'          => [\App\Controllers\UserController::class, 'store'],
    'POST /users/delete'         => [\App\Controllers\UserController::class, 'destroy'],
    'GET /users/permissions'     => [\App\Controllers\UserController::class, 'permissions'],
    'POST /users/permissions/store' => [\App\Controllers\UserController::class, 'storePermissions'],

    // Setelan
    'GET /settings'              => [\App\Controllers\SettingController::class, 'index'],
    'POST /settings'             => [\App\Controllers\SettingController::class, 'index'],

    // Role & Izin Akses
    'GET /roles'                         => [\App\Controllers\RoleController::class, 'index'],
    'GET /roles/form'                    => [\App\Controllers\RoleController::class, 'form'],
    'POST /roles/store'                  => [\App\Controllers\RoleController::class, 'store'],
    'POST /roles/delete'                 => [\App\Controllers\RoleController::class, 'destroy'],
    'GET /roles/permissions'             => [\App\Controllers\RoleController::class, 'permissions'],
    'POST /roles/permissions/store'      => [\App\Controllers\RoleController::class, 'storePermissions'],

    // ── Phase 2: Kehadiran & Produksi Harian ──────────────────────────────
    // Kehadiran (Attendances)
    'GET /attendances'           => [\App\Controllers\AttendanceController::class, 'index'],
    'GET /attendances/form'      => [\App\Controllers\AttendanceController::class, 'loadForm'],
    'POST /attendances/store'    => [\App\Controllers\AttendanceController::class, 'store'],

    // Produksi (Productions)
    'GET /productions'                  => [\App\Controllers\ProductionController::class, 'index'],
    'GET /productions/history'          => [\App\Controllers\ProductionController::class, 'history'],
    'GET /productions/form'             => [\App\Controllers\ProductionController::class, 'loadForm'],
    'POST /productions/store'           => [\App\Controllers\ProductionController::class, 'store'],
    'GET /productions/edit'             => [\App\Controllers\ProductionController::class, 'editForm'],
    'POST /productions/update'          => [\App\Controllers\ProductionController::class, 'update'],
    'POST /productions/delete-employee' => [\App\Controllers\ProductionController::class, 'destroyEmployee'],

    // Lembur Dinamis
    'GET /overtime'              => [\App\Controllers\OvertimeController::class, 'index'],
    'POST /overtime/store'       => [\App\Controllers\OvertimeController::class, 'store'],

    // ── Phase 3: Kasbon & Hutang Karyawan ───────────────────────────────────
    'GET /debts'                        => [\App\Controllers\DebtController::class, 'index'],
    'GET /debts/form'                   => [\App\Controllers\DebtController::class, 'form'],
    'GET /debts/history'                => [\App\Controllers\DebtController::class, 'getHistory'],
    'GET /debts/active-list'            => [\App\Controllers\DebtController::class, 'getActiveList'],
    'POST /debts/store'                 => [\App\Controllers\DebtController::class, 'store'],
    'POST /debts/pay-manual'            => [\App\Controllers\DebtController::class, 'payManual'],
    'POST /debts/delete'                => [\App\Controllers\DebtController::class, 'destroy'],
    'POST /debts/delete-deduction'      => [\App\Controllers\DebtController::class, 'deleteDeduction'],

    // Tabungan Karyawan
    'GET /savings'                      => [\App\Controllers\SavingController::class, 'index'],
    'POST /savings/store'               => [\App\Controllers\SavingController::class, 'store'],
    'GET /savings/history'              => [\App\Controllers\SavingController::class, 'getHistory'],
    'POST /savings/update'              => [\App\Controllers\SavingController::class, 'update'],
    'POST /savings/delete'              => [\App\Controllers\SavingController::class, 'delete'],

    // ── Phase 4: Mesin Perhitungan Gaji (Payroll Engine) ────────────────────
    'GET /payroll'                      => [\App\Controllers\PayrollController::class, 'index'],
    'GET /payroll/create'               => [\App\Controllers\PayrollController::class, 'create'],
    'POST /payroll/store'               => [\App\Controllers\PayrollController::class, 'store'],
    'GET /payroll/preview'              => [\App\Controllers\PayrollController::class, 'show'],
    'POST /payroll/update-item'         => [\App\Controllers\PayrollController::class, 'updateItem'],
    'POST /payroll/toggle-exclude'      => [\App\Controllers\PayrollController::class, 'toggleExclude'],
    'POST /payroll/approve'             => [\App\Controllers\PayrollController::class, 'approve'],
    'POST /payroll/delete'              => [\App\Controllers\PayrollController::class, 'delete'],
    'POST /payroll/regenerate'          => [\App\Controllers\PayrollController::class, 'regenerate'],
    'POST /payroll/cancelApprove'       => [\App\Controllers\PayrollController::class, 'cancelApprove'],

    // Penarikan Gaji
    'GET /payroll/penarikan'            => [\App\Controllers\PenarikanGajiController::class, 'index'],
    'POST /payroll/penarikan/store'     => [\App\Controllers\PenarikanGajiController::class, 'store'],
    'POST /payroll/penarikan/destroy'   => [\App\Controllers\PenarikanGajiController::class, 'destroy'],
    'GET /payroll/penarikan/export'     => [\App\Controllers\PenarikanGajiController::class, 'exportPdf'],
    'GET /payroll/export'               => [\App\Controllers\PayrollController::class, 'exportPdf'],

    // ── Phase 5: Riwayat, Laporan & Slip Gaji ───────────────────────────────
    // History Gaji
    'GET /history'          => [\App\Controllers\HistoryController::class, 'index'],
    'GET /history/slip'         => [\App\Controllers\HistoryController::class, 'downloadSlip'],
    'GET /payroll/export-slips'        => [\App\Controllers\PayrollController::class, 'exportSlipsMass'],
    'GET /payroll/export-combined'     => [\App\Controllers\PayrollController::class, 'exportCombinedPdf'],

    // ── Phase 5: Laporan & Rekapitulasi ───────────────────────────────────────
    // Laporan Finansial Owner
    'GET /reports'                      => [\App\Controllers\ReportController::class, 'index'],
    'GET /reports/export'               => [\App\Controllers\ReportController::class, 'exportPdfBulan'],

    // Gerbang Rekap & Laporan Operasional
    'GET /rekap'                        => [\App\Controllers\RekapController::class, 'index'],
    'GET /rekap/attendance'             => [\App\Controllers\RekapController::class, 'attendance'],
    'GET /rekap/production'             => [\App\Controllers\RekapController::class, 'production'],
    'GET /rekap/overtime'               => [\App\Controllers\RekapController::class, 'overtime'],
    'GET /rekap/employee'               => [\App\Controllers\RekapController::class, 'employee'],
];

$routeKey = $method . ' ' . $uri;

try {
    if (isset($routes[$routeKey])) {
        [$controllerClass, $actionMethod] = $routes[$routeKey];
        $controller = new $controllerClass();
        $controller->$actionMethod();
    } else {
        http_response_code(404);
        view('errors/404', ['title' => '404 – Halaman Tidak Ditemukan'], 'error');
    }
} catch (\Throwable $e) {
    http_response_code(500);
    echo "<div style='padding:20px; font-family:sans-serif; background:#f8d7da; color:#721c24; border:1px solid #f5c6cb;'>";
    echo "<h2>Aplikasi Crash (Error 500)</h2>";
    echo "<p><strong>Pesan Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . " pada baris " . $e->getLine() . "</p>";
    echo "</div>";
    exit;
}
