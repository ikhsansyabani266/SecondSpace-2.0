<?php
// [FILE] cek_konsistensi.php — Skrip Audit Konsistensi Data & Integritas Toko Preloved
// Dipakai untuk audit berkala & verifikasi konsistensi sebelum/sesudah pekerjaan

require_once __DIR__ . '/../../shared/koneksi.php';
require_once __DIR__ . '/../../shared/fungsi.php';

echo "====================================================================\n";
echo "           AUDIT KONSISTENSI DATABASE & INTEGRITAS (FASE 5)         \n";
echo "====================================================================\n\n";

$anomalies = [];

// 1. Pesanan tanpa item
$q1 = $conn->query("SELECT o.id, o.order_code FROM orders o LEFT JOIN order_items oi ON o.id = oi.order_id WHERE oi.id IS NULL");
$cnt1 = $q1 ? $q1->num_rows : 0;
$anomalies['pesanan_tanpa_item'] = $cnt1;

// 2. Item tanpa pesanan
$q2 = $conn->query("SELECT oi.id FROM order_items oi LEFT JOIN orders o ON oi.order_id = o.id WHERE o.id IS NULL");
$cnt2 = $q2 ? $q2->num_rows : 0;
$anomalies['item_tanpa_pesanan'] = $cnt2;

// 3. Pesanan dengan pembeli yang tidak ada di tabel users
$q3 = $conn->query("SELECT o.id FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE u.id IS NULL");
$cnt3 = $q3 ? $q3->num_rows : 0;
$anomalies['pesanan_user_tidak_ada'] = $cnt3;

// 4. Stok negatif atau tidak masuk akal (< 0 atau > 50)
$q4 = $conn->query("SELECT id FROM products WHERE stock < 0 OR stock > 50");
$cnt4 = $q4 ? $q4->num_rows : 0;
$anomalies['stok_anomali'] = $cnt4;

// 5. Produk tanpa kategori atau kategori kosong
$q5 = $conn->query("SELECT id FROM products WHERE category IS NULL OR TRIM(category) = ''");
$cnt5 = $q5 ? $q5->num_rows : 0;
$anomalies['produk_tanpa_kategori'] = $cnt5;

// 6. Harga nol atau negatif
$q6 = $conn->query("SELECT id FROM products WHERE price <= 0");
$cnt6 = $q6 ? $q6->num_rows : 0;
$anomalies['harga_nol_atau_negatif'] = $cnt6;

// 7. Nilai status pesanan tidak baku (harus sesuai DAFTAR_STATUS_ORDER)
$statusOrderList = "'" . implode("','", DAFTAR_STATUS_ORDER) . "'";
$q7 = $conn->query("SELECT id FROM orders WHERE order_status NOT IN ($statusOrderList)");
$cnt7 = $q7 ? $q7->num_rows : 0;
$anomalies['status_pesanan_tidak_baku'] = $cnt7;

// 8. Nilai status pembayaran tidak baku (harus sesuai DAFTAR_STATUS_PAYMENT)
$statusPaymentList = "'" . implode("','", DAFTAR_STATUS_PAYMENT) . "'";
$q8 = $conn->query("SELECT id FROM orders WHERE payment_status NOT IN ($statusPaymentList)");
$cnt8 = $q8 ? $q8->num_rows : 0;
$anomalies['status_bayar_tidak_baku'] = $cnt8;

// 9. Gambar produk fisik yang tidak ada di disk
$q9 = $conn->query("SELECT DISTINCT image FROM products");
$missingImages = 0;
if ($q9) {
    while ($r = $q9->fetch_assoc()) {
        $img = $r['image'];
        $p1 = __DIR__ . '/../../uploads/produk/' . $img;
        $p2 = __DIR__ . '/../../images/' . $img;
        if (!file_exists($p1) && !file_exists($p2)) {
            $missingImages++;
        }
    }
}
$anomalies['gambar_produk_hilang'] = $missingImages;

// 10. Tanggal pesanan kosong
$q10 = $conn->query("SELECT id FROM orders WHERE created_at IS NULL OR created_at = '0000-00-00 00:00:00'");
$cnt10 = $q10 ? $q10->num_rows : 0;
$anomalies['order_tanggal_kosong'] = $cnt10;

// 11. Duplikat username di tabel users
$q11 = $conn->query("SELECT username, COUNT(*) as c FROM users GROUP BY username HAVING c > 1");
$cnt11 = $q11 ? $q11->num_rows : 0;
$anomalies['username_duplikat'] = $cnt11;

// 12. Produk bertanda demo (harus 0)
$q12 = $conn->query("SELECT id FROM products WHERE is_demo = 1");
$cnt12 = $q12 ? $q12->num_rows : 0;
$anomalies['produk_berstatus_demo'] = $cnt12;

$totalAnomalies = 0;
foreach ($anomalies as $kategori => $jumlah) {
    $totalAnomalies += $jumlah;
    printf("%-35s : %d anomali %s\n", $kategori, $jumlah, ($jumlah === 0 ? "[PASS]" : "[PERLU DIPERBAIKI]"));
}

echo "--------------------------------------------------------------------\n";
echo "TOTAL ANOMALI SISTEM               : $totalAnomalies anomali\n";
echo "====================================================================\n";

if ($totalAnomalies === 0) {
    echo "STATUS AUDIT: SEMPURNA (SEMUA INDIKATOR KONSISTEN 100% [TARGET 0 TERCAPAI])\n";
} else {
    echo "STATUS AUDIT: DITEMUKAN $totalAnomalies ANOMALI YANG PERLU DIATASI.\n";
}
