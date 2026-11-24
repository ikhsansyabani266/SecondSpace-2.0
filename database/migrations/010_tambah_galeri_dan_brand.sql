-- ========================================================
-- Migration: 010_tambah_galeri_dan_brand.sql
-- Keterangan: Menambah kolom brand, sku, is_demo pada products
--             serta tabel product_images untuk galeri foto
-- ========================================================

-- 1. Penambahan Kolom pada Tabel `products`
ALTER TABLE `products`
    ADD COLUMN `brand` VARCHAR(100) NULL DEFAULT 'No Brand' AFTER `category`,
    ADD COLUMN `sku` VARCHAR(50) NULL UNIQUE AFTER `size`,
    ADD COLUMN `is_demo` TINYINT(1) NOT NULL DEFAULT 0 AFTER `sku`;

-- 2. Pembuatan Tabel `product_images` untuk Multi-Photo Galeri
CREATE TABLE IF NOT EXISTS `product_images` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL,
    `image` VARCHAR(255) NOT NULL,
    `sort_order` INT NOT NULL DEFAULT 1,
    `type` ENUM('depan', 'belakang', 'detail') NOT NULL DEFAULT 'depan',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_product_images_product` FOREIGN KEY (`product_id`) 
        REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX `idx_product_images_pid` ON `product_images` (`product_id`);
CREATE INDEX `idx_products_is_demo` ON `products` (`is_demo`);
