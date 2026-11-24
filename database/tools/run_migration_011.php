<?php
require_once __DIR__ . '/../../shared/koneksi.php';

$sqlFile = __DIR__ . '/../migrations/011_fase5_schema_dan_audit_trail.sql';
$sql = file_get_contents($sqlFile);

// Pisahkan query berdasarkan titik koma
$queries = explode(';', $sql);
$success = 0;
$errors = [];

foreach ($queries as $q) {
    $q = trim($q);
    // Hapus baris komentar
    $lines = explode("\n", $q);
    $cleanLines = [];
    foreach ($lines as $line) {
        $trim = trim($line);
        if ($trim && strpos($trim, '--') !== 0) {
            $cleanLines[] = $line;
        }
    }
    $cleanQuery = trim(implode("\n", $cleanLines));
    
    if (!empty($cleanQuery)) {
        try {
            if ($conn->query($cleanQuery)) {
                $success++;
            } else {
                $errors[] = $conn->error . " (Query: " . substr($cleanQuery, 0, 60) . "...)";
            }
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

echo "=== HASIL EKSEKUSI MIGRASI 011 ===\n";
echo "Query Berhasil: $success\n";
if (!empty($errors)) {
    echo "Peringatan / Error:\n" . implode("\n", $errors) . "\n";
} else {
    echo "Status: SUKSES 100% tanpa error!\n";
}
