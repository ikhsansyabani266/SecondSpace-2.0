<?php
require_once __DIR__ . '/../../db.php';
$conn->query("UPDATE products SET condition_text = `condition`");
echo "Updated condition_text rows: " . $conn->affected_rows . "\n";
