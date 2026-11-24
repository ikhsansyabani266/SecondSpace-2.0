<?php
// CLI Tool: Memasukkan Data Demo Produk & Relasi Gambar (Mendukung --dry-run & Idempoten)
require_once __DIR__ . '/../../db.php';

$dryRun = in_array('--dry-run', $argv);

echo "========================================================\n";
echo "    SKRIP INSERT PRODUK DEMO & GALERI GAMBAR            \n";
echo "========================================================\n";

if ($dryRun) {
    echo "STATUS: MODE DRY-RUN (Hanya simulasi, data TIDAK ditulis ke DB)\n\n";
} else {
    echo "STATUS: MODE EKSEKUSI LANGSUNG (Menulis ke DB)\n\n";
}

// 12 Draf Produk Terkurasi
$productsData = [
    // 01
    [
        'sku'         => 'DEMO-001',
        'name'        => 'Kaos Athletic Performance Hitam Quick-Dry',
        'category'    => 'Baju',
        'brand'       => 'No Brand',
        'size'        => 'L',
        'material'    => 'Polyester Spandex Quick-Dry',
        'condition'   => 'Seperti Baru',
        'price'       => 75000.00,
        'stock'       => 1,
        'description' => 'Kaos olahraga athletic berwarna hitam pekat berbahan polyester quick-dry yang sangat ringan dan sejuk. Jahitan flatlock rapi dan elastis, sangat cocok untuk aktivitas lari, gym, maupun olahraga harian. Kondisi kain sangat mulus tanpa robek, elastisitas kain terjaga prima. Foto ilustrasi sementara.',
        'images'      => [
            ['file' => 'baju-01-athletic-performance-hitam-depan.webp',    'type' => 'depan',   'sort' => 1],
            ['file' => 'baju-01-athletic-performance-hitam-belakang.webp', 'type' => 'belakang','sort' => 2],
            ['file' => 'baju-01-athletic-performance-hitam-detail.webp',   'type' => 'detail',  'sort' => 3],
        ]
    ],
    // 02
    [
        'sku'         => 'DEMO-002',
        'name'        => 'Kemeja Salur Biru Putih Lengan Pendek Casual',
        'category'    => 'Kemeja',
        'brand'       => 'No Brand',
        'size'        => 'M',
        'material'    => 'Katun Poplin',
        'condition'   => 'Sangat Baik',
        'price'       => 110000.00,
        'stock'       => 1,
        'description' => 'Kemeja kasual bermotif garis vertikal biru muda dan putih dengan potongan santai yang rapi. Menggunakan bahan katun poplin adem dengan saku dada kiri dan kancing mutiara utuh. Warna garis masih cerah tanpa pudar, cocok dipadukan dengan celana chino atau denim. Foto ilustrasi sementara.',
        'images'      => [
            ['file' => 'kemeja-02-garis-biru-putih-depan.webp',    'type' => 'depan',   'sort' => 1],
            ['file' => 'kemeja-02-garis-biru-putih-belakang.webp', 'type' => 'belakang','sort' => 2],
            ['file' => 'kemeja-02-garis-biru-putih-detail.webp',   'type' => 'detail',  'sort' => 3],
        ]
    ],
    // 03
    [
        'sku'         => 'DEMO-003',
        'name'        => 'Kaos Polos Abu-Abu Katun Combed Basic',
        'category'    => 'Baju',
        'brand'       => 'No Brand',
        'size'        => 'L',
        'material'    => 'Katun Combed 24s',
        'condition'   => 'Baik',
        'price'       => 55000.00,
        'stock'       => 1,
        'description' => 'Kaos oblong basic esensial berwarna abu-abu terang netral dengan kerah crewneck bergaris rib lembut. Bahan katun combed 24s nyaman menyerap keringat untuk pemakaian santai di rumah maupun bepergian. Terdapat minus pemakaian normal yang wajar, bahan tetap lembut dan jahitan rantai aman. Foto ilustrasi sementara.',
        'images'      => [
            ['file' => 'baju-03-katun-abu-abu-depan.webp',    'type' => 'depan',   'sort' => 1],
            ['file' => 'baju-03-katun-abu-abu-belakang.webp', 'type' => 'belakang','sort' => 2],
            ['file' => 'baju-03-katun-abu-abu-detail.webp',   'type' => 'detail',  'sort' => 3],
        ]
    ],
    // 04
    [
        'sku'         => 'DEMO-004',
        'name'        => 'Kaos Heavyweight Boxy Heather Gray Streetwear',
        'category'    => 'Baju',
        'brand'       => 'No Brand',
        'size'        => 'XL',
        'material'    => 'Katun Heavyweight 16s (230 GSM)',
        'condition'   => 'Seperti Baru',
        'price'       => 85000.00,
        'stock'       => 1,
        'description' => 'Kaos polos bergaya streetwear boxy fit berbahan katun tebal 16s dengan tekstur serat heather misty yang kokoh. Kerah rib tebal dan kaku (tight neck) yang tidak mudah melar setelah dicuci. Kondisi pekat seperti baru, fitting jatuh sempurna untuk gaya casual loose-fit. Foto ilustrasi sementara.',
        'images'      => [
            ['file' => 'baju-04-heavyweight-heather-gray-depan.webp',    'type' => 'depan',   'sort' => 1],
            ['file' => 'baju-04-heavyweight-heather-gray-belakang.webp', 'type' => 'belakang','sort' => 2],
            ['file' => 'baju-04-heavyweight-heather-gray-detail.webp',   'type' => 'detail',  'sort' => 3],
        ]
    ],
    // 05
    [
        'sku'         => 'DEMO-005',
        'name'        => 'Kemeja Utility Workwear Khaki Saku Ganda',
        'category'    => 'Kemeja',
        'brand'       => 'No Brand',
        'size'        => 'L',
        'material'    => 'Katun Twill Tebal',
        'condition'   => 'Ada Minus',
        'price'       => 95000.00,
        'stock'       => 1,
        'description' => 'Kemeja outdoor safari ala workwear berbahan katun twill tebal warna khaki pasir dengan dua saku berkancing di dada. Jahitan ganda kokoh dengan kancing bernuansa kontras maskulin. Terdapat minus samar berupa titik noda pudar di dekat lipatan bawah, namun kain sangat kokoh dan fungsional. Foto ilustrasi sementara.',
        'images'      => [
            ['file' => 'kemeja-05-workwear-khaki-depan.webp',    'type' => 'depan',   'sort' => 1],
            ['file' => 'kemeja-05-workwear-khaki-belakang.webp', 'type' => 'belakang','sort' => 2],
        ]
    ],
    // 06
    [
        'sku'         => 'DEMO-006',
        'name'        => 'Kaos Breton Belang Garis Navy Putih',
        'category'    => 'Baju',
        'brand'       => 'No Brand',
        'size'        => 'M',
        'material'    => 'Katun Yarn-Dyed Knitted',
        'condition'   => 'Sangat Baik',
        'price'       => 70000.00,
        'stock'       => 1,
        'description' => 'Kaos motif garis horizontal nautikal klasik khas pelaut Perancis dengan perpaduan warna navy gelap dan putih susu. Menggunakan rajutan benang yarn-dyed sehingga warna garis tajam dan tidak luntur. Kondisi jahitan sangat rapi, tidak melintir, dan kerah masih kencang. Foto ilustrasi sementara.',
        'images'      => [
            ['file' => 'baju-06-striped-navy-putih-depan.webp',    'type' => 'depan',   'sort' => 1],
            ['file' => 'baju-06-striped-navy-putih-belakang.webp', 'type' => 'belakang','sort' => 2],
            ['file' => 'baju-06-striped-navy-putih-detail.webp',   'type' => 'detail',  'sort' => 3],
        ]
    ],
    // 07
    [
        'sku'         => 'DEMO-007',
        'name'        => 'Kaos Polo Piqué Navy Kerah Ribbed Classic',
        'category'    => 'Baju',
        'brand'       => 'No Brand',
        'size'        => 'L',
        'material'    => 'Katun Piqué Honeycomb',
        'condition'   => 'Sangat Baik',
        'price'       => 90000.00,
        'stock'       => 1,
        'description' => 'Polo shirt formal kasual berwarna navy gelap dengan tekstur anyaman piqué sarang lebah yang bernapas lega. Dilengkapi plaket 2 kancing di dada dan manset lengan bergaris elastis yang rapi. Warna navy masih solid tanpa kemerahan akibat matahari, kerah tegak sempurna. Foto ilustrasi sementara.',
        'images'      => [
            ['file' => 'baju-07-polo-pique-navy-depan.webp',    'type' => 'depan',   'sort' => 1],
            ['file' => 'baju-07-polo-pique-navy-belakang.webp', 'type' => 'belakang','sort' => 2],
            ['file' => 'baju-07-polo-pique-navy-detail.webp',   'type' => 'detail',  'sort' => 3],
        ]
    ],
    // 08
    [
        'sku'         => 'DEMO-008',
        'name'        => 'Kaos Polos Katun Olive Green Earthy Casual',
        'category'    => 'Baju',
        'brand'       => 'No Brand',
        'size'        => 'M',
        'material'    => 'Katun Combed 30s',
        'condition'   => 'Baik',
        'price'       => 55000.00,
        'stock'       => 1,
        'description' => 'Kaos polos bernuansa warna hijau zaitun / army earth tone yang sangat mudah dipadu-padankan dengan celana pendek maupun outer. Dibuat dari katun combed 30s yang jatuh lembut dan sejuk di kulit tropis. Kondisi warna merata dengan tingkat pemakaian halus yang wajar. Foto ilustrasi sementara.',
        'images'      => [
            ['file' => 'baju-08-katun-olive-green-depan.webp',    'type' => 'depan',   'sort' => 1],
            ['file' => 'baju-08-katun-olive-green-belakang.webp', 'type' => 'belakang','sort' => 2],
            ['file' => 'baju-08-katun-olive-green-detail.webp',   'type' => 'detail',  'sort' => 3],
        ]
    ],
    // 09
    [
        'sku'         => 'DEMO-009',
        'name'        => 'Kaos Oversized Putih Graphic Typography Chest',
        'category'    => 'Baju',
        'brand'       => 'No Brand',
        'size'        => 'XL',
        'material'    => 'Katun Heavy Combed 20s',
        'condition'   => 'Seperti Baru',
        'price'       => 80000.00,
        'stock'       => 1,
        'description' => 'Kaos berpotongan oversized bahu turun (drop-shoulder) dengan aksen sablon grafis tipografi minimalis di bagian dada. Bahan katun tebal tidak menerawang dengan rib leher kokoh yang nyaman. Sablon bebas retak dan warna putih bersih terawat bebas kusam. Foto ilustrasi sementara.',
        'images'      => [
            ['file' => 'baju-09-oversized-graphic-putih-depan.webp',    'type' => 'depan',   'sort' => 1],
            ['file' => 'baju-09-oversized-graphic-putih-belakang.webp', 'type' => 'belakang','sort' => 2],
            ['file' => 'baju-09-oversized-graphic-putih-detail.webp',   'type' => 'detail',  'sort' => 3],
        ]
    ],
    // 10
    [
        'sku'         => 'DEMO-010',
        'name'        => 'Kaos Polos Jet Black Katun Regular Fit',
        'category'    => 'Baju',
        'brand'       => 'No Brand',
        'size'        => 'L',
        'material'    => 'Katun Combed 30s',
        'condition'   => 'Sangat Baik',
        'price'       => 60000.00,
        'stock'       => 1,
        'description' => 'Kaos oblong hitam pekat esensial sehari-hari dengan siluet reguler yang pas di badan. Terbuat dari katun combed 100% yang lembut dan ringan, cocok untuk dalaman kemeja maupun dipakai langsung. Warna hitam pekat tidak belel dan bebas noda deterjen. Foto ilustrasi sementara.',
        'images'      => [
            ['file' => 'baju-10-polos-jet-black-depan.webp',    'type' => 'depan',   'sort' => 1],
            ['file' => 'baju-10-polos-jet-black-belakang.webp', 'type' => 'belakang','sort' => 2],
            ['file' => 'baju-10-polos-jet-black-detail.webp',   'type' => 'detail',  'sort' => 3],
        ]
    ],
    // 11
    [
        'sku'         => 'DEMO-011',
        'name'        => 'Kaos Polos Putih Katun Combed Classic Tee',
        'category'    => 'Baju',
        'brand'       => 'No Brand',
        'size'        => 'M',
        'material'    => 'Katun Combed 24s',
        'condition'   => 'Baik',
        'price'       => 50000.00,
        'stock'       => 1,
        'description' => 'Kaos polos putih bersih serbaguna yang wajib dimiliki setiap lemari pakaian. Ketebalan bahan katun 24s pas tidak menerawang dan lembut menyerap keringat. Kondisi warna putih terjaga tanpa noda kuning di kerah maupun ketiak. Foto ilustrasi sementara.',
        'images'      => [
            ['file' => 'baju-11-polos-putih-classic-depan.webp',    'type' => 'depan',   'sort' => 1],
            ['file' => 'baju-11-polos-putih-classic-belakang.webp', 'type' => 'belakang','sort' => 2],
            ['file' => 'baju-11-polos-putih-classic-detail.webp',   'type' => 'detail',  'sort' => 3],
        ]
    ],
    // 12
    [
        'sku'         => 'DEMO-012',
        'name'        => 'Kemeja Oxford Button-Down Putih Formal Kasual',
        'category'    => 'Kemeja',
        'brand'       => 'No Brand',
        'size'        => 'L',
        'material'    => 'Katun Oxford Anyam Basketweave',
        'condition'   => 'Seperti Baru',
        'price'       => 115000.00,
        'stock'       => 1,
        'description' => 'Kemeja oxford klasik berwarna putih bersih dengan anyaman basketweave berkarakter dan kerah button-down rapi. Dilengkapi saku dada kiri dan kancing berkilau mutiara yang memberikan kesan rapi kasual maupun semi-formal. Kondisi kain sangat terawat tanpa noda, warna putih pekat dan jahitan kokoh. Foto ilustrasi sementara.',
        'images'      => [
            ['file' => 'kemeja-12-oxford-button-down-putih-depan.webp',    'type' => 'depan',   'sort' => 1],
            ['file' => 'kemeja-12-oxford-button-down-putih-belakang.webp', 'type' => 'belakang','sort' => 2],
            ['file' => 'kemeja-12-oxford-button-down-putih-detail.webp',   'type' => 'detail',  'sort' => 3],
        ]
    ],
];

echo "Rencana data yang akan diproses: " . count($productsData) . " produk\n\n";

// Periksa kolom tabel products
$colRes = $conn->query("SHOW COLUMNS FROM products");
$cols = [];
while ($c = $colRes->fetch_assoc()) $cols[] = $c['Field'];

$hasBrand  = in_array('brand', $cols);
$hasSku    = in_array('sku', $cols);
$hasIsDemo = in_array('is_demo', $cols);

// Periksa apakah tabel product_images ada
$tableCheck = $conn->query("SHOW TABLES LIKE 'product_images'");
$hasImgTable = ($tableCheck && $tableCheck->num_rows > 0);

echo "Status Struktur Database:\n";
echo " - Kolom brand   : " . ($hasBrand ? 'Tersedia' : 'Belum ada (akan ditambah di migrasi)') . "\n";
echo " - Kolom sku     : " . ($hasSku ? 'Tersedia' : 'Belum ada (akan ditambah di migrasi)') . "\n";
echo " - Kolom is_demo : " . ($hasIsDemo ? 'Tersedia' : 'Belum ada (akan ditambah di migrasi)') . "\n";
echo " - Tabel galeri  : " . ($hasImgTable ? 'Tersedia' : 'Belum ada (akan dibuat di migrasi)') . "\n\n";

if ($dryRun) {
    echo "=== DAFTAR PRODUK (SIMULASI DRY-RUN) ===\n";
    printf("%-10s | %-45s | %-8s | %-10s | %-6s | %-12s | %-10s | %s\n",
        "SKU", "Nama Produk", "Kategori", "Merek", "Ukuran", "Kondisi", "Harga", "Galeri File"
    );
    echo str_repeat("-", 140) . "\n";
    foreach ($productsData as $p) {
        $imgList = implode(', ', array_column($p['images'], 'file'));
        printf("%-10s | %-45s | %-8s | %-10s | %-6s | %-12s | Rp %-8s | %s\n",
            $p['sku'],
            mb_substr($p['name'], 0, 45),
            $p['category'],
            $p['brand'],
            $p['size'],
            $p['condition'],
            number_format($p['price'], 0, ',', '.'),
            $imgList
        );
    }
    echo str_repeat("-", 140) . "\n";
    echo "Total 12 produk dan 34 gambar galeri siap dimasukkan setelah persetujuan.\n";
    exit(0);
}

// EKSEKUSI PENULISAN NYATA
// Pastikan struktur sudah termigrasi
if (!$hasBrand || !$hasSku || !$hasIsDemo || !$hasImgTable) {
    die("ERROR: Struktur kolom/tabel baru belum dimigrasi. Jalankan migration terlebih dahulu.\n");
}

$conn->begin_transaction();

try {
    $insertProdStmt = $conn->prepare("
        INSERT INTO products (name, category, brand, price, stock, `condition`, image, description, material, size, sku, is_demo)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            category = VALUES(category),
            brand = VALUES(brand),
            price = VALUES(price),
            stock = VALUES(stock),
            `condition` = VALUES(`condition`),
            image = VALUES(image),
            description = VALUES(description),
            material = VALUES(material),
            size = VALUES(size),
            is_demo = 1
    ");

    $insertImgStmt = $conn->prepare("
        INSERT INTO product_images (product_id, image, sort_order, type)
        VALUES (?, ?, ?, ?)
    ");

    $delOldImgStmt = $conn->prepare("DELETE FROM product_images WHERE product_id = ?");

    $insertedCount = 0;
    foreach ($productsData as $p) {
        $mainImage = $p['images'][0]['file']; // Foto depan sebagai gambar utama

        $insertProdStmt->bind_param(
            "sssdissssss",
            $p['name'],
            $p['category'],
            $p['brand'],
            $p['price'],
            $p['stock'],
            $p['condition'],
            $mainImage,
            $p['description'],
            $p['material'],
            $p['size'],
            $p['sku']
        );
        $insertProdStmt->execute();

        // Dapatkan product_id (dari insert_id atau query by sku)
        $prodId = $conn->insert_id;
        if ($prodId === 0) {
            $q = $conn->prepare("SELECT id FROM products WHERE sku = ?");
            $q->bind_param("s", $p['sku']);
            $q->execute();
            $prodId = $q->get_result()->fetch_row()[0];
        }

        // Hapus entri gambar lama jika idempoten
        $delOldImgStmt->bind_param("i", $prodId);
        $delOldImgStmt->execute();

        // Masukkan relasi galeri gambar
        foreach ($p['images'] as $img) {
            $insertImgStmt->bind_param("isis", $prodId, $img['file'], $img['sort'], $img['type']);
            $insertImgStmt->execute();
        }

        $insertedCount++;
        echo "OK: Berhasil menyimpan [{$p['sku']}] #$prodId - {$p['name']}\n";
    }

    $conn->commit();
    echo "\nSUKSES: $insertedCount produk dan semua relasi gambar berhasil disimpan dalam satu transaksi!\n";

} catch (Throwable $e) {
    $conn->rollback();
    echo "\nFATAL ERROR: Transaksi dibatalkan (Rollback). Tidak ada data yang tersimpan.\n";
    echo "Pesan Error: " . $e->getMessage() . "\n";
    exit(1);
}
