# SECONDSPACE - Curated Preloved Streetwear E-Commerce Platform

[![PHP Native](https://img.shields.io/badge/PHP-8.1%20%7C%208.2%20%7C%208.3%20%7C%208.4-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/Database-MySQL%20%2F%20MariaDB-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Zero JavaScript](https://img.shields.io/badge/JavaScript-100%25%20Free%20(Zero%20JS)-black?style=for-the-badge)](https://developer.mozilla.org/)
[![Brutalist UI](https://img.shields.io/badge/Design-Minimalist%20Brutalist-A3E635?style=for-the-badge&labelColor=0A0A0A)](https://github.com/)

**SECONDSPACE** adalah platform e-commerce kurasi pakaian bekas jalanan (*curated preloved streetwear*) berkualitas tinggi yang dirancang khusus untuk barang-barang unik 1-of-1 pieces.

Dibangun dengan prinsip arsitektur **100% Pure PHP Native, HTML5, dan CSS3 murni tanpa ketergantungan JavaScript (Zero JavaScript)**, sistem ini membuktikan bahwa aplikasi web interaktif, cepat, dan aman dapat dibangun secara elegan mengandalkan fitur native web standar, server-side rendering penuh, dan state management PHP session.

---

## DAFTAR ISI

1. [Fitur Unggulan](#fitur-unggulan)
2. [Arsitektur & Konsep Teknis](#arsitektur--konsep-teknis)
3. [Tangkapan Layar & Antarmuka](#tangkapan-layar--antarmuka)
4. [Teknologi yang Digunakan](#teknologi-yang-digunakan)
5. [Struktur Direktori Repositori](#struktur-direktori-repositori)
6. [Persyaratan Sistem](#persyaratan-sistem)
7. [Panduan Instalasi Cepat](#panduan-instalasi-cepat)
8. [Akun Pengujian Demo](#akun-pengujian-demo)
9. [Alur Transaksi & Mutasi Stok Preloved](#alur-transaksi--mutasi-stok-preloved)
10. [Panduan Pemecahan Masalah (Troubleshooting)](#panduan-pemecahan-masalah-troubleshooting)
11. [Tim Pengembang](#tim-pengembang)

---

## FITUR UNGGULAN

### 1. Pengalaman Pengguna (Pembeli / User)
* **Katalog Brutalist & Multi-Filter**: Pencarian teks instan, filter kategori (Baju, Celana, Jaket, Kemeja), filter ukuran (S hingga XXL, 30 hingga 42), serta filter kondisi terstandarisasi (Seperti Baru, Bagus, Ada Minus).
* **Galeri Foto Multi-Sudut (No-JS)**: Inspeksi produk mendalam dari tampak depan, tampak belakang, hingga detail bahan/minus hanya mengandalkan link server murni dan CSS transition.
* **Keranjang Belanja Real-Time (1-of-1 Piece)**: Validasi stok langsung ke database dengan penguncian kuantitas 1 piece per item unik preloved.
* **Buku Alamat & Pilihan Ekspedisi**: Manajemen alamat pengiriman pembeli terintegrasi dengan pilihan ekspedisi terpercaya (JNE, J&T, SiCepat) dan flat ongkos kirim transparan.
* **Multi-Kanal Pembayaran**: Transfer Bank BCA/Mandiri resmi, E-Wallet (GoPay, OVO, Dana), dan QRIS dinamis.
* **Integrasi WhatsApp CS Instan**: Generator format pesan rincian pesanan otomatis siap kirim ke WhatsApp admin untuk konfirmasi pembayaran cepat.
* **Pelacakan Status Pesanan**: Pemantauan tahapan paket real-time (Menunggu Pembayaran, Diproses, Dikirim, Selesai) lengkap dengan nomor resi pengiriman.
* **Keamanan Pengguna Penuh**: Proteksi Cross-Site Request Forgery (CSRF) pada seluruh formulir POST dan hashing kata sandi satu arah (`bcrypt`).

### 2. Panel Administrator (Pengelola Toko)
* **Dashboard Metrik & Laporan Finansial**: Agregat omset penjualan lunas sah, penghitung pesanan aktif, antrean verifikasi pembayaran, dan katalog barang tersedia.
* **Manajemen Inventaris & Galeri Foto**: Tambah/ubah produk dengan konversi otomatis gambar ke format WebP terkompresi berbasis GD Library.
* **Audit Trail Mutasi Stok**: Pencatatan riwayat setiap penambahan, pengurangan saat checkout, pembatalan order, atau restock fisik barang.
* **Manajemen Alur Pesanan (State Machine)**: Pembaruan status transaksi berurutan, penolakan pembayaran tidak sah, penginputan nomor resi, dan penyelesaian order.
* **Audit Trail Perubahan Status**: Log audit permanen di tabel `riwayat_status` untuk mencatat siapa admin yang mengubah status dan alasan perubahannya.
* **Pusat Pesan Kontak CS**: Pengelolaan pesan masuk pembeli dari formulir kontak dengan penanda status baca.
* **Safe Deletion Guard**: Proteksi relasional yang mencegah produk yang sudah memiliki transaksi terhapus dari basis data.

---

## ARSITEKTUR & KONSEP TEKNIS

### Mengapa Zero JavaScript?
Proyek ini mengimplementasikan tantangan teknis untuk membangun pengalaman web modern tanpa menggunakan JavaScript sama sekali:
* **Modal & Lightbox**: Memanfaatkan pseudo-class CSS `:target`.
* **Dropdown & Filter Accordion**: Memanfaatkan elemen bawaan HTML5 `<details>` dan `<summary>`.
* **State Galeri Produk**: Memanfaatkan parameter URL server `?id=X&foto=0|1|2`.
* **Notifikasi Flash**: Memanfaatkan pola arsitektur *Post/Redirect/Get* (PRG) via PHP Session.
* **Keamanan Form**: Memanfaatkan token CSRF sesi yang divalidasi via `hash_equals()`.

---

## TEKNOLOGI YANG DIGUNAKAN

* **Backend Engine**: PHP 8.x Native (Clean Procedural & Modular Pattern)
* **Database Management**: MySQL 8.x / MariaDB (Koneksi via PHP MySQLi Extension)
* **Markup**: HTML5 Semantik
* **Styling**: Modern CSS3 (CSS Variables, Flexbox, CSS Grid, Pseudo-classes)
* **JavaScript**: **0% (Zero JavaScript)**
* **Server Environment**: Laravel Herd, Laragon, atau XAMPP

---

## STRUKTUR DIREKTORI REPOSITORI

```text
Aplikasi-Preloved-main/
|-- admin/                     # Modul Panel Pengelola / Administrator Toko
|   |-- css/admin.css          # Stylesheet streetwear brutalist panel admin
|   |-- auth.php               # Guard proteksi autentikasi session admin
|   |-- index.php              # Dashboard metrik & ringkasan penjualan toko
|   |-- produk.php             # Tabel manajemen inventaris produk & filter Opsi A
|   |-- produk_form.php        # Formulir input/edit produk & unggah WebP otomatis
|   |-- pesanan.php            # Daftar transaksi & filter pemrosesan pesanan
|   |-- detail_pesanan.php     # Rincian pesanan, audit status, & verifikasi bayar
|   |-- stok.php               # Tabel log audit mutasi riwayat stok produk
|   |-- pesan.php              # Manajemen pesan masuk kontak pelanggan
|   |-- kategori.php           # Handler integrasi kategori ke manajemen produk
|   |-- konfirmasi_hapus.php   # Perantara konfirmasi hapus data aman (CSRF protected)
|   |-- login.php / logout.php # Otentikasi sesi administrator
|   `-- sidebar.php            # Navigasi vertikal admin
|-- database/                  # Skrip Basis Data & Diagnostik
|   |-- .htaccess              # Blokir akses publik ke berkas SQL
|   |-- migrations/            # Rekam jejak migrasi perubahan struktur skema
|   |-- tools/                 # Skrip diagnostik konsistensi & ekspor final
|   `-- toko_preloved_final.sql# Dump skema basis data bersih siap impor
|-- docs/                      # Dokumentasi Teknis Proyek
|   |-- ALUR_SISTEM.md         # ERD, diagram relasi, & state machine status transaksi
|   |-- CHECKLIST_PENGUMPULAN.md # Daftar periksa kelengkapan pengumpulan proyek
|   |-- LAPORAN_FINAL.md       # Laporan komprehensif pengujian sistem
|   |-- PANDUAN_KODE.md        # Peta kode, indeks tagged comments, & developer how-to
|   `-- PROGRESS_FINAL.md      # Rekam jejak kemajuan Fase 1 hingga Fase 9
|-- shared/                    # Pustaka Sentral Bersama (Single Source of Truth)
|   |-- css/tokens.css         # Variabel desain CSS global (warna, surface, font)
|   |-- config.example.php     # Templat konfigurasi bersih untuk lingkungan baru
|   |-- config.php             # Konfigurasi aplikasi, kredensial DB, & konstanta status
|   |-- fungsi.php             # Library utilitas, format mata uang, CSRF, & audit status
|   |-- image_helper.php       # Utilitas konversi & kompresi foto ke format WebP
|   `-- koneksi.php            # Koneksi MySQLi dengan port auto-fallback (3307/3306)
|-- uploads/                   # Direktori Berkas Publik
|   `-- produk/                # Koleksi foto produk WebP teroptimasi (72 foto)
|-- user/                      # Modul Antarmuka Pembeli / Pengguna Toko
|   |-- css/                   # Stylesheet antarmuka pembeli (style.css, responsive.css)
|   |-- images/                # Aset visual antarmuka pembeli & barcode QRIS
|   |-- index.php              # Beranda etalase toko preloved
|   |-- produk.php             # Katalog pencarian, multi-filter, & pagination
|   |-- detail.php             # Detail produk & galeri multi-sudut no-JS
|   |-- keranjang.php          # Keranjang belanja & validasi ketersediaan stok
|   |-- checkout.php           # Alamat pengiriman & pemilihan kurir ekspedisi
|   |-- pembayaran.php         # Petunjuk transfer pembayaran bank, e-wallet, & QRIS
|   |-- konfirmasi.php         # Pembuatan transaksi atomik & konfirmasi WhatsApp
|   |-- pesanan.php            # Riwayat daftar transaksi belanja pembeli
|   |-- detail_pesanan.php     # Status pelacakan pesanan & nomor resi paket
|   |-- alamat.php             # Buku alamat pengguna
|   |-- contact.php            # Formulir pesan ke Customer Service
|   `-- login.php / reg.php    # Otentikasi dan registrasi pembeli
|-- .gitignore                 # Konfigurasi pengecualian repositori Git
|-- index.php                  # Redirect entry point utama ke antarmuka katalog user
`-- README.md                  # Dokumentasi utama proyek
```

---

## PERSYARATAN SISTEM

* **Web Server**: Laravel Herd (Direkomendasikan), Laragon, atau XAMPP
* **PHP Engine**: PHP 8.1 / 8.2 / 8.3 / 8.4
* **Ekstensi PHP Wajib**: `mysqli`, `gd`, `session`, `curl`
* **Basis Data**: MySQL 8.0+ atau MariaDB 10.4+
* **Peramban**: Google Chrome, Mozilla Firefox, Microsoft Edge, Safari

---

## PANDUAN INSTALASI CEPAT

### 1. Clone Repositori
```bash
git clone https://github.com/username/Aplikasi-Preloved.git
cd Aplikasi-Preloved
```

### 2. Siapkan Server Lokal (Laravel Herd)
Pindahkan folder proyek ke dalam direktori park Laravel Herd (misalnya `C:\Users\[Username]\Herd\Aplikasi-Preloved-main`) atau tambahkan lokasi folder melalui antarmuka **Herd > Sites**.

### 3. Buat Database & Impor Skema
1. Buka phpMyAdmin atau MySQL GUI client (HeidiSQL/TablePlus).
2. Buat database baru bernama:
   ```text
   secondspace_preloved
   ```
3. Impor berkas dump final yang telah disediakan di:
   ```text
   database/toko_preloved_final.sql
   ```

### 4. Konfigurasi Koneksi (Bila Diperlukan)
File `shared/config.php` telah dirancang dengan fitur **auto-detect port fallback** (mencoba port `3307` default Herd, lalu otomatis fallback ke `3306`). Jika kredensial database Anda berbeda, sesuaikan konstanta di `shared/config.php`:
```php
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'secondspace_preloved');
```

### 5. Jalankan Aplikasi
Buka peramban web dan akses alamat virtual host:
```text
http://aplikasi-preloved-main.test
```
*(Atau `http://localhost/Aplikasi-Preloved-main` jika menggunakan XAMPP).*

---

## AKUN PENGUJIAN DEMO

Gunakan kredensial pengujian berikut untuk mengevaluasi sistem:

| Peran (Role) | Username | Password | Hak Akses & Keterangan |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin` | `password` | Akses penuh dashboard admin, kelola pesanan, ubah status, kelola produk |
| **User (Pembeli 1)** | `demo123` | `password` | Pengguna contoh dengan data pesanan dan alamat terdaftar |
| **User (Pembeli 2)** | `ikhsan123` | `password` | Pengguna contoh untuk pengujian alur checkout baru |

---

## ALUR TRANSAKSI & MUTASI STOK PRELOVED

```text
[Pembeli Memilih Produk]
        |
        v
[Tambah ke Keranjang] (Stok dikunci 1 piece)
        |
        v
[Isi Alamat & Pilih Kurir] (JNE / J&T / SiCepat)
        |
        v
[Pilih Metode Pembayaran] (Transfer Bank / E-Wallet / QRIS)
        |
        v
[Transaksi Atomik Checkout] ---> [Stok Berkurang] & [Tercatat di riwayat_stok]
        |
        v
[Status: Menunggu Pembayaran] ---> [Konfirmasi WhatsApp CS]
        |
        v
[Admin Verifikasi Pembayaran] ---> [Status: Diproses] & [Tercatat di riwayat_status]
        |
        v
[Admin Input Resi & Kurir]    ---> [Status: Dikirim]
        |
        v
[Barang Diterima Pembeli]     ---> [Status: Selesai]
```
*(Catatan: Jika pesanan dibatalkan oleh admin atau pembeli, stok barang secara otomatis dikembalikan ke etalase dan tercatat transparan pada log audit mutasi).*

---

## PANDUAN PEMECAHAN MASALAH (TROUBLESHOOTING)

1. **Koneksi Database Gagal (*Connection Refused*)**:
   - Pastikan servis MySQL di Herd/XAMPP dalam status *Running*.
   - Periksa port aktif MySQL Anda. `shared/config.php` telah mendukung prioritas port `[3307, 3306]`.
2. **Gambar Produk Tidak Tampil**:
   - Pastikan folder `uploads/produk/` memiliki berkas gambar format `.webp`.
   - Buka `shared/config.php` dan pastikan konstanta `BASE_URL` mengarah tepat ke domain virtual host Anda.
3. **Pesan Kesalahan Token CSRF Mismatch**:
   - Terjadi jika form didiamkan hingga sesi kedaluwarsa. Muat ulang (*refresh*) halaman untuk memperbarui token keamanan form.

---

## TIM PENGEMBANG

Proyek ini dikembangkan oleh:

| **[Nama Anggota 1]** | [NIM 1] | Project Lead, Backend User, Checkout & Integrasi Alur Transaksi |
| **[Nama Anggota 2]** | [NIM 2] | Perancangan Database MySQL, Optimasi Skema, Audit Konsistensi |

---

## LISENSI

Proyek ini dirilis untuk keperluan akademik dan demonstrasi arsitektur perangkat lunak e-commerce berbasis PHP Native berkinerja tinggi tanpa dependensi JavaScript. Seluruh merek dagang dan aset visual kurasi preloved merupakan hak cipta dari masing-masing pemiliknya.
