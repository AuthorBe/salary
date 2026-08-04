<?php
/**
 * @var string $title
 * @var string $date
 * @var array $employees
 * @var array $products
 * @var array $productions
 */
?>

<div class="page-title-box d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
    <div class="page-title-left">
        <h4 class="mb-1 text-dark fw-bold d-flex align-items-center">
            <i class="bi bi-box-fill text-primary me-2 fs-4"></i> Data Produksi Harian
        </h4>
        <p class="text-muted mb-0 fs-7 ms-4 ps-1">Catat hasil kerja karyawan borongan (bungkus/bal) per tanggal</p>
    </div>
    <div class="page-title-right">
        <a href="<?= url('/productions/history') ?>" class="btn btn-primary rounded-pill fw-medium shadow-sm d-flex align-items-center gap-2">
            <i class="bi bi-clock-history"></i> Riwayat Produksi
        </a>
    </div>
</div>

<div class="alert alert-primary bg-primary-subtle border-0 rounded-3 d-flex align-items-center mb-4 p-3 shadow-sm" role="alert">
    <i class="bi bi-info-circle-fill text-primary fs-5 me-3"></i>
    <div style="font-size: 0.85rem;" class="text-dark">
        <strong>Info:</strong> Memasukkan data untuk karyawan yang <em>sudah ada di tabel riwayat</em> tidak akan menghapus data lamanya, melainkan akan <strong>menambahkan/menjumlahkan</strong> kuantitas produk tersebut secara otomatis.
    </div>
</div>

<?php if (isset($_SESSION['flash_success'])): ?>
    <div class="position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center" style="z-index: 1055; background: rgba(0,0,0,0.5);" id="production-success-overlay" onclick="this.remove()">
        <div class="card border-0 shadow-lg" style="max-width: 400px; width: 90%; animation: popIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);" onclick="event.stopPropagation()">
            <div class="card-body p-4 text-center">
                <div class="mb-3 text-success">
                    <i class="bi bi-check-circle-fill" style="font-size: 4rem;"></i>
                </div>
                <h4 class="fw-bold mb-2"><?= e($_SESSION['flash_title'] ?? 'Berhasil!') ?></h4>
                <p class="text-muted mb-4"><?= $_SESSION['flash_success'] ?></p>
                <button type="button" class="btn btn-primary px-5 rounded-pill shadow-sm" onclick="document.getElementById('production-success-overlay').remove()">Oke, Tutup</button>
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
<div id="production-alert"></div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom pt-3 pb-3">
        <form class="row gy-2 gx-3 align-items-center" id="dateFilterForm" 
              action="<?= url('/productions') ?>" 
              method="GET"
              onsubmit="event.preventDefault(); loadProductionDate(document.getElementById('date').value);">
            
            <div class="col-auto">
                <label for="date" class="col-form-label fw-bold">Tanggal Input:</label>
            </div>
            <div class="col-auto">
                <input type="date" class="form-control fw-bold text-primary" id="date" name="date" value="<?= e($date) ?>" required max="<?= date('Y-m-d') ?>"
                       onchange="loadProductionDate(this.value);">
            </div>
            <div class="col-auto">
                <div class="htmx-indicator spinner-border spinner-border-sm text-primary" id="dateSpinner" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </form>
    </div>
    
    <div class="card-body p-4" id="production-form-container">
        <?php view('productions/_form', [
            'date'        => $date,
            'employees'   => $employees,
            'products'    => $products,
            'productions' => $productions
        ], 'partials'); ?>
    </div>
</div>

<script>
function loadProductionDate(dateVal) {
    if (!dateVal) return;
    
    var newUrl = '<?= url('/productions') ?>?date=' + encodeURIComponent(dateVal);
    window.history.pushState({ date: dateVal }, '', newUrl);

    var spinner = document.getElementById('dateSpinner');
    if (spinner) spinner.classList.add('htmx-request');

    if (window.htmx) {
        htmx.ajax('GET', '<?= url('/productions/form') ?>?date=' + encodeURIComponent(dateVal), {
            target: '#production-form-container',
            swap: 'innerHTML'
        }).then(function() {
            if (spinner) spinner.classList.remove('htmx-request');
        });
    } else {
        window.location.href = newUrl;
    }
}

window.addEventListener('popstate', function(evt) {
    var urlParams = new URLSearchParams(window.location.search);
    var dateVal = urlParams.get('date');
    if (dateVal) {
        var dateInput = document.getElementById('date');
        if (dateInput) dateInput.value = dateVal;
        loadProductionDate(dateVal);
    }
});
</script>
