<?php
function refactorController() {
    $content = file_get_contents('app/Controllers/PayrollController.php');
    
    // Pecah jadi 3 bagian: Sebelum store(), sesudah regenerate(), lalu store & regenerate & generatePayrollItems.
    
    $startStorePos = strpos($content, 'public function store(): void');
    $beforeStore = substr($content, 0, $startStorePos);
    
    $endRegeneratePos = strpos($content, 'public function exportPdf(): void');
    $afterRegenerate = substr($content, $endRegeneratePos);
    
    $newMethods = "
    public function store(): void
    {
        checkPermission('payroll');
        validateCsrfToken();

        \$types = \$_POST['types'] ?? [];
        if (empty(\$types) || !is_array(\$types)) {
            \$_SESSION['flash_error'] = 'Pilih minimal satu tipe karyawan (Borongan atau Bulanan).';
            redirect('/payroll/create');
            return;
        }

        \$isWeekly = in_array('weekly', \$types);
        \$isMonthly = in_array('monthly', \$types);
        
        \$type = 'mixed';
        if (\$isWeekly && !\$isMonthly) \$type = 'weekly';
        if (!\$isWeekly && \$isMonthly) \$type = 'monthly';

        \$options = [];
        \$allStarts = [];
        \$allEnds = [];

        if (\$isWeekly) {
            \$wStart = \$_POST['periode_awal_weekly'] ?? '';
            \$wEnd = \$_POST['periode_akhir_weekly'] ?? '';
            if (!\$wStart || !\$wEnd || \$wStart > \$wEnd) {
                \$_SESSION['flash_error'] = 'Rentang tanggal Borongan tidak valid.';
                redirect('/payroll/create');
                return;
            }
            \$options['borongan'] = ['start' => \$wStart, 'end' => \$wEnd];
            \$allStarts[] = \$wStart;
            \$allEnds[] = \$wEnd;
        }

        if (\$isMonthly) {
            \$mStart = \$_POST['periode_awal_monthly'] ?? '';
            \$mEnd = \$_POST['periode_akhir_monthly'] ?? '';
            if (!\$mStart || !\$mEnd || \$mStart > \$mEnd) {
                \$_SESSION['flash_error'] = 'Rentang tanggal Bulanan tidak valid.';
                redirect('/payroll/create');
                return;
            }
            \$options['bulanan'] = ['start' => \$mStart, 'end' => \$mEnd];
            \$allStarts[] = \$mStart;
            \$allEnds[] = \$mEnd;
        }

        \$period_start = min(\$allStarts);
        \$period_end = max(\$allEnds);
        \$options_json = json_encode(\$options);

        \$name = trim(\$_POST['name'] ?? '');
        if (\$name === '') {
            \$name = 'Gaji ' . ucfirst(\$type) . ' ' . date('d M Y', strtotime(\$period_start));
        } else {
            \$name = ucwords(\$name);
        }

        \$db = getDB();

        \$checkTypes = [];
        if (\$isWeekly) \$checkTypes[] = 'weekly';
        if (\$isMonthly) \$checkTypes[] = 'monthly';
        \$checkTypes[] = 'mixed';
        \$placeholders = implode(',', array_fill(0, count(\$checkTypes), '?'));

        \$draftExists = \$db->prepare(\"SELECT id FROM penggajian WHERE type IN (\$placeholders) AND status = 'draft'\");
        \$draftExists->execute(\$checkTypes);
        if (\$draft = \$draftExists->fetch()) {
            \$_SESSION['flash_error'] = 'Masih ada Draft Payroll yang belum diselesaikan. Harap Approve atau Hapus draft tersebut terlebih dahulu.';
            redirect('/payroll');
            return;
        }

        \$overlapExists = \$db->prepare(\"
            SELECT id, periode_awal, periode_akhir 
            FROM penggajian 
            WHERE type IN (\$placeholders)
              AND status = 'approved'
              AND periode_awal <= ? 
              AND periode_akhir >= ?
        \");
        \$params = array_merge(\$checkTypes, [\$period_end, \$period_start]);
        \$overlapExists->execute(\$params);
        if (\$overlap = \$overlapExists->fetch()) {
            \$_SESSION['flash_error'] = 'Gagal! Terdapat bentrok tanggal dengan payroll yang sudah disetujui (Periode: ' . date('d M Y', strtotime(\$overlap['periode_awal'])) . ' s/d ' . date('d M Y', strtotime(\$overlap['periode_akhir'])) . '). Pastikan rentang tanggal yang Anda buat tidak tumpang tindih dengan data sebelumnya.';
            redirect('/payroll/create');
            return;
        }

        try {
            \$db->beginTransaction();

            \$stmt = \$db->prepare(\"INSERT INTO penggajian (periode_awal, periode_akhir, type, status, name, options_json) VALUES (?, ?, ?, 'draft', ?, ?)\");
            \$stmt->execute([\$period_start, \$period_end, \$type, \$name, \$options_json]);
            \$runId = (int)\$db->lastInsertId();

            \$itemCount = 0;
            \$preventedDoublePayoutCount = 0;

            \$this->generatePayrollItems(\$db, \$runId, \$type, \$options, \$itemCount, \$preventedDoublePayoutCount);

            if (\$itemCount === 0) {
                \$db->rollBack();
                \$_SESSION['flash_error'] = 'Tidak ada data kehadiran/produksi yang belum di-approve untuk rentang tanggal tersebut.';
                redirect('/payroll');
                return;
            }

            \$db->commit();
            \$successMsg = 'Draft Payroll berhasil dibuat. Silakan periksa rincian sebelum menyetujui.';
            if (\$preventedDoublePayoutCount > 0) {
                \$successMsg .= \"<br><br><small><i class='bi bi-info-circle'></i> <strong>Info:</strong> Tunjangan bulanan untuk <strong>\$preventedDoublePayoutCount karyawan</strong> di-nol-kan secara otomatis oleh sistem karena terdeteksi sudah pernah diberikan pada slip sebelumnya di bulan yang sama.</small>\";
            }
            \$_SESSION['flash_success'] = \$successMsg;
            redirect('/payroll/preview?id=' . \$runId);

        } catch (\Exception \$e) {
            if (\$db->inTransaction()) {
                \$db->rollBack();
            }
            \$_SESSION['flash_error'] = 'Terjadi kesalahan sistem: ' . \$e->getMessage();
            redirect('/payroll');
        }
    }

    public function show(): void
    {
        checkPermission('payroll');
        \$id = (int)(\$_GET['id'] ?? 0);
        
        \$prModel = new PayrollRun();
        \$piModel = new PayrollItem();
        
        \$run = \$prModel->findById(\$id);
        if (!\$run) {
            \$_SESSION['flash_error'] = 'Payroll tidak ditemukan.';
            redirect('/payroll');
            return;
        }

        \$items = \$piModel->getByRunId(\$id);

        \$debtModel = new \App\Models\Debt();
        \$savingModel = new \App\Models\Saving();
        foreach (\$items as &\$item) {
            \$activeDebts = \$debtModel->getActiveDebtsByEmployeeId((int)\$item['id_karyawan']);
            \$item['total_active_kasbon'] = array_sum(array_column(\$activeDebts, 'sisa_nominal'));
            \$item['saldo_tabungan'] = \$savingModel->getBalance((int)\$item['id_karyawan']);
        }
        unset(\$item);

        view('payroll/preview', [
            'title' => 'Preview Payroll',
            'run' => \$run,
            'items' => \$items
        ]);
    }

    public function updateItem(): void
    {
        checkPermission('payroll');
        validateCsrfToken();
        
        \$itemId = (int)(\$_POST['item_id'] ?? 0);
        \$runId = (int)(\$_POST['run_id'] ?? 0);
        
        \$debtDeduction = (float)parseRupiah(\$_POST['total_potongan_kasbon'] ?? '0');
        \$otherAllowances = (float)parseRupiah(\$_POST['tunjangan_lain'] ?? '0');
        \$otherAllowancesNotes = trim(\$_POST['catatan_tunjangan_lain'] ?? '');
        \$otherDeductions = (float)parseRupiah(\$_POST['potongan_lain'] ?? '0');
        \$otherDeductionsNotes = trim(\$_POST['catatan_potongan_lain'] ?? '');
        \$tabunganSetor = (float)parseRupiah(\$_POST['potongan_tabungan'] ?? '0');
        \$tabunganTarik = (float)parseRupiah(\$_POST['penarikan_tabungan'] ?? '0');
        \$roundingAmount = (float)parseRupiah(\$_POST['nominal_pembulatan'] ?? '0');

        \$db = getDB();
        
        \$stmt = \$db->prepare(\"SELECT * FROM rincian_penggajian WHERE id = ? AND id_penggajian = ?\");
        \$stmt->execute([\$itemId, \$runId]);
        \$item = \$stmt->fetch();

        if (!\$item) {
            \$_SESSION['flash_error'] = 'Data tidak ditemukan.';
            redirect('/payroll/preview?id=' . \$runId);
            return;
        }

        \$details = json_decode(\$item['rincian_json'] ?? '[]', true) ?: [];

        if (\$tabunganTarik > 0) {
            \$savingModel = new \App\Models\Saving();
            \$saldoTabungan = \$savingModel->getBalance((int)\$item['id_karyawan']);
            if (\$tabunganTarik > \$saldoTabungan) {
                \$_SESSION['flash_error'] = 'Gagal Menyimpan! Penarikan tabungan (Rp ' . number_format(\$tabunganTarik, 0, ',', '.') . ') melebihi total saldo karyawan (Rp ' . number_format(\$saldoTabungan, 0, ',', '.') . ').';
                redirect('/payroll/preview?id=' . \$runId);
                return;
            }
        }
        
        if (\$debtDeduction > 0) {
            \$debtModel = new \App\Models\Debt();
            \$activeDebts = \$debtModel->getActiveDebtsByEmployeeId((int)\$item['id_karyawan']);
            \$totalActiveKasbon = array_sum(array_column(\$activeDebts, 'sisa_nominal'));
            
            if (\$debtDeduction > \$totalActiveKasbon) {
                \$_SESSION['flash_error'] = 'Gagal Menyimpan! Nominal potongan kasbon melebihi batas maksimal sisa hutang karyawan.';
                redirect('/payroll/preview?id=' . \$runId);
                return;
            }

            \$newDebtsDetails = [];
            \$remainingDeduct = \$debtDeduction;
            foreach (\$activeDebts as \$ad) {
                if (\$remainingDeduct <= 0) break;
                \$sisa = (float)\$ad['sisa_nominal'];
                \$potong = min(\$sisa, \$remainingDeduct);
                \$newDebtsDetails[] = [
                    'id_kasbon' => \$ad['id'],
                    'nominal' => \$potong
                ];
                \$remainingDeduct -= \$potong;
            }
            \$details['debts'] = \$newDebtsDetails;
        } else {
            \$details['debts'] = []; // Kosongkan jika 0
        }
        \$newDetailsJson = json_encode(\$details);

        // Recalculate gaji_bersih
        \$base = (float)\$item['gaji_pokok'];
        \$att = (float)\$item['total_uang_kehadiran'];
        \$prod = (float)\$item['total_upah_produksi'];
        \$lembur = (float)\$item['total_upah_lembur'];
        \$monthly = (float)\$item['tunjangan_bulanan'];
        \$penarikan = (float)(\$item['total_penarikan_gaji'] ?? 0);

        \$newNet = \$base + \$att + \$prod + \$lembur + \$monthly + \$otherAllowances - \$debtDeduction - \$otherDeductions - \$tabunganSetor + \$tabunganTarik - \$penarikan + \$roundingAmount;
        
        if (\$newNet < 0) {
            \$_SESSION['flash_error'] = 'Gagal Menyimpan! Total potongan melebihi total pendapatan. Gaji tidak boleh minus.';
            redirect('/payroll/preview?id=' . \$runId);
            return;
        }

        \$update = \$db->prepare(\"
            UPDATE rincian_penggajian 
            SET total_potongan_kasbon = ?, rincian_json = ?,
                tunjangan_lain = ?, catatan_tunjangan_lain = ?, 
                potongan_lain = ?, catatan_potongan_lain = ?, 
                potongan_tabungan = ?, penarikan_tabungan = ?,
                nominal_pembulatan = ?, gaji_bersih = ?
            WHERE id = ?
        \");
        \$update->execute([
            \$debtDeduction, \$newDetailsJson,
            \$otherAllowances, \$otherAllowancesNotes, 
            \$otherDeductions, \$otherDeductionsNotes,
            \$tabunganSetor, \$tabunganTarik,
            \$roundingAmount, \$newNet, \$itemId
        ]);

        \$_SESSION['flash_success'] = 'Penyesuaian gaji berhasil disimpan.';
        redirect('/payroll/preview?id=' . \$runId);
    }

    public function delete(): void
    {
        checkPermission('payroll');
        validateCsrfToken();

        \$runId = (int)(\$_POST['id'] ?? 0);
        
        \$db = getDB();
        \$prModel = new PayrollRun();
        \$run = \$prModel->findById(\$runId);

        if (!\$run || \$run['status'] !== 'draft') {
            \$_SESSION['flash_error'] = 'Payroll tidak valid atau sudah disetujui (tidak bisa dihapus).';
            redirect('/payroll');
            return;
        }

        try {
            \$db->beginTransaction();

            // Buka kembali kunci Absensi, Produksi, dan Penarikan
            \$db->prepare(\"UPDATE absensi SET id_penggajian = NULL WHERE id_penggajian = ?\")->execute([\$runId]);
            \$db->prepare(\"UPDATE produksi SET id_penggajian = NULL WHERE id_penggajian = ?\")->execute([\$runId]);
            \$db->prepare(\"UPDATE penarikan_gaji SET id_penggajian = NULL WHERE id_penggajian = ?\")->execute([\$runId]);

            \$db->prepare(\"DELETE FROM rincian_penggajian WHERE id_penggajian = ?\")->execute([\$runId]);
            \$prModel->delete(\$runId);

            \$db->commit();
            \$_SESSION['flash_success'] = 'Draft Payroll berhasil dihapus.';
            redirect('/payroll');

        } catch (\Throwable \$e) {
            if (\$db->inTransaction()) {
                try {
                    \$db->rollBack();
                } catch (\Throwable \$t) {}
            }
            \$_SESSION['flash_error'] = 'Gagal menghapus payroll: ' . \$e->getMessage();
            redirect('/payroll/preview?id=' . \$runId);
        }
    }

    public function approve(): void
    {
        checkPermission('payroll');
        validateCsrfToken();

        \$runId = (int)(\$_POST['id'] ?? 0);
        
        \$db = getDB();
        \$prModel = new PayrollRun();
        \$run = \$prModel->findById(\$runId);

        if (!\$run || \$run['status'] === 'approved') {
            \$_SESSION['flash_error'] = 'Payroll tidak valid atau sudah disetujui.';
            redirect('/payroll');
            return;
        }

        try {
            \$db->beginTransaction();

            \$user = currentUser();
            
            \$stmt = \$db->prepare(\"UPDATE penggajian SET status = 'approved', disetujui_pada = NOW(), disetujui_oleh = ? WHERE id = ?\");
            \$stmt->execute([\$user['id'], \$runId]);

            \$piModel = new PayrollItem();
            \$items = \$piModel->getByRunId(\$runId);
            \$debtModel = new Debt();
            \$savingModel = new \App\Models\Saving();

            foreach (\$items as \$item) {
                if (\$item['is_excluded'] == 1) continue;

                if (!empty(\$item['rincian_json'])) {
                    \$details = json_decode(\$item['rincian_json'], true);
                    
                    if (\$item['total_potongan_kasbon'] > 0 && isset(\$details['debts']) && is_array(\$details['debts'])) {
                        foreach (\$details['debts'] as \$d) {
                            \$debtModel->addDeduction(
                                (int)\$d['id_kasbon'], 
                                (float)\$d['nominal'], 
                                date('Y-m-d'), 
                                'payroll', 
                                \$runId, 
                                \$item['id']
                            );
                        }
                    }
                }

                if ((float)\$item['potongan_tabungan'] > 0) {
                    \$savingModel->addTransaction((int)\$item['id_karyawan'], 'deposit', (float)\$item['potongan_tabungan'], date('Y-m-d'), 'Setoran via Payroll #' . \$runId, \$item['id']);
                }
                if ((float)\$item['penarikan_tabungan'] > 0) {
                    \$savingModel->addTransaction((int)\$item['id_karyawan'], 'withdrawal', (float)\$item['penarikan_tabungan'], date('Y-m-d'), 'Penarikan via Payroll #' . \$runId, \$item['id']);
                }
            }

            \$db->commit();
            \$_SESSION['flash_success'] = 'Payroll berhasil disetujui dan dikunci permanen.';
            redirect('/payroll/preview?id=' . \$runId);

        } catch (\Exception \$e) {
            \$db->rollBack();
            \$_SESSION['flash_error'] = 'Terjadi kesalahan saat approve: ' . \$e->getMessage();
            redirect('/payroll/preview?id=' . \$runId);
        }
    }

    public function regenerate(): void
    {
        checkPermission('payroll');
        validateCsrfToken();

        \$runId = (int)(\$_POST['id'] ?? 0);
        
        \$db = getDB();
        \$prModel = new PayrollRun();
        \$run = \$prModel->findById(\$runId);

        if (!\$run || \$run['status'] !== 'draft') {
            \$_SESSION['flash_error'] = 'Payroll tidak valid atau sudah disetujui (tidak bisa di-regenerate).';
            redirect('/payroll');
            return;
        }

        try {
            \$db->beginTransaction();

            \$db->prepare(\"UPDATE absensi SET id_penggajian = NULL WHERE id_penggajian = ?\")->execute([\$runId]);
            \$db->prepare(\"UPDATE produksi SET id_penggajian = NULL WHERE id_penggajian = ?\")->execute([\$runId]);
            \$db->prepare(\"UPDATE penarikan_gaji SET id_penggajian = NULL WHERE id_penggajian = ?\")->execute([\$runId]);

            \$db->prepare(\"DELETE FROM rincian_penggajian WHERE id_penggajian = ?\")->execute([\$runId]);

            \$options = json_decode(\$run['options_json'] ?? '[]', true) ?: [];
            if (empty(\$options)) {
                if (\$run['type'] === 'weekly') {
                    \$options['borongan'] = ['start' => \$run['periode_awal'], 'end' => \$run['periode_akhir']];
                } else if (\$run['type'] === 'monthly') {
                    \$options['bulanan'] = ['start' => \$run['periode_awal'], 'end' => \$run['periode_akhir']];
                }
            }

            \$itemCount = 0;
            \$preventedDoublePayoutCount = 0;

            \$this->generatePayrollItems(\$db, \$runId, \$run['type'], \$options, \$itemCount, \$preventedDoublePayoutCount);

            \$db->commit();

            \$msg = 'Data payroll berhasil di-hitung ulang (Regenerate).';
            if (\$preventedDoublePayoutCount > 0) {
                \$msg .= \"<br><br><small><i class='bi bi-info-circle'></i> <strong>Info:</strong> Tunjangan bulanan untuk <strong>\$preventedDoublePayoutCount karyawan</strong> di-nol-kan karena sudah pernah diberikan sebelumnya di bulan yang sama.</small>\";
            }
            \$_SESSION['flash_success'] = \$msg;
            redirect('/payroll/preview?id=' . \$runId);

        } catch (\Exception \$e) {
            if (\$db->inTransaction()) {
                \$db->rollBack();
            }
            \$_SESSION['flash_error'] = 'Gagal regenerate payroll: ' . \$e->getMessage();
            redirect('/payroll/preview?id=' . \$runId);
        }
    }

    private function generatePayrollItems(\PDO \$db, int \$runId, string \$type, array \$options, int &\$itemCount, int &\$preventedDoublePayoutCount): void
    {
        \$targetTypesArr = [];
        if (isset(\$options['borongan'])) \$targetTypesArr[] = \"'borongan'\";
        if (isset(\$options['bulanan'])) \$targetTypesArr[] = \"'bulanan'\";
        \$targetTypesStr = implode(',', \$targetTypesArr);
        
        \$empStmt = \$db->query(\"
            SELECT e.*, m.gaji_pokok, m.tunjangan_bulanan 
            FROM karyawan e
            LEFT JOIN pengaturan_gaji_bulanan m ON e.id = m.id_karyawan
            WHERE e.aktif = 1 AND e.tipe_gaji IN (\$targetTypesStr)
        \");
        \$employees = \$empStmt->fetchAll();

        \$debtModel = new Debt();
        \$penarikanModel = new PenarikanGaji();
        
        foreach (\$employees as \$emp) {
            \$baseSalary = (float)(\$emp['gaji_pokok'] ?? 0);
            
            \$empStart = '';
            \$empEnd = '';
            
            if (\$emp['tipe_gaji'] === 'borongan') {
                \$empStart = \$options['borongan']['start'];
                \$empEnd = \$options['borongan']['end'];
            } elseif (\$emp['tipe_gaji'] === 'bulanan') {
                \$empStart = \$options['bulanan']['start'];
                \$empEnd = \$options['bulanan']['end'];
            }

            \$attStmt = \$db->prepare(\"
                SELECT COUNT(*) as days, SUM(lembur_nominal) as lembur_bulanan
                FROM absensi 
                WHERE id_karyawan = ? AND date BETWEEN ? AND ? 
                  AND hadir = 1 AND id_penggajian IS NULL
            \");
            \$attStmt->execute([\$emp['id'], \$empStart, \$empEnd]);
            \$attData = \$attStmt->fetch();
            \$attendanceDays = (int)\$attData['days'];
            
            \$attendancePayTotal = \$attendanceDays * (float)\$emp['uang_kehadiran_harian'];

            \$prodPayTotal = 0.00;
            \$overtimePayTotal = 0.00;
            if (\$emp['tipe_gaji'] === 'borongan') {
                \$prodStmt = \$db->prepare(\"
                    SELECT 
                        SUM(p.kuantitas * pg.harga_per_bungkus) as total_reguler,
                        SUM(p.lembur_kuantitas * pg.harga_per_bungkus) as total_lembur
                    FROM produksi p
                    JOIN produk prod ON p.id_produk = prod.id
                    JOIN kelompok_harga_produk pg ON prod.id_kelompok_harga = pg.id
                    WHERE p.id_karyawan = ? AND p.date BETWEEN ? AND ?
                      AND p.id_penggajian IS NULL
                \");
                \$prodStmt->execute([\$emp['id'], \$empStart, \$empEnd]);
                \$prodData = \$prodStmt->fetch();
                \$prodPayTotal = (float)(\$prodData['total_reguler'] ?? 0);
                \$overtimePayTotal = (float)(\$prodData['total_lembur'] ?? 0);
            } else if (\$emp['tipe_gaji'] === 'bulanan') {
                \$overtimePayTotal = (float)(\$attData['lembur_bulanan'] ?? 0);
            }

            if (\$attendanceDays == 0 && \$prodPayTotal == 0 && \$overtimePayTotal == 0 && \$emp['tipe_gaji'] !== 'bulanan') {
                continue; 
            }

            \$monthlyAllowance = 0.00;
            
            if (\$emp['tipe_gaji'] === 'bulanan') {
                \$endDateObj = new \DateTime(\$empEnd);
                \$nextWeekObj = clone \$endDateObj;
                \$nextWeekObj->modify('+7 days');
                \$isEndOfWeekMonth = (\$endDateObj->format('m') !== \$nextWeekObj->format('m'));
                
                \$startObj = new \DateTime(\$empStart);
                \$diffDays = \$startObj->diff(\$endDateObj)->days;
                \$isFullMonth = \$diffDays >= 20;

                if (\$isEndOfWeekMonth || \$isFullMonth) {
                    \$currentMonth = \$endDateObj->format('m');
                    \$currentYear = \$endDateObj->format('Y');
                    
                    \$stmtCheck = \$db->prepare(\"
                        SELECT 1 
                        FROM rincian_penggajian rp
                        JOIN penggajian p ON rp.id_penggajian = p.id
                        WHERE rp.id_karyawan = ? 
                          AND MONTH(p.periode_akhir) = ?
                          AND YEAR(p.periode_akhir) = ?
                          AND rp.tunjangan_bulanan > 0
                    \");
                    \$stmtCheck->execute([\$emp['id'], \$currentMonth, \$currentYear]);
                    \$alreadyGotAllowance = \$stmtCheck->fetchColumn();
                    
                    if (!\$alreadyGotAllowance) {
                        \$monthlyAllowance = (float)(\$emp['tunjangan_bulanan'] ?? 0);
                    } else {
                        if ((float)(\$emp['tunjangan_bulanan'] ?? 0) > 0) {
                            \$preventedDoublePayoutCount++;
                        }
                    }
                }
            }

            \$penarikanTotal = 0.00;
            \$penarikanDetails = [];
            
            \$pendingPenarikan = \$penarikanModel->getPendingByEmployee(\$emp['id'], \$empEnd);
            foreach (\$pendingPenarikan as \$p) {
                \$penarikanTotal += (float)\$p['nominal'];
                \$penarikanDetails[] = [
                    'id_penarikan' => \$p['id'],
                    'tanggal' => \$p['tanggal'],
                    'nominal' => \$p['nominal']
                ];
            }

            \$netBeforeDeductions = \$baseSalary + \$attendancePayTotal + \$prodPayTotal + \$overtimePayTotal + \$monthlyAllowance;
            \$maxAvailableForDebts = \$netBeforeDeductions - \$penarikanTotal;
            if (\$maxAvailableForDebts < 0) \$maxAvailableForDebts = 0;

            \$activeDebts = \$debtModel->getActiveDebtsByEmployeeId(\$emp['id']);
            \$debtsDeductionTotal = 0.00;
            \$debtsDetails = [];
            \$kasbonAdjustedDown = false;
            
            foreach (\$activeDebts as \$debt) {
                \$deduction = (float)\$debt['potongan_bawaan'];
                \$remaining = (float)\$debt['sisa_nominal'];
                if (\$deduction > \$remaining) {
                    \$deduction = \$remaining;
                }
                if (\$deduction > \$maxAvailableForDebts) {
                    \$deduction = \$maxAvailableForDebts;
                    \$kasbonAdjustedDown = true;
                }
                if (\$deduction > 0) {
                    \$debtsDeductionTotal += \$deduction;
                    \$maxAvailableForDebts -= \$deduction;
                    \$debtsDetails[] = [
                        'id_kasbon' => \$debt['id'],
                        'keterangan' => \$debt['keterangan'],
                        'nominal' => \$deduction
                    ];
                }
            }

            \$netSalary = \$baseSalary + \$attendancePayTotal + \$prodPayTotal + \$overtimePayTotal + \$monthlyAllowance - \$debtsDeductionTotal - \$penarikanTotal;
            if (\$netSalary < 0) {
                \$netSalary = 0;
            }

            \$detailsJson = json_encode([
                'debts' => \$debtsDetails,
                'penarikan' => \$penarikanDetails,
                'kasbon_adjusted_down' => \$kasbonAdjustedDown
            ]);

            \$insertItem = \$db->prepare(\"
                INSERT INTO rincian_penggajian (
                    id_penggajian, id_karyawan, gaji_pokok, hari_hadir, total_uang_kehadiran,
                    total_upah_produksi, total_upah_lembur, tunjangan_bulanan, total_potongan_kasbon, total_penarikan_gaji, gaji_bersih, rincian_json
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            \");
            \$insertItem->execute([
                \$runId, \$emp['id'], \$baseSalary, \$attendanceDays, \$attendancePayTotal,
                \$prodPayTotal, \$overtimePayTotal, \$monthlyAllowance, \$debtsDeductionTotal, \$penarikanTotal, \$netSalary, \$detailsJson
            ]);
            
            \$itemCount++;
        }

        if (isset(\$options['borongan'])) {
            \$bStart = \$options['borongan']['start'];
            \$bEnd = \$options['borongan']['end'];
            \$db->prepare(\"UPDATE absensi SET id_penggajian = ? WHERE id_penggajian IS NULL AND date BETWEEN ? AND ? AND id_karyawan IN (SELECT id_karyawan FROM rincian_penggajian WHERE id_penggajian = ? AND is_excluded = 0 AND id_karyawan IN (SELECT id FROM karyawan WHERE tipe_gaji = 'borongan'))\")->execute([\$runId, \$bStart, \$bEnd, \$runId]);
            \$db->prepare(\"UPDATE produksi SET id_penggajian = ? WHERE id_penggajian IS NULL AND date BETWEEN ? AND ? AND id_karyawan IN (SELECT id_karyawan FROM rincian_penggajian WHERE id_penggajian = ? AND is_excluded = 0 AND id_karyawan IN (SELECT id FROM karyawan WHERE tipe_gaji = 'borongan'))\")->execute([\$runId, \$bStart, \$bEnd, \$runId]);
            \$db->prepare(\"UPDATE penarikan_gaji SET id_penggajian = ? WHERE id_penggajian IS NULL AND tanggal <= ? AND id_karyawan IN (SELECT id_karyawan FROM rincian_penggajian WHERE id_penggajian = ? AND is_excluded = 0 AND id_karyawan IN (SELECT id FROM karyawan WHERE tipe_gaji = 'borongan'))\")->execute([\$runId, \$bEnd, \$runId]);
        }
        
        if (isset(\$options['bulanan'])) {
            \$mStart = \$options['bulanan']['start'];
            \$mEnd = \$options['bulanan']['end'];
            \$db->prepare(\"UPDATE absensi SET id_penggajian = ? WHERE id_penggajian IS NULL AND date BETWEEN ? AND ? AND id_karyawan IN (SELECT id_karyawan FROM rincian_penggajian WHERE id_penggajian = ? AND is_excluded = 0 AND id_karyawan IN (SELECT id FROM karyawan WHERE tipe_gaji = 'bulanan'))\")->execute([\$runId, \$mStart, \$mEnd, \$runId]);
            \$db->prepare(\"UPDATE produksi SET id_penggajian = ? WHERE id_penggajian IS NULL AND date BETWEEN ? AND ? AND id_karyawan IN (SELECT id_karyawan FROM rincian_penggajian WHERE id_penggajian = ? AND is_excluded = 0 AND id_karyawan IN (SELECT id FROM karyawan WHERE tipe_gaji = 'bulanan'))\")->execute([\$runId, \$mStart, \$mEnd, \$runId]);
            \$db->prepare(\"UPDATE penarikan_gaji SET id_penggajian = ? WHERE id_penggajian IS NULL AND tanggal <= ? AND id_karyawan IN (SELECT id_karyawan FROM rincian_penggajian WHERE id_penggajian = ? AND is_excluded = 0 AND id_karyawan IN (SELECT id FROM karyawan WHERE tipe_gaji = 'bulanan'))\")->execute([\$runId, \$mEnd, \$runId]);
        }
    }
";
    
    file_put_contents('app/Controllers/PayrollController.php', $beforeStore . $newMethods . "\n" . $afterRegenerate);
    echo "Refactored PayrollController.\n";
}
refactorController();
?>
