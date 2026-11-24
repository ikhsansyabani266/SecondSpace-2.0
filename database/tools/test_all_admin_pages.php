<?php
// Test rendered size for all admin pages
$_SESSION['user_id'] = 1;
$_SESSION['admin_id'] = 1;
$_SESSION['role'] = 'admin';
$_SESSION['username'] = 'admin';
$_SESSION['admin_name'] = 'Administrator';
$_SESSION['name'] = 'Administrator';

$adminPages = [
    'admin/login.php',
    'admin/sidebar.php',
    'admin/index.php',
    'admin/produk.php',
    'admin/produk_form.php',
    'admin/kategori.php',
    'admin/stok.php',
    'admin/pesanan.php',
    'admin/detail_pesanan.php?id=1',
    'admin/pesan.php',
    'admin/konfirmasi_hapus.php?tipe=pesan&id=1'
];

foreach ($adminPages as $p) {
    $parsed = parse_url($p);
    $path = $parsed['path'];
    if (isset($parsed['query'])) {
        parse_str($parsed['query'], $_GET);
    } else {
        $_GET = [];
    }

    ob_start();
    try {
        include __DIR__ . '/../../' . $path;
        $html = ob_get_clean();
        echo "[OK] {$p} -> Rendered " . strlen($html) . " bytes\n";
    } catch (Throwable $e) {
        ob_end_clean();
        echo "[ERROR] {$p} -> Exception: " . $e->getMessage() . "\n";
    }
}
