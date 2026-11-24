<?php
require_once __DIR__ . '/../../shared/koneksi.php';
/** @var mysqli $conn */

$sql = file_get_contents(__DIR__ . '/../migrations/005_persingkat_nama_produk.sql');
if ($conn->multi_query($sql)) {
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());
    echo "MIGRASI 005 SUKSES! Seluruh nama produk telah dipersingkat.\n";
} else {
    echo "GAGAL: " . $conn->error . "\n";
}
