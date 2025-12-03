-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 03, 2025 at 07:27 PM
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
-- Table structure for table `damaged_goods`
--

CREATE TABLE `damaged_goods` (
  `id` int(11) NOT NULL,
  `tool_id` int(11) NOT NULL,
  `tool_name` varchar(80) NOT NULL,
  `quantity_removed` int(11) NOT NULL,
  `damage_reason` varchar(255) NOT NULL,
  `damage_date` datetime DEFAULT current_timestamp(),
  `reported_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `original_value` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `damaged_goods`
--

INSERT INTO `damaged_goods` (`id`, `tool_id`, `tool_name`, `quantity_removed`, `damage_reason`, `damage_date`, `reported_by`, `notes`, `original_value`) VALUES
(1, 6, 'Silicone 500mg', 1, 'Manufacturing Defect', '2025-12-03 19:41:37', 1, 'None', 10000.00);

-- --------------------------------------------------------

--
-- Table structure for table `damaged_products`
--

CREATE TABLE `damaged_products` (
  `id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `quantity_damaged` int(11) NOT NULL,
  `damage_reason` enum('BROKEN_SHIPPING','MANUFACTURING_DEFECT','CUSTOMER_RETURN_DAMAGED','WAREHOUSE_ACCIDENT','EXPIRED','WATER_DAMAGE','THEFT_VANDALISM','QUALITY_CONTROL_FAIL') NOT NULL,
  `damage_description` text DEFAULT NULL,
  `location_id` int(11) NOT NULL,
  `reported_by` int(11) NOT NULL,
  `damage_date` date NOT NULL,
  `estimated_loss` decimal(10,2) DEFAULT NULL,
  `disposal_method` enum('REPAIR','RETURN_TO_SUPPLIER','DISPOSAL','SELL_AS_IS') DEFAULT NULL,
  `disposal_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

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

--
-- Dumping data for table `inventory_method`
--

INSERT INTO `inventory_method` (`id`, `tool_id`, `method`, `created_at`, `updated_at`) VALUES
(1, 5, 'FIFO', '2025-11-16 19:10:55', '2025-11-16 19:10:55'),
(2, 6, 'FIFO', '2025-11-16 19:10:55', '2025-11-16 19:10:55'),
(3, 7, 'LIFO', '2025-11-16 19:10:55', '2025-11-21 22:31:16'),
(5, 8, 'FIFO', '2025-11-21 22:40:12', '2025-11-21 22:40:12');

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `id` int(11) NOT NULL,
  `location_name` varchar(100) NOT NULL,
  `location_type` enum('WAREHOUSE','SHIPPING_HUB','STORE') DEFAULT 'WAREHOUSE',
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `province` varchar(50) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`id`, `location_name`, `location_type`, `address`, `city`, `province`, `contact_person`, `phone_number`, `is_active`, `created_at`) VALUES
(1, 'Kigali Central Warehouse', 'WAREHOUSE', 'KN 3 Ave, Nyarugenge', 'Kigali', 'Kigali City', 'Jean Baptiste', '+250788123456', 1, '2025-11-21 22:15:25'),
(2, 'Nyabugogo Shipping Hub', 'SHIPPING_HUB', 'Nyabugogo Bus Station Area', 'Kigali', 'Kigali City', 'Marie Claire', '+250788234567', 1, '2025-11-21 22:15:25'),
(3, 'Kimironko Warehouse', 'WAREHOUSE', 'Kimironko Sector', 'Kigali', 'Kigali City', 'Paul Mugabo', '+250788345678', 1, '2025-11-21 22:15:25'),
(4, 'Musanze Regional Hub', 'SHIPPING_HUB', 'Musanze Town Center', 'Musanze', 'Northern Province', 'Eric Nzeyimana', '+250788456789', 1, '2025-11-21 22:15:25'),
(5, 'Huye University Store', 'STORE', 'University of Rwanda Campus', 'Huye', 'Southern Province', 'Grace Uwimana', '+250788567890', 1, '2025-11-21 22:15:25'),
(6, 'Rubavu Border Warehouse', 'WAREHOUSE', 'Rubavu Border, Gisenyi', 'Rubavu', 'Western Province', 'David Habimana', '+250788678901', 1, '2025-11-21 22:15:25'),
(7, 'Kayonza Eastern Hub', 'SHIPPING_HUB', 'Kayonza Town Center', 'Kayonza', 'Eastern Province', 'Stella Mukamana', '+250788789012', 1, '2025-11-21 22:15:25'),
(8, 'Kicukiro Industrial Zone', 'WAREHOUSE', 'KK 15 St, Kicukiro', 'Kigali', 'Kigali City', 'James Nkurunziza', '+250788890123', 1, '2025-11-21 22:15:25');

-- --------------------------------------------------------

--
-- Table structure for table `order`
--

CREATE TABLE `order` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tool_id` int(11) DEFAULT NULL,
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

INSERT INTO `order` (`id`, `user_id`, `tool_id`, `u_toolname`, `u_itemsnumber`, `u_type`, `u_tooldescription`, `u_date`, `u_price`, `u_totalprice`, `status`) VALUES
(5, 2, 7, 'Mangos', 11000, 'Very Good', 'I love these items', '2024-04-08', 10000, 0, 'Pending'),
(9, 2, 5, 'APPLES', 11, 'Very Good', 'I love these items', '2024-04-10', 10000, 110000, 'Pending'),
(11, 1, 6, 'Silicone 500mg', 5, 'Not Good', 'From China', '2024-04-12', 10000, 50000, 'Pending'),
(12, 1, 7, 'Mangos', 300, 'Very Good', 'MY mangos', '2025-12-02', 10000, 3000000, 'Pending'),
(13, 1, 6, 'Silicone 500mg', 1, 'Not Good', 'From China', '2025-12-03', 10000, 10000, 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `returned_stock`
--

CREATE TABLE `returned_stock` (
  `id` int(11) NOT NULL,
  `tool_id` int(11) NOT NULL,
  `tool_name` varchar(80) NOT NULL,
  `quantity_returned` int(11) NOT NULL,
  `return_reason` varchar(255) NOT NULL,
  `return_date` datetime DEFAULT current_timestamp(),
  `condition_status` enum('GOOD','DAMAGED','UNUSABLE') DEFAULT 'GOOD',
  `processed_by` int(11) DEFAULT NULL,
  `restock_status` enum('PENDING','RESTOCKED','WRITTEN_OFF') DEFAULT 'PENDING',
  `notes` text DEFAULT NULL,
  `original_value` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `returns`
--

CREATE TABLE `returns` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tool_name` varchar(80) NOT NULL,
  `quantity_returned` int(11) NOT NULL,
  `return_reason` enum('DEFECTIVE','WRONG_ITEM','NOT_AS_DESCRIBED','CHANGED_MIND','SIZE_ISSUE','DAMAGED_IN_TRANSIT') NOT NULL,
  `item_condition` enum('LIKE_NEW','MINOR_WEAR','DAMAGED') NOT NULL,
  `return_description` text DEFAULT NULL,
  `return_date` date NOT NULL,
  `return_status` enum('PENDING','APPROVED','REJECTED','PROCESSED') DEFAULT 'PENDING',
  `refund_amount` decimal(10,2) DEFAULT NULL,
  `restocking_fee` decimal(10,2) DEFAULT 0.00,
  `processed_by` int(11) DEFAULT NULL,
  `processed_date` date DEFAULT NULL,
  `restock_location_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `return_policy`
--

CREATE TABLE `return_policy` (
  `id` int(11) NOT NULL,
  `policy_name` varchar(100) NOT NULL,
  `return_window_days` int(11) DEFAULT 30,
  `restocking_fee_percentage` decimal(5,2) DEFAULT 0.00,
  `condition_requirements` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `return_policy`
--

INSERT INTO `return_policy` (`id`, `policy_name`, `return_window_days`, `restocking_fee_percentage`, `condition_requirements`, `is_active`, `created_at`) VALUES
(1, 'Standard Return Policy', 30, 10.00, 'Items must be in original condition. Like New: Full refund. Minor Wear: 90% refund. Damaged: Case by case review.', 1, '2025-11-21 22:15:25');

-- --------------------------------------------------------

--
-- Table structure for table `stock_alerts`
--

CREATE TABLE `stock_alerts` (
  `id` int(11) NOT NULL,
  `tool_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `alert_type` enum('LOW_STOCK','OUT_OF_STOCK','EXPIRED_SOON','DAMAGED_HIGH') NOT NULL,
  `alert_level` enum('INFO','WARNING','CRITICAL') DEFAULT 'WARNING',
  `threshold_value` int(11) DEFAULT NULL,
  `current_value` int(11) DEFAULT NULL,
  `alert_message` text DEFAULT NULL,
  `is_resolved` tinyint(1) DEFAULT 0,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

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
  `location_id` int(11) DEFAULT 1,
  `expiry_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `stock_batches`
--

INSERT INTO `stock_batches` (`id`, `tool_id`, `batch_number`, `quantity_received`, `quantity_remaining`, `purchase_price`, `batch_date`, `supplier`, `location_id`, `expiry_date`, `created_at`, `updated_at`) VALUES
(1, 5, 'BATCH-0005-001', 11000, 11000, 8000.00, '2024-03-23 00:00:00', 'Default Supplier', 1, NULL, '2025-11-16 19:10:55', '2025-11-16 19:10:55'),
(2, 6, 'BATCH-0006-001', 2, 2, 8000.00, '2024-03-25 00:00:00', 'Default Supplier', 1, NULL, '2025-11-16 19:10:55', '2025-11-16 19:10:55'),
(3, 7, 'BATCH-0007-001', 121212, 121212, 969.60, '2025-10-25 00:00:00', 'Default Supplier', 1, NULL, '2025-11-16 19:10:55', '2025-11-16 19:10:55'),
(4, 5, 'BATCH-0005-002', 100, 100, 300000.00, '2025-12-03 19:56:59', '0', 6, '2026-01-01', '2025-12-03 17:56:59', '2025-12-03 17:56:59');

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

--
-- Dumping data for table `stock_movements`
--

INSERT INTO `stock_movements` (`id`, `batch_id`, `order_id`, `movement_type`, `quantity`, `unit_cost`, `reference`, `notes`, `created_at`, `created_by`) VALUES
(1, 1, NULL, 'IN', 11000, 8000.00, 'INITIAL-BATCH-0005-001', 'Initial stock entry', '2025-11-16 19:10:56', NULL),
(2, 2, NULL, 'IN', 2, 8000.00, 'INITIAL-BATCH-0006-001', 'Initial stock entry', '2025-11-16 19:10:56', NULL),
(3, 3, NULL, 'IN', 121212, 969.60, 'INITIAL-BATCH-0007-001', 'Initial stock entry', '2025-11-16 19:10:56', NULL),
(4, 4, NULL, 'IN', 100, 300000.00, 'STOCK-IN-BATCH-0005-002', NULL, '2025-12-03 17:56:59', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `stock_thresholds`
--

CREATE TABLE `stock_thresholds` (
  `id` int(11) NOT NULL,
  `tool_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `minimum_stock` int(11) DEFAULT 10,
  `reorder_level` int(11) DEFAULT 20,
  `maximum_stock` int(11) DEFAULT 1000,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `stock_thresholds`
--

INSERT INTO `stock_thresholds` (`id`, `tool_id`, `location_id`, `minimum_stock`, `reorder_level`, `maximum_stock`, `created_at`, `updated_at`) VALUES
(1, 5, 1, 10, 50, 1000, '2025-11-21 22:15:25', '2025-11-21 22:15:25'),
(2, 6, 1, 10, 50, 1000, '2025-11-21 22:15:25', '2025-11-21 22:15:25'),
(3, 7, 1, 10, 50, 1000, '2025-11-21 22:15:25', '2025-11-21 22:15:25');

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
  `u_price` int(80) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `tool`
--

INSERT INTO `tool` (`id`, `u_toolname`, `u_itemsnumber`, `u_type`, `u_tooldescription`, `u_date`, `u_price`, `image_url`) VALUES
(5, 'APPLES', 11000, 'Very Good', 'I love these items', '2024-04-07', 10000, NULL),
(6, 'Silicone 500mg', 0, 'Not Good', 'From China', '2024-04-09', 10000, NULL),
(7, 'Mangos', 121212, 'Mangos', '1212', '2025-11-09', 1212, NULL),
(8, 'Living Room Lamps', 100, 'Very Good', '100', '2025-11-21', 2000, NULL),
(9, 'Berryfruits', 20, 'Very Good', 'Some of the berryfruits.', '2025-12-02', 200000, NULL);

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
-- Indexes for table `damaged_goods`
--
ALTER TABLE `damaged_goods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tool_id` (`tool_id`);

--
-- Indexes for table `damaged_products`
--
ALTER TABLE `damaged_products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `batch_id` (`batch_id`),
  ADD KEY `location_id` (`location_id`),
  ADD KEY `reported_by` (`reported_by`);

--
-- Indexes for table `inventory_method`
--
ALTER TABLE `inventory_method`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tool_id` (`tool_id`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order`
--
ALTER TABLE `order`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_ibfk_1` (`user_id`),
  ADD KEY `idx_order_tool_id` (`tool_id`);

--
-- Indexes for table `returned_stock`
--
ALTER TABLE `returned_stock`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tool_id` (`tool_id`);

--
-- Indexes for table `returns`
--
ALTER TABLE `returns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `processed_by` (`processed_by`),
  ADD KEY `restock_location_id` (`restock_location_id`);

--
-- Indexes for table `return_policy`
--
ALTER TABLE `return_policy`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stock_alerts`
--
ALTER TABLE `stock_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tool_id` (`tool_id`),
  ADD KEY `location_id` (`location_id`),
  ADD KEY `resolved_by` (`resolved_by`);

--
-- Indexes for table `stock_batches`
--
ALTER TABLE `stock_batches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tool_id` (`tool_id`),
  ADD KEY `batch_date` (`batch_date`),
  ADD KEY `location_id` (`location_id`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `batch_id` (`batch_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `stock_thresholds`
--
ALTER TABLE `stock_thresholds`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_tool_location` (`tool_id`,`location_id`),
  ADD KEY `location_id` (`location_id`);

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
-- AUTO_INCREMENT for table `damaged_goods`
--
ALTER TABLE `damaged_goods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `damaged_products`
--
ALTER TABLE `damaged_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_method`
--
ALTER TABLE `inventory_method`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `order`
--
ALTER TABLE `order`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `returned_stock`
--
ALTER TABLE `returned_stock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `returns`
--
ALTER TABLE `returns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `return_policy`
--
ALTER TABLE `return_policy`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `stock_alerts`
--
ALTER TABLE `stock_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_batches`
--
ALTER TABLE `stock_batches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `stock_thresholds`
--
ALTER TABLE `stock_thresholds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tool`
--
ALTER TABLE `tool`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `damaged_products`
--
ALTER TABLE `damaged_products`
  ADD CONSTRAINT `damaged_products_ibfk_1` FOREIGN KEY (`batch_id`) REFERENCES `stock_batches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `damaged_products_ibfk_2` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`),
  ADD CONSTRAINT `damaged_products_ibfk_3` FOREIGN KEY (`reported_by`) REFERENCES `admin` (`id`);

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
-- Constraints for table `returns`
--
ALTER TABLE `returns`
  ADD CONSTRAINT `returns_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`),
  ADD CONSTRAINT `returns_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `returns_ibfk_3` FOREIGN KEY (`processed_by`) REFERENCES `admin` (`id`),
  ADD CONSTRAINT `returns_ibfk_4` FOREIGN KEY (`restock_location_id`) REFERENCES `locations` (`id`);

--
-- Constraints for table `stock_alerts`
--
ALTER TABLE `stock_alerts`
  ADD CONSTRAINT `stock_alerts_ibfk_1` FOREIGN KEY (`tool_id`) REFERENCES `tool` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_alerts_ibfk_2` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`),
  ADD CONSTRAINT `stock_alerts_ibfk_3` FOREIGN KEY (`resolved_by`) REFERENCES `admin` (`id`);

--
-- Constraints for table `stock_batches`
--
ALTER TABLE `stock_batches`
  ADD CONSTRAINT `stock_batches_ibfk_1` FOREIGN KEY (`tool_id`) REFERENCES `tool` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_batches_location_fk` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`);

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `stock_movements_ibfk_1` FOREIGN KEY (`batch_id`) REFERENCES `stock_batches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_movements_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `stock_thresholds`
--
ALTER TABLE `stock_thresholds`
  ADD CONSTRAINT `stock_thresholds_ibfk_1` FOREIGN KEY (`tool_id`) REFERENCES `tool` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_thresholds_ibfk_2` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`);

--
-- Constraints for table `transaction`
--
ALTER TABLE `transaction`
  ADD CONSTRAINT `transaction_ibfk_1` FOREIGN KEY (`u_id`) REFERENCES `user` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
