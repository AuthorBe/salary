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
            <i class="bi bi-calendar-check-fill text-primary me-2 fs-4"></i> Rekap Kehadiran
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
        <form method="GET" action="<?= url('/rekap/attendance') ?>" class="row g-3 align-items-end">
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
                        <th class="py-3 px-4 rounded-start w-50">Karyawan</th>
                        <th class="py-3 px-4 text-center">Total Hadir</th>
                        <th class="py-3 px-4 text-center">Total Absen / Izin</th>
                        <th class="py-3 px-4 text-center rounded-end">Keterlambatan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                        $groupedByTipe = [];
                        foreach ($data as $row) {
                            $groupedByTipe[$row['tipe_gaji']][] = $row;
                        }
                    ?>
                    <?php if (empty($groupedByTipe)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                <i class="bi bi-calendar-x fs-1 d-block mb-3 opacity-50"></i>
                                Tidak ada data absensi untuk periode ini.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($groupedByTipe as $tipe => $employees): ?>
                            <!-- Header Grup Tipe Gaji -->
                            <tr class="bg-light">
                                <td colspan="4" class="py-2.5 px-4 fw-bold text-dark border-bottom-0 text-uppercase small">
                                    <i class="bi bi-people-fill text-primary me-2"></i> Karyawan <?= e($tipe) ?> (<?= count($employees) ?> orang)
                                </td>
                            </tr>
                            <?php foreach ($employees as $row): ?>
                                <tr>
                                    <td class="px-4 ps-5 fw-bold text-dark">
                                        <?= e($row['name']) ?>
                                    </td>
                                    <td class="px-4 text-center fw-bold text-success fs-6">
                                        <?= (int)$row['total_hadir'] ?> <span class="fs-7 fw-normal text-muted">Hari</span>
                                    </td>
                                    <td class="px-4 text-center fw-bold <?= (int)$row['total_absen'] > 0 ? 'text-danger' : 'text-muted' ?> fs-6">
                                        <?= (int)$row['total_absen'] ?> <span class="fs-7 fw-normal text-muted">Hari</span>
                                    </td>
                                    <td class="px-4 text-center">
                                        <?php $jmlTelat = (int)($row['total_telat'] ?? 0); ?>
                                        <?php if ($jmlTelat > 0): ?>
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-3 py-1.5 fs-7 fw-semibold">
                                                <i class="bi bi-clock-history me-1"></i><?= $jmlTelat ?>× Telat
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted fw-semibold">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
