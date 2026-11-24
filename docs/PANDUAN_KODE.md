# PANDUAN PENGEMBANG & ARSITEKTUR KODE
## SECONDSPACE // CURATED PRELOVED STREETWEAR PLATFORM
**Stack:** PHP Native 8.x, MySQL (via MySQLi & phpMyAdmin), HTML5, CSS3, Laravel Herd Server.  
**Standar Arsitektur:** 100% Bebas JavaScript (Pure PHP + CSS3), Single Source of Truth (`/shared/`), CSRF Protected.

---

## 1. SISTEM KOMENTAR BERTANDA (TAGGED COMMENTS)

Seluruh file di repositori ini dilengkapi dengan tag terstandarisasi untuk memudahkan pemeliharaan, pengembangan bersama anggota tim, dan penyesuaian di masa depan:

| Tag | Arti / Fungsi | Contoh Penggunaan |
| :--- | :--- | :--- |
| `[FILE]` | Informasi identitas file, modul, dan tujuan utamanya. | `// [FILE]: user/produk.php` |
| `[BAGIAN]` | Membatasi blok fungsionalitas tertentu secara modular. | `/* [BAGIAN: Filter URL] */ ... /* [/BAGIAN] */` |
| `[UBAH DI SINI]` | Parameter, konstanta, atau tampilan yang **aman untuk diubah**. | Ubah nomor WhatsApp, tarif flat ongkir, dsb. |
| `[TAMBAH DI SINI]` | Titik kait (hook) yang disarankan untuk **menambah fitur baru**. | Tambah filter brand, voucher diskon, payment gateway. |
| `[JANGAN UBAH]` | Logika inti kritis yang **dilarang diubah** tanpa diskusi tim. | Mutex transaksi stok, session regeneration, formula order code. |
| `[QUERY]` | Blok SQL Query database (Prepared Statement). | Query SELECT atau INSERT ber-parameter aman. |
| `[VALIDASI]` | Pengecekan input formulir di sisi server (Server-side Validation). | Validasi tipe MIME upload, panjang password, filter angka. |
| `[AKSI]` | Pemrosesan metode POST atau mutasi data transaksi. | Handler form action tambah item, proses checkout. |
| `[TAMPILAN]` | Template rendering HTML & CSS yang terlihat oleh pengguna. | Markup kartu produk, tabel pesanan, grid layout. |
| `[KEAMANAN]` | Lapisan proteksi keamanan sistem (CSRF, sanitasi, auth guard). | Pengecekan token CSRF `hash_equals()`, no-cache headers. |

---

## 2. PETA FILE KE TABEL DATABASE (FILE-TO-TABLE MAP)

Berikut adalah pemetaan setiap file terhadap tabel database yang diakses atau dimanipulasi:

```
+------------------+----------------------------------------------------------------------------------------+
| Tabel Database   | File PHP yang Mengakses / Mengubah                                                     |
+------------------+----------------------------------------------------------------------------------------+
| products         | user/index.php, user/produk.php, user/detail.php, user/keranjang.php,                   |
|                  | admin/produk.php, admin/produk_form.php, admin/stok.php, admin/konfirmasi_hapus.php    |
| product_images   | user/detail.php, admin/produk.php, admin/produk_form.php                               |
| orders           | user/checkout.php, user/pembayaran.php, user/konfirmasi.php, user/pesanan.php,         |
|                  | user/detail_pesanan.php, admin/index.php, admin/pesanan.php, admin/detail_pesanan.php  |
| order_items      | user/pembayaran.php, user/konfirmasi.php, user/pesanan.php, user/detail_pesanan.php,   |
|                  | admin/pesanan.php, admin/detail_pesanan.php, admin/konfirmasi_hapus.php                 |
| users            | user/login.php, user/register.php, user/auth.php, admin/login.php, admin/auth.php,     |
|                  | admin/pesanan.php, admin/detail_pesanan.php                                            |
| addresses        | user/alamat.php, user/checkout.php, user/detail_pesanan.php, admin/detail_pesanan.php  |
| contacts         | user/contact.php, admin/pesan.php, admin/konfirmasi_hapus.php                          |
| riwayat_status   | shared/fungsi.php (ubah_status_pesanan, ubah_status_pembayaran),                       |
|                  | admin/detail_pesanan.php (tabel audit trail status)                                    |
| riwayat_stok     | shared/fungsi.php (ubah_stok, catat_riwayat_stok), admin/stok.php (tabel riwayat stok), |
|                  | user/konfirmasi.php (pencatatan stok keluar saat checkout)                             |
+------------------+----------------------------------------------------------------------------------------+
```

---

## 3. INDEKS AREA `[UBAH DI SINI]` & `[JANGAN UBAH]`

### A. Pengaturan Global & Konfigurasi
* `shared/config.php`:
  * `[UBAH DI SINI]`: Kredensial rekening bank (`BANK_NAME`, `BANK_ACCOUNT_NUMBER`, `BANK_ACCOUNT_NAME`), nomor WhatsApp Admin (`ADMIN_WHATSAPP`), dan konstanta ongkir flat.
  * `[JANGAN UBAH]`: Array status baku (`DAFTAR_STATUS_ORDER`, `DAFTAR_STATUS_PAYMENT`), whitelist ekstensi upload, dan deteksi port fallback database.

* `shared/css/tokens.css`:
  * `[UBAH DI SINI]`: Variabel warna streetwear (`--ac` aksen hijau Sage, `--bg` latar hitam, `--s1` card surface, `--tx` warna teks, `--font-heading`).

### B. Modul User
* `user/produk.php`:
  * `[UBAH DI SINI]`: Jumlah item per halaman (`$perPage = 12`).
  * `[JANGAN UBAH]`: Logika query filter dinamis (multi-checkbox array, sorting whitelist, rentang harga).
* `user/keranjang.php`:
  * `[UBAH DI SINI]`: Teks peringatan stok atau copywriting keranjang kosong.
  * `[JANGAN UBAH]`: Sinkronisasi live stok dengan database (`SELECT stock FROM products`).
* `user/checkout.php` & `user/pembayaran.php`:
  * `[UBAH DI SINI]`: Opsi pilihan ekspedisi kurir default (`$couriers = ['JNE', 'J&T', 'SiCepat']`).
  * `[JANGAN UBAH]`: Transaksi atomik pembuatan kode order (`ORD-YYYYMMDD-XXXX`).

### C. Modul Admin
* `admin/index.php`:
  * `[UBAH DI SINI]`: Jumlah pesanan terbaru yang ditampilkan di dashboard (`LIMIT 5`).
  * `[JANGAN UBAH]`: Formula KPI agregat omset (hanya menghitung pesanan dengan status 'Dibayar' / 'Selesai').
* `admin/produk_form.php`:
  * `[UBAH DI SINI]`: Kualitas konversi WebP otomatis (`$webpQuality = 85`) dan batas resolusi gambar (2048px).
  * `[JANGAN UBAH]`: Konversi GD WebP aman tanpa dependensi library eksternal.
* `admin/konfirmasi_hapus.php`:
  * `[JANGAN UBAH]`: Pemeriksaan integritas riwayat pesanan (produk yang sudah pernah dibeli tidak dihapus permanen, melainkan dinonaktifkan dengan menyetel stok ke 0).

---

## 4. STANDAR IMPLEMENTASI BEBAS JAVASCRIPT (NO-JS RULES)

Seluruh fungsionalitas interaktif dibangun tanpa satu baris pun kode JavaScript (`<script>`, `.js`, atau `onclick`). Alternatif yang digunakan:

1. **Modal & Lightbox Gambar:**  
   Menggunakan CSS `:target` pseudo-class. Link thumbnail mengarah ke anchor `#lightbox-bukti`, dan tombol tutup mengarah ke `#`.
2. **Konfirmasi Penghapusan / Pembatalan:**  
   Menggunakan halaman perantara terpisah (`admin/konfirmasi_hapus.php`) dengan metode `POST` dan validasi token keamanan CSRF (`hash_equals()`).
3. **Dropdown Menu Mobile & Kategori:**  
   Menggunakan elemen bawaan HTML5 `<details>` dan `<summary>`.
4. **Pencarian, Filter, dan Pagination:**  
   Menggunakan form metode `GET` standar dan parameter link URL yang dirender langsung di server.
5. **Notifikasi Sukses / Gagal:**  
   Pola Post/Redirect/Get (PRG) memanfaatkan `$_SESSION['flash_success']` dan `$_SESSION['flash_error']`.
6. **Galeri Foto Produk:**  
   Menggunakan query parameter link murni `?id=X&foto=0|1|2` yang mengubah state gambar utama di sisi server.

---

## 5. PANDUAN PENGEMBANG (DEVELOPER HOW-TO)

### Cara Menambah Produk Baru
1. Buka browser ke `http://aplikasi-preloved-main.test/admin/login.php` (login admin: `admin` / `admin123`).
2. Masuk ke menu **PRODUK** &rarr; klik tombol **+ TAMBAH PRODUK BARU**.
3. Isi nama, kategori, harga, stok (biasanya 1 untuk item preloved), ukuran, kondisi, dan deskripsi.
4. Unggah foto produk (format JPG, PNG, atau WebP). Sistem secara otomatis mengonversi gambar ke format **WebP teroptimasi** di direktori `/uploads/produk/`.
5. Klik **SIMPAN PRODUK**. Produk langsung live di katalog toko user.

### Cara Memproses Pesanan Baru Masuk
1. Buka menu **PESANAN** di panel admin.
2. Temukan pesanan baru bertanda `MENUNGGU KONFIRMASI` atau `MENUNGGU PEMBAYARAN`.
3. Klik tombol **DETAIL & PROSES &rarr;**.
4. Periksa bukti transfer di kartu **BUKTI TRANSFER PEMBAYARAN**. Klik thumbnail untuk memperbesar via modal CSS.
5. Di bagian **VERIFIKASI PEMBAYARAN**, ubah status menjadi `Dibayar (Lunas Terverifikasi)` &rarr; klik **SIMPAN PEMBAYARAN**.
6. Siapkan paket, lalu di bagian **PROSES STATUS PESANAN & PENGIRIMAN**, pilih kurir (misal: `JNE`), masukkan **Nomor Resi**, dan ubah status menjadi `Dikirim` &rarr; klik **PERBARUI STATUS PESANAN**.
7. Pelanggan secara instan melihat nomor resi dan status terkini di halaman `user/pesanan.php` dan `user/detail_pesanan.php`.

### Cara Mengubah Tema dan Warna
1. Buka file `/shared/css/tokens.css`.
2. Ubah nilai variabel di `:root`, misalnya:
   * Mengubah aksen hijau: ubah `--ac: #A3E635;` ke warna hex pilihanmu.
   * Mengubah warna latar belakang: ubah `--bg: #0A0A0A;`.
3. Simpan file. Seluruh halaman pengguna dan halaman admin akan langsung menerapkan warna baru secara konsisten.

---
*Dokumentasi ini disusun sebagai standar baku pengembangan tim proyek SecondSpace.*
