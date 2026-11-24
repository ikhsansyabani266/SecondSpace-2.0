<?php
/**
 * [FILE]: user/checkout.php
 * [BAGIAN]: Form Checkout Pengiriman & Konfirmasi Pesanan
 * [DESKRIPSI]: Mengisi alamat pengiriman, opsi kurir ekspedisi, dan membuat rekaman pesanan (orders + order_items)
 * [JANGAN UBAH]: Transaksi database atomik saat pembuatan pesanan dan pengurangan stok
 * [UBAH DI SINI]: Daftar ekspedisi pengiriman default atau tarif ongkir flat
 * [TAMBAH DI SINI]: Opsi asuransi pengiriman atau catatan instruksi khusus kurir
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "auth.php";
require_once __DIR__ . '/../shared/koneksi.php';
require_once __DIR__ . '/../shared/fungsi.php';

/** @var mysqli $conn */

/* [KEAMANAN]: Guard Keranjang Kosong */
if (!isset($_SESSION["cart"]) || empty($_SESSION["cart"])) {
    header("Location: keranjang.php");
    exit;
}

$userId = (int)($_SESSION["user_id"] ?? 0);
$error = "";

// Ambil data alamat tersimpan sebelumnya jika ada
$lastAddress = null;
if (isset($_SESSION["checkout_address"])) {
    $lastAddress = $_SESSION["checkout_address"];
} else {
    $stmtAddr = $conn->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    if ($stmtAddr) {
        $stmtAddr->bind_param("i", $userId);
        $stmtAddr->execute();
        $resAddr = $stmtAddr->get_result();
        if ($resAddr) $lastAddress = $resAddr->fetch_assoc();
        $stmtAddr->close();
    }
}

// Nilai awal field input (retains typed values or prefill)
$recipientName = trim($_POST["recipient_name"] ?? ($lastAddress["recipient_name"] ?? ($_SESSION["name"] ?? "")));
$phone         = trim($_POST["phone"] ?? ($lastAddress["phone"] ?? ""));
$address       = trim($_POST["address"] ?? ($lastAddress["address"] ?? ""));
$province      = trim($_POST["province"] ?? ($lastAddress["province"] ?? ""));
$city          = trim($_POST["city"] ?? ($lastAddress["city"] ?? ""));
$postalCode    = trim($_POST["postal_code"] ?? ($lastAddress["postal_code"] ?? ""));

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

// Validasi Form Server-side Alamat Pengiriman
if ($_SERVER["REQUEST_METHOD"] === "POST" && !isset($_POST["discount_action"])) {
    $cleanPhone = preg_replace('/[^0-9]/', '', $phone);

    if (empty($recipientName) || empty($phone) || empty($address) || empty($province) || empty($city) || empty($postalCode)) {
        $error = "Semua field alamat pengiriman wajib diisi lengkap.";
    } elseif (strlen($cleanPhone) < 9 || strlen($cleanPhone) > 15) {
        $error = "Nomor handphone tidak valid (harus 9 hingga 15 digit angka).";
    } elseif (!preg_match('/^[0-9]{5}$/', $postalCode)) {
        $error = "Kode pos harus terdiri dari 5 digit angka.";
    } else {
        // Simpan data alamat ke session untuk langkah pembayaran
        $_SESSION["checkout_address"] = [
            "recipient_name" => $recipientName,
            "phone"          => $cleanPhone,
            "address"        => $address,
            "province"       => $province,
            "city"           => $city,
            "postal_code"    => $postalCode
        ];

        header("Location: pembayaran.php");
        exit;
    }
}

// Sinkronisasi data item keranjang langsung dari database
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
    <title>Alamat Pengiriman — SecondSpace PreLoved</title>
    <link rel="stylesheet" href="modern-theme.css">
    <style>
        /* Steps Bar (Barlow Condensed, Minimalist Streetwear) */
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

        .alert-error {
            background: rgba(185, 28, 28, 0.15);
            border: 1px solid var(--alert);
            color: #FCA5A5;
            padding: 12px 16px;
            border-radius: 4px;
            font-size: 13px;
            margin-bottom: 24px;
            line-height: 1.4;
        }

        .form-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: var(--tx);
            font-family: var(--display-font);
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            background: var(--bg);
            border: 1px solid var(--ln);
            border-radius: 4px;
            color: var(--tx);
            padding: 10px 14px;
            font-size: 14px;
            font-family: var(--body-font);
            outline: none;
            transition: border-color 0.2s ease;
        }

        .form-group input {
            height: 44px;
        }

        .form-group textarea {
            min-height: 90px;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: var(--ac);
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

        .summary-divider {
            height: 1px;
            background: var(--ln);
            margin: 16px 0;
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
            margin-top: 18px;
            font-size: 20px;
            font-weight: 800;
            font-family: var(--display-font);
            letter-spacing: 0.02em;
            color: var(--tx);
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

        .summary-bottom-actions {
            margin-top: 28px;
        }

        @media (max-width: 860px) {
            .checkout-layout {
                grid-template-columns: 1fr;
            }
            .form-grid-2 {
                grid-template-columns: 1fr;
                gap: 0;
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
        <div class="step-pill active">
            <span>1</span>
            <span>ALAMAT</span>
        </div>
        <span class="step-divider">——</span>
        <div class="step-pill">
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

        <!-- FORM ALAMAT (KOLOM KIRI) -->
        <div class="checkout-box">
            <h1 class="checkout-title">Alamat Pengiriman</h1>

            <?php if (!empty($error)): ?>
                <div class="alert-error">
                    [Perhatian] <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="addressForm">
                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="recipient_name">Nama Penerima</label>
                        <input type="text" id="recipient_name" name="recipient_name" value="<?= htmlspecialchars($recipientName) ?>" placeholder="Contoh: Arwah Satria" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">No. Handphone (WhatsApp)</label>
                        <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($phone) ?>" placeholder="Contoh: 081234567890" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="address">Alamat Lengkap</label>
                    <textarea id="address" name="address" placeholder="Nama jalan, nomor rumah, RT/RW, kelurahan/kecamatan" required><?= htmlspecialchars($address) ?></textarea>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="province">Provinsi</label>
                        <input type="text" id="province" name="province" value="<?= htmlspecialchars($province) ?>" placeholder="Contoh: DKI Jakarta" required>
                    </div>

                    <div class="form-group">
                        <label for="city">Kota / Kabupaten</label>
                        <input type="text" id="city" name="city" value="<?= htmlspecialchars($city) ?>" placeholder="Contoh: Jakarta Selatan" required>
                    </div>
                </div>

                <div class="form-group" style="max-width: 240px;">
                    <label for="postal_code">Kode Pos</label>
                    <input type="text" id="postal_code" name="postal_code" value="<?= htmlspecialchars($postalCode) ?>" placeholder="5 digit angka" maxlength="5" pattern="[0-9]{5}" required>
                </div>
            </form>
        </div>

        <!-- RINGKASAN PESANAN (KOLOM KANAN - EQUAL HEIGHT) -->
        <div class="summary-card">
            <div class="summary-top-content">
                <h2 class="summary-title">Ringkasan Pesanan</h2>

                <!-- DAFTAR PRODUK (THUMBNAIL, NAMA, SIZE, JUMLAH, SUBTOTAL) -->
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
                            <form method="POST" action="checkout.php" style="margin: 0;">
                                <input type="hidden" name="discount_action" value="remove">
                                <button type="submit" class="btn-remove-discount">HAPUS</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="checkout.php" class="discount-input-group">
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
                <button type="submit" form="addressForm" class="btn-primary" style="width: 100%; text-align: center; justify-content: center;">
                    LANJUT KE PEMBAYARAN →
                </button>
            </div>
        </div>

    </div>

</div>

</body>
</html>
