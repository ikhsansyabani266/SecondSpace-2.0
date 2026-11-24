<?php
// [FILE] config.php — Konfigurasi Utama Proyek Toko Preloved SecondSpace
// Tabel database disentuh: Tidak ada (hanya konstanta & environment)

// [BAGIAN: Session]
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// [/BAGIAN: Session]

// [BAGIAN: Zona Waktu & Error Reporting]
date_default_timezone_set('Asia/Jakarta');

// Nonaktifkan display_errors untuk standar pengumpulan & keamanan produksi
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
// [/BAGIAN: Zona Waktu & Error Reporting]

// [BAGIAN: Base URL Dinamis]
// [UBAH DI SINI] Jika ingin memaksakan URL tertentu, ganti nilai auto-detect di bawah
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Tentukan root path proyek secara dinamis
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $dirName = dirname($scriptName);
    
    // Jika berada di dalam subfolder /user atau /admin atau /database, naik satu tingkat
    $appRoot = preg_replace('/(\/(user|admin|shared|database|database\/tools))$/i', '', $dirName);
    $appRoot = rtrim($appRoot, '/');
    
    define('BASE_URL', rtrim($protocol . $host . $appRoot, '/'));
}
// [/BAGIAN: Base URL Dinamis]

// [BAGIAN: Database Kredensial]
// [UBAH DI SINI] Kredensial koneksi database MySQL lokal
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'secondspace_preloved');
define('DB_PORTS', [3307, 3306]); // Prioritas port 3307 (XAMPP Herd), fallback 3306
// [/BAGIAN: Database Kredensial]

// [BAGIAN: Konstanta Status Toko]
// [TAMBAH DI SINI] Tempat menambah status pesanan atau pembayaran baru
// Status Pesanan (Order Status)
define('STATUS_ORDER_MENUNGGU_BAYAR', 'Menunggu Pembayaran');
define('STATUS_ORDER_DIPROSES',       'Diproses');
define('STATUS_ORDER_DIKIRIM',        'Dikirim');
define('STATUS_ORDER_SELESAI',        'Selesai');
define('STATUS_ORDER_DIBATALKAN',     'Dibatalkan');

// Status Pembayaran (Payment Status)
define('STATUS_PAYMENT_BELUM_BAYAR',  'Belum Bayar');
define('STATUS_PAYMENT_MENUNGGU_VERIF','Menunggu Verifikasi');
define('STATUS_PAYMENT_TERVERIFIKASI','Terverifikasi');
define('STATUS_PAYMENT_DITOLAK',      'Ditolak');

// Daftar Status Baku untuk Validasi Ketat
define('DAFTAR_STATUS_ORDER', [
    STATUS_ORDER_MENUNGGU_BAYAR,
    STATUS_ORDER_DIPROSES,
    STATUS_ORDER_DIKIRIM,
    STATUS_ORDER_SELESAI,
    STATUS_ORDER_DIBATALKAN,
]);

define('DAFTAR_STATUS_PAYMENT', [
    STATUS_PAYMENT_BELUM_BAYAR,
    'Menunggu Pembayaran',
    'Menunggu Konfirmasi',
    'Dibayar',
    STATUS_PAYMENT_MENUNGGU_VERIF,
    STATUS_PAYMENT_TERVERIFIKASI,
    STATUS_PAYMENT_DITOLAK,
    'Selesai',
    'Dibatalkan',
]);
// [/BAGIAN: Konstanta Status Toko]

// [BAGIAN: Pengaturan Bisnis Preloved]
// [UBAH DI SINI] Parameter operasional toko
define('NAMA_TOKO', 'SecondSpace');
define('TAGLINE_TOKO', 'Curated Streetwear Preloved Archive');
define('ONGKIR_FLAT', 15000);
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5 MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('ALLOWED_IMAGE_EXTS',  ['jpg', 'jpeg', 'png', 'webp']);

// Kontak & WhatsApp CS
define('WHATSAPP_CS', '62895327292342');
define('WHATSAPP_CS_URL', 'https://wa.me/62895327292342?s=p');
$whatsappNumber = WHATSAPP_CS; // Variabel kompatibilitas
$whatsappCsUrl = WHATSAPP_CS_URL;

// Informasi Rekening Bank Toko
define('BANK_NAME', 'Bank Central Asia (BCA)');
define('BANK_ACCOUNT_NUMBER', '8801928391');
define('BANK_ACCOUNT_NAME', 'SecondSpace Official');
$bankName = BANK_NAME;
$bankAccountNumber = BANK_ACCOUNT_NUMBER;
$bankAccountName = BANK_ACCOUNT_NAME;

// Informasi E-Wallet Toko
define('EWALLET_NAME', 'DANA / GoPay / OVO');
define('EWALLET_NUMBER', '0895327292342');
define('EWALLET_ACCOUNT_NAME', 'SecondSpace Official');
$ewalletName = EWALLET_NAME;
$ewalletNumber = EWALLET_NUMBER;
$ewalletAccountName = EWALLET_ACCOUNT_NAME;
$ewalletType = EWALLET_NAME;
$ewalletHolder = EWALLET_ACCOUNT_NAME;

// Informasi QRIS Toko
define('QRIS_MERCHANT_NAME', 'SecondSpace PreLoved Official');
define('QRIS_IMAGE_URL', 'images/qris_pembayaran.webp');

// Daftar Metode Pembayaran Resmi
define('DAFTAR_METODE_PEMBAYARAN', [
    'Transfer Bank',
    'QRIS',
    'E-Wallet',
    'COD'
]);

// [BAGIAN: Daftar Kondisi Produk Preloved Baku (Opsi A)]
define('DAFTAR_KONDISI_PRODUK', [
    'Seperti Baru',
    'Bagus',
    'Ada Minus',
]);
// [/BAGIAN: Daftar Kondisi Produk Preloved Baku (Opsi A)]
// [/BAGIAN: Pengaturan Bisnis Preloved]
