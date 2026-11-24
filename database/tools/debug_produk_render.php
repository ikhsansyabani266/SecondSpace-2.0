<?php
$_SERVER['HTTP_HOST'] = 'aplikasi-preloved-main.test';
$_SERVER['REQUEST_URI'] = '/user/produk.php';
$_SERVER['SCRIPT_NAME'] = '/user/produk.php';

ob_start();
include __DIR__ . '/../../user/produk.php';
$html = ob_get_clean();

echo "Panjang HTML: " . strlen($html) . "\n";
if (strpos($html, 'Athletic') !== false) {
    echo "Found 'Athletic'!\n";
} else {
    echo "Substring not found. Sample products rendered:\n";
    preg_match_all('/<div class="product-name">([^<]+)<\/div>/i', $html, $m);
    print_r($m[1]);
}
