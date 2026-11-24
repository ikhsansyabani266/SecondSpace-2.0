-- SecondSpace PreLoved Database Migration Script
-- Database: secondspace_preloved
-- Character Set: utf8mb4 / Collation: utf8mb4_unicode_ci
-- Safe, non-destructive migration script to be run in phpMyAdmin

CREATE DATABASE IF NOT EXISTS `secondspace_preloved` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `secondspace_preloved`;

-- 1. Table Users
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` VARCHAR(20) DEFAULT 'user',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Table Products
CREATE TABLE IF NOT EXISTS `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `category` VARCHAR(50) NOT NULL,
    `price` DECIMAL(12,2) NOT NULL,
    `stock` INT NOT NULL DEFAULT 1,
    `condition` VARCHAR(50) DEFAULT 'Baik',
    `image` VARCHAR(255) NULL,
    `description` TEXT NULL,
    `material` VARCHAR(100) NULL,
    `size` VARCHAR(50) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Table Addresses
CREATE TABLE IF NOT EXISTS `addresses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `recipient_name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `address` TEXT NOT NULL,
    `province` VARCHAR(100) NULL,
    `city` VARCHAR(100) NOT NULL,
    `postal_code` VARCHAR(20) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sync columns for existing addresses table
ALTER TABLE `addresses` ADD COLUMN IF NOT EXISTS `province` VARCHAR(100) NULL AFTER `address`;
ALTER TABLE `addresses` ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- 4. Table Orders
CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `order_code` VARCHAR(50) NULL,
    `order_number` VARCHAR(50) NULL,
    `address_id` INT NULL,
    `subtotal` DECIMAL(12,2) NULL,
    `shipping` DECIMAL(12,2) NULL,
    `total` DECIMAL(12,2) NULL,
    `total_price` DECIMAL(12,2) NULL,
    `payment_method` VARCHAR(50) NULL,
    `payment_status` VARCHAR(50) DEFAULT 'Menunggu Pembayaran',
    `order_status` VARCHAR(50) DEFAULT 'Menunggu Pembayaran',
    `status` VARCHAR(50) DEFAULT 'Menunggu Pembayaran',
    `notes` TEXT NULL,
    `shipping_courier` VARCHAR(50) NULL,
    `tracking_number` VARCHAR(100) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sync columns for existing orders table
ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `order_code` VARCHAR(50) NULL AFTER `user_id`;
ALTER TABLE `orders` MODIFY COLUMN `order_number` VARCHAR(50) NULL;
ALTER TABLE `orders` MODIFY COLUMN `total_price` DECIMAL(12,2) NULL;
ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `discount_code` VARCHAR(50) NULL AFTER `shipping`;
ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `discount_amount` DECIMAL(12,2) DEFAULT 0 AFTER `discount_code`;

-- 5. Table Order Items
CREATE TABLE IF NOT EXISTS `order_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `product_name` VARCHAR(255) NULL,
    `price` DECIMAL(12,2) NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `subtotal` DECIMAL(12,2) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Table Contacts
CREATE TABLE IF NOT EXISTS `contacts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `message` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Default Admin if not exists
INSERT INTO `users` (`name`, `username`, `password`, `role`)
SELECT 'Administrator', 'admin', '$2y$10$wT8K4D4qM7C4G1Qz8u5L/.M8sJ2L6N1gK2W7u7Q1X4O7G0H2Y6J8e', 'admin'
WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE `username` = 'admin');
