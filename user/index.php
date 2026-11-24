<?php
/**
 * [FILE]: user/index.php
 * [BAGIAN]: Beranda / Landing Page Pelanggan
 * [DESKRIPSI]: Menampilkan hero banner streetwear, status stok katalog, preview produk unggulan, dan nilai jual toko
 * [JANGAN UBAH]: Struktur session dan koneksi database bersama
 * [UBAH DI SINI]: Teks hero banner, tagline toko, atau susunan link CTA
 * [TAMBAH DI SINI]: Seksi testimonial pelanggan atau banner promo seasonal baru
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../shared/koneksi.php';
require_once __DIR__ . '/../shared/fungsi.php';

/** @var mysqli $conn */

/* [QUERY]: Total Produk Aktif */
$resultProducts = $conn->query("SELECT COUNT(*) AS total FROM products");
$totalProducts = ($resultProducts && $row = $resultProducts->fetch_assoc()) ? (int)$row["total"] : 0;

/* [QUERY]: Total Transaksi / Pesanan */
$totalOrders = 0;
if (isset($_SESSION["user_id"])) {
    $userId = (int)$_SESSION["user_id"];
    $stmtO = $conn->prepare("SELECT COUNT(*) AS total FROM orders WHERE user_id = ?");
    if ($stmtO) {
        $stmtO->bind_param("i", $userId);
        $stmtO->execute();
        $resO = $stmtO->get_result();
        if ($resO && $rowO = $resO->fetch_assoc()) {
            $totalOrders = (int)$rowO["total"];
        }
        $stmtO->close();
    }
} else {
    $resAllOrders = $conn->query("SELECT COUNT(*) AS total FROM orders");
    if ($resAllOrders && $rowAO = $resAllOrders->fetch_assoc()) {
        $totalOrders = (int)$rowAO["total"];
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
    <title>SecondSpace — Toko Pakaian Preloved Terkurasi</title>
    <link rel="stylesheet" href="modern-theme.css">
    <style>
        /* ======================================================
           STREETWEAR TICKER MARQUEE
        ====================================================== */
        .streetwear-ticker {
            width: 100%;
            background: #111111;
            border-top: none;
            border-bottom: 1px solid var(--ln);
            padding: 10px 0;
            overflow: hidden;
            white-space: nowrap;
            user-select: none;
        }

        .ticker-inner {
            display: inline-flex;
            gap: 36px;
            animation: tickerSlide 28s linear infinite;
        }

        .ticker-item {
            font-family: var(--display-font);
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--mu);
            display: inline-flex;
            align-items: center;
            gap: 12px;
        }

        .ticker-item strong {
            color: var(--tx);
        }

        .ticker-item .dot {
            color: var(--ac);
        }

        @keyframes tickerSlide {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }

        /* ======================================================
           HERO SECTION: BACKGROUND FOTO PRELOVED STREETWEAR
        ====================================================== */
        .hero {
            position: relative;
            min-height: 75vh;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            padding: 85px 7% 80px;
            background: #0A0A0A url('images/hero-preloved.jpg') no-repeat center center / cover;
            border-bottom: 1px solid var(--ln);
            box-sizing: border-box;
            overflow: hidden;
        }

        /* Dark cinematic gradient overlay agar teks terbaca tajam */
        .hero::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(
                90deg, 
                rgba(10, 10, 10, 0.97) 0%, 
                rgba(10, 10, 10, 0.90) 45%, 
                rgba(10, 10, 10, 0.65) 100%
            );
            z-index: 1;
        }

        /* ======================================================
           TOMBOL AUTENTIKASI: TERPISAH DI ATAS KANAN HERO
        ====================================================== */
        .hero-top-auth {
            position: absolute;
            top: 26px;
            right: 7%;
            z-index: 5;
        }

        .btn-top-auth {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 18px;
            border-radius: 999px;
            background: rgba(14, 16, 19, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.14);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            font-family: var(--display-font);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            text-decoration: none;
            color: var(--mu);
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.35);
        }

        .btn-top-auth:hover {
            background: rgba(28, 32, 36, 0.95);
            color: #FFFFFF;
            transform: translateY(-1px);
        }

        .btn-top-logout:hover {
            border-color: var(--alert);
            color: var(--alert);
            box-shadow: 0 4px 14px rgba(239, 68, 68, 0.25);
        }

        .btn-top-login {
            color: var(--ac);
            border-color: rgba(124, 154, 109, 0.4);
        }

        .btn-top-login:hover {
            background: var(--ac);
            color: #0A0A0A;
            border-color: var(--ac);
        }

        /* ======================================================
           HERO BUTTONS ROW: RAPI, PRESISI, DAN SEJAJAR
        ====================================================== */
        .hero-buttons-row {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 44px;
        }

        .hero-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            height: 48px;
            padding: 0 26px;
            border-radius: 999px;
            font-family: var(--display-font);
            font-size: 14.5px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            text-decoration: none;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            box-sizing: border-box;
            white-space: nowrap;
        }

        .hero-btn-primary {
            background: var(--ac);
            color: #0A0A0A;
            border: 1px solid var(--ac);
            padding: 0 32px;
            box-shadow: 0 4px 18px rgba(124, 154, 109, 0.35);
        }

        .hero-btn-primary:hover {
            background: #9BB88D;
            border-color: #9BB88D;
            color: #000000;
            transform: translateY(-2px);
            box-shadow: 0 6px 24px rgba(124, 154, 109, 0.5);
        }

        .hero-btn-dark {
            background: rgba(18, 21, 24, 0.85);
            color: #FFFFFF;
            border: 1px solid rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.4);
        }

        .hero-btn-dark:hover {
            background: rgba(28, 32, 38, 0.95);
            border-color: var(--ac);
            color: #FFFFFF;
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.5), 0 0 10px rgba(124, 154, 109, 0.25);
        }

        .hero-badge-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            border-radius: 999px;
            background: var(--ac);
            color: #0A0A0A;
            font-family: var(--display-font);
            font-size: 11px;
            font-weight: 900;
            line-height: 1;
            margin-left: 2px;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 760px;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--ac);
            font-family: var(--display-font);
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            margin-bottom: 20px;
            background: rgba(124, 154, 109, 0.15);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(124, 154, 109, 0.35);
            padding: 6px 16px;
            border-radius: 999px;
        }

        .hero-badge-pulse {
            width: 7px;
            height: 7px;
            background: var(--ac);
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 10px var(--ac);
        }

        .hero-heading {
            font-family: var(--display-font);
            font-size: clamp(56px, 7.5vw, 94px);
            font-weight: 900;
            line-height: 0.92;
            letter-spacing: -0.02em;
            text-transform: uppercase;
            margin: 0 0 32px 0;
            color: var(--tx);
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
        }

        .hero-heading span.accent {
            color: var(--ac);
        }

        .hero-cta-row {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 44px;
        }

        .btn-hero-solid {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            height: 48px;
            padding: 0 30px;
            background: var(--ac);
            color: #0A0A0A;
            border: 1px solid var(--ac);
            border-radius: 4px;
            font-family: var(--display-font);
            font-size: 16px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn-hero-solid:hover {
            background: var(--ach);
            border-color: var(--ach);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(124, 154, 109, 0.35);
        }

        .btn-hero-outline {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 48px;
            padding: 0 26px;
            background: rgba(22, 22, 22, 0.7);
            backdrop-filter: blur(8px);
            color: var(--tx);
            border: 1px solid var(--ln);
            border-radius: 4px;
            font-family: var(--display-font);
            font-size: 16px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn-hero-outline:hover {
            border-color: var(--tx);
            background: rgba(35, 35, 35, 0.9);
        }

        .hero-stats-strip {
            display: flex;
            gap: 40px;
            border-top: 1px solid var(--ln);
            padding-top: 24px;
        }

        .hero-stat-box strong {
            display: block;
            font-family: var(--display-font);
            font-size: 34px;
            font-weight: 800;
            color: var(--tx);
            line-height: 1;
            margin-bottom: 4px;
        }

        .hero-stat-box span {
            color: var(--mu);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 600;
        }

        /* ======================================================
           BRAND MANIFESTO SECTION (ABOUT US)
        ====================================================== */
        .section-container {
            max-width: 1180px;
            margin: 60px auto 80px;
            padding: 0 24px;
            box-sizing: border-box;
        }

        .manifesto-box {
            background: var(--s1);
            border: 1px solid var(--ln);
            border-radius: 6px;
            padding: 48px 40px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 48px;
            align-items: center;
        }

        .manifesto-left {
            border-right: 1px solid var(--ln);
            padding-right: 36px;
        }

        .manifesto-tag {
            font-family: var(--display-font);
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--ac);
            margin-bottom: 8px;
            display: block;
        }

        .manifesto-title {
            font-family: var(--display-font);
            font-size: clamp(38px, 5vw, 60px);
            font-weight: 900;
            line-height: 0.95;
            letter-spacing: -0.01em;
            text-transform: uppercase;
            color: var(--tx);
            margin: 0 0 20px 0;
        }

        .manifesto-archive-code {
            font-family: monospace;
            font-size: 11px;
            color: var(--mu);
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .manifesto-right p {
            color: var(--mu);
            font-size: 15px;
            line-height: 1.7;
            margin: 0 0 20px 0;
        }

        .manifesto-values {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            padding-top: 20px;
            border-top: 1px solid var(--ln);
        }

        .manifesto-val strong {
            display: block;
            font-family: var(--display-font);
            font-size: 16px;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: var(--tx);
            margin-bottom: 4px;
        }

        .manifesto-val span {
            font-size: 12px;
            color: var(--mu);
            line-height: 1.4;
            display: block;
        }

        /* ======================================================
           RESPONSIVE BREAKPOINTS
        ====================================================== */
        @media (max-width: 900px) {
            .hero {
                padding: 70px 20px 60px;
            }
            .hero-heading {
                font-size: clamp(44px, 9vw, 68px);
            }
            .manifesto-box {
                grid-template-columns: 1fr;
                gap: 32px;
                padding: 32px 24px;
            }
            .manifesto-left {
                border-right: none;
                border-bottom: 1px solid var(--ln);
                padding-right: 0;
                padding-bottom: 24px;
            }
        }

        @media (max-width: 600px) {
            .hero {
                padding: 50px 16px 40px;
            }
            .hero-stats-strip {
                gap: 20px;
            }
            .manifesto-values {
                grid-template-columns: 1fr;
                gap: 12px;
            }
        }
    </style>
</head>
<body>

<main>
    <!-- STREETWEAR TICKER MARQUEE (BERJALAN MULUS TANPA TERTUTUP NAVBAR) -->
    <div class="streetwear-ticker">
        <div class="ticker-inner">
            <span class="ticker-item"><strong>SECONDSPΛCE® PRELOVED</strong> <span class="dot">•</span></span>
            <span class="ticker-item">CURATED SECOND HAND CLOTHING <span class="dot">•</span></span>
            <span class="ticker-item"><strong>STREETWEAR CONCEPT VIBES</strong> <span class="dot">•</span></span>
            <span class="ticker-item">LIMITED 1-OF-1 PIECES <span class="dot">•</span></span>
            <span class="ticker-item">SUSTAINABLE CIRCULAR LIVING <span class="dot">•</span></span>
            <span class="ticker-item"><strong>PENGIRIMAN CEPAT &amp; AMAN</strong> <span class="dot">•</span></span>
            <!-- Duplikat untuk seamless loop -->
            <span class="ticker-item"><strong>SECONDSPΛCE® PRELOVED</strong> <span class="dot">•</span></span>
            <span class="ticker-item">CURATED SECOND HAND CLOTHING <span class="dot">•</span></span>
            <span class="ticker-item"><strong>STREETWEAR CONCEPT VIBES</strong> <span class="dot">•</span></span>
            <span class="ticker-item">LIMITED 1-OF-1 PIECES <span class="dot">•</span></span>
            <span class="ticker-item">SUSTAINABLE CIRCULAR LIVING <span class="dot">•</span></span>
            <span class="ticker-item"><strong>PENGIRIMAN CEPAT &amp; AMAN</strong> <span class="dot">•</span></span>
        </div>
    </div>

    <!-- HERO SECTION DENGAN FOTO BACKGROUND -->
    <section class="hero">
        <!-- [TOMBOL AUTENTIKASI: TERPISAH DI ATAS KANAN HERO] -->
        <div class="hero-top-auth">
            <?php if ($isLoggedIn): ?>
                <a href="logout.php" class="btn-top-auth btn-top-logout">
                    KELUAR &rarr;
                </a>
            <?php else: ?>
                <a href="login.php" class="btn-top-auth btn-top-login">
                    MASUK &rarr;
                </a>
            <?php endif; ?>
        </div>

        <div class="hero-content">
            <div class="hero-badge">
                <span class="hero-badge-pulse"></span>
                SECONDSPΛCE® // CURATED PRELOVED CLOTHING
            </div>
            
            <h1 class="hero-heading">
                CURATED<br>
                <span class="accent">PRELOVED</span><br>
                COLLECTION.
            </h1>

            <!-- [TOMBOL AKSI & NAVIGASI HERO: RAPI, PRESISI, DAN SEJAJAR] -->
            <div class="hero-buttons-row">
                <a href="produk.php" class="hero-btn hero-btn-primary">
                    LIHAT KATALOG PRODUK &rarr;
                </a>
                <a href="keranjang.php" class="hero-btn hero-btn-dark">
                    KERANJANG
                    <?php if ($cartCount > 0): ?>
                        <span class="hero-badge-count"><?= $cartCount ?></span>
                    <?php endif; ?>
                </a>
                <a href="pesanan.php" class="hero-btn hero-btn-dark">
                    PESANAN
                </a>
            </div>

            <div class="hero-stats-strip">
                <div class="hero-stat-box">
                    <strong><?= $totalProducts ?></strong>
                    <span>Pakaian Preloved</span>
                </div>
                <div class="hero-stat-box">
                    <strong>100%</strong>
                    <span>Kualitas Terkurasi</span>
                </div>
                <div class="hero-stat-box">
                    <strong>1-OF-1</strong>
                    <span>Koleksi Unik</span>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION ABOUT US / MANIFESTO PRELOVED -->
    <section class="section-container" id="tentang">
        <div class="manifesto-box">
            <div class="manifesto-left">
                <span class="manifesto-tag">// ABOUT SECONDSPΛCE</span>
                <h2 class="manifesto-title">
                    SECOND LIFE.<br>
                    NEW STYLE.
                </h2>
                <div class="manifesto-archive-code">
                    PRELOVED ARCHIVE &middot; CURATED IN INDONESIA
                </div>
            </div>

            <div class="manifesto-right">
                <p>
                    SecondSpace adalah ruang untuk menemukan dan memberikan kesempatan kedua bagi barang-barang berkualitas. Kami mempertemukan pemilik baru dengan pakaian preloved pilihan yang unik, terjangkau, dan lebih bertanggung jawab bagi lingkungan.
                </p>
                <p>
                    Karena gaya yang baik tidak harus selalu baru — yang penting punya cerita, karakter autentik, dan tetap terasa tepat untuk kamu.
                </p>

                <div class="manifesto-values">
                    <div class="manifesto-val">
                        <strong>100% Curated</strong>
                        <span>Inspeksi teliti pada bahan dan kondisi jahitan.</span>
                    </div>
                    <div class="manifesto-val">
                        <strong>Best Value</strong>
                        <span>Pakaian preloved berkualitas dengan harga bersahabat.</span>
                    </div>
                    <div class="manifesto-val">
                        <strong>Sustainable</strong>
                        <span>Mendukung fesyen sirkular demi kurangi limbah tekstil.</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- SIMPLE STREETWEAR FOOTER (SESUAI DESAIN SEBELUMNYA) -->
<footer class="simple-footer">
    <div class="simple-footer-container">
        <!-- Logo SecondSpace -->
        <div class="simple-footer-main">
            <a href="index.php" class="simple-footer-logo">
                <span class="logo-second">SECOND</span><span class="logo-space">SPΛCE</span><span class="logo-reg">®</span>
            </a>
        </div>

        <!-- Kontak & Info (WA, IG, Lokasi) -->
        <div class="simple-footer-contact">
            <div class="simple-contact-item">
                <span class="simple-contact-icon">[WA]</span>
                <div>
                    <div class="simple-contact-label">WHATSAPP</div>
                    <a href="https://wa.me/<?= normalizePhone($whatsappNumber) ?>?s=p" target="_blank" rel="noopener" class="simple-contact-link">
                        +<?= normalizePhone($whatsappNumber) ?>
                    </a>
                </div>
            </div>

            <div class="simple-contact-item">
                <span class="simple-contact-icon">[IG]</span>
                <div>
                    <div class="simple-contact-label">INSTAGRAM</div>
                    <a href="https://instagram.com/secondspace" target="_blank" rel="noopener" class="simple-contact-link">
                        @secondspace
                    </a>
                </div>
            </div>

            <div class="simple-contact-item">
                <span class="simple-contact-icon">[HQ]</span>
                <div>
                    <div class="simple-contact-label">LOKASI</div>
                    <span class="simple-contact-text">Jakarta, Indonesia</span>
                </div>
            </div>
        </div>
    </div>

    <div class="simple-footer-bottom">
        &copy; <?= date("Y") ?> SECONDSPΛCE&reg; PRELOVED ARCHIVE. ALL RIGHTS RESERVED.
    </div>
</footer>

</body>
</html>
