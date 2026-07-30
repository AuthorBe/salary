<?php
/**
 * @var string $title
 * @var array $users
 */
?>

<div class="page-title-box d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
    <div class="page-title-left">
        <h2 class="h4 mb-1 fw-bold d-flex align-items-center">
            <i class="bi bi-person-badge-fill text-primary me-2 fs-4"></i> Manajemen User & Role
        </h2>
        <p class="text-muted mb-0 fs-7 ms-4 ps-1">Kelola data pengguna aplikasi dan atur peran beserta hak aksesnya</p>
    </div>
    <div class="page-title-right d-flex gap-2">
        <a href="<?= url('/roles') ?>" class="btn btn-outline-primary d-inline-flex align-items-center">
            <i class="bi bi-shield-lock-fill me-2"></i> Kelola Role
        </a>
        <a href="<?= url('/users/form') ?>" class="btn btn-primary d-inline-flex align-items-center">
            <i class="bi bi-person-plus-fill me-2"></i> Tambah User
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
                    <th scope="col" class="ps-4">NAMA</th>
                    <th scope="col">USERNAME</th>
                    <th scope="col">ROLE</th>
                    <th scope="col">STATUS</th>
                    <th scope="col" class="text-end pe-4">AKSI</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">Belum ada data user.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td class="ps-4 fw-medium text-dark">
                                <?= e($user['name']) ?>
                                <?php if ($user['id'] === 1): ?>
                                    <span class="badge bg-secondary ms-1">Utama</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e($user['nama_pengguna']) ?></td>
                            <td>
                                <?php if (!empty($user['superuser'])): ?>
                                    <span class="badge bg-primary rounded-pill fw-normal"><i class="bi bi-shield-lock-fill me-1"></i>Developer</span>
                                <?php else: ?>
                                    <span class="badge bg-info text-dark rounded-pill fw-normal"><?= e($user['role_name'] ?? '-') ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['aktif']): ?>
                                    <span class="badge bg-success-soft text-success rounded-pill px-2 py-1"><i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-soft text-danger rounded-pill px-2 py-1"><i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-flex justify-content-end gap-2">
                                <?php if (empty($user['superuser'])): ?>
                                    <a href="<?= url('/users/permissions?id=' . $user['id']) ?>" class="btn-action btn-action-warning" title="Hak Akses Khusus">
                                        <i class="bi bi-key-fill me-1"></i> Akses
                                    </a>
                                    <a href="<?= url('/users/form?id=' . $user['id']) ?>" class="btn-action btn-action-primary icon-only" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if ($user['id'] !== 1 && $user['id'] !== (int)($_SESSION['user_id'] ?? 0)): ?>
                                    <form action="<?= url('/users/delete') ?>" method="POST" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                        <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                        <button type="submit" class="btn-action btn-action-danger icon-only" title="Hapus" data-confirm="Yakin ingin menghapus user ini?">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted fst-italic fs-7">Restricted</span>
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
