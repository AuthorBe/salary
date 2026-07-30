<?php
/**
 * @var string $title
 * @var array $employees
 */
?>

<div class="page-title-box d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
    <div class="page-title-left">
        <h2 class="h4 mb-1 fw-bold d-flex align-items-center">
            <i class="bi bi-people-fill text-primary me-2 fs-4"></i> Data Karyawan
        </h2>
        <p class="text-muted mb-0 fs-7 ms-4 ps-1">Kelola seluruh data karyawan dan informasi kepegawaian secara terpusat</p>
    </div>
    <div class="page-title-right">
        <a href="<?= url('/employees/form') ?>" class="btn btn-primary d-inline-flex align-items-center">
            <i class="bi bi-person-plus-fill me-2"></i> Tambah Karyawan
        </a>
    </div>
</div>

<?php if (isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success d-flex align-items-center" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>
        <div><?= e($_SESSION['flash_success']) ?></div>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger d-flex align-items-center" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <div><?= e($_SESSION['flash_error']) ?></div>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th scope="col" class="ps-4">NAMA KARYAWAN</th>
                    <th scope="col">TIPE GAJI</th>
                    <th scope="col">U. HADIR / HARI</th>
                    <th scope="col">U. BULANAN</th>
                    <th scope="col">STATUS</th>
                    <th scope="col" class="text-end pe-4">AKSI</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($employees)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">Belum ada data karyawan.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($employees as $emp): ?>
                        <tr>
                            <td class="ps-4 fw-medium text-dark">
                                <?= e($emp['name']) ?>
                                <?php if ($emp['tipe_gaji'] === 'bulanan'): ?>
                                    <div class="small text-muted fw-normal">Gaji Pokok: <?= formatRupiah((int)$emp['gaji_pokok']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($emp['tipe_gaji'] === 'borongan'): ?>
                                    <span class="badge bg-warning text-dark rounded-pill fw-normal">Borongan</span>
                                <?php else: ?>
                                    <span class="badge bg-primary text-white rounded-pill fw-normal">Bulanan</span>
                                <?php endif; ?>
                            </td>
                            <td><?= formatRupiah((int)$emp['uang_kehadiran_harian']) ?></td>
                            <td><?= formatRupiah((int)$emp['tunjangan_bulanan']) ?></td>
                            <td>
                                <?php if ($emp['aktif']): ?>
                                    <span class="badge bg-success-soft text-success rounded-pill px-2 py-1"><i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-soft text-danger rounded-pill px-2 py-1"><i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="<?= url('/employees/form?id=' . $emp['id']) ?>" class="btn-action btn-action-primary icon-only" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="<?= url('/employees/delete') ?>" method="POST" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                        <input type="hidden" name="id" value="<?= $emp['id'] ?>">
                                        <button type="submit" class="btn-action btn-action-danger icon-only" title="Hapus" data-confirm="Yakin ingin menghapus karyawan ini?">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

