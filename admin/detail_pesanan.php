<?php
/**
 * [FILE]: admin/detail_pesanan.php
 * [BAGIAN]: Detail Pesanan, Verifikasi Pembayaran & Pelacakan Resi (Versi Rapi Terpadu)
 * [DESKRIPSI]: Memproses status transaksi, verifikasi pembayaran, input resi kurir, informasi penerima, dan audit trail status
 * [ATURAN]: 100% PHP Native, TANPA JavaScript, Modal CSS-Only via :target, CSRF Protection
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../shared/koneksi.php';
require_once __DIR__ . '/../shared/fungsi.php';

/** @var mysqli $conn */

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($orderId <= 0) {
    header("Location: pesanan.php");
    exit;
}

$adminId = (int)($_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? 1);
$adminName = $_SESSION['admin_name'] ?? $_SESSION['username'] ?? 'admin';

/* [KEAMANAN]: Buat atau Ambil CSRF Token */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

/* [AKSI]: Pemrosesan Formulir Pembaruan Status (Pure PHP POST - No JS) */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        flash_set('error', "Validasi token keamanan CSRF gagal. Silakan coba lagi.");
        header("Location: detail_pesanan.php?id=" . $orderId);
        exit;
    }

    $action = trim($_POST['action'] ?? '');

    // Form Update Terpadu (Menangani Pembayaran, Pesanan & Resi Sekaligus)
    if ($action === 'update_all' || $action === 'update_order' || $action === 'update_payment') {
        $messages = [];
        $hasError = false;

        // 1. Proses Status Pembayaran jika ada inputnya
        if (isset($_POST['payment_status'])) {
            $newPaymentStatus = trim($_POST['payment_status']);
            $paymentNotes = trim($_POST['payment_notes'] ?? '');
            if (!empty($newPaymentStatus)) {
                $payResult = ubah_status_pembayaran($conn, $orderId, $newPaymentStatus, $paymentNotes, $adminId);
                if ($payResult['success']) {
                    $messages[] = "Status pembayaran: {$newPaymentStatus}";
                } else {
                    $hasError = true;
                    flash_set('error', "Gagal update pembayaran: " . ($payResult['error'] ?? 'Terjadi kesalahan.'));
                }
            }
        }

        // 2. Proses Status Pesanan & Resi jika tidak ada error sebelumnya
        if (!$hasError && isset($_POST['order_status'])) {
            $newOrderStatus = trim($_POST['order_status']);
            $orderNotes = trim($_POST['order_notes'] ?? '');
            $courier = trim($_POST['shipping_courier'] ?? '');
            $trackingNumber = trim($_POST['tracking_number'] ?? '');

            // Validasi jika status dikirim maka resi wajib
            if ($newOrderStatus === 'Dikirim' && empty($trackingNumber)) {
                flash_set('error', "Nomor resi pengiriman wajib diisi jika status pesanan diubah ke 'Dikirim'.");
                header("Location: detail_pesanan.php?id=" . $orderId);
                exit;
            }

            $orderResult = ubah_status_pesanan($conn, $orderId, $newOrderStatus, $orderNotes, $adminId, $trackingNumber, $courier);
            if ($orderResult['success']) {
                $messages[] = "Status pesanan: {$newOrderStatus}";
            } else {
                flash_set('error', "Gagal update pesanan: " . ($orderResult['error'] ?? 'Terjadi kesalahan.'));
                header("Location: detail_pesanan.php?id=" . $orderId);
                exit;
            }
        }

        if (!$hasError && !empty($messages)) {
            flash_set('success', "Pembaruan berhasil disimpan (" . implode(', ', $messages) . ").");
        }

        header("Location: detail_pesanan.php?id=" . $orderId);
        exit;
    }
}

/* [QUERY]: Ambil Data Pesanan Lengkap */
$stmtOrder = $conn->prepare("
    SELECT
        o.*,
        u.name AS user_name,
        u.username AS user_handle,
        a.recipient_name,
        a.phone AS recipient_phone,
        a.address AS recipient_address,
        a.city AS recipient_city,
        a.postal_code AS recipient_postal_code
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN addresses a ON o.address_id = a.id
    WHERE o.id = ?
    LIMIT 1
");
$stmtOrder->bind_param("i", $orderId);
$stmtOrder->execute();
$order = $stmtOrder->get_result()->fetch_assoc();
$stmtOrder->close();

if (!$order) {
    flash_set('error', "Pesanan dengan ID #{$orderId} tidak ditemukan.");
    header("Location: pesanan.php");
    exit;
}

/* [QUERY]: Ambil Item Pesanan */
$stmtItems = $conn->prepare("
    SELECT
        oi.*,
        p.name AS product_name,
        p.category AS product_category,
        p.image AS product_image,
        p.stock AS current_stock
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
    ORDER BY oi.id ASC
");
$stmtItems->bind_param("i", $orderId);
$stmtItems->execute();
$itemsResult = $stmtItems->get_result();
$items = [];
while ($row = $itemsResult->fetch_assoc()) {
    $items[] = $row;
}
$stmtItems->close();

$orderCode = !empty($order['order_code']) ? $order['order_code'] : ($order['order_number'] ?: ('ORD-' . $order['id']));
$orderStatus = $order['order_status'] ?: ($order['status'] ?: 'Menunggu Pembayaran');
$payStatus = $order['payment_status'] ?: 'Menunggu Pembayaran';
$subtotal = (float)$order['subtotal'];
$shippingCost = (float)$order['shipping'];
$grandTotal = (float)($order['total'] ?: $order['total_price'] ?: 0);

// Status Badge Map
$statusOrderBadges = [
    'Menunggu Pembayaran' => 'status-warning',
    'Diproses'            => 'status-info',
    'Dikirim'             => 'status-info',
    'Selesai'             => 'status-success',
    'Dibatalkan'          => 'status-danger'
];

$statusPaymentBadges = [
    'Menunggu Pembayaran' => 'status-warning',
    'Menunggu Konfirmasi' => 'status-warning',
    'Dibayar'             => 'status-success',
    'Terverifikasi'       => 'status-success',
    'Selesai'             => 'status-success',
    'Dibatalkan'          => 'status-danger',
    'Belum Bayar'         => 'status-muted'
];

$badgeOrderClass = $statusOrderBadges[$orderStatus] ?? 'status-muted';
$badgePayClass = $statusPaymentBadges[$payStatus] ?? 'status-muted';

$cleanPhone = preg_replace('/[^0-9]/', '', $order['recipient_phone'] ?? '');
if (str_starts_with($cleanPhone, '0')) $cleanPhone = '62' . substr($cleanPhone, 1);

$pageTitle = "Detail Pesanan #{$orderCode} — " . NAMA_TOKO . " Admin";
$currentNav = "pesanan";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        /* Styling Khusus Detail Pesanan Supaya Sangat Rapi & Proporsional */
        .order-top-banner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #141518;
            border: 1px solid var(--ln);
            border-radius: var(--radius-md);
            padding: 16px 20px;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }
        .order-top-info h2 {
            font-family: var(--display-font);
            font-size: 22px;
            font-weight: 800;
            letter-spacing: 0.05em;
            color: var(--tx);
            margin: 0 0 4px 0;
        }
        .order-top-meta {
            font-size: 12px;
            color: var(--mu);
        }
        .order-quick-badges {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .section-box {
            background: #141518;
            border: 1px solid var(--ln);
            border-radius: var(--radius-md);
            padding: 20px;
            margin-bottom: 20px;
        }
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--ln);
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .section-header h3 {
            font-family: var(--display-font);
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--tx);
            margin: 0;
        }
        .form-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        @media (max-width: 768px) {
            .form-grid-2 {
                grid-template-columns: 1fr;
            }
        }
        .item-row {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .item-row:last-child {
            border-bottom: none;
        }
        .item-thumb {
            width: 52px;
            height: 52px;
            border-radius: 4px;
            background: #FFFFFF;
            border: 1px solid #333;
            overflow: hidden;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .item-thumb img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }
        .item-details {
            flex: 1;
            min-width: 0;
        }
        .item-name {
            font-size: 13px;
            font-weight: 700;
            color: var(--tx);
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .item-sub {
            font-size: 11px;
            color: var(--mu);
        }
        .item-price-col {
            text-align: right;
            flex-shrink: 0;
        }
        .timeline-item {
            position: relative;
            padding-left: 20px;
            padding-bottom: 14px;
            border-left: 2px solid var(--ln);
        }
        .timeline-item:last-child {
            border-left: 2px solid transparent;
            padding-bottom: 0;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -5px;
            top: 2px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--ac);
        }
    </style>
</head>
<body>

<div class="admin-wrapper">
    <!-- SIDEBAR NAVIGASI TERPADU -->
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <!-- KONTEN UTAMA -->
    <main class="admin-main">
        <!-- TOPBAR STICKY -->
        <header class="admin-topbar">
            <div>
                <h1 class="topbar-title">Manajemen Pesanan</h1>
            </div>
            <div class="topbar-actions">
                <a href="pesanan.php" class="btn btn-secondary btn-sm">&larr; Kembali ke Daftar Pesanan</a>
            </div>
        </header>

        <div class="admin-content">
            <!-- FLASH NOTIFIKASI -->
            <?php if ($successMsg = flash_get('success')): ?>
                <div class="flash-msg success"><span>✓</span> <?= htmlspecialchars($successMsg) ?></div>
            <?php elseif (!empty($_SESSION['flash_success'])): ?>
                <div class="flash-msg success"><span>✓</span> <?= htmlspecialchars($_SESSION['flash_success']) ?></div>
                <?php unset($_SESSION['flash_success']); ?>
            <?php endif; ?>

            <?php if ($errorMsg = flash_get('error')): ?>
                <div class="flash-msg error"><span>⚠️</span> <?= htmlspecialchars($errorMsg) ?></div>
            <?php elseif (!empty($_SESSION['flash_error'])): ?>
                <div class="flash-msg error"><span>⚠️</span> <?= htmlspecialchars($_SESSION['flash_error']) ?></div>
                <?php unset($_SESSION['flash_error']); ?>
            <?php endif; ?>

            <!-- BANNER RINGKASAN PESANAN -->
            <div class="order-top-banner">
                <div class="order-top-info">
                    <h2>PESANAN: <?= htmlspecialchars($orderCode) ?></h2>
                    <div class="order-top-meta">
                        Waktu Pesan: <strong><?= date('d M Y, H:i', strtotime($order['created_at'])) ?> WIB</strong> &bull;
                        Pembeli: <strong><?= htmlspecialchars($order['recipient_name'] ?: ($order['user_name'] ?: 'Pelanggan')) ?></strong> &bull;
                        Metode: <strong><?= htmlspecialchars($order['payment_method'] ?? 'Transfer Bank') ?></strong>
                    </div>
                </div>
                <div class="order-quick-badges">
                    <div>
                        <span class="meta-field-label">Status Pesanan:</span>
                        <span class="badge-status <?= $badgeOrderClass ?>"><?= htmlspecialchars(strtoupper($orderStatus)) ?></span>
                    </div>
                    <div>
                        <span class="meta-field-label">Status Bayar:</span>
                        <span class="badge-status <?= $badgePayClass ?>"><?= htmlspecialchars(strtoupper($payStatus)) ?></span>
                    </div>
                    <?php if (!empty($order['tracking_number'])): ?>
                        <div>
                            <span class="meta-field-label">Kurir & Resi:</span>
                            <span class="badge-status status-info"><?= htmlspecialchars($order['shipping_courier'] ?: 'Kurir') ?>: <?= htmlspecialchars($order['tracking_number']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- GRID DUA KOLOM DETAIL PESANAN -->
            <div class="detail-grid">

                <!-- ========================================================= -->
                <!-- KOLOM KIRI: FORM PEMBARUAN TERPADU STATUS & RESI          -->
                <!-- ========================================================= -->
                <div class="detail-left-col">
                    <!-- KARTU 1: FORM PEMBARUAN TERPADU STATUS & RESI -->
                    <section class="section-box detail-form-card">
                        <div class="section-header">
                            <h3>Form Kontrol Status & Pengiriman</h3>
                            <span style="font-size: 11px; color: var(--mu);">Update Terpadu</span>
                        </div>

                        <form method="POST" action="detail_pesanan.php?id=<?= $orderId ?>" class="detail-order-form">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                            <input type="hidden" name="action" value="update_all">

                            <div class="detail-form-fields">
                                <div class="form-grid-2">
                                    <!-- KOLOM STATUS PEMBAYARAN -->
                                    <div>
                                        <div class="form-group">
                                            <label for="payment_status" class="form-label">Ubah Status Pembayaran</label>
                                            <select id="payment_status" name="payment_status" class="form-control" required>
                                                <option value="Menunggu Pembayaran" <?= $payStatus === 'Menunggu Pembayaran' ? 'selected' : '' ?>>Menunggu Pembayaran</option>
                                                <option value="Menunggu Konfirmasi" <?= $payStatus === 'Menunggu Konfirmasi' ? 'selected' : '' ?>>Menunggu Konfirmasi</option>
                                                <option value="Dibayar" <?= in_array($payStatus, ['Dibayar', 'Terverifikasi']) ? 'selected' : '' ?>>Dibayar (Lunas Terverifikasi)</option>
                                                <option value="Selesai" <?= $payStatus === 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                                                <option value="Dibatalkan" <?= $payStatus === 'Dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="payment_notes" class="form-label">Catatan Pembayaran (Opsional)</label>
                                            <input type="text" id="payment_notes" name="payment_notes" class="form-control" placeholder="Contoh: Bukti transfer BCA nominal Rp<?= number_format($grandTotal, 0, ',', '.') ?> valid" value="<?= htmlspecialchars($order['payment_notes'] ?? '') ?>">
                                        </div>
                                    </div>

                                    <!-- KOLOM STATUS PESANAN & RESI -->
                                    <div>
                                        <div class="form-group">
                                            <label for="order_status" class="form-label">Ubah Status Pesanan</label>
                                            <select id="order_status" name="order_status" class="form-control" required>
                                                <option value="Menunggu Pembayaran" <?= $orderStatus === 'Menunggu Pembayaran' ? 'selected' : '' ?>>Menunggu Pembayaran</option>
                                                <option value="Diproses" <?= $orderStatus === 'Diproses' ? 'selected' : '' ?>>Diproses (Packing & Persiapan)</option>
                                                <option value="Dikirim" <?= $orderStatus === 'Dikirim' ? 'selected' : '' ?>>Dikirim (Paket di Kurir)</option>
                                                <option value="Selesai" <?= $orderStatus === 'Selesai' ? 'selected' : '' ?>>Selesai (Paket Diterima)</option>
                                                <option value="Dibatalkan" <?= $orderStatus === 'Dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                                            </select>
                                        </div>

                                        <div style="display: grid; grid-template-columns: 1fr 1.3fr; gap: 10px;">
                                            <div class="form-group">
                                                <label for="shipping_courier" class="form-label">Kurir</label>
                                                <select id="shipping_courier" name="shipping_courier" class="form-control">
                                                    <option value="">-- Pilih --</option>
                                                    <?php
                                                        $couriers = ['JNE', 'J&T Express', 'SiCepat', 'AnterAja', 'POS Indonesia', 'GoSend', 'GrabExpress'];
                                                        $currentCourier = $order['shipping_courier'] ?? '';
                                                        foreach ($couriers as $cr):
                                                    ?>
                                                        <option value="<?= $cr ?>" <?= $currentCourier === $cr ? 'selected' : '' ?>><?= $cr ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="tracking_number" class="form-label">Nomor Resi</label>
                                                <input type="text" id="tracking_number" name="tracking_number" class="form-control" placeholder="Wajib jika Dikirim" value="<?= htmlspecialchars($order['tracking_number'] ?? '') ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group" style="margin-bottom: 20px;">
                                    <label for="order_notes" class="form-label">Catatan Pesanan / Pengiriman (Opsional)</label>
                                    <input type="text" id="order_notes" name="order_notes" class="form-control" placeholder="Contoh: Paket di-pickup kurir sore ini" value="<?= htmlspecialchars($order['notes'] ?? '') ?>">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 13px; font-weight: 800; letter-spacing: 0.05em; margin-top: auto;">
                                SIMPAN PERUBAHAN STATUS PESANAN
                            </button>
                        </form>
                    </section>
                </div>

                <!-- ========================================================= -->
                <!-- KOLOM KANAN: RINCIAN PENERIMA & ITEM PRODUK               -->
                <!-- ========================================================= -->
                <div class="detail-right-col">
                    <!-- KARTU 1: INFORMASI PENERIMA & ALAMAT PENGIRIMAN -->
                    <section class="section-box">
                        <div class="section-header">
                            <h3>Informasi Penerima & Alamat</h3>
                            <span style="font-size: 11px; color: var(--mu);">Data Pengiriman</span>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                            <div>
                                <span class="meta-field-label">Nama Penerima:</span>
                                <strong style="color: var(--tx); font-size: 14px;"><?= htmlspecialchars($order['recipient_name'] ?: ($order['user_name'] ?: 'Pelanggan')) ?></strong>
                                <span class="meta-sub" style="display: block; margin-top: 2px;">Akun: @<?= htmlspecialchars($order['user_handle'] ?: 'guest') ?></span>
                            </div>
                            <div>
                                <span class="meta-field-label">Nomor WhatsApp:</span>
                                <strong style="color: var(--tx); font-size: 14px;"><?= htmlspecialchars($order['recipient_phone'] ?? '-') ?></strong>
                                <?php if (!empty($cleanPhone)): ?>
                                    <a href="https://wa.me/<?= $cleanPhone ?>?text=<?= urlencode("Halo " . ($order['recipient_name'] ?: 'Kak') . ", perihal pesanan " . $orderCode) ?>" target="_blank" rel="noopener" style="display: inline-block; margin-top: 4px; color: var(--ac); font-size: 12px; font-weight: 700;">
                                        💬 Chat Pembeli via WA ↗
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div style="padding-top: 12px; border-top: 1px solid var(--ln);">
                            <span class="meta-field-label">Alamat Lengkap Pengiriman:</span>
                            <div style="color: var(--tx); line-height: 1.6; font-size: 13px; margin-top: 4px;">
                                <?= nl2br(htmlspecialchars($order['recipient_address'] ?? 'Alamat tidak tertera')) ?><br>
                                <strong><?= htmlspecialchars($order['recipient_city'] ?? '') ?></strong> <?= htmlspecialchars($order['recipient_postal_code'] ?? '') ?>
                            </div>
                        </div>
                    </section>

                    <!-- KARTU 2: RINCIAN ITEM PRODUK & BIAYA -->
                    <section class="section-box">
                        <div class="section-header">
                            <h3>Rincian Item Pesanan</h3>
                            <span style="color: var(--mu); font-size: 12px;"><?= count($items) ?> Item</span>
                        </div>

                        <div style="margin-bottom: 16px;">
                            <?php foreach ($items as $item): ?>
                                <?php
                                    $itemSub = (float)$item['price'] * (int)$item['quantity'];
                                    $imgName = $item['product_image'] ?? '';
                                    $imgPath = gambar_produk_url($imgName, 'admin');
                                    $hasThumb = !empty($imgPath);
                                ?>
                                <div class="item-row">
                                    <div class="item-thumb">
                                        <?php if ($hasThumb): ?>
                                            <img src="<?= htmlspecialchars($imgPath) ?>" alt="">
                                        <?php else: ?>
                                            <span style="font-size: 9px; color: #888;">FOTO</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="item-details">
                                        <div class="item-name"><?= htmlspecialchars($item['product_name'] ?? ('Produk #' . $item['product_id'])) ?></div>
                                        <div class="item-sub"><?= htmlspecialchars($item['product_category'] ?? 'Pakaian') ?> &bull; Qty: <?= (int)$item['quantity'] ?> pcs</div>
                                    </div>
                                    <div class="item-price-col">
                                        <div style="font-weight: 700; color: var(--tx); font-size: 13px;"><?= formatRupiah($itemSub) ?></div>
                                        <div style="font-size: 11px; color: var(--mu);"><?= formatRupiah((float)$item['price']) ?> /pcs</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- TOTAL BIAYA -->
                        <div class="cost-summary-box" style="margin-top: 12px;">
                            <div class="cost-row">
                                <span>Subtotal Produk</span>
                                <span><?= formatRupiah($subtotal) ?></span>
                            </div>
                            <div class="cost-row">
                                <span>Ongkos Kirim</span>
                                <span><?= formatRupiah($shippingCost) ?></span>
                            </div>
                            <div class="cost-row grand-total">
                                <span>TOTAL TRANSAKSI</span>
                                <span class="total-amount"><?= formatRupiah($grandTotal) ?></span>
                            </div>
                        </div>
                    </section>
                </div>

            </div>
        </div>
    </main>
</div>

</body>
</html>
