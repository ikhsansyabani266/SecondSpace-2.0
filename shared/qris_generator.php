<?php
/**
 * [FILE]: shared/qris_generator.php
 * [BAGIAN]: Generator QRIS Dinamis Standar ASPI / EMVCo (Pure PHP Native - No JS)
 * [DESKRIPSI]: Mengubah payload QRIS statis merchant menjadi dinamis dengan nominal pas transaksi dan render QR Code PNG/Base64
 * [JANGAN UBAH]: Algoritma CRC-16 CCITT dan format tag EMVCo (01, 54, 58, 63)
 */

if (!defined('QRIS_STATIC_PAYLOAD')) {
    // Payload QRIS asli dari merchant SecondSpace
    define('QRIS_STATIC_PAYLOAD', '00020101021126610014COM.GO-JEK.WWW01189360091437886188260210G7886188260303UMI51440014ID.CO.QRIS.WWW0215ID10266317050050303UMI5204569753033605802ID5923MUHAMAD IKHSAN SYA\'BANI6013JAKARTA TIMUR61051345062070703A01630429B8');
}

/**
 * Menghasilkan string payload QRIS Dinamis dengan tag nominal (Tag 54) dan CRC16 baru.
 *
 * @param int $nominal Jumlah rupiah yang harus dibayar (misal 70000)
 * @return string Payload QRIS dinamis lengkap
 */
function generate_qris_payload(int $nominal): string {
    $static = QRIS_STATIC_PAYLOAD;

    // 1. Ubah point of initiation dari '11' (statis) ke '12' (dinamis)
    $payload = str_replace('010211', '010212', $static);

    // 2. Buang tag 63 (CRC lama) di ujung
    $withoutCrc = substr($payload, 0, -8);

    // 3. Format tag 54 (Transaction Amount)
    $amountStr = (string)$nominal;
    $amountLen = str_pad((string)strlen($amountStr), 2, '0', STR_PAD_LEFT);
    $tag54 = '54' . $amountLen . $amountStr;

    // 4. Sisipkan tag 54 tepat sebelum tag 5802ID (Country Code)
    $pos58 = strpos($withoutCrc, '5802ID');
    if ($pos58 !== false) {
        $before = substr($withoutCrc, 0, $pos58);
        $after = substr($withoutCrc, $pos58);
        $raw = $before . $tag54 . $after;
    } else {
        $raw = $withoutCrc . $tag54;
    }

    // 5. Tambahkan tag 6304 untuk penghitungan CRC16
    $forCrc = $raw . '6304';

    // 6. Hitung CRC-16 CCITT (Polynomial 0x1021, Initial 0xFFFF)
    $crc = 0xFFFF;
    $len = strlen($forCrc);
    for ($c = 0; $c < $len; $c++) {
        $crc ^= (ord($forCrc[$c]) << 8);
        for ($i = 0; $i < 8; $i++) {
            if ($crc & 0x8000) {
                $crc = (($crc << 1) ^ 0x1021) & 0xFFFF;
            } else {
                $crc = ($crc << 1) & 0xFFFF;
            }
        }
    }
    $hexCrc = strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));

    return $forCrc . $hexCrc;
}

/**
 * Menghasilkan kartu QR Code QRIS Dinamis lengkap dalam format Base64 Data URL (bisa langsung dimasukkan ke tag src img).
 *
 * @param int $nominal Nominal tagihan belanja
 * @param int $size Ukuran matriks barcode
 * @return string Data URL base64 image PNG
 */
function get_qris_dynamic_image_base64(int $nominal, int $size = 7): string {
    require_once __DIR__ . '/phpqrcode.php';

    $payload = generate_qris_payload($nominal);

    // Matikan notice deprecated saat rendering QR
    $oldLevel = error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

    ob_start();
    QRcode::png($payload, null, QR_ECLEVEL_M, $size, 2);
    $rawQrPng = ob_get_clean();

    error_reporting($oldLevel);

    if (empty($rawQrPng)) {
        return '';
    }

    return 'data:image/png;base64,' . base64_encode($rawQrPng);
}
