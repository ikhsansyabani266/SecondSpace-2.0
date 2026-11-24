<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "auth.php";
require_once "db.php";

/** @var mysqli $conn */

$userId = (int)($_SESSION["user_id"] ?? 0);

// ======================================================
// 1. BAGIAN POST: SIMPAN PESANAN (DATABASE TRANSACTION)
// ======================================================
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Guard Keranjang & Alamat
    if (!isset($_SESSION["cart"]) || empty($_SESSION["cart"])) {
        header("Location: keranjang.php");
        exit;
    }

    if (!isset($_SESSION["checkout_address"]) || empty($_SESSION["checkout_address"])) {
        header("Location: checkout.php");
        exit;
    }

    $paymentMethod = trim($_POST["payment_method"] ?? "Transfer Bank");
    $allowedPayment = ["Transfer Bank", "E-Wallet", "COD"];
    if (!in_array($paymentMethod, $allowedPayment, true)) {
        $paymentMethod = "Transfer Bank";
    }

    $checkout = $_SESSION["checkout_address"];
    $recipientName = trim($checkout["recipient_name"] ?? "");
    $phone         = trim($checkout["phone"] ?? "");
    $address       = trim($checkout["address"] ?? "");
    $province      = trim($checkout["province"] ?? "");
    $city          = trim($checkout["city"] ?? "");
    $postalCode    = trim($checkout["postal_code"] ?? "");

    if (empty($recipientName) || empty($phone) || empty($address) || empty($city) || empty($postalCode)) {
        header("Location: checkout.php");
        exit;
    }

    // Mulai Database Transaction
    $conn->begin_transaction();

    try {
        $subtotal = 0;
        $orderItemsData = [];

        // 1. Kurangi stok setiap produk dengan atomic check (stock >= qty)
        $stmtStockUpdate = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
        $stmtProductInfo = $conn->prepare("SELECT id, name, price, stock, size FROM products WHERE id = ? FOR UPDATE");

        foreach ($_SESSION["cart"] as $pId => $item) {
            $pId = (int)$pId;
            $qty = (int)($item["quantity"] ?? 1);
            if ($qty < 1) $qty = 1;

            if ($stmtProductInfo) {
                $stmtProductInfo->bind_param("i", $pId);
                $stmtProductInfo->execute();
                $resP = $stmtProductInfo->get_result();
                $pRow = $resP ? $resP->fetch_assoc() : null;

                if (!$pRow || (int)$pRow["stock"] < $qty) {
                    throw new Exception("Maaf, produk \"" . ($pRow['name'] ?? 'Item') . "\" stoknya sudah tidak mencukupi atau telah terjual.");
                }

                $price = (float)$pRow["price"];
                $itemSubtotal = $price * $qty;
                $subtotal += $itemSubtotal;

                $orderItemsData[] = [
                    "product_id"   => $pId,
                    "product_name" => $pRow["name"],
                    "price"        => $price,
                    "quantity"     => $qty,
                    "subtotal"     => $itemSubtotal,
                    "size"         => $pRow["size"] ?: "All Size"
                ];
            }

            if ($stmtStockUpdate) {
                $stmtStockUpdate->bind_param("iii", $qty, $pId, $qty);
                $stmtStockUpdate->execute();
                if ($stmtStockUpdate->affected_rows < 1) {
                    throw new Exception("Gagal memotong stok produk: " . ($item['name'] ?? 'Item'));
                }
            }
        }

        if ($stmtStockUpdate) $stmtStockUpdate->close();
        if ($stmtProductInfo) $stmtProductInfo->close();

        // 2. Simpan Alamat Pengiriman (Kompatibel sebelum & sesudah migration.sql)
        $hasProvinceCol = false;
        $colCheck = $conn->query("SHOW COLUMNS FROM addresses LIKE 'province'");
        if ($colCheck && $colCheck->num_rows > 0) {
            $hasProvinceCol = true;
        }

        if ($hasProvinceCol) {
            $stmtAddress = $conn->prepare("INSERT INTO addresses (user_id, recipient_name, phone, address, province, city, postal_code) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if (!$stmtAddress) throw new Exception($conn->error);
            $stmtAddress->bind_param("issssss", $userId, $recipientName, $phone, $address, $province, $city, $postalCode);
        } else {
            $stmtAddress = $conn->prepare("INSERT INTO addresses (user_id, recipient_name, phone, address, city, postal_code) VALUES (?, ?, ?, ?, ?, ?)");
            if (!$stmtAddress) throw new Exception($conn->error);
            $stmtAddress->bind_param("isssss", $userId, $recipientName, $phone, $address, $city, $postalCode);
        }
        $stmtAddress->execute();
        $addressId = (int)$conn->insert_id;
        $stmtAddress->close();

        // 3. Generate Kode Pesanan Unik
        $orderCode = "SS-" . date("Ymd") . sprintf("%04d", rand(1000, 9999)) . rand(10, 99);
        $shipping = $shippingCost ?? 15000;

        // Hitung diskon jika ada kode yang diterapkan di sesi
        $discountAmount = 0;
        $discountCode = "";
        if (isset($_SESSION["discount"]) && is_array($_SESSION["discount"])) {
            $appliedD = $_SESSION["discount"];
            $discountCode = $appliedD["code"] ?? "";
            $discountAmount = calculateDiscount($appliedD, (float)$subtotal, (float)$shipping);
        }

        $total = max(0, $subtotal + $shipping - $discountAmount);
        $initialStatus = "Menunggu Pembayaran";
        $orderNotes = ($discountAmount > 0) ? "Kupon: {$discountCode} (-" . formatRupiah($discountAmount) . ")" : "";

        // 4. Simpan ke Tabel orders (Memenuhi order_code, order_number, total, total_price, notes)
        $stmtOrder = $conn->prepare("INSERT INTO orders (order_code, order_number, user_id, address_id, subtotal, shipping, total, total_price, payment_method, payment_status, order_status, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmtOrder) throw new Exception($conn->error);
        $stmtOrder->bind_param("ssiidddssssss", $orderCode, $orderCode, $userId, $addressId, $subtotal, $shipping, $total, $total, $paymentMethod, $initialStatus, $initialStatus, $initialStatus, $orderNotes);
        $stmtOrder->execute();
        $orderId = (int)$conn->insert_id;
        $stmtOrder->close();

        // 5. Simpan Item ke Tabel order_items
        $stmtItem = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, price, quantity, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
        if (!$stmtItem) throw new Exception($conn->error);
        foreach ($orderItemsData as $oid) {
            $stmtItem->bind_param("iisdid", $orderId, $oid["product_id"], $oid["product_name"], $oid["price"], $oid["quantity"], $oid["subtotal"]);
            $stmtItem->execute();
        }
        $stmtItem->close();

        // Commit Transaksi Database
        $conn->commit();

        // Bersihkan keranjang dan sesi checkout
        $_SESSION["cart"] = [];
        unset($_SESSION["checkout_address"]);
        unset($_SESSION["discount"]);

        // Post/Redirect/Get: Mencegah duplikasi pesanan saat refresh
        header("Location: konfirmasi.php?kode=" . urlencode($orderCode));
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION["cart_notice"] = $e->getMessage();
        header("Location: keranjang.php");
        exit;
    }
}

// ======================================================
// 2. BAGIAN GET: TAMPILKAN KONFIRMASI PESANAN
// ======================================================
$orderCode = trim($_GET["kode"] ?? "");
if (empty($orderCode)) {
    header("Location: produk.php");
    exit;
}

// Ambil data pesanan milik pengguna yang sedang login
$stmtGetOrder = $conn->prepare("
    SELECT o.*, a.recipient_name, a.phone, a.address, a.province, a.city, a.postal_code
    FROM orders o
    LEFT JOIN addresses a ON o.address_id = a.id
    WHERE o.order_code = ? AND o.user_id = ?
    LIMIT 1
");

if (!$stmtGetOrder) {
    header("Location: produk.php");
    exit;
}

$stmtGetOrder->bind_param("si", $orderCode, $userId);
$stmtGetOrder->execute();
$resOrder = $stmtGetOrder->get_result();
$order = $resOrder ? $resOrder->fetch_assoc() : null;
$stmtGetOrder->close();

if (!$order) {
    // Kode tidak dikenal atau bukan milik user ini
    header("Location: produk.php");
    exit;
}

// Ambil item pesanan beserta ukuran dari tabel products
$items = [];
$stmtGetItems = $conn->prepare("
    SELECT oi.*, p.size
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
    ORDER BY oi.id ASC
");

if ($stmtGetItems) {
    $stmtGetItems->bind_param("i", $order["id"]);
    $stmtGetItems->execute();
    $resItems = $stmtGetItems->get_result();
    if ($resItems) {
        while ($rowItem = $resItems->fetch_assoc()) {
            $items[] = $rowItem;
        }
    }
    $stmtGetItems->close();
}

// Generate Format Pesan WhatsApp Sesuai Spesifikasi Persis
$itemListText = "";
foreach ($items as $idx => $it) {
    $num = $idx + 1;
    $sizeText = $it["size"] ?: "All Size";
    $priceText = formatRupiah($it["price"]);
    $itemListText .= "{$num}. {$it['product_name']} | Ukuran {$sizeText} | x{$it['quantity']} | {$priceText}\n";
}

$recipientFullAddress = trim($order["address"] . ", " . $order["city"] . ($order["province"] ? ", " . $order["province"] : "") . " " . $order["postal_code"]);

$discountVal = max(0, ((float)$order["subtotal"] + (float)$order["shipping"]) - (float)$order["total"]);
$discountText = ($discountVal > 0) ? " | Diskon: -" . formatRupiah($discountVal) : "";

$waConfirmMessage = "Halo SecondSpace, saya sudah memesan di website.\n"
    . "Kode pesanan: " . $order["order_code"] . "\n"
    . "Nama: " . $order["recipient_name"] . "\n"
    . "Pesanan:\n"
    . $itemListText
    . "Subtotal: " . formatRupiah($order["subtotal"]) . " | Ongkir: " . formatRupiah($order["shipping"]) . $discountText . " | Total: " . formatRupiah($order["total"]) . "\n"
    . "Metode pembayaran: " . $order["payment_method"] . " | Status: " . ($order["payment_status"] ?? "Menunggu Pembayaran") . "\n"
    . "Alamat: " . $recipientFullAddress . "\n"
    . "Mohon konfirmasi. Bukti pembayaran saya kirim di chat ini.";

$waConfirmUrl = waLink($whatsappNumber, $waConfirmMessage);

$cartCount = isset($_SESSION["cart"]) ? count($_SESSION["cart"]) : 0;
$isLoggedIn = true;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Berhasil — SecondSpace PreLoved</title>
    <link rel="stylesheet" href="modern-theme.css">
    <style>
        /* Steps Bar (Barlow Condensed, Streetwear) */
        .steps-nav {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin: 16px 0 32px 0;
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

        /* Header Konfirmasi */
        .confirm-header {
            text-align: center;
            margin-bottom: 36px;
        }

        .confirm-title {
            font-family: var(--display-font);
            font-size: clamp(34px, 5vw, 48px);
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: -0.01em;
            color: var(--tx);
            margin: 0 0 8px 0;
            line-height: 1;
        }

        .confirm-subtitle {
            color: var(--mu);
            font-size: 15px;
            max-width: 580px;
            margin: 0 auto;
            line-height: 1.5;
        }

        /* 2-Column Grid Layout */
        .confirm-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.15fr) minmax(0, 1fr);
            gap: 32px;
            align-items: stretch;
            margin-bottom: 60px;
        }

        /* Left Box: Rincian Pesanan */
        .order-detail-card {
            background: var(--s1);
            border: 1px solid var(--ln);
            border-radius: 4px;
            padding: 32px;
            display: flex;
            flex-direction: column;
        }

        .order-code-display {
            font-family: var(--display-font);
            font-size: 34px;
            font-weight: 800;
            color: var(--ac);
            letter-spacing: 0.05em;
            margin: 0 0 10px 0;
            line-height: 1;
        }

        .status-badge {
            display: inline-block;
            background: #252E22;
            color: var(--ac);
            border: 1px solid var(--ac);
            border-radius: 3px;
            padding: 4px 12px;
            font-family: var(--display-font);
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 24px;
            align-self: flex-start;
        }

        .section-label {
            font-family: var(--display-font);
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--mu);
            margin: 18px 0 10px 0;
            border-bottom: 1px solid var(--ln);
            padding-bottom: 6px;
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            font-size: 14px;
        }

        .item-row-left {
            color: var(--tx);
            font-weight: 600;
        }

        .item-row-right {
            color: var(--tx);
            font-family: var(--body-font);
        }

        .summary-line {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 14px;
            color: var(--mu);
        }

        .summary-line.total-line {
            border-top: 1px solid var(--ln);
            padding-top: 14px;
            margin-top: 10px;
            font-size: 20px;
            font-weight: 800;
            font-family: var(--display-font);
            letter-spacing: 0.02em;
            color: var(--tx);
        }

        .address-box {
            color: var(--tx);
            font-size: 14px;
            line-height: 1.6;
        }

        /* Right Box: Langkah Selanjutnya */
        .next-steps-card {
            background: var(--s1);
            border: 1px solid var(--ln);
            border-radius: 4px;
            padding: 32px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .next-title {
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

        .steps-list {
            list-style: none;
            padding: 0;
            margin: 0 0 24px 0;
        }

        .steps-list li {
            display: flex;
            gap: 14px;
            align-items: flex-start;
            margin-bottom: 16px;
            font-size: 14px;
            line-height: 1.5;
            color: var(--tx);
        }

        .step-circle {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: var(--s2);
            border: 1px solid var(--ac);
            color: var(--ac);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-family: var(--display-font);
            font-size: 14px;
            font-weight: 800;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .btn-wa-confirm {
            width: 100%;
            height: 48px;
            background: var(--ac);
            color: #0A0A0A;
            border: 1px solid var(--ac);
            border-radius: 4px;
            font-family: var(--display-font);
            font-size: 17px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-bottom: 18px;
        }

        .btn-wa-confirm:hover {
            background: var(--ach);
            border-color: var(--ach);
        }

        .msg-preview-box {
            background: var(--bg);
            border: 1px solid var(--ln);
            border-left: 3px solid var(--ac);
            border-radius: 4px;
            padding: 16px 18px;
            font-size: 12px;
            color: var(--mu);
            line-height: 1.6;
            white-space: pre-line;
            font-family: monospace;
            margin-bottom: 24px;
            max-height: 200px;
            overflow-y: auto;
        }

        .bottom-nav-links {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding-top: 14px;
            border-top: 1px solid var(--ln);
        }

        .bottom-nav-links a {
            color: var(--mu);
            text-decoration: none;
            font-family: var(--display-font);
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            transition: color 0.15s ease;
        }

        .bottom-nav-links a:hover {
            color: var(--tx);
        }

        @media (max-width: 860px) {
            .confirm-layout {
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
        <div class="step-pill done">
            <span>✓</span>
            <span>1 ALAMAT</span>
        </div>
        <span class="step-divider">——</span>
        <div class="step-pill done">
            <span>✓</span>
            <span>2 PEMBAYARAN</span>
        </div>
        <span class="step-divider">——</span>
        <div class="step-pill active">
            <span>3</span>
            <span>KONFIRMASI</span>
        </div>
    </div>

    <!-- HEADER TITLE & SUBTITLE -->
    <div class="confirm-header">
        <h1 class="confirm-title">Pesanan berhasil dibuat</h1>
        <p class="confirm-subtitle">Simpan kode pesananmu. Lakukan pembayaran, lalu konfirmasi lewat WhatsApp.</p>
    </div>

    <div class="confirm-layout">

        <!-- KOTAK KIRI: DETAIL PESANAN -->
        <div class="order-detail-card">
            <div class="order-code-display"><?= htmlspecialchars($order["order_code"]) ?></div>
            <div class="status-badge"><?= htmlspecialchars(strtoupper($order["order_status"] ?? "MENUNGGU PEMBAYARAN")) ?></div>

            <div class="section-label">ITEM PESANAN</div>
            <?php foreach ($items as $it): ?>
                <div class="item-row">
                    <span class="item-row-left">
                        <?= htmlspecialchars($it["product_name"]) ?> · <?= htmlspecialchars($it["size"] ?: "All Size") ?> · x<?= $it["quantity"] ?>
                    </span>
                    <span class="item-row-right">
                        <?= formatRupiah($it["subtotal"]) ?>
                    </span>
                </div>
            <?php endforeach; ?>

            <div class="section-label">RINCIAN BIAYA</div>
            <div class="summary-line">
                <span>Subtotal Produk</span>
                <span><?= formatRupiah($order["subtotal"]) ?></span>
            </div>
            <div class="summary-line">
                <span>Ongkos Kirim (Flat)</span>
                <span><?= formatRupiah($order["shipping"]) ?></span>
            </div>
            <?php if ($discountVal > 0): ?>
                <div class="summary-line" style="color: var(--ac); font-weight: 700;">
                    <span>Diskon</span>
                    <span>-<?= formatRupiah($discountVal) ?></span>
                </div>
            <?php endif; ?>
            <div class="summary-line total-line">
                <span>Total Pembayaran</span>
                <span style="color: var(--ac);"><?= formatRupiah($order["total"]) ?></span>
            </div>

            <div class="section-label">METODE PEMBAYARAN</div>
            <div style="font-weight: 700; color: var(--tx); font-size: 15px;">
                <?= htmlspecialchars($order["payment_method"]) ?>
            </div>

            <div class="section-label">DIKIRIM KE</div>
            <div class="address-box">
                <strong><?= htmlspecialchars($order["recipient_name"]) ?></strong> (<?= htmlspecialchars($order["phone"]) ?>)<br>
                <?= htmlspecialchars($order["address"]) ?><br>
                <?= htmlspecialchars($order["city"]) ?><?= $order["province"] ? ", " . htmlspecialchars($order["province"]) : "" ?> <?= htmlspecialchars($order["postal_code"]) ?>
            </div>
        </div>

        <!-- KOTAK KANAN: LANGKAH SELANJUTNYA -->
        <div class="next-steps-card">
            <div>
                <h2 class="next-title">Langkah Selanjutnya</h2>

                <ul class="steps-list">
                    <li>
                        <span class="step-circle">1</span>
                        <div>
                            <?php if ($order["payment_method"] === "COD"): ?>
                                Siapkan uang tunai pas sebesar <strong><?= formatRupiah($order["total"]) ?></strong> saat kurir mengantarkan barang.
                            <?php elseif ($order["payment_method"] === "E-Wallet"): ?>
                                Transfer sebesar <strong><?= formatRupiah($order["total"]) ?></strong> ke e-wallet <strong><?= htmlspecialchars($ewalletName) ?> (<?= htmlspecialchars($ewalletNumber) ?> a/n <?= htmlspecialchars($ewalletAccountName) ?>)</strong>.
                            <?php else: ?>
                                Transfer sebesar <strong><?= formatRupiah($order["total"]) ?></strong> ke rekening <strong><?= htmlspecialchars($bankName) ?> (<?= htmlspecialchars($bankAccountNumber) ?> a/n <?= htmlspecialchars($bankAccountName) ?>)</strong>.
                            <?php endif; ?>
                        </div>
                    </li>
                    <li>
                        <span class="step-circle">2</span>
                        <div>
                            Tekan tombol konfirmasi di bawah ini untuk membuka WhatsApp dengan pesan yang sudah terisi otomatis.
                        </div>
                    </li>
                    <li>
                        <span class="step-circle">3</span>
                        <div>
                            Kirim pesan tersebut serta lampirkan bukti pembayaran (foto struk / screenshot transaksi) di ruang obrolan WhatsApp.
                        </div>
                    </li>
                </ul>

                <!-- BIG BUTTON KONFIRMASI VIA WHATSAPP -->
                <a href="<?= $waConfirmUrl ?>" target="_blank" rel="noopener" class="btn-wa-confirm">
                    💬 KONFIRMASI VIA WHATSAPP
                </a>

                <!-- PREVIEW BOX PESAN WHATSAPP -->
                <div style="font-size: 12px; color: var(--mu); margin-bottom: 6px; font-weight: 600;">Pesan yang dikirim otomatis:</div>
                <div class="msg-preview-box"><?= htmlspecialchars($waConfirmMessage) ?></div>
            </div>

            <div class="bottom-nav-links">
                <a href="produk.php">← Belanja lagi</a>
                <a href="pesanan.php">Cek pesanan →</a>
            </div>
        </div>

    </div>

</div>

</body>
</html>
