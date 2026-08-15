<?php
/**
 * @var string $title
 * @var array $run
 * @var array $items
 */
$isDraft = $run['status'] === 'draft';

$totalBase = 0;
$totalAtt = 0;
$totalProd = 0;
$totalMonthly = 0;
$totalBonus = 0;
$totalDebt = 0;
$totalDed = 0;
$totalRound = 0;
$totalNet = 0;

$includedItems = [];
$excludedItems = [];
foreach ($items as $it) {
    if (($it['is_excluded'] ?? 0) == 1) {
        $excludedItems[] = $it;
    } else {
        $includedItems[] = $it;
    }
}

foreach ($includedItems as $it) {
    $totalBase += $it['gaji_pokok'];
    $totalAtt += $it['total_uang_kehadiran'];
    $totalProd += $it['total_upah_produksi'];
    $totalMonthly += $it['tunjangan_bulanan'];
    $totalBonus += $it['tunjangan_lain'];
    $totalDebt += $it['total_potongan_kasbon'];
    $totalDed += $it['potongan_lain'];
    $totalRound += $it['nominal_pembulatan'];
    $totalNet += $it['gaji_bersih'];
}
?>

<div class="page-header mb-4 d-flex flex-column flex-sm-row justify-content-between align-items-start gap-3">
    <div class="w-100 flex-grow-1">
        <h5 class="page-header-title text-dark fw-bold mb-2" style="font-size: 1.15rem;">
            <i class="bi bi-file-earmark-text text-primary me-1"></i> 
            Preview Payroll <?= $run['type'] === 'weekly' ? 'Mingguan' : ($run['type'] === 'monthly' ? 'Bulanan' : 'Gabungan') ?>
        </h5>
        <?php $periodDetails = getPayrollPeriodDetails($run); ?>
        <?php if (count($periodDetails) > 1): ?>
            <div class="d-flex flex-wrap align-items-center gap-2 mt-1">
                <span class="badge bg-purple-subtle text-purple border border-purple-subtle px-2.5 py-1 rounded-pill" style="color: #6f42c1; background-color: #e2d9f3; border-color: #d6c8ef; font-size: 0.75rem;">
                    <i class="bi bi-tag-fill me-1"></i> Tipe: Gabungan
                </span>
                <?php if (isset($periodDetails['borongan'])): ?>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 rounded-pill" style="font-size: 0.75rem;">
                        <i class="bi bi-calendar-event me-1"></i> Borongan: <?= $periodDetails['borongan']['formatted'] ?>
                    </span>
                <?php endif; ?>
                <?php if (isset($periodDetails['bulanan'])): ?>
                    <span class="badge bg-info-subtle text-info border border-info-subtle px-2.5 py-1 rounded-pill" style="font-size: 0.75rem;">
                        <i class="bi bi-calendar-event me-1"></i> Bulanan: <?= $periodDetails['bulanan']['formatted'] ?>
                    </span>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="text-muted small d-flex flex-column flex-sm-row gap-1 gap-sm-2 align-items-start align-items-sm-center">
                <span><i class="bi bi-calendar-event me-1"></i> <?= formatTanggal($run['periode_awal']) ?> - <?= formatTanggal($run['periode_akhir']) ?></span>
                <span class="d-none d-sm-inline text-muted">|</span>
                <span class="badge bg-light text-dark border fw-normal"><i class="bi bi-tag me-1"></i> Tipe: <?= $run['type'] === 'weekly' ? 'Mingguan' : ($run['type'] === 'monthly' ? 'Bulanan' : 'Gabungan') ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['flash_success'])): ?>
            <div class="alert alert-success border-0 shadow-sm d-flex align-items-center justify-content-between mt-3 mb-0">
                <div><i class="bi bi-check-circle-fill me-2 fs-5"></i> <?= e($_SESSION['flash_success']) ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['flash_error'])): ?>
            <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center justify-content-between mt-3 mb-0">
                <div><i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i> <?= e($_SESSION['flash_error']) ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>
    </div>
    <div class="d-flex flex-wrap gap-2 w-auto justify-content-start justify-content-sm-end">
        <a href="<?= url('/payroll') ?>" class="btn btn-outline-secondary rounded-pill px-3 flex-sm-grow-0 flex-grow-1">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
        <?php if ($isDraft): ?>
            <form action="<?= url('/payroll/delete') ?>" method="POST" class="flex-sm-grow-0 flex-grow-1 m-0">
                <?= csrfField() ?>
                <input type="hidden" name="id" value="<?= $run['id'] ?>">
                <button type="submit" class="btn btn-outline-danger rounded-pill px-3 shadow-sm w-100" data-confirm="Yakin ingin menghapus data draft payroll ini? Data tidak dapat dikembalikan.">
                    <i class="bi bi-trash me-1"></i> Hapus Data
                </button>
            </form>
        <?php else: ?>
            <a href="<?= url('/payroll/export-combined?id=' . $run['id']) ?>" class="btn btn-danger rounded-pill px-4 shadow-sm flex-sm-grow-0 flex-grow-1">
                <i class="bi bi-file-earmark-pdf me-1"></i> Cetak Laporan (Rekap & Slip)
            </a>
        <?php endif; ?>
        
        <?php if ($isDraft): ?>
            <form action="<?= url('/payroll/approve') ?>" method="POST" class="flex-sm-grow-0 flex-grow-1 m-0">
                <?= csrfField() ?>
                <input type="hidden" name="id" value="<?= $run['id'] ?>">
                <button type="submit" class="btn btn-success rounded-pill px-3 shadow-sm w-100" data-confirm="Apakah Anda yakin ingin menyetujui payroll ini? Setelah disetujui, data akan dikunci dan potongan hutang diterapkan.">
                    <i class="bi bi-check-circle me-1"></i> Approve
                </button>
            </form>
        <?php else: ?>
            <?php 
                $approvedTime = strtotime($run['disetujui_pada']);
                $now = time();
                if (($now - $approvedTime) <= 86400): // Within 24 hours
            ?>
                <form action="<?= url('/payroll/cancelApprove') ?>" method="POST" class="flex-sm-grow-0 flex-grow-1 m-0">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= $run['id'] ?>">
                    <button type="submit" class="btn btn-outline-danger rounded-pill px-3 shadow-sm w-100" data-confirm="BAHAYA: Anda akan membatalkan status Approve, melepaskan kunci data absensi/produksi, mengembalikan saldo hutang karyawan, dan membatalkan mutasi tabungan otomatis. Lanjutkan?" title="Batalkan Approve (Tersedia dalam 24 jam setelah approve)">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Batalkan Approve
                    </button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-4 col-md-6">
        <div class="stat-card-context h-100 p-4">
            <div class="text-muted small fw-bold mb-1">TOTAL KARYAWAN</div>
            <div class="fs-2 fw-bold text-dark"><?= count($includedItems) ?> <span class="fs-6 text-muted fw-normal">Orang</span></div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="stat-card-context h-100 p-4">
            <div class="text-muted small fw-bold mb-1">TOTAL POTONGAN HUTANG</div>
            <div class="fs-2 fw-bold text-danger"><?= formatRupiah((int)$totalDebt) ?></div>
        </div>
    </div>
    <div class="col-xl-4 col-md-12">
        <div class="stat-card-context h-100 p-4 border border-success-subtle bg-success-subtle text-success">
            <div class="small fw-bold mb-1 opacity-75">GRAND TOTAL PENGELUARAN BERSIH</div>
            <div class="fs-2 fw-bold"><?= formatRupiah((int)$totalNet) ?></div>
        </div>
    </div>
</div>

<!-- Detailed Table -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-0">
        <h5 class="fw-bold text-dark mb-0">Rincian per Karyawan</h5>
    </div>
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="min-width: 1200px;">
                <thead class="table-light">
                    <tr>
                        <th scope="col" class="ps-3" style="width: 5%;">NO</th>
                        <th scope="col" style="width: 20%;">KARYAWAN</th>
                        <th scope="col" class="text-end">PENDAPATAN AWAL</th>
                        <th scope="col" class="text-end">POTONGAN</th>
                        <th scope="col" class="text-end">PENYESUAIAN (+/-)</th>
                        <th scope="col" class="text-end pe-3 text-success">NET GAJI</th>
                        <?php if ($isDraft): ?>
                            <th scope="col" class="text-center" style="width: 10%;">AKSI</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                        $no = 1; 
                        $currentTipe = '';
                        $periodDetails = getPayrollPeriodDetails($run);
                        foreach ($includedItems as $it): 
                            if ($currentTipe !== $it['tipe_gaji']):
                                $currentTipe = $it['tipe_gaji'];
                                $tipeKey = strtolower($currentTipe);
                                $tipePeriodStr = isset($periodDetails[$tipeKey]) ? $periodDetails[$tipeKey]['formatted'] : (formatTanggal($run['periode_awal']) . ' – ' . formatTanggal($run['periode_akhir']));
                    ?>
                        <tr>
                            <td colspan="<?= $isDraft ? 6 : 5 ?>" class="bg-light fw-bold text-uppercase text-secondary py-2.5 px-3 border-bottom border-top shadow-sm" style="font-size: 0.75rem; letter-spacing: 1px;">
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                    <div>
                                        <i class="bi bi-people-fill me-2 text-primary"></i> Grup Karyawan <?= $currentTipe ?>
                                    </div>
                                    <span class="badge bg-white text-secondary border fw-normal text-capitalize" style="font-size: 0.725rem; letter-spacing: 0;">
                                        <i class="bi bi-calendar-range text-primary me-1"></i> Periode: <?= $tipePeriodStr ?>
                                    </span>
                                </div>
                            </td>
                        </tr>
                    <?php 
                            endif;
                            $pendapatanAwal = $it['gaji_pokok'] + $it['total_uang_kehadiran'] + $it['total_upah_produksi'] + ($it['total_upah_lembur'] ?? 0) + $it['tunjangan_bulanan'];
                            $penyesuaian = $it['tunjangan_lain'] + $it['nominal_pembulatan'] + ($it['penarikan_tabungan'] ?? 0) - $it['potongan_lain'];
                    ?>
                        <tr>
                            <td class="ps-3 fw-semibold text-muted"><?= $no++ ?></td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($it['employee_name']) ?></div>
                                <small class="text-muted"><?= e(ucfirst($it['tipe_gaji'])) ?> &bull; <?= $it['hari_hadir'] ?> Hari</small>
                            </td>
                            <td class="text-end" style="min-width: 180px;">
                                <div class="fw-bold text-dark mb-1 pb-1 border-bottom"><?= formatRupiah((int)$pendapatanAwal) ?></div>
                                <div class="text-muted d-flex flex-column gap-1" style="font-size: 0.75rem;">
                                    <?php if ($it['gaji_pokok'] > 0): ?>
                                        <div class="d-flex justify-content-between"><span>Gaji Pokok</span> <span><?= formatRupiah((int)$it['gaji_pokok']) ?></span></div>
                                    <?php endif; ?>
                                    <?php if ($it['total_uang_kehadiran'] > 0): ?>
                                        <div class="d-flex justify-content-between"><span>Kehadiran</span> <span><?= formatRupiah((int)$it['total_uang_kehadiran']) ?></span></div>
                                    <?php endif; ?>
                                    <?php if ($it['total_upah_produksi'] > 0): ?>
                                        <div class="d-flex justify-content-between"><span>Borongan</span> <span><?= formatRupiah((int)$it['total_upah_produksi']) ?></span></div>
                                    <?php endif; ?>
                                    <?php if ($it['tunjangan_bulanan'] > 0): ?>
                                        <div class="d-flex justify-content-between"><span>T. Bulanan</span> <span><?= formatRupiah((int)$it['tunjangan_bulanan']) ?></span></div>
                                    <?php endif; ?>
                                    <?php if (($it['total_upah_lembur'] ?? 0) > 0): ?>
                                        <div class="d-flex justify-content-between text-warning fw-bold"><span>Lembur</span> <span><?= formatRupiah((int)$it['total_upah_lembur']) ?></span></div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="text-end" style="min-width: 150px;">
                                <?php $totalPotongan = ($it['total_potongan_kasbon'] ?? 0) + ($it['total_penarikan_gaji'] ?? 0) + ($it['potongan_tabungan'] ?? 0); ?>
                                <div class="fw-bold <?= $totalPotongan > 0 ? 'text-danger' : 'text-muted' ?> mb-1 pb-1 border-bottom">
                                    <?= formatRupiah((int)$totalPotongan) ?>
                                </div>
                                <div class="text-muted d-flex flex-column gap-1" style="font-size: 0.75rem;">
                                    <?php if (($it['total_potongan_kasbon'] ?? 0) > 0): ?>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span>
                                                Kasbon 
                                                <?php 
                                                $rincian = json_decode($it['rincian_json'] ?? '[]', true) ?: [];
                                                if (!empty($rincian['kasbon_adjusted_down'])): 
                                                ?>
                                                    <i class="bi bi-exclamation-circle-fill text-warning ms-1" style="font-size: 0.7rem;" data-bs-toggle="tooltip" title="Angka diturunkan otomatis karena gaji tidak cukup untuk membayar cicilan aslinya."></i>
                                                <?php endif; ?>
                                            </span> 
                                            <span class="text-danger"><?= formatRupiah((int)$it['total_potongan_kasbon']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (($it['total_penarikan_gaji'] ?? 0) > 0): ?>
                                        <div class="d-flex justify-content-between"><span>Penarikan Gaji</span> <span class="text-danger"><?= formatRupiah((int)$it['total_penarikan_gaji']) ?></span></div>
                                    <?php endif; ?>
                                    <?php if (($it['potongan_tabungan'] ?? 0) > 0): ?>
                                        <div class="d-flex justify-content-between"><span>Setor Tabungan</span> <span class="text-danger"><?= formatRupiah((int)$it['potongan_tabungan']) ?></span></div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="text-end">
                                <div class="fw-semibold <?= $penyesuaian > 0 ? 'text-success' : ($penyesuaian < 0 ? 'text-danger' : 'text-muted') ?> mb-1 pb-1 border-bottom">
                                    <?= $penyesuaian > 0 ? '+' : '' ?><?= formatRupiah((int)$penyesuaian) ?>
                                </div>
                                <div class="text-muted d-flex flex-column gap-1" style="font-size: 0.75rem;">
                                    <?php if (($it['tunjangan_lain'] ?? 0) > 0): ?>
                                        <div class="d-flex justify-content-between text-success"><span>Bonus Lain</span> <span>+<?= formatRupiah((int)$it['tunjangan_lain']) ?></span></div>
                                    <?php endif; ?>
                                    <?php if (($it['penarikan_tabungan'] ?? 0) > 0): ?>
                                        <div class="d-flex justify-content-between text-success"><span>Tarik Tabungan</span> <span>+<?= formatRupiah((int)$it['penarikan_tabungan']) ?></span></div>
                                    <?php endif; ?>
                                    <?php if (($it['nominal_pembulatan'] ?? 0) > 0): ?>
                                        <div class="d-flex justify-content-between text-success"><span>Pembulatan</span> <span>+<?= formatRupiah((int)$it['nominal_pembulatan']) ?></span></div>
                                    <?php endif; ?>
                                    <?php if (($it['potongan_lain'] ?? 0) > 0): ?>
                                        <div class="d-flex justify-content-between text-danger"><span>Potongan Manual</span> <span>-<?= formatRupiah((int)$it['potongan_lain']) ?></span></div>
                                    <?php endif; ?>
                                </div>
                                <?php if ($it['catatan_tunjangan_lain'] || $it['catatan_potongan_lain']): ?>
                                    <small class="text-muted d-block mt-2 text-truncate" style="max-width: 150px; font-size: 0.7rem;" title="<?= e(trim($it['catatan_tunjangan_lain'] . ' | ' . $it['catatan_potongan_lain'], ' |')) ?>">
                                        <i class="bi bi-info-circle"></i> Catatan Manual
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-3">
                                <div class="fw-bold text-success fs-5">
                                    <?= formatRupiah((int)$it['gaji_bersih']) ?>
                                </div>
                            </td>
                            <?php if ($isDraft): ?>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 btn-edit-item"
                                            data-id="<?= $it['id'] ?>"
                                            data-name="<?= e($it['employee_name']) ?>"
                                            data-base="<?= (int)$pendapatanAwal ?>"
                                            data-gajipokok="<?= (int)$it['gaji_pokok'] ?>"
                                            data-kehadiran="<?= (int)$it['total_uang_kehadiran'] ?>"
                                            data-borongan="<?= (int)$it['total_upah_produksi'] ?>"
                                            data-tbulanan="<?= (int)$it['tunjangan_bulanan'] ?>"
                                            data-lembur="<?= (int)($it['total_upah_lembur'] ?? 0) ?>"
                                            data-debt="<?= (int)$it['total_potongan_kasbon'] ?>"
                                            data-penarikan="<?= (int)($it['total_penarikan_gaji'] ?? 0) ?>"
                                            data-bonus="<?= (int)$it['tunjangan_lain'] ?>"
                                            data-bnotes="<?= e($it['catatan_tunjangan_lain']) ?>"
                                            data-ded="<?= (int)$it['potongan_lain'] ?>"
                                            data-dnotes="<?= e($it['catatan_potongan_lain']) ?>"
                                            data-tabungansetor="<?= (int)($it['potongan_tabungan'] ?? 0) ?>"
                                            data-tabungantarik="<?= (int)($it['penarikan_tabungan'] ?? 0) ?>"
                                            data-saldotabungan="<?= (int)($it['saldo_tabungan'] ?? 0) ?>"
                                            data-round="<?= (int)$it['nominal_pembulatan'] ?>"
                                            data-net="<?= (int)$it['gaji_bersih'] ?>"
                                            data-maxdebt="<?= (int)$it['total_active_kasbon'] ?>"
                                            data-bs-toggle="modal" data-bs-target="#modalAdjust">
                                        <i class="bi bi-pencil-square"></i> Sesuaikan
                                    </button>
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 btn-exclude-item w-100"
                                                data-id="<?= $it['id'] ?>"
                                                data-name="<?= e($it['employee_name']) ?>"
                                                data-bs-toggle="modal" data-bs-target="#modalExclude">
                                            <i class="bi bi-x-circle"></i> Kecualikan
                                        </button>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (!empty($excludedItems)): ?>
<div class="card border-0 shadow-sm rounded-4 mt-4 border-danger-subtle" style="background-color: #fff5f5;">
    <div class="card-header bg-transparent border-bottom-0 pt-4 px-4 pb-0">
        <h5 class="fw-bold text-danger mb-0"><i class="bi bi-x-circle-fill me-2"></i>Karyawan Dikecualikan</h5>
        <p class="text-muted small">Karyawan berikut dikecualikan dari payroll ini dan gajinya tidak diproses.</p>
    </div>
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-danger">
                    <tr>
                        <th scope="col" class="ps-3" style="width: 5%;">NO</th>
                        <th scope="col" style="width: 25%;">KARYAWAN</th>
                        <th scope="col">ALASAN PENGECUALIAN</th>
                        <?php if ($isDraft): ?>
                            <th scope="col" class="text-center" style="width: 15%;">AKSI</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php $no_exc = 1; foreach ($excludedItems as $eit): ?>
                        <tr>
                            <td class="ps-3 fw-semibold text-muted"><?= $no_exc++ ?></td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($eit['employee_name']) ?></div>
                                <small class="text-muted"><?= e(ucfirst($eit['tipe_gaji'])) ?></small>
                            </td>
                            <td>
                                <?= e($eit['catatan_pengecualian'] ?: '-') ?>
                            </td>
                            <?php if ($isDraft): ?>
                                <td class="text-center">
                                    <form action="<?= url('/payroll/toggle-exclude') ?>" method="POST" class="m-0">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="item_id" value="<?= $eit['id'] ?>">
                                        <input type="hidden" name="run_id" value="<?= $run['id'] ?>">
                                        <input type="hidden" name="is_excluded" value="0">
                                        <button type="submit" class="btn btn-sm btn-outline-success rounded-pill px-3" data-confirm="Batalkan pengecualian dan masukkan kembali karyawan ini ke payroll?">
                                            <i class="bi bi-arrow-counterclockwise"></i> Batal Kecuali
                                        </button>
                                    </form>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($isDraft): ?>
<!-- Modal Kecualikan -->
<div class="modal fade" id="modalExclude" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom px-4 pt-4 pb-3">
                <h5 class="modal-title fw-bold text-danger">
                    <i class="bi bi-exclamation-triangle text-danger me-2"></i> Kecualikan <span id="exc_name" class="text-danger"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= url('/payroll/toggle-exclude') ?>" method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="item_id" id="exc_id" value="">
                <input type="hidden" name="run_id" value="<?= $run['id'] ?>">
                <input type="hidden" name="is_excluded" value="1">
                
                <div class="modal-body p-4">
                    <p class="text-muted">Karyawan ini tidak akan diikutsertakan dalam payroll periode ini. Data absensi dan produksinya tidak akan dikunci sehingga bisa ditarik di payroll berikutnya.</p>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Alasan Pengecualian (Opsional)</label>
                        <textarea class="form-control" name="catatan_pengecualian" rows="3" placeholder="Tulis alasan jika ada..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light px-4 py-3 border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger px-4">
                        <i class="bi bi-x-circle me-1"></i> Kecualikan Karyawan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Sesuaikan -->
<div class="modal fade" id="modalAdjust" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom px-4 pt-4 pb-3">
                <h5 class="modal-title fw-bold text-dark">
                    <i class="bi bi-sliders text-primary me-2"></i> Sesuaikan Gaji <span id="adj_name" class="text-primary"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= url('/payroll/update-item') ?>" method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="item_id" id="adj_id" value="">
                <input type="hidden" name="run_id" value="<?= $run['id'] ?>">
                
                <div class="modal-body p-4">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3 border h-100">
                                <div class="text-muted small fw-bold mb-1">TOTAL PENDAPATAN SEMENTARA</div>
                                <div class="fs-4 fw-bold text-dark border-bottom pb-2 mb-2" id="adj_base_text">Rp 0</div>
                                <div id="adj_base_breakdown" class="text-muted small d-flex flex-column gap-1">
                                    <!-- JS breakdown here -->
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-danger-subtle border border-danger-subtle rounded-3 text-danger h-100">
                                <label for="adj_debt_input" class="small fw-bold mb-1 d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-1 text-danger">
                                    <span>POTONGAN HUTANG / KASBON</span>
                                    <span class="badge bg-white text-danger border border-danger-subtle">Sisa: <span id="info_sisa_kasbon">Rp 0</span></span>
                                </label>
                                <input type="text" class="form-control input-rupiah border-danger-subtle text-danger fw-bold fs-4 bg-white enter-nav" id="adj_debt_input" name="total_potongan_kasbon" value="0" onkeyup="calculateNet()">
                                <div class="text-danger small mt-2 d-none fw-bold" id="err_kasbon"><i class="bi bi-exclamation-triangle-fill"></i> Potongan melebihi sisa hutang karyawan!</div>
                                
                                <div id="adj_penarikan_container" class="mt-3 pt-2 border-top border-danger-subtle d-none">
                                    <div class="d-flex justify-content-between small fw-bold">
                                        <span>PENARIKAN (TETAP)</span>
                                        <span id="adj_penarikan_text">Rp 0</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Penambahan & Pemotongan Manual</h6>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-success fw-bold">Bonus / Penambahan Lain (Rp)</label>
                            <input type="text" class="form-control input-rupiah border-success-subtle text-success fw-semibold enter-nav" id="adj_bonus" name="tunjangan_lain" value="0" onkeyup="calculateNet()">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Keterangan Bonus</label>
                            <input type="text" class="form-control enter-nav" id="adj_bnotes" name="catatan_tunjangan_lain" placeholder="Misal: Bonus lembur">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label text-danger fw-bold">Pemotongan Manual Lain (Rp)</label>
                            <input type="text" class="form-control input-rupiah border-danger-subtle text-danger fw-semibold enter-nav" id="adj_ded" name="potongan_lain" value="0" onkeyup="calculateNet()">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Keterangan Pemotongan</label>
                            <input type="text" class="form-control enter-nav" id="adj_dnotes" name="catatan_potongan_lain" placeholder="Misal: Denda keterlambatan">
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-end gap-2 border-bottom pb-2 mb-3 mt-4">
                        <h6 class="fw-bold text-dark mb-0">Tabungan Karyawan</h6>
                        <span class="badge bg-success-subtle text-success border border-success-subtle fs-6">Total Tabungan: <span id="info_saldo_tabungan">Rp 0</span></span>
                    </div>
                    <div class="row g-3 mb-4 bg-light p-3 rounded-3 border">
                        <div class="col-md-6">
                            <label class="form-label text-success fw-bold">Setor Tabungan (Rp) <small class="fw-normal text-muted">- memotong gaji</small></label>
                            <input type="text" class="form-control input-rupiah text-success fw-semibold enter-nav" id="adj_tabungan_setor" name="potongan_tabungan" value="0" onkeyup="calculateNet()">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-danger fw-bold">Tarik Tabungan (Rp) <small class="fw-normal text-muted">- menambah gaji</small></label>
                            <input type="text" class="form-control input-rupiah text-danger fw-semibold enter-nav" id="adj_tabungan_tarik" name="penarikan_tabungan" value="0" onkeyup="calculateNet()">
                            <div class="text-danger small mt-1 d-none" id="err_tabungan_tarik">Saldo tidak cukup. Saldo: <span id="txt_saldo_tabungan"></span></div>
                        </div>
                    </div>


                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Pembulatan Gaji</h6>
                    <div class="row g-3 align-items-center bg-light p-3 rounded-3 border">
                        <div class="col-md-6">
                            <p class="text-muted small mb-0">Jika angka net gaji tidak bulat (misal belakangnya bukan 000), Anda bisa menginput nominal angka penambah pembulatan di sini agar gajinya pas.</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Angka Penambah Pembulatan (Rp)</label>
                            <input type="text" class="form-control input-rupiah enter-nav" id="adj_round" name="nominal_pembulatan" value="0" onkeyup="calculateNet()">
                        </div>
                    </div>

                    <div class="mt-4 p-3 bg-success-subtle border border-success-subtle rounded-3 text-center">
                        <div class="small fw-bold text-success mb-1">NET GAJI SETELAH PENYESUAIAN</div>
                        <div class="fs-1 fw-bold text-success" id="adj_net_final">Rp 0</div>
                    </div>
                </div>
                <div class="modal-footer bg-light px-4 py-3 border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-save me-1"></i> Simpan Penyesuaian
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentBase = 0;
let currentDebt = 0;
let currentPenarikan = 0;
let currentMaxDebt = 0;
let currentSaldoTabungan = 0;

document.addEventListener('DOMContentLoaded', function() {
    const editBtns = document.querySelectorAll('.btn-edit-item');
    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('adj_id').value = this.dataset.id;
            document.getElementById('adj_name').innerText = '- ' + this.dataset.name;
            
            currentBase = parseInt(this.dataset.base) || 0;
            currentDebt = parseInt(this.dataset.debt) || 0;
            currentPenarikan = parseInt(this.dataset.penarikan) || 0;
            currentMaxDebt = parseInt(this.dataset.maxdebt) || 0;
            
            document.getElementById('adj_base_text').innerText = formatRupiahJs(currentBase);

            if (currentPenarikan > 0) {
                document.getElementById('adj_penarikan_container').classList.remove('d-none');
                document.getElementById('adj_penarikan_text').innerText = formatRupiahJs(currentPenarikan);
            } else {
                document.getElementById('adj_penarikan_container').classList.add('d-none');
            }
            
            let breakdownHtml = '';
            const gp = parseInt(this.dataset.gajipokok) || 0;
            const keh = parseInt(this.dataset.kehadiran) || 0;
            const bor = parseInt(this.dataset.borongan) || 0;
            const tb = parseInt(this.dataset.tbulanan) || 0;
            const lem = parseInt(this.dataset.lembur) || 0;

            if(gp > 0) breakdownHtml += `<div class="d-flex justify-content-between"><span>Gaji Pokok</span> <span>${formatRupiahJs(gp)}</span></div>`;
            if(keh > 0) breakdownHtml += `<div class="d-flex justify-content-between"><span>Kehadiran</span> <span>${formatRupiahJs(keh)}</span></div>`;
            if(bor > 0) breakdownHtml += `<div class="d-flex justify-content-between"><span>Borongan</span> <span>${formatRupiahJs(bor)}</span></div>`;
            if(tb > 0) breakdownHtml += `<div class="d-flex justify-content-between"><span>T. Bulanan</span> <span>${formatRupiahJs(tb)}</span></div>`;
            if(lem > 0) breakdownHtml += `<div class="d-flex justify-content-between text-warning fw-bold"><span>Lembur</span> <span>${formatRupiahJs(lem)}</span></div>`;
            
            document.getElementById('adj_base_breakdown').innerHTML = breakdownHtml;
            document.getElementById('adj_debt_input').value = formatRupiahJs(currentDebt).replace('Rp ', '');
            
            document.getElementById('adj_bonus').value = formatRupiahJs(parseInt(this.dataset.bonus)).replace('Rp ', '');
            document.getElementById('adj_bnotes').value = this.dataset.bnotes;
            document.getElementById('adj_ded').value = formatRupiahJs(parseInt(this.dataset.ded)).replace('Rp ', '');
            document.getElementById('adj_dnotes').value = this.dataset.dnotes;
            document.getElementById('adj_tabungan_setor').value = formatRupiahJs(parseInt(this.dataset.tabungansetor)).replace('Rp ', '');
            document.getElementById('adj_tabungan_tarik').value = formatRupiahJs(parseInt(this.dataset.tabungantarik)).replace('Rp ', '');
            currentSaldoTabungan = parseInt(this.dataset.saldotabungan) || 0;
            document.getElementById('info_sisa_kasbon').innerText = formatRupiahJs(currentMaxDebt);
            document.getElementById('info_saldo_tabungan').innerText = formatRupiahJs(currentSaldoTabungan);
            document.getElementById('adj_round').value = formatRupiahJs(parseInt(this.dataset.round)).replace('Rp ', '');
            
            calculateNet();
        });
    });

    const excludeBtns = document.querySelectorAll('.btn-exclude-item');
    excludeBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('exc_id').value = this.dataset.id;
            document.getElementById('exc_name').innerText = '- ' + this.dataset.name;
        });
    });

    const adjustForm = document.querySelector('#modalAdjust form');
    if (adjustForm) {
        adjustForm.addEventListener('submit', function(e) {
            const parseRupiah = (val) => parseInt(val.toString().replace(/[^0-9]/g, '') || 0);
            
            // Check if net < 0 first
            let debtVal = parseRupiah(document.getElementById('adj_debt_input').value);
            let bonus = parseRupiah(document.getElementById('adj_bonus').value);
            let ded = parseRupiah(document.getElementById('adj_ded').value);
            let tabunganSetor = parseRupiah(document.getElementById('adj_tabungan_setor').value);
            let tabunganTarik = parseRupiah(document.getElementById('adj_tabungan_tarik').value);
            let round = parseRupiah(document.getElementById('adj_round').value);
            let net = currentBase - debtVal - currentPenarikan + bonus - ded - tabunganSetor + tabunganTarik + round;
            
            if (tabunganTarik > currentSaldoTabungan) {
                e.preventDefault();
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Gagal!', 'Penarikan tabungan melebihi saldo tabungan karyawan.', 'error');
                } else {
                    alert('Gagal! Penarikan tabungan melebihi saldo.');
                }
                return false;
            }
            
            if (net < 0) {
                e.preventDefault();
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Gagal!', 'Total potongan melebihi total pendapatan. Gaji tidak boleh minus.', 'error');
                } else {
                    alert('Gagal! Total potongan melebihi total pendapatan. Gaji tidak boleh minus.');
                }
                return false;
            }

            // Removal of sweetalert excess logic, prevented by calculateNet disabling submit
        });
    }
});

function calculateNet() {
    const parseRupiah = (val) => parseInt(val.toString().replace(/[^0-9]/g, '') || 0);
    
    let debt = parseRupiah(document.getElementById('adj_debt_input').value);
    let bonus = parseRupiah(document.getElementById('adj_bonus').value);
    let ded = parseRupiah(document.getElementById('adj_ded').value);
    let tabunganSetor = parseRupiah(document.getElementById('adj_tabungan_setor').value);
    let tabunganTarik = parseRupiah(document.getElementById('adj_tabungan_tarik').value);
    let round = parseRupiah(document.getElementById('adj_round').value);
    
    let errTarik = document.getElementById('err_tabungan_tarik');
    let errKasbon = document.getElementById('err_kasbon');
    let btnSave = document.querySelector('#modalAdjust form button[type="submit"]');
    
    let hasError = false;

    if (tabunganTarik > currentSaldoTabungan) {
        errTarik.classList.remove('d-none');
        document.getElementById('txt_saldo_tabungan').innerText = formatRupiahJs(currentSaldoTabungan);
        hasError = true;
    } else {
        errTarik.classList.add('d-none');
    }

    if (debt > currentMaxDebt) {
        errKasbon.classList.remove('d-none');
        hasError = true;
    } else {
        errKasbon.classList.add('d-none');
    }

    btnSave.disabled = hasError;

    let net = currentBase - debt - currentPenarikan + bonus - ded - tabunganSetor + tabunganTarik + round;
    let finalEl = document.getElementById('adj_net_final');
    finalEl.innerText = formatRupiahJs(net);
    
    if (net < 0) {
        finalEl.classList.remove('text-success');
        finalEl.classList.add('text-danger');
        btnSave.disabled = true;
    } else {
        finalEl.classList.remove('text-danger');
        finalEl.classList.add('text-success');
        if (!hasError) btnSave.disabled = false;
    }
}

function formatRupiahJs(angka) {
    let isNegative = angka < 0;
    let number_string = Math.abs(angka).toString().replace(/[^,\d]/g, ''),
        split = number_string.split(','),
        sisa = split[0].length % 3,
        rupiah = split[0].substr(0, sisa),
        ribuan = split[0].substr(sisa).match(/\d{3}/gi);

    if (ribuan) {
        let separator = sisa ? '.' : '';
        rupiah += separator + ribuan.join('.');
    }
    rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
    return (isNegative ? '- Rp ' : 'Rp ') + rupiah;
}
</script>
<?php endif; ?>
