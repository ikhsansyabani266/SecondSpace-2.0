# INSTRUKSI FINAL PENGUMPULAN - SecondSpace (Toko Preloved)

Dokumen ini adalah instruksi kerja untuk AI agent (Antigravity) di VS Code. Letakkan file ini di root project, lalu minta agent membacanya sampai habis sebelum mulai. Kata "aku" dan "-ku" di dokumen ini merujuk pada pemilik project (pengguna), bukan agent.

## Aturan kerja agent (baca dulu)

1. Baca SELURUH dokumen ini sebelum melakukan apa pun.
2. Kerjakan SATU fase per giliran. Selesai satu fase, tulis ringkasan, lalu BERHENTI dan tunggu persetujuanku. Jangan lanjut ke fase berikutnya tanpa persetujuan.
3. Catat progres di `/docs/PROGRESS_FINAL.md`: fase yang selesai, keputusanku, daftar yang disetujui dan dikecualikan, serta hal yang meragukan. Perbarui setiap akhir fase.
4. Jika sesi baru dimulai (konteks hilang atau terlalu panjang), baca dokumen ini dan `/docs/PROGRESS_FINAL.md` terlebih dahulu, lalu lanjutkan dari fase yang belum selesai.
5. Dokumen ini BUKAN kandidat hapus. Di Fase 8 tanyakan padaku apakah dokumen ini dipindahkan atau dikeluarkan dari paket pengumpulan.
6. TAMPILAN TIDAK DIUBAH: jangan mengubah tampilan website di ukuran layar mana pun, baik desktop maupun mobile. Tugas ini TIDAK mencakup pekerjaan responsif atau penyesuaian mobile: jangan menambah media query, file CSS responsif, atau elemen pembungkus HTML demi layout, dan jangan menambah atau mengubah meta viewport. Jika menemukan masalah tampilan di layar tertentu, cukup catat di laporan sebagai informasi, jangan diperbaiki. Satu-satunya perubahan tampilan yang diizinkan adalah penghapusan emoji yang kusetujui (Fase 4).
7. Jangan menulis emoji di kode, komentar, dokumentasi, maupun laporan.

## Gambaran tugas

Website toko preloved "SecondSpace" (HTML, CSS, PHP native, MySQL via phpMyAdmin, server lokal Laravel Herd) untuk tugas kelompok ini SUDAH SELESAI: halaman user dan admin berfungsi. Tugasmu adalah PERSIAPAN PENGUMPULAN FINAL yang mencakup:

1. menganalisis seluruh project,
2. merapikan file dan kode,
3. menghapus folder, file, kode, dan data yang tidak terpakai atau bersifat demo/uji coba,
4. MENGHAPUS SEMUA EMOJI dari seluruh website (kode, tampilan, dan data di database),
5. MENYEDERHANAKAN STRUKTUR DATABASE: menggabungkan kolom atau tabel yang kembar, menghapus kolom dan tabel yang tidak terpakai, dan memindahkan data yang tidak perlu disimpan di database ke konfigurasi atau session, tanpa mengubah tampilan dan alur kerja,
6. MEMERIKSA SELURUH ALUR (workflow) HALAMAN ADMIN dan memastikan semua data yang ditampilkan VALID dan KONSISTEN dengan database dan dengan halaman user,
7. memastikan tidak ada bug, link rusak, atau kebocoran data sensitif,
8. menyiapkan dokumentasi dan paket yang siap dikumpulkan dan mudah dijalankan oleh dosen.

## Stack (sudah pasti, jangan diubah)

- HTML, CSS, PHP native (bukan framework). TANPA JavaScript sama sekali.
- MySQL via phpMyAdmin. Server lokal Laravel Herd (domain `.test`).
- Jangan menambah Composer package, npm package, atau framework baru.

## Prinsip utama

1. JANGAN mengubah layout, tampilan (desktop maupun mobile), gaya visual, atau workflow (alur kerja) website. Tugas ini hanya merapikan, membersihkan, menghapus emoji, memperbaiki bug yang ditemukan, dan mendokumentasikan. Jika menemukan bug, perbaiki seminimal mungkin dan laporkan. Jika sebuah perbaikan akan mengubah alur kerja, JANGAN lakukan; laporkan dan tunggu keputusanku.
2. Jika ragu apakah sesuatu masih dipakai atau apakah suatu perubahan mengubah tampilan atau alur, JANGAN lakukan. Masukkan ke daftar "meragukan" dan tanyakan padaku.
3. Semua penghapusan HARUS: (a) dibuktikan tidak terpakai dengan mencari referensinya di seluruh project, (b) tercantum di daftar yang kusetujui, (c) dilakukan lewat `git rm` agar bisa dipulihkan. Untuk penghapusan atau perubahan baris data di database: hanya setelah backup, dalam transaksi, dengan dry-run lebih dulu, dan setelah persetujuan eksplisit. Dilarang `DROP TABLE`, `DROP COLUMN`, dan `TRUNCATE` kecuali kusetujui satu per satu; perubahan struktur database (termasuk menghapus kolom atau tabel) HANYA dilakukan di Fase 5.
4. Sebelum mengubah apa pun, buat titik pemulihan: (a) backup database lengkap ke `/database/backup/` (bertanggal), (b) commit semua perubahan yang ada, lalu buat tag git `sebelum-final-cleanup` dan branch baru `final-cleanup`. Tunjukkan padaku bukti keduanya.
5. Ini kerja kelompok: jangan menghapus atau menulis ulang pekerjaan orang lain tanpa menunjukkan buktinya di laporan. Tulis daftar file yang diubah agar bisa dibagikan ke anggota kelompok.
6. Commit git di akhir tiap fase dengan pesan yang jelas.
7. Pengujian yang menulis data (checkout, ubah status, dan sebagainya) dilakukan pada SALINAN database sementara, bukan database utama. Jika konfigurasi perlu diarahkan ke salinan itu, gunakan berkas konfigurasi lokal sementara yang tidak di-commit dan kembalikan setelah selesai. Hapus salinan sementara setelah selesai.

## Perlindungan (jangan pernah dihapus tanpa persetujuan eksplisit satu per satu)

- Foto produk final yang dipakai database dan halaman user di `/uploads/produk`.
- File di `/uploads` yang direferensikan oleh data di database (foto produk, bukti bayar milik pesanan yang dipertahankan).
- `/database/toko_preloved_final.sql` dan file migration yang dibutuhkan untuk membangun database dari nol.
- `.git`, file konfigurasi yang dibutuhkan agar website berjalan, dan akun admin.
- Data produk final (produk berfoto yang tampil di toko). Produk ini BUKAN demo; jangan dihapus walaupun fotonya hasil ilustrasi.
- Simbol tipografi yang BUKAN emoji, misalnya tanda merek terdaftar pada logo "SECONDSPACE", tanda hak cipta, tanda kali, panah teks biasa, titik tengah, simbol mata uang, dan huruf beraksen.
- Dokumen ini (`INSTRUKSI_FINAL.md`) dan `/docs/PROGRESS_FINAL.md`.

## Aturan tanpa JavaScript

- Dilarang: tag `<script>`, file `.js`, atribut event (`onclick`, `onchange`, `onsubmit`, `onload`, dan sejenisnya), `href="javascript:..."`, library JavaScript, `fetch`/AJAX/`XMLHttpRequest`. Berlaku untuk semua kode baru dan yang disentuh.
- Pengganti yang boleh dipakai untuk kebutuhan tampilan: media query, CSS Grid/Flexbox, `clamp()`, `min()`, `max()`, `aspect-ratio`, `overflow-x:auto`, `:target`, elemen `<details>`/`<summary>`, checkbox hack seperlunya.

## Catatan badge "Herd"

Kotak kecil "Herd" di pojok layar pada screenshot adalah alat bantu Laravel Herd, BUKAN bagian website. Abaikan.

---

## FASE 1 - ANALISIS

Hanya membaca. Boleh `SELECT`, `DESCRIBE`, `SHOW`. Dilarang mengedit, memindahkan, menghapus, atau `INSERT`/`UPDATE`/`DELETE`/`ALTER`.

### a. Peta project

Struktur folder lengkap (tree), jumlah dan ukuran file per folder, daftar file yang ukurannya besar, dan peta halaman: URL -> file -> tabel yang dipakai -> fungsi bersama yang dipanggil. SEMUA halaman user dan admin harus terpetakan, termasuk halaman error, halaman konfirmasi hapus, halaman sukses, dan halaman yang jarang terlihat.

### b. Inventaris bahasa dan kepatuhan stack

Daftar file yang bukan HTML, CSS, atau PHP. Cari seluruh sisa JavaScript: tag `<script>`, file `.js`, atribut `onclick`/`onchange`/`onsubmit`/`onload`, `javascript:`, `fetch(`, `XMLHttpRequest`, dan library JS dari CDN. Tandai juga file framework yang tak terpakai (`node_modules`, `vendor`, `package.json`, `composer.json`, folder Laravel sisa, dan sejenisnya).

### c. Kandidat hapus

Untuk setiap kandidat tulis path, jenis, alasan, dan BUKTI (hasil pencarian referensi: `require`/`include`, `href`, `src`, `action`, `header Location`, nama fungsi, class dan ID CSS, nama gambar, path di database). Cari:

- halaman yatim (tidak ditautkan dari mana pun), file tes, file backup/salinan (backup, copy, old, baru, test, tmp, final2, dan sejenisnya), file `.bak`/`.old`/`.zip`/`.rar` di dalam project
- folder kosong, folder sampah, `.DS_Store`, `Thumbs.db`, `desktop.ini`, log, cache
- fungsi PHP yang tidak pernah dipanggil, include yang tidak dipakai, variabel mati
- kode yang dikomentari, `var_dump`, `print_r`, `echo`/`die` debug, `error_reporting` yang diaktifkan untuk debug, komentar TODO/FIXME yang sudah selesai
- aturan CSS, class, dan ID yang tidak dipakai; file CSS yang tidak dimuat halaman mana pun
- gambar, ikon, font, dan aset yang tidak direferensikan di kode maupun database
- skrip alat sementara di `/database/tools` (skrip konversi sekali pakai, skrip hapus data demo, skrip perbaikan) yang tugasnya sudah selesai dan berpotensi berbahaya jika dijalankan lagi. Tandai mana yang layak disimpan sebagai alat bantu yang aman (misalnya `cek_konsistensi.php`) dan mana yang dihapus
- file migration yang sudah digabung ke `toko_preloved_final.sql` atau yang duplikat (laporkan saja; jangan hapus yang dibutuhkan untuk membangun database dari nol)

### d. Kandidat file yatim di /uploads

Bandingkan isi `/uploads` dengan path yang tersimpan di database. Laporkan file di `/uploads` yang tidak direferensikan database (kandidat hapus) dan path di database yang filenya tidak ada (data rusak).

### e. Audit emoji (hanya membaca)

Cari SEMUA emoji di seluruh project dan di seluruh data database.

- Definisi emoji: karakter Unicode berproperti `Emoji_Presentation` atau `Extended_Pictographic`, termasuk penanda variasi U+FE0F, perekat ZWJ U+200D dalam sekuens, keycap U+20E3, penunjuk wilayah (bendera), pengubah warna kulit, dan simbol dingbat bergaya emoji (misalnya tanda centang, silang, bintang, peringatan, hati, panah segitiga). Jika sebuah simbol meragukan, tulis di daftar "meragukan", jangan putuskan sendiri.
- Tempat mencari di KODE dan FILE: semua file PHP, HTML, CSS (termasuk properti `content:` dengan escape seperti `"\1F600"`), SVG, SQL (termasuk `toko_preloved_final.sql` dan migration), Markdown, README, docs, teks favicon, atribut `title`/`alt`/`placeholder`/`value`, pesan flash dan pesan error, serta entitas HTML/numerik (`&#x1F600;`, `&#128512;`) dan escape PHP (`"\u{1F600}"`). Gunakan skrip pencarian yang menangani Unicode dengan benar (misalnya Python dengan modul `regex`, atau `grep -P`), bukan hanya mencari beberapa karakter yang kamu ingat.
- Tempat mencari di DATABASE: tulis skrip PHP CLI baca-saja yang memindai SEMUA kolom bertipe teks di SEMUA tabel (nama produk, deskripsi, merek, kategori, pesan, catatan, alasan penolakan, nama pengguna, alamat, dan seterusnya) dengan ekspresi reguler Unicode, lalu laporkan tabel, kolom, id baris, dan potongan teksnya.
- Pisahkan hasilnya: (i) emoji milik tampilan atau kode (kandidat hapus), (ii) emoji di data produk final, (iii) emoji di teks kiriman pengguna (pesan, alamat, dan sebagainya; ini data milik pengguna, laporkan dan tanyakan padaku, jangan diubah diam-diam), (iv) emoji yang berfungsi sebagai ikon (misalnya keranjang, centang, tutup) beserta usulan penggantinya yang bukan emoji (teks biasa, simbol tipografi, bentuk CSS, atau SVG inline) agar tampilan tetap berfungsi.

### f. Audit demo dan data uji coba (hanya SELECT)

Laporkan jumlah serta contoh isinya:

- baris yang punya penanda demo (kode berawalan `DEMO-`, kolom penanda demo, nilai "demo", "test", "dummy", "contoh", "sementara")
- teks, badge, atau kalimat demo di kode dan data: "demo", "dummy", "lorem ipsum", "sementara", "foto ilustrasi", "coming soon", "test", "asdf", nama produk uji, dan sejenisnya
- pengguna, pesanan, detail pesanan, bukti bayar, dan pesan hasil uji coba (akun test, pesanan dengan nama pembeli "test", pesan "halo", pesan kosong)
- akun admin dan user: daftar akun yang ada, mana yang sebaiknya dipertahankan (satu akun admin untuk dosen, satu akun user contoh) dan mana yang kandidat hapus
- produk berstatus nonaktif atau tanpa foto, kategori kosong, dan duplikat
- tabel database yang tidak dipakai kode mana pun (laporkan saja)

### g. Audit kualitas data

Jalankan semua pengecekan konsistensi (jika ada `/database/tools/cek_konsistensi.php`, jalankan; jika tidak, tulis query `SELECT`-nya): pesanan tanpa item, item tanpa pesanan, total tidak cocok dengan item, stok negatif, status tidak baku, path gambar rusak, harga nol/negatif, duplikat, kategori tidak valid. Laporkan jumlah per kategori.

### h. Audit alur (workflow) admin dan validitas tampilan data

Hanya membaca; ini analisis awal, pengujian penuh ada di Fase 6.

- Telusuri SETIAP halaman admin: URL, form yang ada, target action, metode, query yang dijalankan, redirect setelah aksi, pesan flash, dan hak akses. Gambarkan alur lengkap: login -> dashboard -> produk -> kategori -> stok -> pesanan -> detail pesanan -> bukti bayar -> status pembayaran -> status pesanan -> pesan masuk -> logout.
- Untuk SETIAP angka, kolom, label, dan status yang ditampilkan di admin, catat dari mana asalnya (tabel, kolom, fungsi, query) dan apakah dihitung ulang atau diambil dari kolom tersimpan.
- Bandingkan dengan halaman user: nama produk, harga, stok, kondisi, ukuran, merek, kategori, status pesanan, status pembayaran, total, dan format (Rupiah, tanggal, zona waktu) harus identik di admin dan user.
- Cari ketidakcocokan istilah dan nilai. CONTOH YANG SUDAH TERLIHAT di halaman produk user: filter Kondisi memakai "Sangat Baik / Baik / Cukup" sedangkan kartu produk menampilkan "Bagus". Periksa apakah filter benar-benar menemukan produk dengan nilai database, dan apakah admin memakai istilah yang sama. Laporkan semua kasus serupa pada kondisi, ukuran, status, dan kategori. Jangan mengubahnya di fase ini.
- Cari risiko alur: tombol atau link yang mengarah ke halaman yang salah atau mati, form yang gagal tanpa pesan, halaman yang error saat data kosong atau saat id tidak ada, pagination dan filter yang saling bertabrakan, perpindahan status yang seharusnya tidak sah tetapi lolos, kirim ganda form, halaman admin yang bisa dibuka tanpa login, dan tombol back setelah logout.

### i. Audit keamanan (hanya membaca)

- semua query memakai prepared statement? daftar yang tidak
- output ke HTML sudah `htmlspecialchars()`? daftar yang belum
- semua form yang mengubah data pakai POST dan token CSRF? daftar yang tidak
- password disimpan dengan `password_hash()`? ada password plaintext di database atau di kode?
- semua halaman admin diproteksi login? daftar halaman admin yang bisa dibuka tanpa login
- upload: validasi MIME asli, batas ukuran, nama acak, dan folder uploads tidak bisa mengeksekusi PHP?
- kredensial database, kunci, token, atau password yang ter-hardcode atau ter-commit di git (cek juga riwayat git dengan `git log -p -S` untuk kata "password" dan nama pengguna database)
- error PHP mentah yang bisa tampil ke pengguna (`display_errors`), `phpinfo()`, halaman debug, atau file seperti `info.php`
- file sensitif yang bisa diakses lewat URL: `/database/*.sql`, `/database/backup`, `/.git`, file konfigurasi, `/docs`. Laporkan apakah perlu pembatasan akses (misalnya file `.htaccess` atau pemindahan di luar folder web, sesuaikan dengan kemampuan Herd).

### j. Audit kualitas kode

Duplikasi kode, file terlalu panjang yang mencampur logika dan tampilan, penamaan tidak konsisten, indentasi tidak konsisten, include berlapis yang membingungkan, hard-coded URL atau path, penggunaan konstanta `BASE_URL` yang tidak konsisten, serta HTML tidak valid (doctype, title, tag tidak tertutup, inline style berlebihan).

### k. Audit kelengkapan pengumpulan

Apakah ada README, petunjuk instalasi, file SQL lengkap yang bisa diimpor dari nol, akun login untuk pengujian, `.gitignore` yang benar, file konfigurasi contoh (`config.example.php`), komentar bertanda (`[FILE]`, `[BAGIAN]`, `[UBAH DI SINI]`, `[TAMBAH DI SINI]`, `[JANGAN UBAH]`), dan `/docs/PANDUAN_KODE.md` jika sebelumnya dibuat. Periksa apakah panduan itu masih cocok dengan file yang sebenarnya.

### l. Tes cepat data impor

Periksa apakah `toko_preloved_final.sql` bisa dibangun dari nol di database kosong (buat database uji sementara dengan nama berbeda, impor, cek struktur dan jumlah baris, lalu hapus database uji itu; JANGAN menyentuh database utama).

### m. Audit struktur database (hanya membaca)

Analisis awal untuk Fase 5 (detail dan daftar kecurigaan ada di Fase 5, Tahap A). Cukup lakukan hal berikut dan masukkan hasilnya ke laporan:

- `SHOW CREATE TABLE` untuk SEMUA tabel; daftar semua tabel dan semua foreign key yang sudah ada.
- Kolom atau tabel yang tampak kembar atau tumpang tindih, beserta bukti pemakaiannya di kode PHP (baca dan tulis) dan perbandingan isi datanya.
- Kolom dan tabel yang tidak pernah dibaca atau ditulis kode, atau hanya berisi data demo.
- Nilai `DISTINCT` kolom status, kondisi, kategori, dan ukuran, termasuk nilai yang tidak baku.
- Data yang sebenarnya tidak perlu disimpan di database (cukup di konfigurasi atau session).

### n. Laporan Fase 1

Tulis laporan berisi:

1. peta project dan peta halaman,
2. temuan kepatuhan stack,
3. daftar kandidat hapus file/folder/kode lengkap dengan bukti,
4. kandidat hapus di `/uploads`,
5. hasil audit emoji (di kode, di data produk, di teks pengguna, emoji yang berfungsi sebagai ikon beserta usulan penggantinya),
6. daftar demo dan data uji coba,
7. daftar akun dan rekomendasi akun yang dipertahankan,
8. tabel tidak terpakai,
9. hasil audit kualitas data,
10. hasil analisis awal alur admin dan tabel ketidakcocokan istilah antara admin dan user,
11. temuan keamanan berurut dari yang paling berbahaya,
12. temuan kualitas kode,
13. hasil audit struktur database (kolom kembar, kolom dan tabel tidak terpakai, status tidak baku, relasi yang belum ada) beserta usulan awal,
14. kekurangan kelengkapan pengumpulan,
15. daftar "meragukan",
16. rencana kerja Fase 2 sampai 9.

Lalu BERHENTI dan tunggu persetujuanku. Aku akan membalas dengan daftar yang disetujui dan yang dikecualikan.

---

## FASE 2 - TITIK PEMULIHAN

Setelah laporan Fase 1 kusetujui: buat backup database, commit, tag git `sebelum-final-cleanup`, dan branch `final-cleanup`. Tunjukkan bukti. Baru lanjut.

---

## FASE 3 - PEMBERSIHAN FILE DAN KODE (hanya yang kusetujui)

a. Hapus lewat `git rm` semua file, folder, dan skrip yang sudah disetujui. Hapus folder kosong. Pastikan tidak ada lagi referensi ke file yang dihapus.

b. Hapus kode mati: fungsi yang tidak dipanggil, include yang tidak dipakai, kode yang dikomentari, debug (`var_dump`, `print_r`, `echo`/`die` debug), komentar TODO/FIXME yang sudah selesai (TODO yang belum selesai dilaporkan padaku, bukan dihapus).

c. CSS: hapus selector, class, dan ID yang tidak dipakai (verifikasi dulu dengan pencarian di seluruh PHP), gabungkan aturan duplikat, ganti warna hex yang berulang dengan variabel di `tokens.css`, rapikan urutan dengan komentar `[BAGIAN]`. Tampilan tidak boleh berubah.

d. Hapus semua sisa JavaScript (tag `<script>`, file `.js`, atribut event), dan ganti dengan padanan PHP/HTML/CSS jika fungsinya masih dibutuhkan (laporkan perubahan tampilan jika ada; tanyakan dulu jika penggantinya mengubah tampilan user).

e. Hapus file di `/uploads` yang tidak direferensikan database HANYA yang ada di daftar yang kusetujui.

f. Jalankan `php -l` pada semua file PHP, cari referensi rusak (link, include, gambar, CSS), dan pastikan semua halaman terbuka tanpa warning PHP.

g. Commit di akhir fase.

---

## FASE 4 - PENGHAPUSAN EMOJI DAN PEMBERSIHAN DATA DEMO

### A. Emoji di kode dan file (sesuai daftar yang kusetujui)

1. Hapus semua emoji dari PHP, HTML, CSS (termasuk `content:` pada pseudo-element), teks SVG, atribut (`title`, `alt`, `placeholder`, `value`), pesan flash dan pesan error, README, dan dokumentasi. Jangan menyentuh file di `/database/backup`.
2. Jika emoji berfungsi sebagai ikon (misalnya keranjang, centang, tutup, peringatan), ganti dengan padanan yang BUKAN emoji sesuai usulan yang kusetujui: teks biasa, simbol tipografi sederhana, bentuk CSS, atau SVG inline yang bergaya sama dengan desain yang ada. Pastikan fungsi dan keterbacaan tetap, dan tombol atau label tidak menjadi kosong.
3. Setelah penghapusan, rapikan sisa spasi ganda, spasi di awal atau akhir teks, dan tanda baca yang menggantung, dan pastikan kalimat tetap wajar. Jangan mengubah isi kalimat selain menghapus emoji.
4. Jangan menghapus simbol tipografi non-emoji yang dilindungi (tanda merek terdaftar pada logo, tanda hak cipta, tanda kali, dan sejenisnya).

### B. Emoji dan demo di database (hanya yang kusetujui)

1. Backup database dulu (backup terbaru, terpisah dari yang di Fase 2).
2. Buat skrip `/database/tools/bersihkan_final.php` sekali pakai dengan MODE DRY-RUN sebagai default: menampilkan per tabel, kolom, dan id baris apa yang akan diubah atau dihapus (teks sebelum dan sesudah), tanpa menulis. Tunggu persetujuanku, lalu jalankan sungguhan dalam satu transaksi.
3. Emoji di data produk final (nama, deskripsi, merek, kategori, dan sebagainya): hapus dan rapikan teksnya. Emoji di teks kiriman pengguna (pesan, alamat, dan sebagainya): perlakukan sesuai keputusanku (hapus atau biarkan, per kasus). Tanyakan juga apakah input baru yang mengandung emoji perlu ditolak atau dibersihkan di server; jangan menambahkannya tanpa persetujuan.
4. Hapus data uji coba yang disetujui: akun uji, pesan uji, pesanan uji beserta detail dan bukti bayarnya, serta penanda demo. Urutan hapus harus menghormati foreign key (detail pesanan dulu, lalu pesanan, dan seterusnya). Jika stok berubah karena pesanan uji yang dihapus, kembalikan stok produk ke jumlah yang benar dan laporkan.
5. Bersihkan teks demo di data produk final: hapus kalimat "Foto ilustrasi sementara" dan sejenisnya, hapus awalan `DEMO-` pada kode produk (ganti dengan kode final yang rapi jika perlu), dan pastikan nama, merek, ukuran, kondisi, dan deskripsi produk rapi dan konsisten. JANGAN menulis klaim keaslian seperti "100% original" atau "authentic".
6. Akun: pertahankan HANYA akun yang kusetujui (satu admin untuk dosen, satu user contoh). Pastikan semua password disimpan dengan `password_hash()`. Buat akun pengujian dengan password sederhana yang jelas dan catat di README (bukan password pribadiku).
7. Jalankan ulang audit emoji untuk kode DAN database: hasilnya harus NOL (kecuali yang kuputuskan dibiarkan, dan tuliskan itu di laporan). Jalankan ulang cek konsistensi: semua anomali harus 0.
8. Setelah data bersih dan terverifikasi, hapus skrip `bersihkan_final.php` dan skrip demo berbahaya lain (`hapus_data_demo.php`, rollback demo, dan sejenisnya) dengan `git rm`. Simpan hanya alat bantu yang aman (misalnya `cek_konsistensi.php`) setelah kusetujui.
9. Ekspor ulang database bersih lengkap (struktur + data final) ke `/database/toko_preloved_final.sql`. File ini tidak boleh memuat data uji coba, emoji, atau password asli milikku, dan harus aman untuk diimpor (`CREATE TABLE`, bukan `DROP DATABASE`). Tes impor ke database kosong sementara, lalu hapus database sementara itu.
10. Kolom atau tabel yang tidak terpakai (termasuk kolom penanda demo seperti `is_demo`) JANGAN dihapus di fase ini; semuanya ditangani di Fase 5 setelah persetujuanku.

### C. Commit di akhir fase.

---

## FASE 5 - PENYEDERHANAAN STRUKTUR DATABASE

Tujuan: struktur database lebih sederhana, tidak ada kolom atau tabel kembar, tidak ada yang tidak terpakai, dan data yang tidak perlu disimpan di database dipindah ke tempat yang lebih tepat. Dilakukan SETELAH Fase 4 agar data demo sudah bersih.

### Izin dan syarat

Izin dariku:

- kolom atau tabel yang kembar boleh digabung dan dihapus,
- kolom atau tabel yang tidak terpakai boleh dihapus,
- data yang tidak perlu disimpan di database boleh dipindah ke `/shared/config.php` atau session.

Syarat (tidak boleh dilanggar):

1. Tampilan dan alur kerja website TIDAK berubah.
2. Fitur yang terlihat pengguna (halaman atau tombol) TIDAK boleh dihapus tanpa persetujuan eksplisitku.
3. Nama tabel dan kolom yang dipertahankan TIDAK diganti namanya, kecuali kusetujui secara khusus.
4. Urutan wajib: kode diubah lebih dulu dan diuji, baru migration dijalankan. Perubahan struktur MySQL (hapus kolom, ubah ke ENUM) tidak bisa dibatalkan seperti transaksi, jadi backup wajib tepat sebelum tiap migration, dan migration diuji lebih dulu di SALINAN database sementara.
5. Setiap langkah disetujui satu per satu. Dilarang `DROP DATABASE` dan `TRUNCATE`.

### Temuan awal dari diagram phpMyAdmin (buktikan lewat kode dan data, jangan dianggap pasti)

Tabel yang terlihat di diagram: `contacts`, `riwayat_status`, `product_images`, `products`, `orders`, `addresses`, `users`, `riwayat_stok`, `order_items`. Diagram terpotong sehingga bisa ada tabel lain (misalnya kategori atau admin). Kecurigaan:

- `orders.order_code` dan `orders.order_number` kemungkinan kembar.
- `orders.total` dan `orders.total_price` kemungkinan kembar.
- `orders.status` tumpang tindih dengan `payment_status` dan `order_status`.
- `products.condition` dan `products.condition_text` kemungkinan kembar (dan mungkin penyebab label kondisi di filter berbeda dengan di kartu produk). `condition` adalah kata cadangan MySQL.
- `products.image` dan tabel `product_images` sama-sama menyimpan foto.
- `products.category` berupa teks bebas; periksa apakah halaman admin Kategori butuh tabel `categories`.
- `products.is_demo` adalah penanda sementara; hapus setelah data demo bersih.
- Kolom status (`payment_status`, `order_status`, `status`, `condition`) bertipe `varchar` bebas; kandidat `ENUM`.
- `contacts` tidak punya kolom status baca (misalnya `is_read`) padahal dashboard menampilkan pesan belum dibaca.
- Foreign key tampaknya belum ada (diagram hanya menunjukkan relasi `products` ke `product_images`).
- `orders.address_id` hanya menunjuk ke alamat; jika alamat diubah atau dihapus, pesanan lama ikut berubah (pertimbangkan menyalin alamat ke pesanan saat checkout, atau larang menghapus alamat yang sudah dipakai).
- `riwayat_status` dan `riwayat_stok`: pertahankan jika dibaca dan ditampilkan di halaman admin; jika hanya ditulis dan tidak pernah dibaca, jadi kandidat hapus.

Rekomendasi bawaan (boleh disesuaikan setelah ada bukti):

- Tabel inti yang dipertahankan: `users`, `products`, `product_images`, `orders`, `order_items`, `addresses`, `contacts`.
- Keranjang cukup di session (jangan dibuat tabel).
- Daftar ukuran, kondisi, dan nama status cukup di `/shared/config.php` atau `ENUM`.
- Ongkir tetap atau daftar kurir cukup di konfigurasi, bukan tabel.
- Halaman statis (tentang, FAQ) tidak perlu database.
- Kategori: jika hanya 4 (Baju, Kemeja, Celana, Jacket) dan admin tidak perlu menambah atau mengubahnya, cukup konfigurasi; jika halaman admin Kategori memang menambah dan mengubah, tabel `categories` dipertahankan.

### Tahap A - Audit (hanya membaca: `SELECT`, `SHOW`, `DESCRIBE`)

Lengkapi atau perbarui audit struktur database dari Fase 1 butir m (boleh memakai hasil Fase 1 jika database tidak berubah sejak itu):

1. `SHOW CREATE TABLE` untuk SEMUA tabel; daftar semua tabel dan foreign key.
2. KOLOM KEMBAR: untuk tiap pasangan di atas dan pasangan lain yang ditemukan: (a) cari pemakaiannya di SEMUA file PHP (baca dan tulis, user dan admin), (b) hitung berapa baris yang nilainya berbeda dan tampilkan contohnya, (c) tentukan kolom yang dipertahankan (acuan: yang dipakai halaman user), (d) tulis aturan penggabungan data jika ada konflik. Jangan menimpa nilai diam-diam.
3. KOLOM DAN TABEL TIDAK TERPAKAI: untuk setiap kolom dan tabel, cari referensinya di kode. Tandai yang tidak pernah dibaca atau ditulis, atau hanya berisi data demo.
4. DATA YANG BISA DIPINDAH ke config atau session: daftar kandidat dan buktinya.
5. Nilai `DISTINCT` beserta jumlahnya untuk kolom status, kondisi, kategori, ukuran, serta `status_lama` dan `status_baru` di `riwayat_status`. Tandai nilai yang tidak baku dan bandingkan dengan nilai yang dipakai filter dan kartu produk di halaman user.
6. Cek `contacts` (status baca) dan cara dashboard menghitung pesan belum dibaca.
7. Cek anomali data: pesanan tanpa item, item tanpa pesanan, `user_id` atau `address_id` yang tidak ada, `order_items.product_id` yang tidak ada, stok negatif, total pesanan tidak sama dengan jumlah item.

### Tahap B - Laporan dan usulan (lalu BERHENTI dan tunggu persetujuanku)

Buat tabel keputusan dengan kolom: item | jenis (gabung, hapus, pindah ke config/session, tambah, ubah ke ENUM, tambah foreign key) | kolom atau tabel yang dipertahankan | bukti pemakaian (file dan baris) | risiko | file kode yang harus ikut diubah | berdampak pada fitur yang terlihat pengguna atau tidak. Urutkan dari yang paling aman. Aku akan menyetujui atau mengecualikan SATU PER SATU.

Untuk konflik label kondisi (database vs filter user vs kartu produk), jangan memilih sendiri; sajikan opsi himpunan nilai dan tunggu keputusanku.

### Tahap C - Ubah kode lebih dulu (hanya yang disetujui)

1. Backup database lengkap ke `/database/backup/` (bertanggal), lalu commit git. Kerjakan di branch terpisah.
2. Ubah kode PHP user dan admin agar berhenti memakai kolom atau tabel yang akan dihapus dan memakai kolom yang dipertahankan (termasuk query, form, filter, tampilan, dan fungsi di `/shared`). Pindahkan daftar yang disetujui ke `/shared/config.php`.
3. Uji semua alur terkait (checkout, upload bukti, ubah status, stok, filter produk, dashboard admin, detail pesanan). Tampilan dan perilaku harus sama seperti sebelumnya.

### Tahap D - Migration (hanya setelah Tahap C lulus uji dan kusetujui)

1. Jalankan di SALINAN database sementara dulu; setelah lulus uji, baru di database utama, didahului backup tepat sebelumnya.
2. Satu file migration per langkah di `/database/migrations` (berurutan, ada komentar penjelasan), dengan mode dry-run untuk bagian yang menyentuh data:
   a. gabung data kolom kembar sesuai aturan yang kusetujui (laporkan baris yang konflik, jangan timpa diam-diam),
   b. normalkan nilai status dan kondisi,
   c. ubah kolom status menjadi `ENUM` (nilai persis sama dengan yang dipakai kode),
   d. tambah kolom yang kurang (misalnya `contacts.is_read`),
   e. tambah foreign key dengan perilaku `ON DELETE` yang aman (`RESTRICT` untuk data transaksi),
   f. hapus kolom atau tabel yang disetujui (satu per satu, setelah kode terbukti tidak memakainya lagi, termasuk `is_demo`).
3. Dilarang mengganti nama tabel atau kolom yang tidak ada dalam daftar yang kusetujui.

### Tahap E - Verifikasi dan dokumentasi

1. Perbarui `/database/tools/cek_konsistensi.php` sesuai struktur baru dan jalankan: semua anomali harus 0.
2. Uji ulang alur user dan admin dari awal sampai akhir; angka dashboard harus sama dengan query verifikasi independen.
3. `php -l` semua file PHP; cari sisa referensi ke kolom atau tabel yang dihapus di seluruh project (hasilnya harus nol).
4. Ekspor ulang `/database/toko_preloved_final.sql`, tes impor ke database kosong sementara, lalu hapus database sementara itu.
5. Perbarui ERD di `/docs/ALUR_SISTEM.md` dan peta tabel di `/docs/PANDUAN_KODE.md` (jika belum ada, catat bahwa dokumen itu dibuat di Fase 8). Catat semua perubahan dan keputusanku di `/docs/PROGRESS_FINAL.md`.
6. Commit di akhir fase.

---

## FASE 6 - PEMERIKSAAN ALUR (WORKFLOW) ADMIN DAN VALIDITAS DATA

Dilakukan pada salinan database sementara. Tujuan: setiap angka, status, dan label yang tampil di admin BENAR sesuai database, SAMA dengan yang dilihat user, dan setiap alur admin berjalan tuntas tanpa jalan buntu. Tidak boleh mengubah alur kerja yang ada; jika alur dirasa salah, laporkan dan tunggu keputusanku.

1. VERIFIKASI ANGKA SECARA INDEPENDEN: untuk SETIAP angka dan tabel di admin (dashboard: jumlah produk, total pesanan, menunggu verifikasi, selesai, pesan masuk dan belum dibaca; daftar produk; stok; daftar pesanan; total tiap pesanan; detail pesanan; jumlah per kategori; jumlah hasil pencarian dan filter; jumlah halaman pagination; badge notifikasi), tulis query SQL verifikasi TERPISAH (jangan memakai fungsi aplikasi yang sama) dan bandingkan hasilnya. Sajikan dalam tabel: lokasi di admin | angka yang tampil | query verifikasi | hasil | cocok atau tidak.
2. KESAMAAN ADMIN DAN USER: untuk sampel produk dan pesanan yang sama (minimal produk dengan stok 0, produk dengan stok 1, pesanan dengan beberapa item, pesanan dibatalkan, pembayaran ditolak), bandingkan nama, harga, stok, kondisi, ukuran, merek, kategori, foto, status pembayaran, status pesanan, total, tanggal, dan nomor resi di halaman admin dan halaman user. Semuanya harus identik, termasuk format Rupiah dan tanggal serta zona waktu Asia/Jakarta.
3. KONSISTENSI ISTILAH: pastikan satu set nilai untuk kondisi, ukuran, status pembayaran, status pesanan, dan kategori dipakai di database, filter user, kartu produk, form admin, dan filter admin. Jika ada yang berbeda (misalnya "Bagus" di database tetapi "Baik" atau "Sangat Baik" di filter), cari penyebabnya dan buktikan dampaknya (misalnya filter yang tidak menemukan produk). Usulkan perbaikan paling kecil dan paling aman, lalu tunggu persetujuanku sebelum mengubah data atau tampilan.
4. UJI ALUR ADMIN SATU PER SATU (dengan hasil lulus atau gagal beserta bukti): login (benar dan salah), dashboard, tambah/lihat/ubah/hapus produk (termasuk upload beberapa foto dan hapus lewat halaman konfirmasi), kategori (tambah, ubah, hapus kategori yang masih dipakai harus ditolak), ubah stok, lihat daftar pesanan (cari, filter status dan tanggal, pagination), detail pesanan, buka bukti bayar, ubah status pembayaran (termasuk tolak dengan alasan yang terlihat user), ubah status pesanan (termasuk nomor resi), pesan masuk (baca, tandai, hapus), logout.
5. UJI SINKRONISASI DUA ARAH: admin tambah produk -> muncul di user; user checkout dan upload bukti -> pesanan muncul di admin dan stok berkurang; admin verifikasi pembayaran dan ubah status -> terlihat di riwayat user; batal atau tolak -> stok kembali tepat satu kali; user kirim pesan -> muncul di admin; ubah harga produk di admin -> pesanan lama tidak berubah (harga saat pembelian tersimpan).
6. UJI KASUS TEPI: data kosong (tanpa pesanan, tanpa pesan, tanpa hasil pencarian), id yang tidak ada di URL, parameter filter aneh, halaman pagination di luar jangkauan, kirim ulang form lewat refresh, klik ganda, dua checkout bersamaan untuk produk stok 1 (hanya satu yang berhasil), perpindahan status tidak sah (misalnya langsung "Dikirim" sebelum pembayaran terverifikasi) harus ditolak dengan pesan jelas, dan akses halaman admin tanpa login atau lewat tombol back setelah logout harus ditolak.
7. TEMUAN: setiap ketidakcocokan atau bug dicatat (halaman, langkah, hasil yang diharapkan, hasil sebenarnya, penyebab). Perbaiki hanya bug yang jelas dan tidak mengubah alur kerja, seminimal mungkin dan dengan komentar `[JANGAN UBAH]` pada logika kritis. Perbaikan yang menyentuh alur atau tampilan dilaporkan dulu. Setelah perbaikan, ulangi pengujian terkait.
8. Pastikan perilaku halaman user tidak berubah akibat perbaikan di sini, kecuali yang kusetujui.
9. Commit di akhir fase.

---

## FASE 7 - PERAPIAN KODE, KEAMANAN FINAL, DAN KOMENTAR BERTANDA

a. Perbaiki temuan keamanan Fase 1 berurutan dari yang paling berbahaya: prepared statement, `htmlspecialchars`, CSRF, proteksi login admin, validasi upload, error PHP tidak tampil ke pengguna (`display_errors` off untuk pengumpulan), hapus `phpinfo` atau halaman debug.

b. Kredensial: pastikan tidak ada password pribadi atau token di kode maupun di riwayat git yang akan dikumpulkan. Konfigurasi database memakai nilai default Herd yang umum agar dosen mudah menjalankan; buat `/shared/config.example.php` sebagai contoh dan jelaskan di README bila ada nilai yang perlu disesuaikan. Jika ditemukan rahasia di riwayat git, LAPORKAN padaku dan jangan menulis ulang riwayat git tanpa persetujuan.

c. Pembatasan akses file sensitif: pastikan `/database`, `/docs`, `/.git`, dan file konfigurasi tidak bisa dibuka lewat URL biasa jika memungkinkan di Herd (misalnya `.htaccess` atau pemindahan struktur), tanpa merusak fungsi website. Jelaskan hasilnya.

d. Rapikan kode: indentasi dan format konsisten, penamaan konsisten dan jelas, hapus duplikasi dengan memindahkannya ke fungsi di `/shared`, pisahkan logika dari tampilan sejauh wajar, HTML valid (doctype, title, tag tertutup), tanpa inline style berantakan, class dan ID bermakna. Rapikan tidak boleh mengubah perilaku.

e. KOMENTAR BERTANDA: pastikan SEMUA file PHP dan CSS punya:

- header `[FILE]` (fungsi, halaman yang memakai, tabel yang disentuh, fungsi bersama yang dipanggil),
- blok besar dibungkus `[BAGIAN: nama]` ... `[/BAGIAN: nama]`,
- nilai yang aman diubah bertanda `[UBAH DI SINI]`,
- titik perluasan bertanda `[TAMBAH DI SINI]` beserta petunjuknya,
- logika kritis bertanda `[JANGAN UBAH]` beserta alasannya (stok, status, snapshot harga, CSRF, upload, login).

Komentar berbahasa Indonesia, singkat, akurat, TANPA emoji, dan pakai komentar PHP (bukan komentar HTML) di dalam tampilan agar tidak terlihat di view-source. Perbarui komentar yang usang.

f. Nama file dan folder konsisten (huruf kecil, tanpa spasi), tidak ada file sampah.

g. Commit di akhir fase.

---

## FASE 8 - DOKUMENTASI DAN PAKET PENGUMPULAN

a. `README.md` di root berbahasa Indonesia, TANPA emoji, berisi: nama project dan deskripsi singkat, daftar fitur (user dan admin), teknologi (HTML, CSS, PHP native, MySQL, tanpa JavaScript), anggota kelompok dan pembagian tugas (beri placeholder yang bisa kuisi), struktur folder, persyaratan, langkah instalasi di Laravel Herd (taruh folder project di folder yang di-park Herd atau tambahkan lewat Herd > Sites, nyalakan MySQL, buat database dengan nama yang sama seperti di config, impor `/database/toko_preloved_final.sql` lewat phpMyAdmin, buka `http://namaproject.test`), akun pengujian (admin dan user contoh beserta password), alur penggunaan singkat (user: lihat produk sampai checkout dan upload bukti bayar; admin: login sampai ubah status pesanan), dan masalah umum beserta solusinya (misalnya koneksi database gagal, gambar tidak tampil).

b. Perbarui `/docs/PANDUAN_KODE.md` agar sesuai kondisi akhir: peta file (halaman -> file -> tabel -> fungsi bersama), daftar tag dan cara mencarinya di VS Code, indeks lokasi `[UBAH DI SINI]` dan `[JANGAN UBAH]`, panduan langkah untuk perubahan umum (menambah kolom produk, menambah status pesanan, menambah halaman admin, mengubah tema, menambah kategori).

c. Buat `/docs/ALUR_SISTEM.md` singkat: alur user, alur admin, alur status pembayaran dan pesanan, alur stok, beserta ERD sederhana berbentuk teks (daftar tabel, kolom penting, dan relasi), untuk presentasi atau laporan.

d. `.gitignore` yang benar: `/database/backup/`, file konfigurasi pribadi, log, cache, `.DS_Store`, `Thumbs.db`, folder IDE (`.vscode`, `.idea`), dan file sementara. Folder `/uploads/produk` tetap ikut, bukti bayar hasil uji coba tidak ikut. Tanyakan padaku apakah dokumen `INSTRUKSI_FINAL.md` ini ikut dalam paket atau dikeluarkan.

e. Pastikan struktur final rapi dan sederhana. Tampilkan tree akhir.

f. Buat daftar periksa pengumpulan di `/docs/CHECKLIST_PENGUMPULAN.md`.

g. Jangan membuat file ZIP kecuali kuminta; cukup siapkan folder bersih, dan beri tahu cara membuat ZIP bersih (tanpa `.git`, backup, dan file sementara).

h. Commit di akhir fase.

---

## FASE 9 - PENGUJIAN AKHIR

Dilakukan di Laravel Herd, pada salinan database yang bersih. Uji menyeluruh dan laporkan hasilnya dengan bukti (perintah dan hasilnya):

1. Semua halaman user dan admin terbuka di `http://namaproject.test` tanpa link rusak, gambar hilang, warning, atau error PHP.
2. CEK STACK: pencarian `<script>`, file `.js`, `onclick`/`onchange`/`onsubmit`/`onload`, `javascript:`, `fetch(`, `XMLHttpRequest`, dan library JS dari CDN di seluruh project (kecuali `/database/backup` dan `/.git`) harus NOL.
3. CEK EMOJI: pindai ulang seluruh file project (kecuali `/.git` dan `/database/backup`) DAN seluruh kolom teks database dengan ekspresi reguler Unicode, termasuk entitas HTML/numerik dan escape PHP/CSS. Hasilnya harus NOL, kecuali yang kuputuskan dibiarkan (jelaskan). Pastikan tidak ada kotak kosong atau karakter rusak di tempat bekas emoji, dan tombol atau label tidak menjadi kosong.
4. CEK DEMO: pencarian "demo", "dummy", "lorem ipsum", "sementara", "foto ilustrasi", `DEMO-`, "test", "asdf" di kode dan database harus NOL atau hanya yang sah (jelaskan). Tidak ada akun, pesanan, atau pesan uji yang tersisa selain yang kusetujui.
5. CEK SAMPAH: tidak ada file `.bak`/`.old`/`.zip`/`.rar`, `.DS_Store`, `Thumbs.db`, folder kosong, skrip alat sementara berbahaya, atau file di `/uploads` tanpa referensi.
6. CEK KOMENTAR: setiap file PHP dan CSS punya header `[FILE]`; semua `[BAGIAN]` punya penutup; `PANDUAN_KODE.md` cocok dengan struktur akhir.
7. `php -l` semua file PHP: tanpa error. Tidak ada warning atau notice PHP saat membuka semua halaman.
8. CEK DATABASE: impor `toko_preloved_final.sql` ke database kosong sementara berhasil; jumlah produk, kategori, dan gambar sesuai; `cek_konsistensi` menghasilkan 0 anomali; tidak ada produk tanpa kategori atau foto utama.
9. CEK STRUKTUR DATABASE: struktur sesuai ERD di `/docs/ALUR_SISTEM.md`; tidak ada kolom kembar atau tabel tidak terpakai yang tersisa; tidak ada referensi di kode ke kolom atau tabel yang sudah dihapus (hasil pencarian harus nol); kolom status berupa `ENUM` dan foreign key aktif.
10. ULANGI SELURUH PENGUJIAN FASE 6 (verifikasi angka admin, kesamaan admin dan user, istilah, alur admin satu per satu, sinkronisasi dua arah, kasus tepi) pada kondisi akhir, dan laporkan hasilnya: semua harus lulus.
11. KEAMANAN: coba input berbahaya sederhana (tanda kutip tunggal, tag HTML di form, upload file `.php`), akses halaman admin tanpa login, akses `/database/*.sql` lewat URL: semuanya ditolak atau tidak bisa diakses.
12. TEMA: bandingkan halaman user dan admin berdampingan: warna, font, dan logo konsisten.
13. Setelah tes selesai, hapus semua data uji coba yang dibuat oleh pengujianmu dan salinan database sementara. Pastikan database utama tidak berubah selain yang kusetujui.

Perbaiki semua bug yang ditemukan, lalu ulangi tes yang terkait.

---

## Format laporan akhir

Tulis juga ke `/docs/LAPORAN_FINAL.md`, tanpa emoji:

- Ringkasan kondisi sebelum dan sesudah (jumlah file, ukuran project, jumlah baris kode kira-kira, jumlah anomali data, jumlah emoji yang dihapus)
- Struktur folder final (tree)
- Daftar file/folder/skrip yang DIHAPUS beserta alasan dan bukti tidak terpakai
- Daftar kode dan CSS mati yang dihapus (ringkas, per file)
- Daftar emoji yang dihapus: lokasi di kode dan data, serta pengganti untuk emoji yang berfungsi sebagai ikon
- Daftar data demo/uji coba yang dihapus dari database (jumlah per tabel)
- Ringkasan perubahan struktur database: kolom atau tabel yang digabung, dihapus, dipindah ke config atau session, serta kolom, ENUM, dan foreign key yang ditambah, beserta file migration dan cara memulihkannya
- Hasil pemeriksaan alur admin: tabel verifikasi angka (tampil vs query independen), ketidakcocokan yang ditemukan dan perbaikannya, ketidakcocokan yang menunggu keputusanku (termasuk istilah kondisi)
- Daftar file yang diubah dan alasannya (supaya bisa dibagikan ke anggota kelompok)
- Perbaikan bug dan keamanan yang dilakukan
- Hal yang meragukan dan keputusanku
- Hasil semua tes Fase 9 (lulus atau gagal beserta bukti)
- Akun pengujian, cara instalasi singkat, dan lokasi dokumentasi
- Cara memulihkan jika ada yang rusak: kembalikan backup database di `/database/backup/` dan kembali ke tag git `sebelum-final-cleanup`
- Saran sebelum dikumpulkan (hal yang perlu kupastikan sendiri)

---

## Perintah mulai

Mulai sekarang dengan FASE 1 saja. Jangan edit, pindahkan, hapus, atau ubah database sebelum kusetujui laporan Fase 1. Jangan mengubah tampilan mobile maupun desktop. Setelah Fase 1 selesai, buat `/docs/PROGRESS_FINAL.md`, tulis ringkasan, lalu berhenti dan tunggu persetujuanku.
