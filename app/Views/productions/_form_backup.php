<?php
/**
 * @var string $date
 * @var array $employees
 * @var array $products
 * @var array $productions
 */

// Peta Produk untuk lookup cepat nama & grup
$productMap = [];
foreach ($products as $p) {
    $productMap[$p['id']] = $p;
}
?>

<?php if (empty($employees)): ?>
    <div class="alert alert-info border-0 shadow-sm d-flex align-items-center mb-0">
        <i class="bi bi-info-circle-fill me-2 fs-5"></i>
        <div>Belum ada data karyawan aktif dengan tipe gaji borongan. Silakan tambahkan karyawan borongan terlebih dahulu.</div>
    </div>
<?php elseif (empty($products)): ?>
    <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center mb-0">
        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
        <div>Belum ada data Master Produk. Silakan tambahkan produk terlebih dahulu.</div>
    </div>
<?php else: ?>

<?php
// Siapkan data karyawan untuk JS
$filteredEmps = [];
foreach ($employees as $emp) {
    if (!(bool)$emp['aktif']) continue;
    $hasEntry = !empty($productions[$emp['id']]);
    $filteredEmps[] = [
        'id'       => $emp['id'],
        'name'     => $emp['name'],
        'hasEntry' => $hasEntry,
    ];
}

// Siapkan data produk untuk JS (dengan grup)
$productsForJs = [];
foreach ($products as $p) {
    $productsForJs[] = [
        'id'    => $p['id'],
        'name'  => $p['name'],
        'group' => $p['group_name'] ?? '',
    ];
}
?>

<style>
/* â”€â”€ Searchable Dropdown â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.sd-wrapper, .sd-trigger, .sd-value, .sd-arrow {
    user-select: none !important;
    -webkit-user-select: none !important;
    -moz-user-select: none !important;
    -ms-user-select: none !important;
    -webkit-tap-highlight-color: transparent !important;
}
.sd-trigger {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.625rem 1rem;
    background-color: #ffffff !important;
    border: 1px solid #ced4da;
    border-radius: 0.5rem;
    cursor: pointer;
    min-height: 44px;
    transition: all 0.15s ease-in-out;
    font-size: 0.95rem;
    gap: 8px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.04);
}
.sd-trigger:hover {
    border-color: #86b7fe;
    background-color: #fafcff !important;
}
.sd-trigger:focus,
.sd-trigger.open {
    border-color: #0d6efd !important;
    box-shadow: 0 0 0 0.25rem rgba(13,110,253,0.15) !important;
    outline: 0 !important;
    background-color: #ffffff !important;
}
.sd-trigger::selection,
.sd-trigger *::selection {
    background: transparent !important;
    color: inherit !important;
}
.sd-trigger .sd-value {
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    color: #212529;
    font-weight: 500;
}
.sd-trigger .sd-value.sd-placeholder {
    color: #6c757d;
    font-weight: 400;
}
.sd-trigger .sd-arrow {
    flex-shrink: 0;
    color: #6c757d;
    transition: transform 0.2s;
    font-size: 0.75rem;
}
.sd-trigger.open .sd-arrow {
    transform: rotate(180deg);
}
.sd-dropdown {
    display: none;
    position: fixed;          /* body-level agar tidak terpotong */
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    z-index: 9999;
    overflow: hidden;
    animation: sdFadeIn 0.15s ease;
}
@keyframes sdFadeIn {
    from { opacity: 0; transform: translateY(-4px); }
    to   { opacity: 1; transform: translateY(0); }
}
.sd-dropdown.open { display: block; }
.sd-search-wrap {
    padding: 8px;
    border-bottom: 1px solid #f0f0f0;
    position: sticky;
    top: 0;
    background: #fff;
    z-index: 1;
}
.sd-search {
    width: 100%;
    padding: 6px 10px 6px 32px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    font-size: 0.875rem;
    background: #f8f9fa url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 16 16'%3E%3Cpath fill='%236c757d' d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.099zm-5.242 1.156a5.5 5.5 0 1 1 0-11 5.5 5.5 0 0 1 0 11z'/%3E%3C/svg%3E") no-repeat 10px center;
    outline: none;
    transition: border-color 0.15s;
}
.sd-search:focus { border-color: #86b7fe; }
.sd-list {
    max-height: 240px;
    overflow-y: auto;
    padding: 4px 0;
}
.sd-group-label {
    padding: 6px 14px 2px;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #0d6efd;
    background: #f8f9ff;
}
.sd-option {
    padding: 8px 14px;
    cursor: pointer;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: background 0.1s;
}
.sd-option:hover,
.sd-option.focused {
    background: #f0f4ff;
}
.sd-option.selected {
    background: #e8f0fe;
    font-weight: 600;
    color: #0d6efd;
}
.sd-option .sd-badge {
    font-size: 0.68rem;
    padding: 1px 6px;
    border-radius: 20px;
    background: #d1fae5;
    color: #065f46;
    white-space: nowrap;
}
.sd-empty {
    padding: 14px;
    text-align: center;
    color: #adb5bd;
    font-size: 0.875rem;
}
/* Ukuran lg (karyawan) */
.sd-trigger.sd-lg {
    padding: 0.75rem 1.1rem;
    font-size: 1.1r<form id="expressProductionForm" 
      method="POST"
      action="<?= url('/productions/store') ?>"
      hx-post="<?= url('/productions/store') ?>" 
      hx-target="#production-form-container" 
      hx-swap="innerHTML"
      data-add-row-btn="#btnAddRow">

    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
    <input type="hidden" name="date" value="<?= e($date) ?>">

    <!-- Pilih Karyawan Borongan -->
    <div class="mb-4">
        <label for="expressEmployeeSelect" class="form-label fw-semibold">Pilih Karyawan Borongan</label>
        <select name="id_karyawan" class="form-select form-select-lg searchable-select enter-nav" id="expressEmployeeSelect" required onchange="handleProduksiChange(this)">
            <option value="">-- Pilih Karyawan --</option>
            <?php 
            $filteredEmps = [];
            foreach ($employees as $emp) {
                if (!(bool)$emp['aktif']) continue;
                $hasEntry = !empty($productions[$emp['id']]);
                if ($hasEntry) {
                    $emp['name'] .= ' âœ“ (Sudah ada data)';
                }
                $filteredEmps[] = $emp;
            }
            echo renderEmployeeOptions($filteredEmps);
            ?>
        </select>
    </div>

    <!-- Area Input Produk Dinamis -->
    <div id="zona_produksi" style="display: none;" class="p-4 bg-light rounded-3 border border-secondary-subtle mb-4">
        <p class="text-muted small mb-3">Input ini akan otomatis tercatat sebagai hasil produksi karyawan.</p>
        
        <div id="productionRows" class="d-flex flex-column gap-3 mb-3">
            <div class="item-row card bg-white border border-secondary-subtle p-3">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-5">
                        <label class="form-label small text-muted fw-bold mb-1">Nama Produk</label>
                        <select name="items[0][id_produk]" class="form-select product-select searchable-select enter-nav" required>
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

    <div class="d-flex justify-content-end" id="btnContainerProduksi" style="display: none !important;">
        <span class="htmx-indicator spinner-border spinner-border-sm text-primary me-3 mt-3" role="status"></span>
        <button type="submit" class="btn btn-primary btn-lg rounded-pill shadow-sm px-5 fw-bold">
            <i class="bi bi-save me-2"></i> Simpan Produksi Karyawan
        </button>
    </div>
    
    <div id="emptyContainerProduksi" class="text-center text-muted py-5">
        <i class="bi bi-arrow-up-circle fs-1 mb-2 d-block opacity-25"></i>
        <p class="mb-0 fw-semibold">Silakan pilih karyawan borongan terlebih dahulu.</p>
    </div>
</form>

<script>
window.produksiRowCount = window.produksiRowCount || 1;

function handleProduksiChange(selectElem) {
    const val = selectElem.value;
    const zonaProduksi = document.getElementById('zona_produksi');
    const btnContainer = document.getElementById('btnContainerProduksi');
    const emptyContainer = document.getElementById('emptyContainerProduksi');

    if (val !== '') {
        zonaProduksi.style.display = 'block';
        btnContainer.style.setProperty('display', 'flex', 'important');
        emptyContainer.style.display = 'none';
    } else {
        zonaProduksi.style.display = 'none';
        btnContainer.style.setProperty('display', 'none', 'important');
        emptyContainer.style.display = 'block';
    }
}

// Inisialisasi awal
if (window.initSearchableSelects) {
    window.initSearchableSelects();
}

(function() {
    const btnAdd = document.getElementById('btnAddRow');
    const tbody = document.getElementById('productionRows');
    
    if (btnAdd && !btnAdd.dataset.bound) {
        btnAdd.dataset.bound = "true";
        btnAdd.addEventListener('click', function() {
            const templateRow = tbody.querySelector('.item-row').cloneNode(true);
            
            // Hapus UI custom dropdown lama yang ter-clone
            templateRow.querySelectorAll('.sd-wrapper').forEach(w => w.remove());
            
            const sel = templateRow.querySelector('.product-select');
            sel.style.display = '';
            delete sel.dataset.searchableInit;
            sel.name = `items[${window.produksiRowCount}][id_produk]`;
            sel.value = '';
            sel.removeAttribute('required');

            const qtyInput = templateRow.querySelectorAll('.nominal-input')[0];
            qtyInput.name = `items[${window.produksiRowCount}][kuantitas]`;
            qtyInput.value = '0';

            const balInput = templateRow.querySelectorAll('.nominal-input')[1];
            balInput.name = `items[${window.produksiRowCount}][kuantitas_bal]`;
            balInput.value = '0';

            const btnRemove = templateRow.querySelector('.btn-remove-row');
            btnRemove.removeAttribute('disabled');
            
            tbody.appendChild(templateRow);
            if (window.initSearchableSelects) {
                window.initSearchableSelects(templateRow);
            }
            window.produksiRowCount++;
            updateProduksiRemoveButtons();
        });
    }

    if (tbody && !tbody.dataset.bound) {
        tbody.dataset.bound = "true";
        tbody.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-remove-row');
            if (btn && !btn.hasAttribute('disabled')) {
                const row = btn.closest('.item-row');
                let focusTargetRow = row.previousElementSibling || row.nextElementSibling;
                row.remove();
                updateProduksiRemoveButtons();
                if (focusTargetRow) {
                    const firstInput = focusTargetRow.querySelector('.sd-trigger, .enter-nav');
                    if (firstInput) firstInput.focus();
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
            rows.forEach(r => r.querySelector('.btn-remove-row').removeAttribute('disabled'));
        }
    }
})();
</script>

<?php endif; ?>
€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    if (!window.nominalInputInitialized) {
        window.nominalInputInitialized = true;
        document.addEventListener('input', function(e) {
            if (e.target && e.target.classList.contains('nominal-input')) {
                let val = e.target.value.replace(/[^0-9]/g, '');
                if (val === '') val = '0';
                e.target.value = parseInt(val, 10).toLocaleString('id-ID');
            }
        });
        document.addEventListener('focusin', function(e) {
            if (e.target && e.target.classList.contains('nominal-input')) {
                e.target.select();
            }
        });
    }

})();
</script>

<?php endif; ?>


