<?php
/**
 * [FILE]: user/detail_pesanan.php
 * [BAGIAN]: Detail Pesanan, Status Pengiriman & Upload Ulang Bukti Bayar
 * [DESKRIPSI]: Menampilkan rincian pesanan pembeli, status paket, nomor resi kurir, alamat kirim, dan tombol unggah bukti bayar
 * [JANGAN UBAH]: Pemeriksaan kepemilikan pesanan ($userId vs $order['user_id']) demi privasi pelanggan
 * [UBAH DI SINI]: Format tampilan nomor resi atau teks bantuan kurir
 * [TAMBAH DI SINI]: Tombol lacak paket via API ekspedisi kurir
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "auth.php";
require_once __DIR__ . '/../shared/koneksi.php';
require_once __DIR__ . '/../shared/fungsi.php';

/** @var mysqli $conn */

$userId = (int)($_SESSION["user_id"] ?? 0);
$isAdmin = isset($_SESSION["role"]) && $_SESSION["role"] === "admin";

$order_id = (int)($_GET["id"] ?? 0);
$order_code = trim($_GET["kode"] ?? "");

/* [QUERY]: Pemeriksaan Ketersediaan Kolom Province */
$hasProvince = false;
$colCheck = $conn->query("SHOW COLUMNS FROM addresses LIKE 'province'");
if ($colCheck && $colCheck->num_rows > 0) {
    $hasProvince = true;
}
$provinceSelect = $hasProvince ? "a.province," : "'' AS province,";

$order = null;

if ($order_id > 0) {
    $query = "
        SELECT o.*,
               a.recipient_name,
               a.phone,
               a.address,
               {$provinceSelect}
               a.city,
               a.postal_code
        FROM orders o
        LEFT JOIN addresses a ON o.address_id = a.id
        WHERE o.id = ? " . ($isAdmin ? "" : "AND o.user_id = ?") . "
        LIMIT 1
    ";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        if ($isAdmin) {
            $stmt->bind_param("i", $order_id);
        } else {
            $stmt->bind_param("ii", $order_id, $userId);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $order = $res ? $res->fetch_assoc() : null;
        $stmt->close();
    }
} elseif (!empty($order_code)) {
    $query = "
        SELECT o.*,
               a.recipient_name,
               a.phone,
               a.address,
               {$provinceSelect}
               a.city,
               a.postal_code
        FROM orders o
        LEFT JOIN addresses a ON o.address_id = a.id
        WHERE (o.order_code = ? OR o.order_number = ?) " . ($isAdmin ? "" : "AND o.user_id = ?") . "
        LIMIT 1
    ";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        if ($isAdmin) {
            $stmt->bind_param("ss", $order_code, $order_code);
        } else {
            $stmt->bind_param("ssi", $order_code, $order_code, $userId);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $order = $res ? $res->fetch_assoc() : null;
        $stmt->close();
    }
}

// Ambil item pesanan jika data pesanan ditemukan
$items = [];
if ($order) {
    $stmtItems = $conn->prepare("
        SELECT oi.*, COALESCE(NULLIF(oi.product_name, ''), p.name, 'Produk Preloved') AS product_name, p.image, p.size AS p_size
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
        ORDER BY oi.id ASC
    ");
    if ($stmtItems) {
        $stmtItems->bind_param("i", $order["id"]);
        $stmtItems->execute();
        $resItems = $stmtItems->get_result();
        while ($resItems && $it = $resItems->fetch_assoc()) {
            $items[] = $it;
        }
        $stmtItems->close();
    }
}

// Badge status helper
function getDetailStatusBadge(string $status): array {
    switch ($status) {
        case "Diproses":
            return ["label" => "DIPROSES", "class" => "badge-diproses", "icon" => ""];
        case "Dikirim":
            return ["label" => "DIKIRIM", "class" => "badge-dikirim", "icon" => ""];
        case "Selesai":
            return ["label" => "SELESAI", "class" => "badge-selesai", "icon" => ""];
        case "Dibatalkan":
            return ["label" => "DIBATALKAN", "class" => "badge-dibatalkan", "icon" => ""];
        case "Menunggu Pembayaran":
        default:
            return ["label" => "MENUNGGU PEMBAYARAN", "class" => "badge-menunggu", "icon" => "⏳"];
    }
}

$cartCount = isset($_SESSION["cart"]) ? count($_SESSION["cart"]) : 0;
$isLoggedIn = isset($_SESSION["user_id"]);
$orderCodeDisplay = $order ? ($order["order_code"] ?: ($order["order_number"] ?: "SS-" . $order["id"])) : "";
$currentStatus = $order ? ($order["order_status"] ?: ($order["status"] ?: "Menunggu Pembayaran")) : "";
$badge = getDetailStatusBadge($currentStatus);

// Hitung diskon promo jika ada
// Informasi Rekening & E-Wallet Toko (Defensif)
$bankName = $bankName ?? (defined('BANK_NAME') ? BANK_NAME : 'Bank Central Asia (BCA)');
$bankAccountNumber = $bankAccountNumber ?? (defined('BANK_ACCOUNT_NUMBER') ? BANK_ACCOUNT_NUMBER : '8801928391');
$bankAccountName = $bankAccountName ?? (defined('BANK_ACCOUNT_NAME') ? BANK_ACCOUNT_NAME : 'SecondSpace Official');
$ewalletName = $ewalletName ?? (defined('EWALLET_NAME') ? EWALLET_NAME : 'DANA / GoPay / OVO');
$ewalletNumber = $ewalletNumber ?? (defined('EWALLET_NUMBER') ? EWALLET_NUMBER : '0895327292342');
$ewalletAccountName = $ewalletAccountName ?? (defined('EWALLET_ACCOUNT_NAME') ? EWALLET_ACCOUNT_NAME : 'SecondSpace Official');

$discountVal = 0;
if ($order) {
    $subtotalVal = (float)$order["subtotal"];
    $shippingVal = (float)$order["shipping"];
    $totalVal    = (float)$order["total"];
    $discountVal = max(0, ($subtotalVal + $shippingVal) - $totalVal);
}

// Format WhatsApp Confirm Message
$waConfirmMsg = "";
$waConfirmUrl = "#";
if ($order) {
    $waConfirmMsg = "Halo SecondSpace, saya ingin konfirmasi pembayaran untuk pesanan #" . $orderCodeDisplay . ".\n"
        . "Nama: " . ($order["recipient_name"] ?? $_SESSION["name"] ?? "Pelanggan") . "\n"
        . "Metode: " . ($order["payment_method"] ?? "Transfer Bank") . "\n"
        . "Total Tagihan: " . formatRupiah($order["total"]) . "\n"
        . "Bukti transfer / pembayaran saya lampirkan di chat ini. Mohon segera diproses ya!";
    $waConfirmUrl = waLink($whatsappNumber, $waConfirmMsg);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $order ? "Pesanan #" . htmlspecialchars($orderCodeDisplay) : "Pesanan Tidak Ditemukan" ?> — SecondSpace PreLoved</title>
    <link rel="stylesheet" href="modern-theme.css">
    <style>
        .page-container {
            max-width: 1040px;
            margin: 40px auto 80px;
            padding: 0 20px;
        }

        .back-nav {
            margin-bottom: 24px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--mu);
            text-decoration: none;
            font-family: var(--display-font);
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            transition: color 0.15s ease;
        }

        .back-link:hover {
            color: var(--tx);
        }

        /* Order Header Card */
        .order-head-card {
            background: var(--s1);
            border: 1px solid var(--ln);
            border-radius: 4px;
            padding: 24px 28px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
        }

        .order-head-info h1 {
            font-family: var(--display-font);
            font-size: 32px;
            font-weight: 800;
            letter-spacing: 0.04em;
            color: var(--tx);
            margin: 0 0 6px 0;
            text-transform: uppercase;
        }

        .order-head-date {
            color: var(--mu);
            font-size: 13px;
        }

        /* Status Badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 4px;
            font-family: var(--display-font);
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .badge-menunggu {
            background: rgba(245, 158, 11, 0.15);
            color: #FBBF24;
            border: 1px solid #D97706;
        }

        .badge-diproses {
            background: rgba(59, 130, 246, 0.15);
            color: #60A5FA;
            border: 1px solid #2563EB;
        }

        .badge-dikirim {
            background: rgba(124, 154, 109, 0.2);
            color: var(--ac);
            border: 1px solid var(--ac);
        }

        .badge-selesai {
            background: rgba(16, 185, 129, 0.15);
            color: #34D399;
            border: 1px solid #059669;
        }

        .badge-dibatalkan {
            background: rgba(239, 68, 68, 0.15);
            color: #F87171;
            border: 1px solid #DC2626;
        }

        /* Status Action Banners */
        .detail-banner {
            border-radius: 4px;
            padding: 20px 24px;
            margin-bottom: 24px;
        }

        .detail-banner.menunggu {
            background: #18150F;
            border: 1px solid #854D0E;
            border-left: 4px solid #F59E0B;
        }

        .detail-banner.dikirim {
            background: #0E1812;
            border: 1px solid #2E5A36;
            border-left: 4px solid var(--ac);
        }

        .detail-banner.diproses {
            background: #0C1524;
            border: 1px solid #1E3A8A;
            border-left: 4px solid #3B82F6;
        }

        .banner-heading {
            font-family: var(--display-font);
            font-size: 17px;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: var(--tx);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .banner-desc {
            color: var(--tx);
            font-size: 14px;
            line-height: 1.6;
            margin: 0 0 16px 0;
        }

        .banner-buttons {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn-action-solid {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 20px;
            background: var(--ac);
            color: #0A0A0A !important;
            border: 1px solid var(--ac);
            border-radius: 4px;
            font-family: var(--display-font);
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            text-decoration: none !important;
            transition: all 0.2s ease;
        }

        .btn-action-solid:hover {
            background: var(--ach);
            border-color: var(--ach);
            transform: translateY(-1px);
        }

        .btn-action-outline {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 9px 18px;
            background: transparent;
            color: var(--tx) !important;
            border: 1px solid var(--ln);
            border-radius: 4px;
            font-family: var(--display-font);
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            text-decoration: none !important;
            transition: all 0.2s ease;
        }

        .btn-action-outline:hover {
            background: var(--s2);
            border-color: var(--tx);
        }

        /* 2-Column Detail Grid */
        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 24px;
            align-items: start;
        }

        .detail-box {
            background: var(--s1);
            border: 1px solid var(--ln);
            border-radius: 4px;
            padding: 24px;
            margin-bottom: 24px;
        }

        .detail-box-title {
            font-family: var(--display-font);
            font-size: 18px;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: var(--tx);
            margin: 0 0 16px 0;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--ln);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Order Items List */
        .item-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 14px 0;
            border-bottom: 1px solid #1C1C1C;
        }

        .item-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .item-row:first-child {
            padding-top: 0;
        }

        .item-row-left {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
        }

        .item-thumb {
            width: 58px;
            height: 58px;
            background: #EDEDED;
            border-radius: 4px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .item-thumb img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            mix-blend-mode: multiply;
        }

        .item-thumb-fallback {
            font-family: var(--display-font);
            font-size: 20px;
            font-weight: 800;
            color: #333333;
        }

        .item-name {
            font-family: var(--display-font);
            font-size: 16px;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--tx);
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .item-meta {
            font-size: 12px;
            color: var(--mu);
        }

        .item-price {
            font-family: var(--body-font);
            font-weight: 700;
            font-size: 14px;
            color: var(--tx);
            text-align: right;
            white-space: nowrap;
        }

        /* Address List Details */
        .info-data-list {
            display: grid;
            grid-template-columns: 140px 1fr;
            gap: 12px 16px;
            font-size: 14px;
            line-height: 1.5;
        }

        .info-data-label {
            color: var(--mu);
        }

        .info-data-val {
            color: var(--tx);
            font-weight: 500;
        }

        /* Summary Sidebar */
        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            font-size: 14px;
            color: var(--mu);
        }

        .summary-row strong,
        .summary-row .val-white {
            color: var(--tx);
            font-weight: 600;
        }

        .summary-total-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-top: 14px;
            padding-top: 14px;
            border-top: 1px solid var(--ln);
        }

        .summary-total-label {
            font-family: var(--display-font);
            font-size: 18px;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: var(--tx);
        }

        .summary-total-amount {
            font-family: var(--display-font);
            font-size: 26px;
            font-weight: 800;
            letter-spacing: 0.02em;
            color: var(--ac);
        }

        /* Resi Badge */
        .resi-badge {
            background: #000000;
            border: 1px solid var(--ac);
            color: var(--ac);
            padding: 4px 10px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 13px;
            letter-spacing: 0.05em;
            display: inline-block;
        }

        /* Empty / Not Found Card */
        .not-found-card {
            background: var(--s1);
            border: 1px solid var(--ln);
            border-radius: 4px;
            padding: 60px 24px;
            text-align: center;
            margin-top: 40px;
        }

        .not-found-title {
            font-family: var(--display-font);
            font-size: 32px;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: var(--tx);
            margin: 0 0 12px 0;
        }

        .not-found-desc {
            color: var(--mu);
            font-size: 15px;
            max-width: 480px;
            margin: 0 auto 28px;
            line-height: 1.6;
        }

        @media (max-width: 860px) {
            .detail-grid {
                grid-template-columns: 1fr;
            }
            .order-head-card {
                flex-direction: column;
                align-items: flex-start;
            }
            .info-data-list {
                grid-template-columns: 1fr;
                gap: 6px;
            }
            .info-data-label {
                font-size: 12px;
                color: var(--mu);
            }
        }
    </style>
</head>
<body>

<!-- GLOBAL NAVBAR PILL -->
<nav class="navbar">
    <a href="index.php" class="logo">
        <span class="logo-second">SECOND</span><span class="logo-space">SPΛCE</span><span class="logo-reg">®</span>
    </a>
    <div class="nav-menu">
        <a href="index.php" class="nav-dashboard">DASHBOARD</a>
        <a href="produk.php">PRODUK</a>
        <a href="keranjang.php">
            KERANJANG
            <?php if ($cartCount > 0): ?>
                <span class="cart-badge"><?= $cartCount ?></span>
            <?php endif; ?>
        </a>
        <a href="pesanan.php" class="active">PESANAN</a>
        <?php if ($isLoggedIn): ?>
            <a href="logout.php" class="logout">KELUAR</a>
        <?php else: ?>
            <a href="login.php" style="color: var(--ac);">MASUK</a>
        <?php endif; ?>
    </div>
</nav>

<div class="page-container">

    <div class="back-nav">
        <a href="pesanan.php" class="back-link">&larr; KEMBALI KE PESANAN SAYA</a>
    </div>

    <?php if (!$order): ?>
        <!-- ORDER NOT FOUND STATE -->
        <div class="not-found-card">
            <div style="font-size: 16px; font-weight: bold; margin-bottom: 16px; color: var(--accent);">[DETAIL PESANAN]</div>
            <h1 class="not-found-title">PESANAN TIDAK DITEMUKAN</h1>
            <p class="not-found-desc">
                Data pesanan dengan kode atau ID yang kamu tuju tidak ditemukan atau kamu belum memiliki izin untuk membukanya.
            </p>
            <a href="pesanan.php" class="btn-action-solid">
                KEMBALI KE DAFTAR PESANAN &rarr;
            </a>
        </div>
    <?php else: ?>

        <!-- ORDER HEAD CARD -->
        <div class="order-head-card">
            <div class="order-head-info">
                <h1>#<?= htmlspecialchars($orderCodeDisplay) ?></h1>
                <div class="order-head-date">
                    Dibuat pada: <?= date("d F Y, H:i", strtotime($order["created_at"])) ?> WIB
                </div>
            </div>
            <div>
                <span class="status-badge <?= $badge["class"] ?>">
                    <?= $badge["icon"] ?> <?= $badge["label"] ?>
                </span>
            </div>
        </div>

        <!-- ACTION BANNER BY STATUS -->
        <?php if ($currentStatus === "Menunggu Pembayaran"): ?>
            <div class="detail-banner menunggu">
                <div class="banner-heading">
                    <span>⏳</span> MENUNGGU PEMBAYARAN
                </div>
                <p class="banner-desc">
                    Total tagihan pesanan ini adalah <strong><?= formatRupiah($order["total"]) ?></strong> via <strong><?= htmlspecialchars($order["payment_method"] ?? "Transfer Bank") ?></strong>.
                    <?php if (($order["payment_method"] ?? "") === "Transfer Bank"): ?>
                        Transfer ke rekening <strong>BCA <?= htmlspecialchars($bankAccountNumber) ?> (a.n <?= htmlspecialchars($bankAccountName) ?>)</strong>.
                    <?php elseif (($order["payment_method"] ?? "") === "QRIS"): ?>
                        Scan kode <strong>QRIS Dinamis</strong> sebesar <strong><?= formatRupiah($order["total"]) ?></strong> via m-Banking atau E-Wallet (nominal otomatis terisi tanpa input manual).
                    <?php elseif (($order["payment_method"] ?? "") === "E-Wallet"): ?>
                        Kirim ke e-wallet <strong><?= htmlspecialchars($ewalletName) ?> <?= htmlspecialchars($ewalletNumber) ?> (a.n <?= htmlspecialchars($ewalletAccountName) ?>)</strong>.
                    <?php endif; ?>
                    Setelah transfer, segera konfirmasi via WhatsApp CS dan lampirkan bukti pembayaran agar pesanan langsung disiapkan.
                </p>
                <div class="banner-buttons">
                    <a href="<?= $waConfirmUrl ?>" target="_blank" rel="noopener" class="btn-action-solid">
                        KONFIRMASI PEMBAYARAN VIA WHATSAPP CS
                    </a>
                    <a href="konfirmasi.php?kode=<?= urlencode($orderCodeDisplay) ?>" class="btn-action-outline">
                        PETUNJUK BAYAR &amp; RINGKASAN &rarr;
                    </a>
                </div>
            </div>

        <?php elseif ($currentStatus === "Dikirim"): ?>
            <div class="detail-banner dikirim">
                <div class="banner-heading">
                    PAKET DALAM PENGIRIMAN
                </div>
                <p class="banner-desc">
                    Paketmu sudah dikirim dengan <strong><?= htmlspecialchars($order["shipping_courier"] ?: "Ekspedisi Reguler") ?></strong>
                    <?php if (!empty($order["tracking_number"])): ?>
                        dengan nomor resi: <span class="resi-badge"><?= htmlspecialchars($order["tracking_number"]) ?></span>
                    <?php endif; ?>.
                </p>
                <?php
                $waTrackMsg = "Halo SecondSpace, saya ingin menanyakan status pengiriman pesanan #" . $orderCodeDisplay . (!empty($order["tracking_number"]) ? " dengan nomor resi " . $order["tracking_number"] : "") . ".";
                $waTrackUrl = waLink($whatsappNumber, $waTrackMsg);
                ?>
                <div class="banner-buttons">
                    <a href="<?= $waTrackUrl ?>" target="_blank" rel="noopener" class="btn-action-solid">
                        LACAK VIA WHATSAPP CS
                    </a>
                </div>
            </div>

        <?php elseif ($currentStatus === "Diproses"): ?>
            <div class="detail-banner diproses">
                <div class="banner-heading">
                    PESANAN SEDANG DIPROSES
                </div>
                <p class="banner-desc">
                    Pembayaran telah diverifikasi. Item pesananmu sedang disortir, dibersihkan dan dipacking rapi oleh tim SecondSpace.
                </p>
                <?php
                $waProgresMsg = "Halo SecondSpace, saya ingin cek progres pesanan #" . $orderCodeDisplay . ".";
                $waProgresUrl = waLink($whatsappNumber, $waProgresMsg);
                ?>
                <div class="banner-buttons">
                    <a href="<?= $waProgresUrl ?>" target="_blank" rel="noopener" class="btn-action-solid">
                        TANYA PROGRES PESANAN
                    </a>
                </div>
            </div>

        <?php elseif ($currentStatus === "Selesai"): ?>
            <div class="detail-banner" style="background: #0E1812; border: 1px solid #2E5A36; border-left: 4px solid #10B981;">
                <div class="banner-heading" style="color: #34D399;">
                    PESANAN TELAH SELESAI
                </div>
                <p class="banner-desc" style="margin-bottom: 0;">
                    Pesanan telah selesai diterima. Terima kasih telah mempercayai SecondSpace untuk koleksi streetwear preloved pilihanmu!
                </p>
            </div>
        <?php endif; ?>

        <!-- 2-COLUMN DETAIL GRID -->
        <div class="detail-grid">

            <!-- LEFT COLUMN: PRODUCTS & SHIPPING -->
            <div>
                <!-- PRODUK YANG DIPESAN -->
                <div class="detail-box">
                    <h2 class="detail-box-title">
                        DAFTAR PRODUK (<?= count($items) ?> ITEM)
                    </h2>
                    <div>
                        <?php foreach ($items as $it): ?>
                            <div class="item-row">
                                <div class="item-row-left">
                                    <div class="item-thumb">
                                        <?php $detItemImg = gambar_produk_url($it["image"] ?? '', 'user'); ?>
                                        <?php if (!empty($detItemImg)): ?>
                                            <img src="<?= htmlspecialchars($detItemImg) ?>" alt="<?= htmlspecialchars($it["product_name"] ?? '') ?>">
                                        <?php else: ?>
                                            <span class="item-thumb-fallback"><?= htmlspecialchars(substr((string)($it["product_name"] ?? "S"), 0, 1)) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="item-name"><?= htmlspecialchars($it["product_name"] ?? "Produk Preloved") ?></div>
                                        <div class="item-meta">
                                            Ukuran: <?= htmlspecialchars($it["p_size"] ?: ($it["size"] ?? "All Size")) ?> &middot;
                                            x<?= (int)$it["quantity"] ?> &middot;
                                            <?= formatRupiah($it["price"]) ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="item-price">
                                    <?= formatRupiah($it["subtotal"]) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- ALAMAT PENGIRIMAN -->
                <div class="detail-box">
                    <h2 class="detail-box-title">
                        ALAMAT PENGIRIMAN
                    </h2>
                    <div class="info-data-list">
                        <div class="info-data-label">Nama Penerima</div>
                        <div class="info-data-val"><?= htmlspecialchars($order["recipient_name"] ?? "-") ?></div>

                        <div class="info-data-label">No. Telepon / HP</div>
                        <div class="info-data-val"><?= htmlspecialchars($order["phone"] ?? "-") ?></div>

                        <div class="info-data-label">Alamat Lengkap</div>
                        <div class="info-data-val">
                            <?= nl2br(htmlspecialchars($order["address"] ?? "-")) ?>
                        </div>

                        <div class="info-data-label">Kota &amp; Kode Pos</div>
                        <div class="info-data-val">
                            <?= htmlspecialchars($order["city"] ?? "") ?><?= !empty($order["province"]) ? ", " . htmlspecialchars($order["province"]) : "" ?> <?= htmlspecialchars($order["postal_code"] ?? "") ?>
                        </div>

                        <?php if (!empty($order["shipping_courier"])): ?>
                            <div class="info-data-label">Kurir Ekspedisi</div>
                            <div class="info-data-val"><?= htmlspecialchars($order["shipping_courier"]) ?></div>
                        <?php endif; ?>

                        <?php if (!empty($order["tracking_number"])): ?>
                            <div class="info-data-label">Nomor Resi</div>
                            <div class="info-data-val">
                                <span class="resi-badge"><?= htmlspecialchars($order["tracking_number"]) ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($order["notes"])): ?>
                            <div class="info-data-label">Catatan Pesanan</div>
                            <div class="info-data-val" style="color: var(--ac);"><?= htmlspecialchars($order["notes"]) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: SUMMARY & ACTIONS -->
            <div>
                <!-- RINCIAN PEMBAYARAN -->
                <div class="detail-box">
                    <h2 class="detail-box-title">
                        RINCIAN PEMBAYARAN
                    </h2>

                    <div class="summary-row">
                        <span>Subtotal Produk</span>
                        <span class="val-white"><?= formatRupiah($order["subtotal"]) ?></span>
                    </div>

                    <div class="summary-row">
                        <span>Ongkos Kirim (Flat)</span>
                        <span class="val-white"><?= formatRupiah($order["shipping"]) ?></span>
                    </div>

                    <?php if ($discountVal > 0): ?>
                        <div class="summary-row" style="color: var(--ac);">
                            <span>Potongan Diskon Promo</span>
                            <span style="font-weight: 700;">-<?= formatRupiah($discountVal) ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="summary-total-row">
                        <span class="summary-total-label">Total Bayar</span>
                        <span class="summary-total-amount"><?= formatRupiah($order["total"]) ?></span>
                    </div>

                    <div style="margin-top: 18px; padding-top: 14px; border-top: 1px solid var(--ln); font-size: 13px;">
                        <div style="margin-bottom: 6px;">
                            <span style="color: var(--mu);">Metode:</span>
                            <strong style="color: var(--tx);"><?= htmlspecialchars($order["payment_method"] ?? "Transfer Bank") ?></strong>
                        </div>
                        <div>
                            <span style="color: var(--mu);">Status Bayar:</span>
                            <strong style="color: <?= ($order["payment_status"] ?? "") === "Lunas" ? "var(--ac)" : "#FBBF24" ?>;">
                                <?= htmlspecialchars($order["payment_status"] ?? "Menunggu Pembayaran") ?>
                            </strong>
                        </div>
                    </div>
                </div>

                <!-- LINK BELANJA LAGI -->
                <div style="text-align: center; margin-top: 16px;">
                    <a href="produk.php" class="back-link" style="color: var(--ac);">
                        &larr; LIHAT KOLEKSI LAINNYA
                    </a>
                </div>

            </div>

        </div>

    <?php endif; ?>

</div>

</body>
</html>
