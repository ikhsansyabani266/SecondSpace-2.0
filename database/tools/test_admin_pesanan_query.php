<?php
require_once __DIR__ . '/../../db.php';
$res = $conn->query("SELECT orders.*, users.name AS customer_name, users.email AS customer_email FROM orders INNER JOIN users ON orders.user_id = users.id");
if (!$res) {
    echo "SQL ERROR: " . $conn->error . "\n";
} else {
    echo "Query OK\n";
}
