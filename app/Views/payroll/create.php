<?php
/**
 * @var string $title
 * @var string $type
 * @var string $periode_awal
 * @var string $periode_akhir
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

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form action="<?= url('/payroll/store') ?>" method="POST">
            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label fw-medium">Judul / Nama Payroll <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="Contoh: Gaji Borongan Lebaran, atau Gaji Minggu I Agustus" required>
                    <div class="form-text">Berikan nama agar mudah dikenali di riwayat dan laporan.</div>
                </div>
            </div>
            <?= csrfField() ?>

            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <label for="type" class="form-label fw-semibold">Tipe Penggajian <span class="text-danger">*</span></label>
                    <select class="form-select form-select-lg" id="type" name="type" onchange="toggleMonthlyOption()" required>
                        <option value="weekly" <?= $type === 'weekly' ? 'selected' : '' ?>>Mingguan (Karyawan Borongan)</option>
                        <option value="monthly" <?= $type === 'monthly' ? 'selected' : '' ?>>Bulanan (Karyawan Bulanan)</option>
                    </select>
                    <div class="form-text text-muted mt-2">
                        Pilih <strong>Mingguan</strong> untuk menghitung gaji borongan &amp; uang hadir, atau <strong>Bulanan</strong> untuk gaji tetap.
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="row g-3">
                        <div class="col-6">
                            <label for="periode_awal" class="form-label fw-semibold">Dari Tanggal <span class="text-danger">*</span></label>
                            <input type="date" class="form-control form-control-lg" id="periode_awal" name="periode_awal" value="<?= e($periode_awal ?? '') ?>" required>
                        </div>
                        <div class="col-6">
                            <label for="periode_akhir" class="form-label fw-semibold">Sampai Tanggal <span class="text-danger">*</span></label>
                            <input type="date" class="form-control form-control-lg" id="periode_akhir" name="periode_akhir" value="<?= e($periode_akhir ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="form-text text-muted mt-2">
                        Rentang tanggal absensi dan hasil produksi yang akan ditarik ke dalam perhitungan.
                    </div>
                </div>
            </div>

            <hr class="my-4">
            
            <div class="d-flex justify-content-end gap-2">
                <a href="<?= url('/payroll') ?>" class="btn btn-light border px-4">Batal</a>
                <button type="submit" class="btn btn-primary px-4 d-inline-flex align-items-center">
                    <i class="bi bi-calculator me-2"></i> Kalkulasi &amp; Generate Draft
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Data default yang di-generate dari Controller
const defaultDates = {
    weekly: {
        start: '<?= $default_weekly_start ?>',
        end: '<?= $default_weekly_end ?>'
    },
    monthly: {
        start: '<?= $default_monthly_start ?>',
        end: '<?= $default_monthly_end ?>'
    }
};

function toggleMonthlyOption() {
    const select = document.getElementById('type');
    const startInput = document.getElementById('periode_awal');
    const endInput = document.getElementById('periode_akhir');
    
    // Otomatis ubah tanggal sesuai tipe
    if (select.value === 'weekly') {
        startInput.value = defaultDates.weekly.start;
        endInput.value = defaultDates.weekly.end;
    } else {
        startInput.value = defaultDates.monthly.start;
        endInput.value = defaultDates.monthly.end;
    }
}
</script>
