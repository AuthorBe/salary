<?php
/**
 * @var string $title
 * @var array|null $user
 * @var array $roles
 */
$isEdit = $user !== null;
?>

<div class="page-title-box d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
    <div class="page-title-left">
        <h2 class="h4 mb-1 fw-bold d-flex align-items-center">
            <i class="bi bi-person-fill text-primary me-2 fs-4"></i> <?= $isEdit ? 'Edit' : 'Tambah' ?> User
        </h2>
        <p class="text-muted mb-0 fs-7 ms-4 ps-1">Lengkapi informasi pengguna aplikasi</p>
    </div>
    <div class="page-title-right">
        <a href="<?= url('/users') ?>" class="btn btn-outline-secondary d-inline-flex align-items-center">
            <i class="bi bi-arrow-left me-2"></i> Kembali
        </a>
    </div>
</div>

<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger d-flex align-items-center" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <div><?= e($_SESSION['flash_error']) ?></div>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form action="<?= url('/users/store') ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <?php if ($isEdit): ?>
                <input type="hidden" name="id" value="<?= $user['id'] ?>">
            <?php endif; ?>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="name" class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" 
                           value="<?= $isEdit ? e($user['name']) : '' ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="nama_pengguna" class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nama_pengguna" name="nama_pengguna" 
                           value="<?= $isEdit ? e($user['nama_pengguna']) : '' ?>" 
                           pattern="[a-zA-Z0-9_]{3,}" title="Hanya huruf, angka, underscore. Minimal 3 karakter." required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="password" class="form-label fw-semibold">Password <?= $isEdit ? '<small class="text-muted">(Kosongkan jika tidak ingin diubah)</small>' : '<span class="text-danger">*</span>' ?></label>
                    <input type="password" class="form-control" id="password" name="password" <?= $isEdit ? '' : 'required' ?>>
                </div>
                <div class="col-md-6">
                    <label for="id_peran" class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
                    <select class="form-select" id="id_peran" name="id_peran" required>
                        <option value="">-- Pilih Role --</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= $role['id'] ?>" <?= ($isEdit && $user['id_peran'] == $role['id']) ? 'selected' : '' ?>>
                                <?= e($role['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="aktif" name="aktif" 
                               <?= (!$isEdit || $user['aktif']) ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="aktif">Akun Aktif</label>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary d-inline-flex align-items-center">
                <i class="bi bi-save me-2"></i> Simpan
            </button>
        </form>
    </div>
</div>
