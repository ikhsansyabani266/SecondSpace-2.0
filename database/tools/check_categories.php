<?php
require_once __DIR__ . '/../../db.php';
$r = $conn->query("SELECT DISTINCT category FROM products");
echo "Categories in products:\n";
while ($row = $r->fetch_row()) {
    echo "- " . $row[0] . "\n";
}
$allProducts = $conn->query("SELECT id, name, category, price, stock, `condition`, image, material, size FROM products");
echo "\nAll products in DB:\n";
while ($p = $allProducts->fetch_assoc()) {
    echo json_encode($p, JSON_UNESCAPED_SLASHES) . "\n";
}
