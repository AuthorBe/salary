/**
 * Global Searchable Select Component
 * Transform any standard <select class="searchable-select"> into a custom searchable dropdown.
 * Supports <optgroup>, data-badge, keyboard navigation (.enter-nav), and HTMX re-renders.
 *
 * Bug Fixes:
 * - [Fix #1] mousedown listener GLOBAL di IIFE — tidak lagi menumpuk per-select
 * - [Fix #2] trigger._sdSelect / dropdown._sdSelect menyimpan ref ke <select> asli
 * - [Fix #3] window.closeAllSdDropdowns() di-expose sebagai public API
 * - [Fix #4] scroll/resize listener dibersihkan via AbortController saat elemen dihapus
 * - [Fix #5] positionDropdown memperhitungkan window.scrollX/Y (akurat di halaman yg bisa di-scroll)
 */

(function() {
    'use strict';

    // ─── [Fix #1] Satu mousedown listener GLOBAL untuk semua dropdown ────────────────
    // Dipasang sekali di IIFE, bukan per-select, sehingga tidak menumpuk.
    document.addEventListener('mousedown', function(e) {
        document.querySelectorAll('.sd-dropdown.open').forEach(function(dropdown) {
            const trigger = dropdown._sdTrigger;
            if (trigger && !trigger.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.remove('open');
                trigger.classList.remove('open');
                trigger.setAttribute('aria-expanded', 'false');
            }
        });
    });

    // ─── [Fix #3] Public API: tutup semua sd-dropdown yang terbuka ───────────────────
    window.closeAllSdDropdowns = function() {
        document.querySelectorAll('.sd-dropdown.open').forEach(function(dropdown) {
            const trigger = dropdown._sdTrigger;
            dropdown.classList.remove('open');
            if (trigger) {
                trigger.classList.remove('open');
                trigger.setAttribute('aria-expanded', 'false');
            }
        });
    };

    // ─── Helper: Bersihkan dropdown yatim piatu di body saat HTMX swap ──────────────
    function cleanupOrphanDropdowns() {
        document.querySelectorAll('body > .sd-dropdown').forEach(function(dropdown) {
            if (dropdown._sdTrigger && !document.body.contains(dropdown._sdTrigger)) {
                dropdown.remove();
            }
        });
    }

    /**
     * Transform satu <select> menjadi searchable dropdown.
     */
    function transformSelect(selectEl) {
        if (!selectEl || selectEl.dataset.searchableInit === "true") return;
        selectEl.dataset.searchableInit = "true";
        selectEl.style.display = 'none';

        // Baca opsi & grup
        const optionsData = [];
        let placeholderText = '-- Pilih --';

        Array.from(selectEl.children).forEach(child => {
            if (child.tagName.toLowerCase() === 'optgroup') {
                const groupName = child.getAttribute('label') || '';
                Array.from(child.children).forEach(opt => {
                    if (opt.tagName.toLowerCase() === 'option') {
                        optionsData.push({
                            id:       opt.value,
                            name:     opt.textContent.trim(),
                            group:    groupName,
                            badge:    opt.dataset.badge || null,
                            selected: opt.selected,
                        });
                        if (opt.selected && opt.value !== '') {
                            placeholderText = opt.textContent.trim();
                        }
                    }
                });
            } else if (child.tagName.toLowerCase() === 'option') {
                const isPlaceholder = child.value === '';
                if (isPlaceholder && child.textContent.trim()) {
                    placeholderText = child.textContent.trim();
                }
                optionsData.push({
                    id:       child.value,
                    name:     child.textContent.trim(),
                    group:    '',
                    badge:    child.dataset.badge || null,
                    selected: child.selected,
                });
            }
        });

        // Cari item terpilih awal
        const selectedOpt = optionsData.find(o => o.selected && o.id !== '');
        const initialText = selectedOpt ? selectedOpt.name : placeholderText;
        const isInitialPlaceholder = !selectedOpt;

        // Buat wrapper & trigger
        const wrapper = document.createElement('div');
        wrapper.className = 'sd-wrapper';

        const isLg = selectEl.classList.contains('form-select-lg') || selectEl.classList.contains('sd-lg');
        const hasEnterNav = selectEl.classList.contains('enter-nav');

        const trigger = document.createElement('div');
        trigger.className = ('sd-trigger' + (isLg ? ' sd-lg' : '') + (hasEnterNav ? ' enter-nav' : '')).trim();
        trigger.tabIndex = 0;
        trigger.setAttribute('role', 'combobox');
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');
        // [Fix #2] Simpan referensi ke <select> asli agar keyboard-nav.js bisa resolve
        trigger._sdSelect = selectEl;

        const valueSpan = document.createElement('span');
        valueSpan.className = ('sd-value' + (isInitialPlaceholder ? ' sd-placeholder' : '')).trim();
        valueSpan.textContent = initialText;

        const arrowIcon = document.createElement('i');
        arrowIcon.className = 'bi bi-chevron-down sd-arrow';

        trigger.appendChild(valueSpan);
        trigger.appendChild(arrowIcon);

        // Buat dropdown
        const dropdown = document.createElement('div');
        dropdown.className = 'sd-dropdown';
        dropdown.setAttribute('role', 'listbox');
        dropdown._sdTrigger = trigger;
        // [Fix #2] Simpan ref ke select asli
        dropdown._sdSelect  = selectEl;

        const searchWrap = document.createElement('div');
        searchWrap.className = 'sd-search-wrap';

        const searchInput = document.createElement('input');
        searchInput.type = 'text';
        searchInput.className = 'sd-search';
        searchInput.placeholder = 'Ketik untuk mencari...';
        searchInput.autocomplete = 'off';
        searchInput.tabIndex = -1;

        searchWrap.appendChild(searchInput);

        const listEl = document.createElement('div');
        listEl.className = 'sd-list';

        dropdown.appendChild(searchWrap);
        dropdown.appendChild(listEl);

        // [Fix #2] Simpan ref di wrapper
        wrapper._sdSelect = selectEl;

        // Sisipkan UI ke DOM
        wrapper.appendChild(trigger);
        selectEl.parentNode.insertBefore(wrapper, selectEl.nextSibling);
        document.body.appendChild(dropdown);

        // [Fix #4] AbortController untuk cleanup scroll/resize saat elemen dihapus
        const abortCtrl = new AbortController();
        const abortSignal = abortCtrl.signal;

        let focusedIdx = -1;

        function renderList(query) {
            const q = (query || '').toLowerCase().trim();
            listEl.innerHTML = '';
            focusedIdx = -1;

            const hasGroup = optionsData.some(d => d.group);

            if (!hasGroup) {
                const filtered = optionsData.filter(d => !q || d.name.toLowerCase().includes(q));
                if (!filtered.length) {
                    listEl.innerHTML = `<div class="sd-empty"><i class="bi bi-search me-1"></i>Tidak ditemukan</div>`;
                    return;
                }
                filtered.forEach((item, i) => {
                    if (item.id === '') return; // lewati opsi placeholder kosong di daftar
                    listEl.appendChild(buildOption(item, i));
                });
            } else {
                const groups = {};
                optionsData.forEach(d => {
                    if (d.id === '') return;
                    if (q && !d.name.toLowerCase().includes(q)) return;
                    const g = d.group || 'Lainnya';
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
            if (selectEl.value === item.id) opt.classList.add('selected');
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

        function selectItem(id, name) {
            valueSpan.textContent = name;
            valueSpan.classList.remove('sd-placeholder');
            closeDropdown();

            // Update nilai elemen <select> asli
            selectEl.value = id;

            // Trigger event change & input native agar listener di luar (onchange / HTMX) jalan
            selectEl.dispatchEvent(new Event('change', { bubbles: true }));
            selectEl.dispatchEvent(new Event('input', { bubbles: true }));
            if (typeof selectEl.onchange === 'function') {
                selectEl.onchange();
            }

            // Pindah fokus navigasi keyboard
            if (typeof window.moveNextNavElement === 'function') {
                setTimeout(() => window.moveNextNavElement(trigger), 50);
            }
        }

        function positionDropdown() {
            const rect = trigger.getBoundingClientRect();
            const spaceBelow = window.innerHeight - rect.bottom;
            const dropH = Math.min(300, dropdown.scrollHeight || 300);
            const goUp = spaceBelow < dropH + 8 && rect.top > dropH + 8;

            dropdown.style.width = rect.width + 'px';
            // [Fix #5] Tambahkan window.scrollX agar posisi akurat di halaman yang bisa di-scroll horizontal
            dropdown.style.left  = (rect.left + window.scrollX) + 'px';

            if (goUp) {
                dropdown.style.top    = '';
                // Posisi bottom: jarak dari atas trigger ke bawah viewport
                dropdown.style.bottom = (window.innerHeight - rect.top + 4) + 'px';
            } else {
                // [Fix #5] Tambahkan window.scrollY agar akurat di halaman yang bisa di-scroll vertikal
                dropdown.style.top    = (rect.bottom + window.scrollY + 4) + 'px';
                dropdown.style.bottom = '';
            }
        }

        function openDropdown() {
            // Tutup semua dropdown lain secara konsisten
            document.querySelectorAll('.sd-dropdown.open').forEach(function(d) {
                if (d !== dropdown) {
                    d.classList.remove('open');
                    const t = d._sdTrigger;
                    if (t) { t.classList.remove('open'); t.setAttribute('aria-expanded', 'false'); }
                }
            });

            dropdown.classList.add('open');
            trigger.classList.add('open');
            trigger.setAttribute('aria-expanded', 'true');
            searchInput.value = '';
            renderList('');
            positionDropdown();
            setTimeout(() => searchInput.focus(), 50);
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

        trigger.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdown.classList.contains('open') ? closeDropdown() : openDropdown();
        });
        trigger.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
                e.preventDefault();
                openDropdown();
            }
        });
        searchInput.addEventListener('input', function() { renderList(searchInput.value); });
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowDown')       { e.preventDefault(); moveFocus(1); }
            else if (e.key === 'ArrowUp')    { e.preventDefault(); moveFocus(-1); }
            else if (e.key === 'Enter') {
                e.preventDefault();
                const focused = listEl.querySelector('.sd-option.focused');
                if (focused) selectItem(focused.dataset.id, focused.dataset.name);
            }
            else if (e.key === 'Escape')     { e.stopPropagation(); closeDropdown(); trigger.focus(); }
        });

        // [Fix #1] Mousedown global sudah ditangani di luar fungsi ini (level IIFE)
        // Tidak perlu lagi pasang listener per-select di sini.

        // [Fix #4] Scroll & resize menggunakan AbortController agar bisa dibersihkan
        window.addEventListener('scroll', function() {
            if (dropdown.classList.contains('open')) positionDropdown();
        }, { capture: true, passive: true, signal: abortSignal });
        window.addEventListener('resize', function() {
            if (dropdown.classList.contains('open')) positionDropdown();
        }, { signal: abortSignal });

        // [Fix #4] Cleanup listener scroll/resize saat wrapper dihapus dari DOM
        const cleanupObserver = new MutationObserver(function() {
            if (!document.body.contains(wrapper)) {
                abortCtrl.abort();        // Hentikan scroll & resize listener
                dropdown.remove();        // Pastikan dropdown juga dihapus dari body
                cleanupObserver.disconnect();
            }
        });
        cleanupObserver.observe(document.body, { childList: true, subtree: true });

        // [Fix #6] Lazy Rendering: renderList('') tidak lagi dipanggil di sini.
        // Opsi-opsi baru akan dirender ke dalam DOM (listEl) saat openDropdown() dipanggil.
    }

    /**
     * Inisialisasi semua <select class="searchable-select"> di kontainer.
     */
    window.initSearchableSelects = function(container) {
        cleanupOrphanDropdowns();
        const root = container || document;
        const selects = root.querySelectorAll('select.searchable-select, select[data-searchable="true"]');
        selects.forEach(transformSelect);
    };

    document.addEventListener('DOMContentLoaded', function() { window.initSearchableSelects(); });
    if (document.body) {
        document.body.addEventListener('htmx:afterSwap', function() { window.initSearchableSelects(); });
    }

})();
