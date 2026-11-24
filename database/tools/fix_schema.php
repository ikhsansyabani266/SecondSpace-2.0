<?php
require_once __DIR__ . '/../../db.php';

// Cek apakah condition_text sudah ada
$cols = [];
$r = $conn->query("SHOW COLUMNS FROM products");
while ($c = $r->fetch_assoc()) {
    $cols[] = $c['Field'];
}

if (!in_array('condition_text', $cols)) {
    $conn->query("ALTER TABLE products ADD COLUMN `condition_text` VARCHAR(50) NULL AFTER `condition`");
    $conn->query("UPDATE products SET condition_text = `condition` WHERE condition_text IS NULL OR condition_text = ''");
    echo "OK: Kolom condition_text berhasil ditambahkan dan disinkronkan.\n";
} else {
    echo "INFO: Kolom condition_text sudah ada.\n";
}
