<?php
/**
 * @var array|null $role  — null saat mode tambah, array saat mode edit
 */
$isEdit  = !empty($role);
$roleId  = $role['id'] ?? 0;
$roleName = $role['name'] ?? '';
?>

<!-- Page Header -->
<div class="page-header mb-4">
    <div>
        <h5 class="page-header-title d-flex align-items-center gap-2 text-dark fw-semibold mb-0" style="font-size: 1.05rem;">
            <i class="bi bi-shield-<?= $isEdit ? 'check' : 'plus' ?>-fill text-primary fs-5"></i>
            <?= $isEdit ? 'Perbarui nama role: <strong>' . e($roleName) . '</strong>' : 'Tambahkan role baru ke sistem' ?>
        </h5>
    </div>
    <a href="<?= url('/roles') ?>" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2 rounded-pill px-4">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
</div>

<?php if (!empty($_SESSION['flash_error'])): ?>
    <?= renderAlert('danger', e($_SESSION['flash_error'])) ?>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form action="<?= url('/roles/store') ?>" method="POST" id="roleForm">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <?php if ($isEdit): ?>
                        <input type="hidden" name="id" value="<?= $roleId ?>">
                    <?php endif; ?>

                    <div class="mb-4">
                        <label for="name" class="form-label fw-semibold">
                            Nama Role <span class="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            class="form-control form-control-lg"
                            id="name"
                            name="name"
                            value="<?= e($roleName) ?>"
                            placeholder="Contoh: Mandor, Supervisor, Kasir"
                            required
                            maxlength="50"
                            autofocus
                        >
                        <div class="form-text">Nama role harus unik. Contoh: Admin, Owner, Mandor.</div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end">
                        <a href="<?= url('/roles') ?>" class="btn btn-outline-secondary rounded-pill px-4">Batal</a>
                        <button type="submit" class="btn btn-primary rounded-pill px-5 fw-semibold" id="submitBtn">
                            <i class="bi bi-save me-2"></i><?= $isEdit ? 'Perbarui' : 'Simpan' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
  document.getElementById('roleForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Menyimpan...';
    }
  });
</script>
