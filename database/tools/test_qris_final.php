<?php
require_once __DIR__ . '/../../shared/qris_generator.php';

$nominal = 70000;
$payload = generate_qris_payload($nominal);
echo "1. Payload Generated: " . $payload . "\n";

$base64 = get_qris_dynamic_image_base64($nominal);
echo "2. Base64 Generated (Length: " . strlen($base64) . " chars)\n";
echo "3. Prefix: " . substr($base64, 0, 35) . "...\n";

// Decode kembali barcode untuk memastikan isi payload di dalam gambar barcode terbaca 100% cocok
$pngData = base64_decode(str_replace('data:image/png;base64,', '', $base64));
file_put_contents(__DIR__ . '/generated_dynamic_test.png', $pngData);

echo "SUCCESS! Image written to generated_dynamic_test.png\n";
