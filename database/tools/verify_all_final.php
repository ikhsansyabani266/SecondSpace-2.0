<?php
require_once __DIR__ . '/../../db.php';

$res = $conn->query("SELECT id, sku, name, category, brand, size, `condition`, price, stock, is_demo, image FROM products ORDER BY id ASC");
printf("%-4s | %-8s | %-42s | %-8s | %-10s | %-12s | %-4s | %-5s | %-5s\n", "ID", "SKU", "Nama", "Kategori", "Harga", "Kondisi", "Size", "Stock", "Demo");
echo str_repeat("-", 115) . "\n";
while ($r = $res->fetch_assoc()) {
    printf("%-4d | %-8s | %-42s | %-8s | Rp %-7s | %-12s | %-4s | %-5d | %-5d\n",
        $r['id'], $r['sku'], mb_strimwidth($r['name'], 0, 42, "..."),
        $r['category'], number_format($r['price'], 0, ',', '.'),
        $r['condition'], $r['size'], $r['stock'], $r['is_demo']
    );
}

echo "\nVERIFIKASI RELASI GAMBAR PER PRODUK:\n";
$imgRes = $conn->query("SELECT product_id, COUNT(*) as cnt, GROUP_CONCAT(CONCAT(sort_order, ':', type) ORDER BY sort_order ASC) as types FROM product_images GROUP BY product_id ORDER BY product_id ASC");
while ($ir = $imgRes->fetch_assoc()) {
    echo "Produk ID {$ir['product_id']} -> {$ir['cnt']} gambar ({$ir['types']})\n";
}
