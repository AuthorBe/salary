
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        <i class="bi bi-cash-stack text-primary me-2"></i> Penarikan Gaji Bulanan
    </h1>
    <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addPenarikanModal">
        <i class="bi bi-plus-circle me-1"></i> Catat Penarikan
    </button>
</div>


<?php if (isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success d-flex align-items-center rounded-4 border-0 shadow-sm mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2 fs-4"></i>
        <div><?= e($_SESSION['flash_success']) ?></div>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger d-flex align-items-center rounded-4 border-0 shadow-sm mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2 fs-4"></i>
        <div><?= e($_SESSION['flash_error']) ?></div>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<div class="alert alert-info border-0 shadow-sm rounded-4 mb-4" role="alert">
    <div class="d-flex align-items-start">
        <i class="bi bi-info-circle-fill text-info fs-4 me-3"></i>
        <div>
            <h6 class="alert-heading fw-bold mb-1">Informasi Status Penarikan</h6>
            <p class="mb-1 text-secondary">
                <span class="badge bg-warning-subtle text-warning me-1">Belum di Payroll</span> 
                Penarikan ini <strong>akan dipotong secara otomatis</strong> dari gaji karyawan pada saat pembuatan slip gaji (Generate Payroll) berikutnya.
            </p>
            <p class="mb-0 text-secondary">
                <span class="badge bg-success-subtle text-success me-1">Sudah di Payroll</span> 
                Penarikan ini <strong>telah selesai dipotong</strong> dari gaji karyawan pada slip gaji yang tercantum.
            </p>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 pb-2">
            <form method="GET" action="<?= url('/payroll/penarikan') ?>" class="d-flex flex-wrap align-items-center gap-2 m-0">
                
                <!-- Tanggal Mulai -->
                <div class="input-group input-group-sm" style="width: auto;">
                    <span class="input-group-text bg-light text-muted border-end-0 rounded-start-pill px-3">Dari</span>
                    <input type="date" name="start_date" class="form-control rounded-end-pill border-start-0" value="<?= htmlspecialchars($startDate ?? '') ?>" title="Tanggal Mulai">
                </div>
                
                <!-- Tanggal Akhir -->
                <div class="input-group input-group-sm" style="width: auto;">
                    <span class="input-group-text bg-light text-muted border-end-0 rounded-start-pill px-3">Sampai</span>
                    <input type="date" name="end_date" class="form-control rounded-end-pill border-start-0" value="<?= htmlspecialchars($endDate ?? '') ?>" title="Tanggal Akhir">
                </div>
                
                <!-- Status -->
                <select name="status" class="form-select form-select-sm rounded-pill px-3" style="width: auto; min-width: 150px;">
                    <option value="">Semua Status</option>
                    <option value="pending" <?= ($statusFilter ?? '') === 'pending' ? 'selected' : '' ?>>Belum di Payroll</option>
                    <option value="applied" <?= ($statusFilter ?? '') === 'applied' ? 'selected' : '' ?>>Sudah di Payroll</option>
                </select>
                
                <!-- Tombol -->
                <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4 shadow-sm">
                    <i class="bi bi-funnel-fill me-1"></i> Filter
                </button>
                
                <?php if (!empty($startDate) || !empty($endDate) || !empty($statusFilter)): ?>
                    <a href="<?= url('/payroll/penarikan') ?>" class="btn btn-sm btn-light rounded-pill px-3 border shadow-sm">
                        <i class="bi bi-x-circle me-1"></i> Reset
                    </a>
                <?php endif; ?>
            </form>

            <!-- Export PDF -->
            <div>
                <?php
                    $exportUrl = url('/payroll/penarikan/export');
                    $queryParams = [];
                    if (!empty($startDate)) $queryParams['start_date'] = $startDate;
                    if (!empty($endDate)) $queryParams['end_date'] = $endDate;
                    if (!empty($statusFilter)) $queryParams['status'] = $statusFilter;
                    
                    if (!empty($queryParams)) {
                        $exportUrl .= '?' . http_build_query($queryParams);
                    }
                ?>
                <a href="<?= $exportUrl ?>" class="btn btn-sm btn-danger rounded-pill px-4 shadow-sm">
                    <i class="bi bi-file-earmark-pdf-fill me-1"></i> Export PDF
                </a>
            </div>
        </div>
    </div>
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="border-0 rounded-start">Karyawan</th>
                        <th class="border-0">Tanggal Penarikan</th>
                        <th class="border-0 text-end">Nominal</th>
                        <th class="border-0">Keterangan</th>
                        <th class="border-0">Status</th>
                        <th class="border-0 rounded-end text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($penarikan)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">Belum ada data penarikan gaji.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($penarikan as $p): ?>
                        <tr>
                            <td class="fw-medium">
                                <i class="bi bi-person-circle text-secondary me-2"></i>
                                <?= htmlspecialchars($p['employee_name']) ?>
                            </td>
                            <td><?= date('d M Y', strtotime($p['tanggal'])) ?></td>
                            <td class="text-end fw-bold text-danger">Rp <?= number_format((float)$p['nominal'], 0, ',', '.') ?></td>
                            <td>
                                <?= htmlspecialchars($p['keterangan'] ?: '-') ?>
                            </td>
                            <td>
                                <?php if ($p['id_penggajian']): ?>
                                    <span class="badge bg-success-subtle text-success rounded-pill px-3" title="Sudah dipotong pada penggajian #<?= $p['id_penggajian'] ?>">
                                        <i class="bi bi-check-circle me-1"></i> Sudah di Payroll
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning rounded-pill px-3" title="Menunggu dipotong pada penggajian berikutnya">
                                        <i class="bi bi-hourglass-split me-1"></i> Belum di Payroll
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if (!$p['id_penggajian']): ?>
                                <form action="<?= url('/payroll/penarikan/destroy') ?>" method="POST" class="d-inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill" data-confirm="Apakah Anda yakin ingin menghapus data penarikan ini?">
                                        <i class="bi bi-trash"></i> Hapus
                                    </button>
                                </form>
                                <?php else: ?>
                                    <small class="text-muted"><i class="bi bi-lock-fill"></i> Terkunci</small>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Penarikan -->
<div class="modal fade" id="addPenarikanModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable">
        <form action="<?= url('/payroll/penarikan/store') ?>" method="POST" class="modal-content border-0 shadow">
            <?= csrfField() ?>
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold">Catat Penarikan Gaji</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pb-2">
                <div class="alert alert-info border-0 bg-info-subtle text-info-emphasis rounded-3 d-flex align-items-start mb-4">
                    <i class="bi bi-info-circle-fill me-2 mt-1"></i>
                    <div>
                        Batas maksimal penarikan dihitung dari: <br><strong>Gaji Pokok + Tunjangan Bulanan + Total Kehadiran</strong><br>bulan berjalan.
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-medium">Karyawan Bulanan</label>
                    <select name="id_karyawan" class="form-select searchable-select rounded-3" required>
                        <option value="">-- Pilih Karyawan --</option>
                        <?= renderEmployeeOptions($karyawan) ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-medium">Tanggal Penarikan</label>
                    <input type="date" name="tanggal" class="form-control rounded-3" value="<?= date('Y-m-d') ?>" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-medium">Nominal Penarikan</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">Rp</span>
                        <input type="text" name="nominal" class="form-control border-start-0 ps-0 text-end fw-bold text-primary input-rupiah" placeholder="0" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-medium">Keterangan <small class="text-muted">(Opsional)</small></label>
                    <textarea name="keterangan" class="form-control rounded-3" rows="2" placeholder="Cth: Keperluan mendadak keluarga"></textarea>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Simpan Data</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    initRupiahInputs();
});
</script>
