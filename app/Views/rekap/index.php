

<div class="mb-4">
    <h2 class="h3 fw-bold text-dark mb-2">Gerbang Rekapitulasi</h2>
    <p class="text-muted">Pilih laporan yang ingin Anda lihat rinciannya.</p>
</div>

<div class="row g-4 mb-5">
    <!-- Rekap Kehadiran -->
    <div class="col-md-6 col-lg-3">
        <a href="<?= url('/rekap/attendance') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white hover-zoom transition-all">
                <div class="card-body p-4 text-center d-flex flex-column align-items-center">
                    <div class="rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px; background-color: rgba(59, 130, 246, 0.1);">
                        <i class="bi bi-calendar-check-fill fs-2" style="color: #3b82f6;"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Kehadiran</h5>
                    <p class="text-muted small mb-0">Total absensi karyawan</p>
                </div>
            </div>
        </a>
    </div>

    <!-- Rekap Produksi -->
    <div class="col-md-6 col-lg-3">
        <a href="<?= url('/rekap/production') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white hover-zoom transition-all">
                <div class="card-body p-4 text-center d-flex flex-column align-items-center">
                    <div class="rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px; background-color: rgba(236, 72, 153, 0.1);">
                        <i class="bi bi-box-seam-fill fs-2" style="color: #ec4899;"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Produksi</h5>
                    <p class="text-muted small mb-0">Hasil kerja borongan</p>
                </div>
            </div>
        </a>
    </div>

    <!-- Rekap Lembur -->
    <div class="col-md-6 col-lg-3">
        <a href="<?= url('/rekap/overtime') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white hover-zoom transition-all">
                <div class="card-body p-4 text-center d-flex flex-column align-items-center">
                    <div class="rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px; background-color: rgba(245, 158, 11, 0.1);">
                        <i class="bi bi-clock-history fs-2" style="color: #f59e0b;"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Lembur</h5>
                    <p class="text-muted small mb-0">Ekstra waktu & produksi</p>
                </div>
            </div>
        </a>
    </div>

    <!-- Rekap Karyawan -->
    <div class="col-md-6 col-lg-3">
        <a href="<?= url('/rekap/employee') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white hover-zoom transition-all">
                <div class="card-body p-4 text-center d-flex flex-column align-items-center">
                    <div class="rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px; background-color: rgba(16, 185, 129, 0.1);">
                        <i class="bi bi-person-vcard-fill fs-2" style="color: #10b981;"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Detail Karyawan</h5>
                    <p class="text-muted small mb-0">Rapor & profil individu</p>
                </div>
            </div>
        </a>
    </div>
</div>

<style>
.hover-zoom:hover {
    transform: translateY(-5px);
}
.transition-all {
    transition: all 0.2s ease-in-out;
}
</style>


