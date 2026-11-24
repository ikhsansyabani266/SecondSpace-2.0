-- ========================================================
-- Toko Preloved Final SQL Dump (Ready for phpMyAdmin)
-- Database: secondspace_preloved
-- Exported: 2026-11-24 16:12:05
-- ========================================================

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
START TRANSACTION;
SET time_zone = '+00:00';

-- --------------------------------------------------------
-- Struktur tabel `addresses`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `addresses`;
CREATE TABLE `addresses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `recipient_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `city` varchar(100) NOT NULL,
  `postal_code` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data untuk tabel `addresses`
INSERT INTO `addresses` (`id`, `user_id`, `recipient_name`, `phone`, `address`, `city`, `postal_code`) VALUES ('1', '2', 'ikhsan', '0895327292342', 'Ss', 'Jakarta Selatan', '13453');
INSERT INTO `addresses` (`id`, `user_id`, `recipient_name`, `phone`, `address`, `city`, `postal_code`) VALUES ('2', '2', 'ikhsan', '0895327292342', 'Ssadas', 'Jakarta Selatan', '13453');
INSERT INTO `addresses` (`id`, `user_id`, `recipient_name`, `phone`, `address`, `city`, `postal_code`) VALUES ('4', '2', 'ikhsan', '0895327292342', 'Ssadas', 'Jakarta Selatan', '13453');
INSERT INTO `addresses` (`id`, `user_id`, `recipient_name`, `phone`, `address`, `city`, `postal_code`) VALUES ('6', '2', 'ikhsan', '0895327292342', 'Ssadas', 'Jakarta Selatan', '13453');
INSERT INTO `addresses` (`id`, `user_id`, `recipient_name`, `phone`, `address`, `city`, `postal_code`) VALUES ('7', '1', 'nabila', '081808318350', 'Jl.bojong indah 3 rt 3 rw 6 no 19', 'Jakarta timur', '13450');
INSERT INTO `addresses` (`id`, `user_id`, `recipient_name`, `phone`, `address`, `city`, `postal_code`) VALUES ('8', '7', 'demo', '081808318350', 'Jl.bojong indah 3 rt 3 rw 6 no 19', 'Jakarta timur', '13450');

-- --------------------------------------------------------
-- Struktur tabel `contacts`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `contacts`;
CREATE TABLE `contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Struktur tabel `order_items`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(12,2) NOT NULL,
  `subtotal` decimal(12,2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data untuk tabel `order_items`
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `price`, `subtotal`) VALUES ('1', '1', '0', 'Kaos Rubber Stamp Boxy', '1', '120000.00', '120000.00');
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `price`, `subtotal`) VALUES ('5', '4', '0', 'Kaos Rubber Stamp Boxy', '1', '120000.00', '120000.00');
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `price`, `subtotal`) VALUES ('6', '4', '0', 'Kemeja Pinstripe Script Print', '1', '185000.00', '185000.00');
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `price`, `subtotal`) VALUES ('8', '6', '19', 'Kaos Polos Putih Katun Combed Classic Tee', '1', '50000.00', '50000.00');
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `price`, `subtotal`) VALUES ('13', '11', '20', 'Kemeja Oxford Button-Down Putih Formal Kasual', '1', '115000.00', '115000.00');
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `price`, `subtotal`) VALUES ('14', '12', '20', 'Oxford Button-Down Shirt', '1', '115000.00', '115000.00');

-- --------------------------------------------------------
-- Struktur tabel `orders`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `order_code` varchar(50) DEFAULT NULL,
  `address_id` int(11) DEFAULT NULL,
  `subtotal` decimal(12,2) DEFAULT NULL,
  `shipping` decimal(12,2) DEFAULT NULL,
  `total` decimal(12,2) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `proof_image` varchar(255) DEFAULT NULL,
  `payment_notes` text DEFAULT NULL,
  `payment_status` varchar(50) DEFAULT 'Menunggu Pembayaran',
  `order_status` varchar(50) DEFAULT 'Menunggu Pembayaran',
  `notes` text DEFAULT NULL,
  `shipping_courier` varchar(50) DEFAULT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `order_number` varchar(50) NOT NULL,
  `total_price` decimal(12,2) NOT NULL,
  `status` varchar(50) DEFAULT 'Menunggu Pembayaran',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_order_code` (`order_code`),
  KEY `idx_order_status` (`order_status`),
  KEY `idx_payment_status` (`payment_status`),
  KEY `idx_orders_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data untuk tabel `orders`
INSERT INTO `orders` (`id`, `user_id`, `order_code`, `address_id`, `subtotal`, `shipping`, `total`, `payment_method`, `proof_image`, `payment_notes`, `payment_status`, `order_status`, `notes`, `shipping_courier`, `tracking_number`, `order_number`, `total_price`, `status`, `created_at`) VALUES ('1', '2', 'SS-20261118244834', '1', '120000.00', '15000.00', '135000.00', 'Transfer Bank', NULL, NULL, 'Selesai', 'Selesai', NULL, NULL, NULL, 'SS-20261118244834', '135000.00', 'Selesai', '2026-11-18 16:03:39');
INSERT INTO `orders` (`id`, `user_id`, `order_code`, `address_id`, `subtotal`, `shipping`, `total`, `payment_method`, `proof_image`, `payment_notes`, `payment_status`, `order_status`, `notes`, `shipping_courier`, `tracking_number`, `order_number`, `total_price`, `status`, `created_at`) VALUES ('4', '2', 'SS-20261122542323', '4', '305000.00', '15000.00', '167500.00', 'Transfer Bank', NULL, NULL, 'Selesai', 'Selesai', 'Kupon: DISKON50 (-Rp 152.500)', NULL, NULL, 'SS-20261122542323', '167500.00', 'Selesai', '2026-11-22 20:08:09');
INSERT INTO `orders` (`id`, `user_id`, `order_code`, `address_id`, `subtotal`, `shipping`, `total`, `payment_method`, `proof_image`, `payment_notes`, `payment_status`, `order_status`, `notes`, `shipping_courier`, `tracking_number`, `order_number`, `total_price`, `status`, `created_at`) VALUES ('6', '2', 'SS-20261122570654', '6', '50000.00', '15000.00', '40000.00', 'Transfer Bank', NULL, NULL, 'Dibatalkan', 'Dibatalkan', 'Kupon: DISKON50 (-Rp 25.000)', NULL, NULL, 'SS-20261122570654', '40000.00', 'Dibatalkan', '2026-11-22 22:24:17');
INSERT INTO `orders` (`id`, `user_id`, `order_code`, `address_id`, `subtotal`, `shipping`, `total`, `payment_method`, `proof_image`, `payment_notes`, `payment_status`, `order_status`, `notes`, `shipping_courier`, `tracking_number`, `order_number`, `total_price`, `status`, `created_at`) VALUES ('11', '1', 'SS-20261123498237', '7', '115000.00', '15000.00', '130000.00', 'COD', NULL, NULL, 'Selesai', 'Selesai', '', 'SiCepat', '124154134124132', 'SS-20261123498237', '130000.00', 'Selesai', '2026-11-23 00:55:35');
INSERT INTO `orders` (`id`, `user_id`, `order_code`, `address_id`, `subtotal`, `shipping`, `total`, `payment_method`, `proof_image`, `payment_notes`, `payment_status`, `order_status`, `notes`, `shipping_courier`, `tracking_number`, `order_number`, `total_price`, `status`, `created_at`) VALUES ('12', '7', 'SS-20261124968285', '8', '115000.00', '15000.00', '130000.00', 'E-Wallet', NULL, NULL, 'Selesai', 'Selesai', '', 'JNE', '121441241532', 'SS-20261124968285', '130000.00', 'Selesai', '2026-11-24 09:30:24');

-- --------------------------------------------------------
-- Struktur tabel `product_images`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `product_images`;
CREATE TABLE `product_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `image` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 1,
  `type` enum('depan','belakang','detail') NOT NULL DEFAULT 'depan',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_product_images_product` (`product_id`),
  CONSTRAINT `fk_product_images_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=78 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data untuk tabel `product_images`
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('1', '9', 'baju-01-athletic-performance-hitam-depan.webp', '1', 'depan', '2026-11-22 22:03:27');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('2', '9', 'baju-01-athletic-performance-hitam-belakang.webp', '2', 'belakang', '2026-11-22 22:03:27');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('3', '9', 'baju-01-athletic-performance-hitam-detail.webp', '3', 'detail', '2026-11-22 22:03:27');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('4', '10', 'kemeja-02-garis-biru-putih-depan.webp', '1', 'depan', '2026-11-22 22:03:27');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('5', '10', 'kemeja-02-garis-biru-putih-belakang.webp', '2', 'belakang', '2026-11-22 22:03:27');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('6', '10', 'kemeja-02-garis-biru-putih-detail.webp', '3', 'detail', '2026-11-22 22:03:27');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('7', '11', 'baju-03-katun-abu-abu-depan.webp', '1', 'depan', '2026-11-22 22:03:27');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('8', '11', 'baju-03-katun-abu-abu-belakang.webp', '2', 'belakang', '2026-11-22 22:03:27');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('9', '11', 'baju-03-katun-abu-abu-detail.webp', '3', 'detail', '2026-11-22 22:03:27');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('10', '12', 'baju-04-heavyweight-heather-gray-depan.webp', '1', 'depan', '2026-11-22 22:03:27');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('11', '12', 'baju-04-heavyweight-heather-gray-belakang.webp', '2', 'belakang', '2026-11-22 22:03:27');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('12', '12', 'baju-04-heavyweight-heather-gray-detail.webp', '3', 'detail', '2026-11-22 22:03:27');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('13', '13', 'kemeja-05-workwear-khaki-depan.webp', '1', 'depan', '2026-11-22 22:03:27');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('14', '13', 'kemeja-05-workwear-khaki-belakang.webp', '2', 'belakang', '2026-11-22 22:03:27');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('15', '14', 'baju-06-striped-navy-putih-depan.webp', '1', 'depan', '2026-11-22 22:03:27');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('16', '14', 'baju-06-striped-navy-putih-belakang.webp', '2', 'belakang', '2026-11-22 22:03:27');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('17', '14', 'baju-06-striped-navy-putih-detail.webp', '3', 'detail', '2026-11-22 22:03:27');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('18', '15', 'baju-07-polo-pique-navy-depan.webp', '1', 'depan', '2026-11-22 22:03:27');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('19', '15', 'baju-07-polo-pique-navy-belakang.webp', '2', 'belakang', '2026-11-22 22:03:27');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('20', '15', 'baju-07-polo-pique-navy-detail.webp', '3', 'detail', '2026-11-22 22:03:27');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('21', '16', 'baju-08-katun-olive-green-depan.webp', '1', 'depan', '2026-11-22 22:03:27');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('22', '16', 'baju-08-katun-olive-green-belakang.webp', '2', 'belakang', '2026-11-22 22:03:27');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('23', '16', 'baju-08-katun-olive-green-detail.webp', '3', 'detail', '2026-11-22 22:03:27');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('24', '17', 'baju-09-oversized-graphic-putih-depan.webp', '1', 'depan', '2026-11-22 22:03:27');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('25', '17', 'baju-09-oversized-graphic-putih-belakang.webp', '2', 'belakang', '2026-11-22 22:03:27');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('26', '17', 'baju-09-oversized-graphic-putih-detail.webp', '3', 'detail', '2026-11-22 22:03:27');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('27', '18', 'baju-10-polos-jet-black-depan.webp', '1', 'depan', '2026-11-22 22:03:27');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('28', '18', 'baju-10-polos-jet-black-belakang.webp', '2', 'belakang', '2026-11-22 22:03:27');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('29', '18', 'baju-10-polos-jet-black-detail.webp', '3', 'detail', '2026-11-22 22:03:27');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('30', '19', 'baju-11-polos-putih-classic-depan.webp', '1', 'depan', '2026-11-22 22:03:27');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('31', '19', 'baju-11-polos-putih-classic-belakang.webp', '2', 'belakang', '2026-11-22 22:03:27');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('32', '19', 'baju-11-polos-putih-classic-detail.webp', '3', 'detail', '2026-11-22 22:03:27');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('33', '20', 'kemeja-12-oxford-button-down-putih-depan.webp', '1', 'depan', '2026-11-22 22:03:27');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('34', '20', 'kemeja-12-oxford-button-down-putih-belakang.webp', '2', 'belakang', '2026-11-22 22:03:27');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('35', '20', 'kemeja-12-oxford-button-down-putih-detail.webp', '3', 'detail', '2026-11-22 22:03:27');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('36', '13', 'kemeja-05-workwear-khaki-detail.webp', '3', 'detail', '2026-11-22 23:30:12');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('37', '21', 'celana-13-denim-regular-classic-blue-depan.webp', '1', 'depan', '2026-11-22 23:30:12');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('38', '21', 'celana-13-denim-regular-classic-blue-belakang.webp', '2', 'belakang', '2026-11-22 23:30:12');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('39', '21', 'celana-13-denim-regular-classic-blue-detail.webp', '3', 'detail', '2026-11-22 23:30:12');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('40', '22', 'jacket-14-puffer-down-hitam-depan.webp', '1', 'depan', '2026-11-22 23:30:12');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('41', '22', 'jacket-14-puffer-down-hitam-belakang.webp', '2', 'belakang', '2026-11-22 23:30:12');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('42', '22', 'jacket-14-puffer-down-hitam-detail.webp', '3', 'detail', '2026-11-22 23:30:12');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('43', '23', 'celana-15-track-pants-hitam-depan.webp', '1', 'depan', '2026-11-22 23:30:12');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('44', '23', 'celana-15-track-pants-hitam-belakang.webp', '2', 'belakang', '2026-11-22 23:30:12');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('45', '23', 'celana-15-track-pants-hitam-detail.webp', '3', 'detail', '2026-11-22 23:30:12');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('46', '24', 'celana-16-chino-twill-hitam-depan.webp', '1', 'depan', '2026-11-22 23:30:12');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('47', '24', 'celana-16-chino-twill-hitam-belakang.webp', '2', 'belakang', '2026-11-22 23:30:12');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('48', '24', 'celana-16-chino-twill-hitam-detail.webp', '3', 'detail', '2026-11-22 23:30:12');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('49', '25', 'jacket-17-denim-trucker-washed-black-depan.webp', '1', 'depan', '2026-11-22 23:30:12');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('50', '25', 'jacket-17-denim-trucker-washed-black-belakang.webp', '2', 'belakang', '2026-11-22 23:30:12');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('51', '25', 'jacket-17-denim-trucker-washed-black-detail.webp', '3', 'detail', '2026-11-22 23:30:12');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('52', '26', 'celana-18-double-knee-canvas-cokelat-depan.webp', '1', 'depan', '2026-11-22 23:30:12');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('53', '26', 'celana-18-double-knee-canvas-cokelat-belakang.webp', '2', 'belakang', '2026-11-22 23:30:12');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('54', '26', 'celana-18-double-knee-canvas-cokelat-detail.webp', '3', 'detail', '2026-11-22 23:30:12');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('55', '27', 'jacket-19-chore-coat-canvas-cokelat-depan.webp', '1', 'depan', '2026-11-22 23:30:12');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('56', '27', 'jacket-19-chore-coat-canvas-cokelat-belakang.webp', '2', 'belakang', '2026-11-22 23:30:12');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('57', '27', 'jacket-19-chore-coat-canvas-cokelat-detail.webp', '3', 'detail', '2026-11-22 23:30:12');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('58', '28', 'jacket-20-fleece-sherpa-forest-green-depan.webp', '1', 'depan', '2026-11-22 23:30:12');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('59', '28', 'jacket-20-fleece-sherpa-forest-green-belakang.webp', '2', 'belakang', '2026-11-22 23:30:12');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('60', '28', 'jacket-20-fleece-sherpa-forest-green-detail.webp', '3', 'detail', '2026-11-22 23:30:12');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('61', '29', 'celana-21-straight-leg-khaki-depan.webp', '1', 'depan', '2026-11-22 23:30:12');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('62', '29', 'celana-21-straight-leg-khaki-belakang.webp', '2', 'belakang', '2026-11-22 23:30:12');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('63', '29', 'celana-21-straight-leg-khaki-detail.webp', '3', 'detail', '2026-11-22 23:30:12');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('64', '30', 'jacket-22-windbreaker-parasut-navy-depan.webp', '1', 'depan', '2026-11-22 23:30:12');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('65', '30', 'jacket-22-windbreaker-parasut-navy-belakang.webp', '2', 'belakang', '2026-11-22 23:30:12');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('66', '30', 'jacket-22-windbreaker-parasut-navy-detail.webp', '3', 'detail', '2026-11-22 23:30:12');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('67', '31', 'celana-23-cargo-ripstop-olive-green-depan.webp', '1', 'depan', '2026-11-22 23:30:12');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('68', '31', 'celana-23-cargo-ripstop-olive-green-belakang.webp', '2', 'belakang', '2026-11-22 23:30:12');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('69', '31', 'celana-23-cargo-ripstop-olive-green-detail.webp', '3', 'detail', '2026-11-22 23:30:12');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('70', '32', 'celana-24-denim-vintage-medium-wash-depan.webp', '1', 'depan', '2026-11-22 23:30:12');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('71', '32', 'celana-24-denim-vintage-medium-wash-belakang.webp', '2', 'belakang', '2026-11-22 23:30:12');
INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`, `type`, `created_at`) VALUES ('72', '32', 'celana-24-denim-vintage-medium-wash-detail.webp', '3', 'detail', '2026-11-22 23:30:12');

-- --------------------------------------------------------
-- Struktur tabel `products`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `category` varchar(50) NOT NULL,
  `brand` varchar(100) DEFAULT 'No Brand',
  `price` decimal(12,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 1,
  `condition` varchar(50) DEFAULT 'Baik',
  `condition_text` varchar(50) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `material` varchar(100) DEFAULT NULL,
  `size` varchar(50) DEFAULT NULL,
  `sku` varchar(50) DEFAULT NULL,
  `is_demo` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`),
  KEY `idx_product_category` (`category`),
  KEY `idx_product_status` (`is_demo`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data untuk tabel `products`
INSERT INTO `products` (`id`, `name`, `category`, `brand`, `price`, `stock`, `condition`, `condition_text`, `image`, `description`, `material`, `size`, `sku`, `is_demo`, `created_at`) VALUES ('9', 'Athletic Performance Tee', 'Baju', 'No Brand', '75000.00', '1', 'Seperti Baru', 'Seperti Baru', 'baju-01-athletic-performance-hitam-depan.webp', 'Kaos olahraga athletic berwarna hitam pekat berbahan polyester quick-dry yang ringan dan sejuk. Potongan raglan dengan jahitan flatlock rapi memberikan kenyamanan optimal saat bergerak. Kondisi sangat terawat tanpa noda maupun robek, elastisitas kain masih prima. Cocok untuk postur tubuh dengan estimasi ukuran L lokal.', 'Polyester Spandex', 'L', 'PL-001', '0', '2026-11-22 22:03:27');
INSERT INTO `products` (`id`, `name`, `category`, `brand`, `price`, `stock`, `condition`, `condition_text`, `image`, `description`, `material`, `size`, `sku`, `is_demo`, `created_at`) VALUES ('10', 'Blue Striped Short Shirt', 'Kemeja', 'No Brand', '110000.00', '1', 'Bagus', 'Bagus', 'kemeja-02-garis-biru-putih-depan.webp', 'Kemeja kasual motif salur garis vertikal biru muda dan putih dengan potongan santai yang rapi. Dilengkapi saku depan di dada kiri dan kancing depan lengkap terpasang kokoh. Bahan katun poplin adem dan nyaman digunakan seharian di cuaca tropis. Kondisi kain bersih terawat, pas untuk ukuran M pria atau wanita (unisex).', 'Katun Poplin', 'M', 'PL-002', '0', '2026-11-22 22:03:27');
INSERT INTO `products` (`id`, `name`, `category`, `brand`, `price`, `stock`, `condition`, `condition_text`, `image`, `description`, `material`, `size`, `sku`, `is_demo`, `created_at`) VALUES ('11', 'Basic Cotton Tee Grey', 'Baju', 'No Brand', '55000.00', '1', 'Ada Minus', 'Ada Minus', 'baju-03-katun-abu-abu-depan.webp', 'Kaos oblong basic esensial berwarna abu-abu terang netral dengan kerah crewneck bergaris rib lembut. Bahan katun combed 24s lembut dan menyerap keringat dengan baik untuk pakaian harian santai. Kondisi layak pakai dengan minus bulu halus wajar bekas pencucian tanpa lubang atau robekan. Disarankan untuk yang mencari kaos santai bersahabat ukuran L.', 'Katun Combed 24s', 'L', 'PL-003', '0', '2026-11-22 22:03:27');
INSERT INTO `products` (`id`, `name`, `category`, `brand`, `price`, `stock`, `condition`, `condition_text`, `image`, `description`, `material`, `size`, `sku`, `is_demo`, `created_at`) VALUES ('12', 'Heavyweight Boxy Tee', 'Baju', 'No Brand', '95000.00', '1', 'Bagus', 'Bagus', 'baju-04-heavyweight-heather-gray-depan.webp', 'Kaos berpotongan boxy fit modern berwarna abu-abu misty (heather gray) berbahan katun tebal 16s. Struktur kerah rib tebal dan siluet bahu turun (drop shoulder) memberikan kesan streetwear yang tegas dan kokoh. Warna kain solid dan jahitan rantai samping aman terjaga. Cocok untuk penyuka gaya oversized atau ukuran fit XL.', 'Katun Heavyweight 16s', 'XL', 'PL-004', '0', '2026-11-22 22:03:27');
INSERT INTO `products` (`id`, `name`, `category`, `brand`, `price`, `stock`, `condition`, `condition_text`, `image`, `description`, `material`, `size`, `sku`, `is_demo`, `created_at`) VALUES ('13', 'Safari Workshirt Khaki', 'Kemeja', 'No Brand', '125000.00', '1', 'Bagus', 'Bagus', 'kemeja-05-workwear-khaki-depan.webp', 'Kemeja bergaya utilitarian workwear berwarna cokelat khaki dengan dua saku dada berkancing dan detail lipatan fungsional. Menggunakan bahan katun drill bertekstur kuat namun tetap luwes dan tidak panas. Kondisi warna merata tanpa noda membandel dan seluruh kancing lengkap. Sangat cocok dipadukan sebagai outer santai dengan ukuran L.', 'Katun Drill Halus', 'L', 'PL-005', '0', '2026-11-22 22:03:27');
INSERT INTO `products` (`id`, `name`, `category`, `brand`, `price`, `stock`, `condition`, `condition_text`, `image`, `description`, `material`, `size`, `sku`, `is_demo`, `created_at`) VALUES ('14', 'Breton Striped Tee', 'Baju', 'No Brand', '70000.00', '1', 'Bagus', 'Bagus', 'baju-06-striped-navy-putih-depan.webp', 'Kaos motif garis horizontal navy dan putih bergaya nautical klasik dengan rajutan motif tenun yarn-dyed rapi. Bahan katun lembut dengan ketebalan pas memberikan sirkulasi udara yang baik saat beraktivitas harian. Kerah leher bulat dengan jahitan leher yang masih kencang dan tidak melar. Ukuran pas untuk berat badan proporsional size M.', 'Katun Yarn Dyed', 'M', 'PL-006', '0', '2026-11-22 22:03:27');
INSERT INTO `products` (`id`, `name`, `category`, `brand`, `price`, `stock`, `condition`, `condition_text`, `image`, `description`, `material`, `size`, `sku`, `is_demo`, `created_at`) VALUES ('15', 'Pique Polo Shirt', 'Baju', 'No Brand', '85000.00', '1', 'Bagus', 'Bagus', 'baju-07-polo-pique-navy-depan.webp', 'Kaos polo klasik berwarna biru dongker (navy) dengan tekstur rajut pique berpori yang sejuk dan menyerap keringat. Dilengkapi kerah kemeja kokoh bergaris tepi serta bukaan dua kancing mutiara rapi. Kondisi warna navy masih pekat dan jahitan manset lengan terawat baik. Pilihan sempurna untuk tampilan rapi semi-formal ukuran L.', 'Katun Pique Berpori', 'L', 'PL-007', '0', '2026-11-22 22:03:27');
INSERT INTO `products` (`id`, `name`, `category`, `brand`, `price`, `stock`, `condition`, `condition_text`, `image`, `description`, `material`, `size`, `sku`, `is_demo`, `created_at`) VALUES ('16', 'Olive Green Cotton Tee', 'Baju', 'No Brand', '60000.00', '1', 'Bagus', 'Bagus', 'baju-08-katun-olive-green-depan.webp', 'Kaos oblong bernuansa earth-tone warna hijau zaitun (olive green) berpotongan regular fit yang nyaman dipakai harian. Terbuat dari katun combed 30s sejuk, jatuh di badan, dan tidak menerawang saat dikenakan. Warna hijau army masih bersih merata tanpa bercak dengan kondisi jahitan ujung pakaian aman. Sangat cocok untuk postur ukuran M santai.', 'Katun Combed 30s', 'M', 'PL-008', '0', '2026-11-22 22:03:27');
INSERT INTO `products` (`id`, `name`, `category`, `brand`, `price`, `stock`, `condition`, `condition_text`, `image`, `description`, `material`, `size`, `sku`, `is_demo`, `created_at`) VALUES ('17', 'Graphic Oversized Tee', 'Baju', 'No Brand', '80000.00', '1', 'Seperti Baru', 'Seperti Baru', 'baju-09-oversized-graphic-putih-depan.webp', 'Kaos streetwear berpotongan oversized warna putih bersih dengan aksen tipografi grafis minimalis di area dada kiri. Bahan katun combed 20s tebal, halus, dan tidak tembus pandang dengan sensasi jatuh yang mantap. Kondisi sablon mulus tanpa retak dan kerah leher masih sangat rapat kencang. Pas bagi yang menyukai tampilan santai loose fit ukuran L.', 'Katun Combed 20s', 'L', 'PL-009', '0', '2026-11-22 22:03:27');
INSERT INTO `products` (`id`, `name`, `category`, `brand`, `price`, `stock`, `condition`, `condition_text`, `image`, `description`, `material`, `size`, `sku`, `is_demo`, `created_at`) VALUES ('18', 'Jet Black Classic Tee', 'Baju', 'No Brand', '60000.00', '1', 'Bagus', 'Bagus', 'baju-10-polos-jet-black-depan.webp', 'Kaos oblong hitam pekat (jet black) esensial serbaguna dengan potongan regular fit yang bersih dan simpel. Menggunakan bahan katun combed 24s yang lembut di kulit, menyerap keringat dengan baik, dan nyaman untuk cuaca panas. Kondisi warna hitam masih pekat tanpa pudar di kerah maupun ketiak. Pilihan pakaian harian yang awet untuk ukuran M.', 'Katun Combed 24s', 'M', 'PL-010', '0', '2026-11-22 22:03:27');
INSERT INTO `products` (`id`, `name`, `category`, `brand`, `price`, `stock`, `condition`, `condition_text`, `image`, `description`, `material`, `size`, `sku`, `is_demo`, `created_at`) VALUES ('19', 'Classic White Cotton Tee', 'Baju', 'No Brand', '50000.00', '2', 'Bagus', 'Bagus', 'baju-11-polos-putih-classic-depan.webp', 'Kaos polos putih bersih klasik model crewneck yang wajib ada dalam lemari pakaian untuk inner kemeja maupun dipakai langsung. Terbuat dari katun combed 30s yang ringan, adem, dan menyerap keringat dengan sempurna. Kondisi kain putih terjaga tanpa noda kuning dan jahitan benang rantai samping aman. Direkomendasikan untuk ukuran M.', 'Katun Combed 30s', 'M', 'PL-011', '0', '2026-11-22 22:03:27');
INSERT INTO `products` (`id`, `name`, `category`, `brand`, `price`, `stock`, `condition`, `condition_text`, `image`, `description`, `material`, `size`, `sku`, `is_demo`, `created_at`) VALUES ('20', 'Oxford Button-Down Shirt', 'Kemeja', 'No Brand', '115000.00', '0', 'Seperti Baru', 'Seperti Baru', 'kemeja-12-oxford-button-down-putih-depan.webp', 'Kemeja lengan panjang berkerah button-down berwarna putih bersih dari bahan katun oxford bertekstur tenun khas yang kokoh. Potongan rapi dengan saku dada dan manset lengan yang bisa dilipat rapi untuk gaya smart-casual maupun formal. Kondisi seperti baru, warna putih cerah sempurna dan kancing kerah masih utuh kencang. Pas untuk ukuran tubuh L.', 'Katun Oxford Berkualitas', 'L', 'PL-012', '0', '2026-11-22 22:03:27');
INSERT INTO `products` (`id`, `name`, `category`, `brand`, `price`, `stock`, `condition`, `condition_text`, `image`, `description`, `material`, `size`, `sku`, `is_demo`, `created_at`) VALUES ('21', 'Classic Blue Jeans', 'Celana', 'No Brand', '140000.00', '1', 'Bagus', 'Bagus', 'celana-13-denim-regular-classic-blue-depan.webp', 'Celana panjang denim klasik berwarna biru indigo cerah dengan potongan regular leg yang abadi dan nyaman dipakai harian. Bahan denim katun 13 oz yang kokoh dengan resleting tembaga dan jahitan kontras warna kuning khas vintage. Kondisi kain sangat kuat, tidak ada robekan atau sobekan tidak wajar di selangkangan. Cocok untuk lingkar pinggang size 32.', 'Denim Katun Non-Stretch 13 oz', '32', 'PL-013', '0', '2026-11-22 23:30:12');
INSERT INTO `products` (`id`, `name`, `category`, `brand`, `price`, `stock`, `condition`, `condition_text`, `image`, `description`, `material`, `size`, `sku`, `is_demo`, `created_at`) VALUES ('22', 'Down Puffer Jacket', 'Jacket', 'No Brand', '185000.00', '1', 'Bagus', 'Bagus', 'jacket-14-puffer-down-hitam-depan.webp', 'Jaket tebal model puffer berwarna hitam pekat dengan jahitan sekat bantal (quilted) yang empuk dan menghangatkan tubuh. Dilengkapi resleting utama yang kokoh, kerah tinggi pelindung angin, serta dua saku samping beritsleting fungsional. Lapisan dalam nilon halus tidak gatal dan dakron masih mengembang merata. Pas untuk ukuran badan L saat traveling atau berkendara.', 'Nilon Ripstop & Dakron Sintetis', 'L', 'PL-014', '0', '2026-11-22 23:30:12');
INSERT INTO `products` (`id`, `name`, `category`, `brand`, `price`, `stock`, `condition`, `condition_text`, `image`, `description`, `material`, `size`, `sku`, `is_demo`, `created_at`) VALUES ('23', 'Side Stripe Track Pants', 'Celana', 'No Brand', '95000.00', '1', 'Bagus', 'Bagus', 'celana-15-track-pants-hitam-depan.webp', 'Celana olahraga santai warna hitam dengan aksen garis setrip samping putih bergaya track pants retro streetwear. Menggunakan pinggang karet elastis berkolor tarik yang fleksibel serta bukaan resleting di pergelangan kaki bawah. Bahan polyester tricot licin dan lentur memudahkan pergerakan olahraga maupun hangout santai. Cocok untuk lingkar pinggang M (29-31).', 'Polyester Tricot', 'M', 'PL-015', '0', '2026-11-22 23:30:12');
INSERT INTO `products` (`id`, `name`, `category`, `brand`, `price`, `stock`, `condition`, `condition_text`, `image`, `description`, `material`, `size`, `sku`, `is_demo`, `created_at`) VALUES ('24', 'Straight Chino Pants', 'Celana', 'No Brand', '120000.00', '1', 'Bagus', 'Bagus', 'celana-16-chino-twill-hitam-depan.webp', 'Celana panjang kerja model chino berwarna hitam pekat dengan potongan pipa lurus (straight cut) yang kokoh dan tahan lama. Kain twill tebal berdaya tahan tinggi tidak mudah kusut, dilengkapi saku belakang model welt dan pengait kancing kokoh. Kondisi warna hitam masih pekat tanpa aus di bagian lutut. Pilihan tepat untuk aktivitas harian santai maupun kerja ukuran 32.', 'Katun Poly Twill Tebal', '32', 'PL-016', '0', '2026-11-22 23:30:12');
INSERT INTO `products` (`id`, `name`, `category`, `brand`, `price`, `stock`, `condition`, `condition_text`, `image`, `description`, `material`, `size`, `sku`, `is_demo`, `created_at`) VALUES ('25', 'Washed Denim Jacket', 'Jacket', 'No Brand', '175000.00', '1', 'Bagus', 'Bagus', 'jacket-17-denim-trucker-washed-black-depan.webp', 'Jaket denim model trucker legendaris dengan efek washed hitam pudar bernuansa vintage grunge yang berkarakter. Memiliki saku dada ganda dengan penutup lidah kancing logam dan tali pengatur lingkar pinggang di bagian belakang. Bahan denim tebal dan berbobot mantap saat dikenakan. Kondisi jahitan utuh tanpa cacat lubang, pas untuk postur ukuran L.', 'Denim Katun Heavyweight', 'L', 'PL-017', '0', '2026-11-22 23:30:12');
INSERT INTO `products` (`id`, `name`, `category`, `brand`, `price`, `stock`, `condition`, `condition_text`, `image`, `description`, `material`, `size`, `sku`, `is_demo`, `created_at`) VALUES ('26', 'Double Knee Work Pants', 'Celana', 'No Brand', '150000.00', '1', 'Bagus', 'Bagus', 'celana-18-double-knee-canvas-cokelat-depan.webp', 'Celana tukang workwear berbahan duck canvas warna cokelat karamel dengan lapisan ganda di bagian lutut (double knee panel) dan paku rivet tembaga. Dilengkapi saku samping utilitarian untuk peralatan serta gantungan palu khas pakaian kerja lapangan. Bahan tebal tahan gesekan dengan fading natural yang estetik. Pas untuk ukuran lingkar pinggang 32.', 'Duck Canvas Katun Berat', '32', 'PL-018', '0', '2026-11-22 23:30:12');
INSERT INTO `products` (`id`, `name`, `category`, `brand`, `price`, `stock`, `condition`, `condition_text`, `image`, `description`, `material`, `size`, `sku`, `is_demo`, `created_at`) VALUES ('27', 'Duck Canvas Chore Coat', 'Jacket', 'No Brand', '210000.00', '1', 'Seperti Baru', 'Seperti Baru', 'jacket-19-chore-coat-canvas-cokelat-depan.webp', 'Jaket chore coat gaya pekerja lapangan berbahan duck canvas warna cokelat tan dengan sentuhan kerah korduroi cokelat gelap kontras. Memiliki empat saku tempel fungsional di bagian depan serta kancing logam berdaya tahan tinggi. Bahan sangat kokoh dan memberikan perlindungan maksimal dari angin. Pilihan mantel luar berkarakter tebal untuk ukuran XL.', 'Duck Canvas Katun & Korduroi', 'XL', 'PL-019', '0', '2026-11-22 23:30:12');
INSERT INTO `products` (`id`, `name`, `category`, `brand`, `price`, `stock`, `condition`, `condition_text`, `image`, `description`, `material`, `size`, `sku`, `is_demo`, `created_at`) VALUES ('28', 'Sherpa Fleece Jacket', 'Jacket', 'No Brand', '165000.00', '1', 'Bagus', 'Bagus', 'jacket-20-fleece-sherpa-forest-green-depan.webp', 'Jaket bulu fleece hangat berwarna hijau lumut rimba (forest green) dengan aksen saku dada berkancing warna kontras. Tekstur sherpa fleece yang empuk dan tebal mampu menjaga suhu tubuh tetap nyaman di dataran tinggi atau ruangan ber-AC. Dilengkapi resleting leher penuh dan manset rib elastis pada lengan. Kondisi bulu masih mengembang rapi untuk ukuran L.', 'Polyester Fleece Bulu Lembut', 'L', 'PL-020', '0', '2026-11-22 23:30:12');
INSERT INTO `products` (`id`, `name`, `category`, `brand`, `price`, `stock`, `condition`, `condition_text`, `image`, `description`, `material`, `size`, `sku`, `is_demo`, `created_at`) VALUES ('29', 'Straight Leg Khaki Chino', 'Celana', 'No Brand', '115000.00', '1', 'Bagus', 'Bagus', 'celana-21-straight-leg-khaki-depan.webp', 'Celana panjang kerja model klasik berwarna khaki krem dengan potongan pipa lurus yang leluasa untuk aktivitas harian. Bahan katun twill kokoh tahan lama dengan lipatan garis depan yang rapi serta jahitan benang ganda pada bagian samping. Kondisi resleting dan kancing berfungsi sempurna tanpa kendala. Sangat pas untuk ukuran lingkar pinggang 30.', 'Katun Twill Drill', '30', 'PL-021', '0', '2026-11-22 23:30:12');
INSERT INTO `products` (`id`, `name`, `category`, `brand`, `price`, `stock`, `condition`, `condition_text`, `image`, `description`, `material`, `size`, `sku`, `is_demo`, `created_at`) VALUES ('30', 'Navy Windbreaker Jacket', 'Jacket', 'No Brand', '135000.00', '1', 'Seperti Baru', 'Seperti Baru', 'jacket-22-windbreaker-parasut-navy-depan.webp', 'Jaket windbreaker bertudung (hoodie) warna biru dongker (navy) berbahan parasut nilon yang ringan, tahan angin, dan cepat kering. Memiliki resleting depan tahan air, karet pergelangan elastis kencang, serta tali serut di bagian bawah jaket. Sangat praktis dilipat kecil untuk kebutuhan olahraga lari maupun berkendara motor. Cocok untuk ukuran M.', 'Parasut Nilon Tahan Angin', 'M', 'PL-022', '0', '2026-11-22 23:30:12');
INSERT INTO `products` (`id`, `name`, `category`, `brand`, `price`, `stock`, `condition`, `condition_text`, `image`, `description`, `material`, `size`, `sku`, `is_demo`, `created_at`) VALUES ('31', 'Utility Cargo Pants', 'Celana', 'No Brand', '145000.00', '1', 'Bagus', 'Bagus', 'celana-23-cargo-ripstop-olive-green-depan.webp', 'Celana kargo utilitarian warna hijau army bertekstur tenun kotak ripstop yang kuat terhadap robekan dan gesekan benda tajam. Memiliki total 6 saku fungsional dengan penutup lidah kancing aman untuk menyimpan dompet, gawai, dan perlengkapan harian. Kondisi warna hijau masih terjaga merata tanpa noda cat atau karat. Pas untuk lingkar pinggang 32.', 'Katun Ripstop Kotak Tahan Robek', '32', 'PL-023', '0', '2026-11-22 23:30:12');
INSERT INTO `products` (`id`, `name`, `category`, `brand`, `price`, `stock`, `condition`, `condition_text`, `image`, `description`, `material`, `size`, `sku`, `is_demo`, `created_at`) VALUES ('32', 'Vintage Medium Wash Jeans', 'Celana', 'No Brand', '155000.00', '1', 'Bagus', 'Bagus', 'celana-24-denim-vintage-medium-wash-depan.webp', 'Celana denim bergaya vintage tahun 90-an dengan warna medium blue wash dan aksen fading whisker alami di paha depan. Menggunakan bahan katun denim 14 oz tebal tanpa campuran stretch untuk mempertahankan siluet jatuh pipa lurus yang maskulin. Jahitan bar-tack di saku aman dan resleting depan lancar. Pilihan sempurna untuk lingkar pinggang 34.', 'Denim Katun 14 oz Vintage Wash', '34', 'PL-024', '0', '2026-11-22 23:30:12');

-- --------------------------------------------------------
-- Struktur tabel `riwayat_status`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `riwayat_status`;
CREATE TABLE `riwayat_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `jenis` enum('pesanan','pembayaran') NOT NULL,
  `status_lama` varchar(50) NOT NULL,
  `status_baru` varchar(50) NOT NULL,
  `catatan` text DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_rs_order` (`order_id`),
  KEY `idx_rs_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data untuk tabel `riwayat_status`
INSERT INTO `riwayat_status` (`id`, `order_id`, `jenis`, `status_lama`, `status_baru`, `catatan`, `admin_id`, `created_at`) VALUES ('10', '11', 'pesanan', 'Menunggu Pembayaran', 'Diproses', '', '1', '2026-11-23 00:56:46');
INSERT INTO `riwayat_status` (`id`, `order_id`, `jenis`, `status_lama`, `status_baru`, `catatan`, `admin_id`, `created_at`) VALUES ('11', '12', 'pembayaran', 'Menunggu Pembayaran', 'Dibayar', '', '1', '2026-11-24 09:35:50');
INSERT INTO `riwayat_status` (`id`, `order_id`, `jenis`, `status_lama`, `status_baru`, `catatan`, `admin_id`, `created_at`) VALUES ('12', '12', 'pesanan', 'Menunggu Pembayaran', 'Diproses', '', '1', '2026-11-24 09:35:50');
INSERT INTO `riwayat_status` (`id`, `order_id`, `jenis`, `status_lama`, `status_baru`, `catatan`, `admin_id`, `created_at`) VALUES ('13', '12', 'pembayaran', 'Dibayar', 'Selesai', '', '1', '2026-11-24 09:36:47');
INSERT INTO `riwayat_status` (`id`, `order_id`, `jenis`, `status_lama`, `status_baru`, `catatan`, `admin_id`, `created_at`) VALUES ('14', '12', 'pesanan', 'Diproses', 'Selesai', '', '1', '2026-11-24 09:36:47');
INSERT INTO `riwayat_status` (`id`, `order_id`, `jenis`, `status_lama`, `status_baru`, `catatan`, `admin_id`, `created_at`) VALUES ('15', '11', 'pembayaran', 'Menunggu Pembayaran', 'Selesai', '', '1', '2026-11-24 09:37:12');
INSERT INTO `riwayat_status` (`id`, `order_id`, `jenis`, `status_lama`, `status_baru`, `catatan`, `admin_id`, `created_at`) VALUES ('16', '11', 'pesanan', 'Diproses', 'Selesai', '', '1', '2026-11-24 09:37:12');
INSERT INTO `riwayat_status` (`id`, `order_id`, `jenis`, `status_lama`, `status_baru`, `catatan`, `admin_id`, `created_at`) VALUES ('19', '6', 'pesanan', 'Menunggu Pembayaran', 'Dibatalkan', 'Kupon: DISKON50 (-Rp 25.000)', '1', '2026-11-24 09:37:39');
INSERT INTO `riwayat_status` (`id`, `order_id`, `jenis`, `status_lama`, `status_baru`, `catatan`, `admin_id`, `created_at`) VALUES ('22', '6', 'pembayaran', 'Menunggu Pembayaran', 'Dibatalkan', '', '1', '2026-11-24 09:38:12');
INSERT INTO `riwayat_status` (`id`, `order_id`, `jenis`, `status_lama`, `status_baru`, `catatan`, `admin_id`, `created_at`) VALUES ('23', '4', 'pembayaran', 'Menunggu Pembayaran', 'Selesai', '', '1', '2026-11-24 09:38:30');
INSERT INTO `riwayat_status` (`id`, `order_id`, `jenis`, `status_lama`, `status_baru`, `catatan`, `admin_id`, `created_at`) VALUES ('24', '4', 'pesanan', 'Menunggu Pembayaran', 'Selesai', 'Kupon: DISKON50 (-Rp 152.500)', '1', '2026-11-24 09:38:30');
INSERT INTO `riwayat_status` (`id`, `order_id`, `jenis`, `status_lama`, `status_baru`, `catatan`, `admin_id`, `created_at`) VALUES ('25', '1', 'pembayaran', 'Menunggu Pembayaran', 'Selesai', '', '1', '2026-11-24 09:38:42');
INSERT INTO `riwayat_status` (`id`, `order_id`, `jenis`, `status_lama`, `status_baru`, `catatan`, `admin_id`, `created_at`) VALUES ('26', '1', 'pesanan', 'Menunggu Pembayaran', 'Selesai', '', '1', '2026-11-24 09:38:42');

-- --------------------------------------------------------
-- Struktur tabel `riwayat_stok`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `riwayat_stok`;
CREATE TABLE `riwayat_stok` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `perubahan` int(11) NOT NULL,
  `stok_sebelum` int(11) NOT NULL,
  `stok_sesudah` int(11) NOT NULL,
  `alasan` varchar(100) NOT NULL,
  `referensi_order_id` int(11) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_rstok_product` (`product_id`),
  KEY `idx_rstok_order` (`referensi_order_id`),
  KEY `idx_rstok_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data untuk tabel `riwayat_stok`
INSERT INTO `riwayat_stok` (`id`, `product_id`, `perubahan`, `stok_sebelum`, `stok_sesudah`, `alasan`, `referensi_order_id`, `admin_id`, `created_at`) VALUES ('3', '9', '-1', '1', '0', '[order_out] Checkout pesanan #ORD-20261123-B4C3E0', NULL, '2', '2026-11-23 00:31:48');
INSERT INTO `riwayat_stok` (`id`, `product_id`, `perubahan`, `stok_sebelum`, `stok_sesudah`, `alasan`, `referensi_order_id`, `admin_id`, `created_at`) VALUES ('4', '10', '-1', '1', '0', '[order_out] Checkout pesanan #ORD-20261123-8405DA', NULL, '2', '2026-11-23 00:32:21');
INSERT INTO `riwayat_stok` (`id`, `product_id`, `perubahan`, `stok_sebelum`, `stok_sesudah`, `alasan`, `referensi_order_id`, `admin_id`, `created_at`) VALUES ('5', '11', '-1', '1', '0', '[order_out] Checkout pesanan #ORD-20261123-3D88F9', NULL, '2', '2026-11-23 00:32:56');
INSERT INTO `riwayat_stok` (`id`, `product_id`, `perubahan`, `stok_sebelum`, `stok_sesudah`, `alasan`, `referensi_order_id`, `admin_id`, `created_at`) VALUES ('6', '11', '1', '0', '1', '[admin_adjust] Rollback pasca test simulasi', NULL, '1', '2026-11-23 00:32:57');
INSERT INTO `riwayat_stok` (`id`, `product_id`, `perubahan`, `stok_sebelum`, `stok_sesudah`, `alasan`, `referensi_order_id`, `admin_id`, `created_at`) VALUES ('7', '10', '1', '0', '1', 'Koreksi Admin', NULL, '1', '2026-11-23 00:44:12');
INSERT INTO `riwayat_stok` (`id`, `product_id`, `perubahan`, `stok_sebelum`, `stok_sesudah`, `alasan`, `referensi_order_id`, `admin_id`, `created_at`) VALUES ('8', '20', '1', '0', '1', 'Koreksi Admin', NULL, '1', '2026-11-23 01:03:14');
INSERT INTO `riwayat_stok` (`id`, `product_id`, `perubahan`, `stok_sebelum`, `stok_sesudah`, `alasan`, `referensi_order_id`, `admin_id`, `created_at`) VALUES ('9', '9', '1', '0', '1', 'Koreksi Admin', NULL, '1', '2026-11-23 01:03:18');
INSERT INTO `riwayat_stok` (`id`, `product_id`, `perubahan`, `stok_sebelum`, `stok_sesudah`, `alasan`, `referensi_order_id`, `admin_id`, `created_at`) VALUES ('10', '9', '-1', '1', '0', 'Pesanan ORD-20261123-B4C3E0 (Selesai)', '8', '1', '2026-11-24 09:37:24');
INSERT INTO `riwayat_stok` (`id`, `product_id`, `perubahan`, `stok_sebelum`, `stok_sesudah`, `alasan`, `referensi_order_id`, `admin_id`, `created_at`) VALUES ('11', '19', '1', '1', '2', 'Pembatalan Pesanan SS-20261122570654', '6', '1', '2026-11-24 09:37:39');
INSERT INTO `riwayat_stok` (`id`, `product_id`, `perubahan`, `stok_sebelum`, `stok_sesudah`, `alasan`, `referensi_order_id`, `admin_id`, `created_at`) VALUES ('14', '35', '1', '1', '2', 'Stok Awal Penambahan Produk', NULL, '1', '2026-11-24 10:56:33');
INSERT INTO `riwayat_stok` (`id`, `product_id`, `perubahan`, `stok_sebelum`, `stok_sesudah`, `alasan`, `referensi_order_id`, `admin_id`, `created_at`) VALUES ('16', '9', '1', '0', '1', 'Rollback Pesanan Uji Coba #8,9', NULL, '1', '2026-11-24 16:05:07');

-- --------------------------------------------------------
-- Struktur tabel `users`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) DEFAULT 'user',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data untuk tabel `users`
INSERT INTO `users` (`id`, `name`, `username`, `password`, `role`, `created_at`) VALUES ('1', 'Administrator', 'admin', '$2y$10$YkqAyZcxfTpxnJOFhm8iB.l0wDTggUYhc9rSOC50L1lv8sByBHZPS', 'admin', '2026-11-18 10:20:11');
INSERT INTO `users` (`id`, `name`, `username`, `password`, `role`, `created_at`) VALUES ('2', 'ikhsan', 'ikhsan123', '$2y$12$PN8DB3Uj/P5tYOem52F.t.ii.J2XD9vhGKmJJ9z4lc3ZUGA4Ux0su', 'user', '2026-11-18 14:57:07');
INSERT INTO `users` (`id`, `name`, `username`, `password`, `role`, `created_at`) VALUES ('7', 'demo', 'demo123', '$2y$12$4fdkRUGuWW7ElJDI/tc0mu5yevjjIFTc.3QX4sad279EeUiZh3lxe', 'user', '2026-11-24 09:29:03');

SET FOREIGN_KEY_CHECKS=1;
COMMIT;
