<div class="error-page">
    <div class="error-icon error-icon-danger">
        <i class="bi bi-exclamation-triangle-fill"></i>
    </div>

    <h1 class="error-code text-danger">500</h1>
    <h2 class="error-title">Terjadi Kesalahan pada Server</h2>
    <p class="error-message">
        Sistem mengalami gangguan saat memproses permintaan Anda.<br>
        Silakan coba muat ulang halaman atau hubungi administrator sistem.
    </p>

    <?php if (isset($debugError) && !empty($debugError)): ?>
    <div class="alert alert-danger text-start mt-3 mb-4 p-3 rounded-3" style="max-width: 600px; margin: 0 auto; font-family: monospace; font-size: 0.85rem; word-break: break-all;">
        <strong>Debug Info:</strong><br>
        <?= e($debugError) ?>
    </div>
    <?php endif; ?>

    <div class="error-actions">
        <a href="javascript:location.reload()" class="btn btn-outline-secondary" id="btn-reload-500">
            <i class="bi bi-arrow-clockwise"></i>
            Muat Ulang
        </a>
        <?php if (isLoggedIn()): ?>
        <a href="<?= url('/dashboard') ?>" class="btn btn-primary" id="btn-dashboard-500">
            <i class="bi bi-grid-1x2-fill"></i>
            Dashboard
        </a>
        <?php else: ?>
        <a href="<?= url('/login') ?>" class="btn btn-primary" id="btn-login-500">
            <i class="bi bi-box-arrow-in-right"></i>
            Login
        </a>
        <?php endif; ?>
    </div>

    <p class="error-footer">
        <i class="bi bi-shield-lock"></i>
        Salary System
    </p>
</div>
