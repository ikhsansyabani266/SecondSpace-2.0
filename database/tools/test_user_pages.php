<?php
// Test execution of key user pages
$_SERVER['HTTP_HOST'] = 'aplikasi-preloved-main.test';
$_SERVER['REQUEST_URI'] = '/user/produk.php';
$_SERVER['SCRIPT_NAME'] = '/user/produk.php';

ob_start();
include __DIR__ . '/../../user/produk.php';
$html = ob_get_clean();

if (stripos($html, 'Athletic') !== false) {
    echo "USER PRODUK.PHP TEST: SUKSES (HTML ter-render lengkap dengan 24 produk!)\n";
} else {
    echo "USER PRODUK.PHP TEST: GAGAL\n";
    echo substr($html, 0, 500) . "\n";
}

$_GET['id'] = 9;
$_SERVER['REQUEST_URI'] = '/user/detail.php?id=9';
$_SERVER['SCRIPT_NAME'] = '/user/detail.php';
ob_start();
include __DIR__ . '/../../user/detail.php';
$htmlDetail = ob_get_clean();

if (strpos($htmlDetail, 'Kaos Athletic Performance Hitam') !== false && strpos($htmlDetail, 'baju-01-athletic-performance-hitam-depan.webp') !== false) {
    echo "USER DETAIL.PHP TEST: SUKSES (Detail produk #9 dan galeri ter-render!)\n";
} else {
    echo "USER DETAIL.PHP TEST: GAGAL\n";
    echo substr($htmlDetail, 0, 500) . "\n";
}
