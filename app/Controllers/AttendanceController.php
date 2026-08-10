<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\PenarikanGaji;

class AttendanceController
{
    public function index(): void
    {
        checkPermission('attendance');

        $date = $_GET['date'] ?? date('Y-m-d');

        $empModel = new Employee();
        $allActive = array_filter($empModel->getAll(), fn($e) => (bool) $e['aktif']);
        
        $employeesBulanan = array_filter($allActive, fn($e) => strtolower($e['tipe_gaji']) === 'bulanan');
        $employeesBorongan = array_filter($allActive, fn($e) => strtolower($e['tipe_gaji']) !== 'bulanan');

        $attModel = new Attendance();
        $attendances = $attModel->getByDate($date);

        view('attendances/index', [
            'title'             => 'Kehadiran Karyawan – Salary',
            'pageTitle'         => 'Kehadiran Karyawan',
            'pageKey'           => 'attendances',
            'date'              => $date,
            'employeesBulanan'  => $employeesBulanan,
            'employeesBorongan' => $employeesBorongan,
            'attendances'       => $attendances
        ]);
    }

    public function loadForm(): void
    {
        checkPermission('attendance');
        
        $date = $_GET['date'] ?? date('Y-m-d');

        $empModel = new Employee();
        // Ambil SEMUA karyawan yang aktif saja
        $allActive = array_filter($empModel->getAll(), fn($e) => (bool) $e['aktif']);
        $employeesBulanan = array_filter($allActive, fn($e) => strtolower($e['tipe_gaji']) === 'bulanan');
        $employeesBorongan = array_filter($allActive, fn($e) => strtolower($e['tipe_gaji']) !== 'bulanan');

        $attModel = new Attendance();
        $attendances = $attModel->getByDate($date);

        view('attendances/_tabs', [
            'date'              => $date,
            'employeesBulanan'  => $employeesBulanan,
            'employeesBorongan' => $employeesBorongan,
            'attendances'       => $attendances
        ], 'partials'); // Menggunakan layout kosong (karena diload via HTMX)
    }

    public function store(): void
    {
        checkPermission('attendance');
        validateCsrfToken();

        $date       = $_POST['date'] ?? date('Y-m-d');
        $type       = $_POST['employee_type'] ?? 'bulanan'; // bulanan atau borongan
        $presentIds = $_POST['hadir'] ?? []; // Array of employee_ids yang diceklis
        $notesMap   = $_POST['catatan'] ?? [];      // Array of catatan indexed by id_karyawan
        $telatIds   = $_POST['telat'] ?? [];        // Array of employee_ids yang telat
        $ambilUangIds = $_POST['ambil_uang'] ?? []; // Array of employee_ids yang ambil uang

        if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            if (isHtmx()) {
                header('HX-Reswap: beforeend');
                header('HX-Retarget: body');
                echo renderAlert('danger', 'Tanggal tidak valid.');
            } else {
                redirect('/attendances');
            }
            return;
        }

        // Ambil semua karyawan aktif sesuai tipe untuk update bulk
        $empModel = new Employee();
        $allActive = array_filter($empModel->getAll(), fn($e) => (bool) $e['aktif']);
        
        if ($type === 'bulanan') {
            $employees = array_filter($allActive, fn($e) => strtolower($e['tipe_gaji']) === 'bulanan');
        } else {
            $employees = array_filter($allActive, fn($e) => strtolower($e['tipe_gaji']) !== 'bulanan');
        }
        
        $employeeIds = array_column($employees, 'id');

        $attModel = new Attendance();
        try {
            $db = getDB();
            $db->beginTransaction();
            
            $isUpdate = false;
            if (count($employeeIds) > 0) {
                $placeholders = implode(',', array_fill(0, count($employeeIds), '?'));
                $checkUpdate = $db->prepare("SELECT COUNT(*) FROM absensi WHERE date = ? AND id_karyawan IN ($placeholders)");
                $params = array_merge([$date], $employeeIds);
                $checkUpdate->execute($params);
                $isUpdate = $checkUpdate->fetchColumn() > 0;
            }

            $attModel->saveBulk($date, $employeeIds, $presentIds, $notesMap, $telatIds, $ambilUangIds);
            
            // SINKRONISASI DENGAN PENARIKAN GAJI (KASBON HARIAN)
            if ($type === 'bulanan') {
                $penarikanModel = new PenarikanGaji();
                $keterangan = 'Penarikan Harian';
                
                foreach ($employees as $emp) {
                    $empId = (int)$emp['id'];
                    $isPresent = in_array($empId, $presentIds);
                    $isAmbilUang = $isPresent && in_array($empId, $ambilUangIds);
                    
                    if ($isAmbilUang) {
                        $uangHadir = (float)($emp['uang_kehadiran_harian'] ?? 0);
                        if ($uangHadir > 0) {
                            $check = $db->prepare("SELECT id, nominal FROM penarikan_gaji WHERE id_karyawan = ? AND tanggal = ? AND keterangan = ? AND id_penggajian IS NULL");
                            $check->execute([$empId, $date, $keterangan]);
                            $existing = $check->fetch();
                            
                            if (!$existing) {
                                $penarikanModel->store([
                                    'id_karyawan' => $empId,
                                    'tanggal' => $date,
                                    'nominal' => $uangHadir,
                                    'keterangan' => $keterangan
                                ]);
                            } else if ((float)$existing['nominal'] !== $uangHadir) {
                                // Jika nominal di master data berubah, update nominal kasbonnya
                                $db->prepare("UPDATE penarikan_gaji SET nominal = ? WHERE id = ?")->execute([$uangHadir, $existing['id']]);
                            }
                        } else {
                            // Jika uang hadir di-nol-kan di master data tapi checkbox masih ada/tersubmit
                            $stmtDel = $db->prepare("DELETE FROM penarikan_gaji WHERE id_karyawan = ? AND tanggal = ? AND keterangan = ? AND id_penggajian IS NULL");
                            $stmtDel->execute([$empId, $date, $keterangan]);
                        }
                    } else {
                        // Jika tidak ambil uang (atau tidak hadir), hapus jika ada dan belum di-payroll
                        $stmtDel = $db->prepare("DELETE FROM penarikan_gaji WHERE id_karyawan = ? AND tanggal = ? AND keterangan = ? AND id_penggajian IS NULL");
                        $stmtDel->execute([$empId, $date, $keterangan]);
                    }
                }
            }
            
            $db->commit();
            
            $jmlHadir = count($presentIds);
            $jmlTidakHadir = count($employeeIds) - $jmlHadir;
            $jmlTelat = count(array_intersect($presentIds, $telatIds));
            $jmlAmbilUang = count(array_intersect($presentIds, $ambilUangIds));
            
            $typeLabel = ($type === 'bulanan') ? 'Karyawan Bulanan' : 'Karyawan Borongan';
            $titleText = $isUpdate ? "Absensi $typeLabel Diperbarui!" : "Absensi $typeLabel Disimpan!";
            $descText = $isUpdate ? 'Data kehadiran <strong>' . $typeLabel . '</strong> tanggal <strong class="text-dark">%s</strong> telah berhasil diperbarui.' : 'Data kehadiran <strong>' . $typeLabel . '</strong> tanggal <strong class="text-dark">%s</strong> telah berhasil tersimpan.';
            
            $sessionText = $isUpdate ? "Data kehadiran $typeLabel tanggal %s berhasil diperbarui." : "Data kehadiran $typeLabel tanggal %s berhasil disimpan.";
            if ($type === 'bulanan') {
                $sessionText .= ' (Hadir: ' . $jmlHadir . ', Telat: ' . $jmlTelat . ', Ambil Uang: ' . $jmlAmbilUang . ', Tidak Hadir: ' . $jmlTidakHadir . ')';
            } else {
                $sessionText .= ' (Hadir: ' . $jmlHadir . ', Telat: ' . $jmlTelat . ', Tidak Hadir: ' . $jmlTidakHadir . ')';
            }
            
            // Build badges HTML
            $badgesHtml = '<span class="badge bg-success-subtle text-success rounded-pill px-3 py-2 fs-6 border border-success-subtle">' . $jmlHadir . ' Hadir</span>' .
                          '<span class="badge bg-warning-subtle text-warning-emphasis rounded-pill px-3 py-2 fs-6 border border-warning-subtle">' . $jmlTelat . ' Telat</span>';
            if ($type === 'bulanan') {
                $badgesHtml .= '<span class="badge bg-info-subtle text-info-emphasis rounded-pill px-3 py-2 fs-6 border border-info-subtle">' . $jmlAmbilUang . ' Ambil Uang</span>';
            }
            $badgesHtml .= '<span class="badge bg-danger-subtle text-danger rounded-pill px-3 py-2 fs-6 border border-danger-subtle">' . $jmlTidakHadir . ' Tidak Hadir</span>';
            
            $msg = sprintf(
                '<div class="position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center" style="z-index: 1055; background: rgba(0,0,0,0.5);" id="attendance-success-overlay" onclick="this.remove()">' .
                    '<div class="card border-0 shadow-lg" style="max-width: 420px; width: 90%%; animation: popIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);" onclick="event.stopPropagation()">' .
                        '<div class="card-body p-4 text-center">' .
                            '<div class="mb-3 text-success">' .
                                '<i class="bi bi-check-circle-fill" style="font-size: 4rem;"></i>' .
                            '</div>' .
                            '<h4 class="fw-bold mb-2">%s</h4>' .
                            '<p class="text-muted mb-4">' . $descText . '</p>' .
                            '<div class="d-flex justify-content-center gap-2 mb-4 flex-wrap">' .
                                $badgesHtml .
                            '</div>' .
                            '<button type="button" class="btn btn-primary px-5 rounded-pill shadow-sm" onclick="document.getElementById(\'attendance-success-overlay\').remove()">Oke, Tutup</button>' .
                        '</div>' .
                    '</div>' .
                    '<style>@keyframes popIn { 0%% { opacity: 0; transform: scale(0.8); } 100%% { opacity: 1; transform: scale(1); } }</style>' .
                '</div>',
                $titleText,
                e(formatTanggal($date))
            );
            
            if (isHtmx()) {
                // Trigger HTMX untuk reload form container agar data terbaru tampil sesuai tanggal filter
                header('HX-Trigger: attendanceSaved');
                echo $msg;
            } else {
                $_SESSION['flash_title'] = $titleText;
                $_SESSION['flash_success'] = sprintf($sessionText, e(formatTanggal($date)));
                redirect('/attendances?date=' . urlencode($date));
            }
        } catch (\Exception $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            $errMsg = sprintf(
                '<div class="position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center" style="z-index: 1055; background: rgba(0,0,0,0.5);" id="attendance-error-overlay" onclick="this.remove()">' .
                    '<div class="card border-0 shadow-lg" style="max-width: 420px; width: 90%%; animation: popIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);" onclick="event.stopPropagation()">' .
                        '<div class="card-body p-4 text-center">' .
                            '<div class="mb-3 text-danger">' .
                                '<i class="bi bi-x-circle-fill" style="font-size: 4rem;"></i>' .
                            '</div>' .
                            '<h4 class="fw-bold mb-2 text-danger">Gagal Menyimpan!</h4>' .
                            '<p class="text-muted mb-4">%s</p>' .
                            '<button type="button" class="btn btn-danger px-5 rounded-pill shadow-sm" onclick="document.getElementById(\'attendance-error-overlay\').remove()">Tutup</button>' .
                        '</div>' .
                    '</div>' .
                    '<style>@keyframes popIn { 0%% { opacity: 0; transform: scale(0.8); } 100%% { opacity: 1; transform: scale(1); } }</style>' .
                '</div>',
                e($e->getMessage())
            );
            if (isHtmx()) {
                echo $errMsg;
            } else {
                $_SESSION['flash_error'] = 'Gagal menyimpan kehadiran: ' . e($e->getMessage());
                redirect('/attendances?date=' . urlencode($date));
            }
        }
    }
}
