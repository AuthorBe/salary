<?php
/**
 * @var string $title
 * @var string $pageTitle
 * @var string $start_date
 * @var string $end_date
 * @var array $groupedData
 */
?>

<div class="page-title-box d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
    <div class="page-title-left">
        <h4 class="mb-1 text-dark fw-bold d-flex align-items-center">
            <i class="bi bi-box-seam-fill text-pink me-2 fs-4" style="color: #ec4899;"></i> Rekap Produksi
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
        <form method="GET" action="<?= url('/rekap/production') ?>" class="row g-3 align-items-end">
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

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4 p-md-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="py-3 px-4 rounded-start" style="width: 60px;">No</th>
                        <th class="py-3 px-4">Tanggal Produksi</th>
                        <th class="py-3 px-4 text-end">Total Kuantitas</th>
                        <th class="py-3 px-4 text-end">Total Upah (Rp)</th>
                        <th class="py-3 px-4 text-center rounded-end" style="width: 120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($groupedData)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-box-seam fs-1 d-block mb-3 opacity-50"></i>
                                Tidak ada data produksi untuk periode ini.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $no = 1; 
                        $grandTotal = 0;
                        $grandQty = 0;
                        $grandBal = 0;
                        foreach ($groupedData as $date => $row): 
                            $grandTotal += (float)$row['total_upah'];
                            $grandQty   += (int)$row['total_qty'];
                            $grandBal   += (float)$row['total_bal'];
                            $modalId = 'modalDetail' . str_replace('-', '', $date);
                        ?>
                            <tr>
                                <td class="px-4 fw-bold text-muted"><?= $no++ ?></td>
                                <td class="px-4 fw-bold text-dark"><?= e(formatTanggal($date)) ?></td>
                                <td class="px-4 text-end text-nowrap">
                                    <span class="fw-bold text-primary"><?= number_format($row['total_qty'], 0, ',', '.') ?> Pcs</span>
                                    <br><small class="text-muted fw-bold"><?= (float)$row['total_bal'] ?> Bal</small>
                                </td>
                                <td class="px-4 text-end fw-bold text-success fs-6 text-nowrap"><?= formatRupiah($row['total_upah']) ?></td>
                                <td class="px-4 text-center">
                                    <button class="btn btn-sm btn-outline-primary rounded-pill fw-semibold px-3" data-bs-toggle="modal" data-bs-target="#<?= $modalId ?>">
                                        <i class="bi bi-eye me-1"></i> Detail
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="table-light fw-bold">
                            <td colspan="2" class="px-4 text-end rounded-start text-dark">GRAND TOTAL PRODUKSI:</td>
                            <td class="px-4 text-end text-nowrap">
                                <span class="text-primary"><?= number_format($grandQty, 0, ',', '.') ?> Pcs</span>
                                <br><small class="text-muted"><?= (float)$grandBal ?> Bal</small>
                            </td>
                            <td class="px-4 text-end text-success fs-5 text-nowrap"><?= formatRupiah($grandTotal) ?></td>
                            <td class="rounded-end"></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Detail Produksi Per Tanggal -->
<?php if (!empty($groupedData)): ?>
    <?php foreach ($groupedData as $date => $row): 
        $modalId = 'modalDetail' . str_replace('-', '', $date);
    ?>
    <div class="modal fade" id="<?= $modalId ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header border-bottom pb-3 pt-4 px-4 bg-white">
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0">Detail Produksi Harian</h5>
                        <small class="text-muted fw-semibold"><?= e(formatTanggal($date)) ?></small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="py-3 px-4">Produk</th>
                                    <th class="py-3 px-4 text-end">Kuantitas</th>
                                    <th class="py-3 px-4 text-end">Upah (Rp)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                    $empGroups = [];
                                    $productGroups = [];
                                    foreach ($row['details'] as $det) {
                                        $empName = $det['employee_name'];
                                        $prodName = $det['product_name'];
                                        
                                        if (!isset($empGroups[$empName])) {
                                            $empGroups[$empName] = [];
                                        }
                                        $empGroups[$empName][] = $det;
                                        
                                        if (!isset($productGroups[$prodName])) {
                                            $productGroups[$prodName] = [
                                                'qty' => 0,
                                                'bal' => 0,
                                                'upah' => 0
                                            ];
                                        }
                                        $productGroups[$prodName]['qty'] += $det['qty'];
                                        $productGroups[$prodName]['bal'] += $det['bal'];
                                        $productGroups[$prodName]['upah'] += $det['upah'];
                                    }
                                ?>
                                <?php foreach ($empGroups as $empName => $items): 
                                    $empTotalUpah = array_sum(array_column($items, 'upah'));
                                ?>
                                    <!-- Header Karyawan -->
                                    <tr style="background-color: #f8fafc; border-top: 2px solid #e2e8f0;">
                                        <td colspan="2" class="py-2.5 px-4 fw-bold text-dark border-bottom-0">
                                            <div class="d-flex align-items-center">
                                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-2.5" style="width: 32px; height: 32px;">
                                                    <i class="bi bi-person-fill fs-6"></i>
                                                </div>
                                                <span><?= e($empName) ?></span>
                                            </div>
                                        </td>
                                        <td class="py-2.5 px-4 text-end fw-bold text-primary fs-6 border-bottom-0 align-middle">
                                            <?= formatRupiah($empTotalUpah) ?>
                                        </td>
                                    </tr>
                                    <!-- Daftar Produk Karyawan -->
                                    <?php foreach ($items as $det): ?>
                                    <tr>
                                        <td class="px-4 ps-5">
                                            <div class="bg-light text-dark rounded px-2.5 py-1 fw-medium d-inline-block border" style="font-size: 0.85rem;">
                                                <i class="bi bi-box-seam text-secondary me-1"></i> <?= e($det['product_name']) ?>
                                            </div>
                                        </td>
                                        <td class="px-4 text-end text-nowrap align-middle">
                                            <span class="fw-bold text-dark"><?= number_format($det['qty'], 0, ',', '.') ?> Pcs</span><br>
                                            <small class="text-muted fw-bold"><?= (float)$det['bal'] ?> Bal</small>
                                        </td>
                                        <td class="px-4 text-end fw-bold text-success fs-6 text-nowrap align-middle"><?= formatRupiah($det['upah']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </tbody>
                            <tbody class="border-top-0">
                                <tr style="background-color: #f1f5f9; border-top: 3px solid #cbd5e1;">
                                    <td colspan="3" class="py-2.5 px-4 fw-bold text-dark border-bottom-0">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-secondary bg-opacity-10 text-secondary rounded-circle d-flex align-items-center justify-content-center me-2.5" style="width: 32px; height: 32px;">
                                                <i class="bi bi-list-check fs-6"></i>
                                            </div>
                                            <span>Rekap Total Per Item</span>
                                        </div>
                                    </td>
                                </tr>
                                <?php foreach ($productGroups as $prodName => $prodTotals): ?>
                                <tr>
                                    <td class="px-4 ps-5">
                                        <div class="bg-primary bg-opacity-10 text-primary border border-primary-subtle rounded px-2.5 py-1 fw-bold d-inline-block" style="font-size: 0.85rem;">
                                            <i class="bi bi-box-fill me-1"></i> <?= e($prodName) ?>
                                        </div>
                                    </td>
                                    <td class="px-4 text-end text-nowrap align-middle">
                                        <span class="fw-bold text-dark"><?= number_format($prodTotals['qty'], 0, ',', '.') ?> Pcs</span><br>
                                        <small class="text-muted fw-bold"><?= (float)$prodTotals['bal'] ?> Bal</small>
                                    </td>
                                    <td class="px-4 text-end fw-bold text-success fs-6 text-nowrap align-middle"><?= formatRupiah($prodTotals['upah']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-light fw-bold border-top border-2">
                                    <td class="py-3 px-4 text-end text-dark">TOTAL HARI INI:</td>
                                    <td class="py-3 px-4 text-end text-nowrap">
                                        <span class="fw-bold text-primary"><?= number_format($row['total_qty'], 0, ',', '.') ?> Pcs</span><br>
                                        <small class="text-muted fw-bold"><?= (float)$row['total_bal'] ?> Bal</small>
                                    </td>
                                    <td class="py-3 px-4 text-end fw-bold text-success fs-5 text-nowrap"><?= formatRupiah($row['total_upah']) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-3 px-4 pb-4 bg-light">
                    <button type="button" class="btn btn-secondary rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
