<?php
/**
 * @var string $title
 * @var array $summary
 * @var array $bulanan
 * @var array $borongan
 */
?>

<!-- Page Header -->
<div class="page-title-box d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
    <div class="page-title-left">
        <h4 class="mb-1 text-dark fw-bold d-flex align-items-center">
            <i class="bi bi-safe text-primary me-2 fs-4"></i> Tabungan Karyawan
        </h4>
        <p class="text-muted mb-0 fs-7 ms-4 ps-1">Kelola saldo tabungan dan riwayat transaksi secara manual maupun lewat payroll.</p>
    </div>
</div>

<!-- 2 Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-6 col-md-6">
        <div class="stat-card h-100">
            <div class="stat-icon stat-icon-success">
                <i class="bi bi-cash-coin"></i>
            </div>
            <div class="stat-info">
                <div class="stat-label">Total Saldo Tabungan</div>
                <div class="stat-value text-success" id="stat-total-saldo">
                    <?= formatRupiah((int)$summary['total_saldo']) ?>
                </div>
                <div class="stat-sub">Semua karyawan</div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-6 col-md-6">
        <div class="stat-card h-100">
            <div class="stat-icon stat-icon-info">
                <i class="bi bi-people-fill"></i>
            </div>
            <div class="stat-info">
                <div class="stat-label">Karyawan Memiliki Tabungan</div>
                <div class="stat-value text-info" id="stat-total-karyawan">
                    <?= (int)$summary['karyawan_menabung'] ?> <span class="fs-6 text-muted fw-normal">Orang</span>
                </div>
                <div class="stat-sub">Saldo aktif lebih dari 0</div>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Karyawan Bulanan -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-0">
        <h5 class="fw-bold text-dark mb-0">Karyawan Bulanan</h5>
    </div>
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="table-bulanan">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width: 5%;">NO</th>
                        <th style="width: 30%;">NAMA KARYAWAN</th>
                        <th class="text-end" style="width: 25%;">TOTAL SALDO (Rp)</th>
                        <th class="text-center" style="width: 40%;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bulanan)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">Tidak ada data karyawan bulanan.</td></tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($bulanan as $k): ?>
                            <tr id="row-<?= $k['id'] ?>">
                                <td class="ps-3 text-muted"><?= $no++ ?></td>
                                <td class="fw-bold text-dark"><?= e($k['name']) ?></td>
                                <td class="text-end fw-bold text-success saldo-cell" data-saldo="<?= $k['saldo'] ?>">
                                    <?= formatRupiah((int)$k['saldo']) ?>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-secondary rounded-pill px-3 me-1" onclick="openHistory(<?= $k['id'] ?>)">
                                        <i class="bi bi-clock-history"></i> Riwayat
                                    </button>
                                    <button class="btn btn-sm btn-outline-success rounded-pill px-3 me-1" onclick="openTransaction(<?= $k['id'] ?>, '<?= e(addslashes($k['name'])) ?>', 'deposit')">
                                        <i class="bi bi-box-arrow-in-down"></i> Setor
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="openTransaction(<?= $k['id'] ?>, '<?= e(addslashes($k['name'])) ?>', 'withdrawal')">
                                        <i class="bi bi-box-arrow-up"></i> Tarik
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Tabel Karyawan Borongan -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-0">
        <h5 class="fw-bold text-dark mb-0">Karyawan Borongan</h5>
    </div>
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="table-borongan">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width: 5%;">NO</th>
                        <th style="width: 30%;">NAMA KARYAWAN</th>
                        <th class="text-end" style="width: 25%;">TOTAL SALDO (Rp)</th>
                        <th class="text-center" style="width: 40%;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($borongan)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">Tidak ada data karyawan borongan.</td></tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($borongan as $k): ?>
                            <tr id="row-<?= $k['id'] ?>">
                                <td class="ps-3 text-muted"><?= $no++ ?></td>
                                <td class="fw-bold text-dark"><?= e($k['name']) ?></td>
                                <td class="text-end fw-bold text-success saldo-cell" data-saldo="<?= $k['saldo'] ?>">
                                    <?= formatRupiah((int)$k['saldo']) ?>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-secondary rounded-pill px-3 me-1" onclick="openHistory(<?= $k['id'] ?>)">
                                        <i class="bi bi-clock-history"></i> Riwayat
                                    </button>
                                    <button class="btn btn-sm btn-outline-success rounded-pill px-3 me-1" onclick="openTransaction(<?= $k['id'] ?>, '<?= e(addslashes($k['name'])) ?>', 'deposit')">
                                        <i class="bi bi-box-arrow-in-down"></i> Setor
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="openTransaction(<?= $k['id'] ?>, '<?= e(addslashes($k['name'])) ?>', 'withdrawal')">
                                        <i class="bi bi-box-arrow-up"></i> Tarik
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Setor/Tarik -->
<div class="modal fade" id="modalTransaction" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-bottom-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold" id="modalTransactionTitle">Setor Tabungan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formTransaction" onsubmit="submitTransaction(event)">
                    <?= csrfField() ?>
                    <input type="hidden" name="id_karyawan" id="trans_id_karyawan">
                    <input type="hidden" name="tipe" id="trans_tipe">
                    
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Karyawan</label>
                        <input type="text" class="form-control" id="trans_nama" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Tanggal</label>
                        <input type="date" class="form-control" name="tanggal" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold" id="label-jumlah">Jumlah Setoran (Rp)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">Rp</span>
                            <input type="text" class="form-control border-start-0 ps-0 fw-bold" 
                                   name="jumlah" id="trans_jumlah" required 
                                   oninput="formatRupiahInput(this); validateTransaction()">
                        </div>
                        <div id="trans_error" class="text-danger small mt-1 d-none">
                            <i class="bi bi-exclamation-triangle"></i> Saldo tidak mencukupi. Saldo saat ini: <span id="trans_current_saldo_txt"></span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold">Keterangan (Opsional)</label>
                        <textarea class="form-control" name="keterangan" rows="2" placeholder="Contoh: Setoran tunai bulan Juni"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold" id="btnSubmitTrans">
                        Simpan Transaksi
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Riwayat -->
<div class="modal fade" id="modalHistory" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-bottom-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold">Riwayat Tabungan: <span id="history_nama" class="text-primary"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 15%;">TANGGAL</th>
                                <th style="width: 15%;">TIPE</th>
                                <th class="text-end" style="width: 20%;">JUMLAH (Rp)</th>
                                <th style="width: 25%;">KETERANGAN / SUMBER</th>
                                <th class="text-center" style="width: 25%;">AKSI</th>
                            </tr>
                        </thead>
                        <tbody id="history_tbody">
                            <tr><td colspan="5" class="text-center py-4">Memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Transaksi Manual -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-bottom-0 pb-0 pt-3 px-3">
                <h6 class="modal-title fw-bold">Edit Transaksi Manual</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <form id="formEdit" onsubmit="submitEdit(event)">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Jumlah (Rp)</label>
                        <input type="text" class="form-control fw-bold" name="jumlah" id="edit_jumlah" required oninput="formatRupiahInput(this)">
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Keterangan</label>
                        <textarea class="form-control" name="keterangan" id="edit_keterangan" rows="2"></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-light rounded-pill flex-grow-1" onclick="backToHistory()">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-pill flex-grow-1">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Utils for formatting
function formatRupiahJS(angka) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
}

function formatRupiahInput(input) {
    let value = input.value.replace(/[^,\d]/g, '').toString();
    let split = value.split(',');
    let sisa = split[0].length % 3;
    let rupiah = split[0].substr(0, sisa);
    let ribuan = split[0].substr(sisa).match(/\d{3}/gi);
    
    if (ribuan) {
        let separator = sisa ? '.' : '';
        rupiah += separator + ribuan.join('.');
    }
    
    input.value = rupiah;
}

// Global state for validation
let currentTransactionEmpId = 0;
let currentTransactionType = '';

function openTransaction(id_karyawan, nama, tipe) {
    currentTransactionEmpId = id_karyawan;
    currentTransactionType = tipe;
    
    document.getElementById('trans_id_karyawan').value = id_karyawan;
    document.getElementById('trans_tipe').value = tipe;
    document.getElementById('trans_nama').value = nama;
    document.getElementById('trans_jumlah').value = '';
    document.getElementById('trans_error').classList.add('d-none');
    document.getElementById('btnSubmitTrans').disabled = false;
    
    if (tipe === 'deposit') {
        document.getElementById('modalTransactionTitle').innerText = 'Setor Tabungan';
        document.getElementById('label-jumlah').innerText = 'Jumlah Setoran (Rp)';
        document.getElementById('btnSubmitTrans').classList.replace('btn-danger', 'btn-primary');
    } else {
        document.getElementById('modalTransactionTitle').innerText = 'Tarik Tabungan';
        document.getElementById('label-jumlah').innerText = 'Jumlah Penarikan (Rp)';
        document.getElementById('btnSubmitTrans').classList.replace('btn-primary', 'btn-danger');
    }
    
    let modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalTransaction'));
    modal.show();
}

function validateTransaction() {
    if (currentTransactionType !== 'withdrawal') return;
    
    let amountStr = document.getElementById('trans_jumlah').value.replace(/\./g, '');
    let amount = parseInt(amountStr) || 0;
    
    let row = document.getElementById('row-' + currentTransactionEmpId);
    let currentSaldo = parseInt(row.querySelector('.saldo-cell').dataset.saldo);
    
    let btn = document.getElementById('btnSubmitTrans');
    let err = document.getElementById('trans_error');
    let txt = document.getElementById('trans_current_saldo_txt');
    
    if (amount > currentSaldo) {
        btn.disabled = true;
        err.classList.remove('d-none');
        txt.innerText = formatRupiahJS(currentSaldo);
    } else {
        btn.disabled = false;
        err.classList.add('d-none');
    }
}

async function submitTransaction(e) {
    e.preventDefault();
    
    let tipe = currentTransactionType === 'deposit' ? 'menyetorkan' : 'menarik';
    let nama = document.getElementById('trans_nama').value;
    let jumlah = document.getElementById('trans_jumlah').value;
    
    let confirm = await Swal.fire({
        title: 'Konfirmasi',
        text: `Anda akan ${tipe} Rp ${jumlah} untuk karyawan ${nama}. Lanjutkan?`,
        icon: 'question',
        showCancelButton: true,
        reverseButtons: true,
        confirmButtonText: 'Ya, Lanjutkan',
        cancelButtonText: 'Batal',
        allowEnterKey: false,
        didOpen: (popup) => {
            window._swalKeyHandler = function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    e.stopPropagation();
                    Swal.clickConfirm();
                }
            };
            document.addEventListener('keydown', window._swalKeyHandler, true);
        },
        willClose: () => {
            if (window._swalKeyHandler) {
                document.removeEventListener('keydown', window._swalKeyHandler, true);
            }
        }
    });
    
    if (!confirm.isConfirmed) return;
    
    let form = e.target;
    let formData = new FormData(form);
    
    try {
        let response = await fetch('<?= url('/savings/store') ?>', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        
        let res = await response.json();
        if (res.success) {
            Swal.fire('Berhasil!', res.message, 'success');
            
            // Update table cell
            let row = document.getElementById('row-' + currentTransactionEmpId);
            let cell = row.querySelector('.saldo-cell');
            cell.dataset.saldo = res.new_balance;
            cell.innerText = formatRupiahJS(res.new_balance);
            
            bootstrap.Modal.getInstance(document.getElementById('modalTransaction')).hide();
            
            // Note: Update global stat total is a bit complex via DOM, we just let it be or reload if strictly needed.
            // For now, updating row is enough for UX.
        } else {
            Swal.fire('Gagal!', res.message || 'Terjadi kesalahan sistem', 'error');
        }
    } catch (err) {
        Swal.fire('Error!', 'Gagal menghubungi server.', 'error');
    }
}

let activeHistoryEmpId = 0;

async function openHistory(id_karyawan) {
    activeHistoryEmpId = id_karyawan;
    let tbody = document.getElementById('history_tbody');
    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4">Memuat data...</td></tr>';
    document.getElementById('history_nama').innerText = '';
    
    let modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalHistory'));
    modal.show();
    
    try {
        let response = await fetch('<?= url('/savings/history?id=') ?>' + id_karyawan, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        let res = await response.json();
        
        if (res.success) {
            document.getElementById('history_nama').innerText = res.employee.name;
            
            if (res.transactions.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">Belum ada riwayat transaksi.</td></tr>';
                return;
            }
            
            let html = '';
            res.transactions.forEach(t => {
                let badge = t.tipe === 'deposit' 
                    ? '<span class="badge bg-success-subtle text-success">Setor</span>' 
                    : '<span class="badge bg-danger-subtle text-danger">Tarik</span>';
                
                let nominal = formatRupiahJS(t.jumlah);
                let isManual = t.sumber === 'manual';
                let sumber = isManual ? '<span class="badge bg-secondary-subtle text-secondary">Manual</span>' : '<span class="badge bg-primary-subtle text-primary">Payroll</span>';
                let ket = t.keterangan || '-';
                
                let btnAksi = '-';
                if (isManual) {
                    btnAksi = `
                        <button class="btn btn-sm btn-outline-primary rounded-pill px-2 py-0 me-1" onclick="openEdit(${t.id}, ${t.jumlah}, '${addslashes(t.keterangan || '')}')" title="Edit"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger rounded-pill px-2 py-0" onclick="deleteTransaction(${t.id})" title="Hapus"><i class="bi bi-trash"></i></button>
                    `;
                }

                html += `
                    <tr>
                        <td>${t.tanggal}</td>
                        <td>${badge}</td>
                        <td class="text-end fw-bold">${nominal}</td>
                        <td><div class="small">${ket}</div>${sumber}</td>
                        <td class="text-center">${btnAksi}</td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        } else {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger py-4">${res.message}</td></tr>`;
        }
    } catch (err) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-4">Gagal memuat data dari server.</td></tr>';
    }
}

function addslashes(str) {
    return (str + '').replace(/[\\"']/g, '\\$&').replace(/\u0000/g, '\\0');
}

function openEdit(id, jumlah, keterangan) {
    bootstrap.Modal.getInstance(document.getElementById('modalHistory')).hide();
    
    document.getElementById('edit_id').value = id;
    let inputJumlah = document.getElementById('edit_jumlah');
    inputJumlah.value = jumlah;
    formatRupiahInput(inputJumlah);
    
    document.getElementById('edit_keterangan').value = keterangan;
    
    setTimeout(() => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEdit')).show();
    }, 400); // Wait for history modal to hide
}

function backToHistory() {
    bootstrap.Modal.getInstance(document.getElementById('modalEdit')).hide();
    setTimeout(() => {
        openHistory(activeHistoryEmpId);
    }, 400);
}

async function submitEdit(e) {
    e.preventDefault();
    
    let form = e.target;
    let formData = new FormData(form);
    
    try {
        let response = await fetch('<?= url('/savings/update') ?>', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        
        let res = await response.json();
        if (res.success) {
            Swal.fire({ title: 'Tersimpan!', text: res.message, icon: 'success', timer: 1500 });
            
            // Update table cell
            let row = document.getElementById('row-' + activeHistoryEmpId);
            if (row) {
                let cell = row.querySelector('.saldo-cell');
                cell.dataset.saldo = res.new_balance;
                cell.innerText = formatRupiahJS(res.new_balance);
            }
            
            backToHistory();
        } else {
            Swal.fire('Gagal!', res.message, 'error');
        }
    } catch (err) {
        Swal.fire('Error!', 'Gagal menghubungi server.', 'error');
    }
}

async function deleteTransaction(id) {
    let confirm = await Swal.fire({
        title: 'Hapus Transaksi?',
        text: "Data yang dihapus tidak dapat dikembalikan, dan saldo tabungan akan disesuaikan secara otomatis.",
        icon: 'warning',
        showCancelButton: true,
        reverseButtons: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        allowEnterKey: false,
        didOpen: (popup) => {
            window._swalKeyHandlerDel = function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    e.stopPropagation();
                    Swal.clickConfirm();
                }
            };
            document.addEventListener('keydown', window._swalKeyHandlerDel, true);
        },
        willClose: () => {
            if (window._swalKeyHandlerDel) {
                document.removeEventListener('keydown', window._swalKeyHandlerDel, true);
            }
        }
    });
    
    if (!confirm.isConfirmed) return;
    
    let formData = new FormData();
    formData.append('id', id);
    formData.append('csrf_token', '<?= generateCsrfToken() ?>');
    
    try {
        let response = await fetch('<?= url('/savings/delete') ?>', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        
        let res = await response.json();
        if (res.success) {
            Swal.fire({ title: 'Terhapus!', text: res.message, icon: 'success', timer: 1500 });
            
            // Update table cell
            let row = document.getElementById('row-' + activeHistoryEmpId);
            if (row) {
                let cell = row.querySelector('.saldo-cell');
                cell.dataset.saldo = res.new_balance;
                cell.innerText = formatRupiahJS(res.new_balance);
            }
            
            // Reload history
            openHistory(activeHistoryEmpId);
        } else {
            Swal.fire('Gagal!', res.message, 'error');
        }
    } catch (err) {
        Swal.fire('Error!', 'Gagal menghubungi server.', 'error');
    }
}
</script>
