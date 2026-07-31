<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rekapitulasi Gaji Bulanan - <?= htmlspecialchars($bulanName) ?></title>
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
        .subtotal-row th {
            background-color: #f9f9f9;
            font-size: 10px;
            font-style: italic;
        }
        .footer-row th {
            background-color: #d0d0d0;
            font-size: 11px;
        }
        .type-header th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: left !important;
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
        <h1>REKAPITULASI PEMBAYARAN GAJI BULANAN</h1>
        <p>Sistem Penggajian - Laporan Keuangan</p>
    </div>

    <table class="info-table">
        <tr>
            <td class="info-label" width="15%">Bulan</td>
            <td width="35%">: <strong><?= htmlspecialchars($bulanName) ?></strong></td>
            <td class="info-label" width="15%">Dicetak Pada</td>
            <td width="35%">: <?= date('d F Y H:i') ?></td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th rowspan="2">No</th>
                <th rowspan="2">Nama Karyawan</th>
                <th rowspan="2">Gaji Pokok</th>
                <th rowspan="2">Kehadiran<br>(Hari / Rp)</th>
                <th rowspan="2">Produksi (Rp)</th>
                <th colspan="3">Tambahan (Rp)</th>
                <th colspan="3">Potongan (Rp)</th>
                <th rowspan="2">Netto (Rp)</th>
            </tr>
            <tr>
                <th>Lembur</th>
                <th>Bulanan</th>
                <th>Lain-lain</th>
                <th>Hutang</th>
                <th>Penarikan</th>
                <th>Lain-lain</th>
            </tr>
        </thead>
        <tbody>
            <?php 
                $grandTotal = [
                    'pokok' => 0, 'kehadiran' => 0, 'produksi' => 0,
                    'lembur' => 0, 'tunj_bulanan' => 0, 'tunj_lain' => 0,
                    'kasbon' => 0, 'penarikan' => 0, 'pot_lain' => 0, 'netto' => 0
                ];
                
                $currentType = '';
                $subTotal = [];
                $no = 1;

                // Helper to initialize subtotal
                $initSubTotal = function() {
                    return [
                        'pokok' => 0, 'kehadiran' => 0, 'produksi' => 0,
                        'lembur' => 0, 'tunj_bulanan' => 0, 'tunj_lain' => 0,
                        'kasbon' => 0, 'penarikan' => 0, 'pot_lain' => 0, 'netto' => 0
                    ];
                };

                // Helper to print subtotal row
                $printSubTotal = function($type, $st) {
                    echo '<tr class="subtotal-row">';
                    echo '<th colspan="2" class="text-right">SUBTOTAL ' . strtoupper($type) . ' (Rp)</th>';
                    echo '<th class="text-right">' . number_format((float)$st['pokok'], 0, ',', '.') . '</th>';
                    echo '<th class="text-right">' . number_format((float)$st['kehadiran'], 0, ',', '.') . '</th>';
                    echo '<th class="text-right">' . number_format((float)$st['produksi'], 0, ',', '.') . '</th>';
                    echo '<th class="text-right">' . number_format((float)$st['lembur'], 0, ',', '.') . '</th>';
                    echo '<th class="text-right">' . number_format((float)$st['tunj_bulanan'], 0, ',', '.') . '</th>';
                    echo '<th class="text-right">' . number_format((float)$st['tunj_lain'], 0, ',', '.') . '</th>';
                    echo '<th class="text-right">' . number_format((float)$st['kasbon'], 0, ',', '.') . '</th>';
                    echo '<th class="text-right">' . number_format((float)$st['penarikan'], 0, ',', '.') . '</th>';
                    echo '<th class="text-right">' . number_format((float)$st['pot_lain'], 0, ',', '.') . '</th>';
                    echo '<th class="text-right">' . number_format((float)$st['netto'], 0, ',', '.') . '</th>';
                    echo '</tr>';
                };

                foreach ($items as $row) {
                    if ($currentType !== $row['tipe_gaji']) {
                        if ($currentType !== '') {
                            $printSubTotal($currentType, $subTotal);
                        }
                        $currentType = $row['tipe_gaji'];
                        $subTotal = $initSubTotal();
                        $no = 1;
                        echo '<tr class="type-header"><th colspan="12">KARYAWAN ' . strtoupper($currentType) . '</th></tr>';
                    }

                    // Add to subtotal
                    $subTotal['pokok'] += $row['gaji_pokok'];
                    $subTotal['kehadiran'] += $row['total_uang_kehadiran'];
                    $subTotal['produksi'] += $row['total_upah_produksi'];
                    $subTotal['lembur'] += $row['total_upah_lembur'] ?? 0;
                    $subTotal['tunj_bulanan'] += $row['tunjangan_bulanan'];
                    $subTotal['tunj_lain'] += $row['tunjangan_lain'] + $row['nominal_pembulatan'];
                    $subTotal['kasbon'] += $row['total_potongan_kasbon'];
                    $subTotal['penarikan'] += $row['total_penarikan_gaji'] ?? 0;
                    $subTotal['pot_lain'] += $row['potongan_lain'];
                    $subTotal['netto'] += $row['gaji_bersih'];

                    // Add to grand total
                    $grandTotal['pokok'] += $row['gaji_pokok'];
                    $grandTotal['kehadiran'] += $row['total_uang_kehadiran'];
                    $grandTotal['produksi'] += $row['total_upah_produksi'];
                    $grandTotal['lembur'] += $row['total_upah_lembur'] ?? 0;
                    $grandTotal['tunj_bulanan'] += $row['tunjangan_bulanan'];
                    $grandTotal['tunj_lain'] += $row['tunjangan_lain'] + $row['nominal_pembulatan'];
                    $grandTotal['kasbon'] += $row['total_potongan_kasbon'];
                    $grandTotal['penarikan'] += $row['total_penarikan_gaji'] ?? 0;
                    $grandTotal['pot_lain'] += $row['potongan_lain'];
                    $grandTotal['netto'] += $row['gaji_bersih'];
            ?>
            <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td><?= htmlspecialchars($row['employee_name']) ?></td>
                <td class="text-right"><?= number_format((float)$row['gaji_pokok'], 0, ',', '.') ?></td>
                <td class="text-right"><?= $row['hari_hadir'] ?> hr / <?= number_format((float)$row['total_uang_kehadiran'], 0, ',', '.') ?></td>
                <td class="text-right"><?= number_format((float)$row['total_upah_produksi'], 0, ',', '.') ?></td>
                <td class="text-right"><?= number_format((float)($row['total_upah_lembur'] ?? 0), 0, ',', '.') ?></td>
                <td class="text-right"><?= number_format((float)$row['tunjangan_bulanan'], 0, ',', '.') ?></td>
                <td class="text-right"><?= number_format($row['tunjangan_lain'] + $row['nominal_pembulatan'], 0, ',', '.') ?></td>
                <td class="text-right"><?= number_format($row['total_potongan_kasbon'], 0, ',', '.') ?></td>
                <td class="text-right"><?= number_format($row['total_penarikan_gaji'] ?? 0, 0, ',', '.') ?></td>
                <td class="text-right"><?= number_format((float)$row['potongan_lain'], 0, ',', '.') ?></td>
                <td class="text-right" style="font-weight:bold;"><?= number_format((float)$row['gaji_bersih'], 0, ',', '.') ?></td>
            </tr>
            <?php 
                } // end foreach
                
                // Print last subtotal
                if ($currentType !== '') {
                    $printSubTotal($currentType, $subTotal);
                }
            ?>
        </tbody>
        <tfoot>
            <tr class="footer-row">
                <th colspan="2" class="text-right">GRAND TOTAL KESELURUHAN (Rp)</th>
                <th class="text-right"><?= number_format($grandTotal['pokok'], 0, ',', '.') ?></th>
                <th class="text-right"><?= number_format($grandTotal['kehadiran'], 0, ',', '.') ?></th>
                <th class="text-right"><?= number_format($grandTotal['produksi'], 0, ',', '.') ?></th>
                <th class="text-right"><?= number_format($grandTotal['lembur'], 0, ',', '.') ?></th>
                <th class="text-right"><?= number_format($grandTotal['tunj_bulanan'], 0, ',', '.') ?></th>
                <th class="text-right"><?= number_format($grandTotal['tunj_lain'], 0, ',', '.') ?></th>
                <th class="text-right"><?= number_format($grandTotal['kasbon'], 0, ',', '.') ?></th>
                <th class="text-right"><?= number_format($grandTotal['penarikan'], 0, ',', '.') ?></th>
                <th class="text-right"><?= number_format($grandTotal['pot_lain'], 0, ',', '.') ?></th>
                <th class="text-right"><?= number_format($grandTotal['netto'], 0, ',', '.') ?></th>
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
