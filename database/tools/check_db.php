<?php
// CLI Tool: Membaca struktur database tanpa mengubah apa pun (Hanya SELECT / SHOW / DESCRIBE)
require_once __DIR__ . '/../../db.php';

echo "=== STATUS KONEKSI DATABASE ===\n";
if (!$conn || $conn->connect_error) {
    die("Koneksi gagal: " . ($conn ? $conn->connect_error : "Unknown error") . "\n");
}
echo "Berhasil terhubung ke database: " . $conn->query("SELECT DATABASE()")->fetch_row()[0] . "\n";
echo "Host info: " . $conn->host_info . "\n\n";

echo "=== DAFTAR TABEL ===\n";
$tablesRes = $conn->query("SHOW TABLES");
$tables = [];
while ($row = $tablesRes->fetch_row()) {
    $tables[] = $row[0];
    echo "- " . $row[0] . "\n";
}
echo "\n";

foreach ($tables as $t) {
    echo "=== STRUKTUR TABEL: $t ===\n";
    $descRes = $conn->query("DESCRIBE `$t`");
    printf("%-20s %-25s %-8s %-8s %-15s\n", "Field", "Type", "Null", "Key", "Default");
    echo str_repeat("-", 80) . "\n";
    while ($row = $descRes->fetch_assoc()) {
        printf("%-20s %-25s %-8s %-8s %-15s\n", $row['Field'], $row['Type'], $row['Null'], $row['Key'], (string)$row['Default']);
    }
    echo "\n";
}

// Cek isi kategori jika ada tabel categories / sejenis
if (in_array('categories', $tables)) {
    echo "=== DATA TABEL: categories ===\n";
    $catRes = $conn->query("SELECT * FROM categories");
    while ($r = $catRes->fetch_assoc()) {
        echo json_encode($r) . "\n";
    }
    echo "\n";
}

// Cek isi produk yang sudah ada
if (in_array('products', $tables)) {
    echo "=== DATA TABEL: products (Sampel) ===\n";
    $prodCount = $conn->query("SELECT COUNT(*) FROM products")->fetch_row()[0];
    echo "Total produk saat ini: $prodCount\n";
    $prodRes = $conn->query("SELECT * FROM products LIMIT 5");
    while ($r = $prodRes->fetch_assoc()) {
        echo json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    }
    echo "\n";
}
