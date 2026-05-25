-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 21, 2026 at 07:58 AM
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
-- Database: `web_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `cafe_settings`
--

CREATE TABLE `cafe_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(120) NOT NULL,
  `setting_value` longtext DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_date` datetime NOT NULL,
  `items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`items`)),
  `total` decimal(10,2) NOT NULL,
  `paid` decimal(10,2) DEFAULT NULL,
  `change_amount` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_date`, `items`, `total`, `paid`, `change_amount`, `created_at`) VALUES
(2, '2026-03-30 17:08:51', '[{\"id\":2,\"name\":\"Americano\",\"price\":52.5,\"quantity\":1},{\"id\":3,\"name\":\"Cappuccino\",\"price\":43,\"quantity\":1},{\"id\":1,\"name\":\"Espresso\",\"price\":42,\"quantity\":1},{\"id\":10,\"name\":\"Choco\\/Vanilla Bavarian\",\"price\":10,\"quantity\":1},{\"id\":7,\"name\":\"Malunggay Pandesal\",\"price\":5,\"quantity\":2}]', 157.50, 200.00, 42.50, '2026-03-30 15:08:51'),
(3, '2026-03-31 05:26:00', '[{\"id\":10,\"name\":\"Choco\\/Vanilla Bavarian\",\"price\":10,\"quantity\":1},{\"id\":8,\"name\":\"Egg Bread\",\"price\":5,\"quantity\":1}]', 15.00, 15.00, 0.00, '2026-03-31 03:26:00'),
(4, '2026-03-31 07:59:12', '[{\"id\":2,\"name\":\"Americano\",\"price\":52.5,\"quantity\":3},{\"id\":3,\"name\":\"Cappuccino\",\"price\":43,\"quantity\":1},{\"id\":10,\"name\":\"Choco\\/Vanilla Bavarian\",\"price\":10,\"quantity\":1}]', 210.50, 300.00, 89.50, '2026-03-31 05:59:12'),
(5, '2026-03-31 09:04:47', '[{\"id\":2,\"name\":\"Americano\",\"price\":52.5,\"quantity\":2},{\"id\":10,\"name\":\"Choco\\/Vanilla Bavarian\",\"price\":10,\"quantity\":2}]', 125.00, 130.00, 5.00, '2026-03-31 07:04:47'),
(6, '2026-05-20 13:48:23', '[{\"id\":1,\"name\":\"Espresso\",\"price\":42,\"quantity\":1},{\"id\":3,\"name\":\"Cappuccino\",\"price\":43,\"quantity\":1},{\"id\":2,\"name\":\"Americano\",\"price\":52.5,\"quantity\":1}]', 137.50, 150.00, 12.50, '2026-05-20 11:48:23');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `category`, `price`, `image`, `available`, `created_at`, `updated_at`) VALUES
(1, 'Espresso', 'Coffees', 42.00, 'images/espresso.jpg', 1, '2026-03-28 13:33:13', '2026-03-28 13:33:13'),
(2, 'Americano', 'Coffees', 52.50, 'images/americano.jpg', 1, '2026-03-28 13:33:13', '2026-03-28 13:33:13'),
(3, 'Cappuccino', 'Coffees', 43.00, 'images/cappuccino.jpg', 1, '2026-03-28 13:33:13', '2026-03-28 13:33:13'),
(4, 'Latte', 'Coffees', 33.50, 'images/latte.jpg', 1, '2026-03-28 13:33:13', '2026-03-28 13:33:13'),
(5, 'Mocha', 'Coffees', 34.00, 'images/mocha.jpg', 1, '2026-03-28 13:33:13', '2026-03-28 13:33:13'),
(6, 'Macchiato', 'Coffees', 32.75, 'images/macchiato.jpg', 1, '2026-03-28 13:33:13', '2026-03-28 13:33:13'),
(7, 'Malunggay Pandesal', 'Pastries', 5.00, 'images/pandesal.jpg', 1, '2026-03-28 13:33:13', '2026-03-28 13:33:13'),
(8, 'Egg Bread', 'Pastries', 5.00, 'images/egg.jpg', 1, '2026-03-28 13:33:13', '2026-03-28 13:33:13'),
(9, 'Pan de Coco', 'Pastries', 5.00, 'images/coco.jpg', 1, '2026-03-28 13:33:13', '2026-03-28 13:33:13'),
(10, 'Choco/Vanilla Bavarian', 'Pastries', 10.00, 'images/bavarian.jpg', 1, '2026-03-28 13:33:13', '2026-03-28 13:33:13'),
(17, 'Kape Stick', 'Coffees', 2.00, 'images/bfc.jpg', 1, '2026-03-30 12:30:48', '2026-03-30 12:30:48');

-- --------------------------------------------------------

--
-- Table structure for table `registration`
--

CREATE TABLE `registration` (
  `id` int(11) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT '',
  `sex` varchar(20) DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `registration`
--

INSERT INTO `registration` (`id`, `firstname`, `lastname`, `email`, `password`, `address`, `sex`, `created_at`) VALUES
(1, 'test', 'user', 'test@gmail.com', 'user', 'Taytay', 'Male', '2026-03-29 12:42:59'),
(3, 'test', 'user', 'user@gmail.com', '123123', 'Taytay', 'Male', '2026-03-30 12:09:04'),
(5, 'qwerty', 'uiop', 'qwerty@gmail.com', 'qwerty', 'Taytay', 'Prefer not to say', '2026-03-30 12:39:49');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `role` varchar(50) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`id`, `name`, `role`, `username`, `password`, `active`, `created_at`, `updated_at`) VALUES
(1, 'Administrator', 'Manager', 'admin', 'admin123', 1, '2026-03-28 13:33:13', '2026-03-28 13:33:13'),
(2, 'Justin', 'Cashier', 'justin', '123123', 1, '2026-03-30 12:33:20', '2026-03-30 12:33:20');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) DEFAULT 'user',
  `date_registered` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `email`, `username`, `password`, `role`, `date_registered`) VALUES
(1, 'Justin Roble', 'justin@gmail.com', 'larvondy', '$2y$10$uk8UZwoiInRd/mOhPX35BuWeiDOz8q5W/L3lk3gNE9eLpxkbaE9ei', 'user', '2026-03-08 06:03:22'),
(2, 'Administrator', 'admin@admin.com', 'jireh', 'faith', 'admin', '2026-03-10 07:48:03');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cafe_settings`
--
ALTER TABLE `cafe_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `registration`
--
ALTER TABLE `registration`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cafe_settings`
--
ALTER TABLE `cafe_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `registration`
--
ALTER TABLE `registration`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
