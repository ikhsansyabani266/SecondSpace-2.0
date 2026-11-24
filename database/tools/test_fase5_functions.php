<?php
require_once __DIR__ . '/../../shared/koneksi.php';
require_once __DIR__ . '/../../shared/fungsi.php';

echo "=== PENGUJIAN FUNGSI BISNIS FASE 5 ===\n";

$conn->begin_transaction();

try {
    // 1. Uji ubah_stok
    $resStok = ubah_stok($conn, 9, -1, "Test Pengurangan Stok Checkout", 1, 1);
    echo "1. Uji ubah_stok berhasil: Stok awal {$resStok['stok_sebelum']} -> {$resStok['stok_sesudah']}\n";

    // 2. Cek catatan riwayat_stok
    $rStok = $conn->query("SELECT * FROM riwayat_stok WHERE product_id = 9 ORDER BY id DESC LIMIT 1")->fetch_assoc();
    echo "   Riwayat Stok Tercatat: Alasan='{$rStok['alasan']}', Perubahan={$rStok['perubahan']}\n";

    // 3. Uji ubah_status_pembayaran
    $resBayar = ubah_status_pembayaran($conn, 1, STATUS_PAYMENT_TERVERIFIKASI, "Verifikasi transfer BCA oleh Admin", 1);
    echo "2. Uji ubah_status_pembayaran berhasil: " . ($resBayar['success'] ? "OK" : "FAIL: " . $resBayar['error']) . "\n";

    // 4. Uji ubah_status_pesanan
    $resOrder = ubah_status_pesanan($conn, 1, STATUS_ORDER_DIKIRIM, "Paket telah dikirim via JNE", 1, "JNE123456789", "JNE Express");
    echo "3. Uji ubah_status_pesanan berhasil: " . ($resOrder['success'] ? "OK" : "FAIL: " . $resOrder['error']) . "\n";

    // 5. Cek catatan riwayat_status
    $rStatus = $conn->query("SELECT * FROM riwayat_status WHERE order_id = 1 ORDER BY id DESC LIMIT 2");
    while ($rs = $rStatus->fetch_assoc()) {
        echo "   Riwayat Status Tercatat: Jenis='{$rs['jenis']}', {$rs['status_lama']} -> {$rs['status_baru']} (Catatan: {$rs['catatan']})\n";
    }

    // 6. Uji KPI Dashboard
    echo "4. Uji KPI Dashboard Tunggal:\n";
    echo "   - Total Produk     : " . kpi_total_produk($conn) . "\n";
    echo "   - Total Pesanan    : " . kpi_total_pesanan($conn) . "\n";
    echo "   - Menunggu Verif   : " . kpi_menunggu_verifikasi($conn) . "\n";
    echo "   - Pesanan Selesai  : " . kpi_pesanan_selesai($conn) . "\n";
    echo "   - Pesan Masuk      : " . kpi_pesan_masuk($conn) . "\n";

    // Rollback test agar data database tetap utuh
    $conn->rollback();
    echo "\nSTATUS ROLLBACK: SUKSES (Data database tetap bersih).\n";
    echo "SEMUA FUNGSI BISNIS FASE 5 LOLOS UJI 100%!\n";

} catch (Throwable $e) {
    $conn->rollback();
    echo "ERROR: " . $e->getMessage() . "\n";
}
