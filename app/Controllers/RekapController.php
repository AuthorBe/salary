<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Employee;
use App\Models\Saving;

class RekapController
{
    /**
     * Sanitasi dan validasi rentang tanggal input.
     * Fallback ke awal dan akhir bulan berjalan jika kosong/tidak valid.
     * Memastikan $startDate <= $endDate.
     *
     * @return array{0: string, 1: string} [$startDate, $endDate]
     */
    protected function sanitizeDates(?string $start, ?string $end): array
    {
        $startDate = !empty($start) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)
            ? $start
            : date('Y-m-01');

        $endDate = !empty($end) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)
            ? $end
            : date('Y-m-t');

        if ($startDate > $endDate) {
            $temp = $startDate;
            $startDate = $endDate;
            $endDate = $temp;
        }

        return [$startDate, $endDate];
    }

    public function index(): void
    {
        requireLogin();
        checkPermission('rekap');

        // Gerbang Rekap
        view('rekap/index', [
            'title'     => 'Rekapitulasi – Salary',
            'pageTitle' => 'Gerbang Rekapitulasi',
            'pageKey'   => 'rekap',
        ]);
    }

    public function attendance(): void
    {
        requireLogin();
        checkPermission('rekap');

        $db = getDB();
        [$start_date, $end_date] = $this->sanitizeDates($_GET['start_date'] ?? null, $_GET['end_date'] ?? null);

        $stmt = $db->prepare("
            SELECT 
                k.id, k.name, k.tipe_gaji,
                COALESCE(SUM(a.hadir), 0) as total_hadir,
                COALESCE(SUM(CASE WHEN a.hadir = 0 AND (a.catatan IS NOT NULL AND a.catatan != '') THEN 1 ELSE 0 END), 0) as total_absen,
                COALESCE(SUM(CASE WHEN a.hadir = 1 AND a.telat = 1 THEN 1 ELSE 0 END), 0) as total_telat
            FROM karyawan k
            LEFT JOIN absensi a ON k.id = a.id_karyawan AND a.date BETWEEN ? AND ?
            WHERE k.aktif = 1
            GROUP BY k.id, k.name, k.tipe_gaji
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
            'data'       => $data,
        ]);
    }

    public function production(): void
    {
        requireLogin();
        checkPermission('rekap');

        $db = getDB();
        [$start_date, $end_date] = $this->sanitizeDates($_GET['start_date'] ?? null, $_GET['end_date'] ?? null);

        // Ambil data produksi reguler
        $stmt = $db->prepare("
            SELECT 
                p.date,
                COALESCE(k.name, 'Karyawan Dihapus') as employee_name,
                COALESCE(pr.name, 'Produk Dihapus') as product_name,
                COALESCE(p.kuantitas, 0) as total_qty,
                COALESCE(p.kuantitas_bal, 0) as total_bal,
                COALESCE(pg.harga_per_bungkus, 0) as harga_per_bungkus
            FROM produksi p
            LEFT JOIN karyawan k ON p.id_karyawan = k.id
            LEFT JOIN produk pr ON p.id_produk = pr.id
            LEFT JOIN kelompok_harga_produk pg ON pr.id_kelompok_harga = pg.id
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
            $upah = (float)$row['total_qty'] * (float)$row['harga_per_bungkus'];
            
            $groupedData[$date]['total_qty'] += (int)$row['total_qty'];
            $groupedData[$date]['total_bal'] += (float)$row['total_bal'];
            $groupedData[$date]['total_upah'] += $upah;
            $groupedData[$date]['details'][] = [
                'employee_name' => $row['employee_name'],
                'product_name'  => $row['product_name'],
                'qty'           => (int)$row['total_qty'],
                'bal'           => (float)$row['total_bal'],
                'upah'          => $upah
            ];
        }

        view('rekap/production', [
            'title'       => 'Rekap Produksi – Salary',
            'pageTitle'   => 'Rekap Produksi',
            'pageKey'     => 'rekap',
            'start_date'  => $start_date,
            'end_date'    => $end_date,
            'groupedData' => $groupedData,
        ]);
    }

    public function overtime(): void
    {
        requireLogin();
        checkPermission('rekap');

        $db = getDB();
        [$start_date, $end_date] = $this->sanitizeDates($_GET['start_date'] ?? null, $_GET['end_date'] ?? null);

        // Untuk lembur, ada bulanan (nominal) dan borongan (kuantitas)
        $stmtBulanan = $db->prepare("
            SELECT k.id, k.name, k.tipe_gaji, COALESCE(SUM(a.lembur_nominal), 0) as total_uang_lembur
            FROM absensi a
            JOIN karyawan k ON a.id_karyawan = k.id
            WHERE a.date BETWEEN ? AND ? AND k.tipe_gaji = 'bulanan' AND a.lembur_nominal > 0
            GROUP BY k.id, k.name, k.tipe_gaji
            ORDER BY k.name ASC
        ");
        $stmtBulanan->execute([$start_date, $end_date]);
        $dataBulanan = $stmtBulanan->fetchAll();

        $stmtBorongan = $db->prepare("
            SELECT k.id, k.name, k.tipe_gaji, COALESCE(SUM(p.lembur_kuantitas * COALESCE(pg.harga_per_bungkus, 0)), 0) as total_uang_lembur
            FROM produksi p
            JOIN karyawan k ON p.id_karyawan = k.id
            LEFT JOIN produk pr ON p.id_produk = pr.id
            LEFT JOIN kelompok_harga_produk pg ON pr.id_kelompok_harga = pg.id
            WHERE p.date BETWEEN ? AND ? AND p.lembur_kuantitas > 0
            GROUP BY k.id, k.name, k.tipe_gaji
            ORDER BY k.name ASC
        ");
        $stmtBorongan->execute([$start_date, $end_date]);
        $dataBorongan = $stmtBorongan->fetchAll();

        $data = array_merge($dataBulanan, $dataBorongan);
        usort($data, function($a, $b) {
            $cmp = strcmp((string)$a['tipe_gaji'], (string)$b['tipe_gaji']);
            if ($cmp === 0) {
                return strcmp((string)$a['name'], (string)$b['name']);
            }
            return $cmp;
        });

        view('rekap/overtime', [
            'title'      => 'Rekap Lembur – Salary',
            'pageTitle'  => 'Rekap Lembur',
            'pageKey'    => 'rekap',
            'start_date' => $start_date,
            'end_date'   => $end_date,
            'data'       => $data,
        ]);
    }

    public function employee(): void
    {
        requireLogin();
        checkPermission('rekap');

        $db = getDB();
        [$start_date, $end_date] = $this->sanitizeDates($_GET['start_date'] ?? null, $_GET['end_date'] ?? null);
        $emp_id = (int)($_GET['id_karyawan'] ?? 0);

        $empModel = new Employee();
        $employees = $empModel->getAllActive();

        $empData = null;
        $stats = [
            'hadir'             => 0,
            'absen'             => 0,
            'telat'             => 0,
            'produksi_reguler'  => 0.0,
            'uang_lembur'       => 0.0,
            'kasbon_sisa'       => 0.0,
            'tabungan_saldo'    => 0.0,
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
                    $d = $a['date'];
                    if ((int)$a['hadir'] === 1) {
                        $stats['hadir']++;
                        if ((int)$a['telat'] === 1) {
                            $stats['telat']++;
                        }
                    } elseif (!empty($a['catatan'])) {
                        $stats['absen']++;
                    }
                    
                    if ($empData['tipe_gaji'] === 'bulanan' && (float)$a['lembur_nominal'] > 0) {
                        $stats['uang_lembur'] += (float)$a['lembur_nominal'];
                    }

                    $logs[$d]['absensi'] = $a;
                }

                // Produksi & Lembur Borongan
                if ($empData['tipe_gaji'] === 'borongan') {
                    $prodStmt = $db->prepare("
                        SELECT p.*, pr.name as product_name, COALESCE(pg.harga_per_bungkus, 0) as harga_per_bungkus 
                        FROM produksi p
                        LEFT JOIN produk pr ON p.id_produk = pr.id
                        LEFT JOIN kelompok_harga_produk pg ON pr.id_kelompok_harga = pg.id
                        WHERE p.id_karyawan = ? AND p.date BETWEEN ? AND ?
                    ");
                    $prodStmt->execute([$emp_id, $start_date, $end_date]);
                    $prodLogs = $prodStmt->fetchAll();

                    foreach ($prodLogs as $p) {
                        $d = $p['date'];
                        $uangProd = (float)$p['kuantitas'] * (float)$p['harga_per_bungkus'];
                        $stats['produksi_reguler'] += $uangProd;
                        
                        $uangLembur = (float)$p['lembur_kuantitas'] * (float)$p['harga_per_bungkus'];
                        $stats['uang_lembur'] += $uangLembur;

                        $logs[$d]['produksi'][] = $p;
                    }
                }

                // Kasbon Sisa
                $kasbonStmt = $db->prepare("SELECT COALESCE(SUM(sisa_nominal), 0) FROM kasbon WHERE id_karyawan = ? AND status != 'lunas'");
                $kasbonStmt->execute([$emp_id]);
                $stats['kasbon_sisa'] = (float)$kasbonStmt->fetchColumn();

                // Peminjaman Kasbon (Baru)
                $pinjamStmt = $db->prepare("SELECT id, keterangan, total_nominal, DATE(created_at) as date FROM kasbon WHERE id_karyawan = ? AND DATE(created_at) BETWEEN ? AND ?");
                $pinjamStmt->execute([$emp_id, $start_date, $end_date]);
                $pinjamLogs = $pinjamStmt->fetchAll();
                foreach ($pinjamLogs as $k) {
                    $d = $k['date'];
                    $logs[$d]['kasbon'][] = [
                        'type'       => 'pinjam',
                        'nominal'    => (float)$k['total_nominal'],
                        'keterangan' => $k['keterangan'] ?: 'Pinjam Kasbon',
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
                    $d = $b['date'];
                    $logs[$d]['kasbon'][] = [
                        'type'       => 'bayar',
                        'nominal'    => (float)$b['nominal'],
                        'keterangan' => $b['catatan'] ?: ($b['method'] === 'payroll' ? 'Dipotong dari gaji' : 'Pembayaran cicilan'),
                    ];
                }

                // Saldo Tabungan
                $savingModel = new Saving();
                $stats['tabungan_saldo'] = $savingModel->getBalance($emp_id);

                // Transaksi Tabungan
                $tabunganStmt = $db->prepare("
                    SELECT id, tipe, jumlah, sumber, tanggal as date, keterangan
                    FROM transaksi_tabungan
                    WHERE id_karyawan = ? AND tanggal BETWEEN ? AND ?
                ");
                $tabunganStmt->execute([$emp_id, $start_date, $end_date]);
                $tabunganLogs = $tabunganStmt->fetchAll();
                foreach ($tabunganLogs as $t) {
                    $d = $t['date'];
                    $logs[$d]['tabungan'][] = [
                        'tipe'       => $t['tipe'],
                        'jumlah'     => (float)$t['jumlah'],
                        'keterangan' => $t['keterangan'] ?: ($t['tipe'] === 'deposit' ? 'Setoran Tabungan' : 'Penarikan Tabungan'),
                        'sumber'     => $t['sumber'],
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
            'logs'       => $logs,
        ]);
    }
}
