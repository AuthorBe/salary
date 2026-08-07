<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h3 fw-bold text-dark mb-1">Rekap Produksi</h2>
        <p class="text-muted mb-0">Laporan total produksi reguler per hari.</p>
    </div>
    <div>
        <a href="<?= url('/rekap') ?>" class="btn btn-outline-secondary rounded-pill fw-bold">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <form method="GET" action="<?= url('/rekap/production') ?>" class="row g-3 align-items-end">
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

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4 p-md-5">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="py-3 px-4 rounded-start">No</th>
                        <th class="py-3 px-4">Tanggal Produksi</th>
                        <th class="py-3 px-4 text-end">Total Kuantitas</th>
                        <th class="py-3 px-4 text-end">Total Upah (Rp)</th>
                        <th class="py-3 px-4 text-center rounded-end">Aksi</th>
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
                        foreach ($groupedData as $date => $row): 
                            $grandTotal += $row['total_upah'];
                            $modalId = 'modalDetail' . str_replace('-', '', $date);
                        ?>
                            <tr>
                                <td class="px-4 fw-bold text-muted"><?= $no++ ?></td>
                                <td class="px-4 fw-bold text-dark"><?= date('d F Y', strtotime($date)) ?></td>
                                <td class="px-4 text-end text-nowrap">
                                    <span class="fw-bold text-primary"><?= number_format($row['total_qty'], 0, ',', '.') ?> Pcs</span>
                                    <br><small class="text-muted fw-bold"><?= (float)$row['total_bal'] ?> Bal</small>
                                </td>
                                <td class="px-4 text-end fw-bold text-success fs-5 text-nowrap"><?= formatRupiah($row['total_upah']) ?></td>
                                <td class="px-4 text-center">
                                    <button class="btn btn-sm btn-outline-primary rounded-pill fw-bold px-4" data-bs-toggle="modal" data-bs-target="#<?= $modalId ?>">
                                        <i class="bi bi-eye me-1"></i> Detail
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="table-light fw-bold">
                            <td colspan="3" class="px-4 text-end rounded-start text-dark">GRAND TOTAL UPAH PRODUKSI:</td>
                            <td class="px-4 text-end text-success fs-4 text-nowrap"><?= formatRupiah($grandTotal) ?></td>
                            <td class="rounded-end"></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Render semua Modal Detail di luar dari elemen Table -->
<?php if (!empty($groupedData)): ?>
    <?php foreach ($groupedData as $date => $row): 
        $modalId = 'modalDetail' . str_replace('-', '', $date);
    ?>
    <div class="modal fade" id="<?= $modalId ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header border-bottom pb-3 pt-4 px-4 bg-white">
                    <div>
                        <h4 class="modal-title fw-bold text-dark mb-0">Detail Produksi</h4>
                        <small class="text-muted fw-bold"><?= date('d F Y', strtotime($date)) ?></small>
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
                                <?php foreach ($empGroups as $empName => $items): ?>
                                    <!-- Header Karyawan -->
                                    <tr class="bg-light">
                                        <td colspan="3" class="py-2 px-4 fw-bold text-dark border-bottom-0">
                                            <i class="bi bi-person-circle text-primary me-2"></i> <?= e($empName) ?>
                                        </td>
                                    </tr>
                                    <!-- Daftar Produk Karyawan -->
                                    <?php foreach ($items as $det): ?>
                                    <tr>
                                        <td class="px-4 ps-5"><span class="badge bg-white text-dark border shadow-sm px-3 py-2"><?= e($det['product_name']) ?></span></td>
                                        <td class="px-4 text-end text-nowrap">
                                            <span class="fw-bold text-primary"><?= number_format($det['qty'], 0, ',', '.') ?> Pcs</span><br>
                                            <small class="text-muted fw-bold"><?= (float)$det['bal'] ?> Bal</small>
                                        </td>
                                        <td class="px-4 text-end fw-bold text-success fs-6 text-nowrap"><?= formatRupiah($det['upah']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </tbody>
                            <tbody class="border-top-0">
                                <tr class="bg-light">
                                    <td colspan="3" class="py-3 px-4 fw-bold text-dark border-bottom-0" style="border-top: 2px dashed #dee2e6;">
                                        <i class="bi bi-list-check text-secondary me-2"></i> Rekap Total Per Item
                                    </td>
                                </tr>
                                <?php foreach ($productGroups as $prodName => $prodTotals): ?>
                                <tr>
                                    <td class="px-4 ps-5"><span class="badge bg-secondary text-white shadow-sm px-3 py-2"><?= e($prodName) ?></span></td>
                                    <td class="px-4 text-end text-nowrap">
                                        <span class="fw-bold text-primary"><?= number_format($prodTotals['qty'], 0, ',', '.') ?> Pcs</span><br>
                                        <small class="text-muted fw-bold"><?= (float)$prodTotals['bal'] ?> Bal</small>
                                    </td>
                                    <td class="px-4 text-end fw-bold text-success fs-6 text-nowrap"><?= formatRupiah($prodTotals['upah']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-light fw-bold border-top border-2">
                                    <td class="py-3 px-4 text-end text-dark fs-6">TOTAL KESELURUHAN:</td>
                                    <td class="py-3 px-4 text-end text-nowrap">
                                        <span class="fw-bold text-primary fs-6"><?= number_format($row['total_qty'], 0, ',', '.') ?> Pcs</span><br>
                                        <small class="text-muted fw-bold"><?= (float)$row['total_bal'] ?> Bal</small>
                                    </td>
                                    <td class="py-3 px-4 text-end fw-bold text-success fs-5 text-nowrap"><?= formatRupiah($row['total_upah']) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-3 px-4 pb-4 bg-light">
                    <button type="button" class="btn btn-secondary rounded-pill px-4 fw-bold shadow-sm" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
