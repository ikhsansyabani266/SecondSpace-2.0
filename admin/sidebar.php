<?php
// [FILE] sidebar.php — Komponen Navigasi Sidebar Admin Terpadu (100% No-JS)
// Dipakai oleh: Seluruh halaman modul admin

require_once __DIR__ . '/../shared/config.php';
require_once __DIR__ . '/../shared/koneksi.php';
require_once __DIR__ . '/../shared/fungsi.php';

// Ambil angka notifikasi server-side
$badgeMenunggu = kpi_menunggu_verifikasi($conn);

$activePage = basename($_SERVER['PHP_SELF']);
?>
<!-- [BAGIAN: Sidebar Dashboard Admin] -->
<aside class="admin-sidebar">
    <div class="sidebar-header">
        <div>
            <div class="sidebar-logo">SECOND<span style="color: var(--ac);">SPΛCE</span><span style="font-size: 19px; font-weight: 900; margin-left: 5px; color: var(--tx); vertical-align: middle;">®</span></div>
            <span class="sidebar-role-badge">ADMIN PANEL</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="sidebar-group-title">Menu Utama</div>

        <a href="index.php" class="nav-item <?= $activePage === 'index.php' ? 'active' : '' ?>">
            <div class="nav-item-left">
                <span class="nav-icon">[D]</span>
                <span>Dashboard</span>
            </div>
        </a>

        <a href="pesanan.php" class="nav-item <?= in_array($activePage, ['pesanan.php', 'detail_pesanan.php']) ? 'active' : '' ?>">
            <div class="nav-item-left">
                <span class="nav-icon">[P]</span>
                <span>Pesanan</span>
            </div>
            <?php if ($badgeMenunggu > 0): ?>
                <span class="nav-badge"><?= $badgeMenunggu ?></span>
            <?php endif; ?>
        </a>

        <a href="riwayat.php" class="nav-item <?= in_array($activePage, ['riwayat.php', 'stok.php']) ? 'active' : '' ?>">
            <div class="nav-item-left">
                <span class="nav-icon">[R]</span>
                <span>Riwayat</span>
            </div>
        </a>

        <div class="sidebar-group-title">Katalog</div>

        <a href="produk.php" class="nav-item <?= in_array($activePage, ['produk.php', 'produk_form.php', 'kategori.php']) ? 'active' : '' ?>">
            <div class="nav-item-left">
                <span class="nav-icon">[K]</span>
                <span>Manajemen Produk</span>
            </div>
        </a>

        <a href="../user/index.php" target="_blank" rel="noopener" class="nav-item" style="margin-top: 16px; border: 1px dashed var(--ln);">
            <div class="nav-item-left">
                <span class="nav-icon">[W]</span>
                <span>Buka Toko User &UpperRightArrow;</span>
            </div>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="admin-user-info">
            <div class="admin-user-name"><?= htmlspecialchars($_SESSION['name'] ?? 'Administrator') ?></div>
            <div class="admin-user-sub">@<?= htmlspecialchars($_SESSION['username'] ?? 'admin') ?></div>
        </div>
        <a href="logout.php" class="btn btn-secondary btn-sm" title="Keluar">
            Keluar ⎋
        </a>
    </div>
</aside>
<!-- [/BAGIAN: Sidebar Dashboard Admin] -->
