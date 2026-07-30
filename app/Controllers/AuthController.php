<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;

/**
 * AuthController
 * Menangani login dan logout user.
 *
 * Metode standar CRUD tidak berlaku di sini, tapi method naming tetap
 * mengikuti konvensi yang masuk akal untuk flow auth.
 */
class AuthController
{
    /**
     * Tampilkan form login.
     * Jika sudah login, redirect langsung ke dashboard.
     */
    public function showLogin(): void
    {
        if (isLoggedIn()) {
            redirect('/dashboard');
        }

        // Ambil flash message error dari session (jika ada) lalu hapus
        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);

        view('auth/login', [
            'title' => 'Login – Salary',
            'error' => $error,
        ], 'auth');
    }

    /**
     * Proses submit form login.
     *
     * Alur:
     *   1. Validasi CSRF token (cegah CSRF attack)
     *   2. Validasi input tidak kosong
     *   3. Cari user berdasarkan nama_pengguna
     *   4. Verifikasi password dengan password_verify()
     *   5. Jika valid: isi session, regenerate session ID (cegah session fixation)
     *   6. Redirect ke dashboard
     */
    public function processLogin(): void
    {
        // Step 1: Validasi CSRF
        validateCsrfToken();

        $username = trim($_POST['nama_pengguna'] ?? '');
        $password = $_POST['password'] ?? '';

        // Step 2: Validasi input
        if ($username === '' || $password === '') {
            $_SESSION['login_error'] = 'Username dan password wajib diisi.';
            redirect('/login');
        }

        // Step 3: Cari user di database
        $userModel = new User();
        $user      = $userModel->findByUsername($username);

        // Step 4: Verifikasi password
        // Kenapa hash_equals tidak dipakai di sini? Karena password_verify() sudah
        // melindungi dari timing attack secara internal.
        if (!$user || !password_verify($password, $user['kata_sandi'])) {
            // Pesan yang sama untuk nama_pengguna salah DAN password salah —
            // tidak boleh beri tahu mana yang salah (keamanan)
            $_SESSION['login_error'] = 'Username atau password salah.';
            redirect('/login');
        }

        // Step 5: Login berhasil — set session
        // Regenerate session ID untuk mencegah session fixation attack
        session_regenerate_id(true);

        $_SESSION['user_id']        = $user['id'];
        $_SESSION['user_name']      = $user['name'];
        $_SESSION['user_role_id']   = !empty($user['superuser']) ? 0 : $user['id_peran'];
        $_SESSION['user_role_name'] = !empty($user['superuser']) ? 'Developer' : ($user['role_name'] ?? '-');
        $_SESSION['is_superuser']   = (bool) ($user['superuser'] ?? false);

        // Step 6: Redirect ke dashboard
        redirect('/dashboard');
    }

    /**
     * Logout: hapus semua data session, destroy session, redirect ke login.
     *
     * Kenapa perlu clear cookie juga?
     * Supaya session cookie di browser juga dihapus, tidak hanya server-side.
     */
    public function logout(): void
    {
        // Kosongkan array session
        $_SESSION = [];

        // Hapus cookie session dari browser
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000, // Tanggal di masa lalu → browser hapus cookie
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        // Destroy session di server jika aktif
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        redirect('/login');
    }
}
