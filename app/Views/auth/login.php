<div class="auth-container">
    <div class="auth-card">

        <!-- Logo & Brand -->
        <div class="auth-logo">
            <div class="auth-logo-icon" style="background: #ffffff; padding: 14px; border: 1px solid rgba(0, 120, 212, 0.12); box-shadow: 0 8px 16px -4px rgba(0, 120, 212, 0.08), inset 0 2px 0 rgba(255,255,255,1), inset 0 -2px 0 rgba(248,250,252,1); transform: translateY(-2px);">
                <img src="<?= assetUrl('favicon/favicon.svg') ?>" alt="Logo Salary" style="width: 100%; height: 100%; object-fit: contain; filter: drop-shadow(0 4px 6px rgba(0, 120, 212, 0.15));">
            </div>
            <h1 class="auth-logo-name">Salary</h1>
            <p class="auth-logo-sub">Sistem Penggajian Karyawan</p>
        </div>

        <!-- Error Alert -->
        <?php if (!empty($error)): ?>
        <div class="alert alert-danger d-flex align-items-center justify-content-between" role="alert" data-auto-dismiss="5000">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-circle-fill fs-5 flex-shrink-0"></i>
                <span><?= e($error) ?></span>
            </div>
            <button type="button" class="alert-close ms-auto" onclick="this.closest('.alert').remove()" aria-label="Tutup">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <?php endif; ?>

        <!-- Success Alert -->
        <?php if (!empty($success)): ?>
        <div class="alert alert-success d-flex align-items-center justify-content-between" role="alert" data-auto-dismiss="3500">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill fs-5 flex-shrink-0"></i>
                <span><?= e($success) ?></span>
            </div>
            <button type="button" class="alert-close ms-auto" onclick="this.closest('.alert').remove()" aria-label="Tutup">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST"
              action="<?= url('/login') ?>"
              class="auth-form"
              id="loginForm"
              novalidate>

            <!-- CSRF Token (wajib di semua form POST) -->
            <?= csrfField() ?>

            <!-- Username -->
            <div class="form-group">
                <label for="nama_pengguna" class="form-label">Username</label>
                <div class="input-wrapper">
                    <i class="bi bi-person-fill input-icon"></i>
                    <input
                        type="text"
                        id="nama_pengguna"
                        name="nama_pengguna"
                        class="form-input"
                        placeholder="Masukkan username..."
                        autocomplete="username"
                        spellcheck="false"
                        autocapitalize="none"
                        required
                        autofocus
                    >
                </div>
            </div>

            <!-- Password -->
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="input-wrapper">
                    <i class="bi bi-lock-fill input-icon"></i>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-input"
                        placeholder="Masukkan password..."
                        autocomplete="current-password"
                        required
                    >
                    <button type="button"
                            class="input-toggle-password"
                            id="togglePassword"
                            aria-label="Tampilkan/sembunyikan password">
                        <i class="bi bi-eye-fill" id="togglePasswordIcon"></i>
                    </button>
                </div>
            </div>

            <!-- Submit -->
            <button type="submit" class="btn btn-primary btn-full" id="loginBtn">
                <i class="bi bi-box-arrow-in-right"></i>
                <span>Masuk</span>
            </button>

        </form>

        <p class="auth-footer">
            Salary v1.0 &copy; <?= date('Y') ?> &mdash; Internal Use Only
        </p>

    </div>
</div>
