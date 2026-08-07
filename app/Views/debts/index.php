<?php
/**
 * @var string $title
 * @var array $debts
 * @var array $summary
 * @var array $employees
 * @var int|null $selectedEmp
 * @var string|null $selectedStatus
 * @var array|null $selectedDebt
 * @var array $deductions
 */
?>

<!-- Page Header & Actions -->
<div class="page-title-box d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
    <div class="page-title-left">
        <h4 class="mb-1 text-dark fw-bold d-flex align-items-center">
            <i class="bi bi-credit-card-fill text-primary me-2 fs-4"></i> Kasbon & Pinjaman Karyawan
        </h4>
        <p class="text-muted mb-0 fs-7 ms-4 ps-1">Kelola pencatatan kasbon, batas potongan per periode, dan pantau riwayat cicilan.</p>
    </div>
    <div>
        <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalDebtForm">
            <i class="bi bi-plus-lg me-1"></i> Catat Kasbon Baru
        </button>
    </div>
</div>

<!-- Flash Messages -->
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

<!-- 3 Stat Cards -->
<div class="row g-3 mb-4">
    <!-- Stat 1: Total Sisa Kasbon Aktif -->
    <div class="col-xl-4 col-md-6">
        <div class="stat-card h-100">
            <div class="stat-icon" style="background: var(--danger-soft); color: var(--danger);">
                <i class="bi bi-wallet2"></i>
            </div>
            <div class="stat-info">
                <div class="stat-label">Sisa Kasbon Aktif</div>
                <div class="stat-value text-danger" title="<?= formatRupiah((int)$summary['total_active_remaining']) ?>">
                    <?= formatRupiah((int)$summary['total_active_remaining']) ?>
                </div>
                <div class="stat-sub">Belum lunas di sistem</div>
            </div>
        </div>
    </div>
    
    <!-- Stat 2: Total Cicilan Terbayar -->
    <div class="col-xl-4 col-md-6">
        <div class="stat-card h-100">
            <div class="stat-icon stat-icon-success">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div class="stat-info">
                <div class="stat-label">Cicilan Terbayar</div>
                <div class="stat-value text-success" title="<?= formatRupiah((int)$summary['total_paid_deductions']) ?>">
                    <?= formatRupiah((int)$summary['total_paid_deductions']) ?>
                </div>
                <div class="stat-sub">Total terpotong & dibayar</div>
            </div>
        </div>
    </div>
    
    <!-- Stat 3: Karyawan Berhutang -->
    <div class="col-xl-4 col-md-6">
        <div class="stat-card h-100">
            <div class="stat-icon stat-icon-info">
                <i class="bi bi-people-fill"></i>
            </div>
            <div class="stat-info">
                <div class="stat-label">Karyawan Berhutang</div>
                <div class="stat-value text-info">
                    <?= (int)$summary['active_debtors_count'] ?> <span class="fs-6 text-muted fw-normal">Orang</span>
                </div>
                <div class="stat-sub">Memiliki kasbon aktif</div>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Daftar Kasbon -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-0">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <h5 class="fw-bold text-dark mb-0">Daftar Kasbon Karyawan</h5>
            
            <!-- Filter Form -->
            <form method="GET" action="<?= url('/debts') ?>" class="d-flex flex-column flex-md-row gap-2">
                <select name="id_karyawan" class="form-select form-select-sm" style="min-width: 180px;">
                    <option value="">-- Semua Karyawan --</option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?= e($emp['id']) ?>" <?= $selectedEmp == $emp['id'] ? 'selected' : '' ?>>
                            <?= e($emp['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="status" class="form-select form-select-sm" style="min-width: 140px;">
                    <option value="">-- Semua Status --</option>
                    <option value="active" <?= $selectedStatus === 'active' ? 'selected' : '' ?>>Aktif</option>
                    <option value="paid_off" <?= $selectedStatus === 'paid_off' ? 'selected' : '' ?>>Lunas</option>
                </select>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary px-3">
                        <i class="bi bi-filter me-1"></i> Filter
                    </button>
                    <?php if ($selectedEmp || $selectedStatus): ?>
                        <a href="<?= url('/debts') ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    <div class="card-body p-4">
        <?php if (empty($debts)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                Belum ada data kasbon/hutang yang tercatat.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="ps-3" style="width: 50px;">NO</th>
                            <th scope="col">KARYAWAN</th>
                            <th scope="col">KETERANGAN</th>
                            <th scope="col" class="text-end">TOTAL</th>
                            <th scope="col" class="text-end">CICILAN / BLN</th>
                            <th scope="col" class="text-end">SISA</th>
                            <th scope="col" class="text-center">STATUS</th>
                            <th scope="col" class="text-end pe-3">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; foreach ($debts as $d): ?>
                            <tr>
                                <td class="ps-3 fw-semibold text-muted"><?= $no++ ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= e($d['employee_name']) ?></div>
                                    <small class="text-muted"><?= e(ucfirst($d['tipe_gaji'])) ?></small>
                                </td>
                                <td>
                                    <span class="fw-semibold text-dark"><?= e($d['keterangan'] ?: 'Kasbon') ?></span>
                                    <?php if ($d['catatan']): ?>
                                        <div class="small text-muted text-truncate" style="max-width: 200px;" title="<?= e($d['catatan']) ?>"><?= e($d['catatan']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-semibold text-dark"><?= formatRupiah((int)$d['total_nominal']) ?></td>
                                <td class="text-end text-muted"><?= formatRupiah((int)$d['potongan_bawaan']) ?></td>
                                <td class="text-end fw-bold <?= $d['sisa_nominal'] > 0 ? 'text-danger' : 'text-success' ?>">
                                    <?= formatRupiah((int)$d['sisa_nominal']) ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($d['status'] === 'active'): ?>
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-1 rounded-pill">
                                            Aktif
                                        </span>
                                    <?php elseif ($d['status'] === 'paid_off'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill">
                                            Lunas
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary border px-3 py-1 rounded-pill">
                                            Dibatalkan
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light border-0 rounded-circle text-muted" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" style="width: 32px; height: 32px;" title="Aksi">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 py-2 rounded-3 fs-7">
                                            <?php if ($d['status'] === 'active'): ?>
                                            <li>
                                                <button class="dropdown-item d-flex align-items-center text-success fw-medium btn-pay py-2" type="button"
                                                        data-id="<?= e($d['id']) ?>"
                                                        data-emp="<?= e($d['employee_name']) ?>"
                                                        data-remaining="<?= e($d['sisa_nominal']) ?>">
                                                    <i class="bi bi-cash me-2 fs-6"></i> Bayar Manual
                                                </button>
                                            </li>
                                            <?php endif; ?>
                                            <li>
                                                <button class="dropdown-item d-flex align-items-center text-warning fw-medium btn-edit-debt py-2" type="button" data-id="<?= e($d['id']) ?>">
                                                    <i class="bi bi-pencil me-2 fs-6"></i> Edit Kasbon
                                                </button>
                                            </li>
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center text-primary fw-medium py-2" href="<?= url('/debts?detail_id=' . $d['id']) ?>">
                                                    <i class="bi bi-clock-history me-2 fs-6"></i> Lihat Riwayat
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider opacity-25"></li>
                                            <li>
                                                <form action="<?= url('/debts/delete') ?>" method="POST">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="id" value="<?= e($d['id']) ?>">
                                                    <button type="submit" class="dropdown-item d-flex align-items-center text-danger fw-medium py-2" data-confirm="Yakin ingin menghapus catatan kasbon ini?">
                                                        <i class="bi bi-trash me-2 fs-6"></i> Hapus Data
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Form Kasbon Baru / Edit -->
<div class="modal fade" id="modalDebtForm" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom px-4 pt-4 pb-3">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center">
                    <i class="bi bi-wallet2 text-primary me-2"></i> <span id="modalDebtFormTitle">Form Kasbon</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div id="modalDebtFormPlaceholder">
                <div class="p-5 text-center text-muted">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Pembayaran Manual -->
<div class="modal fade" id="modalPayManual" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom px-4 pt-4 pb-3">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center">
                    <i class="bi bi-cash-coin text-success me-2"></i> Pembayaran Cicilan Manual
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= url('/debts/pay-manual') ?>" method="POST" id="formPayManual">
                <?= csrfField() ?>
                <input type="hidden" name="id_kasbon" id="pay_debt_id" value="">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label text-muted small mb-1">KARYAWAN</label>
                        <div id="pay_employee_name" class="fw-bold text-dark fs-5"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small mb-1">SISA HUTANG SAAT INI</label>
                        <div id="pay_remaining_text" class="fw-bold text-danger fs-5"></div>
                    </div>
                    <div class="mb-3">
                        <label for="pay_amount" class="form-label required">Nominal Pembayaran (Rp)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light fw-bold text-muted">Rp</span>
                            <input type="text" name="nominal" id="pay_amount" class="form-control input-rupiah enter-nav" placeholder="100.000" required>
                        </div>
                        <small class="text-danger d-none mt-1" id="pay_error_msg">Nominal melebihi sisa hutang!</small>
                    </div>
                    <div class="mb-3">
                        <label for="pay_date" class="form-label required">Tanggal Pembayaran</label>
                        <input type="date" name="tanggal_potongan" id="pay_date" class="form-control enter-nav" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-0">
                        <label for="pay_notes" class="form-label">Catatan / Keterangan</label>
                        <input type="text" name="catatan" id="pay_notes" class="form-control enter-nav" value="Pembayaran tunai/manual">
                    </div>
                </div>
                <div class="modal-footer bg-light px-4 py-3 border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success px-4">
                        <i class="bi bi-check-lg me-1"></i> Simpan Pembayaran
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal / Offcanvas Riwayat Cicilan (Jika selectedDebt terisi) -->
<?php if ($selectedDebt): ?>
<div class="modal fade show" id="modalDeductionHistory" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom px-4 pt-4 pb-3">
                <div>
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center">
                        <i class="bi bi-clock-history text-primary me-2"></i> Riwayat Cicilan / Potongan Kasbon
                    </h5>
                    <div class="small text-muted mt-1">
                        Karyawan: <strong class="text-dark"><?= e($selectedDebt['employee_name']) ?></strong> | 
                        Kasbon: <strong><?= e($selectedDebt['keterangan'] ?: 'Kasbon') ?></strong>
                    </div>
                </div>
                <a href="<?= url('/debts') ?>" class="btn-close"></a>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3 mb-4 bg-light p-3 rounded-3 border">
                    <div class="col-md-4">
                        <small class="text-muted d-block">TOTAL PINJAMAN</small>
                        <strong class="text-dark fs-5"><?= formatRupiah((int)$selectedDebt['total_nominal']) ?></strong>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">POTONGAN / PERIODE</small>
                        <strong class="text-dark fs-5"><?= formatRupiah((int)$selectedDebt['potongan_bawaan']) ?></strong>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">SISA HUTANG</small>
                        <strong class="<?= $selectedDebt['sisa_nominal'] > 0 ? 'text-danger' : 'text-success' ?> fs-5">
                            <?= formatRupiah((int)$selectedDebt['sisa_nominal']) ?>
                        </strong>
                    </div>
                </div>

                <h6 class="fw-bold text-dark mb-3">Log Transaksi Pemotongan</h6>
                <?php if (empty($deductions)): ?>
                    <div class="text-center py-4 text-muted bg-light rounded-3">Belum ada transaksi potongan atau pembayaran yang dicatat.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>TANGGAL</th>
                                    <th>NOMINAL</th>
                                    <th>METODE</th>
                                    <th>CATATAN</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($deductions as $ded): ?>
                                    <tr>
                                        <td><i class="bi bi-calendar-event me-1 text-muted"></i> <?= e(formatTanggal($ded['tanggal_potongan'])) ?></td>
                                        <td class="fw-bold text-success">- <?= formatRupiah((int)$ded['nominal']) ?></td>
                                        <td>
                                            <?php if ($ded['type'] === 'payroll'): ?>
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Otomatis Payroll</span>
                                            <?php else: ?>
                                                <span class="badge bg-info-subtle text-info border border-info-subtle">Tunai / Manual</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-muted"><?= e($ded['catatan'] ?: '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer bg-light px-4 py-3 border-top">
                <a href="<?= url('/debts') ?>" class="btn btn-secondary px-4">Tutup</a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tombol Bayar Manual
    document.querySelectorAll('.btn-pay').forEach(btn => {
        btn.addEventListener('click', function() {
            const debtId = this.getAttribute('data-id');
            const empName = this.getAttribute('data-emp');
            const remaining = parseInt(this.getAttribute('data-remaining'));
            
            document.getElementById('pay_debt_id').value = debtId;
            document.getElementById('pay_employee_name').innerText = empName;
            document.getElementById('pay_remaining_text').innerText = 'Rp ' + Number(remaining).toLocaleString('id-ID');
            document.getElementById('pay_amount').setAttribute('data-max', remaining);
            document.getElementById('pay_amount').value = '';
            document.getElementById('pay_error_msg').classList.add('d-none');
            
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPayManual')).show();
        });
    });

    // Validasi form Bayar Manual
    document.getElementById('formPayManual')?.addEventListener('submit', function(e) {
        const inputAmount = document.getElementById('pay_amount');
        const max = parseInt(inputAmount.getAttribute('data-max'));
        const val = parseInt(inputAmount.value.replace(/\./g, ''));
        
        if (val > max) {
            e.preventDefault();
            document.getElementById('pay_error_msg').classList.remove('d-none');
            inputAmount.classList.add('is-invalid');
            inputAmount.focus();
        } else {
            document.getElementById('pay_error_msg').classList.add('d-none');
            inputAmount.classList.remove('is-invalid');
        }
    });

    // Fungsi Fetch Modal Form
    function fetchDebtForm(id = '') {
        const placeholder = document.getElementById('modalDebtFormPlaceholder');
        const title = document.getElementById('modalDebtFormTitle');
        
        title.innerText = id ? 'Edit Kasbon' : 'Catat Kasbon Baru';
        placeholder.innerHTML = '<div class="p-5 text-center text-muted"><div class="spinner-border text-primary" role="status"></div></div>';
        
        fetch('<?= url("/debts/form") ?>' + (id ? '?id=' + id : ''))
            .then(res => res.text())
            .then(html => {
                placeholder.innerHTML = html;
                // Init format rupiah jika ada di partials
                if (typeof initRupiahInput === 'function') initRupiahInput();
                // Trigger keyboard nav UI injection (karena ini pakai fetch manual, bukan htmx)
                if (typeof window.initKeyboardNavUI === 'function') window.initKeyboardNavUI();
            })
            .catch(err => {
                placeholder.innerHTML = '<div class="p-4 text-center text-danger">Gagal memuat form.</div>';
            });
    }

    // Tombol Edit
    document.querySelectorAll('.btn-edit-debt').forEach(btn => {
        btn.addEventListener('click', function() {
            fetchDebtForm(this.getAttribute('data-id'));
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDebtForm')).show();
        });
    });

    // Tombol Catat Kasbon Baru (override data-bs-toggle default event to ensure fresh fetch)
    const btnNew = document.querySelector('[data-bs-target="#modalDebtForm"]');
    if (btnNew) {
        btnNew.addEventListener('click', function() {
            fetchDebtForm();
        });
    }
});
</script>
