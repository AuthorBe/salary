<?php
/**
 * @var string $title
 * @var string $pageTitle
 * @var string $pageKey
 * @var string $date
 * @var array $employees
 * @var array $products
 * @var array $attendances
 */

// Pisahkan karyawan
$boronganEmps = array_filter($employees, fn($e) => $e['tipe_gaji'] === 'borongan');
$bulananEmps  = array_filter($employees, fn($e) => $e['tipe_gaji'] === 'bulanan');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800 fw-bold"><?= e($pageTitle) ?></h1>
</div>

<?php if (isset($_SESSION['flash_success'])): ?>
    <div class="position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center" style="z-index: 1055; background: rgba(0,0,0,0.5);" id="overtime-success-overlay" onclick="this.remove()">
        <div class="card border-0 shadow-lg" style="max-width: 400px; width: 90%; animation: popIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);" onclick="event.stopPropagation()">
            <div class="card-body p-4 text-center">
                <div class="mb-3 text-success">
                    <i class="bi bi-check-circle-fill" style="font-size: 4rem;"></i>
                </div>
                <h4 class="fw-bold mb-2"><?= e($_SESSION['flash_title'] ?? 'Berhasil Disimpan!') ?></h4>
                <p class="text-muted mb-4"><?= $_SESSION['flash_success'] ?></p>
                <button type="button" class="btn btn-primary px-5 rounded-pill shadow-sm" onclick="document.getElementById('overtime-success-overlay').remove()">Oke, Tutup</button>
            </div>
        </div>
        <style>@keyframes popIn { 0% { opacity: 0; transform: scale(0.8); } 100% { opacity: 1; transform: scale(1); } }</style>
    </div>
    <?php unset($_SESSION['flash_success'], $_SESSION['flash_title']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 justify-content-start" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <span><?= e($_SESSION['flash_error']); ?></span>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom pt-3 pb-3">
        <form method="GET" action="<?= url('/overtime') ?>" class="row gy-2 gx-3 align-items-center" id="dateForm">
            <div class="col-auto">
                <label for="dateFilter" class="col-form-label fw-bold">Tanggal Input:</label>
            </div>
            <div class="col-auto">
                <input type="date" class="form-control fw-bold text-primary" id="dateFilter" name="date" value="<?= e($date) ?>" onchange="document.getElementById('dateSpinner').classList.add('htmx-request'); document.getElementById('dateForm').submit();">
            </div>
            <div class="col-auto">
                <div class="htmx-indicator spinner-border spinner-border-sm text-primary" id="dateSpinner" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </form>
    </div>
    
    <div class="card-body p-0">
        <style>
            .custom-tab {
                color: #6c757d;
                border-bottom: 3px solid transparent;
                transition: all 0.2s;
                outline: none !important;
                background: transparent !important;
                box-shadow: none !important;
                border-top: none; border-left: none; border-right: none;
                cursor: pointer;
            }
            .custom-tab.active {
                color: #0d6efd !important;
                border-bottom: 3px solid #0d6efd !important;
            }
            .custom-tab:hover {
                color: #0d6efd;
            }
        </style>
        <div class="d-flex border-bottom px-4 pt-3" role="tablist">
            <a href="#borongan" data-bs-toggle="tab" role="tab" class="custom-tab active fw-bold px-4 py-3 text-decoration-none">
                <i class="bi bi-box-seam text-warning me-2"></i> Lembur Borongan
            </a>
            <a href="#bulanan" data-bs-toggle="tab" role="tab" class="custom-tab fw-bold px-4 py-3 text-decoration-none">
                <i class="bi bi-clock-history text-primary me-2"></i> Lembur Bulanan (Massal)
            </a>
        </div>

        <div class="tab-content p-4" id="overtimeTabsContent">
            
            <!-- TAB BORONGAN -->
            <div class="tab-pane fade show active" id="borongan" role="tabpanel" aria-labelledby="borongan-tab">
                <form method="POST" action="<?= url('/overtime/store') ?>" id="boronganForm" data-add-row-btn="#btnAddRow">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <input type="hidden" name="date" value="<?= e($date) ?>">
                    <input type="hidden" name="tipe_input" value="borongan">

                    <div class="mb-4">
                        <label for="employeeSelect" class="form-label fw-semibold">Pilih Karyawan Borongan</label>
                        <select name="id_karyawan" class="form-select form-select-lg border-primary-subtle enter-nav" id="employeeSelect" required onchange="handleBoronganChange(this)">
                            <option value="">-- Pilih Karyawan --</option>
                            <?= renderEmployeeOptions($boronganEmps) ?>
                        </select>
                    </div>

                    <div id="zona_borongan" style="display: none;" class="p-4 bg-light rounded-3 border border-secondary-subtle mb-4">
                        <p class="text-muted small mb-3">Input ini akan otomatis tercatat sebagai hasil produksi karyawan.</p>
                        
                        <div id="productionRows" class="d-flex flex-column gap-3 mb-3">
                            <div class="item-row card bg-white border border-secondary-subtle p-3">
                                <div class="row g-3 align-items-end">
                                    <div class="col-12 col-md-5">
                                        <label class="form-label small text-muted fw-bold mb-1">Nama Produk</label>
                                        <select name="items[0][id_produk]" class="form-select product-select enter-nav" required>
                                            <option value="">-- Pilih Produk --</option>
                                            <?php 
                                            $currentGroup = '';
                                            foreach ($products as $p): 
                                                if ($currentGroup !== $p['group_name']):
                                                    if ($currentGroup !== '') echo '</optgroup>';
                                                    $currentGroup = $p['group_name'];
                                                    echo '<optgroup label="' . e($currentGroup) . '">';
                                                endif;
                                            ?>
                                                <option value="<?= $p['id'] ?>">
                                                    <?= e($p['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                            <?php if ($currentGroup !== '') echo '</optgroup>'; ?>
                                        </select>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label small text-muted fw-bold mb-1">Qty (Bungkus)</label>
                                        <input type="text" name="items[0][kuantitas]" class="form-control text-end nominal-input enter-nav" value="0">
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label small text-muted fw-bold mb-1">Qty (Bal)</label>
                                        <input type="text" name="items[0][kuantitas_bal]" class="form-control text-end nominal-input enter-nav" value="0">
                                    </div>
                                    <div class="col-12 col-md-1 mt-3 mt-md-0">
                                        <button type="button" class="btn btn-outline-danger w-100 btn-remove-row" title="Hapus Baris" disabled>
                                            <i class="bi bi-trash"></i> <span class="d-md-none ms-1">Hapus</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="text-center">
                            <button type="button" class="btn btn-outline-primary fw-bold rounded-pill px-4 mt-2" id="btnAddRow">
                                <i class="bi bi-plus-circle me-1"></i> Tambah Baris Produk
                            </button>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end" id="btnContainerBorongan" style="display: none !important;">
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill shadow-sm px-5 fw-bold">
                            <i class="bi bi-save me-2"></i> Simpan Data Borongan
                        </button>
                    </div>
                    
                    <div id="emptyContainerBorongan" class="text-center text-muted py-5">
                        <i class="bi bi-arrow-up-circle fs-1 mb-2 d-block opacity-25"></i>
                        <p class="mb-0 fw-semibold">Silakan pilih karyawan borongan terlebih dahulu.</p>
                    </div>
                </form>
            </div>

            <!-- TAB BULANAN -->
            <div class="tab-pane fade" id="bulanan" role="tabpanel" aria-labelledby="bulanan-tab">
                <form method="POST" action="<?= url('/overtime/store') ?>" id="bulananForm" data-add-row-btn="#btnAddBulanan">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <input type="hidden" name="date" value="<?= e($date) ?>">
                    <input type="hidden" name="tipe_input" value="bulanan">
                    
                    <div class="alert alert-info border-0 shadow-sm d-flex align-items-center">
                        <i class="bi bi-info-circle-fill fs-4 me-3"></i>
                        <div>Karyawan bulanan yang Anda isikan nominal lemburnya akan <strong>otomatis dianggap Hadir</strong> pada tanggal ini. Isi 0 jika tidak ada lembur.</div>
                    </div>

                    <div id="bulananRows" class="d-flex flex-column gap-3 mb-3">
                        <div class="bulanan-row card bg-white border border-secondary-subtle p-3">
                            <div class="row g-3 align-items-end">
                                <div class="col-12 col-md-6">
                                    <label class="form-label small text-muted fw-bold mb-1">Pilih Karyawan Bulanan</label>
                                    <select name="bulanan_items[0][id_karyawan]" class="form-select bulanan-select enter-nav" required>
                                        <option value="">-- Pilih Karyawan --</option>
                                        <?= renderEmployeeOptions($bulananEmps) ?>
                                    </select>
                                </div>
                                <div class="col-10 col-md-5">
                                    <label class="form-label small text-muted fw-bold mb-1">Bonus Lembur (Rp)</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light text-muted border-end-0">Rp</span>
                                        <input type="text" name="bulanan_items[0][nominal]" class="form-control text-end fw-bold text-success border-start-0 nominal-input enter-nav" value="0">
                                    </div>
                                </div>
                                <div class="col-2 col-md-1 mt-3 mt-md-0">
                                    <button type="button" class="btn btn-outline-danger w-100 btn-remove-bulanan" title="Hapus Baris" disabled>
                                        <i class="bi bi-trash"></i> <span class="d-md-none ms-1">Hapus</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mb-4">
                        <button type="button" class="btn btn-outline-primary fw-bold rounded-pill px-4 mt-2" id="btnAddBulanan">
                            <i class="bi bi-plus-circle me-1"></i> Tambah Baris Karyawan
                        </button>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill shadow-sm px-5 fw-bold" <?= empty($bulananEmps) ? 'disabled' : '' ?>>
                            <i class="bi bi-save me-2"></i> Simpan Lembur Massal
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>



<script>
let rowCount = 1;

function handleBoronganChange(selectElem) {
    const val = selectElem.value;
    const zonaBorongan = document.getElementById('zona_borongan');
    const btnContainer = document.getElementById('btnContainerBorongan');
    const emptyContainer = document.getElementById('emptyContainerBorongan');

    if (val !== '') {
        zonaBorongan.style.display = 'block';
        btnContainer.style.setProperty('display', 'flex', 'important');
        emptyContainer.style.display = 'none';
    } else {
        zonaBorongan.style.display = 'none';
        btnContainer.style.setProperty('display', 'none', 'important');
        emptyContainer.style.display = 'block';
    }
}

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

// Dynamic Rows untuk Borongan
document.getElementById('btnAddRow')?.addEventListener('click', function() {
    const tbody = document.getElementById('productionRows');
    const templateRow = tbody.querySelector('.item-row').cloneNode(true);
    
    templateRow.querySelector('.product-select').name = `items[${rowCount}][id_produk]`;
    templateRow.querySelector('.product-select').value = '';
    templateRow.querySelector('.product-select').removeAttribute('required');

    const qtyInput = templateRow.querySelectorAll('.nominal-input')[0];
    qtyInput.name = `items[${rowCount}][kuantitas]`;
    qtyInput.value = '0';

    const balInput = templateRow.querySelectorAll('.nominal-input')[1];
    balInput.name = `items[${rowCount}][kuantitas_bal]`;
    balInput.value = '0';

    const btnRemove = templateRow.querySelector('.btn-remove-row');
    btnRemove.removeAttribute('disabled');
    
    tbody.appendChild(templateRow);
    rowCount++;
    updateRemoveButtons();
});

document.getElementById('productionRows')?.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-remove-row');
    if (btn && !btn.hasAttribute('disabled')) {
        const row = btn.closest('.item-row');
        let focusTargetRow = row.previousElementSibling;
        if (!focusTargetRow) focusTargetRow = row.nextElementSibling;
        
        row.remove();
        updateRemoveButtons();
        
        if (focusTargetRow) {
            const firstInput = focusTargetRow.querySelector('.enter-nav');
            if (firstInput) {
                firstInput.focus();
                if (firstInput.tagName.toLowerCase() === 'input') firstInput.select();
            }
        }
    }
});

function updateRemoveButtons() {
    const rows = document.querySelectorAll('#productionRows .item-row');
    if (rows.length === 1) {
        rows[0].querySelector('.btn-remove-row').setAttribute('disabled', 'disabled');
        rows[0].querySelector('.product-select').setAttribute('required', 'required');
    } else {
        rows.forEach(r => r.querySelector('.btn-remove-row').removeAttribute('disabled'));
    }
}

// Dynamic Rows untuk Bulanan
let bulananRowCount = 1;

document.getElementById('btnAddBulanan')?.addEventListener('click', function() {
    const tbody = document.getElementById('bulananRows');
    const templateRow = tbody.querySelector('.bulanan-row').cloneNode(true);
    
    templateRow.querySelector('.bulanan-select').name = `bulanan_items[${bulananRowCount}][id_karyawan]`;
    templateRow.querySelector('.bulanan-select').value = '';
    templateRow.querySelector('.bulanan-select').removeAttribute('required');

    const nominalInput = templateRow.querySelector('.nominal-input');
    nominalInput.name = `bulanan_items[${bulananRowCount}][nominal]`;
    nominalInput.value = '0';

    const btnRemove = templateRow.querySelector('.btn-remove-bulanan');
    btnRemove.removeAttribute('disabled');
    
    tbody.appendChild(templateRow);
    bulananRowCount++;
    updateRemoveButtonsBulanan();
});

document.getElementById('bulananRows')?.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-remove-bulanan');
    if (btn && !btn.hasAttribute('disabled')) {
        const row = btn.closest('.bulanan-row');
        let focusTargetRow = row.previousElementSibling;
        if (!focusTargetRow) focusTargetRow = row.nextElementSibling;
        
        row.remove();
        updateRemoveButtonsBulanan();
        
        if (focusTargetRow) {
            const firstInput = focusTargetRow.querySelector('.enter-nav');
            if (firstInput) {
                firstInput.focus();
                if (firstInput.tagName.toLowerCase() === 'input') firstInput.select();
            }
        }
    }
});

function updateRemoveButtonsBulanan() {
    const rows = document.querySelectorAll('#bulananRows .bulanan-row');
    if (rows.length === 1) {
        rows[0].querySelector('.btn-remove-bulanan').setAttribute('disabled', 'disabled');
        rows[0].querySelector('.bulanan-select').setAttribute('required', 'required');
    } else {
        rows.forEach(r => r.querySelector('.btn-remove-bulanan').removeAttribute('disabled'));
    }
}
</script>
