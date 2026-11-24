# DAFTAR PERIKSA KESIAPAN PENGUMPULAN PROYEK (SUBMISSION CHECKLIST)
## SECONDSPACE // CURATED PRELOVED STREETWEAR PLATFORM
**Standar Mutu:** 100% PHP Native, Zero JavaScript, Zero Emoji, Skema Relasional Normal, Siap Uji di Laravel Herd.

---

## 1. KELENGKAPAN DOKUMENTASI

- [x] **README.md (Root Proyek)**
  - Menggunakan Bahasa Indonesia formal, bersih, dan **100% Bebas Emoji**.
  - Terdapat nama proyek dan deskripsi komprehensif.
  - Memuat daftar lengkap fitur pengguna (user/pembeli) dan admin toko.
  - Mencantumkan rincian teknologi: PHP Native 8.x, MySQL, HTML5, CSS3, ZERO JavaScript.
  - Memuat placeholder nama anggota kelompok dan pembagian tugas yang terstruktur.
  - Memuat visualisasi struktur folder proyek.
  - Mencantumkan persyaratan sistem operasional (Laravel Herd, PHP 8.x, MySQL).
  - Panduan instalasi langkah demi langkah di Laravel Herd.
  - Akun pengujian siap pakai untuk Administrator (`admin`/`password`) dan User (`demo123`/`password`).
  - Alur penggunaan singkat untuk pembeli dan admin.
  - Troubleshooting panduan solusi masalah koneksi database dan gambar tidak muncul.

- [x] **PANDUAN_KODE.md (`/docs/PANDUAN_KODE.md`)**
  - Pemetaan komprehensif berkas PHP ke tabel database dan fungsi bersama `/shared/`.
  - Daftar komentar bertanda terstandarisasi (`[FILE]`, `[BAGIAN]`, `[QUERY]`, dll) dan panduan pencarian di VS Code.
  - Indeks lokasi parameter yang aman diubah (`[UBAH DI SINI]`) dan logika yang dilarang diubah (`[JANGAN UBAH]`).
  - Panduan langkah pengembangan (How-To): menambah kolom produk, menambah status pesanan, menambah halaman admin, mengubah tema, dan menambah kategori.

- [x] **ALUR_SISTEM.md (`/docs/ALUR_SISTEM.md`)**
  - Diagram hubungan antar tabel (ERD) berbasis teks/Mermaid.
  - Alur alur transaksi dan state machine status pesanan dan pembayaran.
  - Aturan himpunan kondisi produk tunggal terstandarisasi (Opsi A: Seperti Baru, Bagus, Ada Minus).
  - Alur mutasi audit stok barang 1-of-1 preloved.

- [x] **PROGRESS_FINAL.md (`/docs/PROGRESS_FINAL.md`)**
  - Catatan rekapitulasi audit dan kemajuan seluruh fase refaktorisasi (Fase 1 hingga Fase 8).

---

## 2. STANDAR TEKNIS & KODE SUMBER

- [x] **Zero JavaScript Compliance**:
  - Nol file ekstensi `.js`.
  - Nol tag `<script>` pada seluruh file PHP dan template aplikasi.
  - Nol inline event handler (`onclick`, `onchange`, `onsubmit`, `onload`, dll).
  - Nol pemanggilan JavaScript (`javascript:`, `fetch(`, `XMLHttpRequest`, CDN libraries).
- [x] **Zero Emoji Compliance**:
  - Nol karakter emoji Unicode pada kode program, komentar, maupun database.
  - Nol entitas emoji HTML (`&#x...;`) atau escape sequence CSS/PHP.
  - Tampilan visual bersih tanpa icon/simbol rusak.
- [x] **Keamanan & Validasi**:
  - Proteksi Cross-Site Request Forgery (CSRF) aktif pada seluruh formulir POST.
  - Seluruh query database dinamis menggunakan Prepared Statement aman (`bind_param`).
  - Direktori `/database/` dan `/docs/` terlindungi dengan konfigurasi `.htaccess` (`Require all denied`).
  - Penanganan otentikasi kata sandi menggunakan `password_hash()` (bcrypt) dan `password_verify()`.
  - Error reporting produksi (`display_errors = 0`) aktif secara default di `shared/config.php`.
- [x] **Kerapian & Komentar Bertanda**:
  - Setiap file PHP dan CSS memiliki blok identitas `[FILE]`.
  - Setiap blok fungsi modular terbungkus rapi oleh pasangan `[BAGIAN: ...]` dan `[/BAGIAN: ...]`.
  - Seluruh file PHP lolos verifikasi sintaksis `php -l` tanpa warning maupun error.

---

## 3. INTEGRITAS BASIS DATA

- [x] **Skema Basis Data Normal & Konsisten**:
  - Kolom kembar redundan telah dihapus (`order_number`, `total_price`, `status`, `condition_text`, `is_demo`).
  - Definisi foreign key formal aktif (`fk_orders_user`, `fk_order_items_order`).
  - Himpunan label kondisi seragam satu pintu (`Seperti Baru`, `Bagus`, `Ada Minus`).
  - Skrip diagnostik `/database/tools/cek_konsistensi.php` menghasilkan **0 Anomali**.
- [x] **Dump Basis Data Bersih**:
  - Berkas `/database/toko_preloved_final.sql` bersih, terstruktur, dan telah teruji dapat diimpor tanpa error.
  - Data demo tidak sah, dummy user yang tidak diperlukan, dan order eksperimen telah dibersihkan.
  - Akun contoh administrator dan pembeli aktif dengan kredensial terdokumentasi.

---

## 4. KEBERSIHAN FOLDER & PANDUAN PEMBUATAN ARSIP ZIP

- [x] **Pengecualian Berkas Sampah**:
  - Berkas `.gitignore` telah dikonfigurasi mengecualikan cache sistem (`.DS_Store`, `Thumbs.db`), folder IDE (`.vscode/`, `.idea/`), log, folder backup lokal (`database/backup/`), dan file sementara.
  - Folder `uploads/produk/` disertakan lengkap dengan foto produk WebP teroptimasi.
  - Tidak ada file backup berceceran (`.bak`, `.old`, `.zip`, `.rar`) di direktori kerja.

---

## CARA MEMBUAT BERKAS ZIP PENGUMPULAN BERSIH

Untuk mengumpulkan proyek ke dosen atau tim penguji tanpa menyertakan repositori git internal, berkas backup lokal, dan berkas sampah sementara:

### Opsi A: Menggunakan Git Archive (Metode Paling Bersih & Otomatis)
Buka terminal PowerShell pada direktori utama proyek, lalu jalankan perintah:
```powershell
git archive -o Aplikasi-Preloved-SecondSpace.zip HEAD
```
Perintah ini akan membuat satu berkas ZIP bersih yang hanya memuat berkas-berkas yang tercatat di repositori Git resmi (otomatis mengabaikan folder `.git`, isi `.gitignore`, dan berkas lokal yang tidak dilacak).

### Opsi B: Menggunakan PowerShell Script Manual
Jika ingin menyertakan berkas lokal tertentu yang dikecualikan git tetapi tetap membuang folder `.git` dan `/database/backup/`:
```powershell
Compress-Archive -Path admin, database\toko_preloved_final.sql, database\migrations, docs, images, shared, uploads, user, index.php, mysql_config.php, README.md, .gitignore -DestinationPath Aplikasi-Preloved-SecondSpace-Manual.zip -Force
```

---
*Daftar periksa ini disiapkan untuk memastikan kesiapan 100% sebelum pengujian akhir (Fase 9).*
