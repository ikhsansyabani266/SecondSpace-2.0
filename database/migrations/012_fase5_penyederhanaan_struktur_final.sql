-- ========================================================
-- Migration 012: Penyederhanaan Struktur Database Final (Fase 5)
-- Sesuai Persetujuan Pemilik Proyek pada Tahap B
-- ========================================================

SET FOREIGN_KEY_CHECKS=0;

-- 1. Normalisasi Data Kondisi Produk Sesuai Opsi A
UPDATE `products` SET `condition` = 'Seperti Baru' WHERE `condition` = 'Sangat Baik';
UPDATE `products` SET `condition` = 'Bagus' WHERE `condition` = 'Baik';
UPDATE `products` SET `condition` = 'Ada Minus' WHERE `condition` = 'Cukup';

-- Hapus kolom condition_text dan is_demo, ubah condition ke ENUM baku
ALTER TABLE `products` 
    DROP COLUMN `condition_text`,
    DROP COLUMN `is_demo`,
    MODIFY `condition` ENUM('Seperti Baru', 'Bagus', 'Ada Minus') NOT NULL DEFAULT 'Bagus';

-- 2. Penyederhanaan Kolom Tabel orders
-- Hapus kolom kembar order_number, total_price, dan status
-- Ubah order_status dan payment_status ke ENUM baku
ALTER TABLE `orders`
    DROP COLUMN `order_number`,
    DROP COLUMN `total_price`,
    DROP COLUMN `status`,
    MODIFY `order_status` ENUM('Menunggu Pembayaran', 'Diproses', 'Dikirim', 'Selesai', 'Dibatalkan') NOT NULL DEFAULT 'Menunggu Pembayaran',
    MODIFY `payment_status` ENUM('Belum Bayar', 'Menunggu Pembayaran', 'Menunggu Konfirmasi', 'Menunggu Verifikasi', 'Dibayar', 'Terverifikasi', 'Ditolak', 'Selesai', 'Dibatalkan') NOT NULL DEFAULT 'Menunggu Pembayaran';

-- 3. Penambahan Kolom Status Baca pada Tabel contacts
ALTER TABLE `contacts`
    ADD COLUMN `is_read` TINYINT(1) NOT NULL DEFAULT 0 AFTER `message`;

-- 4. Penambahan Foreign Key Constraint Formal
-- Pastikan orders terhubung ke users
ALTER TABLE `orders`
    ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

-- Pastikan order_items terhubung ke orders
ALTER TABLE `order_items`
    ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

SET FOREIGN_KEY_CHECKS=1;
