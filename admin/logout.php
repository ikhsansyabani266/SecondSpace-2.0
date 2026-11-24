<?php
// [FILE] logout.php — Proses Logout Administrator SecondSpace
// Dipakai oleh: Administrator
// Tabel database disentuh: Tidak ada

require_once __DIR__ . '/../shared/config.php';

// [BAGIAN: Hapus Sesi Admin]
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_unset();
session_destroy();
// [/BAGIAN: Hapus Sesi Admin]

// [KEAMANAN: Header No-Cache]
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Location: login.php");
exit;
