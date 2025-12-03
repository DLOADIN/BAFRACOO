-- Database Updates for BAFRACOO System
-- Run this SQL to add the necessary tables and columns
-- Date: December 2025

-- ============================================
-- 1. Add image_url column to tool table
-- ============================================
ALTER TABLE `tool` ADD COLUMN IF NOT EXISTS `image_url` VARCHAR(255) DEFAULT NULL;

-- ============================================
-- 2. Create damaged_goods table
-- ============================================
CREATE TABLE IF NOT EXISTS `damaged_goods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tool_id` int(11) NOT NULL,
  `tool_name` varchar(80) NOT NULL,
  `quantity_removed` int(11) NOT NULL,
  `damage_reason` varchar(255) NOT NULL,
  `damage_date` datetime DEFAULT current_timestamp(),
  `reported_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `original_value` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tool_id` (`tool_id`),
  KEY `damage_date` (`damage_date`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- ============================================
-- 3. Create returned_stock table
-- ============================================
CREATE TABLE IF NOT EXISTS `returned_stock` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tool_id` int(11) NOT NULL,
  `tool_name` varchar(80) NOT NULL,
  `quantity_returned` int(11) NOT NULL,
  `return_reason` varchar(255) NOT NULL,
  `return_date` datetime DEFAULT current_timestamp(),
  `condition_status` enum('GOOD','DAMAGED','UNUSABLE') DEFAULT 'GOOD',
  `processed_by` int(11) DEFAULT NULL,
  `restock_status` enum('PENDING','RESTOCKED','WRITTEN_OFF') DEFAULT 'PENDING',
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tool_id` (`tool_id`),
  KEY `return_date` (`return_date`),
  KEY `restock_status` (`restock_status`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
