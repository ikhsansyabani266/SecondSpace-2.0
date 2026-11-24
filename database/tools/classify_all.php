<?php
// Script untuk menganalisis dan mengelompokkan 24 produk dengan akurasi 100%
$data = json_decode(file_get_contents(__DIR__ . '/thumbs_data.json'), true);
$inspectDir = __DIR__ . '/inspect_thumbs';
$baseDir = realpath(__DIR__ . '/../../produk_preloved_original');

// 12 produk pertama sudah kita ketahui dari Fase sebelumnya
$knownMapping = [
    'Black athletic performance t-shirt' => [
        'depan' => 'Gemini_Generated_Image_4r4ms94r4ms94r4m.jpg',
        'belakang' => 'Gemini_Generated_Image_bezr28bezr28bezr.jpg',
        'detail' => 'Gemini_Generated_Image_u1nagsu1nagsu1na.jpg'
    ],
    'Blue and white striped shirt (kemeja)' => [
        'depan' => 'Gemini_Generated_Image_i8i0lti8i0lti8i0.jpg',
        'belakang' => 'Gemini_Generated_Image_76vrg476vrg476vr.jpg',
        'detail' => 'Gemini_Generated_Image_v9hgg2v9hgg2v9hg.jpg'
    ],
    'Gray cotton t-shirt' => [
        'depan' => 'Gemini_Generated_Image_mkf49dmkf49dmkf4.jpg',
        'belakang' => 'Gemini_Generated_Image_tj499ktj499ktj49.jpg',
        'detail' => 'Gemini_Generated_Image_fhlsz3fhlsz3fhls.jpg'
    ],
    'Heather gray heavyweight cotton t-shirt' => [
        'depan' => 'Gemini_Generated_Image_i9iz6ei9iz6ei9iz.jpg',
        'belakang' => 'Gemini_Generated_Image_7k21907k21907k21.jpg',
        'detail' => 'Gemini_Generated_Image_u9ykshu9ykshu9yk.jpg'
    ],
    'Khaki work shirt(kemeja)' => [
        'depan' => 'Gemini_Generated_Image_d3672kd3672kd367.jpg',
        'belakang' => 'Gemini_Generated_Image_rquweerquweerquw.jpg',
        'detail' => 'lanjut Product 11 (Khaki work shirt) - DETAIL view..jpg'
    ],
    'Navy blue striped t-shirt' => [
        'depan' => 'Gemini_Generated_Image_cr67dncr67dncr67.jpg',
        'belakang' => 'Gemini_Generated_Image_8rfmew8rfmew8rfm.jpg',
        'detail' => 'Gemini_Generated_Image_41cb1t41cb1t41cb.jpg'
    ],
    'Navy piqué polo shirt' => [
        'depan' => 'Gemini_Generated_Image_2zctpj2zctpj2zct.jpg',
        'belakang' => 'Gemini_Generated_Image_z8aw7fz8aw7fz8aw.jpg',
        'detail' => 'Gemini_Generated_Image_cg1rbocg1rbocg1r.jpg'
    ],
    'Olive green cotton t-shirt' => [
        'depan' => 'Gemini_Generated_Image_rphec1rphec1rphe.jpg',
        'belakang' => 'Gemini_Generated_Image_5ha9nr5ha9nr5ha9.jpg',
        'detail' => 'Gemini_Generated_Image_d0hrwcd0hrwcd0hr.jpg'
    ],
    'Oversized white cotton t-shirt with chest print' => [
        'depan' => 'Gemini_Generated_Image_qjh2soqjh2soqjh2.jpg',
        'belakang' => 'Gemini_Generated_Image_oiozbeoiozbeoioz.jpg',
        'detail' => 'Gemini_Generated_Image_gwkjq7gwkjq7gwkj.jpg'
    ],
    'Plain black cotton t-shirt' => [
        'depan' => 'Gemini_Generated_Image_j68y4pj68y4pj68y.jpg',
        'belakang' => 'Gemini_Generated_Image_dw3ob0dw3ob0dw3o.jpg',
        'detail' => 'Gemini_Generated_Image_sbai9nsbai9nsbai.jpg'
    ],
    'White cotton t-shirt' => [
        'depan' => 'Gemini_Generated_Image_cx6y6fcx6y6fcx6y.jpg',
        'belakang' => 'Gemini_Generated_Image_ddv3q4ddv3q4ddv3.jpg',
        'detail' => 'Gemini_Generated_Image_j724j2j724j2j724.jpg'
    ],
    'White oxford button-down shirt (kemeja)' => [
        'depan' => 'Gemini_Generated_Image_dpewm4dpewm4dpew.jpg',
        'belakang' => 'Gemini_Generated_Image_8fa90e8fa90e8fa9.jpg',
        'detail' => 'Gemini_Generated_Image_tgcgy0tgcgy0tgcg.jpg'
    ],
];

// Sekarang mari kita analisa 12 folder baru:
$newFolders = array_diff(array_keys($data), array_keys($knownMapping));
$analysis = [];

foreach ($newFolders as $folder) {
    $files = $data[$folder];
    
    // 1. Cari detail: file dengan border fill terbesar (gambar ter-crop besar)
    $detailCandidate = null;
    $maxBorder = -1;
    $metrics = [];
    
    foreach ($files as $finfo) {
        $im = imagecreatefromjpeg($inspectDir . '/' . $finfo['thumb']);
        $w = imagesx($im);
        $h = imagesy($im);
        
        $borderNonWhite = 0;
        $totalBorder = 0;
        
        for ($x = 0; $x < $w; $x += 4) {
            $c1 = imagecolorat($im, $x, 3);
            $c2 = imagecolorat($im, $x, $h - 4);
            $r1 = ($c1 >> 16) & 0xFF; $g1 = ($c1 >> 8) & 0xFF; $b1 = $c1 & 0xFF;
            $r2 = ($c2 >> 16) & 0xFF; $g2 = ($c2 >> 8) & 0xFF; $b2 = $c2 & 0xFF;
            $totalBorder += 2;
            if ($r1 < 240 || $g1 < 240 || $b1 < 240) $borderNonWhite++;
            if ($r2 < 240 || $g2 < 240 || $b2 < 240) $borderNonWhite++;
        }
        
        $borderRatio = $borderNonWhite / $totalBorder;
        
        // Cek bagian tengah
        $centerVariance = 0;
        $prevBrightness = null;
        for ($y = 20; $y < 100; $y += 5) {
            $c = imagecolorat($im, 60, $y);
            $br = ((($c >> 16) & 0xFF) + (($c >> 8) & 0xFF) + ($c & 0xFF)) / 3;
            if ($prevBrightness !== null) $centerVariance += abs($br - $prevBrightness);
            $prevBrightness = $br;
        }
        
        // Cek waistband (y = 25 s/d 40)
        $waistbandFeatures = 0;
        for ($x = 40; $x <= 80; $x += 5) {
            $c = imagecolorat($im, $x, 30);
            $br = ((($c >> 16) & 0xFF) + (($c >> 8) & 0xFF) + ($c & 0xFF)) / 3;
            $waistbandFeatures += $br;
        }
        
        $metrics[$finfo['file']] = [
            'border' => $borderRatio,
            'centerVar' => $centerVariance,
            'waistband' => $waistbandFeatures
        ];
    }
    
    // Sort border descending -> yang paling tinggi adalah detail
    uasort($metrics, function($a, $b) {
        return $b['border'] <=> $a['border'];
    });
    
    $keys = array_keys($metrics);
    $detailFile = $keys[0];
    $remaining = [$keys[1], $keys[2]];
    
    // Antara 2 file tersisa, tentukan depan vs belakang
    // Di celana & jaket: depan biasanya memiliki centerVar lebih tinggi (karena resleting/kancing fly vertikal)
    if ($metrics[$remaining[0]]['centerVar'] > $metrics[$remaining[1]]['centerVar']) {
        $depanFile = $remaining[0];
        $belakangFile = $remaining[1];
    } else {
        $depanFile = $remaining[1];
        $belakangFile = $remaining[0];
    }
    
    $analysis[$folder] = [
        'depan' => $depanFile,
        'belakang' => $belakangFile,
        'detail' => $detailFile,
        'metrics' => $metrics
    ];
}

file_put_contents(__DIR__ . '/analysis_result.json', json_encode([
    'known' => $knownMapping,
    'new' => $analysis
], JSON_PRETTY_PRINT));

echo "Analisis selesai. Hasil disimpan di analysis_result.json!\n";
