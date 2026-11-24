<?php
/**
 * [FILE]: admin/konfirmasi_hapus.php
 * [BAGIAN]: Halaman Konfirmasi Penghapusan Terpisah (No-JS Standard)
 * [DESKRIPSI]: Menggantikan dialog konfirmasi JavaScript dengan halaman konfirmasi murni PHP POST + CSRF
 * [ATURAN]: 100% PHP Native, TANPA JavaScript, Wajib Validasi CSRF Token
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../shared/koneksi.php';
require_once __DIR__ . '/../shared/fungsi.php';

/** @var mysqli $conn */

$tipe = trim($_GET['tipe'] ?? $_POST['tipe'] ?? '');
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if ($id <= 0 || !in_array($tipe, ['produk', 'pesan'], true)) {
    header("Location: index.php");
    exit;
}

/* [KEAMANAN]: Buat atau Ambil CSRF Token */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

/* [BAGIAN]: Ambil Data Entitas yang Ingin Dihapus */
$itemData = null;
$hasOrderConstraint = false;
$cancelUrl = "index.php";

if ($tipe === 'produk') {
    $cancelUrl = "produk.php";
    $stmt = $conn->prepare("SELECT id, name, price, category, stock, image FROM products WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $itemData = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$itemData) {
        $_SESSION['flash_error'] = "Produk tidak ditemukan.";
        header("Location: produk.php");
        exit;
    }

    // Cek apakah produk ini memiliki relasi di order_items
    $orderCheckStmt = $conn->prepare("SELECT COUNT(id) AS cnt FROM order_items WHERE product_id = ?");
    $orderCheckStmt->bind_param("i", $id);
    $orderCheckStmt->execute();
    $orderCount = (int)$orderCheckStmt->get_result()->fetch_assoc()['cnt'];
    $orderCheckStmt->close();

    if ($orderCount > 0) {
        $hasOrderConstraint = true;
    }

} elseif ($tipe === 'pesan') {
    $cancelUrl = "pesan.php";
    $stmt = $conn->prepare("SELECT id, name, email, message, created_at FROM contacts WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $itemData = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$itemData) {
        $_SESSION['flash_error'] = "Pesan tidak ditemukan.";
        header("Location: pesan.php");
        exit;
    }
}

/* [AKSI]: Pemrosesan Eksekusi Penghapusan via POST + CSRF */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        $_SESSION['flash_error'] = "Validasi token keamanan CSRF gagal.";
        header("Location: {$cancelUrl}");
        exit;
    }

    if ($tipe === 'produk') {
        if ($hasOrderConstraint) {
            // Jika ada pesanan, lakukan pengarsipan aman: set stok ke 0
            $archiveStmt = $conn->prepare("UPDATE products SET stock = 0 WHERE id = ?");
            $archiveStmt->bind_param("i", $id);
            if ($archiveStmt->execute()) {
                catat_riwayat_stok($conn, $id, (int)$itemData['stock'], 0, 'archive', 'Diarsipkan / Stok dinolkan oleh admin karena memiliki riwayat pesanan', (int)($_SESSION['admin_id'] ?? 1));
                $_SESSION['flash_success'] = "Produk '{$itemData['name']}' berhasil dinonaktifkan (stok diset ke 0) untuk menjaga keutuhan riwayat transaksi pelanggan.";
            } else {
                $_SESSION['flash_error'] = "Gagal menonaktifkan produk: " . $conn->error;
            }
            $archiveStmt->close();
        } else {
            // Jika tidak ada riwayat pesanan, aman dihapus permanen
            $delStmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            $delStmt->bind_param("i", $id);
            if ($delStmt->execute()) {
                $_SESSION['flash_success'] = "Produk '{$itemData['name']}' berhasil dihapus permanen.";
            } else {
                $_SESSION['flash_error'] = "Gagal menghapus produk: " . $conn->error;
            }
            $delStmt->close();
        }
        header("Location: produk.php");
        exit;

    } elseif ($tipe === 'pesan') {
        $delMsg = $conn->prepare("DELETE FROM contacts WHERE id = ?");
        $delMsg->bind_param("i", $id);
        if ($delMsg->execute()) {
            $_SESSION['flash_success'] = "Pesan dari {$itemData['name']} berhasil dihapus.";
        } else {
            $_SESSION['flash_error'] = "Gagal menghapus pesan: " . $conn->error;
        }
        $delMsg->close();
        header("Location: pesan.php");
        exit;
    }
}

$pageTitle = "Konfirmasi Hapus // SECONDSPACE ADMIN";
$currentNav = $tipe === 'produk' ? 'produk' : 'pesan';
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
        <header class="admin-topbar">
            <h1 class="topbar-title">Konfirmasi Penghapusan</h1>
            <div class="topbar-actions">
                <a href="<?= $cancelUrl ?>" class="btn btn-secondary btn-sm">&larr; Batal & Kembali</a>
            </div>
        </header>

        <div class="admin-content">

        <section class="card" style="max-width: 650px; margin: 40px auto; border-color: var(--danger);">
            <div style="text-align: center; margin-bottom: 24px;">
                <span style="font-size: 48px; display: block; margin-bottom: 12px;">⚠️</span>
                <h2 style="font-family: var(--font-heading); color: var(--danger); font-size: 24px;">
                    YAKIN INGIN MENGHAPUS <?= strtoupper($tipe) ?> INI?
                </h2>
            </div>

            <!-- RINCIAN DATA YANG AKAN DIHAPUS -->
            <div style="background: var(--bg); border: 1px solid var(--ln); border-radius: var(--radius-sm); padding: 20px; margin-bottom: 24px;">
                <?php if ($tipe === 'produk'): ?>
                    <div style="display: flex; gap: 16px; align-items: center;">
                        <div style="width: 64px; height: 64px; background: #222; border-radius: var(--radius-sm); overflow: hidden; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <?php if (!empty($itemData['image']) && file_exists(__DIR__ . '/../uploads/produk/' . $itemData['image'])): ?>
                                <img src="../uploads/produk/<?= htmlspecialchars($itemData['image']) ?>" alt="" style="width: 100%; height: 100%; object-fit: contain;">
                            <?php else: ?>
                                <span style="font-size: 10px; color: var(--mu);">FOTO</span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <strong style="color: var(--tx); font-size: 16px; display: block;"><?= htmlspecialchars($itemData['name']) ?></strong>
                            <span class="meta-sub">Kategori: <?= htmlspecialchars($itemData['category']) ?> &bull; Harga: <?= formatRupiah((float)$itemData['price']) ?> &bull; Sisa Stok: <?= (int)$itemData['stock'] ?></span>
                        </div>
                    </div>

                    <?php if ($hasOrderConstraint): ?>
                        <div class="alert alert-warning" style="margin-top: 16px; font-size: 13px; line-height: 1.5;">
                            <strong>Perhatian Arsip Transaksi:</strong> Produk ini sudah pernah dibeli oleh pelanggan dalam transaksi sebelumnya. Menghapus baris produk secara permanen akan merusak riwayat faktur pesanan. Oleh karena itu, mengonfirmasi tindakan ini akan <strong>menonaktifkan produk (mengubah stok menjadi 0)</strong> agar produk tidak dapat lagi dibeli oleh pengguna baru tanpa merusak data lama.
                        </div>
                    <?php endif; ?>

                <?php elseif ($tipe === 'pesan'): ?>
                    <div>
                        <strong style="color: var(--tx); font-size: 16px; display: block;">Dari: <?= htmlspecialchars($itemData['name']) ?> (<?= htmlspecialchars($itemData['email']) ?>)</strong>
                        <span class="meta-sub">Dikirim pada: <?= date('d M Y, H:i', strtotime($itemData['created_at'])) ?> WIB</span>
                        <div style="margin-top: 12px; padding: 12px; background: #000; border: 1px solid var(--ln); border-radius: 4px; color: var(--tx); font-size: 13px; line-height: 1.5;">
                            <?= nl2br(htmlspecialchars($itemData['message'])) ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- FORM KONFIRMASI (Murni POST + CSRF - No JS) -->
            <form method="POST" action="konfirmasi_hapus.php" style="display: flex; gap: 16px;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="tipe" value="<?= htmlspecialchars($tipe) ?>">
                <input type="hidden" name="id" value="<?= (int)$id ?>">

                <a href="<?= $cancelUrl ?>" class="btn btn-secondary" style="flex: 1; text-align: center;">
                    BATAL (KEMBALI)
                </a>
                <button type="submit" class="btn btn-danger" style="flex: 1;">
                    <?= $hasOrderConstraint ? 'YA, NONAKTIFKAN PRODUK' : 'YA, HAPUS PERMANEN' ?>
                </button>
            </form>
        </section>
        </div> <!-- /.admin-content -->
    </main>
</div> <!-- /.admin-wrapper -->

</body>
</html>
