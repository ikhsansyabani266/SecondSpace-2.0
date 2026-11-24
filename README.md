# SECONDSPACE - CURATED PRELOVED STREETWEAR PLATFORM

Platform e-commerce preloved streetwear berbasis web yang dirancang khusus untuk kurasi pakaian bekas berkualitas (1-of-1 pieces). Aplikasi ini mengusung pendekatan minimalis brutalist streetwear dengan performa tinggi, keamanan server-side yang ketat, dan arsitektur murni bebas JavaScript (Pure PHP Native + HTML5 + CSS3).

---

## DAFTAR FITUR

### 1. Fitur Pengguna (User / Pembeli)
* **Katalog Produk Dinamis**: Eksplorasi katalog streetwear dengan pencarian teks, filter kategori (Baju, Celana, Jaket, Kemeja), filter kondisi terstandarisasi (Seperti Baru, Bagus, Ada Minus), sorting harga/terbaru, serta navigasi halaman (pagination).
* **Detail Produk & Galeri Multi-Sudut**: Inspeksi mendalam produk dengan galeri foto multi-sudut (tampak depan, tampak belakang, detail bahan/minus) menggunakan preview murni CSS tanpa JavaScript.
* **Manajemen Keranjang Belanja**: Penambahan item ke keranjang belanja berbasis session PHP dengan validasi ketersediaan stok aktual secara real-time.
* **Buku Alamat Pengiriman**: Simpan dan kelola alamat tujuan pengiriman terhubung langsung ke profil pengguna.
* **Checkout & Pilihan Kurir**: Kalkulasi subtotal belanja otomatis, pilihan kurir pengiriman (JNE, J&T, SiCepat), flat ongkos kirim transparan, dan catatan pengiriman.
* **Instruksi Pembayaran Multi-Kanal**: Dukungan pembayaran via Transfer Bank (BCA, Mandiri) dengan nomor rekening resmi, E-Wallet (GoPay, OVO, Dana), dan QRIS dinamis.
* **Konfirmasi & Notifikasi WhatsApp Otomatis**: Integrasi pembuatan pesan ringkasan transaksi otomatis yang siap diteruskan ke WhatsApp Customer Service admin toko untuk konfirmasi instan.
* **Riwayat & Pelacakan Pesanan**: Halaman daftar pesanan pembeli dengan status transaksi real-time (Menunggu Pembayaran, Diproses, Dikirim, Selesai, Dibatalkan) dan nomor resi pengiriman.
* **Formulir Kontak CS**: Layanan kirim pesan pertanyaan atau bantuan langsung dari pengguna ke dashboard admin.
* **Autentikasi Pengguna**: Registrasi akun baru dan login aman dengan enkripsi password satu arah (bcrypt).

### 2. Fitur Administrator (Admin Toko)
* **Dashboard Metrik & Ringkasan Penjualan**: Ringkasan performa toko secara real-time meliputi total omset penjualan sah, total pesanan diproses, antrean verifikasi pembayaran, total katalog produk aktif, serta daftar pesanan terbaru.
* **Manajemen Katalog Produk**: Tambah produk baru, ubah rincian produk (nama, brand, ukuran, kategori, kondisi, material, harga, deskripsi), serta unggah dan kelola galeri multi-foto.
* **Optimasi Gambar Otomatis**: Kompresi dan konversi otomatis gambar produk yang diunggah menjadi format WebP modern berbasis GD Library.
* **Audit & Pelacakan Stok (Stock History)**: Riwayat mutasi kuantitas stok produk yang mencatat alasan perubahan (penjualan, pembatalan, restock admin) beserta ID penanggung jawab.
* **Manajemen Pesanan & Alur Status**: Pembaruan status pesanan bertahap dari penerimaan pesanan, verifikasi pembayaran, pengemasan, penginputan nomor resi pengiriman, hingga penyelesaian pesanan.
* **Audit Trail Perubahan Status**: Pencatatan log permanen di database setiap kali terjadi perubahan status pembayaran atau pesanan untuk akuntabilitas operasional toko.
* **Manajemen Pesan Masuk**: Pengelolaan pesan dari formulir kontak pengguna dengan penanda status sudah dibaca atau belum dibaca.
* **Penghapusan Aman (Safe Deletion Guard)**: Proteksi data relasional di mana produk yang sudah memiliki riwayat transaksi tidak dapat dihapus permanen, melainkan dinonaktifkan stoknya.
* **Keamanan Admin**: Proteksi halaman admin berbasis middleware session dan pengecekan role secara ketat.

---

## TEKNOLOGI YANG DIGUNAKAN

* **Backend Engine**: PHP 8.x Native (Procedural & Clean Modular Architecture)
* **Database**: MySQL 8.x / MariaDB (Koneksi via PHP MySQLi Extension)
* **Frontend Markup**: HTML5 Semantik
* **Styling**: CSS3 Murni (CSS Variables, Flexbox, CSS Grid, Pseudo-classes)
* **Arsitektur JavaScript**: **ZERO JAVASCRIPT** (100% Bebas dari tag script, berkas .js, inline event handler, dan AJAX framework)
* **Lingkungan Server Lokal**: Laravel Herd (Windows)

---

## ANGGOTA KELOMPOK & PEMBAGIAN TUGAS

| No | Nama Mahasiswa / Anggota | NIM | Pembagian Tugas / Tanggung Jawab |
| :---: | :--- | :--- | :--- |
| 1 | [Nama Anggota 1] | [NIM 1] | Koordinator Proyek, Backend User, Alur Checkout & Pembayaran |
| 2 | [Nama Anggota 2] | [NIM 2] | Perancangan Database MySQL, Optimasi Skema, Audit Konsistensi |
| 3 | [Nama Anggota 3] | [NIM 3] | Backend Modul Admin, Dashboard Metrik, Alur Status Pesanan & Stok |
| 4 | [Nama Anggota 4] | [NIM 4] | Desain UI/UX Streetwear Brutalist, Responsive CSS, Implementasi No-JS |
| 5 | [Nama Anggota 5] | [NIM 5] | Pengujian Sistem, Keamanan CSRF & Sanitasi Input, Dokumentasi |

*(Catatan: Bagian tabel di atas dapat disesuaikan dengan data riil anggota kelompok).*

---

## STRUKTUR FOLDER PROYEK

```text
Aplikasi-Preloved-main/
|-- admin/                     # Halaman dan antarmuka administrator toko
|   |-- css/                   # Berkas stylesheet khusus panel admin (admin.css)
|   |-- auth.php               # Guard autentikasi session admin
|   |-- index.php              # Dashboard metrik & ringkasan penjualan
|   |-- produk.php             # Tabel manajemen katalog produk
|   |-- produk_form.php        # Formulir tambah & edit produk beserta upload foto
|   |-- pesanan.php            # Daftar dan filter transaksi pesanan
|   |-- detail_pesanan.php     # Rincian pesanan, audit status, & verifikasi pembayaran
|   |-- stok.php               # Log audit mutasi stok barang
|   |-- pesan.php              # Pengelolaan pesan masuk kontak pengguna
|   |-- kategori.php           # Manajemen kategori pakaian
|   |-- riwayat.php            # Log rekap riwayat sistem
|   |-- konfirmasi_hapus.php   # Halaman perantara konfirmasi hapus data (CSRF safe)
|   |-- login.php              # Halaman login administrator
|   |-- logout.php             # Handler terminasi sesi login
|   `-- sidebar.php            # Komponen navigasi vertikal admin
|-- database/                  # Skrip basis data & utilitas pemeliharaan
|   |-- backup/                # Arsip pencadangan database berkala (diabaikan git)
|   |-- migrations/            # Rekam jejak migrasi perubahan struktur skema
|   |-- tools/                 # Skrip diagnostik (cek konsistensi, backup, ekspor final)
|   |-- .htaccess              # Proteksi blokir akses langsung berkas SQL via web
|   |-- migration.sql          # Skrip migrasi komparatif
|   `-- toko_preloved_final.sql# Dump basis data final bersih siap impor
|-- docs/                      # Dokumentasi teknis dan panduan pengembang
|   |-- .htaccess              # Proteksi pembatasan akses dokumen
|   |-- ALUR_SISTEM.md         # Diagram alur sistem, state machine, dan teks ERD
|   |-- CHECKLIST_PENGUMPULAN.md # Daftar periksa kesiapan paket pengumpulan
|   |-- PANDUAN_KODE.md        # Peta kode, indeks komentar bertanda, & how-to guide
|   `-- PROGRESS_FINAL.md      # Catatan audit kemajuan pengerjaan proyek
|-- images/                    # Asset gambar umum dan logo antarmuka
|-- shared/                    # Modul sentral, konfigurasi, dan fungsi bersama
|   |-- css/                   # Design token global & variabel warna CSS (tokens.css)
|   |-- config.php             # Konfigurasi sistem utama (database, status, konstanta)
|   |-- config.example.php     # Templat contoh konfigurasi untuk deployment
|   |-- koneksi.php            # Penghubung koneksi MySQLi dengan port auto-fallback
|   |-- fungsi.php             # Library fungsi utilitas, format rupiah, dan audit status
|   `-- image_helper.php       # Utilitas kompresi & konversi otomatis gambar ke WebP
|-- uploads/                   # Direktori penyimpanan unggahan berkas publik
|   `-- produk/                # Berkas foto produk terkompresi WebP
|-- user/                      # Halaman antarmuka belanja pengguna (pembeli)
|   |-- css/                   # Stylesheet antarmuka user (style.css, responsive.css)
|   |-- images/                # Asset gambar khusus antarmuka user & QRIS
|   |-- index.php              # Halaman beranda utama toko preloved
|   |-- produk.php             # Katalog pencarian, filter, dan pagination produk
|   |-- detail.php             # Halaman rincian produk & galeri multi-foto
|   |-- keranjang.php          # Keranjang belanja & sinkronisasi stok
|   |-- checkout.php           # Formulir alamat & pemilihan opsi kurir
|   |-- pembayaran.php         # Pemilihan metode pembayaran & transfer instruksi
|   |-- konfirmasi.php         # Transaksi atomik pembuatan pesanan & link konfirmasi WA
|   |-- pesanan.php            # Riwayat daftar pesanan pengguna
|   |-- detail_pesanan.php     # Status pelacakan pesanan pembeli
|   |-- alamat.php             # Pengelolaan buku alamat pengiriman pengguna
|   |-- contact.php            # Formulir kirim pesan layanan pelanggan
|   |-- login.php              # Formulir login pembeli
|   |-- register.php           # Formulir pendaftaran akun baru
|   |-- logout.php             # Handler logout pengguna
|   |-- auth.php               # Guard session pembeli
|   |-- modern-theme.css       # Stylesheet tema streetwear modern
|   |-- header.php             # Komponen navigasi atas pembeli
|   `-- footer.php             # Komponen footer informasi toko
|-- .gitignore                 # Aturan pengecualian berkas repositori Git
|-- index.php                  # Entry point pengarah (redirect) ke katalog user
`-- README.md                  # Dokumentasi utama proyek
```

---

## PERSYARATAN SISTEM

* **Sistem Operasi**: Windows 10 / 11, macOS, atau Linux
* **Web Server & Environment**: Laravel Herd (Sangat Direkomendasikan) atau Laragon / XAMPP
* **Versi PHP**: PHP 8.1, 8.2, 8.3, atau 8.4 (Wajib mengaktifkan ekstensi `mysqli`, `gd`, dan `session`)
* **Database Server**: MySQL 8.0+ atau MariaDB 10.4+
* **Web Browser**: Google Chrome, Mozilla Firefox, Microsoft Edge, atau Safari (Versi modern)

---

## LANGKAH INSTALASI DI LARAVEL HERD

1. **Letakkan Folder Proyek**:
   Pindahkan folder `Aplikasi-Preloved-main` ke dalam direktori park Laravel Herd (biasanya di `C:\Users\[Username]\Herd\` pada Windows) atau buka antarmuka Laravel Herd, pilih menu **Sites**, lalu tambahkan path folder ini.

2. **Nyalakan Layanan Database**:
   Buka aplikasi Laravel Herd, pastikan status **Nginx/Web Server** dan **MySQL Service** dalam kondisi berjalan (*Running*).

3. **Buat Database Baru**:
   Buka antarmuka phpMyAdmin atau MySQL GUI Client (HeidiSQL/TablePlus). Buat database baru dengan nama:
   ```text
   secondspace_preloved
   ```
   Gunakan collation `utf8mb4_general_ci` atau `utf8mb4_unicode_ci`.

4. **Impor Skema Basis Data**:
   Pilih database `secondspace_preloved`, lalu impor berkas SQL bersih yang telah disediakan di:
   ```text
   /database/toko_preloved_final.sql
   ```
   Pastikan proses impor selesai dengan pesan sukses tanpa error.

5. **Sesuaikan Konfigurasi Koneksi (Bila Diperlukan)**:
   Secara default, file `shared/config.php` telah dikonfigurasi untuk mendeteksi port MySQL secara otomatis (mencoba port `3307` bawaan Herd terlebih dahulu, kemudian fallback ke port `3306`).
   Bila kredensial Anda berbeda, sesuaikan konstanta berikut di `shared/config.php`:
   ```php
   define('DB_HOST', '127.0.0.1');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'secondspace_preloved');
   ```

6. **Akses Aplikasi**:
   Buka peramban web dan akses alamat domain lokal Herd:
   ```text
   http://aplikasi-preloved-main.test
   ```
   *(Atau sesuai nama virtual host yang didaftarkan di Laravel Herd).*

---

## AKUN PENGUJIAN (TEST ACCOUNTS)

Sistem telah dilengkapi akun contoh siap pakai untuk pengujian fungsionalitas:

| Peran (Role) | Username | Password | Keterangan Akses |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin` | `password` | Akses penuh ke seluruh menu Panel Admin (`/admin/login.php`) |
| **User (Pembeli 1)** | `demo123` | `password` | Pengguna contoh dengan data pesanan dan alamat tersimpan |
| **User (Pembeli 2)** | `ikhsan123` | `password` | Pengguna contoh untuk pengujian transaksi checkout baru |

---

## ALUR PENGGUNAAN SINGKAT

### 1. Alur Pengguna (Membeli Produk)
1. **Pilih Produk**: Akses halaman katalog di `/user/produk.php`, gunakan filter kategori, ukuran, atau kondisi untuk menemukan barang streetwear yang diminati.
2. **Lihat Detail**: Klik kartu produk untuk melihat detail spesifikasi, deskripsi minus, serta galeri foto dari berbagai sudut.
3. **Masukkan ke Keranjang**: Klik tombol **Tambah ke Keranjang**.
4. **Periksa Keranjang**: Masuk ke `/user/keranjang.php`, pastikan kuantitas barang sesuai, lalu klik **Lanjut ke Checkout**.
5. **Isi Alamat & Pilih Kurir**: Pada halaman `/user/checkout.php`, pilih atau masukkan alamat pengiriman, pilih kurir (JNE / J&T / SiCepat), lalu klik **Lanjut ke Pembayaran**.
6. **Pilih Metode Bayar**: Pada halaman `/user/pembayaran.php`, pilih opsi Transfer Bank (BCA/Mandiri), E-Wallet, atau QRIS. Klik **Bayar Sekarang**.
7. **Konfirmasi Pesanan**: Halaman `/user/konfirmasi.php` akan menampilkan kode pesanan (`ORD-YYYYMMDD-XXXX`), instruksi transfer, serta tombol pintasan konfirmasi via WhatsApp ke CS toko.
8. **Pantau Status**: Pembeli dapat memantau perkembangan paket di menu **Pesanan Saya** (`/user/pesanan.php`).

### 2. Alur Administrator (Memproses Pesanan)
1. **Login Admin**: Akses `/admin/login.php` dan masuk menggunakan akun administrator.
2. **Pantau Dashboard**: Lihat ringkasan metrik omset dan daftar antrean transaksi baru pada dashboard `/admin/index.php`.
3. **Buka Pesanan**: Masuk ke menu **Pesanan** (`/admin/pesanan.php`) untuk melihat daftar seluruh transaksi pembeli.
4. **Verifikasi Pembayaran**: Buka **Detail Pesanan**, periksa kesesuaian pembayaran, lalu ubah Status Pembayaran menjadi **Terverifikasi** (status pesanan otomatis maju ke **Diproses**).
5. **Kirim Barang & Input Resi**: Setelah barang dikemas, perbarui Status Pesanan menjadi **Dikirim** dan cantumkan nomor resi kurir.
6. **Selesaikan Transaksi**: Setelah paket diterima oleh pembeli, ubah status pesanan menjadi **Selesai**. Seluruh perubahan status akan tercatat otomatis pada log audit sistem.

---

## MASALAH UMUM & SOLUSI (TROUBLESHOOTING)

### 1. Masalah: Gagal Terhubung ke Database (Connection Refused / Access Denied)
* **Penyebab**: Layanan MySQL belum menyala, port MySQL tidak sesuai, atau username/password root tidak cocok.
* **Solusi**:
  1. Pastikan status MySQL di Laravel Herd aktif (berwarna hijau).
  2. Periksa port MySQL yang digunakan Herd (umumnya port `3307`). File `shared/config.php` telah mendukung port fallback `[3307, 3306]`.
  3. Pastikan database bernama `secondspace_preloved` sudah dibuat dan berkas `toko_preloved_final.sql` sudah diimpor.
  4. Periksa file `shared/config.php` dan sesuaikan `DB_USER` dan `DB_PASS` dengan konfigurasi server Anda.

### 2. Masalah: Gambar Produk Tidak Muncul (Broken Image Icon)
* **Penyebab**: Folder upload belum memiliki izin baca (*file permission*) atau URL dasar (*Base URL*) tidak terdeteksi dengan tepat.
* **Solusi**:
  1. Pastikan berkas gambar WebP berada lengkap pada direktori `/uploads/produk/`.
  2. Buka berkas `shared/config.php` dan periksa konstanta `BASE_URL`. Jika sistem mendeteksi domain secara keliru, Anda dapat mendefinisikan URL secara manual (contoh: `define('BASE_URL', 'http://aplikasi-preloved-main.test');`).
  3. Pastikan web server memiliki akses baca terhadap folder `uploads/` dan `user/images/`.

### 3. Masalah: Notifikasi Session Expired / Form CSRF Token Mismatch
* **Penyebab**: Pengguna mendiamkan form terlalu lama sehingga sesi PHP berakhir atau browser memblokir cookie session lokal.
* **Solusi**:
  1. Refresh halaman untuk memperbarui token proteksi keamanan CSRF form.
  2. Pastikan cookie diizinkan pada peramban web untuk domain `.test` atau `localhost`.
  3. Pastikan direktori temporary session PHP pada sistem operasi memiliki hak akses tulis (*writable*).

---

## LISENSI & HAK CIPTA

Dikembangkan untuk keperluan akademik dan demonstrasi arsitektur perangkat lunak e-commerce berbasis PHP Native berkinerja tinggi tanpa dependensi JavaScript. Seluruh aset foto produk dan merek dagang merupakan hak cipta dari masing-masing pemilik aslinya.
