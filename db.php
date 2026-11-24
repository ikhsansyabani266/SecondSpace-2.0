<?php

// ======================================================
// KONFIGURASI DATABASE (LARAVEL HERD & LOCALHOST)
// ======================================================

// Matikan auto-throw exception pada PHP 8.1+ agar fallback port bekerja mulus
mysqli_report(MYSQLI_REPORT_OFF);

ini_set('default_socket_timeout', '2');
set_time_limit(10);

$database = "secondspace_preloved";
$mysqlHost = "127.0.0.1";

// Prioritaskan Port 3307 (MySQL XAMPP yang sedang aktif di laptop Anda), lalu fallback ke 3306
$targetPorts = [3307, 3306];
$targetPasswords = ['', 'admin123', 'root'];

// File penyimpan password lokal
$passConfigFile = __DIR__ . '/mysql_config.php';
if (file_exists($passConfigFile)) {
    $savedPass = (string) include $passConfigFile;
    if (!in_array($savedPass, $targetPasswords, true)) {
        array_unshift($targetPasswords, $savedPass);
    }
}

/** @var mysqli $conn */
$conn = null;
$connectError = null;

// Coba koneksi ke port dan password yang tersedia
foreach ($targetPorts as $port) {
    foreach ($targetPasswords as $pass) {
        // 1. Coba koneksi langsung ke database tujuan
        try {
            $testConn = mysqli_init();
            if ($testConn) {
                $testConn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 1);
                if (@$testConn->real_connect($mysqlHost, 'root', $pass, $database, $port)) {
                    $conn = $testConn;
                    break 2;
                }
            }
        } catch (Throwable $e) {}

        // 2. Jika database belum dibuat, buat database otomatis
        try {
            $serverConn = mysqli_init();
            if ($serverConn) {
                $serverConn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 1);
                if (@$serverConn->real_connect($mysqlHost, 'root', $pass, "", $port)) {
                    $serverConn->query("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    $serverConn->select_db($database);
                    $conn = $serverConn;
                    break 2;
                }
            }
        } catch (Throwable $e) {}
    }
}

if (!$conn || $conn->connect_error) {
    $errMsg = $formError ?: $connectError ?: "Koneksi ke MySQL ditolak.";
    die("<!DOCTYPE html>
<html lang='id'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Koneksi MySQL Diperlukan</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8fafc; color: #1e293b; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; }
        .card { background: #fff; padding: 32px; border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); max-width: 520px; width: 100%; border-top: 5px solid #2563eb; }
        h2 { margin-top: 0; color: #1e293b; font-size: 20px; }
        p { line-height: 1.6; font-size: 14px; color: #475569; }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; padding: 12px 14px; border-radius: 8px; font-size: 13px; margin: 16px 0; word-break: break-all; }
        .form-box { margin-top: 20px; padding: 20px; background: #f1f5f9; border-radius: 10px; }
        .input-group { display: flex; gap: 8px; margin-top: 10px; }
        input[type='password'], input[type='text'] { flex: 1; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none; }
        input[type='password']:focus, input[type='text']:focus { border-color: #2563eb; }
        button { padding: 10px 18px; background: #2563eb; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: 0.2s; white-space: nowrap; }
        button:hover { background: #1d4ed8; }
        .hint { font-size: 12px; color: #64748b; margin-top: 8px; line-height: 1.5; }
    </style>
</head>
<body>
    <div class='card'>
        <h2>🔑 Masukkan Password MySQL Anda</h2>
        <p>Layanan <strong>MySQL80 (Port 3306)</strong> di laptop Anda aktif, namun kata sandi yang digunakan saat ini belum cocok.</p>
        
        <div class='alert-error'>
            <strong>Pesan MySQL:</strong> " . htmlspecialchars($errMsg) . "
        </div>

        <div class='form-box'>
            <form method='POST'>
                <label style='font-size: 13px; font-weight: 600; color: #334155;'>Password root MySQL Anda:</label>
                <div class='input-group'>
                    <input type='text' name='mysql_password' placeholder='Ketik password MySQL...' autofocus required>
                    <button type='submit'>Hubungkan</button>
                </div>
                <div class='hint'>💡 Masukkan password akun root yang Anda buat saat instalasi MySQL. Jika benar, website akan langsung terbuka otomatis.</div>
            </form>
        </div>
    </div>
</body>
</html>");
}

assert($conn instanceof mysqli);
$conn->set_charset("utf8mb4");

// ======================================================
// KONFIGURASI TOKO & REKENING PEMBAYARAN
// ======================================================
$shippingCost = 15000;
$whatsappNumber = "6281234567890";

// Rekening Bank
$bankName = "Bank Central Asia (BCA)";
$bankAccountNumber = "8720912345";
$bankAccount = "8720912345";
$bankAccountName = "SecondSpace Official";
$bankHolder = "SecondSpace Official";

// E-Wallet
$ewalletName = "DANA / GoPay / OVO";
$ewalletType = "DANA / GoPay / OVO / QRIS";
$ewalletNumber = "081234567890";
$ewalletAccountName = "SecondSpace Official";
$ewalletHolder = "SecondSpace Official";

// ======================================================
// DAFTAR KODE DISKON RESMI
// ======================================================
$validDiscounts = [
    "SECONDSPACE10" => [
        "type"        => "percent",
        "value"       => 10,
        "description" => "Diskon 10% Subtotal"
    ],
    "HEMAT20" => [
        "type"        => "fixed",
        "value"       => 20000,
        "description" => "Potongan Rp 20.000"
    ],
    "ONGKIRGRATIS" => [
        "type"        => "fixed",
        "value"       => 15000,
        "description" => "Gratis Ongkos Kirim Rp 15.000"
    ],
    "DISKON50" => [
        "type"        => "percent",
        "value"       => 50,
        "description" => "Diskon Spesial 50%"
    ]
];

// ======================================================
// FUNGSI HELPER UMUM (PURCHASE WORKFLOW)
// ======================================================

/**
 * Hitung nilai potongan diskon
 */
if (!function_exists('calculateDiscount')) {
    function calculateDiscount(array $discountConfig, float $subtotal, float $shipping): float {
        if (($discountConfig["type"] ?? "") === "percent") {
            $val = (float)($discountConfig["value"] ?? 0);
            return round($subtotal * ($val / 100));
        } elseif (($discountConfig["type"] ?? "") === "fixed") {
            $val = (float)($discountConfig["value"] ?? 0);
            return min($val, $subtotal + $shipping);
        }
        return 0;
    }
}

/**
 * Format angka ke format Rupiah standar Indonesia
 * Contoh: 185000 -> "Rp 185.000"
 */
if (!function_exists('formatRupiah')) {
    function formatRupiah($n): string {
        return "Rp " . number_format((float)$n, 0, ",", ".");
    }
}

/**
 * Normalisasi nomor HP ke format internasional WhatsApp (62...)
 * Menghapus spasi, strip, tanda plus, dan mengganti awalan 0 menjadi 62
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
 * Menghasilkan link tautan WhatsApp langsung (wa.me)
 */
if (!function_exists('waLink')) {
    function waLink($number, $text): string {
        $norm = normalizePhone($number);
        return "https://wa.me/" . $norm . "?text=" . rawurlencode((string)$text);
    }
}

// ======================================================
// PEMBUATAN STRUKTUR TABEL OTOMATIS JIKA BELUM ADA
// ======================================================

$schemaQueries = [
    "CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `role` VARCHAR(20) DEFAULT 'user',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "CREATE TABLE IF NOT EXISTS `products` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(255) NOT NULL,
        `category` VARCHAR(50) NOT NULL,
        `price` DECIMAL(12,2) NOT NULL,
        `stock` INT NOT NULL DEFAULT 1,
        `condition` VARCHAR(50) DEFAULT 'Baik',
        `image` VARCHAR(255) NULL,
        `description` TEXT NULL,
        `material` VARCHAR(100) NULL,
        `size` VARCHAR(50) NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "CREATE TABLE IF NOT EXISTS `addresses` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `recipient_name` VARCHAR(100) NOT NULL,
        `phone` VARCHAR(20) NOT NULL,
        `address` TEXT NOT NULL,
        `province` VARCHAR(100) NULL,
        `city` VARCHAR(100) NOT NULL,
        `postal_code` VARCHAR(20) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "CREATE TABLE IF NOT EXISTS `orders` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `order_code` VARCHAR(50) NULL,
        `order_number` VARCHAR(50) NULL,
        `address_id` INT NULL,
        `subtotal` DECIMAL(12,2) NULL,
        `shipping` DECIMAL(12,2) NULL,
        `total` DECIMAL(12,2) NULL,
        `total_price` DECIMAL(12,2) NULL,
        `payment_method` VARCHAR(50) NULL,
        `payment_status` VARCHAR(50) DEFAULT 'Menunggu Pembayaran',
        `order_status` VARCHAR(50) DEFAULT 'Menunggu Pembayaran',
        `status` VARCHAR(50) DEFAULT 'Menunggu Pembayaran',
        `notes` TEXT NULL,
        `shipping_courier` VARCHAR(50) NULL,
        `tracking_number` VARCHAR(100) NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "CREATE TABLE IF NOT EXISTS `order_items` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `order_id` INT NOT NULL,
        `product_id` INT NOT NULL,
        `product_name` VARCHAR(255) NULL,
        `price` DECIMAL(12,2) NOT NULL,
        `quantity` INT NOT NULL DEFAULT 1,
        `subtotal` DECIMAL(12,2) NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "CREATE TABLE IF NOT EXISTS `contacts` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `email` VARCHAR(100) NOT NULL,
        `message` TEXT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

foreach ($schemaQueries as $sql) {
    $conn->query($sql);
}

// Tambahkan data produk contoh jika tabel masih kosong
$checkProducts = $conn->query("SELECT id FROM products LIMIT 1");
if ($checkProducts && $checkProducts->num_rows === 0) {
    $seedProducts = "INSERT INTO `products` (`name`, `category`, `price`, `stock`, `condition`, `image`, `description`, `material`, `size`) VALUES
    ('Kemeja Flannel Tartan Vintage', 'Kemeja', 85000, 5, 'Sangat Baik', 'kemeja.jpg', 'Kemeja flannel motif tartan preloved dengan bahan katun tebal, warna masih pekat dan kancing lengkap.', 'Katun Flannel', 'L'),
    ('Celana Denim Regular Vintage 90s', 'Celana', 120000, 3, 'Baik', 'celana.jpg', 'Celana jeans denim klasik potongan regular fit, nyaman dipakai untuk gaya harian kasual.', 'Denim Non-Stretch', '32'),
    ('Sepatu Sneaker Canvas Classic', 'Sepatu', 150000, 2, 'Sangat Baik', 'sepatu.jpg', 'Sneaker canvas hitam putih klasik, sol tebal dan bersih, kondisi terawat layak pakai.', 'Canvas & Rubber', '42'),
    ('Kaos Graphic Band Tour Vintage', 'Baju', 65000, 4, 'Sangat Baik', '1790343658_2ca517529c4a72c7.jpg', 'T-shirt grafis gaya vintage streetwear, bahan katun adem dengan sablon awet.', 'Cotton Combed 24s', 'XL'),
    ('Jacket Windbreaker Outdoor Casual', 'Jacket', 135000, 2, 'Sangat Baik', '1790400477_f0970f290aec711c.jpg', 'Jaket windbreaker preloved tahan angin ringan, resleting lancar dan karet lengan elastis.', 'Polyester Nylon', 'L');";
    $conn->query($seedProducts);
}

// Tambahkan user admin default jika tabel users masih kosong
$checkUsers = $conn->query("SELECT id FROM users LIMIT 1");
if ($checkUsers && $checkUsers->num_rows === 0) {
    $defaultAdminPass = password_hash("admin123", PASSWORD_DEFAULT);
    $conn->query("INSERT INTO `users` (`name`, `username`, `password`, `role`) VALUES ('Administrator', 'admin', '{$defaultAdminPass}', 'admin')");
}
?>
