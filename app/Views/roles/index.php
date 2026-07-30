<?php
/**
 * @var array  $roles   — hasil getAllWithUserCount()
 */
?>

<!-- Page Header -->
<div class="page-header mb-4">
    <div>
        <h5 class="page-header-title d-flex align-items-center gap-2 text-dark fw-semibold mb-0" style="font-size: 1.05rem;">
            <i class="bi fs-5 bi-shield-lock-fill text-primary"></i> Kelola role dan izin akses sistem Salary
        </h5>
    </div>
    <a href="<?= url('/roles/form') ?>" class="btn btn-primary btn-fab-mobile d-inline-flex align-items-center gap-2 rounded-pill px-4">
        <i class="bi bi-plus-circle-fill"></i> Tambah Role
    </a>
</div>

<?php if (!empty($_SESSION['flash_success'])): ?>
    <?= renderAlert('success', e($_SESSION['flash_success'])) ?>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
    <?= renderAlert('danger', e($_SESSION['flash_error'])) ?>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th scope="col" class="ps-4" style="width:40px">#</th>
                    <th scope="col">NAMA ROLE</th>
                    <th scope="col" class="text-center">JUMLAH USER</th>
                    <th scope="col" class="text-end pe-4">AKSI</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($roles)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">Belum ada data role.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($roles as $i => $role): ?>
                        <tr>
                            <td class="ps-4 text-muted small"><?= $i + 1 ?></td>
                            <td class="fw-semibold text-dark">
                                <i class="bi bi-shield-fill me-2 text-primary opacity-75"></i>
                                <?= e($role['name']) ?>
                            </td>
                            <td class="text-center">
                                <span class="badge rounded-pill <?= $role['user_count'] > 0 ? 'bg-primary-soft text-primary' : 'bg-secondary-soft text-secondary' ?> px-3 py-1">
                                    <?= (int)$role['user_count'] ?> user
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-flex justify-content-end gap-2">
                                    <!-- Set Izin -->
                                    <a href="<?= url('/roles/permissions?id_peran=' . $role['id']) ?>"
                                       class="btn-action btn-action-warning"
                                       title="Atur Izin Role">
                                        <i class="bi bi-key-fill me-1"></i> Izin
                                    </a>
                                    <!-- Edit -->
                                    <a href="<?= url('/roles/form?id=' . $role['id']) ?>"
                                       class="btn-action btn-action-primary icon-only"
                                       title="Edit Nama Role">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <!-- Hapus (hanya jika user_count = 0 dan total role > 1) -->
                                    <?php if ((int)$role['user_count'] === 0 && count($roles) > 1): ?>
                                        <form action="<?= url('/roles/delete') ?>" method="POST" class="d-inline"
                                              data-confirm="Yakin hapus role «<?= e($role['name']) ?>»? Tindakan ini tidak dapat dibatalkan.">
                                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                            <input type="hidden" name="id" value="<?= $role['id'] ?>">
                                            <button type="submit" class="btn-action btn-action-danger icon-only" title="Hapus Role">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button type="button" class="btn-action btn-action-danger icon-only opacity-40"
                                                disabled title="<?= $role['user_count'] > 0 ? 'Tidak bisa dihapus — masih dipakai ' . (int)$role['user_count'] . ' user' : 'Minimal harus ada 1 role' ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
