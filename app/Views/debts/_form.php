<?php
/**
 * @var array|null $debt
 * @var array $activeEmployees
 * @var int|null $presetEmpId
 * @var array|null $presetEmployee
 */
$isEdit = !empty($debt);
$lockedEmpName = null;
$lockedEmpId = null;

if ($isEdit) {
    $lockedEmpName = $debt['employee_name'] . (isset($debt['tipe_gaji']) ? ' (' . ucfirst($debt['tipe_gaji']) . ')' : '');
    $lockedEmpId = (int)$debt['id_karyawan'];
} elseif (!empty($presetEmployee)) {
    $lockedEmpName = $presetEmployee['name'] . ' (' . ucfirst($presetEmployee['tipe_gaji']) . ')';
    $lockedEmpId = (int)$presetEmployee['id'];
}
?>
<form id="formDebtModal" action="<?= url('/debts/store') ?>" method="POST" onsubmit="submitDebtForm(event)" class="d-flex flex-column flex-grow-1" style="min-height: 0; height: 100%;">
    <?= csrfField() ?>
    <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= e($debt['id']) ?>">
    <?php endif; ?>

    <!-- Modal Header -->
    <div class="modal-header border-bottom px-3 px-sm-4 pt-3 pt-sm-4 pb-3 flex-shrink-0">
        <h5 class="modal-title fw-bold text-dark d-flex align-items-center mb-0">
            <i class="bi bi-wallet2 text-danger me-2"></i> <?= $isEdit ? 'Edit Kasbon' : 'Catat Kasbon Baru' ?>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <!-- Modal Body (Scrollable) -->
    <div class="modal-body p-3 p-sm-4" style="overflow-y: auto !important; flex: 1 1 auto; min-height: 0; max-height: calc(90vh - 135px);">
        <!-- Pilih / Lock Karyawan -->
        <div class="mb-3">
            <label class="form-label required fw-bold small text-muted">Karyawan</label>
            <?php if ($lockedEmpName !== null): ?>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted">
                        <i class="bi bi-person-lock fs-6"></i>
                    </span>
                    <input type="text" class="form-control bg-light border-start-0 fw-bold text-dark" value="<?= e($lockedEmpName) ?>" readonly>
                </div>
                <input type="hidden" name="id_karyawan" value="<?= e($lockedEmpId) ?>">
                <small class="text-muted" style="font-size: 0.75rem;"><i class="bi bi-lock-fill me-1"></i>Terkunci untuk karyawan yang dipilih</small>
            <?php else: ?>
                <select name="id_karyawan" id="id_karyawan" class="form-select searchable-select enter-nav" required>
                    <option value="">-- Pilih Karyawan --</option>
                    <?php foreach ($activeEmployees as $emp): ?>
                        <option value="<?= e($emp['id']) ?>" <?= ($presetEmpId == $emp['id']) ? 'selected' : '' ?>>
                            <?= e($emp['name']) ?> (<?= ucfirst(e($emp['tipe_gaji'])) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
        </div>

        <!-- Deskripsi Kasbon -->
        <div class="mb-3">
            <label for="keterangan" class="form-label required fw-bold small text-muted">Deskripsi / Peruntukan Kasbon</label>
            <input type="text" 
                   name="keterangan" 
                   id="keterangan" 
                   class="form-control enter-nav" 
                   placeholder="Contoh: Kasbon Motor, Pinjaman Pribadi, Biaya Berobat" 
                   value="<?= e($debt['keterangan'] ?? '') ?>" 
                   required>
        </div>

        <!-- Nominal Total Kasbon -->
        <div class="mb-3">
            <label for="total_nominal" class="form-label required fw-bold small text-muted">Nominal Total Kasbon (Rp)</label>
            <div class="input-group">
                <span class="input-group-text bg-light fw-bold text-muted">Rp</span>
                <input type="text" 
                       name="total_nominal" 
                       id="total_nominal" 
                       class="form-control input-rupiah enter-nav fw-bold" 
                       placeholder="1.000.000" 
                       value="<?= isset($debt['total_nominal']) ? number_format((int)$debt['total_nominal'], 0, ',', '.') : '' ?>" 
                       oninput="formatRupiahInput(this); syncDefaultDeduction(this)"
                       <?= $isEdit ? 'readonly' : 'required' ?>>
            </div>
            <?php if ($isEdit): ?>
                <small class="text-muted">Nominal total tidak dapat diubah setelah dicatat.</small>
            <?php endif; ?>
        </div>

        <!-- Nominal Potongan Default Per Periode -->
        <div class="mb-3">
            <label for="potongan_bawaan" class="form-label required fw-bold small text-muted">Potongan Default Per Periode (Rp)</label>
            <div class="input-group">
                <span class="input-group-text bg-light fw-bold text-muted">Rp</span>
                <input type="text" 
                       name="potongan_bawaan" 
                       id="potongan_bawaan" 
                       class="form-control input-rupiah enter-nav fw-bold" 
                       placeholder="100.000" 
                       value="<?= isset($debt['potongan_bawaan']) ? number_format((int)$debt['potongan_bawaan'], 0, ',', '.') : '' ?>" 
                       oninput="formatRupiahInput(this)"
                       required>
            </div>
            <small class="text-muted" style="font-size: 0.75rem;">Nominal cicilan standar yang akan otomatis dipotong saat Payroll Run.</small>
        </div>

        <!-- Catatan Tambahan -->
        <div class="mb-0">
            <label for="catatan" class="form-label fw-bold small text-muted">Catatan (Opsional)</label>
            <textarea name="catatan" id="catatan" class="form-control enter-nav" rows="3" placeholder="Catatan tambahan bila ada..."><?= e($debt['catatan'] ?? '') ?></textarea>
        </div>
    </div>

    <!-- Modal Footer -->
    <div class="modal-footer bg-light px-3 px-sm-4 py-3 border-top d-flex justify-content-end gap-2 flex-shrink-0">
        <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-danger px-4 rounded-pill fw-bold" id="btnSubmitDebt">
            <i class="bi bi-check-lg me-1"></i> <?= $isEdit ? 'Simpan Perubahan' : 'Simpan Kasbon' ?>
        </button>
    </div>
</form>
