
<div class="card glass-card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <h5 class="card-title text-gray-800 fw-bold mb-4">Filter Pencarian</h5>
        <form method="GET" action="/history" class="row g-4">
            <div class="col-md-3">
                <label class="form-label">Karyawan</label>
                <select name="employee_id" class="form-select searchable-select">
                    <option value="">Semua Karyawan</option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?= $emp['id'] ?>" <?= ($filters['employee_id'] == $emp['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($emp['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-4">
                <label class="form-label">Periode (Payroll Run)</label>
                <select name="run_id" class="form-select">
                    <option value="">Semua Periode</option>
                    <?php foreach ($runs as $run): 
                        $pDetails = getPayrollPeriodDetails($run);
                        $pStr = count($pDetails) > 1 
                            ? ('Borongan: ' . $pDetails['borongan']['formatted_short'] . ' | Bulanan: ' . $pDetails['bulanan']['formatted_short'])
                            : ($pDetails[array_key_first($pDetails)]['formatted_short'] ?? (formatTanggalShort($run['periode_awal']) . ' – ' . formatTanggalShort($run['periode_akhir'])));
                    ?>
                        <option value="<?= $run['id'] ?>" <?= ($filters['run_id'] == $run['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($run['name'] ?? 'Run #' . $run['id']) ?> (<?= $pStr ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Tipe Gaji</label>
                <select name="type" class="form-select">
                    <option value="">Semua Tipe</option>
                    <option value="bulanan" <?= ($filters['type'] === 'bulanan') ? 'selected' : '' ?>>Bulanan</option>
                    <option value="borongan" <?= ($filters['type'] === 'borongan') ? 'selected' : '' ?>>Borongan</option>
                    </select>
            </div>

            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100 py-2 shadow-sm rounded-3">
                    <i class="bi bi-funnel me-1"></i> Terapkan Filter
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card glass-card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 border-0">
                <thead class="table-light">
                    <tr style="height: 50px;">
                        <th class="ps-4">Karyawan</th>
                        <th>Periode</th>
                        <th>Tipe</th>
                        <th class="text-end">Gaji Bersih</th>
                        <th class="text-center pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($histories)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">Belum ada riwayat gaji yang ditemukan.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($histories as $row): 
                            $itemPeriodFormatted = getPayrollPeriodForType($row, $row['tipe_gaji']);
                        ?>
                        <tr>
                            <td class="ps-4 py-3 fw-medium text-dark"><?= htmlspecialchars($row['employee_name']) ?></td>
                            <td class="py-3">
                                <div class="fw-bold text-dark mb-1"><?= htmlspecialchars($row['run_name'] ?? 'Run #' . $row['run_id']) ?></div>
                                <div class="text-muted small">
                                    <i class="bi bi-calendar-event me-1 text-primary"></i> <?= $itemPeriodFormatted ?>
                                </div>
                            </td>
                            <td class="py-3">
                                <?php if ($row['tipe_gaji'] === 'borongan'): ?>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3">Borongan</span>
                                <?php else: ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3">Bulanan</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end fw-bold text-success py-3">
                                Rp <?= number_format((float)$row['gaji_bersih'], 0, ',', '.') ?>
                            </td>
                            <td class="text-center pe-4 py-3">
                                <a href="/history/slip?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger px-3 shadow-sm rounded-pill" title="Download PDF Slip">
                                    <i class="bi bi-file-earmark-pdf me-1"></i> Slip
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

