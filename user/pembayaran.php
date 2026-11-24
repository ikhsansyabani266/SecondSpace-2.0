<?php
/**
 * [FILE]: user/pembayaran.php
 * [BAGIAN]: Pemilihan Metode Pembayaran & Penyelesaian Pesanan
 * [DESKRIPSI]: Menampilkan rincian tagihan final, instruksi rekening bank, dan menyelesaikan order ke database
 * [JANGAN UBAH]: Algoritma pembuatan order_code unik dan transaksi insert order_items
 * [UBAH DI SINI]: Rekening tujuan transfer bank di shared/config.php
 * [TAMBAH DI SINI]: Metode pembayaran baru (misal QRIS / e-wallet)
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "auth.php";
require_once __DIR__ . '/../shared/koneksi.php';
require_once __DIR__ . '/../shared/fungsi.php';
require_once __DIR__ . '/../shared/qris_generator.php';

/** @var mysqli $conn */

/* [KEAMANAN]: Guard Validasi Alur Belanja */
if (!isset($_SESSION["cart"]) || empty($_SESSION["cart"])) {
    header("Location: keranjang.php");
    exit;
}

// Guard Alamat Belum Diisi
if (!isset($_SESSION["checkout_address"]) || empty($_SESSION["checkout_address"])) {
    header("Location: checkout.php");
    exit;
}

$checkoutAddress = $_SESSION["checkout_address"];

// Informasi Rekening & E-Wallet Toko (Defensif)
$bankName = $bankName ?? (defined('BANK_NAME') ? BANK_NAME : 'Bank Central Asia (BCA)');
$bankAccountNumber = $bankAccountNumber ?? (defined('BANK_ACCOUNT_NUMBER') ? BANK_ACCOUNT_NUMBER : '8801928391');
$bankAccountName = $bankAccountName ?? (defined('BANK_ACCOUNT_NAME') ? BANK_ACCOUNT_NAME : 'SecondSpace Official');
$ewalletName = $ewalletName ?? (defined('EWALLET_NAME') ? EWALLET_NAME : 'DANA / GoPay / OVO');
$ewalletNumber = $ewalletNumber ?? (defined('EWALLET_NUMBER') ? EWALLET_NUMBER : '0895327292342');
$ewalletAccountName = $ewalletAccountName ?? (defined('EWALLET_ACCOUNT_NAME') ? EWALLET_ACCOUNT_NAME : 'SecondSpace Official');

// Hitung subtotal & verifikasi item dari database
$cartItems = [];
$subtotal = 0;
foreach ($_SESSION["cart"] as $pId => $item) {
    $pId = (int)$pId;
    $stmt = $conn->prepare("SELECT id, name, price, stock, size, image FROM products WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $pId);
        $stmt->execute();
        $res = $stmt->get_result();
        $pLive = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        if ($pLive && (int)$pLive["stock"] > 0) {
            $qty = min((int)$item["quantity"], (int)$pLive["stock"]);
            $price = (float)$pLive["price"];
            $cartItems[] = [
                "id"       => $pId,
                "name"     => $pLive["name"],
                "price"    => $price,
                "size"     => $pLive["size"] ?: "All Size",
                "image"    => $pLive["image"] ?? "",
                "quantity" => $qty,
                "subtotal" => $price * $qty
            ];
            $subtotal += ($price * $qty);
        }
    }
}

if (empty($cartItems)) {
    header("Location: keranjang.php");
    exit;
}

$shipping = $shippingCost ?? 15000;
$discountError = "";
$discountSuccess = "";

// Proses Tambah / Hapus Kode Diskon (Pure PHP Form Submission - No JS)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["discount_action"])) {
    $dAction = $_POST["discount_action"];
    if ($dAction === "apply") {
        $inputCode = strtoupper(trim($_POST["discount_code"] ?? ""));
        if (empty($inputCode)) {
            $discountError = "Silakan ketik kode diskon.";
        } elseif (isset($validDiscounts[$inputCode])) {
            $dInfo = $validDiscounts[$inputCode];
            $_SESSION["discount"] = [
                "code"        => $inputCode,
                "type"        => $dInfo["type"],
                "value"       => $dInfo["value"],
                "description" => $dInfo["description"]
            ];
            $discountSuccess = "Kode diskon " . htmlspecialchars($inputCode) . " berhasil diterapkan!";
        } else {
            $discountError = "Kode diskon \"" . htmlspecialchars($inputCode) . "\" tidak valid atau sudah tidak berlaku.";
        }
    } elseif ($dAction === "remove") {
        unset($_SESSION["discount"]);
        $discountSuccess = "Kode diskon berhasil dihapus.";
    }
}

// Hitung nilai diskon aktif
$discountAmount = 0;
$appliedDiscount = $_SESSION["discount"] ?? null;
if ($appliedDiscount && isset($validDiscounts[$appliedDiscount["code"]])) {
    $discountAmount = calculateDiscount($appliedDiscount, (float)$subtotal, (float)$shipping);
} else {
    unset($_SESSION["discount"]);
    $appliedDiscount = null;
}

$total = max(0, $subtotal + $shipping - $discountAmount);
$cartCount = count($cartItems);
$isLoggedIn = true;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Metode Pembayaran — SecondSpace PreLoved</title>
    <link rel="stylesheet" href="modern-theme.css">
    <style>
        /* Steps Bar (Barlow Condensed, Streetwear) */
        .steps-nav {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin: 16px 0 36px 0;
            flex-wrap: wrap;
        }

        .step-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 18px;
            border-radius: 4px;
            font-family: var(--display-font);
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            border: 1px solid var(--ln);
            background: var(--s1);
            color: var(--mu);
        }

        .step-pill.active {
            background: var(--ac);
            color: #0A0A0A;
            border-color: var(--ac);
            font-weight: 800;
        }

        .step-pill.done {
            background: transparent;
            color: var(--ac);
            border-color: var(--ac);
        }

        .step-divider {
            color: var(--ln);
            font-size: 14px;
        }

        /* 2-Column Checkout Layout */
        .checkout-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 340px;
            gap: 32px;
            align-items: stretch;
            margin-bottom: 60px;
        }

        .checkout-box {
            background: var(--s1);
            border: 1px solid var(--ln);
            border-radius: 4px;
            padding: 32px;
        }

        .checkout-title {
            font-family: var(--display-font);
            font-size: 28px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: -0.01em;
            color: var(--tx);
            margin: 0 0 24px 0;
            border-bottom: 1px solid var(--ln);
            padding-bottom: 16px;
        }

        /* Pure CSS Radio Selection (No JavaScript) */
        .pay-option-wrapper {
            margin-bottom: 14px;
            position: relative;
        }

        .pay-radio-input {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
            pointer-events: none;
        }

        .pay-card-label {
            display: flex;
            align-items: center;
            gap: 16px;
            background: var(--s2);
            border: 1px solid var(--ln);
            border-radius: 4px;
            padding: 18px 20px;
            cursor: pointer;
            transition: all 0.2s ease;
            user-select: none;
        }

        .pay-card-label:hover {
            border-color: #4A4A4A;
        }

        .pay-radio-circle {
            width: 22px;
            height: 22px;
            border: 2px solid var(--mu);
            border-radius: 50%;
            display: inline-block;
            flex-shrink: 0;
            transition: all 0.2s ease;
        }

        .pay-card-header {
            flex: 1;
        }

        .pay-card-title {
            display: block;
            font-family: var(--display-font);
            font-size: 19px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: var(--tx);
            margin-bottom: 2px;
        }

        .pay-card-sub {
            display: block;
            color: var(--mu);
            font-size: 13px;
        }

        /* Active State */
        .pay-radio-input:checked + .pay-card-label {
            border-color: var(--ac);
            background: #151D13;
        }

        .pay-radio-input:checked + .pay-card-label .pay-radio-circle {
            border-color: var(--ac);
            background: var(--ac);
            box-shadow: inset 0 0 0 4px #151D13;
        }

        /* CSS-Only Instruction Box Display */
        .pay-instruction-box {
            display: none;
            margin-top: 10px;
            background: var(--bg);
            border: 1px solid var(--ln);
            border-left: 3px solid var(--ac);
            border-radius: 4px;
            padding: 20px 22px;
        }

        .pay-radio-input:checked ~ .pay-instruction-box {
            display: block;
        }

        .pay-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 14px;
            margin-bottom: 16px;
        }

        .pay-info-item span {
            display: block;
            color: var(--mu);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 2px;
        }

        .pay-info-item strong {
            display: block;
            color: var(--tx);
            font-size: 15px;
        }

        .pay-info-item strong.highlight-val {
            color: var(--ac);
            font-family: var(--display-font);
            font-size: 20px;
            letter-spacing: 0.05em;
        }

        .pay-reminder-highlight {
            background: rgba(124, 154, 109, 0.12);
            border: 1px solid var(--ac);
            color: var(--tx);
            padding: 12px 14px;
            border-radius: 4px;
            font-size: 13px;
            line-height: 1.5;
        }

        .cod-instruction-text {
            color: var(--tx);
            font-size: 14px;
            line-height: 1.6;
        }

        /* Summary Sidebar (Equal Height Stretched) */
        .summary-card {
            background: var(--s1);
            border: 1px solid var(--ln);
            border-radius: 4px;
            padding: 28px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
        }

        .summary-top-content {
            flex: 1;
        }

        .summary-title {
            font-family: var(--display-font);
            font-size: 24px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--tx);
            margin: 0 0 20px 0;
            border-bottom: 1px solid var(--ln);
            padding-bottom: 14px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 14px;
            font-size: 14px;
            color: var(--mu);
        }

        .summary-row.total-row {
            border-top: 1px solid var(--ln);
            padding-top: 16px;
            margin-top: 20px;
            font-size: 20px;
            font-weight: 800;
            font-family: var(--display-font);
            letter-spacing: 0.02em;
            color: var(--tx);
        }

        .summary-bottom-actions {
            margin-top: 28px;
        }

        /* Discount Code Box */
        .discount-box {
            margin-top: 22px;
            padding-top: 18px;
            border-top: 1px solid var(--ln);
        }

        .discount-header-label {
            font-family: var(--display-font);
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--mu);
            margin-bottom: 10px;
        }

        .discount-input-group {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .discount-input {
            flex: 1;
            height: 40px !important;
            background: #121212 !important;
            border: 1px solid var(--ln) !important;
            border-radius: 4px !important;
            color: var(--tx) !important;
            padding: 0 12px !important;
            font-family: var(--body-font) !important;
            font-size: 13px !important;
            text-transform: uppercase;
            box-sizing: border-box;
        }

        .discount-input:focus {
            border-color: var(--ac) !important;
            outline: none;
        }

        .btn-apply-discount {
            height: 40px;
            padding: 0 16px;
            background: var(--ac);
            color: #0A0A0A;
            border: 1px solid var(--ac);
            border-radius: 4px;
            font-family: var(--display-font);
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .btn-apply-discount:hover {
            background: var(--ach);
            border-color: var(--ach);
        }

        .discount-applied-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(124, 154, 109, 0.1);
            border: 1px solid var(--ac);
            border-radius: 4px;
            padding: 10px 12px;
        }

        .discount-code-tag {
            font-family: var(--display-font);
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 0.08em;
            color: var(--ac);
            text-transform: uppercase;
        }

        .discount-desc-tag {
            display: block;
            font-size: 11px;
            color: var(--mu);
            margin-top: 2px;
        }

        .btn-remove-discount {
            background: transparent;
            border: none;
            color: #ff6b6b;
            font-family: var(--display-font);
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.05em;
            cursor: pointer;
            padding: 4px 6px;
            text-transform: uppercase;
        }

        .btn-remove-discount:hover {
            color: #ff4747;
            text-decoration: underline;
        }

        .discount-alert {
            font-size: 12px;
            margin-top: 8px;
            line-height: 1.4;
        }

        .discount-alert.error {
            color: #ff6b6b;
        }

        .discount-alert.success {
            color: var(--ac);
        }

        /* Summary Items & Details List */
        .summary-items-list {
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--ln);
        }

        .summary-item-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 0;
            border-bottom: 1px solid #1A1D20;
        }

        .summary-item-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .summary-item-thumb {
            width: 44px;
            height: 44px;
            border-radius: 4px;
            background: #FFFFFF;
            border: 1px solid #333333;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .summary-item-thumb img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        .summary-item-info {
            flex: 1;
            min-width: 0;
        }

        .summary-item-name {
            font-family: var(--body-font);
            font-size: 13px;
            font-weight: 700;
            color: var(--tx);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 2px;
        }

        .summary-item-meta {
            font-size: 11px;
            color: var(--mu);
        }

        .summary-item-price {
            font-family: var(--display-font);
            font-size: 14px;
            font-weight: 700;
            color: var(--tx);
            text-align: right;
            flex-shrink: 0;
        }

        /* Summary Destination Box */
        .summary-destination-box {
            margin: 14px 0 16px 0;
            padding: 12px 14px;
            background: #0E1012;
            border: 1px solid var(--ln);
            border-radius: 4px;
            font-size: 12px;
        }

        .summary-dest-label {
            font-family: var(--display-font);
            font-size: 11px;
            font-weight: 800;
            color: var(--mu);
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .summary-dest-recipient {
            color: var(--tx);
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .summary-dest-address {
            color: var(--mu);
            font-size: 12px;
            line-height: 1.4;
            margin-bottom: 6px;
        }

        .summary-dest-courier {
            display: inline-block;
            font-size: 11px;
            color: var(--ac);
            font-weight: 600;
        }

        @media (max-width: 860px) {
            .checkout-layout {
                grid-template-columns: 1fr;
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
        <a href="pesanan.php" class="nav-pesanan">PESANAN</a>
        <a href="logout.php" class="logout">KELUAR</a>
    </div>
</nav>

<div class="container">

    <!-- STEPS PROGRESS BAR -->
    <div class="steps-nav">
        <a href="checkout.php" class="step-pill done" style="text-decoration: none;">
            <span class="check-icon-clean">[OK]</span>
            <span>1 ALAMAT</span>
        </a>
        <span class="step-divider">——</span>
        <div class="step-pill active">
            <span>2</span>
            <span>PEMBAYARAN</span>
        </div>
        <span class="step-divider">——</span>
        <div class="step-pill">
            <span>3</span>
            <span>KONFIRMASI</span>
        </div>
    </div>

    <div class="checkout-layout">

        <!-- FORM PILIH PEMBAYARAN (KOLOM KIRI) -->
        <div class="checkout-box">
            <h1 class="checkout-title">Metode Pembayaran</h1>

            <form method="POST" action="konfirmasi.php" id="paymentForm">

                <!-- 1. TRANSFER BANK (SELECTED BY DEFAULT) -->
                <div class="pay-option-wrapper">
                    <input type="radio" id="pay_transfer" name="payment_method" value="Transfer Bank" checked class="pay-radio-input">
                    <label for="pay_transfer" class="pay-card-label">
                        <span class="pay-radio-circle"></span>
                        <div class="pay-card-header">
                            <span class="pay-card-title">Transfer Bank</span>
                            <span class="pay-card-sub">BCA / Mandiri / BNI / BRI manual transfer</span>
                        </div>
                    </label>
                    <div class="pay-instruction-box">
                        <div class="pay-info-grid">
                            <div class="pay-info-item">
                                <span>Bank:</span>
                                <strong><?= htmlspecialchars($bankName) ?></strong>
                            </div>
                            <div class="pay-info-item">
                                <span>No. Rekening:</span>
                                <strong class="highlight-val"><?= htmlspecialchars($bankAccountNumber) ?></strong>
                            </div>
                            <div class="pay-info-item">
                                <span>Atas Nama:</span>
                                <strong><?= htmlspecialchars($bankAccountName) ?></strong>
                            </div>
                            <div class="pay-info-item">
                                <span>Jumlah:</span>
                                <strong class="highlight-val"><?= formatRupiah($total) ?></strong>
                            </div>
                        </div>
                        <div class="pay-reminder-highlight">
                            Penting: simpan bukti transfer (screenshot atau foto struk). Bukti ini akan kamu kirim lewat WhatsApp untuk konfirmasi pembayaran.
                        </div>
                    </div>
                </div>

                <!-- 2. QRIS DINAMIS (NOMINAL OTOMATIS SESUAI TOTAL TRANSAKSI) -->
                <?php
                $qrisDynamicImg = get_qris_dynamic_image_base64((int)$total);
                ?>
                <div class="pay-option-wrapper">
                    <input type="radio" id="pay_qris" name="payment_method" value="QRIS" class="pay-radio-input">
                    <label for="pay_qris" class="pay-card-label">
                        <span class="pay-radio-circle"></span>
                        <div class="pay-card-header">
                            <span class="pay-card-title">QRIS Dinamis</span>
                            <span class="pay-card-sub">BCA, GoPay, OVO, DANA, ShopeePay &amp; Semua Bank (Nominal Otomatis)</span>
                        </div>
                        <span style="font-family: var(--display-font); font-size: 11px; font-weight: 800; background: var(--ac); color: #0A0A0A; padding: 4px 10px; border-radius: 3px; letter-spacing: 0.05em;">AUTO NOMINAL</span>
                    </label>
                    <div class="pay-instruction-box">
                        <div style="text-align: center; margin: 12px 0 16px 0;">
                            <div style="display: inline-block; background: #FFFFFF; padding: 18px; border-radius: 8px; border: 1px solid #333; box-shadow: 0 6px 24px rgba(0,0,0,0.5); max-width: 290px;">
                                <div style="font-family: var(--display-font); font-size: 18px; font-weight: 900; color: #111111; letter-spacing: 0.08em; margin-bottom: 6px; text-transform: uppercase;">
                                    QRIS STANDAR ASPI
                                </div>
                                <img src="<?= $qrisDynamicImg ?: ('qris_image.php?nominal=' . (int)$total) ?>" alt="QRIS Dinamis Nominal <?= formatRupiah($total) ?>" style="width: 100%; height: auto; display: block; border-radius: 4px; margin: 0 auto;">
                                <div style="margin-top: 10px; font-family: var(--display-font); font-size: 12px; font-weight: 800; color: #111111; letter-spacing: 0.05em; text-transform: uppercase;">
                                    SECONDSPACE PRELOVED OFFICIAL
                                </div>
                                <div style="font-size: 10px; color: #666666; margin-top: 2px;">
                                    NMID: ID1026631705005 &bull; A01
                                </div>
                            </div>

                            <!-- BADGE NOMINAL OTOMATIS -->
                            <div style="max-width: 320px; margin: 14px auto 0 auto; background: #111215; border: 1px solid var(--ac); border-radius: 6px; padding: 10px 14px; text-align: center;">
                                <div style="font-family: var(--display-font); font-size: 11px; font-weight: 700; color: var(--mu); letter-spacing: 0.08em; text-transform: uppercase;">
                                    Total Tagihan Otomatis Pas
                                </div>
                                <div style="font-family: var(--display-font); font-size: 22px; font-weight: 900; color: var(--ac); letter-spacing: 0.05em;">
                                    <?= formatRupiah($total) ?>
                                </div>
                                <div style="font-size: 12px; color: #CCCCCC; margin-top: 4px; line-height: 1.4;">
                                    Saat discan dengan BCA, Mandiri, GoPay, DANA, dll., nominal <strong><?= formatRupiah($total) ?></strong> langsung otomatis terisi tanpa perlu ketik manual.
                                </div>
                            </div>
                        </div>

                        <div class="pay-info-grid">
                            <div class="pay-info-item">
                                <span>Metode:</span>
                                <strong>QRIS Dinamis (Auto Amount)</strong>
                            </div>
                            <div class="pay-info-item">
                                <span>Nominal Terkunci:</span>
                                <strong class="highlight-val"><?= formatRupiah($total) ?></strong>
                            </div>
                        </div>
                        <div class="pay-reminder-highlight">
                            Penting: Buka aplikasi mobile banking atau e-wallet kamu, scan barcode QRIS dinamis di atas. Nominal otomatis terkunci pas <?= formatRupiah($total) ?>. Simpan bukti pembayaran untuk konfirmasi pesanan.
                        </div>
                    </div>
                </div>

                <!-- 2. E-WALLET -->
                <div class="pay-option-wrapper">
                    <input type="radio" id="pay_ewallet" name="payment_method" value="E-Wallet" class="pay-radio-input">
                    <label for="pay_ewallet" class="pay-card-label">
                        <span class="pay-radio-circle"></span>
                        <div class="pay-card-header">
                            <span class="pay-card-title">E-Wallet</span>
                            <span class="pay-card-sub">DANA / GoPay / OVO instant transfer</span>
                        </div>
                    </label>
                    <div class="pay-instruction-box">
                        <div class="pay-info-grid">
                            <div class="pay-info-item">
                                <span>E-Wallet:</span>
                                <strong><?= htmlspecialchars($ewalletName ?? 'DANA / GoPay / OVO') ?></strong>
                            </div>
                            <div class="pay-info-item">
                                <span>Nomor E-Wallet:</span>
                                <strong class="highlight-val"><?= htmlspecialchars($ewalletNumber ?? '0895327292342') ?></strong>
                            </div>
                            <div class="pay-info-item">
                                <span>Atas Nama:</span>
                                <strong><?= htmlspecialchars($ewalletAccountName ?? 'SecondSpace Official') ?></strong>
                            </div>
                            <div class="pay-info-item">
                                <span>Jumlah:</span>
                                <strong class="highlight-val"><?= formatRupiah($total) ?></strong>
                            </div>
                        </div>
                        <div class="pay-reminder-highlight">
                            Penting: simpan bukti pembayaran (screenshot transaksi e-wallet). Bukti ini akan kamu kirim lewat WhatsApp untuk konfirmasi pembayaran.
                        </div>
                    </div>
                </div>

                <!-- 3. COD -->
                <div class="pay-option-wrapper">
                    <input type="radio" id="pay_cod" name="payment_method" value="COD" class="pay-radio-input">
                    <label for="pay_cod" class="pay-card-label">
                        <span class="pay-radio-circle"></span>
                        <div class="pay-card-header">
                            <span class="pay-card-title">COD (Bayar di Tempat)</span>
                            <span class="pay-card-sub">Bayar tunai langsung saat barang diterima</span>
                        </div>
                    </label>
                    <div class="pay-instruction-box">
                        <div class="cod-instruction-text">
                            Siapkan uang pas sebesar <strong><?= formatRupiah($total) ?></strong> saat barang diterima. Admin akan mengonfirmasi pesananmu lewat WhatsApp.
                        </div>
                    </div>
                </div>

            </form>
        </div>

        <!-- RINGKASAN PESANAN (KOLOM KANAN - EQUAL HEIGHT) -->
        <div class="summary-card">
            <div class="summary-top-content">
                <h2 class="summary-title">Ringkasan Pesanan</h2>

                <!-- DAFTAR ITEM PRODUK YANG DIBELI -->
                <div class="summary-items-list">
                    <?php foreach ($cartItems as $cItem): ?>
                        <?php
                            $imgSrc = gambar_produk_url($cItem["image"] ?? '', 'user');
                        ?>
                        <div class="summary-item-row">
                            <div class="summary-item-thumb">
                                <?php if (!empty($imgSrc)): ?>
                                    <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($cItem["name"]) ?>">
                                <?php else: ?>
                                    <span style="font-size: 10px; color: #888;">FOTO</span>
                                <?php endif; ?>
                            </div>
                            <div class="summary-item-info">
                                <div class="summary-item-name" title="<?= htmlspecialchars($cItem["name"]) ?>">
                                    <?= htmlspecialchars($cItem["name"]) ?>
                                </div>
                                <div class="summary-item-meta">
                                    Size: <?= htmlspecialchars($cItem["size"]) ?> &bull; <?= (int)$cItem["quantity"] ?>x
                                </div>
                            </div>
                            <div class="summary-item-price">
                                <?= formatRupiah($cItem["subtotal"]) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- RINCIAN TUJUAN PENGIRIMAN -->
                <div class="summary-destination-box">
                    <div class="summary-dest-label">
                        <span>Alamat Pengiriman</span>
                        <a href="checkout.php" style="color: var(--ac); font-size: 10px; text-decoration: underline;">UBAH</a>
                    </div>
                    <div class="summary-dest-recipient">
                        <?= htmlspecialchars($checkoutAddress["recipient_name"] ?? "Pelanggan") ?>
                        <span style="font-weight: normal; font-size: 11px; color: var(--mu);">(<?= htmlspecialchars($checkoutAddress["phone"] ?? "-") ?>)</span>
                    </div>
                    <div class="summary-dest-address">
                        <?= htmlspecialchars($checkoutAddress["address"] ?? "-") ?>,
                        <?= htmlspecialchars($checkoutAddress["city"] ?? "") ?> <?= htmlspecialchars($checkoutAddress["postal_code"] ?? "") ?>
                    </div>
                    <div class="summary-dest-courier">
                        Ekspedisi Reguler Flat (Rp 15.000)
                    </div>
                </div>

                <div class="summary-row">
                    <span>Subtotal (<?= $cartCount ?> produk)</span>
                    <span><?= formatRupiah($subtotal) ?></span>
                </div>
                <div class="summary-row">
                    <span>Ongkos Kirim (Flat)</span>
                    <span><?= formatRupiah($shipping) ?></span>
                </div>

                <?php if ($appliedDiscount && $discountAmount > 0): ?>
                    <div class="summary-row" style="color: var(--ac); font-weight: 700;">
                        <span>Diskon (<?= htmlspecialchars($appliedDiscount["code"]) ?>)</span>
                        <span>-<?= formatRupiah($discountAmount) ?></span>
                    </div>
                <?php endif; ?>

                <div class="summary-row total-row">
                    <span>Total Tagihan</span>
                    <span style="color: var(--ac);"><?= formatRupiah($total) ?></span>
                </div>

                <!-- FORM KODE DISKON (PURE PHP - NO JS) -->
                <div class="discount-box">
                    <div class="discount-header-label">KODE DISKON</div>
                    <?php if ($appliedDiscount): ?>
                        <div class="discount-applied-card">
                            <div>
                                <span class="discount-code-tag">[Kupon Aktif] <?= htmlspecialchars($appliedDiscount["code"]) ?></span>
                                <span class="discount-desc-tag"><?= htmlspecialchars($appliedDiscount["description"]) ?></span>
                            </div>
                            <form method="POST" action="pembayaran.php" style="margin: 0;">
                                <input type="hidden" name="discount_action" value="remove">
                                <button type="submit" class="btn-remove-discount">HAPUS</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="pembayaran.php" class="discount-input-group">
                            <input type="hidden" name="discount_action" value="apply">
                            <input type="text" name="discount_code" placeholder="Ketik kode diskon..." class="discount-input" required>
                            <button type="submit" class="btn-apply-discount">PAKAI</button>
                        </form>
                    <?php endif; ?>

                    <?php if (!empty($discountError)): ?>
                        <div class="discount-alert error">[!] <?= htmlspecialchars($discountError) ?></div>
                    <?php elseif (!empty($discountSuccess)): ?>
                        <div class="discount-alert success">[OK] <?= htmlspecialchars($discountSuccess) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="summary-bottom-actions">
                <button type="submit" form="paymentForm" class="btn-primary" style="width: 100%; text-align: center; justify-content: center;">
                    BUAT PESANAN →
                </button>
            </div>
        </div>

    </div>

</div>

</body>
</html>
