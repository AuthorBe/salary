<?php
/**
 * config/permissions.php
 * Single Source of Truth untuk semua page_key yang valid di aplikasi Salary.
 *
 * ATURAN PENTING:
 *   - Setiap kali ada halaman baru, tambahkan page_key-nya di sini DULU
 *     sebelum membuat route, controller, atau seed permission-nya.
 *   - Tidak boleh ada halaman yang route-nya jalan tapi page_key-nya
 *     tidak terdaftar di sini.
 *   - Dipakai oleh: checkPermission(), halaman Manajemen User & Role (Fase 1).
 *
 * Format: 'page_key' => 'Deskripsi halaman (untuk UI manajemen permission)'
 */

return [
    // ── Fase 0 ──────────────────────────────────────────────────────────────
    'dashboard'       => 'Dashboard Utama',

    // ── Fase 1 ──────────────────────────────────────────────────────────────
    'employees'       => 'Manajemen Karyawan',
    'product_groups'  => 'Kelompok Harga Borongan',
    'products'        => 'Manajemen Produk',
    'users_roles'     => 'Manajemen User & Role',
    'app_settings'    => 'Setelan Aplikasi',

    // ── Fase 2 ──────────────────────────────────────────────────────────────
    'attendance'      => 'Input Kehadiran',
    'production'      => 'Input Produksi',
    'overtime'        => 'Input Lembur',

    // ── Fase 3 ──────────────────────────────────────────────────────────────
    'debts'           => 'Kasbon & Hutang',
    'savings'         => 'Tabungan Karyawan',

    // ── Fase 4 ──────────────────────────────────────────────────────────────
    'payroll'         => 'Manajemen Payroll',

    // ── Fase 5 ──────────────────────────────────────────────────────────────
    'payroll_history' => 'Riwayat Payroll',
    'reports_owner'   => 'Laporan Owner',
    'rekap'           => 'Rekapitulasi',
];

