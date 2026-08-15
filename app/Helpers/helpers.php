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
 * Validasi CSRF token dari form POST atau Header AJAX.
 * Menggunakan hash_equals() untuk mencegah timing attack.
 *
 * Dipanggil di Controller sebelum memproses data POST.
 */
function validateCsrfToken(): void
{
    $submitted = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $stored    = $_SESSION['csrf_token'] ?? '';

    if (!$stored || !hash_equals($stored, (string) $submitted)) {
        unset($_SESSION['csrf_token']);
        http_response_code(419);

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
               || (!empty($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success'    => false,
                'message'    => 'Sesi Anda telah kedaluwarsa atau token keamanan tidak valid (CSRF). Silakan refresh halaman dan coba kembali.',
                'csrf_error' => true
            ]);
            exit;
        }

        view('errors/csrf', ['title' => 'Sesi Berakhir (CSRF Error)'], 'error');
        exit;
    }
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
 * Format rentang tanggal cerdas (menghindari pengulangan nama bulan/tahun jika berada di bulan yang sama).
 * Contoh:
 *   - 08/08/2026 s/d 14/08/2026 -> "8 – 14 Agustus 2026" (short: "8 – 14 Ags 2026")
 *   - 25/07/2026 s/d 01/08/2026 -> "25 Juli – 1 Agustus 2026" (short: "25 Jul – 1 Ags 2026")
 */
function formatRentangTanggal(string $start, string $end, bool $short = false): string
{
    $s = strtotime($start);
    $e = strtotime($end);
    if (!$s || !$e) {
        return $start . ' - ' . $end;
    }

    $bulanShort = [
        1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
        'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des',
    ];
    $bulanLong = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];
    $bMap = $short ? $bulanShort : $bulanLong;

    $dStart = date('j', $s);
    $mStart = (int)date('n', $s);
    $yStart = date('Y', $s);

    $dEnd = date('j', $e);
    $mEnd = (int)date('n', $e);
    $yEnd = date('Y', $e);

    // Kasus 1: Hari sama (1 hari saja)
    if ($start === $end) {
        return "{$dStart} {$bMap[$mStart]} {$yStart}";
    }

    // Kasus 2: Bulan & Tahun sama (e.g. 8 – 14 Agustus 2026)
    if ($mStart === $mEnd && $yStart === $yEnd) {
        return "{$dStart} – {$dEnd} {$bMap[$mEnd]} {$yEnd}";
    }

    // Kasus 3: Beda Bulan, Tahun sama (e.g. 25 Juli – 1 Agustus 2026)
    if ($yStart === $yEnd) {
        return "{$dStart} {$bMap[$mStart]} – {$dEnd} {$bMap[$mEnd]} {$yEnd}";
    }

    // Kasus 4: Beda Tahun (e.g. 28 Des 2025 – 3 Jan 2026)
    return "{$dStart} {$bMap[$mStart]} {$yStart} – {$dEnd} {$bMap[$mEnd]} {$yEnd}";
}

/**
 * Ekstrak detail periode terpisah untuk Borongan dan Bulanan dari data payroll.
 */
function getPayrollPeriodDetails(array $payroll): array
{
    $options = [];
    if (!empty($payroll['options_json'])) {
        $decoded = json_decode((string)$payroll['options_json'], true);
        if (is_array($decoded)) {
            $options = $decoded;
        }
    }

    $details = [];
    $isMixed = ($payroll['type'] ?? '') === 'mixed';

    if ($isMixed && !empty($options)) {
        if (!empty($options['borongan']['start']) && !empty($options['borongan']['end'])) {
            $details['borongan'] = [
                'label'           => 'Borongan',
                'start'           => $options['borongan']['start'],
                'end'             => $options['borongan']['end'],
                'formatted'       => formatRentangTanggal($options['borongan']['start'], $options['borongan']['end'], false),
                'formatted_short' => formatRentangTanggal($options['borongan']['start'], $options['borongan']['end'], true),
            ];
        }
        if (!empty($options['bulanan']['start']) && !empty($options['bulanan']['end'])) {
            $details['bulanan'] = [
                'label'           => 'Bulanan',
                'start'           => $options['bulanan']['start'],
                'end'             => $options['bulanan']['end'],
                'formatted'       => formatRentangTanggal($options['bulanan']['start'], $options['bulanan']['end'], false),
                'formatted_short' => formatRentangTanggal($options['bulanan']['start'], $options['bulanan']['end'], true),
            ];
        }
    }

    if (empty($details)) {
        $type = $payroll['type'] ?? 'weekly';
        $typeLabel = $type === 'weekly' ? 'Borongan' : ($type === 'monthly' ? 'Bulanan' : 'Gabungan');
        $start = $payroll['periode_awal'] ?? date('Y-m-d');
        $end = $payroll['periode_akhir'] ?? date('Y-m-d');
        $key = $type === 'weekly' ? 'borongan' : ($type === 'monthly' ? 'bulanan' : 'general');
        $details[$key] = [
            'label'           => $typeLabel,
            'start'           => $start,
            'end'             => $end,
            'formatted'       => formatRentangTanggal($start, $end, false),
            'formatted_short' => formatRentangTanggal($start, $end, true),
        ];
    }

    return $details;
}

/**
 * Render representasi HTML rapi untuk periode payroll (memisahkan Borongan & Bulanan jika Gabungan).
 */
function renderPayrollPeriodHtml(array $payroll, bool $short = false): string
{
    $details = getPayrollPeriodDetails($payroll);

    if (count($details) === 1) {
        $d = reset($details);
        return '<div class="fw-semibold text-dark text-nowrap" style="font-size: 0.8125rem;">' . ($short ? $d['formatted_short'] : $d['formatted']) . '</div>';
    }

    $html = '<div class="d-inline-flex flex-column gap-1.5 align-items-start">';
    if (isset($details['borongan'])) {
        $html .= '<div class="d-inline-flex align-items-center bg-light border rounded-pill px-2.5 py-1 text-nowrap gap-2 shadow-2xs" style="font-size: 0.75rem; border-color: #e2e8f0 !important;">'
              . '<span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-0.5" style="font-size: 0.65rem; font-weight: 600; text-transform: uppercase;">Borongan</span> '
              . '<span class="fw-semibold text-dark text-nowrap" style="font-size: 0.8rem;">' . ($short ? $details['borongan']['formatted_short'] : $details['borongan']['formatted']) . '</span>'
              . '</div>';
    }
    if (isset($details['bulanan'])) {
        $html .= '<div class="d-inline-flex align-items-center bg-light border rounded-pill px-2.5 py-1 text-nowrap gap-2 shadow-2xs" style="font-size: 0.75rem; border-color: #e2e8f0 !important;">'
              . '<span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-2 py-0.5" style="font-size: 0.65rem; font-weight: 600; text-transform: uppercase;">Bulanan</span> '
              . '<span class="fw-semibold text-dark text-nowrap" style="font-size: 0.8rem;">' . ($short ? $details['bulanan']['formatted_short'] : $details['bulanan']['formatted']) . '</span>'
              . '</div>';
    }
    $html .= '</div>';
    return $html;
}

/**
 * Ambil detail periode khusus untuk tipe gaji karyawan tertentu.
 */
function getPayrollPeriodForType(array $payroll, string $employeeType, bool $short = false): string
{
    $details = getPayrollPeriodDetails($payroll);
    $key = strtolower($employeeType);

    if (isset($details[$key])) {
        return $short ? $details[$key]['formatted_short'] : $details[$key]['formatted'];
    }

    $first = reset($details);
    return $short ? $first['formatted_short'] : $first['formatted'];
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

/**
 * Render opsi dropdown karyawan dengan pengelompokan (optgroup) berdasarkan tipe gaji.
 *
 * @param array $employees Daftar karyawan dari database.
 * @param int|string|null $selectedId ID karyawan yang sedang terpilih.
 * @return string HTML opsi `<option>` dan `<optgroup>`.
 */
function renderEmployeeOptions(array $employees, $selectedId = null): string
{
    $html = '';
    $currentType = '';
    
    foreach ($employees as $e) {
        $tipeGaji = $e['tipe_gaji'] ?? 'unknown';
        
        if ($currentType !== $tipeGaji) {
            if ($currentType !== '') {
                $html .= '</optgroup>';
            }
            $currentType = $tipeGaji;
            $html .= '<optgroup label="Karyawan ' . ucfirst($currentType) . '">';
        }
        
        $selected = ($selectedId !== null && $selectedId == $e['id']) ? ' selected' : '';
        $html .= '<option value="' . e($e['id']) . '"' . $selected . '>' . e($e['name']) . '</option>';
    }
    
    if ($currentType !== '') {
        $html .= '</optgroup>';
    }
    
    return $html;
}

/**
 * Menghasilkan saran nama payroll ringkas dan akurat berdasarkan tipe dan rentang tanggal.
 * Contoh: "Agustus Week 1", "Bulanan Agustus 2026", "Gabungan Agustus Week 2", "Borongan W1 Jul & Bulanan Agu".
 *
 * @param string|null $startDate Tanggal awal (Y-m-d)
 * @param string|null $endDate Tanggal akhir (Y-m-d)
 * @param string $type Tipe payroll ('weekly', 'monthly', 'mixed')
 * @param array $options Opsi tambahan (misal dates untuk borongan dan bulanan jika mixed)
 * @return string Judul payroll ringkas
 */
function generateCompactPayrollTitle(?string $startDate, ?string $endDate, string $type = 'weekly', array $options = []): string
{
    $bulanMap = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    $bulanShortMap = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
        5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
        9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
    ];

    try {
        if ($type === 'mixed' && !empty($options['borongan']['start']) && !empty($options['borongan']['end'])) {
            $wStart = $options['borongan']['start'];
            $wEnd = $options['borongan']['end'];
            $mStart = $options['bulanan']['start'] ?? $startDate;
            $mEnd = $options['bulanan']['end'] ?? $endDate;

            // Hitung bulan mayoritas & minggu borongan
            $calcMaj = function(string $s, string $e) {
                $sDt = new \DateTime($s);
                $eDt = new \DateTime($e);
                if ($sDt > $eDt) { $temp = $sDt; $sDt = $eDt; $eDt = $temp; }
                $counts = [];
                $cur = clone $sDt;
                while ($cur <= $eDt) {
                    $ym = $cur->format('Y-m');
                    $counts[$ym] = ($counts[$ym] ?? 0) + 1;
                    $cur->modify('+1 day');
                }
                arsort($counts);
                return array_key_first($counts) ?: $sDt->format('Y-m');
            };

            $wMajYm = $calcMaj($wStart, $wEnd);
            [$wY, $wM] = explode('-', $wMajYm);
            $wMInt = (int)$wM;
            $wMonthName = $bulanMap[$wMInt] ?? 'Bulan';
            $wShortMonth = $bulanShortMap[$wMInt] ?? 'Bln';

            $wStartDt = new \DateTime($wStart);
            $wStartInMaj = ($wStartDt->format('Y-m') === $wMajYm);
            $wDay = $wStartInMaj ? (int)$wStartDt->format('j') : 1;
            $wWeek = min(5, max(1, (int)ceil($wDay / 7)));

            // Hitung bulan mayoritas bulanan
            $mMajYm = $calcMaj($mStart ?: $wStart, $mEnd ?: $wEnd);
            [$mY, $mM] = explode('-', $mMajYm);
            $mMInt = (int)$mM;
            $mMonthName = $bulanMap[$mMInt] ?? 'Bulan';
            $mShortMonth = $bulanShortMap[$mMInt] ?? 'Bln';

            if ($wMajYm === $mMajYm) {
                return "Gabungan {$wMonthName} Week {$wWeek}";
            }

            return "Borongan W{$wWeek} {$wShortMonth} & Bulanan {$mShortMonth}";
        }

        $refStart = $startDate ?: date('Y-m-d');
        $refEnd = $endDate ?: date('Y-m-d');
        $sDt = new \DateTime($refStart);
        $eDt = new \DateTime($refEnd);
        if ($sDt > $eDt) {
            $temp = $sDt; $sDt = $eDt; $eDt = $temp;
        }

        // Hitung bulan mayoritas (modus hari)
        $monthCounts = [];
        $curr = clone $sDt;
        while ($curr <= $eDt) {
            $ym = $curr->format('Y-m');
            $monthCounts[$ym] = ($monthCounts[$ym] ?? 0) + 1;
            $curr->modify('+1 day');
        }
        arsort($monthCounts);
        $majYm = array_key_first($monthCounts) ?: $sDt->format('Y-m');
        [$majY, $majM] = explode('-', $majYm);
        $majMInt = (int)$majM;
        $monthName = $bulanMap[$majMInt] ?? $sDt->format('F');
        $year = $majY;

        if ($type === 'monthly') {
            return "Bulanan {$monthName} {$year}";
        }

        // Hitung nomor minggu (Week 1..5) dalam bulan mayoritas
        $startInMaj = ($sDt->format('Y-m') === $majYm);
        $dayOfMonth = $startInMaj ? (int)$sDt->format('j') : 1;
        $weekNum = min(5, max(1, (int)ceil($dayOfMonth / 7)));

        return "{$monthName} Week {$weekNum}";
    } catch (\Throwable) {
        return 'Gaji ' . ucfirst($type) . ' ' . date('d M Y');
    }
}


