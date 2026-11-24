# PROGRESS_FINAL.md - Catatan Progres 9 Fase SecondSpace

Dokumen ini mencatat status pengerjaan refactoring dan optimasi sistem SecondSpace berdasarkan INSTRUKSI_FINAL.md.

---

## Ringkasan Status Proyek

- Total Fase: 9 Fase
- Fase Terakhir Selesai: FASE 8 (Dokumentasi dan Paket Pengumpulan)
- Fase Selanjutnya: FASE 9 (Pengujian Akhir - Menunggu Persetujuan)
- Aturan Kerja: Kerjakan SATU fase per giliran, lalu BERHENTI dan tunggu persetujuan pemilik sebelum melanjutkan ke fase berikutnya.
- Prinsip Utama: Tanpa JavaScript (Zero JS), tanpa emoji di seluruh sistem, pertahankan tampilan dan workflow eksisting 100%, perubahan skema database hanya dilakukan di Fase 5 setelah konfirmasi butir per butir.

---

## Log Pelaksanaan Fase

### FASE 1: ANALISIS MENYELURUH (READ-ONLY)
- Status: SELESAI (Disetujui Pemilik Proyek)
- Tanggal Eksekusi: 24 November 2026
- Lingkup Kerja:
  1. Audit peta project dan inventarisasi berkas.
  2. Audit kepatuhan stack: Terverifikasi zero JavaScript pada halaman produksi /user dan /admin.
  3. Identifikasi file/folder duplikat: `admin_lama/`, `produk_preloved_original/`, file duplikat di root vs `/user/`.
  4. Audit direktori `/uploads/` dan konsistensi gambar produk di database.
  5. Audit kemunculan emoji di kode PHP, antarmuka, dan database (171 temuan di kode, 0 di database).
  6. Audit akun demo, produk uji coba (ID 36), dan pesanan uji coba.
  7. Audit konsistensi relasi database: Integritas foreign key dan orphaned records berstatus 0 anomali.
  8. Audit skema database: Duplikasi kolom pada tabel `orders` (`order_code` vs `order_number`, `total` vs `total_price`) dan `products` (`condition` vs `condition_text`).
  9. Penyusunan 16 butir laporan komprehensif sesuai butir n INSTRUKSI_FINAL.md.
- Catatan Kritis: Tidak ada file sistem yang diubah, dihapus, atau dipindahkan. Skema dan data database tetap utuh tanpa modifikasi.

---

### FASE 2: TITIK PEMULIHAN
- Status: SELESAI
- Tanggal Eksekusi: 24 November 2026
- Lingkup Kerja & Bukti:
  1. Backup Database:
     - File backup penuh berhasil dibuat di `database/backup/backup_secondspace_preloved_2026-11-24_070141.sql` (55.27 KB) dan disalin ke `database/backup/backup_fase2_sebelum_final_cleanup.sql`.
  2. Git Baseline Commit:
     - Repository Git diinisialisasi dan seluruh codebase awal dicommit dengan pesan: `titik pemulihan sebelum final cleanup` (Commit ID: `9713bfa`).
  3. Git Tag:
     - Dibuat tag penanda pemulihan: `sebelum-final-cleanup`.
  4. Git Branch:
     - Branch kerja baru dibuat dan aktif: `final-cleanup`.

---

### FASE 3: PEMBERSIHAN FILE DAN KODE
- Status: SELESAI
- Tanggal Eksekusi: 24 November 2026
- Lingkup Kerja & Bukti:
  1. Penghapusan Folder Duplikat:
     - `admin_lama/` (10 berkas) dihapus permanen melalui `git rm`.
     - `produk_preloved_original/` (~198 MB, 72 berkas foto mentah kamera) dihapus bersih dari sistem berkas.
  2. Penghapusan Berkas Duplikat di Root Project:
     - 16 berkas duplikat (`alamat.php`, `auth.php`, `checkout.php`, `contact.php`, `db.php`, `detail.php`, `detail_pesanan.php`, `keranjang.php`, `konfirmasi.php`, `login.php`, `logout.php`, `modern-theme.css`, `pembayaran.php`, `pesanan.php`, `produk.php`, `register.php`) dihapus melalui `git rm`.
     - Berkas router `index.php` di root dipertahankan sebagai redirector bersih ke `user/index.php`.
  3. Pembersihan Alat Bantu Sementara di `database/tools/`:
     - 13 berkas skrip Python, 12 berkas folder `crops/`, berkas JSON sementara, dan skrip debug dihapus melalui `git rm`.
     - Skrip operasional esensial `database/tools/backup_db.php` dan `database/tools/cek_konsistensi.php` dipertahankan dan disinkronkan ke `shared/koneksi.php`.
  4. Pembersihan Berkas Yatim di `uploads/produk/` & `user/images/`:
     - 11 berkas sisa uji coba (`hero-preloved.jpg`, `prod_1795488404_8e585fb0ddeb.webp`, `prod_1795488689_00a531fd26c5.webp`, `prod_1795492593_82f85f69969d.webp`, `prod_1795492593_c6bd658692d0.webp`, `prod_1795492704_7b7233a458b1.webp`, `qris_pembayaran.webp`) dihapus melalui `git rm`.
  5. Audit Kode Mati & Debug:
     - Terverifikasi 0 `var_dump`, 0 `print_r`, 0 sisa tag `<script>` / inline event JS, dan 0 penanda TODO/FIXME di seluruh folder produksi (`admin/`, `user/`, `shared/`).
  6. Verifikasi Sintaks PHP (`php -l`):
     - Seluruh berkas PHP proyek lolos kompilasi 100% tanpa error syntax (`0 syntax errors detected`).
  7. Audit Konsistensi Integritas:
     - Skrip `cek_konsistensi.php` dijalankan ulang dengan hasil 0 anomali sistem (100% konsisten).

---

### FASE 4: PENGHAPUSAN EMOJI DAN PEMBERSIHAN DATA DEMO
- Status: SELESAI
- Tanggal Eksekusi: 24 November 2026
- Lingkup Kerja & Bukti:
  1. Pembersihan Emoji di Kode dan Antarmuka (Bagian A):
     - Seluruh 171 kemunculan simbol emoji pada 22 berkas antarmuka `/admin/` dan `/user/` berhasil dieliminasi dan digantikan dengan teks deskriptif baku atau tipografi bersih.
     - Pindaian ulang menghasilkan 0 berkas dengan emoji (100% Bebas Emoji).
  2. Backup Basis Data Fase 4:
     - Berkas cadangan database dibuat di `database/backup/backup_fase4_sebelum_pembersihan_emoji_demo.sql` (55.27 KB).
  3. Pembersihan Data Demo di Database (Bagian B):
     - Dijalankan melalui skrip transaksi aman:
       * Produk uji coba ID 36 (`test`) beserta 2 foto produk dieliminasi dari database dan media penyimpanan.
       * Pesanan uji coba otomatis ID 8 dan ID 9 dieliminasi, dengan pemulihan kuantitas stok produk ID 9 (`Athletic Performance Tee`) dan ID 10 (`Blue Striped Short Shirt`) kembali ke stok 1.
       * Akun pengguna dummy ID 3, 4, 5 (Budi Tester) dan ID 6 (Arwah Buyer) serta pesanan yatim terkait dieliminasi.
       * Akun resmi yang dipertahankan: ID 1 (`admin`), ID 2 (`ikhsan123`), dan ID 7 (`demo123`).
  4. Ekspor Basis Data Bersih:
     - Database bersih berhasil diekspor ulang ke `database/toko_preloved_final.sql` (49.99 KB).
     - Pengujian impor dari nol ke database sementara `test_clean_fase4` sukses 100% tanpa error, lalu database uji dihapus.
  5. Audit Konsistensi:
     - Skrip `cek_konsistensi.php` melaporkan 0 anomali sistem (100% konsisten).
  6. Pembersihan Skrip Sementara:
     - Seluruh skrip bantu usang di `database/tools/` dibersihkan melalui `git rm`, menyisakan alat bantu aman esensial (`backup_db.php`, `cek_konsistensi.php`, `export_final.php`).

---

### FASE 5: PENYEDERHANAAN STRUKTUR DATABASE
- Status: SELESAI
- Tanggal Eksekusi: 24 November 2026
- Keputusan Pemilik Proyek (Tahap B):
  1. Item 1 sampai 8 pada Tabel Keputusan: Disetujui 100% tanpa pengecualian.
  2. Himpunan Label Kondisi: Opsi A dipilih ("Seperti Baru", "Bagus", "Ada Minus") sebagai satu-satunya himpunan baku.
- Pelaksanaan Tahap C (Ubah Kode Lebih Dulu):
  1. Backup Basis Data:
     - Berkas cadangan dibuat di `database/backup/backup_fase5_tahap_c_sebelum_ubah_kode.sql` (49.61 KB).
  2. Branch Terpisah:
     - Branch `fase5-code-prep` dibuat dan aktif.
  3. Konfigurasi Baku:
     - Didefinisikan konstanta `DAFTAR_KONDISI_PRODUK = ['Seperti Baru', 'Bagus', 'Ada Minus']` di `shared/config.php`.
  4. Penyesuaian Kode PHP:
     - `user/produk.php`: Filter kondisi disinkronkan menggunakan `DAFTAR_KONDISI_PRODUK` (Opsi A). Filter kini menemukan produk dengan akurat (Seperti Baru: 5, Bagus: 18, Ada Minus: 1).
     - `admin/detail_pesanan.php`: Berhenti membaca kolom `order_number`, `total_price`, dan `status`; murni memakai `order_code`, `total`, dan `order_status`.
     - `admin/pesanan.php`: Query filter dan render baris berhenti membaca `order_number`, `total_price`, dan `status`.
     - `user/detail_pesanan.php` & `user/pesanan.php`: Tampilan dan query pesanan murni menggunakan `order_code` dan `order_status`.
     - `admin/index.php`, `admin/produk.php`, `admin/riwayat.php`, `shared/fungsi.php`: Dihapus klausul filter sementara `is_demo = 0`.
     - `admin/produk_form.php` & `user/konfirmasi.php`: Query INSERT/UPDATE murni menggunakan kolom baku (`condition`, `order_code`, `total`, `order_status`, `payment_status`).
     - Hasil pencarian di seluruh project: 0 referensi tersisa ke kolom yang dihapus/digabung.
- Pelaksanaan Tahap D (Migrasi Database Utama Sesuai Syarat & Urutan Baru):
  1. Uji Coba Salinan Sementara:
     - Telah diuji pada salinan database `secondspace_salinan_uji`: migrasi 012 sukses 100%, seluruh alur (tambah produk, filter kondisi Opsi A, checkout, upload bukti, ubah status bayar dan pesanan, dashboard KPI, detail pesanan, kontak masuk) LULUS 100%. Salinan uji dihapus bersih.
  2. Backup Basis Data Utama Tepat Sebelum Migrasi:
     - Dibuat di `database/backup/backup_fase5_tepat_sebelum_migrasi_utama.sql` (49.61 KB).
     - Telah diverifikasi dengan mengimpor ke database kosong sementara `test_backup_verify` tanpa error (155 kueri berhasil), lalu database sementara dihapus.
  3. Eksekusi Migrasi 012 pada Database Utama `secondspace_preloved`:
     - Dijalankan 10 langkah DDL tanpa error:
       * Kolom `order_number`, `total_price`, dan `status` terhapus dari `orders`.
       * Kolom `condition_text` dan `is_demo` terhapus dari `products`.
       * Kolom `condition` bertipe ENUM baku (`'Seperti Baru'`, `'Bagus'`, `'Ada Minus'`).
       * Kolom `order_status` dan `payment_status` bertipe ENUM baku terstandarisasi.
       * Kolom `is_read` (TINYINT 1) ditambahkan ke tabel `contacts`.
       * Foreign key formal `fk_orders_user` dan `fk_order_items_order` aktif sempurna.
  4. Audit Konsistensi Pasca-Migrasi:
     - `database/tools/cek_konsistensi.php` dijalankan: 0 anomali sistem (Target 0 anomali tercapai).
  5. Pengujian Alur Utama di Database Utama & Verifikasi Dashboard:
     - Angka dashboard KPI (Produk: 24, Pesanan: 5, Menunggu Verif: 0, Selesai: 4) terbukti identik 100% dengan kueri independen.
     - Alur tambah produk, checkout, bukti bayar, dan transisi status diuji dengan data minimal dan langsung dibersihkan kembali ke kondisi semula.
  6. Ekspor Basis Data Bersih:
     - `database/toko_preloved_final.sql` diekspor ulang (49.15 KB).
     - Telah diverifikasi diimpor ke database kosong sementara `test_reexport_verify` tanpa error (165 kueri berhasil), lalu database sementara dihapus.
  7. Pemutakhiran Dokumentasi:
     - `docs/ALUR_SISTEM.md` dibuat (memuat ERD Mermaid 9 tabel, aturan kondisi Opsi A, dan state machine status).
     - `docs/PANDUAN_KODE.md` dimutakhirkan pada peta tabel database.
     - `docs/PROGRESS_FINAL.md` dimutakhirkan.

---

### FASE 6: PEMERIKSAAN ALUR (WORKFLOW) ADMIN DAN VALIDITAS DATA
- Status: SELESAI
- Tanggal Eksekusi: 24 November 2026
- Lingkup Kerja & Bukti:
  1. Verifikasi Angka Secara Independen:
     - Seluruh angka metrik dashboard admin diuji menggunakan kueri SQL terpisah independen:
       * Total Produk Aktif: UI = 24 | Kueri = 24 [COCOK]
       * Total Seluruh Pesanan: UI = 5 | Kueri = 5 [COCOK]
       * Menunggu Verifikasi: UI = 0 | Kueri = 0 [COCOK]
       * Pesanan Selesai: UI = 4 | Kueri = 4 [COCOK]
       * Pesan Kontak: Total = 0 | Belum Dibaca = 0 [COCOK]
       * Omzet Penjualan Sah: UI = Rp 562.500 | Kueri = Rp 562.500 [COCOK]
       * Status Pesanan: Menunggu Bayar (0), Diproses (0), Dikirim (0), Selesai (4), Dibatalkan (1). Total = 5 [COCOK]
     - Perhitungan Nilai Pesanan: Subtotal + Ongkir - Diskon Kupon = Total terverifikasi 100% konsisten pada seluruh pesanan historis.
  2. Kesamaan Admin dan User pada Sampel Data:
     - Sampel produk (stok = 1, ID 9): Nama, harga (Rp 75.000), stok (1), kondisi (Seperti Baru), merek, kategori, ukuran (L) terbukti 100% identik di panel admin dan user.
     - Sampel pesanan selesai (ID 11): Order code, nama pembeli, total (Rp 130.000), nomor resi (`124154134124132`), format tanggal (`d M Y, H:i WIB`), dan zona waktu `Asia/Jakarta` terbukti 100% identik di admin dan user.
     - Sampel pesanan dibatalkan (ID 6): Status pesanan (`Dibatalkan`) dan status bayar (`Dibatalkan`) terbukti identik.
  3. Konsistensi Istilah Sistem:
     - Himpunan Kondisi Produk: Tersinkronisasi penuh dengan Opsi A (`Seperti Baru`, `Bagus`, `Ada Minus`) di database, `shared/config.php`, filter katalog user, kartu produk, dan form admin.
     - Status Pesanan: Terpusat pada `DAFTAR_STATUS_ORDER` (`Menunggu Pembayaran`, `Diproses`, `Dikirim`, `Selesai`, `Dibatalkan`).
     - Status Pembayaran: Terpusat pada `DAFTAR_STATUS_PAYMENT` (`Belum Bayar`, `Menunggu Pembayaran`, `Menunggu Konfirmasi`, `Menunggu Verifikasi`, `Dibayar`, `Terverifikasi`, `Ditolak`, `Selesai`, `Dibatalkan`).
  4. Pengujian Alur Admin Lengkap & Sinkronisasi Dua Arah:
     - Otentikasi & Guard Admin: Login kredensial valid lolos, password salah ditolak dengan pesan error yang aman.
     - CRUD Produk & Mutasi Stok: Penambahan produk baru, mutasi stok, pencatatan otomatis di `riwayat_stok`, dan penghapusan produk aman lolos 100%.
     - Transaksi Sinkronisasi Dua Arah: User checkout -> stok barang berkurang otomatis -> user upload bukti -> admin tolak bukti bayar -> user upload ulang bukti valid -> admin verifikasi pembayaran (pesanan otomatis maju ke `Diproses`) -> admin input resi & ubah ke `Dikirim` -> pesanan `Selesai` lolos 100%.
     - Pengembalian Stok saat Batal: Saat pesanan dibatalkan, stok barang preloved unik kembali tepat satu kali dari 0 menjadi 1, dan tercatat di `riwayat_stok`.
     - Integritas Snapshot Harga: Perubahan harga katalog produk di kemudian hari tidak mengubah harga historis pesanan yang telah terjadi pada `order_items`.
     - Alur Pesan Kontak: Pesan masuk tercatat -> admin membaca (`is_read = 1`) -> pesan dihapus lolos 100%.
  5. Pengujian Kasus Tepi (Edge Cases):
     - Loncat status ilegal (langsung `Dikirim` sebelum pembayaran terverifikasi) ditolak oleh state machine.
     - Mengubah status pesanan yang sudah `Selesai` ditolak oleh sistem.
     - Mengubah status ke `Dikirim` tanpa mengisi nomor resi ditolak oleh sistem.
     - Akses pesanan dengan ID yang tidak ada ditangani secara aman.
  6. Temuan dan Catatan Khusus untuk Pemilik Proyek:
     - Pada orientasi foto produk, ditemukan bahwa beberapa foto produk di `uploads/produk/` (antara lain celana kargo ID 31, jeans ID 32, windbreaker ID 30, chino ID 24, kemeja ID 20, dll) memiliki penamaan file `-depan.webp` yang secara visual menampilkan sisi belakang pakaian, dan file `-belakang.webp` menampilkan sisi depan pakaian. Temuan ini dicatat dan siap ditata posisinya dengan persetujuan pemilik proyek.

---

### FASE 7: PERAPIAN KODE, KEAMANAN FINAL, DAN KOMENTAR BERTANDA
- Status: SELESAI
- Tanggal Eksekusi: 24 November 2026
- Lingkup Kerja & Bukti:
  1. Penguatan Keamanan Form POST & Proteksi CSRF Menyeluruh:
     - Fungsi `csrf_field()` dan `csrf_verify()` diterapkan pada seluruh formulir POST di modul pengguna (`user/login.php`, `user/register.php`, `user/contact.php`, `user/alamat.php`, `user/detail.php`, `user/keranjang.php`, `user/checkout.php`, `user/pembayaran.php`, `user/konfirmasi.php`) melengkapi proteksi CSRF di panel admin yang sudah aktif sebelumnya.
  2. Prepared Statements & Sanitasi Kueri:
     - Kueri dinamis di `user/index.php` telah dimigrasikan penuh ke prepared statement ber-parameter tipe integer.
     - Seluruh kueri input pengguna di modul admin dan user terbukti 100% menggunakan prepared statement aman dari injeksi SQL.
  3. Pengaturan Error Reporting & Produksi:
     - Ditambahkan konfigurasi `display_errors = '0'`, `display_startup_errors = '0'` pada `shared/config.php` untuk memastikan tidak ada pesan error PHP mentah atau kebocoran path internal sistem yang tampil ke pengguna saat pengumpulan.
     - Pindaian menyeluruh mengonfirmasi ketiadaan fungsi debug berbahaya seperti `phpinfo()` (0 temuan).
  4. Template Konfigurasi & Kredensial:
     - Dibuat berkas template resmi `shared/config.example.php` dengan nilai default lokal Laravel Herd/XAMPP yang bersih dan aman untuk panduan instalasi dosen/evaluator.
     - Audit riwayat git mengonfirmasi tidak ada password pribadi atau rahasia yang ter-commit. Seluruh password di basis data telah terenkripsi menggunakan `password_hash()` (bcrypt).
  5. Pembatasan Akses File Sensitif:
     - Dibuat berkas `.htaccess` di direktori `database/` dan `docs/` (`Require all denied`) untuk mencegah pembacaan langsung berkas SQL dump, berkas cadangan, atau berkas dokumentasi internal melalui browser web.
     - Direktori `uploads/` terproteksi dengan aturan pemblokiran eksekusi skrip PHP (`Options -ExecCGI` & penolakan ekstensi file skrip).
  6. Standardisasi Komentar Bertanda:
     - Seluruh berkas PHP dan CSS kini telah dilengkapi dengan komentar bertanda baku:
       * Header `[FILE]` pada setiap berkas.
       * Blok modular `[BAGIAN: nama]` ... `[/BAGIAN: nama]`.
       * Parameter aman diubah bertanda `[UBAH DI SINI]`.
       * Titik kait fitur baru bertanda `[TAMBAH DI SINI]`.
       * Logika bisnis kritis bertanda `[JANGAN UBAH]` (stok atomik, snapshot harga, CSRF, guard auth, state machine status).
  7. Verifikasi Sintaks:
     - Pemeriksaan linting `php -l` pada seluruh berkas PHP: 0 kesalahan sintaks (*No syntax errors detected*).
     - Skrip `database/tools/cek_konsistensi.php` dijalankan: 0 anomali sistem (100% konsisten).

---

### FASE 8: DOKUMENTASI DAN PAKET PENGUMPULAN
- Status: SELESAI
- Tanggal Eksekusi: 24 November 2026
- Lingkup Kerja & Bukti:
  1. Penulisan `README.md` di Root Proyek:
     - Berbahasa Indonesia formal, terstruktur rapi, dan 100% Bebas Emoji.
     - Memuat nama proyek & deskripsi, daftar lengkap fitur user & admin, rincian teknologi (HTML, CSS, PHP Native, MySQL, Zero JS), placeholder anggota kelompok & pembagian tugas, diagram pohon struktur folder, persyaratan lingkungan, langkah instalasi lengkap di Laravel Herd, kredensial akun pengujian (admin & user), alur kerja ringkas pembeli & admin, serta panduan troubleshooting masalah koneksi database dan gambar.
  2. Pembaruan Komprehensif `docs/PANDUAN_KODE.md`:
     - Peta lengkap berkas (Halaman -> File PHP -> Tabel Database -> Fungsi Bersama).
     - Panduan sistem komentar bertanda terstandarisasi (`[FILE]`, `[BAGIAN]`, `[UBAH DI SINI]`, `[TAMBAH DI SINI]`, `[JANGAN UBAH]`, `[QUERY]`, dll) dan cara pencarian cepat di VS Code.
     - Indeks lokasi parameter `[UBAH DI SINI]` dan batasan `[JANGAN UBAH]`.
     - Panduan pengembangan fitur umum (Developer How-To): menambah kolom produk (berat), menambah status pesanan baru, menambah halaman baru di panel admin, mengubah tema dan variabel warna CSS, serta menambah kategori baru.
  3. Validasi & Verifikasi `docs/ALUR_SISTEM.md`:
     - Memuat teks ERD lengkap (Mermaid diagram relasi 9 tabel).
     - State machine alur transaksi dan status pesanan/pembayaran.
     - Definisi himpunan label kondisi produk tunggal (Opsi A: Seperti Baru, Bagus, Ada Minus).
     - Alur audit mutasi stok barang preloved 1-of-1.
  4. Konfigurasi Aturan `.gitignore` & Pengecualian Sampah:
     - Mengabaikan direktori `/database/backup/`, file kredensial lokal pribadi (`mysql_config.php`), log, cache, berkas sementara (`.DS_Store`, `Thumbs.db`), folder IDE (`.vscode/`, `.idea/`), dan dump arsip sementara.
     - Mempertahankan folder `/uploads/produk/` dengan foto produk WebP teroptimasi, serta mengecualikan bukti bayar uji coba (`/uploads/bukti/`).
  5. Penyusunan `docs/CHECKLIST_PENGUMPULAN.md`:
     - Daftar periksa kesiapan pengumpulan proyek mencakup aspek dokumentasi, arsitektur teknis tanpa JS, ketiadaan emoji, proteksi keamanan, integritas skema database, dan panduan membuat arsip ZIP bersih via `git archive` maupun PowerShell manual.
  6. Verifikasi Sistem:
     - Pemeriksaan lint `php -l` seluruh berkas: 0 error.
     - Audit konsistensi database `database/tools/cek_konsistensi.php`: 0 anomali sistem (100% konsisten).

---

### FASE 9: PENGUJIAN AKHIR
- Status: PENDING (Menunggu Persetujuan Pemilik Proyek)
- Rencana Kerja:
  1. Pengujian menyeluruh semua halaman user dan admin di Laravel Herd (`http://namaproject.test`) tanpa link rusak, gambar hilang, warning, atau error PHP.
  2. Pindai kepatuhan Zero JavaScript, Zero Emoji, Zero Sampah, dan Zero Demo yang tidak sah.
  3. Verifikasi impor skema `toko_preloved_final.sql` pada basis data kosong sementara.
  4. Pengulangan verifikasi alur transaksi dan keamanan (SQL injection, XSS, upload filter, pembatasan admin).
  5. Penyusunan `docs/LAPORAN_FINAL.md`.
