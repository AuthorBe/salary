<?php
/**
 * app/Helpers/helpers.php
 * Global Helper Functions — dimuat sekali di bootstrap (public/index.php).
 *
 * Fungsi tersedia secara global di seluruh aplikasi:
 *   - View rendering    : view()
 *   - URL helpers       : url(), assetUrl()
 *   - Security          : e(), generateCsrfToken(), validateCsrfToken()
 *   - RBAC              : checkPermission(), denyAccess(), isSuperuser(), hasPermission()
 *   - Auth              : requireLogin(), isLoggedIn(), currentUser()
 *   - Navigation        : redirect()
 *   - Formatting        : formatRupiah(), formatTanggal(), formatTanggalShort()
 *   - App config        : appSetting()
 */

// ── View Renderer ──────────────────────────────────────────────────────────

/**
 * Render sebuah view file di dalam layout.
 * Menggunakan output buffering: view di-buffer dulu, lalu di-inject ke layout via $content.
 *
 * @param string $view    Path view relatif dari /app/Views/, e.g. 'dashboard/index'
 * @param array  $data    Variabel yang di-extract ke scope view
 * @param string $layout  Nama layout di /app/Views/layouts/ ('main', 'auth', 'error')
 */
function view(string $_viewPath, array $_viewData = [], string $_layoutName = 'main'): void
{
    // Extract $_viewData ke scope lokal (EXTR_SKIP: jangan timpa variabel yang sudah ada)
    extract($_viewData, EXTR_SKIP);

    $viewFile   = APP_ROOT . '/app/Views/' . $_viewPath . '.php';
    $layoutFile = APP_ROOT . '/app/Views/layouts/' . $_layoutName . '.php';

    if (!file_exists($viewFile)) {
        throw new RuntimeException("View tidak ditemukan: {$_viewPath}");
    }
    if (!file_exists($layoutFile)) {
        throw new RuntimeException("Layout tidak ditemukan: {$_layoutName}");
    }

    // Buffer konten view
    ob_start();
    include $viewFile;
    $content = ob_get_clean();

    // Render layout (layout akan echo $content)
    include $layoutFile;
}

/**
 * Render notifikasi alert terharmonisasi (Sukses, Gagal, Peringatan, Info).
 */
function renderAlert(string $type, string $message, int $autoDismissMs = 3000): string
{
    $icons = [
        'success' => 'bi-check-circle-fill',
        'danger'  => 'bi-exclamation-triangle-fill',
        'warning' => 'bi-exclamation-circle-fill',
        'info'    => 'bi-info-circle-fill',
    ];

    $icon = $icons[$type] ?? 'bi-info-circle-fill';
    $dismissAttr = $autoDismissMs > 0 ? " data-auto-dismiss=\"{$autoDismissMs}\"" : '';

    return sprintf(
        '<div class="alert alert-%s" role="alert"%s><i class="bi %s"></i><span>%s</span><button type="button" class="alert-close" onclick="this.closest(\'.alert\').remove()" aria-label="Tutup"><i class="bi bi-x-lg"></i></button></div>',
        e($type),
        $dismissAttr,
        $icon,
        $message
    );
}

// ── URL Helpers ────────────────────────────────────────────────────────────

/**
 * Ambil base path aplikasi secara cerdas.
 * Jika diakses via http://localhost/salary, kembalikan '/salary'.
 * Jika diakses via VHost http://salary.test, kembalikan ''.
 */
function getAppBasePath(): string
{
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $basePath = dirname($scriptName);
    
    if ($basePath === '\\' || $basePath === '/') {
        return '';
    }
    
    return rtrim(str_replace('\\', '/', $basePath), '/');
}

/**
 * Cek apakah request saat ini dikirim via HTMX (AJAX).
 */
function isHtmx(): bool
{
    return isset($_SERVER['HTTP_HX_REQUEST']) && $_SERVER['HTTP_HX_REQUEST'] === 'true';
}

/**
 * Buat URL absolut/relatif untuk rute internal aplikasi.
 */
function url(string $path = ''): string
{
    $basePath = getAppBasePath();
    return $basePath . '/' . ltrim($path, '/');
}

/**
 * Buat URL absolut untuk aset (CSS/JS/Gambar).
 * Otomatis menambahkan query parameter versi (berdasarkan waktu modifikasi file)
 * agar file JS/CSS terbaru tidak ter-cache oleh browser (cache-busting).
 */
function assetUrl(string $path): string
{
    $absoluteUrl = url('/assets/' . ltrim($path, '/'));
    
    // Cari path absolut file di server
    $filePath = __DIR__ . '/../../public/assets/' . ltrim($path, '/');
    if (file_exists($filePath)) {
        $version = filemtime($filePath);
        return $absoluteUrl . '?v=' . $version;
    }
    
    return $absoluteUrl;
}

// ── Security Helpers ───────────────────────────────────────────────────────

/**
 * Escape output untuk HTML yang aman (mencegah XSS).
 * WAJIB dipakai di semua output ke HTML: <?= e($variable) ?>
 */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Generate CSRF token dan simpan di session.
 * Panggil sekali saat render form POST, masukkan ke hidden input.
 *
 * Kenapa generate jika belum ada (bukan regenerate tiap render)?
 * Karena halaman bisa di-render ulang setelah validasi gagal,
 * token yang sama masih valid untuk submit berikutnya.
 */
function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Helper singkat untuk merender input hidden CSRF token di HTML form.
 */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}


/**
 * Validasi CSRF token dari form POST.
 * Menggunakan hash_equals() untuk mencegah timing attack.
 * Token di-unset setelah validasi (one-time use).
 *
 * Dipanggil di Controller sebelum memproses data POST.
 */
function validateCsrfToken(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    $stored    = $_SESSION['csrf_token'] ?? '';

    if (!$stored || !hash_equals($stored, $submitted)) {
        unset($_SESSION['csrf_token']);
        http_response_code(419);
        view('errors/csrf', ['title' => 'Sesi Berakhir (CSRF Error)'], 'error');
        exit;
    }

    // Token tidak di-regenerate setiap request supaya HTMX partial-updates tetap bisa bekerja dengan form yang sama.
}

// ── RBAC / Permission Check ────────────────────────────────────────────────

/**
 * Cek apakah user yang sedang login adalah Superuser (Developer).
 * Superuser bypass semua permission check.
 */
function isSuperuser(): bool
{
    return !empty($_SESSION['is_superuser']) && (bool) $_SESSION['is_superuser'];
}

/**
 * Cek apakah user punya izin ke pageKey tertentu, TANPA redirect/halt.
 * Digunakan di view (sidebar) untuk menyembunyikan menu yang tidak diizinkan.
 *
 * Superuser selalu true. Urutan cek: user_permissions → role_permissions → false.
 *
 * @param string $pageKey  Page key dari config/permissions.php
 */
function hasPermission(string $pageKey): bool
{
    if (!isLoggedIn()) return false;
    if (isSuperuser()) return true;

    $userId = (int) $_SESSION['user_id'];
    $roleId = (int) $_SESSION['user_role_id'];
    $db     = getDB();

    // Cek user-level override
    $stmt = $db->prepare('SELECT diizinkan FROM izin_pengguna WHERE id_pengguna = ? AND kunci_halaman = ? LIMIT 1');
    $stmt->execute([$userId, $pageKey]);
    $userOverride = $stmt->fetch();
    if ($userOverride !== false) {
        return (bool) $userOverride['diizinkan'];
    }

    // Cek role-level
    $stmt = $db->prepare('SELECT diizinkan FROM izin_peran WHERE id_peran = ? AND kunci_halaman = ? LIMIT 1');
    $stmt->execute([$roleId, $pageKey]);
    $rolePermission = $stmt->fetch();
    if ($rolePermission !== false) {
        return (bool) $rolePermission['diizinkan'];
    }

    return false; // Default DENY
}

/**
 * Periksa apakah user yang sedang login punya izin ke halaman tertentu.
 * Redirect/halt dengan 403 jika tidak punya izin.
 *
 * Alur resolusi (3 langkah, fail-safe):
 *   0. Superuser (is_superuser=1) → BYPASS semua, langsung return.
 *   1. Cek tabel user_permissions → jika ada baris, pakai nilai is_allowed-nya.
 *   2. Tidak ada override? Cek tabel role_permissions berdasarkan role user.
 *   3. Role juga tidak punya baris? DEFAULT: DENY.
 *
 * @param string $pageKey  Harus terdaftar di config/permissions.php
 */
function checkPermission(string $pageKey): void
{
    // Pastikan sudah login
    requireLogin();

    // ── Langkah 0: Superuser bypass semua izin ────────────────────────────
    if (isSuperuser()) {
        return; // Developer/superuser: akses ke mana saja tanpa query DB
    }

    $userId = (int) $_SESSION['user_id'];
    $roleId = (int) $_SESSION['user_role_id'];
    $db     = getDB();

    // ── Langkah 1: Cek user-level override ────────────────────────────────
    $stmt = $db->prepare(
        'SELECT diizinkan FROM izin_pengguna WHERE id_pengguna = ? AND kunci_halaman = ? LIMIT 1'
    );
    $stmt->execute([$userId, $pageKey]);
    $userOverride = $stmt->fetch();

    if ($userOverride !== false) {
        if ((bool) $userOverride['diizinkan']) {
            return;
        }
        denyAccess();
    }

    // ── Langkah 2: Cek role-level permission ──────────────────────────────
    $stmt = $db->prepare(
        'SELECT diizinkan FROM izin_peran WHERE id_peran = ? AND kunci_halaman = ? LIMIT 1'
    );
    $stmt->execute([$roleId, $pageKey]);
    $rolePermission = $stmt->fetch();

    if ($rolePermission !== false) {
        if ((bool) $rolePermission['diizinkan']) {
            return;
        }
        denyAccess();
    }

    // ── Langkah 3: Tidak ada baris sama sekali → default DENY ─────────────
    denyAccess();
}

/**
 * Tolak akses: tampilkan halaman 403 animasi dan hentikan eksekusi.
 * Halaman 403 bersifat standalone (tidak pakai layout main) untuk keamanan.
 */
function denyAccess(): never
{
    http_response_code(403);
    view('errors/403', ['title' => '403 – Akses Ditolak'], 'error');
    exit;
}

// ── Auth Helpers ───────────────────────────────────────────────────────────

/**
 * Wajibkan user sudah login. Redirect ke /login jika belum.
 * Dipanggil di awal method controller yang butuh autentikasi.
 */
function requireLogin(): void
{
    // Jika tidak ada user_id, berarti belum login
    if (empty($_SESSION['user_id'])) {
        session_unset();
        redirect('/login');
    }

    // Jika dia BUKAN superuser, maka WAJIB memiliki role_id (id_peran)
    if (!isSuperuser() && empty($_SESSION['user_role_id'])) {
        session_unset();
        redirect('/login');
    }
}

/**
 * Cek apakah user sedang login.
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

/**
 * Ambil data user yang sedang login dari session.
 */
function currentUser(): array
{
    return [
        'id'           => $_SESSION['user_id']       ?? null,
        'name'         => $_SESSION['user_name']      ?? '',
        'role_id'      => $_SESSION['user_role_id']   ?? null,
        'role_name'    => $_SESSION['user_role_name'] ?? '',
        'is_superuser' => (bool) ($_SESSION['is_superuser'] ?? false),
    ];
}

// ── Redirect ───────────────────────────────────────────────────────────────

/**
 * Redirect ke path relatif (ditambahkan APP_BASE_PATH otomatis).
 * Selalu diakhiri dengan exit agar tidak ada kode yang jalan setelah redirect.
 *
 * Contoh: redirect('/dashboard') → Location: /salary/public/dashboard
 */
function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

// ── Formatting Helpers ─────────────────────────────────────────────────────

/**
 * Format integer amount sebagai Rupiah Indonesia.
 * Semua nilai moneter di app ini disimpan sebagai integer (tanpa desimal).
 *
 * Kenapa satu helper? Supaya format konsisten di seluruh app — tidak ada
 * format manual berulang di tiap view yang bisa tidak konsisten.
 *
 * Contoh: formatRupiah(1500000) → "Rp 1.500.000"
 */
function formatRupiah(int $amount): string
{
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

/**
 * Parsing string input rupiah (misal "1.500.000") menjadi integer (1500000).
 */
function parseRupiah(string $rupiah): int
{
    $isNegative = (strpos(trim($rupiah), '-') === 0);
    // Hapus karakter selain angka
    $clean = preg_replace('/[^0-9]/', '', $rupiah);
    return $isNegative ? -(int)$clean : (int)$clean;
}

/**
 * Format date string (Y-m-d) ke format Indonesia panjang.
 * Kenapa satu helper? Sama — konsistensi dan tidak ada inline date() di view.
 *
 * Contoh: formatTanggal('2026-07-28') → "28 Juli 2026"
 */
function formatTanggal(string $date): string
{
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    $ts    = strtotime($date);
    $hari  = (int) date('j', $ts);
    $bulanStr = $bulan[(int) date('n', $ts)];
    $tahun = date('Y', $ts);

    return "{$hari} {$bulanStr} {$tahun}";
}

/**
 * Format date string ke format pendek Indonesia.
 * Contoh: formatTanggalShort('2026-07-28') → "28 Jul 2026"
 */
function formatTanggalShort(string $date): string
{
    $bulan = [
        1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
        'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des',
    ];

    $ts    = strtotime($date);
    $hari  = (int) date('j', $ts);
    $bulanStr = $bulan[(int) date('n', $ts)];
    $tahun = date('Y', $ts);

    return "{$hari} {$bulanStr} {$tahun}";
}

/**
 * Ambil nilai konfigurasi dari tabel app_settings.
 * Cached di memory selama request berlangsung.
 *
 * Contoh: appSetting('week_start_day') → '1' (Senin)
 */
function appSetting(string $key, string $default = ''): string
{
    static $cache = [];

    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    try {
        $stmt = getDB()->prepare(
            'SELECT nilai_pengaturan FROM pengaturan_aplikasi WHERE kunci_pengaturan = ? LIMIT 1'
        );
        $stmt->execute([$key]);
        $row          = $stmt->fetch();
        $cache[$key]  = $row ? $row['nilai_pengaturan'] : $default;
    } catch (Throwable) {
        $cache[$key] = $default;
    }

    return $cache[$key];
}
