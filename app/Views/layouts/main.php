<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Salary') ?></title>
    <meta name="keterangan" content="Sistem Penggajian Karyawan – Salary">
    <meta name="robots" content="noindex, nofollow">

    <!-- Google Fonts: Inter (body) + Poppins (heading) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons — wajib ada ikon di setiap tombol & judul card -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- PWA & Mobile Optimization -->
    <link rel="manifest" href="<?= url('/manifest.json') ?>">
    <meta name="theme-color" content="#0078d4">
    <!-- iOS Support -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Salary">
    <link rel="apple-touch-icon" href="<?= assetUrl('favicon/apple-touch-icon.png') ?>">
    <link rel="icon" type="image/png" sizes="96x96" href="<?= assetUrl('favicon/favicon-96x96.png') ?>">
    <link rel="icon" type="image/svg+xml" href="<?= assetUrl('favicon/favicon.svg') ?>">
    <link rel="shortcut icon" href="<?= assetUrl('favicon/favicon.ico') ?>">

    <!-- App CSS (design tokens dari desain.md) -->
    <link rel="stylesheet" href="<?= assetUrl('css/app.css') ?>">
</head>
<body>

<!-- ── Sidebar Overlay (mobile) ──────────────────────────────────────────── -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ══════════════════════════════════════════════════════════════════════════
     SIDEBAR
     Glassmorphism di mobile saat di-open, putih bersih di desktop.
     width: 260px, fixed, full height.
     ══════════════════════════════════════════════════════════════════════════ -->
<aside class="sidebar" id="sidebar">

    <!-- Brand -->
    <div class="sidebar-brand">
        <div class="brand-icon" style="background: transparent; box-shadow: none;">
            <img src="<?= assetUrl('favicon/favicon.svg') ?>" alt="Logo Salary" style="width: 100%; height: 100%; object-fit: contain; filter: drop-shadow(0 4px 8px rgba(0, 120, 212, 0.2));">
        </div>
        <div class="brand-text">
            <span class="brand-name">Salary</span>
            <span class="brand-tagline"><?= e(appSetting('company_name', 'Payroll System')) ?></span>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">

        <!-- Utama -->
        <div class="nav-section">
            <span class="nav-section-label">Utama</span>
            <a href="<?= url('/dashboard') ?>"
               id="nav-dashboard"
               class="nav-item <?= ($pageKey ?? '') === 'dashboard' ? 'active' : '' ?>">
                <i class="bi bi-grid-1x2-fill" style="color: #3b82f6;"></i>
                <span>Dashboard</span>
            </a>
            <a href="<?= url('/rekap') ?>"
               class="nav-item <?= ($pageKey ?? '') === 'rekap' ? 'active' : '' ?>">
                <i class="bi bi-clipboard2-data-fill" style="color: #f59e0b;"></i>
                <span>Rekapitulasi</span>
            </a>
        </div>

        <!-- Data Master (Fase 1) -->
        <?php if (hasPermission('employees') || hasPermission('product_groups') || hasPermission('products')): ?>
        <div class="nav-section">
            <span class="nav-section-label">Data Master</span>
            <?php if (hasPermission('employees')): ?>
            <a href="<?= url('/employees') ?>" class="nav-item <?= ($pageKey ?? '') === 'employees' ? 'active' : '' ?>">
                <i class="bi bi-people-fill" style="color: #10b981;"></i>
                <span>Karyawan</span>
            </a>
            <?php endif; ?>
            <?php if (hasPermission('product_groups')): ?>
            <a href="<?= url('/product-groups') ?>" class="nav-item <?= ($pageKey ?? '') === 'product-groups' ? 'active' : '' ?>">
                <i class="bi bi-tags-fill" style="color: #8b5cf6;"></i>
                <span>Kelompok Harga</span>
            </a>
            <?php endif; ?>
            <?php if (hasPermission('products')): ?>
            <a href="<?= url('/products') ?>" class="nav-item <?= ($pageKey ?? '') === 'products' ? 'active' : '' ?>">
                <i class="bi bi-box-seam-fill" style="color: #ec4899;"></i>
                <span>Produk</span>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Operasional (Fase 2-3) -->
        <?php if (hasPermission('attendance') || hasPermission('production') || hasPermission('debts')): ?>
        <div class="nav-section">
            <span class="nav-section-label">Operasional</span>
            <?php if (hasPermission('attendance')): ?>
            <a href="<?= url('/attendances') ?>" class="nav-item <?= ($pageKey ?? '') === 'attendance' ? 'active' : '' ?>">
                <i class="bi bi-calendar-check-fill" style="color: #f59e0b;"></i>
                <span>Kehadiran</span>
            </a>
            <?php endif; ?>
            <?php if (hasPermission('production')): ?>
            <a href="<?= url('/productions') ?>" class="nav-item <?= ($pageKey ?? '') === 'production' ? 'active' : '' ?>">
                <i class="bi bi-bar-chart-steps" style="color: #0ea5e9;"></i>
                <span>Produksi</span>
            </a>
            <?php endif; ?>
            <?php if (hasPermission('overtime')): ?>
            <a href="<?= url('/overtime') ?>" class="nav-item <?= ($pageKey ?? '') === 'overtime' ? 'active' : '' ?>">
                <i class="bi bi-clock-history" style="color: #8b5cf6;"></i>
                <span>Input Lembur</span>
            </a>
            <?php endif; ?>
            <?php if (hasPermission('debts')): ?>
            <a href="<?= url('/debts') ?>" class="nav-item <?= ($pageKey ?? '') === 'debts' ? 'active' : '' ?>">
                <i class="bi bi-credit-card-fill" style="color: #ef4444;"></i>
                <span>Kasbon</span>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Payroll (Fase 4-5) -->
        <?php if (hasPermission('payroll') || hasPermission('payroll_history') || hasPermission('reports_owner')): ?>
        <div class="nav-section">
            <span class="nav-section-label">Payroll</span>
            
            <?php if (hasPermission('payroll')): ?>
            <a href="<?= url('/payroll') ?>" class="nav-item <?= ($pageKey ?? '') === 'payroll' ? 'active' : '' ?>">
                <i class="bi bi-wallet2" style="color: #6366f1;"></i>
                <span>Data Payroll</span>
            </a>
            <a href="<?= url('/payroll/penarikan') ?>" class="nav-item <?= ($pageKey ?? '') === 'penarikan_gaji' ? 'active' : '' ?>">
                <i class="bi bi-cash-stack" style="color: #10b981;"></i>
                <span>Penarikan Gaji</span>
            </a>
            <?php endif; ?>
            
            <?php if (hasPermission('payroll_history')): ?>
            <a href="<?= url('/history') ?>" class="nav-item <?= ($pageKey ?? '') === 'payroll_history' ? 'active' : '' ?>">
                <i class="bi bi-clock-history" style="color: #64748b;"></i>
                <span>Riwayat Gaji</span>
            </a>
            <?php endif; ?>
            
            <?php if (hasPermission('reports_owner')): ?>
            <a href="<?= url('/reports') ?>" class="nav-item <?= ($pageKey ?? '') === 'reports_owner' ? 'active' : '' ?>">
                <i class="bi bi-graph-up-arrow" style="color: #eab308;"></i>
                <span>Laporan Owner</span>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Sistem (Fase 1) -->
        <?php if (hasPermission('users_roles') || hasPermission('app_settings')): ?>
        <div class="nav-section">
            <span class="nav-section-label">Sistem</span>
            <?php if (hasPermission('users_roles')): ?>
            <a href="<?= url('/users') ?>" class="nav-item <?= ($pageKey ?? '') === 'users_roles' ? 'active' : '' ?>">
                <i class="bi bi-person-badge-fill" style="color: #06b6d4;"></i>
                <span>User & Role</span>
            </a>
            <?php endif; ?>
            <?php if (hasPermission('app_settings')): ?>
            <a href="<?= url('/settings') ?>" class="nav-item <?= ($pageKey ?? '') === 'app_settings' ? 'active' : '' ?>">
                <i class="bi bi-gear-fill" style="color: #94a3b8;"></i>
                <span>Setelan Aplikasi</span>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </nav>

    <!-- User Info + Logout -->
    <div class="sidebar-footer">
        <?php $user = currentUser(); ?>
        <a href="<?= url('/logout') ?>"
           id="btn-logout"
           class="logout-btn-full"
           title="Keluar dari aplikasi"
           data-confirm="Yakin ingin keluar?">
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
        </a>
        <div class="user-info-widget">
            <div class="user-avatar" title="<?= e($user['name']) ?>">
                <?= e(mb_strtoupper(mb_substr($user['name'], 0, 1))) ?>
            </div>
            <div class="user-details">
                <span class="user-name"><?= e($user['name']) ?></span>
                <span class="user-role"><?= e($user['role_name']) ?></span>
            </div>
        </div>
    </div>

</aside>

<!-- ══════════════════════════════════════════════════════════════════════════
     HEADER
     Glassmorphism, fixed, z-index di bawah sidebar.
     left: 260px di desktop, left: 0 di mobile.
     ══════════════════════════════════════════════════════════════════════════ -->
<header class="app-header" id="appHeader">

    <!-- Hamburger menu (mobile only) - now hidden because of bottom nav -->
    <button class="header-menu-btn d-none" id="menuToggle" aria-label="Buka/tutup menu navigasi">
        <i class="bi bi-list"></i>
    </button>

    <!-- Page title & Subtitle -->
    <div class="header-title-wrapper">
        <div class="d-flex align-items-center gap-2">
            <h1 class="header-title-text mb-0"><?= e($pageTitle ?? 'Dashboard') ?></h1>
            <?php if (isset($pageGuide) && !empty($pageGuide)): ?>
                <button type="button" class="btn btn-link text-primary p-0 m-0" data-bs-toggle="modal" data-bs-target="#guideModal" title="Panduan Halaman">
                    <i class="bi bi-info-circle-fill fs-5"></i>
                </button>
            <?php endif; ?>
        </div>
        <span class="header-subtitle"><?= e(appSetting('company_name', 'Bintang Harapan')) ?> - Sistem Manajemen Penggajian</span>
    </div>

    <!-- Right: Date pill + User Role pill -->
    <div class="header-actions">
        <div class="header-date-pill d-none d-md-flex">
            <i class="bi bi-calendar3"></i>
            <span><?= date('d M Y') ?></span>
        </div>
        <div class="header-role-pill">
            <i class="bi bi-person-fill"></i>
            <span><?= e(currentUser()['role_name']) ?></span>
        </div>
    </div>

</header>

<!-- ══════════════════════════════════════════════════════════════════════════
     MAIN CONTENT AREA
     margin-left: 260px di desktop, 0 di mobile.
     padding-top: 64px (header height).
     ══════════════════════════════════════════════════════════════════════════ -->
<main class="app-main" id="appMain">
    <div class="page-content">
        <?= $content ?>
    </div>
</main>

<!-- ══════════════════════════════════════════════════════════════════════════
     BOTTOM NAVIGATION (mobile only, <992px)
     5 item, fixed di bawah layar.
     ══════════════════════════════════════════════════════════════════════════ -->
<nav class="bottom-nav" id="bottomNav" aria-label="Navigasi bawah">
    <a href="<?= url('/dashboard') ?>"
       class="bottom-nav-item <?= ($pageKey ?? '') === 'dashboard' ? 'active' : '' ?>">
        <i class="bi bi-grid-1x2-fill"></i>
        <span>Dashboard</span>
    </a>
    <a href="<?= url('/employees') ?>" 
       class="bottom-nav-item <?= ($pageKey ?? '') === 'employees' ? 'active' : '' ?>">
        <i class="bi bi-people-fill"></i>
        <span>Karyawan</span>
    </a>
    <a href="<?= url('/attendances') ?>" 
       class="bottom-nav-item <?= ($pageKey ?? '') === 'attendance' ? 'active' : '' ?>">
        <i class="bi bi-calendar-check-fill"></i>
        <span>Absensi</span>
    </a>
    <a href="<?= url('/payroll') ?>" 
       class="bottom-nav-item <?= ($pageKey ?? '') === 'payroll' ? 'active' : '' ?>">
        <i class="bi bi-wallet2"></i>
        <span>Payroll</span>
    </a>
    <a href="#"
       class="bottom-nav-item"
       onclick="document.getElementById('menuToggle').click(); return false;">
        <i class="bi bi-list"></i>
        <span>Lainnya</span>
    </a>
</nav>

<!-- Bootstrap 5 JS Bundle (Includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- HTMX (untuk Fase 2+: partial updates tanpa full page reload) -->
<script src="https://unpkg.com/htmx.org@1.9.12" integrity="sha384-ujb1lZYygJmzgSwoxRggbCHcjc0rB2uH1/8zX9hfg7SIOkzDkAUQY3cos9bnXLex" crossorigin="anonymous"></script>

<!-- SweetAlert2 untuk Notifikasi yang Lebih Baik -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- App JS -->
<script src="<?= assetUrl('js/app.js') ?>?v=<?= time() ?>"></script>
<script src="<?= assetUrl('js/keyboard-nav.js') ?>?v=<?= time() ?>"></script>

<?php if (isset($pageGuide) && !empty($pageGuide)): ?>
<!-- Modal Panduan Halaman -->
<div class="modal fade" id="guideModal" tabindex="-1" aria-labelledby="guideModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content border-0" style="border-radius: 1.5rem; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">
      
      <!-- Decorative Top Gradient Line -->
      <div style="height: 6px; background: linear-gradient(90deg, #4f46e5, #0ea5e9); border-radius: 1.5rem 1.5rem 0 0;"></div>

      <button type="button" class="btn-close position-absolute top-0 end-0 mt-4 me-4" data-bs-dismiss="modal" aria-label="Close" style="z-index: 10;"></button>

      <div class="modal-body p-4 p-sm-5 pb-2">
        <div class="d-flex align-items-center mb-4">
            <div class="d-flex align-items-center justify-content-center rounded-4 me-3" style="width: 56px; height: 56px; background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%);">
                <i class="bi bi-lightbulb-fill" style="font-size: 1.75rem; color: #0284c7;"></i>
            </div>
            <h4 class="modal-title fw-bold mb-0 text-dark" style="letter-spacing: -0.5px;">Panduan Penggunaan</h4>
        </div>
        
        <div class="guide-content text-secondary" style="font-size: 0.95rem; line-height: 1.7;">
          <?= $pageGuide ?>
        </div>
      </div>
      
      <div class="modal-footer border-0 pt-0 pb-4 pb-sm-5 px-4 px-sm-5 justify-content-center">
        <button type="button" class="btn btn-primary w-100 rounded-pill py-2 fw-semibold shadow-sm" data-bs-dismiss="modal" style="background: linear-gradient(to right, #0284c7, #3b82f6); border: none; transition: transform 0.2s ease, box-shadow 0.2s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 10px 20px -10px rgba(59, 130, 246, 0.5)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 .125rem .25rem rgba(0,0,0,.075)';">
          Paham, Terima Kasih!
        </button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

</body>
</html>
