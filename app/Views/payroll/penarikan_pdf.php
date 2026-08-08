<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Penarikan Gaji</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
        }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        .mb-1 { margin-bottom: 5px; }
        .mb-4 { margin-bottom: 20px; }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        th {
            background-color: #f8f9fa;
            text-align: left;
        }
        .header {
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

    <div class="header text-center">
        <h2 class="mb-1">Laporan Penarikan Gaji Bulanan</h2>
        <?php if (!empty($startDate) || !empty($endDate)): ?>
            <p>
                Periode: 
                <?= !empty($startDate) ? date('d M Y', strtotime($startDate)) : 'Awal' ?> 
                s/d 
                <?= !empty($endDate) ? date('d M Y', strtotime($endDate)) : 'Akhir' ?>
            </p>
        <?php else: ?>
            <p>Semua Periode</p>
        <?php endif; ?>
        <?php if (!empty($statusFilter)): ?>
            <p>Status: <?= $statusFilter === 'pending' ? 'Belum di Payroll' : 'Sudah di Payroll' ?></p>
        <?php endif; ?>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Karyawan</th>
                <th>Tanggal Penarikan</th>
                <th class="text-end">Nominal</th>
                <th>Keterangan</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $total = 0;
            if (empty($penarikan)): 
            ?>
            <tr>
                <td colspan="6" class="text-center">Tidak ada data penarikan gaji pada periode ini.</td>
            </tr>
            <?php else: ?>
                <?php 
                $no = 1;
                foreach ($penarikan as $p): 
                    $total += (float)$p['nominal'];
                ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td><?= htmlspecialchars($p['employee_name']) ?></td>
                    <td><?= date('d M Y', strtotime($p['tanggal'])) ?></td>
                    <td class="text-end">Rp <?= number_format((float)$p['nominal'], 0, ',', '.') ?></td>
                    <td><?= htmlspecialchars($p['keterangan'] ?: '-') ?></td>
                    <td>
                        <?= $p['id_penggajian'] ? 'Sudah di Payroll' : 'Belum di Payroll' ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="3" class="text-end fw-bold">TOTAL KESELURUHAN</td>
                    <td class="text-end fw-bold">Rp <?= number_format($total, 0, ',', '.') ?></td>
                    <td colspan="2"></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>
