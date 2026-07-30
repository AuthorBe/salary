
<!-- Simple Chart using Chart.js via CDN for visual impact -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card glass-card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h5 class="card-title text-gray-800 fw-bold mb-4">Tren Pengeluaran Gaji (6 Periode Terakhir)</h5>
                <div style="position: relative; height: 300px; width: 100%;">
                    <canvas id="expenseChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card glass-card border-0 shadow-sm">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="card-title text-gray-800 fw-bold mb-0">Rekapitulasi Gaji Per Periode</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 border-0">
                <thead class="table-light">
                    <tr style="height: 50px;">
                        <th class="ps-4">Run ID & Periode</th>
                        <th>Tipe</th>
                        <th class="text-center">Karyawan</th>
                        <th class="text-end">Total Potongan (Hutang)</th>
                        <th class="text-end">Total Pengeluaran (Nett)</th>
                        <th class="text-center pe-4">Aksi Cetak</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reports)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">Belum ada data periode gaji yang disetujui.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($reports as $r): ?>
                        <tr>
                            <td class="ps-4 py-3">
                                <div class="fw-bold text-dark mb-1"><?= htmlspecialchars($r['run_name'] ?? 'Run #' . $r['id']) ?></div>
                                <div class="text-muted small">
                                    <?= date('d M Y', strtotime($r['periode_awal'])) ?> - <?= date('d M Y', strtotime($r['periode_akhir'])) ?>
                                </div>
                            </td>
                            <td class="py-3">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3"><?= ucfirst($r['type']) ?></span>
                            </td>
                            <td class="text-center fw-medium py-3 text-dark">
                                <?= $r['total_karyawan'] ?> Orang
                            </td>
                            <td class="text-end text-danger py-3">
                                Rp <?= number_format((float)$r['total_potongan'], 0, ',', '.') ?>
                            </td>
                            <td class="text-end fw-bold text-success py-3">
                                Rp <?= number_format((float)$r['total_pengeluaran'], 0, ',', '.') ?>
                            </td>
                            <td class="text-center pe-4 py-3">
                                <div class="d-flex flex-column gap-2 justify-content-center">
                                    <a href="/reports/export?id=<?= $r['id'] ?>" class="btn btn-sm btn-danger px-3 shadow-sm rounded-pill" title="Ekspor PDF">
                                        <i class="bi bi-file-earmark-pdf me-1"></i> Rekap Total
                                    </a>
                                    <a href="/history/slips-batch?run_id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-secondary px-3 rounded-pill" title="Cetak Semua Slip Gaji">
                                        <i class="bi bi-printer me-1"></i> Cetak Slips
                                    </a>
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

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('expenseChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= $chartLabels ?>,
                datasets: [{
                    label: 'Total Pengeluaran (Rp)',
                    data: <?= $chartValues ?>,
                    borderColor: '#4e73df',
                    backgroundColor: 'rgba(78, 115, 223, 0.1)',
                    tension: 0.3,
                    fill: true,
                    pointRadius: 5,
                    pointBackgroundColor: '#4e73df'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>

