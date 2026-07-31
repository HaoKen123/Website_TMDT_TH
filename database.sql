-- phpMyAdmin SQL Dump
-- Database: `pixelgear_shop`

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+07:00";

--
-- Database for Awardspace
--
-- Do not include CREATE DATABASE or USE statements for shared hosting

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `old_price` decimal(10,2) DEFAULT NULL,
  `badge` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category`, `name`, `image_url`, `price`, `old_price`, `badge`, `description`) VALUES
(1, 'accessories', 'Minecraft Enderman Youth Baseball Hat', 'https://images.unsplash.com/photo-1588850561407-ed78c282e89b?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80', '19.95', NULL, 'Best Seller', 'Mũ lưỡi trai Minecraft Enderman thêu họa tiết mắt Enderman phát sáng huyền bí.'),
(2, 'clothing', 'Minecraft Enderman Eyes Unisex Short Sleeve T-Shirt', 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80', '22.95', NULL, 'Best Seller', 'Áo thun đen in hình đôi mắt Enderman màu tím đặc trưng, chất liệu cotton 100% thoáng mát.'),
(3, 'clothing', 'Minecraft Cat Kids Pullover Hoodie', 'https://images.unsplash.com/photo-1556905055-8f358a7a47b2?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80', '34.95', NULL, 'Mới', 'Áo nỉ có mũ trẻ em màu xám in hình chú mèo Minecraft ngộ nghĩnh.'),
(4, 'clothing', 'Minecraft Fox Adult Pullover Hoodie', 'https://images.unsplash.com/photo-1605901309584-818e25960b8f?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80', '49.95', '65.00', 'Giảm giá', 'Áo hoodie người lớn tông màu cam đất phối họa tiết Cáo Minecraft độc đáo.'),
(5, 'accessories', 'Minecraft Creeper Face Youth Backpack', 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80', '39.95', NULL, 'Hot', 'Balo học sinh chống nước in hình khuôn mặt Creeper huyền thoại.'),
(6, 'accessories', 'Minecraft Light-Up Wall Torch Light', 'https://images.unsplash.com/photo-1587573089734-09cb69c0f2b4?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80', '29.95', NULL, 'Best Seller', 'Đèn ngọn đuốc Minecraft treo tường tích hợp pin sạc tiện lợi.'),
(7, 'toys', 'Minecraft Axolotl Plush Toy 12-inch', 'https://images.unsplash.com/photo-1610041321427-0cf0a58ebbc1?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80', '19.95', NULL, 'Hot', 'Thú nhồi bông Kì Giông Axolotl màu hồng mềm mại chuẩn Minecraft Official.'),
(8, 'toys', 'Minecraft Ender Dragon Plush Toy 15-inch', 'https://images.unsplash.com/photo-1563245372-f21724e3856d?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80', '24.95', NULL, 'Mới', 'Gấu bông Rồng Ender thần thoại dành cho các nhà sưu tầm game thủ.');

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fullname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `avatar_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_phone` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Chưa thanh toán',
  `status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Chờ xác nhận',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `admins` (`username`, `password`) VALUES ('admin', 'admin');

COMMIT;
