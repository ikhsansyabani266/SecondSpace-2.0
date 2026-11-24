<?php
// CLI Tool: Robust Migration Runner
require_once __DIR__ . '/../../db.php';

echo "=== MEMULAI MIGRASI STRUKTUR DATABASE ===\n";

// 1. Cek kolom pada products
$cols = [];
$r = $conn->query("SHOW COLUMNS FROM products");
while ($c = $r->fetch_assoc()) {
    $cols[] = $c['Field'];
}

if (!in_array('brand', $cols)) {
    if ($conn->query("ALTER TABLE products ADD COLUMN `brand` VARCHAR(100) NULL DEFAULT 'No Brand' AFTER `category`")) {
        echo "OK: Kolom 'brand' berhasil ditambahkan ke tabel products.\n";
    } else {
        echo "ERROR: " . $conn->error . "\n";
    }
} else {
    echo "INFO: Kolom 'brand' sudah ada.\n";
}

if (!in_array('sku', $cols)) {
    if ($conn->query("ALTER TABLE products ADD COLUMN `sku` VARCHAR(50) NULL UNIQUE AFTER `size`")) {
        echo "OK: Kolom 'sku' berhasil ditambahkan ke tabel products.\n";
    } else {
        echo "ERROR: " . $conn->error . "\n";
    }
} else {
    echo "INFO: Kolom 'sku' sudah ada.\n";
}

if (!in_array('is_demo', $cols)) {
    if ($conn->query("ALTER TABLE products ADD COLUMN `is_demo` TINYINT(1) NOT NULL DEFAULT 0 AFTER `sku`")) {
        echo "OK: Kolom 'is_demo' berhasil ditambahkan ke tabel products.\n";
    } else {
        echo "ERROR: " . $conn->error . "\n";
    }
} else {
    echo "INFO: Kolom 'is_demo' sudah ada.\n";
}

// 2. Buat tabel product_images
$createTableSql = "CREATE TABLE IF NOT EXISTS `product_images` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL,
    `image` VARCHAR(255) NOT NULL,
    `sort_order` INT NOT NULL DEFAULT 1,
    `type` ENUM('depan', 'belakang', 'detail') NOT NULL DEFAULT 'depan',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_product_images_product` FOREIGN KEY (`product_id`) 
        REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn->query($createTableSql)) {
    echo "OK: Tabel 'product_images' berhasil dibuat / dipastikan ada.\n";
} else {
    echo "ERROR: " . $conn->error . "\n";
}

echo "=== MIGRASI SELESAI ===\n";
