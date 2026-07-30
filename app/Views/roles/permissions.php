<?php
/**
 * @var array  $role             — data role (id, name)
 * @var array  $allKeys          — dari config/permissions.php ['kunci_halaman' => 'Label']
 * @var array  $rolePermissions  — ['kunci_halaman' => diizinkan (0/1)]
 */
?>

<!-- Page Header -->
<div class="page-header mb-4">
    <div>
        <h5 class="page-header-title d-flex align-items-center gap-2 text-dark fw-semibold mb-0" style="font-size: 1.05rem;">
            <i class="bi fs-5 bi-key-fill text-warning"></i> Atur halaman mana saja yang dapat diakses oleh role <strong><?= e($role['name']) ?></strong>
        </h5>
    </div>
    <a href="<?= url('/roles') ?>" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2 rounded-pill px-4">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
</div>

<?php if (!empty($_SESSION['flash_success'])): ?>
    <?= renderAlert('success', e($_SESSION['flash_success'])) ?>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<!-- Info -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3 d-flex align-items-start gap-3">
        <i class="bi bi-info-circle-fill text-primary fs-5 mt-1"></i>
        <div class="small text-muted">
            <strong class="text-dark">Allow</strong> — user dengan role ini <strong>dapat mengakses</strong> halaman tersebut.<br>
            <strong class="text-dark">Deny</strong> — user dengan role ini <strong>tidak dapat mengakses</strong> halaman tersebut.<br>
            Izin per-role ini dapat di-override lebih lanjut di halaman <em>Hak Akses Khusus</em> per user individu.
        </div>
    </div>
</div>

<form action="<?= url('/roles/permissions/store') ?>" method="POST" id="rolePermForm">
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
    <input type="hidden" name="id_peran" value="<?= $role['id'] ?>">

    <div class="card border-0 shadow-sm mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col" class="ps-4">HALAMAN / FITUR</th>
                        <th scope="col" class="text-center" style="width:130px">
                            <span class="text-success fw-semibold">ALLOW</span>
                        </th>
                        <th scope="col" class="text-center pe-4" style="width:130px">
                            <span class="text-danger fw-semibold">DENY</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allKeys as $pageKey => $label): ?>
                        <?php
                        // Current value: 1=allow, 0=deny, not set = deny by default
                        $currentAllow = isset($rolePermissions[$pageKey]) && (bool)$rolePermissions[$pageKey];
                        ?>
                        <tr>
                            <td class="ps-4">
                                <span class="fw-medium text-dark"><?= e($label) ?></span>
                                <br><small class="text-muted font-monospace"><?= e($pageKey) ?></small>
                            </td>
                            <td class="text-center">
                                <div class="form-check d-flex justify-content-center">
                                    <input
                                        class="form-check-input"
                                        type="radio"
                                        name="permissions[<?= e($pageKey) ?>]"
                                        id="perm_allow_<?= e($pageKey) ?>"
                                        value="allow"
                                        <?= $currentAllow ? 'checked' : '' ?>
                                    >
                                </div>
                            </td>
                            <td class="text-center pe-4">
                                <div class="form-check d-flex justify-content-center">
                                    <input
                                        class="form-check-input"
                                        type="radio"
                                        name="permissions[<?= e($pageKey) ?>]"
                                        id="perm_deny_<?= e($pageKey) ?>"
                                        value="deny"
                                        <?= !$currentAllow ? 'checked' : '' ?>
                                    >
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Save bar -->
    <div class="d-flex align-items-center justify-content-between gap-3 p-3 bg-white border rounded-3 shadow-sm">
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3" id="allowAllBtn">
                <i class="bi bi-check-all me-1"></i> Allow Semua
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3" id="denyAllBtn">
                <i class="bi bi-x-circle me-1"></i> Deny Semua
            </button>
        </div>
        <button type="submit" class="btn btn-primary px-5 fw-semibold rounded-pill shadow-sm" id="savePermBtn">
            <i class="bi bi-save me-2"></i> Simpan Izin
        </button>
    </div>
</form>

<script>
  // Allow/Deny All shortcuts
  document.getElementById('allowAllBtn')?.addEventListener('click', () => {
    document.querySelectorAll('input[value="allow"]').forEach(r => r.checked = true);
  });
  document.getElementById('denyAllBtn')?.addEventListener('click', () => {
    document.querySelectorAll('input[value="deny"]').forEach(r => r.checked = true);
  });

  // Prevent double submit
  document.getElementById('rolePermForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('savePermBtn');
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Menyimpan...';
    }
  });
</script>
