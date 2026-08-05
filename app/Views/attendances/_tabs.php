<?php
/**
 * @var string $date
 * @var array $employeesBulanan
 * @var array $employeesBorongan
 * @var array $attendances
 */
?>

<!-- Info Banner Petunjuk Keterangan Tidak Hadir -->
<div class="alert alert-info border-0 shadow-sm d-flex align-items-start gap-2 mb-3">
    <i class="bi bi-info-circle-fill text-info fs-5 mt-1"></i>
    <div>
        <strong class="d-block mb-1">Petunjuk Keterangan Tidak Hadir:</strong>
        Untuk karyawan yang <strong>Tidak Hadir</strong> (sakelar nonaktif), Anda dapat mengisi kolom <strong>Keterangan Tidak Hadir</strong> (misal: <em>Sakit, Izin, Cuti</em>). 
        <span class="text-danger fw-semibold">Jika kolom keterangan dibiarkan kosong, sistem akan otomatis mencatatnya sebagai <u>Alfa</u>.</span>
    </div>
</div>

<style>
    /* Paksa tab yang tidak aktif agar background-nya transparan (menghilangkan warna hitam) */
    #attendanceTabs .nav-link:not(.active) {
        background-color: transparent !important;
        color: #495057 !important; /* warna teks abu gelap */
    }
    #attendanceTabs .nav-link:hover:not(.active) {
        background-color: rgba(0,0,0,0.05) !important;
    }
</style>

<ul class="nav nav-pills mb-4" id="attendanceTabs" role="tablist" style="gap: 0.5rem;">
    <li role="presentation">
        <a class="nav-link active rounded-pill px-4 fw-semibold cursor-pointer" id="bulanan-tab" data-bs-toggle="tab" data-bs-target="#bulanan" role="tab" aria-controls="bulanan" aria-selected="true" style="cursor: pointer;">
            <i class="bi bi-calendar-month me-2"></i>Karyawan Bulanan
        </a>
    </li>
    <li role="presentation">
        <a class="nav-link rounded-pill px-4 fw-semibold cursor-pointer" id="borongan-tab" data-bs-toggle="tab" data-bs-target="#borongan" role="tab" aria-controls="borongan" aria-selected="false" style="cursor: pointer;">
            <i class="bi bi-briefcase me-2"></i>Karyawan Borongan
        </a>
    </li>
</ul>

<div class="tab-content" id="attendanceTabsContent">
    <div class="tab-pane fade show active" id="bulanan" role="tabpanel" aria-labelledby="bulanan-tab">
        <?php view('attendances/_form', [
            'date'          => $date,
            'employee_type' => 'bulanan',
            'typeLabel'     => 'Bulanan',
            'employees'     => $employeesBulanan,
            'attendances'   => $attendances
        ], 'partials'); ?>
    </div>
    <div class="tab-pane fade" id="borongan" role="tabpanel" aria-labelledby="borongan-tab">
        <?php view('attendances/_form', [
            'date'          => $date,
            'employee_type' => 'borongan',
            'typeLabel'     => 'Borongan',
            'employees'     => $employeesBorongan,
            'attendances'   => $attendances
        ], 'partials'); ?>
    </div>
</div>

<script>
    // Inisialisasi ulang tab bootstrap jika load via htmx
    if (typeof bootstrap !== 'undefined' && document.querySelectorAll('#attendanceTabs button').length > 0) {
        document.querySelectorAll('#attendanceTabs button').forEach(function(triggerEl) {
            if (!bootstrap.Tab.getInstance(triggerEl)) {
                new bootstrap.Tab(triggerEl);
            }
        });
    }
</script>
