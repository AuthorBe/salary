<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Slip Gaji Batch - <?= htmlspecialchars($safeRunName ?? 'Payroll') ?></title>
    <style>
        @page { margin: 5px; }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #333;
            margin: 0;
            padding: 0;
        }
        
        .page-break {
            page-break-after: always;
            clear: both;
        }

        /* Gunakan border-spacing agar TD bisa punya border dan tingginya otomatis sama persis */
        .grid-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 5px; /* Jarak antar slip sangat sempit */
        }
        
        .grid-table tr {
            page-break-inside: avoid;
        }
        
        .grid-cell {
            width: 50%;
            vertical-align: top;
            padding: 8px;
            border: 1px dashed #999;
        }

        .slip-header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 5px;
            margin-bottom: 8px;
        }
        .slip-header h2 { margin: 0; font-size: 14px; text-transform: uppercase; }
        .slip-header p { margin: 2px 0 0 0; font-size: 10px; color: #555; }

        .emp-info { width: 100%; margin-bottom: 8px; font-size: 10px; }
        .emp-info td { padding: 2px 0; }
        .emp-info .label { width: 60px; font-weight: bold; }

        .details-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; font-size: 10px; }
        .details-table th, .details-table td { padding: 3px; border-bottom: 1px dotted #ccc; }
        .details-table th { text-align: left; background-color: #f5f5f5; border-bottom: 1px solid #999; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }

        .total-row td { border-top: 1px solid #333; font-weight: bold; }
        
    </style>
</head>
<body>

<?php 
$options = json_decode($run['options_json'] ?? '[]', true);
$perPage = 4; // Maksimal 4 slip (2 baris) per halaman untuk semua tipe gaji
$totalItems = count($items);
?>

<table class="grid-table">
    <tr>
<?php 
foreach ($items as $index => $row): 
    $count = $index + 1;
    
    $slipStart = $run['periode_awal'];
    $slipEnd = $run['periode_akhir'];
    if ($row['tipe_gaji'] === 'borongan' && isset($options['borongan'])) {
        $slipStart = $options['borongan']['start'];
        $slipEnd = $options['borongan']['end'];
    } elseif ($row['tipe_gaji'] === 'bulanan' && isset($options['bulanan'])) {
        $slipStart = $options['bulanan']['start'];
        $slipEnd = $options['bulanan']['end'];
    }
?>
        <td class="grid-cell">
            <div class="slip-header">
                <h2>SLIP GAJI KARYAWAN</h2>
                <p>Periode: <?= date('d M Y', strtotime($slipStart)) ?> - <?= date('d M Y', strtotime($slipEnd)) ?></p>
            </div>

            <table class="emp-info">
                <tr>
                    <td class="label">Nama</td>
                    <td>: <strong><?= htmlspecialchars($row['employee_name']) ?></strong></td>
                </tr>
                <tr>
                    <td class="label">Tipe</td>
                    <td>: <?= ucfirst($row['tipe_gaji']) ?></td>
                </tr>
            </table>

            <!-- Pemasukan -->
            <strong>PENDAPATAN</strong>
            <table class="details-table">
                <tr>
                    <td>Gaji Pokok</td>
                    <td class="text-right"><?= number_format((float)$row['gaji_pokok'], 0, ',', '.') ?></td>
                </tr>
                <tr>
                    <td>Kehadiran (<?= $row['hari_hadir'] ?> hr)</td>
                    <td class="text-right"><?= number_format((float)$row['total_uang_kehadiran'], 0, ',', '.') ?></td>
                </tr>
                <tr>
                    <td>Uang Produksi</td>
                    <td class="text-right"><?= number_format((float)$row['total_upah_produksi'], 0, ',', '.') ?></td>
                </tr>
                <?php if ((float)($row['total_upah_lembur'] ?? 0) > 0): ?>
                <tr>
                    <td>Uang Lembur</td>
                    <td class="text-right"><?= number_format((float)$row['total_upah_lembur'], 0, ',', '.') ?></td>
                </tr>
                <?php endif; ?>
                <?php if ((float)$row['tunjangan_bulanan'] > 0): ?>
                <tr>
                    <td>Tunjangan Bulanan</td>
                    <td class="text-right"><?= number_format((float)$row['tunjangan_bulanan'], 0, ',', '.') ?></td>
                </tr>
                <?php endif; ?>
                <?php if ((float)$row['tunjangan_lain'] > 0): ?>
                <tr>
                    <td><?= htmlspecialchars(!empty(trim($row['catatan_tunjangan_lain'] ?? '')) ? trim($row['catatan_tunjangan_lain']) : 'Tunjangan Lain') ?></td>
                    <td class="text-right"><?= number_format((float)$row['tunjangan_lain'], 0, ',', '.') ?></td>
                </tr>
                <?php endif; ?>
                <?php if ((float)($row['penarikan_tabungan'] ?? 0) > 0): ?>
                <tr>
                    <td>Tarik Tabungan</td>
                    <td class="text-right"><?= number_format((float)$row['penarikan_tabungan'], 0, ',', '.') ?></td>
                </tr>
                <?php endif; ?>
                <?php 
                    $totalPendapatan = (float)$row['gaji_pokok'] + (float)$row['total_uang_kehadiran'] + (float)$row['total_upah_produksi'] + (float)($row['total_upah_lembur'] ?? 0) + (float)$row['tunjangan_bulanan'] + (float)$row['tunjangan_lain'] + (float)($row['penarikan_tabungan'] ?? 0);
                ?>
                <tr class="total-row">
                    <td>Total Pendapatan</td>
                    <td class="text-right"><?= number_format($totalPendapatan, 0, ',', '.') ?></td>
                </tr>
            </table>

            <!-- Potongan -->
            <strong>POTONGAN</strong>
            <table class="details-table">
                <?php 
                    $totalPotongan = (float)$row['total_potongan_kasbon'] + (float)$row['potongan_lain'] + (float)($row['total_penarikan_gaji'] ?? 0) + (float)($row['potongan_tabungan'] ?? 0);
                    $rincian = json_decode($row['rincian_json'], true) ?? [];
                ?>
                <?php if ((float)$row['total_potongan_kasbon'] > 0): ?>
                <tr>
                    <td>Potongan Kasbon</td>
                    <td class="text-right"><?= number_format((float)$row['total_potongan_kasbon'], 0, ',', '.') ?></td>
                </tr>
                <?php endif; ?>
                <?php if (!empty($rincian['penarikan'])): ?>
                    <?php foreach ($rincian['penarikan'] as $p): ?>
                    <tr>
                        <td>Penarikan (<?= date('d M', strtotime($p['tanggal'])) ?>)</td>
                        <td class="text-right"><?= number_format((float)$p['nominal'], 0, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php elseif (($row['total_penarikan_gaji'] ?? 0) > 0): ?>
                    <tr>
                        <td>Penarikan Gaji</td>
                        <td class="text-right"><?= number_format((float)$row['total_penarikan_gaji'], 0, ',', '.') ?></td>
                    </tr>
                <?php endif; ?>
                <?php if ((float)$row['potongan_lain'] > 0): ?>
                <tr>
                    <td><?= htmlspecialchars(!empty(trim($row['catatan_potongan_lain'] ?? '')) ? trim($row['catatan_potongan_lain']) : 'Potongan Lain') ?></td>
                    <td class="text-right"><?= number_format((float)$row['potongan_lain'], 0, ',', '.') ?></td>
                </tr>
                <?php endif; ?>
                <?php if ((float)($row['potongan_tabungan'] ?? 0) > 0): ?>
                <tr>
                    <td>Setor Tabungan</td>
                    <td class="text-right"><?= number_format((float)$row['potongan_tabungan'], 0, ',', '.') ?></td>
                </tr>
                <?php endif; ?>
                <?php
                    if ($totalPotongan == 0):
                ?>
                <tr>
                    <td colspan="2" style="font-style:italic; color:#777;">Tidak ada potongan</td>
                </tr>
                <?php else: ?>
                <tr class="total-row">
                    <td>Total Potongan</td>
                    <td class="text-right"><?= number_format($totalPotongan, 0, ',', '.') ?></td>
                </tr>
                <?php endif; ?>
            </table>

            <table style="width: 100%; margin-top: 15px; border-collapse: collapse;">
                <tr>
                    <td style="font-weight:bold; font-size:12px; border-top: 2px solid #000; border-bottom: 2px solid #000; padding: 6px 0;">PENERIMAAN BERSIH</td>
                    <td class="text-right" style="font-weight:bold; font-size:12px; border-top: 2px solid #000; border-bottom: 2px solid #000; padding: 6px 0; text-align: right;">Rp <?= number_format((float)$row['gaji_bersih'], 0, ',', '.') ?></td>
                </tr>
            </table>

        </td>
<?php 
    // Jika mencapai kelipatan 2, tutup baris TR
    if ($count % 2 === 0) {
        echo '</tr>';
        
        // Jika mencapai kelipatan batas per-halaman, potong halaman dan buat tabel baru
        if ($count % $perPage === 0 && $count < $totalItems) {
            echo '</table>';
            echo '<div class="page-break"></div>';
            echo '<table class="grid-table"><tr>';
        } elseif ($count < $totalItems) {
            // Jika belum ganti halaman tapi sudah ganti baris, buka TR baru
            echo '<tr>';
        }
    }
?>
<?php endforeach; ?>
<?php 
    // Jika jumlah data ganjil, tutup baris terakhir dengan TD kosong
    if ($totalItems % 2 !== 0) {
        echo '<td class="grid-cell" style="border:none;"></td></tr>';
    }
?>
</table>

</body>
</html>
