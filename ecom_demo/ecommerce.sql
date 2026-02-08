-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 08, 2026 at 07:45 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ecommerce`
--

-- --------------------------------------------------------

--
-- Table structure for table `banners`
--

CREATE TABLE `banners` (
  `id` int(11) NOT NULL,
  `image_url` varchar(500) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `banners`
--

INSERT INTO `banners` (`id`, `image_url`, `is_active`, `created_at`) VALUES
(1, 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?w=1200', 1, '2026-02-08 17:53:07'),
(2, 'https://images.unsplash.com/photo-1550009158-9ebf69173e03?w=1200', 1, '2026-02-08 17:53:07'),
(3, 'https://images.unsplash.com/photo-1460353581641-37baddab0fa2?w=1200', 1, '2026-02-08 17:53:07'),
(5, 'assets/banners/banner_1770575124_6988d5145b56b.jpg', 1, '2026-02-08 18:25:24');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `product_id`, `quantity`, `added_at`) VALUES
(9, 1, 2, 1, '2026-02-08 18:10:58');

-- --------------------------------------------------------

--
-- Table structure for table `order_history`
--

CREATE TABLE `order_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `shipping_address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_history`
--

INSERT INTO `order_history` (`id`, `user_id`, `total_amount`, `status`, `order_date`, `shipping_address`) VALUES
(1, 1, 1309.99, 'processing', '2026-02-06 06:53:06', 'Raipur'),
(2, 1, 510.00, 'pending', '2026-02-08 18:02:11', 'Raipur chhattisgarh');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 2, 1, 1299.99),
(2, 2, 9, 1, 500.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `stock` int(11) DEFAULT 100,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `category`, `stock`, `created_at`) VALUES
(1, 'Smartphone Xyz', 'Latest flagship smartphone with advanced camera system, 5G connectivity, and all-day battery life', 699.99, 'Electronics', 50, '2026-02-04 07:08:57'),
(2, 'Laptop Pro', 'High performance laptop with Intel i7 processor, 16GB RAM, 512GB SSD, perfect for professionals', 1299.99, 'Electronics', 29, '2026-02-04 07:08:57'),
(3, 'Wireless Headphones', 'Premium noise cancelling wireless headphones with 30-hour battery life and superior sound quality', 199.99, 'Electronics', 75, '2026-02-04 07:08:57'),
(4, 'Cotton T-Shirt', 'Comfortable 100% cotton t-shirt, available in multiple colors, perfect for everyday wear', 24.99, 'Clothing', 200, '2026-02-04 07:08:57'),
(5, 'Classic Jeans', 'Premium denim jeans with perfect fit and durability, classic blue wash', 49.99, 'Clothing', 150, '2026-02-04 07:08:57'),
(6, 'Running Shoes', 'Lightweight performance running shoes with cushioned sole and breathable mesh upper', 89.99, 'Footwear', 100, '2026-02-04 07:08:57'),
(9, 'Bass Boosted head phones', 'Best bass boosted head phones.', 500.00, 'Electronics', 49, '2026-02-08 16:56:57'),
(10, 'pen drive 64GB', 'Pendrive 64Gb', 100.00, 'Electronics', 100, '2026-02-08 18:33:31'),
(11, 'T-shirt white', 'Cotton T-shirt white all size awailable', 15.00, 'Clothing', 100, '2026-02-08 18:36:40'),
(12, 'Mixer Grinder', 'Mixer Grinder 750W with 3 Stainless Steel Liquidiser, Grinder and Chutney Jars - Grey (4025)', 20.00, 'Electronics', 100, '2026-02-08 18:38:50'),
(14, 'Men\'s Casual Sneakers Shooes Grey-07', 'Product details\r\nMaterial typePolyurethane, Leather\r\nClosure typeLace-Up\r\nHeel typeFlat\r\nWater resistance levelNot Water Resistant\r\nSole materialPolyurethane\r\nStyleFlat', 10.00, 'Footwear', 100, '2026-02-08 18:44:11');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_url` varchar(500) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `is_primary`, `display_order`, `created_at`) VALUES
(1, 1, 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=800', 1, 1, '2026-02-04 07:08:57'),
(2, 1, 'https://images.unsplash.com/photo-1592286927505-c8d5e9d6f84d?w=800', 0, 2, '2026-02-04 07:08:57'),
(3, 1, 'https://images.unsplash.com/photo-1598327105666-5b89351aff97?w=800', 0, 3, '2026-02-04 07:08:57'),
(4, 2, 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=800', 1, 1, '2026-02-04 07:08:57'),
(5, 2, 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=800', 0, 2, '2026-02-04 07:08:57'),
(6, 2, 'https://images.unsplash.com/photo-1525547719571-a2d4ac8945e2?w=800', 0, 3, '2026-02-04 07:08:57'),
(7, 3, 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=800', 1, 1, '2026-02-04 07:08:57'),
(8, 3, 'https://images.unsplash.com/photo-1484704849700-f032a568e944?w=800', 0, 2, '2026-02-04 07:08:57'),
(9, 3, 'https://images.unsplash.com/photo-1545127398-14699f92334b?w=800', 0, 3, '2026-02-04 07:08:57'),
(10, 4, 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=800', 1, 1, '2026-02-04 07:08:57'),
(11, 4, 'https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?w=800', 0, 2, '2026-02-04 07:08:57'),
(12, 4, 'https://images.unsplash.com/photo-1562157873-818bc0726f68?w=800', 0, 3, '2026-02-04 07:08:57'),
(13, 5, 'https://images.unsplash.com/photo-1542272604-787c3835535d?w=800', 1, 1, '2026-02-04 07:08:57'),
(14, 5, 'https://images.unsplash.com/photo-1475178626620-a4d074967452?w=800', 0, 2, '2026-02-04 07:08:57'),
(15, 5, 'https://images.unsplash.com/photo-1604176354204-9268737828e4?w=800', 0, 3, '2026-02-04 07:08:57'),
(16, 6, 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=800', 1, 1, '2026-02-04 07:08:57'),
(17, 6, 'https://images.unsplash.com/photo-1460353581641-37baddab0fa2?w=800', 0, 2, '2026-02-04 07:08:57'),
(18, 6, 'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=800', 0, 3, '2026-02-04 07:08:57'),
(22, 9, 'assets/product-images/product_9_6988c0592725f_0.jpg', 1, 1, '2026-02-08 16:56:57'),
(23, 10, 'assets/product-images/product_10_6988d6fb48779_0.jpg', 1, 1, '2026-02-08 18:33:31'),
(24, 11, 'assets/product-images/product_11_6988d7b857348_0.webp', 1, 1, '2026-02-08 18:36:40'),
(25, 12, 'assets/product-images/product_12_6988d83a5f480_0.webp', 1, 1, '2026-02-08 18:38:50'),
(27, 14, 'assets/product-images/product_14_6988d97bf05c0_0.jpg', 1, 1, '2026-02-08 18:44:11');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `created_at`) VALUES
(1, 'tarun', 'tarun@gmail.com', '$2y$10$VCVbP/CNg64nwM/g2sNpRubGPVVGyaxfg14qLUN8A2MPLxNFWHquu', '2026-02-05 14:11:15');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`id`, `user_id`, `product_id`, `added_at`) VALUES
(5, 1, 2, '2026-02-08 17:41:31');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `order_history`
--
ALTER TABLE `order_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_id` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `banners`
--
ALTER TABLE `banners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `order_history`
--
ALTER TABLE `order_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `order_history`
--
ALTER TABLE `order_history`
  ADD CONSTRAINT `order_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `order_history` (`id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
