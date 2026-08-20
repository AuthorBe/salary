<?php
/**
 * @var string $title
 * @var string $pageTitle
 * @var string $pageKey
 */
?>

<div class="page-title-box d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
    <div class="page-title-left">
        <h4 class="mb-1 text-dark fw-bold d-flex align-items-center">
            <i class="bi bi-clipboard2-data-fill text-warning me-2 fs-4"></i> Gerbang Rekapitulasi
        </h4>
        <p class="text-muted mb-0 fs-7 ms-4 ps-1">Pusat kendali dan rekapitulasi data operasional kehadiran, produksi, lembur, dan rapor karyawan</p>
    </div>
</div>

<div class="row g-4 mb-5">
    <!-- Rekap Kehadiran -->
    <div class="col-sm-6 col-xl-3">
        <a href="<?= url('/rekap/attendance') ?>" class="text-decoration-none d-block h-100">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white hover-zoom transition-all p-2">
                <div class="card-body p-4 text-center d-flex flex-column align-items-center justify-content-between h-100">
                    <div>
                        <div class="rounded-circle d-flex align-items-center justify-content-center mb-3 mx-auto shadow-sm" style="width: 64px; height: 64px; background-color: rgba(59, 130, 246, 0.12);">
                            <i class="bi bi-calendar-check-fill fs-2" style="color: #3b82f6;"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-1">Kehadiran</h5>
                        <p class="text-muted small mb-0">Total absensi, hari hadir, izin, dan keterlambatan karyawan per periode</p>
                    </div>
                    <div class="mt-4 pt-2 border-top w-100 d-flex align-items-center justify-content-center text-primary fw-semibold small">
                        <span>Buka Rekap</span>
                        <i class="bi bi-arrow-right ms-1"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Rekap Produksi -->
    <div class="col-sm-6 col-xl-3">
        <a href="<?= url('/rekap/production') ?>" class="text-decoration-none d-block h-100">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white hover-zoom transition-all p-2">
                <div class="card-body p-4 text-center d-flex flex-column align-items-center justify-content-between h-100">
                    <div>
                        <div class="rounded-circle d-flex align-items-center justify-content-center mb-3 mx-auto shadow-sm" style="width: 64px; height: 64px; background-color: rgba(236, 72, 153, 0.12);">
                            <i class="bi bi-box-seam-fill fs-2" style="color: #ec4899;"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-1">Produksi</h5>
                        <p class="text-muted small mb-0">Hasil kerja borongan harian, kuantitas pcs, bal, dan rincian upah</p>
                    </div>
                    <div class="mt-4 pt-2 border-top w-100 d-flex align-items-center justify-content-center text-pink fw-semibold small" style="color: #ec4899;">
                        <span>Buka Rekap</span>
                        <i class="bi bi-arrow-right ms-1"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Rekap Lembur -->
    <div class="col-sm-6 col-xl-3">
        <a href="<?= url('/rekap/overtime') ?>" class="text-decoration-none d-block h-100">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white hover-zoom transition-all p-2">
                <div class="card-body p-4 text-center d-flex flex-column align-items-center justify-content-between h-100">
                    <div>
                        <div class="rounded-circle d-flex align-items-center justify-content-center mb-3 mx-auto shadow-sm" style="width: 64px; height: 64px; background-color: rgba(245, 158, 11, 0.12);">
                            <i class="bi bi-clock-history fs-2" style="color: #f59e0b;"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-1">Lembur</h5>
                        <p class="text-muted small mb-0">Akumulasi uang lembur karyawan bulanan (nominal) & borongan (kuantitas)</p>
                    </div>
                    <div class="mt-4 pt-2 border-top w-100 d-flex align-items-center justify-content-center text-warning fw-semibold small" style="color: #d97706 !important;">
                        <span>Buka Rekap</span>
                        <i class="bi bi-arrow-right ms-1"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Rekap Karyawan -->
    <div class="col-sm-6 col-xl-3">
        <a href="<?= url('/rekap/employee') ?>" class="text-decoration-none d-block h-100">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white hover-zoom transition-all p-2">
                <div class="card-body p-4 text-center d-flex flex-column align-items-center justify-content-between h-100">
                    <div>
                        <div class="rounded-circle d-flex align-items-center justify-content-center mb-3 mx-auto shadow-sm" style="width: 64px; height: 64px; background-color: rgba(16, 185, 129, 0.12);">
                            <i class="bi bi-person-vcard-fill fs-2" style="color: #10b981;"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-1">Detail Karyawan</h5>
                        <p class="text-muted small mb-0">Rapor individu: rekam jejak absensi, lembur, kasbon, dan tabungan</p>
                    </div>
                    <div class="mt-4 pt-2 border-top w-100 d-flex align-items-center justify-content-center text-success fw-semibold small">
                        <span>Buka Rapor</span>
                        <i class="bi bi-arrow-right ms-1"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<style>
.hover-zoom {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.hover-zoom:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08) !important;
}
</style>
