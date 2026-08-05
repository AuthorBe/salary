/**
 * Global Keyboard Navigation for Forms
 * - Enter: Move to the next input with class .enter-nav
 * - Spasi: Open dropdown (Select2 & Custom Searchable Dropdown)
 * - Shift+Enter or Backspace (if empty): Move to the previous input
 * - Ctrl+Enter: Submit the form
 * - Ctrl+Delete: Delete the current row (if it has a .btn-remove-row button)
 * - Auto Add Row: If Enter is pressed on the last .enter-nav element and the form has data-add-row-btn, click it.
 *
 * Custom Searchable Dropdown (.sd-*) integration:
 * - getVisibleNavElements() mengembalikan .sd-trigger sebagai pengganti <select hidden>
 * - getCurrentNavElement() me-resolve .sd-trigger, .sd-search, .select2 ke elemen nav
 * - focusElement() sadar .sd-trigger dan memberi focus ke trigger langsung
 * - closeAllSdDropdowns() dipanggil saat navigasi mundur / pindah elemen
 */

let globalTargetForm = null;

document.addEventListener('DOMContentLoaded', function() {
    
    window.initKeyboardNavUI = () => {
        // Auto-inject Keyboard Shortcuts Info Card if the page uses it
        const generateInfoHtml = (hasAddRow) => {
            let enterText = hasAddRow ? 'Maju &amp; Tambah Baris' : 'Maju';
            let deleteHtml = hasAddRow ? `<kbd class="bg-white text-dark border shadow-sm px-2 py-1">Ctrl+Del</kbd> Hapus Baris &nbsp;&bull;&nbsp;` : '';
            
            return `
                <div class="alert bg-light border-0 d-none d-md-flex align-items-center mb-4 py-2 px-3 shadow-sm keyboard-nav-info" style="border-left: 4px solid #6366f1 !important; border-radius: 0.5rem;">
                    <i class="bi bi-keyboard-fill fs-2 me-3" style="color: #6366f1;"></i>
                    <div class="small">
                        <strong class="text-dark"><i class="bi bi-lightning-charge-fill text-warning"></i> Mode Input Cepat Aktif</strong><br>
                        <span class="text-muted d-inline-block mt-1" style="font-size: 0.85rem; line-height: 1.8;">
                            <kbd class="bg-white text-dark border shadow-sm px-2 py-1">Enter</kbd> ${enterText} &nbsp;&bull;&nbsp;
                            <kbd class="bg-white text-dark border shadow-sm px-2 py-1">&rarr;</kbd> Maju &nbsp;&bull;&nbsp;
                            <kbd class="bg-white text-dark border shadow-sm px-2 py-1">&larr;</kbd> Mundur &nbsp;&bull;&nbsp;
                            <kbd class="bg-white text-dark border shadow-sm px-2 py-1">Spasi</kbd> Buka Dropdown &nbsp;&bull;&nbsp;
                            <kbd class="bg-white text-dark border shadow-sm px-2 py-1">Shift+Enter</kbd> atau <kbd class="bg-white text-dark border shadow-sm px-2 py-1">Backspace</kbd> Mundur &nbsp;&bull;&nbsp;
                            ${deleteHtml}
                            <kbd class="bg-white text-dark border shadow-sm px-2 py-1">Ctrl+Enter</kbd> Simpan Semua
                        </span>
                    </div>
                </div>
            `;
        };

        const formsWithAddRow = document.querySelectorAll('form[data-add-row-btn]');
        if (formsWithAddRow.length > 0) {
            formsWithAddRow.forEach(f => {
                if (!f.querySelector('.keyboard-nav-info')) {
                    let container = f.querySelector('.modal-body') || f.querySelector('.card-body') || f;
                    container.insertAdjacentHTML('afterbegin', generateInfoHtml(true));
                }
                if (!globalTargetForm) globalTargetForm = f;
            });
        } else {
            const navEls = document.querySelectorAll('.enter-nav');
            navEls.forEach(navEl => {
                const form = navEl.closest('form');
                if (form && !form.querySelector('.keyboard-nav-info')) {
                    let container = form.querySelector('.modal-body') || form.querySelector('.card-body') || form;
                    container.insertAdjacentHTML('afterbegin', generateInfoHtml(false));
                    if (!globalTargetForm) globalTargetForm = form;
                }
            });
        }
    };

    window.initKeyboardNavUI();
    document.body.addEventListener('htmx:afterSwap', window.initKeyboardNavUI);

    // Helper to reliably find the original <select> element from a Select2 container
    const getSelectFromContainer = (container) => {
        if (!container || !window.jQuery) return null;
        const $container = window.jQuery(container);
        let $select = $container.prev('select');
        if (!$select.length) {
            $select = $container.prevAll('select').first();
        }
        if (!$select.length) {
            $select = $container.parent().find('select.select2-hidden-accessible').first();
        }
        return $select.length ? $select : null;
    };

    // Helper function to focus an element safely (including Select2 & Custom Dropdown)
    const focusElement = (el) => {
        if (!el) return;
        if (el.classList.contains('select2-hidden-accessible') && window.jQuery) {
            const $next = window.jQuery(el).nextAll('.select2-container').first();
            if ($next.length) {
                $next.find('.select2-selection').focus();
            }
        } else if (el.classList.contains('sd-trigger')) {
            el.focus();
            if (window.getSelection) { window.getSelection().removeAllRanges(); }
        } else {
            el.focus();
            if (el.tagName.toLowerCase() === 'input' && (el.type === 'text' || el.type === 'number')) {
                el.select();
            }
        }
    };

    // Helper to get visible nav elements
    // Menangani: input/textarea biasa, Select2 (hidden-accessible), Custom SD (.sd-trigger)
    // [Fix Bug #1] <select class="searchable-select enter-nav"> disembunyikan dengan display:none
    // sehingga offsetWidth/Height = 0. Solusi: kembalikan .sd-trigger-nya sebagai pengganti.
    const getVisibleNavElements = (form) => {
        const result = [];
        form.querySelectorAll('.enter-nav').forEach(el => {
            if (el.disabled || el.readOnly) return;

            // Kasus 1: <select> yang sudah di-transform oleh searchable-select.js
            // → kembalikan .sd-trigger sebagai representasinya (yang benar-benar visible)
            if (el.tagName.toLowerCase() === 'select' && el.dataset.searchableInit === 'true') {
                const wrapper = el.nextSibling;
                if (wrapper && wrapper.classList && wrapper.classList.contains('sd-wrapper')) {
                    const sdTrigger = wrapper.querySelector('.sd-trigger');
                    if (sdTrigger && sdTrigger.offsetWidth > 0) {
                        result.push(sdTrigger);
                        return;
                    }
                }
                // Fallback: jika trigger tidak ditemukan, skip
                return;
            }

            // Kasus 2: Select2 (hidden-accessible tapi tetap dianggap visible)
            if (el.classList.contains('select2-hidden-accessible')) {
                result.push(el);
                return;
            }

            // Kasus 3: Elemen biasa — cek visibility lewat offsetWidth/Height
            if (el.offsetWidth > 0 && el.offsetHeight > 0) {
                result.push(el);
            }
        });
        return result;
    };

    // Helper to get current nav element from active element
    // Me-resolve elemen aktif (bisa di dalam dropdown) ke elemen .enter-nav yang sesuai
    const getCurrentNavElement = (activeElement) => {
        if (!activeElement) return null;

        // Kasus: fokus sudah di .enter-nav langsung (input, textarea, atau .sd-trigger)
        if (activeElement.classList.contains('enter-nav')) {
            // Jika ini sd-trigger, kembalikan langsung (sudah masuk list nav)
            return activeElement;
        }

        // Kasus: fokus di dalam select2
        if (activeElement.closest('.select2-selection')) {
            const container = activeElement.closest('.select2-container');
            const $select = getSelectFromContainer(container);
            if ($select && $select[0].classList.contains('enter-nav')) {
                return $select[0];
            }
        } else if (activeElement.classList.contains('select2-search__field')) {
            const container = document.querySelector('.select2-container--open');
            if (container) {
                const $select = getSelectFromContainer(container);
                if ($select && $select[0].classList.contains('enter-nav')) {
                    return $select[0];
                }
            }
        }
        // Kasus: fokus di sd-search input → resolve ke sd-trigger milik dropdown
        else if (activeElement.classList.contains('sd-search')) {
            const dropdown = activeElement.closest('.sd-dropdown');
            if (dropdown && dropdown._sdTrigger) {
                const sdTrigger = dropdown._sdTrigger;
                // sd-trigger harus punya class enter-nav untuk ikut navigasi
                if (sdTrigger.classList.contains('enter-nav')) {
                    return sdTrigger;
                }
            }
        }
        // Kasus: fokus di sd-trigger sendiri (tapi tidak punya enter-nav — rare case)
        else if (activeElement.classList.contains('sd-trigger') && !activeElement.classList.contains('enter-nav')) {
            return activeElement;
        }

        return null;
    };

    // Helper untuk maju ke elemen .enter-nav berikutnya dari elemen asal
    // fromEl bisa berupa: input, .sd-trigger, select2 element, dll.
    const moveNextNavElement = (fromEl) => {
        if (!fromEl) return;
        const form = fromEl.closest('form') || globalTargetForm || document.querySelector('form');
        if (!form) return;
        const navElements = getVisibleNavElements(form);
        const currentIndex = navElements.indexOf(fromEl);
        if (currentIndex > -1) {
            if (currentIndex < navElements.length - 1) {
                focusElement(navElements[currentIndex + 1]);
            } else {
                const addRowBtnSelector = form.getAttribute('data-add-row-btn');
                if (addRowBtnSelector) {
                    const addRowBtn = form.querySelector(addRowBtnSelector);
                    if (addRowBtn) {
                        addRowBtn.click();
                        setTimeout(() => {
                            const newNavElements = getVisibleNavElements(form);
                            if (newNavElements.length > navElements.length) {
                                focusElement(newNavElements[currentIndex + 1]);
                            }
                        }, 80); // Sedikit lebih lama agar sd-trigger sudah di-init
                    }
                }
            }
        }
    };

    // Export helpers ke window
    window.focusElement = focusElement;
    window.getVisibleNavElements = getVisibleNavElements;
    window.getCurrentNavElement = getCurrentNavElement;
    window.moveNextNavElement = moveNextNavElement;
    
    // Listen to keydown on the document
    document.addEventListener('keydown', function(e) {
        
        // Auto-focus ke input pertama jika Enter atau Spasi ditekan saat kursor sedang kosong (di luar form)
        if ((e.key === 'Enter' || e.key === ' ') && !e.shiftKey && !e.ctrlKey && !e.altKey) {
            const activeElement = document.activeElement;
            const isInputMode = activeElement && (
                activeElement.tagName.toLowerCase() === 'input' || 
                activeElement.tagName.toLowerCase() === 'select' || 
                activeElement.tagName.toLowerCase() === 'textarea' || 
                activeElement.tagName.toLowerCase() === 'button' ||
                activeElement.classList.contains('select2-selection') ||
                activeElement.classList.contains('select2-search__field') ||
                activeElement.classList.contains('sd-trigger') ||
                activeElement.classList.contains('sd-search')
            );
            
            if (!isInputMode) {
                // Cari elemen nav visible pertama — termasuk .sd-trigger
                const navElements = Array.from(document.querySelectorAll('.enter-nav')).filter(el => {
                    if (el.tagName.toLowerCase() === 'select' && el.dataset.searchableInit === 'true') {
                        // Untuk searchable-select, cek sd-trigger-nya
                        const wrapper = el.nextSibling;
                        if (wrapper && wrapper.classList && wrapper.classList.contains('sd-wrapper')) {
                            const sdTrigger = wrapper.querySelector('.sd-trigger');
                            return sdTrigger && sdTrigger.offsetWidth > 0;
                        }
                        return false;
                    }
                    return el.offsetWidth > 0 && el.offsetHeight > 0;
                });
                if (navElements.length > 0) {
                    e.preventDefault();
                    // Resolve ke sd-trigger jika perlu
                    const firstEl = navElements[0];
                    if (firstEl.tagName.toLowerCase() === 'select' && firstEl.dataset.searchableInit === 'true') {
                        const wrapper = firstEl.nextSibling;
                        const sdTrigger = wrapper && wrapper.querySelector('.sd-trigger');
                        focusElement(sdTrigger || firstEl);
                    } else {
                        focusElement(firstEl);
                    }
                    return;
                }
            }
        }
        
        // Handle Ctrl + Enter for Submitting Form
        if (e.ctrlKey && e.key === 'Enter') {
            const activeElement = document.activeElement;
            if (activeElement) {
                let form = activeElement.closest('form');
                
                if (!form && (activeElement.classList.contains('select2-search__field') || activeElement.classList.contains('sd-search'))) {
                    form = globalTargetForm || document.querySelector('form');
                }
                
                if (form) {
                    e.preventDefault();
                    
                    if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
                        form.reportValidity();
                        return;
                    }
                    
                    const submitBtn = form.querySelector('button[type="submit"], button.btn-save, input[type="submit"]');
                    if (submitBtn) {
                        submitBtn.click();
                    } else {
                        form.submit();
                    }
                    return;
                }
            }
        }
        
        // Handle Ctrl + Delete for Removing the Current Row
        if (e.ctrlKey && e.key === 'Delete') {
            const activeElement = document.activeElement;
            if (activeElement) {
                const navElement = getCurrentNavElement(activeElement);
                if (navElement) {
                    const row = navElement.closest('tr, .row, .item-row, .bulanan-row');
                    if (row) {
                        const removeBtn = row.querySelector('.btn-remove-row, .remove-row-btn, .btn-remove-bulanan');
                        if (removeBtn) {
                            e.preventDefault();
                            
                            const form = row.closest('form');
                            if (form) {
                                const navElements = getVisibleNavElements(form);
                                const currentIndex = navElements.indexOf(navElement);
                                if (currentIndex > 0) {
                                    focusElement(navElements[currentIndex - 1]);
                                }
                            }
                            
                            removeBtn.click();
                            return;
                        }
                    }
                }
            }
        }
        
        // Handle Space to open dropdown (Select2, Custom Dropdown, or standard select)
        if (e.key === ' ' && !e.ctrlKey && !e.shiftKey && !e.altKey) {
            const activeElement = document.activeElement;
            if (activeElement && activeElement.tagName.toLowerCase() !== 'input' && activeElement.tagName.toLowerCase() !== 'textarea') {
                const isNativeSelect = activeElement.tagName.toLowerCase() === 'select';
                const isSelect2Container = activeElement.closest('.select2-selection');
                const isSdTrigger = activeElement.classList.contains('sd-trigger');
                
                if (isSdTrigger) {
                    e.preventDefault();
                    e.stopPropagation();
                    activeElement.click();
                    return;
                }
                
                const isNativeButNotSelect2 = isNativeSelect && !activeElement.classList.contains('select2-hidden-accessible');
                if (isNativeButNotSelect2) {
                    return;
                }
                
                if (isNativeSelect || isSelect2Container) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    if (window.jQuery) {
                        if (isSelect2Container) {
                            setTimeout(() => {
                                const $container = window.jQuery(isSelect2Container);
                                const mouseDown = window.jQuery.Event('mousedown', { which: 1 });
                                $container.trigger(mouseDown);
                            }, 10);
                        } else if (isNativeSelect) {
                            if (activeElement.classList.contains('select2-hidden-accessible')) {
                                setTimeout(() => {
                                    window.jQuery(activeElement).select2('open');
                                }, 10);
                            }
                        }
                    }
                }
            }
        }
        
        // Handle Backspace or Shift + Enter for reverse navigation
        if ((e.key === 'Backspace' || (e.key === 'Enter' && e.shiftKey)) && !e.ctrlKey && !e.altKey) {
            const activeElement = document.activeElement;
            if (activeElement) {
                if (activeElement.classList.contains('select2-search__field') || activeElement.classList.contains('sd-search')) {
                    if (e.key === 'Backspace' && activeElement.value !== '') {
                        return; // Biarkan Backspace menghapus karakter di pencarian
                    }
                }
                
                const navElement = getCurrentNavElement(activeElement);
                if (navElement) {
                    const isSelect = navElement.tagName.toLowerCase() === 'select';
                    const isCheckbox = activeElement.type === 'checkbox' || activeElement.type === 'radio';
                    const isEmpty = isCheckbox || (('value' in activeElement) ? (activeElement.value === '' || activeElement.value === '0') : true);
                    const isSpan = activeElement.tagName.toLowerCase() === 'span' || activeElement.closest('.select2-selection') || activeElement.classList.contains('sd-trigger');
                    
                    if (e.key === 'Enter' || isSelect || (isEmpty && !isSpan) || (e.key === 'Backspace' && isSpan) || activeElement.classList.contains('sd-search')) {
                        if (e.key === 'Enter') e.preventDefault();
                        
                        const form = navElement.closest('form') || globalTargetForm || document.querySelector('form');
                        if (form) {
                            const navElements = getVisibleNavElements(form);
                            const currentIndex = navElements.indexOf(navElement);
                            
                            if (currentIndex > 0) {
                                e.preventDefault();
                                // [Fix] Tutup custom dropdown via public API terpusat
                                if (typeof window.closeAllSdDropdowns === 'function') {
                                    window.closeAllSdDropdowns();
                                }
                                focusElement(navElements[currentIndex - 1]);
                            }
                        }
                    }
                }
            }
        }
        
        // Handle standalone Enter key (without Shift/Ctrl/Alt)
        if (e.key === 'Enter' && !e.shiftKey && !e.ctrlKey && !e.altKey) {
            const activeElement = document.activeElement;
            if (!activeElement) return;
            
            // Jika sedang berada di input pencarian custom dropdown, biarkan dropdown me-handle Enter (memilih opsi)
            if (activeElement.classList.contains('sd-search')) {
                return;
            }
            
            const isTextarea = activeElement.tagName.toLowerCase() === 'textarea';
            const isButton = activeElement.tagName.toLowerCase() === 'button' || activeElement.type === 'button' || activeElement.type === 'submit';
            
            if (isTextarea || isButton) {
                return;
            }
            
            const form = activeElement.closest('form');
            if (form) {
                e.preventDefault(); 
                
                const navElement = getCurrentNavElement(activeElement);
                if (navElement) {
                    moveNextNavElement(navElement);
                }
            }
        }

        // ── Handle ArrowRight / ArrowLeft untuk navigasi antar field ──────────────────
        // ArrowRight → maju ke field berikutnya (seperti Enter, tapi hanya di boundary)
        // ArrowLeft  → mundur ke field sebelumnya (seperti Shift+Enter, tapi hanya di boundary)
        if ((e.key === 'ArrowRight' || e.key === 'ArrowLeft') && !e.ctrlKey && !e.altKey && !e.shiftKey) {
            const activeElement = document.activeElement;
            if (!activeElement) return;

            const isSdTrigger   = activeElement.classList.contains('sd-trigger');
            const isSdSearch    = activeElement.classList.contains('sd-search');
            const isSelect2     = !!(activeElement.closest('.select2-selection') || activeElement.classList.contains('select2-search__field'));
            const isInput       = activeElement.tagName.toLowerCase() === 'input' && !isSdSearch;
            const isSelect      = activeElement.tagName.toLowerCase() === 'select';

            let shouldIntercept = false;

            if (isSdTrigger || isSelect2 || isSelect) {
                // Dropdown/select: selalu intercept (tidak ada cursor teks)
                shouldIntercept = true;
            } else if (isSdSearch) {
                // Di dalam search input: intercept hanya jika search kosong
                shouldIntercept = activeElement.value === '';
            } else if (isInput) {
                // Input teks/angka: intercept hanya di boundary cursor
                const len      = activeElement.value.length;
                const selStart = activeElement.selectionStart;
                const selEnd   = activeElement.selectionEnd;
                if (e.key === 'ArrowRight' && selStart === len && selEnd === len) {
                    shouldIntercept = true;   // Cursor di ujung kanan → maju
                } else if (e.key === 'ArrowLeft' && selStart === 0 && selEnd === 0) {
                    shouldIntercept = true;   // Cursor di ujung kiri → mundur
                }
            }

            if (!shouldIntercept) return;

            const navElement = getCurrentNavElement(activeElement);
            if (!navElement) return;

            const form = navElement.closest('form') || globalTargetForm || document.querySelector('form');
            if (!form) return;

            const navElements  = getVisibleNavElements(form);
            const currentIndex = navElements.indexOf(navElement);
            if (currentIndex === -1) return;

            if (e.key === 'ArrowRight' && currentIndex < navElements.length - 1) {
                e.preventDefault();
                if (typeof window.closeAllSdDropdowns === 'function') window.closeAllSdDropdowns();
                focusElement(navElements[currentIndex + 1]);
            } else if (e.key === 'ArrowLeft' && currentIndex > 0) {
                e.preventDefault();
                if (typeof window.closeAllSdDropdowns === 'function') window.closeAllSdDropdowns();
                focusElement(navElements[currentIndex - 1]);
            }
        }
    });

});

