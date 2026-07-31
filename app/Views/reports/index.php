
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
            <h5 class="card-title text-gray-800 fw-bold mb-0">Rekapitulasi Gaji Bulanan</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 border-0">
                <thead class="table-light">
                    <tr style="height: 50px;">
                        <th class="ps-4">Bulan</th>
                        <th class="text-center">Total Karyawan</th>
                        <th class="text-end">Total Potongan (Hutang)</th>
                        <th class="text-end">Total Pengeluaran (Nett)</th>
                        <th class="text-center pe-4">Aksi Cetak</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reports)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">Belum ada data periode gaji yang disetujui.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($reports as $r): ?>
                        <tr>
                            <td class="ps-4 py-3">
                                <div class="fw-bold text-dark mb-1">
                                    <?php 
                                    $bulanArr = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];
                                    $b = explode('-', $r['bulan']);
                                    echo $bulanArr[$b[1]] . ' ' . $b[0];
                                    ?>
                                </div>
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
                                <div class="d-flex justify-content-center">
                                    <a href="/reports/export?bulan=<?= $r['bulan'] ?>" class="btn btn-sm btn-danger px-3 shadow-sm rounded-pill" title="Ekspor PDF">
                                        <i class="bi bi-file-earmark-pdf me-1"></i> Rekap Bulanan
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

