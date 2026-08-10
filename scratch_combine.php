<?php
$c = file_get_contents('app/Controllers/PayrollController.php');

$newMethod = <<<EOT
    public function exportCombinedPdf(): void
    {
        checkPermission('payroll');
        \$runId = (int)(\$_GET['id'] ?? 0);
        \$db = getDB();

        \$stmtRun = \$db->prepare("SELECT * FROM penggajian WHERE id = ?");
        \$stmtRun->execute([\$runId]);
        \$run = \$stmtRun->fetch();

        if (!\$run) {
            \$_SESSION['flash_error'] = 'Data payroll tidak valid.';
            redirect('/payroll');
            return;
        }

        \$stmtItems = \$db->prepare("
            SELECT pi.*, e.name as employee_name, e.tipe_gaji
            FROM rincian_penggajian pi
            JOIN karyawan e ON pi.id_karyawan = e.id
            WHERE pi.id_penggajian = ? AND pi.is_excluded = 0
            ORDER BY e.tipe_gaji ASC, e.name ASC
        ");
        \$stmtItems->execute([\$runId]);
        \$items = \$stmtItems->fetchAll();

        if (empty(\$items)) {
            \$_SESSION['flash_error'] = 'Tidak ada slip gaji untuk periode ini.';
            redirect('/payroll/preview?id=' . \$runId);
            return;
        }

        ob_start();
        include APP_ROOT . '/app/Views/reports/pdf_rekap.php';
        \$htmlRekap = ob_get_clean();

        ob_start();
        include APP_ROOT . '/app/Views/payroll/pdf_slips_batch.php';
        \$htmlSlips = ob_get_clean();

        \$htmlSlips = preg_replace('/<!DOCTYPE html>/i', '', \$htmlSlips);
        \$htmlSlips = preg_replace('/<html>/i', '', \$htmlSlips);
        \$htmlSlips = preg_replace('/<\/html>/i', '', \$htmlSlips);
        
        preg_match('/<style>(.*?)<\/style>/is', \$htmlSlips, \$matches);
        \$slipsStyle = \$matches[1] ?? '';
        
        \$htmlSlips = preg_replace('/<head>.*?<\/head>/is', '', \$htmlSlips);
        \$htmlSlips = preg_replace('/<body>/i', '', \$htmlSlips);
        \$htmlSlips = preg_replace('/<\/body>/i', '', \$htmlSlips);

        \$htmlRekap = str_replace('</style>', \$slipsStyle . '</style>', \$htmlRekap);

        \$pageBreak = '<div style="page-break-before: always; clear: both;"></div>';
        \$htmlRekap = str_replace('</body>', \$pageBreak . \$htmlSlips . '</body>', \$htmlRekap);

        \$runName = \$run['name'] ?: ('Run ' . \$runId);
        \$safeRunName = preg_replace('/[^A-Za-z0-9_\- ]/', '', \$runName);
        \$statusStr = \$run['status'] === 'draft' ? 'DRAFT ' : '';
        \$filename = 'Laporan Lengkap ' . \$statusStr . \$safeRunName;
        
        streamPdf(\$htmlRekap, \$filename, 'A4', 'landscape');
    }
EOT;

// hapus function exportSlipsMass jika ada (kita abaikan saja, tambahkan sebelum exportSlipsMass)
$c = str_replace('public function exportSlipsMass(): void', $newMethod . "\n\n    public function exportSlipsMass(): void", $c);

file_put_contents('app/Controllers/PayrollController.php', $c);
echo "Added exportCombinedPdf\n";
?>
