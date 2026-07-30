<?php
/**
 * @var string $title
 * @var array $groups
 */
?>

<div class="page-title-box d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
    <div class="page-title-left">
        <h2 class="h4 mb-1 fw-bold d-flex align-items-center">
            <i class="bi bi-tags-fill text-primary me-2 fs-4"></i> Kelompok Harga Borongan
        </h2>
        <p class="text-muted mb-0 fs-7 ms-4 ps-1">Kelola data grup harga borongan untuk produk</p>
    </div>
    <div class="page-title-right">
        <a href="<?= url('/product-groups/form') ?>" class="btn btn-primary d-inline-flex align-items-center">
            <i class="bi bi-plus-lg me-2"></i> Tambah Kelompok
        </a>
    </div>
</div>

<?php if (isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success d-flex align-items-center" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>
        <div><?= e($_SESSION['flash_success']) ?></div>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger d-flex align-items-center" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <div><?= e($_SESSION['flash_error']) ?></div>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th scope="col" class="ps-4">NAMA KELOMPOK</th>
                    <th scope="col">HARGA PER BUNGKUS</th>
                    <th scope="col" class="text-end pe-4">AKSI</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($groups)): ?>
                    <tr>
                        <td colspan="3" class="text-center py-4 text-muted">Belum ada data kelompok harga.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($groups as $group): ?>
                        <tr>
                            <td class="ps-4 fw-medium text-dark"><?= e($group['name']) ?></td>
                            <td><?= formatRupiah((int)$group['harga_per_bungkus']) ?></td>
                            <td class="text-end pe-4">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="<?= url('/product-groups/form?id=' . $group['id']) ?>" class="btn-action btn-action-primary icon-only" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="<?= url('/product-groups/delete') ?>" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus kelompok harga ini?');">
                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                        <input type="hidden" name="id" value="<?= $group['id'] ?>">
                                        <button type="submit" class="btn-action btn-action-danger icon-only" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
