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
    <!-- Info Banner Petunjuk Keterangan Tidak Hadir -->
    <div class="alert alert-info border-0 shadow-sm d-flex align-items-start gap-2 mb-3">
        <i class="bi bi-info-circle-fill text-info fs-5 mt-1"></i>
        <div>
            <strong class="d-block mb-1">Petunjuk Keterangan Tidak Hadir:</strong>
            Untuk karyawan yang <strong>Tidak Hadir</strong> (sakelar nonaktif), Anda dapat mengisi kolom <strong>Keterangan Tidak Hadir</strong> (misal: <em>Sakit, Izin, Cuti</em>). 
            <span class="text-danger fw-semibold">Jika kolom keterangan dibiarkan kosong, sistem akan otomatis mencatatnya sebagai <u>Alfa</u>.</span>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
            <h5 class="card-title fw-bold text-dark mb-0">Daftar Kehadiran: <?= e(formatTanggal($date)) ?></h5>
        </div>
        <div class="card-body p-0">
            <form id="attendanceBulkForm" 
                  method="POST"
                  action="<?= url('/attendances/store') ?>"
                  hx-post="<?= url('/attendances/store') ?>" 
                  hx-target="#attendance-alert" 
                  hx-swap="innerHTML">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="date" value="<?= e($date) ?>">
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-secondary">
                            <tr>
                                <th scope="col" class="ps-4 fw-semibold" style="width: 100px;">Hadir</th>
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
                                    $notes     = $attData !== null && is_array($attData) ? ($attData['catatan'] ?? '') : '';
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="form-check form-switch form-switch-lg mb-0" style="padding-left: 2.5rem;">
                                            <input class="form-check-input attendance-switch enter-nav" type="checkbox" role="switch" 
                                                   name="hadir[]" value="<?= $emp['id'] ?>" 
                                                   id="present_<?= $emp['id'] ?>" 
                                                   data-emp-id="<?= $emp['id'] ?>"
                                                   <?= $isPresent ? 'checked' : '' ?>
                                                   style="width: 2.5em; height: 1.25em; cursor: pointer;">
                                        </div>
                                    </td>
                                    <td>
                                        <label class="form-check-label fw-medium w-100 mb-0" for="present_<?= $emp['id'] ?>" style="cursor: pointer;">
                                            <?= e($emp['name']) ?>
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
                                               id="notes_<?= $emp['id'] ?>"
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
                        <i class="bi bi-save me-2"></i>Simpan Semua
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
                    if (notesInput) {
                        if (this.checked) {
                            notesInput.disabled = true;
                            notesInput.style.opacity = '0.45';
                            notesInput.style.backgroundColor = '#f8fafc';
                        } else {
                            notesInput.disabled = false;
                            notesInput.style.opacity = '1';
                            notesInput.style.backgroundColor = '#ffffff';
                            notesInput.focus();
                        }
                    }
                };
            });
        }
    }
    initAttendanceSwitches();
    </script>
<?php endif; ?>
