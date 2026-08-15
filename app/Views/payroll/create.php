<?php
/**
 * @var string $title
 * @var string $type
 * @var string $periode_awal
 * @var string $periode_akhir
 * @var string $default_payroll_name
 * @var string $default_weekly_start
 * @var string $default_weekly_end
 * @var string $default_monthly_start
 * @var string $default_monthly_end
 */
?>

<div class="page-header mb-4 d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3">
    <div>
        <h5 class="page-header-title d-flex align-items-center gap-2 text-dark fw-semibold mb-0" style="font-size: 1.05rem;">
            <i class="bi fs-5 bi-calculator text-primary"></i> Pilih tipe penggajian dan rentang tanggal periode yang akan dihitung.
        </h5>
    </div>
    <a href="<?= url('/payroll') ?>" class="btn btn-outline-secondary d-inline-flex align-items-center rounded-pill px-4">
        <i class="bi bi-arrow-left me-2"></i> Kembali
    </a>
</div>

<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <div><?= e($_SESSION['flash_error']) ?></div>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <form action="<?= url('/payroll/store') ?>" method="POST" id="form_create_payroll">
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="d-flex justify-content-between align-items-center mb-1.5">
                        <label class="form-label fw-semibold text-dark mb-0" for="payroll_name">
                            Judul / Nama Payroll <span class="text-danger">*</span>
                        </label>
                        <button type="button" class="btn btn-link btn-sm text-decoration-none text-primary p-0 d-inline-flex align-items-center gap-1" id="btn_sync_title" onclick="resetToAutoTitle()" style="font-size: 0.8rem;" title="Perbarui judul sesuai rentang tanggal">
                            <i class="bi bi-arrow-repeat"></i> Sinkronkan Judul
                        </button>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-primary border-end-0"><i class="bi bi-card-heading"></i></span>
                        <input type="text" name="name" id="payroll_name" class="form-control border-start-0 ps-1" placeholder="Contoh: Agustus Week 1" value="<?= e($default_payroll_name ?? '') ?>" required autocomplete="off">
                    </div>
                    <div class="form-text text-muted mb-2.5">Berikan nama agar mudah dikenali di riwayat dan laporan finansial.</div>

                    <!-- Area Saran Judul Cepat -->
                    <div class="p-3 rounded-3 bg-light border border-light-subtle mt-1" id="box_saran_judul" style="background-color: #f8fafc !important;">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2.5 pb-2 border-bottom border-light-subtle">
                            <span class="text-secondary small fw-semibold d-inline-flex align-items-center gap-1.5" style="font-size: 0.8rem;">
                                <i class="bi bi-stars text-primary"></i> Saran Judul Singkat:
                            </span>
                            <span class="badge bg-white text-secondary border px-2.5 py-1 shadow-2xs" id="calc_info_badge" style="font-size: 0.72rem; font-weight: 500;"></span>
                        </div>
                        <div class="d-flex flex-wrap align-items-center" id="suggestion_chips" style="gap: 0.55rem 0.65rem;">
                            <!-- Chips saran judul akan dirender otomatis oleh JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
            <?= csrfField() ?>

            <div class="row g-4 mb-4">
                <div class="col-md-12">
                    <label class="form-label fw-semibold">Tipe Penggajian & Rentang Tanggal <span class="text-danger">*</span></label>
                    <div class="form-text text-muted mb-3">
                        Pilih tipe karyawan yang ingin dihitung. Anda dapat menggabungkan Borongan dan Bulanan sekaligus dengan rentang tanggal yang berbeda-beda.
                    </div>

                    <div class="card border mb-3 rounded-3">
                        <div class="card-body">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" role="switch" id="check_borongan" name="types[]" value="weekly" <?= $type === 'weekly' ? 'checked' : '' ?> onchange="toggleDateInputs('borongan', this.checked)">
                                <label class="form-check-label fw-bold text-dark" for="check_borongan">Karyawan Borongan</label>
                            </div>
                            <div class="row g-3" id="date_inputs_borongan" style="<?= $type === 'weekly' ? '' : 'display: none;' ?>">
                                <div class="col-6">
                                    <label class="form-label small fw-medium">Dari Tanggal (Borongan)</label>
                                    <input type="date" class="form-control" name="periode_awal_weekly" id="periode_awal_weekly" value="<?= e($default_weekly_start) ?>">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-medium">Sampai Tanggal (Borongan)</label>
                                    <input type="date" class="form-control" name="periode_akhir_weekly" id="periode_akhir_weekly" value="<?= e($default_weekly_end) ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border rounded-3">
                        <div class="card-body">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" role="switch" id="check_bulanan" name="types[]" value="monthly" <?= $type === 'monthly' ? 'checked' : '' ?> onchange="toggleDateInputs('bulanan', this.checked)">
                                <label class="form-check-label fw-bold text-dark" for="check_bulanan">Karyawan Bulanan</label>
                            </div>
                            <div class="row g-3" id="date_inputs_bulanan" style="<?= $type === 'monthly' ? '' : 'display: none;' ?>">
                                <div class="col-6">
                                    <label class="form-label small fw-medium">Dari Tanggal (Bulanan)</label>
                                    <input type="date" class="form-control" name="periode_awal_monthly" id="periode_awal_monthly" value="<?= e($default_monthly_start) ?>">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-medium">Sampai Tanggal (Bulanan)</label>
                                    <input type="date" class="form-control" name="periode_akhir_monthly" id="periode_akhir_monthly" value="<?= e($default_monthly_end) ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <hr class="my-4">
            
            <div class="d-flex justify-content-end gap-2">
                <a href="<?= url('/payroll') ?>" class="btn btn-light border px-4">Batal</a>
                <button type="submit" class="btn btn-primary px-4 d-inline-flex align-items-center" onclick="return validateCheckboxes()">
                    <i class="bi bi-calculator me-2"></i> Kalkulasi &amp; Generate Draft
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.suggestion-chip {
    font-size: 0.8125rem;
    padding: 0.35rem 0.85rem;
    border-radius: 9999px;
    border: 1px solid #e2e8f0;
    background-color: #ffffff;
    color: #334155;
    cursor: pointer;
    transition: all 0.15s ease-in-out;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    line-height: 1.4;
    user-select: none;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
}
.suggestion-chip:hover {
    background-color: #f1f5f9;
    border-color: #cbd5e1;
    color: #0f172a;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.06);
}
.suggestion-chip.active {
    background-color: #e0f2fe;
    border-color: #0284c7;
    color: #0369a1;
    font-weight: 600;
    box-shadow: 0 1px 3px rgba(2, 132, 199, 0.2);
}
</style>

<script>
const MONTH_NAMES = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
const MONTH_SHORTS = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

let selectedFormatIndex = 0;
let isCustomTyped = false;
let currentSuggestions = [];

function getMajorityMonthInfo(startStr, endStr) {
    if (!startStr || !endStr) {
        const now = new Date();
        const y = now.getFullYear();
        const m = now.getMonth() + 1;
        return { year: y, month: m, monthName: MONTH_NAMES[m], shortMonth: MONTH_SHORTS[m], ym: `${y}-${String(m).padStart(2, '0')}` };
    }
    let [y1, m1, d1] = startStr.split('-').map(Number);
    let [y2, m2, d2] = endStr.split('-').map(Number);
    let cur = new Date(y1, m1 - 1, d1);
    let end = new Date(y2, m2 - 1, d2);
    
    if (cur > end) {
        const temp = cur;
        cur = end;
        end = temp;
    }
    
    const counts = {};
    while (cur <= end) {
        const ym = `${cur.getFullYear()}-${String(cur.getMonth() + 1).padStart(2, '0')}`;
        counts[ym] = (counts[ym] || 0) + 1;
        cur.setDate(cur.getDate() + 1);
    }
    
    let maxCount = -1;
    let bestYm = `${y1}-${String(m1).padStart(2, '0')}`;
    for (const ym in counts) {
        if (counts[ym] > maxCount) {
            maxCount = counts[ym];
            bestYm = ym;
        }
    }
    
    const [resY, resM] = bestYm.split('-').map(Number);
    return {
        year: resY,
        month: resM,
        monthName: MONTH_NAMES[resM],
        shortMonth: MONTH_SHORTS[resM],
        ym: bestYm
    };
}

function getWeekOfMonth(startStr, endStr, majorityInfo) {
    if (!startStr) return 1;
    let [y1, m1, d1] = startStr.split('-').map(Number);
    if (endStr) {
        let [y2, m2, d2] = endStr.split('-').map(Number);
        if (new Date(y1, m1 - 1, d1) > new Date(y2, m2 - 1, d2)) {
            y1 = y2; m1 = m2; d1 = d2;
        }
    }
    const startYm = `${y1}-${String(m1).padStart(2, '0')}`;
    const dayInMonth = (startYm === majorityInfo.ym) ? d1 : 1;
    const week = Math.min(5, Math.max(1, Math.ceil(dayInMonth / 7)));
    return week;
}

function formatCompactRange(startStr, endStr) {
    if (!startStr || !endStr) return '';
    let [y1, m1, d1] = startStr.split('-').map(Number);
    let [y2, m2, d2] = endStr.split('-').map(Number);
    if (new Date(y1, m1 - 1, d1) > new Date(y2, m2 - 1, d2)) {
        const ty = y1, tm = m1, td = d1;
        y1 = y2; m1 = m2; d1 = d2;
        y2 = ty; m2 = tm; d2 = td;
    }
    if (m1 === m2 && y1 === y2) {
        return `${d1} – ${d2} ${MONTH_SHORTS[m1]} ${y1}`;
    }
    if (y1 === y2) {
        return `${d1} ${MONTH_SHORTS[m1]} – ${d2} ${MONTH_SHORTS[m2]} ${y1}`;
    }
    return `${d1} ${MONTH_SHORTS[m1]} ${y1} – ${d2} ${MONTH_SHORTS[m2]} ${y2}`;
}

function calculateSuggestions() {
    const isWeekly = document.getElementById('check_borongan').checked;
    const isMonthly = document.getElementById('check_bulanan').checked;

    const wStart = document.getElementById('periode_awal_weekly').value;
    const wEnd = document.getElementById('periode_akhir_weekly').value;
    const mStart = document.getElementById('periode_awal_monthly').value;
    const mEnd = document.getElementById('periode_akhir_monthly').value;

    const suggestions = [];
    let badgeInfo = '';

    if (isWeekly && isMonthly) {
        // Mode Gabungan
        const wMaj = getMajorityMonthInfo(wStart, wEnd);
        const wWeek = getWeekOfMonth(wStart, wEnd, wMaj);
        const wRange = formatCompactRange(wStart, wEnd);
        const mMaj = getMajorityMonthInfo(mStart, mEnd);
        const mRange = formatCompactRange(mStart, mEnd);

        const isSameMonth = (wMaj.ym === mMaj.ym);

        if (isSameMonth) {
            badgeInfo = `Gabungan: Borongan W${wWeek} & Bulanan ${mMaj.monthName}`;
            suggestions.push(`Gabungan ${wMaj.monthName} Week ${wWeek}`);
            suggestions.push(`${wMaj.monthName} Week ${wWeek} (Gabungan)`);
            suggestions.push(`Minggu ${wWeek} ${wMaj.monthName} ${wMaj.year}`);
            suggestions.push(`Borongan W${wWeek} & Bulanan ${mMaj.shortMonth}`);
            suggestions.push(`Gabungan ${wMaj.shortMonth} ${wMaj.year}`);
            if (wRange) {
                suggestions.push(`Borongan (${wRange}) & Bulanan`);
            }
        } else {
            // Jika Borongan dan Bulanan berada di bulan yang berbeda (misal Borongan Juli vs Bulanan Agustus)
            const yearStr = (wMaj.year === mMaj.year) ? wMaj.year : `${wMaj.year}/${mMaj.year}`;
            badgeInfo = `Gabungan: Borongan W${wWeek} (${wMaj.shortMonth}) + Bulanan (${mMaj.shortMonth})`;
            suggestions.push(`Borongan W${wWeek} ${wMaj.shortMonth} & Bulanan ${mMaj.shortMonth}`);
            suggestions.push(`${wMaj.monthName} W${wWeek} & Bulanan ${mMaj.monthName} ${yearStr}`);
            suggestions.push(`Gabungan ${wMaj.shortMonth} & ${mMaj.shortMonth} ${yearStr}`);
            if (wRange) {
                suggestions.push(`Borongan (${wRange}) & Bulanan ${mMaj.shortMonth}`);
            }
            suggestions.push(`Gabungan ${wMaj.monthName} – ${mMaj.monthName} ${yearStr}`);
        }
    } else if (isWeekly) {
        // Mode Borongan (Mingguan)
        const wMaj = getMajorityMonthInfo(wStart, wEnd);
        const wWeek = getWeekOfMonth(wStart, wEnd, wMaj);
        const range = formatCompactRange(wStart, wEnd);

        badgeInfo = `Borongan: Week ${wWeek} (${wMaj.monthName} ${wMaj.year})`;
        suggestions.push(`${wMaj.monthName} Week ${wWeek}`);
        suggestions.push(`Minggu ${wWeek} ${wMaj.monthName} ${wMaj.year}`);
        suggestions.push(`Borongan Week ${wWeek} ${wMaj.shortMonth}`);
        suggestions.push(`W${wWeek} ${wMaj.monthName} ${wMaj.year}`);
        if (range) {
            suggestions.push(`Borongan ${range}`);
        }
    } else if (isMonthly) {
        // Mode Bulanan
        const mMaj = getMajorityMonthInfo(mStart, mEnd);
        const range = formatCompactRange(mStart, mEnd);

        badgeInfo = `Bulanan: ${mMaj.monthName} ${mMaj.year}`;
        suggestions.push(`Bulanan ${mMaj.monthName} ${mMaj.year}`);
        suggestions.push(`${mMaj.monthName} ${mMaj.year}`);
        suggestions.push(`Gaji Bulanan ${mMaj.shortMonth} ${mMaj.year}`);
        if (range) {
            suggestions.push(`Bulanan ${range}`);
        }
    } else {
        badgeInfo = 'Pilih tipe karyawan';
        suggestions.push('Payroll ' + MONTH_NAMES[new Date().getMonth() + 1]);
    }

    currentSuggestions = [...new Set(suggestions)];
    return { suggestions: currentSuggestions, badgeInfo };
}

function updateSuggestionsUI(updateInputValue = false) {
    const { suggestions, badgeInfo } = calculateSuggestions();
    const chipsContainer = document.getElementById('suggestion_chips');
    const badgeEl = document.getElementById('calc_info_badge');
    const nameInput = document.getElementById('payroll_name');

    if (badgeEl) {
        badgeEl.textContent = badgeInfo;
    }

    if (updateInputValue && !isCustomTyped) {
        const targetIndex = (selectedFormatIndex !== null && selectedFormatIndex >= 0 && selectedFormatIndex < suggestions.length)
            ? selectedFormatIndex
            : 0;
        selectedFormatIndex = targetIndex;
        if (suggestions[targetIndex]) {
            nameInput.value = suggestions[targetIndex];
        }
    }

    const currentVal = nameInput.value.trim();

    chipsContainer.innerHTML = '';
    suggestions.forEach((item, idx) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        const isActive = (!isCustomTyped && selectedFormatIndex === idx) || (item === currentVal);
        btn.className = `suggestion-chip ${isActive ? 'active' : ''}`;
        btn.innerHTML = isActive 
            ? `<i class="bi bi-check2"></i> <span>${escapeHtml(item)}</span>`
            : `<span>${escapeHtml(item)}</span>`;
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            applySuggestion(idx);
        });
        chipsContainer.appendChild(btn);
    });
}

function applySuggestion(index) {
    const nameInput = document.getElementById('payroll_name');
    if (currentSuggestions[index] !== undefined) {
        selectedFormatIndex = index;
        isCustomTyped = false;
        nameInput.value = currentSuggestions[index];
        updateSuggestionsUI(false);
    }
}

function resetToAutoTitle() {
    selectedFormatIndex = 0;
    isCustomTyped = false;
    updateSuggestionsUI(true);
}

function escapeHtml(str) {
    return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}

function toggleDateInputs(type, isChecked) {
    const container = document.getElementById('date_inputs_' + type);
    if (isChecked) {
        container.style.display = 'flex';
    } else {
        container.style.display = 'none';
    }
    updateSuggestionsUI(true);
}

function validateCheckboxes() {
    const checkBorongan = document.getElementById('check_borongan').checked;
    const checkBulanan = document.getElementById('check_bulanan').checked;
    
    if (!checkBorongan && !checkBulanan) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'Pilih Tipe Penggajian',
                text: 'Harap centang minimal satu tipe karyawan (Borongan atau Bulanan) yang ingin dihitung.',
                confirmButtonColor: '#0078d4'
            });
        } else {
            alert('Pilih minimal satu tipe karyawan (Borongan atau Bulanan).');
        }
        return false;
    }

    if (checkBorongan) {
        const wStart = document.getElementById('periode_awal_weekly').value;
        const wEnd = document.getElementById('periode_akhir_weekly').value;
        if (!wStart || !wEnd) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Rentang Tanggal Borongan',
                    text: 'Harap lengkapi tanggal awal dan tanggal akhir untuk Karyawan Borongan.',
                    confirmButtonColor: '#0078d4'
                });
            } else {
                alert('Harap lengkapi tanggal awal dan tanggal akhir untuk Karyawan Borongan.');
            }
            return false;
        }
        if (wStart > wEnd) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Rentang Tanggal Tidak Valid',
                    text: 'Tanggal awal Borongan tidak boleh lebih besar dari tanggal akhir.',
                    confirmButtonColor: '#0078d4'
                });
            } else {
                alert('Tanggal awal Borongan tidak boleh lebih besar dari tanggal akhir.');
            }
            return false;
        }
    }

    if (checkBulanan) {
        const mStart = document.getElementById('periode_awal_monthly').value;
        const mEnd = document.getElementById('periode_akhir_monthly').value;
        if (!mStart || !mEnd) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Rentang Tanggal Bulanan',
                    text: 'Harap lengkapi tanggal awal dan tanggal akhir untuk Karyawan Bulanan.',
                    confirmButtonColor: '#0078d4'
                });
            } else {
                alert('Harap lengkapi tanggal awal dan tanggal akhir untuk Karyawan Bulanan.');
            }
            return false;
        }
        if (mStart > mEnd) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Rentang Tanggal Tidak Valid',
                    text: 'Tanggal awal Bulanan tidak boleh lebih besar dari tanggal akhir.',
                    confirmButtonColor: '#0078d4'
                });
            } else {
                alert('Tanggal awal Bulanan tidak boleh lebih besar dari tanggal akhir.');
            }
            return false;
        }
    }

    const nameInput = document.getElementById('payroll_name');
    if (!nameInput.value.trim()) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'Nama Payroll Kosong',
                text: 'Harap isi judul/nama payroll terlebih dahulu.',
                confirmButtonColor: '#0078d4'
            });
        } else {
            alert('Harap isi judul/nama payroll terlebih dahulu.');
        }
        return false;
    }

    return true;
}

document.addEventListener('DOMContentLoaded', function() {
    const nameInput = document.getElementById('payroll_name');
    
    nameInput.addEventListener('input', function() {
        const val = this.value.trim();
        const matchIdx = currentSuggestions.indexOf(val);
        if (matchIdx !== -1) {
            selectedFormatIndex = matchIdx;
            isCustomTyped = false;
        } else {
            selectedFormatIndex = null;
            isCustomTyped = (val !== '');
        }
        updateSuggestionsUI(false);
    });

    const dateInputs = [
        'periode_awal_weekly', 'periode_akhir_weekly',
        'periode_awal_monthly', 'periode_akhir_monthly'
    ];

    dateInputs.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('change', () => updateSuggestionsUI(true));
            el.addEventListener('input', () => updateSuggestionsUI(true));
        }
    });

    // Inisialisasi awal
    updateSuggestionsUI(false);
});
</script>

