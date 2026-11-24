-- [FILE] 011_fase5_schema_dan_audit_trail.sql
-- Migrasi Skema FASE 5: Bukti Pembayaran, Indeks Pencarian, dan Tabel Riwayat (Audit Trail)
-- Database: secondspace_preloved
-- Aman dijalankan di phpMyAdmin (Hanya bersifat menambah / ADDITIVE)

-- 1. Tambah kolom bukti pembayaran dan catatan verifikasi di tabel orders (jika belum ada)
ALTER TABLE `orders`
    ADD COLUMN IF NOT EXISTS `proof_image` VARCHAR(255) NULL AFTER `payment_method`,
    ADD COLUMN IF NOT EXISTS `payment_notes` TEXT NULL AFTER `proof_image`;

-- 2. Tambah indeks untuk pencarian cepat & integritas kode pesanan
ALTER TABLE `orders`
    ADD INDEX IF NOT EXISTS `idx_order_code` (`order_code`),
    ADD INDEX IF NOT EXISTS `idx_order_status` (`order_status`),
    ADD INDEX IF NOT EXISTS `idx_payment_status` (`payment_status`),
    ADD INDEX IF NOT EXISTS `idx_orders_created` (`created_at`);

ALTER TABLE `products`
    ADD INDEX IF NOT EXISTS `idx_product_category` (`category`),
    ADD INDEX IF NOT EXISTS `idx_product_status` (`is_demo`);

-- 3. Tabel Riwayat Status (Audit Trail Perpindahan Status Pesanan & Pembayaran)
CREATE TABLE IF NOT EXISTS `riwayat_status` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `jenis` ENUM('pesanan', 'pembayaran') NOT NULL,
    `status_lama` VARCHAR(50) NOT NULL,
    `status_baru` VARCHAR(50) NOT NULL,
    `catatan` TEXT NULL,
    `admin_id` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_rs_order` (`order_id`),
    INDEX `idx_rs_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabel Riwayat Mutasi Stok (Audit Trail Stok Aman & Anti-Overselling)
CREATE TABLE IF NOT EXISTS `riwayat_stok` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL,
    `perubahan` INT NOT NULL,
    `stok_sebelum` INT NOT NULL,
    `stok_sesudah` INT NOT NULL,
    `alasan` VARCHAR(100) NOT NULL,
    `referensi_order_id` INT NULL,
    `admin_id` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_rstok_product` (`product_id`),
    INDEX `idx_rstok_order` (`referensi_order_id`),
    INDEX `idx_rstok_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
