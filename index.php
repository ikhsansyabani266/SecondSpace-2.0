<?php
// [FILE] index.php — Root Router Aplikasi SecondSpace
// Mengarahkan pengunjung domain utama (misal http://aplikasi-preloved-main.test) langsung ke halaman user

require_once __DIR__ . '/shared/config.php';

header("Location: user/index.php");
exit;
