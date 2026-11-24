<?php
function buat_payload_qris_dinamis(int $nominal): string {
    $staticPayload = "00020101021126610014COM.GO-JEK.WWW01189360091437886188260210G7886188260303UMI51440014ID.CO.QRIS.WWW0215ID10266317050050303UMI5204569753033605802ID5923MUHAMAD IKHSAN SYA'BANI6013JAKARTA TIMUR61051345062070703A01630429B8";
    $payload = str_replace('010211', '010212', $staticPayload);
    $payloadWithoutCrc = substr($payload, 0, -8);
    $strNominal = (string)$nominal;
    $lenNominal = str_pad((string)strlen($strNominal), 2, '0', STR_PAD_LEFT);
    $tag54 = '54' . $lenNominal . $strNominal;
    $pos58 = strpos($payloadWithoutCrc, '5802ID');
    $newPayload = substr($payloadWithoutCrc, 0, $pos58) . $tag54 . substr($payloadWithoutCrc, $pos58);
    $forCrc = $newPayload . '6304';
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
    return $forCrc . strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
}

$dynamicString = buat_payload_qris_dinamis(70000);
echo "Nominal 70.000 QRIS Dynamic Payload:\n" . $dynamicString . "\n";
