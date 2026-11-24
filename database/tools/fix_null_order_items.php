<?php
require_once __DIR__ . '/../../shared/koneksi.php';
/** @var mysqli $conn */

$sql = "UPDATE order_items oi JOIN products p ON oi.product_id = p.id SET oi.product_name = p.name WHERE oi.product_name IS NULL OR oi.product_name = ''";
if ($conn->query($sql)) {
    echo "Updated: " . $conn->affected_rows . " rows.\n";
} else {
    echo "Error: " . $conn->error . "\n";
}
