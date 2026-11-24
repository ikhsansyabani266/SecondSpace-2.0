<?php
// SKRIP DINONAKTIFKAN: Data produk telah bermigrasi menjadi data FINAL (PL-001 s/d PL-024).
// Untuk mencegah penghapusan data secara tidak sengaja, skrip ini dinonaktifkan permanen.
die("SKRIP DINONAKTIFKAN: Seluruh produk SecondSpace kini telah menjadi data FINAL.\n");


$dryRun = in_array('--dry-run', $argv);
$force  = in_array('--force', $argv);

echo "========================================================\n";
echo "   SKRIP PENGHAPUS DATA DEMO PRODUK SEMENTARA           \n";
echo "========================================================\n";

if ($dryRun) {
    echo "MODE: DRY-RUN (Hanya simulasi, tidak ada data yang dihapus)\n\n";
} else {
    echo "MODE: EKSEKUSI NYATA\n\n";
}

// 1. Ambil semua data produk yang memiliki penanda is_demo = 1 atau SKU bertanda DEMO-
$stmt = $conn->prepare("SELECT id, name, sku, image FROM products WHERE is_demo = 1 OR sku LIKE 'DEMO-%'");
$stmt->execute();
$res = $stmt->get_result();

$productsToDelete = [];
while ($row = $res->fetch_assoc()) {
    $productsToDelete[] = $row;
}

if (empty($productsToDelete)) {
    echo "INFO: Tidak ada produk bertanda demo yang ditemukan di database.\n";
    exit(0);
}

echo "Ditemukan " . count($productsToDelete) . " produk demo:\n";
foreach ($productsToDelete as $p) {
    echo " - [{$p['sku']}] #{$p['id']} {$p['name']} (Gambar Utama: {$p['image']})\n";
}
echo "\n";

// 2. Ambil gambar galeri di tabel product_images
$pids = array_column($productsToDelete, 'id');
$placeholders = implode(',', array_fill(0, count($pids), '?'));
$types = str_repeat('i', count($pids));

$imgStmt = $conn->prepare("SELECT id, product_id, image FROM product_images WHERE product_id IN ($placeholders)");
$imgStmt->bind_param($types, ...$pids);
$imgStmt->execute();
$imgRes = $imgStmt->get_result();

$imagesToDelete = [];
while ($row = $imgRes->fetch_assoc()) {
    $imagesToDelete[] = $row;
}

echo "Total " . count($imagesToDelete) . " gambar galeri demo terdeteksi:\n";
foreach ($imagesToDelete as $img) {
    echo " - Product ID #{$img['product_id']}: {$img['image']}\n";
}
echo "\n";

if (!$dryRun && !$force) {
    echo "PERINGATAN: Anda akan menghapus " . count($productsToDelete) . " produk dan filenya!\n";
    echo "Ketik 'HAPUS' dan tekan enter untuk melanjutkan: ";
    $line = trim(fgets(STDIN));
    if ($line !== 'HAPUS') {
        echo "Dibatalkan oleh pengguna.\n";
        exit(0);
    }
}

if ($dryRun) {
    echo "DRY-RUN SELESAI: Simulasi berhasil tanpa menghapus data nyata.\n";
    exit(0);
}

// Eksekusi Penghapusan dalam Transaksi
$conn->begin_transaction();

try {
    // Hapus dari product_images
    $delImages = $conn->prepare("DELETE FROM product_images WHERE product_id IN ($placeholders)");
    $delImages->bind_param($types, ...$pids);
    $delImages->execute();
    $deletedImgCount = $delImages->affected_rows;

    // Hapus dari products
    $delProds = $conn->prepare("DELETE FROM products WHERE id IN ($placeholders)");
    $delProds->bind_param($types, ...$pids);
    $delProds->execute();
    $deletedProdCount = $delProds->affected_rows;

    $conn->commit();
    echo "BERHASIL: $deletedProdCount produk dan $deletedImgCount relasi gambar dihapus dari database.\n";

    // Hapus file fisik gambar WebP demo
    $imagesDir = realpath(__DIR__ . '/../../images');
    $fileDelCount = 0;
    foreach ($imagesToDelete as $img) {
        $filePath = $imagesDir . '/' . $img['image'];
        if (file_exists($filePath) && str_ends_with($filePath, '.webp')) {
            unlink($filePath);
            $fileDelCount++;
        }
    }
    echo "BERHASIL: $fileDelCount file gambar WebP demo dihapus dari folder images/.\n";

} catch (Throwable $e) {
    $conn->rollback();
    echo "ERROR: Gagal menghapus, transaksi dibatalkan (Rollback): " . $e->getMessage() . "\n";
    exit(1);
}
