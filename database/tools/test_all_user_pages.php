<?php
// Test execution of all user pages
$_SERVER['HTTP_HOST'] = 'aplikasi-preloved-main.test';
$_SERVER['REQUEST_METHOD'] = 'GET';
session_start();
$_SESSION['user_id'] = 2; // Simulated login as user ikhsan
$_SESSION['username'] = 'ikhsan123';
$_SESSION['name'] = 'ikhsan';
$_SESSION['role'] = 'user';

$userPages = [
    'index.php',
    'produk.php',
    'detail.php?id=9',
    'keranjang.php',
    'pesanan.php',
    'detail_pesanan.php?id=1',
    'contact.php',
    'alamat.php',
    'login.php',
    'register.php',
];

$allOk = true;

foreach ($userPages as $pageInfo) {
    $parts = explode('?', $pageInfo);
    $page = $parts[0];
    $_GET = [];
    if (isset($parts[1])) {
        parse_str($parts[1], $_GET);
    }
    $_SERVER['SCRIPT_NAME'] = '/user/' . $page;
    $_SERVER['REQUEST_URI'] = '/user/' . $pageInfo;
    
    ob_start();
    try {
        include __DIR__ . '/../../user/' . $page;
        $html = ob_get_clean();
        if (strlen($html) > 500) {
            echo "[OK] user/$page -> Rendered " . strlen($html) . " bytes\n";
        } else {
            echo "[WARN] user/$page -> HTML short (" . strlen($html) . " bytes)\n";
        }
    } catch (Throwable $e) {
        ob_end_clean();
        echo "[ERROR] user/$page -> " . $e->getMessage() . "\n";
        $allOk = false;
    }
}

if ($allOk) {
    echo "\nSEMUA HALAMAN USER LOLOS UJI RENDER 100%!\n";
} else {
    echo "\nADA HALAMAN USER YANG MENGALAMI KENDALA!\n";
}
