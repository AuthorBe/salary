<?php
/**
 * @var string $title
 * @var array $payrolls
 */
?>

<div class="page-title-box d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
    <div class="page-title-left">
        <h4 class="mb-1 text-dark fw-bold d-flex align-items-center">
            <i class="bi bi-calendar-week-fill text-primary me-2 fs-4"></i> Data Payroll
        </h4>
        <p class="text-muted mb-0 fs-7 ms-4 ps-1">Kelola perhitungan dan persetujuan gaji karyawan</p>
    </div>
    <div class="page-title-right">
        <a href="<?= url('/payroll/create') ?>" class="btn btn-primary btn-fab-mobile rounded-pill px-4 shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Generate Payroll Baru
        </a>
    </div>
</div>

<?php if (isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success border-0 shadow-sm d-flex align-items-center justify-content-between mb-4">
        <div><i class="bi bi-check-circle-fill me-2 fs-5"></i> <?= e($_SESSION['flash_success']) ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center justify-content-between mb-4">
        <div><i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i> <?= e($_SESSION['flash_error']) ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['swal_warning'])): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'Perhatian!',
                html: `<?= $_SESSION['swal_warning'] ?>`,
                confirmButtonText: 'Oke, Saya Paham',
                allowOutsideClick: false,
                allowEscapeKey: false,
                allowEnterKey: false,
                customClass: {
                    popup: 'rounded-4 shadow-lg border-0',
                    title: 'fw-bold text-dark',
                    content: 'text-secondary',
                    confirmButton: 'btn btn-primary rounded-pill px-5 py-2 fw-semibold shadow-sm'
                },
                buttonsStyling: false
            });
        } else {
            alert('Perhatian!\n\n<?= strip_tags(str_replace("<br>", "\n", $_SESSION['swal_warning'])) ?>');
        }
    });
    </script>
    <?php unset($_SESSION['swal_warning']); ?>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <?php if (empty($payrolls)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                Belum ada data payroll yang di-generate.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr style="height: 46px;">
                            <th scope="col" class="ps-3" style="width: 24%;">NAMA / ID</th>
                            <th scope="col" style="min-width: 240px;">PERIODE KERJA</th>
                            <th scope="col" style="width: 13%;">TIPE</th>
                            <th scope="col" style="width: 16%;">DIBUAT PADA</th>
                            <th scope="col" style="width: 13%;">STATUS</th>
                            <th scope="col" class="text-end pe-3" style="width: 16%;">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payrolls as $pr): ?>
                            <tr>
                                <td class="ps-3 fw-semibold text-muted">
                                    <div class="text-dark fw-bold"><?= e($pr['name'] ?? 'Run #' . $pr['id']) ?></div>
                                    <small>#<?= $pr['id'] ?></small>
                                </td>
                                <td>
                                    <?= renderPayrollPeriodHtml($pr) ?>
                                </td>
                                <td>
                                    <?php if ($pr['type'] === 'weekly'): ?>
                                        <span class="badge bg-info-subtle text-info border border-info-subtle px-3 py-1 rounded-pill">Mingguan</span>
                                    <?php elseif ($pr['type'] === 'monthly'): ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill">Bulanan</span>
                                    <?php else: ?>
                                        <span class="badge bg-purple-subtle text-purple border border-purple-subtle px-3 py-1 rounded-pill" style="color: #6f42c1; background-color: #e2d9f3; border-color: #d6c8ef;">Gabungan</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted"><?= formatTanggal(substr($pr['created_at'], 0, 10)) ?></td>
                                <td>
                                    <?php if ($pr['status'] === 'draft'): ?>
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-1 rounded-pill">Draft</span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill" title="Disetujui oleh <?= e($pr['approved_by_name']) ?>">
                                            <i class="bi bi-check-circle-fill me-1"></i> Approved
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3">
                                    <a href="<?= url('/payroll/preview?id=' . $pr['id']) ?>" class="btn btn-sm btn-light">
                                        <i class="bi bi-eye"></i> <?= $pr['status'] === 'draft' ? 'Review & Approve' : 'Detail' ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
