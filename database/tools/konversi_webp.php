<?php
// CLI Tool: Konversi Gambar ke WebP menggunakan GD (Idempoten)
ini_set('memory_limit', '512M');
set_time_limit(600);

$sourceBase = realpath(__DIR__ . '/../../produk_preloved_original');
if (!$sourceBase || !is_dir($sourceBase)) {
    echo "ERROR: Folder sumber produk_preloved_original tidak ditemukan!\n";
    exit(1);
}

// Tujuan konversi: uploads/produk dan images
$uploadsDir = realpath(__DIR__ . '/../../uploads/produk');
if (!$uploadsDir) {
    mkdir(__DIR__ . '/../../uploads/produk', 0777, true);
    $uploadsDir = realpath(__DIR__ . '/../../uploads/produk');
}

$imagesDir = realpath(__DIR__ . '/../../images');
if (!$imagesDir) {
    mkdir(__DIR__ . '/../../images', 0777, true);
    $imagesDir = realpath(__DIR__ . '/../../images');
}

$items = [
    // 01
    [
        'folder' => 'Black athletic performance t-shirt',
        'depan'   => 'Gemini_Generated_Image_4r4ms94r4ms94r4m.jpg',
        'belakang'=> 'Gemini_Generated_Image_bezr28bezr28bezr.jpg',
        'detail'  => 'Gemini_Generated_Image_u1nagsu1nagsu1na.jpg',
        'slug'    => 'baju-01-athletic-performance-hitam',
    ],
    // 02
    [
        'folder' => 'Blue and white striped shirt (kemeja)',
        'depan'   => 'Gemini_Generated_Image_i8i0lti8i0lti8i0.jpg',
        'belakang'=> 'Gemini_Generated_Image_76vrg476vrg476vr.jpg',
        'detail'  => 'Gemini_Generated_Image_v9hgg2v9hgg2v9hg.jpg',
        'slug'    => 'kemeja-02-garis-biru-putih',
    ],
    // 03
    [
        'folder' => 'Gray cotton t-shirt',
        'depan'   => 'Gemini_Generated_Image_mkf49dmkf49dmkf4.jpg',
        'belakang'=> 'Gemini_Generated_Image_tj499ktj499ktj49.jpg',
        'detail'  => 'Gemini_Generated_Image_fhlsz3fhlsz3fhls.jpg',
        'slug'    => 'baju-03-katun-abu-abu',
    ],
    // 04
    [
        'folder' => 'Heather gray heavyweight cotton t-shirt',
        'depan'   => 'Gemini_Generated_Image_i9iz6ei9iz6ei9iz.jpg',
        'belakang'=> 'Gemini_Generated_Image_7k21907k21907k21.jpg',
        'detail'  => 'Gemini_Generated_Image_u9ykshu9ykshu9yk.jpg',
        'slug'    => 'baju-04-heavyweight-heather-gray',
    ],
    // 05
    [
        'folder' => 'Khaki work shirt(kemeja)',
        'depan'   => 'Gemini_Generated_Image_d3672kd3672kd367.jpg',
        'belakang'=> 'Gemini_Generated_Image_rquweerquweerquw.jpg',
        'detail'  => 'lanjut Product 11 (Khaki work shirt) - DETAIL view..jpg',
        'slug'    => 'kemeja-05-workwear-khaki',
    ],
    // 06
    [
        'folder' => 'Navy blue striped t-shirt',
        'depan'   => 'Gemini_Generated_Image_cr67dncr67dncr67.jpg',
        'belakang'=> 'Gemini_Generated_Image_8rfmew8rfmew8rfm.jpg',
        'detail'  => 'Gemini_Generated_Image_41cb1t41cb1t41cb.jpg',
        'slug'    => 'baju-06-striped-navy-putih',
    ],
    // 07
    [
        'folder' => 'Navy piqué polo shirt',
        'depan'   => 'Gemini_Generated_Image_2zctpj2zctpj2zct.jpg',
        'belakang'=> 'Gemini_Generated_Image_z8aw7fz8aw7fz8aw.jpg',
        'detail'  => 'Gemini_Generated_Image_cg1rbocg1rbocg1r.jpg',
        'slug'    => 'baju-07-polo-pique-navy',
    ],
    // 08
    [
        'folder' => 'Olive green cotton t-shirt',
        'depan'   => 'Gemini_Generated_Image_rphec1rphec1rphe.jpg',
        'belakang'=> 'Gemini_Generated_Image_5ha9nr5ha9nr5ha9.jpg',
        'detail'  => 'Gemini_Generated_Image_d0hrwcd0hrwcd0hr.jpg',
        'slug'    => 'baju-08-katun-olive-green',
    ],
    // 09
    [
        'folder' => 'Oversized white cotton t-shirt with chest print',
        'depan'   => 'Gemini_Generated_Image_qjh2soqjh2soqjh2.jpg',
        'belakang'=> 'Gemini_Generated_Image_oiozbeoiozbeoioz.jpg',
        'detail'  => 'Gemini_Generated_Image_gwkjq7gwkjq7gwkj.jpg',
        'slug'    => 'baju-09-oversized-graphic-putih',
    ],
    // 10
    [
        'folder' => 'Plain black cotton t-shirt',
        'depan'   => 'Gemini_Generated_Image_j68y4pj68y4pj68y.jpg',
        'belakang'=> 'Gemini_Generated_Image_dw3ob0dw3ob0dw3o.jpg',
        'detail'  => 'Gemini_Generated_Image_sbai9nsbai9nsbai.jpg',
        'slug'    => 'baju-10-polos-jet-black',
    ],
    // 11
    [
        'folder' => 'White cotton t-shirt',
        'depan'   => 'Gemini_Generated_Image_cx6y6fcx6y6fcx6y.jpg',
        'belakang'=> 'Gemini_Generated_Image_ddv3q4ddv3q4ddv3.jpg',
        'detail'  => 'Gemini_Generated_Image_j724j2j724j2j724.jpg',
        'slug'    => 'baju-11-polos-putih-classic',
    ],
    // 12
    [
        'folder' => 'White oxford button-down shirt (kemeja)',
        'depan'   => 'Gemini_Generated_Image_dpewm4dpewm4dpew.jpg',
        'belakang'=> 'Gemini_Generated_Image_8fa90e8fa90e8fa9.jpg',
        'detail'  => 'Gemini_Generated_Image_tgcgy0tgcgy0tgcg.jpg',
        'slug'    => 'kemeja-12-oxford-button-down-putih',
    ],
    // 13
    [
        'folder' => '(Classic blue denim jeans, Levi\'s 501-style',
        'depan'   => 'Gemini_Generated_Image_bcxipdbcxipdbcxi.jpg',
        'belakang'=> 'Gemini_Generated_Image_y2s4duy2s4duy2s4.jpg',
        'detail'  => 'Gemini_Generated_Image_md6qn3md6qn3md6q.jpg',
        'slug'    => 'celana-13-denim-regular-classic-blue',
    ],
    // 14
    [
        'folder' => 'Black puffer jacket, The North Face-style',
        'depan'   => 'Gemini_Generated_Image_cjjybicjjybicjjy.jpg',
        'belakang'=> 'Gemini_Generated_Image_u3dx9uu3dx9uu3dx.jpg',
        'detail'  => 'Gemini_Generated_Image_l768xkl768xkl768.jpg',
        'slug'    => 'jacket-14-puffer-down-hitam',
    ],
    // 15
    [
        'folder' => 'Black track pants, Adidas-style',
        'depan'   => 'Gemini_Generated_Image_v0jo5hv0jo5hv0jo.jpg',
        'belakang'=> 'Gemini_Generated_Image_u02je0u02je0u02j.jpg',
        'detail'  => 'Gemini_Generated_Image_cq00zocq00zocq00.jpg',
        'slug'    => 'celana-15-track-pants-hitam',
    ],
    // 16
    [
        'folder' => 'Black twill chino work pants, Dickies 874-style',
        'depan'   => 'Gemini_Generated_Image_a1pnmwa1pnmwa1pn.jpg',
        'belakang'=> 'Gemini_Generated_Image_vkj45ivkj45ivkj4.jpg',
        'detail'  => 'Gemini_Generated_Image_djura7djura7djur.jpg',
        'slug'    => 'celana-16-chino-twill-hitam',
    ],
    // 17
    [
        'folder' => 'Black washed denim trucker jacket',
        'depan'   => 'Gemini_Generated_Image_3xnc373xnc373xnc.jpg',
        'belakang'=> 'Gemini_Generated_Image_4cj2om4cj2om4cj2.jpg',
        'detail'  => 'Gemini_Generated_Image_4cj2om4cj2om4cj2 (2).jpg',
        'slug'    => 'jacket-17-denim-trucker-washed-black',
    ],
    // 18
    [
        'folder' => 'Brown canvas double-knee work pants, Carhartt-style',
        'depan'   => 'Gemini_Generated_Image_ukg5pgukg5pgukg5.jpg',
        'belakang'=> 'Gemini_Generated_Image_9bv7x9bv7x9bv7x9.jpg',
        'detail'  => 'Gemini_Generated_Image_6sbsk6sbsk6sbsk6.jpg',
        'slug'    => 'celana-18-double-knee-canvas-cokelat',
    ],
    // 19
    [
        'folder' => 'Brown duck canvas chore coat, Carhartt-style',
        'depan'   => 'Gemini_Generated_Image_2g5drx2g5drx2g5d.jpg',
        'belakang'=> 'Gemini_Generated_Image_25y81y25y81y25y8.jpg',
        'detail'  => 'Gemini_Generated_Image_vdc5oxvdc5oxvdc5.jpg',
        'slug'    => 'jacket-19-chore-coat-canvas-cokelat',
    ],
    // 20
    [
        'folder' => 'Forest green fleece jacket, Patagonia-style',
        'depan'   => 'Gemini_Generated_Image_rrl0ntrrl0ntrrl0.jpg',
        'belakang'=> 'Gemini_Generated_Image_s0iab6s0iab6s0ia.jpg',
        'detail'  => 'Gemini_Generated_Image_tl3mtutl3mtutl3m.jpg',
        'slug'    => 'jacket-20-fleece-sherpa-forest-green',
    ],
    // 21
    [
        'folder' => 'Khaki straight-leg work pants, Dickies 874-style',
        'depan'   => 'Gemini_Generated_Image_8rrvbb8rrvbb8rrv.jpg',
        'belakang'=> 'Gemini_Generated_Image_bes787bes787bes7.jpg',
        'detail'  => 'Gemini_Generated_Image_kvy43pkvy43pkvy4.jpg',
        'slug'    => 'celana-21-straight-leg-khaki',
    ],
    // 22
    [
        'folder' => 'Navy lightweight windbreaker, Nike-style',
        'depan'   => 'Gemini_Generated_Image_3ihrwx3ihrwx3ihr.jpg',
        'belakang'=> 'Gemini_Generated_Image_pr912mpr912mpr91.jpg',
        'detail'  => 'Gemini_Generated_Image_m5qmilm5qmilm5qm.jpg',
        'slug'    => 'jacket-22-windbreaker-parasut-navy',
    ],
    // 23
    [
        'folder' => 'Olive green ripstop cargo pants, utility-style',
        'depan'   => 'Gemini_Generated_Image_lgf64xlgf64xlgf6.jpg',
        'belakang'=> 'Gemini_Generated_Image_oizozuoizozuoizo.jpg',
        'detail'  => 'Gemini_Generated_Image_g9yy54g9yy54g9yy.jpg',
        'slug'    => 'celana-23-cargo-ripstop-olive-green',
    ],
    // 24
    [
        'folder' => 'Vintage medium-wash blue denim straight-leg jeans, Levi\'s 501-style',
        'depan'   => 'Gemini_Generated_Image_d0qdund0qdund0qd.jpg',
        'belakang'=> 'Gemini_Generated_Image_lo8euqlo8euqlo8e.jpg',
        'detail'  => 'Gemini_Generated_Image_y5bagyy5bagyy5ba.jpg',
        'slug'    => 'celana-24-denim-vintage-medium-wash',
    ],
];

echo "=== MEMULAI KONVERSI WEBP (PHP GD) UNTUK 24 PRODUK (72 FOTO) ===\n";
echo "Folder Sumber: $sourceBase\n";
echo "Folder Uploads: $uploadsDir\n";
echo "Folder Images : $imagesDir\n\n";

printf("%-50s | %-48s | %-10s | %-10s | %-8s\n", "File Sumber", "File Hasil (WebP)", "Awal", "WebP", "Hemat");
echo str_repeat("-", 136) . "\n";

$totalBefore = 0;
$totalAfter = 0;
$successCount = 0;

foreach ($items as $item) {
    $types = ['depan', 'belakang', 'detail'];
    foreach ($types as $type) {
        $sourceFile = $item[$type];
        if (!$sourceFile) {
            echo "LEWAT: {$item['folder']} [$type] tidak ada file.\n";
            continue;
        }

        $sourcePath = $sourceBase . DIRECTORY_SEPARATOR . $item['folder'] . DIRECTORY_SEPARATOR . $sourceFile;
        if (!file_exists($sourcePath)) {
            echo "ERROR: File tidak ditemukan: $sourcePath\n";
            continue;
        }

        $outName = "{$item['slug']}-{$type}.webp";
        $outPathUploads = $uploadsDir . DIRECTORY_SEPARATOR . $outName;
        $outPathImages = $imagesDir . DIRECTORY_SEPARATOR . $outName;

        $fileSizeBefore = filesize($sourcePath);
        $totalBefore += $fileSizeBefore;

        // Idempoten: Cek apakah file sudah pernah dikonversi dengan ukuran valid dan file sumber tidak lebih baru
        if (file_exists($outPathUploads) && file_exists($outPathImages) && filesize($outPathUploads) > 1000 && filemtime($outPathUploads) >= filemtime($sourcePath)) {
            $fileSizeAfter = filesize($outPathUploads);
            $totalAfter += $fileSizeAfter;
            $saved = round((1 - ($fileSizeAfter / $fileSizeBefore)) * 100, 1);
            printf("%-50s | %-48s | %-10s | %-10s | %-7s%% (Ada)\n", 
                substr($sourceFile, 0, 48), 
                substr($outName, 0, 46), 
                round($fileSizeBefore / 1024) . " KB", 
                round($fileSizeAfter / 1024) . " KB", 
                $saved
            );
            $successCount++;
            continue;
        }

        // Buka gambar sumber
        $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
        if ($ext === 'jpg' || $ext === 'jpeg') {
            $src = @imagecreatefromjpeg($sourcePath);
        } elseif ($ext === 'png') {
            $src = @imagecreatefrompng($sourcePath);
        } else {
            echo "ERROR: Format tidak didukung: $sourcePath\n";
            continue;
        }

        if (!$src) {
            echo "ERROR: Gagal membaca gambar: $sourcePath\n";
            continue;
        }

        $origW = imagesx($src);
        $origH = imagesy($src);

        // Jika asli > 2048px, perkecil ke 2048px. Jika <= 2048px, pertahankan resolusi asli agar tetap HD
        $targetW = min(2048, $origW);
        $targetH = min(2048, $origH);

        if ($targetW !== $origW || $targetH !== $origH) {
            $dest = imagecreatetruecolor($targetW, $targetH);
            // Latar putih
            $white = imagecolorallocate($dest, 255, 255, 255);
            imagefill($dest, 0, 0, $white);
            imagecopyresampled($dest, $src, 0, 0, 0, 0, $targetW, $targetH, $origW, $origH);
        } else {
            $dest = $src;
        }

        // Simpan ke WebP kualitas 88
        $okUploads = imagewebp($dest, $outPathUploads, 88);
        $okImages = imagewebp($dest, $outPathImages, 88);

        if ($dest !== $src) {
            imagedestroy($dest);
        }
        imagedestroy($src);

        if ($okUploads && $okImages) {
            $fileSizeAfter = filesize($outPathUploads);
            $totalAfter += $fileSizeAfter;
            $saved = round((1 - ($fileSizeAfter / $fileSizeBefore)) * 100, 1);
            printf("%-50s | %-48s | %-10s | %-10s | %-7s%%\n", 
                substr($sourceFile, 0, 48), 
                substr($outName, 0, 46), 
                round($fileSizeBefore / 1024) . " KB", 
                round($fileSizeAfter / 1024) . " KB", 
                $saved
            );
            $successCount++;
        } else {
            echo "ERROR: Gagal menulis WebP: $outName\n";
        }
    }
}

echo str_repeat("-", 136) . "\n";
echo "RINGKASAN KONVERSI WEBP:\n";
echo "- Total File Berhasil Di-generate : $successCount dari " . (count($items) * 3) . " target\n";
echo "- Total Ukuran Asli               : " . round($totalBefore / (1024 * 1024), 2) . " MB\n";
echo "- Total Ukuran WebP               : " . round($totalAfter / (1024 * 1024), 2) . " MB\n";
$totalSaved = $totalBefore > 0 ? round((1 - ($totalAfter / $totalBefore)) * 100, 1) : 0;
echo "- Efisiensi Ruang Tersimpan       : " . $totalSaved . "%\n";
