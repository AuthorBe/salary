<?php
/**
 * @var string $title
 * @var array|null $employee
 */
$isEdit = $employee !== null;
$salaryType = $isEdit ? $employee['tipe_gaji'] : 'borongan';
?>

<div class="page-title-box d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
    <div class="page-title-left">
        <h2 class="h4 mb-1 fw-bold d-flex align-items-center">
            <i class="bi bi-person-fill text-primary me-2 fs-4"></i> <?= $isEdit ? 'Edit' : 'Tambah' ?> Karyawan
        </h2>
        <p class="text-muted mb-0 fs-7 ms-4 ps-1">Lengkapi data informasi karyawan dengan benar</p>
    </div>
    <div class="page-title-right">
        <a href="<?= url('/employees') ?>" class="btn btn-outline-secondary d-inline-flex align-items-center">
            <i class="bi bi-arrow-left me-2"></i> Kembali
        </a>
    </div>
</div>

<!-- Info Box: Penjelasan Pengosongan Field -->
<div class="alert alert-info d-flex align-items-center mb-4 border-0 shadow-sm" role="alert">
    <i class="bi bi-info-circle-fill text-info fs-4 me-3"></i>
    <div>
        <strong>Info Nominal:</strong> Jika komponen uang (seperti Gaji Pokok, Uang Hadir, dll) tidak diperlukan untuk karyawan ini, silakan dikosongkan. Sistem akan menganggapnya <strong>Rp 0</strong> secara otomatis dan penghitungan Payroll tetap aman.
    </div>
</div>

<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger d-flex align-items-center" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <div><?= e($_SESSION['flash_error']) ?></div>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form action="<?= url('/employees/store') ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <?php if ($isEdit): ?>
                <input type="hidden" name="id" value="<?= $employee['id'] ?>">
            <?php endif; ?>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="name" class="form-label fw-semibold">Nama Karyawan <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" 
                           value="<?= $isEdit ? e($employee['name']) : '' ?>" 
                           placeholder="Nama Lengkap" required>
                </div>
                <div class="col-md-6">
                    <label for="tipe_gaji" class="form-label fw-semibold">Tipe Gaji <span class="text-danger">*</span></label>
                    <select class="form-select" id="tipe_gaji" name="tipe_gaji" onchange="toggleSalaryFields()" required>
                        <option value="borongan" <?= $salaryType === 'borongan' ? 'selected' : '' ?>>Borongan</option>
                        <option value="bulanan" <?= $salaryType === 'bulanan' ? 'selected' : '' ?>>Bulanan</option>
                        <option value="harian" <?= $salaryType === 'harian' ? 'selected' : '' ?>>Harian</option>
                    </select>
                </div>
            </div>

            <!-- Tipe Bulanan -->
            <div class="row mb-3 salary-field salary-bulanan" style="display: <?= $salaryType === 'bulanan' ? 'flex' : 'none' ?>;">
                <div class="col-md-6">
                    <label for="gaji_pokok" class="form-label fw-semibold">Gaji Pokok (Rp)</label>
                    <input type="text" inputmode="numeric" class="form-control input-rupiah" id="gaji_pokok" name="gaji_pokok" 
                           value="<?= $isEdit ? (int)($employee['gaji_pokok'] ?? 0) : '' ?>">
                </div>
            </div>

            <!-- Tipe Harian -->
            <div class="row mb-3 salary-field salary-harian" style="display: <?= $salaryType === 'harian' ? 'flex' : 'none' ?>;">
                <div class="col-md-6">
                    <label for="upah_harian" class="form-label fw-semibold">Rate Harian (Rp)</label>
                    <input type="text" inputmode="numeric" class="form-control input-rupiah" id="upah_harian" name="upah_harian" 
                           value="<?= $isEdit ? (int)($employee['upah_harian'] ?? 0) : '' ?>">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="uang_kehadiran_harian" class="form-label fw-semibold">Uang Hadir per Hari (Rp)</label>
                    <input type="text" inputmode="numeric" class="form-control input-rupiah" id="uang_kehadiran_harian" name="uang_kehadiran_harian" 
                           value="<?= $isEdit ? (int)$employee['uang_kehadiran_harian'] : '0' ?>">
                    <div class="form-text">Diberikan sesuai jumlah hari hadir.</div>
                </div>
                <div class="col-md-6">
                    <label for="tunjangan_bulanan" class="form-label fw-semibold">Uang Bulanan (Rp)</label>
                    <input type="text" inputmode="numeric" class="form-control input-rupiah" id="tunjangan_bulanan" name="tunjangan_bulanan" 
                           value="<?= $isEdit ? (int)$employee['tunjangan_bulanan'] : '0' ?>">
                    <div class="form-text">Dibayarkan di akhir bulan jika ada kehadiran.</div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="aktif" name="aktif" 
                               <?= (!$isEdit || $employee['aktif']) ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="aktif">Status Aktif</label>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary d-inline-flex align-items-center">
                <i class="bi bi-save me-2"></i> Simpan
            </button>
        </form>
    </div>
</div>

<script>
function toggleSalaryFields() {
    const type = document.getElementById('tipe_gaji').value;
    document.querySelectorAll('.salary-field').forEach(el => el.style.display = 'none');
    
    if (type === 'bulanan') {
        document.querySelector('.salary-bulanan').style.display = 'flex';
    } else if (type === 'harian') {
        document.querySelector('.salary-harian').style.display = 'flex';
    }
}
</script>
