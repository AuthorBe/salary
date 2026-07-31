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

    <form id="expressProductionForm" 
          method="POST"
          action="<?= url('/productions/store') ?>"
          hx-post="<?= url('/productions/store') ?>" 
          hx-target="#production-form-container" 
          hx-swap="innerHTML"
          data-add-row-btn="#btnAddRow">

        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
        <input type="hidden" name="date" value="<?= e($date) ?>">

        <!-- Pilih Karyawan -->
        <div class="mb-4">
            <label for="expressEmployeeSelect" class="form-label fw-semibold">Pilih Karyawan Borongan</label>
            <select name="id_karyawan" class="form-select form-select-lg border-primary-subtle enter-nav" id="expressEmployeeSelect" required onchange="handleProduksiChange(this)">
                <option value="">-- Pilih Karyawan --</option>
                <?php 
                $filteredEmps = [];
                foreach ($employees as $emp) {
                    if (!(bool)$emp['aktif']) continue;
                    $hasEntry = !empty($productions[$emp['id']]);
                    if ($hasEntry) {
                        $emp['name'] .= ' ✓ (Sudah ada data)';
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

    <!-- Script Tambah Baris Produk Dinamis -->
    <script>
        // Gunakan variable yang unik atau hindari redeclaration jika dipanggil ulang via HTMX
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

        // Format Nominal Input khusus jika belum dipasang di parent
        if (!window.nominalInputInitialized) {
            window.nominalInputInitialized = true;
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
        }

        // Event listener dengan pengecekan agar tidak double binding
        (function() {
            const btnAdd = document.getElementById('btnAddRow');
            const tbody = document.getElementById('productionRows');
            
            if (btnAdd && !btnAdd.dataset.bound) {
                btnAdd.dataset.bound = "true";
                btnAdd.addEventListener('click', function() {
                    const templateRow = tbody.querySelector('.item-row').cloneNode(true);
                    
                    templateRow.querySelector('.product-select').name = `items[${window.produksiRowCount}][id_produk]`;
                    templateRow.querySelector('.product-select').value = '';
                    templateRow.querySelector('.product-select').removeAttribute('required');

                    const qtyInput = templateRow.querySelectorAll('.nominal-input')[0];
                    qtyInput.name = `items[${window.produksiRowCount}][kuantitas]`;
                    qtyInput.value = '0';

                    const balInput = templateRow.querySelectorAll('.nominal-input')[1];
                    balInput.name = `items[${window.produksiRowCount}][kuantitas_bal]`;
                    balInput.value = '0';

                    const btnRemove = templateRow.querySelector('.btn-remove-row');
                    btnRemove.removeAttribute('disabled');
                    
                    tbody.appendChild(templateRow);
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
                        let focusTargetRow = row.previousElementSibling;
                        if (!focusTargetRow) focusTargetRow = row.nextElementSibling;
                        
                        row.remove();
                        updateProduksiRemoveButtons();
                        
                        // Kembalikan kursor ke baris lain agar tidak hilang
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
                    rows.forEach(r => r.querySelector('.btn-remove-row').removeAttribute('disabled'));
                }
            }
        })();
    </script>

<?php endif; ?>
