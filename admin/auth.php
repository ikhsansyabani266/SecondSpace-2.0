<?php
// [FILE] auth.php — Middleware Proteksi Akses Halaman Admin
// Dipakai oleh: Seluruh halaman di folder /admin/*.php
// Tabel database disentuh: Tidak ada (hanya pemeriksaan session)
// Fungsi bersama: /shared/config.php

require_once __DIR__ . '/../shared/config.php';

// [KEAMANAN: Header Anti-Cache]
// [JANGAN UBAH] Mencegah halaman admin dapat dilihat kembali melalui tombol 'Back' browser setelah logout
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
// [/KEAMANAN: Header Anti-Cache]

// [BAGIAN: Pemeriksaan Sesi Admin]
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$role = $_SESSION["role"] ?? "user";
$userId = (int)($_SESSION["user_id"] ?? 0);

// [KEAMANAN: Hak Akses Role]
// Hanya akun bertanda role 'admin' atau user ID 1 yang diizinkan mengakses
if ($role !== "admin" && $userId !== 1) {
    header("Location: login.php?error=unauthorized");
    exit;
}
// [/BAGIAN: Pemeriksaan Sesi Admin]
