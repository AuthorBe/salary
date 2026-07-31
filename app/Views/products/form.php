<?php
/**
 * @var string $title
 * @var array $products
 * @var array $groups
 */

if (empty($products)) {
    $products = [[
        'id' => 0,
        'name' => '',
        'id_kelompok_harga' => 0,
        'error' => null
    ]];
}

$isEditMode = false;
foreach ($products as $product) {
    if (!empty($product['id'])) {
        $isEditMode = true;
        break;
    }
}
?>

<div class="page-title-box d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
    <div class="page-title-left">
        <h2 class="h4 mb-1 fw-bold d-flex align-items-center">
            <i class="bi bi-box-seam-fill text-primary me-2 fs-4"></i> <?= $title ?>
        </h2>
        <p class="text-muted mb-0 fs-7 ms-4 ps-1">Kelola data produk dalam jumlah banyak (Bulk Input/Edit)</p>
    </div>
    <div class="page-title-right">
        <a href="<?= url('/products') ?>" class="btn btn-outline-secondary d-inline-flex align-items-center">
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
        <form action="<?= url('/products/store') ?>" method="POST" id="bulkForm" data-add-row-btn=".btn-add-row">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

            <style>
            @media (max-width: 767.98px) {
                .responsive-table thead { display: none; }
                .responsive-table tbody tr {
                    display: block;
                    margin-bottom: 1rem;
                    border: 1px solid #e0e0e0;
                    border-radius: 0.5rem;
                    padding: 1rem;
                    background: #fff;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                }
                .responsive-table tbody td {
                    display: block;
                    border: none;
                    padding: 0.5rem 0;
                    text-align: left !important;
                }
                .responsive-table tbody td::before {
                    content: attr(data-label);
                    display: block;
                    font-weight: 600;
                    margin-bottom: 0.25rem;
                    color: #495057;
                    font-size: 0.875rem;
                }
                .responsive-table tbody td:last-child {
                    border-top: 1px solid #eee;
                    margin-top: 0.5rem;
                    padding-top: 1rem;
                    text-align: right !important;
                }
                .responsive-table tbody td:last-child::before {
                    display: none;
                }
            }
            </style>

            <div class="table-responsive mb-3" style="overflow-x: hidden;">
                <table class="table table-bordered align-middle responsive-table" id="dynamicTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 45%;">Nama Produk <span class="text-danger">*</span></th>
                            <th style="width: 45%;">Kelompok Harga <span class="text-danger">*</span></th>
                            <th style="width: 10%; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <?php foreach ($products as $index => $prod): ?>
                            <tr>
                                <td data-label="Nama Produk">
                                    <input type="hidden" name="products[<?= $index ?>][id]" value="<?= $prod['id'] ?? 0 ?>">
                                    <input type="text" class="form-control enter-nav" name="products[<?= $index ?>][name]" 
                                           value="<?= e($prod['name'] ?? '') ?>" placeholder="Misal: Produk A" required>
                                    <?php if (!empty($prod['error'])): ?>
                                        <div class="text-danger mt-1 fs-7"><i class="bi bi-x-circle"></i> <?= e($prod['error']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Kelompok Harga">
                                    <select class="form-select enter-nav" name="products[<?= $index ?>][id_kelompok_harga]" required>
                                        <option value="">-- Pilih Kelompok Harga --</option>
                                        <?php foreach ($groups as $group): ?>
                                            <option value="<?= $group['id'] ?>" <?= (($prod['id_kelompok_harga'] ?? 0) == $group['id']) ? 'selected' : '' ?>>
                                                <?= e($group['name']) ?> (<?= formatRupiah((int)$group['harga_per_bungkus']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td class="text-md-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row w-100" onclick="removeRow(this)">
                                        <i class="bi bi-trash me-1"></i> Hapus
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex flex-column flex-sm-row justify-content-between gap-3 mt-4">
                <?php if (!$isEditMode): ?>
                <button type="button" class="btn btn-secondary rounded-pill px-4 py-2 w-100 btn-add-row" onclick="addRow()">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Baris
                </button>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 shadow-sm w-100">
                    <i class="bi bi-save me-2"></i> Simpan Semua
                </button>
            </div>
        </form>
    </div>
</div>

<template id="rowTemplate">
    <tr>
        <td data-label="Nama Produk">
            <input type="hidden" name="products[{index}][id]" value="0">
            <input type="text" class="form-control enter-nav" name="products[{index}][name]" placeholder="Misal: Produk A" required>
        </td>
        <td data-label="Kelompok Harga">
            <select class="form-select enter-nav" name="products[{index}][id_kelompok_harga]" required>
                <option value="">-- Pilih Kelompok Harga --</option>
                <?php foreach ($groups as $group): ?>
                    <option value="<?= $group['id'] ?>">
                        <?= e($group['name']) ?> (<?= formatRupiah((int)$group['harga_per_bungkus']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
        <td class="text-md-center">
            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row w-100" onclick="removeRow(this)">
                <i class="bi bi-trash me-1"></i> Hapus
            </button>
        </td>
    </tr>
</template>

<script>
let rowIndex = <?= max(count($products), 1) * 100 ?>;

function addRow() {
    const tbody = document.getElementById('tableBody');
    const template = document.getElementById('rowTemplate').innerHTML;
    const newHtml = template.replace(/\{index\}/g, rowIndex++);
    
    tbody.insertAdjacentHTML('beforeend', newHtml);
}

function removeRow(btn) {
    const tbody = document.getElementById('tableBody');
    if (tbody.querySelectorAll('tr').length > 1) {
        const row = btn.closest('tr');
        let focusTargetRow = row.previousElementSibling;
        if (!focusTargetRow) {
            focusTargetRow = row.nextElementSibling;
        }
        
        row.remove();
        
        if (focusTargetRow) {
            const firstInput = focusTargetRow.querySelector('.enter-nav');
            if (firstInput) {
                firstInput.focus();
                if (firstInput.tagName.toLowerCase() === 'input') {
                    firstInput.select();
                }
            }
        }
    } else {
        Swal.fire({
            icon: 'warning',
            text: 'Minimal harus ada 1 baris.',
            toast: true,
            position: 'top',
            showConfirmButton: false,
            timer: 3000
        });
    }
}
</script>
