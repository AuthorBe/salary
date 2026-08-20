<?php
declare(strict_types=1);

/**
 * database/setup.php
 * Script CLI untuk inisialisasi database dari awal.
 *
 * Tugas:
 * 1. Baca .env untuk dapatkan DB credential
 * 2. Connect ke MySQL (tanpa nama DB dulu)
 * 3. Create database jika belum ada
 * 4. Connect ke database tersebut
 * 5. Eksekusi semua file .sql di folder migrations/ (urut abjad)
 * 6. Eksekusi semua file .sql di folder seeds/ (urut abjad)
 * 7. Buat 1 user Admin default (password di-hash pakai bcrypt, tidak plain text SQL)
 *
 * Cara eksekusi:
 * php database/setup.php
 */

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/config/env.php';

echo "Memulai setup database Salary...\n";
echo "--------------------------------------------------------\n";

$host     = getenv('DB_HOST')     ?: 'localhost';
$port     = getenv('DB_PORT')     ?: '3306';
$dbname   = getenv('DB_DATABASE') ?: 'salary';
$username = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';

// ── 1. Create Database ──────────────────────────────────────────────────
try {
    $pdo = new PDO("mysql:host=$host;port=$port", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Database '$dbname' berhasil dibuat (atau sudah ada).\n";
} catch (PDOException $e) {
    die("❌ Gagal membuat database: " . $e->getMessage() . "\n");
}

// ── 2. Connect ke Database Baru ─────────────────────────────────────────
try {
    $db = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Berhasil connect ke database '$dbname'.\n";
} catch (PDOException $e) {
    die("❌ Gagal connect ke database: " . $e->getMessage() . "\n");
}

// ── Fungsi Helper untuk Eksekusi Folder SQL ─────────────────────────────
function executeSqlFolder(PDO $db, string $folderPath, string $label): void
{
    if (!is_dir($folderPath)) {
        echo "⚠️ Folder $label tidak ditemukan: $folderPath\n";
        return;
    }

    $files = glob($folderPath . '/*.sql');
    sort($files); // Pastikan eksekusi berurutan (001_, 002_, dst)

    if (empty($files)) {
        echo "⚠️ Tidak ada file SQL di folder $label.\n";
        return;
    }

    foreach ($files as $file) {
        $basename = basename($file);
        $sql = file_get_contents($file);

        if (empty(trim($sql))) continue;

        try {
            $db->exec($sql);
            echo "✅ [OK] $basename\n";
        } catch (PDOException $e) {
            echo "❌ [GAGAL] $basename: " . $e->getMessage() . "\n";
        }
    }
}

// ── 3. Run Migrations ───────────────────────────────────────────────────
echo "\nMenjalankan Migrations...\n";
executeSqlFolder($db, __DIR__ . '/migrations', 'Migrations');

// ── 4. Run Seeds ────────────────────────────────────────────────────────
echo "\nMenjalankan Seeds...\n";
executeSqlFolder($db, __DIR__ . '/seeds', 'Seeds');

// ── 5. Buat User Admin Default ──────────────────────────────────────────
echo "\nMembuat User Admin Default...\n";
try {
    // Cek apakah admin sudah ada
    $stmt = $db->prepare("SELECT id FROM pengguna WHERE nama_pengguna = 'admin'");
    $stmt->execute();
    if ($stmt->fetch()) {
        echo "ℹ️ User 'admin' sudah ada, dilewati.\n";
    } else {
        // Ambil role ID untuk Admin
        $stmtRole = $db->prepare("SELECT id FROM peran WHERE name = 'Admin'");
        $stmtRole->execute();
        $roleId = $stmtRole->fetchColumn();

        if (!$roleId) {
             throw new Exception("Role 'Admin' tidak ditemukan di database. Pastikan seed role berhasil.");
        }

        $passwordHash = password_hash('Admin123!', PASSWORD_BCRYPT);
        $stmtInsert = $db->prepare(
            "INSERT INTO pengguna (name, nama_pengguna, kata_sandi, id_peran) VALUES ('Administrator', 'admin', ?, ?)"
        );
        $stmtInsert->execute([$passwordHash, $roleId]);
        echo "✅ User 'admin' berhasil dibuat.\n";
        echo "   Username: admin\n";
        echo "   Password: Admin123!\n";
    }
} catch (Exception $e) {
    echo "❌ Gagal membuat user admin: " . $e->getMessage() . "\n";
}

// ── 6. Buat User Developer (Superuser) ──────────────────────────────────
echo "\nMembuat User Developer (Superuser)...\n";
try {
    $stmt = $db->prepare("SELECT id FROM pengguna WHERE nama_pengguna = 'developer'");
    $stmt->execute();
    if ($stmt->fetch()) {
        // Pastikan superuser = 1
        $db->exec("UPDATE pengguna SET superuser = 1 WHERE nama_pengguna = 'developer'");
        echo "ℹ️ User 'developer' sudah ada — superuser dikonfirmasi 1.\n";
    } else {
        $stmtRole = $db->query("SELECT id FROM peran ORDER BY id LIMIT 1");
        $roleId   = $stmtRole->fetchColumn();

        $passwordHash = password_hash('developer123', PASSWORD_BCRYPT);
        $db->prepare(
            "INSERT INTO pengguna (name, nama_pengguna, kata_sandi, id_peran, superuser) VALUES ('Developer', 'developer', ?, ?, 1)"
        )->execute([$passwordHash, $roleId]);

        echo "✅ User 'developer' berhasil dibuat.\n";
        echo "   Username: developer\n";
        echo "   Password: developer123\n";
        echo "   ⚠️  Ganti password ini setelah pertama kali login!\n";
    }
} catch (Exception $e) {
    echo "❌ Gagal membuat user developer: " . $e->getMessage() . "\n";
}

echo "\n--------------------------------------------------------\n";
echo "🎉 Setup database selesai!\n";

