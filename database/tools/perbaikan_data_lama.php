<?php
// [FILE] perbaikan_data_lama.php — Skrip Perbaikan Anomali Data Lama (Mendukung --dry-run & Transaksi)
require_once __DIR__ . '/../../shared/koneksi.php';
require_once __DIR__ . '/../../shared/config.php';

$dryRun = in_array('--dry-run', $argv);

echo "====================================================================\n";
echo "       SKRIP PERBAIKAN ANOMALI STATUS PEMBAYARAN DATA LAMA          \n";
echo "====================================================================\n";

if ($dryRun) {
    echo "STATUS: MODE DRY-RUN (Hanya simulasi, TIDAK ADA data yang diubah)\n\n";
} else {
    echo "STATUS: MODE EKSEKUSI NYATA (Dalam Transaksi Database)\n\n";
}

$conn->begin_transaction();

try {
    $res = $conn->query("SELECT id, order_code, order_status, payment_status, total FROM orders WHERE payment_status = 'Menunggu Pembayaran'");
    $count = 0;
    
    while ($r = $res->fetch_assoc()) {
        $orderId = (int)$r['id'];
        // Jika order sudah berstatus 'Dikirim' atau 'Selesai', status pembayaran baku adalah 'Terverifikasi'
        // Jika order masih 'Menunggu Pembayaran' atau 'Diproses', status pembayaran baku adalah 'Belum Bayar'
        $statusBaru = in_array($r['order_status'], ['Dikirim', 'Selesai']) ? STATUS_PAYMENT_TERVERIFIKASI : STATUS_PAYMENT_BELUM_BAYAR;
        
        echo "Order #{$orderId} ({$r['order_code']}):\n";
        echo "   - Status Pesanan     : {$r['order_status']}\n";
        echo "   - Status Bayar Lama  : {$r['payment_status']}\n";
        echo "   - Status Bayar Baru  : {$statusBaru}\n";

        if (!$dryRun) {
            $up = $conn->prepare("UPDATE orders SET payment_status = ? WHERE id = ?");
            $up->bind_param("si", $statusBaru, $orderId);
            $up->execute();
            $up->close();
        }
        $count++;
    }

    if ($dryRun) {
        $conn->rollback();
        echo "\nRINGKASAN DRY-RUN:\n";
        echo "Total pesanan yang akan distandarisasi status pembayarannya: $count baris.\n";
        echo "Status Transaksi: ROLLBACK BERHASIL (Database TIDAK mengalami perubahan).\n";
    } else {
        $conn->commit();
        echo "\nRINGKASAN EKSEKUSI:\n";
        echo "Total pesanan berhasil diperbarui: $count baris.\n";
        echo "Status Transaksi: COMMIT BERHASIL.\n";
    }

} catch (Throwable $e) {
    $conn->rollback();
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
