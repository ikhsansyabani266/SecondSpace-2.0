# ALUR SISTEM & DIAGRAM RELASI DATA (ERD)
## SECONDSPACE // CURATED PRELOVED STREETWEAR PLATFORM
**Versi:** 2.0 (Fase 5 - Struktur Database Baku & Normal)  
**Kepatuhan:** 100% PHP Native, Bebas JavaScript, Zero Emoji, Integritas Relasional Penuh.

---

## 1. ENTITY RELATIONSHIP DIAGRAM (ERD)

Struktur basis data SecondSpace terdiri dari 9 tabel terhubung dengan foreign key formal dan penghapusan kolom kembar/redundant:

```mermaid
erDiagram
    users ||--o{ orders : "makes"
    users ||--o{ addresses : "has"
    users ||--o{ riwayat_status : "admin_audits"
    users ||--o{ riwayat_stok : "admin_audits"
    addresses ||--o{ orders : "delivers_to"
    products ||--o{ product_images : "contains"
    products ||--o{ order_items : "sold_in"
    products ||--o{ riwayat_stok : "tracks"
    orders ||--|{ order_items : "contains"
    orders ||--o{ riwayat_status : "tracks"
    orders ||--o{ riwayat_stok : "triggers"

    users {
        int id PK
        varchar name
        varchar username
        varchar password
        enum role "admin, user"
        timestamp created_at
    }

    addresses {
        int id PK
        int user_id FK
        varchar recipient_name
        varchar phone
        text address
        varchar city
        varchar postal_code
    }

    products {
        int id PK
        varchar sku UK
        varchar name
        varchar category
        varchar brand
        varchar size
        enum condition "Seperti Baru, Bagus, Ada Minus"
        varchar material
        decimal price
        int stock
        text description
        varchar image
        timestamp created_at
    }

    product_images {
        int id PK
        int product_id FK
        varchar image
        int sort_order
        enum type "depan, belakang, detail"
        timestamp created_at
    }

    orders {
        int id PK
        int user_id FK
        varchar order_code UK
        int address_id FK
        decimal subtotal
        decimal shipping
        decimal total
        varchar payment_method
        varchar proof_image
        text payment_notes
        enum payment_status "Belum Bayar, Menunggu Pembayaran, Menunggu Konfirmasi, Menunggu Verifikasi, Dibayar, Terverifikasi, Ditolak, Selesai, Dibatalkan"
        enum order_status "Menunggu Pembayaran, Diproses, Dikirim, Selesai, Dibatalkan"
        text notes
        varchar shipping_courier
        varchar tracking_number
        timestamp created_at
    }

    order_items {
        int id PK
        int order_id FK
        int product_id FK
        varchar product_name
        decimal price
        int quantity
    }

    contacts {
        int id PK
        varchar name
        email email
        text message
        tinyint is_read
        timestamp created_at
    }

    riwayat_status {
        int id PK
        int order_id FK
        enum jenis "pesanan, pembayaran"
        varchar status_lama
        varchar status_baru
        text catatan
        int admin_id FK
        timestamp created_at
    }

    riwayat_stok {
        int id PK
        int product_id FK
        int perubahan
        int stok_akhir
        varchar alasan
        int order_id FK
        int admin_id FK
        timestamp created_at
    }
```

---

## 2. ATURAN HIMPUNAN KONDISI PRODUK (OPSI A)

Sesuai persetujuan pemilik proyek, sistem SecondSpace murni menerapkan satu himpunan label kondisi:

| Nilai ENUM Basis Data | Label Tampilan Pengguna | Deskripsi Standar |
| :--- | :--- | :--- |
| `Seperti Baru` | Seperti Baru | Kondisi mulus sempurna tanpa noda, robek, atau jejak pemakaian berarti. |
| `Bagus` | Bagus | Kondisi terawat rapi, warna solid, minus wajar pemakaian normal preloved. |
| `Ada Minus` | Ada Minus | Memiliki minus tertentu (misal: pudar halus, kikis minor) yang dicantumkan jelas pada deskripsi. |

**Lokasi Definisi:** Terpusat di `/shared/config.php` pada konstanta `DAFTAR_KONDISI_PRODUK`.

---

## 3. ALUR STATUS TRANSAKSI & PEMBAYARAN (STATE MACHINE)

```mermaid
flowchart TD
    A["User Checkout Pesanan"] --> B["Status: Menunggu Pembayaran<br>Payment: Menunggu Pembayaran"]
    B --> C["User Upload Bukti Bayar"]
    C --> D["Status: Menunggu Pembayaran<br>Payment: Menunggu Verifikasi"]
    D --> E{"Admin Verifikasi Bukti"}
    E -- "Bukti Sah" --> F["Payment: Terverifikasi<br>Status: Diproses (Otomatis Maju)"]
    E -- "Bukti Tidak Sah" --> G["Payment: Ditolak<br>Pesanan Tetap Menunggu"]
    F --> H["Admin Input No Resi & Kurir"]
    H --> I["Status: Dikirim"]
    I --> J["Konfirmasi Penerimaan Barang"]
    J --> K["Status: Selesai<br>Payment: Selesai"]
    B -- "Batal / Kedaluwarsa" --> L["Status: Dibatalkan<br>(Stok Produk Dikembalikan)"]
```

---

## 4. INTEGRITAS TRANSAKSI & MUTASI STOK PRELOVED

1. **Sifat Barang 1-of-1**: Setiap produk preloved merupakan pakaian unik dengan stok 1 (`stock = 1`).
2. **Pencatatan Audit Riwayat**:
   - Setiap pengurangan stok saat checkout dicatat di tabel `riwayat_stok`.
   - Setiap perubahan status pesanan atau pembayaran diverifikasi melalui `ubah_status_pesanan()` dan `ubah_status_pembayaran()`, lalu diaudit otomatis pada tabel `riwayat_status`.
   - Jika pesanan dibatalkan, kuantitas stok dikembalikan tepat satu kali ke tabel `products` dan dicatat ke `riwayat_stok`.
