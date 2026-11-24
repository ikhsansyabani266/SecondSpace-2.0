<?php
/**
 * [FILE]: user/produk.php
 * [BAGIAN]: Katalog Koleksi Produk Preloved
 * [DESKRIPSI]: Menampilkan grid produk dengan filter kategori multi-pilihan, ukuran, kondisi, rentang harga, dan pengurutan
 * [JANGAN UBAH]: Struktur query filter WHERE dinamis dan logika pagination
 * [UBAH DI SINI]: Jumlah produk per halaman ($perPage), opsi sorting default
 * [TAMBAH DI SINI]: Filter baru (misal: filter brand/merek atau warna)
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../shared/koneksi.php';
require_once __DIR__ . '/../shared/fungsi.php';

/** @var mysqli $conn */

/* [BAGIAN]: Pengambilan Filter & Parameter Pencarian (Pure GET - No JS) */

// Kategori (bisa dari pill 'category' tunggal atau array 'categories[]' dari sidebar)
$activeCategory = trim($_GET["category"] ?? "Semua");
$selectedCategories = $_GET["categories"] ?? [];
if (!is_array($selectedCategories)) {
    $selectedCategories = [$selectedCategories];
}
if ($activeCategory !== "Semua" && empty($selectedCategories)) {
    $selectedCategories[] = $activeCategory;
}

// Pencarian
$search = trim($_GET["search"] ?? "");

// Ukuran
$selectedSizes = $_GET["sizes"] ?? [];
if (!is_array($selectedSizes)) {
    $selectedSizes = [$selectedSizes];
}

// Rentang Harga
$minPrice = isset($_GET["min_price"]) && is_numeric($_GET["min_price"]) ? (float)$_GET["min_price"] : null;
$maxPrice = isset($_GET["max_price"]) && is_numeric($_GET["max_price"]) ? (float)$_GET["max_price"] : null;

// Kondisi
$selectedConditions = $_GET["conditions"] ?? [];
if (!is_array($selectedConditions)) {
    $selectedConditions = [$selectedConditions];
}

// Status: Hanya yang tersedia
$availableOnly = isset($_GET["available_only"]) && $_GET["available_only"] == "1";

// Pengurutan (Sort)
$sort = trim($_GET["sort"] ?? "terbaru");

// ======================================================
// PEMBUATAN QUERY DINAMIS
// ======================================================

$query = "SELECT * FROM products WHERE 1=1";
$params = [];
$types = "";

// Filter Kategori
if (!empty($selectedCategories) && !in_array("Semua", $selectedCategories, true)) {
    $placeholders = implode(",", array_fill(0, count($selectedCategories), "?"));
    $query .= " AND category IN ($placeholders)";
    foreach ($selectedCategories as $cat) {
        $params[] = $cat;
        $types .= "s";
    }
}

// Filter Pencarian
if ($search !== "") {
    $query .= " AND (name LIKE ? OR description LIKE ? OR category LIKE ?)";
    $kw = "%" . $search . "%";
    $params[] = $kw;
    $params[] = $kw;
    $params[] = $kw;
    $types .= "sss";
}

// Filter Ukuran
if (!empty($selectedSizes)) {
    $placeholders = implode(",", array_fill(0, count($selectedSizes), "?"));
    $query .= " AND size IN ($placeholders)";
    foreach ($selectedSizes as $sz) {
        $params[] = $sz;
        $types .= "s";
    }
}

// Filter Harga
if ($minPrice !== null && $minPrice > 0) {
    $query .= " AND price >= ?";
    $params[] = $minPrice;
    $types .= "d";
}
if ($maxPrice !== null && $maxPrice > 0) {
    $query .= " AND price <= ?";
    $params[] = $maxPrice;
    $types .= "d";
}

// Filter Kondisi
if (!empty($selectedConditions)) {
    $placeholders = implode(",", array_fill(0, count($selectedConditions), "?"));
    $query .= " AND `condition` IN ($placeholders)";
    foreach ($selectedConditions as $cnd) {
        $params[] = $cnd;
        $types .= "s";
    }
}

// Filter Hanya yang tersedia
if ($availableOnly) {
    $query .= " AND stock > 0";
}

// Pengurutan
switch ($sort) {
    case "termurah":
        $query .= " ORDER BY price ASC, id DESC";
        break;
    case "termahal":
        $query .= " ORDER BY price DESC, id DESC";
        break;
    case "terbaru":
    default:
        $query .= " ORDER BY id DESC";
        break;
}

$stmt = $conn->prepare($query);
if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $products = $stmt->get_result();
} else {
    $products = null;
}

$totalProducts = $products ? $products->num_rows : 0;
$cartCount = isset($_SESSION["cart"]) ? count($_SESSION["cart"]) : 0;
$isLoggedIn = isset($_SESSION["user_id"]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Produk — SecondSpace PreLoved</title>
    <link rel="stylesheet" href="modern-theme.css">
    <style>
        /* ======================================================
           HEADER KATALOG
        ====================================================== */
        .catalog-header {
            margin-bottom: 28px;
        }

        .catalog-headline {
            font-family: var(--display-font);
            font-size: clamp(48px, 6vw, 76px);
            font-weight: 800;
            line-height: 0.95;
            letter-spacing: -0.02em;
            text-transform: uppercase;
            color: var(--tx);
            margin: 0 0 10px 0;
        }

        .catalog-subheadline {
            color: var(--mu);
            font-size: 15px;
            line-height: 1.5;
            margin: 0;
        }

        /* ======================================================
           LAYOUT DUA KOLOM: SIDEBAR FILTER & MAIN CONTENT
        ====================================================== */
        .catalog-layout {
            display: grid;
            grid-template-columns: 240px 1fr;
            gap: 36px;
            align-items: start;
        }

        /* ======================================================
           SIDEBAR FILTER (SESUAI PROTOTYPE)
        ====================================================== */
        .filter-sidebar {
            background: transparent;
        }

        .filter-header-title {
            font-family: var(--display-font);
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--tx);
            padding-bottom: 12px;
            border-bottom: 1px solid var(--ln);
            margin-bottom: 22px;
        }

        .filter-section {
            margin-bottom: 24px;
        }

        .filter-title {
            font-family: var(--body-font);
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--tx);
            margin-bottom: 12px;
            display: block;
        }

        /* Custom Checkbox */
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: #C0C0C0;
            margin-bottom: 8px;
            cursor: pointer;
            user-select: none;
        }

        .checkbox-label input[type="checkbox"] {
            appearance: none;
            -webkit-appearance: none;
            width: 18px;
            height: 18px;
            border: 1px solid var(--ln);
            border-radius: 3px;
            background: var(--s1);
            cursor: pointer;
            position: relative;
            margin: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .checkbox-label input[type="checkbox"]:checked {
            background: var(--ac);
            border-color: var(--ac);
        }

        .checkbox-label input[type="checkbox"]:checked::after {
            content: "[v]";
            color: #0A0A0A;
            font-size: 12px;
            font-weight: 900;
        }

        /* Ukuran Chips Grid */
        .size-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 6px;
            margin-bottom: 6px;
        }

        .size-chip {
            position: relative;
        }

        .size-chip input[type="checkbox"] {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .size-chip label {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 38px;
            background: var(--s1);
            border: 1px solid var(--ln);
            border-radius: 4px;
            color: var(--tx);
            font-family: var(--body-font);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
            text-transform: uppercase;
        }

        .size-chip input[type="checkbox"]:checked + label {
            background: var(--ac);
            color: #0A0A0A;
            border-color: var(--ac);
            font-weight: 700;
        }

        /* Input Kisaran Harga */
        .price-inputs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .price-inputs input {
            height: 40px !important;
            min-height: 40px !important;
            padding: 0 10px !important;
            font-size: 13px !important;
            background: var(--s1) !important;
            border: 1px solid var(--ln) !important;
            border-radius: 4px !important;
            color: var(--tx) !important;
        }

        .price-inputs input::placeholder {
            color: var(--mu) !important;
        }

        /* Tombol Aksi Filter */
        .filter-btn-apply {
            width: 100%;
            height: 42px;
            background: var(--ac);
            color: #0A0A0A;
            border: 1px solid var(--ac);
            border-radius: 4px;
            font-family: var(--display-font);
            font-size: 16px;
            font-weight: 800;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-bottom: 8px;
        }

        .filter-btn-apply:hover {
            background: var(--ach);
            border-color: var(--ach);
        }

        .filter-btn-reset {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 42px;
            background: transparent;
            color: var(--tx);
            border: 1px solid var(--ln);
            border-radius: 4px;
            font-family: var(--display-font);
            font-size: 16px;
            font-weight: 800;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .filter-btn-reset:hover {
            border-color: var(--tx);
            background: var(--s1);
        }

        /* ======================================================
           AREA KANAN: TOOLBAR, TABS KATEGORI, GRID PRODUK
        ====================================================== */
        .catalog-main {
            min-width: 0;
        }

        /* Single-Row Toolbar (Kategori + Search + Cari + Sort dalam 1 Baris) */
        .catalog-toolbar-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .category-tabs {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 0;
        }

        .category-tab-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 40px;
            padding: 0 16px;
            border-radius: 4px;
            font-family: var(--display-font);
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            text-decoration: none;
            background: var(--s1);
            color: var(--tx);
            border: 1px solid var(--ln);
            transition: all 0.15s ease;
            white-space: nowrap;
        }

        .category-tab-btn:hover {
            border-color: var(--tx);
        }

        .category-tab-btn.active {
            background: var(--ac);
            color: #0A0A0A;
            border-color: var(--ac);
            font-weight: 800;
        }

        .catalog-top-bar {
            display: flex;
            gap: 8px;
            margin-bottom: 0;
            align-items: center;
            flex: 1;
            max-width: 460px;
            justify-content: flex-end;
        }

        .catalog-search-field {
            flex: 1;
            min-width: 220px;
            position: relative;
        }

        .catalog-search-field input[type="text"] {
            width: 100%;
            height: 40px !important;
            min-height: 40px !important;
            background: var(--s1) !important;
            border: 1px solid var(--ln) !important;
            border-radius: 4px !important;
            color: var(--tx) !important;
            padding: 0 14px !important;
            font-size: 13px !important;
        }

        .catalog-search-field input[type="text"]::placeholder {
            color: var(--mu) !important;
        }

        .btn-cari-top {
            height: 40px;
            padding: 0 18px;
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

        .btn-cari-top:hover {
            background: var(--ach);
            border-color: var(--ach);
        }

        .product-count-label {
            color: var(--mu);
            font-size: 14px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        /* ======================================================
           GRID PRODUK (3 KOLOM SESUAI FOTO PROTOTYPE)
        ====================================================== */
        .proto-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 22px;
        }

        .proto-card {
            display: block;
            text-decoration: none;
            color: inherit;
            position: relative;
            transition: transform 0.2s ease;
        }

        .proto-card:hover {
            transform: translateY(-2px);
        }

        .proto-image-box {
            width: 100%;
            aspect-ratio: 4 / 5;
            background: #FFFFFF;
            border: 1px solid #EAEAEA;
            border-radius: 4px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .proto-image-box img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 8px;
            display: block;
        }

        .proto-watermark {
            font-family: var(--display-font);
            font-size: 42px;
            font-weight: 800;
            letter-spacing: 0.1em;
            color: #B5B5B5;
            text-transform: uppercase;
            user-select: none;
        }

        /* Badge Available / Sold */
        .badge-available {
            position: absolute;
            top: 12px;
            left: 12px;
            background: #252e22;
            color: var(--ac);
            border: 1px solid var(--ac);
            font-family: var(--display-font);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            padding: 3px 8px;
            border-radius: 2px;
            z-index: 4;
        }

        .badge-sold {
            position: absolute;
            top: 12px;
            left: 12px;
            background: #1F1F1F;
            color: #8A8A8A;
            border: 1px solid #333333;
            font-family: var(--display-font);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            padding: 3px 8px;
            border-radius: 2px;
            z-index: 7;
        }

        .proto-sold-overlay {
            position: absolute;
            inset: 0;
            background: rgba(30, 30, 30, 0.75);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: var(--display-font);
            font-size: 58px;
            font-weight: 900;
            letter-spacing: 0.08em;
            color: #EDEDED;
            text-transform: uppercase;
            z-index: 5;
        }

        /* Card Meta */
        .proto-card-info {
            padding: 12px 4px 0 4px;
            text-align: center;
        }

        .proto-card-title {
            font-family: var(--body-font);
            font-size: 14px;
            font-weight: 700;
            color: var(--tx);
            text-transform: uppercase;
            line-height: 1.35;
            margin-bottom: 4px;
            min-height: 38px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .proto-card-category {
            font-size: 13px;
            color: var(--mu);
            margin-bottom: 4px;
        }

        .proto-card-price-row {
            font-size: 14px;
            color: var(--tx);
            margin-top: 4px;
        }

        .proto-card-price {
            font-weight: 700;
            color: var(--tx);
        }

        .proto-card-condition {
            color: var(--mu);
            font-size: 13px;
            margin-left: 6px;
        }

        /* Empty State */
        .proto-empty-state {
            background: var(--s1);
            border: 1px solid var(--ln);
            border-radius: 4px;
            padding: 60px 24px;
            text-align: center;
            color: var(--mu);
        }

        .proto-empty-state h3 {
            color: var(--tx);
            font-family: var(--display-font);
            font-size: 26px;
            font-weight: 800;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        @media (max-width: 900px) {
            .catalog-layout {
                grid-template-columns: 1fr;
            }
            .proto-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 560px) {
            .proto-grid {
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

    <!-- HEADER KATALOG -->
    <div class="catalog-header">
        <h1 class="catalog-headline">PRELOVED. ONE OF ONE.</h1>
    </div>

    <!-- MAIN TWO COLUMN LAYOUT -->
    <div class="catalog-layout">

        <!-- ==============================================
             KOLOM KIRI: SIDEBAR FILTER
        ============================================== -->
        <aside class="filter-sidebar">
            <form method="GET" action="produk.php" id="filterForm">
                <?php if ($search !== ""): ?>
                    <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                <?php endif; ?>
                <?php if ($sort !== "terbaru"): ?>
                    <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
                <?php endif; ?>
                <?php if (!empty($selectedCategories)): ?>
                    <?php foreach ($selectedCategories as $cat): ?>
                        <input type="hidden" name="categories[]" value="<?= htmlspecialchars($cat) ?>">
                    <?php endforeach; ?>
                <?php endif; ?>

                <div class="filter-header-title">FILTER</div>

                <!-- UKURAN -->
                <div class="filter-section">
                    <span class="filter-title">UKURAN</span>
                    <div class="size-grid">
                        <?php
                        $row1Sizes = ["S", "M", "L", "XL", "XXL"];
                        foreach ($row1Sizes as $sz):
                            $checked = in_array($sz, $selectedSizes, true);
                        ?>
                            <div class="size-chip">
                                <input type="checkbox" name="sizes[]" value="<?= $sz ?>" id="sz_<?= $sz ?>" <?= $checked ? 'checked' : '' ?>>
                                <label for="sz_<?= $sz ?>"><?= $sz ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="size-grid" style="grid-template-columns: repeat(4, 1fr);">
                        <?php
                        $row2Sizes = ["30", "32", "34", "42"];
                        foreach ($row2Sizes as $sz):
                            $checked = in_array($sz, $selectedSizes, true);
                        ?>
                            <div class="size-chip">
                                <input type="checkbox" name="sizes[]" value="<?= $sz ?>" id="sz_<?= $sz ?>" <?= $checked ? 'checked' : '' ?>>
                                <label for="sz_<?= $sz ?>"><?= $sz ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- KISARAN HARGA -->
                <div class="filter-section">
                    <span class="filter-title">KISARAN HARGA</span>
                    <div class="price-inputs">
                        <input type="number" name="min_price" value="<?= $minPrice ? htmlspecialchars((string)$minPrice) : '' ?>" placeholder="Harga min">
                        <input type="number" name="max_price" value="<?= $maxPrice ? htmlspecialchars((string)$maxPrice) : '' ?>" placeholder="Harga max">
                    </div>
                </div>

                <!-- KONDISI -->
                <div class="filter-section">
                    <span class="filter-title">KONDISI</span>
                    <?php
                    $allConditions = defined('DAFTAR_KONDISI_PRODUK') ? DAFTAR_KONDISI_PRODUK : ["Seperti Baru", "Bagus", "Ada Minus"];
                    foreach ($allConditions as $cndOption):
                        $checked = in_array($cndOption, $selectedConditions, true);
                    ?>
                        <label class="checkbox-label">
                            <input type="checkbox" name="conditions[]" value="<?= $cndOption ?>" <?= $checked ? 'checked' : '' ?>>
                            <span><?= $cndOption ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <!-- STATUS -->
                <div class="filter-section">
                    <span class="filter-title">STATUS</span>
                    <label class="checkbox-label">
                        <input type="checkbox" name="available_only" value="1" <?= $availableOnly ? 'checked' : '' ?>>
                        <span>Hanya yang tersedia</span>
                    </label>
                </div>

                <!-- BUTTONS -->
                <button type="submit" class="filter-btn-apply">TERAPKAN</button>
                <a href="produk.php" class="filter-btn-reset">RESET</a>
            </form>
        </aside>

        <!-- ==============================================
             KOLOM KANAN: TOOLBAR + KATEGORI TABS + PRODUK GRID
        ============================================== -->
        <main class="catalog-main">

            <!-- SINGLE ROW TOOLBAR: KATEGORI TABS + SEARCH + SORT (1 BARIS RAPIH) -->
            <div class="catalog-toolbar-row">
                <!-- CATEGORY TABS -->
                <div class="category-tabs">
                    <a href="produk.php<?= !empty($search) ? '?search=' . urlencode($search) : '' ?>" class="category-tab-btn <?= (empty($selectedCategories) || in_array('Semua', $selectedCategories, true)) ? 'active' : '' ?>">SEMUA</a>
                    <a href="produk.php?categories[]=Baju<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="category-tab-btn <?= in_array('Baju', $selectedCategories, true) ? 'active' : '' ?>">BAJU</a>
                    <a href="produk.php?categories[]=Kemeja<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="category-tab-btn <?= in_array('Kemeja', $selectedCategories, true) ? 'active' : '' ?>">KEMEJA</a>
                    <a href="produk.php?categories[]=Celana<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="category-tab-btn <?= in_array('Celana', $selectedCategories, true) ? 'active' : '' ?>">CELANA</a>
                    <a href="produk.php?categories[]=Jacket<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="category-tab-btn <?= in_array('Jacket', $selectedCategories, true) ? 'active' : '' ?>">JACKET</a>
                </div>

                <!-- TOP BAR (SEARCH + SORT) -->
                <form method="GET" action="produk.php" class="catalog-top-bar">
                    <?php if (!empty($selectedCategories)): ?>
                        <?php foreach ($selectedCategories as $cat): ?>
                            <input type="hidden" name="categories[]" value="<?= htmlspecialchars($cat) ?>">
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <div class="catalog-search-field">
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari produk...">
                    </div>

                    <button type="submit" class="btn-cari-top">CARI</button>
                </form>
            </div>

            <!-- JUMLAH PRODUK -->
            <div class="product-count-label">
                <?= $totalProducts ?> produk
            </div>

            <!-- PRODUCT GRID (3 KOLOM SESUAI FOTO PROTOTYPE) -->
            <?php if ($products && $totalProducts > 0): ?>
                <div class="proto-grid">
                    <?php while ($product = $products->fetch_assoc()): ?>
                        <?php $isSold = ((int)$product["stock"] <= 0); ?>
                        <a href="detail.php?id=<?= $product["id"] ?>" class="proto-card">
                            <div class="proto-image-box">
                                <?php if ($isSold): ?>
                                    <span class="badge-sold">SOLD</span>
                                    <div class="proto-sold-overlay">SOLD</div>
                                <?php else: ?>
                                    <span class="badge-available">AVAILABLE</span>
                                <?php endif; ?>

                                <?php 
                                $cardImgUrl = gambar_produk_url($product["image"] ?? '', 'user'); 
                                ?>
                                <?php if (!empty($cardImgUrl)): ?>
                                    <img src="<?= htmlspecialchars($cardImgUrl) ?>" alt="<?= htmlspecialchars($product["name"] . ' - Foto Depan') ?>" loading="lazy" width="400" height="400">
                                <?php else: ?>
                                    <div class="proto-watermark">
                                        <?= htmlspecialchars(strtoupper($product["category"] ?? "SECONDSPACE")) ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="proto-card-info">
                                <div class="proto-card-title">
                                    <?= htmlspecialchars(strtoupper($product["name"])) ?>
                                </div>
                                <div class="proto-card-price-row">
                                    <span class="proto-card-price">Rp <?= number_format($product["price"], 0, ",", ".") ?></span>
                                    <span class="proto-card-condition"><?= htmlspecialchars($product["condition"] ?? "") ?></span>
                                </div>
                            </div>
                        </a>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="proto-empty-state">
                    <h3>Produk tidak ditemukan</h3>
                    <p>Contoh tampilan kosong saat filter tidak cocok dengan produk mana pun. Coba ubah kata kunci atau reset filter.</p>
                </div>
            <?php endif; ?>

        </main>

    </div>

</div>

</body>
</html>
