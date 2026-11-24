# PANDUAN PENGEMBANG & ARSITEKTUR KODE
## SECONDSPACE // CURATED PRELOVED STREETWEAR PLATFORM
**Stack:** PHP Native 8.x, MySQL (via MySQLi & phpMyAdmin), HTML5, CSS3, Laravel Herd Server.  
**Standar Arsitektur:** 100% Bebas JavaScript (Pure PHP + CSS3), Single Source of Truth (`/shared/`), CSRF Protected, Zero Emoji.

---

## 1. SISTEM KOMENTAR BERTANDA (TAGGED COMMENTS)

Seluruh berkas PHP dan stylesheet di repositori ini dilengkapi dengan tag terstandarisasi untuk memudahkan pemeliharaan, pencarian kode cepat, dan kolaborasi tim:

| Tag | Arti / Fungsi | Contoh Penggunaan | Cara Mencari di VS Code |
| :--- | :--- | :--- | :--- |
| `[FILE]` | Informasi identitas berkas, modul tujuan, dan tabel yang diakses. | `// [FILE] produk_form.php - Formulir Produk Admin` | Tekan `Ctrl + Shift + F`, ketik `[FILE]` |
| `[BAGIAN]` | Membatasi blok fungsionalitas tertentu secara terstruktur dan berpasangan. | `// [BAGIAN: Logika Filter]` ... `// [/BAGIAN: Logika Filter]` | Tekan `Ctrl + Shift + F`, ketik `[BAGIAN:` |
| `[UBAH DI SINI]` | Parameter, konstanta, atau tampilan yang **aman untuk diubah**. | Nomor WhatsApp admin, tarif flat ongkos kirim, rasio pagination. | Tekan `Ctrl + Shift + F`, ketik `[UBAH DI SINI]` |
| `[TAMBAH DI SINI]` | Titik kait (hook) yang disarankan untuk **menambah fitur baru**. | Tambah opsi status pesanan, tambah metode bayar baru, tambah menu. | Tekan `Ctrl + Shift + F`, ketik `[TAMBAH DI SINI]` |
| `[JANGAN UBAH]` | Logika inti kritis yang **dilarang diubah** tanpa diskusi tim. | Mutex transaksi atomik stok, formula kode order, hashing password. | Tekan `Ctrl + Shift + F`, ketik `[JANGAN UBAH]` |
| `[QUERY]` | Blok SQL Query database (Prepared Statement aman). | Query SELECT berfilter aman atau eksekusi mutasi INSERT/UPDATE. | Tekan `Ctrl + Shift + F`, ketik `[QUERY]` |
| `[VALIDASI]` | Pengecekan input formulir di sisi server (Server-side Validation). | Validasi tipe MIME upload, validasi format angka, whitelist input. | Tekan `Ctrl + Shift + F`, ketik `[VALIDASI]` |
| `[AKSI]` | Pemrosesan metode POST atau mutasi data transaksi. | Handler form action tambah item, proses pembaruan status pesanan. | Tekan `Ctrl + Shift + F`, ketik `[AKSI]` |
| `[TAMPILAN]` | Template rendering HTML & CSS yang terlihat oleh pengguna. | Markup kartu produk, tabel pesanan, formulir input, badge status. | Tekan `Ctrl + Shift + F`, ketik `[TAMPILAN]` |
| `[KEAMANAN]` | Lapisan proteksi keamanan sistem (CSRF, sanitasi, auth guard). | Pengecekan token CSRF `hash_equals()`, no-cache headers, session check. | Tekan `Ctrl + Shift + F`, ketik `[KEAMANAN]` |

---

## 2. PETA LENGKAP FILE, TABEL DATABASE & FUNGSI BERSAMA (FILE MAP)

Berikut adalah pemetaan setiap halaman aplikasi terhadap berkas PHP, tabel database yang diakses, dan fungsi bersama (`/shared/`) yang dipanggil:

```text
+-----------------------+--------------------------+-----------------------+----------------------------------------------+
| Halaman / Fitur       | Berkas PHP               | Tabel Database        | Fungsi Bersama (/shared/) Digunakan          |
+-----------------------+--------------------------+-----------------------+----------------------------------------------+
| Entry Point           | index.php                | -                     | header() redirect ke user/index.php          |
| Beranda Toko          | user/index.php           | products, categories  | formatRupiah(), safe_image(), csrf_token()   |
| Katalog & Filter      | user/produk.php          | products, categories  | formatRupiah(), safe_image(), csrf_token()   |
| Detail & Galeri       | user/detail.php          | products, images      | formatRupiah(), safe_image(), csrf_token()   |
| Keranjang Belanja     | user/keranjang.php       | products              | formatRupiah(), csrf_token(), csrf_verify()  |
| Checkout Pengiriman   | user/checkout.php        | addresses, users      | csrf_token(), csrf_verify(), auth guard      |
| Pembayaran            | user/pembayaran.php      | orders, order_items   | formatRupiah(), csrf_token(), csrf_verify()  |
| Konfirmasi & WA       | user/konfirmasi.php      | orders, items, stok   | formatRupiah(), csrf_token(), ubah_stok()    |
| Riwayat Pesanan User  | user/pesanan.php         | orders, order_items   | formatRupiah(), badgeStatus(), auth guard    |
| Detail Pesanan User   | user/detail_pesanan.php  | orders, items, addr   | formatRupiah(), safe_image(), auth guard     |
| Buku Alamat User      | user/alamat.php          | addresses             | csrf_token(), csrf_verify(), auth guard      |
| Kontak CS             | user/contact.php         | contacts              | csrf_token(), csrf_verify(), sanitasi        |
| Autentikasi User      | user/login.php, reg.php  | users                 | csrf_token(), csrf_verify(), password_hash() |
| Dashboard Admin       | admin/index.php          | orders, products, etc | total_omset(), pesanan_baru(), formatRupiah()|
| Manajemen Produk      | admin/produk.php         | products, images      | formatRupiah(), safe_image(), pagination     |
| Formulir Produk Admin | admin/produk_form.php    | products, images, stok| simpan_gambar_webp(), catat_riwayat_stok()   |
| Manajemen Pesanan     | admin/pesanan.php        | orders, users         | formatRupiah(), badgeStatus(), filterOrder() |
| Detail Pesanan Admin  | admin/detail_pesanan.php | orders, items, status | ubah_status_pesanan(), ubah_status_bayar()   |
| Audit Riwayat Stok    | admin/stok.php           | riwayat_stok, products| formatRupiah(), catat_riwayat_stok()         |
| Manajemen Pesan CS    | admin/pesan.php          | contacts              | csrf_token(), csrf_verify(), update is_read  |
| Kelola Kategori       | admin/kategori.php       | categories, products  | csrf_token(), csrf_verify(), query prepared  |
| Konfirmasi Hapus      | admin/konfirmasi_hapus.php| products, orders, msg | csrf_verify(), proteksi integritas relasional|
| Login & Logout Admin  | admin/login.php, logout  | users                 | csrf_verify(), password_verify(), auth guard |
+-----------------------+--------------------------+-----------------------+----------------------------------------------+
```

---

## 3. INDEKS LOKASI `[UBAH DI SINI]` DAN `[JANGAN UBAH]`

### A. Pengaturan Global & Konfigurasi
* `shared/config.php`:
  * `[UBAH DI SINI]`: Kredensial rekening bank (`BANK_NAME`, `BANK_ACCOUNT_NUMBER`, `BANK_ACCOUNT_NAME`), nomor WhatsApp Admin (`ADMIN_WHATSAPP`), dan konstanta ongkos kirim flat (`FLAT_SHIPPING_COST`).
  * `[JANGAN UBAH]`: Array status baku (`DAFTAR_STATUS_ORDER`, `DAFTAR_STATUS_PAYMENT`), himpunan nilai kondisi produk (`DAFTAR_KONDISI_PRODUK`), dan deteksi koneksi port fallback MySQL (`DB_PORTS = [3307, 3306]`).
* `shared/css/tokens.css`:
  * `[UBAH DI SINI]`: Variabel warna streetwear (`--ac` aksen hijau Sage, `--bg` latar hitam, `--s1` card surface, `--tx` warna teks, `--font-heading`).

### B. Modul User (Pengguna)
* `user/produk.php`:
  * `[UBAH DI SINI]`: Jumlah item per halaman (`$perPage = 12`).
  * `[JANGAN UBAH]`: Logika query filter dinamis (multi-checkbox array, sorting whitelist, rentang harga dengan prepared statement).
* `user/keranjang.php`:
  * `[UBAH DI SINI]`: Teks peringatan stok atau copywriting keranjang belanja kosong.
  * `[JANGAN UBAH]`: Sinkronisasi ketersediaan stok aktual langsung ke database (`SELECT stock FROM products`).
* `user/checkout.php` & `user/pembayaran.php`:
  * `[UBAH DI SINI]`: Pilihan daftar ekspedisi kurir default (`$couriers = ['JNE', 'J&T', 'SiCepat']`).
  * `[JANGAN UBAH]`: Formula atomik pembuatan kode order unik (`ORD-YYYYMMDD-XXXX`).

### C. Modul Admin (Administrator)
* `admin/index.php`:
  * `[UBAH DI SINI]`: Batas jumlah transaksi pesanan terbaru yang ditampilkan di dashboard (`LIMIT 5`).
  * `[JANGAN UBAH]`: Formula agregat omset penjualan toko (hanya mengakumulasi pesanan dengan status 'Dibayar' / 'Selesai').
* `admin/produk_form.php`:
  * `[UBAH DI SINI]`: Kualitas kompresi berkas WebP (`$webpQuality = 85`) dan batas resolusi gambar maksimum (2048px).
  * `[JANGAN UBAH]`: Utilitas konversi GD WebP aman tanpa dependensi library eksternal.
* `admin/konfirmasi_hapus.php`:
  * `[JANGAN UBAH]`: Pemeriksaan integritas riwayat pesanan (produk yang sudah pernah dipesan tidak boleh dihapus secara permanen dari tabel, melainkan dinonaktifkan dengan menyetel stok ke 0).

---

## 4. STANDAR ARSITEKTUR BEBAS JAVASCRIPT (ZERO JAVASCRIPT)

Seluruh fungsionalitas interaktif toko dibangun tanpa satu baris pun kode JavaScript (`<script>`, berkas `.js`, atau event handler `onclick`). Standar pengganti yang digunakan:

1. **Modal & Lightbox Gambar**:  
   Menggunakan CSS `:target` pseudo-class. Link thumbnail mengarah ke anchor `#lightbox-preview`, dan tombol tutup mengarah ke `#`.
2. **Konfirmasi Penghapusan / Pembatalan**:  
   Menggunakan halaman perantara terpisah (`admin/konfirmasi_hapus.php`) dengan metode `POST` dan validasi token keamanan CSRF (`hash_equals()`).
3. **Dropdown Menu Mobile & Filter Accordion**:  
   Menggunakan elemen bawaan HTML5 `<details>` dan `<summary>` yang ditata dengan transisi CSS modern.
4. **Pencarian, Filter, dan Pagination**:  
   Menggunakan formulir dengan metode `GET` standar dan parameter link URL yang dirender langsung di sisi server.
5. **Notifikasi Sukses / Gagal**:  
   Menggunakan pola *Post/Redirect/Get* (PRG) memanfaatkan `$_SESSION['flash_success']` dan `$_SESSION['flash_error']`.
6. **Galeri Multi-Sudut Foto Produk**:  
   Menggunakan query parameter link server murni `?id=X&foto=0|1|2` yang mengubah state gambar utama di sisi server secara instan.

---

## 5. PANDUAN PENGEMBANGAN FITUR UMUM (DEVELOPER HOW-TO)

### A. Panduan Menambah Kolom Baru pada Produk (Contoh: Kolom Berat / Weight)
1. **Tambahkan Kolom di Database**:
   Jalankan query ALTER TABLE pada MySQL:
   ```sql
   ALTER TABLE `products` ADD COLUMN `weight` INT NOT NULL DEFAULT 500 AFTER `material`;
   ```
2. **Perbarui Formulir Tambah/Edit Produk Admin (`admin/produk_form.php`)**:
   - Tambahkan input HTML di blok `[TAMPILAN: Form Input Produk]`:
     ```html
     <div class="form-group">
         <label for="weight">Berat Produk (Gram)</label>
         <input type="number" id="weight" name="weight" class="form-control" value="<?= htmlspecialchars($product['weight'] ?? '500') ?>" required>
     </div>
     ```
   - Ambil dan validasi input di blok `[AKSI: Simpan Produk]`:
     ```php
     $weight = max(1, (int)($_POST['weight'] ?? 500));
     ```
   - Tambahkan variabel `$weight` ke query prepared statement `INSERT INTO products` dan `UPDATE products` beserta penyesuaian bind_param (`i` atau `s`).
3. **Tampilkan di Halaman Detail User (`user/detail.php`)**:
   - Di blok spesifikasi produk, tambahkan:
     ```html
     <div class="spec-row">
         <span class="spec-label">Berat:</span>
         <span class="spec-value"><?= number_format($product['weight'], 0, ',', '.') ?> gram</span>
     </div>
     ```
4. **Perbarui Berkas Dump Basis Data**:
   Jalankan skrip ekspor `/database/tools/export_final.php` untuk memperbarui berkas `/database/toko_preloved_final.sql`.

---

### B. Panduan Menambah Status Pesanan Baru (Contoh: Status 'Siap Diambil')
1. **Perbarui ENUM Database**:
   Modifikasi definisi kolom `order_status` di tabel `orders`:
   ```sql
   ALTER TABLE `orders` MODIFY COLUMN `order_status` ENUM('Menunggu Pembayaran', 'Diproses', 'Siap Diambil', 'Dikirim', 'Selesai', 'Dibatalkan') NOT NULL DEFAULT 'Menunggu Pembayaran';
   ```
2. **Daftarkan Konstanta di `shared/config.php`**:
   - Tambahkan konstanta baru:
     ```php
     define('STATUS_ORDER_SIAP_DIAMBIL', 'Siap Diambil');
     ```
   - Daftarkan konstanta tersebut ke dalam array `DAFTAR_STATUS_ORDER`:
     ```php
     define('DAFTAR_STATUS_ORDER', [
         STATUS_ORDER_MENUNGGU_BAYAR,
         STATUS_ORDER_DIPROSES,
         STATUS_ORDER_SIAP_DIAMBIL,
         STATUS_ORDER_DIKIRIM,
         STATUS_ORDER_SELESAI,
         STATUS_ORDER_DIBATALKAN
     ]);
     ```
3. **Tambahkan Styling Badge di CSS**:
   - Buka `admin/css/admin.css` dan `user/css/style.css`, tambahkan kelas warna status:
     ```css
     .badge-status.status-siap-diambil { background: #3b82f6; color: #ffffff; }
     ```
4. **Perbarui Logika Helper di `shared/fungsi.php`**:
   - Tambahkan pemetaan kelas badge pada fungsi `badgeStatusOrder($status)`.

---

### C. Panduan Menambah Halaman Baru di Panel Admin (Contoh: Laporan Penjualan)
1. **Buat Berkas Baru di Folder `admin/`**:
   Buat berkas `admin/laporan.php` dengan struktur standar berikut:
   ```php
   <?php
   // [FILE] admin/laporan.php - Rekap Laporan Penjualan Toko
   require_once __DIR__ . '/../shared/config.php';
   require_once __DIR__ . '/../shared/koneksi.php';
   require_once __DIR__ . '/../shared/fungsi.php';
   require_once __DIR__ . '/auth.php'; // Guard otentikasi admin
   
   $pageTitle = "Laporan Penjualan";
   $activeMenu = "laporan";
   
   // [BAGIAN: Logika Query Data]
   // Tulis prepared query di sini...
   // [/BAGIAN: Logika Query Data]
   
   include __DIR__ . '/sidebar.php';
   ?>
   <!-- [BAGIAN: Tampilan Konten Laporan] -->
   <main class="admin-main">
       <!-- Konten tabel/rekap di sini -->
   </main>
   <!-- [/BAGIAN: Tampilan Konten Laporan] -->
   ```
2. **Tambahkan Menu Navigasi di `admin/sidebar.php`**:
   - Tambahkan item tautan baru di daftar menu vertikal:
     ```html
     <li class="sidebar-item <?= ($activeMenu === 'laporan') ? 'active' : '' ?>">
         <a href="laporan.php" class="sidebar-link">Laporan Penjualan</a>
     </li>
     ```

---

### D. Panduan Mengubah Tema dan Warna Global
1. Buka berkas `/shared/css/tokens.css`.
2. Ubah nilai variabel warna hex pada blok root:
   ```css
   :root {
       --ac: #A3E635; /* Warna Aksen Utama (Ganti dengan kode warna hex baru) */
       --bg: #0A0A0A; /* Latar Belakang Gelap */
       --s1: #141414; /* Warna Kartu Surface 1 */
       --s2: #1C1C1C; /* Warna Kartu Surface 2 */
       --tx: #F5F5F5; /* Warna Teks Utama */
       --mu: #8A8A8A; /* Warna Teks Redup / Keterangan */
   }
   ```
3. Simpan berkas. Seluruh antarmuka pembeli (`user/`) dan administrator (`admin/`) akan menerapkan skema warna baru secara instan dan seragam.

---

### E. Panduan Menambah Kategori Produk Baru
1. Masuk ke Panel Admin &rarr; menu **KATEGORI** (`/admin/kategori.php`).
2. Masukkan nama kategori baru (misalnya: `Aksesoris` atau `Sepatu`) pada form tambah kategori, lalu klik **TAMBAH KATEGORI**.
3. Sistem secara otomatis menyisipkan data kategori ke tabel `categories`.
4. Kategori baru tersebut akan langsung muncul secara otomatis di dropdown pilihan saat menambah produk (`admin/produk_form.php`) serta pada filter pencarian katalog pembeli (`user/produk.php`).

---
*Dokumentasi ini disusun sebagai standar baku pengembangan dan pemeliharaan platform SecondSpace.*
