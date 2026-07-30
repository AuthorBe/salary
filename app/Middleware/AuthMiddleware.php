<?php
declare(strict_types=1);

namespace App\Middleware;

/**
 * AuthMiddleware
 * Memastikan user sudah login sebelum mengakses route yang dilindungi.
 * Wrapper class untuk global helper requireLogin().
 */
class AuthMiddleware
{
    /**
     * Jalankan pengecekan autentikasi.
     * Redirect ke /login jika session tidak ada.
     */
    public function handle(): void
    {
        requireLogin();
    }
}
