<?php
/**
 * @var string $title
 * @var string $pageTitle
 * @var string $start_date
 * @var string $end_date
 * @var array $employees
 * @var int $emp_id
 * @var array|null $empData
 * @var array $stats
 * @var array $logs
 */
?>

<div class="page-title-box d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
    <div class="page-title-left">
        <h4 class="mb-1 text-dark fw-bold d-flex align-items-center">
            <i class="bi bi-person-vcard-fill text-success me-2 fs-4"></i> Rapor & Detail Karyawan
        </h4>
        <p class="text-muted mb-0 fs-7 ms-4 ps-1">
            Lihat histori aktivitas absensi, produksi, lembur, kasbon, dan tabungan per individu
        </p>
    </div>
    <div>
        <a href="<?= url('/rekap') ?>" class="btn btn-outline-secondary rounded-pill px-4 fw-semibold">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <form method="GET" action="<?= url('/rekap/employee') ?>" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label text-muted fw-bold small">Pilih Karyawan</label>
                <select name="id_karyawan" class="form-select searchable-select" required>
                    <option value="">-- Pilih Karyawan --</option>
                    <?= renderEmployeeOptions($employees, $emp_id) ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label text-muted fw-bold small">Tanggal Mulai</label>
                <input type="date" name="start_date" class="form-control" value="<?= e($start_date) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label text-muted fw-bold small">Tanggal Akhir</label>
                <input type="date" name="end_date" class="form-control" value="<?= e($end_date) ?>" required>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100 rounded-pill fw-semibold py-2">
                    <i class="bi bi-search me-1"></i> Cek Data
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($empData): ?>
    <!-- Employee Header Card -->
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
        <div class="card-body p-4 d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 bg-light border" style="width: 64px; height: 64px;">
                    <i class="bi bi-person-fill text-secondary fs-2"></i>
                </div>
                <div>
                    <h4 class="fw-bold text-dark mb-1"><?= e($empData['name']) ?></h4>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <?php 
                            $isBulanan = $empData['tipe_gaji'] === 'bulanan';
                            $badgeBg = $isBulanan ? 'rgba(13, 110, 253, 0.1)' : 'rgba(25, 135, 84, 0.1)';
                            $badgeText = $isBulanan ? '#0d6efd' : '#198754';
                        ?>
                        <span class="badge rounded-pill px-3 py-1.5 fw-semibold" style="background-color: <?= $badgeBg ?>; color: <?= $badgeText ?>; font-size: 0.8rem;">
                            <i class="bi bi-briefcase-fill me-1"></i> Karyawan <?= ucfirst($empData['tipe_gaji']) ?>
                        </span>
                        <span class="text-muted small">
                            <i class="bi bi-calendar3 me-1"></i> Periode: <strong><?= e(formatRentangTanggal($start_date, $end_date)) ?></strong>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Stats (6 Cards Grid) -->
    <div class="row g-3 g-md-3 mb-4">
        <!-- 1. Hadir -->
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 text-center d-flex flex-column justify-content-center">
                    <span class="text-muted fw-semibold small mb-1">Kehadiran</span>
                    <h4 class="fw-bold text-dark mb-0"><?= $stats['hadir'] ?> <span class="fs-7 fw-normal text-muted">Hari</span></h4>
                    <?php if ($stats['absen'] > 0): ?>
                        <small class="text-danger fw-bold mt-1" style="font-size: 0.72rem;"><?= $stats['absen'] ?> absen/izin</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <!-- 2. Keterlambatan -->
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="border-left: 3px solid #fd7e14 !important;">
                <div class="card-body p-3 text-center d-flex flex-column justify-content-center">
                    <span class="fw-semibold small mb-1" style="color: #fd7e14;">Keterlambatan</span>
                    <h4 class="fw-bold mb-0" style="color: <?= $stats['telat'] > 0 ? '#fd7e14' : '#adb5bd' ?>">
                        <?= $stats['telat'] ?> <span class="fs-7 text-muted fw-normal">Kali</span>
                    </h4>
                    <small class="mt-1 <?= $stats['telat'] > 0 ? 'fw-bold' : 'text-muted' ?>" style="font-size: 0.72rem; color: <?= $stats['telat'] > 0 ? '#fd7e14' : '' ?>;">
                        <?= $stats['telat'] > 0 ? 'Terlambat hadir' : 'Tepat waktu' ?>
                    </small>
                </div>
            </div>
        </div>
        <!-- 3. Upah Produksi -->
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 text-center d-flex flex-column justify-content-center">
                    <span class="text-muted fw-semibold small mb-1">Upah Produksi</span>
                    <h5 class="fw-bold text-success mb-0 text-truncate"><?= formatRupiah($stats['produksi_reguler']) ?></h5>
                </div>
            </div>
        </div>
        <!-- 4. Upah Lembur -->
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 text-center d-flex flex-column justify-content-center">
                    <span class="text-muted fw-semibold small mb-1">Upah Lembur</span>
                    <h5 class="fw-bold text-warning-emphasis mb-0 text-truncate"><?= formatRupiah($stats['uang_lembur']) ?></h5>
                </div>
            </div>
        </div>
        <!-- 5. Sisa Kasbon -->
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 text-center d-flex flex-column justify-content-center">
                    <span class="text-muted fw-semibold small mb-1">Sisa Kasbon</span>
                    <h5 class="fw-bold text-danger mb-0 text-truncate"><?= formatRupiah($stats['kasbon_sisa']) ?></h5>
                </div>
            </div>
        </div>
        <!-- 6. Saldo Tabungan -->
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 text-center d-flex flex-column justify-content-center">
                    <span class="text-muted fw-semibold small mb-1">Saldo Tabungan</span>
                    <h5 class="fw-bold text-primary mb-0 text-truncate"><?= formatRupiah($stats['tabungan_saldo']) ?></h5>
                </div>
            </div>
        </div>
    </div>

    <!-- Log Aktivitas Harian Table -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-bottom pt-3 pb-3 px-4">
            <h5 class="fw-bold text-dark mb-0">Log Aktivitas Harian</h5>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="py-3 px-3 rounded-start" style="width: 130px;">Tanggal</th>
                            <th class="py-3 px-3 text-center" style="width: 140px;">Status Absen</th>
                            <th class="py-3 px-3">Catatan / Detail Produksi</th>
                            <th class="py-3 px-3 text-end">Lembur</th>
                            <th class="py-3 px-3 text-end">Produksi</th>
                            <th class="py-3 px-3 text-end">Kasbon</th>
                            <th class="py-3 px-3 text-end rounded-end">Tabungan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                                    Tidak ada catatan aktivitas pada rentang tanggal ini.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $date => $log): ?>
                                <?php 
                                    $hasAbsen = isset($log['absensi']);
                                    $isHadir = $hasAbsen && (int)$log['absensi']['hadir'] === 1;
                                    $lemburBulanan = $hasAbsen ? (float)$log['absensi']['lembur_nominal'] : 0.0;
                                    $noteAbsen = $hasAbsen ? (string)$log['absensi']['catatan'] : '';
                                    
                                    $totalLemburProd = 0.0;
                                    $totalUpahProd = 0.0;
                                    $prodDetails = [];
                                    
                                    if (isset($log['produksi'])) {
                                        foreach ($log['produksi'] as $p) {
                                            $upah = (float)$p['kuantitas'] * (float)$p['harga_per_bungkus'];
                                            $lembur = (float)$p['lembur_kuantitas'] * (float)$p['harga_per_bungkus'];
                                            $totalUpahProd += $upah;
                                            $totalLemburProd += $lembur;
                                            $prodDetails[] = $p['kuantitas'] . 'x ' . $p['product_name'] . ($p['lembur_kuantitas'] > 0 ? ' (Lembur: '.$p['lembur_kuantitas'].'x)' : '');
                                        }
                                    }
                                    
                                    $uangLemburTampil = ($empData['tipe_gaji'] === 'bulanan') ? $lemburBulanan : $totalLemburProd;
                                ?>
                                <tr>
                                    <td class="px-3 fw-bold text-dark text-nowrap">
                                        <?= e(formatTanggalShort($date)) ?>
                                    </td>
                                    <td class="px-3 text-center">
                                        <?php if ($hasAbsen): ?>
                                            <?php if ($isHadir): ?>
                                                <?php $isTelat = isset($log['absensi']['telat']) && (int)$log['absensi']['telat'] === 1; ?>
                                                <div class="d-flex justify-content-center gap-1 flex-wrap">
                                                    <?php if ($isTelat): ?>
                                                        <span class="badge rounded-pill px-2.5 py-1" style="background-color: #fd7e14; color: #fff;">
                                                            <i class="bi bi-clock-history me-1"></i>Telat
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success rounded-pill px-2.5 py-1">Hadir</span>
                                                    <?php endif; ?>
                                                    <?php if ($uangLemburTampil > 0): ?>
                                                        <span class="badge bg-primary rounded-pill px-2.5 py-1">Lembur</span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="badge bg-danger rounded-pill px-2.5 py-1">Absen</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border px-2.5 py-1">Kosong</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-3">
                                        <?php if ($noteAbsen): ?>
                                            <div class="text-danger small fw-bold mb-1"><i class="bi bi-info-circle me-1"></i><?= e($noteAbsen) ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($prodDetails)): ?>
                                            <ul class="mb-0 ps-3 small text-muted">
                                                <?php foreach ($prodDetails as $pd): ?>
                                                    <li><?= e($pd) ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-3 text-end fw-bold <?= $uangLemburTampil > 0 ? 'text-warning-emphasis' : 'text-muted' ?> text-nowrap">
                                        <?= $uangLemburTampil > 0 ? formatRupiah($uangLemburTampil) : '—' ?>
                                    </td>
                                    <td class="px-3 text-end fw-bold <?= $totalUpahProd > 0 ? 'text-success' : 'text-muted' ?> text-nowrap">
                                        <?= $totalUpahProd > 0 ? formatRupiah($totalUpahProd) : '—' ?>
                                    </td>
                                    <td class="px-3 text-end text-nowrap">
                                        <?php if (!empty($log['kasbon'])): ?>
                                            <div class="d-flex flex-column gap-1 align-items-end">
                                                <?php foreach ($log['kasbon'] as $k): ?>
                                                    <span class="badge <?= $k['type'] === 'pinjam' ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-success-subtle text-success border border-success-subtle' ?> fw-semibold py-1 px-2" title="<?= e($k['keterangan']) ?>">
                                                        <i class="bi <?= $k['type'] === 'pinjam' ? 'bi-arrow-down-left' : 'bi-arrow-up-right' ?> me-1"></i>
                                                        <?= $k['type'] === 'pinjam' ? 'Pinjam: ' : 'Bayar: ' ?> <?= formatRupiah($k['nominal']) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-3 text-end text-nowrap">
                                        <?php if (!empty($log['tabungan'])): ?>
                                            <div class="d-flex flex-column gap-1 align-items-end">
                                                <?php foreach ($log['tabungan'] as $t): ?>
                                                    <span class="badge <?= $t['tipe'] === 'deposit' ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' ?> fw-semibold py-1 px-2" title="<?= e($t['keterangan']) ?>">
                                                        <i class="bi <?= $t['tipe'] === 'deposit' ? 'bi-plus-circle' : 'bi-dash-circle' ?> me-1"></i>
                                                        <?= $t['tipe'] === 'deposit' ? 'Setor: ' : 'Tarik: ' ?> <?= formatRupiah($t['jumlah']) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
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
<?php elseif ($emp_id > 0): ?>
    <div class="alert alert-danger rounded-4">Karyawan tidak ditemukan atau tidak aktif.</div>
<?php else: ?>
    <div class="card border-0 shadow-sm rounded-4 text-center py-5">
        <div class="card-body">
            <i class="bi bi-person-badge fs-1 text-muted d-block mb-3 opacity-25"></i>
            <h6 class="fw-bold text-dark mb-1">Pilih Karyawan</h6>
            <p class="text-muted small mb-0">Pilih salah satu karyawan di atas untuk melihat detail rapor dan riwayat aktivitasnya.</p>
        </div>
    </div>
<?php endif; ?>
