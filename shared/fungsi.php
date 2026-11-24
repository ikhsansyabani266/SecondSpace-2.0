<?php
// [FILE] fungsi.php — Kumpulan Fungsi Bisnis Bersama (Satu Sumber Kebenaran)
// Dipakai oleh: /user dan /admin
// Tabel database disentuh: products, orders, order_items, users, riwayat_status, riwayat_stok, contacts

require_once __DIR__ . '/config.php';

// [BAGIAN: Format Rupiah]
/**
 * Format angka menjadi format Rupiah Indonesia yang rapi.
 * @param float|int|string $angka
 * @return string Contoh: "Rp 75.000"
 */
function format_rupiah($angka) {
    return "Rp " . number_format((float)$angka, 0, ',', '.');
}
if (!function_exists('formatRupiah')) {
    function formatRupiah($angka) { return format_rupiah($angka); }
}
// [/BAGIAN: Format Rupiah]

// [BAGIAN: Flash Message Session (PRG Pattern)]
/**
 * Simpan pesan flash ke dalam session (Post/Redirect/Get pattern)
 * @param string $tipe 'success' | 'error' | 'info'
 * @param string $pesan
 */
function flash_set($tipe, $pesan) {
    $_SESSION['flash'][$tipe] = $pesan;
}

/**
 * Ambil dan hapus pesan flash dari session
 * @param string $tipe
 * @return string|null
 */
function flash_get($tipe) {
    if (isset($_SESSION['flash'][$tipe])) {
        $msg = $_SESSION['flash'][$tipe];
        unset($_SESSION['flash'][$tipe]);
        return $msg;
    }
    return null;
}

/**
 * Cek apakah ada pesan flash
 * @param string $tipe
 * @return bool
 */
function flash_has($tipe) {
    return isset($_SESSION['flash'][$tipe]);
}
// [/BAGIAN: Flash Message Session (PRG Pattern)]

// [BAGIAN: CSRF Protection (Keamanan Form POST)]
/**
 * Dapatkan atau buat token CSRF sesi saat ini
 * @return string
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Render input hidden HTML CSRF
 * @return string
 */
function csrf_field() {
    $token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Verifikasi token CSRF dari request POST
 * @return bool
 */
function csrf_verify() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return false;
    }
    $postToken = $_POST['csrf_token'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    if (empty($postToken) || empty($sessionToken)) {
        return false;
    }
    return hash_equals($sessionToken, $postToken);
}
// [/BAGIAN: CSRF Protection (Keamanan Form POST)]

// [BAGIAN: Helper Sanitasi Output]
/**
 * Escape teks aman untuk HTML
 * @param mixed $val
 * @return string
 */
function h($val) {
    return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
}
// [/BAGIAN: Helper Sanitasi Output]

// [BAGIAN: Status Badges CSS]
/**
 * Dapatkan class CSS untuk badge status pesanan
 * @param string $status
 * @return string
 */
function get_order_status_class($status) {
    switch ($status) {
        case STATUS_ORDER_MENUNGGU_BAYAR: return 'status-warning';
        case STATUS_ORDER_DIPROSES:       return 'status-info';
        case STATUS_ORDER_DIKIRIM:        return 'status-primary';
        case STATUS_ORDER_SELESAI:        return 'status-success';
        case STATUS_ORDER_DIBATALKAN:     return 'status-danger';
        default:                          return 'status-muted';
    }
}

/**
 * Dapatkan class CSS untuk badge status pembayaran
 * @param string $status
 * @return string
 */
function get_payment_status_class($status) {
    switch ($status) {
        case STATUS_PAYMENT_BELUM_BAYAR:   return 'status-muted';
        case STATUS_PAYMENT_MENUNGGU_VERIF:return 'status-warning';
        case STATUS_PAYMENT_TERVERIFIKASI: return 'status-success';
        case STATUS_PAYMENT_DITOLAK:       return 'status-danger';
        default:                           return 'status-muted';
    }
}
// [/BAGIAN: Status Badges CSS]

// [BAGIAN: Helper WhatsApp Link]
/**
 * Normalisasi format nomor HP ke standar Indonesia (62...)
 * @param mixed $phone
 * @return string
 */
if (!function_exists('normalizePhone')) {
    function normalizePhone($phone): string {
        $clean = preg_replace('/[^0-9]/', '', (string)$phone);
        if (str_starts_with($clean, '0')) {
            $clean = '62' . substr($clean, 1);
        } elseif (str_starts_with($clean, '8')) {
            $clean = '62' . $clean;
        }
        return $clean;
    }
}

/**
 * Buat link WhatsApp direct chat
 * @param string $phone
 * @param string $text
 * @return string
 */
function wa_link($phone, $text = '') {
    $norm = normalizePhone($phone);
    if ($text) {
        return 'https://wa.me/' . $norm . '?text=' . rawurlencode((string)$text) . '&s=p';
    }
    return 'https://wa.me/' . $norm . '?s=p';
}
if (!function_exists('waLink')) {
    function waLink($phone, $text = '') { return wa_link($phone, $text); }
}
// [/BAGIAN: Helper WhatsApp Link]

// =========================================================================
// [BAGIAN: LOGIKA BISNIS KRITIS & SATU SUMBER KEBENARAN (FASE 5)]
// =========================================================================

// [BAGIAN: Mutasi Stok Atomik]
// [JANGAN UBAH] Perhitungan stok menggunakan transaksi dengan SELECT ... FOR UPDATE
// untuk menjamin tidak ada overselling dan setiap mutasi tercatat di tabel riwayat_stok.
/**
 * Mengubah stok produk secara aman dengan pencatatan riwayat audit.
 * 
 * @param mysqli $conn
 * @param int $productId
 * @param int $perubahan Nilai negatif untuk pengurangan (checkout), positif untuk penambahan (batal)
 * @param string $alasan Keterangan mutasi (misal: 'Checkout Pesanan', 'Pembatalan Pesanan', 'Koreksi Admin')
 * @param int|null $referensiOrderId
 * @param int|null $adminId
 * @return array ['success' => bool, 'stok_sebelum' => int, 'stok_sesudah' => int, 'error' => string|null]
 * @throws Exception
 */
function ubah_stok($conn, $productId, $perubahan, $alasan, $referensiOrderId = null, $adminId = null) {
    $productId = (int)$productId;
    $perubahan = (int)$perubahan;

    // Lock baris produk
    $stmt = $conn->prepare("SELECT id, name, stock FROM products WHERE id = ? FOR UPDATE");
    if (!$stmt) throw new Exception("Gagal prepare kunci produk: " . $conn->error);
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $res = $stmt->get_result();
    $p = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$p) {
        throw new Exception("Produk #{$productId} tidak ditemukan di database.");
    }

    $stokLama = (int)$p['stock'];
    $stokBaru = $stokLama + $perubahan;

    if ($stokBaru < 0) {
        throw new Exception("Stok untuk produk '{$p['name']}' tidak mencukupi (Tersedia: {$stokLama}).");
    }

    // Update stok produk
    $upStmt = $conn->prepare("UPDATE products SET stock = ? WHERE id = ?");
    if (!$upStmt) throw new Exception("Gagal prepare update stok: " . $conn->error);
    $upStmt->bind_param("ii", $stokBaru, $productId);
    $upStmt->execute();
    $upStmt->close();

    // Catat mutasi di tabel riwayat_stok
    $rStmt = $conn->prepare("INSERT INTO riwayat_stok (product_id, perubahan, stok_sebelum, stok_sesudah, alasan, referensi_order_id, admin_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($rStmt) {
        $rStmt->bind_param("iiiisii", $productId, $perubahan, $stokLama, $stokBaru, $alasan, $referensiOrderId, $adminId);
        $rStmt->execute();
        $rStmt->close();
    }

    return [
        'success'      => true,
        'stok_sebelum' => $stokLama,
        'stok_sesudah' => $stokBaru,
        'error'        => null
    ];
}

/**
 * Mencatat riwayat mutasi stok ke tabel riwayat_stok secara eksplisit.
 * @param mysqli $conn
 * @param int $productId
 * @param int $stokSebelum
 * @param int $stokSesudah
 * @param string $tipe
 * @param string $alasan
 * @param int|null $adminId
 * @param int|null $referensiOrderId
 * @return bool
 */
function catat_riwayat_stok($conn, $productId, $stokSebelum, $stokSesudah, $tipe, $alasan, $adminId = null, $referensiOrderId = null) {
    $productId = (int)$productId;
    $stokSebelum = (int)$stokSebelum;
    $stokSesudah = (int)$stokSesudah;
    $perubahan = $stokSesudah - $stokSebelum;
    $fullAlasan = "[{$tipe}] " . trim($alasan);
    
    $rStmt = $conn->prepare("INSERT INTO riwayat_stok (product_id, perubahan, stok_sebelum, stok_sesudah, alasan, referensi_order_id, admin_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($rStmt) {
        $rStmt->bind_param("iiiisii", $productId, $perubahan, $stokSebelum, $stokSesudah, $fullAlasan, $referensiOrderId, $adminId);
        $res = $rStmt->execute();
        $rStmt->close();
        return $res;
    }
    return false;
}
// [/BAGIAN: Mutasi Stok Atomik]

// [BAGIAN: State Machine & Alur Status Pesanan]
// [JANGAN UBAH] Alur status pesanan dikontrol ketat untuk mencegah perpindahan tidak sah.
/**
 * Mengubah status pesanan dengan validasi transisi alur dan pencatatan riwayat audit.
 * 
 * @param mysqli $conn
 * @param int $orderId
 * @param string $statusBaru
 * @param string $catatan
 * @param int|null $adminId
 * @param string|null $nomorResi
 * @param string|null $kurir
 * @return array ['success' => bool, 'error' => string|null]
 */
function ubah_status_pesanan($conn, $orderId, $statusBaru, $catatan = '', $adminId = null, $nomorResi = null, $kurir = null) {
    $orderId = (int)$orderId;
    $statusBaru = trim($statusBaru);

    if (!in_array($statusBaru, DAFTAR_STATUS_ORDER, true)) {
        return ['success' => false, 'error' => "Status pesanan '{$statusBaru}' tidak valid."];
    }

    // Ambil status saat ini dengan lock
    $stmt = $conn->prepare("SELECT id, order_code, order_status, payment_status, tracking_number, shipping_courier FROM orders WHERE id = ? FOR UPDATE");
    if (!$stmt) return ['success' => false, 'error' => $conn->error];
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $res = $stmt->get_result();
    $order = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$order) {
        return ['success' => false, 'error' => "Pesanan #{$orderId} tidak ditemukan."];
    }

    $statusLama = $order['order_status'];
    if ($statusLama === $statusBaru) {
        // Jika status sama tapi nomor resi atau kurir diperbarui, simpan perubahan resi
        if (($nomorResi !== null && $nomorResi !== $order['tracking_number']) || ($kurir !== null && $kurir !== $order['shipping_courier'])) {
            $upResi = $nomorResi ?: $order['tracking_number'];
            $upKurir = $kurir ?: $order['shipping_courier'];
            $upStmt = $conn->prepare("UPDATE orders SET tracking_number = ?, shipping_courier = ? WHERE id = ?");
            if ($upStmt) {
                $upStmt->bind_param("ssi", $upResi, $upKurir, $orderId);
                $upStmt->execute();
                $upStmt->close();
            }
        }
        return ['success' => true, 'error' => null]; // Selesai
    }

    // [VALIDASI] Validasi Aturan Transisi Status
    // 1. Pesanan Selesai atau Dibatalkan adalah status akhir (tidak boleh diubah lagi)
    if ($statusLama === STATUS_ORDER_SELESAI || $statusLama === STATUS_ORDER_DIBATALKAN) {
        return ['success' => false, 'error' => "Pesanan berstatus '{$statusLama}' sudah final dan tidak dapat diubah lagi."];
    }

    // 2. Status 'Dikirim' wajib didahului pembayaran Terverifikasi dan wajib memiliki nomor resi
    if ($statusBaru === STATUS_ORDER_DIKIRIM) {
        if ($order['payment_status'] !== STATUS_PAYMENT_TERVERIFIKASI && $order['payment_status'] !== 'Dibayar') {
            return ['success' => false, 'error' => "Pesanan belum dapat dikirim karena pembayaran belum diverifikasi."];
        }
        $finalResi = trim((string)($nomorResi ?: $order['tracking_number']));
        if (empty($finalResi)) {
            return ['success' => false, 'error' => "Nomor resi pengiriman wajib diisi saat mengubah status ke 'Dikirim'."];
        }
    }

    // 3. Jika status dibatalkan, kembalikan stok produk tepat satu kali
    if ($statusBaru === STATUS_ORDER_DIBATALKAN && $statusLama !== STATUS_ORDER_DIBATALKAN) {
        $itemStmt = $conn->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
        if ($itemStmt) {
            $itemStmt->bind_param("i", $orderId);
            $itemStmt->execute();
            $iRes = $itemStmt->get_result();
            while ($it = $iRes->fetch_assoc()) {
                if (!empty($it['product_id'])) {
                    ubah_stok($conn, (int)$it['product_id'], (int)$it['quantity'], "Pembatalan Pesanan {$order['order_code']}", $orderId, $adminId);
                }
            }
            $itemStmt->close();
        }
    } elseif (in_array($statusBaru, [STATUS_ORDER_DIPROSES, STATUS_ORDER_DIKIRIM, STATUS_ORDER_SELESAI], true)) {
        // Jika pesanan diproses/dikirim/selesai, barang preloved dipastikan SOLD (stok 0)
        $itemStmt = $conn->prepare("SELECT product_id FROM order_items WHERE order_id = ?");
        if ($itemStmt) {
            $itemStmt->bind_param("i", $orderId);
            $itemStmt->execute();
            $iRes = $itemStmt->get_result();
            while ($it = $iRes->fetch_assoc()) {
                $pId = (int)$it['product_id'];
                if ($pId > 0) {
                    $chkP = $conn->query("SELECT stock FROM products WHERE id = {$pId}")->fetch_assoc();
                    if ($chkP && (int)$chkP['stock'] > 0) {
                        ubah_stok($conn, $pId, -1 * (int)$chkP['stock'], "Pesanan {$order['order_code']} ({$statusBaru})", $orderId, $adminId);
                    }
                }
            }
            $itemStmt->close();
        }
    }

    // Update tabel orders
    $upResi = $nomorResi ?: $order['tracking_number'];
    $upKurir = $kurir ?: $order['shipping_courier'];
    $upStmt = $conn->prepare("UPDATE orders SET order_status = ?, tracking_number = ?, shipping_courier = ? WHERE id = ?");
    if (!$upStmt) return ['success' => false, 'error' => $conn->error];
    $upStmt->bind_param("sssi", $statusBaru, $upResi, $upKurir, $orderId);
    $upStmt->execute();
    $upStmt->close();

    // Catat riwayat perubahan status
    $rStmt = $conn->prepare("INSERT INTO riwayat_status (order_id, jenis, status_lama, status_baru, catatan, admin_id) VALUES (?, 'pesanan', ?, ?, ?, ?)");
    if ($rStmt) {
        $rStmt->bind_param("isssi", $orderId, $statusLama, $statusBaru, $catatan, $adminId);
        $rStmt->execute();
        $rStmt->close();
    }

    return ['success' => true, 'error' => null];
}

/**
 * Mengubah status pembayaran pesanan dengan validasi transisi alur dan pencatatan riwayat audit.
 * 
 * @param mysqli $conn
 * @param int $orderId
 * @param string $statusBaru
 * @param string $catatan
 * @param int|null $adminId
 * @return array ['success' => bool, 'error' => string|null]
 */
function ubah_status_pembayaran($conn, $orderId, $statusBaru, $catatan = '', $adminId = null) {
    $orderId = (int)$orderId;
    $statusBaru = trim($statusBaru);

    if (!in_array($statusBaru, DAFTAR_STATUS_PAYMENT, true)) {
        return ['success' => false, 'error' => "Status pembayaran '{$statusBaru}' tidak valid."];
    }

    $stmt = $conn->prepare("SELECT id, order_code, payment_status, order_status FROM orders WHERE id = ? FOR UPDATE");
    if (!$stmt) return ['success' => false, 'error' => $conn->error];
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $res = $stmt->get_result();
    $order = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$order) {
        return ['success' => false, 'error' => "Pesanan #{$orderId} tidak ditemukan."];
    }

    $statusLama = $order['payment_status'];
    if ($statusLama === $statusBaru) {
        return ['success' => true, 'error' => null];
    }

    // Jika pembayaran terverifikasi dan pesanan masih menunggu bayar, majukan pesanan ke 'Diproses'
    $syncOrderStatus = null;
    if ($statusBaru === STATUS_PAYMENT_TERVERIFIKASI && $order['order_status'] === STATUS_ORDER_MENUNGGU_BAYAR) {
        $syncOrderStatus = STATUS_ORDER_DIPROSES;
    }

    // Update status pembayaran
    if ($syncOrderStatus) {
        $upStmt = $conn->prepare("UPDATE orders SET payment_status = ?, order_status = ? WHERE id = ?");
        $upStmt->bind_param("ssi", $statusBaru, $syncOrderStatus, $orderId);
    } else {
        $upStmt = $conn->prepare("UPDATE orders SET payment_status = ? WHERE id = ?");
        $upStmt->bind_param("si", $statusBaru, $orderId);
    }
    $upStmt->execute();
    $upStmt->close();

    // Catat di riwayat status
    $rStmt = $conn->prepare("INSERT INTO riwayat_status (order_id, jenis, status_lama, status_baru, catatan, admin_id) VALUES (?, 'pembayaran', ?, ?, ?, ?)");
    if ($rStmt) {
        $rStmt->bind_param("isssi", $orderId, $statusLama, $statusBaru, $catatan, $adminId);
        $rStmt->execute();
        $rStmt->close();
    }

    if ($syncOrderStatus) {
        $rStmt2 = $conn->prepare("INSERT INTO riwayat_status (order_id, jenis, status_lama, status_baru, catatan, admin_id) VALUES (?, 'pesanan', ?, ?, ?, ?)");
        if ($rStmt2) {
            $autoNote = 'Otomatis dimajukan setelah pembayaran terverifikasi';
            $rStmt2->bind_param("isssi", $orderId, $order['order_status'], $syncOrderStatus, $autoNote, $adminId);
            $rStmt2->execute();
            $rStmt2->close();
        }
    }

    return ['success' => true, 'error' => null];
}
// [/BAGIAN: State Machine & Alur Status Pesanan]

// [BAGIAN: Definisi Tunggal Angka KPI Dashboard]
/**
 * Total produk aktif non-demo
 * @param mysqli $conn
 * @return int
 */
function kpi_total_produk($conn): int {
    $res = $conn->query("SELECT COUNT(*) AS total FROM products");
    return ($res && $r = $res->fetch_assoc()) ? (int)$r['total'] : 0;
}

/**
 * Total seluruh pesanan yang pernah masuk
 * @param mysqli $conn
 * @return int
 */
function kpi_total_pesanan($conn): int {
    $res = $conn->query("SELECT COUNT(*) AS total FROM orders");
    return ($res && $r = $res->fetch_assoc()) ? (int)$r['total'] : 0;
}

/**
 * Total pesanan menunggu verifikasi admin (belum bayar / baru upload bukti)
 * @param mysqli $conn
 * @return int
 */
function kpi_menunggu_verifikasi($conn): int {
    $res = $conn->query("SELECT COUNT(*) AS total FROM orders WHERE payment_status = '" . STATUS_PAYMENT_MENUNGGU_VERIF . "' OR (order_status = '" . STATUS_ORDER_MENUNGGU_BAYAR . "' AND proof_image IS NOT NULL AND proof_image != '')");
    return ($res && $r = $res->fetch_assoc()) ? (int)$r['total'] : 0;
}

/**
 * Total pesanan yang berhasil selesai
 * @param mysqli $conn
 * @return int
 */
function kpi_pesanan_selesai($conn): int {
    $res = $conn->query("SELECT COUNT(*) AS total FROM orders WHERE order_status = '" . STATUS_ORDER_SELESAI . "'");
    return ($res && $r = $res->fetch_assoc()) ? (int)$r['total'] : 0;
}

/**
 * Total pesan kontak masuk
 * @param mysqli $conn
 * @return int
 */
function kpi_pesan_masuk($conn): int {
    $res = $conn->query("SELECT COUNT(*) AS total FROM contacts");
    return ($res && $r = $res->fetch_assoc()) ? (int)$r['total'] : 0;
}
// [/BAGIAN: Definisi Tunggal Angka KPI Dashboard]

// [BAGIAN: Helper Query Produk & Kategori]
/**
 * Ambil produk untuk katalog dengan filter & sorting
 * @param mysqli $conn
 * @param string|null $category
 * @param string $sort
 * @param string $search
 * @param int $limit
 * @param int $offset
 * @return array
 */
function ambil_produk_katalog($conn, $category = null, $sort = 'newest', $search = '', $limit = 24, $offset = 0): array {
    $where = ["1=1"];
    $types = "";
    $params = [];

    if ($category && $category !== 'all') {
        $where[] = "category = ?";
        $types .= "s";
        $params[] = $category;
    }

    if ($search !== '') {
        $where[] = "(name LIKE ? OR brand LIKE ? OR description LIKE ?)";
        $types .= "sss";
        $sTerm = "%" . $search . "%";
        $params[] = $sTerm;
        $params[] = $sTerm;
        $params[] = $sTerm;
    }

    $orderBy = "id DESC";
    if ($sort === 'price_low')  $orderBy = "price ASC";
    if ($sort === 'price_high') $orderBy = "price DESC";
    if ($sort === 'name_asc')   $orderBy = "name ASC";

    $sql = "SELECT id, sku, name, category, brand, size, `condition`, price, stock, image, description, created_at 
            FROM products 
            WHERE " . implode(" AND ", $where) . " 
            ORDER BY $orderBy 
            LIMIT ? OFFSET ?";
    
    $types .= "ii";
    $params[] = (int)$limit;
    $params[] = (int)$offset;

    $stmt = $conn->prepare($sql);
    if (!$stmt) return [];
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();

    $products = [];
    while ($r = $res->fetch_assoc()) {
        $products[] = $r;
    }
    $stmt->close();
    return $products;
}

/**
 * Ambil satu produk detail beserta 3 foto galeri
 * @param mysqli $conn
 * @param int $id
 * @return array|null
 */
function ambil_produk_detail($conn, $id) {
    $id = (int)$id;
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
    if (!$stmt) return null;
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $prod = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$prod) return null;

    // Ambil galeri foto
    $imgStmt = $conn->prepare("SELECT id, image, sort_order, type FROM product_images WHERE product_id = ? ORDER BY sort_order ASC");
    $gallery = [];
    if ($imgStmt) {
        $imgStmt->bind_param("i", $id);
        $imgStmt->execute();
        $iRes = $imgStmt->get_result();
        while ($img = $iRes->fetch_assoc()) {
            $gallery[] = $img;
        }
        $imgStmt->close();
    }
    $prod['gallery'] = $gallery;
    return $prod;
}

/**
 * Ambil daftar kategori produk yang tersedia
 * @param mysqli $conn
 * @return array
 */
function ambil_semua_kategori($conn): array {
    $res = $conn->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");
    $cats = [];
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $cats[] = $r['category'];
        }
    }
    return $cats;
}
// [/BAGIAN: Helper Query Produk & Kategori]

// [BAGIAN: Diskon & Promo]
$validDiscounts = [
    'SECONDSPACE'   => ['code' => 'SECONDSPACE',   'type' => 'percentage', 'value' => 10,    'description' => 'Diskon 10% Spesial Pengguna'],
    'PRELOVEDHEMAT' => ['code' => 'PRELOVEDHEMAT', 'type' => 'fixed',      'value' => 15000, 'description' => 'Gratis Ongkir Rp 15.000'],
    'STREETWEAR'    => ['code' => 'STREETWEAR',    'type' => 'fixed',      'value' => 20000, 'description' => 'Potongan Langsung Rp 20.000'],
];

if (!function_exists('calculateDiscount')) {
    function calculateDiscount($discount, $subtotal, $shipping = 0) {
        if (!$discount || !is_array($discount)) return 0;
        $type = $discount['type'] ?? 'fixed';
        $val = (float)($discount['value'] ?? 0);
        if ($type === 'percentage') {
            return (float)round(($subtotal * $val) / 100);
        } elseif ($type === 'fixed') {
            return (float)min($subtotal + $shipping, $val);
        }
        return 0;
    }
}
// [/BAGIAN: Diskon & Promo]

// [BAGIAN: Helper Resolusi URL Gambar Produk Multi-Folder]
if (!function_exists('gambar_produk_url')) {
    /**
     * Resolves product image URL with automatic fallback across directories
     * @param string|null $image
     * @param string $context 'user' | 'admin' | 'root'
     * @return string
     */
    function gambar_produk_url($image, $context = 'user') {
        if (empty($image)) return '';
        $clean = basename($image);
        $root = dirname(__DIR__);

        if ($context === 'user') {
            if (file_exists($root . '/user/images/' . $clean)) return 'images/' . rawurlencode($clean);
            if (file_exists($root . '/images/' . $clean)) return '../images/' . rawurlencode($clean);
            if (file_exists($root . '/uploads/produk/' . $clean)) return '../uploads/produk/' . rawurlencode($clean);
        } elseif ($context === 'admin') {
            if (file_exists($root . '/images/' . $clean)) return '../images/' . rawurlencode($clean);
            if (file_exists($root . '/user/images/' . $clean)) return '../user/images/' . rawurlencode($clean);
            if (file_exists($root . '/uploads/produk/' . $clean)) return '../uploads/produk/' . rawurlencode($clean);
        } else {
            if (file_exists($root . '/images/' . $clean)) return 'images/' . rawurlencode($clean);
            if (file_exists($root . '/user/images/' . $clean)) return 'user/images/' . rawurlencode($clean);
            if (file_exists($root . '/uploads/produk/' . $clean)) return 'uploads/produk/' . rawurlencode($clean);
        }
        return '';
    }
}
// [/BAGIAN: Helper Resolusi URL Gambar Produk Multi-Folder]

