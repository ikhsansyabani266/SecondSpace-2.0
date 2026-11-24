<?php
// [FILE] config.example.php — Contoh Konfigurasi Template Sistem Toko Preloved SecondSpace
// Salin berkas ini ke /shared/config.php jika melakukan instalasi baru di lingkungan lokal / server baru.

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
// [UBAH DI SINI] Kredensial koneksi database MySQL lokal (standar Laravel Herd / XAMPP)
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'secondspace_preloved');
define('DB_PORTS', [3307, 3306]); // Prioritas port 3307 (Herd), fallback 3306 (XAMPP/MySQL standar)
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

// Himpunan Kondisi Produk Terpusat (Opsi A: Satu-Satunya Sumber Kebenaran)
define('DAFTAR_KONDISI_PRODUK', [
    'Seperti Baru',
    'Bagus',
    'Ada Minus',
]);
// [/BAGIAN: Konstanta Status Toko]

// [BAGIAN: Pengaturan Bisnis & Kontak Toko]
// [UBAH DI SINI] Nomor WhatsApp CS Toko SecondSpace (format internasional tanpa tanda +)
define('ADMIN_WHATSAPP', '62895327292342');

// Informasi Rekening Pembayaran Resmi Toko
define('BANK_NAME', 'BCA (Bank Central Asia)');
define('BANK_ACCOUNT_NUMBER', '8735091823');
define('BANK_ACCOUNT_NAME', 'SECONDSPACE STORE');

// Informasi Akun E-Wallet Resmi Toko
define('EWALLET_NAME', 'GoPay / OVO / Dana');
define('EWALLET_NUMBER', '0895-3272-92342');
define('EWALLET_HOLDER', 'SECONDSPACE OFFICIAL');

// Tarif Ongkos Kirim Flat Jabodetabek & Nasional
define('SHIPPING_COST_FLAT', 15000);
// [/BAGIAN: Pengaturan Bisnis & Kontak Toko]

// [BAGIAN: Keamanan Upload]
// [JANGAN UBAH] Batas maksimal ukuran upload gambar produk (5MB dalam byte)
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024);

// Format file gambar yang diizinkan untuk upload
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('ALLOWED_IMAGE_EXTS', ['jpg', 'jpeg', 'png', 'webp']);
// [/BAGIAN: Keamanan Upload]
