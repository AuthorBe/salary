<?php
$host = '127.0.0.1';
$db   = 'salary';
$user = 'root';
$pass = '';

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    $tables = ['karyawan', 'produk', 'produksi', 'kehadiran', 'product_groups'];
    
    foreach ($tables as $table) {
        echo "=== TABLE: $table ===\n";
        $stmt = $pdo->query("DESCRIBE $table");
        while ($row = $stmt->fetch()) {
            echo "{$row['Field']} - {$row['Type']} (Null: {$row['Null']}, Default: {$row['Default']})\n";
        }
        echo "\n";
    }

} catch (PDOException $e) {
    echo $e->getMessage();
}
