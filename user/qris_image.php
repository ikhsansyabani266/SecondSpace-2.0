<?php
/**
 * [FILE]: user/qris_image.php
 * [BAGIAN]: Endpoint Gambar Barcode QRIS Dinamis On-the-Fly
 * [DESKRIPSI]: Menghasilkan barcode QRIS standar EMVCo dengan nominal transaksi dinamis langsung sebagai binary PNG
 * [ATURAN]: 100% PHP Native, TANPA JavaScript
 */

require_once __DIR__ . '/../shared/qris_generator.php';

$nominal = isset($_GET['nominal']) ? max(1, (int)$_GET['nominal']) : 10000;
$payload = generate_qris_payload($nominal);

require_once __DIR__ . '/../shared/phpqrcode.php';

header('Content-Type: image/png');
header('Cache-Control: no-cache, no-store, must-revalidate');

$oldLevel = error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
QRcode::png($payload, null, QR_ECLEVEL_M, 7, 2);
error_reporting($oldLevel);
exit;
