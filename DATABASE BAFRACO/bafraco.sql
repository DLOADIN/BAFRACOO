-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 16, 2025 at 07:50 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bafraco`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `u_name` varchar(80) NOT NULL,
  `u_email` varchar(80) NOT NULL,
  `u_phonenumber` int(15) NOT NULL,
  `u_address` varchar(80) NOT NULL,
  `u_password` varchar(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `u_name`, `u_email`, `u_phonenumber`, `u_address`, `u_password`) VALUES
(9, 'Manzi David', 'm.david@alustudent.com', 791291003, 'Kigalui', '12345');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_method`
--

CREATE TABLE `inventory_method` (
  `id` int(11) NOT NULL,
  `tool_id` int(11) NOT NULL,
  `method` enum('FIFO','LIFO') DEFAULT 'FIFO',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order`
--

CREATE TABLE `order` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `u_toolname` varchar(80) DEFAULT NULL,
  `u_itemsnumber` int(11) DEFAULT NULL,
  `u_type` varchar(80) DEFAULT NULL,
  `u_tooldescription` varchar(255) DEFAULT NULL,
  `u_date` date DEFAULT NULL,
  `u_price` int(80) NOT NULL,
  `u_totalprice` int(100) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `order`
--

INSERT INTO `order` (`id`, `user_id`, `u_toolname`, `u_itemsnumber`, `u_type`, `u_tooldescription`, `u_date`, `u_price`, `u_totalprice`, `status`) VALUES
(5, 2, 'Mangos', 11000, 'Very Good', 'I love these items', '2024-04-08', 10000, 0, 'Pending'),
(9, 2, 'APPLES', 11, 'Very Good', 'I love these items', '2024-04-10', 10000, 110000, 'Pending'),
(11, 1, 'Silicone 500mg', 5, 'Not Good', 'From China', '2024-04-12', 10000, 50000, 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `stock_batches`
--

CREATE TABLE `stock_batches` (
  `id` int(11) NOT NULL,
  `tool_id` int(11) NOT NULL,
  `batch_number` varchar(50) NOT NULL,
  `quantity_received` int(11) NOT NULL,
  `quantity_remaining` int(11) NOT NULL,
  `purchase_price` decimal(10,2) NOT NULL,
  `batch_date` datetime DEFAULT current_timestamp(),
  `supplier` varchar(100) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `movement_type` enum('IN','OUT','ADJUSTMENT') NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_cost` decimal(10,2) DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tool`
--

CREATE TABLE `tool` (
  `id` int(11) NOT NULL,
  `u_toolname` varchar(80) NOT NULL,
  `u_itemsnumber` int(11) NOT NULL,
  `u_type` varchar(80) NOT NULL,
  `u_tooldescription` varchar(80) NOT NULL,
  `u_date` date NOT NULL,
  `u_price` int(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `tool`
--

INSERT INTO `tool` (`id`, `u_toolname`, `u_itemsnumber`, `u_type`, `u_tooldescription`, `u_date`, `u_price`) VALUES
(5, 'APPLES', 11000, 'Very Good', 'I love these items', '2024-04-07', 10000),
(6, 'Silicone 500mg', 2, 'Not Good', 'From China', '2024-04-09', 10000),
(7, 'Mangos', 121212, 'Mangos', '1212', '2025-11-09', 1212);

-- --------------------------------------------------------

--
-- Table structure for table `transaction`
--

CREATE TABLE `transaction` (
  `id` int(10) NOT NULL,
  `u_toolname` varchar(80) DEFAULT NULL,
  `u_item` varchar(80) DEFAULT NULL,
  `u_type` varchar(80) DEFAULT NULL,
  `u_amount` varchar(80) DEFAULT NULL,
  `u_status` varchar(80) DEFAULT NULL,
  `u_date` date DEFAULT NULL,
  `u_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `u_name` varchar(80) NOT NULL,
  `u_email` varchar(80) NOT NULL,
  `u_phonenumber` varchar(80) NOT NULL,
  `u_address` varchar(80) NOT NULL,
  `u_password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `u_name`, `u_email`, `u_phonenumber`, `u_address`, `u_password`) VALUES
(1, 'Hendricks', 'm.david@alustudent.com', '0791291003', 'Musanze', '12345'),
(2, 'Ganza', 'manzidavid111@gmail.com', '188171212', 'Kigalui', 'Chrispaul_120');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `u_phonenumber` (`u_phonenumber`);

--
-- Indexes for table `inventory_method`
--
ALTER TABLE `inventory_method`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tool_id` (`tool_id`);

--
-- Indexes for table `order`
--
ALTER TABLE `order`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_ibfk_1` (`user_id`);

--
-- Indexes for table `stock_batches`
--
ALTER TABLE `stock_batches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tool_id` (`tool_id`),
  ADD KEY `batch_date` (`batch_date`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `batch_id` (`batch_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `tool`
--
ALTER TABLE `tool`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transaction`
--
ALTER TABLE `transaction`
  ADD PRIMARY KEY (`id`),
  ADD KEY `u_id` (`u_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `u_name` (`u_name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `inventory_method`
--
ALTER TABLE `inventory_method`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order`
--
ALTER TABLE `order`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `stock_batches`
--
ALTER TABLE `stock_batches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tool`
--
ALTER TABLE `tool`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `inventory_method`
--
ALTER TABLE `inventory_method`
  ADD CONSTRAINT `inventory_method_ibfk_1` FOREIGN KEY (`tool_id`) REFERENCES `tool` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order`
--
ALTER TABLE `order`
  ADD CONSTRAINT `order_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Constraints for table `stock_batches`
--
ALTER TABLE `stock_batches`
  ADD CONSTRAINT `stock_batches_ibfk_1` FOREIGN KEY (`tool_id`) REFERENCES `tool` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `stock_movements_ibfk_1` FOREIGN KEY (`batch_id`) REFERENCES `stock_batches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_movements_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `transaction`
--
ALTER TABLE `transaction`
  ADD CONSTRAINT `transaction_ibfk_1` FOREIGN KEY (`u_id`) REFERENCES `user` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
