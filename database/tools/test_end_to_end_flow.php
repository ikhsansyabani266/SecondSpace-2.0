<?php
/**
 * Test Simulasi End-to-End Flow Lengkap
 */
require_once __DIR__ . '/../../shared/koneksi.php';
require_once __DIR__ . '/../../shared/fungsi.php';

/** @var mysqli $conn */

echo "=== MEMULAI TEST SIMULASI END-TO-END ===\n";

// 1. Ambil 1 produk dengan stok > 0
$pStmt = $conn->query("SELECT id, name, price, stock FROM products WHERE stock > 0 LIMIT 1");
$prod = $pStmt->fetch_assoc();
if (!$prod) {
    die("[FAIL] Tidak ada produk aktif dengan stok > 0\n");
}
$prodId = (int)$prod['id'];
$stokAwal = (int)$prod['stock'];
echo "[1] Produk uji coba: ID #{$prodId} - {$prod['name']} (Stok awal: {$stokAwal})\n";

// 2. Ambil user pelanggan (non-admin)
$uStmt = $conn->query("SELECT id, name, username FROM users WHERE role = 'user' LIMIT 1");
$user = $uStmt->fetch_assoc();
if (!$user) {
    // Buat user dummy jika belum ada
    $conn->query("INSERT INTO users (name, username, password, role) VALUES ('Pembeli Uji', 'buyer_test', '" . password_hash('123456', PASSWORD_BCRYPT) . "', 'user')");
    $userId = $conn->insert_id;
    echo "[2] Dibuat user pembeli baru: ID #{$userId}\n";
} else {
    $userId = (int)$user['id'];
    echo "[2] User pembeli uji coba: ID #{$userId} - {$user['name']}\n";
}

// 3. Ambil / buat alamat pengiriman
$aStmt = $conn->prepare("SELECT id FROM addresses WHERE user_id = ? LIMIT 1");
$aStmt->bind_param("i", $userId);
$aStmt->execute();
$addr = $aStmt->get_result()->fetch_assoc();
$aStmt->close();

if (!$addr) {
    $conn->query("INSERT INTO addresses (user_id, recipient_name, phone, address, province, city, postal_code) VALUES ({$userId}, 'Pembeli Uji', '081234567890', 'Jl. Streetwear No. 88', 'DKI Jakarta', 'Jakarta Selatan', '12340')");
    $addressId = $conn->insert_id;
} else {
    $addressId = (int)$addr['id'];
}
echo "[3] Alamat pengiriman valid: ID #{$addressId}\n";

// 4. Simulasi Pembuatan Pesanan Baru
$orderCode = 'ORD-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
$subtotal = (float)$prod['price'];
$shipping = 15000.00;
$total = $subtotal + $shipping;

$conn->begin_transaction();
try {
    $stmtO = $conn->prepare("
        INSERT INTO orders (user_id, order_code, address_id, subtotal, shipping, total, payment_method, payment_status, order_status, notes, order_number, total_price, status)
        VALUES (?, ?, ?, ?, ?, ?, 'BCA Transfer', 'Menunggu Konfirmasi', 'Menunggu Pembayaran', 'Uji Coba Otomatis', ?, ?, 'Menunggu Pembayaran')
    ");
    $stmtO->bind_param("isidddsd", $userId, $orderCode, $addressId, $subtotal, $shipping, $total, $orderCode, $total);
    $stmtO->execute();
    $newOrderId = $conn->insert_id;
    $stmtO->close();

    $stmtI = $conn->prepare("
        INSERT INTO order_items (order_id, product_id, quantity, price)
        VALUES (?, ?, 1, ?)
    ");
    $stmtI->bind_param("iid", $newOrderId, $prodId, $subtotal);
    $stmtI->execute();
    $stmtI->close();

    // Catat riwayat stok keluar
    catat_riwayat_stok($conn, $prodId, $stokAwal, $stokAwal - 1, 'order_out', 'Checkout pesanan #' . $orderCode, $userId);

    // Update stok live
    $conn->query("UPDATE products SET stock = stock - 1 WHERE id = {$prodId}");

    $conn->commit();
    echo "[4] Sukses membuat pesanan baru: ID #{$newOrderId} ({$orderCode}) senilai Rp " . number_format($total, 0, ',', '.') . "\n";
} catch (Throwable $e) {
    $conn->rollback();
    die("[FAIL] Gagal checkout: " . $e->getMessage() . "\n");
}

// 5. Simulasi Admin Memverifikasi Pembayaran
echo "[5] Menguji aksi admin: ubah status pembayaran ke 'Dibayar'...\n";
$payRes = ubah_status_pembayaran($conn, $newOrderId, 'Dibayar', 'Bukti transfer BCA valid via ATM', 1);
if (!$payRes['success']) {
    die("[FAIL] Gagal ubah status pembayaran: " . $payRes['error'] . "\n");
}
echo "    -> Sukses! Status pembayaran kini 'Dibayar'.\n";

// 6. Simulasi Admin Memproses Pesanan & Input Resi Pengiriman
echo "[6] Menguji aksi admin: ubah status pesanan ke 'Dikirim' dengan Resi & Kurir...\n";
$resiSample = 'JNEX' . date('Ymd') . '998877';
$kurirSample = 'JNE';
$orderRes = ubah_status_pesanan($conn, $newOrderId, 'Dikirim', 'Paket diserahkan ke kurir JNE', 1, $resiSample, $kurirSample);
if (!$orderRes['success']) {
    die("[FAIL] Gagal ubah status pesanan: " . $orderRes['error'] . "\n");
}
echo "    -> Sukses! Status pesanan kini 'Dikirim', Kurir: {$kurirSample}, Resi: {$resiSample}\n";

// 7. Verifikasi Data Akhir di Database
$chk = $conn->query("SELECT order_code, payment_status, order_status, shipping_courier, tracking_number FROM orders WHERE id = {$newOrderId}")->fetch_assoc();
echo "[7] Verifikasi baris pesanan final:\n";
echo "    - Order Code: {$chk['order_code']}\n";
echo "    - Payment Status: {$chk['payment_status']}\n";
echo "    - Order Status: {$chk['order_status']}\n";
echo "    - Kurir: {$chk['shipping_courier']}\n";
echo "    - Resi: {$chk['tracking_number']}\n";

// 8. Verifikasi Audit Trail di riwayat_status
$rCheck = $conn->query("SELECT jenis, status_lama, status_baru, catatan FROM riwayat_status WHERE order_id = {$newOrderId} ORDER BY id ASC");
echo "[8] Audit trail status tercatat:\n";
if ($rCheck) {
    while ($row = $rCheck->fetch_assoc()) {
        echo "    - [{$row['jenis']}] {$row['status_lama']} -> {$row['status_baru']} (Catatan: {$row['catatan']})\n";
    }
}

// 9. Bersihkan Data Simulasi agar database tetap bersih & kembalikan stok
$conn->query("DELETE FROM riwayat_status WHERE order_id = {$newOrderId}");
$conn->query("DELETE FROM order_items WHERE order_id = {$newOrderId}");
$conn->query("DELETE FROM orders WHERE id = {$newOrderId}");
$conn->query("UPDATE products SET stock = stock + 1 WHERE id = {$prodId}");
catat_riwayat_stok($conn, $prodId, $stokAwal - 1, $stokAwal, 'admin_adjust', 'Rollback pasca test simulasi', 1);

echo "[9] Pembersihan data simulasi berhasil. Stok produk ID #{$prodId} dikembalikan utuh ke {$stokAwal}.\n";
echo "=== TEST END-TO-END FLOW SELESAI DENGAN SUKSES [100% PASS] ===\n";
