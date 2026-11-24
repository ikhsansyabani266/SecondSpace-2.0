<?php
// [FILE] stok.php — Dialihkan ke modul Riwayat Preloved (100% No-JS)
// Karena sistem toko preloved menggunakan model 1 produk = 1 stok unik,
// seluruh manajemen persediaan telah disederhanakan menjadi riwayat status & mutasi atomik.

require_once __DIR__ . '/auth.php';
header("Location: riwayat.php");
exit;
