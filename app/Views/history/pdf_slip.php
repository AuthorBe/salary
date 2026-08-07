<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Slip Gaji - <?= htmlspecialchars($data['employee_name']) ?></title>
    <style>
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 14px;
            color: #333;
            margin: 0;
            padding: 30px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
            text-transform: uppercase;
        }
        .header p {
            margin: 5px 0 0 0;
            font-size: 14px;
            color: #666;
        }
        .info-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .info-table td {
            padding: 4px 0;
        }
        .info-label {
            width: 120px;
            font-weight: bold;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .details-table th, .details-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .details-table th {
            background-color: #f5f5f5;
        }
        .text-right {
            text-align: right !important;
        }
        .section-title {
            background-color: #f9f9f9;
            font-weight: bold;
        }
        .total-row th {
            background-color: #eef;
            font-size: 16px;
        }
        .footer {
            margin-top: 40px;
            width: 100%;
        }
        .signature {
            width: 50%;
            float: left;
            text-align: center;
        }
        .signature p {
            margin-top: 60px;
            border-top: 1px solid #333;
            display: inline-block;
            padding: 0 20px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>SLIP GAJI KARYAWAN</h1>
        <p>Periode: <?= date('d M Y', strtotime($data['periode_awal'])) ?> - <?= date('d M Y', strtotime($data['periode_akhir'])) ?></p>
    </div>

    <table class="info-table">
        <tr>
            <td class="info-label">Nama Karyawan</td>
            <td>: <?= htmlspecialchars($data['employee_name']) ?></td>
            <td class="info-label">Tipe Gaji</td>
            <td>: <?= ucfirst($data['tipe_gaji']) ?></td>
        </tr>
        <tr>
            <td class="info-label">ID Payroll Run</td>
            <td>: #<?= $data['id_penggajian'] ?></td>
            <td class="info-label">Tgl Persetujuan</td>
            <td>: <?= $data['disetujui_pada'] ? date('d M Y', strtotime($data['disetujui_pada'])) : '-' ?></td>
        </tr>
    </table>

    <table class="details-table">
        <thead>
            <tr>
                <th>Deskripsi</th>
                <th class="text-right">Nominal (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <!-- PENDAPATAN -->
            <tr>
                <td colspan="2" class="section-title">PENDAPATAN</td>
            </tr>
            <tr>
                <td>Gaji Pokok</td>
                <td class="text-right"><?= number_format((float)$data['gaji_pokok'], 0, ',', '.') ?></td>
            </tr>
            <tr>
                <td>Uang Kehadiran (<?= $data['hari_hadir'] ?> hari)</td>
                <td class="text-right"><?= number_format((float)$data['total_uang_kehadiran'], 0, ',', '.') ?></td>
            </tr>
            <tr>
                <td>Upah Produksi Borongan</td>
                <td class="text-right"><?= number_format((float)$data['total_upah_produksi'], 0, ',', '.') ?></td>
            </tr>
            <tr>
                <td>Tunjangan Bulanan</td>
                <td class="text-right"><?= number_format((float)$data['tunjangan_bulanan'], 0, ',', '.') ?></td>
            </tr>
            <?php if ($data['tunjangan_lain'] > 0): ?>
            <tr>
                <td>Tunjangan Lain-lain <?= $data['catatan_tunjangan_lain'] ? '(' . htmlspecialchars($data['catatan_tunjangan_lain']) . ')' : '' ?></td>
                <td class="text-right"><?= number_format((float)$data['tunjangan_lain'], 0, ',', '.') ?></td>
            </tr>
            <?php endif; ?>
            <?php if (($data['penarikan_tabungan'] ?? 0) > 0): ?>
            <tr>
                <td>Tarik Tabungan</td>
                <td class="text-right"><?= number_format((float)$data['penarikan_tabungan'], 0, ',', '.') ?></td>
            </tr>
            <?php endif; ?>
            
            <?php 
                $totalPendapatan = $data['gaji_pokok'] + $data['total_uang_kehadiran'] + $data['total_upah_produksi'] + $data['tunjangan_bulanan'] + $data['tunjangan_lain'] + ($data['penarikan_tabungan'] ?? 0);
            ?>
            <tr style="font-weight:bold; background-color:#fafafa;">
                <td>TOTAL PENDAPATAN</td>
                <td class="text-right"><?= number_format((float)$totalPendapatan, 0, ',', '.') ?></td>
            </tr>

            <!-- POTONGAN -->
            <tr>
                <td colspan="2" class="section-title">POTONGAN</td>
            </tr>
            <?php if (($data['total_potongan_kasbon'] ?? 0) > 0): ?>
            <tr>
                <td>Potongan Kasbon / Hutang</td>
                <td class="text-right"><?= number_format((float)$data['total_potongan_kasbon'], 0, ',', '.') ?></td>
            </tr>
            <?php endif; ?>
            <?php if (($data['total_penarikan_gaji'] ?? 0) > 0): ?>
            <tr>
                <td>Penarikan Gaji Manual</td>
                <td class="text-right"><?= number_format((float)$data['total_penarikan_gaji'], 0, ',', '.') ?></td>
            </tr>
            <?php endif; ?>
            <?php if (($data['potongan_tabungan'] ?? 0) > 0): ?>
            <tr>
                <td>Setor Tabungan</td>
                <td class="text-right"><?= number_format((float)$data['potongan_tabungan'], 0, ',', '.') ?></td>
            </tr>
            <?php endif; ?>
            <?php if ($data['potongan_lain'] > 0): ?>
            <tr>
                <td>Potongan Lain-lain <?= $data['catatan_potongan_lain'] ? '(' . htmlspecialchars($data['catatan_potongan_lain']) . ')' : '' ?></td>
                <td class="text-right"><?= number_format((float)$data['potongan_lain'], 0, ',', '.') ?></td>
            </tr>
            <?php endif; ?>

            <?php 
                $totalPotongan = ($data['total_potongan_kasbon'] ?? 0) + ($data['total_penarikan_gaji'] ?? 0) + ($data['potongan_tabungan'] ?? 0) + ($data['potongan_lain'] ?? 0);
            ?>
            <tr style="font-weight:bold; background-color:#fafafa;">
                <td>TOTAL POTONGAN</td>
                <td class="text-right"><?= number_format((float)$totalPotongan, 0, ',', '.') ?></td>
            </tr>

            <!-- PEMBULATAN -->
            <?php if ($data['nominal_pembulatan'] != 0): ?>
            <tr>
                <td>Penyesuaian / Pembulatan</td>
                <td class="text-right"><?= number_format((float)$data['nominal_pembulatan'], 0, ',', '.') ?></td>
            </tr>
            <?php endif; ?>

            <!-- GAJI BERSIH -->
            <tr class="total-row">
                <th>TAKE HOME PAY (GAJI BERSIH)</th>
                <th class="text-right">Rp <?= number_format((float)$data['gaji_bersih'], 0, ',', '.') ?></th>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <div class="signature">
            Diterima Oleh,<br><br><br>
            <p><?= htmlspecialchars($data['employee_name']) ?></p>
        </div>
        <div class="signature">
            Disetujui Oleh,<br><br><br>
            <p>Manajemen / Kasir</p>
        </div>
    </div>

</body>
</html>
