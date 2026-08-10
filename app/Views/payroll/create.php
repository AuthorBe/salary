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
                <div class="col-md-12">
                    <label class="form-label fw-semibold">Tipe Penggajian & Rentang Tanggal <span class="text-danger">*</span></label>
                    <div class="form-text text-muted mb-3">
                        Pilih tipe karyawan yang ingin dihitung. Anda dapat menggabungkan Borongan dan Bulanan sekaligus dengan rentang tanggal yang berbeda-beda.
                    </div>

                    <div class="card border mb-3">
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

                    <div class="card border">
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

<script>
function toggleDateInputs(type, isChecked) {
    const container = document.getElementById('date_inputs_' + type);
    if (isChecked) {
        container.style.display = 'flex';
    } else {
        container.style.display = 'none';
    }
}

function validateCheckboxes() {
    const checkBorongan = document.getElementById('check_borongan').checked;
    const checkBulanan = document.getElementById('check_bulanan').checked;
    
    if (!checkBorongan && !checkBulanan) {
        Swal.fire({
            icon: 'warning',
            title: 'Pilih Tipe Penggajian',
            text: 'Harap centang minimal satu tipe karyawan (Borongan atau Bulanan) yang ingin dihitung.',
            confirmButtonColor: '#0078d4'
        });
        return false;
    }
    return true;
}
</script>
