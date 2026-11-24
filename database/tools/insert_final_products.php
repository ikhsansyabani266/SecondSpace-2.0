<?php
// CLI Tool: Update & Insert Data FINAL Produk (Mendukung --dry-run, Transaksi, & Idempoten)
require_once __DIR__ . '/../../db.php';

$dryRun = in_array('--dry-run', $argv);

echo "====================================================================\n";
echo "   SINKRONISASI DATA PRODUK FINAL & RELASI GAMBAR KE DATABASE       \n";
echo "====================================================================\n";

if ($dryRun) {
    echo "STATUS: MODE DRY-RUN (Simulasi saja, TIDAK ADA perubahan ditulis ke database)\n\n";
} else {
    echo "STATUS: MODE EKSEKUSI LANGSUNG (Menggunakan Transaksi & Prepared Statements)\n\n";
}

// 24 Definisi Produk Final Terkurasi
$products = [
    // 01
    [
        'sku' => 'PL-001',
        'old_sku' => 'DEMO-001',
        'name' => 'Kaos Athletic Performance Hitam Quick-Dry',
        'category' => 'Baju',
        'brand' => 'No Brand',
        'size' => 'L',
        'material' => 'Polyester Spandex',
        'condition' => 'Seperti Baru',
        'price' => 75000.00,
        'stock' => 1,
        'description' => 'Kaos olahraga athletic berwarna hitam pekat berbahan polyester quick-dry yang ringan dan sejuk. Potongan raglan dengan jahitan flatlock rapi memberikan kenyamanan optimal saat bergerak. Kondisi sangat terawat tanpa noda maupun robek, elastisitas kain masih prima. Cocok untuk postur tubuh dengan estimasi ukuran L lokal.',
        'images' => [
            ['file' => 'baju-01-athletic-performance-hitam-depan.webp', 'type' => 'depan', 'sort' => 1],
            ['file' => 'baju-01-athletic-performance-hitam-belakang.webp', 'type' => 'belakang', 'sort' => 2],
            ['file' => 'baju-01-athletic-performance-hitam-detail.webp', 'type' => 'detail', 'sort' => 3],
        ]
    ],
    // 02
    [
        'sku' => 'PL-002',
        'old_sku' => 'DEMO-002',
        'name' => 'Kemeja Salur Biru Putih Lengan Pendek Kasual',
        'category' => 'Kemeja',
        'brand' => 'No Brand',
        'size' => 'M',
        'material' => 'Katun Poplin',
        'condition' => 'Bagus',
        'price' => 110000.00,
        'stock' => 1,
        'description' => 'Kemeja kasual motif salur garis vertikal biru muda dan putih dengan potongan santai yang rapi. Dilengkapi saku depan di dada kiri dan kancing depan lengkap terpasang kokoh. Bahan katun poplin adem dan nyaman digunakan seharian di cuaca tropis. Kondisi kain bersih terawat, pas untuk ukuran M pria atau wanita (unisex).',
        'images' => [
            ['file' => 'kemeja-02-garis-biru-putih-depan.webp', 'type' => 'depan', 'sort' => 1],
            ['file' => 'kemeja-02-garis-biru-putih-belakang.webp', 'type' => 'belakang', 'sort' => 2],
            ['file' => 'kemeja-02-garis-biru-putih-detail.webp', 'type' => 'detail', 'sort' => 3],
        ]
    ],
    // 03
    [
        'sku' => 'PL-003',
        'old_sku' => 'DEMO-003',
        'name' => 'Kaos Polos Abu-Abu Katun Combed Basic',
        'category' => 'Baju',
        'brand' => 'No Brand',
        'size' => 'L',
        'material' => 'Katun Combed 24s',
        'condition' => 'Ada Minus',
        'price' => 55000.00,
        'stock' => 1,
        'description' => 'Kaos oblong basic esensial berwarna abu-abu terang netral dengan kerah crewneck bergaris rib lembut. Bahan katun combed 24s lembut dan menyerap keringat dengan baik untuk pakaian harian santai. Kondisi layak pakai dengan minus bulu halus wajar bekas pencucian tanpa lubang atau robekan. Disarankan untuk yang mencari kaos santai bersahabat ukuran L.',
        'images' => [
            ['file' => 'baju-03-katun-abu-abu-depan.webp', 'type' => 'depan', 'sort' => 1],
            ['file' => 'baju-03-katun-abu-abu-belakang.webp', 'type' => 'belakang', 'sort' => 2],
            ['file' => 'baju-03-katun-abu-abu-detail.webp', 'type' => 'detail', 'sort' => 3],
        ]
    ],
    // 04
    [
        'sku' => 'PL-004',
        'old_sku' => 'DEMO-004',
        'name' => 'Kaos Heavyweight Boxy Heather Gray Streetwear',
        'category' => 'Baju',
        'brand' => 'No Brand',
        'size' => 'XL',
        'material' => 'Katun Heavyweight 16s',
        'condition' => 'Bagus',
        'price' => 95000.00,
        'stock' => 1,
        'description' => 'Kaos berpotongan boxy fit modern berwarna abu-abu misty (heather gray) berbahan katun tebal 16s. Struktur kerah rib tebal dan siluet bahu turun (drop shoulder) memberikan kesan streetwear yang tegas dan kokoh. Warna kain solid dan jahitan rantai samping aman terjaga. Cocok untuk penyuka gaya oversized atau ukuran fit XL.',
        'images' => [
            ['file' => 'baju-04-heavyweight-heather-gray-depan.webp', 'type' => 'depan', 'sort' => 1],
            ['file' => 'baju-04-heavyweight-heather-gray-belakang.webp', 'type' => 'belakang', 'sort' => 2],
            ['file' => 'baju-04-heavyweight-heather-gray-detail.webp', 'type' => 'detail', 'sort' => 3],
        ]
    ],
    // 05
    [
        'sku' => 'PL-005',
        'old_sku' => 'DEMO-005',
        'name' => 'Kemeja Workwear Khaki Safari Lengan Pendek',
        'category' => 'Kemeja',
        'brand' => 'No Brand',
        'size' => 'L',
        'material' => 'Katun Drill Halus',
        'condition' => 'Bagus',
        'price' => 125000.00,
        'stock' => 1,
        'description' => 'Kemeja bergaya utilitarian workwear berwarna cokelat khaki dengan dua saku dada berkancing dan detail lipatan fungsional. Menggunakan bahan katun drill bertekstur kuat namun tetap luwes dan tidak panas. Kondisi warna merata tanpa noda membandel dan seluruh kancing lengkap. Sangat cocok dipadukan sebagai outer santai dengan ukuran L.',
        'images' => [
            ['file' => 'kemeja-05-workwear-khaki-depan.webp', 'type' => 'depan', 'sort' => 1],
            ['file' => 'kemeja-05-workwear-khaki-belakang.webp', 'type' => 'belakang', 'sort' => 2],
            ['file' => 'kemeja-05-workwear-khaki-detail.webp', 'type' => 'detail', 'sort' => 3],
        ]
    ],
    // 06
    [
        'sku' => 'PL-006',
        'old_sku' => 'DEMO-006',
        'name' => 'Kaos Salur Navy Putih Breton Nautical Style',
        'category' => 'Baju',
        'brand' => 'No Brand',
        'size' => 'M',
        'material' => 'Katun Yarn Dyed',
        'condition' => 'Bagus',
        'price' => 70000.00,
        'stock' => 1,
        'description' => 'Kaos motif garis horizontal navy dan putih bergaya nautical klasik dengan rajutan motif tenun yarn-dyed rapi. Bahan katun lembut dengan ketebalan pas memberikan sirkulasi udara yang baik saat beraktivitas harian. Kerah leher bulat dengan jahitan leher yang masih kencang dan tidak melar. Ukuran pas untuk berat badan proporsional size M.',
        'images' => [
            ['file' => 'baju-06-striped-navy-putih-depan.webp', 'type' => 'depan', 'sort' => 1],
            ['file' => 'baju-06-striped-navy-putih-belakang.webp', 'type' => 'belakang', 'sort' => 2],
            ['file' => 'baju-06-striped-navy-putih-detail.webp', 'type' => 'detail', 'sort' => 3],
        ]
    ],
    // 07
    [
        'sku' => 'PL-007',
        'old_sku' => 'DEMO-007',
        'name' => 'Kaos Polo Pique Navy Blue Smart Casual',
        'category' => 'Baju',
        'brand' => 'No Brand',
        'size' => 'L',
        'material' => 'Katun Pique Berpori',
        'condition' => 'Bagus',
        'price' => 85000.00,
        'stock' => 1,
        'description' => 'Kaos polo klasik berwarna biru dongker (navy) dengan tekstur rajut pique berpori yang sejuk dan menyerap keringat. Dilengkapi kerah kemeja kokoh bergaris tepi serta bukaan dua kancing mutiara rapi. Kondisi warna navy masih pekat dan jahitan manset lengan terawat baik. Pilihan sempurna untuk tampilan rapi semi-formal ukuran L.',
        'images' => [
            ['file' => 'baju-07-polo-pique-navy-depan.webp', 'type' => 'depan', 'sort' => 1],
            ['file' => 'baju-07-polo-pique-navy-belakang.webp', 'type' => 'belakang', 'sort' => 2],
            ['file' => 'baju-07-polo-pique-navy-detail.webp', 'type' => 'detail', 'sort' => 3],
        ]
    ],
    // 08
    [
        'sku' => 'PL-008',
        'old_sku' => 'DEMO-008',
        'name' => 'Kaos Polos Olive Green Katun Earth Tone',
        'category' => 'Baju',
        'brand' => 'No Brand',
        'size' => 'M',
        'material' => 'Katun Combed 30s',
        'condition' => 'Bagus',
        'price' => 60000.00,
        'stock' => 1,
        'description' => 'Kaos oblong bernuansa earth-tone warna hijau zaitun (olive green) berpotongan regular fit yang nyaman dipakai harian. Terbuat dari katun combed 30s sejuk, jatuh di badan, dan tidak menerawang saat dikenakan. Warna hijau army masih bersih merata tanpa bercak dengan kondisi jahitan ujung pakaian aman. Sangat cocok untuk postur ukuran M santai.',
        'images' => [
            ['file' => 'baju-08-katun-olive-green-depan.webp', 'type' => 'depan', 'sort' => 1],
            ['file' => 'baju-08-katun-olive-green-belakang.webp', 'type' => 'belakang', 'sort' => 2],
            ['file' => 'baju-08-katun-olive-green-detail.webp', 'type' => 'detail', 'sort' => 3],
        ]
    ],
    // 09
    [
        'sku' => 'PL-009',
        'old_sku' => 'DEMO-009',
        'name' => 'Kaos Oversized Putih Graphic Typography Dada',
        'category' => 'Baju',
        'brand' => 'No Brand',
        'size' => 'L',
        'material' => 'Katun Combed 20s',
        'condition' => 'Seperti Baru',
        'price' => 80000.00,
        'stock' => 1,
        'description' => 'Kaos streetwear berpotongan oversized warna putih bersih dengan aksen tipografi grafis minimalis di area dada kiri. Bahan katun combed 20s tebal, halus, dan tidak tembus pandang dengan sensasi jatuh yang mantap. Kondisi sablon mulus tanpa retak dan kerah leher masih sangat rapat kencang. Pas bagi yang menyukai tampilan santai loose fit ukuran L.',
        'images' => [
            ['file' => 'baju-09-oversized-graphic-putih-depan.webp', 'type' => 'depan', 'sort' => 1],
            ['file' => 'baju-09-oversized-graphic-putih-belakang.webp', 'type' => 'belakang', 'sort' => 2],
            ['file' => 'baju-09-oversized-graphic-putih-detail.webp', 'type' => 'detail', 'sort' => 3],
        ]
    ],
    // 10
    [
        'sku' => 'PL-010',
        'old_sku' => 'DEMO-010',
        'name' => 'Kaos Polos Jet Black Katun Regular Fit',
        'category' => 'Baju',
        'brand' => 'No Brand',
        'size' => 'M',
        'material' => 'Katun Combed 24s',
        'condition' => 'Bagus',
        'price' => 60000.00,
        'stock' => 1,
        'description' => 'Kaos oblong hitam pekat (jet black) esensial serbaguna dengan potongan regular fit yang bersih dan simpel. Menggunakan bahan katun combed 24s yang lembut di kulit, menyerap keringat dengan baik, dan nyaman untuk cuaca panas. Kondisi warna hitam masih pekat tanpa pudar di kerah maupun ketiak. Pilihan pakaian harian yang awet untuk ukuran M.',
        'images' => [
            ['file' => 'baju-10-polos-jet-black-depan.webp', 'type' => 'depan', 'sort' => 1],
            ['file' => 'baju-10-polos-jet-black-belakang.webp', 'type' => 'belakang', 'sort' => 2],
            ['file' => 'baju-10-polos-jet-black-detail.webp', 'type' => 'detail', 'sort' => 3],
        ]
    ],
    // 11
    [
        'sku' => 'PL-011',
        'old_sku' => 'DEMO-011',
        'name' => 'Kaos Polos Putih Katun Combed Classic Tee',
        'category' => 'Baju',
        'brand' => 'No Brand',
        'size' => 'M',
        'material' => 'Katun Combed 30s',
        'condition' => 'Bagus',
        'price' => 50000.00,
        'stock' => 1,
        'description' => 'Kaos polos putih bersih klasik model crewneck yang wajib ada dalam lemari pakaian untuk inner kemeja maupun dipakai langsung. Terbuat dari katun combed 30s yang ringan, adem, dan menyerap keringat dengan sempurna. Kondisi kain putih terjaga tanpa noda kuning dan jahitan benang rantai samping aman. Direkomendasikan untuk ukuran M.',
        'images' => [
            ['file' => 'baju-11-polos-putih-classic-depan.webp', 'type' => 'depan', 'sort' => 1],
            ['file' => 'baju-11-polos-putih-classic-belakang.webp', 'type' => 'belakang', 'sort' => 2],
            ['file' => 'baju-11-polos-putih-classic-detail.webp', 'type' => 'detail', 'sort' => 3],
        ]
    ],
    // 12
    [
        'sku' => 'PL-012',
        'old_sku' => 'DEMO-012',
        'name' => 'Kemeja Oxford Button-Down Putih Formal Kasual',
        'category' => 'Kemeja',
        'brand' => 'No Brand',
        'size' => 'L',
        'material' => 'Katun Oxford Berkualitas',
        'condition' => 'Seperti Baru',
        'price' => 115000.00,
        'stock' => 1,
        'description' => 'Kemeja lengan panjang berkerah button-down berwarna putih bersih dari bahan katun oxford bertekstur tenun khas yang kokoh. Potongan rapi dengan saku dada dan manset lengan yang bisa dilipat rapi untuk gaya smart-casual maupun formal. Kondisi seperti baru, warna putih cerah sempurna dan kancing kerah masih utuh kencang. Pas untuk ukuran tubuh L.',
        'images' => [
            ['file' => 'kemeja-12-oxford-button-down-putih-depan.webp', 'type' => 'depan', 'sort' => 1],
            ['file' => 'kemeja-12-oxford-button-down-putih-belakang.webp', 'type' => 'belakang', 'sort' => 2],
            ['file' => 'kemeja-12-oxford-button-down-putih-detail.webp', 'type' => 'detail', 'sort' => 3],
        ]
    ],
    // 13
    [
        'sku' => 'PL-013',
        'old_sku' => null,
        'name' => 'Celana Jeans Regular Classic Blue Denim',
        'category' => 'Celana',
        'brand' => 'No Brand',
        'size' => '32',
        'material' => 'Denim Katun Non-Stretch 13 oz',
        'condition' => 'Bagus',
        'price' => 140000.00,
        'stock' => 1,
        'description' => 'Celana panjang denim klasik berwarna biru indigo cerah dengan potongan regular leg yang abadi dan nyaman dipakai harian. Bahan denim katun 13 oz yang kokoh dengan resleting tembaga dan jahitan kontras warna kuning khas vintage. Kondisi kain sangat kuat, tidak ada robekan atau sobekan tidak wajar di selangkangan. Cocok untuk lingkar pinggang size 32.',
        'images' => [
            ['file' => 'celana-13-denim-regular-classic-blue-depan.webp', 'type' => 'depan', 'sort' => 1],
            ['file' => 'celana-13-denim-regular-classic-blue-belakang.webp', 'type' => 'belakang', 'sort' => 2],
            ['file' => 'celana-13-denim-regular-classic-blue-detail.webp', 'type' => 'detail', 'sort' => 3],
        ]
    ],
    // 14
    [
        'sku' => 'PL-014',
        'old_sku' => null,
        'name' => 'Jacket Puffer Down Hitam Quilted Padded',
        'category' => 'Jacket',
        'brand' => 'No Brand',
        'size' => 'L',
        'material' => 'Nilon Ripstop & Dakron Sintetis',
        'condition' => 'Bagus',
        'price' => 185000.00,
        'stock' => 1,
        'description' => 'Jaket tebal model puffer berwarna hitam pekat dengan jahitan sekat bantal (quilted) yang empuk dan menghangatkan tubuh. Dilengkapi resleting utama yang kokoh, kerah tinggi pelindung angin, serta dua saku samping beritsleting fungsional. Lapisan dalam nilon halus tidak gatal dan dakron masih mengembang merata. Pas untuk ukuran badan L saat traveling atau berkendara.',
        'images' => [
            ['file' => 'jacket-14-puffer-down-hitam-depan.webp', 'type' => 'depan', 'sort' => 1],
            ['file' => 'jacket-14-puffer-down-hitam-belakang.webp', 'type' => 'belakang', 'sort' => 2],
            ['file' => 'jacket-14-puffer-down-hitam-detail.webp', 'type' => 'detail', 'sort' => 3],
        ]
    ],
    // 15
    [
        'sku' => 'PL-015',
        'old_sku' => null,
        'name' => 'Celana Training Track Pants Hitam Side Stripes',
        'category' => 'Celana',
        'brand' => 'No Brand',
        'size' => 'M',
        'material' => 'Polyester Tricot',
        'condition' => 'Bagus',
        'price' => 95000.00,
        'stock' => 1,
        'description' => 'Celana olahraga santai warna hitam dengan aksen garis setrip samping putih bergaya track pants retro streetwear. Menggunakan pinggang karet elastis berkolor tarik yang fleksibel serta bukaan resleting di pergelangan kaki bawah. Bahan polyester tricot licin dan lentur memudahkan pergerakan olahraga maupun hangout santai. Cocok untuk lingkar pinggang M (29-31).',
        'images' => [
            ['file' => 'celana-15-track-pants-hitam-depan.webp', 'type' => 'depan', 'sort' => 1],
            ['file' => 'celana-15-track-pants-hitam-belakang.webp', 'type' => 'belakang', 'sort' => 2],
            ['file' => 'celana-15-track-pants-hitam-detail.webp', 'type' => 'detail', 'sort' => 3],
        ]
    ],
    // 16
    [
        'sku' => 'PL-016',
        'old_sku' => null,
        'name' => 'Celana Chino Twill Hitam Straight Cut Workwear',
        'category' => 'Celana',
        'brand' => 'No Brand',
        'size' => '32',
        'material' => 'Katun Poly Twill Tebal',
        'condition' => 'Bagus',
        'price' => 120000.00,
        'stock' => 1,
        'description' => 'Celana panjang kerja model chino berwarna hitam pekat dengan potongan pipa lurus (straight cut) yang kokoh dan tahan lama. Kain twill tebal berdaya tahan tinggi tidak mudah kusut, dilengkapi saku belakang model welt dan pengait kancing kokoh. Kondisi warna hitam masih pekat tanpa aus di bagian lutut. Pilihan tepat untuk aktivitas harian santai maupun kerja ukuran 32.',
        'images' => [
            ['file' => 'celana-16-chino-twill-hitam-depan.webp', 'type' => 'depan', 'sort' => 1],
            ['file' => 'celana-16-chino-twill-hitam-belakang.webp', 'type' => 'belakang', 'sort' => 2],
            ['file' => 'celana-16-chino-twill-hitam-detail.webp', 'type' => 'detail', 'sort' => 3],
        ]
    ],
    // 17
    [
        'sku' => 'PL-017',
        'old_sku' => null,
        'name' => 'Jacket Denim Trucker Washed Black Vintage',
        'category' => 'Jacket',
        'brand' => 'No Brand',
        'size' => 'L',
        'material' => 'Denim Katun Heavyweight',
        'condition' => 'Bagus',
        'price' => 175000.00,
        'stock' => 1,
        'description' => 'Jaket denim model trucker legendaris dengan efek washed hitam pudar bernuansa vintage grunge yang berkarakter. Memiliki saku dada ganda dengan penutup lidah kancing logam dan tali pengatur lingkar pinggang di bagian belakang. Bahan denim tebal dan berbobot mantap saat dikenakan. Kondisi jahitan utuh tanpa cacat lubang, pas untuk postur ukuran L.',
        'images' => [
            ['file' => 'jacket-17-denim-trucker-washed-black-depan.webp', 'type' => 'depan', 'sort' => 1],
            ['file' => 'jacket-17-denim-trucker-washed-black-belakang.webp', 'type' => 'belakang', 'sort' => 2],
            ['file' => 'jacket-17-denim-trucker-washed-black-detail.webp', 'type' => 'detail', 'sort' => 3],
        ]
    ],
    // 18
    [
        'sku' => 'PL-018',
        'old_sku' => null,
        'name' => 'Celana Double Knee Work Pants Canvas Cokelat',
        'category' => 'Celana',
        'brand' => 'No Brand',
        'size' => '32',
        'material' => 'Duck Canvas Katun Berat',
        'condition' => 'Bagus',
        'price' => 150000.00,
        'stock' => 1,
        'description' => 'Celana tukang workwear berbahan duck canvas warna cokelat karamel dengan lapisan ganda di bagian lutut (double knee panel) dan paku rivet tembaga. Dilengkapi saku samping utilitarian untuk peralatan serta gantungan palu khas pakaian kerja lapangan. Bahan tebal tahan gesekan dengan fading natural yang estetik. Pas untuk ukuran lingkar pinggang 32.',
        'images' => [
            ['file' => 'celana-18-double-knee-canvas-cokelat-depan.webp', 'type' => 'depan', 'sort' => 1],
            ['file' => 'celana-18-double-knee-canvas-cokelat-belakang.webp', 'type' => 'belakang', 'sort' => 2],
            ['file' => 'celana-18-double-knee-canvas-cokelat-detail.webp', 'type' => 'detail', 'sort' => 3],
        ]
    ],
    // 19
    [
        'sku' => 'PL-019',
        'old_sku' => null,
        'name' => 'Jacket Chore Coat Duck Canvas Cokelat Kerah Korduroi',
        'category' => 'Jacket',
        'brand' => 'No Brand',
        'size' => 'XL',
        'material' => 'Duck Canvas Katun & Korduroi',
        'condition' => 'Seperti Baru',
        'price' => 210000.00,
        'stock' => 1,
        'description' => 'Jaket chore coat gaya pekerja lapangan berbahan duck canvas warna cokelat tan dengan sentuhan kerah korduroi cokelat gelap kontras. Memiliki empat saku tempel fungsional di bagian depan serta kancing logam berdaya tahan tinggi. Bahan sangat kokoh dan memberikan perlindungan maksimal dari angin. Pilihan mantel luar berkarakter tebal untuk ukuran XL.',
        'images' => [
            ['file' => 'jacket-19-chore-coat-canvas-cokelat-depan.webp', 'type' => 'depan', 'sort' => 1],
            ['file' => 'jacket-19-chore-coat-canvas-cokelat-belakang.webp', 'type' => 'belakang', 'sort' => 2],
            ['file' => 'jacket-19-chore-coat-canvas-cokelat-detail.webp', 'type' => 'detail', 'sort' => 3],
        ]
    ],
    // 20
    [
        'sku' => 'PL-020',
        'old_sku' => null,
        'name' => 'Jacket Fleece Sherpa Forest Green Warm Outer',
        'category' => 'Jacket',
        'brand' => 'No Brand',
        'size' => 'L',
        'material' => 'Polyester Fleece Bulu Lembut',
        'condition' => 'Bagus',
        'price' => 165000.00,
        'stock' => 1,
        'description' => 'Jaket bulu fleece hangat berwarna hijau lumut rimba (forest green) dengan aksen saku dada berkancing warna kontras. Tekstur sherpa fleece yang empuk dan tebal mampu menjaga suhu tubuh tetap nyaman di dataran tinggi atau ruangan ber-AC. Dilengkapi resleting leher penuh dan manset rib elastis pada lengan. Kondisi bulu masih mengembang rapi untuk ukuran L.',
        'images' => [
            ['file' => 'jacket-20-fleece-sherpa-forest-green-depan.webp', 'type' => 'depan', 'sort' => 1],
            ['file' => 'jacket-20-fleece-sherpa-forest-green-belakang.webp', 'type' => 'belakang', 'sort' => 2],
            ['file' => 'jacket-20-fleece-sherpa-forest-green-detail.webp', 'type' => 'detail', 'sort' => 3],
        ]
    ],
    // 21
    [
        'sku' => 'PL-021',
        'old_sku' => null,
        'name' => 'Celana Workwear Khaki Straight Leg Chino Pant',
        'category' => 'Celana',
        'brand' => 'No Brand',
        'size' => '30',
        'material' => 'Katun Twill Drill',
        'condition' => 'Bagus',
        'price' => 115000.00,
        'stock' => 1,
        'description' => 'Celana panjang kerja model klasik berwarna khaki krem dengan potongan pipa lurus yang leluasa untuk aktivitas harian. Bahan katun twill kokoh tahan lama dengan lipatan garis depan yang rapi serta jahitan benang ganda pada bagian samping. Kondisi resleting dan kancing berfungsi sempurna tanpa kendala. Sangat pas untuk ukuran lingkar pinggang 30.',
        'images' => [
            ['file' => 'celana-21-straight-leg-khaki-depan.webp', 'type' => 'depan', 'sort' => 1],
            ['file' => 'celana-21-straight-leg-khaki-belakang.webp', 'type' => 'belakang', 'sort' => 2],
            ['file' => 'celana-21-straight-leg-khaki-detail.webp', 'type' => 'detail', 'sort' => 3],
        ]
    ],
    // 22
    [
        'sku' => 'PL-022',
        'old_sku' => null,
        'name' => 'Jacket Windbreaker Parasut Navy Hoodie Ringan',
        'category' => 'Jacket',
        'brand' => 'No Brand',
        'size' => 'M',
        'material' => 'Parasut Nilon Tahan Angin',
        'condition' => 'Seperti Baru',
        'price' => 135000.00,
        'stock' => 1,
        'description' => 'Jaket windbreaker bertudung (hoodie) warna biru dongker (navy) berbahan parasut nilon yang ringan, tahan angin, dan cepat kering. Memiliki resleting depan tahan air, karet pergelangan elastis kencang, serta tali serut di bagian bawah jaket. Sangat praktis dilipat kecil untuk kebutuhan olahraga lari maupun berkendara motor. Cocok untuk ukuran M.',
        'images' => [
            ['file' => 'jacket-22-windbreaker-parasut-navy-depan.webp', 'type' => 'depan', 'sort' => 1],
            ['file' => 'jacket-22-windbreaker-parasut-navy-belakang.webp', 'type' => 'belakang', 'sort' => 2],
            ['file' => 'jacket-22-windbreaker-parasut-navy-detail.webp', 'type' => 'detail', 'sort' => 3],
        ]
    ],
    // 23
    [
        'sku' => 'PL-023',
        'old_sku' => null,
        'name' => 'Celana Cargo Utility Ripstop Olive Green Multi-Pocket',
        'category' => 'Celana',
        'brand' => 'No Brand',
        'size' => '32',
        'material' => 'Katun Ripstop Kotak Tahan Robek',
        'condition' => 'Bagus',
        'price' => 145000.00,
        'stock' => 1,
        'description' => 'Celana kargo utilitarian warna hijau army bertekstur tenun kotak ripstop yang kuat terhadap robekan dan gesekan benda tajam. Memiliki total 6 saku fungsional dengan penutup lidah kancing aman untuk menyimpan dompet, gawai, dan perlengkapan harian. Kondisi warna hijau masih terjaga merata tanpa noda cat atau karat. Pas untuk lingkar pinggang 32.',
        'images' => [
            ['file' => 'celana-23-cargo-ripstop-olive-green-depan.webp', 'type' => 'depan', 'sort' => 1],
            ['file' => 'celana-23-cargo-ripstop-olive-green-belakang.webp', 'type' => 'belakang', 'sort' => 2],
            ['file' => 'celana-23-cargo-ripstop-olive-green-detail.webp', 'type' => 'detail', 'sort' => 3],
        ]
    ],
    // 24
    [
        'sku' => 'PL-024',
        'old_sku' => null,
        'name' => 'Celana Jeans Vintage Medium Wash Straight Denim',
        'category' => 'Celana',
        'brand' => 'No Brand',
        'size' => '34',
        'material' => 'Denim Katun 14 oz Vintage Wash',
        'condition' => 'Bagus',
        'price' => 155000.00,
        'stock' => 1,
        'description' => 'Celana denim bergaya vintage tahun 90-an dengan warna medium blue wash dan aksen fading whisker alami di paha depan. Menggunakan bahan katun denim 14 oz tebal tanpa campuran stretch untuk mempertahankan siluet jatuh pipa lurus yang maskulin. Jahitan bar-tack di saku aman dan resleting depan lancar. Pilihan sempurna untuk lingkar pinggang 34.',
        'images' => [
            ['file' => 'celana-24-denim-vintage-medium-wash-depan.webp', 'type' => 'depan', 'sort' => 1],
            ['file' => 'celana-24-denim-vintage-medium-wash-belakang.webp', 'type' => 'belakang', 'sort' => 2],
            ['file' => 'celana-24-denim-vintage-medium-wash-detail.webp', 'type' => 'detail', 'sort' => 3],
        ]
    ],
];

// Eksekusi Dry Run atau Live
$conn->begin_transaction();

try {
    $updatedCount = 0;
    $insertedCount = 0;
    $imagesSynced = 0;

    foreach ($products as $idx => $p) {
        $productNumber = $idx + 1;
        $primaryImage = $p['images'][0]['file'];

        // Cek apakah produk sudah ada berdasarkan SKU final atau old_sku demo
        $existing = null;
        $checkStmt = $conn->prepare("SELECT id, sku, name FROM products WHERE sku = ? OR (sku = ? AND ? IS NOT NULL)");
        $checkStmt->bind_param("sss", $p['sku'], $p['old_sku'], $p['old_sku']);
        $checkStmt->execute();
        $res = $checkStmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $existing = $row;
        }
        $checkStmt->close();

        if ($existing) {
            $productId = $existing['id'];
            echo sprintf("[%02d] UPDATE (ID: %d | SKU: %s -> %s) %s\n", $productNumber, $productId, $existing['sku'], $p['sku'], $p['name']);
            echo "     Kategori: {$p['category']} | Merek: {$p['brand']} | Size: {$p['size']} | Kondisi: {$p['condition']} | Harga: Rp " . number_format($p['price'], 0, ',', '.') . "\n";

            if (!$dryRun) {
                $upStmt = $conn->prepare("UPDATE products SET sku = ?, name = ?, category = ?, brand = ?, size = ?, material = ?, `condition` = ?, price = ?, stock = ?, description = ?, image = ?, is_demo = 0 WHERE id = ?");
                $upStmt->bind_param(
                    "sssssssdissi",
                    $p['sku'],
                    $p['name'],
                    $p['category'],
                    $p['brand'],
                    $p['size'],
                    $p['material'],
                    $p['condition'],
                    $p['price'],
                    $p['stock'],
                    $p['description'],
                    $primaryImage,
                    $productId
                );
                $upStmt->execute();
                $upStmt->close();
            }
            $updatedCount++;
        } else {
            echo sprintf("[%02d] INSERT (Baru -> SKU: %s) %s\n", $productNumber, $p['sku'], $p['name']);
            echo "     Kategori: {$p['category']} | Merek: {$p['brand']} | Size: {$p['size']} | Kondisi: {$p['condition']} | Harga: Rp " . number_format($p['price'], 0, ',', '.') . "\n";

            if (!$dryRun) {
                $inStmt = $conn->prepare("INSERT INTO products (sku, name, category, brand, size, material, `condition`, price, stock, description, image, is_demo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)");
                $inStmt->bind_param(
                    "sssssssdiss",
                    $p['sku'],
                    $p['name'],
                    $p['category'],
                    $p['brand'],
                    $p['size'],
                    $p['material'],
                    $p['condition'],
                    $p['price'],
                    $p['stock'],
                    $p['description'],
                    $primaryImage
                );
                $inStmt->execute();
                $productId = $conn->insert_id;
                $inStmt->close();
            } else {
                $productId = 9000 + $productNumber; // Dummy id for dry run
            }
            $insertedCount++;
        }

        // Sinkronkan 3 foto ke product_images
        foreach ($p['images'] as $img) {
            echo "     -> Foto {$img['type']} (Urutan {$img['sort']}): {$img['file']}\n";
            if (!$dryRun) {
                // Cek apakah foto untuk produk ini sudah ada dengan urutan tersebut
                $chkImg = $conn->prepare("SELECT id FROM product_images WHERE product_id = ? AND sort_order = ?");
                $chkImg->bind_param("ii", $productId, $img['sort']);
                $chkImg->execute();
                $imgRes = $chkImg->get_result();

                if ($imgRow = $imgRes->fetch_assoc()) {
                    // Update
                    $upImg = $conn->prepare("UPDATE product_images SET image = ?, type = ? WHERE id = ?");
                    $upImg->bind_param("ssi", $img['file'], $img['type'], $imgRow['id']);
                    $upImg->execute();
                    $upImg->close();
                } else {
                    // Insert
                    $inImg = $conn->prepare("INSERT INTO product_images (product_id, image, sort_order, type) VALUES (?, ?, ?, ?)");
                    $inImg->bind_param("isis", $productId, $img['file'], $img['sort'], $img['type']);
                    $inImg->execute();
                    $inImg->close();
                }
                $chkImg->close();
            }
            $imagesSynced++;
        }
    }

    if ($dryRun) {
        $conn->rollback();
        echo "\n=== RINGKASAN DRY-RUN ===\n";
        echo "Produk yang akan di-UPDATE : $updatedCount produk (ID 9-20 dari data demo)\n";
        echo "Produk yang akan di-INSERT : $insertedCount produk baru (Celana & Jacket)\n";
        echo "Total produk final         : " . ($updatedCount + $insertedCount) . " produk\n";
        echo "Total gambar disinkronkan  : $imagesSynced gambar (3 gambar x 24 produk)\n";
        echo "Status Transaksi           : ROLLBACK BERHASIL (Database TIDAK mengalami perubahan)\n";
    } else {
        $conn->commit();
        echo "\n=== RINGKASAN EKSEKUSI SUKSES ===\n";
        echo "Produk di-UPDATE           : $updatedCount produk\n";
        echo "Produk di-INSERT           : $insertedCount produk baru\n";
        echo "Total produk final         : " . ($updatedCount + $insertedCount) . " produk\n";
        echo "Total gambar disinkronkan  : $imagesSynced gambar (3 gambar x 24 produk)\n";
        echo "Status Transaksi           : COMMIT BERHASIL\n";
    }

} catch (Exception $e) {
    $conn->rollback();
    echo "\n[ERROR] Terjadi kesalahan: " . $e->getMessage() . "\n";
    echo "Seluruh transaksi telah di-ROLLBACK!\n";
    exit(1);
}
