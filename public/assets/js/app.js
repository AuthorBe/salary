/**
 * app.js — Salary Global JavaScript
 * Minimal JS: sidebar toggle, password visibility, confirm dialogs, login state.
 *
 * HTMX handle sebagian besar interaksi dinamis (Fase 2+).
 * File ini hanya untuk UI feedback yang tidak butuh server round-trip.
 */

'use strict';

// ── Service Worker Registration (PWA) ────────────────────────────────────
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    // Gunakan root-relative path karena sw.js ada di root (public/sw.js)
    // Di VHost ini akan menjadi /sw.js, namun di subdirektori kita harus pastikan
    // path base-nya dinamis. Karena app.js dimuat relatif terhadap document base,
    // kita gunakan getAppBasePath() dari backend (tapi karena ini static JS, kita ambil
    // dari href manifest atau path window.location).
    const swUrl = document.querySelector('link[rel="manifest"]')?.href.replace('manifest.json', 'sw.js') || '/sw.js';
    navigator.serviceWorker.register(swUrl)
      .then(registration => {
        console.log('ServiceWorker registration successful with scope: ', registration.scope);
      })
      .catch(err => {
        console.log('ServiceWorker registration failed: ', err);
      });
  });
}

document.addEventListener('DOMContentLoaded', () => {

  // ── Sidebar Toggle (Mobile) ─────────────────────────────────────────────
  const sidebar        = document.getElementById('sidebar');
  const sidebarOverlay = document.getElementById('sidebarOverlay');
  const menuToggle     = document.getElementById('menuToggle');
  const closeSidebarBtn = document.getElementById('closeSidebarBtn');

  function openSidebar() {
    if (!sidebar) return;
    sidebar.classList.add('is-open');
    sidebarOverlay?.classList.add('is-visible');
    document.body.style.overflow = 'hidden'; // Cegah scroll background
  }

  function closeSidebar() {
    if (!sidebar) return;
    sidebar.classList.remove('is-open');
    sidebarOverlay?.classList.remove('is-visible');
    document.body.style.overflow = '';
  }

  menuToggle?.addEventListener('click', () => {
    sidebar?.classList.contains('is-open') ? closeSidebar() : openSidebar();
  });

  closeSidebarBtn?.addEventListener('click', closeSidebar);

  // Tutup sidebar saat overlay diklik
  sidebarOverlay?.addEventListener('click', closeSidebar);



  // Tutup sidebar saat Escape ditekan
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeSidebar();
  });

  // ── Password Toggle (Event Delegation - Bulletproof) ─────────────────────
  document.addEventListener('click', (e) => {
    const toggleBtn = e.target.closest('#togglePassword, .input-toggle-password');
    if (!toggleBtn) return;

    e.preventDefault();
    e.stopPropagation();

    const passwordInput = document.getElementById('password') || toggleBtn.parentElement?.querySelector('input');
    const toggleIcon    = toggleBtn.querySelector('i') || document.getElementById('togglePasswordIcon');

    if (passwordInput) {
      const isHidden = passwordInput.type === 'password';
      passwordInput.type = isHidden ? 'text' : 'password';
      if (toggleIcon) {
        toggleIcon.className = isHidden ? 'bi bi-eye-slash-fill' : 'bi bi-eye-fill';
      }
      passwordInput.focus();
    }
  });

  // ── Salary Custom Confirm Dialog Modal ──────────────────────────────────
  function showCustomConfirm(message, onConfirm) {
    let backdrop = document.getElementById('salaryConfirmModal');
    if (!backdrop) {
      backdrop = document.createElement('div');
      backdrop.id = 'salaryConfirmModal';
      backdrop.className = 'salary-modal-backdrop';
      backdrop.innerHTML = `
        <div class="salary-modal-box">
          <div class="salary-modal-icon warning" id="salaryModalIcon">
            <i class="bi bi-exclamation-triangle-fill"></i>
          </div>
          <h4 class="salary-modal-title" id="salaryModalTitle">Konfirmasi Tindakan</h4>
          <p class="salary-modal-message" id="salaryModalMessage"></p>
          <div class="salary-modal-actions">
            <button type="button" class="btn btn-light px-4 rounded-pill fw-medium" id="salaryModalCancelBtn">Batal</button>
            <button type="button" class="btn btn-danger px-4 rounded-pill fw-semibold shadow-sm" id="salaryModalConfirmBtn">Ya, Lanjutkan</button>
          </div>
        </div>
      `;
      document.body.appendChild(backdrop);
    }

    const messageEl  = document.getElementById('salaryModalMessage');
    const cancelBtn   = document.getElementById('salaryModalCancelBtn');
    const confirmBtn  = document.getElementById('salaryModalConfirmBtn');

    if (messageEl) messageEl.textContent = message;

    backdrop.classList.add('is-visible');

    const closeModal = () => {
      backdrop.classList.remove('is-visible');
    };

    const handleConfirm = () => {
      closeModal();
      onConfirm();
    };

    cancelBtn.onclick = closeModal;
    confirmBtn.onclick = handleConfirm;

    backdrop.onclick = (e) => {
      if (e.target === backdrop) closeModal();
    };
  }

  // Handle Global Keyboard Shortcuts for Modals (Enter to Confirm, Backspace/Esc to Cancel)
  document.addEventListener('keydown', (e) => {
    // 1. Handle Custom Confirm Modal
    const confirmModal = document.getElementById('salaryConfirmModal');
    if (confirmModal && confirmModal.classList.contains('is-visible')) {
      if (e.key === 'Enter') {
        e.preventDefault();
        e.stopPropagation();
        const btn = document.getElementById('salaryModalConfirmBtn');
        if (btn) btn.click();
        return;
      }
      if (e.key === 'Backspace' || e.key === 'Escape') {
        // Prevent backspace from navigating back in browser
        e.preventDefault();
        e.stopPropagation();
        const btn = document.getElementById('salaryModalCancelBtn');
        if (btn) btn.click();
        return;
      }
    }

    // 2. Handle Success Overlays (any overlay ending with -success-overlay)
    const successOverlays = document.querySelectorAll('[id$="-success-overlay"]');
    for (const overlay of successOverlays) {
      if (overlay) {
        if (e.key === 'Enter' || e.key === 'Backspace' || e.key === 'Escape') {
          e.preventDefault();
          e.stopPropagation();
          overlay.remove();
          return;
        }
      }
    }

    // 3. Handle Static Modals (Server-rendered like Riwayat Kasbon)
    const staticModal = document.querySelector('.modal.show[style*="display: block"]');
    if (staticModal) {
      // Check if focus is inside an input, if so, allow backspace to delete text
      if (e.key === 'Backspace') {
        const activeTag = document.activeElement ? document.activeElement.tagName.toLowerCase() : '';
        if (activeTag === 'input' || activeTag === 'textarea') {
           return; // Allow typing backspace
        }
      }

      if (e.key === 'Backspace' || e.key === 'Escape') {
        e.preventDefault();
        e.stopPropagation();
        const closeBtn = staticModal.querySelector('.btn-close, [data-bs-dismiss="modal"]');
        if (closeBtn) {
          closeBtn.click();
        }
        return;
      }
    }
  });

  // Intercept data-confirm untuk link dan form submit biasa
  document.addEventListener('click', (e) => {
    const target = e.target.closest('[data-confirm]');
    if (!target) return;

    e.preventDefault();
    e.stopPropagation();

    const message = target.getAttribute('data-confirm') || 'Apakah Anda yakin ingin melanjutkan?';

    showCustomConfirm(message, () => {
      if (target.tagName === 'A') {
        window.location.href = target.href;
      } else if (target.tagName === 'BUTTON' && target.form) {
        target.form.submit();
      } else if (target.form) {
        target.form.submit();
      }
    });
  });

  // Intercept HTMX native confirm event (hx-confirm)
  document.body?.addEventListener('htmx:confirm', (evt) => {
    const message = evt.detail.question;
    if (!message) return;

    evt.preventDefault();
    showCustomConfirm(message, () => {
      evt.detail.issueRequest(true);
    });
  });

  // ── Login Button & Smart Focus ────────────────────────────────────────
  // Saat form login disubmit, ubah button ke state loading agar tidak double submit.
  // Juga tangani fokus cerdas: tekan enter di username lompat ke password jika kosong.
  const loginForm = document.getElementById('loginForm');
  const loginBtn  = document.getElementById('loginBtn');
  const usernameInput = document.getElementById('nama_pengguna') || document.getElementById('username');
  const passwordInput = document.getElementById('password');

  if (loginForm && usernameInput && passwordInput) {
    // Intercept Enter pada kolom username
    usernameInput.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        if (!usernameInput.value.trim()) {
          e.preventDefault();
          usernameInput.focus();
        } else if (!passwordInput.value) {
          e.preventDefault();
          passwordInput.focus();
        }
      }
    });

    // Intercept Enter pada kolom password
    passwordInput.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        if (!passwordInput.value) {
          e.preventDefault();
          passwordInput.focus();
        } else if (!usernameInput.value.trim()) {
          e.preventDefault();
          usernameInput.focus();
        }
      }
    });

    loginForm.addEventListener('submit', (e) => {
      if (!usernameInput.value.trim()) {
        e.preventDefault();
        usernameInput.focus();
        return;
      }
      if (!passwordInput.value) {
        e.preventDefault();
        passwordInput.focus();
        return;
      }

      if (loginBtn) {
        setTimeout(() => {
          loginBtn.disabled = true;
          loginBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Memproses...';
          loginBtn.style.opacity = '0.8';
        }, 0);
      }
    });
  }

  // ── Auto-dismiss Alerts & URL Clean up ──────────────────────────────────
  // Clean notification query parameters (e.g. ?logged_out=1) from URL so reloading page won't re-trigger alert
  if (window.location.search.includes('logged_out=')) {
    try {
      const url = new URL(window.location.href);
      url.searchParams.delete('logged_out');
      const cleanSearch = url.searchParams.toString();
      const cleanUrl = url.pathname + (cleanSearch ? '?' + cleanSearch : '');
      window.history.replaceState({}, document.title, cleanUrl);
    } catch (e) {
      console.warn('URL parameter cleanup failed:', e);
    }
  }

  // Elemen dengan data-auto-dismiss="3000" akan fade out setelah N millisecond.
  const initAutoDismiss = (container = document) => {
    container.querySelectorAll?.('[data-auto-dismiss]').forEach((el) => {
      const delay = parseInt(el.getAttribute('data-auto-dismiss') || '3000', 10);
      setTimeout(() => {
        el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        el.style.opacity = '0';
        el.style.transform = 'translateY(-8px)';
        setTimeout(() => el.remove(), 500);
      }, delay);
    });
  };
  
  // Init saat pertama kali dimuat
  initAutoDismiss();

  // ── HTMX Global Indicator ──────────────────────────────────────────────
  // Tambahkan class 'htmx-loading' ke body saat ada request HTMX aktif.
  // (Berguna Fase 2+ untuk loading state pada form absensi/produksi)
  document.body.addEventListener('htmx:beforeRequest', () => {
    document.body.classList.add('htmx-loading');
  });
  document.body.addEventListener('htmx:afterRequest', () => {
    document.body.classList.remove('htmx-loading');
  });

  // ── Active Nav Highlight ────────────────────────────────────────────────
  // Double-check active state berdasarkan URL saat ini (fallback jika PHP
  // belum meng-assign class 'active' dari server-side).
  const currentPath = window.location.pathname;
  document.querySelectorAll('.nav-item:not(.disabled)').forEach((item) => {
    const href = item.getAttribute('href');
    if (href && href !== '#' && currentPath.endsWith(href)) {
      item.classList.add('active');
    }
  });

  // ── Auto-Format Rupiah (Event Delegation & HTMX Compatible) ─────────────
  const formatRupiahValue = (val) => {
    const clean = val.replace(/\D/g, '');
    if (clean === '') return '';
    return parseInt(clean, 10).toLocaleString('id-ID');
  };

  // Event delegation pada document untuk semua .input-rupiah (static & dynamic HTMX elements)
  document.addEventListener('input', (e) => {
    if (e.target && e.target.classList && e.target.classList.contains('input-rupiah')) {
      const input = e.target;
      let cursorPosition = input.selectionStart;
      let valueBefore = input.value;
      
      input.value = formatRupiahValue(input.value);
      
      let diff = input.value.length - valueBefore.length;
      input.setSelectionRange(cursorPosition + diff, cursorPosition + diff);
    }
  });

  // Format awal untuk elemen yang sudah ada saat halaman dimuat
  document.querySelectorAll('.input-rupiah').forEach((input) => {
    if (input.value) {
      input.value = formatRupiahValue(input.value);
    }
  });

  // Auto-format setiap kali HTMX selesai menyuntikkan konten baru ke DOM
  document.body?.addEventListener('htmx:afterSettle', (evt) => {
    const target = evt.detail?.target || evt.target || document;
    target.querySelectorAll?.('.input-rupiah').forEach((input) => {
      if (input.value) {
        input.value = formatRupiahValue(input.value);
      }
    });
    
    // Inisialisasi auto-dismiss untuk alert baru
    initAutoDismiss(target);
  });

  // ── Attendance Switch Toggle (Hadir / Tidak Hadir) ──────────────────────────
  document.addEventListener('change', (e) => {
    if (e.target && e.target.classList.contains('attendance-switch')) {
      const empId = e.target.getAttribute('data-emp-id');
      const notesInput = document.getElementById('notes_' + empId);
      if (notesInput) {
        if (e.target.checked) {
          notesInput.disabled = true;
          notesInput.style.opacity = '0.45';
          notesInput.style.backgroundColor = '#f8fafc';
          notesInput.value = ''; // Kosongkan saat hadir
        } else {
          notesInput.disabled = false;
          notesInput.style.opacity = '1';
          notesInput.style.backgroundColor = '#ffffff';
          notesInput.focus();
        }
      }
    }
  });

  // ── Bootstrap Modal Z-Index Fix ─────────────────────────────────────────
  // Pindahkan semua elemen modal ke akhir document.body agar tidak 
  // terperangkap stacking context/z-index dari elemen parent pembungkus.
  document.querySelectorAll('.modal').forEach((modal) => {
    document.body.appendChild(modal);
  });

});
