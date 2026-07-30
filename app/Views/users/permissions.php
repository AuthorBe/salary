<?php
/**
 * @var string $title
 * @var array $user
 * @var array $rolePermissions
 * @var array $userOverrides
 * @var array $allKeys
 */
?>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
    <div>
        <h2 class="h4 mb-0 fw-bold">Hak Akses Khusus: <?= e($user['name']) ?></h2>
        <p class="text-muted mb-0 small">Role Default: <strong><?= e($user['role_name']) ?></strong></p>
    </div>
    <a href="<?= url('/users') ?>" class="btn btn-outline-secondary d-inline-flex align-items-center">
        <i class="bi bi-arrow-left me-2"></i> Kembali
    </a>
</div>

<?php if (isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success d-flex align-items-center" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>
        <div><?= e($_SESSION['flash_success']) ?></div>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body bg-light rounded">
        <h6 class="fw-bold"><i class="bi bi-info-circle me-2"></i>Cara Kerja RBAC 2 Lapis (Override)</h6>
        <ul class="mb-0 small text-muted">
            <li><strong>Ikut Role Default:</strong> Izin akses mengikuti pengaturan bawaan role (<?= e($user['role_name']) ?>).</li>
            <li><strong>ALLOW (Override):</strong> Memaksa user BISA mengakses halaman ini, meskipun role-nya tidak mengizinkan.</li>
            <li><strong>DENY (Override):</strong> Memaksa user DILARANG mengakses halaman ini, meskipun role-nya mengizinkan.</li>
        </ul>
    </div>
</div>

<form action="<?= url('/users/permissions/store') ?>" method="POST">
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
    <input type="hidden" name="id_pengguna" value="<?= $user['id'] ?>">

    <div class="card border-0 shadow-sm mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col" class="ps-4">NAMA HALAMAN (PAGE KEY)</th>
                        <th scope="col" class="text-center">AKSES ROLE BAWAAN</th>
                        <th scope="col">ATUR OVERRIDE UNTUK USER INI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allKeys as $pageKey => $label): ?>
                        <?php 
                        $roleHasAccess = isset($rolePermissions[$pageKey]) && $rolePermissions[$pageKey];
                        
                        // Cek status override saat ini
                        $overrideState = 'inherit';
                        if (isset($userOverrides[$pageKey])) {
                            $overrideState = $userOverrides[$pageKey] ? 'allow' : 'deny';
                        }
                        ?>
                        <tr>
                            <td class="ps-4 fw-medium text-dark">
                                <?= e($label) ?>
                                <br><small class="text-muted font-monospace fw-normal"><?= e($pageKey) ?></small>
                            </td>
                            <td class="text-center">
                                <?php if ($roleHasAccess): ?>
                                    <span class="badge bg-success-soft text-success"><i class="bi bi-check-lg me-1"></i>Diizinkan</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-soft text-danger"><i class="bi bi-x-lg me-1"></i>Ditolak</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <select class="form-select form-select-sm" name="override[<?= e($pageKey) ?>]" style="max-width: 250px;">
                                    <option value="inherit" <?= $overrideState === 'inherit' ? 'selected' : '' ?>>
                                        (Ikut Role Default)
                                    </option>
                                    <option value="allow" <?= $overrideState === 'allow' ? 'selected' : '' ?> class="text-success fw-bold">
                                        🟢 ALLOW (Paksa Izinkan)
                                    </option>
                                    <option value="deny" <?= $overrideState === 'deny' ? 'selected' : '' ?> class="text-danger fw-bold">
                                        🔴 DENY (Paksa Tolak)
                                    </option>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <button type="submit" class="btn btn-primary d-inline-flex align-items-center mb-5">
        <i class="bi bi-save me-2"></i> Simpan Hak Akses Khusus
    </button>
</form>
