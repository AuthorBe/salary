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

foreach ($items as $it) {
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

<div class="page-header mb-4 d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3">
    <div class="w-100">
        <h5 class="page-header-title text-dark fw-bold mb-2" style="font-size: 1.15rem;">
            <i class="bi bi-file-earmark-text text-primary me-1"></i> 
            Preview Payroll <?= $run['type'] === 'weekly' ? 'Mingguan' : 'Bulanan' ?>
        </h5>
        <div class="text-muted small d-flex flex-column flex-sm-row gap-1 gap-sm-2 align-items-start align-items-sm-center">
            <span><i class="bi bi-calendar-event me-1"></i> <?= formatTanggal($run['periode_awal']) ?> - <?= formatTanggal($run['periode_akhir']) ?></span>
            <span class="d-none d-sm-inline text-muted">|</span>
            <span class="badge bg-light text-dark border fw-normal"><i class="bi bi-tag me-1"></i> Tipe: <?= $run['type'] === 'weekly' ? 'Mingguan' : 'Bulanan' ?></span>
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2 w-100 justify-content-start justify-content-sm-end">
        <a href="<?= url('/payroll') ?>" class="btn btn-outline-secondary rounded-pill px-3 flex-sm-grow-0 flex-grow-1">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
        <?php if ($isDraft): ?>
            <button type="button" class="btn btn-secondary rounded-pill px-3 shadow-sm flex-sm-grow-0 flex-grow-1" disabled title="Approve payroll terlebih dahulu untuk mengunduh rekap">
                <i class="bi bi-file-earmark-pdf me-1"></i> Rekap
            </button>
        <?php else: ?>
            <a href="<?= url('/payroll/export?id=' . $run['id']) ?>" class="btn btn-danger rounded-pill px-3 shadow-sm flex-sm-grow-0 flex-grow-1" target="_blank">
                <i class="bi bi-file-earmark-pdf me-1"></i> Rekap
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
            <button class="btn btn-light text-success border border-success-subtle rounded-pill px-3 flex-sm-grow-0 flex-grow-1" disabled>
                <i class="bi bi-check-circle-fill me-1"></i> Approved
            </button>
            
            <?php 
                $approvedTime = strtotime($run['disetujui_pada']);
                $now = time();
                if (($now - $approvedTime) <= 86400): // Within 24 hours
            ?>
                <form action="<?= url('/payroll/regenerate') ?>" method="POST" class="flex-sm-grow-0 flex-grow-1 m-0">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= $run['id'] ?>">
                    <button type="submit" class="btn btn-outline-danger rounded-pill px-3 shadow-sm w-100" data-confirm="BAHAYA: Anda akan membatalkan status Approve, melepaskan kunci data absensi/produksi, dan mengembalikan saldo hutang karyawan. Lanjutkan?" title="Batal & Generate Ulang (Tersedia dalam 24 jam setelah approve)">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Batal & Ulang
                    </button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
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

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-4 col-md-6">
        <div class="stat-card-context h-100 p-4">
            <div class="text-muted small fw-bold mb-1">TOTAL KARYAWAN</div>
            <div class="fs-2 fw-bold text-dark"><?= count($items) ?> <span class="fs-6 text-muted fw-normal">Orang</span></div>
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
                        <th scope="col" class="text-end">P. HUTANG</th>
                        <th scope="col" class="text-end">PENYESUAIAN (+/-)</th>
                        <th scope="col" class="text-end pe-3 text-success">NET GAJI</th>
                        <?php if ($isDraft): ?>
                            <th scope="col" class="text-center" style="width: 10%;">AKSI</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; foreach ($items as $it): 
                        $pendapatanAwal = $it['gaji_pokok'] + $it['total_uang_kehadiran'] + $it['total_upah_produksi'] + ($it['total_upah_lembur'] ?? 0) + $it['tunjangan_bulanan'];
                        $penyesuaian = $it['tunjangan_lain'] + $it['nominal_pembulatan'] - $it['potongan_lain'];
                    ?>
                        <tr>
                            <td class="ps-3 fw-semibold text-muted"><?= $no++ ?></td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($it['employee_name']) ?></div>
                                <small class="text-muted"><?= e(ucfirst($it['tipe_gaji'])) ?> &bull; <?= $it['hari_hadir'] ?> Hari</small>
                            </td>
                            <td class="text-end">
                                <div class="fw-semibold text-dark"><?= formatRupiah((int)$pendapatanAwal) ?></div>
                                <?php if ($it['tunjangan_bulanan'] > 0): ?>
                                    <small class="text-primary d-block" style="font-size: 0.7rem;">+ Uang Bulanan</small>
                                <?php endif; ?>
                                <?php if (($it['total_upah_lembur'] ?? 0) > 0): ?>
                                    <small class="text-warning d-block fw-bold" style="font-size: 0.7rem;">+ Lembur <?= formatRupiah((int)$it['total_upah_lembur']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="fw-semibold <?= $it['total_potongan_kasbon'] > 0 ? 'text-danger' : 'text-muted' ?>">
                                    <?= formatRupiah((int)$it['total_potongan_kasbon']) ?>
                                </div>
                            </td>
                            <td class="text-end">
                                <div class="fw-semibold <?= $penyesuaian > 0 ? 'text-success' : ($penyesuaian < 0 ? 'text-danger' : 'text-muted') ?>">
                                    <?= $penyesuaian > 0 ? '+' : '' ?><?= formatRupiah((int)$penyesuaian) ?>
                                </div>
                                <?php if ($it['catatan_tunjangan_lain'] || $it['catatan_potongan_lain']): ?>
                                    <small class="text-muted d-block text-truncate" style="max-width: 150px; font-size: 0.7rem;" title="<?= e($it['catatan_tunjangan_lain'] . ' ' . $it['catatan_potongan_lain']) ?>">
                                        <i class="bi bi-info-circle"></i> Info
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
                                            data-debt="<?= (int)$it['total_potongan_kasbon'] ?>"
                                            data-bonus="<?= (int)$it['tunjangan_lain'] ?>"
                                            data-bnotes="<?= e($it['catatan_tunjangan_lain']) ?>"
                                            data-ded="<?= (int)$it['potongan_lain'] ?>"
                                            data-dnotes="<?= e($it['catatan_potongan_lain']) ?>"
                                            data-round="<?= (int)$it['nominal_pembulatan'] ?>"
                                            data-net="<?= (int)$it['gaji_bersih'] ?>"
                                            data-bs-toggle="modal" data-bs-target="#modalAdjust">
                                        <i class="bi bi-pencil-square"></i> Sesuaikan
                                    </button>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($isDraft): ?>
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
                            <div class="p-3 bg-light rounded-3 border">
                                <div class="text-muted small fw-bold mb-1">TOTAL PENDAPATAN SEMENTARA</div>
                                <div class="fs-4 fw-bold text-dark" id="adj_base_text">Rp 0</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-danger-subtle border border-danger-subtle rounded-3 text-danger">
                                <label for="adj_debt_input" class="small fw-bold mb-1 d-block text-danger">POTONGAN HUTANG / KASBON (RP)</label>
                                <input type="text" class="form-control input-rupiah border-danger-subtle text-danger fw-bold fs-4 bg-white" id="adj_debt_input" name="total_potongan_kasbon" value="0" onkeyup="calculateNet()">
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Penambahan & Pemotongan Manual</h6>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-success fw-bold">Bonus / Penambahan Lain (Rp)</label>
                            <input type="text" class="form-control input-rupiah border-success-subtle text-success fw-semibold" id="adj_bonus" name="tunjangan_lain" value="0" onkeyup="calculateNet()">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Keterangan Bonus</label>
                            <input type="text" class="form-control" id="adj_bnotes" name="catatan_tunjangan_lain" placeholder="Misal: Bonus lembur">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label text-danger fw-bold">Pemotongan Manual Lain (Rp)</label>
                            <input type="text" class="form-control input-rupiah border-danger-subtle text-danger fw-semibold" id="adj_ded" name="potongan_lain" value="0" onkeyup="calculateNet()">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Keterangan Pemotongan</label>
                            <input type="text" class="form-control" id="adj_dnotes" name="catatan_potongan_lain" placeholder="Misal: Denda keterlambatan">
                        </div>
                    </div>

                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Pembulatan Gaji (F4-07)</h6>
                    <div class="row g-3 align-items-center bg-light p-3 rounded-3 border">
                        <div class="col-md-6">
                            <p class="text-muted small mb-0">Jika angka net gaji tidak bulat (misal belakangnya bukan 000), Anda bisa menginput nominal angka penambah pembulatan di sini agar gajinya pas.</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Angka Penambah Pembulatan (Rp)</label>
                            <input type="text" class="form-control input-rupiah" id="adj_round" name="nominal_pembulatan" value="0" onkeyup="calculateNet()">
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

document.addEventListener('DOMContentLoaded', function() {
    const editBtns = document.querySelectorAll('.btn-edit-item');
    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('adj_id').value = this.dataset.id;
            document.getElementById('adj_name').innerText = '- ' + this.dataset.name;
            
            currentBase = parseInt(this.dataset.base);
            currentDebt = parseInt(this.dataset.debt);
            
            document.getElementById('adj_base_text').innerText = formatRupiahJs(currentBase);
            document.getElementById('adj_debt_input').value = formatRupiahJs(currentDebt).replace('Rp ', '');
            
            document.getElementById('adj_bonus').value = formatRupiahJs(parseInt(this.dataset.bonus)).replace('Rp ', '');
            document.getElementById('adj_bnotes').value = this.dataset.bnotes;
            document.getElementById('adj_ded').value = formatRupiahJs(parseInt(this.dataset.ded)).replace('Rp ', '');
            document.getElementById('adj_dnotes').value = this.dataset.dnotes;
            document.getElementById('adj_round').value = formatRupiahJs(parseInt(this.dataset.round)).replace('Rp ', '');
            
            calculateNet();
        });
    });
});

function calculateNet() {
    const parseRupiah = (val) => parseInt(val.toString().replace(/[^0-9]/g, '') || 0);
    
    let debt = parseRupiah(document.getElementById('adj_debt_input').value);
    let bonus = parseRupiah(document.getElementById('adj_bonus').value);
    let ded = parseRupiah(document.getElementById('adj_ded').value);
    let round = parseRupiah(document.getElementById('adj_round').value);
    
    let net = currentBase - debt + bonus - ded + round;
    document.getElementById('adj_net_final').innerText = formatRupiahJs(net);
}

function formatRupiahJs(angka) {
    let number_string = angka.toString().replace(/[^,\d]/g, ''),
        split = number_string.split(','),
        sisa = split[0].length % 3,
        rupiah = split[0].substr(0, sisa),
        ribuan = split[0].substr(sisa).match(/\d{3}/gi);

    if (ribuan) {
        separator = sisa ? '.' : '';
        rupiah += separator + ribuan.join('.');
    }
    rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
    return 'Rp ' + rupiah;
}
</script>
<?php endif; ?>
