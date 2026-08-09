-- phpMyAdmin SQL Dump
-- Database: `pixelgear_shop`

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+07:00";

--
-- Table structure for table `categories`
--
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL UNIQUE,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categories` (`id`, `name`, `slug`, `status`) VALUES
(1, 'Quần áo & Hoodies', 'clothing', 1),
(2, 'Phụ kiện Minecraft', 'accessories', 1),
(3, 'Đồ chơi & Gấu bông', 'toys', 1),
(4, 'Đèn & Vật dụng', 'decor', 1);

--
-- Table structure for table `products`
--
CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `old_price` decimal(10,2) DEFAULT NULL,
  `badge` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `stock` int(11) NOT NULL DEFAULT 50,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `products` (`id`, `category`, `name`, `image_url`, `price`, `old_price`, `badge`, `description`, `stock`, `status`) VALUES
(1, 'accessories', 'Minecraft Enderman Youth Baseball Hat', 'https://images.unsplash.com/photo-1588850561407-ed78c282e89b?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80', '19.95', NULL, 'Best Seller', 'Mũ lưỡi trai Minecraft Enderman thêu họa tiết mắt Enderman phát sáng huyền bí.', 50, 1),
(2, 'clothing', 'Minecraft Enderman Eyes Unisex Short Sleeve T-Shirt', 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80', '22.95', NULL, 'Best Seller', 'Áo thun đen in hình đôi mắt Enderman màu tím đặc trưng, chất liệu cotton 100% thoáng mát.', 35, 1),
(3, 'clothing', 'Minecraft Cat Kids Pullover Hoodie', 'https://images.unsplash.com/photo-1556905055-8f358a7a47b2?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80', '34.95', NULL, 'Mới', 'Áo nỉ có mũ trẻ em màu xám in hình chú mèo Minecraft ngộ nghĩnh.', 20, 1),
(4, 'clothing', 'Minecraft Fox Adult Pullover Hoodie', 'https://images.unsplash.com/photo-1605901309584-818e25960b8f?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80', '49.95', '65.00', 'Giảm giá', 'Áo hoodie người lớn tông màu cam đất phối họa tiết Cáo Minecraft độc đáo.', 15, 1),
(5, 'accessories', 'Minecraft Creeper Face Youth Backpack', 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80', '39.95', NULL, 'Hot', 'Balo học sinh chống nước in hình khuôn mặt Creeper huyền thoại.', 40, 1),
(6, 'accessories', 'Minecraft Light-Up Wall Torch Light', 'https://images.unsplash.com/photo-1587573089734-09cb69c0f2b4?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80', '29.95', NULL, 'Best Seller', 'Đèn ngọn đuốc Minecraft treo tường tích hợp pin sạc tiện lợi.', 60, 1),
(7, 'toys', 'Minecraft Axolotl Plush Toy 12-inch', 'https://images.unsplash.com/photo-1610041321427-0cf0a58ebbc1?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80', '19.95', NULL, 'Hot', 'Thú nhồi bông Kì Giông Axolotl màu hồng mềm mại chuẩn Minecraft Official.', 25, 1),
(8, 'toys', 'Minecraft Ender Dragon Plush Toy 15-inch', 'https://images.unsplash.com/photo-1563245372-f21724e3856d?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80', '24.95', NULL, 'Mới', 'Gấu bông Rồng Ender thần thoại dành cho các nhà sưu tầm game thủ.', 18, 1);

--
-- Table structure for table `users`
--
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fullname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `reset_otp` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reset_expiry` datetime DEFAULT NULL,
  `avatar_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1: Active, 0: Blocked',
  `role` varchar(20) NOT NULL DEFAULT 'customer' COMMENT 'customer, staff, admin',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `comments`
--
CREATE TABLE IF NOT EXISTS `comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rating` int(11) DEFAULT 5,
  `comment` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'approved',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `comments` (`id`, `product_id`, `user_name`, `rating`, `comment`, `status`) VALUES
(1, 1, 'Nguyễn Văn An', 5, 'Mũ lưỡi trai Minecraft rất đẹp, vải mịn, thêu sắc nét!', 'approved'),
(2, 2, 'Trần Thị Bích', 5, 'Áo thun cotton thoáng mát, in hình Enderman cực chất.', 'approved'),
(3, 5, 'Lê Hoàng Cường', 4, 'Balo chắc chắn, chống nước tốt, bé nhà mình rất thích.', 'approved');

--
-- Table structure for table `shipping_fees`
--
CREATE TABLE IF NOT EXISTS `shipping_fees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `province` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL UNIQUE,
  `fee` decimal(10,2) NOT NULL DEFAULT 30000.00,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `shipping_fees` (`id`, `province`, `fee`) VALUES
(1, 'Hà Nội', '20000.00'),
(2, 'TP. Hồ Chí Minh', '25000.00'),
(3, 'Đà Nẵng', '30000.00'),
(4, 'Hải Phòng', '25000.00'),
(5, 'Cần Thơ', '30000.00'),
(6, 'Tỉnh/Thành khác', '35000.00');

--
-- Table structure for table `orders`
--
CREATE TABLE IF NOT EXISTS `orders` (
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

--
-- Table structure for table `order_items`
--
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `admins`
--
CREATE TABLE IF NOT EXISTS `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `admins` (`username`, `password`) VALUES ('admin', 'admin');

--
-- Table structure for table `coupons`
--
CREATE TABLE IF NOT EXISTS `coupons` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `discount_type` ENUM('percent', 'fixed') NOT NULL DEFAULT 'percent',
  `discount_value` DECIMAL(10, 2) NOT NULL,
  `min_order` DECIMAL(10, 2) DEFAULT 0,
  `status` ENUM('active', 'expired') DEFAULT 'active',
  `expires_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `coupons` (`code`, `discount_type`, `discount_value`, `min_order`, `status`) VALUES
  ('WELCOME15', 'percent', 15.00, 0, 'active'),
  ('FREESHIP', 'fixed', 2.00, 10.00, 'active'),
  ('PIXEL10', 'percent', 10.00, 0, 'active');

CREATE TABLE IF NOT EXISTS `user_coupons` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `coupon_code` VARCHAR(50) NOT NULL,
  `used_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `user_coupon_unique` (`user_id`, `coupon_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `subscribers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `voucher_sent` VARCHAR(50) DEFAULT 'WELCOME15',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;
