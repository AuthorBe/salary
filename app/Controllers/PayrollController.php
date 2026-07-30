<?php
namespace App\Controllers;

use App\Models\PayrollRun;
use App\Models\PayrollItem;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Production;
use App\Models\Debt;

class PayrollController
{
    public function index(): void
    {
        checkPermission('payroll');
        $prModel = new PayrollRun();
        $payrolls = $prModel->getAll();

        view('payroll/index', [
            'title'     => 'Data Payroll – Salary',
            'pageTitle' => 'Data Payroll',
            'pageKey'   => 'payroll',
            'payrolls'  => $payrolls
        ]);
    }

    public function create(): void
    {
        checkPermission('payroll');
        
        $type = $_GET['type'] ?? 'weekly';
        
        $settingModel = new \App\Models\AppSetting();
        $weekStartDay = (int) $settingModel->get('week_start_day', '1');
        $map = [0 => 'sunday', 1 => 'monday', 2 => 'tuesday', 3 => 'wednesday', 4 => 'thursday', 5 => 'friday', 6 => 'saturday'];
        
        $endDayIndex = ($weekStartDay + 6) % 7;
        $endDayName = $map[$endDayIndex];
        
        // Default Mingguan
        $endWeekly = new \DateTime("last " . $endDayName);
        $startWeekly = clone $endWeekly;
        $startWeekly->modify('-6 days');
        
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

        $db = getDB();

        // Check if there's already a draft for this type
        $draftExists = $db->prepare("SELECT id FROM penggajian WHERE type = ? AND status = 'draft'");
        $draftExists->execute([$type]);
        if ($draft = $draftExists->fetch()) {
            $_SESSION['flash_error'] = 'Masih ada Draft Payroll ' . ucfirst($type) . ' yang belum diselesaikan. Harap Approve atau Hapus draft tersebut terlebih dahulu.';
            redirect('/payroll');
            return;
        }

        try {
            $db->beginTransaction();

            // 1. Create payroll run
            $stmt = $db->prepare("INSERT INTO penggajian (periode_awal, periode_akhir, type, status) VALUES (?, ?, ?, 'draft')");
            $stmt->execute([$period_start, $period_end, $type]);
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
            
            $itemCount = 0;

            foreach ($employees as $emp) {
                $baseSalary = (float)($emp['gaji_pokok'] ?? 0);
                
                // Get Attendances (unlocked)
                $attStmt = $db->prepare("
                    SELECT COUNT(*) as days 
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
                if ($emp['tipe_gaji'] === 'borongan') {
                    $prodStmt = $db->prepare("
                        SELECT SUM(p.kuantitas * pg.harga_per_bungkus) as total
                        FROM produksi p
                        JOIN produk prod ON p.id_produk = prod.id
                        JOIN kelompok_harga_produk pg ON prod.id_kelompok_harga = pg.id
                        WHERE p.id_karyawan = ? AND p.date BETWEEN ? AND ?
                          AND p.id_penggajian IS NULL
                    ");
                    $prodStmt->execute([$emp['id'], $period_start, $period_end]);
                    $prodPayTotal = (float)$prodStmt->fetchColumn() ?: 0.00;
                }

                // If completely inactive in this period, skip generating a payroll item for them
                if ($attendanceDays == 0 && $prodPayTotal == 0 && $emp['tipe_gaji'] !== 'bulanan') {
                    continue; // Skip borongan workers who didn't work at all this week
                }

                // Calculate Monthly Allowance
                $monthlyAllowance = 0.00;
                if ($type === 'monthly' && $emp['tipe_gaji'] === 'bulanan') {
                    $monthlyAllowance = (float)$emp['tunjangan_bulanan'];
                } elseif ($type === 'weekly' && $isLastWeekOfMonth) {
                    $monthlyAllowance = (float)$emp['tunjangan_bulanan'];
                }

                // Calculate Debts
                $activeDebts = $debtModel->getActiveDebtsByEmployeeId($emp['id']);
                $debtsDeductionTotal = 0.00;
                $debtsDetails = [];
                
                foreach ($activeDebts as $debt) {
                    $deduction = (float)$debt['potongan_bawaan'];
                    $remaining = (float)$debt['sisa_nominal'];
                    if ($deduction > $remaining) {
                        $deduction = $remaining;
                    }
                    if ($deduction > 0) {
                        $debtsDeductionTotal += $deduction;
                        $debtsDetails[] = [
                            'id_kasbon' => $debt['id'],
                            'keterangan' => $debt['keterangan'],
                            'nominal' => $deduction
                        ];
                    }
                }

                // Initial Net Salary
                $netSalary = $baseSalary + $attendancePayTotal + $prodPayTotal + $monthlyAllowance - $debtsDeductionTotal;

                // Build details JSON
                $detailsJson = json_encode([
                    'debts' => $debtsDetails
                ]);

                // Insert Payroll Item
                $insertItem = $db->prepare("
                    INSERT INTO rincian_penggajian (
                        id_penggajian, id_karyawan, gaji_pokok, hari_hadir, total_uang_kehadiran,
                        total_upah_produksi, tunjangan_bulanan, total_potongan_kasbon, gaji_bersih, rincian_json
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $insertItem->execute([
                    $runId, $emp['id'], $baseSalary, $attendanceDays, $attendancePayTotal,
                    $prodPayTotal, $monthlyAllowance, $debtsDeductionTotal, $netSalary, $detailsJson
                ]);
                
                $itemCount++;
            }

            if ($itemCount === 0) {
                $db->rollBack();
                $_SESSION['flash_error'] = 'Tidak ada data kehadiran/produksi yang belum di-approve untuk rentang tanggal tersebut.';
                redirect('/payroll');
                return;
            }

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
        
        $debtDeduction = (float)str_replace('.', '', $_POST['total_potongan_kasbon'] ?? '0');
        $otherAllowances = (float)str_replace('.', '', $_POST['tunjangan_lain'] ?? '0');
        $otherAllowancesNotes = trim($_POST['catatan_tunjangan_lain'] ?? '');
        $otherDeductions = (float)str_replace('.', '', $_POST['potongan_lain'] ?? '0');
        $otherDeductionsNotes = trim($_POST['catatan_potongan_lain'] ?? '');
        $roundingAmount = (float)str_replace('.', '', $_POST['nominal_pembulatan'] ?? '0');

        $db = getDB();
        
        $stmt = $db->prepare("SELECT * FROM rincian_penggajian WHERE id = ? AND id_penggajian = ?");
        $stmt->execute([$itemId, $runId]);
        $item = $stmt->fetch();

        if (!$item) {
            $_SESSION['flash_error'] = 'Data tidak ditemukan.';
            redirect('/payroll/preview?id=' . $runId);
            return;
        }

        // Proporsikan nominal potongan ke rincian_json jika ada
        $details = json_decode($item['rincian_json'] ?? '[]', true) ?: [];
        if (!empty($details['debts']) && is_array($details['debts'])) {
            $totalOrig = 0;
            foreach ($details['debts'] as $d) {
                $totalOrig += (float)$d['nominal'];
            }
            if ($totalOrig > 0) {
                $ratio = $debtDeduction / $totalOrig;
                foreach ($details['debts'] as &$d) {
                    $d['nominal'] = round((float)$d['nominal'] * $ratio, 2);
                }
                unset($d);
            }
        } elseif ($debtDeduction > 0) {
            $debtModel = new \App\Models\Debt();
            $activeDebts = $debtModel->getActiveDebtsByEmployeeId((int)$item['id_karyawan']);
            if (!empty($activeDebts)) {
                $details['debts'] = [
                    [
                        'id_kasbon' => $activeDebts[0]['id'],
                        'nominal' => $debtDeduction
                    ]
                ];
            }
        }
        $newDetailsJson = json_encode($details);

        // Recalculate gaji_bersih
        $base = (float)$item['gaji_pokok'];
        $att = (float)$item['total_uang_kehadiran'];
        $prod = (float)$item['total_upah_produksi'];
        $monthly = (float)$item['tunjangan_bulanan'];

        $newNet = $base + $att + $prod + $monthly + $otherAllowances - $debtDeduction - $otherDeductions + $roundingAmount;

        $update = $db->prepare("
            UPDATE rincian_penggajian 
            SET total_potongan_kasbon = ?, rincian_json = ?,
                tunjangan_lain = ?, catatan_tunjangan_lain = ?, 
                potongan_lain = ?, catatan_potongan_lain = ?, 
                nominal_pembulatan = ?, gaji_bersih = ?
            WHERE id = ?
        ");
        $update->execute([
            $debtDeduction, $newDetailsJson,
            $otherAllowances, $otherAllowancesNotes, 
            $otherDeductions, $otherDeductionsNotes, 
            $roundingAmount, $newNet, $itemId
        ]);

        $_SESSION['flash_success'] = 'Penyesuaian gaji berhasil disimpan.';
        redirect('/payroll/preview?id=' . $runId);
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

            // 2. Lock Attendances and Productions
            $lockAtt = $db->prepare("UPDATE absensi SET id_penggajian = ? WHERE id_penggajian IS NULL AND date BETWEEN ? AND ?");
            $lockAtt->execute([$runId, $run['periode_awal'], $run['periode_akhir']]);

            $lockProd = $db->prepare("UPDATE produksi SET id_penggajian = ? WHERE id_penggajian IS NULL AND date BETWEEN ? AND ?");
            $lockProd->execute([$runId, $run['periode_awal'], $run['periode_akhir']]);

            // 3. Process Debts
            $piModel = new PayrollItem();
            $items = $piModel->getByRunId($runId);
            $debtModel = new Debt();

            foreach ($items as $item) {
                if ($item['total_potongan_kasbon'] > 0 && !empty($item['rincian_json'])) {
                    $details = json_decode($item['rincian_json'], true);
                    if (isset($details['debts']) && is_array($details['debts'])) {
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

            // 1. Unlock Attendances and Productions
            $db->prepare("UPDATE absensi SET id_penggajian = NULL WHERE id_penggajian = ?")->execute([$runId]);
            $db->prepare("UPDATE produksi SET id_penggajian = NULL WHERE id_penggajian = ?")->execute([$runId]);

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
            }

            // 3. Delete Payroll Items
            $db->prepare("DELETE FROM rincian_penggajian WHERE id_penggajian = ?")->execute([$runId]);

            // 4. Delete the Payroll Run entirely (we recreate it in draft)
            $prModel->delete($runId);

            $db->commit();
            $_SESSION['flash_success'] = 'Payroll berhasil dibatalkan dan dikembalikan. Silakan Generate Ulang dari awal.';
            redirect('/payroll/create?type=' . $run['type'] . '&periode_awal=' . $run['periode_awal'] . '&periode_akhir=' . $run['periode_akhir']);

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
}
