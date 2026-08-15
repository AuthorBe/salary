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
        
        $todayObj = new \DateTime();
        $currentDayOfWeek = (int)$todayObj->format('w');
        $diff = ($currentDayOfWeek - $weekStartDay + 7) % 7;

        $startThisWeek = (clone $todayObj)->modify("-{$diff} days");
        $endThisWeek = (clone $startThisWeek)->modify('+6 days');

        $startLastWeek = (clone $startThisWeek)->modify('-7 days');
        $endLastWeek = (clone $startThisWeek)->modify('-1 day');
        
        // Default Mingguan: pekan lalu (yang baru tutup buku)
        $startWeekly = $startLastWeek;
        $endWeekly = $endLastWeek;
        
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

        $types = $_POST['types'] ?? [];
        if (empty($types) || !is_array($types)) {
            $_SESSION['flash_error'] = 'Pilih minimal satu tipe karyawan (Borongan atau Bulanan).';
            redirect('/payroll/create');
            return;
        }

        $isWeekly = in_array('weekly', $types);
        $isMonthly = in_array('monthly', $types);
        
        $type = 'mixed';
        if ($isWeekly && !$isMonthly) $type = 'weekly';
        if (!$isWeekly && $isMonthly) $type = 'monthly';

        $options = [];
        $allStarts = [];
        $allEnds = [];

        if ($isWeekly) {
            $wStart = $_POST['periode_awal_weekly'] ?? '';
            $wEnd = $_POST['periode_akhir_weekly'] ?? '';
            if (!$wStart || !$wEnd || $wStart > $wEnd) {
                $_SESSION['flash_error'] = 'Rentang tanggal Borongan tidak valid.';
                redirect('/payroll/create');
                return;
            }
            $options['borongan'] = ['start' => $wStart, 'end' => $wEnd];
            $allStarts[] = $wStart;
            $allEnds[] = $wEnd;
        }

        if ($isMonthly) {
            $mStart = $_POST['periode_awal_monthly'] ?? '';
            $mEnd = $_POST['periode_akhir_monthly'] ?? '';
            if (!$mStart || !$mEnd || $mStart > $mEnd) {
                $_SESSION['flash_error'] = 'Rentang tanggal Bulanan tidak valid.';
                redirect('/payroll/create');
                return;
            }
            $options['bulanan'] = ['start' => $mStart, 'end' => $mEnd];
            $allStarts[] = $mStart;
            $allEnds[] = $mEnd;
        }

        $period_start = min($allStarts);
        $period_end = max($allEnds);
        $options_json = json_encode($options);

        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            $name = 'Gaji ' . ucfirst($type) . ' ' . date('d M Y', strtotime($period_start));
        } else {
            $name = ucwords($name);
        }

        $db = getDB();

        $checkTypes = [];
        if ($isWeekly) $checkTypes[] = 'weekly';
        if ($isMonthly) $checkTypes[] = 'monthly';
        $checkTypes[] = 'mixed';
        $placeholders = implode(',', array_fill(0, count($checkTypes), '?'));

        $draftExists = $db->prepare("SELECT id FROM penggajian WHERE type IN ($placeholders) AND status = 'draft'");
        $draftExists->execute($checkTypes);
        if ($draft = $draftExists->fetch()) {
            $_SESSION['flash_error'] = 'Masih ada Draft Payroll yang belum diselesaikan. Harap Approve atau Hapus draft tersebut terlebih dahulu.';
            redirect('/payroll');
            return;
        }

        $overlapExists = false;
        $overlapMsg = '';

        $stmtApproved = $db->query("SELECT id, type, periode_awal, periode_akhir, options_json FROM penggajian WHERE status = 'approved'");
        $approvedRuns = $stmtApproved->fetchAll();

        foreach ($approvedRuns as $app) {
            $appOptions = json_decode($app['options_json'] ?? '[]', true) ?: [];
            
            $appBoronganStart = null;
            $appBoronganEnd = null;
            $appBulananStart = null;
            $appBulananEnd = null;
            
            if ($app['type'] === 'weekly' || $app['type'] === 'mixed') {
                $appBoronganStart = $appOptions['borongan']['start'] ?? $app['periode_awal'];
                $appBoronganEnd = $appOptions['borongan']['end'] ?? $app['periode_akhir'];
            }
            if ($app['type'] === 'monthly' || $app['type'] === 'mixed') {
                $appBulananStart = $appOptions['bulanan']['start'] ?? $app['periode_awal'];
                $appBulananEnd = $appOptions['bulanan']['end'] ?? $app['periode_akhir'];
            }

            if ($isWeekly && $appBoronganStart && $appBoronganEnd) {
                if ($wStart <= $appBoronganEnd && $wEnd >= $appBoronganStart) {
                    $overlapExists = true;
                    $overlapMsg = "Gagal! Terdapat bentrok tanggal Borongan dengan payroll yang sudah disetujui (Run #{$app['id']}: " . date('d M Y', strtotime($appBoronganStart)) . " s/d " . date('d M Y', strtotime($appBoronganEnd)) . ").";
                    break;
                }
            }

            if ($isMonthly && $appBulananStart && $appBulananEnd) {
                if ($mStart <= $appBulananEnd && $mEnd >= $appBulananStart) {
                    $overlapExists = true;
                    $overlapMsg = "Gagal! Terdapat bentrok tanggal Bulanan dengan payroll yang sudah disetujui (Run #{$app['id']}: " . date('d M Y', strtotime($appBulananStart)) . " s/d " . date('d M Y', strtotime($appBulananEnd)) . ").";
                    break;
                }
            }
        }

        if ($overlapExists) {
            $_SESSION['flash_error'] = $overlapMsg;
            redirect('/payroll/create');
            return;
        }

        try {
            $db->beginTransaction();

            $stmt = $db->prepare("INSERT INTO penggajian (periode_awal, periode_akhir, type, status, name, options_json) VALUES (?, ?, ?, 'draft', ?, ?)");
            $stmt->execute([$period_start, $period_end, $type, $name, $options_json]);
            $runId = (int)$db->lastInsertId();

            $itemCount = 0;
            $preventedDoublePayoutCount = 0;

            $this->generatePayrollItems($db, $runId, $type, $options, $itemCount, $preventedDoublePayoutCount);

            if ($itemCount === 0) {
                $db->rollBack();
                $_SESSION['flash_error'] = 'Tidak ada data kehadiran/produksi yang belum di-approve untuk rentang tanggal tersebut.';
                redirect('/payroll');
                return;
            }

            $db->commit();
            $successMsg = 'Draft Payroll berhasil dibuat. Silakan periksa rincian sebelum menyetujui.';
            if ($preventedDoublePayoutCount > 0) {
                $successMsg .= "<br><br><small><i class='bi bi-info-circle'></i> <strong>Info:</strong> Gaji Pokok dan Tunjangan bulanan untuk <strong>$preventedDoublePayoutCount karyawan</strong> di-nol-kan secara otomatis oleh sistem karena terdeteksi sudah pernah diberikan pada slip sebelumnya di bulan yang sama atau belum waktunya diberikan.</small>";
            }
            $_SESSION['flash_success'] = $successMsg;
            redirect('/payroll/preview?id=' . $runId);

        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
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
                $_SESSION['flash_error'] = 'Gagal Menyimpan! Nominal potongan kasbon melebihi batas maksimal sisa hutang karyawan.';
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

            // Buka kembali kunci Absensi, Produksi, dan Penarikan
            $db->prepare("UPDATE absensi SET id_penggajian = NULL WHERE id_penggajian = ?")->execute([$runId]);
            $db->prepare("UPDATE produksi SET id_penggajian = NULL WHERE id_penggajian = ?")->execute([$runId]);
            $db->prepare("UPDATE penarikan_gaji SET id_penggajian = NULL WHERE id_penggajian = ?")->execute([$runId]);

            $db->prepare("DELETE FROM rincian_penggajian WHERE id_penggajian = ?")->execute([$runId]);
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
            
            $stmt = $db->prepare("UPDATE penggajian SET status = 'approved', disetujui_pada = NOW(), disetujui_oleh = ? WHERE id = ?");
            $stmt->execute([$user['id'], $runId]);

            $piModel = new PayrollItem();
            $items = $piModel->getByRunId($runId);
            $debtModel = new Debt();
            $savingModel = new \App\Models\Saving();
            $transModel = new \App\Models\SavingTransaction();

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
                                $runId, 
                                $item['id']
                            );
                        }
                    }
                }

                if ((float)$item['potongan_tabungan'] > 0) {
                    $transModel->create((int)$item['id_karyawan'], 'deposit', (float)$item['potongan_tabungan'], 'payroll', date('Y-m-d'), $item['id'], 'Setoran via Payroll #' . $runId);
                    $savingModel->adjustBalance((int)$item['id_karyawan'], (float)$item['potongan_tabungan']);
                }
                if ((float)$item['penarikan_tabungan'] > 0) {
                    $transModel->create((int)$item['id_karyawan'], 'withdrawal', (float)$item['penarikan_tabungan'], 'payroll', date('Y-m-d'), $item['id'], 'Penarikan via Payroll #' . $runId);
                    $savingModel->adjustBalance((int)$item['id_karyawan'], -((float)$item['penarikan_tabungan']));
                }
            }

            $db->commit();
            $_SESSION['flash_success'] = 'Payroll berhasil disetujui dan dikunci permanen.';
            redirect('/payroll/preview?id=' . $runId);

        } catch (\Exception $e) {
            $db->rollBack();
            $_SESSION['flash_error'] = 'Terjadi kesalahan saat approve: ' . $e->getMessage();
            redirect('/payroll/preview?id=' . $runId);
        }
    }

    public function cancelApprove(): void
    {
        checkPermission('payroll');
        validateCsrfToken();

        $runId = (int)($_POST['id'] ?? 0);
        
        $db = getDB();
        $prModel = new PayrollRun();
        $run = $prModel->findById($runId);

        if (!$run || $run['status'] !== 'approved') {
            $_SESSION['flash_error'] = 'Payroll tidak valid atau belum disetujui.';
            redirect('/payroll');
            return;
        }

        $approvedTime = strtotime($run['disetujui_pada']);
        $now = time();
        if (($now - $approvedTime) > 86400) {
            $_SESSION['flash_error'] = 'Batas waktu pembatalan (24 jam) sudah habis.';
            redirect('/payroll/preview?id=' . $runId);
            return;
        }

        try {
            $db->beginTransaction();

            $stmtItems = $db->prepare("SELECT id FROM rincian_penggajian WHERE id_penggajian = ?");
            $stmtItems->execute([$runId]);
            $itemIds = $stmtItems->fetchAll(\PDO::FETCH_COLUMN);

            if (!empty($itemIds)) {
                $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
                
                // Revert Potongan Kasbon
                $stmtKasbon = $db->prepare("SELECT id, id_kasbon, nominal FROM potongan_kasbon WHERE id_rincian_penggajian IN ($placeholders)");
                $stmtKasbon->execute($itemIds);
                $potonganKasbonList = $stmtKasbon->fetchAll();

                foreach ($potonganKasbonList as $pk) {
                    $db->prepare("UPDATE kasbon SET sisa_nominal = sisa_nominal + ?, status = 'active' WHERE id = ?")->execute([$pk['nominal'], $pk['id_kasbon']]);
                    $db->prepare("DELETE FROM potongan_kasbon WHERE id = ?")->execute([$pk['id']]);
                }

                // Revert Transaksi Tabungan
                $stmtTabungan = $db->prepare("SELECT id, id_karyawan, tipe, jumlah FROM transaksi_tabungan WHERE id_rincian_penggajian IN ($placeholders)");
                $stmtTabungan->execute($itemIds);
                $tabunganList = $stmtTabungan->fetchAll();

                $savingModel = new \App\Models\Saving();
                foreach ($tabunganList as $tb) {
                    $adjust = $tb['tipe'] === 'deposit' ? -((float)$tb['jumlah']) : (float)$tb['jumlah'];
                    $savingModel->adjustBalance((int)$tb['id_karyawan'], $adjust);
                    $db->prepare("DELETE FROM transaksi_tabungan WHERE id = ?")->execute([$tb['id']]);
                }
            }

            // Kembalikan status ke draft
            $stmtRevert = $db->prepare("UPDATE penggajian SET status = 'draft', disetujui_pada = NULL, disetujui_oleh = NULL WHERE id = ?");
            $stmtRevert->execute([$runId]);

            $db->commit();
            $_SESSION['flash_success'] = 'Approval Payroll berhasil dibatalkan. Status kembali menjadi Draft.';
            redirect('/payroll/preview?id=' . $runId);

        } catch (\Exception $e) {
            $db->rollBack();
            $_SESSION['flash_error'] = 'Terjadi kesalahan saat membatalkan approve: ' . $e->getMessage();
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

        if (!$run || $run['status'] !== 'draft') {
            $_SESSION['flash_error'] = 'Payroll tidak valid atau sudah disetujui (tidak bisa di-regenerate).';
            redirect('/payroll');
            return;
        }

        try {
            $db->beginTransaction();

            $db->prepare("UPDATE absensi SET id_penggajian = NULL WHERE id_penggajian = ?")->execute([$runId]);
            $db->prepare("UPDATE produksi SET id_penggajian = NULL WHERE id_penggajian = ?")->execute([$runId]);
            $db->prepare("UPDATE penarikan_gaji SET id_penggajian = NULL WHERE id_penggajian = ?")->execute([$runId]);

            $db->prepare("DELETE FROM rincian_penggajian WHERE id_penggajian = ?")->execute([$runId]);

            $options = json_decode($run['options_json'] ?? '[]', true) ?: [];
            if (empty($options)) {
                if ($run['type'] === 'weekly') {
                    $options['borongan'] = ['start' => $run['periode_awal'], 'end' => $run['periode_akhir']];
                } else if ($run['type'] === 'monthly') {
                    $options['bulanan'] = ['start' => $run['periode_awal'], 'end' => $run['periode_akhir']];
                }
            }

            $itemCount = 0;
            $preventedDoublePayoutCount = 0;

            $this->generatePayrollItems($db, $runId, $run['type'], $options, $itemCount, $preventedDoublePayoutCount);

            $db->commit();

            $msg = 'Data payroll berhasil di-hitung ulang (Regenerate).';
            if ($preventedDoublePayoutCount > 0) {
                $msg .= "<br><br><small><i class='bi bi-info-circle'></i> <strong>Info:</strong> Gaji Pokok dan Tunjangan bulanan untuk <strong>$preventedDoublePayoutCount karyawan</strong> di-nol-kan secara otomatis oleh sistem karena terdeteksi sudah pernah diberikan pada slip sebelumnya di bulan yang sama atau belum waktunya diberikan.</small>";
            }
            $_SESSION['flash_success'] = $msg;
            redirect('/payroll/preview?id=' . $runId);

        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $_SESSION['flash_error'] = 'Gagal regenerate payroll: ' . $e->getMessage();
            redirect('/payroll/preview?id=' . $runId);
        }
    }

    private function generatePayrollItems(\PDO $db, int $runId, string $type, array $options, int &$itemCount, int &$preventedDoublePayoutCount): void
    {
        $targetTypesArr = [];
        if (isset($options['borongan'])) $targetTypesArr[] = "'borongan'";
        if (isset($options['bulanan'])) $targetTypesArr[] = "'bulanan'";
        $targetTypesStr = implode(',', $targetTypesArr);
        
        $empStmt = $db->query("
            SELECT e.*, m.gaji_pokok 
            FROM karyawan e
            LEFT JOIN pengaturan_gaji_bulanan m ON e.id = m.id_karyawan
            WHERE e.aktif = 1 AND e.tipe_gaji IN ($targetTypesStr)
        ");
        $employees = $empStmt->fetchAll();

        $debtModel = new Debt();
        $penarikanModel = new PenarikanGaji();
        
        foreach ($employees as $emp) {
            $baseSalary = 0.00;
            
            $empStart = '';
            $empEnd = '';
            
            if ($emp['tipe_gaji'] === 'borongan') {
                $empStart = $options['borongan']['start'];
                $empEnd = $options['borongan']['end'];
            } elseif ($emp['tipe_gaji'] === 'bulanan') {
                $empStart = $options['bulanan']['start'];
                $empEnd = $options['bulanan']['end'];
            }

            $attStmt = $db->prepare("
                SELECT COUNT(*) as days, SUM(lembur_nominal) as lembur_bulanan
                FROM absensi 
                WHERE id_karyawan = ? AND date BETWEEN ? AND ? 
                  AND hadir = 1 AND id_penggajian IS NULL
            ");
            $attStmt->execute([$emp['id'], $empStart, $empEnd]);
            $attData = $attStmt->fetch();
            $attendanceDays = (int)$attData['days'];
            
            $attendancePayTotal = $attendanceDays * (float)$emp['uang_kehadiran_harian'];

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
                $prodStmt->execute([$emp['id'], $empStart, $empEnd]);
                $prodData = $prodStmt->fetch();
                $prodPayTotal = (float)($prodData['total_reguler'] ?? 0);
                $overtimePayTotal = (float)($prodData['total_lembur'] ?? 0);
            } else if ($emp['tipe_gaji'] === 'bulanan') {
                $overtimePayTotal = (float)($attData['lembur_bulanan'] ?? 0);
            }

            // Kondisi skip karyawan kini dipindahkan ke bawah agar lebih akurat menghitung total pendapatan

            $monthlyAllowance = 0.00;
            
            if ($emp['tipe_gaji'] === 'bulanan') {
                $endDateObj = new \DateTime($empEnd);
                $nextWeekObj = clone $endDateObj;
                $nextWeekObj->modify('+7 days');
                $isEndOfWeekMonth = ($endDateObj->format('m') !== $nextWeekObj->format('m'));
                
                $startObj = new \DateTime($empStart);
                $diffDays = $startObj->diff($endDateObj)->days;
                $isFullMonth = $diffDays >= 20;

                if ($isEndOfWeekMonth || $isFullMonth) {
                    $currentMonth = $endDateObj->format('m');
                    $currentYear = $endDateObj->format('Y');
                    
                    $stmtCheck = $db->prepare("
                        SELECT 1 
                        FROM rincian_penggajian rp
                        JOIN penggajian p ON rp.id_penggajian = p.id
                        WHERE rp.id_karyawan = ? 
                          AND MONTH(p.periode_akhir) = ?
                          AND YEAR(p.periode_akhir) = ?
                          AND (rp.tunjangan_bulanan > 0 OR rp.gaji_pokok > 0)
                    ");
                    $stmtCheck->execute([$emp['id'], $currentMonth, $currentYear]);
                    $alreadyGotAllowance = $stmtCheck->fetchColumn();
                    
                    if (!$alreadyGotAllowance) {
                        $monthlyAllowance = (float)($emp['tunjangan_bulanan'] ?? 0);
                        $baseSalary = (float)($emp['gaji_pokok'] ?? 0);
                    } else {
                        if ((float)($emp['tunjangan_bulanan'] ?? 0) > 0 || (float)($emp['gaji_pokok'] ?? 0) > 0) {
                            $preventedDoublePayoutCount++;
                        }
                    }
                }
            }

            $penarikanTotal = 0.00;
            $penarikanDetails = [];
            
            $pendingPenarikan = $penarikanModel->getPendingByEmployee($emp['id'], $empEnd);
            foreach ($pendingPenarikan as $p) {
                $penarikanTotal += (float)$p['nominal'];
                $penarikanDetails[] = [
                    'id_penarikan' => $p['id'],
                    'tanggal' => $p['tanggal'],
                    'nominal' => $p['nominal']
                ];
            }

            $netBeforeDeductions = $baseSalary + $attendancePayTotal + $prodPayTotal + $overtimePayTotal + $monthlyAllowance;

            // Jika karyawan tidak punya penghasilan sama sekali di periode ini dan juga tidak punya potongan/kasbon, lewati (jangan buat slip 0 rupiah)
            if ($netBeforeDeductions == 0 && $penarikanTotal == 0) {
                continue;
            }
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

            $netSalary = $baseSalary + $attendancePayTotal + $prodPayTotal + $overtimePayTotal + $monthlyAllowance - $debtsDeductionTotal - $penarikanTotal;
            if ($netSalary < 0) {
                $netSalary = 0;
            }

            $detailsJson = json_encode([
                'debts' => $debtsDetails,
                'penarikan' => $penarikanDetails,
                'kasbon_adjusted_down' => $kasbonAdjustedDown
            ]);

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

        if (isset($options['borongan'])) {
            $bStart = $options['borongan']['start'];
            $bEnd = $options['borongan']['end'];
            $db->prepare("UPDATE absensi SET id_penggajian = ? WHERE id_penggajian IS NULL AND date BETWEEN ? AND ? AND id_karyawan IN (SELECT id_karyawan FROM rincian_penggajian WHERE id_penggajian = ? AND is_excluded = 0 AND id_karyawan IN (SELECT id FROM karyawan WHERE tipe_gaji = 'borongan'))")->execute([$runId, $bStart, $bEnd, $runId]);
            $db->prepare("UPDATE produksi SET id_penggajian = ? WHERE id_penggajian IS NULL AND date BETWEEN ? AND ? AND id_karyawan IN (SELECT id_karyawan FROM rincian_penggajian WHERE id_penggajian = ? AND is_excluded = 0 AND id_karyawan IN (SELECT id FROM karyawan WHERE tipe_gaji = 'borongan'))")->execute([$runId, $bStart, $bEnd, $runId]);
            $db->prepare("UPDATE penarikan_gaji SET id_penggajian = ? WHERE id_penggajian IS NULL AND tanggal <= ? AND id_karyawan IN (SELECT id_karyawan FROM rincian_penggajian WHERE id_penggajian = ? AND is_excluded = 0 AND id_karyawan IN (SELECT id FROM karyawan WHERE tipe_gaji = 'borongan'))")->execute([$runId, $bEnd, $runId]);
        }
        
        if (isset($options['bulanan'])) {
            $mStart = $options['bulanan']['start'];
            $mEnd = $options['bulanan']['end'];
            $db->prepare("UPDATE absensi SET id_penggajian = ? WHERE id_penggajian IS NULL AND date BETWEEN ? AND ? AND id_karyawan IN (SELECT id_karyawan FROM rincian_penggajian WHERE id_penggajian = ? AND is_excluded = 0 AND id_karyawan IN (SELECT id FROM karyawan WHERE tipe_gaji = 'bulanan'))")->execute([$runId, $mStart, $mEnd, $runId]);
            $db->prepare("UPDATE produksi SET id_penggajian = ? WHERE id_penggajian IS NULL AND date BETWEEN ? AND ? AND id_karyawan IN (SELECT id_karyawan FROM rincian_penggajian WHERE id_penggajian = ? AND is_excluded = 0 AND id_karyawan IN (SELECT id FROM karyawan WHERE tipe_gaji = 'bulanan'))")->execute([$runId, $mStart, $mEnd, $runId]);
            $db->prepare("UPDATE penarikan_gaji SET id_penggajian = ? WHERE id_penggajian IS NULL AND tanggal <= ? AND id_karyawan IN (SELECT id_karyawan FROM rincian_penggajian WHERE id_penggajian = ? AND is_excluded = 0 AND id_karyawan IN (SELECT id FROM karyawan WHERE tipe_gaji = 'bulanan'))")->execute([$runId, $mEnd, $runId]);
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
            ORDER BY e.tipe_gaji ASC, e.name ASC
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

        public function exportCombinedPdf(): void
    {
        checkPermission('payroll');
        $runId = (int)($_GET['id'] ?? 0);
        $db = getDB();

        $stmtRun = $db->prepare("SELECT * FROM penggajian WHERE id = ?");
        $stmtRun->execute([$runId]);
        $run = $stmtRun->fetch();

        if (!$run) {
            $_SESSION['flash_error'] = 'Data payroll tidak valid.';
            redirect('/payroll');
            return;
        }

        $stmtItems = $db->prepare("
            SELECT pi.*, e.name as employee_name, e.tipe_gaji
            FROM rincian_penggajian pi
            JOIN karyawan e ON pi.id_karyawan = e.id
            WHERE pi.id_penggajian = ? AND pi.is_excluded = 0
            ORDER BY e.tipe_gaji ASC, e.name ASC
        ");
        $stmtItems->execute([$runId]);
        $items = $stmtItems->fetchAll();

        if (empty($items)) {
            $_SESSION['flash_error'] = 'Tidak ada slip gaji untuk periode ini.';
            redirect('/payroll/preview?id=' . $runId);
            return;
        }

        ob_start();
        include APP_ROOT . '/app/Views/reports/pdf_rekap.php';
        $htmlRekap = ob_get_clean();

        ob_start();
        include APP_ROOT . '/app/Views/payroll/pdf_slips_batch.php';
        $htmlSlips = ob_get_clean();

        $htmlSlips = preg_replace('/<!DOCTYPE html>/i', '', $htmlSlips);
        $htmlSlips = preg_replace('/<html>/i', '', $htmlSlips);
        $htmlSlips = preg_replace('/<\/html>/i', '', $htmlSlips);
        
        preg_match('/<style>(.*?)<\/style>/is', $htmlSlips, $matches);
        $slipsStyle = $matches[1] ?? '';
        
        $htmlSlips = preg_replace('/<head>.*?<\/head>/is', '', $htmlSlips);
        $htmlSlips = preg_replace('/<body>/i', '', $htmlSlips);
        $htmlSlips = preg_replace('/<\/body>/i', '', $htmlSlips);

        $htmlRekap = str_replace('</style>', $slipsStyle . '</style>', $htmlRekap);

        $pageBreak = '<div style="page-break-before: always; clear: both;"></div>';
        $htmlRekap = str_replace('</body>', $pageBreak . $htmlSlips . '</body>', $htmlRekap);

        $runName = $run['name'] ?: ('Run ' . $runId);
        $safeRunName = preg_replace('/[^A-Za-z0-9_\- ]/', '', $runName);
        $statusStr = $run['status'] === 'draft' ? 'DRAFT ' : '';
        $filename = 'Laporan Lengkap ' . $statusStr . $safeRunName;
        
        streamPdf($htmlRekap, $filename, 'A4', 'landscape');
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
            ORDER BY e.tipe_gaji ASC, e.name ASC
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
        $stmtItem = $db->prepare("
            SELECT r.id_karyawan, k.tipe_gaji 
            FROM rincian_penggajian r 
            JOIN karyawan k ON r.id_karyawan = k.id 
            WHERE r.id = ? AND r.id_penggajian = ?
        ");
        $stmtItem->execute([$itemId, $runId]);
        $item = $stmtItem->fetch();
        
        if (!$item) {
            $_SESSION['flash_error'] = 'Data rincian tidak ditemukan.';
            redirect('/payroll/preview?id=' . $runId);
            return;
        }
        
        $stmtRun = $db->prepare("SELECT type, periode_awal, periode_akhir, options_json FROM penggajian WHERE id = ?");
        $stmtRun->execute([$runId]);
        $run = $stmtRun->fetch();
        
        $startDate = $run['periode_awal'];
        $endDate = $run['periode_akhir'];
        $options = json_decode($run['options_json'] ?? '[]', true) ?: [];
        
        if ($run['type'] === 'gabungan') {
            if ($item['tipe_gaji'] === 'borongan' && isset($options['borongan'])) {
                $startDate = $options['borongan']['start'];
                $endDate = $options['borongan']['end'];
            } elseif ($item['tipe_gaji'] === 'bulanan' && isset($options['bulanan'])) {
                $startDate = $options['bulanan']['start'];
                $endDate = $options['bulanan']['end'];
            }
        }

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
                // Batal Kecuali: Kunci kembali absensinya ke draf ini dengan tanggal spesifik
                $db->prepare("UPDATE absensi SET id_penggajian = ? WHERE id_penggajian IS NULL AND id_karyawan = ? AND date BETWEEN ? AND ?")
                   ->execute([$runId, $empId, $startDate, $endDate]);
                $db->prepare("UPDATE produksi SET id_penggajian = ? WHERE id_penggajian IS NULL AND id_karyawan = ? AND date BETWEEN ? AND ?")
                   ->execute([$runId, $empId, $startDate, $endDate]);
                $db->prepare("UPDATE penarikan_gaji SET id_penggajian = ? WHERE id_penggajian IS NULL AND id_karyawan = ? AND tanggal <= ?")
                   ->execute([$runId, $empId, $endDate]);
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
