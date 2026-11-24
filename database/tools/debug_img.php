<?php
require_once __DIR__ . '/../../db.php';
$res = $conn->query("SELECT DISTINCT image FROM products");
while ($r = $res->fetch_row()) {
    echo "P image: " . var_export($r[0], true) . "\n";
}
$res2 = $conn->query("SELECT DISTINCT image FROM product_images");
while ($r2 = $res2->fetch_row()) {
    if (!file_exists(__DIR__ . '/../../images/' . $r2[0])) {
        echo "Missing in images/: " . $r2[0] . "\n";
    }
    if (!file_exists(__DIR__ . '/../../uploads/produk/' . $r2[0])) {
        echo "Missing in uploads/produk/: " . $r2[0] . "\n";
    }
}
