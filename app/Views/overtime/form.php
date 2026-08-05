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

// Data JS untuk Searchable Dropdown
$boronganEmpsForJs = [];
foreach ($boronganEmps as $emp) {
    if (!(bool)$emp['aktif']) continue;
    $boronganEmpsForJs[] = [
        'id'   => $emp['id'],
        'name' => $emp['name'],
    ];
}

$bulananEmpsForJs = [];
foreach ($bulananEmps as $emp) {
    if (!(bool)$emp['aktif']) continue;
    $bulananEmpsForJs[] = [
        'id'   => $emp['id'],
        'name' => $emp['name'],
    ];
}

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
    
<?php
$activeTab = $_GET['tab'] ?? 'borongan';
?>
    <div class="card-body p-0">
        <div class="d-flex border-bottom px-4 pt-3" role="tablist">
            <a href="#borongan" data-bs-toggle="tab" role="tab" class="custom-tab <?= $activeTab === 'borongan' ? 'active' : '' ?> fw-bold px-4 py-3 text-decoration-none">
                <i class="bi bi-box-seam text-warning me-2"></i> Lembur Borongan
            </a>
            <a href="#bulanan" data-bs-toggle="tab" role="tab" class="custom-tab <?= $activeTab === 'bulanan' ? 'active' : '' ?> fw-bold px-4 py-3 text-decoration-none">
                <i class="bi bi-clock-history text-primary me-2"></i> Lembur Bulanan (Massal)
            </a>
        </div>

        <div class="tab-content p-4" id="overtimeTabsContent">
            
            <!-- TAB BORONGAN -->
            <div class="tab-pane fade <?= $activeTab === 'borongan' ? 'show active' : '' ?>" id="borongan" role="tabpanel" aria-labelledby="borongan-tab">
                <form method="POST" action="<?= url('/overtime/store') ?>" id="boronganForm" data-add-row-btn="#btnAddRow">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <input type="hidden" name="date" value="<?= e($date) ?>">
                    <input type="hidden" name="tipe_input" value="borongan">

                    <input type="hidden" name="id_karyawan" id="hiddenBoronganKaryawanId" value="">

                    <!-- Pilih Karyawan Borongan -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Pilih Karyawan Borongan</label>
                        <div class="sd-wrapper" id="sdWrapperBoronganKaryawan">
                            <div class="sd-trigger sd-lg enter-nav" tabindex="0" id="sdTriggerBoronganKaryawan" aria-haspopup="listbox" aria-expanded="false" role="combobox">
                                <span class="sd-value sd-placeholder" id="sdValueBoronganKaryawan">-- Pilih Karyawan --</span>
                                <i class="bi bi-chevron-down sd-arrow"></i>
                            </div>
                            <div class="sd-dropdown" id="sdDropdownBoronganKaryawan" role="listbox">
                                <div class="sd-search-wrap">
                                    <input type="text" class="sd-search" id="sdSearchBoronganKaryawan" placeholder="Ketik nama karyawan..." autocomplete="off" tabindex="-1">
                                </div>
                                <div class="sd-list" id="sdListBoronganKaryawan"></div>
                            </div>
                        </div>
                    </div>

                    <div id="zona_borongan" style="display: none;" class="p-4 bg-light rounded-3 border border-secondary-subtle mb-4">
                        <p class="text-muted small mb-3">Input ini akan otomatis tercatat sebagai hasil produksi lembur karyawan.</p>
                        
                        <div id="productionRows" class="d-flex flex-column gap-3 mb-3">
                            <div class="item-row card bg-white border border-secondary-subtle p-3">
                                <div class="row g-3 align-items-end">
                                    <div class="col-12 col-md-5">
                                        <label class="form-label small text-muted fw-bold mb-1">Nama Produk</label>
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
                                                <div class="sd-list sd-list-produk"></div>
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
            <div class="tab-pane fade <?= $activeTab === 'bulanan' ? 'show active' : '' ?>" id="bulanan" role="tabpanel" aria-labelledby="bulanan-tab">
                <form method="POST" action="<?= url('/overtime/store') ?>" id="bulananForm" data-add-row-btn="#btnAddBulanan">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <input type="hidden" name="date" value="<?= e($date) ?>">
                    <input type="hidden" name="tipe_input" value="bulanan">
                    
                    <div class="alert alert-info border-0 shadow-sm d-flex align-items-center">
                        <i class="bi bi-info-circle-fill fs-4 me-3"></i>
                        <div>Karyawan bulanan yang dimasukkan di form ini wajib diisikan <strong>Bonus Lembur (Rp) lebih dari Rp 0</strong>. Penginputan lembur otomatis memastikan karyawan dicatat Hadir pada hari tersebut.</div>
                    </div>

                    <div id="bulananRows" class="d-flex flex-column gap-3 mb-3">
                        <div class="bulanan-row card bg-white border border-secondary-subtle p-3">
                            <div class="row g-3 align-items-end">
                                <div class="col-12 col-md-6">
                                    <label class="form-label small text-muted fw-bold mb-1">Pilih Karyawan Bulanan</label>
                                    <input type="hidden" name="bulanan_items[0][id_karyawan]" class="hidden-bulanan-emp-id" value="">
                                    <div class="sd-wrapper sd-bulanan-emp-wrapper">
                                        <div class="sd-trigger enter-nav" tabindex="0" aria-haspopup="listbox" aria-expanded="false" role="combobox">
                                            <span class="sd-value sd-placeholder">-- Pilih Karyawan --</span>
                                            <i class="bi bi-chevron-down sd-arrow"></i>
                                        </div>
                                        <div class="sd-dropdown" role="listbox">
                                            <div class="sd-search-wrap">
                                                <input type="text" class="sd-search sd-search-bulanan-emp" placeholder="Ketik nama karyawan..." autocomplete="off" tabindex="-1">
                                            </div>
                                            <div class="sd-list sd-list-bulanan-emp"></div>
                                        </div>
                                    </div>
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
(function() {
    // Bersihkan dropdown lama di body jika ada
    document.querySelectorAll('body > .sd-dropdown').forEach(el => el.remove());

    const BORONGAN_EMPS_DATA = <?= json_encode(array_values($boronganEmpsForJs), JSON_UNESCAPED_UNICODE) ?>;
    const BULANAN_EMPS_DATA  = <?= json_encode(array_values($bulananEmpsForJs), JSON_UNESCAPED_UNICODE) ?>;
    const PRODUK_DATA        = <?= json_encode(array_values($productsForJs), JSON_UNESCAPED_UNICODE) ?>;

    window.rowCount = window.rowCount || 1;
    window.bulananRowCount = window.bulananRowCount || 1;

    // ── SEARCHABLE DROPDOWN FACTORY ──────────────────────────────────────────
    function initSearchableDropdown(cfg) {
        const { trigger, dropdown, searchEl, listEl, valueEl, data, onSelect } = cfg;
        let focusedIdx = -1;

        function renderList(query) {
            const q = (query || '').toLowerCase().trim();
            listEl.innerHTML = '';
            focusedIdx = -1;

            const hasGroup = data.some(d => d.group);

            if (!hasGroup) {
                const filtered = data.filter(d => !q || d.name.toLowerCase().includes(q));
                if (!filtered.length) {
                    listEl.innerHTML = `<div class="sd-empty"><i class="bi bi-search me-1"></i>Tidak ditemukan</div>`;
                    return;
                }
                filtered.forEach((item, i) => {
                    listEl.appendChild(buildOption(item, i));
                });
            } else {
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
                        listEl.appendChild(buildOption(item, idx++));
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
            opt.innerHTML    = `<span>${item.name}</span>`;

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

        document.addEventListener('mousedown', (e) => {
            if (!trigger.contains(e.target) && !dropdown.contains(e.target)) {
                closeDropdown();
            }
        });

        window.addEventListener('scroll', () => { if (dropdown.classList.contains('open')) positionDropdown(); }, true);
        window.addEventListener('resize', () => { if (dropdown.classList.contains('open')) positionDropdown(); });

        renderList('');
    }

    // ── KARYAWAN BORONGAN DROPDOWN ───────────────────────────────────────────
    initSearchableDropdown({
        trigger:     document.getElementById('sdTriggerBoronganKaryawan'),
        dropdown:    document.getElementById('sdDropdownBoronganKaryawan'),
        searchEl:    document.getElementById('sdSearchBoronganKaryawan'),
        listEl:      document.getElementById('sdListBoronganKaryawan'),
        valueEl:     document.getElementById('sdValueBoronganKaryawan'),
        data:        BORONGAN_EMPS_DATA,
        onSelect: function(id, name) {
            document.getElementById('hiddenBoronganKaryawanId').value = id;
            const zona = document.getElementById('zona_borongan');
            const btn  = document.getElementById('btnContainerBorongan');
            const emp  = document.getElementById('emptyContainerBorongan');
            zona.style.display = 'block';
            btn.style.setProperty('display', 'flex', 'important');
            emp.style.display = 'none';
        }
    });

    // ── PRODUK DROPDOWN FACTORY ───────────────────────────────────────────────
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
            data: PRODUK_DATA,
            onSelect: function(id, name) {
                if (hiddenId) hiddenId.value = id;
            }
        });
    }

    document.querySelectorAll('.sd-produk-wrapper').forEach(w => initProdukDropdown(w));

    // ── KARYAWAN BULANAN DROPDOWN FACTORY ─────────────────────────────────────
    function initBulananEmpDropdown(wrapper) {
        const trigger  = wrapper.querySelector('.sd-trigger');
        const dropdown = wrapper.querySelector('.sd-dropdown');
        const searchEl = wrapper.querySelector('.sd-search-bulanan-emp');
        const listEl   = wrapper.querySelector('.sd-list-bulanan-emp');
        const valueEl  = trigger.querySelector('.sd-value');
        const hiddenId = wrapper.closest('.bulanan-row, .col-12').querySelector('.hidden-bulanan-emp-id')
                      || wrapper.parentElement.querySelector('.hidden-bulanan-emp-id');

        initSearchableDropdown({
            trigger, dropdown, searchEl, listEl, valueEl,
            data: BULANAN_EMPS_DATA,
            onSelect: function(id, name) {
                if (hiddenId) hiddenId.value = id;
            }
        });
    }

    document.querySelectorAll('.sd-bulanan-emp-wrapper').forEach(w => initBulananEmpDropdown(w));

    // ── DYNAMIC ROWS BORONGAN ────────────────────────────────────────────────
    const btnAdd = document.getElementById('btnAddRow');
    const tbody  = document.getElementById('productionRows');

    if (btnAdd && !btnAdd.dataset.bound) {
        btnAdd.dataset.bound = 'true';
        btnAdd.addEventListener('click', function() {
            const idx = window.rowCount;
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
            window.rowCount++;
            updateRemoveButtons();
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
                updateRemoveButtons();
                if (next) {
                    const firstInput = next.querySelector('.sd-trigger, .enter-nav');
                    if (firstInput) firstInput.focus();
                }
            }
        });
    }

    function updateRemoveButtons() {
        const rows = document.querySelectorAll('#productionRows .item-row');
        rows.forEach(r => {
            const btn = r.querySelector('.btn-remove-row');
            if (rows.length === 1) btn.setAttribute('disabled', 'disabled');
            else btn.removeAttribute('disabled');
        });
    }

    // ── DYNAMIC ROWS BULANAN ─────────────────────────────────────────────────
    const btnAddBulanan = document.getElementById('btnAddBulanan');
    const tbodyBulanan  = document.getElementById('bulananRows');

    if (btnAddBulanan && !btnAddBulanan.dataset.bound) {
        btnAddBulanan.dataset.bound = 'true';
        btnAddBulanan.addEventListener('click', function() {
            const idx = window.bulananRowCount;
            const newRow = document.createElement('div');
            newRow.className = 'bulanan-row card bg-white border border-secondary-subtle p-3';
            newRow.innerHTML = `
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-6">
                        <label class="form-label small text-muted fw-bold mb-1">Pilih Karyawan Bulanan</label>
                        <input type="hidden" name="bulanan_items[${idx}][id_karyawan]" class="hidden-bulanan-emp-id" value="">
                        <div class="sd-wrapper sd-bulanan-emp-wrapper">
                            <div class="sd-trigger enter-nav" tabindex="0" aria-haspopup="listbox" aria-expanded="false" role="combobox">
                                <span class="sd-value sd-placeholder">-- Pilih Karyawan --</span>
                                <i class="bi bi-chevron-down sd-arrow"></i>
                            </div>
                            <div class="sd-dropdown" role="listbox">
                                <div class="sd-search-wrap">
                                    <input type="text" class="sd-search sd-search-bulanan-emp" placeholder="Ketik nama karyawan..." autocomplete="off" tabindex="-1">
                                </div>
                                <div class="sd-list sd-list-bulanan-emp"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-10 col-md-5">
                        <label class="form-label small text-muted fw-bold mb-1">Bonus Lembur (Rp)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted border-end-0">Rp</span>
                            <input type="text" name="bulanan_items[${idx}][nominal]" class="form-control text-end fw-bold text-success border-start-0 nominal-input enter-nav" value="0">
                        </div>
                    </div>
                    <div class="col-2 col-md-1 mt-3 mt-md-0">
                        <button type="button" class="btn btn-outline-danger w-100 btn-remove-bulanan" title="Hapus Baris">
                            <i class="bi bi-trash"></i> <span class="d-md-none ms-1">Hapus</span>
                        </button>
                    </div>
                </div>`;
            tbodyBulanan.appendChild(newRow);
            initBulananEmpDropdown(newRow.querySelector('.sd-bulanan-emp-wrapper'));
            window.bulananRowCount++;
            updateRemoveButtonsBulanan();
            setTimeout(() => newRow.querySelector('.sd-trigger')?.focus(), 50);
        });
    }

    if (tbodyBulanan && !tbodyBulanan.dataset.bound) {
        tbodyBulanan.dataset.bound = 'true';
        tbodyBulanan.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-remove-bulanan');
            if (btn && !btn.hasAttribute('disabled')) {
                const row = btn.closest('.bulanan-row');
                let next = row.previousElementSibling || row.nextElementSibling;
                row.remove();
                updateRemoveButtonsBulanan();
                if (next) {
                    const firstInput = next.querySelector('.sd-trigger, .enter-nav');
                    if (firstInput) firstInput.focus();
                }
            }
        });
    }

    function updateRemoveButtonsBulanan() {
        const rows = document.querySelectorAll('#bulananRows .bulanan-row');
        rows.forEach(r => {
            const btn = r.querySelector('.btn-remove-bulanan');
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

