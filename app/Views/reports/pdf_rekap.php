<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rekapitulasi Gaji - <?= htmlspecialchars($run['name'] ?? 'Run #' . $run['id']) ?></title>
    <style>
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #333;
            margin: 0;
            padding: 10px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 16px;
            text-transform: uppercase;
        }
        .header p {
            margin: 5px 0 0 0;
            font-size: 11px;
            color: #555;
        }
        .info-table {
            width: 100%;
            margin-bottom: 15px;
            font-size: 11px;
        }
        .info-label {
            font-weight: bold;
            width: 100px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .data-table th, .data-table td {
            border: 1px solid #000;
            padding: 5px;
            text-align: left;
        }
        .data-table th {
            background-color: #e0e0e0;
            text-align: center;
            font-weight: bold;
        }
        .text-right {
            text-align: right !important;
        }
        .text-center {
            text-align: center !important;
        }
        .footer-row th {
            background-color: #f0f0f0;
            font-size: 11px;
        }
        .signature-area {
            width: 100%;
            margin-top: 30px;
        }
        .signature {
            width: 50%;
            float: left;
            text-align: center;
            font-size: 11px;
        }
        .signature p {
            margin-top: 50px;
            display: inline-block;
            border-top: 1px solid #333;
            padding: 0 15px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>REKAPITULASI PEMBAYARAN GAJI</h1>
        <p>Sistem Penggajian - Laporan Keuangan</p>
    </div>

    <table class="info-table">
        <tr>
            <td class="info-label" width="15%">Nama Payroll</td>
            <td width="35%">: <strong><?= htmlspecialchars($run['name'] ?? 'Run #' . $run['id']) ?></strong></td>
            <td class="info-label" width="15%">Periode</td>
            <td width="35%">: <?= date('d F Y', strtotime($run['periode_awal'])) ?> s/d <?= date('d F Y', strtotime($run['periode_akhir'])) ?></td>
        </tr>
        <tr>
            <td class="info-label">ID Payroll Run</td>
            <td>: #<?= $run['id'] ?></td>
            <td class="info-label">Tipe Karyawan</td>
            <td>: <?= ucfirst($run['type']) ?></td>
            <td class="info-label">Tgl Disetujui</td>
            <td>: <?= $run['disetujui_pada'] ? date('d F Y H:i', strtotime($run['disetujui_pada'])) : '-' ?></td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th rowspan="2">No</th>
                <th rowspan="2">Nama Karyawan</th>
                <th rowspan="2">Tipe</th>
                <th rowspan="2">Gaji Pokok</th>
                <th rowspan="2">Kehadiran<br>(Hari / Rp)</th>
                <th rowspan="2">Produksi (Rp)</th>
                <th colspan="3">Tambahan (Rp)</th>
                <th colspan="2">Potongan (Rp)</th>
                <th rowspan="2">Netto (Rp)</th>
            </tr>
            <tr>
                <th>Lembur</th>
                <th>Bulanan</th>
                <th>Lain-lain</th>
                <th>Kasbon</th>
                <th>Lain-lain</th>
            </tr>
        </thead>
        <tbody>
            <?php 
                $no = 1;
                $totGajiPokok = 0;
                $totKehadiran = 0;
                $totProduksi = 0;
                $totLembur = 0;
                $totTunjBulanan = 0;
                $totTunjLain = 0;
                $totKasbon = 0;
                $totPotLain = 0;
                $totNetto = 0;

                foreach ($items as $row): 
                    $totGajiPokok += $row['gaji_pokok'];
                    $totKehadiran += $row['total_uang_kehadiran'];
                    $totProduksi += $row['total_upah_produksi'];
                    $totLembur += $row['total_upah_lembur'] ?? 0;
                    $totTunjBulanan += $row['tunjangan_bulanan'];
                    $totTunjLain += $row['tunjangan_lain'];
                    $totKasbon += $row['total_potongan_kasbon'];
                    $totPotLain += $row['potongan_lain'];
                    $totNetto += $row['gaji_bersih'];
            ?>
            <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td><?= htmlspecialchars($row['employee_name']) ?></td>
                <td class="text-center"><?= ucfirst($row['tipe_gaji']) ?></td>
                <td class="text-right"><?= number_format((float)$row['gaji_pokok'], 0, ',', '.') ?></td>
                <td class="text-right"><?= $row['hari_hadir'] ?> hr / <?= number_format((float)$row['total_uang_kehadiran'], 0, ',', '.') ?></td>
                <td class="text-right"><?= number_format((float)$row['total_upah_produksi'], 0, ',', '.') ?></td>
                <td class="text-right"><?= number_format((float)($row['total_upah_lembur'] ?? 0), 0, ',', '.') ?></td>
                <td class="text-right"><?= number_format((float)$row['tunjangan_bulanan'], 0, ',', '.') ?></td>
                <td class="text-right"><?= number_format((float)$row['tunjangan_lain'], 0, ',', '.') ?></td>
                <td class="text-right"><?= number_format((float)$row['total_potongan_kasbon'], 0, ',', '.') ?></td>
                <td class="text-right"><?= number_format((float)$row['potongan_lain'], 0, ',', '.') ?></td>
                <td class="text-right" style="font-weight:bold;"><?= number_format((float)$row['gaji_bersih'], 0, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>

            <?php if (empty($items)): ?>
            <tr>
                <td colspan="12" class="text-center">Tidak ada data rincian pada periode ini.</td>
            </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="footer-row">
                <th colspan="3" class="text-right">TOTAL KESELURUHAN (Rp)</th>
                <th class="text-right"><?= number_format($totGajiPokok, 0, ',', '.') ?></th>
                <th class="text-right"><?= number_format($totKehadiran, 0, ',', '.') ?></th>
                <th class="text-right"><?= number_format($totProduksi, 0, ',', '.') ?></th>
                <th class="text-right"><?= number_format($totLembur, 0, ',', '.') ?></th>
                <th class="text-right"><?= number_format($totTunjBulanan, 0, ',', '.') ?></th>
                <th class="text-right"><?= number_format($totTunjLain, 0, ',', '.') ?></th>
                <th class="text-right"><?= number_format($totKasbon, 0, ',', '.') ?></th>
                <th class="text-right"><?= number_format($totPotLain, 0, ',', '.') ?></th>
                <th class="text-right"><?= number_format($totNetto, 0, ',', '.') ?></th>
            </tr>
        </tfoot>
    </table>

    <div class="signature-area">
        <div class="signature">
            Disiapkan Oleh,<br><br><br>
            <p>Admin Payroll</p>
        </div>
        <div class="signature">
            Mengetahui,<br><br><br>
            <p>Owner / Direktur</p>
        </div>
    </div>

</body>
</html>
