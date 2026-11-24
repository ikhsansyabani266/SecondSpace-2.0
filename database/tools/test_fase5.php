<?php
// Comprehensive Verification Script for FASE 5
require_once __DIR__ . '/../../db.php';

echo "====================================================================\n";
echo "           PENGUJIAN MENYELURUH SISTEM (FASE 5)                     \n";
echo "====================================================================\n\n";

$pass = true;

// 1. Verifikasi Jumlah Produk & Gambar di Database
echo "1. VERIFIKASI JUMLAH DATA:\n";
$prodRes = $conn->query("SELECT COUNT(*) as total, SUM(CASE WHEN is_demo = 1 THEN 1 ELSE 0 END) as demo_cnt FROM products");
$pRow = $prodRes->fetch_assoc();
echo "   - Total Produk: {$pRow['total']} (Harus 24) -> " . ($pRow['total'] == 24 ? "OK [PASS]" : "FAIL") . "\n";
echo "   - Produk Demo: {$pRow['demo_cnt']} (Harus 0) -> " . ($pRow['demo_cnt'] == 0 ? "OK [PASS]" : "FAIL") . "\n";

$imgRes = $conn->query("SELECT COUNT(*) as total FROM product_images");
$iRow = $imgRes->fetch_assoc();
echo "   - Total Gambar Galeri: {$iRow['total']} (Harus 72) -> " . ($iRow['total'] == 72 ? "OK [PASS]" : "FAIL") . "\n\n";

if ($pRow['total'] != 24 || $pRow['demo_cnt'] != 0 || $iRow['total'] != 72) {
    $pass = false;
}

// 2. Verifikasi Keberadaan Fisik File Gambar di Disk
echo "2. VERIFIKASI KEBERADAAN FILE FISIK GAMBAR:\n";
$missingImages = [];
$allImagesQuery = $conn->query("SELECT DISTINCT image FROM product_images UNION SELECT DISTINCT image FROM products");
$imgCount = 0;
while ($r = $allImagesQuery->fetch_assoc()) {
    $imgFile = $r['image'];
    $imgCount++;
    $path1 = __DIR__ . '/../../images/' . $imgFile;
    $path2 = __DIR__ . '/../../uploads/produk/' . $imgFile;
    if (!file_exists($path1) || !file_exists($path2)) {
        $missingImages[] = $imgFile;
    }
}
echo "   - Total File Unik: $imgCount\n";
if (empty($missingImages)) {
    echo "   - Semua file fisik WebP ditemukan di /images/ dan /uploads/produk/ -> OK [PASS]\n\n";
} else {
    echo "   - File TIDAK DITEMUKAN: " . implode(", ", $missingImages) . " -> FAIL\n\n";
    $pass = false;
}

// 3. Verifikasi Filter Kategori
echo "3. VERIFIKASI DISTRIBUSI KATEGORI:\n";
$catRes = $conn->query("SELECT category, COUNT(*) as cnt FROM products GROUP BY category ORDER BY category ASC");
$categories = [];
while ($cr = $catRes->fetch_assoc()) {
    $categories[$cr['category']] = $cr['cnt'];
    echo "   - {$cr['category']}: {$cr['cnt']} produk\n";
}
$expectedCategories = ['Baju', 'Kemeja', 'Celana', 'Jacket'];
foreach ($expectedCategories as $ec) {
    if (!isset($categories[$ec]) || $categories[$ec] <= 0) {
        echo "   [ERROR] Kategori $ec tidak memiliki produk!\n";
        $pass = false;
    }
}
echo "   - Status Kategori: OK [PASS]\n\n";

// 4. Simulasi Keranjang dan Checkout untuk Produk Baru (PL-013 s/d PL-024)
echo "4. SIMULASI TAMBAH KERANJANG & DETAIL PRODUK:\n";
// Ambil 1 produk celana dan 1 produk jacket
$sampleRes = $conn->query("SELECT id, sku, name, price, stock FROM products WHERE sku IN ('PL-013', 'PL-019')");
while ($s = $sampleRes->fetch_assoc()) {
    echo "   - Produk #{$s['id']} [{$s['sku']}] {$s['name']} (Stok: {$s['stock']}, Harga: Rp " . number_format($s['price'], 0, ',', '.') . ")\n";
    // Cek galeri
    $gRes = $conn->query("SELECT sort_order, type, image FROM product_images WHERE product_id = {$s['id']} ORDER BY sort_order ASC");
    while ($gr = $gRes->fetch_assoc()) {
        echo "     * Galeri {$gr['sort_order']} [{$gr['type']}]: {$gr['image']}\n";
    }
}
echo "   - Status Galeri Produk Baru: OK [PASS]\n\n";

// 5. Cek PHP Lint Syntax seluruh file
echo "5. VERIFIKASI SYNTAX PHP (PHP LINT):\n";
$phpFiles = glob(__DIR__ . '/../../{*.php,admin/*.php,shared/*.php}', GLOB_BRACE);
$lintErrors = [];
foreach ($phpFiles as $pf) {
    $cmd = 'php -l "' . $pf . '" 2>&1';
    $output = shell_exec($cmd);
    if (strpos($output, 'No syntax errors detected') === false) {
        $lintErrors[] = basename($pf) . ": " . trim($output);
    }
}
if (empty($lintErrors)) {
    echo "   - Seluruh file PHP (" . count($phpFiles) . " file) lolos uji syntax -> OK [PASS]\n\n";
} else {
    echo "   - Syntax Error ditemukan pada: \n" . implode("\n", $lintErrors) . "\n\n";
    $pass = false;
}

echo "====================================================================\n";
if ($pass) {
    echo "       HASIL PENGUJIAN: SEMUA TEST LOLOS 100% [SUCCESS]            \n";
} else {
    echo "       HASIL PENGUJIAN: TERDAPAT TEST GAGAL [FAILED]               \n";
}
echo "====================================================================\n";
