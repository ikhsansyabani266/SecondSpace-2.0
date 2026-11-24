<?php
// [FILE] produk.php — Modul Terpadu Manajemen Produk & Kategori Preloved (100% No-JS)
// Dipakai oleh: Administrator SecondSpace
// Tabel database disentuh: products, product_images
// Fungsi bersama: /shared/config.php, /shared/koneksi.php, /shared/fungsi.php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../shared/koneksi.php';
require_once __DIR__ . '/../shared/fungsi.php';

// [AKSI: Tambah Kategori Baru via POST (No-JS)]
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_add_kategori'])) {
    if (!csrf_verify()) {
        flash_set('error', 'Token keamanan tidak valid.');
    } else {
        $namaKategori = trim($_POST['nama_kategori'] ?? '');
        if (empty($namaKategori)) {
            flash_set('error', 'Nama kategori tidak boleh kosong.');
        } else {
            $chk = $conn->prepare("SELECT id FROM products WHERE category = ? LIMIT 1");
            $chk->bind_param("s", $namaKategori);
            $chk->execute();
            if ($chk->get_result()->num_rows > 0) {
                flash_set('info', "Kategori '{$namaKategori}' sudah aktif.");
            } else {
                flash_set('success', "Kategori '{$namaKategori}' siap digunakan pada saat input produk baru.");
            }
            $chk->close();
        }
    }
    header("Location: produk.php");
    exit;
}

// [BAGIAN: Filter & Pagination GET Parameter (No-JS)]
$search    = trim($_GET['q'] ?? '');
$category  = trim($_GET['kategori'] ?? 'all');
$size      = trim($_GET['size'] ?? 'all');
$condition = trim($_GET['kondisi'] ?? 'all');
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 12;
$offset    = ($page - 1) * $perPage;

// Helper URL Generator untuk Filter Multiseksi (Pure GET)
function filter_url($overrides = []) {
    $current = [
        'q'        => $_GET['q'] ?? '',
        'kategori' => $_GET['kategori'] ?? 'all',
        'size'     => $_GET['size'] ?? 'all',
        'kondisi'  => $_GET['kondisi'] ?? 'all',
    ];
    $merged = array_merge($current, $overrides);
    $query = [];
    foreach ($merged as $k => $v) {
        if ($v !== '' && $v !== 'all') {
            $query[$k] = $v;
        }
    }
    return 'produk.php' . (!empty($query) ? '?' . http_build_query($query) : '');
}

// [QUERY: Hitung Total Produk Sesuai Filter]
$where = ["is_demo = 0"];
$types = "";
$params = [];

if ($category !== 'all' && !empty($category)) {
    $where[] = "category = ?";
    $types .= "s";
    $params[] = $category;
}

if ($size !== 'all' && !empty($size)) {
    $where[] = "size = ?";
    $types .= "s";
    $params[] = $size;
}

if ($condition !== 'all' && !empty($condition)) {
    $where[] = "`condition` = ?";
    $types .= "s";
    $params[] = $condition;
}

if ($search !== '') {
    $where[] = "(name LIKE ? OR brand LIKE ?)";
    $types .= "ss";
    $sTerm = "%" . $search . "%";
    $params[] = $sTerm;
    $params[] = $sTerm;
}

$whereSql = implode(" AND ", $where);

// Hitung total data terfilter
$countSql = "SELECT COUNT(*) as total FROM products WHERE {$whereSql}";
$cStmt = $conn->prepare($countSql);
if ($types !== "") {
    $cStmt->bind_param($types, ...$params);
}
$cStmt->execute();
$totalRecords = (int)($cStmt->get_result()->fetch_assoc()['total'] ?? 0);
$cStmt->close();

$totalPages = max(1, ceil($totalRecords / $perPage));

// [QUERY: Ambil Data Produk (SKU Ditiadakan Sesuai Permintaan)]
$sql = "SELECT id, name, category, brand, size, `condition`, price, stock, image, created_at 
        FROM products 
        WHERE {$whereSql} 
        ORDER BY id DESC 
        LIMIT ? OFFSET ?";

$typesWithLimit = $types . "ii";
$paramsWithLimit = array_merge($params, [$perPage, $offset]);

$stmt = $conn->prepare($sql);
$stmt->bind_param($typesWithLimit, ...$paramsWithLimit);
$stmt->execute();
$prodRes = $stmt->get_result();
$products = [];
while ($row = $prodRes->fetch_assoc()) {
    $products[] = $row;
}
$stmt->close();

// [QUERY: Kategori Aktif beserta Jumlah Produk]
$catQuery = $conn->query("
    SELECT category, COUNT(*) as total_prod
    FROM products
    WHERE is_demo = 0 AND category IS NOT NULL AND category != ''
    GROUP BY category
    ORDER BY category ASC
");
$allCategoriesStats = [];
$totalAllCatProducts = 0;
if ($catQuery) {
    while ($cr = $catQuery->fetch_assoc()) {
        $allCategoriesStats[] = $cr;
        $totalAllCatProducts += (int)$cr['total_prod'];
    }
}

// Daftar Pilihan Ukuran & Kondisi (Persis Halaman User)
$availableSizes = ["S", "M", "L", "XL", "XXL", "30", "32", "34", "42"];
$availableConditions = ["Seperti Baru", "Sangat Baik", "Baik", "Cukup"];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Produk — <?= NAMA_TOKO ?> Admin</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>

<div class="admin-wrapper">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="admin-main">
        <!-- Topbar -->
        <header class="admin-topbar">
            <div>
                <h1 class="topbar-title">Manajemen Produk</h1>
            </div>
            <div class="topbar-actions">
                <a href="produk_form.php" class="btn btn-primary btn-sm">+ Tambah Produk Baru</a>
            </div>
        </header>

        <div class="admin-content">
            <!-- Flash Message -->
            <?php if ($successMsg = flash_get('success')): ?>
                <div class="flash-msg success"><span class="status-badge-icon">[OK]</span> <?= htmlspecialchars($successMsg) ?></div>
            <?php endif; ?>
            <?php if ($infoMsg = flash_get('info')): ?>
                <div class="flash-msg info"><span>ℹ</span> <?= htmlspecialchars($infoMsg) ?></div>
            <?php endif; ?>
            <?php if ($errorMsg = flash_get('error')): ?>
                <div class="flash-msg error"><span class="status-badge-icon">[!]</span> <?= htmlspecialchars($errorMsg) ?></div>
            <?php endif; ?>

            <!-- [BAGIAN: FILTER SECTIONS MANAJEMEN PRODUK (KATEGORI, SIZE, KONDISI, STATUS)] -->
            <div class="product-filter-card">
                <!-- 1. SEKSI KATEGORI (SEPERTI HALAMAN USER + FORM TAMBAH KATEGORI) -->
                <div class="filter-row-section">
                    <span class="filter-section-title">KATEGORI:</span>
                    <div class="filter-pills-list">
                        <a href="<?= filter_url(['kategori' => 'all']) ?>" class="filter-pill <?= $category === 'all' ? 'active' : '' ?>">
                            SEMUA KATEGORI (<?= $totalAllCatProducts ?>)
                        </a>
                        <?php foreach ($allCategoriesStats as $cStat): ?>
                            <a href="<?= filter_url(['kategori' => $cStat['category']]) ?>" class="filter-pill <?= $category === $cStat['category'] ? 'active' : '' ?>">
                                <?= htmlspecialchars(strtoupper($cStat['category'])) ?> (<?= $cStat['total_prod'] ?>)
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 2. SEKSI UKURAN / SIZE (PERSIS HALAMAN USER) -->
                <div class="filter-row-section">
                    <span class="filter-section-title">UKURAN (SIZE):</span>
                    <div class="filter-pills-list">
                        <a href="<?= filter_url(['size' => 'all']) ?>" class="filter-pill <?= $size === 'all' ? 'active' : '' ?>">
                            SEMUA SIZE
                        </a>
                        <?php foreach ($availableSizes as $sz): ?>
                            <a href="<?= filter_url(['size' => $sz]) ?>" class="filter-pill size-pill <?= $size === $sz ? 'active' : '' ?>">
                                <?= htmlspecialchars($sz) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 3. SEKSI KONDISI (PERSIS HALAMAN USER) -->
                <div class="filter-row-section">
                    <span class="filter-section-title">KONDISI:</span>
                    <div class="filter-pills-list">
                        <a href="<?= filter_url(['kondisi' => 'all']) ?>" class="filter-pill <?= $condition === 'all' ? 'active' : '' ?>">
                            SEMUA KONDISI
                        </a>
                        <?php foreach ($availableConditions as $cnd): ?>
                            <a href="<?= filter_url(['kondisi' => $cnd]) ?>" class="filter-pill <?= $condition === $cnd ? 'active' : '' ?>">
                                <?= htmlspecialchars(strtoupper($cnd)) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- BARIS PENCARIAN & TAMBAH KATEGORI (TERINTEGRASI 1 BARIS) -->
                <div style="border-top: 1px solid var(--ln); padding-top: 14px; margin-top: 4px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
                    <!-- Form Pencarian Produk (Tanpa SKU) -->
                    <form method="GET" action="produk.php" style="display: flex; gap: 8px; align-items: center; flex: 1; max-width: 480px;">
                        <?php if ($category !== 'all'): ?><input type="hidden" name="kategori" value="<?= htmlspecialchars($category) ?>"><?php endif; ?>
                        <?php if ($size !== 'all'): ?><input type="hidden" name="size" value="<?= htmlspecialchars($size) ?>"><?php endif; ?>
                        <?php if ($condition !== 'all'): ?><input type="hidden" name="kondisi" value="<?= htmlspecialchars($condition) ?>"><?php endif; ?>
                        
                        <input type="text" name="q" placeholder="Cari nama produk atau merek..." value="<?= htmlspecialchars($search) ?>" style="height: 34px; padding: 0 12px; font-size: 12px; background: #14171A; border: 1px solid var(--ln); border-radius: 4px; color: var(--tx); width: 100%; outline: none; box-sizing: border-box;">
                        <button type="submit" class="btn btn-secondary btn-sm" style="height: 34px; padding: 0 14px; font-size: 11px;">Cari</button>
                        <?php if ($search !== '' || $category !== 'all' || $size !== 'all' || $condition !== 'all'): ?>
                            <a href="produk.php" class="btn btn-secondary btn-sm" style="height: 34px; padding: 0 12px; font-size: 11px;">Reset</a>
                        <?php endif; ?>
                    </form>

                    <!-- Form Tambah Kategori Baru Cepat (Integrasi dari Kategori.php) -->
                    <form method="POST" action="produk.php" style="display: flex; gap: 8px; align-items: center;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action_add_kategori" value="1">
                        <span style="font-family: var(--display-font); font-size: 10px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: var(--mu); white-space: nowrap;">
                            + KATEGORI BARU:
                        </span>
                        <input type="text" name="nama_kategori" placeholder="Nama kategori..." required style="height: 34px; width: 140px; padding: 0 10px; font-size: 11.5px; background: #14171A; border: 1px solid var(--ln); border-radius: 4px; color: var(--tx); outline: none; box-sizing: border-box;">
                        <button type="submit" class="btn btn-primary btn-sm" style="height: 34px; padding: 0 12px; font-size: 11px; white-space: nowrap;">
                            Tambah
                        </button>
                    </form>
                </div>
            </div>
            <!-- [/BAGIAN: FILTER SECTIONS MANAJEMEN PRODUK] -->

            <!-- [BAGIAN: TABEL DATA PRODUK (SKU DIHAPUS PENUH)] -->
            <div class="table-card">
                <div class="table-header">
                    <div>
                        <h2 class="table-title">Daftar Produk Preloved</h2>
                        <span style="font-size: 12px; color: var(--mu);">Menampilkan <?= count($products) ?> dari <?= $totalRecords ?> total produk terdaftar</span>
                    </div>
                </div>

                <div class="table-responsive" style="overflow-x: hidden;">
                    <table class="admin-table" style="table-layout: fixed; width: 100%;">
                        <thead>
                            <tr>
                                <th style="width: 6%;">FOTO</th>
                                <th style="width: 23%;">NAMA PRODUK</th>
                                <th style="width: 11%;">KATEGORI</th>
                                <th style="width: 10%;">MEREK</th>
                                <th style="width: 7%;">SIZE</th>
                                <th style="width: 9%;">KONDISI</th>
                                <th style="width: 10%;">HARGA</th>
                                <th style="width: 10%;">STATUS STOK</th>
                                <th style="width: 14%; text-align: right; padding-right: 14px;">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; color: var(--mu); padding: 40px;">
                                        <div style="font-size: 14px; font-weight: bold; margin-bottom: 8px; color: var(--accent);">[KATALOG]</div>
                                        <div style="font-weight: 700; color: var(--tx); font-size: 14px; margin-bottom: 4px;">Tidak Ada Produk Ditemukan</div>
                                        <div style="font-size: 13px; color: var(--mu);">Tidak ada produk yang cocok dengan kombinasi filter Anda.</div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($products as $p): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($p['image']) && file_exists(__DIR__ . '/../images/' . $p['image'])): ?>
                                                <img src="../images/<?= htmlspecialchars($p['image']) ?>" alt="Thumbnail" width="40" height="40" loading="lazy" style="object-fit: cover; border-radius: 4px; border: 1px solid var(--ln);">
                                            <?php else: ?>
                                                <div style="width: 40px; height: 40px; background: #222; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 9px; color: #666;">FOTO</div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong style="font-size: 12px; color: var(--tx); display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                <?= htmlspecialchars($p['name']) ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <span class="badge-status status-muted" style="font-size: 10px; padding: 2px 6px;">
                                                <?= htmlspecialchars($p['category']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span style="font-size: 11.5px;"><?= htmlspecialchars($p['brand']) ?></span>
                                        </td>
                                        <td>
                                            <strong style="font-size: 11.5px; font-family: var(--display-font); color: var(--ac);"><?= htmlspecialchars($p['size']) ?></strong>
                                        </td>
                                        <td>
                                            <span style="font-size: 11px; color: var(--mu);"><?= htmlspecialchars($p['condition']) ?></span>
                                        </td>
                                        <td>
                                            <strong style="color: var(--tx); font-size: 12.5px; font-family: var(--display-font);"><?= format_rupiah($p['price']) ?></strong>
                                        </td>
                                        <td>
                                            <?php if ((int)$p['stock'] > 0): ?>
                                                <span class="badge-status status-success" style="font-weight: 800; font-size: 10px; padding: 3px 8px; letter-spacing: 0.04em;">AVAILABLE</span>
                                            <?php else: ?>
                                                <span class="badge-status status-danger" style="font-weight: 800; font-size: 10px; padding: 3px 8px; letter-spacing: 0.04em;">SOLD</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: right; white-space: nowrap; padding-right: 14px;">
                                            <a href="produk_form.php?id=<?= $p['id'] ?>" class="btn btn-secondary btn-sm" style="height: 26px; padding: 0 8px; font-size: 10px; margin-right: 4px;">Ubah</a>
                                            <a href="konfirmasi_hapus.php?tipe=produk&id=<?= $p['id'] ?>" class="btn btn-danger btn-sm" style="height: 26px; padding: 0 8px; font-size: 10px;">Hapus</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Paginasi (Pure GET - No JS) -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination-wrap" style="padding: 14px 20px; border-top: 1px solid var(--ln); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                        <span style="font-size: 11.5px; color: var(--mu);">
                            Halaman <strong><?= $page ?></strong> dari <strong><?= $totalPages ?></strong> (Total <?= $totalRecords ?> Produk)
                        </span>
                        <div style="display: flex; gap: 6px;">
                            <?php if ($page > 1): ?>
                                <a href="<?= filter_url(['page' => $page - 1]) ?>" class="btn btn-secondary btn-sm">&laquo; Prev</a>
                            <?php endif; ?>
                            <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                                <a href="<?= filter_url(['page' => $p]) ?>" class="btn <?= $p === $page ? 'btn-primary' : 'btn-secondary' ?> btn-sm" style="padding: 0 10px;">
                                    <?= $p ?>
                                </a>
                            <?php endfor; ?>
                            <?php if ($page < $totalPages): ?>
                                <a href="<?= filter_url(['page' => $page + 1]) ?>" class="btn btn-secondary btn-sm">Next &raquo;</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <!-- [/BAGIAN: TABEL DATA PRODUK] -->
        </div>
    </main>
</div>

</body>
</html>
