<?php
// [FILE] produk_form.php — Formulir Tambah & Edit Produk Preloved (100% No-JS)
// Dipakai oleh: Administrator SecondSpace
// Tabel database disentuh: products, product_images, riwayat_stok
// Fungsi bersama: /shared/config.php, /shared/koneksi.php, /shared/fungsi.php, /shared/image_helper.php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../shared/koneksi.php';
require_once __DIR__ . '/../shared/fungsi.php';
require_once __DIR__ . '/../shared/image_helper.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;

$product = [
    'sku'         => '',
    'name'        => '',
    'category'    => 'Baju',
    'brand'       => 'No Brand',
    'size'        => 'L',
    'condition'   => 'Bagus',
    'material'    => '',
    'price'       => 50000,
    'stock'       => 1,
    'description' => '',
    'image'       => '',
];

$galleryImages = [];
$existingImgBack = '';
$existingImgDetail = '';

// [QUERY: Ambil Data Produk Jika Mode Edit]
if ($isEdit) {
    $detail = ambil_produk_detail($conn, $id);
    if (!$detail) {
        flash_set('error', 'Produk tidak ditemukan.');
        header("Location: produk.php");
        exit;
    }
    $product = $detail;
    $galleryImages = $detail['gallery'] ?? [];

    if (!empty($galleryImages)) {
        foreach ($galleryImages as $g) {
            $t = strtolower($g['type'] ?? '');
            $so = (int)($g['sort_order'] ?? 0);
            if ($t === 'belakang' || $t === 'back' || $so === 2) {
                $existingImgBack = $g['image'];
            } elseif ($t === 'detail' || $so === 3) {
                $existingImgDetail = $g['image'];
            }
        }
    }
}

$errorMsg = "";

// [AKSI: Proses Simpan Data Form POST]
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errorMsg = "Token keamanan form tidak valid. Silakan coba lagi.";
    } elseif ($isEdit && isset($_POST['delete_photo'])) {
        $photoType = trim($_POST['delete_photo']);
        if ($photoType === 'depan') {
            $oldImg = $product['image'];
            $conn->query("UPDATE products SET image = '' WHERE id = " . (int)$id);
            $conn->query("DELETE FROM product_images WHERE product_id = " . (int)$id . " AND (type = 'depan' OR sort_order = 1)");
            if (!empty($oldImg) && !str_starts_with($oldImg, 'baju-') && !str_starts_with($oldImg, 'celana-') && !str_starts_with($oldImg, 'jacket-') && !str_starts_with($oldImg, 'kemeja-')) {
                @unlink(__DIR__ . '/../images/' . $oldImg);
                @unlink(__DIR__ . '/../user/images/' . $oldImg);
                @unlink(__DIR__ . '/../uploads/produk/' . $oldImg);
            }
            flash_set('success', 'Foto utama produk berhasil dihapus.');
            header("Location: produk_form.php?id=" . $id);
            exit;
        } elseif ($photoType === 'belakang') {
            $delStmt = $conn->prepare("SELECT image FROM product_images WHERE product_id = ? AND (type = 'belakang' OR sort_order = 2) LIMIT 1");
            $delStmt->bind_param("i", $id);
            $delStmt->execute();
            $delRow = $delStmt->get_result()->fetch_assoc();
            $delStmt->close();
            if ($delRow && !empty($delRow['image'])) {
                $oldImg = $delRow['image'];
                if (!str_starts_with($oldImg, 'baju-') && !str_starts_with($oldImg, 'celana-') && !str_starts_with($oldImg, 'jacket-') && !str_starts_with($oldImg, 'kemeja-')) {
                    @unlink(__DIR__ . '/../images/' . $oldImg);
                    @unlink(__DIR__ . '/../user/images/' . $oldImg);
                    @unlink(__DIR__ . '/../uploads/produk/' . $oldImg);
                }
            }
            $conn->query("DELETE FROM product_images WHERE product_id = " . (int)$id . " AND (type = 'belakang' OR sort_order = 2)");
            flash_set('success', 'Foto belakang produk berhasil dihapus.');
            header("Location: produk_form.php?id=" . $id);
            exit;
        } elseif ($photoType === 'detail') {
            $delStmt = $conn->prepare("SELECT image FROM product_images WHERE product_id = ? AND (type = 'detail' OR sort_order = 3) LIMIT 1");
            $delStmt->bind_param("i", $id);
            $delStmt->execute();
            $delRow = $delStmt->get_result()->fetch_assoc();
            $delStmt->close();
            if ($delRow && !empty($delRow['image'])) {
                $oldImg = $delRow['image'];
                if (!str_starts_with($oldImg, 'baju-') && !str_starts_with($oldImg, 'celana-') && !str_starts_with($oldImg, 'jacket-') && !str_starts_with($oldImg, 'kemeja-')) {
                    @unlink(__DIR__ . '/../images/' . $oldImg);
                    @unlink(__DIR__ . '/../user/images/' . $oldImg);
                    @unlink(__DIR__ . '/../uploads/produk/' . $oldImg);
                }
            }
            $conn->query("DELETE FROM product_images WHERE product_id = " . (int)$id . " AND (type = 'detail' OR sort_order = 3)");
            flash_set('success', 'Foto detail / label produk berhasil dihapus.');
            header("Location: produk_form.php?id=" . $id);
            exit;
        }
    } else {
        $name        = trim($_POST['name'] ?? '');
        $category    = trim($_POST['category'] ?? '');
        $brand       = trim($_POST['brand'] ?? 'No Brand') ?: 'No Brand';
        $size        = trim($_POST['size'] ?? 'L');
        $condition   = trim($_POST['condition'] ?? 'Bagus');
        $material    = trim($_POST['material'] ?? '');
        $price       = max(0, (float)($_POST['price'] ?? 0));
        $stock       = max(0, (int)($_POST['stock'] ?? 1));
        $description = trim($_POST['description'] ?? '');

        // [VALIDASI: Input Wajib]
        if (empty($name) || empty($category) || $price <= 0) {
            $errorMsg = "Nama produk, kategori, dan harga wajib diisi dengan benar.";
        } else {
            // Generate SKU internal otomatis (admin/user tidak perlu mengelola SKU)
            if (!$isEdit) {
                $maxIdRes = $conn->query("SELECT MAX(id) as max_id FROM products");
                $nextNum = ($maxIdRes && $r = $maxIdRes->fetch_assoc()) ? ((int)$r['max_id'] + 1) : 1;
                $sku = sprintf("PRD-%04d", $nextNum);
            } else {
                $sku = !empty($product['sku']) ? $product['sku'] : sprintf("PRD-%04d", $id);
            }

            // Upload & Konversi Gambar Utama via PHP GD
            $mainImageFile = $product['image'];
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadRes = processAndConvertToWebP($_FILES['image'], __DIR__ . '/../images/', 88, 2048);
                if ($uploadRes['success']) {
                    $mainImageFile = $uploadRes['filename'];
                    // Sinkronkan ke seluruh folder penyimpanan produk
                    @copy(__DIR__ . '/../images/' . $mainImageFile, __DIR__ . '/../uploads/produk/' . $mainImageFile);
                    @copy(__DIR__ . '/../images/' . $mainImageFile, __DIR__ . '/../user/images/' . $mainImageFile);
                } else {
                    $errorMsg = "Gagal memproses gambar utama: " . $uploadRes['error'];
                }
            }

            if (empty($errorMsg)) {
                $conn->begin_transaction();
                try {
                    if ($isEdit) {
                        // [QUERY: Update Produk Eksisting]
                        $upStmt = $conn->prepare("UPDATE products SET sku = ?, name = ?, category = ?, brand = ?, size = ?, `condition` = ?, condition_text = ?, material = ?, price = ?, stock = ?, description = ?, image = ? WHERE id = ?");
                        $upStmt->bind_param("ssssssssdissi", $sku, $name, $category, $brand, $size, $condition, $condition, $material, $price, $stock, $description, $mainImageFile, $id);
                        $upStmt->execute();
                        $upStmt->close();
                        $productId = $id;
                    } else {
                        // [QUERY: Insert Produk Baru]
                        $inStmt = $conn->prepare("INSERT INTO products (sku, name, category, brand, size, `condition`, condition_text, material, price, stock, description, image, is_demo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)");
                        $inStmt->bind_param("ssssssssdiss", $sku, $name, $category, $brand, $size, $condition, $condition, $material, $price, $stock, $description, $mainImageFile);
                        $inStmt->execute();
                        $productId = $conn->insert_id;
                        $inStmt->close();

                        // Catat mutasi stok awal
                        $adminId = (int)($_SESSION['user_id'] ?? 1);
                        ubah_stok($conn, $productId, $stock, "Stok Awal Penambahan Produk", null, $adminId);
                    }

                    // Sinkronkan gambar utama ke product_images (urutan 1 / depan)
                    if (!empty($mainImageFile)) {
                        $chkImg = $conn->prepare("SELECT id FROM product_images WHERE product_id = ? AND sort_order = 1");
                        $chkImg->bind_param("i", $productId);
                        $chkImg->execute();
                        $existImg = $chkImg->get_result()->fetch_assoc();
                        $chkImg->close();

                        if ($existImg) {
                            $upImg = $conn->prepare("UPDATE product_images SET image = ? WHERE id = ?");
                            $upImg->bind_param("si", $mainImageFile, $existImg['id']);
                            $upImg->execute();
                            $upImg->close();
                        } else {
                            $insImg = $conn->prepare("INSERT INTO product_images (product_id, image, sort_order, type) VALUES (?, ?, 1, 'depan')");
                            $insImg->bind_param("is", $productId, $mainImageFile);
                            $insImg->execute();
                            $insImg->close();
                        }
                    }

                    // Upload Foto Tambahan: Belakang (urutan 2)
                    if (isset($_FILES['image_back']) && $_FILES['image_back']['error'] === UPLOAD_ERR_OK) {
                        $upBack = processAndConvertToWebP($_FILES['image_back'], __DIR__ . '/../images/', 88, 2048);
                        if ($upBack['success']) {
                            $fileBack = $upBack['filename'];
                            @copy(__DIR__ . '/../images/' . $fileBack, __DIR__ . '/../uploads/produk/' . $fileBack);
                            @copy(__DIR__ . '/../images/' . $fileBack, __DIR__ . '/../user/images/' . $fileBack);
                            $chkBack = $conn->prepare("SELECT id FROM product_images WHERE product_id = ? AND (type = 'belakang' OR sort_order = 2) LIMIT 1");
                            $chkBack->bind_param("i", $productId);
                            $chkBack->execute();
                            $existBack = $chkBack->get_result()->fetch_assoc();
                            $chkBack->close();
                            if ($existBack) {
                                $upB = $conn->prepare("UPDATE product_images SET image = ?, sort_order = 2, type = 'belakang' WHERE id = ?");
                                $upB->bind_param("si", $fileBack, $existBack['id']);
                                $upB->execute();
                                $upB->close();
                            } else {
                                $insB = $conn->prepare("INSERT INTO product_images (product_id, image, sort_order, type) VALUES (?, ?, 2, 'belakang')");
                                $insB->bind_param("is", $productId, $fileBack);
                                $insB->execute();
                                $insB->close();
                            }
                        }
                    }

                    // Upload Foto Tambahan: Detail (urutan 3)
                    if (isset($_FILES['image_detail']) && $_FILES['image_detail']['error'] === UPLOAD_ERR_OK) {
                        $upDet = processAndConvertToWebP($_FILES['image_detail'], __DIR__ . '/../images/', 88, 2048);
                        if ($upDet['success']) {
                            $fileDet = $upDet['filename'];
                            @copy(__DIR__ . '/../images/' . $fileDet, __DIR__ . '/../uploads/produk/' . $fileDet);
                            @copy(__DIR__ . '/../images/' . $fileDet, __DIR__ . '/../user/images/' . $fileDet);
                            $chkDet = $conn->prepare("SELECT id FROM product_images WHERE product_id = ? AND (type = 'detail' OR sort_order = 3) LIMIT 1");
                            $chkDet->bind_param("i", $productId);
                            $chkDet->execute();
                            $existDet = $chkDet->get_result()->fetch_assoc();
                            $chkDet->close();
                            if ($existDet) {
                                $upD = $conn->prepare("UPDATE product_images SET image = ?, sort_order = 3, type = 'detail' WHERE id = ?");
                                $upD->bind_param("si", $fileDet, $existDet['id']);
                                $upD->execute();
                                $upD->close();
                            } else {
                                $insD = $conn->prepare("INSERT INTO product_images (product_id, image, sort_order, type) VALUES (?, ?, 3, 'detail')");
                                $insD->bind_param("is", $productId, $fileDet);
                                $insD->execute();
                                $insD->close();
                            }
                        }
                    }

                    $conn->commit();
                    flash_set('success', $isEdit ? 'Data produk berhasil diperbarui.' : 'Produk baru berhasil ditambahkan.');
                    header("Location: produk.php");
                    exit;

                } catch (Throwable $e) {
                    $conn->rollback();
                    $errorMsg = "Gagal menyimpan ke database: " . $e->getMessage();
                }
            }
        }
    }
}

$categoriesList = ambil_semua_kategori($conn);
?>
<!-- [TAMPILAN: Formulir Produk Admin] -->
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEdit ? 'Edit Produk' : 'Tambah Produk' ?> — <?= NAMA_TOKO ?> Admin</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>

<div class="admin-wrapper">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="admin-main">
        <header class="admin-topbar">
            <h1 class="topbar-title"><?= $isEdit ? 'Edit Produk: ' . htmlspecialchars($product['name']) : 'Tambah Produk Baru' ?></h1>
            <div class="topbar-actions">
                <a href="produk.php" class="btn btn-secondary btn-sm">&larr; Kembali ke Daftar</a>
            </div>
        </header>

        <div class="admin-content" style="max-width: 900px;">
            <?php if ($flashSuccess = flash_get('success')): ?>
                <div class="flash-msg success"><span class="status-badge-icon">[OK]</span> <?= htmlspecialchars($flashSuccess) ?></div>
            <?php endif; ?>

            <?php if (!empty($errorMsg)): ?>
                <div class="flash-msg error"><span class="status-badge-icon">[!]</span> <?= htmlspecialchars($errorMsg) ?></div>
            <?php endif; ?>

            <div class="table-card" style="padding: 32px;">
                <!-- [BAGIAN: Form Input POST (Enctype Multipart - No JS)] -->
                <form method="POST" action="produk_form.php<?= $isEdit ? '?id=' . $id : '' ?>" enctype="multipart/form-data">
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label class="form-label" for="category">Kategori Pakaian *</label>
                        <select id="category" name="category" class="form-control" required>
                            <?php foreach ($categoriesList as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>" <?= ($product['category'] === $cat) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="name">Nama Produk Lengkap *</label>
                        <input type="text" id="name" name="name" class="form-control" placeholder="Contoh: Kaos Heavyweight Boxy Heather Gray" required value="<?= htmlspecialchars($_POST['name'] ?? $product['name']) ?>">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="brand">Merek / Brand</label>
                            <input type="text" id="brand" name="brand" class="form-control" placeholder="Contoh: No Brand / Levi's" value="<?= htmlspecialchars($_POST['brand'] ?? $product['brand']) ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="size">Ukuran (Size) *</label>
                            <input type="text" id="size" name="size" class="form-control" placeholder="S, M, L, XL, 30, 32" required value="<?= htmlspecialchars($_POST['size'] ?? $product['size']) ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="condition">Kondisi Barang *</label>
                            <select id="condition" name="condition" class="form-control" required>
                                <option value="Bagus" <?= ($product['condition'] === 'Bagus') ? 'selected' : '' ?>>Bagus (Standar Preloved)</option>
                                <option value="Seperti Baru" <?= ($product['condition'] === 'Seperti Baru') ? 'selected' : '' ?>>Seperti Baru (Like New)</option>
                                <option value="Ada Minus" <?= ($product['condition'] === 'Ada Minus') ? 'selected' : '' ?>>Ada Minus (Defect Ringan)</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="material">Jenis Bahan</label>
                            <input type="text" id="material" name="material" class="form-control" placeholder="Contoh: Katun Combed 24s / Denim 14 oz" value="<?= htmlspecialchars($_POST['material'] ?? ($product['material'] ?? '')) ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="price">Harga Jual (Rp) *</label>
                            <input type="number" id="price" name="price" class="form-control" step="1000" min="0" placeholder="75000" required value="<?= htmlspecialchars($_POST['price'] ?? $product['price']) ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="stock">Stok Tersedia *</label>
                            <input type="number" id="stock" name="stock" class="form-control" min="0" required value="<?= htmlspecialchars($_POST['stock'] ?? $product['stock']) ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="description">Deskripsi Produk (Bahasa Indonesia)</label>
                        <textarea id="description" name="description" class="form-control" rows="4" placeholder="Jelaskan karakteristik, bahan, model potongan, dan kondisi detail pakaian..."><?= htmlspecialchars($_POST['description'] ?? $product['description']) ?></textarea>
                    </div>

                    <!-- [BAGIAN: Upload Foto Produk WebP Otomatis via GD] -->
                    <div style="border-top: 1px solid var(--ln); margin: 28px 0; padding-top: 24px;">
                        <h3 class="table-title" style="margin-bottom: 16px;">Foto Produk (Otomatis Dikonversi ke WebP HD)</h3>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="image">Foto Utama (Depan) <?= empty($product['image']) ? '*' : '' ?></label>
                                <?php if (!empty($product['image'])): ?>
                                    <?php $imgDepanUrl = gambar_produk_url($product['image'], 'admin'); ?>
                                    <div style="margin-bottom: 8px; display: flex; align-items: center; gap: 10px;">
                                        <img src="<?= htmlspecialchars($imgDepanUrl ?: '../images/' . $product['image']) ?>" alt="Foto Depan" width="80" height="80" style="object-fit: cover; border-radius: 4px; border: 1px solid var(--ln);">
                                        <button type="submit" name="delete_photo" value="depan" formnovalidate class="btn btn-secondary btn-sm" style="color: #ff6b6b; border-color: #552222; font-size: 11px; padding: 6px 10px; cursor: pointer;">
                                            Hapus Foto
                                        </button>
                                    </div>
                                <?php endif; ?>
                                <input type="file" id="image" name="image" class="form-control" accept="image/jpeg,image/png,image/webp" <?= (!$isEdit || empty($product['image'])) ? 'required' : '' ?>>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="image_back">Foto Belakang</label>
                                <?php if (!empty($existingImgBack)): ?>
                                    <?php $imgBackUrl = gambar_produk_url($existingImgBack, 'admin'); ?>
                                    <div style="margin-bottom: 8px; display: flex; align-items: center; gap: 10px;">
                                        <img src="<?= htmlspecialchars($imgBackUrl ?: '../images/' . $existingImgBack) ?>" alt="Foto Belakang" width="80" height="80" style="object-fit: cover; border-radius: 4px; border: 1px solid var(--ln);">
                                        <button type="submit" name="delete_photo" value="belakang" formnovalidate class="btn btn-secondary btn-sm" style="color: #ff6b6b; border-color: #552222; font-size: 11px; padding: 6px 10px; cursor: pointer;">
                                            Hapus Foto
                                        </button>
                                    </div>
                                <?php endif; ?>
                                <input type="file" id="image_back" name="image_back" class="form-control" accept="image/jpeg,image/png,image/webp">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="image_detail">Foto Detail / Label</label>
                                <?php if (!empty($existingImgDetail)): ?>
                                    <?php $imgDetailUrl = gambar_produk_url($existingImgDetail, 'admin'); ?>
                                    <div style="margin-bottom: 8px; display: flex; align-items: center; gap: 10px;">
                                        <img src="<?= htmlspecialchars($imgDetailUrl ?: '../images/' . $existingImgDetail) ?>" alt="Foto Detail" width="80" height="80" style="object-fit: cover; border-radius: 4px; border: 1px solid var(--ln);">
                                        <button type="submit" name="delete_photo" value="detail" formnovalidate class="btn btn-secondary btn-sm" style="color: #ff6b6b; border-color: #552222; font-size: 11px; padding: 6px 10px; cursor: pointer;">
                                            Hapus Foto
                                        </button>
                                    </div>
                                <?php endif; ?>
                                <input type="file" id="image_detail" name="image_detail" class="form-control" accept="image/jpeg,image/png,image/webp">
                            </div>
                        </div>
                    </div>
                    <!-- [/BAGIAN: Upload Foto Produk WebP Otomatis via GD] -->

                    <div style="display: flex; gap: 14px; margin-top: 20px;">
                        <button type="submit" class="btn btn-primary" style="padding: 12px 28px;">
                            <?= $isEdit ? 'SIMPAN PERUBAHAN' : 'TERBITKAN PRODUK' ?>
                        </button>
                        <a href="produk.php" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
                <!-- [/BAGIAN: Form Input POST] -->
            </div>
        </div>
    </main>
</div>

</body>
</html>
<!-- [/TAMPILAN: Formulir Produk Admin] -->
