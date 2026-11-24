<?php
// Script untuk Inventarisasi File dan Bahasa di FASE 1
function scanDirRecursive($dir, &$results = []) {
    $files = scandir($dir);
    foreach ($files as $f) {
        if ($f === '.' || $f === '..' || $f === '.git') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $f;
        $relPath = str_replace(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR, '', $path);
        $relPath = str_replace('\\', '/', $relPath);
        
        // Skip backup folder and uploads/produk_preloved_original from detailed listing if massive
        if (strpos($relPath, 'database/backup') === 0 || strpos($relPath, 'produk_preloved_original') === 0) {
            continue;
        }

        if (is_dir($path)) {
            scanDirRecursive($path, $results);
        } else {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $results[] = [
                'path' => $relPath,
                'ext' => $ext,
                'size' => filesize($path),
            ];
        }
    }
    return $results;
}

$root = dirname(__DIR__, 2);
$files = scanDirRecursive($root);

$extStats = [];
foreach ($files as $f) {
    $e = $f['ext'] ?: '[no extension]';
    if (!isset($extStats[$e])) $extStats[$e] = 0;
    $extStats[$e]++;
}

echo "=== STATISTIK EKSTENSI FILE PROYEK (Di luar .git, backup, & original images) ===\n";
arsort($extStats);
foreach ($extStats as $ext => $cnt) {
    echo sprintf("%-15s: %d file\n", $ext, $cnt);
}

echo "\n=== DAFTAR SEMUA FILE NON-GAMBAR ===\n";
foreach ($files as $f) {
    if (!in_array($f['ext'], ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'ico'])) {
        echo sprintf("%-50s | %-6s | %d bytes\n", $f['path'], $f['ext'], $f['size']);
    }
}
