<?php
// [FILE] riwayat.php — Modul Riwayat Transaksi & Status Koleksi Preloved (100% No-JS)
// Dipakai oleh: Administrator SecondSpace
// Tabel database disentuh: products, order_items, orders, users
// Fungsi bersama: /shared/config.php, /shared/koneksi.php, /shared/fungsi.php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../shared/koneksi.php';
require_once __DIR__ . '/../shared/fungsi.php';

// [PARAMETER FILTER PURE GET (NO-JS)]
$statusFilter = strtolower(trim($_GET['status'] ?? 'all'));
if (!in_array($statusFilter, ['all', 'available', 'sold'], true)) {
    $statusFilter = 'all';
}
$search = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

// [HITUNG STATISTIK STATUS PRELOVED (AVAILABLE VS SOLD)]
$countAvailSql = "
    SELECT COUNT(DISTINCT p.id) as c
    FROM products p
    LEFT JOIN order_items oi ON p.id = oi.product_id
    LEFT JOIN orders o ON oi.order_id = o.id AND o.order_status != 'Dibatalkan'
    WHERE (p.stock > 0 AND o.id IS NULL)
";
$countAvailable = (int)($conn->query($countAvailSql)->fetch_assoc()['c'] ?? 0);

$countSoldSql = "
    SELECT COUNT(DISTINCT p.id) as c
    FROM products p
    LEFT JOIN order_items oi ON p.id = oi.product_id
    LEFT JOIN orders o ON oi.order_id = o.id AND o.order_status != 'Dibatalkan'
    WHERE (p.stock <= 0 OR o.id IS NOT NULL)
";
$countSold = (int)($conn->query($countSoldSql)->fetch_assoc()['c'] ?? 0);
$countTotal = $countAvailable + $countSold;

// [QUERY DATA RIWAYAT TRANSAKSI DENGAN FILTER]
$whereConditions = ["1=1"];
$params = [];
$types = "";

if ($statusFilter === 'available') {
    $whereConditions[] = "(p.stock > 0 AND o.id IS NULL)";
} elseif ($statusFilter === 'sold') {
    $whereConditions[] = "(p.stock <= 0 OR o.id IS NOT NULL)";
}

if ($search !== '') {
    $whereConditions[] = "(p.name LIKE ? OR p.category LIKE ? OR p.brand LIKE ? OR o.order_code LIKE ? OR u.name LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sssss";
}

$whereSql = implode(" AND ", $whereConditions);

// Hitung total item terfilter
$countFilterSql = "
    SELECT COUNT(DISTINCT p.id) as total
    FROM products p
    LEFT JOIN order_items oi ON p.id = oi.product_id
    LEFT JOIN orders o ON oi.order_id = o.id AND o.order_status != 'Dibatalkan'
    LEFT JOIN users u ON o.user_id = u.id
    WHERE {$whereSql}
";
$cntStmt = $conn->prepare($countFilterSql);
if (!empty($params)) {
    $cntStmt->bind_param($types, ...$params);
}
$cntStmt->execute();
$totalFiltered = (int)($cntStmt->get_result()->fetch_assoc()['total'] ?? 0);
$cntStmt->close();

$totalPages = max(1, ceil($totalFiltered / $perPage));

// Ambil daftar riwayat per-item preloved beserta transaksi pesanan terkait
$dataSql = "
    SELECT 
        p.id as product_id,
        p.name as product_name,
        p.category as product_category,
        p.brand as product_brand,
        p.size as product_size,
        p.condition as product_condition,
        p.price as product_price,
        p.stock as product_stock,
        p.image as product_image,
        p.created_at as product_created_at,
        o.id as order_id,
        o.order_code,
        o.order_status,
        o.payment_method,
        o.payment_status,
        o.created_at as order_created_at,
        COALESCE(a.recipient_name, u.name) as buyer_name,
        oi.price as sold_price
    FROM products p
    LEFT JOIN order_items oi ON p.id = oi.product_id
    LEFT JOIN orders o ON oi.order_id = o.id AND o.order_status != 'Dibatalkan'
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN addresses a ON o.address_id = a.id
    WHERE {$whereSql}
    GROUP BY p.id
    ORDER BY (o.id IS NOT NULL) DESC, o.id DESC, p.id DESC
    LIMIT ? OFFSET ?
";

$dataParams = $params;
$dataParams[] = $perPage;
$dataParams[] = $offset;
$dataTypes = $types . "ii";

$stmt = $conn->prepare($dataSql);
$stmt->bind_param($dataTypes, ...$dataParams);
$stmt->execute();
$dataRes = $stmt->get_result();
$itemsList = [];
while ($row = $dataRes->fetch_assoc()) {
    $itemsList[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Transaksi Preloved — <?= NAMA_TOKO ?> Admin</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>

<div class="admin-wrapper">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="admin-main">
        <!-- Topbar -->
        <header class="admin-topbar">
            <div>
                <h1 class="topbar-title">Riwayat Transaksi Preloved</h1>
            </div>
            <div style="font-size: 12px; color: var(--mu);">
                Pencatatan Otomatis Status Transaksi & Ketersediaan Koleksi Preloved
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

            <!-- [BAGIAN: 2 KARTU PENARIKAN DATA BAR (AVAILABLE VS SOLD)] -->
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 24px;">
                <!-- KARTU 1: AVAILABLE (TERSEDIA) -->
                <a href="riwayat.php?status=available<?= $search !== '' ? '&q=' . urlencode($search) : '' ?>" 
                   style="text-decoration: none; display: block; border-radius: var(--radius-card); padding: 20px 24px; transition: all 0.2s ease; background: var(--s1); border: 2px solid <?= $statusFilter === 'available' ? 'var(--status-success-border)' : 'var(--ln)' ?>; box-shadow: <?= $statusFilter === 'available' ? '0 0 16px rgba(16, 185, 129, 0.2)' : 'none' ?>;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <span style="font-family: var(--display-font); font-size: 11px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: var(--status-success-text); display: inline-flex; align-items: center; gap: 6px;">
                                <span style="width: 8px; height: 8px; border-radius: 50%; background: var(--status-success-text); display: inline-block;"></span>
                                STATUS AVAILABLE
                            </span>
                            <div style="font-family: var(--display-font); font-size: 32px; font-weight: 900; color: var(--tx); margin: 6px 0 2px 0;">
                                <?= $countAvailable ?> <span style="font-size: 15px; color: var(--mu); font-weight: 600;">Item</span>
                            </div>
                            <div style="font-size: 12px; color: var(--mu);">
                                Koleksi barang preloved yang masih tersedia & siap dibeli (Stok 1)
                            </div>
                        </div>
                        <?php if ($statusFilter === 'available'): ?>
                            <span style="background: var(--status-success-bg); color: var(--status-success-text); border: 1px solid var(--status-success-border); font-size: 10px; font-weight: 800; padding: 4px 10px; border-radius: 20px; text-transform: uppercase;">
                                Aktif Difilter
                            </span>
                        <?php endif; ?>
                    </div>
                </a>

                <!-- KARTU 2: SOLD OUT (TERJUAL VIA PESANAN) -->
                <a href="riwayat.php?status=sold<?= $search !== '' ? '&q=' . urlencode($search) : '' ?>" 
                   style="text-decoration: none; display: block; border-radius: var(--radius-card); padding: 20px 24px; transition: all 0.2s ease; background: var(--s1); border: 2px solid <?= $statusFilter === 'sold' ? 'var(--status-danger-border)' : 'var(--ln)' ?>; box-shadow: <?= $statusFilter === 'sold' ? '0 0 16px rgba(239, 68, 68, 0.2)' : 'none' ?>;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <span style="font-family: var(--display-font); font-size: 11px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: var(--status-danger-text); display: inline-flex; align-items: center; gap: 6px;">
                                <span style="width: 8px; height: 8px; border-radius: 50%; background: var(--status-danger-text); display: inline-block;"></span>
                                STATUS SOLD OUT
                            </span>
                            <div style="font-family: var(--display-font); font-size: 32px; font-weight: 900; color: var(--tx); margin: 6px 0 2px 0;">
                                <?= $countSold ?> <span style="font-size: 15px; color: var(--mu); font-weight: 600;">Item</span>
                            </div>
                            <div style="font-size: 12px; color: var(--mu);">
                                Riwayat transaksi barang preloved yang telah terjual melalui pesanan
                            </div>
                        </div>
                        <?php if ($statusFilter === 'sold'): ?>
                            <span style="background: var(--status-danger-bg); color: var(--status-danger-text); border: 1px solid var(--status-danger-border); font-size: 10px; font-weight: 800; padding: 4px 10px; border-radius: 20px; text-transform: uppercase;">
                                Aktif Difilter
                            </span>
                        <?php endif; ?>
                    </div>
                </a>
            </div>

            <!-- [BAGIAN: TABEL RIWAYAT TRANSAKSI PRELOVED (AUDIT PER-ITEM)] -->
            <section class="table-card">
                <div class="table-header" style="flex-wrap: wrap; gap: 14px; padding-bottom: 16px; border-bottom: 1px solid var(--ln);">
                    <div>
                        <h2 class="table-title">Daftar Riwayat Transaksi Koleksi Preloved</h2>
                        <span style="font-size: 12px; color: var(--mu);">
                            Menampilkan <?= count($itemsList) ?> dari <?= $totalFiltered ?> koleksi preloved
                            <?= $statusFilter !== 'all' ? '(Filter: ' . strtoupper($statusFilter) . ')' : '' ?>
                        </span>
                    </div>

                    <!-- Toolbar Filter Pencarian & Status Tab -->
                    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                        <!-- Filter Tab Pills -->
                        <div style="display: flex; gap: 6px;">
                            <a href="riwayat.php?status=all<?= $search !== '' ? '&q=' . urlencode($search) : '' ?>" 
                               class="btn btn-secondary btn-sm" 
                               style="<?= $statusFilter === 'all' ? 'background: var(--ac); color: #0A0A0A; font-weight: 800;' : '' ?>">
                                Semua (<?= $countTotal ?>)
                            </a>
                            <a href="riwayat.php?status=available<?= $search !== '' ? '&q=' . urlencode($search) : '' ?>" 
                               class="btn btn-secondary btn-sm" 
                               style="<?= $statusFilter === 'available' ? 'background: var(--status-success-border); color: #FFF; font-weight: 800;' : '' ?>">
                                Available (<?= $countAvailable ?>)
                            </a>
                            <a href="riwayat.php?status=sold<?= $search !== '' ? '&q=' . urlencode($search) : '' ?>" 
                               class="btn btn-secondary btn-sm" 
                               style="<?= $statusFilter === 'sold' ? 'background: var(--status-danger-border); color: #FFF; font-weight: 800;' : '' ?>">
                                Sold (<?= $countSold ?>)
                            </a>
                        </div>

                        <!-- Form Pencarian -->
                        <form method="GET" action="riwayat.php" style="display: flex; gap: 8px; align-items: center;">
                            <?php if ($statusFilter !== 'all'): ?>
                                <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
                            <?php endif; ?>
                            <input type="text" name="q" placeholder="Cari nama, merek, no pesanan..." value="<?= htmlspecialchars($search) ?>" style="height: 32px; width: 220px; padding: 4px 10px; font-size: 11.5px; box-sizing: border-box; background: #14171A; border: 1px solid var(--ln); border-radius: 4px; color: var(--tx); outline: none;">
                            <button type="submit" class="btn btn-secondary btn-sm" style="height: 32px; padding: 0 14px; font-size: 11px; font-weight: 800;">Cari</button>
                            <?php if ($search !== '' || $statusFilter !== 'all'): ?>
                                <a href="riwayat.php" class="btn btn-secondary btn-sm" style="height: 32px; padding: 0 12px; font-size: 11px;">Reset</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <div class="table-responsive" style="overflow-x: hidden;">
                    <table class="admin-table" style="table-layout: fixed; width: 100%;">
                        <thead>
                            <tr>
                                <th style="width: 28%;">BARANG PRELOVED</th>
                                <th style="width: 13%;">STATUS PRELOVED</th>
                                <th style="width: 12%;">HARGA</th>
                                <th style="width: 33%;">RIWAYAT TRANSAKSI PESANAN</th>
                                <th style="width: 14%; text-align: right;">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($itemsList)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; color: var(--mu); padding: 48px 20px;">
                                        <div style="font-size: 14px; font-weight: bold; margin-bottom: 8px; color: var(--accent);">[LOG MUTASI]</div>
                                        <div style="font-weight: 700; color: var(--tx); font-size: 15px; margin-bottom: 4px;">Tidak Ada Data Ditemukan</div>
                                        <div style="font-size: 13px; color: var(--mu);">Tidak ada catatan koleksi preloved yang sesuai dengan filter pencarian.</div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($itemsList as $it): ?>
                                    <?php
                                        $isSold = (!empty($it['order_code']) || (int)$it['product_stock'] <= 0);
                                    ?>
                                    <tr>
                                        <!-- KOLOM 1: BARANG PRELOVED -->
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 12px;">
                                                <?php if (!empty($it['product_image']) && file_exists(__DIR__ . '/../images/' . $it['product_image'])): ?>
                                                    <img src="../images/<?= htmlspecialchars($it['product_image']) ?>" alt="Thumbnail" width="44" height="44" style="object-fit: cover; border-radius: 4px; border: 1px solid var(--ln); flex-shrink: 0;">
                                                <?php else: ?>
                                                    <div style="width: 44px; height: 44px; background: #202428; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 9px; color: #888; flex-shrink: 0;">FOTO</div>
                                                <?php endif; ?>
                                                <div style="overflow: hidden;">
                                                    <strong style="font-size: 12.5px; color: var(--tx); display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                        <?= htmlspecialchars($it['product_name']) ?>
                                                    </strong>
                                                    <div style="display: flex; gap: 6px; align-items: center; margin-top: 3px; flex-wrap: wrap;">
                                                        <span style="font-size: 10.5px; color: var(--mu);"><?= htmlspecialchars($it['product_category']) ?></span>
                                                        <span style="color: var(--ln);">•</span>
                                                        <span style="font-size: 10px; background: #1C2024; padding: 1px 6px; border-radius: 3px; color: #DDD;">
                                                            Size: <?= htmlspecialchars($it['product_size'] ?: 'All Size') ?>
                                                        </span>
                                                        <span style="font-size: 10px; background: #1C2024; padding: 1px 6px; border-radius: 3px; color: #BBB;">
                                                            <?= htmlspecialchars($it['product_condition'] ?: 'Bagus') ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- KOLOM 2: STATUS PRELOVED -->
                                        <td>
                                            <?php if ($isSold): ?>
                                                <span class="badge-status status-danger" style="font-size: 10px; padding: 3px 8px; font-weight: 800; white-space: nowrap;">
                                                    SOLD (TERJUAL)
                                                </span>
                                            <?php else: ?>
                                                <span class="badge-status status-success" style="font-size: 10px; padding: 3px 8px; font-weight: 800; white-space: nowrap;">
                                                    AVAILABLE (READY)
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- KOLOM 3: HARGA -->
                                        <td>
                                            <span style="font-family: var(--display-font); font-size: 12px; font-weight: 800; color: var(--tx);">
                                                <?= formatRupiah((float)($it['sold_price'] ?: $it['product_price'])) ?>
                                            </span>
                                        </td>

                                        <!-- KOLOM 4: RIWAYAT TRANSAKSI PESANAN -->
                                        <td>
                                            <?php if ($isSold && !empty($it['order_code'])): ?>
                                                <div>
                                                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 3px;">
                                                        <a href="detail_pesanan.php?id=<?= $it['order_id'] ?>" class="order-code-badge" style="font-size: 11px; text-decoration: none;">
                                                            <?= htmlspecialchars($it['order_code']) ?>
                                                        </a>
                                                        <span class="badge-status status-info" style="font-size: 9px; padding: 1px 5px;">
                                                            <?= htmlspecialchars(strtoupper($it['order_status'] ?: 'Diproses')) ?>
                                                        </span>
                                                    </div>
                                                    <div style="font-size: 11px; color: var(--tx);">
                                                        Pembeli: <strong><?= htmlspecialchars($it['buyer_name'] ?: 'Pelanggan') ?></strong>
                                                        <span style="color: var(--mu);"> • <?= htmlspecialchars($it['payment_method'] ?: 'Transfer') ?></span>
                                                    </div>
                                                    <div style="font-size: 10px; color: var(--mu); margin-top: 2px;">
                                                        Waktu Terjual: <?= date('d M Y, H:i', strtotime($it['order_created_at'])) ?> WIB
                                                    </div>
                                                </div>
                                            <?php elseif ($isSold): ?>
                                                <span style="font-size: 11.5px; color: var(--status-danger-text);">
                                                    Barang sudah laku terjual (Stok 0).
                                                </span>
                                            <?php else: ?>
                                                <span style="font-size: 11.5px; color: var(--mu);">
                                                    Siap dipesan di etalase toko. Belum ada transaksi pembelian.
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- KOLOM 5: AKSI -->
                                        <td style="text-align: right;">
                                            <?php if ($isSold && !empty($it['order_id'])): ?>
                                                <a href="detail_pesanan.php?id=<?= $it['order_id'] ?>" class="btn btn-secondary btn-sm" style="font-size: 10.5px; padding: 4px 10px; white-space: nowrap;">
                                                    Lihat Pesanan &UpperRightArrow;
                                                </a>
                                            <?php else: ?>
                                                <a href="../user/detail.php?id=<?= $it['product_id'] ?>" target="_blank" rel="noopener" class="btn btn-secondary btn-sm" style="font-size: 10.5px; padding: 4px 10px; white-space: nowrap;">
                                                    Lihat di Toko &UpperRightArrow;
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- [PAGINASI PURE GET] -->
                <?php if ($totalPages > 1): ?>
                    <div class="table-footer" style="padding-top: 16px; margin-top: 14px; border-top: 1px solid var(--ln); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                        <div class="table-info" style="font-size: 12px; color: var(--mu);">
                            Halaman <?= $page ?> dari <?= $totalPages ?> (Total <?= $totalFiltered ?> Koleksi)
                        </div>
                        <div class="pagination-btns" style="display: flex; gap: 6px;">
                            <?php if ($page > 1): ?>
                                <a href="riwayat.php?status=<?= urlencode($statusFilter) ?>&q=<?= urlencode($search) ?>&page=<?= $page - 1 ?>" class="page-btn">&laquo; Prev</a>
                            <?php endif; ?>

                            <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                                <a href="riwayat.php?status=<?= urlencode($statusFilter) ?>&q=<?= urlencode($search) ?>&page=<?= $p ?>" class="page-btn <?= $p === $page ? 'active' : '' ?>">
                                    <?= $p ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <a href="riwayat.php?status=<?= urlencode($statusFilter) ?>&q=<?= urlencode($search) ?>&page=<?= $page + 1 ?>" class="page-btn">Next &raquo;</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>
</div>

</body>
</html>
