<?php
/**
 * @var string $title
 * @var array $settings
 */
$companyName  = $settings['company_name'] ?? 'Bintang Harapan';
$weekStartDay = (string)($settings['week_start_day'] ?? '1');
$weekEndDay   = (string)($settings['week_end_day'] ?? '0');

$days = [
    '0' => 'Minggu',
    '1' => 'Senin',
    '2' => 'Selasa',
    '3' => 'Rabu',
    '4' => 'Kamis',
    '5' => 'Jumat',
    '6' => 'Sabtu',
];
?>

<div class="page-title-box d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
    <div class="page-title-left">
        <h2 class="h4 mb-1 fw-bold d-flex align-items-center">
            <i class="bi bi-gear-fill text-primary me-2 fs-4"></i> Setelan Aplikasi
        </h2>
        <p class="text-muted mb-0 fs-7 ms-4 ps-1">Kelola konfigurasi nama perusahaan dan siklus minggu payroll.</p>
    </div>
</div>

<?php if (isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
        <i class="bi bi-check-circle-fill fs-5 me-2"></i>
        <div><?= e($_SESSION['flash_success']) ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
        <div><?= e($_SESSION['flash_error']) ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<form action="<?= url('/settings') ?>" method="POST">
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

    <div class="row g-4">
        <!-- Section 1: Identitas Perusahaan -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="card-title h6 mb-0 fw-bold d-flex align-items-center text-primary">
                        <i class="bi bi-building me-2 fs-5"></i> Identitas Perusahaan
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label for="company_name" class="form-label fw-semibold">
                            <i class="bi bi-shop me-1 text-secondary"></i> Nama Perusahaan / Toko <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control form-control-lg" 
                               id="company_name" 
                               name="company_name" 
                               value="<?= e($companyName) ?>" 
                               placeholder="Contoh: Bintang Harapan" 
                               required>
                        <div class="form-text">Nama bisnis/toko yang digunakan pada kop dan judul laporan aplikasi.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Siklus Payroll & Standar Waktu -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="card-title h6 mb-0 fw-bold d-flex align-items-center text-primary">
                        <i class="bi bi-sliders me-2 fs-5"></i> Siklus Payroll & Zona Waktu
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-4">
                        <div class="row g-2">
                            <div class="col-sm-6">
                                <label for="week_start_day" class="form-label fw-semibold">
                                    <i class="bi bi-calendar-week me-1 text-secondary"></i> Hari Awal Pekan
                                </label>
                                <select class="form-select form-select-lg" id="week_start_day" name="week_start_day">
                                    <?php foreach ($days as $val => $label): ?>
                                        <option value="<?= $val ?>" <?= (string)$val === $weekStartDay ? 'selected' : '' ?>>
                                            <?= $label ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label for="week_end_day" class="form-label fw-semibold">
                                    <i class="bi bi-calendar-check me-1 text-secondary"></i> Hari Akhir Pekan
                                </label>
                                <select class="form-select form-select-lg" id="week_end_day" name="week_end_day">
                                    <?php foreach ($days as $val => $label): ?>
                                        <option value="<?= $val ?>" <?= (string)$val === $weekEndDay ? 'selected' : '' ?>>
                                            <?= $label ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-text mt-2">Menentukan hari pertama dan hari tutup buku dalam 1 siklus penggajian mingguan (Otomatis menyesuaikan filter tanggal).</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-clock-history me-1 text-secondary"></i> Zona Waktu (Timezone)
                        </label>
                        <div class="form-control form-control-lg bg-light text-dark d-flex align-items-center border-0">
                            <i class="bi bi-geo-alt-fill text-primary me-2"></i>
                            <span>Waktu Indonesia Barat (WIB / Asia/Jakarta)</span>
                        </div>
                        <div class="form-text">Seluruh pencatatan transaksi dan absensi secara permanen menggunakan acuan standar WIB.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tombol Simpan -->
    <div class="mt-4 text-end">
        <button type="submit" class="btn btn-primary btn-lg px-4 py-2 d-inline-flex align-items-center shadow-sm">
            <i class="bi bi-save me-2"></i> Simpan Setelan
        </button>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const startSelect = document.getElementById('week_start_day');
    const endSelect = document.getElementById('week_end_day');
    if (startSelect && endSelect) {
        startSelect.addEventListener('change', function() {
            const startVal = parseInt(this.value, 10);
            if (!isNaN(startVal)) {
                const autoEndVal = (startVal + 6) % 7;
                endSelect.value = autoEndVal.toString();
            }
        });
    }
});
</script>
