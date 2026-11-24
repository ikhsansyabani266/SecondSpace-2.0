<?php
// Script Audit Data Eksisting FASE 1 (Tepat sesuai kolom database asli)
require_once __DIR__ . '/../../db.php';

echo "====================================================================\n";
echo "           AUDIT DATA EKSISTING DATABASE (FASE 1)                   \n";
echo "====================================================================\n\n";

$anomalies = [];

// 1. Pesanan tanpa item
$q1 = $conn->query("SELECT o.id, COALESCE(o.order_code, o.order_number) as code FROM orders o LEFT JOIN order_items oi ON o.id = oi.order_id WHERE oi.id IS NULL");
$cnt1 = $q1 ? $q1->num_rows : 0;
$anomalies['pesanan_tanpa_item'] = ['count' => $cnt1, 'sample' => []];
if ($q1) while ($r = $q1->fetch_assoc()) $anomalies['pesanan_tanpa_item']['sample'][] = "#{$r['id']} ({$r['code']})";

// 2. Item tanpa pesanan
$q2 = $conn->query("SELECT oi.id, oi.order_id FROM order_items oi LEFT JOIN orders o ON oi.order_id = o.id WHERE o.id IS NULL");
$cnt2 = $q2 ? $q2->num_rows : 0;
$anomalies['item_tanpa_pesanan'] = ['count' => $cnt2, 'sample' => []];
if ($q2) while ($r = $q2->fetch_assoc()) $anomalies['item_tanpa_pesanan']['sample'][] = "Item #{$r['id']} (order_id: {$r['order_id']})";

// 3. Pesanan dengan pembeli yang tidak ada di tabel users
$q3 = $conn->query("SELECT o.id, COALESCE(o.order_code, o.order_number) as code, o.user_id FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE u.id IS NULL");
$cnt3 = $q3 ? $q3->num_rows : 0;
$anomalies['pesanan_user_tidak_ada'] = ['count' => $cnt3, 'sample' => []];
if ($q3) while ($r = $q3->fetch_assoc()) $anomalies['pesanan_user_tidak_ada']['sample'][] = "Order #{$r['id']} (user_id: {$r['user_id']})";

// 4. Total pesanan vs jumlah (harga x qty) item
$q4 = $conn->query("
    SELECT o.id, COALESCE(o.order_code, o.order_number) as code, COALESCE(o.total, o.total_price) as ord_total, SUM(oi.price * oi.quantity) as calc_total
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    GROUP BY o.id
    HAVING ABS(ord_total - calc_total) > 0.01
");
$cnt4 = $q4 ? $q4->num_rows : 0;
$anomalies['total_pesanan_mismatch'] = ['count' => $cnt4, 'sample' => []];
if ($q4) while ($r = $q4->fetch_assoc()) $anomalies['total_pesanan_mismatch']['sample'][] = "Order #{$r['id']} ({$r['code']}): DB {$r['ord_total']} vs Calc {$r['calc_total']}";

// 5. Stok negatif atau tidak masuk akal (< 0 atau > 100 untuk preloved)
$q5 = $conn->query("SELECT id, sku, name, stock FROM products WHERE stock < 0 OR stock > 50");
$cnt5 = $q5 ? $q5->num_rows : 0;
$anomalies['stok_anomali'] = ['count' => $cnt5, 'sample' => []];
if ($q5) while ($r = $q5->fetch_assoc()) $anomalies['stok_anomali']['sample'][] = "#{$r['id']} ({$r['sku']}) stok: {$r['stock']}";

// 6. Produk tanpa kategori atau kategori kosong
$q6 = $conn->query("SELECT id, sku, name, category FROM products WHERE category IS NULL OR TRIM(category) = ''");
$cnt6 = $q6 ? $q6->num_rows : 0;
$anomalies['produk_tanpa_kategori'] = ['count' => $cnt6, 'sample' => []];
if ($q6) while ($r = $q6->fetch_assoc()) $anomalies['produk_tanpa_kategori']['sample'][] = "#{$r['id']} ({$r['sku']})";

// 7. Nilai status pesanan yang ada di orders
$q7 = $conn->query("SELECT DISTINCT order_status, status, payment_status FROM orders");
$orderStatuses = [];
if ($q7) {
    while ($r = $q7->fetch_assoc()) {
        $orderStatuses[] = sprintf("order_status='%s', status='%s', payment_status='%s'", $r['order_status'], $r['status'], $r['payment_status']);
    }
}

// 8. Path gambar produk fisik yang tidak ada
$q9 = $conn->query("SELECT DISTINCT image FROM products");
$missingProdImages = [];
while ($r = $q9->fetch_assoc()) {
    $file = $r['image'];
    if (!file_exists(__DIR__ . '/../../images/' . $file) && !file_exists(__DIR__ . '/../../uploads/produk/' . $file)) {
        $missingProdImages[] = $file;
    }
}
$anomalies['gambar_produk_hilang'] = ['count' => count($missingProdImages), 'sample' => $missingProdImages];

// 9. Harga nol atau negatif
$q11 = $conn->query("SELECT id, sku, name, price FROM products WHERE price <= 0");
$cnt11 = $q11 ? $q11->num_rows : 0;
$anomalies['harga_nol_negatif'] = ['count' => $cnt11, 'sample' => []];
if ($q11) while ($r = $q11->fetch_assoc()) $anomalies['harga_nol_negatif']['sample'][] = "#{$r['id']} ({$r['sku']}) price: {$r['price']}";

// 10. Tanggal kosong di orders
$q12 = $conn->query("SELECT id, COALESCE(o.order_code, o.order_number) as code FROM orders o WHERE created_at IS NULL OR created_at = '0000-00-00 00:00:00'");
$cnt12 = $q12 ? $q12->num_rows : 0;
$anomalies['order_tanggal_kosong'] = ['count' => $cnt12, 'sample' => []];
if ($q12) while ($r = $q12->fetch_assoc()) $anomalies['order_tanggal_kosong']['sample'][] = "#{$r['id']} ({$r['code']})";

// 11. Duplikat username di users
$q14 = $conn->query("SELECT username, COUNT(*) as c FROM users GROUP BY username HAVING c > 1");
$cnt14 = $q14 ? $q14->num_rows : 0;
$anomalies['username_duplikat'] = ['count' => $cnt14, 'sample' => []];
if ($q14) while ($r = $q14->fetch_assoc()) $anomalies['username_duplikat']['sample'][] = "{$r['username']} ({$r['c']}x)";

// Tampilkan Laporan Anomali
foreach ($anomalies as $k => $v) {
    echo sprintf("%-30s : %d anomali\n", $k, $v['count']);
    if ($v['count'] > 0) {
        foreach (array_slice($v['sample'], 0, 5) as $s) {
            echo "   -> $s\n";
        }
    }
}

echo "\nSTATUS PESANAN EKSISTING DI ORDERS:\n";
foreach ($orderStatuses as $st) {
    echo " - $st\n";
}

echo "\nDATA TABEL ORDERS LENGKAP:\n";
$ordQuery = $conn->query("SELECT * FROM orders");
while ($o = $ordQuery->fetch_assoc()) {
    echo json_encode($o, JSON_UNESCAPED_SLASHES) . "\n";
}

echo "\nDATA TABEL USERS LENGKAP:\n";
$uQuery = $conn->query("SELECT id, name, username, role, created_at FROM users");
while ($u = $uQuery->fetch_assoc()) {
    echo json_encode($u, JSON_UNESCAPED_SLASHES) . "\n";
}
