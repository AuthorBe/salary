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
            <span class="stat-value"><?= $pendingPayroll ? '1 Draft' : 'Tidak Ada' ?></span>
            <span class="stat-sub"><?= $pendingPayroll ? e(formatRupiah((int)($pendingPayroll['total_nominal'] ?? 0))) : 'Semua Lunas' ?></span>
        </div>
    </div>
</div>

<?php
$topEmployees = $topEmployees ?? [];
$topEmployeesLastWeek = $topEmployeesLastWeek ?? [];
$topEmployees7Days = $topEmployees7Days ?? [];
$weekPeriod = $weekPeriod ?? ['start' => date('Y-m-d'), 'end' => date('Y-m-d')];
$lastWeekPeriod = $lastWeekPeriod ?? ['start' => date('Y-m-d'), 'end' => date('Y-m-d')];
$sevenDaysPeriod = $sevenDaysPeriod ?? ['start' => date('Y-m-d'), 'end' => date('Y-m-d')];

$renderTopTable = function(array $list, string $emptyMsg, string $periodLabel) {
    if (empty($list)): ?>
        <div class="p-4 text-center text-muted">
            <i class="bi bi-inbox fs-2 mb-2 d-block opacity-50"></i>
            <div class="fw-medium text-secondary"><?= $emptyMsg ?></div>
            <small class="text-muted d-block mt-1"><i class="bi bi-calendar-range me-1"></i>Periode: <?= $periodLabel ?></small>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4 text-muted" style="font-size: 0.75rem; text-transform: uppercase;">Karyawan</th>
                        <th class="text-end pe-4 text-muted" style="font-size: 0.75rem; text-transform: uppercase;">Total Produksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($list as $index => $emp): 
                        $badgeStyle = match($index) {
                            0 => 'background-color: #fef3c7; color: #b45309; border: 1px solid #fde68a;',
                            1 => 'background-color: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;',
                            2 => 'background-color: #ffedd5; color: #c2410c; border: 1px solid #fed7aa;',
                            default => 'background-color: #f8fafc; color: #64748b; border: 1px solid #e2e8f0;'
                        };
                        $rankLabel = match($index) {
                            0 => '🥇',
                            1 => '🥈',
                            2 => '🥉',
                            default => (string)($index + 1)
                        };
                    ?>
                        <tr>
                            <td class="ps-4 py-3">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="badge rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 28px; height: 28px; font-size: 0.8rem; <?= $badgeStyle ?>">
                                        <?= $rankLabel ?>
                                    </span>
                                    <div>
                                        <div class="fw-semibold text-dark"><?= e($emp['name']) ?></div>
                                        <div class="text-muted" style="font-size: 0.75rem; text-transform: capitalize;"><?= e($emp['tipe_gaji']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-end pe-4 align-middle">
                                <div class="fw-bold text-success">
                                    <?= number_format((int)$emp['total_bungkus'], 0, ',', '.') ?> <span class="fw-normal text-muted" style="font-size: 0.8rem;">Bks</span>
                                </div>
                                <?php if (!empty($emp['total_bal']) && (int)$emp['total_bal'] > 0): ?>
                                    <div class="text-muted" style="font-size: 0.75rem;">
                                        <?= number_format((int)$emp['total_bal'], 0, ',', '.') ?> Bal
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="p-2 px-4 bg-light border-top text-muted d-flex align-items-center justify-content-between" style="font-size: 0.75rem;">
            <span><i class="bi bi-calendar-check me-1"></i> <?= $periodLabel ?></span>
            <span class="text-secondary"><?= count($list) ?> Karyawan Teratas</span>
        </div>
    <?php endif;
};
?>

<style>
/* Segmented Control - Modern iOS/SaaS Pill Switcher */
.segmented-control {
    display: inline-flex;
    align-items: center;
    background-color: #f1f5f9;
    padding: 3px;
    border-radius: 9999px;
    border: 1px solid #e2e8f0;
    gap: 3px;
}

.segmented-control .segmented-pill {
    border: none;
    background: transparent;
    color: #64748b;
    font-size: 0.775rem;
    font-weight: 500;
    padding: 5px 14px;
    border-radius: 9999px;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    line-height: 1.3;
    cursor: pointer;
    white-space: nowrap;
    text-decoration: none;
    user-select: none;
    outline: none;
}

.segmented-control .segmented-pill:hover:not(.active) {
    color: #0f172a;
    background-color: rgba(255, 255, 255, 0.6);
}

.segmented-control .segmented-pill.active {
    background-color: #ffffff;
    color: #2563eb;
    font-weight: 600;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08), 0 1px 2px rgba(15, 23, 42, 0.04);
}

.segmented-control .segmented-pill:focus-visible {
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.35);
}
</style>

<div class="row mt-4">
    <!-- Top Karyawan Produksi -->
    <div class="col-lg-6 mb-4">
        <div class="card h-100 border-0 shadow-sm" style="border-radius: var(--radius-lg, 16px); overflow: hidden;">
            <div class="card-header bg-white d-flex align-items-center justify-content-between flex-wrap gap-2 py-3 px-4" style="border-bottom: 1px solid #f1f5f9;">
                <h3 class="card-title mb-0 fs-6 fw-bold d-flex align-items-center text-dark">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-3 me-2.5" style="width: 32px; height: 32px; background: #fef3c7; color: #d97706;">
                        <i class="bi bi-trophy-fill fs-6"></i>
                    </span>
                    <span>Top Produksi</span>
                </h3>
                <div class="segmented-control" id="topProdTabs" role="tablist">
                    <button class="segmented-pill active" id="tab-pekan-ini" data-bs-toggle="tab" data-bs-target="#content-pekan-ini" type="button" role="tab" aria-controls="content-pekan-ini" aria-selected="true">
                        Pekan Ini
                    </button>
                    <button class="segmented-pill" id="tab-7-hari" data-bs-toggle="tab" data-bs-target="#content-7-hari" type="button" role="tab" aria-controls="content-7-hari" aria-selected="false">
                        7 Hari
                    </button>
                    <button class="segmented-pill" id="tab-pekan-lalu" data-bs-toggle="tab" data-bs-target="#content-pekan-lalu" type="button" role="tab" aria-controls="content-pekan-lalu" aria-selected="false">
                        Pekan Lalu
                    </button>
                </div>
            </div>
            <div class="card-body p-0 tab-content" id="topProdTabContent">
                <div class="tab-pane fade show active" id="content-pekan-ini" role="tabpanel">
                    <?php 
                    $pekanIniLabel = formatTanggalShort($weekPeriod['start']) . ' – ' . formatTanggalShort($weekPeriod['end']);
                    $renderTopTable($topEmployees, 'Belum ada data produksi pada pekan berjalan.', $pekanIniLabel); 
                    ?>
                </div>
                <div class="tab-pane fade" id="content-7-hari" role="tabpanel">
                    <?php 
                    $sevenDaysLabel = formatTanggalShort($sevenDaysPeriod['start']) . ' – ' . formatTanggalShort($sevenDaysPeriod['end']);
                    $renderTopTable($topEmployees7Days, 'Belum ada data produksi dalam 7 hari terakhir.', $sevenDaysLabel); 
                    ?>
                </div>
                <div class="tab-pane fade" id="content-pekan-lalu" role="tabpanel">
                    <?php 
                    $lastWeekLabel = formatTanggalShort($lastWeekPeriod['start']) . ' – ' . formatTanggalShort($lastWeekPeriod['end']);
                    $renderTopTable($topEmployeesLastWeek, 'Belum ada data produksi pada pekan lalu.', $lastWeekLabel); 
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Aktivitas Terbaru -->
    <div class="col-lg-6 mb-4">
        <div class="card h-100 border-0 shadow-sm" style="border-radius: var(--radius-lg, 16px); overflow: hidden;">
            <div class="card-header bg-white d-flex align-items-center justify-content-between flex-wrap gap-2 py-3 px-4" style="border-bottom: 1px solid #f1f5f9;">
                <h3 class="card-title mb-0 fs-6 fw-bold d-flex align-items-center text-dark">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-3 me-2.5" style="width: 32px; height: 32px; background: #e0f2fe; color: #0284c7;">
                        <i class="bi bi-activity fs-6"></i>
                    </span>
                    <span>Aktivitas Terbaru</span>
                </h3>
                <span class="badge bg-light text-muted border px-2.5 py-1" style="font-size: 0.75rem; font-weight: 500;">
                    <i class="bi bi-clock-history me-1"></i> Log Terbaru
                </span>
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
