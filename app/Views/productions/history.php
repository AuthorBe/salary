<?php
/**
 * @var string $title
 * @var string $pageTitle
 * @var string $pageKey
 * @var string $startDate
 * @var string $endDate
 * @var int $employeeId
 * @var array $employees
 * @var array $history
 */
?>

<div class="page-title-box d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
    <div class="page-title-left">
        <h4 class="mb-1 text-dark fw-bold d-flex align-items-center">
            <i class="bi bi-clock-history text-primary me-2 fs-4"></i> Riwayat Produksi
        </h4>
        <p class="text-muted mb-0 fs-7 ms-4 ps-1">Lihat dan kelola rekap produksi harian karyawan borongan</p>
    </div>
    <div class="page-title-right">
        <a href="<?= url('/productions') ?>" class="btn btn-outline-secondary rounded-pill fw-medium shadow-sm d-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i> Kembali ke Form Input
        </a>
    </div>
</div>

<?php if (isset($_SESSION['flash_success'])): ?>
    <?= renderAlert('success', e($_SESSION['flash_success']), 5000) ?>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['flash_error'])): ?>
    <?= renderAlert('danger', e($_SESSION['flash_error']), 5000) ?>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<!-- Filter Section -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <form method="GET" action="<?= url('/productions/history') ?>" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="start_date" class="form-label fw-semibold">Tanggal Awal</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?= e($startDate) ?>" required>
            </div>
            <div class="col-md-3">
                <label for="end_date" class="form-label fw-semibold">Tanggal Akhir</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?= e($endDate) ?>" required>
            </div>
            <div class="col-md-4">
                <label for="id_karyawan" class="form-label fw-semibold">Filter Karyawan</label>
                <select name="id_karyawan" id="id_karyawan" class="form-select">
                    <option value="">-- Semua Karyawan --</option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?= $emp['id'] ?>" <?= $emp['id'] == $employeeId ? 'selected' : '' ?>>
                            <?= e($emp['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100 fw-medium rounded text-nowrap">
                    <i class="bi bi-funnel-fill me-1"></i> Terapkan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- History Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center">
            <i class="bi bi-table text-success me-2"></i> Data Riwayat
        </h5>
        <span class="badge bg-primary-subtle text-primary rounded-pill px-3">
            <?= count($history) ?> Catatan Ditemukan
        </span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($history)): ?>
            <div class="p-5 text-center text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-3 text-secondary opacity-50"></i>
                <div class="fw-semibold">Tidak ada riwayat produksi.</div>
                <small>Silakan ubah filter tanggal atau karyawan.</small>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="ps-4 fw-semibold text-nowrap">Tanggal</th>
                            <th scope="col" class="fw-semibold text-nowrap">Nama Karyawan</th>
                            <th scope="col" class="fw-semibold">Rincian Produk Hasil</th>
                            <th scope="col" class="text-end pe-4 fw-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $record): ?>
                            <tr>
                                <td class="ps-4 fw-medium text-dark text-nowrap">
                                    <?= e(formatTanggal($record['date'])) ?>
                                </td>
                                <td class="fw-medium text-dark text-nowrap">
                                    <?= e($record['nama_karyawan']) ?>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-2 py-1">
                                        <?php foreach ($record['products'] as $pData): ?>
                                            <?php 
                                            $pName = $pData['nama_produk'];
                                            $qty = $pData['kuantitas'] ?? 0;
                                            $bal = $pData['kuantitas_bal'] ?? 0;
                                            ?>
                                            <div class="border rounded px-2 py-1 bg-light d-inline-flex flex-column lh-sm">
                                                <span class="text-secondary" style="font-size: 0.75rem;"><?= e($pName) ?></span>
                                                <strong class="text-dark" style="font-size: 0.85rem;">
                                                    <?php if ($qty > 0): ?><?= number_format($qty, 0, ',', '.') ?> Bks<?php endif; ?>
                                                    <?php if ($qty > 0 && $bal > 0): ?> &bull; <?php endif; ?>
                                                    <?php if ($bal > 0): ?><?= number_format($bal, 0, ',', '.') ?> Bal<?php endif; ?>
                                                </strong>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td class="text-end pe-4">
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-danger border-0 rounded-circle btn-delete-history" 
                                            title="Hapus catatan"
                                            data-date="<?= e($record['date']) ?>"
                                            data-display-date="<?= e(formatTanggal($record['date'])) ?>"
                                            data-empid="<?= $record['id_karyawan'] ?>"
                                            data-empname="<?= e($record['nama_karyawan']) ?>">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Form tersembunyi untuk proses hapus -->
<form id="formDeleteHistory" method="POST" action="<?= url('/productions/delete-employee') ?>" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
    <input type="hidden" name="date" id="delete_date" value="">
    <input type="hidden" name="id_karyawan" id="delete_empid" value="">
    <input type="hidden" name="source" value="history">
</form>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const deleteBtns = document.querySelectorAll('.btn-delete-history');
    const form = document.getElementById('formDeleteHistory');
    const inputDate = document.getElementById('delete_date');
    const inputEmp = document.getElementById('delete_empid');

    deleteBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const date = this.getAttribute('data-date');
            const displayDate = this.getAttribute('data-display-date');
            const empId = this.getAttribute('data-empid');
            const empName = this.getAttribute('data-empname');

            Swal.fire({
                title: 'Hapus Seluruh Data Produksi?',
                html: `Anda akan menghapus <b>semua</b> catatan produksi untuk karyawan <b class="text-danger">${empName}</b> pada tanggal <b>${displayDate}</b>.<br><br><span class="text-muted small">Tindakan ini tidak dapat dibatalkan.</span>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="bi bi-trash"></i> Ya, Hapus Semua',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    inputDate.value = date;
                    inputEmp.value = empId;
                    form.submit();
                }
            });
        });
    });
});
</script>
