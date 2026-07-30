

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h3 fw-bold text-dark mb-1">Rekap Kehadiran</h2>
        <p class="text-muted mb-0">Laporan absensi karyawan berdasarkan rentang tanggal.</p>
    </div>
    <div>
        <a href="<?= url('/rekap') ?>" class="btn btn-outline-secondary rounded-pill fw-bold">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <form method="GET" action="<?= url('/rekap/attendance') ?>" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label text-muted fw-bold">Tanggal Mulai</label>
                <input type="date" name="start_date" class="form-control form-control-lg" value="<?= e($start_date) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label text-muted fw-bold">Tanggal Akhir</label>
                <input type="date" name="end_date" class="form-control form-control-lg" value="<?= e($end_date) ?>" required>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill fw-bold">
                    <i class="bi bi-search me-2"></i> Tampilkan
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4 p-md-5">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="py-3 px-4 rounded-start w-50">Karyawan</th>
                        <th class="py-3 px-4 text-center">Total Hadir</th>
                        <th class="py-3 px-4 text-center rounded-end">Total Absen</th>
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
                            <td colspan="3" class="text-center py-5 text-muted">
                                <i class="bi bi-calendar-x fs-1 d-block mb-3 opacity-50"></i>
                                Tidak ada data absensi untuk periode ini.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($groupedByTipe as $tipe => $employees): ?>
                            <!-- Header Grup Tipe Gaji -->
                            <tr class="bg-light">
                                <td colspan="3" class="py-2 px-4 fw-bold text-dark border-bottom-0 text-uppercase">
                                    <i class="bi bi-people-fill text-primary me-2"></i> Karyawan <?= e($tipe) ?>
                                </td>
                            </tr>
                            <!-- Daftar Karyawan -->
                            <?php foreach ($employees as $row): ?>
                                <tr>
                                    <td class="px-4 ps-5 fw-bold text-dark"><?= e($row['name']) ?></td>
                                    <td class="px-4 text-center fw-bold text-success fs-5"><?= (int)$row['total_hadir'] ?> <span class="fs-6 fw-normal text-muted">Hari</span></td>
                                    <td class="px-4 text-center fw-bold text-danger fs-5"><?= (int)$row['total_absen'] ?> <span class="fs-6 fw-normal text-muted">Hari</span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


