<?php
/**
 * @var string $title
 * @var array|null $group
 */
$isEdit = $group !== null;
?>

<div class="page-title-box d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
    <div class="page-title-left">
        <h2 class="h4 mb-1 fw-bold d-flex align-items-center">
            <i class="bi bi-tags-fill text-primary me-2 fs-4"></i> <?= $isEdit ? 'Edit' : 'Tambah' ?> Kelompok Harga
        </h2>
        <p class="text-muted mb-0 fs-7 ms-4 ps-1">Lengkapi data kelompok harga dengan benar</p>
    </div>
    <div class="page-title-right">
        <a href="<?= url('/product-groups') ?>" class="btn btn-outline-secondary d-inline-flex align-items-center">
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
        <form action="<?= url('/product-groups/store') ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <?php if ($isEdit): ?>
                <input type="hidden" name="id" value="<?= $group['id'] ?>">
            <?php endif; ?>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="name" class="form-label fw-semibold">Nama Kelompok <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" 
                           value="<?= $isEdit ? e($group['name']) : '' ?>" 
                           placeholder="Misal: Kelompok A" required>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <label for="harga_per_bungkus" class="form-label fw-semibold">Harga per Bungkus (Rp) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">Rp</span>
                        <input type="text" inputmode="numeric" class="form-control border-start-0 input-rupiah" id="harga_per_bungkus" name="harga_per_bungkus" 
                               value="<?= $isEdit ? (int)$group['harga_per_bungkus'] : '' ?>" 
                               placeholder="0" required>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary d-inline-flex align-items-center">
                <i class="bi bi-save me-2"></i> Simpan
            </button>
        </form>
    </div>
</div>
