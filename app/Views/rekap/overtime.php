<?php
/**
 * @var string $title
 * @var string $pageTitle
 * @var string $start_date
 * @var string $end_date
 * @var array $data
 */
?>

<div class="page-title-box d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
    <div class="page-title-left">
        <h4 class="mb-1 text-dark fw-bold d-flex align-items-center">
            <i class="bi bi-clock-history text-warning me-2 fs-4" style="color: #f59e0b;"></i> Rekap Lembur
        </h4>
        <p class="text-muted mb-0 fs-7 ms-4 ps-1">
            Periode: <strong><?= e(formatRentangTanggal($start_date, $end_date)) ?></strong>
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
        <form method="GET" action="<?= url('/rekap/overtime') ?>" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label text-muted fw-bold small">Tanggal Mulai</label>
                <input type="date" name="start_date" class="form-control" value="<?= e($start_date) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label text-muted fw-bold small">Tanggal Akhir</label>
                <input type="date" name="end_date" class="form-control" value="<?= e($end_date) ?>" required>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100 rounded-pill fw-semibold py-2">
                    <i class="bi bi-filter me-1"></i> Tampilkan Data
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4 p-md-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="py-3 px-4 rounded-start w-75">Karyawan</th>
                        <th class="py-3 px-4 text-end rounded-end">Total Uang Lembur (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                        // First group by ID to avoid duplicates
                        $groupedById = [];
                        foreach ($data as $row) {
                            $id = $row['id'];
                            if (!isset($groupedById[$id])) {
                                $groupedById[$id] = [
                                    'name'  => $row['name'],
                                    'tipe'  => $row['tipe_gaji'],
                                    'total' => 0.0,
                                ];
                            }
                            $groupedById[$id]['total'] += (float)$row['total_uang_lembur'];
                        }

                        // Then group by tipe_gaji
                        $groupedByTipe = [];
                        $grandTotal = 0.0;
                        foreach ($groupedById as $g) {
                            $groupedByTipe[$g['tipe']][] = $g;
                            $grandTotal += (float)$g['total'];
                        }
                    ?>

                    <?php if (empty($groupedByTipe)): ?>
                        <tr>
                            <td colspan="2" class="text-center py-5 text-muted">
                                <i class="bi bi-clock-history fs-1 d-block mb-3 opacity-50"></i>
                                Tidak ada data lembur untuk periode ini.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($groupedByTipe as $tipe => $employees): ?>
                            <!-- Header Grup Tipe Gaji -->
                            <tr class="bg-light">
                                <td colspan="2" class="py-2.5 px-4 fw-bold text-dark border-bottom-0 text-uppercase small">
                                    <i class="bi bi-people-fill text-primary me-2"></i> Karyawan <?= e($tipe) ?> (<?= count($employees) ?> orang)
                                </td>
                            </tr>
                            <!-- Daftar Karyawan -->
                            <?php foreach ($employees as $row): ?>
                                <tr>
                                    <td class="px-4 ps-5 fw-bold text-dark"><?= e($row['name']) ?></td>
                                    <td class="px-4 text-end fw-bold text-success fs-6"><?= formatRupiah($row['total']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                        
                        <tr class="table-light fw-bold border-top border-2">
                            <td class="px-4 text-end rounded-start text-dark">GRAND TOTAL UANG LEMBUR:</td>
                            <td class="px-4 text-end text-success fs-5 rounded-end"><?= formatRupiah($grandTotal) ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
