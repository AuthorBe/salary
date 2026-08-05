<?php
/**
 * @var string $title
 * @var string $date
 * @var array $employees
 * @var array $attendances
 */
?>

<div class="page-title-box d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
    <div class="page-title-left">
        <h4 class="mb-1 text-dark fw-bold d-flex align-items-center">
            <i class="bi bi-calendar-check-fill text-primary me-2 fs-4"></i> Data Kehadiran Karyawan
        </h4>
        <p class="text-muted mb-0 fs-7 ms-4 ps-1">Catat kehadiran dan alasan ketidakhadiran karyawan per tanggal</p>
    </div>
</div>

<?php if (isset($_SESSION['flash_success'])): ?>
    <div class="position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center" style="z-index: 1055; background: rgba(0,0,0,0.5);" id="attendance-success-overlay" onclick="this.remove()">
        <div class="card border-0 shadow-lg" style="max-width: 400px; width: 90%; animation: popIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);" onclick="event.stopPropagation()">
            <div class="card-body p-4 text-center">
                <div class="mb-3 text-success">
                    <i class="bi bi-check-circle-fill" style="font-size: 4rem;"></i>
                </div>
                <h4 class="fw-bold mb-2"><?= e($_SESSION['flash_title'] ?? 'Berhasil!') ?></h4>
                <p class="text-muted mb-4"><?= $_SESSION['flash_success'] ?></p>
                <button type="button" class="btn btn-primary px-5 rounded-pill shadow-sm" onclick="document.getElementById('attendance-success-overlay').remove()">Oke, Tutup</button>
            </div>
        </div>
        <style>@keyframes popIn { 0% { opacity: 0; transform: scale(0.8); } 100% { opacity: 1; transform: scale(1); } }</style>
    </div>
    <?php unset($_SESSION['flash_success'], $_SESSION['flash_title']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['flash_error'])): ?>
    <?= renderAlert('danger', e($_SESSION['flash_error']), 5000) ?>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<!-- Container untuk Notifikasi / Toast dari HTMX -->
<div id="attendance-alert"></div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <form class="row g-3 align-items-end" id="dateFilterForm" 
              action="<?= url('/attendances') ?>" 
              method="GET"
              onsubmit="event.preventDefault(); loadAttendanceDate(document.getElementById('date').value);">
            
            <div class="col-md-4">
                <label for="date" class="form-label fw-semibold">Pilih Tanggal</label>
                <input type="date" class="form-control" id="date" name="date" value="<?= e($date) ?>" required
                       onchange="loadAttendanceDate(this.value);">
            </div>
            <div class="col-md-8">
                <!-- HTMX Indicator -->
                <div class="htmx-indicator spinner-border spinner-border-sm text-primary" id="dateSpinner" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Tempat memuat form checklist karyawan (SSR Initial Load + HTMX Update on Date Change) -->
<div id="attendance-form-container"
     hx-get="<?= url('/attendances/form') ?>"
     hx-target="#attendance-form-container"
     hx-swap="innerHTML"
     hx-trigger="attendanceSaved from:body">
    <?php view('attendances/_tabs', [
        'date'              => $date,
        'employeesBulanan'  => $employeesBulanan,
        'employeesBorongan' => $employeesBorongan,
        'attendances'       => $attendances
    ], 'partials'); ?>
</div>

<script>
function loadAttendanceDate(dateVal) {
    if (!dateVal) return;
    
    // 1. Update URL di browser bar secara instan
    var newUrl = '<?= url('/attendances') ?>?date=' + encodeURIComponent(dateVal);
    window.history.pushState({ date: dateVal }, '', newUrl);

    // 2. Tampilkan indicator loading
    var spinner = document.getElementById('dateSpinner');
    if (spinner) spinner.classList.add('htmx-request');

    // 3. Jalankan HTMX AJAX secara eksplisit untuk memperbarui container form
    if (window.htmx) {
        htmx.ajax('GET', '<?= url('/attendances/form') ?>?date=' + encodeURIComponent(dateVal), {
            target: '#attendance-form-container',
            swap: 'innerHTML'
        }).then(function() {
            if (spinner) spinner.classList.remove('htmx-request');
        });
    } else {
        window.location.href = newUrl;
    }
}

// Tangani tombol Back / Forward browser
window.addEventListener('popstate', function(evt) {
    var urlParams = new URLSearchParams(window.location.search);
    var dateVal = urlParams.get('date');
    if (dateVal) {
        var dateInput = document.getElementById('date');
        if (dateInput) dateInput.value = dateVal;
        loadAttendanceDate(dateVal);
    }
});

// Pastikan HTMX menyertakan tanggal yang aktif saat trigger attendanceSaved
document.body.addEventListener('htmx:configRequest', function(evt) {
    var container = document.getElementById('attendance-form-container');
    if (evt.detail.elt === container || (evt.detail.elt && evt.detail.elt.closest('#attendance-form-container') === container)) {
        var dateInput = document.getElementById('date');
        if (dateInput && dateInput.value) {
            evt.detail.parameters['date'] = dateInput.value;
        }
    }
});
</script>
