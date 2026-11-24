<?php
require_once __DIR__ . '/../../db.php';
$res = $conn->query("SELECT id, sku, name, image FROM products WHERE image = '0' OR image = '' OR image IS NULL");
while ($r = $res->fetch_assoc()) {
    echo "ID: {$r['id']} | SKU: {$r['sku']} | Name: {$r['name']} | Image: {$r['image']}\n";
}
