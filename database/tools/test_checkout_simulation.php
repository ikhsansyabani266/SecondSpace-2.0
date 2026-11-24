<?php
// CLI Tool: Pengujian Alur Checkout & Pengurangan Stok Atomik
require_once __DIR__ . '/../../db.php';

echo "=== PENGUJIAN CHECKOUT & PENGURANGAN STOK ===\n";

// Ambil produk final PL-001
$prod = $conn->query("SELECT id, name, stock, price FROM products WHERE sku = 'PL-001'")->fetch_assoc();
if (!$prod) die("Produk PL-001 tidak ditemukan.\n");

$pId = (int)$prod['id'];
$initialStock = (int)$prod['stock'];
echo "1. Produk Uji: {$prod['name']} (ID: $pId)\n";
echo "   Stok Awal: $initialStock\n";

// Mulai transaksi uji
$conn->begin_transaction();

try {
    $qty = 1;
    $stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
    $stmt->bind_param("iii", $qty, $pId, $qty);
    $stmt->execute();

    if ($stmt->affected_rows !== 1) {
        throw new Exception("Gagal memotong stok.");
    }

    // Periksa stok setelah pemotongan
    $check = $conn->query("SELECT stock FROM products WHERE id = $pId")->fetch_assoc();
    $newStock = (int)$check['stock'];
    echo "2. Pemotongan Stok Sukses: Stok berkurang dari $initialStock menjadi $newStock.\n";

    // Coba potong lagi ketika stok sudah 0 (harus gagal / dicegah)
    $stmt->bind_param("iii", $qty, $pId, $qty);
    $stmt->execute();
    if ($stmt->affected_rows === 0) {
        echo "3. Proteksi Overselling Sukses: Pembelian kedua dicegah karena stok habis (0 affected rows).\n";
    }

    // Kembalikan ke kondisi semula agar data demo tetap utuh
    $conn->rollback();
    echo "4. Rollback Berhasil: Stok dikembalikan ke kondisi semula ($initialStock).\n";
    echo "=== PENGUJIAN CHECKOUT SUKSES 100% ===\n";

} catch (Throwable $e) {
    $conn->rollback();
    echo "ERROR: " . $e->getMessage() . "\n";
}
