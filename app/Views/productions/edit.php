<?php
/**
 * @var string $title
 * @var string $date
 * @var array|null $employee
 * @var array $products
 * @var array $currentData
 * @var string|null $error
 */

$empName = $employee ? $employee['name'] : 'Karyawan';

// Prepare options for select
ob_start();
$currentGroup = '';
foreach ($products as $p): 
    if ($currentGroup !== $p['group_name']):
        if ($currentGroup !== '') echo '</optgroup>';
        $currentGroup = $p['group_name'];
        echo '<optgroup label="' . e($currentGroup) . '">';
    endif;
    echo '<option value="' . $p['id'] . '">' . e($p['name']) . '</option>';
endforeach;
if ($currentGroup !== '') echo '</optgroup>';
$productOptionsHtml = ob_get_clean();
?>

<div class="page-title-box d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
    <div class="page-title-left">
        <h4 class="mb-1 text-dark fw-bold d-flex align-items-center">
            <i class="bi bi-pencil-square text-primary me-2 fs-4"></i> Edit Produksi Harian
        </h4>
        <p class="text-muted mb-0 fs-7 ms-4 ps-1">Perbarui catatan produksi untuk <strong><?= e($empName) ?></strong> pada <strong><?= formatTanggal($date) ?></strong></p>
    </div>
    <div class="page-title-right">
        <a href="<?= url('/productions/history') ?>" class="btn btn-secondary rounded-pill fw-medium shadow-sm d-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i> Kembali ke Riwayat
        </a>
    </div>
</div>

<?php if ($error): ?>
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-body p-5 text-center bg-white">
            <div class="d-inline-flex align-items-center justify-content-center mb-4" style="width: 90px; height: 90px; background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); border-radius: 50%; box-shadow: 0 10px 25px -5px rgba(239, 68, 68, 0.4);">
                <i class="bi bi-shield-x" style="font-size: 3.5rem; color: #ef4444;"></i>
            </div>
            <h4 class="fw-bold mb-3 text-dark" style="letter-spacing: -0.5px;">Akses Terkunci</h4>
            <p class="text-secondary mb-4 mx-auto" style="line-height: 1.7; max-width: 400px; font-size: 1rem;"><?= e($error) ?></p>
            <a href="<?= url('/productions/history') ?>" class="btn btn-dark rounded-pill px-5 py-2 fw-semibold shadow-sm">Kembali ke Riwayat</a>
        </div>
    </div>
<?php else: ?>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
    <!-- Decorative Top Gradient Line -->
    <div style="height: 6px; background: linear-gradient(90deg, #6366f1, #3b82f6, #0ea5e9);"></div>
    
    <div class="card-body p-4 p-md-5">
        <form id="formEditProduction" method="POST" action="<?= url('/productions/update') ?>" data-add-row-btn="#btnAddRowEdit">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="date" value="<?= e($date) ?>">
            <input type="hidden" name="id_karyawan" value="<?= $employee['id'] ?>">
            
            <!-- Info Banner -->
            <div class="alert alert-info bg-info-subtle border-0 rounded-4 d-flex align-items-center mb-4 p-3 shadow-sm">
                <i class="bi bi-info-circle-fill text-info fs-4 me-3"></i>
                <div style="font-size: 0.9rem;" class="text-dark">
                    <strong>Peringatan Edit:</strong> Tindakan ini akan <strong>mengganti total seluruh catatan produksi lama</strong> milik <strong><?= e($empName) ?></strong> pada tanggal <strong><?= formatTanggalShort($date) ?></strong> dengan angka baru di bawah ini.
                </div>
            </div>

            <!-- Bagian Info Karyawan Statis -->
            <div class="mb-4">
                <label class="form-label fw-semibold">Karyawan Borongan</label>
                <input type="text" class="form-control form-control-lg border-primary-subtle bg-light" value="<?= e($empName) ?>" readonly>
            </div>

            <!-- Area Input Produk Dinamis -->
            <div id="zona_produksi" class="p-4 bg-light rounded-3 border border-secondary-subtle mb-4">
                <p class="text-muted small mb-3">Sesuaikan jumlah produksi di bawah ini.</p>
                
                <div id="productionRows" class="d-flex flex-column gap-3 mb-3">
                    <?php 
                    $rowIndex = 0;
                    if (empty($currentData)): 
                    ?>
                        <!-- Jika tidak ada data, tampilkan 1 baris kosong -->
                        <div class="item-row card bg-white border border-secondary-subtle p-3">
                            <div class="row g-3 align-items-end">
                                <div class="col-12 col-md-5">
                                    <label class="form-label small text-muted fw-bold mb-1">Nama Produk</label>
                                    <select name="items[<?= $rowIndex ?>][id_produk]" class="form-select searchable-select product-select enter-nav" required>
                                        <option value="">-- Pilih Produk --</option>
                                        <?= $productOptionsHtml ?>
                                    </select>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small text-muted fw-bold mb-1">Qty (Bungkus)</label>
                                    <input type="text" name="items[<?= $rowIndex ?>][kuantitas]" class="form-control text-end nominal-input enter-nav" value="0">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small text-muted fw-bold mb-1">Qty (Bal)</label>
                                    <input type="text" name="items[<?= $rowIndex ?>][kuantitas_bal]" class="form-control text-end nominal-input enter-nav" value="0">
                                </div>
                                <div class="col-12 col-md-1 mt-3 mt-md-0">
                                    <button type="button" class="btn btn-outline-danger w-100 btn-remove-row" title="Hapus Baris" disabled>
                                        <i class="bi bi-trash"></i> <span class="d-md-none ms-1">Hapus</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php $rowIndex++; ?>
                    <?php else: ?>
                        <!-- Loop data yang sudah ada -->
                        <?php foreach ($currentData as $id_produk => $data): ?>
                            <div class="item-row card bg-white border border-secondary-subtle p-3">
                                <div class="row g-3 align-items-end">
                                    <div class="col-12 col-md-5">
                                        <label class="form-label small text-muted fw-bold mb-1">Nama Produk</label>
                                        <select name="items[<?= $rowIndex ?>][id_produk]" class="form-select searchable-select product-select enter-nav" required>
                                            <option value="">-- Pilih Produk --</option>
                                            <?php
                                            // Render options manually to set selected
                                            $currGroup = '';
                                            foreach ($products as $p): 
                                                if ($currGroup !== $p['group_name']):
                                                    if ($currGroup !== '') echo '</optgroup>';
                                                    $currGroup = $p['group_name'];
                                                    echo '<optgroup label="' . e($currGroup) . '">';
                                                endif;
                                                $selected = ($p['id'] == $id_produk) ? 'selected' : '';
                                                echo '<option value="' . $p['id'] . '" ' . $selected . '>' . e($p['name']) . '</option>';
                                            endforeach;
                                            if ($currGroup !== '') echo '</optgroup>';
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label small text-muted fw-bold mb-1">Qty (Bungkus)</label>
                                        <input type="text" name="items[<?= $rowIndex ?>][kuantitas]" class="form-control text-end nominal-input enter-nav" value="<?= number_format($data['qty'], 0, ',', '.') ?>">
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label small text-muted fw-bold mb-1">Qty (Bal)</label>
                                        <input type="text" name="items[<?= $rowIndex ?>][kuantitas_bal]" class="form-control text-end nominal-input enter-nav" value="<?= number_format($data['bal'], 0, ',', '.') ?>">
                                    </div>
                                    <div class="col-12 col-md-1 mt-3 mt-md-0">
                                        <button type="button" class="btn btn-outline-danger w-100 btn-remove-row" title="Hapus Baris" <?= count($currentData) === 1 ? 'disabled' : '' ?>>
                                            <i class="bi bi-trash"></i> <span class="d-md-none ms-1">Hapus</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php $rowIndex++; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="text-center">
                    <button type="button" class="btn btn-outline-primary fw-bold rounded-pill px-4 mt-2" id="btnAddRowEdit">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Baris Produk
                    </button>
                </div>
            </div>
            
            <div class="mt-5 text-end border-top pt-4">
                <a href="<?= url('/productions/history') ?>" class="btn text-muted fw-medium px-4 border-0 bg-transparent hover-bg-light rounded-pill me-2">Batalkan</a>
                <button type="submit" class="btn btn-primary px-5 py-2 rounded-pill shadow-sm fw-semibold d-inline-flex align-items-center gap-2 transition-all" style="background: linear-gradient(to right, #3b82f6, #6366f1); border: none;">
                    <i class="bi bi-check2-circle fs-5"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Template baris kosong untuk Javascript -->
<template id="rowTemplateEdit">
    <div class="item-row card bg-white border border-secondary-subtle p-3">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-5">
                <label class="form-label small text-muted fw-bold mb-1">Nama Produk</label>
                <select class="form-select searchable-select product-select enter-nav" required>
                    <option value="">-- Pilih Produk --</option>
                    <?= $productOptionsHtml ?>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small text-muted fw-bold mb-1">Qty (Bungkus)</label>
                <input type="text" class="form-control text-end nominal-input enter-nav qty-input" value="0">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small text-muted fw-bold mb-1">Qty (Bal)</label>
                <input type="text" class="form-control text-end nominal-input enter-nav bal-input" value="0">
            </div>
            <div class="col-12 col-md-1 mt-3 mt-md-0">
                <button type="button" class="btn btn-outline-danger w-100 btn-remove-row" title="Hapus Baris">
                    <i class="bi bi-trash"></i> <span class="d-md-none ms-1">Hapus</span>
                </button>
            </div>
        </div>
    </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', function() {
    window.produksiRowCount = <?= $rowIndex ?>;

    // Format Nominal Input
    document.addEventListener('input', function(e) {
        if (e.target && e.target.classList.contains('nominal-input')) {
            let val = e.target.value.replace(/[^0-9]/g, '');
            if (val === '') val = '0';
            e.target.value = parseInt(val, 10).toLocaleString('id-ID');
        }
    });

    // Fokus seluruh teks saat di-klik
    document.addEventListener('focusin', function(e) {
        if (e.target && e.target.classList.contains('nominal-input')) {
            e.target.select();
        }
    });
    
    const btnAdd = document.getElementById('btnAddRowEdit');
    const tbody = document.getElementById('productionRows');
    const template = document.getElementById('rowTemplateEdit');
    
    if (btnAdd) {
        btnAdd.addEventListener('click', function() {
            const clone = template.content.cloneNode(true);
            const templateRow = clone.querySelector('.item-row');
            
            templateRow.querySelector('.product-select').name = `items[${window.produksiRowCount}][id_produk]`;
            templateRow.querySelector('.qty-input').name = `items[${window.produksiRowCount}][kuantitas]`;
            templateRow.querySelector('.bal-input').name = `items[${window.produksiRowCount}][kuantitas_bal]`;
            
            tbody.appendChild(templateRow);
            window.produksiRowCount++;
            updateProduksiRemoveButtons();
            // Init searchable dropdown pada baris baru
            if (typeof window.initSearchableSelects === 'function') {
                window.initSearchableSelects(templateRow);
            }
        });
    }

    if (tbody) {
        tbody.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-remove-row');
            if (btn && !btn.hasAttribute('disabled')) {
                const row = btn.closest('.item-row');
                let focusTargetRow = row.previousElementSibling;
                if (!focusTargetRow) focusTargetRow = row.nextElementSibling;
                
                row.remove();
                updateProduksiRemoveButtons();
                
                if (focusTargetRow) {
                    const firstInput = focusTargetRow.querySelector('.enter-nav');
                    if (firstInput) {
                        firstInput.focus();
                        if (firstInput.tagName.toLowerCase() === 'input') {
                            firstInput.select();
                        }
                    }
                }
            }
        });
    }

    function updateProduksiRemoveButtons() {
        const rows = document.querySelectorAll('#productionRows .item-row');
        if (rows.length === 1) {
            rows[0].querySelector('.btn-remove-row').setAttribute('disabled', 'disabled');
            rows[0].querySelector('.product-select').setAttribute('required', 'required');
        } else {
            rows.forEach(r => {
                r.querySelector('.btn-remove-row').removeAttribute('disabled');
            });
        }
    }
    
    // Init button state
    updateProduksiRemoveButtons();
});
</script>
<?php endif; ?>
