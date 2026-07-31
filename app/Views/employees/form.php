<?php
/**
 * @var string $title
 * @var array $employees
 */

if (empty($employees)) {
    $employees = [[
        'id' => 0,
        'name' => '',
        'tipe_gaji' => 'borongan',
        'uang_kehadiran_harian' => 0,
        'tunjangan_bulanan' => 0,
        'gaji_pokok' => 0,
        'aktif' => 1,
        'error' => null
    ]];
}

$isEditMode = false;
foreach ($employees as $emp) {
    if (!empty($emp['id'])) {
        $isEditMode = true;
        break;
    }
}
?>

<div class="page-title-box d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
    <div class="page-title-left">
        <h2 class="h4 mb-1 fw-bold d-flex align-items-center">
            <i class="bi bi-person-fill text-primary me-2 fs-4"></i> <?= $title ?>
        </h2>
        <p class="text-muted mb-0 fs-7 ms-4 ps-1">Kelola data karyawan dalam jumlah banyak (Bulk Input/Edit)</p>
    </div>
    <div class="page-title-right">
        <a href="<?= url('/employees') ?>" class="btn btn-outline-secondary d-inline-flex align-items-center">
            <i class="bi bi-arrow-left me-2"></i> Kembali
        </a>
    </div>
</div>

<div class="alert alert-info d-flex align-items-center mb-4 border-0 shadow-sm" role="alert">
    <i class="bi bi-info-circle-fill text-info fs-4 me-3"></i>
    <div>
        <strong>Info Nominal:</strong> Jika komponen uang tidak diperlukan, biarkan Rp 0. Gaji Pokok khusus tipe Bulanan.
    </div>
</div>

<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger d-flex align-items-center" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <div><?= e($_SESSION['flash_error']) ?></div>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form action="<?= url('/employees/store') ?>" method="POST" id="bulkForm" data-add-row-btn=".btn-add-row">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

            <style>
            @media (max-width: 767.98px) {
                .responsive-table thead { display: none; }
                .responsive-table tbody tr {
                    display: block;
                    margin-bottom: 1rem;
                    border: 1px solid #e0e0e0;
                    border-radius: 0.5rem;
                    padding: 1rem;
                    background: #fff;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                }
                .responsive-table tbody td {
                    display: block;
                    border: none;
                    padding: 0.5rem 0;
                    text-align: left !important;
                }
                .responsive-table tbody td::before {
                    content: attr(data-label);
                    display: block;
                    font-weight: 600;
                    margin-bottom: 0.25rem;
                    color: #495057;
                    font-size: 0.875rem;
                }
                .responsive-table tbody td:last-child {
                    border-top: 1px solid #eee;
                    margin-top: 0.5rem;
                    padding-top: 1rem;
                    text-align: right !important;
                }
                .responsive-table tbody td:last-child::before {
                    display: none;
                }
            }
            </style>

            <div class="table-responsive mb-3" style="overflow-x: hidden;">
                <table class="table table-bordered align-middle responsive-table" id="dynamicTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 20%;">Nama Karyawan <span class="text-danger">*</span></th>
                            <th style="width: 12%;">Tipe Gaji</th>
                            <th style="width: 18%;">Gaji Pokok</th>
                            <th style="width: 18%;">Uang Hadir/Hari</th>
                            <th style="width: 18%;">Uang Bulanan</th>
                            <th style="width: 7%; text-align: center;">Aktif</th>
                            <th style="width: 7%; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <?php foreach ($employees as $index => $emp): ?>
                            <?php $isBulanan = ($emp['tipe_gaji'] ?? 'borongan') === 'bulanan'; ?>
                            <tr>
                                <td data-label="Nama Karyawan">
                                    <input type="hidden" name="employees[<?= $index ?>][id]" value="<?= $emp['id'] ?? 0 ?>">
                                    <input type="text" class="form-control enter-nav" name="employees[<?= $index ?>][name]" 
                                           value="<?= e($emp['name'] ?? '') ?>" placeholder="Nama Karyawan" required>
                                    <?php if (!empty($emp['error'])): ?>
                                        <div class="text-danger mt-1 fs-7"><i class="bi bi-x-circle"></i> <?= e($emp['error']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Tipe Gaji">
                                    <select class="form-select tipe-gaji-select enter-nav" name="employees[<?= $index ?>][tipe_gaji]" required>
                                        <option value="borongan" <?= !$isBulanan ? 'selected' : '' ?>>Borongan</option>
                                        <option value="bulanan" <?= $isBulanan ? 'selected' : '' ?>>Bulanan</option>
                                    </select>
                                </td>
                                <td data-label="Gaji Pokok (Bulanan)">
                                    <input type="text" inputmode="numeric" class="form-control input-rupiah input-gajipokok enter-nav" 
                                           name="employees[<?= $index ?>][gaji_pokok]" 
                                           value="<?= (int)($emp['gaji_pokok'] ?? 0) ?>" <?= !$isBulanan ? 'readonly' : '' ?>>
                                </td>
                                <td data-label="Uang Hadir / Hari">
                                    <input type="text" inputmode="numeric" class="form-control input-rupiah enter-nav" 
                                           name="employees[<?= $index ?>][uang_kehadiran_harian]" 
                                           value="<?= (int)($emp['uang_kehadiran_harian'] ?? 0) ?>">
                                </td>
                                <td data-label="Uang Bulanan">
                                    <input type="text" inputmode="numeric" class="form-control input-rupiah enter-nav" 
                                           name="employees[<?= $index ?>][tunjangan_bulanan]" 
                                           value="<?= (int)($emp['tunjangan_bulanan'] ?? 0) ?>">
                                </td>
                                <td data-label="Status Aktif" class="text-md-center">
                                    <?php if (empty($emp['id'])): ?>
                                        <input type="hidden" name="employees[<?= $index ?>][aktif]" value="on">
                                        <span class="badge bg-success rounded-pill px-3 py-2 fs-7">Aktif</span>
                                    <?php else: ?>
                                        <div class="form-check form-switch d-inline-block">
                                            <input class="form-check-input" type="checkbox" name="employees[<?= $index ?>][aktif]" 
                                                   <?= (!isset($emp['aktif']) || $emp['aktif']) ? 'checked' : '' ?>>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-md-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row w-100" onclick="removeRow(this)">
                                        <i class="bi bi-trash me-1"></i> Hapus
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex flex-column flex-sm-row justify-content-between gap-3 mt-4">
                <?php if (!$isEditMode): ?>
                <button type="button" class="btn btn-secondary rounded-pill px-4 py-2 w-100 btn-add-row" onclick="addRow()">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Baris
                </button>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 shadow-sm w-100">
                    <i class="bi bi-save me-2"></i> Simpan Semua
                </button>
            </div>
        </form>
    </div>
</div>

<template id="rowTemplate">
    <tr>
        <td data-label="Nama Karyawan">
            <input type="hidden" name="employees[{index}][id]" value="0">
            <input type="text" class="form-control enter-nav" name="employees[{index}][name]" placeholder="Nama Karyawan" required>
        </td>
        <td data-label="Tipe Gaji">
            <select class="form-select tipe-gaji-select enter-nav" name="employees[{index}][tipe_gaji]" required>
                <option value="borongan" selected>Borongan</option>
                <option value="bulanan">Bulanan</option>
            </select>
        </td>
        <td data-label="Gaji Pokok (Bulanan)">
            <input type="text" inputmode="numeric" class="form-control input-rupiah input-gajipokok enter-nav" 
                   name="employees[{index}][gaji_pokok]" value="0" readonly>
        </td>
        <td data-label="Uang Hadir / Hari">
            <input type="text" inputmode="numeric" class="form-control input-rupiah enter-nav" 
                   name="employees[{index}][uang_kehadiran_harian]" value="0">
        </td>
        <td data-label="Uang Bulanan">
            <input type="text" inputmode="numeric" class="form-control input-rupiah enter-nav" 
                   name="employees[{index}][tunjangan_bulanan]" value="0">
        </td>
        <td data-label="Status Aktif" class="text-md-center">
            <input type="hidden" name="employees[{index}][aktif]" value="on">
            <span class="badge bg-success rounded-pill px-3 py-2 fs-7">Aktif</span>
        </td>
        <td class="text-md-center">
            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row w-100" onclick="removeRow(this)">
                <i class="bi bi-trash me-1"></i> Hapus
            </button>
        </td>
    </tr>
</template>

<script>
let rowIndex = <?= max(count($employees), 1) * 100 ?>; // Make sure it's unique enough

function addRow() {
    const tbody = document.getElementById('tableBody');
    const template = document.getElementById('rowTemplate').innerHTML;
    const newHtml = template.replace(/\{index\}/g, rowIndex++);
    
    tbody.insertAdjacentHTML('beforeend', newHtml);
    
    // Re-init rupiah inputs for the newly added row
    if (typeof initRupiahInputs === 'function') {
        initRupiahInputs();
    }
}

function removeRow(btn) {
    const tbody = document.getElementById('tableBody');
    if (tbody.querySelectorAll('tr').length > 1) {
        const row = btn.closest('tr');
        let focusTargetRow = row.previousElementSibling;
        if (!focusTargetRow) {
            focusTargetRow = row.nextElementSibling;
        }
        
        row.remove();
        
        // Kembalikan kursor ke baris sebelumnya (atau setelahnya jika baris pertama dihapus)
        if (focusTargetRow) {
            const firstInput = focusTargetRow.querySelector('.enter-nav');
            if (firstInput) {
                firstInput.focus();
                if (firstInput.tagName.toLowerCase() === 'input') {
                    firstInput.select();
                }
            }
        }
    } else {
        Swal.fire({
            icon: 'warning',
            text: 'Minimal harus ada 1 baris.',
            toast: true,
            position: 'top',
            showConfirmButton: false,
            timer: 3000
        });
    }
}

document.addEventListener('change', function(e) {
    if (e.target && e.target.classList.contains('tipe-gaji-select')) {
        const row = e.target.closest('tr');
        const gajiPokokInput = row.querySelector('.input-gajipokok');
        if (e.target.value === 'bulanan') {
            gajiPokokInput.removeAttribute('readonly');
        } else {
            gajiPokokInput.setAttribute('readonly', 'true');
            gajiPokokInput.value = '0';
        }
    }
});
</script>
