<?php
namespace App\Controllers;

use App\Models\Employee;

class RekapController
{
    public function index(): void
    {
        // Gerbang Rekap
        view('rekap/index', [
            'title'     => 'Rekapitulasi – Salary',
            'pageTitle' => 'Gerbang Rekapitulasi',
            'pageKey'   => 'rekap'
        ]);
    }

    public function attendance(): void
    {
        $db = getDB();
        $start_date = $_GET['start_date'] ?? date('Y-m-01');
        $end_date   = $_GET['end_date'] ?? date('Y-m-t');

        $stmt = $db->prepare("
            SELECT 
                k.id, k.name, k.tipe_gaji,
                SUM(a.hadir) as total_hadir,
                SUM(CASE WHEN a.hadir = 0 AND a.catatan != '' THEN 1 ELSE 0 END) as total_absen
            FROM karyawan k
            LEFT JOIN absensi a ON k.id = a.id_karyawan AND a.date BETWEEN ? AND ?
            WHERE k.aktif = 1
            GROUP BY k.id
            ORDER BY k.tipe_gaji ASC, k.name ASC
        ");
        $stmt->execute([$start_date, $end_date]);
        $data = $stmt->fetchAll();

        view('rekap/attendance', [
            'title'      => 'Rekap Kehadiran – Salary',
            'pageTitle'  => 'Rekap Kehadiran',
            'pageKey'    => 'rekap',
            'start_date' => $start_date,
            'end_date'   => $end_date,
            'data'       => $data
        ]);
    }

    public function production(): void
    {
        $db = getDB();
        $start_date = $_GET['start_date'] ?? date('Y-m-01');
        $end_date   = $_GET['end_date'] ?? date('Y-m-t');

        // Ambil data produksi reguler
        $stmt = $db->prepare("
            SELECT 
                p.date,
                k.name as employee_name,
                pr.name as product_name,
                p.kuantitas as total_qty,
                p.kuantitas_bal as total_bal,
                pg.harga_per_bungkus
            FROM produksi p
            JOIN karyawan k ON p.id_karyawan = k.id
            JOIN produk pr ON p.id_produk = pr.id
            JOIN kelompok_harga_produk pg ON pr.id_kelompok_harga = pg.id
            WHERE p.date BETWEEN ? AND ?
            ORDER BY p.date ASC, k.name ASC, pr.name ASC
        ");
        $stmt->execute([$start_date, $end_date]);
        $rawData = $stmt->fetchAll();

        $groupedData = [];
        foreach ($rawData as $row) {
            $date = $row['date'];
            if (!isset($groupedData[$date])) {
                $groupedData[$date] = [
                    'date' => $date,
                    'total_qty' => 0,
                    'total_bal' => 0,
                    'total_upah' => 0,
                    'details' => []
                ];
            }
            $upah = $row['total_qty'] * $row['harga_per_bungkus'];
            
            $groupedData[$date]['total_qty'] += $row['total_qty'];
            $groupedData[$date]['total_bal'] += $row['total_bal'];
            $groupedData[$date]['total_upah'] += $upah;
            $groupedData[$date]['details'][] = [
                'employee_name' => $row['employee_name'],
                'product_name' => $row['product_name'],
                'qty' => $row['total_qty'],
                'bal' => $row['total_bal'],
                'upah' => $upah
            ];
        }

        view('rekap/production', [
            'title'      => 'Rekap Produksi – Salary',
            'pageTitle'  => 'Rekap Produksi',
            'pageKey'    => 'rekap',
            'start_date' => $start_date,
            'end_date'   => $end_date,
            'groupedData'=> $groupedData
        ]);
    }

    public function overtime(): void
    {
        $db = getDB();
        $start_date = $_GET['start_date'] ?? date('Y-m-01');
        $end_date   = $_GET['end_date'] ?? date('Y-m-t');

        // Untuk lembur, ada bulanan (nominal) dan borongan (kuantitas)
        $stmtBulanan = $db->prepare("
            SELECT k.id, k.name, k.tipe_gaji, SUM(a.lembur_nominal) as total_uang_lembur
            FROM absensi a
            JOIN karyawan k ON a.id_karyawan = k.id
            WHERE a.date BETWEEN ? AND ? AND k.tipe_gaji = 'bulanan' AND a.lembur_nominal > 0
            GROUP BY k.id
            ORDER BY k.name ASC
        ");
        $stmtBulanan->execute([$start_date, $end_date]);
        $dataBulanan = $stmtBulanan->fetchAll();

        $stmtBorongan = $db->prepare("
            SELECT k.id, k.name, k.tipe_gaji, SUM(p.lembur_kuantitas * pg.harga_per_bungkus) as total_uang_lembur
            FROM produksi p
            JOIN karyawan k ON p.id_karyawan = k.id
            JOIN produk pr ON p.id_produk = pr.id
            JOIN kelompok_harga_produk pg ON pr.id_kelompok_harga = pg.id
            WHERE p.date BETWEEN ? AND ? AND p.lembur_kuantitas > 0
            GROUP BY k.id
            ORDER BY k.name ASC
        ");
        $stmtBorongan->execute([$start_date, $end_date]);
        $dataBorongan = $stmtBorongan->fetchAll();

        $data = array_merge($dataBulanan, $dataBorongan);
        usort($data, function($a, $b) {
            $cmp = strcmp($a['tipe_gaji'], $b['tipe_gaji']);
            if ($cmp === 0) {
                return strcmp($a['name'], $b['name']);
            }
            return $cmp;
        });

        view('rekap/overtime', [
            'title'      => 'Rekap Lembur – Salary',
            'pageTitle'  => 'Rekap Lembur',
            'pageKey'    => 'rekap',
            'start_date' => $start_date,
            'end_date'   => $end_date,
            'data'       => $data
        ]);
    }

    public function employee(): void
    {
        $db = getDB();
        $start_date = $_GET['start_date'] ?? date('Y-m-01');
        $end_date   = $_GET['end_date'] ?? date('Y-m-t');
        $emp_id     = (int)($_GET['id_karyawan'] ?? 0);

        $empModel = new Employee();
        $employees = $empModel->getAllActive();

        $empData = null;
        $stats = [
            'hadir' => 0, 'absen' => 0, 'produksi_reguler' => 0, 'uang_lembur' => 0, 'kasbon_sisa' => 0
        ];
        $logs = [];

        if ($emp_id > 0) {
            $empData = $empModel->findById($emp_id);
            if ($empData) {
                // Kehadiran & Lembur Bulanan
                $attStmt = $db->prepare("SELECT * FROM absensi WHERE id_karyawan = ? AND date BETWEEN ? AND ? ORDER BY date ASC");
                $attStmt->execute([$emp_id, $start_date, $end_date]);
                $attLogs = $attStmt->fetchAll();

                foreach ($attLogs as $a) {
                    if ($a['hadir'] == 1) $stats['hadir']++;
                    else if ($a['catatan']) $stats['absen']++;
                    
                    if ($empData['tipe_gaji'] === 'bulanan' && $a['lembur_nominal'] > 0) {
                        $stats['uang_lembur'] += $a['lembur_nominal'];
                    }

                    $logs[$a['date']]['absensi'] = $a;
                }

                // Produksi & Lembur Borongan
                if ($empData['tipe_gaji'] === 'borongan') {
                    $prodStmt = $db->prepare("
                        SELECT p.*, pr.name as product_name, pg.harga_per_bungkus 
                        FROM produksi p
                        JOIN produk pr ON p.id_produk = pr.id
                        JOIN kelompok_harga_produk pg ON pr.id_kelompok_harga = pg.id
                        WHERE p.id_karyawan = ? AND p.date BETWEEN ? AND ?
                    ");
                    $prodStmt->execute([$emp_id, $start_date, $end_date]);
                    $prodLogs = $prodStmt->fetchAll();

                    foreach ($prodLogs as $p) {
                        $uangProd = $p['kuantitas'] * $p['harga_per_bungkus'];
                        $stats['produksi_reguler'] += $uangProd;
                        
                        $uangLembur = $p['lembur_kuantitas'] * $p['harga_per_bungkus'];
                        $stats['uang_lembur'] += $uangLembur;

                        $logs[$p['date']]['produksi'][] = $p;
                    }
                }

                // Kasbon Sisa
                $kasbonStmt = $db->prepare("SELECT SUM(sisa_nominal) FROM kasbon WHERE id_karyawan = ? AND status != 'lunas'");
                $kasbonStmt->execute([$emp_id]);
                $stats['kasbon_sisa'] = (float)$kasbonStmt->fetchColumn();

                // Peminjaman Kasbon (Baru)
                $pinjamStmt = $db->prepare("SELECT id, keterangan, total_nominal, DATE(created_at) as date FROM kasbon WHERE id_karyawan = ? AND DATE(created_at) BETWEEN ? AND ?");
                $pinjamStmt->execute([$emp_id, $start_date, $end_date]);
                $pinjamLogs = $pinjamStmt->fetchAll();
                foreach ($pinjamLogs as $k) {
                    $logs[$k['date']]['kasbon'][] = [
                        'type' => 'pinjam',
                        'nominal' => $k['total_nominal'],
                        'keterangan' => $k['keterangan'] ?: 'Pinjam Kasbon'
                    ];
                }

                // Pembayaran Kasbon (Cicilan)
                $bayarStmt = $db->prepare("
                    SELECT p.nominal, p.tanggal_potongan as date, p.catatan, p.type as method
                    FROM potongan_kasbon p
                    JOIN kasbon k ON p.id_kasbon = k.id
                    WHERE k.id_karyawan = ? AND p.tanggal_potongan BETWEEN ? AND ?
                ");
                $bayarStmt->execute([$emp_id, $start_date, $end_date]);
                $bayarLogs = $bayarStmt->fetchAll();
                foreach ($bayarLogs as $b) {
                    $logs[$b['date']]['kasbon'][] = [
                        'type' => 'bayar',
                        'nominal' => $b['nominal'],
                        'keterangan' => $b['catatan'] ?: ($b['method'] == 'payroll' ? 'Dipotong dari gaji' : 'Pembayaran cicilan')
                    ];
                }
            }
        }
        
        ksort($logs);

        view('rekap/employee', [
            'title'      => 'Rekap Karyawan – Salary',
            'pageTitle'  => 'Detail & Rapor Karyawan',
            'pageKey'    => 'rekap',
            'start_date' => $start_date,
            'end_date'   => $end_date,
            'employees'  => $employees,
            'emp_id'     => $emp_id,
            'empData'    => $empData,
            'stats'      => $stats,
            'logs'       => $logs
        ]);
    }
}
