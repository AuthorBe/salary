<?php
namespace App\Controllers;

use App\Models\PayrollRun;
use App\Models\PayrollItem;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Production;
use App\Models\Debt;
use App\Models\PenarikanGaji;

class PayrollController
{
    public function index(): void
    {
        checkPermission('payroll');
        $prModel = new PayrollRun();
        $payrolls = $prModel->getAll();

        $pageGuide = '
            <p>Halaman <strong>Data Payroll</strong> adalah pusat kendali untuk melakukan perhitungan (generate) gaji semua karyawan.</p>
            <ul class="mb-0 text-muted">
                <li class="mb-2">Klik tombol <strong>Generate Payroll Baru</strong> untuk memulai perhitungan gaji otomatis berdasarkan data kehadiran, produksi borongan, tunjangan, dan kasbon.</li>
                <li class="mb-2">Payroll yang baru digenerate akan berstatus <span class="badge bg-warning-subtle text-warning">Draft</span>. Artinya data tersebut masih bisa di-review dan di-edit (seperti menambah Denda/Potongan Lain atau Tunjangan Ekstra).</li>
                <li>Setelah direview dan dirasa akurat, Anda harus mengklik tombol <span class="badge bg-success">Approve</span>. Payroll yang sudah disetujui akan terkunci permanen, menjadi riwayat resmi, dan barulah bisa dicetak slip gajinya.</li>
            </ul>
        ';

        view('payroll/index', [
            'title'     => 'Data Payroll – Salary',
            'pageTitle' => 'Data Payroll',
            'pageKey'   => 'payroll',
            'pageGuide' => $pageGuide,
            'payrolls'  => $payrolls
        ]);
    }

    public function create(): void
    {
        checkPermission('payroll');
        
        $type = $_GET['type'] ?? 'weekly';
        
        $settingModel = new \App\Models\AppSetting();
        $weekStartDay = (int) $settingModel->get('week_start_day', '1');
        $weekEndDay = (int) $settingModel->get('week_end_day', '0');
        $map = [0 => 'sunday', 1 => 'monday', 2 => 'tuesday', 3 => 'wednesday', 4 => 'thursday', 5 => 'friday', 6 => 'saturday'];
        
        $endDayName = $map[$weekEndDay];
        $startDayName = $map[$weekStartDay];
        
        // Default Mingguan
        $endWeekly = new \DateTime("last " . $endDayName);
        $startWeekly = clone $endWeekly;
        if ($weekStartDay === $weekEndDay) {
            $startWeekly->modify('-6 days');
        } else {
            $startWeekly->modify("last " . $startDayName);
        }
        
        // Default Bulanan (Bulan ini)
        $startMonthly = new \DateTime('first day of this month');
        $endMonthly = new \DateTime('last day of this month');
        
        $period_start = $_GET['periode_awal'] ?? ($type === 'monthly' ? $startMonthly->format('Y-m-d') : $startWeekly->format('Y-m-d'));
        $period_end = $_GET['periode_akhir'] ?? ($type === 'monthly' ? $endMonthly->format('Y-m-d') : $endWeekly->format('Y-m-d'));
        
        view('payroll/create', [
            'title' => 'Generate Payroll Baru',
            'type' => $type,
            'periode_awal' => $period_start,
            'periode_akhir' => $period_end,
            'default_weekly_start' => $startWeekly->format('Y-m-d'),
            'default_weekly_end' => $endWeekly->format('Y-m-d'),
            'default_monthly_start' => $startMonthly->format('Y-m-d'),
            'default_monthly_end' => $endMonthly->format('Y-m-d'),
        ]);
    }

    public function store(): void
    {
        checkPermission('payroll');
        validateCsrfToken();

        $type = $_POST['type'] ?? 'weekly';
        $period_start = $_POST['periode_awal'] ?? '';
        $period_end = $_POST['periode_akhir'] ?? '';
        
        // Deteksi apakah ini adalah minggu terakhir bulan ini
        // (jika ditambah 7 hari masuk ke bulan berikutnya)
        $isLastWeekOfMonth = false;
        if ($type === 'weekly' && $period_end) {
            try {
                $endDateObj = new \DateTime($period_end);
                $nextWeekObj = clone $endDateObj;
                $nextWeekObj->modify('+7 days');
                if ($endDateObj->format('m') !== $nextWeekObj->format('m')) {
                    $isLastWeekOfMonth = true;
                }
            } catch (\Exception $e) {
                // Abaikan jika format tanggal invalid
            }
        }

        if (!$period_start || !$period_end || $period_start > $period_end) {
            $_SESSION['flash_error'] = 'Rentang tanggal tidak valid.';
            redirect('/payroll/create');
            return;
        }

        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            // Fallback nama jika tidak diisi
            $name = 'Gaji ' . ucfirst($type) . ' ' . date('d M Y', strtotime($period_start));
        } else {
            $name = ucwords($name);
        }

        $db = getDB();

        // Check if there's already a draft for this type
        $draftExists = $db->prepare("SELECT id FROM penggajian WHERE type = ? AND status = 'draft'");
        $draftExists->execute([$type]);
        if ($draft = $draftExists->fetch()) {
            $_SESSION['flash_error'] = 'Masih ada Draft Payroll ' . ucfirst($type) . ' yang belum diselesaikan. Harap Approve atau Hapus draft tersebut terlebih dahulu.';
            redirect('/payroll');
            return;
        }

        // Check if there's any approved payroll that overlaps with the requested period
        $overlapExists = $db->prepare("
            SELECT id, periode_awal, periode_akhir 
            FROM penggajian 
            WHERE type = ? 
              AND status = 'approved'
              AND periode_awal <= ? 
              AND periode_akhir >= ?
        ");
        $overlapExists->execute([$type, $period_end, $period_start]);
        if ($overlap = $overlapExists->fetch()) {
            $_SESSION['flash_error'] = 'Gagal! Terdapat bentrok tanggal dengan payroll ' . ucfirst($type) . ' yang sudah disetujui (Periode: ' . date('d M Y', strtotime($overlap['periode_awal'])) . ' s/d ' . date('d M Y', strtotime($overlap['periode_akhir'])) . '). Pastikan rentang tanggal yang Anda buat tidak tumpang tindih dengan data sebelumnya.';
            redirect('/payroll/create?type=' . $type);
            return;
        }

        try {
            $db->beginTransaction();

            // 1. Create payroll run
            $stmt = $db->prepare("INSERT INTO penggajian (periode_awal, periode_akhir, type, status, name) VALUES (?, ?, ?, 'draft', ?)");
            $stmt->execute([$period_start, $period_end, $type, $name]);
            $runId = (int)$db->lastInsertId();

            // 2. Fetch target employees
            $targetTypes = $type === 'weekly' ? "'borongan'" : "'bulanan'";
            
            // Get all active employees of target types
            $empStmt = $db->query("
                SELECT e.*, m.gaji_pokok 
                FROM karyawan e
                LEFT JOIN pengaturan_gaji_bulanan m ON e.id = m.id_karyawan
                WHERE e.aktif = 1 AND e.tipe_gaji IN ($targetTypes)
            ");
            $employees = $empStmt->fetchAll();

            $debtModel = new Debt();
            $penarikanModel = new PenarikanGaji();
            
            $itemCount = 0;

            foreach ($employees as $emp) {
                $baseSalary = (float)($emp['gaji_pokok'] ?? 0);
                
                // Get Attendances (unlocked)
                $attStmt = $db->prepare("
                    SELECT COUNT(*) as days, SUM(lembur_nominal) as lembur_bulanan
                    FROM absensi 
                    WHERE id_karyawan = ? AND date BETWEEN ? AND ? 
                      AND hadir = 1 AND id_penggajian IS NULL
                ");
                $attStmt->execute([$emp['id'], $period_start, $period_end]);
                $attData = $attStmt->fetch();
                $attendanceDays = (int)$attData['days'];
                
                $attendancePayTotal = $attendanceDays * (float)$emp['uang_kehadiran_harian'];

                // Get Productions (unlocked)
                $prodPayTotal = 0.00;
                $overtimePayTotal = 0.00;
                if ($emp['tipe_gaji'] === 'borongan') {
                    $prodStmt = $db->prepare("
                        SELECT 
                            SUM(p.kuantitas * pg.harga_per_bungkus) as total_reguler,
                            SUM(p.lembur_kuantitas * pg.harga_per_bungkus) as total_lembur
                        FROM produksi p
                        JOIN produk prod ON p.id_produk = prod.id
                        JOIN kelompok_harga_produk pg ON prod.id_kelompok_harga = pg.id
                        WHERE p.id_karyawan = ? AND p.date BETWEEN ? AND ?
                          AND p.id_penggajian IS NULL
                    ");
                    $prodStmt->execute([$emp['id'], $period_start, $period_end]);
                    $prodData = $prodStmt->fetch();
                    $prodPayTotal = (float)($prodData['total_reguler'] ?? 0);
                    $overtimePayTotal = (float)($prodData['total_lembur'] ?? 0);
                } else if ($emp['tipe_gaji'] === 'bulanan') {
                    $overtimePayTotal = (float)($attData['lembur_bulanan'] ?? 0);
                }

                // If completely inactive in this period, skip generating a payroll item for them
                if ($attendanceDays == 0 && $prodPayTotal == 0 && $overtimePayTotal == 0 && $emp['tipe_gaji'] !== 'bulanan') {
                    continue; // Skip borongan workers who didn't work at all this week
                }

                // Calculate Monthly Allowance
                $monthlyAllowance = 0.00;
                if ($type === 'monthly' && $emp['tipe_gaji'] === 'bulanan') {
                    $monthlyAllowance = (float)$emp['tunjangan_bulanan'];
                } elseif ($type === 'weekly' && $isLastWeekOfMonth) {
                    $monthlyAllowance = (float)$emp['tunjangan_bulanan'];
                }

                // Calculate Penarikan Gaji (Done before Kasbon so we know available net)
                $penarikanTotal = 0.00;
                $penarikanDetails = [];
                if ($emp['tipe_gaji'] === 'bulanan' && $type === 'monthly') {
                    $pendingPenarikan = $penarikanModel->getPendingByEmployee($emp['id'], $period_end);
                    foreach ($pendingPenarikan as $p) {
                        $penarikanTotal += (float)$p['nominal'];
                        $penarikanDetails[] = [
                            'id_penarikan' => $p['id'],
                            'tanggal' => $p['tanggal'],
                            'nominal' => $p['nominal']
                        ];
                    }
                }

                // Calculate Available Net for Debts
                $netBeforeDeductions = $baseSalary + $attendancePayTotal + $prodPayTotal + $overtimePayTotal + $monthlyAllowance;
                $maxAvailableForDebts = $netBeforeDeductions - $penarikanTotal;
                if ($maxAvailableForDebts < 0) $maxAvailableForDebts = 0;

                $activeDebts = $debtModel->getActiveDebtsByEmployeeId($emp['id']);
                $debtsDeductionTotal = 0.00;
                $debtsDetails = [];
                $kasbonAdjustedDown = false;
                
                foreach ($activeDebts as $debt) {
                    $deduction = (float)$debt['potongan_bawaan'];
                    $remaining = (float)$debt['sisa_nominal'];
                    if ($deduction > $remaining) {
                        $deduction = $remaining;
                    }
                    if ($deduction > $maxAvailableForDebts) {
                        $deduction = $maxAvailableForDebts;
                        $kasbonAdjustedDown = true;
                    }
                    if ($deduction > 0) {
                        $debtsDeductionTotal += $deduction;
                        $maxAvailableForDebts -= $deduction;
                        $debtsDetails[] = [
                            'id_kasbon' => $debt['id'],
                            'keterangan' => $debt['keterangan'],
                            'nominal' => $deduction
                        ];
                    }
                }

                // Initial Net Salary
                $netSalary = $baseSalary + $attendancePayTotal + $prodPayTotal + $overtimePayTotal + $monthlyAllowance - $debtsDeductionTotal - $penarikanTotal;
                if ($netSalary < 0) {
                    $netSalary = 0;
                }

                // Build details JSON
                $detailsJson = json_encode([
                    'debts' => $debtsDetails,
                    'penarikan' => $penarikanDetails,
                    'kasbon_adjusted_down' => $kasbonAdjustedDown
                ]);

                // Insert Payroll Item
                $insertItem = $db->prepare("
                    INSERT INTO rincian_penggajian (
                        id_penggajian, id_karyawan, gaji_pokok, hari_hadir, total_uang_kehadiran,
                        total_upah_produksi, total_upah_lembur, tunjangan_bulanan, total_potongan_kasbon, total_penarikan_gaji, gaji_bersih, rincian_json
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $insertItem->execute([
                    $runId, $emp['id'], $baseSalary, $attendanceDays, $attendancePayTotal,
                    $prodPayTotal, $overtimePayTotal, $monthlyAllowance, $debtsDeductionTotal, $penarikanTotal, $netSalary, $detailsJson
                ]);
                
                $itemCount++;
            }

            if ($itemCount === 0) {
                $db->rollBack();
                $_SESSION['flash_error'] = 'Tidak ada data kehadiran/produksi yang belum di-approve untuk rentang tanggal tersebut.';
                redirect('/payroll');
                return;
            }

            // 3. Lock Attendances, Productions, and Penarikan Gaji to this draft
            $lockAtt = $db->prepare("
                UPDATE absensi 
                SET id_penggajian = ? 
                WHERE id_penggajian IS NULL 
                  AND date BETWEEN ? AND ?
                  AND id_karyawan IN (SELECT id_karyawan FROM rincian_penggajian WHERE id_penggajian = ? AND is_excluded = 0)
            ");
            $lockAtt->execute([$runId, $period_start, $period_end, $runId]);

            $lockProd = $db->prepare("
                UPDATE produksi 
                SET id_penggajian = ? 
                WHERE id_penggajian IS NULL 
                  AND date BETWEEN ? AND ?
                  AND id_karyawan IN (SELECT id_karyawan FROM rincian_penggajian WHERE id_penggajian = ? AND is_excluded = 0)
            ");
            $lockProd->execute([$runId, $period_start, $period_end, $runId]);

            $lockPenarikan = $db->prepare("
                UPDATE penarikan_gaji 
                SET id_penggajian = ? 
                WHERE id_penggajian IS NULL 
                  AND tanggal <= ?
                  AND id_karyawan IN (SELECT id_karyawan FROM rincian_penggajian WHERE id_penggajian = ? AND is_excluded = 0)
            ");
            $lockPenarikan->execute([$runId, $period_end, $runId]);

            $db->commit();
            $_SESSION['flash_success'] = 'Draft Payroll berhasil dibuat. Silakan periksa rincian sebelum menyetujui.';
            redirect('/payroll/preview?id=' . $runId);

        } catch (\Exception $e) {
            $db->rollBack();
            $_SESSION['flash_error'] = 'Terjadi kesalahan sistem: ' . $e->getMessage();
            redirect('/payroll');
        }
    }
    public function show(): void
    {
        checkPermission('payroll');
        $id = (int)($_GET['id'] ?? 0);
        
        $prModel = new PayrollRun();
        $piModel = new PayrollItem();
        
        $run = $prModel->findById($id);
        if (!$run) {
            $_SESSION['flash_error'] = 'Payroll tidak ditemukan.';
            redirect('/payroll');
            return;
        }

        $items = $piModel->getByRunId($id);

        $debtModel = new \App\Models\Debt();
        $savingModel = new \App\Models\Saving();
        foreach ($items as &$item) {
            $activeDebts = $debtModel->getActiveDebtsByEmployeeId((int)$item['id_karyawan']);
            $item['total_active_kasbon'] = array_sum(array_column($activeDebts, 'sisa_nominal'));
            $item['saldo_tabungan'] = $savingModel->getBalance((int)$item['id_karyawan']);
        }
        unset($item);

        view('payroll/preview', [
            'title' => 'Preview Payroll',
            'run' => $run,
            'items' => $items
        ]);
    }

    public function updateItem(): void
    {
        checkPermission('payroll');
        validateCsrfToken();
        
        $itemId = (int)($_POST['item_id'] ?? 0);
        $runId = (int)($_POST['run_id'] ?? 0);
        
        $debtDeduction = (float)parseRupiah($_POST['total_potongan_kasbon'] ?? '0');
        $otherAllowances = (float)parseRupiah($_POST['tunjangan_lain'] ?? '0');
        $otherAllowancesNotes = trim($_POST['catatan_tunjangan_lain'] ?? '');
        $otherDeductions = (float)parseRupiah($_POST['potongan_lain'] ?? '0');
        $otherDeductionsNotes = trim($_POST['catatan_potongan_lain'] ?? '');
        $tabunganSetor = (float)parseRupiah($_POST['potongan_tabungan'] ?? '0');
        $tabunganTarik = (float)parseRupiah($_POST['penarikan_tabungan'] ?? '0');
        $roundingAmount = (float)parseRupiah($_POST['nominal_pembulatan'] ?? '0');

        $db = getDB();
        
        $stmt = $db->prepare("SELECT * FROM rincian_penggajian WHERE id = ? AND id_penggajian = ?");
        $stmt->execute([$itemId, $runId]);
        $item = $stmt->fetch();

        if (!$item) {
            $_SESSION['flash_error'] = 'Data tidak ditemukan.';
            redirect('/payroll/preview?id=' . $runId);
            return;
        }

        $details = json_decode($item['rincian_json'] ?? '[]', true) ?: [];

        if ($tabunganTarik > 0) {
            $savingModel = new \App\Models\Saving();
            $saldoTabungan = $savingModel->getBalance((int)$item['id_karyawan']);
            if ($tabunganTarik > $saldoTabungan) {
                $_SESSION['flash_error'] = 'Gagal Menyimpan! Penarikan tabungan (Rp ' . number_format($tabunganTarik, 0, ',', '.') . ') melebihi total saldo karyawan (Rp ' . number_format($saldoTabungan, 0, ',', '.') . ').';
                redirect('/payroll/preview?id=' . $runId);
                return;
            }
        }
        
        if ($debtDeduction > 0) {
            $debtModel = new \App\Models\Debt();
            $activeDebts = $debtModel->getActiveDebtsByEmployeeId((int)$item['id_karyawan']);
            $totalActiveKasbon = array_sum(array_column($activeDebts, 'sisa_nominal'));
            
            if ($debtDeduction > $totalActiveKasbon) {
                $_SESSION['flash_error'] = 'Gagal Menyimpan! Nominal potongan kasbon (Rp ' . number_format($debtDeduction, 0, ',', '.') . ') melebihi batas maksimal sisa hutang karyawan (Rp ' . number_format($totalActiveKasbon, 0, ',', '.') . '). Silakan kurangi nominalnya agar tidak terjadi kerugian pada karyawan.';
                redirect('/payroll/preview?id=' . $runId);
                return;
            }

            $newDebtsDetails = [];
            $remainingDeduct = $debtDeduction;
            foreach ($activeDebts as $ad) {
                if ($remainingDeduct <= 0) break;
                $sisa = (float)$ad['sisa_nominal'];
                $potong = min($sisa, $remainingDeduct);
                $newDebtsDetails[] = [
                    'id_kasbon' => $ad['id'],
                    'nominal' => $potong
                ];
                $remainingDeduct -= $potong;
            }
            $details['debts'] = $newDebtsDetails;
        } else {
            $details['debts'] = []; // Kosongkan jika 0
        }
        $newDetailsJson = json_encode($details);

        // Recalculate gaji_bersih
        $base = (float)$item['gaji_pokok'];
        $att = (float)$item['total_uang_kehadiran'];
        $prod = (float)$item['total_upah_produksi'];
        $lembur = (float)$item['total_upah_lembur'];
        $monthly = (float)$item['tunjangan_bulanan'];
        $penarikan = (float)($item['total_penarikan_gaji'] ?? 0);

        $newNet = $base + $att + $prod + $lembur + $monthly + $otherAllowances - $debtDeduction - $otherDeductions - $tabunganSetor + $tabunganTarik - $penarikan + $roundingAmount;
        
        if ($newNet < 0) {
            $_SESSION['flash_error'] = 'Gagal Menyimpan! Total potongan melebihi total pendapatan. Gaji tidak boleh minus.';
            redirect('/payroll/preview?id=' . $runId);
            return;
        }

        $update = $db->prepare("
            UPDATE rincian_penggajian 
            SET total_potongan_kasbon = ?, rincian_json = ?,
                tunjangan_lain = ?, catatan_tunjangan_lain = ?, 
                potongan_lain = ?, catatan_potongan_lain = ?, 
                potongan_tabungan = ?, penarikan_tabungan = ?,
                nominal_pembulatan = ?, gaji_bersih = ?
            WHERE id = ?
        ");
        $update->execute([
            $debtDeduction, $newDetailsJson,
            $otherAllowances, $otherAllowancesNotes, 
            $otherDeductions, $otherDeductionsNotes,
            $tabunganSetor, $tabunganTarik,
            $roundingAmount, $newNet, $itemId
        ]);

        $_SESSION['flash_success'] = 'Penyesuaian gaji berhasil disimpan.';
        redirect('/payroll/preview?id=' . $runId);
    }

    public function delete(): void
    {
        checkPermission('payroll');
        validateCsrfToken();

        $runId = (int)($_POST['id'] ?? 0);
        
        $db = getDB();
        $prModel = new PayrollRun();
        $run = $prModel->findById($runId);

        if (!$run || $run['status'] !== 'draft') {
            $_SESSION['flash_error'] = 'Payroll tidak valid atau sudah disetujui (tidak bisa dihapus).';
            redirect('/payroll');
            return;
        }

        try {
            $db->beginTransaction();

            // 1. Buka kembali kunci Absensi, Produksi, dan Penarikan
            $db->prepare("UPDATE absensi SET id_penggajian = NULL WHERE id_penggajian = ?")->execute([$runId]);
            $db->prepare("UPDATE produksi SET id_penggajian = NULL WHERE id_penggajian = ?")->execute([$runId]);
            $db->prepare("UPDATE penarikan_gaji SET id_penggajian = NULL WHERE id_penggajian = ?")->execute([$runId]);

            // 2. Hapus rincian_penggajian
            $db->prepare("DELETE FROM rincian_penggajian WHERE id_penggajian = ?")->execute([$runId]);

            // 3. Hapus penggajian
            $prModel->delete($runId);

            $db->commit();
            $_SESSION['flash_success'] = 'Draft Payroll berhasil dihapus.';
            redirect('/payroll');

        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                try {
                    $db->rollBack();
                } catch (\Throwable $t) {}
            }
            $_SESSION['flash_error'] = 'Gagal menghapus payroll: ' . $e->getMessage();
            redirect('/payroll/preview?id=' . $runId);
        }
    }

    public function approve(): void
    {
        checkPermission('payroll');
        validateCsrfToken();

        $runId = (int)($_POST['id'] ?? 0);
        
        $db = getDB();
        $prModel = new PayrollRun();
        $run = $prModel->findById($runId);

        if (!$run || $run['status'] === 'approved') {
            $_SESSION['flash_error'] = 'Payroll tidak valid atau sudah disetujui.';
            redirect('/payroll');
            return;
        }

        try {
            $db->beginTransaction();

            $user = currentUser();
            
            // 1. Update status
            $stmt = $db->prepare("UPDATE penggajian SET status = 'approved', disetujui_pada = NOW(), disetujui_oleh = ? WHERE id = ?");
            $stmt->execute([$user['id'], $runId]);

            // (Penguncian absensi dan produksi sudah dilakukan saat pembuatan draft di metode generate)
            // 3. Process Debts
            $piModel = new PayrollItem();
            $items = $piModel->getByRunId($runId);
            $debtModel = new Debt();

            foreach ($items as $item) {
                if ($item['is_excluded'] == 1) continue;

                if (!empty($item['rincian_json'])) {
                    $details = json_decode($item['rincian_json'], true);
                    
                    if ($item['total_potongan_kasbon'] > 0 && isset($details['debts']) && is_array($details['debts'])) {
                        foreach ($details['debts'] as $d) {
                            $debtModel->addDeduction(
                                (int)$d['id_kasbon'], 
                                (float)$d['nominal'], 
                                date('Y-m-d'), 
                                'payroll', 
                                'Potongan otomatis payroll ID #' . $runId,
                                (int)$item['id']
                            );
                        }
                    }

                    if ($item['total_penarikan_gaji'] > 0 && isset($details['penarikan']) && is_array($details['penarikan'])) {
                        foreach ($details['penarikan'] as $p) {
                            $updPenarikan = $db->prepare("UPDATE penarikan_gaji SET id_penggajian = ? WHERE id = ?");
                            $updPenarikan->execute([$runId, (int)$p['id_penarikan']]);
                        }
                    }

                    // Tabungan: Setor (potongan gaji) & Tarik (penambahan gaji)
                    $savingModel = new \App\Models\Saving();
                    $savingTransModel = new \App\Models\SavingTransaction();

                    if ((float)$item['potongan_tabungan'] > 0) {
                        $amt = (float)$item['potongan_tabungan'];
                        $savingTransModel->create(
                            (int)$item['id_karyawan'], 'deposit', $amt, 'payroll', date('Y-m-d'), (int)$item['id'], 'Setoran tabungan via payroll #' . $runId
                        );
                        $savingModel->adjustBalance((int)$item['id_karyawan'], $amt);
                    }

                    if ((float)$item['penarikan_tabungan'] > 0) {
                        $amt = (float)$item['penarikan_tabungan'];
                        $currentSaldo = $savingModel->getBalance((int)$item['id_karyawan']);
                        if ($amt > $currentSaldo) {
                            throw new \Exception('Saldo tabungan karyawan ' . ($item['employee_name'] ?? 'ID ' . $item['id_karyawan']) . ' tidak mencukupi untuk penarikan sebesar Rp ' . number_format($amt, 0, ',', '.') . '. Karyawan mungkin telah menarik tabungan secara manual setelah draft dibuat. Silakan hapus draft dan generate ulang, atau sesuaikan kembali rincian karyawan ini.');
                        }
                        $savingTransModel->create(
                            (int)$item['id_karyawan'], 'withdrawal', $amt, 'payroll', date('Y-m-d'), (int)$item['id'], 'Penarikan tabungan via payroll #' . $runId
                        );
                        $savingModel->adjustBalance((int)$item['id_karyawan'], -$amt);
                    }
                }
            }

            $db->commit();
            $_SESSION['flash_success'] = 'Payroll berhasil disetujui! Data telah dikunci dan potongan kasbon telah diterapkan.';
            redirect('/payroll/preview?id=' . $runId);

        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                try {
                    $db->rollBack();
                } catch (\Throwable $t) {}
            }
            $_SESSION['flash_error'] = 'Gagal menyetujui payroll: ' . $e->getMessage();
            redirect('/payroll/preview?id=' . $runId);
        }
    }

    public function regenerate(): void
    {
        checkPermission('payroll');
        validateCsrfToken();

        $runId = (int)($_POST['id'] ?? 0);
        
        $db = getDB();
        $prModel = new PayrollRun();
        $run = $prModel->findById($runId);

        if (!$run || $run['status'] !== 'approved') {
            $_SESSION['flash_error'] = 'Payroll tidak valid.';
            redirect('/payroll');
            return;
        }

        if (empty($run['disetujui_pada'])) {
            $_SESSION['flash_error'] = 'Data waktu persetujuan tidak valid.';
            redirect('/payroll/preview?id=' . $runId);
            return;
        }

        // Check if within 24 hours
        $approvedTime = strtotime($run['disetujui_pada']);
        $now = time();
        if (($now - $approvedTime) > 86400) {
            $_SESSION['flash_error'] = 'Gagal: Payroll sudah di-approve lebih dari 24 jam yang lalu.';
            redirect('/payroll/preview?id=' . $runId);
            return;
        }

        try {
            $db->beginTransaction();
            $tabunganWarning = [];

            // 1. Unlock Attendances, Productions, and Penarikan Gaji
            $db->prepare("UPDATE absensi SET id_penggajian = NULL WHERE id_penggajian = ?")->execute([$runId]);
            $db->prepare("UPDATE produksi SET id_penggajian = NULL WHERE id_penggajian = ?")->execute([$runId]);
            $db->prepare("UPDATE penarikan_gaji SET id_penggajian = NULL WHERE id_penggajian = ?")->execute([$runId]);

            // 2. Revert Debts (must restore sisa_nominal based on debt_deductions linked to payroll_items of this run)
            $piModel = new PayrollItem();
            $items = $piModel->getByRunId($runId);

            foreach ($items as $item) {
                // Fetch deductions linked to this item
                $dedStmt = $db->prepare("SELECT id, id_kasbon, nominal FROM potongan_kasbon WHERE id_rincian_penggajian = ?");
                $dedStmt->execute([$item['id']]);
                $deductions = $dedStmt->fetchAll();

                foreach ($deductions as $ded) {
                    // Revert debt balance
                    $updDebt = $db->prepare("UPDATE kasbon SET sisa_nominal = sisa_nominal + ?, status = 'active' WHERE id = ?");
                    $updDebt->execute([$ded['nominal'], $ded['id_kasbon']]);
                }
                
                // Delete the deductions
                $db->prepare("DELETE FROM potongan_kasbon WHERE id_rincian_penggajian = ?")->execute([$item['id']]);

                // Revert Tabungan
                $savingModel = new \App\Models\Saving();
                $stStmt = $db->prepare("SELECT id, tipe, jumlah FROM transaksi_tabungan WHERE id_rincian_penggajian = ?");
                $stStmt->execute([$item['id']]);
                $savingTrans = $stStmt->fetchAll();

                foreach ($savingTrans as $st) {
                    if ($st['tipe'] === 'deposit') {
                        // Check if rollback will cause negative balance
                        $currentSaldo = $savingModel->getBalance((int)$item['id_karyawan']);
                        if ($currentSaldo - (float)$st['jumlah'] < 0) {
                            $selisih = (float)$st['jumlah'] - $currentSaldo;
                            $tabunganWarning[] = "Karyawan <strong>" . e($item['employee_name'] ?? 'ID ' . $item['id_karyawan']) . "</strong> kurang " . formatRupiah((int)$selisih);
                            
                            // Hanya kurangi saldo yang tersisa (jadi 0)
                            if ($currentSaldo > 0) {
                                $savingModel->adjustBalance((int)$item['id_karyawan'], -$currentSaldo);
                            }
                        } else {
                            $savingModel->adjustBalance((int)$item['id_karyawan'], -(float)$st['jumlah']);
                        }
                    } else if ($st['tipe'] === 'withdrawal') {
                        $savingModel->adjustBalance((int)$item['id_karyawan'], (float)$st['jumlah']);
                    }
                }
                
                // Delete saving transactions
                $db->prepare("DELETE FROM transaksi_tabungan WHERE id_rincian_penggajian = ?")->execute([$item['id']]);
            }

            // 3. Delete Payroll Items
            $db->prepare("DELETE FROM rincian_penggajian WHERE id_penggajian = ?")->execute([$runId]);

            // 4. Delete the Payroll Run entirely (we recreate it in draft)
            $prModel->delete($runId);

            $db->commit();
            
            if (!empty($tabunganWarning)) {
                $warnText = "Payroll berhasil dibatalkan, namun ada catatan khusus terkait Tabungan:<br><br><ul class='mb-3 text-start' style='font-size: 0.9rem;'>";
                foreach ($tabunganWarning as $w) {
                    $warnText .= "<li>$w</li>";
                }
                $warnText .= "</ul><p class='text-start mb-0' style='font-size: 0.9rem;'>Saldo tabungan mereka tidak dikurangi sepenuhnya (tidak dikembalikan ke kas perusahaan) karena akan menyebabkan saldo minus (karyawan keburu mencairkan uangnya sebelum payroll ini dibatalkan).</p>";
                $_SESSION['swal_warning'] = $warnText;
            } else {
                $_SESSION['flash_success'] = 'Payroll berhasil dibatalkan dan data telah dikembalikan seperti semula.';
            }
            
            redirect('/payroll');

        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                try {
                    $db->rollBack();
                } catch (\Throwable $t) {}
            }
            $_SESSION['flash_error'] = 'Gagal membatalkan payroll: ' . $e->getMessage();
            redirect('/payroll/preview?id=' . $runId);
        }
    }

    public function exportPdf(): void
    {
        checkPermission('payroll');

        $runId = (int)($_GET['id'] ?? 0);
        $db = getDB();

        // Ambil info payroll run
        $stmtRun = $db->prepare("SELECT * FROM penggajian WHERE id = ?");
        $stmtRun->execute([$runId]);
        $run = $stmtRun->fetch();

        if (!$run) {
            $_SESSION['flash_error'] = 'Data payroll tidak valid.';
            redirect('/payroll');
            return;
        }

        // Ambil semua rincian
        $stmtItems = $db->prepare("
            SELECT pi.*, e.name as employee_name, e.tipe_gaji
            FROM rincian_penggajian pi
            JOIN karyawan e ON pi.id_karyawan = e.id
            WHERE pi.id_penggajian = ? AND pi.is_excluded = 0
            ORDER BY e.name ASC
        ");
        $stmtItems->execute([$runId]);
        $items = $stmtItems->fetchAll();

        ob_start();
        include APP_ROOT . '/app/Views/reports/pdf_rekap.php';
        $html = ob_get_clean();

        $runName = $run['name'] ?: ('Run ' . $runId);
        $safeRunName = preg_replace('/[^A-Za-z0-9_\- ]/', '', $runName);
        $statusStr = $run['status'] === 'draft' ? 'DRAFT ' : '';
        $filename = 'Rekap ' . $statusStr . $safeRunName;
        
        streamPdf($html, $filename, 'A4', 'landscape');
    }

    public function exportSlipsMass(): void
    {
        $runId = (int)($_GET['run_id'] ?? 0);
        $db = getDB();

        // Get payroll run
        $stmtRun = $db->prepare("SELECT * FROM penggajian WHERE id = ?");
        $stmtRun->execute([$runId]);
        $run = $stmtRun->fetch();

        if (!$run) {
            $_SESSION['flash_error'] = 'Data payroll tidak ditemukan.';
            redirect('/payroll');
            return;
        }

        // Ambil semua rincian
        $stmtItems = $db->prepare("
            SELECT pi.*, e.name as employee_name, e.tipe_gaji
            FROM rincian_penggajian pi
            JOIN karyawan e ON pi.id_karyawan = e.id
            WHERE pi.id_penggajian = ? AND pi.is_excluded = 0
            ORDER BY e.name ASC
        ");
        $stmtItems->execute([$runId]);
        $items = $stmtItems->fetchAll();

        if (empty($items)) {
            $_SESSION['flash_error'] = 'Tidak ada slip gaji untuk periode ini.';
            redirect('/payroll/preview?id=' . $runId);
            return;
        }

        ob_start();
        include APP_ROOT . '/app/Views/payroll/pdf_slips_batch.php';
        $html = ob_get_clean();

        $runName = $run['name'] ?: ('Run ' . $runId);
        $safeRunName = preg_replace('/[^A-Za-z0-9_\- ]/', '', $runName);
        $filename = 'Slip Gaji Batch ' . $safeRunName;
        
        // Always A4 landscape for these layouts (2x2 or 1x2)
        streamPdf($html, $filename, 'A4', 'landscape');
    }

    public function toggleExclude(): void
    {
        checkPermission('payroll');
        validateCsrfToken();
        
        $itemId = (int)($_POST['item_id'] ?? 0);
        $runId = (int)($_POST['run_id'] ?? 0);
        $isExcluded = (int)($_POST['is_excluded'] ?? 0);
        $catatan = trim($_POST['catatan_pengecualian'] ?? '');

        $db = getDB();
        $stmtItem = $db->prepare("SELECT id_karyawan FROM rincian_penggajian WHERE id = ? AND id_penggajian = ?");
        $stmtItem->execute([$itemId, $runId]);
        $item = $stmtItem->fetch();
        
        if (!$item) {
            $_SESSION['flash_error'] = 'Data rincian tidak ditemukan.';
            redirect('/payroll/preview?id=' . $runId);
            return;
        }
        
        $stmtRun = $db->prepare("SELECT periode_awal, periode_akhir FROM penggajian WHERE id = ?");
        $stmtRun->execute([$runId]);
        $run = $stmtRun->fetch();

        try {
            $db->beginTransaction();

            // Update status pengecualian
            $stmt = $db->prepare("UPDATE rincian_penggajian SET is_excluded = ?, catatan_pengecualian = ? WHERE id = ?");
            $stmt->execute([$isExcluded, $catatan, $itemId]);

            $empId = (int)$item['id_karyawan'];

            if ($isExcluded) {
                // Karyawan dikecualikan: Lepas kunci absensinya agar bisa ditarik payroll lain
                $db->prepare("UPDATE absensi SET id_penggajian = NULL WHERE id_penggajian = ? AND id_karyawan = ?")->execute([$runId, $empId]);
                $db->prepare("UPDATE produksi SET id_penggajian = NULL WHERE id_penggajian = ? AND id_karyawan = ?")->execute([$runId, $empId]);
                $db->prepare("UPDATE penarikan_gaji SET id_penggajian = NULL WHERE id_penggajian = ? AND id_karyawan = ?")->execute([$runId, $empId]);
            } else {
                // Batal Kecuali: Kunci kembali absensinya ke draf ini
                $db->prepare("UPDATE absensi SET id_penggajian = ? WHERE id_penggajian IS NULL AND id_karyawan = ? AND date BETWEEN ? AND ?")
                   ->execute([$runId, $empId, $run['periode_awal'], $run['periode_akhir']]);
                $db->prepare("UPDATE produksi SET id_penggajian = ? WHERE id_penggajian IS NULL AND id_karyawan = ? AND date BETWEEN ? AND ?")
                   ->execute([$runId, $empId, $run['periode_awal'], $run['periode_akhir']]);
                $db->prepare("UPDATE penarikan_gaji SET id_penggajian = ? WHERE id_penggajian IS NULL AND id_karyawan = ? AND tanggal <= ?")
                   ->execute([$runId, $empId, $run['periode_akhir']]);
            }

            $db->commit();
        } catch (\Exception $e) {
            $db->rollBack();
            $_SESSION['flash_error'] = 'Terjadi kesalahan sistem: ' . $e->getMessage();
            redirect('/payroll/preview?id=' . $runId);
            return;
        }

        $_SESSION['flash_success'] = $isExcluded ? 'Karyawan berhasil dikecualikan.' : 'Pengecualian karyawan dibatalkan.';
        redirect('/payroll/preview?id=' . $runId);
    }
}
