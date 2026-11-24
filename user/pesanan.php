<?php
/**
 * [FILE]: user/pesanan.php
 * [BAGIAN]: Riwayat Pesanan & Pelacakan Paket Pelanggan
 * [DESKRIPSI]: Menampilkan daftar pesanan pembeli dengan filter tab status (Aktif, Menunggu, Selesai), resi kurir, dan tombol detail
 * [JANGAN UBAH]: Filter tab berbasis link GET murni tanpa JavaScript
 * [UBAH DI SINI]: Label status pesanan atau teks bantuan WhatsApp
 * [TAMBAH DI SINI]: Tombol unduh invoice PDF pesanan
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../shared/koneksi.php';
require_once __DIR__ . '/../shared/fungsi.php';

/** @var mysqli $conn */

$isLoggedIn = isset($_SESSION["user_id"]);
$userId = $isLoggedIn ? (int)$_SESSION["user_id"] : 0;
$name = $_SESSION["name"] ?? "Pelanggan";
$username = $_SESSION["username"] ?? "guest";

$orders = [];
$statusFilter = strtolower(trim($_GET["status"] ?? "semua"));

/* [QUERY]: Ambil Riwayat Pesanan Milik User */
if ($isLoggedIn && $userId > 0) {
    // Cek keberadaan kolom province di addresses agar kompatibel sebelum/sesudah migration.sql
    $hasProvince = false;
    $colCheck = $conn->query("SHOW COLUMNS FROM addresses LIKE 'province'");
    if ($colCheck && $colCheck->num_rows > 0) {
        $hasProvince = true;
    }
    $provinceSelect = $hasProvince ? "a.province," : "'' AS province,";

    // Ambil semua pesanan milik user
    $query = "
        SELECT
            o.*,
            a.recipient_name,
            a.phone,
            a.address,
            a.city,
            {$provinceSelect}
            a.postal_code
        FROM orders o
        LEFT JOIN addresses a ON o.address_id = a.id
        WHERE o.user_id = ?
    ";

    // Filter status tab jika dipilih
    if ($statusFilter === "aktif") {
        $query .= " AND o.order_status IN ('Diproses', 'Dikirim')";
    } elseif ($statusFilter === "menunggu") {
        $query .= " AND o.order_status = 'Menunggu Pembayaran'";
    } elseif ($statusFilter === "selesai") {
        $query .= " AND o.order_status = 'Selesai'";
    }

    $query .= " ORDER BY o.id DESC";

    $stmtOrders = $conn->prepare($query);
    if ($stmtOrders) {
        $stmtOrders->bind_param("i", $userId);
        $stmtOrders->execute();
        $res = $stmtOrders->get_result();

        // Siapkan statement pengambilan item pesanan
        $stmtItems = $conn->prepare("
            SELECT oi.*, p.image, p.size as p_size, p.category
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
            ORDER BY oi.id ASC
        ");

        while ($res && $order = $res->fetch_assoc()) {
            $orderId = (int)$order["id"];
            $items = [];

            if ($stmtItems) {
                $stmtItems->bind_param("i", $orderId);
                $stmtItems->execute();
                $resItems = $stmtItems->get_result();
                while ($resItems && $it = $resItems->fetch_assoc()) {
                    $items[] = $it;
                }
            }

            $order["items"] = $items;
            $orders[] = $order;
        }

        if ($stmtItems) $stmtItems->close();
        $stmtOrders->close();
    }
}

// Helper badge status
function getOrderStatusBadge(string $status): array {
    switch ($status) {
        case "Diproses":
            return [
                "label" => "DIPROSES",
                "class" => "badge-diproses",
                "desc"  => "Pesanan sudah dikonfirmasi & sedang disiapkan.",
                "icon"  => ""
            ];
        case "Dikirim":
            return [
                "label" => "DIKIRIM",
                "class" => "badge-dikirim",
                "desc"  => "Paket sedang dalam perjalanan pengiriman kurir.",
                "icon"  => ""
            ];
        case "Selesai":
            return [
                "label" => "SELESAI",
                "class" => "badge-selesai",
                "desc"  => "Pesanan telah selesai diterima.",
                "icon"  => ""
            ];
        case "Dibatalkan":
            return [
                "label" => "DIBATALKAN",
                "class" => "badge-dibatalkan",
                "desc"  => "Pesanan ini telah dibatalkan.",
                "icon"  => ""
            ];
        case "Menunggu Pembayaran":
        default:
            return [
                "label" => "MENUNGGU PEMBAYARAN",
                "class" => "badge-menunggu",
                "desc"  => "Menunggu transfer/bukti pembayaran.",
                "icon"  => ""
            ];
    }
}

// Generate URL WhatsApp Tracking Umum
$lastOrderCode = !empty($orders) ? ($orders[0]["order_code"] ?: "-") : "-";
$waTrackMsg = "Halo SecondSpace, saya ingin cek status dan nomor resi pengiriman pesanan saya. Akun: " . $name . " (@" . $username . "). Kode pesanan terakhir: " . $lastOrderCode . ".";
$waTrackUrl = waLink($whatsappNumber, $waTrackMsg);

$cartCount = isset($_SESSION["cart"]) ? count($_SESSION["cart"]) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Saya & Pelacakan — SecondSpace PreLoved</title>
    <link rel="stylesheet" href="modern-theme.css">
    <style>
        .pesanan-page-container {
            max-width: 980px;
            margin: 36px auto 80px;
            padding: 0 20px;
        }

        .page-header {
            margin-bottom: 28px;
        }

        .page-eyebrow {
            color: var(--ac);
            font-family: var(--display-font);
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            margin-bottom: 6px;
            display: block;
        }

        .page-title {
            font-family: var(--display-font);
            font-size: clamp(38px, 5.5vw, 56px);
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: -0.01em;
            color: var(--tx);
            margin: 0 0 10px 0;
            line-height: 1;
        }

        .page-subtitle {
            color: var(--mu);
            font-size: 14px;
            line-height: 1.6;
            margin: 0;
        }

        /* Filter Tabs (Murni Link PHP - No JS) */
        .filter-tabs-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 28px;
            flex-wrap: wrap;
            border-bottom: 1px solid var(--ln);
            padding-bottom: 14px;
        }

        .filter-tab-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            background: var(--s1);
            border: 1px solid var(--ln);
            border-radius: 4px;
            font-family: var(--display-font);
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--mu);
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .filter-tab-pill:hover {
            color: var(--tx);
            border-color: var(--tx);
        }

        .filter-tab-pill.active {
            background: var(--ac);
            color: #0A0A0A;
            border-color: var(--ac);
            font-weight: 800;
        }

        /* Order Card */
        .order-card {
            background: var(--s1);
            border: 1px solid var(--ln);
            border-radius: 4px;
            margin-bottom: 24px;
            overflow: hidden;
            transition: border-color 0.2s ease;
        }

        .order-card:hover {
            border-color: #3E3E3E;
        }

        /* Card Header */
        .order-card-header {
            background: #111111;
            padding: 16px 20px;
            border-bottom: 1px solid var(--ln);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .order-meta-info {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
        }

        .order-code-title {
            font-family: var(--display-font);
            font-size: 18px;
            font-weight: 800;
            letter-spacing: 0.06em;
            color: var(--tx);
        }

        .order-date-text {
            color: var(--mu);
            font-size: 12px;
        }

        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 4px;
            font-family: var(--display-font);
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .status-badge.badge-menunggu {
            background: rgba(217, 119, 6, 0.15);
            border: 1px solid #D97706;
            color: #FBBF24;
        }

        .status-badge.badge-diproses {
            background: rgba(59, 130, 246, 0.15);
            border: 1px solid #3B82F6;
            color: #60A5FA;
        }

        .status-badge.badge-dikirim {
            background: rgba(124, 154, 109, 0.18);
            border: 1px solid var(--ac);
            color: var(--ac);
        }

        .status-badge.badge-selesai {
            background: #1C1C1C;
            border: 1px solid #383838;
            color: #A3A3A3;
        }

        .status-badge.badge-dibatalkan {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid #EF4444;
            color: #F87171;
        }

        /* Tracking Banner (Live Tracking Status) */
        .tracking-banner {
            padding: 16px 20px;
            border-bottom: 1px solid var(--ln);
        }

        .tracking-banner.dikirim {
            background: rgba(124, 154, 109, 0.08);
            border-left: 4px solid var(--ac);
        }

        .tracking-banner.diproses {
            background: rgba(59, 130, 246, 0.08);
            border-left: 4px solid #3B82F6;
        }

        .tracking-banner.menunggu {
            background: rgba(217, 119, 6, 0.08);
            border-left: 4px solid #D97706;
        }

        .tracking-banner.selesai {
            background: rgba(255, 255, 255, 0.03);
            border-left: 4px solid #555555;
        }

        .tracking-banner-title {
            font-family: var(--display-font);
            font-size: 15px;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--tx);
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 4px;
        }

        .tracking-banner-desc {
            color: var(--mu);
            font-size: 13px;
            line-height: 1.5;
            margin: 0 0 12px 0;
        }

        .tracking-data-row {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        .tracking-data-item {
            font-size: 13px;
        }

        .tracking-data-item span {
            color: var(--mu);
            margin-right: 6px;
        }

        .tracking-data-item strong {
            color: var(--tx);
        }

        .resi-badge {
            background: #000000;
            border: 1px solid var(--ac);
            color: var(--ac);
            padding: 4px 10px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 13px;
            letter-spacing: 0.05em;
        }

        /* Order Items List */
        .order-items-list {
            padding: 16px 20px;
        }

        .order-item-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 12px 0;
            border-bottom: 1px solid #1C1C1C;
        }

        .order-item-row:last-child {
            border-bottom: none;
        }

        .order-item-left {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
        }

        .order-item-thumb {
            width: 52px;
            height: 52px;
            background: #EDEDED;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
        }

        .order-item-thumb img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            mix-blend-mode: multiply;
        }

        .order-item-thumb .thumb-letter {
            font-family: var(--display-font);
            font-size: 18px;
            font-weight: 800;
            color: #333333;
        }

        .order-item-name {
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

        .order-item-meta {
            color: var(--mu);
            font-size: 12px;
        }

        .order-item-subtotal {
            font-family: var(--body-font);
            font-size: 14px;
            font-weight: 700;
            color: var(--tx);
            text-align: right;
            white-space: nowrap;
        }

        /* Card Footer */
        .order-card-footer {
            background: #111111;
            padding: 16px 20px;
            border-top: 1px solid var(--ln);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .order-summary-meta {
            font-size: 13px;
            color: var(--mu);
        }

        .order-summary-meta .total-highlight {
            font-size: 18px;
            font-weight: 800;
            color: var(--ac);
            font-family: var(--display-font);
            letter-spacing: 0.02em;
            margin-left: 6px;
        }

        .order-card-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-order-outline {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 16px;
            background: transparent;
            color: var(--tx);
            border: 1px solid var(--ln);
            border-radius: 4px;
            font-family: var(--display-font);
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn-order-outline:hover {
            background: var(--s2);
            border-color: var(--tx);
        }

        .btn-order-solid {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 16px;
            background: var(--ac);
            color: #0A0A0A;
            border: 1px solid var(--ac);
            border-radius: 4px;
            font-family: var(--display-font);
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn-order-solid:hover {
            background: var(--ach);
            border-color: var(--ach);
        }

        /* Empty & Guest States */
        .state-box {
            background: var(--s1);
            border: 1px solid var(--ln);
            border-radius: 4px;
            padding: 60px 24px;
            text-align: center;
            margin-top: 20px;
        }

        .state-icon {
            font-size: 48px;
            margin-bottom: 16px;
            display: inline-block;
        }

        .state-title {
            font-family: var(--display-font);
            font-size: 28px;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: var(--tx);
            margin: 0 0 10px 0;
        }

        .state-desc {
            color: var(--mu);
            font-size: 14px;
            line-height: 1.6;
            max-width: 440px;
            margin: 0 auto 28px;
        }

        .state-actions {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        /* Help Box at Bottom */
        .help-box {
            margin-top: 40px;
            padding: 20px 24px;
            background: var(--s1);
            border: 1px solid var(--ln);
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
        }

        .help-text strong {
            display: block;
            font-family: var(--display-font);
            font-size: 16px;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: var(--tx);
            margin-bottom: 4px;
        }

        .help-text p {
            color: var(--mu);
            font-size: 13px;
            margin: 0;
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

<div class="pesanan-page-container">

    <!-- PAGE HEADER -->
    <div class="page-header" style="margin-bottom: 24px;">
        <span class="page-eyebrow">DASHBOARD &amp; PELACAKAN</span>
        <h1 class="page-title" style="margin-bottom: 0;">PESANAN SAYA</h1>
    </div>

    <?php if (!$isLoggedIn): ?>
        <!-- GUEST STATE: BELUM LOGIN -->
        <div class="state-box">
            
            <h2 class="state-title">Masuk untuk Melacak Pesanan</h2>
            <p class="state-desc">
                Silakan masuk dengan akun SecondSpace kamu untuk melihat pesanan yang sedang diproses dan mengecek status pengiriman.
            </p>
            <div class="state-actions">
                <a href="login.php?redirect=pesanan.php" class="btn-order-solid" style="padding: 12px 28px; font-size: 16px;">
                    MASUK KE AKUN
                </a>
                <a href="<?= $waTrackUrl ?>" target="_blank" rel="noopener" class="btn-order-outline" style="padding: 12px 24px; font-size: 16px;">
                    TANYA ADMIN VIA WA
                </a>
            </div>
        </div>

    <?php else: ?>

        <!-- FILTER TABS (MURNI LINK PHP) -->
        <div class="filter-tabs-row">
            <a href="pesanan.php" class="filter-tab-pill <?= $statusFilter === 'semua' ? 'active' : '' ?>">
                Semua Pesanan
            </a>
            <a href="pesanan.php?status=aktif" class="filter-tab-pill <?= $statusFilter === 'aktif' ? 'active' : '' ?>">
                Sedang Diproses &amp; Dikirim
            </a>
            <a href="pesanan.php?status=menunggu" class="filter-tab-pill <?= $statusFilter === 'menunggu' ? 'active' : '' ?>">
                Menunggu Pembayaran
            </a>
            <a href="pesanan.php?status=selesai" class="filter-tab-pill <?= $statusFilter === 'selesai' ? 'active' : '' ?>">
                Selesai
            </a>
        </div>

        <?php if (empty($orders)): ?>
            <!-- EMPTY STATE: BELUM ADA PESANAN -->
            <div class="state-box">
                
                <h2 class="state-title">Belum Ada Pesanan</h2>
                <p class="state-desc">
                    <?php if ($statusFilter !== "semua"): ?>
                        Tidak ada pesanan dengan status yang dipilih.
                    <?php else: ?>
                        Kamu belum memiliki pesanan aktif atau riwayat pembelian. Temukan pakaian preloved pilihanmu sekarang!
                    <?php endif; ?>
                </p>
                <div class="state-actions">
                    <a href="produk.php" class="btn-order-solid" style="padding: 12px 28px; font-size: 16px;">
                        EXPLORE KATALOG PRODUK &rarr;
                    </a>
                </div>
            </div>

        <?php else: ?>

            <!-- DAFTAR PESANAN -->
            <?php foreach ($orders as $order): ?>
                <?php
                $orderCode = $order["order_code"] ?: ("SS-" . $order["id"]);
                $currentStatus = $order["order_status"] ?: "Menunggu Pembayaran";
                $statusBadge = getOrderStatusBadge($currentStatus);
                $hasTracking = !empty($order["tracking_number"]);
                $courierName = $order["shipping_courier"] ?: ($order["courier"] ?? "Ekspedisi Reguler");
                $dateFormatted = date("d M Y, H:i", strtotime($order["created_at"])) . " WIB";
                ?>

                <div class="order-card">
                    <!-- HEADER KARTU PESANAN -->
                    <div class="order-card-header">
                        <div class="order-meta-info">
                            <span class="order-code-title">#<?= htmlspecialchars($orderCode) ?></span>
                            <span class="order-date-text"><?= htmlspecialchars($dateFormatted) ?></span>
                        </div>
                        <div>
                            <span class="status-badge <?= $statusBadge["class"] ?>">
                                <?= $statusBadge["icon"] ?> <?= $statusBadge["label"] ?>
                            </span>
                        </div>
                    </div>

                    <!-- BANNER LIVE TRACKING / STATUS PESANAN -->
                    <?php if ($currentStatus === "Dikirim"): ?>
                        <div class="tracking-banner dikirim">
                            <div class="tracking-banner-title">
                                PAKET SEDANG DALAM PENGIRIMAN
                            </div>
                            <p class="tracking-banner-desc">
                                Paketmu telah diserahkan ke kurir dan sedang dalam perjalanan menuju alamat pengiriman.
                            </p>
                            <div class="tracking-data-row">
                                <div class="tracking-data-item">
                                    <span>Kurir:</span>
                                    <strong><?= htmlspecialchars($courierName) ?></strong>
                                </div>
                                <div class="tracking-data-item">
                                    <span>Nomor Resi:</span>
                                    <?php if ($hasTracking): ?>
                                        <span class="resi-badge"><?= htmlspecialchars($order["tracking_number"]) ?></span>
                                    <?php else: ?>
                                        <span style="color: var(--mu); font-style: italic;">Sedang diperbarui oleh admin</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php
                            $waTrackSpecific = "Halo SecondSpace, saya ingin melacak pengiriman pesanan #" . $orderCode . ($hasTracking ? " dengan no. resi " . $order["tracking_number"] : "") . ". Mohon info posisi paketnya ya.";
                            $waTrackUrlSpecific = waLink($whatsappNumber, $waTrackSpecific);
                            ?>
                            <div style="margin-top: 14px;">
                                <a href="<?= $waTrackUrlSpecific ?>" target="_blank" rel="noopener" class="btn-order-solid" style="font-size: 13px; padding: 7px 14px;">
                                    LACAK VIA WHATSAPP CS
                                </a>
                            </div>
                        </div>

                    <?php elseif ($currentStatus === "Diproses"): ?>
                        <div class="tracking-banner diproses">
                            <div class="tracking-banner-title">
                                PESANAN SEDANG DIPROSES &amp; DIKEMAS
                            </div>
                            <p class="tracking-banner-desc">
                                Pembayaran pesananmu telah dikonfirmasi. Pakaian preloved kamu sedang disortir, dibersihkan, dan dikemas dengan rapi oleh tim SecondSpace sebelum diserahkan ke pihak ekspedisi.
                            </p>
                            <?php
                            $waProgresMsg = "Halo SecondSpace, saya ingin menanyakan progres pesanan #" . $orderCode . " yang sedang diproses.";
                            $waProgresUrl = waLink($whatsappNumber, $waProgresMsg);
                            ?>
                            <a href="<?= $waProgresUrl ?>" target="_blank" rel="noopener" class="btn-order-outline" style="font-size: 13px; padding: 7px 14px;">
                                TANYA PROGRES PESANAN
                            </a>
                        </div>

                    <?php elseif ($currentStatus === "Menunggu Pembayaran"): ?>
                        <?php
                        $waConfirmOrderMsg = "Halo SecondSpace, saya ingin konfirmasi pembayaran pesanan #" . $orderCode . ".\n"
                            . "Nama: " . ($order["recipient_name"] ?? $name) . "\n"
                            . "Metode: " . ($order["payment_method"] ?? "Transfer Bank") . "\n"
                            . "Total Tagihan: " . formatRupiah($order["total"]) . "\n"
                            . "Bukti transfer/pembayaran saya lampirkan di chat ini. Mohon segera diproses ya!";
                        $waConfirmOrderUrl = waLink($whatsappNumber, $waConfirmOrderMsg);
                        ?>
                        <div class="tracking-banner menunggu">
                            <div class="tracking-banner-title">
                                MENUNGGU PEMBAYARAN
                            </div>
                            <p class="tracking-banner-desc">
                                Total tagihan: <strong><?= formatRupiah($order["total"]) ?></strong> via <strong><?= htmlspecialchars($order["payment_method"] ?? "Transfer Bank") ?></strong>. Segera selesaikan pembayaran dan kirim bukti transfer agar pesananmu dapat langsung diproses.
                            </p>
                            <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 14px;">
                                <a href="<?= $waConfirmOrderUrl ?>" target="_blank" rel="noopener" class="btn-order-solid" style="font-size: 13px; padding: 8px 18px;">
                                    KONFIRMASI PEMBAYARAN VIA WHATSAPP CS
                                </a>
                            </div>
                        </div>

                    <?php elseif ($currentStatus === "Selesai"): ?>
                        <div class="tracking-banner selesai">
                            <div class="tracking-banner-title">
                                PESANAN SELESAI
                            </div>
                            <p class="tracking-banner-desc" style="margin-bottom: 0;">
                                Pesanan ini telah berhasil diselesaikan. Terima kasih telah berbelanja di SecondSpace!
                            </p>
                        </div>
                    <?php endif; ?>

                    <!-- DAFTAR ITEM PRODUK -->
                    <div class="order-items-list">
                        <?php if (!empty($order["items"])): ?>
                            <?php foreach ($order["items"] as $it): ?>
                                <div class="order-item-row">
                                    <div class="order-item-left">
                                        <div class="order-item-thumb">
                                            <?php $orderItemImg = gambar_produk_url($it["image"] ?? '', 'user'); ?>
                                            <?php if (!empty($orderItemImg)): ?>
                                                <img src="<?= htmlspecialchars($orderItemImg) ?>" alt="<?= htmlspecialchars($it["product_name"]) ?>">
                                            <?php else: ?>
                                                <span class="thumb-letter"><?= htmlspecialchars(substr($it["product_name"] ?? "S", 0, 1)) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div class="order-item-name"><?= htmlspecialchars($it["product_name"]) ?></div>
                                            <div class="order-item-meta">
                                                Ukuran: <?= htmlspecialchars($it["p_size"] ?: ($it["size"] ?? "All Size")) ?> &middot;
                                                x<?= (int)$it["quantity"] ?> &middot;
                                                <?= formatRupiah($it["price"]) ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="order-item-subtotal">
                                        <?= formatRupiah($it["subtotal"]) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="color: var(--mu); font-size: 13px; padding: 10px 0;">
                                Rincian item pesanan
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- FOOTER KARTU PESANAN (TOTAL & AKSI) -->
                    <div class="order-card-footer">
                        <div class="order-summary-meta">
                            Alamat: <?= htmlspecialchars($order["recipient_name"] ?? $name) ?> &middot;
                            <?= htmlspecialchars($order["city"] ?? "Jakarta") ?><br>
                            <?php if (!empty($order["notes"])): ?>
                                <span style="color: var(--ac); font-size: 12px;"><?= htmlspecialchars($order["notes"]) ?></span> &middot;
                            <?php endif; ?>
                            <span>Total Pesanan:</span>
                            <strong class="total-highlight"><?= formatRupiah($order["total"]) ?></strong>
                        </div>
                        <div class="order-card-actions">
                            <a href="detail_pesanan.php?id=<?= $order["id"] ?>" class="btn-order-outline">
                                DETAIL PESANAN
                            </a>
                            <a href="konfirmasi.php?kode=<?= urlencode($orderCode) ?>" class="btn-order-outline">
                                RINGKASAN &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php endif; ?>

        <!-- BANTUAN PELACAKAN (Hanya untuk pengguna yang sudah login) -->
        <div class="help-box">
            <div class="help-text">
                <strong>Butuh Bantuan Pelacakan?</strong>
                <p>Admin SecondSpace siap membantu mengecek posisi pesanan dan kendala ekspedisi langsung lewat WhatsApp.</p>
            </div>
            <a href="<?= $waTrackUrl ?>" target="_blank" rel="noopener" class="btn-order-solid">
                HUBUNGI CS VIA WHATSAPP
            </a>
        </div>

    <?php endif; ?>

</div>

</body>
</html>
