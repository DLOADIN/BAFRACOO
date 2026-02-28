-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 28, 2026 at 08:36 PM
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

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_check_refund_eligibility` (IN `p_order_id` INT, IN `p_user_id` INT, OUT `p_eligible` BOOLEAN, OUT `p_message` VARCHAR(255), OUT `p_max_refund_amount` DECIMAL(12,2))   BEGIN
    DECLARE v_order_status VARCHAR(50);
    DECLARE v_order_amount DECIMAL(12,2);
    DECLARE v_refunded_amount DECIMAL(12,2);
    DECLARE v_order_date DATE;
    DECLARE v_refund_window INT;
    DECLARE v_existing_request INT;
    
    SET p_eligible = FALSE;
    SET p_max_refund_amount = 0;
    
    -- Get refund window setting
    SELECT CAST(setting_value AS UNSIGNED) INTO v_refund_window
    FROM refund_policy_settings 
    WHERE setting_name = 'refund_window_days' AND is_active = 1
    LIMIT 1;
    
    IF v_refund_window IS NULL THEN
        SET v_refund_window = 30;
    END IF;
    
    -- Get order details
    SELECT status, u_totalprice, IFNULL(refunded_amount, 0), u_date
    INTO v_order_status, v_order_amount, v_refunded_amount, v_order_date
    FROM `order`
    WHERE id = p_order_id AND user_id = p_user_id;
    
    IF v_order_status IS NULL THEN
        SET p_message = 'Order not found or does not belong to this user';
    ELSEIF v_order_status NOT IN ('Paid', 'Completed', 'Payment Failed') THEN
        SET p_message = 'Order status does not qualify for refund';
    ELSEIF DATEDIFF(CURDATE(), v_order_date) > v_refund_window THEN
        SET p_message = CONCAT('Refund window of ', v_refund_window, ' days has expired');
    ELSE
        -- Check for existing pending requests
        SELECT COUNT(*) INTO v_existing_request
        FROM refund_requests
        WHERE order_id = p_order_id 
          AND status IN ('PENDING', 'UNDER_REVIEW', 'APPROVED');
        
        IF v_existing_request > 0 THEN
            SET p_message = 'A refund request for this order is already in progress';
        ELSE
            SET p_eligible = TRUE;
            SET p_max_refund_amount = v_order_amount - v_refunded_amount;
            SET p_message = 'Order is eligible for refund';
        END IF;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_deduct_stock_fifo_lifo` (IN `p_tool_id` INT, IN `p_quantity` INT, IN `p_order_id` INT, OUT `p_success` BOOLEAN, OUT `p_message` VARCHAR(255))   BEGIN
    DECLARE v_method VARCHAR(10);
    DECLARE v_batch_id INT;
    DECLARE v_batch_quantity INT;
    DECLARE v_remaining_to_deduct INT;
    DECLARE v_deduct_quantity INT;
    DECLARE v_purchase_price DECIMAL(10,2);
    DECLARE done INT DEFAULT FALSE;
    
    -- Cursor for FIFO (oldest first)
    DECLARE fifo_cursor CURSOR FOR 
        SELECT id, quantity_remaining, purchase_price 
        FROM stock_batches 
        WHERE tool_id = p_tool_id AND quantity_remaining > 0
        ORDER BY batch_date ASC;
    
    -- Cursor for LIFO (newest first)
    DECLARE lifo_cursor CURSOR FOR 
        SELECT id, quantity_remaining, purchase_price 
        FROM stock_batches 
        WHERE tool_id = p_tool_id AND quantity_remaining > 0
        ORDER BY batch_date DESC;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    -- Initialize
    SET p_success = FALSE;
    SET p_message = '';
    SET v_remaining_to_deduct = p_quantity;
    
    -- Get the inventory method for this tool
    SELECT COALESCE(method, 'FIFO') INTO v_method 
    FROM inventory_method 
    WHERE tool_id = p_tool_id;
    
    IF v_method IS NULL THEN
        SET v_method = 'FIFO';
    END IF;
    
    -- Check if enough stock available
    IF (SELECT COALESCE(SUM(quantity_remaining), 0) FROM stock_batches WHERE tool_id = p_tool_id) < p_quantity THEN
        SET p_message = 'Insufficient stock available';
        -- Return early
    ELSE
        -- Start transaction
        START TRANSACTION;
        
        IF v_method = 'FIFO' THEN
            OPEN fifo_cursor;
            
            read_loop: LOOP
                FETCH fifo_cursor INTO v_batch_id, v_batch_quantity, v_purchase_price;
                
                IF done OR v_remaining_to_deduct <= 0 THEN
                    LEAVE read_loop;
                END IF;
                
                SET v_deduct_quantity = LEAST(v_remaining_to_deduct, v_batch_quantity);
                
                -- Update batch
                UPDATE stock_batches 
                SET quantity_remaining = quantity_remaining - v_deduct_quantity 
                WHERE id = v_batch_id;
                
                -- Record movement
                INSERT INTO stock_movements (batch_id, order_id, movement_type, quantity, unit_cost, reference)
                VALUES (v_batch_id, p_order_id, 'OUT', v_deduct_quantity, v_purchase_price, CONCAT('ORDER-', LPAD(p_order_id, 6, '0')));
                
                SET v_remaining_to_deduct = v_remaining_to_deduct - v_deduct_quantity;
            END LOOP;
            
            CLOSE fifo_cursor;
        ELSE
            -- LIFO
            OPEN lifo_cursor;
            
            read_loop2: LOOP
                FETCH lifo_cursor INTO v_batch_id, v_batch_quantity, v_purchase_price;
                
                IF done OR v_remaining_to_deduct <= 0 THEN
                    LEAVE read_loop2;
                END IF;
                
                SET v_deduct_quantity = LEAST(v_remaining_to_deduct, v_batch_quantity);
                
                -- Update batch
                UPDATE stock_batches 
                SET quantity_remaining = quantity_remaining - v_deduct_quantity 
                WHERE id = v_batch_id;
                
                -- Record movement
                INSERT INTO stock_movements (batch_id, order_id, movement_type, quantity, unit_cost, reference)
                VALUES (v_batch_id, p_order_id, 'OUT', v_deduct_quantity, v_purchase_price, CONCAT('ORDER-', LPAD(p_order_id, 6, '0')));
                
                SET v_remaining_to_deduct = v_remaining_to_deduct - v_deduct_quantity;
            END LOOP;
            
            CLOSE lifo_cursor;
        END IF;
        
        IF v_remaining_to_deduct = 0 THEN
            COMMIT;
            SET p_success = TRUE;
            SET p_message = CONCAT('Stock deducted successfully using ', v_method, ' method');
        ELSE
            ROLLBACK;
            SET p_message = 'Failed to deduct all required stock';
        END IF;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_process_refund` (IN `p_refund_request_id` INT, IN `p_admin_id` INT, IN `p_stripe_refund_id` VARCHAR(100), IN `p_notes` TEXT, OUT `p_success` BOOLEAN, OUT `p_message` VARCHAR(255))   BEGIN
    DECLARE v_order_id INT;
    DECLARE v_user_id INT;
    DECLARE v_refund_amount DECIMAL(12,2);
    DECLARE v_current_status VARCHAR(50);
    
    -- Initialize
    SET p_success = FALSE;
    
    -- Get refund request details
    SELECT order_id, user_id, refund_amount, status 
    INTO v_order_id, v_user_id, v_refund_amount, v_current_status
    FROM refund_requests 
    WHERE id = p_refund_request_id;
    
    -- Validate status
    IF v_current_status != 'APPROVED' THEN
        SET p_message = 'Refund must be in APPROVED status to process';
    ELSE
        START TRANSACTION;
        
        -- Update refund request status
        UPDATE refund_requests 
        SET status = 'PROCESSED',
            stripe_refund_id = p_stripe_refund_id,
            processed_by = p_admin_id,
            processed_at = NOW(),
            admin_notes = CONCAT(IFNULL(admin_notes, ''), '\n[', NOW(), '] Processed: ', IFNULL(p_notes, ''))
        WHERE id = p_refund_request_id;
        
        -- Create refund transaction record
        INSERT INTO refund_transactions 
            (refund_request_id, order_id, user_id, amount, stripe_refund_id, status, notes, processed_by)
        VALUES 
            (p_refund_request_id, v_order_id, v_user_id, v_refund_amount, p_stripe_refund_id, 'COMPLETED', p_notes, p_admin_id);
        
        -- Update order refund status
        UPDATE `order`
        SET refund_status = 'FULL',
            refunded_amount = IFNULL(refunded_amount, 0) + v_refund_amount
        WHERE id = v_order_id;
        
        -- Log the action
        INSERT INTO refund_audit_log 
            (refund_request_id, action, old_status, new_status, action_by, action_by_type, notes)
        VALUES 
            (p_refund_request_id, 'PROCESS_REFUND', 'APPROVED', 'PROCESSED', p_admin_id, 'ADMIN', p_notes);
        
        COMMIT;
        
        SET p_success = TRUE;
        SET p_message = CONCAT('Refund of ', v_refund_amount, ' RWF processed successfully');
    END IF;
END$$

DELIMITER ;

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
(1, 5, 'FIFO', '2025-12-16 00:48:58', '2025-12-16 00:48:58'),
(2, 6, 'FIFO', '2025-12-16 00:48:58', '2025-12-16 00:48:58'),
(3, 7, 'FIFO', '2025-12-16 00:48:58', '2025-12-16 00:48:58'),
(4, 8, 'FIFO', '2025-12-16 00:48:58', '2025-12-16 00:48:58'),
(5, 9, 'FIFO', '2025-12-16 00:48:58', '2025-12-16 00:48:58'),
(6, 10, 'FIFO', '2025-12-16 00:48:58', '2025-12-16 00:48:58');

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
  `status` varchar(50) NOT NULL DEFAULT 'Pending',
  `stripe_payment_intent` varchar(100) DEFAULT NULL,
  `stripe_charge_id` varchar(100) DEFAULT NULL,
  `refund_status` enum('NONE','PENDING','PARTIAL','FULL') DEFAULT 'NONE',
  `refunded_amount` decimal(12,2) DEFAULT 0.00,
  `payment_date` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `order`
--

INSERT INTO `order` (`id`, `user_id`, `tool_id`, `u_toolname`, `u_itemsnumber`, `u_type`, `u_tooldescription`, `u_date`, `u_price`, `u_totalprice`, `status`, `stripe_payment_intent`, `stripe_charge_id`, `refund_status`, `refunded_amount`, `payment_date`) VALUES
(9, 2, 5, 'APPLES', 11, 'Very Good', 'I love these items', '2024-04-10', 10000, 110000, 'Pending', NULL, NULL, 'NONE', 0.00, NULL),
(11, 1, 6, 'Silicone 500mg', 5, 'Not Good', 'From China', '2024-04-12', 10000, 50000, 'Pending', NULL, NULL, 'NONE', 0.00, NULL),
(24, 1, 6, 'Silicone 500mg', 1, 'Not Good', 'From China', '2025-12-04', 10000, 10000, 'Payment Cancelled', NULL, NULL, 'NONE', 0.00, NULL),
(25, 1, 8, 'Living Room Lamps', 1, 'Very Good', '100', '2026-02-10', 2000, 2000, 'Paid', NULL, NULL, 'NONE', 0.00, NULL),
(26, 1, 12, 'Wheelbarrow', 10, 'Very Good', 'In very good condition', '2026-02-28', 10000, 100000, 'Payment Cancelled', NULL, NULL, 'NONE', 0.00, NULL),
(27, 1, 12, 'Wheelbarrow', 2, 'Very Good', 'In very good condition', '2026-02-28', 10000, 20000, 'Refunded', NULL, NULL, 'NONE', 0.00, NULL),
(28, 1, 9, 'Berryfruits', 1, 'Very Good', 'Some of the berryfruits.', '2026-02-28', 200000, 200000, 'Paid', NULL, NULL, 'NONE', 0.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `refund_audit_log`
--

CREATE TABLE `refund_audit_log` (
  `id` int(11) NOT NULL,
  `refund_request_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) DEFAULT NULL,
  `action_by` int(11) DEFAULT NULL,
  `action_by_type` enum('ADMIN','USER','SYSTEM') DEFAULT 'ADMIN',
  `ip_address` varchar(45) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `refund_policy_settings`
--

CREATE TABLE `refund_policy_settings` (
  `id` int(11) NOT NULL,
  `setting_name` varchar(100) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `refund_policy_settings`
--

INSERT INTO `refund_policy_settings` (`id`, `setting_name`, `setting_value`, `description`, `is_active`, `updated_at`) VALUES
(1, 'refund_window_days', '30', 'Number of days after purchase within which refund can be requested', 1, '2026-02-10 18:02:20'),
(2, 'auto_approve_below_amount', '5000', 'Automatically approve refunds below this amount (in RWF)', 1, '2026-02-10 18:02:20'),
(3, 'require_evidence_above', '50000', 'Require evidence upload for refunds above this amount', 1, '2026-02-10 18:02:20'),
(4, 'allow_partial_refunds', '1', 'Allow partial refund requests (1=yes, 0=no)', 1, '2026-02-10 18:02:20'),
(5, 'restocking_fee_percentage', '0', 'Restocking fee percentage for non-defective returns', 1, '2026-02-10 18:02:20'),
(6, 'payment_failed_auto_approve', '1', 'Auto-approve refunds for failed payments (1=yes, 0=no)', 1, '2026-02-10 18:02:20');

-- --------------------------------------------------------

--
-- Table structure for table `refund_requests`
--

CREATE TABLE `refund_requests` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tool_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `order_amount` decimal(12,2) NOT NULL,
  `refund_amount` decimal(12,2) NOT NULL,
  `refund_reason` enum('PAYMENT_FAILED','DUPLICATE_CHARGE','PRODUCT_NOT_RECEIVED','PRODUCT_DEFECTIVE','WRONG_PRODUCT','CHANGED_MIND','OTHER') NOT NULL,
  `reason_details` text DEFAULT NULL,
  `evidence_file` varchar(255) DEFAULT NULL,
  `status` enum('PENDING','UNDER_REVIEW','APPROVED','REJECTED','PROCESSED','CANCELLED') DEFAULT 'PENDING',
  `admin_notes` text DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `stripe_refund_id` varchar(100) DEFAULT NULL,
  `stripe_payment_intent` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `refund_requests`
--

INSERT INTO `refund_requests` (`id`, `order_id`, `user_id`, `tool_name`, `quantity`, `order_amount`, `refund_amount`, `refund_reason`, `reason_details`, `evidence_file`, `status`, `admin_notes`, `processed_by`, `stripe_refund_id`, `stripe_payment_intent`, `created_at`, `updated_at`, `processed_at`) VALUES
(1, 25, 1, 'Living Room Lamps', 1, 2000.00, 2000.00, 'WRONG_PRODUCT', 'rrj', NULL, 'REJECTED', 'not valid', 9, NULL, '', '2026-02-10 18:34:22', '2026-02-10 18:40:23', NULL),
(2, 27, 1, 'Wheelbarrow', 2, 20000.00, 20000.00, 'CHANGED_MIND', 'gjgjkghgj', NULL, 'PROCESSED', 'hghgjhg', 9, 're_2c0de1278a822a4fbc858b21', '', '2026-02-28 18:48:18', '2026-02-28 18:49:01', '2026-02-28 18:49:01');

-- --------------------------------------------------------

--
-- Table structure for table `refund_transactions`
--

CREATE TABLE `refund_transactions` (
  `id` int(11) NOT NULL,
  `refund_request_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `stripe_refund_id` varchar(100) DEFAULT NULL,
  `status` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `refund_transactions`
--

INSERT INTO `refund_transactions` (`id`, `refund_request_id`, `order_id`, `user_id`, `amount`, `stripe_refund_id`, `status`, `created_at`) VALUES
(1, 2, 27, 1, 20000.00, 're_2c0de1278a822a4fbc858b21', 'COMPLETED', '2026-02-28 18:49:01');

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
(4, 5, 'BATCH-0005-002', 100, 100, 300000.00, '2025-12-03 19:56:59', '0', 6, '2026-01-01', '2025-12-03 17:56:59', '2025-12-03 17:56:59'),
(5, 8, 'BATCH-INITIAL-0008', 100, 100, 1600.00, '2025-11-21 00:00:00', 'Initial Stock', 1, NULL, '2025-12-16 00:48:58', '2025-12-16 00:48:58'),
(6, 9, 'BATCH-INITIAL-0009', 20, 20, 160000.00, '2025-12-02 00:00:00', 'Initial Stock', 1, NULL, '2025-12-16 00:48:58', '2025-12-16 00:48:58'),
(7, 10, 'BATCH-INITIAL-0010', 30, 30, 32000.00, '2025-12-16 00:00:00', 'Initial Stock', 1, NULL, '2025-12-16 00:48:58', '2025-12-16 00:48:58');

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
(6, 'Silicone 500mg', 2, 'Not Good', 'From China', '2024-04-09', 10000, NULL),
(7, 'Mangos', 121212, 'Mangos', '1212', '2025-11-09', 1212, NULL),
(8, 'Living Room Lamps', 99, 'Very Good', '100', '2025-11-21', 2000, NULL),
(9, 'Berryfruits', 9, 'Very Good', 'Some of the berryfruits.', '2025-12-02', 200000, NULL),
(10, 'Cinnamon Flour', 30, 'Very Good', 'Quality Cinnamon Flour', '2025-12-16', 40000, 'uploads/tools/tool_1765843444_6940a1f4d1fba.jpeg'),
(11, 'Berryfruits', 10000, 'Very Good', 'Berryfruits are good for health', '2025-12-16', 900, 'uploads/tools/tool_1765848888_6940b73808f08.png'),
(12, 'Wheelbarrow', 100, 'Very Good', 'In very good condition', '2026-02-11', 10000, 'uploads/tools/tool_1770833411_698cc6038df53.jpeg'),
(13, 'Wheelbarrow', 100, 'Very Good', 'In very good condition', '2026-02-27', 10000, 'uploads/tools/tool_1772302389_69a3303535e61.jpeg');

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

--
-- Dumping data for table `transaction`
--

INSERT INTO `transaction` (`id`, `u_toolname`, `u_item`, `u_type`, `u_amount`, `u_status`, `u_date`, `u_id`, `order_id`) VALUES
(0, 'Living Room Lamps', '1', 'Very Good', '2000', 'Completed', '2026-02-10', 1, 25);

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
(1, 'Hendricks', 'david@gmail.com', '0791291003', 'Musanze', '12345'),
(2, 'Ganza', 'manzidavid111@gmail.com', '188171212', 'Kigalui', 'Chrispaul_120'),
(3, 'Test User', 'test123@test.com', '0781234567', 'Kigali', 'test123'),
(4, 'TestUser', 'testuser999@test.com', '0781234567', 'Kigali', 'test123'),
(5, 'Marie Claire', 'marieclaire@gmail.com', '0728375922', 'Kigali', 'majweuh4890239!');

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_current_stock_by_method`
-- (See below for the actual view)
--
CREATE TABLE `vw_current_stock_by_method` (
`tool_id` int(11)
,`u_toolname` varchar(80)
,`tool_table_stock` int(11)
,`batch_total_stock` decimal(32,0)
,`inventory_method` varchar(4)
,`total_batches` bigint(21)
,`oldest_batch_date` datetime
,`newest_batch_date` datetime
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_fifo_stock_order`
-- (See below for the actual view)
--
CREATE TABLE `vw_fifo_stock_order` (
`batch_id` int(11)
,`tool_id` int(11)
,`tool_name` varchar(80)
,`batch_number` varchar(50)
,`quantity_remaining` int(11)
,`purchase_price` decimal(10,2)
,`batch_date` datetime
,`supplier` varchar(100)
,`location_name` varchar(100)
,`inventory_method` enum('FIFO','LIFO')
,`fifo_order` bigint(21)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_lifo_stock_order`
-- (See below for the actual view)
--
CREATE TABLE `vw_lifo_stock_order` (
`batch_id` int(11)
,`tool_id` int(11)
,`tool_name` varchar(80)
,`batch_number` varchar(50)
,`quantity_remaining` int(11)
,`purchase_price` decimal(10,2)
,`batch_date` datetime
,`supplier` varchar(100)
,`location_name` varchar(100)
,`inventory_method` enum('FIFO','LIFO')
,`lifo_order` bigint(21)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_pending_refunds`
-- (See below for the actual view)
--
CREATE TABLE `vw_pending_refunds` (
`refund_id` int(11)
,`order_id` int(11)
,`customer_name` varchar(80)
,`customer_email` varchar(80)
,`tool_name` varchar(100)
,`refund_amount` decimal(12,2)
,`refund_reason` enum('PAYMENT_FAILED','DUPLICATE_CHARGE','PRODUCT_NOT_RECEIVED','PRODUCT_DEFECTIVE','WRONG_PRODUCT','CHANGED_MIND','OTHER')
,`status` enum('PENDING','UNDER_REVIEW','APPROVED','REJECTED','PROCESSED','CANCELLED')
,`created_at` timestamp
,`days_pending` int(7)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_refund_statistics`
-- (See below for the actual view)
--
CREATE TABLE `vw_refund_statistics` (
`month` varchar(7)
,`total_requests` bigint(21)
,`processed_count` decimal(22,0)
,`rejected_count` decimal(22,0)
,`pending_count` decimal(22,0)
,`total_refunded_amount` decimal(34,2)
,`avg_refund_amount` decimal(16,6)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_user_refund_history`
-- (See below for the actual view)
--
CREATE TABLE `vw_user_refund_history` (
`user_id` int(11)
,`customer_name` varchar(80)
,`total_refund_requests` bigint(21)
,`approved_refunds` decimal(22,0)
,`rejected_refunds` decimal(22,0)
,`total_refunded` decimal(34,2)
,`last_refund_request` timestamp
);

-- --------------------------------------------------------

--
-- Structure for view `vw_current_stock_by_method`
--
DROP TABLE IF EXISTS `vw_current_stock_by_method`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_current_stock_by_method`  AS SELECT `t`.`id` AS `tool_id`, `t`.`u_toolname` AS `u_toolname`, `t`.`u_itemsnumber` AS `tool_table_stock`, coalesce(sum(`sb`.`quantity_remaining`),0) AS `batch_total_stock`, coalesce(`im`.`method`,'FIFO') AS `inventory_method`, count(distinct `sb`.`id`) AS `total_batches`, min(`sb`.`batch_date`) AS `oldest_batch_date`, max(`sb`.`batch_date`) AS `newest_batch_date` FROM ((`tool` `t` left join `stock_batches` `sb` on(`t`.`id` = `sb`.`tool_id` and `sb`.`quantity_remaining` > 0)) left join `inventory_method` `im` on(`t`.`id` = `im`.`tool_id`)) GROUP BY `t`.`id`, `t`.`u_toolname`, `t`.`u_itemsnumber`, `im`.`method` ;

-- --------------------------------------------------------

--
-- Structure for view `vw_fifo_stock_order`
--
DROP TABLE IF EXISTS `vw_fifo_stock_order`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_fifo_stock_order`  AS SELECT `sb`.`id` AS `batch_id`, `sb`.`tool_id` AS `tool_id`, `t`.`u_toolname` AS `tool_name`, `sb`.`batch_number` AS `batch_number`, `sb`.`quantity_remaining` AS `quantity_remaining`, `sb`.`purchase_price` AS `purchase_price`, `sb`.`batch_date` AS `batch_date`, `sb`.`supplier` AS `supplier`, `l`.`location_name` AS `location_name`, `im`.`method` AS `inventory_method`, row_number() over ( partition by `sb`.`tool_id` order by `sb`.`batch_date`) AS `fifo_order` FROM (((`stock_batches` `sb` join `tool` `t` on(`sb`.`tool_id` = `t`.`id`)) join `locations` `l` on(`sb`.`location_id` = `l`.`id`)) left join `inventory_method` `im` on(`sb`.`tool_id` = `im`.`tool_id`)) WHERE `sb`.`quantity_remaining` > 0 ORDER BY `sb`.`tool_id` ASC, `sb`.`batch_date` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `vw_lifo_stock_order`
--
DROP TABLE IF EXISTS `vw_lifo_stock_order`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_lifo_stock_order`  AS SELECT `sb`.`id` AS `batch_id`, `sb`.`tool_id` AS `tool_id`, `t`.`u_toolname` AS `tool_name`, `sb`.`batch_number` AS `batch_number`, `sb`.`quantity_remaining` AS `quantity_remaining`, `sb`.`purchase_price` AS `purchase_price`, `sb`.`batch_date` AS `batch_date`, `sb`.`supplier` AS `supplier`, `l`.`location_name` AS `location_name`, `im`.`method` AS `inventory_method`, row_number() over ( partition by `sb`.`tool_id` order by `sb`.`batch_date` desc) AS `lifo_order` FROM (((`stock_batches` `sb` join `tool` `t` on(`sb`.`tool_id` = `t`.`id`)) join `locations` `l` on(`sb`.`location_id` = `l`.`id`)) left join `inventory_method` `im` on(`sb`.`tool_id` = `im`.`tool_id`)) WHERE `sb`.`quantity_remaining` > 0 ORDER BY `sb`.`tool_id` ASC, `sb`.`batch_date` DESC ;

-- --------------------------------------------------------

--
-- Structure for view `vw_pending_refunds`
--
DROP TABLE IF EXISTS `vw_pending_refunds`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_pending_refunds`  AS SELECT `rr`.`id` AS `refund_id`, `rr`.`order_id` AS `order_id`, `u`.`u_name` AS `customer_name`, `u`.`u_email` AS `customer_email`, `rr`.`tool_name` AS `tool_name`, `rr`.`refund_amount` AS `refund_amount`, `rr`.`refund_reason` AS `refund_reason`, `rr`.`status` AS `status`, `rr`.`created_at` AS `created_at`, to_days(current_timestamp()) - to_days(`rr`.`created_at`) AS `days_pending` FROM (`refund_requests` `rr` join `user` `u` on(`rr`.`user_id` = `u`.`id`)) WHERE `rr`.`status` in ('PENDING','UNDER_REVIEW') ORDER BY `rr`.`created_at` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `vw_refund_statistics`
--
DROP TABLE IF EXISTS `vw_refund_statistics`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_refund_statistics`  AS SELECT date_format(`rr`.`created_at`,'%Y-%m') AS `month`, count(0) AS `total_requests`, sum(case when `rr`.`status` = 'PROCESSED' then 1 else 0 end) AS `processed_count`, sum(case when `rr`.`status` = 'REJECTED' then 1 else 0 end) AS `rejected_count`, sum(case when `rr`.`status` = 'PENDING' then 1 else 0 end) AS `pending_count`, sum(case when `rr`.`status` = 'PROCESSED' then `rr`.`refund_amount` else 0 end) AS `total_refunded_amount`, avg(case when `rr`.`status` = 'PROCESSED' then `rr`.`refund_amount` else NULL end) AS `avg_refund_amount` FROM `refund_requests` AS `rr` GROUP BY date_format(`rr`.`created_at`,'%Y-%m') ORDER BY date_format(`rr`.`created_at`,'%Y-%m') DESC ;

-- --------------------------------------------------------

--
-- Structure for view `vw_user_refund_history`
--
DROP TABLE IF EXISTS `vw_user_refund_history`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_user_refund_history`  AS SELECT `rr`.`user_id` AS `user_id`, `u`.`u_name` AS `customer_name`, count(0) AS `total_refund_requests`, sum(case when `rr`.`status` = 'PROCESSED' then 1 else 0 end) AS `approved_refunds`, sum(case when `rr`.`status` = 'REJECTED' then 1 else 0 end) AS `rejected_refunds`, sum(case when `rr`.`status` = 'PROCESSED' then `rr`.`refund_amount` else 0 end) AS `total_refunded`, max(`rr`.`created_at`) AS `last_refund_request` FROM (`refund_requests` `rr` join `user` `u` on(`rr`.`user_id` = `u`.`id`)) GROUP BY `rr`.`user_id`, `u`.`u_name` ORDER BY count(0) DESC ;

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
-- Indexes for table `refund_audit_log`
--
ALTER TABLE `refund_audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_refund_request_id` (`refund_request_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `refund_policy_settings`
--
ALTER TABLE `refund_policy_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_setting_name` (`setting_name`);

--
-- Indexes for table `refund_requests`
--
ALTER TABLE `refund_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `fk_refund_requests_admin` (`processed_by`);

--
-- Indexes for table `refund_transactions`
--
ALTER TABLE `refund_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_refund_request_id` (`refund_request_id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `fk_refund_transactions_user` (`user_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `order`
--
ALTER TABLE `order`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `refund_audit_log`
--
ALTER TABLE `refund_audit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `refund_policy_settings`
--
ALTER TABLE `refund_policy_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `refund_requests`
--
ALTER TABLE `refund_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `refund_transactions`
--
ALTER TABLE `refund_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
-- Constraints for table `refund_requests`
--
ALTER TABLE `refund_requests`
  ADD CONSTRAINT `fk_refund_requests_admin` FOREIGN KEY (`processed_by`) REFERENCES `admin` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_refund_requests_order` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_refund_requests_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `refund_transactions`
--
ALTER TABLE `refund_transactions`
  ADD CONSTRAINT `fk_refund_transactions_order` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_refund_transactions_request` FOREIGN KEY (`refund_request_id`) REFERENCES `refund_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_refund_transactions_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

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
