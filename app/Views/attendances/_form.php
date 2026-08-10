<?php
/**
 * @var string $date
 * @var array $employees
 * @var array $attendances
 */
?>

<?php if (empty($employees)): ?>
    <div class="alert alert-info border-0 shadow-sm d-flex align-items-center">
        <i class="bi bi-info-circle-fill me-2 fs-5"></i>
        <div>Belum ada data karyawan aktif.</div>
    </div>
<?php else: ?>
    <!-- Info Banner telah dipindah ke _tabs.php -->

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
                <h5 class="card-title fw-bold text-dark mb-0">Daftar Kehadiran: <?= e(formatTanggal($date)) ?> (<?= e($typeLabel ?? 'Semua') ?>)</h5>
                <span class="badge bg-light text-secondary border fw-normal py-1 px-2" style="font-size: 0.75rem;">
                    <i class="bi bi-check-circle-fill text-success me-1"></i> Data sudah ada di sistem
                </span>
            </div>
        </div>
        <div class="card-body p-0">
            <form id="attendanceBulkForm_<?= e($employee_type ?? 'all') ?>" 
                  method="POST"
                  action="<?= url('/attendances/store') ?>"
                  hx-post="<?= url('/attendances/store') ?>" 
                  hx-target="body" 
                  hx-swap="beforeend">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="date" value="<?= e($date) ?>">
                <input type="hidden" name="employee_type" value="<?= e($employee_type ?? 'bulanan') ?>">
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-secondary">
                            <tr>
                                <th scope="col" class="ps-4 fw-semibold" style="width: 100px;">Hadir</th>
                                <th scope="col" class="fw-semibold" style="width: 90px;">Telat</th>
                                <?php if (($employee_type ?? 'bulanan') === 'bulanan'): ?>
                                    <th scope="col" class="fw-semibold text-center" style="width: 100px;">Ambil Uang</th>
                                <?php endif; ?>
                                <th scope="col" class="fw-semibold">Nama Karyawan</th>
                                <th scope="col" class="fw-semibold">Tipe Gaji</th>
                                <th scope="col" class="pe-4 fw-semibold">Keterangan Tidak Hadir</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            <?php foreach ($employees as $emp): ?>
                                <?php 
                                    $attData   = $attendances[$emp['id']] ?? null;
                                    $isPresent = $attData !== null ? (is_array($attData) ? (bool)$attData['hadir'] : (bool)$attData) : true;
                                    $isTelat   = $attData !== null && is_array($attData) ? (bool)($attData['telat'] ?? false) : false;
                                    $ambilUang = $attData !== null && is_array($attData) ? (bool)($attData['ambil_uang'] ?? false) : false;
                                    $notes     = $attData !== null && is_array($attData) ? ($attData['catatan'] ?? '') : '';
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="form-check form-switch form-switch-lg mb-0" style="padding-left: 2.5rem;">
                                            <input class="form-check-input attendance-switch enter-nav" type="checkbox" role="switch" 
                                                   name="hadir[]" value="<?= $emp['id'] ?>" 
                                                   id="present_<?= e($employee_type) ?>_<?= $emp['id'] ?>" 
                                                   data-emp-id="<?= e($employee_type) ?>_<?= $emp['id'] ?>"
                                                   <?= $isPresent ? 'checked' : '' ?>
                                                   style="width: 2.5em; height: 1.25em; cursor: pointer;">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="form-check mb-0 ps-4">
                                            <input class="form-check-input telat-check" type="checkbox"
                                                   name="telat[]" value="<?= $emp['id'] ?>"
                                                   id="telat_<?= e($employee_type) ?>_<?= $emp['id'] ?>"
                                                   data-emp-id="<?= e($employee_type) ?>_<?= $emp['id'] ?>"
                                                   <?= $isTelat ? 'checked' : '' ?>
                                                   <?= !$isPresent ? 'disabled' : '' ?>
                                                   style="width: 1.1em; height: 1.1em; cursor: pointer; accent-color: #fd7e14;">
                                            <label class="form-check-label text-warning-emphasis fw-semibold small" for="telat_<?= e($employee_type) ?>_<?= $emp['id'] ?>" style="cursor: pointer;">
                                                <i class="bi bi-clock-history"></i>
                                            </label>
                                        </div>
                                    </td>
                                    <?php if (($employee_type ?? 'bulanan') === 'bulanan'): ?>
                                        <td class="text-center">
                                            <?php if (($emp['uang_kehadiran_harian'] ?? 1) > 0): ?>
                                                <div class="form-check mb-0 d-flex justify-content-center">
                                                    <input class="form-check-input ambil-uang-check shadow-sm" type="checkbox"
                                                           name="ambil_uang[]" value="<?= $emp['id'] ?>"
                                                           id="ambil_uang_<?= e($employee_type) ?>_<?= $emp['id'] ?>"
                                                           <?= $ambilUang ? 'checked' : '' ?>
                                                           <?= !$isPresent ? 'disabled' : '' ?>
                                                           style="width: 1.4em; height: 1.4em; cursor: pointer; accent-color: #198754; border: 2px solid #ced4da;">
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted small">-</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                    <td>
                                        <label class="form-check-label fw-medium w-100 mb-0 d-flex align-items-center" for="present_<?= e($employee_type) ?>_<?= $emp['id'] ?>" style="cursor: pointer;">
                                            <?= e($emp['name']) ?>
                                            <?php if ($attData !== null): ?>
                                                <i class="bi bi-check-circle-fill text-success ms-2" style="font-size: 0.85rem;" data-bs-toggle="tooltip" title="Data sudah ada di sistem"></i>
                                            <?php endif; ?>
                                        </label>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <?= e(ucfirst($emp['tipe_gaji'])) ?>
                                        </span>
                                    </td>
                                    <td class="pe-4" style="min-width: 240px;">
                                        <input type="text" 
                                               class="form-control form-control-sm catatan-input enter-nav" 
                                               name="catatan[<?= $emp['id'] ?>]" 
                                               id="notes_<?= e($employee_type) ?>_<?= $emp['id'] ?>"
                                               value="<?= e($notes) ?>"
                                               placeholder="Alfa (otomatis jika kosong)"
                                               <?= $isPresent ? 'disabled style="opacity: 0.45; background-color: #f8fafc;"' : 'style="background-color: #ffffff;"' ?>>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="card-footer bg-light border-top-0 p-3 text-end rounded-bottom">
                    <!-- HTMX Indicator -->
                    <span class="htmx-indicator spinner-border spinner-border-sm text-primary me-3" role="status" aria-hidden="true"></span>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold rounded-pill shadow-sm">
                        <i class="bi bi-save me-2"></i>Simpan <?= e($typeLabel ?? 'Semua') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    if (typeof initAttendanceSwitches !== 'function') {
        function initAttendanceSwitches() {
            document.querySelectorAll('.attendance-switch').forEach(sw => {
                sw.onchange = function() {
                    const empId = this.getAttribute('data-emp-id');
                    const notesInput = document.getElementById('notes_' + empId);
                    const telatCheck = document.getElementById('telat_' + empId);
                    const ambilUangCheck = document.getElementById('ambil_uang_' + empId);
                    if (notesInput) {
                        if (this.checked) {
                            notesInput.disabled = true;
                            notesInput.style.opacity = '0.45';
                            notesInput.style.backgroundColor = '#f8fafc';
                            // Enable telat checkbox saat hadir
                            if (telatCheck) {
                                telatCheck.disabled = false;
                                telatCheck.style.opacity = '1';
                                telatCheck.style.cursor = 'pointer';
                            }
                            if (ambilUangCheck) {
                                ambilUangCheck.disabled = false;
                                ambilUangCheck.style.opacity = '1';
                                ambilUangCheck.style.cursor = 'pointer';
                            }
                        } else {
                            notesInput.disabled = false;
                            notesInput.style.opacity = '1';
                            notesInput.style.backgroundColor = '#ffffff';
                            notesInput.focus();
                            // Disable & uncheck telat checkbox saat tidak hadir
                            if (telatCheck) {
                                telatCheck.checked = false;
                                telatCheck.disabled = true;
                                telatCheck.style.opacity = '0.4';
                                telatCheck.style.cursor = 'not-allowed';
                            }
                            if (ambilUangCheck) {
                                ambilUangCheck.checked = false;
                                ambilUangCheck.disabled = true;
                                ambilUangCheck.style.opacity = '0.4';
                                ambilUangCheck.style.cursor = 'not-allowed';
                            }
                        }
                    }
                };
            });
        }
    }
    initAttendanceSwitches();
    </script>
<?php endif; ?>
