<style>
/* ── Segmented Control - Modern SaaS Pill Switcher ────────────────────────── */
.segmented-control {
    display: inline-flex;
    align-items: center;
    background-color: #f1f5f9;
    padding: 5px;
    border-radius: 9999px;
    border: 1px solid #e2e8f0;
    gap: 6px;
}

.segmented-control .segmented-pill {
    border: none;
    background: transparent;
    color: #64748b;
    font-size: 0.825rem;
    font-weight: 500;
    padding: 8px 20px;
    border-radius: 9999px;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    line-height: 1.4;
    cursor: pointer;
    white-space: nowrap;
    text-decoration: none;
    user-select: none;
    outline: none;
}

.segmented-control .segmented-pill:hover:not(.active) {
    color: #0f172a;
    background-color: rgba(255, 255, 255, 0.7);
}

.segmented-control .segmented-pill.active {
    background-color: #ffffff;
    color: #2563eb;
    font-weight: 600;
    box-shadow: 0 2px 4px rgba(15, 23, 42, 0.08), 0 1px 2px rgba(15, 23, 42, 0.04);
}

/* ── Chart Mode Toggle Pill Switcher ─────────────────────────────────────── */
.chart-toggle-group {
    display: inline-flex;
    align-items: center;
    background-color: #f1f5f9;
    padding: 3px;
    border-radius: 9999px;
    border: 1px solid #e2e8f0;
    gap: 3px;
}

.chart-toggle-pill {
    border: none;
    background: transparent;
    color: #64748b;
    font-size: 0.775rem;
    font-weight: 500;
    padding: 5px 14px;
    border-radius: 9999px;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    cursor: pointer;
    line-height: 1.3;
}

.chart-toggle-pill:hover:not(.active) {
    color: #0f172a;
    background: rgba(255, 255, 255, 0.7);
}

.chart-toggle-pill.active {
    background: #ffffff;
    color: #2563eb;
    font-weight: 600;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
}

/* ── Horizontal Scrollable Chart ─────────────────────────────────────────── */
.chart-scroll-container {
    width: 100%;
    overflow-x: auto;
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
    padding-bottom: 4px;
}

.chart-scroll-container::-webkit-scrollbar {
    height: 5px;
}

.chart-scroll-container::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 9999px;
}

.chart-canvas-wrapper {
    position: relative;
    height: 220px;
    width: 100%;
    transition: width 0.15s ease;
}

/* ── Robust Report Toolbar Layout ────────────────────────────────────────── */
.report-toolbar-card {
    background: #ffffff;
    border-radius: 18px;
    padding: 16px 20px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.03);
    margin-bottom: 24px;
}

.report-toolbar-row {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 14px;
}

/* Date Filter Pill Container */
.report-date-filter {
    display: inline-flex;
    align-items: center;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 9999px;
    padding: 4px 6px 4px 14px;
    gap: 8px;
    flex-shrink: 0;
}

.report-filter-label {
    display: inline-flex;
    align-items: center;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #475569;
    white-space: nowrap;
}

.report-date-input {
    border: 1px solid #e2e8f0;
    background: #ffffff;
    font-size: 0.8125rem;
    font-weight: 500;
    color: #0f172a;
    padding: 4px 8px;
    border-radius: 8px;
    outline: none;
    width: 125px;
    cursor: pointer;
    transition: border-color 0.15s ease;
}

.report-date-input:focus {
    border-color: #3b82f6;
}

.report-date-sep {
    font-size: 0.75rem;
    color: #94a3b8;
    font-weight: 500;
    padding: 0 2px;
}

.report-btn-filter {
    border: none;
    background: #2563eb;
    color: #ffffff;
    font-size: 0.8125rem;
    font-weight: 600;
    padding: 6px 14px;
    border-radius: 9999px;
    display: inline-flex;
    align-items: center;
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
    box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);
}

.report-btn-filter:hover {
    background: #1d4ed8;
    transform: translateY(-1px);
    color: #ffffff;
}

/* Search Box */
.report-search-box {
    display: inline-flex;
    align-items: center;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 9999px;
    padding: 6px 16px;
    flex: 1 1 200px;
    min-width: 180px;
}

.report-search-input {
    border: none;
    background: transparent;
    font-size: 0.8125rem;
    color: #0f172a;
    outline: none;
    width: 100%;
    padding: 0;
}

/* Export PDF Box */
.report-export-box {
    display: inline-flex;
    align-items: center;
    flex: 0 0 auto;
}

.report-btn-export {
    border: none;
    background: #ef4444;
    color: #ffffff;
    font-size: 0.8125rem;
    font-weight: 600;
    padding: 8px 20px;
    border-radius: 9999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s ease;
    box-shadow: 0 2px 5px rgba(239, 68, 68, 0.25);
    white-space: nowrap;
    text-decoration: none;
}

.report-btn-export:hover {
    background: #dc2626;
    transform: translateY(-1px);
    color: #ffffff;
}

/* Bottom Summary Row */
.report-summary-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 14px;
    padding-top: 14px;
    border-top: 1px solid #f1f5f9;
}

.report-metrics-group {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}

.report-actions-group {
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-action-pill {
    border: 1px solid #e2e8f0;
    background: #ffffff;
    color: #475569;
    font-size: 0.8125rem;
    font-weight: 500;
    padding: 6px 16px;
    border-radius: 9999px;
    display: inline-flex;
    align-items: center;
    cursor: pointer;
    transition: all 0.15s ease;
    text-decoration: none;
}

.btn-action-pill:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    color: #0f172a;
}

/* ── Minimalist Metric Strip ─────────────────────────────────────────────── */
.metric-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 14px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 9999px;
    font-size: 0.8125rem;
}

.metric-pill-label {
    color: #64748b;
    font-weight: 500;
}

.metric-pill-value {
    color: #0f172a;
    font-weight: 700;
}

/* ── Custom Table Polish ─────────────────────────────────────────────────── */
.table-reports th {
    font-size: 0.725rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 600;
    color: #64748b;
    background-color: #f8fafc;
}

.custom-payroll-row {
    transition: background-color 0.15s ease;
    cursor: pointer;
}

.custom-payroll-row:hover {
    background-color: #f8fafc !important;
}

.custom-payroll-row.row-selected {
    background-color: #eff6ff !important;
}

.month-parent-row {
    cursor: pointer;
    transition: background-color 0.15s ease;
}

.month-parent-row:hover {
    background-color: #f8fafc !important;
}

.chevron-icon {
    transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.month-parent-row[aria-expanded="true"] .chevron-icon,
[data-bs-toggle="collapse"][aria-expanded="true"] .chevron-icon {
    transform: rotate(180deg);
}

/* ── Responsive Enhancements ────────────────────────────────────────────── */
@media (max-width: 767.98px) {
    .segmented-control {
        width: 100%;
        display: flex;
    }
    .segmented-control .segmented-pill {
        flex: 1 1 50%;
        padding: 8px 10px;
        font-size: 0.75rem;
        text-align: center;
    }
    .metric-pill {
        flex: 1 1 calc(50% - 4px);
        justify-content: space-between;
    }
}
</style>

<!-- Top Bar: Mode Switcher -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2.5">
    <div class="segmented-control" id="reportModeTabs" role="tablist">
        <button class="segmented-pill active" id="tab-mode-bulanan" data-bs-toggle="tab" data-bs-target="#content-mode-bulanan" type="button" role="tab" aria-controls="content-mode-bulanan" aria-selected="true">
            <i class="bi bi-calendar3 fs-6"></i>
            <span>Rekapitulasi Bulanan</span>
        </button>
        <button class="segmented-pill" id="tab-mode-kustom" data-bs-toggle="tab" data-bs-target="#content-mode-kustom" type="button" role="tab" aria-controls="content-mode-kustom" aria-selected="false">
            <i class="bi bi-sliders fs-6"></i>
            <span>Laporan Kustom / Multi-Batch</span>
        </button>
    </div>
    <span class="badge bg-light text-muted border px-3 py-2 small d-none d-sm-inline-flex align-items-center" style="border-radius: 9999px;">
        <i class="bi bi-shield-check text-success me-1.5"></i> Status Payroll Approved
    </span>
</div>

<div class="tab-content" id="reportModeContent">
    <!-- ==================== TAB 1: REKAP BULANAN ==================== -->
    <div class="tab-pane fade show active" id="content-mode-bulanan" role="tabpanel">
        <!-- Chart Tren Pengeluaran -->
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px; overflow: hidden;">
            <div class="card-header bg-white py-3 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2" style="border-bottom: 1px solid #f1f5f9;">
                <h6 class="card-title text-dark fw-bold mb-0 d-flex align-items-center fs-6">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-3 me-2" style="width: 28px; height: 28px; background: #e0f2fe; color: #0284c7;">
                        <i class="bi bi-graph-up-arrow fs-6"></i>
                    </span>
                    <span id="chartTitleText">Tren Beban Gaji (Per Bulan)</span>
                </h6>
                <div class="chart-toggle-group">
                    <button type="button" class="chart-toggle-pill active" id="btnChartModeMonthly">
                        <i class="bi bi-calendar3"></i> Per Bulan
                    </button>
                    <button type="button" class="chart-toggle-pill" id="btnChartModeBatch">
                        <i class="bi bi-lightning-charge"></i> Per Batch
                    </button>
                </div>
            </div>
            <div class="card-body p-3 p-sm-4">
                <div class="chart-scroll-container">
                    <div class="chart-canvas-wrapper" id="chartCanvasWrapper">
                        <canvas id="expenseChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Rekapitulasi Bulanan dengan Accordion Batch -->
        <div class="card border-0 shadow-sm" style="border-radius: 16px; overflow: hidden;">
            <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center" style="border-bottom: 1px solid #f1f5f9;">
                <h6 class="card-title text-dark fw-bold mb-0 d-flex align-items-center fs-6">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-3 me-2" style="width: 28px; height: 28px; background: #fef3c7; color: #d97706;">
                        <i class="bi bi-table fs-6"></i>
                    </span>
                    Rekapitulasi Gaji Bulanan
                </h6>
                <span class="text-muted small">Klik baris bulan untuk rincian batch</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0 border-0 table-reports">
                        <thead>
                            <tr style="height: 44px;">
                                <th class="ps-4" style="width: 26%;">Bulan Kalender</th>
                                <th class="text-center" style="width: 12%;">Total Batch</th>
                                <th class="text-center" style="width: 14%;">Total Karyawan</th>
                                <th class="text-end" style="width: 16%;">Total Potongan</th>
                                <th class="text-end" style="width: 18%;">Pengeluaran Nett</th>
                                <th class="text-center pe-4" style="width: 14%;">Aksi Cetak</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reports)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                    Belum ada data periode gaji yang disetujui.
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php 
                                $bulanArr = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];
                                foreach ($reports as $index => $r): 
                                    $b = explode('-', $r['bulan']);
                                    $namaBulan = ($bulanArr[$b[1]] ?? '') . ' ' . $b[0];
                                    $batches = $monthlyBatches[$r['bulan']] ?? [];
                                    $collapseId = 'collapseMonth' . str_replace('-', '', $r['bulan']);
                                ?>
                                <!-- Parent Row (Bulan) -->
                                <tr class="month-parent-row" data-bs-toggle="collapse" data-bs-target="#<?= $collapseId ?>" aria-expanded="false" style="border-bottom: 1px solid #f1f5f9;">
                                    <td class="ps-4 py-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="btn btn-sm btn-light border p-0 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                                <i class="bi bi-chevron-down chevron-icon" style="font-size: 0.7rem;"></i>
                                            </span>
                                            <div>
                                                <div class="fw-bold text-dark"><?= $namaBulan ?></div>
                                                <small class="text-muted" style="font-size: 0.75rem;"><?= count($batches) ?> siklus payroll</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center py-3">
                                        <span class="badge bg-light text-secondary border px-2.5 py-1">
                                            <?= count($batches) ?> Batch
                                        </span>
                                    </td>
                                    <td class="text-center py-3 fw-semibold text-dark">
                                        <?= (int)$r['total_karyawan'] ?> Orang
                                    </td>
                                    <td class="text-end text-danger py-3 fw-medium">
                                        <?= formatRupiah((float)$r['total_potongan']) ?>
                                    </td>
                                    <td class="text-end fw-bold text-success py-3 fs-6">
                                        <?= formatRupiah((float)$r['total_pengeluaran']) ?>
                                    </td>
                                    <td class="text-center pe-4 py-3" onclick="event.stopPropagation();">
                                        <a href="<?= url('/reports/export?bulan=' . urlencode($r['bulan'])) ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3 shadow-none d-inline-flex align-items-center gap-1.5" title="Cetak Seluruh Bulan Ini">
                                            <i class="bi bi-file-earmark-pdf"></i>
                                            <span>Cetak Bulan</span>
                                        </a>
                                    </td>
                                </tr>

                                <!-- Child Collapse Row (Rincian Batch Payroll di Bulan ini) -->
                                <tr class="collapse-row p-0">
                                    <td colspan="6" class="p-0 border-0">
                                        <div class="collapse" id="<?= $collapseId ?>">
                                            <div class="p-3" style="background-color: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                                                <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 px-1 gap-2">
                                                    <span class="fw-semibold text-secondary small">
                                                        <i class="bi bi-list-nested me-1"></i> Rincian Siklus Penggajian di <?= $namaBulan ?>:
                                                    </span>
                                                    <div class="d-flex gap-2 align-items-center">
                                                        <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-2.5 py-1 select-all-month-btn" data-target-month="<?= $r['bulan'] ?>" style="font-size: 0.75rem;">
                                                            Pilih Semua
                                                        </button>
                                                        <button type="button" class="btn btn-xs btn-primary rounded-pill px-3 py-1 export-selected-month-btn" data-target-month="<?= $r['bulan'] ?>" style="font-size: 0.75rem;">
                                                            <i class="bi bi-printer me-1"></i> Cetak Batch Terpilih
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="table-responsive bg-white rounded-3 border" style="border-color: #e2e8f0 !important;">
                                                    <table class="table table-sm table-hover mb-0 align-middle" style="font-size: 0.8rem;">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th class="text-center ps-3" style="width: 38px;">
                                                                    <input type="checkbox" class="form-check-input check-all-in-month" data-month="<?= $r['bulan'] ?>">
                                                                </th>
                                                                <th>Nama / Judul Payroll</th>
                                                                <th>Tipe</th>
                                                                <th>Periode Kerja</th>
                                                                <th class="text-center">Karyawan</th>
                                                                <th class="text-end">Potongan</th>
                                                                <th class="text-end pe-3">Pengeluaran (Nett)</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($batches as $bRow): 
                                                                $typeBadge = $bRow['type'] === 'weekly' ? 'bg-primary-subtle text-primary border-primary-subtle' : ($bRow['type'] === 'monthly' ? 'bg-info-subtle text-info border-info-subtle' : 'bg-warning-subtle text-warning border-warning-subtle');
                                                                $typeName = $bRow['type'] === 'weekly' ? 'Borongan' : ($bRow['type'] === 'monthly' ? 'Bulanan' : 'Campuran');
                                                            ?>
                                                            <tr>
                                                                <td class="text-center ps-3">
                                                                    <input type="checkbox" class="form-check-input month-batch-checkbox month-<?= $r['bulan'] ?>" value="<?= $bRow['id'] ?>">
                                                                </td>
                                                                <td class="fw-semibold text-dark">
                                                                    <?= e($bRow['name'] ?: 'Gaji ' . $typeName . ' #' . $bRow['id']) ?>
                                                                </td>
                                                                <td>
                                                                    <span class="badge <?= $typeBadge ?> border px-2 py-0.5" style="font-size: 0.7rem; font-weight: 500;">
                                                                        <?= $typeName ?>
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <?= renderPayrollPeriodHtml($bRow, true) ?>
                                                                </td>
                                                                <td class="text-center fw-medium">
                                                                    <?= (int)$bRow['total_karyawan'] ?> Org
                                                                </td>
                                                                <td class="text-end text-danger">
                                                                    <?= formatRupiah((float)$bRow['total_potongan']) ?>
                                                                </td>
                                                                <td class="text-end pe-3 fw-bold text-success">
                                                                    <?= formatRupiah((float)$bRow['total_pengeluaran']) ?>
                                                                </td>
                                                            </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
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
    </div>

    <!-- ==================== TAB 2: LAPORAN KUSTOM / MULTI-BATCH ==================== -->
    <div class="tab-pane fade" id="content-mode-kustom" role="tabpanel">
        <!-- Integrated Filter & Action Toolbar Card -->
        <div class="report-toolbar-card">
            <!-- Row 1: Controls -->
            <div class="report-toolbar-row">
                <!-- Date Range Filter Pill -->
                <div class="report-date-filter">
                    <span class="report-filter-label">
                        <i class="bi bi-calendar-range text-primary" style="margin-right: 8px;"></i>
                        <span>Rentang:</span>
                    </span>
                    <input type="date" class="report-date-input" id="customStartDate" value="<?= date('Y-m-01') ?>">
                    <span class="report-date-sep">s/d</span>
                    <input type="date" class="report-date-input" id="customEndDate" value="<?= date('Y-m-d') ?>">
                    <button type="button" class="report-btn-filter" id="btnApplyDateFilter" title="Saring baris sesuai rentang tanggal">
                        <i class="bi bi-funnel-fill" style="margin-right: 6px;"></i>
                        <span>Filter</span>
                    </button>
                </div>

                <!-- Search Box Pill -->
                <div class="report-search-box">
                    <i class="bi bi-search text-muted" style="margin-right: 8px; font-size: 0.85rem;"></i>
                    <input type="text" class="report-search-input" id="searchCustomPayroll" placeholder="Cari nama payroll...">
                </div>

                <!-- Export Button Pill -->
                <div class="report-export-box">
                    <button type="button" class="report-btn-export" id="btnExportSelectedCustom">
                        <i class="bi bi-file-earmark-pdf" style="margin-right: 6px;"></i>
                        <span>Cetak PDF (<span id="btnBadgeCount">0</span>)</span>
                    </button>
                </div>
            </div>

            <!-- Row 2: Live Metrics & Fast Actions -->
            <div class="report-summary-row">
                <div class="report-metrics-group">
                    <div class="metric-pill">
                        <span class="metric-pill-label">Batch Dipilih:</span>
                        <span class="metric-pill-value text-primary" id="summarySelectedCount">0 Batch</span>
                    </div>
                    <div class="metric-pill">
                        <span class="metric-pill-label">Total Karyawan:</span>
                        <span class="metric-pill-value" id="summaryTotalKaryawan">0 Org</span>
                    </div>
                    <div class="metric-pill">
                        <span class="metric-pill-label">Beban Gaji (Nett):</span>
                        <span class="metric-pill-value text-success" id="summaryTotalAmount">Rp 0</span>
                    </div>
                </div>

                <div class="report-actions-group">
                    <button type="button" class="btn-action-pill" id="btnSelectAllCustom">
                        <i class="bi bi-check2-all text-primary" style="margin-right: 6px;"></i>
                        <span>Pilih Semua</span>
                    </button>
                    <button type="button" class="btn-action-pill" id="btnUnselectAllCustom">
                        <i class="bi bi-x-circle text-danger" style="margin-right: 6px;"></i>
                        <span>Batal Pilih</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabel Pemilih Batch Payroll Kustom -->
        <div class="card border-0 shadow-sm" style="border-radius: 16px; overflow: hidden;">
            <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center" style="border-bottom: 1px solid #f1f5f9;">
                <h6 class="card-title text-dark fw-bold mb-0 d-flex align-items-center fs-6">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-3 me-2" style="width: 28px; height: 28px; background: #e0e7ff; color: #4338ca;">
                        <i class="bi bi-list-check fs-6"></i>
                    </span>
                    Daftar Seluruh Batch Payroll (Approved)
                </h6>
                <span class="text-muted small">Centang baris yang ingin digabungkan</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 border-0 table-reports" id="tableCustomPayroll">
                        <thead>
                            <tr style="height: 44px;">
                                <th class="text-center ps-4" style="width: 44px;">
                                    <input type="checkbox" class="form-check-input" id="checkAllCustomHeader" title="Pilih Semua">
                                </th>
                                <th>Nama / Judul Payroll</th>
                                <th>Tipe</th>
                                <th>Periode Kerja</th>
                                <th class="text-center">Tanggal Disetujui</th>
                                <th class="text-center">Karyawan</th>
                                <th class="text-end">Total Potongan</th>
                                <th class="text-end pe-4">Pengeluaran (Nett)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($allApprovedRuns)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                    Belum ada data payroll yang disetujui.
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($allApprovedRuns as $run): 
                                    $tBadge = $run['type'] === 'weekly' ? 'bg-primary-subtle text-primary border-primary-subtle' : ($run['type'] === 'monthly' ? 'bg-info-subtle text-info border-info-subtle' : 'bg-warning-subtle text-warning border-warning-subtle');
                                    $tName = $run['type'] === 'weekly' ? 'Borongan' : ($run['type'] === 'monthly' ? 'Bulanan' : 'Campuran');
                                ?>
                                <tr class="custom-payroll-row" 
                                    data-start="<?= $run['periode_awal'] ?>" 
                                    data-end="<?= $run['periode_akhir'] ?>"
                                    data-amount="<?= (float)$run['total_pengeluaran'] ?>"
                                    data-karyawan="<?= (int)$run['total_karyawan'] ?>"
                                    data-title="<?= strtolower(e($run['name'] . ' ' . $tName)) ?>"
                                    style="border-bottom: 1px solid #f8fafc;">
                                    <td class="text-center ps-4" onclick="event.stopPropagation();">
                                        <input type="checkbox" class="form-check-input custom-batch-checkbox" value="<?= $run['id'] ?>"
                                               data-amount="<?= (float)$run['total_pengeluaran'] ?>"
                                               data-karyawan="<?= (int)$run['total_karyawan'] ?>">
                                    </td>
                                    <td class="fw-semibold text-dark">
                                        <?= e($run['name'] ?: 'Gaji ' . $tName . ' #' . $run['id']) ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $tBadge ?> border px-2 py-0.5" style="font-size: 0.7rem; font-weight: 500;">
                                            <?= $tName ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= renderPayrollPeriodHtml($run, true) ?>
                                    </td>
                                    <td class="text-center text-muted small">
                                        <?= !empty($run['disetujui_pada']) ? date('d/m/Y H:i', strtotime($run['disetujui_pada'])) : '-' ?>
                                    </td>
                                    <td class="text-center fw-medium">
                                        <?= (int)$run['total_karyawan'] ?> Org
                                    </td>
                                    <td class="text-end text-danger fw-medium">
                                        <?= formatRupiah((float)$run['total_potongan']) ?>
                                    </td>
                                    <td class="text-end pe-4 fw-bold text-success">
                                        <?= formatRupiah((float)$run['total_pengeluaran']) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <tr id="customNoMatchRow" style="display: none;">
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        <i class="bi bi-funnel fs-3 d-block mb-1 text-secondary opacity-50"></i>
                                        Tidak ada batch payroll yang sesuai dengan filter atau pencarian.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Inisialisasi Chart Pengeluaran dengan Toggle Mode & Horizontal Scrolling
    const monthlyLabels = <?= $chartMonthlyLabels ?>;
    const monthlyValues = <?= $chartMonthlyValues ?>;
    const batchLabels = <?= $chartBatchLabels ?>;
    const batchValues = <?= $chartBatchValues ?>;

    const ctx = document.getElementById('expenseChart');
    const chartWrapper = document.getElementById('chartCanvasWrapper');
    let expenseChartInstance = null;
    let currentChartMode = 'monthly';

    function updateChartWidth(dataCount) {
        if (!chartWrapper) return;
        const isMobile = window.innerWidth < 768;
        const minPointWidth = isMobile ? 120 : 95;
        const neededWidth = dataCount * minPointWidth;
        const parentWidth = chartWrapper.parentElement ? chartWrapper.parentElement.clientWidth : 0;

        if (neededWidth > parentWidth) {
            chartWrapper.style.width = neededWidth + 'px';
        } else {
            chartWrapper.style.width = '100%';
        }
    }

    if (ctx) {
        updateChartWidth(monthlyLabels.length);

        expenseChartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: monthlyLabels,
                datasets: [{
                    label: 'Pengeluaran Gaji (Rp)',
                    data: monthlyValues,
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.06)',
                    tension: 0.35,
                    fill: true,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#2563eb'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return ' Pengeluaran: Rp ' + context.parsed.y.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + (value / 1000000).toLocaleString('id-ID') + ' Jt';
                            }
                        },
                        grid: { color: '#f1f5f9' }
                    },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    const btnChartMonthly = document.getElementById('btnChartModeMonthly');
    const btnChartBatch = document.getElementById('btnChartModeBatch');
    const chartTitleText = document.getElementById('chartTitleText');

    if (btnChartMonthly && btnChartBatch && expenseChartInstance) {
        btnChartMonthly.addEventListener('click', function() {
            currentChartMode = 'monthly';
            btnChartMonthly.classList.add('active');
            btnChartBatch.classList.remove('active');
            if (chartTitleText) chartTitleText.innerText = 'Tren Beban Gaji (Per Bulan)';
            
            updateChartWidth(monthlyLabels.length);
            expenseChartInstance.data.labels = monthlyLabels;
            expenseChartInstance.data.datasets[0].data = monthlyValues;
            expenseChartInstance.data.datasets[0].borderColor = '#2563eb';
            expenseChartInstance.data.datasets[0].backgroundColor = 'rgba(37, 99, 235, 0.06)';
            expenseChartInstance.data.datasets[0].pointBackgroundColor = '#2563eb';
            expenseChartInstance.resize();
            expenseChartInstance.update();
        });

        btnChartBatch.addEventListener('click', function() {
            currentChartMode = 'batch';
            btnChartBatch.classList.add('active');
            btnChartMonthly.classList.remove('active');
            if (chartTitleText) chartTitleText.innerText = 'Tren Beban Gaji (Per Batch Payroll)';
            
            updateChartWidth(batchLabels.length);
            expenseChartInstance.data.labels = batchLabels;
            expenseChartInstance.data.datasets[0].data = batchValues;
            expenseChartInstance.data.datasets[0].borderColor = '#0284c7';
            expenseChartInstance.data.datasets[0].backgroundColor = 'rgba(2, 132, 199, 0.06)';
            expenseChartInstance.data.datasets[0].pointBackgroundColor = '#0284c7';
            expenseChartInstance.resize();
            expenseChartInstance.update();
        });

        window.addEventListener('resize', function() {
            const count = currentChartMode === 'monthly' ? monthlyLabels.length : batchLabels.length;
            updateChartWidth(count);
            expenseChartInstance.resize();
        });
    }

    // 2. Logic Tab 1: Checkbox per Bulan
    document.querySelectorAll('.check-all-in-month').forEach(function(master) {
        master.addEventListener('change', function() {
            const m = this.dataset.month;
            document.querySelectorAll('.month-' + m).forEach(cb => cb.checked = master.checked);
        });
    });

    document.querySelectorAll('.select-all-month-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const m = this.dataset.targetMonth;
            const checkboxes = document.querySelectorAll('.month-' + m);
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            checkboxes.forEach(cb => cb.checked = !allChecked);
            const master = document.querySelector('.check-all-in-month[data-month="' + m + '"]');
            if (master) master.checked = !allChecked;
        });
    });

    document.querySelectorAll('.export-selected-month-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const m = this.dataset.targetMonth;
            const selected = Array.from(document.querySelectorAll('.month-' + m + ':checked')).map(cb => cb.value);
            if (selected.length === 0) {
                showToast('Silakan pilih sekurang-kurangnya 1 batch payroll pada bulan ini.', 'warning');
                return;
            }
            window.location.href = '<?= url('/reports/export') ?>?payroll_ids=' + selected.join(',');
        });
    });

    // 3. Logic Tab 2: Laporan Kustom & Live Calculator
    const customCheckboxes = document.querySelectorAll('.custom-batch-checkbox');
    const summaryCountEl = document.getElementById('summarySelectedCount');
    const summaryKaryawanEl = document.getElementById('summaryTotalKaryawan');
    const summaryAmountEl = document.getElementById('summaryTotalAmount');
    const btnBadgeCount = document.getElementById('btnBadgeCount');
    const headerCheckCustom = document.getElementById('checkAllCustomHeader');

    function updateCustomSummary() {
        let count = 0;
        let karyawan = 0;
        let amount = 0;
        customCheckboxes.forEach(function(cb) {
            const row = cb.closest('tr');
            if (cb.checked && row && row.style.display !== 'none') {
                count++;
                karyawan += parseInt(cb.dataset.karyawan || '0', 10);
                amount += parseFloat(cb.dataset.amount || '0');
                row.classList.add('row-selected');
            } else if (row) {
                row.classList.remove('row-selected');
            }
        });
        if (summaryCountEl) summaryCountEl.innerText = count + ' Batch';
        if (summaryKaryawanEl) summaryKaryawanEl.innerText = karyawan + ' Org';
        if (summaryAmountEl) summaryAmountEl.innerText = 'Rp ' + amount.toLocaleString('id-ID');
        if (btnBadgeCount) btnBadgeCount.innerText = count;
    }

    customCheckboxes.forEach(cb => cb.addEventListener('change', updateCustomSummary));

    // Klik seluruh baris tabel untuk toggle checkbox
    document.querySelectorAll('.custom-payroll-row').forEach(function(row) {
        row.addEventListener('click', function(e) {
            if (e.target.tagName === 'INPUT' || e.target.closest('a') || e.target.closest('button')) {
                return;
            }
            const cb = row.querySelector('.custom-batch-checkbox');
            if (cb) {
                cb.checked = !cb.checked;
                updateCustomSummary();
            }
        });
    });

    if (headerCheckCustom) {
        headerCheckCustom.addEventListener('change', function() {
            customCheckboxes.forEach(function(cb) {
                const row = cb.closest('tr');
                if (row && row.style.display !== 'none') {
                    cb.checked = headerCheckCustom.checked;
                }
            });
            updateCustomSummary();
        });
    }

    const btnSelectAllCustom = document.getElementById('btnSelectAllCustom');
    if (btnSelectAllCustom) {
        btnSelectAllCustom.addEventListener('click', function() {
            customCheckboxes.forEach(function(cb) {
                const row = cb.closest('tr');
                if (row && row.style.display !== 'none') cb.checked = true;
            });
            if (headerCheckCustom) headerCheckCustom.checked = true;
            updateCustomSummary();
        });
    }

    const btnUnselectAllCustom = document.getElementById('btnUnselectAllCustom');
    if (btnUnselectAllCustom) {
        btnUnselectAllCustom.addEventListener('click', function() {
            customCheckboxes.forEach(cb => cb.checked = false);
            if (headerCheckCustom) headerCheckCustom.checked = false;
            updateCustomSummary();
        });
    }

    // Export Selected Custom Batches
    const btnExportSelectedCustom = document.getElementById('btnExportSelectedCustom');
    if (btnExportSelectedCustom) {
        btnExportSelectedCustom.addEventListener('click', function() {
            const selected = Array.from(customCheckboxes).filter(cb => cb.checked).map(cb => cb.value);
            if (selected.length === 0) {
                showToast('Silakan pilih sekurang-kurangnya 1 batch payroll untuk dicetak.', 'warning');
                return;
            }
            window.location.href = '<?= url('/reports/export') ?>?payroll_ids=' + selected.join(',');
        });
    }

    // Filter by Date and Live Search Synchronization
    const btnApplyDateFilter = document.getElementById('btnApplyDateFilter');
    const searchInput = document.getElementById('searchCustomPayroll');
    const startDateInput = document.getElementById('customStartDate');
    const endDateInput = document.getElementById('customEndDate');
    const noMatchRow = document.getElementById('customNoMatchRow');

    function applyCustomFilters(autoSelectVisible = false) {
        const start = startDateInput ? startDateInput.value : '';
        const end = endDateInput ? endDateInput.value : '';
        const q = searchInput ? searchInput.value.toLowerCase().trim() : '';

        let visibleCount = 0;
        const rows = document.querySelectorAll('.custom-payroll-row');
        
        rows.forEach(function(row) {
            const rStart = row.dataset.start || '';
            const rEnd = row.dataset.end || '';
            const title = (row.dataset.title || '').toLowerCase();

            const dateMatch = (!start || !end) ? true : (rStart <= end && rEnd >= start);
            const searchMatch = !q ? true : title.includes(q);

            const isVisible = dateMatch && searchMatch;
            row.style.display = isVisible ? '' : 'none';

            if (isVisible) {
                visibleCount++;
                if (autoSelectVisible) {
                    const cb = row.querySelector('.custom-batch-checkbox');
                    if (cb) cb.checked = true;
                }
            } else if (autoSelectVisible) {
                const cb = row.querySelector('.custom-batch-checkbox');
                if (cb) cb.checked = false;
            }
        });

        if (noMatchRow) {
            noMatchRow.style.display = (visibleCount === 0 && rows.length > 0) ? '' : 'none';
        }

        updateCustomSummary();
        return visibleCount;
    }

    if (btnApplyDateFilter) {
        btnApplyDateFilter.addEventListener('click', function() {
            const start = startDateInput ? startDateInput.value : '';
            const end = endDateInput ? endDateInput.value : '';
            if (!start || !end) {
                showToast('Silakan masukkan tanggal mulai dan selesai terlebih dahulu.', 'warning');
                return;
            }
            if (start > end) {
                showToast('Tanggal mulai tidak boleh melebihi tanggal selesai.', 'error');
                return;
            }

            const matchCount = applyCustomFilters(true);
            showToast('Filter tanggal diterapkan (' + matchCount + ' batch terpilih).', 'info');
        });
    }

    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                applyCustomFilters(false);
            }, 150);
        });

        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                applyCustomFilters(false);
            }
        });
    }

    if (startDateInput && endDateInput) {
        [startDateInput, endDateInput].forEach(inp => {
            inp.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (btnApplyDateFilter) btnApplyDateFilter.click();
                }
            });
        });
    }
});
</script>
