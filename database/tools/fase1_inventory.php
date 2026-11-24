<?php
require_once __DIR__ . '/../../db.php';

echo "=== FASE 1: INVENTARISASI SISTEM & FOTO PRODUK ===\n\n";

// 1. KONEKSI & STRUKTUR DATABASE
echo "--- 1. DATABASE TABLES & STRUCTURE ---\n";
$tables = ['products', 'categories', 'product_images'];
foreach ($tables as $tbl) {
    echo "\n[Table: $tbl]\n";
    $res = $conn->query("SHOW COLUMNS FROM `$tbl`");
    if ($res) {
        while ($col = $res->fetch_assoc()) {
            echo " - {$col['Field']} ({$col['Type']}) " . ($col['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . " Default: {$col['Default']}\n";
        }
    } else {
        echo " (Tabel tidak ditemukan / error: " . $conn->error . ")\n";
    }
}

// 2. KATEGORI SAAT INI
echo "\n--- 2. DAFTAR KATEGORI SAAT INI ---\n";
$resCat = $conn->query("SELECT * FROM categories ORDER BY id ASC");
if ($resCat) {
    while ($cat = $resCat->fetch_assoc()) {
        echo "ID: {$cat['id']} | Nama: {$cat['name']}\n";
    }
}

// 3. PRODUK SAAT INI DI DATABASE
echo "\n--- 3. DATA PRODUK SAAT INI DI DATABASE ---\n";
$resProd = $conn->query("SELECT id, name, category, price, `condition`, sku, is_demo, image FROM products ORDER BY id ASC");
$existingProducts = [];
if ($resProd) {
    while ($p = $resProd->fetch_assoc()) {
        $existingProducts[] = $p;
        echo "ID: {$p['id']} | SKU: {$p['sku']} | is_demo: {$p['is_demo']} | Nama: {$p['name']} | Kategori: {$p['category']} | Harga: {$p['price']} | Image: {$p['image']}\n";
    }
}
echo "Total produk di database: " . count($existingProducts) . "\n";

// 4. SCAN FOLDER FOTO PRODUK ORIGINAL
echo "\n--- 4. SCANNING FOLDER PRODUK ORIGINAL ---\n";
$baseDir = realpath(__DIR__ . '/../../produk_preloved_original');
echo "Base Directory: $baseDir\n";

if (!is_dir($baseDir)) {
    echo "ERROR: Folder tidak ditemukan!\n";
    exit(1);
}

$folders = scandir($baseDir);
$productFolders = [];

foreach ($folders as $f) {
    if ($f === '.' || $f === '..') continue;
    $fullPath = $baseDir . DIRECTORY_SEPARATOR . $f;
    if (is_dir($fullPath)) {
        $productFolders[$f] = [];
        $files = scandir($fullPath);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            $filePath = $fullPath . DIRECTORY_SEPARATOR . $file;
            if (is_file($filePath)) {
                $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                $size = filesize($filePath);
                $mtime = date('Y-m-d H:i:s', filemtime($filePath));
                $imgInfo = @getimagesize($filePath);
                $dim = $imgInfo ? "{$imgInfo[0]}x{$imgInfo[1]}" : "Corrupt/Not Image";
                $mime = $imgInfo ? $imgInfo['mime'] : 'Unknown';

                $productFolders[$f][] = [
                    'file' => $file,
                    'path' => $filePath,
                    'size' => $size,
                    'size_mb' => round($size / (1024 * 1024), 2),
                    'dim' => $dim,
                    'mime' => $mime,
                    'mtime' => $mtime
                ];
            }
        }
    }
}

echo "Total folder produk ditemukan: " . count($productFolders) . "\n";
$totalFiles = 0;
$totalSize = 0;

foreach ($productFolders as $folderName => $files) {
    $count = count($files);
    $totalFiles += $count;
    echo "\n>> Folder: $folderName ($count files)\n";
    foreach ($files as $idx => $finfo) {
        $totalSize += $finfo['size'];
        echo "   [" . ($idx + 1) . "] {$finfo['file']} | {$finfo['dim']} | {$finfo['size_mb']} MB | {$finfo['mime']}\n";
    }
}

echo "\nTotal seluruh file: $totalFiles file\n";
echo "Total ukuran seluruh file: " . round($totalSize / (1024 * 1024), 2) . " MB\n";
