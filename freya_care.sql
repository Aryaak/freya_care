-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.0.30 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.1.0.6537
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Dumping structure for table freya_care.carts
CREATE TABLE IF NOT EXISTS `carts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_carts_to_users` (`user_id`),
  CONSTRAINT `fk_carts_to_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- Dumping data for table freya_care.carts: ~3 rows (approximately)
INSERT INTO `carts` (`id`, `user_id`) VALUES
	(1, 2);

-- Dumping structure for table freya_care.cart_details
CREATE TABLE IF NOT EXISTS `cart_details` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cart_id` int NOT NULL,
  `item_id` int NOT NULL,
  `qty` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_cart_details_to_items` (`item_id`),
  KEY `fk_cart_details_to_carts` (`cart_id`),
  CONSTRAINT `fk_cart_details_to_carts` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_cart_details_to_items` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- Dumping data for table freya_care.cart_details: ~0 rows (approximately)

-- Dumping structure for table freya_care.categories
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(225) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table freya_care.categories: ~2 rows (approximately)
INSERT INTO `categories` (`id`, `name`) VALUES
	(1, 'Alat Kesehatan'),
	(2, 'Vitamin dan Suplement');

-- Dumping structure for table freya_care.items
CREATE TABLE IF NOT EXISTS `items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category_id` int NOT NULL,
  `store_id` int NOT NULL,
  `image` varchar(225) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(225) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_items_to_categories` (`category_id`),
  KEY `fk_items_to_stores` (`store_id`),
  CONSTRAINT `fk_items_to_categories` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_items_to_stores` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table freya_care.items: ~3 rows (approximately)
INSERT INTO `items` (`id`, `category_id`, `store_id`, `image`, `name`, `description`, `price`) VALUES
	(7, 2, 5, 'assets/img/items/681594582f962.jpg', 'Amunizer', 'Amunizer adalah suplemen makanan yang mengandung ekstrak elderberry, ekstrak lonicera, ekstrak forsythia, ekstrak phyllanthus niruri, zinc, dan vitamin C 1000mg', 10500),
	(8, 1, 5, 'assets/img/items/681594f4df9d5.jpg', 'Stetoskop', 'Stetoskop adalah alat medis yang digunakan oleh dokter untuk mendengarkan suara di dalam tubuh, seperti suara jantung, paru-paru, dan usus', 549000),
	(9, 2, 4, 'assets/img/items/681597ac13f4d.jpg', 'Boost D 5000', 'Obat ini diindikasikan untuk meningkatkan kadar serum 25(OH)D dalam darah pada pasien yang kekurangan vitamin D (kadar serum 25(OH)D <30ng/mL)', 112999);

-- Dumping structure for table freya_care.orders
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `store_id` int NOT NULL,
  `payment` enum('debit','credit','cod') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_amount` int NOT NULL DEFAULT '0',
  `status` enum('process','deliver','done','cancel') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cancel_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `fk_orders_to_users` (`user_id`),
  KEY `fk_orders_to_stores` (`store_id`),
  CONSTRAINT `fk_orders_to_stores` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_orders_to_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table freya_care.orders: ~0 rows (approximately)
INSERT INTO `orders` (`id`, `user_id`, `store_id`, `payment`, `total_amount`, `status`, `cancel_reason`) VALUES
	(1, 2, 4, 'debit', 225998, 'process', ''),
	(2, 2, 5, 'debit', 1098000, 'cancel', 'GAAAK JADIIII KAAAAK MAAAP YA ;)'),
	(3, 2, 5, 'credit', 549000, 'process', ''),
	(7, 2, 4, 'cod', 112999, 'process', ''),
	(8, 2, 5, 'cod', 10500, 'process', ''),
	(9, 2, 5, 'debit', 2755500, 'done', NULL),
	(13, 2, 4, 'cod', 225998, 'process', NULL),
	(14, 2, 5, 'cod', 31500, 'done', NULL);

-- Dumping structure for table freya_care.order_details
CREATE TABLE IF NOT EXISTS `order_details` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `item_id` int NOT NULL,
  `qty` int NOT NULL,
  `price` int NOT NULL,
  `subtotal` int NOT NULL,
  `feedback` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `fk_order_details_to_orders` (`order_id`),
  KEY `fk_order_details_to_items` (`item_id`),
  CONSTRAINT `fk_order_details_to_items` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_order_details_to_orders` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table freya_care.order_details: ~0 rows (approximately)
INSERT INTO `order_details` (`id`, `order_id`, `item_id`, `qty`, `price`, `subtotal`, `feedback`) VALUES
	(1, 1, 9, 2, 112999, 225998, ''),
	(2, 2, 8, 2, 549000, 1098000, ''),
	(3, 3, 8, 1, 549000, 549000, ''),
	(7, 7, 9, 1, 112999, 112999, ''),
	(8, 8, 7, 1, 10500, 10500, ''),
	(9, 9, 7, 1, 10500, 10500, 'Keren'),
	(10, 9, 8, 5, 549000, 2745000, 'Keren'),
	(13, 13, 9, 2, 112999, 225998, NULL),
	(14, 14, 7, 3, 10500, 31500, 'Keren');

-- Dumping structure for table freya_care.stores
CREATE TABLE IF NOT EXISTS `stores` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `name` varchar(225) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','accept','reject') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reject_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `fk_shops_to_users` (`user_id`),
  CONSTRAINT `fk_shops_to_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table freya_care.stores: ~2 rows (approximately)
INSERT INTO `stores` (`id`, `user_id`, `name`, `status`, `reject_reason`) VALUES
	(4, 2, 'Yak Store', 'accept', NULL),
	(5, 5, 'Frey Store', 'accept', '');

-- Dumping structure for table freya_care.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `role` enum('administrator','customer') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'customer',
  `name` varchar(225) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(225) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `password` varchar(225) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table freya_care.users: ~3 rows (approximately)
INSERT INTO `users` (`id`, `role`, `name`, `email`, `address`, `password`) VALUES
	(1, 'administrator', 'Administrator', 'administrator', '', '$2y$10$HHXQpnO2QIWwYIhowriRme9gGB68w2aPH4SOIS6WsvIqMGN5UO9uW'),
	(2, 'customer', 'Arya Rizky Tri Putra', 'arya@gmail.com', 'Jl. Babatan UNESA Gg 5G No 3C RT 07 RW 01', '$2y$10$X3j0PyKTh/rrPSgft/gL9OpWxqZeyhgkr8A8mO.hphefZnjrXetNG'),
	(5, 'customer', 'Freya Enggrayni', 'freya@gmail.com', '', '$2y$10$5rbuVxtvBXThjvkAFmdFL.WJDkZQRbv2s3UqXOeFqU34wS.VksVP6');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
