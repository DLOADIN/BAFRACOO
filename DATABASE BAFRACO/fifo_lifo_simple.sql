-- FIFO/LIFO Implementation for BAFRACOO - phpMyAdmin Simple Version
-- This script creates the necessary tables and structure for batch inventory management
-- Run each section separately in phpMyAdmin if needed

-- Step 1: Create stock_batches table to track inventory batches
CREATE TABLE IF NOT EXISTS `stock_batches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tool_id` int(11) NOT NULL,
  `batch_number` varchar(50) NOT NULL,
  `quantity_received` int(11) NOT NULL,
  `quantity_remaining` int(11) NOT NULL,
  `purchase_price` decimal(10,2) NOT NULL,
  `batch_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `supplier` varchar(100) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tool_id` (`tool_id`),
  KEY `batch_date` (`batch_date`),
  CONSTRAINT `stock_batches_ibfk_1` FOREIGN KEY (`tool_id`) REFERENCES `tool` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Step 2: Create inventory_method table to store FIFO/LIFO preferences per tool
CREATE TABLE IF NOT EXISTS `inventory_method` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tool_id` int(11) NOT NULL,
  `method` enum('FIFO','LIFO') DEFAULT 'FIFO',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tool_id` (`tool_id`),
  CONSTRAINT `inventory_method_ibfk_1` FOREIGN KEY (`tool_id`) REFERENCES `tool` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Step 3: Create stock_movements table to track all inventory movements
CREATE TABLE IF NOT EXISTS `stock_movements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `movement_type` enum('IN','OUT','ADJUSTMENT') NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_cost` decimal(10,2) DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `batch_id` (`batch_id`),
  KEY `order_id` (`order_id`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `stock_movements_ibfk_1` FOREIGN KEY (`batch_id`) REFERENCES `stock_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_movements_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
