<?php
/**
 * @var string $title
 * @var array $products
 */
?>

<div class="page-title-box d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
    <div class="page-title-left">
        <h2 class="h4 mb-1 fw-bold d-flex align-items-center">
            <i class="bi bi-box-seam-fill text-primary me-2 fs-4"></i> Data Produk
        </h2>
        <p class="text-muted mb-0 fs-7 ms-4 ps-1">Kelola data item produk dan harga per satuan</p>
    </div>
    <div class="page-title-right">
        <a href="<?= url('/products/form') ?>" class="btn btn-primary d-inline-flex align-items-center">
            <i class="bi bi-plus-lg me-2"></i> Tambah Produk
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
                    <th scope="col" class="ps-4">NAMA PRODUK</th>
                    <th scope="col">KELOMPOK HARGA</th>
                    <th scope="col">HARGA (Rp)</th>
                    <th scope="col" class="text-end pe-4">AKSI</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">Belum ada data produk.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td class="ps-4 fw-medium text-dark"><?= e($product['name']) ?></td>
                            <td>
                                <span class="badge bg-info text-dark rounded-pill fw-normal">
                                    <?= e($product['group_name']) ?>
                                </span>
                            </td>
                            <td><?= formatRupiah((int)$product['harga_per_bungkus']) ?> / bungkus</td>
                            <td class="text-end pe-4">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="<?= url('/products/form?id=' . $product['id']) ?>" class="btn-action btn-action-primary icon-only" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="<?= url('/products/delete') ?>" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus produk ini?');">
                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                        <input type="hidden" name="id" value="<?= $product['id'] ?>">
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
