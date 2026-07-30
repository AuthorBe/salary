<?php
$hour = (int) date('H');
$greeting = $hour < 12 ? 'Selamat Pagi' : ($hour < 17 ? 'Selamat Siang' : 'Selamat Malam');
$user = currentUser();

// Fallback pengaman data (opsional)
$totalEmployees = $totalEmployees ?? 0;
$presentTodayCount = $presentTodayCount ?? 0;
$todayAttendances = $todayAttendances ?? [];
$totalBungkusToday = $totalBungkusToday ?? 0;
$totalBalToday = $totalBalToday ?? 0;
$pendingPayroll = $pendingPayroll ?? null;
$topEmployees = $topEmployees ?? [];
$recentActivities = $recentActivities ?? [];
?>

<!-- Page Header -->
<div class="page-title-box mb-4">
    <div class="page-title-left">
        <h2 class="h4 mb-1 fw-bold d-flex align-items-center">
            <i class="bi bi-grid-1x2-fill text-primary me-2 fs-4"></i> Dashboard
        </h2>
        <p class="text-muted mb-0 fs-7 ms-4 ps-1">
            <?= e($greeting) ?>, <strong><?= e($user['name']) ?></strong>! 👋
            &nbsp;|&nbsp;
            <?= date('l, d F Y') ?>
        </p>
    </div>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon stat-icon-primary">
            <i class="bi bi-people-fill"></i>
        </div>
        <div class="stat-info">
            <span class="stat-label">Total Karyawan Aktif</span>
            <span class="stat-value"><?= e((string)$totalEmployees) ?></span>
            <span class="stat-sub">Semua Tipe Gaji</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon stat-icon-success">
            <i class="bi bi-calendar-check-fill"></i>
        </div>
        <div class="stat-info">
            <span class="stat-label">Hadir Hari Ini</span>
            <span class="stat-value"><?= e((string)$presentTodayCount) ?></span>
            <span class="stat-sub">Dari <?= count($todayAttendances) ?> Data</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon stat-icon-warning">
            <i class="bi bi-box-fill"></i>
        </div>
        <div class="stat-info">
            <span class="stat-label">Produksi Hari Ini</span>
            <span class="stat-value"><?= number_format($totalBungkusToday, 0, ',', '.') ?> Bks</span>
            <span class="stat-sub"><?= number_format($totalBalToday, 0, ',', '.') ?> Bal</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon stat-icon-info">
            <i class="bi bi-cash-stack"></i>
        </div>
        <div class="stat-info">
            <span class="stat-label">Payroll Draft</span>
            <span class="stat-value"><?= $pendingPayroll ? e(formatTanggalShort($pendingPayroll['periode_awal'])) : 'Tidak Ada' ?></span>
            <span class="stat-sub"><?= $pendingPayroll ? e(formatRupiah((int)($pendingPayroll['total_nominal'] ?? 0))) : 'Semua Lunas' ?></span>
        </div>
    </div>
</div>

<div class="row mt-4">
    <!-- Top Karyawan Produksi -->
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-trophy-fill text-warning"></i>
                    Top Produksi (Minggu Ini)
                </h3>
            </div>
            <div class="card-body p-0">
                <?php if (empty($topEmployees)): ?>
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-inbox fs-2 mb-2 d-block"></i>
                        Belum ada data produksi minggu ini.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4 text-muted" style="font-size: 0.75rem; text-transform: uppercase;">Karyawan</th>
                                    <th class="text-end pe-4 text-muted" style="font-size: 0.75rem; text-transform: uppercase;">Total Produksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topEmployees as $index => $emp): ?>
                                    <tr>
                                        <td class="ps-4 py-3">
                                            <div class="d-flex align-items-center gap-3">
                                                <span class="badge bg-light text-secondary border rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                                    <?= $index + 1 ?>
                                                </span>
                                                <div>
                                                    <div class="fw-semibold text-dark"><?= e($emp['name']) ?></div>
                                                    <div class="text-muted" style="font-size: 0.75rem; text-transform: capitalize;"><?= e($emp['tipe_gaji']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end pe-4 fw-bold text-success align-middle">
                                            <?= number_format((int)$emp['total_bungkus'], 0, ',', '.') ?> <span class="fw-normal text-muted" style="font-size: 0.8rem;">Bks</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Aktivitas Terbaru -->
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="bi bi-activity text-primary"></i>
                    Aktivitas Terbaru
                </h3>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentActivities)): ?>
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-clock-history fs-2 mb-2 d-block"></i>
                        Belum ada aktivitas tercatat.
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recentActivities as $act): ?>
                            <div class="list-group-item d-flex align-items-start gap-3 p-3">
                                <?php if ($act['type'] === 'Kehadiran'): ?>
                                    <div class="text-success mt-1"><i class="bi bi-calendar-check-fill fs-5"></i></div>
                                <?php elseif ($act['type'] === 'Produksi'): ?>
                                    <div class="text-primary mt-1"><i class="bi bi-box-fill fs-5"></i></div>
                                <?php elseif ($act['type'] === 'Kasbon'): ?>
                                    <div class="text-warning mt-1"><i class="bi bi-credit-card-fill fs-5"></i></div>
                                <?php else: ?>
                                    <div class="text-secondary mt-1"><i class="bi bi-info-circle-fill fs-5"></i></div>
                                <?php endif; ?>
                                
                                <div>
                                    <div class="fw-medium text-dark" style="font-size: 0.9rem;">
                                        <?= e($act['keterangan']) ?>
                                    </div>
                                    <div class="text-muted mt-1" style="font-size: 0.75rem;">
                                        <i class="bi bi-clock"></i> <?= date('d M Y, H:i', strtotime($act['created_at'])) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
