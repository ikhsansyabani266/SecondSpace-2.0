<?php
// [FILE] shared/image_helper.php — Validasi & Konversi Otomatis Upload Gambar ke Format WebP HD
// Dipakai oleh: admin/produk_form.php
// Tabel database disentuh: Tidak ada (operasi filesystem dan PHP GD image processing)
// Fungsi bersama: processAndConvertToWebP()

if (!function_exists('processAndConvertToWebP')) {
    /**
     * Memproses file upload gambar dan mengonversinya secara otomatis ke WebP HD.
     * 
     * @param array $fileArray $_FILES['image']
     * @param string $uploadDir Folder tujuan (misal '../images/' atau 'images/')
     * @param int $quality Kualitas WebP (default: 88)
     * @param int $maxDimension Dimensi maksimal sisi terpanjang (default: 2048)
     * @return array ['success' => bool, 'filename' => string|null, 'error' => string|null]
     */
    function processAndConvertToWebP($fileArray, $uploadDir = '../images/', $quality = 88, $maxDimension = 2048) {
        if (!isset($fileArray) || $fileArray['error'] === UPLOAD_ERR_NO_FILE) {
            return ['success' => false, 'filename' => null, 'error' => 'Tidak ada file yang diunggah.'];
        }

        if ($fileArray['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'filename' => null, 'error' => 'Gagal mengupload file gambar (Error code: ' . $fileArray['error'] . ').'];
        }

        // Validasi ukuran (maks 5MB)
        if ($fileArray['size'] > 5 * 1024 * 1024) {
            return ['success' => false, 'filename' => null, 'error' => 'Ukuran gambar maksimal 5 MB.'];
        }

        $tmpName = $fileArray['tmp_name'];
        if (!is_uploaded_file($tmpName) && !file_exists($tmpName)) {
            return ['success' => false, 'filename' => null, 'error' => 'File sementara tidak valid.'];
        }

        // Validasi MIME Type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmpName);

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mime, $allowedMimes, true)) {
            return ['success' => false, 'filename' => null, 'error' => 'Format gambar harus berupa JPG, PNG, atau WEBP.'];
        }

        // Buka gambar dengan PHP GD sesuai format aslinya
        $sourceImage = null;
        switch ($mime) {
            case 'image/jpeg':
                $sourceImage = @imagecreatefromjpeg($tmpName);
                break;
            case 'image/png':
                $sourceImage = @imagecreatefrompng($tmpName);
                if ($sourceImage) {
                    imagepalettetotruecolor($sourceImage);
                    imagealphablending($sourceImage, true);
                    imagesavealpha($sourceImage, true);
                }
                break;
            case 'image/webp':
                $sourceImage = @imagecreatefromwebp($tmpName);
                break;
        }

        if (!$sourceImage) {
            return ['success' => false, 'filename' => null, 'error' => 'Gambar tidak dapat diproses oleh PHP GD.'];
        }

        $origW = imagesx($sourceImage);
        $origH = imagesy($sourceImage);

        // Resize proporsional jika melebihi batas maksimal (tanpa distorsi)
        $targetW = $origW;
        $targetH = $origH;
        if ($origW > $maxDimension || $origH > $maxDimension) {
            if ($origW >= $origH) {
                $targetW = $maxDimension;
                $targetH = (int) round(($origH / $origW) * $maxDimension);
            } else {
                $targetH = $maxDimension;
                $targetW = (int) round(($origW / $origH) * $maxDimension);
            }

            $resizedImage = imagecreatetruecolor($targetW, $targetH);
            // Tangani latar belakang transparan bila ada PNG
            $white = imagecolorallocate($resizedImage, 255, 255, 255);
            imagefill($resizedImage, 0, 0, $white);

            imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $targetW, $targetH, $origW, $origH);
            if (function_exists('imagedestroy') && PHP_VERSION_ID < 80500) {
                @imagedestroy($sourceImage);
            }
            $sourceImage = $resizedImage;
        }

        // Buat folder jika belum ada
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Generate nama file WebP yang unik
        $uniqueId = time() . '_' . bin2hex(random_bytes(6));
        $destFilename = 'prod_' . $uniqueId . '.webp';
        $destPath = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $destFilename;

        // Simpan sebagai WebP dengan kualitas yang ditentukan
        $saved = imagewebp($sourceImage, $destPath, $quality);
        if (function_exists('imagedestroy') && PHP_VERSION_ID < 80500) {
            @imagedestroy($sourceImage);
        }

        if (!$saved || !file_exists($destPath)) {
            return ['success' => false, 'filename' => null, 'error' => 'Gagal menyimpan hasil konversi WebP.'];
        }

        return [
            'success'  => true,
            'filename' => $destFilename,
            'error'    => null
        ];
    }
}
