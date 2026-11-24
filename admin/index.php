<?php
// [FILE] index.php — Dashboard Utama Pengelola Toko (Admin)
// Dipakai oleh: Administrator SecondSpace
// Tabel database disentuh: products, orders, order_items, contacts
// Fungsi bersama: /shared/config.php, /shared/koneksi.php, /shared/fungsi.php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../shared/koneksi.php';
require_once __DIR__ . '/../shared/fungsi.php';

// [BAGIAN: Parameter Filter Laporan Penjualan (GET - Zero JS)]
$period = trim($_GET['period'] ?? '');
$startDate = trim($_GET['start_date'] ?? '');
$endDate = trim($_GET['end_date'] ?? '');
$selectedMethod = trim($_GET['method'] ?? '');

// Helper URL Generator untuk mempertahankan kombinasi filter waktu & metode bayar
function build_report_url($params = []) {
    $current = [
        'period'     => $_GET['period'] ?? '',
        'start_date' => $_GET['start_date'] ?? '',
        'end_date'   => $_GET['end_date'] ?? '',
        'method'     => $_GET['method'] ?? ''
    ];
    foreach ($params as $k => $v) {
        if ($v === null || $v === '') {
            unset($current[$k]);
        } else {
            $current[$k] = $v;
        }
    }
    $current = array_filter($current, fn($val) => $val !== '');
    $qs = http_build_query($current);
    return 'index.php' . ($qs ? ('?' . $qs) : '');
}

// Logika Kondisi Tanggal
$dateCondition = "1=1";
$periodLabel = "Semua Waktu";

if ($startDate !== '' || $endDate !== '') {
    $period = 'custom';
    if ($startDate !== '' && $endDate !== '') {
        $cleanStart = preg_replace('/[^0-9\-]/', '', $startDate);
        $cleanEnd = preg_replace('/[^0-9\-]/', '', $endDate);
        $dateCondition = "DATE(o.created_at) BETWEEN '{$cleanStart}' AND '{$cleanEnd}'";
        $periodLabel = date('d M Y', strtotime($startDate)) . " s/d " . date('d M Y', strtotime($endDate));
    } elseif ($startDate !== '') {
        $cleanStart = preg_replace('/[^0-9\-]/', '', $startDate);
        $dateCondition = "DATE(o.created_at) >= '{$cleanStart}'";
        $periodLabel = "Mulai " . date('d M Y', strtotime($startDate));
    } elseif ($endDate !== '') {
        $cleanEnd = preg_replace('/[^0-9\-]/', '', $endDate);
        $dateCondition = "DATE(o.created_at) <= '{$cleanEnd}'";
        $periodLabel = "Sampai " . date('d M Y', strtotime($endDate));
    }
} elseif ($period === 'today') {
    $dateCondition = "DATE(o.created_at) = CURDATE()";
    $periodLabel = "Hari Ini (" . date('d M Y') . ")";
} elseif ($period === '7days') {
    $dateCondition = "o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $periodLabel = "7 Hari Terakhir";
} elseif ($period === 'month') {
    $dateCondition = "YEAR(o.created_at) = YEAR(CURDATE()) AND MONTH(o.created_at) = MONTH(CURDATE())";
    $periodLabel = "Bulan Ini (" . date('F Y') . ")";
}

// [BAGIAN: Definisi Tunggal Penjualan Sah / Lunas / Selesai]
// Membedakan Laporan Penjualan (Omzet Terverifikasi) dari Halaman Pesanan (Monitoring Operasional)
$salesBaseCondition = "(o.payment_status IN ('Dibayar', 'Terverifikasi', 'Selesai') OR o.order_status = 'Selesai')";

// [QUERY: Penjualan Harian, Mingguan, Bulanan, dan Total Akumulatif (Khusus Penjualan Lunas)]
// 1. Penjualan Hari Ini
$qHariIni = $conn->query("
    SELECT COALESCE(SUM(total), 0) AS omzet, COUNT(*) AS count
    FROM orders o
    WHERE DATE(o.created_at) = CURDATE()
      AND {$salesBaseCondition}
");
$salesHariIni = $qHariIni ? $qHariIni->fetch_assoc() : ['omzet' => 0, 'count' => 0];

// 2. Penjualan Minggu Ini (dimulai dari Senin pekan ini)
$qMingguIni = $conn->query("
    SELECT COALESCE(SUM(total), 0) AS omzet, COUNT(*) AS count
    FROM orders o
    WHERE YEARWEEK(o.created_at, 1) = YEARWEEK(CURDATE(), 1)
      AND {$salesBaseCondition}
");
$salesMingguIni = $qMingguIni ? $qMingguIni->fetch_assoc() : ['omzet' => 0, 'count' => 0];

// 3. Penjualan Bulan Ini
$qBulanIni = $conn->query("
    SELECT COALESCE(SUM(total), 0) AS omzet, COUNT(*) AS count
    FROM orders o
    WHERE YEAR(o.created_at) = YEAR(CURDATE())
      AND MONTH(o.created_at) = MONTH(CURDATE())
      AND {$salesBaseCondition}
");
$salesBulanIni = $qBulanIni ? $qBulanIni->fetch_assoc() : ['omzet' => 0, 'count' => 0];

// 4. Total Seluruh Omzet Penjualan
$qTotalSales = $conn->query("
    SELECT COALESCE(SUM(total), 0) AS omzet, COUNT(*) AS count
    FROM orders o
    WHERE {$salesBaseCondition}
");
$salesTotal = $qTotalSales ? $qTotalSales->fetch_assoc() : ['omzet' => 0, 'count' => 0];

// [DEFINISI: 4 Metode Pembayaran Resmi Toko SecondSpace]
$officialPaymentMethods = [
    'Transfer BCA' => [
        'label'   => 'Transfer BCA',
        'aliases' => ['BCA Transfer', 'Transfer Bank', 'Transfer BCA', 'Transfer', 'BCA']
    ],
    'QRIS' => [
        'label'   => 'QRIS',
        'aliases' => ['QRIS', 'QRIS Dinamis', 'QRIS Standar']
    ],
    'E-Wallet' => [
        'label'   => 'E-Wallet',
        'aliases' => ['E-Wallet', 'EWallet', 'DANA', 'GoPay', 'OVO', 'ShopeePay']
    ],
    'COD' => [
        'label'   => 'COD',
        'aliases' => ['COD', 'Cash on Delivery', 'Bayar di Tempat']
    ]
];

// Inisialisasi setiap metode pembayaran resmi (selalu tampil meskipun omzet 0)
$methodStats = [];
foreach ($officialPaymentMethods as $mKey => $mMeta) {
    $methodStats[$mKey] = [
        'label' => $mMeta['label'],
        'omzet' => 0,
        'count' => 0
    ];
}

// [QUERY: Rekapitulasi Bar Metode Pembayaran - Mengikuti Periode Waktu Aktif]
$whereMetode = [$salesBaseCondition];
if ($dateCondition !== "1=1") {
    $whereMetode[] = $dateCondition;
}
$whereMetodeSql = implode(" AND ", $whereMetode);

$qMetode = $conn->query("
    SELECT 
        o.payment_method,
        COUNT(*) as jml_transaksi,
        SUM(o.total) as omzet
    FROM orders o
    WHERE {$whereMetodeSql}
    GROUP BY o.payment_method
");

$totalPeriodOmzet = 0;
$totalPeriodCount = 0;

if ($qMetode) {
    while ($m = $qMetode->fetch_assoc()) {
        $pMethod = trim($m['payment_method'] ?? '');
        $mOmzet  = (float)$m['omzet'];
        $mJml    = (int)$m['jml_transaksi'];

        $totalPeriodOmzet += $mOmzet;
        $totalPeriodCount += $mJml;

        // Petakan ke salah satu dari 4 metode resmi
        $matched = false;
        foreach ($officialPaymentMethods as $mKey => $mMeta) {
            foreach ($mMeta['aliases'] as $alias) {
                if (strcasecmp($pMethod, $alias) === 0 || stripos($pMethod, $alias) !== false) {
                    $methodStats[$mKey]['omzet'] += $mOmzet;
                    $methodStats[$mKey]['count'] += $mJml;
                    $matched = true;
                    break 2;
                }
            }
        }
        if (!$matched) {
            $methodStats['Transfer BCA']['omzet'] += $mOmzet;
            $methodStats['Transfer BCA']['count'] += $mJml;
        }
    }
}

// [QUERY: Transaksi Penjualan Terfilter Berdasarkan Periode & Metode Pembayaran]
$whereSales = [$salesBaseCondition];
$salesParams = [];
$salesTypes = "";

if ($dateCondition !== "1=1") {
    $whereSales[] = $dateCondition;
}

if ($selectedMethod !== '') {
    if (isset($officialPaymentMethods[$selectedMethod])) {
        $aliases = $officialPaymentMethods[$selectedMethod]['aliases'];
        $placeholders = implode(',', array_fill(0, count($aliases), '?'));
        $whereSales[] = "(o.payment_method IN ($placeholders) OR o.payment_method LIKE ?)";
        foreach ($aliases as $al) {
            $salesParams[] = $al;
            $salesTypes .= "s";
        }
        $salesParams[] = "%" . $officialPaymentMethods[$selectedMethod]['label'] . "%";
        $salesTypes .= "s";
    } else {
        $whereSales[] = "o.payment_method = ?";
        $salesParams[] = $selectedMethod;
        $salesTypes .= "s";
    }
}

$whereSalesSql = implode(" AND ", $whereSales);

// Ringkasan hasil filter
$summaryStmt = $conn->prepare("
    SELECT COALESCE(SUM(o.total), 0) AS filtered_omzet, COUNT(o.id) AS filtered_count
    FROM orders o
    WHERE {$whereSalesSql}
");
if (!empty($salesParams)) {
    $summaryStmt->bind_param($salesTypes, ...$salesParams);
}
$summaryStmt->execute();
$filteredSummary = $summaryStmt->get_result()->fetch_assoc();
$summaryStmt->close();

// Daftar transaksi laporan penjualan (maksimal 50 data terbaru)
$salesStmt = $conn->prepare("
    SELECT o.id, o.order_code, o.total, o.order_status, o.payment_status, o.payment_method, o.created_at, u.name as customer_name
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    WHERE {$whereSalesSql}
    ORDER BY o.id DESC
    LIMIT 50
");
if (!empty($salesParams)) {
    $salesStmt->bind_param($salesTypes, ...$salesParams);
}
$salesStmt->execute();
$salesRes = $salesStmt->get_result();
$salesOrders = [];
while ($so = $salesRes->fetch_assoc()) {
    $salesOrders[] = $so;
}
$salesStmt->close();

// [QUERY: Produk Preloved yang Sold Out atau Stok 0]
$qSold = $conn->query("
    SELECT id, sku, name, category, price, image
    FROM products
    WHERE stock <= 0 AND is_demo = 0
    ORDER BY id DESC
    LIMIT 5
");
$soldOutProducts = [];
if ($qSold) {
    while ($r = $qSold->fetch_assoc()) {
        $soldOutProducts[] = $r;
    }
}
?>
<!-- [TAMPILAN: Dashboard Admin SecondSpace] -->
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — <?= NAMA_TOKO ?> Admin</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>

<div class="admin-wrapper">
    <!-- Komponen Sidebar -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="admin-main">
        <!-- [BAGIAN: Topbar] -->
        <header class="admin-topbar">
            <h1 class="topbar-title">Dashboard</h1>
        </header>
        <!-- [/BAGIAN: Topbar] -->

        <div class="admin-content">
            <!-- [BAGIAN: Flash Messages] -->
            <?php if ($successMsg = flash_get('success')): ?>
                <div class="flash-msg success">
                    <strong>[OK]</strong>  <?= htmlspecialchars($successMsg) ?>
                </div>
            <?php endif; ?>
            <?php if ($errorMsg = flash_get('error')): ?>
                <div class="flash-msg error">
                    <strong>[!]</strong>  <?= htmlspecialchars($errorMsg) ?>
                </div>
            <?php endif; ?>
            <!-- [/BAGIAN: Flash Messages] -->

            <!-- [BAGIAN: Kartu Statistik Penjualan (Harian, Mingguan, Bulanan, Total)] -->
            <div class="stats-grid">
                <div class="stat-card primary">
                    <div class="stat-label">Penjualan Hari Ini</div>
                    <div class="stat-number" style="color: var(--ac); font-size: 26px;"><?= format_rupiah($salesHariIni['omzet']) ?></div>
                    <div style="font-size: 12px; color: var(--mu); margin-top: 6px;">
                        <?= (int)$salesHariIni['count'] ?> transaksi hari ini
                    </div>
                </div>

                <div class="stat-card info">
                    <div class="stat-label">Penjualan Minggu Ini</div>
                    <div class="stat-number" style="color: var(--status-info-text); font-size: 26px;"><?= format_rupiah($salesMingguIni['omzet']) ?></div>
                    <div style="font-size: 12px; color: var(--mu); margin-top: 6px;">
                        <?= (int)$salesMingguIni['count'] ?> transaksi pekan ini
                    </div>
                </div>

                <div class="stat-card warning">
                    <div class="stat-label">Penjualan Bulan Ini</div>
                    <div class="stat-number" style="color: var(--status-warning-text); font-size: 26px;"><?= format_rupiah($salesBulanIni['omzet']) ?></div>
                    <div style="font-size: 12px; color: var(--mu); margin-top: 6px;">
                        <?= (int)$salesBulanIni['count'] ?> transaksi <?= date('M Y') ?>
                    </div>
                </div>

                <div class="stat-card success">
                    <div class="stat-label">Total Omzet Penjualan</div>
                    <div class="stat-number" style="color: var(--status-success-text); font-size: 26px;"><?= format_rupiah($salesTotal['omzet']) ?></div>
                    <div style="font-size: 12px; color: var(--mu); margin-top: 6px;">
                        <?= (int)$salesTotal['count'] ?> total transaksi berhasil
                    </div>
                </div>
            </div>
            <!-- [/BAGIAN: Kartu Statistik Penjualan] -->

            <!-- [BAGIAN: Laporan Penjualan (Interaktif Filter Periode, Tanggal & Metode Bayar)] -->
            <div class="table-card">
                <div class="table-header">
                    <div>
                        <h2 class="table-title">Laporan Penjualan</h2>
                        <span style="font-size: 12px; color: var(--mu);">Rekapitulasi performa transaksi penjualan dan omzet toko</span>
                    </div>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <a href="pesanan.php" class="btn btn-secondary btn-sm">Monitoring Pesanan &rarr;</a>
                    </div>
                </div>

                <!-- 1. TOOLBAR FILTER PERIODE & PEMILIH TANGGAL (FULL WIDTH: KIRI PERIODE, KANAN TANGGAL) -->
                <div class="report-filters-bar">
                    <!-- Bagian Periode Cepat (Kiri) -->
                    <div class="filter-section-quick">
                        <span class="filter-group-label">PERIODE:</span>
                        <a href="<?= build_report_url(['period' => '', 'start_date' => '', 'end_date' => '']) ?>" 
                           class="period-btn <?= ($period === '' && empty($startDate) && empty($endDate)) ? 'active' : '' ?>">
                            Semua Waktu
                        </a>
                        <a href="<?= build_report_url(['period' => 'today', 'start_date' => '', 'end_date' => '']) ?>" 
                           class="period-btn <?= ($period === 'today') ? 'active' : '' ?>">
                            Hari Ini
                        </a>
                        <a href="<?= build_report_url(['period' => '7days', 'start_date' => '', 'end_date' => '']) ?>" 
                           class="period-btn <?= ($period === '7days') ? 'active' : '' ?>">
                            7 Hari
                        </a>
                        <a href="<?= build_report_url(['period' => 'month', 'start_date' => '', 'end_date' => '']) ?>" 
                           class="period-btn <?= ($period === 'month') ? 'active' : '' ?>">
                            Bulan Ini
                        </a>
                    </div>

                    <!-- Bagian Pemilih Rentang Tanggal Kustom (Kanan) -->
                    <form method="GET" action="index.php" class="filter-section-custom">
                        <?php if (!empty($selectedMethod)): ?>
                            <input type="hidden" name="method" value="<?= htmlspecialchars($selectedMethod) ?>">
                        <?php endif; ?>
                        <input type="hidden" name="period" value="custom">
                        <span class="filter-group-label">RENTANG:</span>
                        <div class="date-input-wrap">
                            <label for="start_date">Dari:</label>
                            <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" class="date-input" required>
                        </div>
                        <div class="date-input-wrap">
                            <label for="end_date">Sampai:</label>
                            <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" class="date-input" required>
                        </div>
                        <button type="submit" class="btn btn-secondary btn-sm filter-submit-btn">
                            Periksa
                        </button>
                        <?php if ($period !== '' || !empty($startDate) || !empty($endDate) || !empty($selectedMethod)): ?>
                            <a href="index.php" class="btn-reset-filter">Reset</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- 2. BAR METODE PEMBAYARAN INTERAKTIF (1 BARIS PENUH TANPA BERTUMPUK) -->
                <div class="method-chips-bar">
                    <span class="method-chips-label">
                        METODE BAYAR:
                    </span>
                    <!-- Tombol Semua Metode -->
                    <a href="<?= build_report_url(['method' => '']) ?>" class="method-chip-btn <?= empty($selectedMethod) ? 'active' : '' ?>">
                        <span class="method-title">Semua Metode</span>
                        <span class="method-omzet"><?= format_rupiah($totalPeriodOmzet) ?></span>
                        <span class="method-count">(<?= $totalPeriodCount ?>x)</span>
                    </a>

                    <!-- Tombol 4 Metode Resmi Toko: Transfer BCA, QRIS, E-Wallet, COD -->
                    <?php foreach ($methodStats as $mKey => $ms): ?>
                        <?php $isAct = ($selectedMethod === $mKey); ?>
                        <a href="<?= build_report_url(['method' => $mKey]) ?>" class="method-chip-btn <?= $isAct ? 'active' : '' ?>">
                            <span class="method-title"><?= htmlspecialchars($ms['label']) ?></span>
                            <span class="method-omzet"><?= format_rupiah($ms['omzet']) ?></span>
                            <span class="method-count">(<?= (int)$ms['count'] ?>x)</span>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- 3. STRIP RINGKASAN HASIL FILTER -->
                <div class="report-summary-strip">
                    <span>
                        Menampilkan <strong><?= (int)$filteredSummary['filtered_count'] ?> transaksi</strong> 
                        &bull; Periode: <strong style="color: var(--tx);"><?= htmlspecialchars($periodLabel) ?></strong>
                        <?php if (!empty($selectedMethod)): ?>
                            &bull; Metode: <strong style="color: var(--ac);"><?= htmlspecialchars($selectedMethod) ?></strong>
                        <?php endif; ?>
                    </span>
                    <span style="font-family: var(--display-font); font-size: 14px; font-weight: 800; letter-spacing: 0.05em; color: var(--ac);">
                        TOTAL OMZET: <?= format_rupiah($filteredSummary['filtered_omzet']) ?>
                    </span>
                </div>

                <!-- 4. TABEL TRANSAKSI HASIL FILTER (FIXED SIZE PAS LAYAR TANPA PERLU GESER-GESER) -->
                <div class="table-responsive" style="overflow-x: hidden;">
                    <table class="admin-table sales-report-table">
                        <thead>
                            <tr>
                                <th style="width: 20%;">No. Transaksi</th>
                                <th style="width: 16%;">Waktu Lunas</th>
                                <th style="width: 14%;">Pelanggan</th>
                                <th style="width: 13%;">Metode Bayar</th>
                                <th style="width: 13%;">Status Finansial</th>
                                <th style="width: 13%;">Nilai Omzet</th>
                                <th style="width: 11%; text-align: right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($salesOrders)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; color: var(--mu); padding: 40px 20px;">
                                        <div style="font-size: 14px; font-weight: bold; margin-bottom: 8px; color: var(--accent);">[RINGKASAN]</div>
                                        <div style="font-weight: 700; color: var(--tx); font-size: 14px; margin-bottom: 4px;">Tidak Ada Transaksi Penjualan Terverifikasi</div>
                                        <div style="font-size: 13px; color: var(--mu); margin-bottom: 14px;">
                                            Tabel laporan penjualan ini khusus mencatat transaksi yang sudah dibayar/lunas. Pesanan yang belum bayar atau sedang diproses kurir dapat dipantau di halaman pesanan.
                                        </div>
                                        <a href="pesanan.php" class="btn btn-secondary btn-sm">Buka Halaman Monitoring Pesanan &rarr;</a>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($salesOrders as $so): ?>
                                    <tr>
                                        <td><strong style="font-size: 11px; word-break: break-all;"><?= htmlspecialchars($so['order_code']) ?></strong></td>
                                        <td><span style="font-size: 11px; white-space: nowrap;"><?= date('d M Y, H:i', strtotime($so['created_at'])) ?> WIB</span></td>
                                        <td><span style="font-size: 11.5px;"><?= htmlspecialchars($so['customer_name'] ?? 'Pelanggan') ?></span></td>
                                        <td>
                                            <span style="font-weight: 700; color: var(--tx); font-size: 11.5px;">
                                                <?= htmlspecialchars($so['payment_method'] ?? 'Transfer') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge-status status-success" style="font-size: 9.5px; padding: 2px 6px; font-weight: 800; white-space: nowrap;">
                                                LUNAS
                                            </span>
                                        </td>
                                        <td><strong style="color: var(--ac); font-size: 13px; font-family: var(--display-font);"><?= format_rupiah($so['total']) ?></strong></td>
                                        <td style="text-align: right;">
                                            <a href="detail_pesanan.php?id=<?= $so['id'] ?>" class="btn btn-secondary btn-sm" style="height: 26px; padding: 0 8px; font-size: 10px; line-height: 24px; white-space: nowrap;">Detail & Faktur</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- [/BAGIAN: Laporan Penjualan] -->

            <!-- [BAGIAN: Tabel Sold Out Products] -->
            <div class="table-card">
                <div class="table-header">
                    <h2 class="table-title">Produk Preloved Terjual (Sold Out / Stok 0)</h2>
                    <a href="riwayat.php" class="btn btn-secondary btn-sm">Lihat Riwayat &rarr;</a>
                </div>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Foto</th>
                                <th>Nama Produk</th>
                                <th>Kategori</th>
                                <th>Harga</th>
                                <th>Status Stok</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($soldOutProducts)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; color: var(--mu); padding: 24px;">
                                        Saat ini semua produk memiliki persediaan aktif (1 stok).
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($soldOutProducts as $sp): ?>
                                    <tr>
                                        <td>
                                            <img src="../images/<?= htmlspecialchars($sp['image']) ?>" alt="Thumbnail" width="40" height="40" style="object-fit: cover; border-radius: 4px;">
                                        </td>
                                        <td><?= htmlspecialchars($sp['name']) ?></td>
                                        <td><?= htmlspecialchars($sp['category']) ?></td>
                                        <td><?= format_rupiah($sp['price']) ?></td>
                                        <td><span class="badge-status status-danger">TERJUAL (0)</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- [/BAGIAN: Tabel Sold Out Products] -->
        </div>
    </main>
</div>

</body>
</html>
<!-- [/TAMPILAN: Dashboard Admin SecondSpace] -->
