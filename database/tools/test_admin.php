<?php
require_once __DIR__ . '/../../db.php';
$r = $conn->query("SELECT id, name, category, price, condition_text FROM products LIMIT 1");
if (!$r) {
    echo "ERROR: " . $conn->error . "\n";
} else {
    echo "SUCCESS: condition_text found!\n";
}
