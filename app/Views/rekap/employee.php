

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h3 fw-bold text-dark mb-1">Rapor & Profil Karyawan</h2>
        <p class="text-muted mb-0">Lihat histori absensi, lembur, dan kasbon per individu.</p>
    </div>
    <div>
        <a href="<?= url('/rekap') ?>" class="btn btn-outline-secondary rounded-pill fw-bold">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <form method="GET" action="<?= url('/rekap/employee') ?>" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label text-muted fw-bold">Pilih Karyawan</label>
                <select name="id_karyawan" class="form-select form-select-lg" required>
                    <option value="">-- Pilih Karyawan --</option>
                    <?php foreach ($employees as $e): ?>
                        <option value="<?= $e['id'] ?>" <?= $emp_id == $e['id'] ? 'selected' : '' ?>>
                            <?= e($e['name']) ?> (<?= ucfirst($e['tipe_gaji']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label text-muted fw-bold">Tanggal Mulai</label>
                <input type="date" name="start_date" class="form-control form-control-lg" value="<?= e($start_date) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label text-muted fw-bold">Tanggal Akhir</label>
                <input type="date" name="end_date" class="form-control form-control-lg" value="<?= e($end_date) ?>" required>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill fw-bold">
                    <i class="bi bi-search"></i> Cek
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($empData): ?>
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm rounded-4 bg-white">
                <div class="card-body p-4 p-md-4 d-flex align-items-center">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-4" style="width: 75px; height: 75px; background-color: #f8f9fa; border: 1px solid #dee2e6;">
                        <i class="bi bi-person-fill text-secondary" style="font-size: 3rem;"></i>
                    </div>
                    <div>
                        <h2 class="fw-bold text-dark mb-2" style="letter-spacing: -0.5px;"><?= e($empData['name']) ?></h2>
                        <div class="d-flex align-items-center">
                            <?php 
                                $isBulanan = $empData['tipe_gaji'] === 'bulanan';
                                $badgeBg = $isBulanan ? 'rgba(13, 110, 253, 0.1)' : 'rgba(25, 135, 84, 0.1)';
                                $badgeText = $isBulanan ? '#0d6efd' : '#198754';
                            ?>
                            <span class="badge rounded-pill px-3 py-2 fw-normal" style="background-color: <?= $badgeBg ?>; color: <?= $badgeText ?>; font-size: 0.85rem; letter-spacing: 0.2px;">
                                <i class="bi bi-briefcase-fill me-1"></i> Karyawan <?= ucfirst($empData['tipe_gaji']) ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4 text-center">
                    <h6 class="text-muted fw-bold mb-3">Kehadiran</h6>
                    <h3 class="fw-bold text-dark mb-0"><?= $stats['hadir'] ?> <span class="fs-6 text-muted">Hari</span></h3>
                    <?php if ($stats['absen'] > 0): ?>
                        <small class="text-danger fw-bold"><?= $stats['absen'] ?> absen/izin</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4 text-center">
                    <h6 class="text-muted fw-bold mb-3">Total Upah Produksi</h6>
                    <h3 class="fw-bold text-success mb-0">Rp <?= formatRupiah($stats['produksi_reguler']) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4 text-center">
                    <h6 class="text-muted fw-bold mb-3">Total Upah Lembur</h6>
                    <h3 class="fw-bold text-warning-emphasis mb-0">Rp <?= formatRupiah($stats['uang_lembur']) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4 text-center">
                    <h6 class="text-muted fw-bold mb-3">Sisa Kasbon Saat Ini</h6>
                    <h3 class="fw-bold text-danger mb-0">Rp <?= formatRupiah($stats['kasbon_sisa']) ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Log Aktivitas -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
            <h5 class="fw-bold text-dark mb-0">Log Aktivitas Harian</h5>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="py-3 px-3 rounded-start">Tanggal</th>
                            <th class="py-3 px-3 text-center">Status Absen</th>
                            <th class="py-3 px-3">Catatan / Detail Pekerjaan</th>
                            <th class="py-3 px-3 text-end">Uang Lembur (Rp)</th>
                            <th class="py-3 px-3 text-end rounded-end">Upah Borongan (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">Tidak ada aktivitas pada rentang tanggal ini.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $date => $log): ?>
                                <?php 
                                    $hasAbsen = isset($log['absensi']);
                                    $isHadir = $hasAbsen && $log['absensi']['hadir'] == 1;
                                    $lemburBulanan = $hasAbsen ? $log['absensi']['lembur_nominal'] : 0;
                                    $noteAbsen = $hasAbsen ? $log['absensi']['catatan'] : '';
                                    
                                    $totalLemburProd = 0;
                                    $totalUpahProd = 0;
                                    $prodDetails = [];
                                    
                                    if (isset($log['produksi'])) {
                                        foreach ($log['produksi'] as $p) {
                                            $upah = $p['kuantitas'] * $p['harga_per_bungkus'];
                                            $lembur = $p['lembur_kuantitas'] * $p['harga_per_bungkus'];
                                            $totalUpahProd += $upah;
                                            $totalLemburProd += $lembur;
                                            $prodDetails[] = $p['kuantitas'] . 'x ' . $p['product_name'] . ($p['lembur_kuantitas'] > 0 ? ' (Lembur: '.$p['lembur_kuantitas'].'x)' : '');
                                        }
                                    }
                                    
                                    $uangLemburTampil = ($empData['tipe_gaji'] === 'bulanan') ? $lemburBulanan : $totalLemburProd;
                                ?>
                                <tr>
                                    <td class="px-3 fw-bold text-dark"><?= date('d M Y', strtotime($date)) ?></td>
                                    <td class="px-3 text-center">
                                        <?php if ($hasAbsen): ?>
                                            <?php if ($isHadir): ?>
                                                <?php if ($uangLemburTampil > 0): ?>
                                                    <span class="badge bg-primary rounded-pill px-3">Lembur</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success rounded-pill px-3">Hadir</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge bg-danger rounded-pill px-3">Absen</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border px-3">Kosong</span>
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
                                    <td class="px-3 text-end fw-bold <?= $uangLemburTampil > 0 ? 'text-warning-emphasis' : 'text-muted' ?>">
                                        <?= $uangLemburTampil > 0 ? formatRupiah($uangLemburTampil) : '-' ?>
                                    </td>
                                    <td class="px-3 text-end fw-bold <?= $totalUpahProd > 0 ? 'text-success' : 'text-muted' ?>">
                                        <?= $totalUpahProd > 0 ? formatRupiah($totalUpahProd) : '-' ?>
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
    <div class="text-center py-5">
        <i class="bi bi-person-bounding-box fs-1 text-muted d-block mb-3 opacity-25"></i>
        <p class="text-muted fw-bold">Pilih karyawan di atas untuk melihat detail rapornya.</p>
    </div>
<?php endif; ?>


