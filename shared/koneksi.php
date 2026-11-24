<?php
// [FILE] koneksi.php — Koneksi Database MySQLi Tunggal & Aman
// Dipakai oleh: Seluruh halaman /user, /admin, dan skrip /database/tools
// Tabel database disentuh: Semua

require_once __DIR__ . '/config.php';

// Matikan auto-throw exception pada PHP 8.1+ agar fallback port bekerja mulus
mysqli_report(MYSQLI_REPORT_OFF);

/** @var mysqli|null $conn */
$conn = null;
$connectError = null;

// Coba koneksi ke port yang tersedia (3307 lalu 3306)
foreach (DB_PORTS as $port) {
    try {
        $testConn = mysqli_init();
        if ($testConn) {
            $testConn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 2);
            if (@$testConn->real_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, $port)) {
                $conn = $testConn;
                $conn->set_charset("utf8mb4");
                break;
            }
        }
    } catch (Throwable $e) {
        $connectError = $e->getMessage();
    }
}

if (!$conn || $conn->connect_error) {
    $err = $conn ? $conn->connect_error : ($connectError ?: "Koneksi database gagal.");
    // Tampilkan pesan kesalahan ramah pengguna tanpa membocorkan kredensial
    die("<!DOCTYPE html>
<html lang='id'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Database Offline - SecondSpace</title>
    <style>
        body { background: #0A0A0A; color: #EDEDED; font-family: sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .box { background: #161616; border: 1px solid #2E2E2E; border-radius: 8px; padding: 32px; max-width: 480px; text-align: center; }
        h2 { color: #B91C1C; margin-top: 0; }
        p { color: #8A8A8A; line-height: 1.6; }
    </style>
</head>
<body>
    <div class='box'>
        <h2>Layanan Database Sedang Tidak Tersedia</h2>
        <p>Aplikasi tidak dapat terhubung ke MySQL (Port 3307 / 3306). Pastikan MySQL pada Laravel Herd atau XAMPP sudah berjalan.</p>
    </div>
</body>
</html>");
}
