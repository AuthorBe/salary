<?php
/**
 * config/database.php
 * PDO connection singleton.
 *
 * Menggunakan environment variable dari .env — TIDAK hardcode credential.
 * WAJIB pakai prepared statement di seluruh aplikasi (cegah SQL Injection).
 *
 * Cara pakai:
 *   $db = getDB();
 *   $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
 *   $stmt->execute([$id]);
 */

function getDB(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $host     = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost';
    $port     = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '3306';
    $dbname   = $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: 'salary';
    $username = $_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME') ?: 'root';
    $password = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '';

    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // Lempar exception jika query gagal
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // Default fetch sebagai array asosiatif
        PDO::ATTR_EMULATE_PREPARES   => false,                    // Pakai native prepared statement
    ];

    try {
        $pdo = new PDO($dsn, $username, $password, $options);
    } catch (PDOException $e) {
        // Jangan expose detail credential di pesan error
        if (($_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG')) === 'true') {
            throw new RuntimeException('Database connection failed: ' . $e->getMessage());
        }
        throw new RuntimeException('Koneksi database gagal. Periksa konfigurasi di file .env.');
    }

    return $pdo;
}
