<?php
require_once __DIR__ . '/../../db.php';

$totalProds = $conn->query("SELECT COUNT(*) FROM products")->fetch_row()[0];
$totalDemo = $conn->query("SELECT COUNT(*) FROM products WHERE is_demo = 1")->fetch_row()[0];
$totalImages = $conn->query("SELECT COUNT(*) FROM product_images")->fetch_row()[0];

echo "VERIFIKASI DATABASE:\n";
echo "- Total Produk Total : $totalProds (8 lama + 12 baru)\n";
echo "- Total Produk Demo  : $totalDemo\n";
echo "- Total Relasi Gambar: $totalImages\n\n";

$res = $conn->query("
    SELECT p.id, p.sku, p.name, p.category, p.brand, p.price, p.size, p.condition, COUNT(pi.id) as total_imgs
    FROM products p
    LEFT JOIN product_images pi ON p.id = pi.product_id
    WHERE p.is_demo = 1
    GROUP BY p.id
    ORDER BY p.id ASC
");

printf("%-4s | %-10s | %-40s | %-8s | %-10s | %-8s | %-4s | %s\n",
    "ID", "SKU", "Nama", "Kategori", "Harga", "Kondisi", "Size", "Jml Foto"
);
echo str_repeat("-", 105) . "\n";
while ($row = $res->fetch_assoc()) {
    printf("%-4s | %-10s | %-40s | %-8s | Rp %-6s | %-8s | %-4s | %d foto\n",
        $row['id'], $row['sku'], mb_substr($row['name'], 0, 40), $row['category'],
        number_format($row['price'], 0, ',', '.'), $row['condition'], $row['size'], $row['total_imgs']
    );
}
