<?php
/**
 * [FILE]: user/keranjang.php
 * [BAGIAN]: Keranjang Belanja Pelanggan
 * [DESKRIPSI]: Mengelola item belanja dalam session, sinkronisasi stok live database, dan hitung subtotal
 * [JANGAN UBAH]: Mekanisme verifikasi stok live agar pesanan tidak melebihi stok yang ada
 * [UBAH DI SINI]: Batasan jumlah pembelian atau pesan peringatan stok
 * [TAMBAH DI SINI]: Input kode voucher diskon / kupon belanja
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../shared/koneksi.php';
require_once __DIR__ . '/../shared/fungsi.php';

/** @var mysqli $conn */

if (!isset($_SESSION["cart"])) {
    $_SESSION["cart"] = [];
}

/* [AKSI]: Handler Aksi Keranjang (Pure PHP - No JavaScript) */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!csrf_verify()) {
        $_SESSION["cart_notice"] = "Sesi keamanan form telah berakhir (CSRF). Silakan coba lagi.";
        header("Location: keranjang.php");
        exit;
    }
    $action = $_POST["action"] ?? "";

    if ($action === "add") {
        $product_id = intval($_POST["product_id"] ?? 0);
        $quantity = intval($_POST["quantity"] ?? 1);
        if ($quantity < 1) $quantity = 1;

        if ($product_id > 0) {
            $stmt = $conn->prepare("SELECT id, name, price, image, stock, size FROM products WHERE id = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("i", $product_id);
                $stmt->execute();
                $res = $stmt->get_result();
                $product = $res ? $res->fetch_assoc() : null;
                $stmt->close();

                if ($product && (int)$product["stock"] > 0) {
                    $stock = (int)$product["stock"];
                    $currentInCart = isset($_SESSION["cart"][$product_id]) ? (int)$_SESSION["cart"][$product_id]["quantity"] : 0;
                    
                    if ($currentInCart >= $stock) {
                        $_SESSION["cart_notice"] = "Produk ini hanya tersedia {$stock} buah (one of one) dan sudah ada di keranjangmu.";
                        header("Location: detail.php?id=" . $product_id);
                        exit;
                    }
                    
                    $newQty = $currentInCart + $quantity;
                    if ($newQty > $stock) {
                        $newQty = $stock;
                        $_SESSION["cart_notice"] = "Jumlah dibatasi sesuai stok tersedia ({$stock} buah).";
                    }

                    $_SESSION["cart"][$product_id] = [
                        "id"       => (int)$product["id"],
                        "name"     => $product["name"],
                        "price"    => (float)$product["price"],
                        "image"    => $product["image"],
                        "size"     => $product["size"] ?? "All Size",
                        "quantity" => $newQty,
                        "stock"    => $stock
                    ];
                } else {
                    $_SESSION["cart_notice"] = "Maaf, produk ini sudah terjual.";
                    header("Location: detail.php?id=" . $product_id);
                    exit;
                }
            }
        }
        header("Location: keranjang.php");
        exit;
    }

    if ($action === "step") {
        $product_id = intval($_POST["product_id"] ?? 0);
        $delta = intval($_POST["delta"] ?? 0);

        if (isset($_SESSION["cart"][$product_id])) {
            $current = (int)$_SESSION["cart"][$product_id]["quantity"];
            $new = $current + $delta;

            if ($new <= 0) {
                unset($_SESSION["cart"][$product_id]);
            } else {
                // Verifikasi stok live
                $st = $conn->prepare("SELECT stock FROM products WHERE id = ? LIMIT 1");
                if ($st) {
                    $st->bind_param("i", $product_id);
                    $st->execute();
                    $r = $st->get_result();
                    $pLive = $r ? $r->fetch_assoc() : null;
                    $st->close();
                    $liveStock = $pLive ? (int)$pLive["stock"] : 0;
                    if ($new > $liveStock) $new = $liveStock;
                }
                $_SESSION["cart"][$product_id]["quantity"] = $new;
            }
        }
        header("Location: keranjang.php");
        exit;
    }

    if ($action === "delete") {
        $product_id = intval($_POST["product_id"] ?? 0);
        if (isset($_SESSION["cart"][$product_id])) {
            unset($_SESSION["cart"][$product_id]);
        }
        header("Location: keranjang.php");
        exit;
    }
}

// Verifikasi dan sinkronisasi live data dengan database
$cartItems = [];
$hasStockIssue = false;
$subtotal = 0;

if (!empty($_SESSION["cart"])) {
    foreach ($_SESSION["cart"] as $pId => $item) {
        $pId = (int)$pId;
        $stmt = $conn->prepare("SELECT id, name, price, image, stock, size FROM products WHERE id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("i", $pId);
            $stmt->execute();
            $res = $stmt->get_result();
            $prod = $res ? $res->fetch_assoc() : null;
            $stmt->close();

            if ($prod) {
                $liveStock = (int)$prod["stock"];
                $qty = (int)$item["quantity"];
                $isSold = ($liveStock <= 0);
                $exceeds = ($qty > $liveStock);

                if ($isSold || $exceeds) {
                    $hasStockIssue = true;
                }

                // Perbarui session dengan harga dan stok live terbaru
                $_SESSION["cart"][$pId]["price"] = (float)$prod["price"];
                $_SESSION["cart"][$pId]["stock"] = $liveStock;
                $_SESSION["cart"][$pId]["name"]  = $prod["name"];
                $_SESSION["cart"][$pId]["image"] = $prod["image"];
                $_SESSION["cart"][$pId]["size"]  = $prod["size"] ?: "All Size";

                $cartItems[] = [
                    "id"       => $pId,
                    "name"     => $prod["name"],
                    "price"    => (float)$prod["price"],
                    "image"    => $prod["image"],
                    "size"     => $prod["size"] ?: "All Size",
                    "quantity" => $qty,
                    "stock"    => $liveStock,
                    "is_sold"  => $isSold,
                    "exceeds"  => $exceeds
                ];
                $subtotal += ((float)$prod["price"] * $qty);
            } else {
                unset($_SESSION["cart"][$pId]);
            }
        }
    }
}

$total = $subtotal;
$cartCount = count($cartItems);
$isLoggedIn = isset($_SESSION["user_id"]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja — SecondSpace PreLoved</title>
    <link rel="stylesheet" href="modern-theme.css">
    <style>
        .cart-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 340px;
            gap: 32px;
            align-items: stretch;
            margin-top: 24px;
        }

        .cart-table-wrapper {
            background: var(--s1);
            border: 1px solid var(--ln);
            border-radius: 4px;
            padding: 28px;
            display: flex;
            flex-direction: column;
        }

        .cart-header-title {
            font-family: var(--display-font);
            font-size: 32px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: -0.01em;
            color: var(--tx);
            margin: 0 0 24px 0;
            border-bottom: 1px solid var(--ln);
            padding-bottom: 16px;
        }

        .cart-item {
            display: grid;
            grid-template-columns: 80px 1fr auto auto;
            gap: 20px;
            align-items: center;
            padding: 20px 0;
            border-bottom: 1px solid var(--ln);
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .cart-item.has-issue {
            background: rgba(185, 28, 28, 0.08);
            border-radius: 4px;
            padding: 16px 14px;
            margin: 6px 0;
            border: 1px solid var(--alert);
        }

        .item-thumb {
            width: 80px;
            aspect-ratio: 4 / 5;
            background: #EDEDED;
            border-radius: 3px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .item-thumb img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            mix-blend-mode: multiply;
            padding: 4px;
        }

        .item-thumb-watermark {
            font-family: var(--display-font);
            font-size: 13px;
            font-weight: 800;
            color: #999999;
            text-transform: uppercase;
        }

        .item-thumb-sold {
            position: absolute;
            inset: 0;
            background: rgba(10, 10, 10, 0.85);
            color: #EDEDED;
            font-family: var(--display-font);
            font-size: 14px;
            font-weight: 900;
            display: flex;
            align-items: center;
            justify-content: center;
            letter-spacing: 0.1em;
        }

        .item-info .item-name {
            font-family: var(--display-font);
            font-size: 19px;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--tx);
            text-decoration: none;
            display: block;
            margin-bottom: 4px;
            line-height: 1.15;
        }

        .item-info .item-name:hover {
            color: var(--ac);
        }

        .item-info .item-meta {
            color: var(--mu);
            font-size: 13px;
        }

        .item-issue-badge {
            display: inline-block;
            background: rgba(185, 28, 28, 0.25);
            color: #F87171;
            border: 1px solid var(--alert);
            border-radius: 3px;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 8px;
            margin-top: 6px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Stepper Form Buttons (Pure HTML - No JS) */
        .stepper-form {
            display: inline-flex;
            align-items: center;
            background: var(--bg);
            border: 1px solid var(--ln);
            border-radius: 4px;
            overflow: hidden;
        }

        .btn-qty-step {
            width: 36px;
            height: 38px;
            background: transparent;
            color: var(--tx);
            border: none;
            font-family: var(--display-font);
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.15s ease;
        }

        .btn-qty-step:hover:not(:disabled) {
            background: var(--s2);
            color: var(--ac);
        }

        .btn-qty-step:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }

        .qty-val-display {
            min-width: 32px;
            text-align: center;
            font-family: var(--display-font);
            font-size: 16px;
            font-weight: 700;
            color: var(--tx);
            user-select: none;
        }

        .item-price-col {
            text-align: right;
            min-width: 120px;
        }

        .item-price-val {
            font-family: var(--body-font);
            font-size: 16px;
            font-weight: 700;
            color: var(--tx);
            margin-bottom: 6px;
        }

        .btn-hapus-item {
            background: transparent;
            border: none;
            color: var(--mu);
            font-family: var(--display-font);
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            cursor: pointer;
            padding: 4px 0;
            transition: color 0.15s ease;
        }

        .btn-hapus-item:hover {
            color: var(--alert);
        }

        /* Summary Box (Equal Height Stretched) */
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
            margin: 0 0 24px 0;
            border-bottom: 1px solid var(--ln);
            padding-bottom: 16px;
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
            padding-top: 18px;
            margin-top: 20px;
            font-size: 20px;
            font-weight: 800;
            font-family: var(--display-font);
            letter-spacing: 0.02em;
            color: var(--tx);
        }

        .summary-bottom-actions {
            margin-top: 28px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .guest-notice {
            background: rgba(124, 154, 109, 0.12);
            border: 1px solid var(--ac);
            color: var(--tx);
            padding: 10px 14px;
            border-radius: 4px;
            font-size: 13px;
            text-align: center;
            line-height: 1.4;
        }

        .link-continue-shopping {
            display: block;
            text-align: center;
            color: var(--mu);
            text-decoration: none;
            font-family: var(--display-font);
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            padding: 6px 0;
            transition: color 0.15s ease;
        }

        .link-continue-shopping:hover {
            color: var(--tx);
        }

        /* Redesigned Streetwear Empty State */
        .empty-cart-card {
            background: var(--s1);
            border: 1px solid var(--ln);
            border-radius: 4px;
            padding: 60px 32px;
            text-align: center;
            max-width: 680px;
            margin: 40px auto 60px;
            position: relative;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.4);
        }

        .empty-cart-icon-wrapper {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: #111111;
            border: 1px solid var(--ln);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--ac);
            position: relative;
        }

        .empty-cart-icon-wrapper::after {
            content: '';
            position: absolute;
            top: -3px;
            right: -3px;
            width: 10px;
            height: 10px;
            background: var(--ac);
            border-radius: 50%;
            border: 2px solid var(--s1);
        }

        .empty-cart-tag {
            display: inline-block;
            font-family: var(--display-font);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--mu);
            background: #0D0D0D;
            border: 1px solid #222222;
            padding: 4px 12px;
            border-radius: 999px;
            margin-bottom: 16px;
        }

        .empty-cart-title {
            font-family: var(--display-font);
            font-size: 34px;
            font-weight: 800;
            text-transform: uppercase;
            color: var(--tx);
            margin: 0 0 24px 0;
            letter-spacing: 0.03em;
            line-height: 1.1;
        }

        .empty-cart-text {
            color: var(--mu);
            font-size: 15px;
            line-height: 1.6;
            max-width: 460px;
            margin: 0 auto 28px;
        }

        .empty-cart-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: var(--ac);
            color: #0A0A0A !important;
            font-family: var(--display-font);
            font-size: 16px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            text-decoration: none !important;
            padding: 14px 32px;
            border-radius: 4px;
            min-height: 48px;
            transition: all 0.2s ease;
            box-shadow: 0 6px 20px rgba(124, 154, 109, 0.25);
        }

        .empty-cart-action:hover {
            background: var(--ach);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(124, 154, 109, 0.35);
        }

        /* Quick Category Discovery Pills */
        .empty-cart-categories {
            margin-top: 36px;
            padding-top: 28px;
            border-top: 1px solid #1C1C1C;
        }

        .empty-cat-label {
            font-family: var(--display-font);
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--mu);
            margin-bottom: 14px;
            display: block;
        }

        .empty-cat-pills {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .empty-cat-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            background: #111111;
            border: 1px solid var(--ln);
            border-radius: 4px;
            color: var(--tx) !important;
            text-decoration: none !important;
            font-family: var(--display-font);
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            transition: all 0.2s ease;
        }

        .empty-cat-pill:hover {
            border-color: var(--ac);
            background: var(--s2);
            color: var(--ac) !important;
            transform: translateY(-1px);
        }

        /* Streetwear Perks row */
        .empty-perks-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #1C1C1C;
            text-align: left;
        }

        .empty-perk-item {
            background: #101010;
            border: 1px solid #1F1F1F;
            border-radius: 4px;
            padding: 12px 14px;
        }

        .empty-perk-title {
            font-family: var(--display-font);
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.05em;
            color: var(--tx);
            text-transform: uppercase;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .empty-perk-desc {
            font-size: 11px;
            color: var(--mu);
            line-height: 1.4;
            margin: 0;
        }

        @media (max-width: 640px) {
            .empty-cart-card {
                padding: 40px 18px;
            }
            .empty-cart-title {
                font-size: 26px;
            }
            .empty-perks-row {
                grid-template-columns: 1fr;
                gap: 10px;
            }
        }

        @media (max-width: 860px) {
            .cart-layout {
                grid-template-columns: 1fr;
            }
            .cart-item {
                grid-template-columns: 70px 1fr;
                gap: 14px;
            }
            .stepper-form {
                grid-column: 1 / -1;
                margin-top: 8px;
            }
            .item-price-col {
                grid-column: 1 / -1;
                text-align: left;
                display: flex;
                justify-content: space-between;
                align-items: center;
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
        <a href="keranjang.php" class="active">
            KERANJANG
            <?php if ($cartCount > 0): ?>
                <span class="cart-badge"><?= $cartCount ?></span>
            <?php endif; ?>
        </a>
        <a href="pesanan.php" class="nav-pesanan">PESANAN</a>
        <?php if ($isLoggedIn): ?>
            <a href="logout.php" class="logout">KELUAR</a>
        <?php else: ?>
            <a href="login.php" style="color: var(--ac);">MASUK</a>
        <?php endif; ?>
    </div>
</nav>

<div class="container">

    <?php if (!empty($cartItems)): ?>
        <div class="cart-layout">

            <!-- DAFTAR ITEM (KOLOM KIRI) -->
            <div class="cart-table-wrapper">
                <h1 class="cart-header-title">Keranjang Belanja (<?= $cartCount ?> Item)</h1>

                <?php foreach ($cartItems as $item): ?>
                    <div class="cart-item <?= ($item["is_sold"] || $item["exceeds"]) ? 'has-issue' : '' ?>">
                        <div class="item-thumb">
                            <?php $cartImgUrl = gambar_produk_url($item["image"] ?? '', 'user'); ?>
                            <?php if (!empty($cartImgUrl)): ?>
                                <img src="<?= htmlspecialchars($cartImgUrl) ?>" alt="<?= htmlspecialchars($item["name"]) ?>">
                            <?php else: ?>
                                <span class="item-thumb-watermark">ITEM</span>
                            <?php endif; ?>

                            <?php if ($item["is_sold"]): ?>
                                <div class="item-thumb-sold">SOLD</div>
                            <?php endif; ?>
                        </div>

                        <div class="item-info">
                            <a href="detail.php?id=<?= $item['id'] ?>" class="item-name"><?= htmlspecialchars($item["name"]) ?></a>
                            <div class="item-meta">
                                Ukuran: <?= htmlspecialchars($item["size"]) ?> · <?= formatRupiah($item["price"]) ?>
                            </div>

                            <?php if ($item["is_sold"]): ?>
                                <span class="item-issue-badge">[SOLD] Produk ini sudah terjual</span>
                            <?php elseif ($item["exceeds"]): ?>
                                <span class="item-issue-badge">[Stok Terbatas] Stok tersisa hanya <?= $item["stock"] ?> buah</span>
                            <?php endif; ?>
                        </div>

                        <!-- Kuantitas Produk Preloved (1-of-1 Piece, Tetap 1) -->
                        <div class="stepper-form" style="display: flex; align-items: center; justify-content: center; width: 44px; height: 38px; border: 1px solid var(--ln); border-radius: 4px; background: var(--s2);">
                            <span class="qty-val-display" style="font-weight: 700; color: var(--tx);"><?= $item["quantity"] ?></span>
                        </div>

                        <div class="item-price-col">
                            <div class="item-price-val">
                                <?= formatRupiah($item["price"] * $item["quantity"]) ?>
                            </div>
                            <form method="POST" style="margin: 0;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="product_id" value="<?= $item["id"] ?>">
                                <button type="submit" class="btn-hapus-item">HAPUS</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- SUMMARY BOX (KOLOM KANAN - EQUAL HEIGHT) -->
            <div class="summary-card">
                <div class="summary-top-content">
                    <h2 class="summary-title">Ringkasan</h2>

                    <div class="summary-row">
                        <span>Subtotal (<?= $cartCount ?> produk)</span>
                        <span><?= formatRupiah($subtotal) ?></span>
                    </div>

                    <div class="summary-row total-row">
                        <span>Total</span>
                        <span style="color: var(--ac);"><?= formatRupiah($total) ?></span>
                    </div>

                    <?php if ($hasStockIssue): ?>
                        <div style="background: rgba(185, 28, 28, 0.15); border: 1px solid var(--alert); color: #FCA5A5; padding: 12px; border-radius: 4px; font-size: 13px; margin-top: 18px; line-height: 1.4;">
                            Perhatian: Beberapa item di keranjangmu stoknya habis atau berkurang. Hapus atau sesuaikan item bertanda peringatan untuk melanjutkan checkout.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- BOTTOM ACTIONS -->
                <div class="summary-bottom-actions">
                    <?php if ($hasStockIssue): ?>
                        <button class="btn-disabled-action" disabled style="width: 100%; text-align: center;">CHECKOUT</button>
                    <?php elseif ($isLoggedIn): ?>
                        <a href="checkout.php" class="btn-primary" style="width: 100%; text-align: center; justify-content: center;">CHECKOUT</a>
                    <?php else: ?>
                        <div class="guest-notice">Login atau buat akun untuk checkout.</div>
                        <a href="login.php?redirect=checkout.php" class="btn-primary" style="width: 100%; text-align: center; justify-content: center;">CHECKOUT</a>
                    <?php endif; ?>

                    <a href="produk.php" class="link-continue-shopping">Lanjut belanja</a>
                </div>
            </div>

        </div>
    <?php else: ?>
        <!-- REDESIGNED STREETWEAR EMPTY STATE -->
        <div class="empty-cart-card">
            <h1 class="empty-cart-title" style="margin-top: 0;">KERANJANG BELANJA MASIH KOSONG</h1>

            <a href="produk.php" class="empty-cart-action">
                LIHAT KOLEKSI PRELOVED &rarr;
            </a>

            <!-- Quick Category Discovery Pills -->
            <div class="empty-cart-categories">
                <span class="empty-cat-label">Jelajahi Kategori Pilihan:</span>
                <div class="empty-cat-pills">
                    <a href="produk.php?kategori=Baju" class="empty-cat-pill">
                        BAJU &amp; KAOS
                    </a>
                    <a href="produk.php?kategori=Kemeja" class="empty-cat-pill">
                        KEMEJA
                    </a>
                    <a href="produk.php?kategori=Celana" class="empty-cat-pill">
                        CELANA
                    </a>
                    <a href="produk.php?kategori=Jacket" class="empty-cat-pill">
                        JACKET &amp; HOODIE
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

</body>
</html>
