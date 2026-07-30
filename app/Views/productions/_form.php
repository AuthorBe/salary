<?php
/**
 * @var string $date
 * @var array $employees
 * @var array $products
 * @var array $productions
 */

// Peta Produk untuk lookup cepat nama & grup
$productMap = [];
foreach ($products as $p) {
    $productMap[$p['id']] = $p;
}
?>

<?php if (empty($employees)): ?>
    <div class="alert alert-info border-0 shadow-sm d-flex align-items-center mb-0">
        <i class="bi bi-info-circle-fill me-2 fs-5"></i>
        <div>Belum ada data karyawan aktif dengan tipe gaji borongan. Silakan tambahkan karyawan borongan terlebih dahulu.</div>
    </div>
<?php elseif (empty($products)): ?>
    <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center mb-0">
        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
        <div>Belum ada data Master Produk. Silakan tambahkan produk terlebih dahulu.</div>
    </div>
<?php else: ?>

    <div class="row g-4">
        <!-- ── Form Express Input Per-Karyawan ────────────────────────────── -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom pt-3 pb-3">
                    <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center">
                        <i class="bi bi-pencil-square text-primary me-2"></i>
                        Express Input Produksi Karyawan
                    </h5>
                    <small class="text-muted">Tanggal: <?= e(formatTanggal($date)) ?></small>
                </div>
                <div class="card-body p-4">
                    <form id="expressProductionForm" 
                          method="POST"
                          action="<?= url('/productions/store') ?>"
                          hx-post="<?= url('/productions/store') ?>" 
                          hx-target="#production-form-container" 
                          hx-swap="innerHTML">

                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                        <input type="hidden" name="date" value="<?= e($date) ?>">

                        <!-- Pilih Karyawan -->
                        <div class="mb-4">
                            <label for="expressEmployeeSelect" class="form-label fw-semibold">Pilih Karyawan Borongan</label>
                            <select name="id_karyawan" class="form-select form-select-lg border-primary-subtle" id="expressEmployeeSelect" required>
                                <option value="">-- Pilih Karyawan --</option>
                                <?php foreach ($employees as $emp): ?>
                                    <?php if (!(bool)$emp['aktif']) continue; ?>
                                    <?php $hasEntry = !empty($productions[$emp['id']]); ?>
                                    <option value="<?= $emp['id'] ?>">
                                        <?= e($emp['name']) ?> <?= $hasEntry ? '✓ (Sudah ada data)' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Area Input Produk Dinamis -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label fw-semibold mb-0">Item Produk Hasil Kerja</label>
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill" id="btnAddProductRow">
                                    <i class="bi bi-plus-lg me-1"></i>Tambah Produk Lain
                                </button>
                            </div>

                            <div id="productRowsContainer" class="d-flex flex-column gap-3">
                                <!-- Baris Default Pertama -->
                                <div class="product-row-item card bg-light border-0 p-3">
                                    <div class="row g-2 align-items-end">
                                        <div class="col-12 col-sm-6">
                                            <label class="form-label small text-muted">Nama Produk</label>
                                            <select name="items[0][id_produk]" class="form-select form-select-sm" required>
                                                <option value="">-- Pilih Produk --</option>
                                                <?php foreach ($products as $prod): ?>
                                                    <option value="<?= $prod['id'] ?>">
                                                        <?= e($prod['name']) ?> (<?= e($prod['group_name'] ?? 'Umum') ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-5 col-sm-3">
                                            <label class="form-label small text-muted">Bungkus</label>
                                            <input type="text" inputmode="numeric" class="form-control form-control-sm text-center input-rupiah" 
                                                   name="items[0][kuantitas]" placeholder="0">
                                        </div>
                                        <div class="col-5 col-sm-3">
                                            <label class="form-label small text-muted">Bal</label>
                                            <input type="text" inputmode="numeric" class="form-control form-control-sm text-center input-rupiah" 
                                                   name="items[0][kuantitas_bal]" placeholder="0">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-3 border-top d-flex justify-content-end align-items-center gap-2">
                            <span class="htmx-indicator spinner-border spinner-border-sm text-primary me-2" role="status"></span>
                            <button type="submit" class="btn btn-primary px-4 fw-semibold rounded-pill shadow-sm">
                                <i class="bi bi-save me-2"></i>Simpan Produksi Karyawan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ── Tabel Ringkasan Live Produksi Hari Ini ───────────────────────── -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom pt-3 pb-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title fw-bold text-dark mb-0 d-flex align-items-center">
                        <i class="bi bi-list-check text-success me-2"></i>
                        Ringkasan Terisi (<?= count($productions) ?> Karyawan)
                    </h5>
                    <span class="badge bg-primary-subtle text-primary rounded-pill px-3">
                        <?= e(formatTanggal($date)) ?>
                    </span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($productions)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            <div>Belum ada data produksi terisi pada tanggal ini.</div>
                            <small>Gunakan form di samping untuk menginput produksi karyawan.</small>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" class="ps-3 fw-semibold">Nama Karyawan</th>
                                        <th scope="col" class="fw-semibold">Rincian Produk Hasil</th>
                                        <th scope="col" class="text-end pe-3 fw-semibold">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    // Peta nama karyawan untuk render cepat
                                    $empMap = [];
                                    foreach ($employees as $e) {
                                        $empMap[$e['id']] = $e['name'];
                                    }
                                    ?>
                                    <?php foreach ($productions as $empId => $userProds): ?>
                                        <tr>
                                            <td class="ps-3 fw-medium text-dark">
                                                <?= e($empMap[$empId] ?? "Karyawan #{$empId}") ?>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <?php foreach ($userProds as $prodId => $pData): ?>
                                                        <?php 
                                                        $pName = $productMap[$prodId]['name'] ?? "Produk #{$prodId}";
                                                        $qty = $pData['kuantitas'] ?? 0;
                                                        $bal = $pData['kuantitas_bal'] ?? 0;
                                                        ?>
                                                        <div class="border rounded px-2 py-1 bg-light d-inline-flex flex-column lh-sm">
                                                            <span class="text-secondary" style="font-size: 0.7rem;"><?= e($pName) ?></span>
                                                            <strong class="text-dark" style="font-size: 0.85rem;">
                                                                <?php if ($qty > 0): ?><?= number_format($qty, 0, ',', '.') ?> Bks<?php endif; ?>
                                                                <?php if ($qty > 0 && $bal > 0): ?> &bull; <?php endif; ?>
                                                                <?php if ($bal > 0): ?><?= number_format($bal, 0, ',', '.') ?> Bal<?php endif; ?>
                                                            </strong>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </td>
                                            <td class="text-end pe-3">
                                                <form method="POST"
                                                      action="<?= url('/productions/delete-employee') ?>"
                                                      hx-post="<?= url('/productions/delete-employee') ?>" 
                                                      hx-target="#production-form-container" 
                                                      hx-swap="innerHTML"
                                                      hx-confirm="Yakin ingin menghapus catatan produksi <?= e($empMap[$empId] ?? 'karyawan ini') ?> di tanggal ini?"
                                                      class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                                    <input type="hidden" name="date" value="<?= e($date) ?>">
                                                    <input type="hidden" name="id_karyawan" value="<?= $empId ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger border-0 rounded-circle" title="Hapus catatan">
                                                        <i class="bi bi-trash-fill"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Script Tambah Baris Produk Dinamis -->
    <script>
        (function() {
            let rowCount = 1;
            const container = document.getElementById('productRowsContainer');
            const btnAdd = document.getElementById('btnAddProductRow');

            if (btnAdd && container) {
                btnAdd.addEventListener('click', () => {
                    const rowDiv = document.createElement('div');
                    rowDiv.className = 'product-row-item card bg-light border-0 p-3';
                    rowDiv.innerHTML = `
                        <div class="row g-2 align-items-end">
                            <div class="col-12 col-sm-6">
                                <label class="form-label small text-muted">Nama Produk</label>
                                <select name="items[` + rowCount + `][id_produk]" class="form-select form-select-sm" required>
                                    <option value="">-- Pilih Produk --</option>
                                    <?php foreach ($products as $prod): ?>
                                        <option value="<?= $prod['id'] ?>">
                                            <?= e($prod['name']) ?> (<?= e($prod['group_name'] ?? 'Umum') ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-4 col-sm-2">
                                <label class="form-label small text-muted">Bungkus</label>
                                <input type="text" inputmode="numeric" class="form-control form-control-sm text-center input-rupiah" 
                                       name="items[` + rowCount + `][kuantitas]" placeholder="0">
                            </div>
                            <div class="col-4 col-sm-2">
                                <label class="form-label small text-muted">Bal</label>
                                <input type="text" inputmode="numeric" class="form-control form-control-sm text-center input-rupiah" 
                                       name="items[` + rowCount + `][kuantitas_bal]" placeholder="0">
                            </div>
                            <div class="col-4 col-sm-2 text-end">
                                <button type="button" class="btn btn-sm btn-outline-danger w-100 btn-remove-row" title="Hapus baris ini">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    `;
                    container.appendChild(rowDiv);
                    rowCount++;

                    // Attach remove event handler
                    rowDiv.querySelector('.btn-remove-row')?.addEventListener('click', () => {
                        rowDiv.remove();
                    });
                });
            }
        })();
    </script>

<?php endif; ?>
