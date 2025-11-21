-- Enhanced Stock Management System for BAFRACOO
-- Includes Damaged Products, Returns, and Rwandan Shipping Hubs

-- Step 1: Create locations table for Rwandan shipping hubs and warehouses
CREATE TABLE IF NOT EXISTS `locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `location_name` varchar(100) NOT NULL,
  `location_type` enum('WAREHOUSE','SHIPPING_HUB','STORE') DEFAULT 'WAREHOUSE',
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `province` varchar(50) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Insert Rwandan shipping hubs and warehouses
INSERT INTO `locations` (`location_name`, `location_type`, `address`, `city`, `province`, `contact_person`, `phone_number`) VALUES
('Kigali Central Warehouse', 'WAREHOUSE', 'KN 3 Ave, Nyarugenge', 'Kigali', 'Kigali City', 'Jean Baptiste', '+250788123456'),
('Nyabugogo Shipping Hub', 'SHIPPING_HUB', 'Nyabugogo Bus Station Area', 'Kigali', 'Kigali City', 'Marie Claire', '+250788234567'),
('Kimironko Warehouse', 'WAREHOUSE', 'Kimironko Sector', 'Kigali', 'Kigali City', 'Paul Mugabo', '+250788345678'),
('Musanze Regional Hub', 'SHIPPING_HUB', 'Musanze Town Center', 'Musanze', 'Northern Province', 'Eric Nzeyimana', '+250788456789'),
('Huye University Store', 'STORE', 'University of Rwanda Campus', 'Huye', 'Southern Province', 'Grace Uwimana', '+250788567890'),
('Rubavu Border Warehouse', 'WAREHOUSE', 'Rubavu Border, Gisenyi', 'Rubavu', 'Western Province', 'David Habimana', '+250788678901'),
('Kayonza Eastern Hub', 'SHIPPING_HUB', 'Kayonza Town Center', 'Kayonza', 'Eastern Province', 'Stella Mukamana', '+250788789012'),
('Kicukiro Industrial Zone', 'WAREHOUSE', 'KK 15 St, Kicukiro', 'Kigali', 'Kigali City', 'James Nkurunziza', '+250788890123');

-- Step 2: Create damaged_products table
CREATE TABLE IF NOT EXISTS `damaged_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_id` int(11) NOT NULL,
  `quantity_damaged` int(11) NOT NULL,
  `damage_reason` enum('BROKEN_SHIPPING','MANUFACTURING_DEFECT','CUSTOMER_RETURN_DAMAGED','WAREHOUSE_ACCIDENT','EXPIRED','WATER_DAMAGE','THEFT_VANDALISM','QUALITY_CONTROL_FAIL') NOT NULL,
  `damage_description` text DEFAULT NULL,
  `location_id` int(11) NOT NULL,
  `reported_by` int(11) NOT NULL, -- admin user who reported
  `damage_date` date NOT NULL,
  `estimated_loss` decimal(10,2) DEFAULT NULL,
  `disposal_method` enum('REPAIR','RETURN_TO_SUPPLIER','DISPOSAL','SELL_AS_IS') DEFAULT NULL,
  `disposal_date` date DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `batch_id` (`batch_id`),
  KEY `location_id` (`location_id`),
  KEY `reported_by` (`reported_by`),
  CONSTRAINT `damaged_products_ibfk_1` FOREIGN KEY (`batch_id`) REFERENCES `stock_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `damaged_products_ibfk_2` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `damaged_products_ibfk_3` FOREIGN KEY (`reported_by`) REFERENCES `admin` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Step 3: Create returns table
CREATE TABLE IF NOT EXISTS `returns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `processed_by` int(11) DEFAULT NULL, -- admin who processed
  `processed_date` date DEFAULT NULL,
  `restock_location_id` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `user_id` (`user_id`),
  KEY `processed_by` (`processed_by`),
  KEY `restock_location_id` (`restock_location_id`),
  CONSTRAINT `returns_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`),
  CONSTRAINT `returns_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`),
  CONSTRAINT `returns_ibfk_3` FOREIGN KEY (`processed_by`) REFERENCES `admin` (`id`),
  CONSTRAINT `returns_ibfk_4` FOREIGN KEY (`restock_location_id`) REFERENCES `locations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Step 4: Create stock_alerts table
CREATE TABLE IF NOT EXISTS `stock_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tool_id` (`tool_id`),
  KEY `location_id` (`location_id`),
  KEY `resolved_by` (`resolved_by`),
  CONSTRAINT `stock_alerts_ibfk_1` FOREIGN KEY (`tool_id`) REFERENCES `tool` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_alerts_ibfk_2` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `stock_alerts_ibfk_3` FOREIGN KEY (`resolved_by`) REFERENCES `admin` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Step 5: Create stock_thresholds table
CREATE TABLE IF NOT EXISTS `stock_thresholds` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tool_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `minimum_stock` int(11) DEFAULT 10,
  `reorder_level` int(11) DEFAULT 20,
  `maximum_stock` int(11) DEFAULT 1000,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_tool_location` (`tool_id`, `location_id`),
  KEY `location_id` (`location_id`),
  CONSTRAINT `stock_thresholds_ibfk_1` FOREIGN KEY (`tool_id`) REFERENCES `tool` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_thresholds_ibfk_2` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Step 6: Add location_id to stock_batches table
ALTER TABLE `stock_batches` ADD COLUMN `location_id` int(11) DEFAULT 1 AFTER `supplier`;
ALTER TABLE `stock_batches` ADD KEY `location_id` (`location_id`);
ALTER TABLE `stock_batches` ADD CONSTRAINT `stock_batches_location_fk` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`);

-- Step 7: Update existing stock_batches with default location (Kigali Central Warehouse)
UPDATE `stock_batches` SET `location_id` = 1 WHERE `location_id` IS NULL;

-- Step 8: Insert default stock thresholds for existing tools
INSERT INTO `stock_thresholds` (`tool_id`, `location_id`, `minimum_stock`, `reorder_level`, `maximum_stock`)
SELECT t.`id`, 1, 10, 50, 1000 
FROM `tool` t 
WHERE NOT EXISTS (
  SELECT 1 FROM `stock_thresholds` st 
  WHERE st.`tool_id` = t.`id` AND st.`location_id` = 1
);

-- Step 9: Create return_policy table
CREATE TABLE IF NOT EXISTS `return_policy` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `policy_name` varchar(100) NOT NULL,
  `return_window_days` int(11) DEFAULT 30,
  `restocking_fee_percentage` decimal(5,2) DEFAULT 0.00,
  `condition_requirements` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Insert default return policy
INSERT INTO `return_policy` (`policy_name`, `return_window_days`, `restocking_fee_percentage`, `condition_requirements`) VALUES
('Standard Return Policy', 30, 10.00, 'Items must be in original condition. Like New: Full refund. Minor Wear: 90% refund. Damaged: Case by case review.');

-- Step 10: Create inventory_method table for FIFO/LIFO configuration per tool
CREATE TABLE IF NOT EXISTS `inventory_method` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tool_id` int(11) NOT NULL,
  `method` enum('FIFO','LIFO') DEFAULT 'FIFO',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_tool_method` (`tool_id`),
  CONSTRAINT `inventory_method_ibfk_1` FOREIGN KEY (`tool_id`) REFERENCES `tool` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Insert default FIFO method for all existing tools
INSERT INTO `inventory_method` (`tool_id`, `method`)
SELECT `id`, 'FIFO' 
FROM `tool` 
WHERE NOT EXISTS (
  SELECT 1 FROM `inventory_method` im 
  WHERE im.`tool_id` = `tool`.`id`
);

-- Step 11: Success message
SELECT 'Enhanced Stock Management System with Rwandan locations created successfully!' as message;