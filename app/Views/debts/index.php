<?php
/**
 * @var string $title
 * @var array $summary
 * @var array $bulanan
 * @var array $borongan
 * @var array $activeEmployees
 */
?>

<!-- Page Header -->
<div class="page-title-box d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
    <div class="page-title-left">
        <h4 class="mb-1 text-dark fw-bold d-flex align-items-center">
            <i class="bi bi-credit-card-fill text-primary me-2 fs-4"></i> Kasbon & Pinjaman Karyawan
        </h4>
        <p class="text-muted mb-0 fs-7 ms-4 ps-1">Kelola kasbon per karyawan, pantau sisa pinjaman, dan catat cicilan/pembayaran.</p>
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
    <div class="col-12 col-md-4">
        <div class="stat-card h-100">
            <div class="stat-icon" style="background: var(--danger-soft, #fee2e2); color: var(--danger, #ef4444);">
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
    <div class="col-12 col-md-4">
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
    <div class="col-12 col-md-4">
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

<!-- Search Bar -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-2 p-sm-3">
        <div class="input-group">
            <span class="input-group-text bg-transparent border-0 text-muted ps-3">
                <i class="bi bi-search fs-5"></i>
            </span>
            <input type="text" id="debtSearchInput" class="form-control border-0 shadow-none ps-2" 
                   placeholder="Ketik nama karyawan untuk mencari dengan cepat..." 
                   oninput="filterDebtTables(this.value)">
            <button class="btn btn-link text-muted pe-3 text-decoration-none d-none" id="btnClearSearch" onclick="clearDebtSearch()">
                <i class="bi bi-x-circle-fill"></i>
            </button>
        </div>
    </div>
</div>

<!-- Tabel Karyawan Bulanan -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold text-dark mb-0 d-flex align-items-center">
            <span class="badge bg-primary-subtle text-primary me-2 px-2 py-1 fs-7 rounded-3">Bulanan</span>
            Karyawan Bulanan
        </h5>
        <span class="text-muted fs-7"><span id="count-bulanan"><?= count($bulanan) ?></span> Karyawan</span>
    </div>
    <div class="card-body p-3 p-sm-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="table-bulanan">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3 text-nowrap" style="width: 5%;">NO</th>
                        <th class="text-nowrap" style="width: 28%;">NAMA KARYAWAN</th>
                        <th class="text-center text-nowrap" style="width: 18%;">STATUS KASBON</th>
                        <th class="text-end text-nowrap" style="width: 22%;">TOTAL SISA KASBON (Rp)</th>
                        <th class="text-center text-nowrap" style="width: 27%;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bulanan)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada data karyawan bulanan.</td></tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($bulanan as $k): ?>
                            <?php 
                                $sisa = (float)$k['total_sisa'];
                                $activeCount = (int)$k['active_debts_count'];
                                $totalCount = (int)$k['total_debts_count'];
                            ?>
                            <tr id="row-debt-<?= $k['id'] ?>" class="debt-emp-row" data-name="<?= strtolower(e($k['name'])) ?>">
                                <td class="ps-3 text-muted row-number"><?= $no++ ?></td>
                                <td>
                                    <div class="fw-bold text-dark emp-name"><?= e($k['name']) ?></div>
                                    <small class="text-muted">Bulanan</small>
                                </td>
                                <td class="text-center text-nowrap">
                                    <?php if ($activeCount > 0): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1 rounded-pill">
                                            <?= $activeCount ?> Kasbon Aktif
                                        </span>
                                    <?php elseif ($totalCount > 0): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill">
                                            <i class="bi bi-check-lg me-1"></i> Lunas
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border px-3 py-1 rounded-pill">
                                            Bebas Kasbon
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-bold text-nowrap <?= $sisa > 0 ? 'text-danger' : 'text-muted' ?>">
                                    <?= formatRupiah((int)$sisa) ?>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1 text-nowrap">
                                        <!-- Riwayat / Detail Kasbon -->
                                        <button class="btn btn-sm btn-outline-secondary rounded-pill px-2 px-sm-3" 
                                                onclick="openHistoryModal(<?= $k['id'] ?>)" 
                                                title="Lihat riwayat kasbon & cicilan">
                                            <i class="bi bi-clock-history"></i> <span class="d-none d-md-inline">Riwayat</span>
                                        </button>
                                        
                                        <!-- Catat Kasbon Baru untuk Karyawan Ini (Merah) -->
                                        <button class="btn btn-sm btn-outline-danger rounded-pill px-2 px-sm-3" 
                                                onclick="openNewDebtModal(<?= $k['id'] ?>)" 
                                                title="Catat kasbon baru untuk karyawan ini">
                                            <i class="bi bi-plus-lg"></i> <span class="d-none d-md-inline">Kasbon</span>
                                        </button>
                                        
                                        <!-- Bayar Kasbon -->
                                        <?php if ($activeCount > 0): ?>
                                            <button class="btn btn-sm btn-outline-success rounded-pill px-2 px-sm-3" 
                                                    onclick="openQuickPayModal(<?= $k['id'] ?>, '<?= e(addslashes($k['name'])) ?>')" 
                                                    title="Bayar cicilan kasbon manual">
                                                <i class="bi bi-cash-coin"></i> <span class="d-none d-md-inline">Bayar</span>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-light text-muted rounded-pill px-2 px-sm-3 opacity-50" disabled title="Tidak ada kasbon aktif">
                                                <i class="bi bi-cash-coin"></i> <span class="d-none d-md-inline">Bayar</span>
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
</div>

<!-- Tabel Karyawan Borongan -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold text-dark mb-0 d-flex align-items-center">
            <span class="badge bg-warning-subtle text-warning border border-warning-subtle me-2 px-2 py-1 fs-7 rounded-3">Borongan</span>
            Karyawan Borongan
        </h5>
        <span class="text-muted fs-7"><span id="count-borongan"><?= count($borongan) ?></span> Karyawan</span>
    </div>
    <div class="card-body p-3 p-sm-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="table-borongan">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3 text-nowrap" style="width: 5%;">NO</th>
                        <th class="text-nowrap" style="width: 28%;">NAMA KARYAWAN</th>
                        <th class="text-center text-nowrap" style="width: 18%;">STATUS KASBON</th>
                        <th class="text-end text-nowrap" style="width: 22%;">TOTAL SISA KASBON (Rp)</th>
                        <th class="text-center text-nowrap" style="width: 27%;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($borongan)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada data karyawan borongan.</td></tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($borongan as $k): ?>
                            <?php 
                                $sisa = (float)$k['total_sisa'];
                                $activeCount = (int)$k['active_debts_count'];
                                $totalCount = (int)$k['total_debts_count'];
                            ?>
                            <tr id="row-debt-<?= $k['id'] ?>" class="debt-emp-row" data-name="<?= strtolower(e($k['name'])) ?>">
                                <td class="ps-3 text-muted row-number"><?= $no++ ?></td>
                                <td>
                                    <div class="fw-bold text-dark emp-name"><?= e($k['name']) ?></div>
                                    <small class="text-muted">Borongan</small>
                                </td>
                                <td class="text-center text-nowrap">
                                    <?php if ($activeCount > 0): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1 rounded-pill">
                                            <?= $activeCount ?> Kasbon Aktif
                                        </span>
                                    <?php elseif ($totalCount > 0): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill">
                                            <i class="bi bi-check-lg me-1"></i> Lunas
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border px-3 py-1 rounded-pill">
                                            Bebas Kasbon
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-bold text-nowrap <?= $sisa > 0 ? 'text-danger' : 'text-muted' ?>">
                                    <?= formatRupiah((int)$sisa) ?>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1 text-nowrap">
                                        <!-- Riwayat / Detail Kasbon -->
                                        <button class="btn btn-sm btn-outline-secondary rounded-pill px-2 px-sm-3" 
                                                onclick="openHistoryModal(<?= $k['id'] ?>)" 
                                                title="Lihat riwayat kasbon & cicilan">
                                            <i class="bi bi-clock-history"></i> <span class="d-none d-md-inline">Riwayat</span>
                                        </button>
                                        
                                        <!-- Catat Kasbon Baru untuk Karyawan Ini (Merah) -->
                                        <button class="btn btn-sm btn-outline-danger rounded-pill px-2 px-sm-3" 
                                                onclick="openNewDebtModal(<?= $k['id'] ?>)" 
                                                title="Catat kasbon baru untuk karyawan ini">
                                            <i class="bi bi-plus-lg"></i> <span class="d-none d-md-inline">Kasbon</span>
                                        </button>
                                        
                                        <!-- Bayar Kasbon -->
                                        <?php if ($activeCount > 0): ?>
                                            <button class="btn btn-sm btn-outline-success rounded-pill px-2 px-sm-3" 
                                                    onclick="openQuickPayModal(<?= $k['id'] ?>, '<?= e(addslashes($k['name'])) ?>')" 
                                                    title="Bayar cicilan kasbon manual">
                                                <i class="bi bi-cash-coin"></i> <span class="d-none d-md-inline">Bayar</span>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-light text-muted rounded-pill px-2 px-sm-3 opacity-50" disabled title="Tidak ada kasbon aktif">
                                                <i class="bi bi-cash-coin"></i> <span class="d-none d-md-inline">Bayar</span>
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
</div>

<!-- ========================================================================= -->
<!-- MODAL: Form Catat / Edit Kasbon (Static Backdrop & Keyboard Disabled) -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalDebtForm" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden" id="modalDebtFormPlaceholder" style="max-height: 90vh; display: flex; flex-direction: column;">
            <div class="p-5 text-center text-muted">
                <div class="spinner-border text-danger" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: Riwayat & Rincian Kasbon Karyawan (Static Backdrop & Keyboard Disabled) -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalDebtHistory" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden" style="max-height: 90vh; display: flex; flex-direction: column;">
            <div class="modal-header border-bottom px-3 px-sm-4 py-3 flex-shrink-0">
                <div class="d-flex align-items-center justify-content-between w-100">
                    <div>
                        <h5 class="modal-title fw-bold text-dark d-flex align-items-center mb-1">
                            <i class="bi bi-clock-history text-primary me-2"></i> Rincian & Riwayat Kasbon
                        </h5>
                        <div class="small text-muted d-flex align-items-center gap-2">
                            <span>Karyawan: <strong class="text-dark" id="hist_emp_name">-</strong></span>
                            <span class="badge bg-secondary-subtle text-secondary" id="hist_emp_type">-</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close align-self-start" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-3 p-sm-4 overflow-y-auto" style="min-height: 0; flex: 1 1 auto; max-height: calc(90vh - 130px);">
                <!-- 3 Top Stats inside Modal -->
                <div class="row g-2 g-sm-3 mb-4">
                    <div class="col-12 col-sm-4">
                        <div class="p-3 bg-light rounded-4 border h-100">
                            <small class="text-muted d-block fw-semibold mb-1">TOTAL PINJAMAN</small>
                            <h5 class="fw-bold text-dark mb-0" id="hist_stat_pinjaman">Rp 0</h5>
                            <small class="text-muted" style="font-size: 0.75rem;">Semua kasbon pernah dibuat</small>
                        </div>
                    </div>
                    <div class="col-12 col-sm-4">
                        <div class="p-3 bg-light rounded-4 border h-100">
                            <small class="text-muted d-block fw-semibold mb-1">TOTAL TERBAYAR</small>
                            <h5 class="fw-bold text-success mb-0" id="hist_stat_terbayar">Rp 0</h5>
                            <small class="text-muted" style="font-size: 0.75rem;">Cicilan payroll & tunai</small>
                        </div>
                    </div>
                    <div class="col-12 col-sm-4">
                        <div class="p-3 bg-light rounded-4 border h-100">
                            <small class="text-muted d-block fw-semibold mb-1">SISA KASBON SAAT INI</small>
                            <h5 class="fw-bold text-danger mb-0" id="hist_stat_sisa">Rp 0</h5>
                            <small class="text-muted" style="font-size: 0.75rem;" id="hist_stat_active_count">0 Kasbon aktif</small>
                        </div>
                    </div>
                </div>

                <!-- Debts List Section Header & Add Button -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Daftar Pinjaman / Kasbon</h6>
                        <span class="text-muted small d-none d-sm-inline">Klik pada kasbon untuk melihat log cicilan</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-danger rounded-pill px-3 py-1 fw-bold shadow-xs d-flex align-items-center" id="hist_btn_add_debt">
                        <i class="bi bi-plus-lg me-1"></i> Tambah Kasbon
                    </button>
                </div>

                <div id="hist_debts_container">
                    <div class="text-center py-5 text-muted">
                        <div class="spinner-border text-primary" role="status"></div>
                        <div class="mt-2">Memuat rincian kasbon...</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light px-3 px-sm-4 py-3 border-top flex-shrink-0">
                <button type="button" class="btn btn-outline-secondary px-4 rounded-pill w-100 w-sm-auto" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: Pembayaran Cicilan Manual (Static Backdrop & Keyboard Disabled) -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalPayManual" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden" style="max-height: 90vh; display: flex; flex-direction: column;">
            <div class="modal-header border-bottom px-3 px-sm-4 pt-3 pt-sm-4 pb-3 flex-shrink-0">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center mb-0">
                    <i class="bi bi-cash-coin text-success me-2"></i> Pembayaran Cicilan Kasbon
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formPayManual" onsubmit="submitPayManual(event)" class="d-flex flex-column flex-grow-1" style="min-height: 0; height: 100%;">
                <?= csrfField() ?>
                <div class="modal-body p-3 p-sm-4 overflow-y-auto" style="min-height: 0; flex: 1 1 auto; max-height: calc(90vh - 135px);">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold mb-1">KARYAWAN</label>
                        <div id="pay_employee_name" class="fw-bold text-dark fs-5"></div>
                    </div>

                    <!-- Pilih Kasbon (jika ada lebih dari 1) -->
                    <div class="mb-3">
                        <label for="pay_debt_id" class="form-label required fw-bold small text-muted">Pilih Kasbon yang Dibayar</label>
                        <select name="id_kasbon" id="pay_debt_id" class="form-select enter-nav" required onchange="onPayDebtChange(this)">
                            <option value="">-- Pilih Kasbon --</option>
                        </select>
                    </div>

                    <!-- Sisa Hutang Kasbon Terpilih -->
                    <div class="mb-3 p-3 bg-light rounded-3 border">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small fw-semibold">Sisa Hutang Kasbon Ini:</span>
                            <span id="pay_remaining_text" class="fw-bold text-danger fs-6">Rp 0</span>
                        </div>
                    </div>

                    <!-- Nominal Pembayaran + Quick Fill Buttons -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-1">
                            <label for="pay_amount" class="form-label required fw-bold small text-muted mb-0">Nominal Pembayaran (Rp)</label>
                            <div class="d-flex gap-1" id="pay_quick_buttons">
                                <!-- Dynamic Quick Fill Badges -->
                            </div>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-light fw-bold text-muted">Rp</span>
                            <input type="text" name="nominal" id="pay_amount" class="form-control input-rupiah enter-nav fw-bold fs-6" 
                                   placeholder="0" required autocomplete="off"
                                   oninput="formatRupiahInput(this); validatePayAmount()">
                        </div>
                        <div class="text-danger small mt-1 d-none" id="pay_error_msg">
                            <i class="bi bi-exclamation-triangle"></i> Nominal pembayaran melebihi sisa hutang!
                        </div>
                    </div>

                    <!-- Tanggal Pembayaran -->
                    <div class="mb-3">
                        <label for="pay_date" class="form-label required fw-bold small text-muted">Tanggal Pembayaran</label>
                        <input type="date" name="tanggal_potongan" id="pay_date" class="form-control enter-nav" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <!-- Catatan -->
                    <div class="mb-0">
                        <label for="pay_notes" class="form-label fw-bold small text-muted">Catatan (Opsional)</label>
                        <input type="text" name="catatan" id="pay_notes" class="form-control enter-nav" value="Pembayaran tunai/manual">
                    </div>
                </div>
                <div class="modal-footer bg-light px-3 px-sm-4 py-3 border-top d-flex justify-content-end gap-2 flex-shrink-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success px-4 rounded-pill fw-bold" id="btnSubmitPay">
                        <i class="bi bi-check-lg me-1"></i> Simpan Pembayaran
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// =========================================================================
// Formatting & CSRF Utilities
// =========================================================================
const APP_CSRF_TOKEN = '<?= generateCsrfToken() ?>';

function formatRupiahJS(angka) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
}

function formatRupiahInput(input) {
    let value = input.value.replace(/[^,\d]/g, '').toString();
    let split = value.split(',');
    let sisa = split[0].length % 3;
    let rupiah = split[0].substr(0, sisa);
    let ribuan = split[0].substr(sisa).match(/\d{3}/gi);
    
    if (ribuan) {
        let separator = sisa ? '.' : '';
        rupiah += separator + ribuan.join('.');
    }
    
    input.value = rupiah;
}

function syncDefaultDeduction(totalInput) {
    let potInput = document.getElementById('potongan_bawaan');
    if (potInput && (!potInput.value || potInput.value === '0')) {
        potInput.value = totalInput.value;
    }
}

function addslashes(str) {
    return (str + '').replace(/[\\"']/g, '\\$&').replace(/\u0000/g, '\\0');
}

// Modal instance helper with static backdrop & keyboard disabled
function getModalInstance(elementId) {
    let elem = document.getElementById(elementId);
    return bootstrap.Modal.getOrCreateInstance(elem, {
        backdrop: 'static',
        keyboard: false
    });
}

// Handler for CSRF and network errors
function handleAjaxError(err, customMessage = 'Gagal menghubungi server.') {
    Swal.fire({
        title: 'Error!',
        text: customMessage,
        icon: 'error',
        allowOutsideClick: false,
        allowEscapeKey: false
    });
}

// =========================================================================
// Realtime Live Search Filter
// =========================================================================
function filterDebtTables(keyword) {
    keyword = keyword.toLowerCase().trim();
    let btnClear = document.getElementById('btnClearSearch');
    
    if (keyword.length > 0) {
        btnClear.classList.remove('d-none');
    } else {
        btnClear.classList.add('d-none');
    }
    
    let filterTable = function(tableId, countId) {
        let table = document.getElementById(tableId);
        if (!table) return;
        let rows = table.querySelectorAll('tbody tr.debt-emp-row');
        let visibleCount = 0;
        
        rows.forEach(row => {
            let name = row.getAttribute('data-name') || '';
            if (name.includes(keyword)) {
                row.classList.remove('d-none');
                visibleCount++;
                let rowNum = row.querySelector('.row-number');
                if (rowNum) rowNum.innerText = visibleCount;
            } else {
                row.classList.add('d-none');
            }
        });
        
        let countElem = document.getElementById(countId);
        if (countElem) countElem.innerText = visibleCount;
    };
    
    filterTable('table-bulanan', 'count-bulanan');
    filterTable('table-borongan', 'count-borongan');
}

function clearDebtSearch() {
    let input = document.getElementById('debtSearchInput');
    input.value = '';
    filterDebtTables('');
    input.focus();
}

// =========================================================================
// Modal Catat / Edit Kasbon
// =========================================================================
function openNewDebtModal(employeeId = null) {
    let placeholder = document.getElementById('modalDebtFormPlaceholder');
    placeholder.innerHTML = '<div class="p-5 text-center text-muted"><div class="spinner-border text-danger" role="status"></div><div class="mt-2">Memuat form...</div></div>';
    
    let url = '<?= url("/debts/form") ?>' + (employeeId ? '?id_karyawan=' + employeeId : '');
    
    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(res => res.text())
        .then(html => {
            placeholder.innerHTML = html;
            let modal = getModalInstance('modalDebtForm');
            modal.show();
        })
        .catch(err => {
            placeholder.innerHTML = '<div class="p-4 text-center text-danger">Gagal memuat form.</div>';
        });
}

function openEditDebtModal(debtId) {
    let placeholder = document.getElementById('modalDebtFormPlaceholder');
    placeholder.innerHTML = '<div class="p-5 text-center text-muted"><div class="spinner-border text-danger" role="status"></div><div class="mt-2">Memuat form...</div></div>';
    
    // Close history modal temporarily if open
    let histModal = bootstrap.Modal.getInstance(document.getElementById('modalDebtHistory'));
    if (histModal) histModal.hide();
    
    fetch('<?= url("/debts/form?id=") ?>' + debtId, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(res => res.text())
        .then(html => {
            placeholder.innerHTML = html;
            let modal = getModalInstance('modalDebtForm');
            modal.show();
        })
        .catch(err => {
            placeholder.innerHTML = '<div class="p-4 text-center text-danger">Gagal memuat form.</div>';
        });
}

async function submitDebtForm(e) {
    e.preventDefault();
    let form = e.target;
    let btn = document.getElementById('btnSubmitDebt');
    btn.disabled = true;
    
    let formData = new FormData(form);
    if (!formData.has('csrf_token')) {
        formData.append('csrf_token', APP_CSRF_TOKEN);
    }
    
    try {
        let response = await fetch('<?= url("/debts/store") ?>', {
            method: 'POST',
            body: formData,
            headers: { 
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': APP_CSRF_TOKEN
            }
        });
        
        let res = await response.json();
        if (res.success) {
            Swal.fire({
                title: 'Berhasil!',
                text: res.message,
                icon: 'success',
                timer: 1500,
                showConfirmButton: false,
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then(() => {
                location.reload();
            });
        } else {
            btn.disabled = false;
            if (res.csrf_error) {
                Swal.fire({
                    title: 'Sesi Kedaluwarsa',
                    text: res.message,
                    icon: 'warning',
                    confirmButtonText: 'Muat Ulang Halaman',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                }).then(() => location.reload());
            } else {
                Swal.fire({
                    title: 'Gagal!',
                    text: res.message || 'Terjadi kesalahan sistem',
                    icon: 'error',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                });
            }
        }
    } catch (err) {
        btn.disabled = false;
        handleAjaxError(err);
    }
}

// =========================================================================
// Modal Riwayat / Detail Kasbon Karyawan
// =========================================================================
let currentActiveHistoryEmpId = 0;

async function openHistoryModal(employeeId) {
    currentActiveHistoryEmpId = employeeId;
    let container = document.getElementById('hist_debts_container');
    container.innerHTML = '<div class="text-center py-5 text-muted"><div class="spinner-border text-primary" role="status"></div><div class="mt-2">Memuat rincian kasbon...</div></div>';
    
    document.getElementById('hist_btn_add_debt').onclick = function() {
        bootstrap.Modal.getInstance(document.getElementById('modalDebtHistory')).hide();
        openNewDebtModal(employeeId);
    };
    
    let modal = getModalInstance('modalDebtHistory');
    modal.show();
    
    try {
        let response = await fetch('<?= url("/debts/history?id=") ?>' + employeeId, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        let res = await response.json();
        
        if (res.success) {
            document.getElementById('hist_emp_name').innerText = res.employee.name;
            document.getElementById('hist_emp_type').innerText = res.employee.tipe_gaji.toUpperCase();
            
            document.getElementById('hist_stat_pinjaman').innerText = formatRupiahJS(res.summary.total_pinjaman);
            document.getElementById('hist_stat_terbayar').innerText = formatRupiahJS(res.summary.total_terbayar);
            document.getElementById('hist_stat_sisa').innerText = formatRupiahJS(res.summary.total_sisa);
            document.getElementById('hist_stat_active_count').innerText = res.summary.active_count + ' Kasbon aktif';
            
            if (res.debts.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-5 bg-light rounded-4 text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                        Karyawan ini belum pernah memiliki riwayat kasbon.
                    </div>
                `;
                return;
            }
            
            let html = '<div class="accordion" id="accordionDebts">';
            res.debts.forEach((d, idx) => {
                let isActive = d.status === 'active' && parseFloat(d.sisa_nominal) > 0;
                let statusBadge = isActive 
                    ? '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1 rounded-pill">Aktif</span>'
                    : '<span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill"><i class="bi bi-check-lg me-1"></i> Lunas</span>';
                
                let isCollapseShow = '';
                let isCollapsedBtn = 'collapsed';
                
                // Deduction table
                let dedRows = '';
                if (d.deductions && d.deductions.length > 0) {
                    d.deductions.forEach(ded => {
                        let dedType = ded.type === 'payroll' 
                            ? '<span class="badge bg-primary-subtle text-primary border border-primary-subtle">Otomatis Payroll</span>'
                            : '<span class="badge bg-info-subtle text-info border border-info-subtle">Tunai / Manual</span>';
                        
                        let delBtn = ded.type === 'manual' 
                            ? `<button class="btn btn-sm btn-outline-danger rounded-pill px-2 py-0" onclick="deleteDeductionLog(${ded.id}, ${employeeId})" title="Batalkan / Hapus Pembayaran Manual"><i class="bi bi-trash"></i></button>`
                            : '-';
                        
                        dedRows += `
                            <tr>
                                <td class="text-nowrap"><i class="bi bi-calendar-event me-1 text-muted"></i> ${ded.tanggal_potongan}</td>
                                <td class="fw-bold text-success text-nowrap">- ${formatRupiahJS(ded.nominal)}</td>
                                <td class="text-nowrap">${dedType}</td>
                                <td><div class="small">${ded.catatan || '-'}</div></td>
                                <td class="text-center">${delBtn}</td>
                            </tr>
                        `;
                    });
                } else {
                    dedRows = `<tr><td colspan="5" class="text-center text-muted py-3">Belum ada catatan cicilan atau pemotongan.</td></tr>`;
                }
                
                let payBtn = isActive ? `
                    <button class="btn btn-sm btn-success rounded-pill px-3 py-1 fw-bold d-flex align-items-center justify-content-center shadow-xs debt-btn-pay" onclick="openDirectPayModal(${d.id}, '${addslashes(res.employee.name)}', '${addslashes(d.keterangan || 'Kasbon')}', ${d.sisa_nominal}, ${d.potongan_bawaan || 0}, ${employeeId})">
                        <i class="bi bi-cash-coin me-1"></i> Bayar Cicilan
                    </button>
                ` : '';
                
                let editBtn = `
                    <button class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1 d-flex align-items-center justify-content-center debt-btn-secondary" onclick="openEditDebtModal(${d.id})">
                        <i class="bi bi-pencil me-1"></i> Edit Kasbon
                    </button>
                `;

                let deleteDebtBtn = (parseFloat(d.total_terbayar) === 0) ? `
                    <button class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1 d-flex align-items-center justify-content-center debt-btn-secondary" onclick="deleteDebt(${d.id}, ${employeeId})" title="Hapus Data Kasbon Ini">
                        <i class="bi bi-trash me-1"></i> Hapus Kasbon
                    </button>
                ` : '';

                html += `
                    <div class="accordion-item border rounded-4 mb-3 overflow-hidden shadow-sm">
                        <h2 class="accordion-header" id="heading-${d.id}">
                            <button class="accordion-button ${isCollapsedBtn} bg-white py-3 px-3 px-sm-4" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-${d.id}">
                                <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between w-100 me-2 gap-2">
                                    <div class="d-flex align-items-center gap-2 gap-sm-3">
                                        <div class="stat-icon p-2 rounded-circle ${isActive ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success'}">
                                            <i class="bi ${isActive ? 'bi-wallet2' : 'bi-check-circle-fill'} fs-5"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark fs-6">${d.keterangan || 'Kasbon Karyawan'}</div>
                                            <small class="text-muted">Dicatat: ${d.created_at || '-'}</small>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between justify-content-sm-end w-100 w-sm-auto gap-3">
                                        <div class="text-start text-sm-end">
                                            <small class="text-muted d-block" style="font-size: 0.75rem;">Sisa Kasbon</small>
                                            <strong class="${isActive ? 'text-danger' : 'text-success'} fs-6">${formatRupiahJS(d.sisa_nominal)}</strong>
                                        </div>
                                        <div>${statusBadge}</div>
                                    </div>
                                </div>
                            </button>
                        </h2>
                        <div id="collapse-${d.id}" class="accordion-collapse collapse ${isCollapseShow}" data-bs-parent="#accordionDebts">
                            <div class="accordion-body bg-light p-3 p-sm-4">
                                <div class="row g-2 g-sm-3 mb-3 bg-white p-3 rounded-3 border">
                                    <div class="col-6 col-sm-4">
                                        <small class="text-muted d-block" style="font-size: 0.75rem;">TOTAL PINJAMAN</small>
                                        <strong class="text-dark fs-6">${formatRupiahJS(d.total_nominal)}</strong>
                                    </div>
                                    <div class="col-6 col-sm-4">
                                        <small class="text-muted d-block" style="font-size: 0.75rem;">POTONGAN / PERIODE</small>
                                        <strong class="text-dark fs-6">${formatRupiahJS(d.potongan_bawaan)}</strong>
                                    </div>
                                    <div class="col-12 col-sm-4">
                                        <small class="text-muted d-block" style="font-size: 0.75rem;">TOTAL TERBAYAR</small>
                                        <strong class="text-success fs-6">${formatRupiahJS(d.total_terbayar)}</strong>
                                    </div>
                                    ${d.catatan ? `<div class="col-12 border-top pt-2 mt-2"><small class="text-muted"><strong>Catatan:</strong> ${d.catatan}</small></div>` : ''}
                                </div>

                                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3 pt-2">
                                    <h6 class="fw-bold text-dark mb-0 fs-7 d-flex align-items-center">
                                        <i class="bi bi-journal-text me-1 text-primary"></i> Log Cicilan & Pemotongan
                                    </h6>
                                    <div class="debt-action-toolbar d-flex align-items-center gap-2 flex-wrap justify-content-start justify-content-md-end w-100 w-md-auto">
                                        ${payBtn}
                                        ${editBtn}
                                        ${deleteDebtBtn}
                                    </div>
                                </div>

                                <div class="table-responsive bg-white rounded-3 border">
                                    <table class="table table-sm table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="text-nowrap" style="width: 20%;">TANGGAL</th>
                                                <th class="text-nowrap" style="width: 20%;">NOMINAL</th>
                                                <th class="text-nowrap" style="width: 25%;">METODE</th>
                                                <th style="width: 25%;">CATATAN</th>
                                                <th class="text-center text-nowrap" style="width: 10%;">AKSI</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${dedRows}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            container.innerHTML = html;
        } else {
            container.innerHTML = `<div class="p-4 text-center text-danger">${res.message}</div>`;
        }
    } catch (err) {
        container.innerHTML = `<div class="p-4 text-center text-danger">Gagal memuat rincian kasbon dari server.</div>`;
    }
}

// =========================================================================
// Modal Pembayaran Cicilan Manual
// =========================================================================
let quickPayDebtsCache = [];
let historyModalReopenEmpId = 0;
let isPaymentSubmitted = false;

document.addEventListener('DOMContentLoaded', function() {
    let payModalElem = document.getElementById('modalPayManual');
    if (payModalElem) {
        payModalElem.addEventListener('hidden.bs.modal', function () {
            if (!isPaymentSubmitted && historyModalReopenEmpId > 0) {
                let empId = historyModalReopenEmpId;
                historyModalReopenEmpId = 0;
                openHistoryModal(empId);
            }
        });
    }
});

function setPayAmount(amount) {
    let input = document.getElementById('pay_amount');
    input.value = amount.toString();
    formatRupiahInput(input);
    validatePayAmount();
    input.focus();
}

async function openQuickPayModal(employeeId, employeeName) {
    historyModalReopenEmpId = 0;
    isPaymentSubmitted = false;
    document.getElementById('pay_employee_name').innerText = employeeName;
    let selectDebt = document.getElementById('pay_debt_id');
    selectDebt.innerHTML = '<option value="">Memuat daftar kasbon aktif...</option>';
    document.getElementById('pay_amount').value = '';
    document.getElementById('pay_date').value = new Date().toISOString().split('T')[0];
    document.getElementById('pay_notes').value = 'Pembayaran tunai/manual';
    document.getElementById('pay_remaining_text').innerText = 'Rp 0';
    document.getElementById('pay_error_msg').classList.add('d-none');
    document.getElementById('pay_quick_buttons').innerHTML = '';
    document.getElementById('btnSubmitPay').disabled = true;
    
    let modal = getModalInstance('modalPayManual');
    modal.show();
    
    try {
        let response = await fetch('<?= url("/debts/active-list?id=") ?>' + employeeId, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        let res = await response.json();
        
        if (res.success && res.debts.length > 0) {
            quickPayDebtsCache = res.debts;
            selectDebt.innerHTML = '';
            
            res.debts.forEach((d) => {
                let opt = document.createElement('option');
                opt.value = d.id;
                opt.text = (d.keterangan || 'Kasbon') + ' — Sisa: ' + formatRupiahJS(d.sisa_nominal);
                opt.setAttribute('data-remaining', d.sisa_nominal);
                opt.setAttribute('data-potongan', d.potongan_bawaan || 0);
                selectDebt.appendChild(opt);
            });
            
            selectDebt.selectedIndex = 0;
            onPayDebtChange(selectDebt);
        } else {
            selectDebt.innerHTML = '<option value="">Tidak ada kasbon aktif</option>';
            document.getElementById('btnSubmitPay').disabled = true;
            document.getElementById('pay_amount').disabled = true;
        }
    } catch (err) {
        selectDebt.innerHTML = '<option value="">Gagal memuat kasbon</option>';
        document.getElementById('btnSubmitPay').disabled = true;
        document.getElementById('pay_amount').disabled = true;
    }
}

function openDirectPayModal(debtId, empName, debtKet, remaining, defaultPotongan = 0, employeeId = 0) {
    historyModalReopenEmpId = employeeId;
    isPaymentSubmitted = false;
    
    let histModal = bootstrap.Modal.getInstance(document.getElementById('modalDebtHistory'));
    if (histModal) histModal.hide();
    
    document.getElementById('pay_employee_name').innerText = empName;
    let selectDebt = document.getElementById('pay_debt_id');
    selectDebt.innerHTML = '';
    
    let opt = document.createElement('option');
    opt.value = debtId;
    opt.text = debtKet + ' — Sisa: ' + formatRupiahJS(remaining);
    opt.setAttribute('data-remaining', remaining);
    opt.setAttribute('data-potongan', defaultPotongan);
    selectDebt.appendChild(opt);
    
    selectDebt.selectedIndex = 0;
    onPayDebtChange(selectDebt);
    
    document.getElementById('pay_amount').value = '';
    document.getElementById('pay_date').value = new Date().toISOString().split('T')[0];
    document.getElementById('pay_notes').value = 'Pembayaran tunai/manual';
    document.getElementById('pay_error_msg').classList.add('d-none');
    document.getElementById('btnSubmitPay').disabled = true;
    
    let modal = getModalInstance('modalPayManual');
    modal.show();
}

function onPayDebtChange(selectElem) {
    let selectedOption = selectElem.options[selectElem.selectedIndex];
    let quickContainer = document.getElementById('pay_quick_buttons');
    quickContainer.innerHTML = '';
    
    if (selectedOption && selectedOption.getAttribute('data-remaining')) {
        let remaining = parseFloat(selectedOption.getAttribute('data-remaining')) || 0;
        let defaultPotongan = parseFloat(selectedOption.getAttribute('data-potongan')) || 0;
        
        document.getElementById('pay_remaining_text').innerText = formatRupiahJS(remaining);
        let inputAmount = document.getElementById('pay_amount');
        inputAmount.setAttribute('data-max', remaining);
        inputAmount.disabled = (remaining <= 0);
        
        if (remaining > 0) {
            // Quick Button: Bayar Lunas
            let btnLunas = document.createElement('button');
            btnLunas.type = 'button';
            btnLunas.className = 'btn btn-outline-danger btn-sm rounded-pill px-2 py-0 fw-semibold';
            btnLunas.style.fontSize = '0.75rem';
            btnLunas.innerText = 'Bayar Lunas';
            btnLunas.onclick = function() { setPayAmount(remaining); };
            quickContainer.appendChild(btnLunas);
            
            // Quick Button: Cicilan Normal (jika < remaining)
            if (defaultPotongan > 0 && defaultPotongan < remaining) {
                let btnNormal = document.createElement('button');
                btnNormal.type = 'button';
                btnNormal.className = 'btn btn-outline-primary btn-sm rounded-pill px-2 py-0 fw-semibold';
                btnNormal.style.fontSize = '0.75rem';
                btnNormal.innerText = 'Cicilan: ' + formatRupiahJS(defaultPotongan);
                btnNormal.onclick = function() { setPayAmount(defaultPotongan); };
                quickContainer.appendChild(btnNormal);
            }
        }
        
        validatePayAmount();
    } else {
        document.getElementById('pay_remaining_text').innerText = 'Rp 0';
        document.getElementById('pay_amount').disabled = true;
        validatePayAmount();
    }
}

function validatePayAmount() {
    let inputAmount = document.getElementById('pay_amount');
    let max = parseFloat(inputAmount.getAttribute('data-max')) || 0;
    let raw = (inputAmount.value || '').toString().replace(/[^0-9]/g, '');
    let val = parseFloat(raw) || 0;
    let btn = document.getElementById('btnSubmitPay');
    let err = document.getElementById('pay_error_msg');
    
    if (val > max && max > 0) {
        btn.disabled = true;
        err.innerHTML = `<i class="bi bi-exclamation-triangle"></i> Nominal pembayaran (${formatRupiahJS(val)}) melebihi sisa hutang (${formatRupiahJS(max)})!`;
        err.classList.remove('d-none');
    } else if (val <= 0) {
        btn.disabled = true;
        err.classList.add('d-none');
    } else {
        btn.disabled = false;
        err.classList.add('d-none');
    }
}

async function submitPayManual(e) {
    e.preventDefault();
    let form = e.target;
    let btn = document.getElementById('btnSubmitPay');
    
    let rawAmount = (document.getElementById('pay_amount').value || '').replace(/[^0-9]/g, '');
    let numAmount = parseFloat(rawAmount) || 0;
    
    if (numAmount <= 0) {
        Swal.fire({
            title: 'Nominal Tidak Valid',
            text: 'Silakan masukkan nominal pembayaran lebih dari Rp 0.',
            icon: 'warning',
            allowOutsideClick: false,
            allowEscapeKey: false
        });
        return;
    }
    
    btn.disabled = true;
    let amountFormatted = formatRupiahJS(numAmount);
    let empName = document.getElementById('pay_employee_name').innerText;
    
    let confirm = await Swal.fire({
        title: 'Konfirmasi Pembayaran',
        text: `Catat pembayaran sebesar ${amountFormatted} untuk ${empName}?`,
        icon: 'question',
        showCancelButton: true,
        reverseButtons: true,
        confirmButtonText: 'Ya, Simpan',
        cancelButtonText: 'Batal',
        allowOutsideClick: false,
        allowEscapeKey: false
    });
    
    if (!confirm.isConfirmed) {
        btn.disabled = false;
        return;
    }
    
    let formData = new FormData(form);
    if (!formData.has('csrf_token')) {
        formData.append('csrf_token', APP_CSRF_TOKEN);
    }
    
    try {
        let response = await fetch('<?= url("/debts/pay-manual") ?>', {
            method: 'POST',
            body: formData,
            headers: { 
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': APP_CSRF_TOKEN
            }
        });
        
        let res = await response.json();
        if (res.success) {
            isPaymentSubmitted = true;
            Swal.fire({
                title: 'Berhasil!',
                text: res.message,
                icon: 'success',
                timer: 1500,
                showConfirmButton: false,
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then(() => {
                location.reload();
            });
        } else {
            btn.disabled = false;
            if (res.csrf_error) {
                Swal.fire({
                    title: 'Sesi Kedaluwarsa',
                    text: res.message,
                    icon: 'warning',
                    confirmButtonText: 'Muat Ulang Halaman',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                }).then(() => location.reload());
            } else {
                Swal.fire({
                    title: 'Gagal!',
                    text: res.message || 'Terjadi kesalahan saat memproses pembayaran.',
                    icon: 'error',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                });
            }
        }
    } catch (err) {
        btn.disabled = false;
        handleAjaxError(err);
    }
}

// =========================================================================
// Hapus Kasbon & Hapus Potongan Manual
// =========================================================================
let historyNeedsRefresh = false;

document.addEventListener('DOMContentLoaded', function() {
    let histModalElem = document.getElementById('modalDebtHistory');
    if (histModalElem) {
        histModalElem.addEventListener('hidden.bs.modal', function () {
            if (historyNeedsRefresh) {
                historyNeedsRefresh = false;
                location.reload();
            }
        });
    }
});

async function deleteDebt(debtId, employeeId) {
    let confirm = await Swal.fire({
        title: 'Hapus Kasbon?',
        text: 'Data kasbon ini akan dihapus secara permanen dari sistem.',
        icon: 'warning',
        showCancelButton: true,
        reverseButtons: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        allowOutsideClick: false,
        allowEscapeKey: false
    });
    
    if (!confirm.isConfirmed) return;
    
    let formData = new FormData();
    formData.append('id', debtId);
    formData.append('csrf_token', APP_CSRF_TOKEN);
    
    try {
        let response = await fetch('<?= url("/debts/delete") ?>', {
            method: 'POST',
            body: formData,
            headers: { 
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': APP_CSRF_TOKEN
            }
        });
        
        let res = await response.json();
        if (res.success) {
            historyNeedsRefresh = true;
            Swal.fire({ title: 'Terhapus!', text: res.message, icon: 'success', timer: 1500, showConfirmButton: false, allowOutsideClick: false, allowEscapeKey: false });
            openHistoryModal(employeeId);
        } else {
            if (res.csrf_error) {
                Swal.fire({
                    title: 'Sesi Kedaluwarsa',
                    text: res.message,
                    icon: 'warning',
                    confirmButtonText: 'Muat Ulang Halaman',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                }).then(() => location.reload());
            } else {
                Swal.fire({ title: 'Gagal!', text: res.message, icon: 'error', allowOutsideClick: false, allowEscapeKey: false });
            }
        }
    } catch (err) {
        handleAjaxError(err);
    }
}

async function deleteDeductionLog(deductionId, employeeId) {
    let confirm = await Swal.fire({
        title: 'Batalkan Pembayaran Ini?',
        text: 'Riwayat pembayaran manual akan dihapus dan sisa hutang kasbon akan dikembalikan secara otomatis.',
        icon: 'warning',
        showCancelButton: true,
        reverseButtons: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Ya, Batalkan',
        cancelButtonText: 'Batal',
        allowOutsideClick: false,
        allowEscapeKey: false
    });
    
    if (!confirm.isConfirmed) return;
    
    let formData = new FormData();
    formData.append('id', deductionId);
    formData.append('csrf_token', APP_CSRF_TOKEN);
    
    try {
        let response = await fetch('<?= url("/debts/delete-deduction") ?>', {
            method: 'POST',
            body: formData,
            headers: { 
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': APP_CSRF_TOKEN
            }
        });
        
        let res = await response.json();
        if (res.success) {
            historyNeedsRefresh = true;
            Swal.fire({ title: 'Berhasil!', text: res.message, icon: 'success', timer: 1500, showConfirmButton: false, allowOutsideClick: false, allowEscapeKey: false });
            openHistoryModal(employeeId);
        } else {
            if (res.csrf_error) {
                Swal.fire({
                    title: 'Sesi Kedaluwarsa',
                    text: res.message,
                    icon: 'warning',
                    confirmButtonText: 'Muat Ulang Halaman',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                }).then(() => location.reload());
            } else {
                Swal.fire({ title: 'Gagal!', text: res.message, icon: 'error', allowOutsideClick: false, allowEscapeKey: false });
            }
        }
    } catch (err) {
        handleAjaxError(err);
    }
}
</script>
