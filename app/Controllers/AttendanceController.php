<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Attendance;
use App\Models\Employee;

class AttendanceController
{
    public function index(): void
    {
        checkPermission('attendance');

        $date = $_GET['date'] ?? date('Y-m-d');

        $empModel = new Employee();
        $employees = array_filter($empModel->getAll(), fn($e) => (bool) $e['aktif']);

        $attModel = new Attendance();
        $attendances = $attModel->getByDate($date);

        view('attendances/index', [
            'title'       => 'Kehadiran Karyawan – Salary',
            'pageTitle'   => 'Kehadiran Karyawan',
            'pageKey'     => 'attendances',
            'date'        => $date,
            'employees'   => $employees,
            'attendances' => $attendances
        ]);
    }

    public function loadForm(): void
    {
        checkPermission('attendance');
        
        $date = $_GET['date'] ?? date('Y-m-d');

        $empModel = new Employee();
        // Ambil SEMUA karyawan yang aktif saja
        $employees = array_filter($empModel->getAll(), fn($e) => (bool) $e['aktif']);

        $attModel = new Attendance();
        $attendances = $attModel->getByDate($date);

        view('attendances/_form', [
            'date'        => $date,
            'employees'   => $employees,
            'attendances' => $attendances
        ], 'partials'); // Menggunakan layout kosong (karena diload via HTMX)
    }

    public function store(): void
    {
        checkPermission('attendance');
        validateCsrfToken();

        $date       = $_POST['date'] ?? date('Y-m-d');
        $presentIds = $_POST['hadir'] ?? []; // Array of employee_ids yang diceklis
        $notesMap   = $_POST['catatan'] ?? [];      // Array of catatan indexed by id_karyawan

        if (!$date) {
            if (isHtmx()) {
                echo renderAlert('danger', 'Tanggal tidak valid.');
            } else {
                redirect('/attendances');
            }
            return;
        }

        // Ambil semua karyawan aktif untuk update bulk
        $empModel = new Employee();
        $employees = array_filter($empModel->getAll(), fn($e) => (bool) $e['aktif']);
        $employeeIds = array_column($employees, 'id');

        $attModel = new Attendance();
        try {
            $db = getDB();
            $check = $db->prepare("SELECT COUNT(*) FROM absensi WHERE date = ?");
            $check->execute([$date]);
            $isUpdate = $check->fetchColumn() > 0;

            $attModel->saveBulk($date, $employeeIds, $presentIds, $notesMap);
            
            $jmlHadir = count($presentIds);
            $jmlTidakHadir = count($employeeIds) - $jmlHadir;
            
            $titleText = $isUpdate ? 'Diperbarui!' : 'Berhasil!';
            $descText = $isUpdate ? 'Data kehadiran tanggal <strong class="text-dark">%s</strong> telah diperbarui.' : 'Data kehadiran tanggal <strong class="text-dark">%s</strong> telah tersimpan.';
            $sessionText = $isUpdate ? 'Data kehadiran tanggal %s berhasil diperbarui.' : 'Data kehadiran tanggal %s berhasil disimpan.';
            
            $msg = sprintf(
                '<div class="position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center" style="z-index: 1055; background: rgba(0,0,0,0.5);" id="attendance-success-overlay" onclick="this.remove()">' .
                    '<div class="card border-0 shadow-lg" style="max-width: 400px; width: 90%%; animation: popIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);" onclick="event.stopPropagation()">' .
                        '<div class="card-body p-4 text-center">' .
                            '<div class="mb-3 text-success">' .
                                '<i class="bi bi-check-circle-fill" style="font-size: 4rem;"></i>' .
                            '</div>' .
                            '<h4 class="fw-bold mb-2">%s</h4>' .
                            '<p class="text-muted mb-4">' . $descText . '</p>' .
                            '<div class="d-flex justify-content-center gap-2 mb-4">' .
                                '<span class="badge bg-success-subtle text-success rounded-pill px-3 py-2 fs-6 border border-success-subtle">%d Hadir</span>' .
                                '<span class="badge bg-danger-subtle text-danger rounded-pill px-3 py-2 fs-6 border border-danger-subtle">%d Tidak Hadir</span>' .
                            '</div>' .
                            '<button type="button" class="btn btn-primary px-5 rounded-pill shadow-sm" onclick="document.getElementById(\'attendance-success-overlay\').remove()">Oke, Tutup</button>' .
                        '</div>' .
                    '</div>' .
                    '<style>@keyframes popIn { 0%% { opacity: 0; transform: scale(0.8); } 100%% { opacity: 1; transform: scale(1); } }</style>' .
                '</div>',
                $titleText,
                e(formatTanggal($date)),
                $jmlHadir,
                $jmlTidakHadir
            );
            
            if (isHtmx()) {
                echo $msg;
            } else {
                $_SESSION['flash_success'] = sprintf($sessionText, e(formatTanggal($date))) . ' (Hadir: ' . $jmlHadir . ', Tidak Hadir: ' . $jmlTidakHadir . ')';
                redirect('/attendances?date=' . urlencode($date));
            }
        } catch (\Exception $e) {
            $msg = renderAlert('danger', 'Gagal menyimpan kehadiran: ' . e($e->getMessage()), 5000);
            if (isHtmx()) {
                echo $msg;
            } else {
                $_SESSION['flash_error'] = 'Gagal menyimpan kehadiran: ' . e($e->getMessage());
                redirect('/attendances?date=' . urlencode($date));
            }
        }
    }
}
