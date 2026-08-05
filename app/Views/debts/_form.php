<?php
/**
 * @var array|null $debt
 * @var array $employees
 */
$isEdit = !empty($debt);
?>
<form action="<?= url('/debts/store') ?>" method="POST">
    <?= csrfField() ?>
    <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= e($debt['id']) ?>">
    <?php endif; ?>

    <div class="modal-body p-4">
        <!-- Pilih Karyawan -->
        <div class="mb-3">
            <label for="id_karyawan" class="form-label required">Karyawan</label>
            <?php if ($isEdit): ?>
                <input type="text" class="form-control bg-light" value="<?= e($debt['employee_name']) ?>" readonly>
                <input type="hidden" name="id_karyawan" value="<?= e($debt['id_karyawan']) ?>">
            <?php else: ?>
                <select name="id_karyawan" id="id_karyawan" class="form-select searchable-select enter-nav" required>
                    <option value="">-- Pilih Karyawan --</option>
                    <?= renderEmployeeOptions($activeEmployees) ?>
                </select>
            <?php endif; ?>
        </div>

        <!-- Deskripsi Kasbon -->
        <div class="mb-3">
            <label for="keterangan" class="form-label required">Deskripsi / Peruntukan Kasbon</label>
            <input type="text" 
                   name="keterangan" 
                   id="keterangan" 
                   class="form-control enter-nav" 
                   placeholder="Contoh: Kasbon Motor, Pinjaman Darurat" 
                   value="<?= e($debt['keterangan'] ?? '') ?>" 
                   required>
        </div>

        <!-- Nominal Total Kasbon -->
        <div class="mb-3">
            <label for="total_nominal" class="form-label required">Nominal Total Kasbon (Rp)</label>
            <div class="input-group">
                <span class="input-group-text bg-light fw-bold text-muted">Rp</span>
                <input type="text" 
                       name="total_nominal" 
                       id="total_nominal" 
                       class="form-control input-rupiah enter-nav" 
                       placeholder="1.000.000" 
                       value="<?= isset($debt['total_nominal']) ? number_format((int)$debt['total_nominal'], 0, ',', '.') : '' ?>" 
                       <?= $isEdit ? 'readonly' : 'required' ?>>
            </div>
            <?php if ($isEdit): ?>
                <small class="text-muted">Nominal total tidak dapat diubah setelah dicatat.</small>
            <?php endif; ?>
        </div>

        <!-- Nominal Potongan Default Per Periode -->
        <div class="mb-3">
            <label for="potongan_bawaan" class="form-label required">Potongan Default Per Periode (Rp)</label>
            <div class="input-group">
                <span class="input-group-text bg-light fw-bold text-muted">Rp</span>
                <input type="text" 
                       name="potongan_bawaan" 
                       id="potongan_bawaan" 
                       class="form-control input-rupiah enter-nav" 
                       placeholder="100.000" 
                       value="<?= isset($debt['potongan_bawaan']) ? number_format((int)$debt['potongan_bawaan'], 0, ',', '.') : '' ?>" 
                       required>
            </div>
            <small class="text-muted">Nominal cicilan standar yang akan otomatis dipotong saat Payroll Run.</small>
        </div>

        <!-- Catatan Tambahan -->
        <div class="mb-0">
            <label for="catatan" class="form-label">Catatan (Opsional)</label>
            <textarea name="catatan" id="catatan" class="form-control enter-nav" rows="2" placeholder="Catatan Tambahan..."><?= e($debt['catatan'] ?? '') ?></textarea>
        </div>
    </div>

    <div class="modal-footer bg-light px-4 py-3 border-top d-flex justify-content-end gap-2">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-check-lg me-1"></i> Simpan Kasbon
        </button>
    </div>
</form>
