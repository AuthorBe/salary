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
/* ── Searchable Dropdown ─────────────────────────────── */
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
    font-size: 1.1rem;
    min-height: 52px;
    border-radius: 0.5rem;
}
</style>

<form id="expressProductionForm" 
      method="POST"
      action="<?= url('/productions/store') ?>"
      hx-post="<?= url('/productions/store') ?>" 
      hx-target="#production-form-container" 
      hx-swap="innerHTML"
      data-add-row-btn="#btnAddRow">

    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
    <input type="hidden" name="date" value="<?= e($date) ?>">

    <!-- Hidden input untuk id_karyawan (pengganti <select>) -->
    <input type="hidden" name="id_karyawan" id="hiddenKaryawanId" value="">

    <!-- Pilih Karyawan (Searchable) -->
    <div class="mb-4">
        <label class="form-label fw-semibold">Pilih Karyawan Borongan</label>
        <div class="sd-wrapper" id="sdWrapperKaryawan">
            <div class="sd-trigger sd-lg enter-nav" tabindex="0" id="sdTriggerKaryawan" aria-haspopup="listbox" aria-expanded="false" role="combobox">
                <span class="sd-value sd-placeholder" id="sdValueKaryawan">-- Pilih Karyawan --</span>
                <i class="bi bi-chevron-down sd-arrow"></i>
            </div>
            <div class="sd-dropdown" id="sdDropdownKaryawan" role="listbox">
                <div class="sd-search-wrap">
                    <input type="text" class="sd-search" id="sdSearchKaryawan" placeholder="Ketik nama karyawan..." autocomplete="off" tabindex="-1">
                </div>
                <div class="sd-list" id="sdListKaryawan">
                    <!-- Render by JS -->
                </div>
            </div>
        </div>
    </div>

    <!-- Area Input Produk Dinamis -->
    <div id="zona_produksi" style="display: none;" class="p-4 bg-light rounded-3 border border-secondary-subtle mb-4">
        <p class="text-muted small mb-3">Input ini akan otomatis tercatat sebagai hasil produksi karyawan.</p>
        
        <div id="productionRows" class="d-flex flex-column gap-3 mb-3">
            <div class="item-row card bg-white border border-secondary-subtle p-3">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-5">
                        <label class="form-label small text-muted fw-bold mb-1">Nama Produk</label>
                        <!-- Hidden input untuk id_produk row 0 -->
                        <input type="hidden" name="items[0][id_produk]" class="hidden-produk-id" value="">
                        <div class="sd-wrapper sd-produk-wrapper">
                            <div class="sd-trigger enter-nav" tabindex="0" data-row="0" aria-haspopup="listbox" aria-expanded="false" role="combobox">
                                <span class="sd-value sd-placeholder">-- Pilih Produk --</span>
                                <i class="bi bi-chevron-down sd-arrow"></i>
                            </div>
                            <div class="sd-dropdown" role="listbox">
                                <div class="sd-search-wrap">
                                    <input type="text" class="sd-search sd-search-produk" placeholder="Ketik nama produk..." autocomplete="off" tabindex="-1">
                                </div>
                                <div class="sd-list sd-list-produk">
                                    <!-- Render by JS -->
                                </div>
                            </div>
                        </div>
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
(function() {
    // Bersihkan dropdown lama di body jika ada (akibat HTMX re-render)
    document.querySelectorAll('body > .sd-dropdown').forEach(el => el.remove());

    // ── DATA ─────────────────────────────────────────────────────────────────
    const KARYAWAN_DATA = <?= json_encode(array_values($filteredEmps), JSON_UNESCAPED_UNICODE) ?>;
    const PRODUK_DATA   = <?= json_encode(array_values($productsForJs), JSON_UNESCAPED_UNICODE) ?>;

    // ── SEARCHABLE DROPDOWN FACTORY ──────────────────────────────────────────
    /**
     * Inisialisasi satu searchable dropdown.
     * @param {Object} cfg
     *   trigger    HTMLElement  – tombol pemicu
     *   dropdown   HTMLElement  – kotak dropdown
     *   searchEl   HTMLElement  – input pencarian
     *   listEl     HTMLElement  – kontainer opsi
     *   valueEl    HTMLElement  – span teks judul
     *   data       Array        – [{id, name, group?, badge?}]
     *   onSelect   Function(id, name) – callback setelah dipilih
     *   placeholder string
     */
    function initSearchableDropdown(cfg) {
        const { trigger, dropdown, searchEl, listEl, valueEl, data, onSelect, placeholder } = cfg;
        let focusedIdx = -1;

        // Render ulang daftar
        function renderList(query) {
            const q = (query || '').toLowerCase().trim();
            listEl.innerHTML = '';
            focusedIdx = -1;

            // Kelompokkan berdasarkan group (jika ada)
            const hasGroup = data.some(d => d.group);

            let filtered;
            if (!hasGroup) {
                filtered = data.filter(d => !q || d.name.toLowerCase().includes(q));
                if (!filtered.length) {
                    listEl.innerHTML = `<div class="sd-empty"><i class="bi bi-search me-1"></i>Tidak ditemukan</div>`;
                    return;
                }
                filtered.forEach((item, i) => {
                    const opt = buildOption(item, i);
                    listEl.appendChild(opt);
                });
            } else {
                // Group rendering
                const groups = {};
                data.forEach(d => {
                    if (q && !d.name.toLowerCase().includes(q)) return;
                    const g = d.group || '—';
                    if (!groups[g]) groups[g] = [];
                    groups[g].push(d);
                });

                let totalCount = 0;
                let idx = 0;
                Object.keys(groups).forEach(gName => {
                    const groupLabel = document.createElement('div');
                    groupLabel.className = 'sd-group-label';
                    groupLabel.textContent = gName;
                    listEl.appendChild(groupLabel);
                    groups[gName].forEach(item => {
                        const opt = buildOption(item, idx++);
                        listEl.appendChild(opt);
                        totalCount++;
                    });
                });

                if (!totalCount) {
                    listEl.innerHTML = `<div class="sd-empty"><i class="bi bi-search me-1"></i>Tidak ditemukan</div>`;
                }
            }
        }

        function buildOption(item, idx) {
            const opt = document.createElement('div');
            opt.className = 'sd-option';
            opt.setAttribute('role', 'option');
            opt.dataset.id   = item.id;
            opt.dataset.name = item.name;
            opt.dataset.idx  = idx;

            let inner = `<span>${item.name}</span>`;
            if (item.badge) inner += `<span class="sd-badge">${item.badge}</span>`;
            opt.innerHTML = inner;

            opt.addEventListener('mousedown', (e) => {
                e.preventDefault();
                selectItem(item.id, item.name);
            });
            return opt;
        }

        dropdown._sdTrigger = trigger;

        function selectItem(id, name) {
            valueEl.textContent = name;
            valueEl.classList.remove('sd-placeholder');
            closeDropdown();
            onSelect(id, name);
            if (typeof window.moveNextNavElement === 'function') {
                setTimeout(() => window.moveNextNavElement(trigger), 50);
            }
        }

        // Pindahkan dropdown ke body agar tidak terpotong overflow apapun
        if (dropdown.parentElement !== document.body) {
            document.body.appendChild(dropdown);
        }

        function positionDropdown() {
            const rect = trigger.getBoundingClientRect();
            const spaceBelow = window.innerHeight - rect.bottom;
            const dropH = Math.min(300, dropdown.scrollHeight || 300);
            const goUp = spaceBelow < dropH + 8 && rect.top > dropH + 8;

            dropdown.style.width = rect.width + 'px';
            dropdown.style.left  = rect.left + 'px';

            if (goUp) {
                dropdown.style.top    = '';
                dropdown.style.bottom = (window.innerHeight - rect.top + 4) + 'px';
            } else {
                dropdown.style.top    = (rect.bottom + 4) + 'px';
                dropdown.style.bottom = '';
            }
        }

        function openDropdown() {
            // Tutup semua dropdown lain dulu
            document.querySelectorAll('.sd-dropdown.open').forEach(d => {
                if (d !== dropdown) d.classList.remove('open');
            });
            document.querySelectorAll('.sd-trigger.open').forEach(t => {
                if (t !== trigger) {
                    t.classList.remove('open');
                    t.setAttribute('aria-expanded', 'false');
                }
            });

            dropdown.classList.add('open');
            trigger.classList.add('open');
            trigger.setAttribute('aria-expanded', 'true');
            searchEl.value = '';
            renderList('');
            positionDropdown();
            setTimeout(() => searchEl.focus(), 50);
        }

        function closeDropdown() {
            dropdown.classList.remove('open');
            trigger.classList.remove('open');
            trigger.setAttribute('aria-expanded', 'false');
        }

        function moveFocus(dir) {
            const opts = listEl.querySelectorAll('.sd-option');
            if (!opts.length) return;
            opts.forEach(o => o.classList.remove('focused'));
            focusedIdx = Math.max(0, Math.min(opts.length - 1, focusedIdx + dir));
            opts[focusedIdx].classList.add('focused');
            opts[focusedIdx].scrollIntoView({ block: 'nearest' });
        }

        // Events
        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdown.classList.contains('open') ? closeDropdown() : openDropdown();
        });
        trigger.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
                e.preventDefault();
                openDropdown();
            }
        });
        searchEl.addEventListener('input', () => renderList(searchEl.value));
        searchEl.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowDown') { e.preventDefault(); moveFocus(1); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); moveFocus(-1); }
            else if (e.key === 'Enter') {
                e.preventDefault();
                const focused = listEl.querySelector('.sd-option.focused');
                if (focused) selectItem(focused.dataset.id, focused.dataset.name);
            }
            else if (e.key === 'Escape') { e.stopPropagation(); closeDropdown(); trigger.focus(); }
        });

        // Tutup jika klik di luar
        document.addEventListener('mousedown', (e) => {
            if (!trigger.contains(e.target) && !dropdown.contains(e.target)) {
                closeDropdown();
            }
        });

        // Reposisi saat scroll atau resize
        window.addEventListener('scroll', () => { if (dropdown.classList.contains('open')) positionDropdown(); }, true);
        window.addEventListener('resize', () => { if (dropdown.classList.contains('open')) positionDropdown(); });

        // Render awal
        renderList('');
    }

    // ── KARYAWAN DROPDOWN ─────────────────────────────────────────────────────
    window.produksiRowCount = window.produksiRowCount || 1;

    const karyawanData = KARYAWAN_DATA.map(e => ({
        id:    e.id,
        name:  e.name,
        badge: e.hasEntry ? 'Sudah ada data' : null,
    }));

    initSearchableDropdown({
        trigger:     document.getElementById('sdTriggerKaryawan'),
        dropdown:    document.getElementById('sdDropdownKaryawan'),
        searchEl:    document.getElementById('sdSearchKaryawan'),
        listEl:      document.getElementById('sdListKaryawan'),
        valueEl:     document.getElementById('sdValueKaryawan'),
        data:        karyawanData,
        placeholder: '-- Pilih Karyawan --',
        onSelect: function(id, name) {
            document.getElementById('hiddenKaryawanId').value = id;
            // Tampilkan area produk
            const zonaProduksi     = document.getElementById('zona_produksi');
            const btnContainer     = document.getElementById('btnContainerProduksi');
            const emptyContainer   = document.getElementById('emptyContainerProduksi');
            zonaProduksi.style.display = 'block';
            btnContainer.style.setProperty('display', 'flex', 'important');
            emptyContainer.style.display = 'none';
        }
    });

    // ── PRODUK DROPDOWN FACTORY ───────────────────────────────────────────────
    const produkData = PRODUK_DATA.map(p => ({
        id:    p.id,
        name:  p.name,
        group: p.group,
    }));

    function initProdukDropdown(wrapper) {
        const trigger  = wrapper.querySelector('.sd-trigger');
        const dropdown = wrapper.querySelector('.sd-dropdown');
        const searchEl = wrapper.querySelector('.sd-search-produk');
        const listEl   = wrapper.querySelector('.sd-list-produk');
        const valueEl  = trigger.querySelector('.sd-value');
        const hiddenId = wrapper.closest('.item-row, .col-12').querySelector('.hidden-produk-id')
                      || wrapper.parentElement.querySelector('.hidden-produk-id');

        initSearchableDropdown({
            trigger, dropdown, searchEl, listEl, valueEl,
            data:        produkData,
            placeholder: '-- Pilih Produk --',
            onSelect: function(id, name) {
                if (hiddenId) hiddenId.value = id;
            }
        });
    }

    // Inisialisasi produk dropdown pada baris pertama
    document.querySelectorAll('.sd-produk-wrapper').forEach(w => initProdukDropdown(w));

    // ── TAMBAH BARIS PRODUK ───────────────────────────────────────────────────
    const btnAdd = document.getElementById('btnAddRow');
    const tbody  = document.getElementById('productionRows');

    if (btnAdd && !btnAdd.dataset.bound) {
        btnAdd.dataset.bound = 'true';
        btnAdd.addEventListener('click', function() {
            const idx = window.produksiRowCount;
            const newRow = document.createElement('div');
            newRow.className = 'item-row card bg-white border border-secondary-subtle p-3';
            newRow.innerHTML = `
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-5">
                        <label class="form-label small text-muted fw-bold mb-1">Nama Produk</label>
                        <input type="hidden" name="items[${idx}][id_produk]" class="hidden-produk-id" value="">
                        <div class="sd-wrapper sd-produk-wrapper">
                            <div class="sd-trigger enter-nav" tabindex="0" data-row="${idx}" aria-haspopup="listbox" aria-expanded="false" role="combobox">
                                <span class="sd-value sd-placeholder">-- Pilih Produk --</span>
                                <i class="bi bi-chevron-down sd-arrow"></i>
                            </div>
                            <div class="sd-dropdown" role="listbox">
                                <div class="sd-search-wrap">
                                    <input type="text" class="sd-search sd-search-produk" placeholder="Ketik nama produk..." autocomplete="off" tabindex="-1">
                                </div>
                                <div class="sd-list sd-list-produk"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small text-muted fw-bold mb-1">Qty (Bungkus)</label>
                        <input type="text" name="items[${idx}][kuantitas]" class="form-control text-end nominal-input enter-nav" value="0">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small text-muted fw-bold mb-1">Qty (Bal)</label>
                        <input type="text" name="items[${idx}][kuantitas_bal]" class="form-control text-end nominal-input enter-nav" value="0">
                    </div>
                    <div class="col-12 col-md-1 mt-3 mt-md-0">
                        <button type="button" class="btn btn-outline-danger w-100 btn-remove-row" title="Hapus Baris">
                            <i class="bi bi-trash"></i> <span class="d-md-none ms-1">Hapus</span>
                        </button>
                    </div>
                </div>`;
            tbody.appendChild(newRow);
            initProdukDropdown(newRow.querySelector('.sd-produk-wrapper'));
            window.produksiRowCount++;
            updateProduksiRemoveButtons();
            // Fokus ke trigger produk baris baru
            setTimeout(() => newRow.querySelector('.sd-trigger')?.focus(), 50);
        });
    }

    if (tbody && !tbody.dataset.bound) {
        tbody.dataset.bound = 'true';
        tbody.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-remove-row');
            if (btn && !btn.hasAttribute('disabled')) {
                const row = btn.closest('.item-row');
                let next = row.previousElementSibling || row.nextElementSibling;
                row.remove();
                updateProduksiRemoveButtons();
                if (next) {
                    const firstInput = next.querySelector('.sd-trigger, .enter-nav');
                    if (firstInput) firstInput.focus();
                }
            }
        });
    }

    function updateProduksiRemoveButtons() {
        const rows = document.querySelectorAll('#productionRows .item-row');
        rows.forEach((r, i) => {
            const btn = r.querySelector('.btn-remove-row');
            if (rows.length === 1) btn.setAttribute('disabled', 'disabled');
            else btn.removeAttribute('disabled');
        });
    }

    // ── FORMAT NOMINAL INPUT ──────────────────────────────────────────────────
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

