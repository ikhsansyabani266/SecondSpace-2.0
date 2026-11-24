<?php
// Skrip untuk Memindahkan dan Memperbarui Path File User ke /user/
$root = dirname(__DIR__, 2);
$userDir = $root . '/user';

$userFiles = [
    'index.php',
    'produk.php',
    'detail.php',
    'keranjang.php',
    'checkout.php',
    'pembayaran.php',
    'konfirmasi.php',
    'pesanan.php',
    'detail_pesanan.php',
    'contact.php',
    'alamat.php',
    'login.php',
    'register.php',
    'logout.php',
    'auth.php',
];

foreach ($userFiles as $f) {
    $src = $root . '/' . $f;
    $dst = $userDir . '/' . $f;
    if (file_exists($src)) {
        copy($src, $dst);
        echo "Copied: $f -> user/$f\n";
    }
}
