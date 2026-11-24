<?php
/**
 * [FILE]: admin/pesanan.php
 * [BAGIAN]: Manajemen & Pelacakan Pesanan Pelanggan (Versi Rapi & Terpadu)
 * [DESKRIPSI]: Menampilkan daftar pesanan dengan filter status, quick-pills, pencarian, dan tautan detail
 * [ATURAN]: 100% PHP Native, TANPA JavaScript, No-JS Filter GET
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../shared/koneksi.php';
require_once __DIR__ . '/../shared/fungsi.php';

/** @var mysqli $conn */

/* [BAGIAN]: Parameter Filter & Pencarian (Pure GET - No JavaScript) */
$search = trim($_GET['q'] ?? '');
$filterOrderStatus = trim($_GET['order_status'] ?? '');
$filterPaymentStatus = trim($_GET['payment_status'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

/* [QUERY]: Quick KPI Counters untuk Situational Awareness (6 Kategori Monitoring Pesanan) */
$countAllOrders  = (int)$conn->query("SELECT COUNT(*) as c FROM orders")->fetch_assoc()['c'];
$countMenunggu   = (int)$conn->query("SELECT COUNT(*) as c FROM orders WHERE order_status = 'Menunggu Pembayaran' OR status = 'Menunggu Pembayaran'")->fetch_assoc()['c'];
$countDiproses   = (int)$conn->query("SELECT COUNT(*) as c FROM orders WHERE order_status = 'Diproses' OR status = 'Diproses'")->fetch_assoc()['c'];
$countDikirim    = (int)$conn->query("SELECT COUNT(*) as c FROM orders WHERE order_status = 'Dikirim' OR status = 'Dikirim'")->fetch_assoc()['c'];
$countSelesai    = (int)$conn->query("SELECT COUNT(*) as c FROM orders WHERE order_status = 'Selesai' OR status = 'Selesai'")->fetch_assoc()['c'];
$countDibatalkan = (int)$conn->query("SELECT COUNT(*) as c FROM orders WHERE order_status = 'Dibatalkan' OR status = 'Dibatalkan'")->fetch_assoc()['c'];

/* [QUERY]: Bangun Klausa WHERE Dinamis */
$whereClauses = ["1=1"];
$params = [];
$types = "";

if ($search !== '') {
    $whereClauses[] = "(o.order_code LIKE ? OR o.order_number LIKE ? OR u.name LIKE ? OR a.recipient_name LIKE ? OR a.phone LIKE ?)";
    $likeSearch = "%{$search}%";
    $params = array_merge($params, [$likeSearch, $likeSearch, $likeSearch, $likeSearch, $likeSearch]);
    $types .= "sssss";
}

if ($filterOrderStatus !== '') {
    $whereClauses[] = "(o.order_status = ? OR o.status = ?)";
    $params[] = $filterOrderStatus;
    $params[] = $filterOrderStatus;
    $types .= "ss";
}

if ($filterPaymentStatus !== '') {
    $whereClauses[] = "o.payment_status = ?";
    $params[] = $filterPaymentStatus;
    $types .= "s";
}

$whereSql = implode(" AND ", $whereClauses);

/* [QUERY]: Hitung Total Data untuk Pagination */
$countSql = "
    SELECT COUNT(o.id) AS total
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN addresses a ON o.address_id = a.id
    WHERE {$whereSql}
";
$countStmt = $conn->prepare($countSql);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalRows = (int)$countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();
$totalPages = max(1, (int)ceil($totalRows / $perPage));

/* [QUERY]: Ambil Daftar Pesanan */
$dataSql = "
    SELECT
        o.*,
        u.name AS user_name,
        u.username,
        a.recipient_name,
        a.phone AS recipient_phone,
        a.city AS recipient_city,
        (SELECT COUNT(oi.id) FROM order_items oi WHERE oi.order_id = o.id) AS total_items
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN addresses a ON o.address_id = a.id
    WHERE {$whereSql}
    ORDER BY o.id DESC
    LIMIT ? OFFSET ?
";

$dataStmt = $conn->prepare($dataSql);
$dataTypes = $types . "ii";
$dataParams = array_merge($params, [$perPage, $offset]);
$dataStmt->bind_param($dataTypes, ...$dataParams);
$dataStmt->execute();
$ordersResult = $dataStmt->get_result();
$orders = [];
while ($row = $ordersResult->fetch_assoc()) {
    $orders[] = $row;
}
$dataStmt->close();

/* [BAGIAN]: Status Badges Style Map */
$statusOrderBadges = [
    'Menunggu Pembayaran' => 'status-warning',
    'Diproses'            => 'status-info',
    'Dikirim'             => 'status-info',
    'Selesai'             => 'status-success',
    'Dibatalkan'          => 'status-danger'
];

$statusPaymentBadges = [
    'Menunggu Pembayaran' => 'status-warning',
    'Menunggu Konfirmasi' => 'status-warning',
    'Dibayar'             => 'status-success',
    'Terverifikasi'       => 'status-success',
    'Selesai'             => 'status-success',
    'Dibatalkan'          => 'status-danger',
    'Belum Bayar'         => 'status-muted'
];

$pageTitle = "Daftar Pesanan — " . NAMA_TOKO . " Admin";
$currentNav = "pesanan";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>

<div class="admin-wrapper">
    <!-- SIDEBAR NAVIGASI TERPADU -->
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <!-- KONTEN UTAMA -->
    <main class="admin-main">
        <!-- TOPBAR STICKY -->
        <header class="admin-topbar">
            <h1 class="topbar-title">Monitoring Pesanan (<?= $totalRows ?> Transaksi)</h1>
            <div class="topbar-actions">
                <a href="pesanan.php" class="btn btn-secondary btn-sm">Refresh Data</a>
            </div>
        </header>

        <div class="admin-content">
            <!-- FLASH NOTIFIKASI -->
            <?php if ($successMsg = flash_get('success')): ?>
                <div class="flash-msg success"><span>✓</span> <?= htmlspecialchars($successMsg) ?></div>
            <?php elseif (!empty($_SESSION['flash_success'])): ?>
                <div class="flash-msg success"><span>✓</span> <?= htmlspecialchars($_SESSION['flash_success']) ?></div>
                <?php unset($_SESSION['flash_success']); ?>
            <?php endif; ?>

            <?php if ($errorMsg = flash_get('error')): ?>
                <div class="flash-msg error"><span>⚠️</span> <?= htmlspecialchars($errorMsg) ?></div>
            <?php elseif (!empty($_SESSION['flash_error'])): ?>
                <div class="flash-msg error"><span>⚠️</span> <?= htmlspecialchars($_SESSION['flash_error']) ?></div>
                <?php unset($_SESSION['flash_error']); ?>
            <?php endif; ?>

            <!-- [BAGIAN: 6 KARTU DASHBOARD MONITORING PESANAN (BERTUMPUK 3 3 SERUPA DASHBOARD)] -->
            <div class="stats-grid order-monitoring-grid">
                <!-- 1. TOTAL SEMUA PESANAN -->
                <a href="pesanan.php" class="stat-card primary <?= ($filterOrderStatus === '' && $filterPaymentStatus === '') ? 'stat-card-active' : '' ?>" style="text-decoration: none; color: inherit;">
                    <span class="stat-label">Total Semua Pesanan</span>
                    <span class="stat-number"><?= $countAllOrders ?></span>
                </a>

                <!-- 2. MENUNGGU PEMBAYARAN -->
                <a href="pesanan.php?order_status=Menunggu+Pembayaran" class="stat-card warning <?= ($filterOrderStatus === 'Menunggu Pembayaran') ? 'stat-card-active' : '' ?>" style="text-decoration: none; color: inherit;">
                    <span class="stat-label">Menunggu Pembayaran</span>
                    <span class="stat-number" style="color: var(--status-warning-text);"><?= $countMenunggu ?></span>
                </a>

                <!-- 3. SEDANG DIPROSES -->
                <a href="pesanan.php?order_status=Diproses" class="stat-card info <?= ($filterOrderStatus === 'Diproses') ? 'stat-card-active' : '' ?>" style="text-decoration: none; color: inherit;">
                    <span class="stat-label">Sedang Diproses</span>
                    <span class="stat-number" style="color: var(--status-info-text);"><?= $countDiproses ?></span>
                </a>

                <!-- 4. SEDANG DIKIRIM -->
                <a href="pesanan.php?order_status=Dikirim" class="stat-card info <?= ($filterOrderStatus === 'Dikirim') ? 'stat-card-active' : '' ?>" style="text-decoration: none; color: inherit;">
                    <span class="stat-label">Sedang Dikirim</span>
                    <span class="stat-number" style="color: #60A5FA;"><?= $countDikirim ?></span>
                </a>

                <!-- 5. SELESAI -->
                <a href="pesanan.php?order_status=Selesai" class="stat-card success <?= ($filterOrderStatus === 'Selesai') ? 'stat-card-active' : '' ?>" style="text-decoration: none; color: inherit;">
                    <span class="stat-label">Selesai</span>
                    <span class="stat-number" style="color: var(--status-success-text);"><?= $countSelesai ?></span>
                </a>

                <!-- 6. DIBATALKAN -->
                <a href="pesanan.php?order_status=Dibatalkan" class="stat-card danger <?= ($filterOrderStatus === 'Dibatalkan') ? 'stat-card-active' : '' ?>" style="text-decoration: none; color: inherit;">
                    <span class="stat-label">Dibatalkan</span>
                    <span class="stat-number" style="color: var(--status-danger-text);"><?= $countDibatalkan ?></span>
                </a>
            </div>

            <!-- FILTER & SEARCH BAR TERPADU (Pure GET - No JS) -->
            <section class="filter-card">
                <form method="GET" action="pesanan.php">
                    <div class="filter-grid">
                        <div class="filter-group">
                            <label for="q">Pencarian Pesanan</label>
                            <input type="text" id="q" name="q" class="form-control" placeholder="Kode order, nama, no HP..." value="<?= htmlspecialchars($search) ?>">
                        </div>

                        <div class="filter-group">
                            <label for="order_status">Status Pesanan</label>
                            <select id="order_status" name="order_status" class="form-control">
                                <option value="">-- Semua Status Pesanan --</option>
                                <option value="Menunggu Pembayaran" <?= $filterOrderStatus === 'Menunggu Pembayaran' ? 'selected' : '' ?>>Menunggu Pembayaran</option>
                                <option value="Diproses" <?= $filterOrderStatus === 'Diproses' ? 'selected' : '' ?>>Diproses</option>
                                <option value="Dikirim" <?= $filterOrderStatus === 'Dikirim' ? 'selected' : '' ?>>Dikirim</option>
                                <option value="Selesai" <?= $filterOrderStatus === 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                                <option value="Dibatalkan" <?= $filterOrderStatus === 'Dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="payment_status">Status Pembayaran</label>
                            <select id="payment_status" name="payment_status" class="form-control">
                                <option value="">-- Semua Status Pembayaran --</option>
                                <option value="Menunggu Pembayaran" <?= $filterPaymentStatus === 'Menunggu Pembayaran' ? 'selected' : '' ?>>Menunggu Pembayaran</option>
                                <option value="Menunggu Konfirmasi" <?= $filterPaymentStatus === 'Menunggu Konfirmasi' ? 'selected' : '' ?>>Menunggu Konfirmasi</option>
                                <option value="Dibayar" <?= $filterPaymentStatus === 'Dibayar' ? 'selected' : '' ?>>Dibayar (Lunas)</option>
                                <option value="Selesai" <?= $filterPaymentStatus === 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                                <option value="Dibatalkan" <?= $filterPaymentStatus === 'Dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                            </select>
                        </div>

                        <div style="display: flex; gap: 8px;">
                            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                            <?php if ($search !== '' || $filterOrderStatus !== '' || $filterPaymentStatus !== ''): ?>
                                <a href="pesanan.php" class="btn btn-secondary btn-sm">Reset</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </section>

            <!-- TABEL PESANAN RAPI -->
            <section class="table-card">
                <div class="table-header">
                    <h2 class="table-title">Daftar Transaksi Pesanan</h2>
                    <span style="font-size: 12px; color: var(--mu);">Menampilkan <?= count($orders) ?> dari <?= $totalRows ?> total transaksi</span>
                </div>

                <?php if (empty($orders)): ?>
                    <div class="empty-box">
                        <span class="empty-box-icon">📦</span>
                        <h3 class="empty-box-title">Tidak Ada Pesanan Ditemukan</h3>
                        <p class="empty-box-desc">Tidak ada data pesanan yang sesuai dengan filter atau kata kunci pencarian kamu.</p>
                        <a href="pesanan.php" class="btn btn-secondary btn-sm">Reset Filter Pencarian</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive" style="overflow-x: hidden;">
                        <table class="admin-table order-monitoring-table">
                            <thead>
                                <tr>
                                    <th style="width: 17%;">KODE & TANGGAL</th>
                                    <th style="width: 17%;">PEMBELI</th>
                                    <th style="width: 13%;">ITEM & TOTAL</th>
                                    <th style="width: 14%;">PEMBAYARAN</th>
                                    <th style="width: 13%;">STATUS PESANAN</th>
                                    <th style="width: 14%;">EKSPEDISI / RESI</th>
                                    <th style="width: 12%; text-align: right;">AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $o): ?>
                                    <?php
                                        $orderCode = !empty($o['order_code']) ? $o['order_code'] : ($o['order_number'] ?: ('ORD-' . $o['id']));
                                        $buyerName = !empty($o['recipient_name']) ? $o['recipient_name'] : ($o['user_name'] ?: 'Pelanggan');
                                        $orderTotal = (float)($o['total'] ?: $o['total_price'] ?: 0);
                                        $orderStatus = $o['order_status'] ?: ($o['status'] ?: 'Menunggu Pembayaran');
                                        $payStatus = $o['payment_status'] ?: 'Menunggu Pembayaran';
                                        $badgeOrderClass = $statusOrderBadges[$orderStatus] ?? 'status-muted';
                                        $badgePayClass = $statusPaymentBadges[$payStatus] ?? 'status-muted';

                                        // Bersihkan nomor HP untuk link WhatsApp
                                        $cleanPhone = preg_replace('/[^0-9]/', '', $o['recipient_phone'] ?? '');
                                        if (str_starts_with($cleanPhone, '0')) $cleanPhone = '62' . substr($cleanPhone, 1);
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="order-code-badge" style="font-size: 11px;"><?= htmlspecialchars($orderCode) ?></span>
                                            <span class="meta-sub" style="font-size: 10.5px;"><?= date('d M Y, H:i', strtotime($o['created_at'])) ?> WIB</span>
                                        </td>
                                        <td>
                                            <strong style="color: var(--tx); font-size: 12px;"><?= htmlspecialchars($buyerName) ?></strong>
                                            <?php if (!empty($o['recipient_phone'])): ?>
                                                <span class="meta-sub" style="font-size: 10.5px;">
                                                    📞 <?= htmlspecialchars($o['recipient_phone']) ?>
                                                    <?php if (!empty($cleanPhone)): ?>
                                                        &bull; <a href="https://wa.me/<?= $cleanPhone ?>" target="_blank" rel="noopener" style="color: var(--ac);">WA ↗</a>
                                                    <?php endif; ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if (!empty($o['recipient_city'])): ?>
                                                <span class="meta-sub" style="font-size: 10.5px;">📍 <?= htmlspecialchars($o['recipient_city']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong style="color: var(--ac); font-size: 13px; font-family: var(--display-font);"><?= formatRupiah($orderTotal) ?></strong>
                                            <span class="meta-sub" style="font-size: 10.5px;"><?= (int)$o['total_items'] ?> item</span>
                                        </td>
                                        <td>
                                            <span class="badge-status <?= $badgePayClass ?>" style="font-size: 9.5px; padding: 2px 6px; white-space: nowrap;"><?= htmlspecialchars(strtoupper($payStatus)) ?></span>
                                            <span class="meta-sub" style="font-size: 10.5px;"><?= htmlspecialchars($o['payment_method'] ?? 'Transfer') ?></span>
                                            <?php if (!empty($o['proof_image'])): ?>
                                                <span class="badge-status status-info" style="font-size: 8.5px; padding: 1px 5px; margin-top: 3px; white-space: nowrap;">📸 BUKTI ADA</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge-status <?= $badgeOrderClass ?>" style="font-size: 9.5px; padding: 2px 6px; white-space: nowrap;"><?= htmlspecialchars(strtoupper($orderStatus)) ?></span>
                                        </td>
                                        <td>
                                            <?php if (!empty($o['tracking_number'])): ?>
                                                <span class="resi-tag" style="font-size: 10px; padding: 2px 6px;"><?= htmlspecialchars($o['tracking_number']) ?></span>
                                                <span class="meta-sub" style="font-size: 10.5px;"><?= htmlspecialchars($o['shipping_courier'] ?? 'Kurir') ?></span>
                                            <?php else: ?>
                                                <span style="color: var(--mu); font-size: 11px;">- Belum Ada Resi -</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-right">
                                            <a href="detail_pesanan.php?id=<?= (int)$o['id'] ?>" class="btn btn-primary btn-sm" style="height: 28px; padding: 0 10px; font-size: 10px; line-height: 26px; white-space: nowrap;">
                                                Detail & Kelola &rarr;
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- PAGINATION RAPI (Pure GET - No JS) -->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination-wrap">
                            <span class="pagination-stats">
                                Menampilkan halaman <strong><?= $page ?></strong> dari <strong><?= $totalPages ?></strong> (Total <?= $totalRows ?> Pesanan)
                            </span>
                            <div class="pagination-btns">
                                <?php if ($page > 1): ?>
                                    <a href="pesanan.php?q=<?= urlencode($search) ?>&order_status=<?= urlencode($filterOrderStatus) ?>&payment_status=<?= urlencode($filterPaymentStatus) ?>&page=<?= $page - 1 ?>" class="page-btn">&laquo; Prev</a>
                                <?php endif; ?>

                                <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                                    <a href="pesanan.php?q=<?= urlencode($search) ?>&order_status=<?= urlencode($filterOrderStatus) ?>&payment_status=<?= urlencode($filterPaymentStatus) ?>&page=<?= $p ?>" class="page-btn <?= $p === $page ? 'active' : '' ?>">
                                        <?= $p ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if ($page < $totalPages): ?>
                                    <a href="pesanan.php?q=<?= urlencode($search) ?>&order_status=<?= urlencode($filterOrderStatus) ?>&payment_status=<?= urlencode($filterPaymentStatus) ?>&page=<?= $page + 1 ?>" class="page-btn">Next &raquo;</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </section>

        </div>
    </main>
</div>

</body>
</html>
