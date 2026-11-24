<?php
/**
 * [FILE]: user/detail.php
 * [BAGIAN]: Detail Produk & Galeri Foto
 * [DESKRIPSI]: Menampilkan foto produk (depan, belakang, detail), ukuran, kondisi, rincian deskripsi, dan tombol tambah ke keranjang
 * [JANGAN UBAH]: Mekanisme galeri switcher tanpa JS via query parameter `?foto=N` dan validasi live stok
 * [UBAH DI SINI]: Layout spesifikasi produk atau copywriting tombol CTA
 * [TAMBAH DI SINI]: Rekomendasi produk serupa (cross-selling)
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../shared/koneksi.php';
require_once __DIR__ . '/../shared/fungsi.php';

/** @var mysqli $conn */

/* [BAGIAN]: Ambil ID Produk & Validasi */
$id = intval($_GET["id"] ?? 0);

if ($id <= 0) {
    header("Location: produk.php");
    exit;
}

/* [QUERY]: Ambil Data Produk Terpilih */
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");

if ($stmt) {
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $product = $result->fetch_assoc();
    }
}

if (!$product) {
    header("Location: produk.php");
    exit;
}

$stock = (int) ($product["stock"] ?? 0);
$isAvailable = $stock > 0;

// Galeri Gambar Produk (Multi-Photo: Depan, Belakang, Detail)
$galleryImages = [];
$stmtImgs = $conn->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC");
if ($stmtImgs) {
    $stmtImgs->bind_param("i", $id);
    $stmtImgs->execute();
    $resImgs = $stmtImgs->get_result();
    if ($resImgs) {
        while ($rowImg = $resImgs->fetch_assoc()) {
            $galleryImages[] = $rowImg;
        }
    }
}
// Fallback jika belum memiliki relasi di product_images
if (empty($galleryImages) && !empty($product["image"])) {
    $galleryImages[] = [
        'id' => 0,
        'product_id' => $id,
        'image' => $product["image"],
        'sort_order' => 1,
        'type' => 'depan'
    ];
}

// URL Produk & Template Pesan WhatsApp Pemesanan Langsung
$productUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '');
$waMessage = "Halo SecondSpace, saya ingin memesan: " . $product["name"] . " | Ukuran: " . ($product["size"] ?: "-") . " | Harga: " . formatRupiah($product["price"]) . " | Link: " . $productUrl . ". Apakah masih tersedia?";
$waOrderUrl = waLink($whatsappNumber, $waMessage);

$cartNotice = $_SESSION["cart_notice"] ?? "";
unset($_SESSION["cart_notice"]);

// Produk Lainnya (4 produk)
$relatedProducts = [];
$stmtRelated = $conn->prepare("SELECT * FROM products WHERE id != ? ORDER BY id DESC LIMIT 4");
if ($stmtRelated) {
    $stmtRelated->bind_param("i", $id);
    $stmtRelated->execute();
    $resRel = $stmtRelated->get_result();
    if ($resRel) {
        while ($rowRel = $resRel->fetch_assoc()) {
            $relatedProducts[] = $rowRel;
        }
    }
}

$cartCount = isset($_SESSION["cart"]) ? count($_SESSION["cart"]) : 0;
$isLoggedIn = isset($_SESSION["user_id"]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product["name"]) ?> — SecondSpace PreLoved</title>
    <link rel="stylesheet" href="modern-theme.css">
    <style>
        .breadcrumb-trail {
            font-size: 13px;
            color: var(--mu);
            margin-bottom: 24px;
        }

        .breadcrumb-trail a {
            color: var(--mu);
            text-decoration: none;
        }

        .breadcrumb-trail a:hover {
            color: var(--tx);
        }

        .breadcrumb-trail .sep {
            margin: 0 8px;
            color: var(--ln);
        }

        /* 2-Column Detail Layout (Proporsional & Seimbang) */
        .detail-layout {
            display: grid;
            grid-template-columns: 440px 1fr;
            gap: 48px;
            align-items: start;
            margin-bottom: 32px;
        }

        /* Left Column: Gallery */
        .detail-gallery {
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .detail-main-image {
            width: 100%;
            aspect-ratio: 4 / 4.4;
            background: #FFFFFF;
            border: 1px solid #E5E5E5;
            border-radius: 4px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin-bottom: 12px;
        }

        .detail-main-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 10px;
            display: block;
        }

        .detail-main-watermark {
            font-family: var(--display-font);
            font-size: 44px;
            font-weight: 800;
            letter-spacing: 0.1em;
            color: #B5B5B5;
            text-transform: uppercase;
        }

        .detail-sold-overlay {
            position: absolute;
            inset: 0;
            background: rgba(30, 30, 30, 0.75);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: var(--display-font);
            font-size: 64px;
            font-weight: 900;
            letter-spacing: 0.08em;
            color: #EDEDED;
            text-transform: uppercase;
            z-index: 5;
        }

        /* Thumbnail Buttons (DEPAN, BELAKANG, DETAIL) - Kotak Persegi 1:1 Tidak Gepeng */
        .thumbnail-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }

        .thumb-box {
            position: relative;
            aspect-ratio: 1 / 1;
            background: #FFFFFF;
            border: 2px solid #E5E5E5;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: var(--display-font);
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.08em;
            color: #888888;
            text-transform: uppercase;
            text-decoration: none;
            cursor: pointer;
            overflow: hidden;
            transition: all 0.2s ease;
        }

        .thumb-box img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
            background: #FFFFFF;
            padding: 4px;
        }

        .thumb-box .thumb-badge {
            position: absolute;
            bottom: 4px;
            left: 4px;
            right: 4px;
            background: rgba(10, 10, 10, 0.75);
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-align: center;
            padding: 2px 4px;
            border-radius: 2px;
            text-transform: uppercase;
        }

        .thumb-box:hover {
            border-color: #AAAAAA;
            transform: translateY(-1px);
        }

        .thumb-box.active {
            border-color: var(--tx);
            background: #FFFFFF;
            box-shadow: 0 0 0 1px var(--tx);
            color: #111111;
        }

        .thumb-box.active .thumb-badge {
            background: var(--ac);
            color: #0A0A0A;
        }

        /* Right Column: Info */
        .detail-info {
            display: flex;
            flex-direction: column;
        }

        .badge-status {
            align-self: flex-start;
            font-family: var(--display-font);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            padding: 4px 10px;
            border-radius: 2px;
            margin-bottom: 12px;
        }

        .badge-status.available {
            background: #252e22;
            color: var(--ac);
            border: 1px solid var(--ac);
        }

        .badge-status.sold {
            background: #1F1F1F;
            color: #8A8A8A;
            border: 1px solid #333333;
        }

        .detail-title {
            font-family: var(--display-font);
            font-size: clamp(34px, 4.5vw, 48px);
            font-weight: 800;
            line-height: 1.05;
            letter-spacing: -0.01em;
            text-transform: uppercase;
            color: var(--tx);
            margin: 0 0 10px 0;
        }

        .detail-price {
            font-family: var(--body-font);
            font-size: 26px;
            font-weight: 700;
            color: var(--tx);
            margin-bottom: 24px;
        }

        /* Specs Table */
        .detail-specs-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }

        .detail-specs-table tr {
            border-top: 1px solid #222222;
            border-bottom: 1px solid #222222;
        }

        .detail-specs-table td {
            padding: 12px 0;
            font-size: 14px;
        }

        .detail-specs-table td.spec-label {
            color: var(--mu);
            width: 40%;
        }

        .detail-specs-table td.spec-val {
            color: var(--tx);
            font-weight: 600;
        }

        .detail-description {
            color: #B0B0B0;
            font-size: 14px;
            line-height: 1.7;
            margin-bottom: 28px;
        }

        /* Action Buttons */
        .btn-cart-solid {
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
            cursor: pointer;
            transition: all 0.2s ease;
            margin-bottom: 10px;
        }

        .btn-cart-solid:hover {
            background: var(--ach);
            border-color: var(--ach);
        }

        .btn-wa-outline {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            height: 48px;
            background: transparent;
            color: var(--tx);
            border: 1px solid var(--ln);
            border-radius: 4px;
            font-family: var(--display-font);
            font-size: 17px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            text-decoration: none;
            transition: all 0.2s ease;
            margin-bottom: 18px;
        }

        .btn-wa-outline:hover {
            border-color: var(--tx);
            background: var(--s1);
        }

        .btn-disabled-action {
            width: 100%;
            height: 48px;
            background: #1C1C1C;
            color: #555555;
            border: 1px solid #282828;
            border-radius: 4px;
            font-family: var(--display-font);
            font-size: 17px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            cursor: not-allowed;
            margin-bottom: 10px;
        }

        /* Box Preview WA */
        .wa-preview-box {
            background: var(--s1);
            border: 1px solid var(--ln);
            border-left: 3px solid var(--ac);
            border-radius: 4px;
            padding: 14px 18px;
            font-size: 13px;
            color: var(--mu);
            line-height: 1.6;
        }

        .wa-preview-box .wa-preview-label {
            font-weight: 700;
            color: var(--tx);
            margin-bottom: 4px;
        }

        .sold-notice-box {
            background: var(--s1);
            border: 1px solid var(--ln);
            border-left: 3px solid var(--ac);
            border-radius: 4px;
            padding: 14px 18px;
            font-size: 13px;
            color: var(--mu);
            line-height: 1.6;
        }

        /* Related Products Section */
        .related-title {
            font-family: var(--display-font);
            font-size: 32px;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: var(--tx);
            margin-bottom: 24px;
        }

        .related-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 20px;
        }

        .related-card {
            display: block;
            text-decoration: none;
            color: inherit;
            transition: transform 0.2s ease;
        }

        .related-card:hover {
            transform: translateY(-2px);
        }

        .related-image-box {
            width: 100%;
            aspect-ratio: 4 / 5;
            background: #EDEDED;
            border-radius: 4px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin-bottom: 10px;
        }

        .related-image-box img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            mix-blend-mode: multiply;
            padding: 16px;
        }

        .related-watermark {
            font-family: var(--display-font);
            font-size: 32px;
            font-weight: 800;
            letter-spacing: 0.1em;
            color: #B5B5B5;
            text-transform: uppercase;
        }

        .related-card-title {
            font-family: var(--body-font);
            font-size: 13px;
            font-weight: 700;
            color: var(--tx);
            text-transform: uppercase;
            text-align: center;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .related-card-price {
            font-size: 13px;
            color: var(--tx);
            font-weight: 600;
            text-align: center;
        }

        @media (max-width: 950px) {
            .detail-layout {
                grid-template-columns: 1fr;
                gap: 32px;
            }
            .detail-gallery {
                max-width: 500px;
                margin: 0 auto;
                width: 100%;
            }
            .related-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
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
        <a href="produk.php" class="active">PRODUK</a>
        <a href="keranjang.php">
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

    <!-- BREADCRUMB (SESUAI PROTOTYPE IMAGE 3) -->
    <div class="breadcrumb-trail">
        <a href="produk.php">Produk</a>
        <span class="sep">/</span>
        <a href="produk.php?categories[]=<?= urlencode($product['category']) ?>"><?= htmlspecialchars($product['category']) ?></a>
        <span class="sep">/</span>
        <span><?= htmlspecialchars($product['name']) ?></span>
    </div>

    <!-- MAIN DETAIL LAYOUT -->
    <div class="detail-layout">

        <!-- KOLOM KIRI: GALERI FOTO -->
        <div class="detail-gallery">
            <div class="detail-main-image">
                <?php if (!$isAvailable): ?>
                    <div class="detail-sold-overlay">SOLD</div>
                <?php endif; ?>

                <?php 
                $activeFotoIdx = isset($_GET['foto']) ? (int)$_GET['foto'] : 0;
                if ($activeFotoIdx < 0 || (!empty($galleryImages) && $activeFotoIdx >= count($galleryImages))) {
                    $activeFotoIdx = 0;
                }
                $mainImgSrc = !empty($galleryImages) ? $galleryImages[$activeFotoIdx]['image'] : ($product["image"] ?? "");
                $mainImgType = !empty($galleryImages) ? ucfirst($galleryImages[$activeFotoIdx]['type']) : "Depan";
                $mainImgUrl = gambar_produk_url($mainImgSrc, 'user');
                ?>
                <?php if (!empty($mainImgUrl)): ?>
                    <img id="mainDetailImg" src="<?= htmlspecialchars($mainImgUrl) ?>" alt="<?= htmlspecialchars($product["name"] . ' - Foto ' . $mainImgType) ?>" loading="lazy" width="600" height="600">
                <?php else: ?>
                    <div class="detail-main-watermark">
                        <?= htmlspecialchars(strtoupper($product["category"] ?? "SECONDSPACE")) ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- THUMBNAIL SELECTOR (DEPAN, BELAKANG, DETAIL) -->
            <?php if (!empty($galleryImages) && count($galleryImages) > 1): ?>
                <div class="thumbnail-row">
                    <?php foreach ($galleryImages as $idx => $gImg): ?>
                        <?php $thumbUrl = gambar_produk_url($gImg["image"] ?? '', 'user'); ?>
                        <a href="detail.php?id=<?= (int)$id ?>&foto=<?= $idx ?>" class="thumb-box <?= $idx === $activeFotoIdx ? 'active' : '' ?>">
                            <?php if (!empty($thumbUrl)): ?>
                                <img src="<?= htmlspecialchars($thumbUrl) ?>" alt="<?= htmlspecialchars($product["name"] . ' - ' . ucfirst($gImg['type'])) ?>" loading="lazy" width="120" height="120">
                            <?php endif; ?>
                            <span class="thumb-badge"><?= htmlspecialchars(strtoupper($gImg['type'])) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- KOLOM KANAN: SPESIFIKASI & AKSI -->
        <div class="detail-info">
            <?php if ($isAvailable): ?>
                <span class="badge-status available">AVAILABLE</span>
            <?php else: ?>
                <span class="badge-status sold">SOLD</span>
            <?php endif; ?>

            <h1 class="detail-title"><?= htmlspecialchars(strtoupper($product["name"])) ?></h1>
            <div class="detail-price">Rp <?= number_format($product["price"], 0, ",", ".") ?></div>

            <!-- TABEL SPESIFIKASI -->
            <table class="detail-specs-table">
                <?php if (!empty($product["brand"])): ?>
                <tr>
                    <td class="spec-label">Merek</td>
                    <td class="spec-val"><?= htmlspecialchars($product["brand"]) ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td class="spec-label">Ukuran</td>
                    <td class="spec-val"><?= htmlspecialchars($product["size"] ?? "-") ?></td>
                </tr>
                <tr>
                    <td class="spec-label">Material dasar</td>
                    <td class="spec-val"><?= htmlspecialchars($product["material"] ?? "Katun") ?></td>
                </tr>
                <tr>
                    <td class="spec-label">Kondisi</td>
                    <td class="spec-val"><?= htmlspecialchars($product["condition"] ?? "Baik") ?></td>
                </tr>
                <tr>
                    <td class="spec-label">Kategori</td>
                    <td class="spec-val"><?= htmlspecialchars($product["category"] ?? "-") ?></td>
                </tr>
            </table>

            <!-- DESKRIPSI PRODUK -->
            <p class="detail-description">
                <?= nl2br(htmlspecialchars($product["description"] ?? "Produk kurasi second hand pilihan dengan kondisi terjamin.")) ?>
            </p>

            <?php if (!empty($cartNotice)): ?>
                <div style="background: rgba(124, 154, 109, 0.15); border: 1px solid var(--ac); color: var(--tx); padding: 10px 14px; border-radius: 4px; font-size: 13px; margin-bottom: 14px;">
                    ℹ️ <?= htmlspecialchars($cartNotice) ?>
                </div>
            <?php endif; ?>

            <!-- ACTION BUTTONS -->
            <?php if ($isAvailable): ?>
                <form method="POST" action="keranjang.php">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?= $product["id"] ?>">
                    <button type="submit" class="btn-cart-solid">TAMBAH KE KERANJANG</button>
                </form>
            <?php else: ?>
                <button class="btn-disabled-action" disabled>Produk ini sudah terjual</button>
            <?php endif; ?>
        </div>

    </div>

    <!-- PRODUK LAINNYA (SESUAI PROTOTYPE IMAGE 4) -->
    <?php if (!empty($relatedProducts)): ?>
        <section style="margin-top: 0; padding-top: 36px; border-top: 1px solid var(--ln);">
            <h2 class="related-title">PRODUK LAINNYA</h2>
            <div class="related-grid">
                <?php foreach ($relatedProducts as $rel): ?>
                    <?php $relSold = ((int)$rel["stock"] <= 0); ?>
                    <a href="detail.php?id=<?= $rel["id"] ?>" class="related-card">
                        <div class="related-image-box">
                            <?php if ($relSold): ?>
                                <div class="proto-sold-overlay" style="font-size: 38px;">SOLD</div>
                            <?php endif; ?>

                            <?php 
                            $relImgUrl = gambar_produk_url($rel["image"] ?? '', 'user'); 
                            ?>
                            <?php if (!empty($relImgUrl)): ?>
                                <img src="<?= htmlspecialchars($relImgUrl) ?>" alt="<?= htmlspecialchars($rel["name"] . ' - Foto Depan') ?>" loading="lazy" width="300" height="300">
                            <?php else: ?>
                                <div class="related-watermark"><?= htmlspecialchars(strtoupper($rel["category"])) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="related-card-title"><?= htmlspecialchars(strtoupper($rel["name"])) ?></div>
                        <div class="related-card-price">Rp <?= number_format($rel["price"], 0, ",", ".") ?></div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

</div>

</body>
</html>
