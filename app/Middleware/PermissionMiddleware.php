<?php
declare(strict_types=1);

namespace App\Middleware;

/**
 * PermissionMiddleware
 * Menangani pengecekan izin akses per halaman menggunakan sistem RBAC.
 * Wrapper class untuk global helper checkPermission().
 */
class PermissionMiddleware
{
    /**
     * Periksa apakah user yang sedang login punya akses ke halaman tertentu.
     * Render 403 dan exit jika ditolak.
     *
     * Alur resolusi RBAC (3 langkah, fail-safe):
     *   1. user_permissions (override per user) → menang
     *   2. role_permissions (default role)
     *   3. Tidak ada baris → DENY
     *
     * @param string $pageKey  Page key yang terdaftar di config/permissions.php
     */
    public function handle(string $pageKey): void
    {
        checkPermission($pageKey);
    }
}
