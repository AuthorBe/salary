<div class="error-page">
    <div class="error-icon error-icon-warning">
        <i class="bi bi-map-fill"></i>
    </div>

    <h1 class="error-code">404</h1>
    <h2 class="error-title">Halaman Tidak Ditemukan</h2>
    <p class="error-message">
        Halaman yang kamu cari tidak ada atau sudah dipindahkan.<br>
        Pastikan URL yang kamu ketik sudah benar.
    </p>

    <div class="error-actions">
        <a href="javascript:history.back()" class="btn btn-outline-secondary" id="btn-back-404">
            <i class="bi bi-arrow-left"></i>
            Kembali
        </a>
        <?php if (isLoggedIn()): ?>
        <a href="<?= url('/dashboard') ?>" class="btn btn-primary" id="btn-dashboard-404">
            <i class="bi bi-grid-1x2-fill"></i>
            Dashboard
        </a>
        <?php else: ?>
        <a href="<?= url('/login') ?>" class="btn btn-primary" id="btn-login-404">
            <i class="bi bi-box-arrow-in-right"></i>
            Login
        </a>
        <?php endif; ?>
    </div>

    <p class="error-footer">
        <i class="bi bi-info-circle"></i>
        Salary v1.0
    </p>
</div>
