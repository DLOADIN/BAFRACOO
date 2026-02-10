-- =====================================================
-- BAFRACO Refund/Reimbursement System SQL Updates
-- Run these queries in phpMyAdmin to ensure the refund system works properly
-- =====================================================

-- =====================================================
-- 1. UPDATE ORDER TABLE - Add missing refund-related columns
-- =====================================================

-- Add refund status column to track if order has been refunded
ALTER TABLE `order` 
ADD COLUMN IF NOT EXISTS `refund_status` ENUM('NONE', 'PENDING', 'PARTIAL', 'FULL') DEFAULT 'NONE' AFTER `stripe_charge_id`;

-- Add refunded amount column
ALTER TABLE `order` 
ADD COLUMN IF NOT EXISTS `refunded_amount` DECIMAL(12,2) DEFAULT 0.00 AFTER `refund_status`;

-- Add payment_date column to track when payment was made
ALTER TABLE `order` 
ADD COLUMN IF NOT EXISTS `payment_date` TIMESTAMP NULL DEFAULT NULL AFTER `refunded_amount`;

-- =====================================================
-- 2. ENSURE REFUND_REQUESTS TABLE EXISTS WITH ALL COLUMNS
-- =====================================================

-- Drop and recreate if you want a clean start (CAREFUL - this deletes data!)
-- DROP TABLE IF EXISTS `refund_transactions`;
-- DROP TABLE IF EXISTS `refund_requests`;

-- Create refund_requests table if not exists
CREATE TABLE IF NOT EXISTS `refund_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `processed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 3. ENSURE REFUND_TRANSACTIONS TABLE EXISTS
-- =====================================================

CREATE TABLE IF NOT EXISTS `refund_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `refund_request_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `stripe_refund_id` varchar(100) DEFAULT NULL,
  `refund_method` ENUM('STRIPE', 'MANUAL', 'BANK_TRANSFER', 'STORE_CREDIT') DEFAULT 'STRIPE',
  `status` varchar(50) NOT NULL,
  `notes` text DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_refund_request_id` (`refund_request_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 4. ADD FOREIGN KEY CONSTRAINTS (if not already added)
-- =====================================================

-- Note: Run these one by one. If constraint already exists, it will error - that's OK

-- Foreign key for refund_requests.order_id
ALTER TABLE `refund_requests` 
ADD CONSTRAINT `fk_refund_requests_order` 
FOREIGN KEY (`order_id`) REFERENCES `order`(`id`) ON DELETE CASCADE;

-- Foreign key for refund_requests.user_id  
ALTER TABLE `refund_requests` 
ADD CONSTRAINT `fk_refund_requests_user` 
FOREIGN KEY (`user_id`) REFERENCES `user`(`id`) ON DELETE CASCADE;

-- Foreign key for refund_requests.processed_by
ALTER TABLE `refund_requests` 
ADD CONSTRAINT `fk_refund_requests_admin` 
FOREIGN KEY (`processed_by`) REFERENCES `admin`(`id`) ON DELETE SET NULL;

-- Foreign key for refund_transactions.refund_request_id
ALTER TABLE `refund_transactions` 
ADD CONSTRAINT `fk_refund_transactions_request` 
FOREIGN KEY (`refund_request_id`) REFERENCES `refund_requests`(`id`) ON DELETE CASCADE;

-- Foreign key for refund_transactions.order_id
ALTER TABLE `refund_transactions` 
ADD CONSTRAINT `fk_refund_transactions_order` 
FOREIGN KEY (`order_id`) REFERENCES `order`(`id`) ON DELETE CASCADE;

-- Foreign key for refund_transactions.user_id
ALTER TABLE `refund_transactions` 
ADD CONSTRAINT `fk_refund_transactions_user` 
FOREIGN KEY (`user_id`) REFERENCES `user`(`id`) ON DELETE CASCADE;

-- =====================================================
-- 5. CREATE REFUND POLICY SETTINGS TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS `refund_policy_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_name` varchar(100) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_setting_name` (`setting_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default refund policy settings
INSERT INTO `refund_policy_settings` (`setting_name`, `setting_value`, `description`) VALUES
('refund_window_days', '30', 'Number of days after purchase within which refund can be requested'),
('auto_approve_below_amount', '5000', 'Automatically approve refunds below this amount (in RWF)'),
('require_evidence_above', '50000', 'Require evidence upload for refunds above this amount'),
('allow_partial_refunds', '1', 'Allow partial refund requests (1=yes, 0=no)'),
('restocking_fee_percentage', '0', 'Restocking fee percentage for non-defective returns'),
('payment_failed_auto_approve', '1', 'Auto-approve refunds for failed payments (1=yes, 0=no)')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- =====================================================
-- 6. CREATE REFUND AUDIT LOG TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS `refund_audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `refund_request_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) DEFAULT NULL,
  `action_by` int(11) DEFAULT NULL,
  `action_by_type` ENUM('ADMIN', 'USER', 'SYSTEM') DEFAULT 'ADMIN',
  `ip_address` varchar(45) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_refund_request_id` (`refund_request_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 7. CREATE USEFUL VIEWS FOR REFUND REPORTING
-- =====================================================

-- View: Pending refund requests summary
CREATE OR REPLACE VIEW `vw_pending_refunds` AS
SELECT 
    rr.id AS refund_id,
    rr.order_id,
    u.u_name AS customer_name,
    u.u_email AS customer_email,
    rr.tool_name,
    rr.refund_amount,
    rr.refund_reason,
    rr.status,
    rr.created_at,
    DATEDIFF(NOW(), rr.created_at) AS days_pending
FROM refund_requests rr
JOIN `user` u ON rr.user_id = u.id
WHERE rr.status IN ('PENDING', 'UNDER_REVIEW')
ORDER BY rr.created_at ASC;

-- View: Refund statistics by month
CREATE OR REPLACE VIEW `vw_refund_statistics` AS
SELECT 
    DATE_FORMAT(rr.created_at, '%Y-%m') AS month,
    COUNT(*) AS total_requests,
    SUM(CASE WHEN rr.status = 'PROCESSED' THEN 1 ELSE 0 END) AS processed_count,
    SUM(CASE WHEN rr.status = 'REJECTED' THEN 1 ELSE 0 END) AS rejected_count,
    SUM(CASE WHEN rr.status = 'PENDING' THEN 1 ELSE 0 END) AS pending_count,
    SUM(CASE WHEN rr.status = 'PROCESSED' THEN rr.refund_amount ELSE 0 END) AS total_refunded_amount,
    AVG(CASE WHEN rr.status = 'PROCESSED' THEN rr.refund_amount ELSE NULL END) AS avg_refund_amount
FROM refund_requests rr
GROUP BY DATE_FORMAT(rr.created_at, '%Y-%m')
ORDER BY month DESC;

-- View: User refund history
CREATE OR REPLACE VIEW `vw_user_refund_history` AS
SELECT 
    rr.user_id,
    u.u_name AS customer_name,
    COUNT(*) AS total_refund_requests,
    SUM(CASE WHEN rr.status = 'PROCESSED' THEN 1 ELSE 0 END) AS approved_refunds,
    SUM(CASE WHEN rr.status = 'REJECTED' THEN 1 ELSE 0 END) AS rejected_refunds,
    SUM(CASE WHEN rr.status = 'PROCESSED' THEN rr.refund_amount ELSE 0 END) AS total_refunded,
    MAX(rr.created_at) AS last_refund_request
FROM refund_requests rr
JOIN `user` u ON rr.user_id = u.id
GROUP BY rr.user_id, u.u_name
ORDER BY total_refund_requests DESC;

-- =====================================================
-- 8. UPDATE EXISTING ORDERS TO HAVE STRIPE INFO (for testing)
-- =====================================================

-- Update paid orders to have a dummy stripe payment intent for testing
-- ONLY run this if you need test data
/*
UPDATE `order` 
SET stripe_payment_intent = CONCAT('pi_test_', id, '_', UNIX_TIMESTAMP()),
    stripe_charge_id = CONCAT('ch_test_', id, '_', UNIX_TIMESTAMP()),
    payment_date = u_date
WHERE status = 'Paid' 
  AND stripe_payment_intent IS NULL;
*/

-- =====================================================
-- 9. STORED PROCEDURE: Process Refund
-- =====================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS `sp_process_refund`$$

CREATE PROCEDURE `sp_process_refund`(
    IN p_refund_request_id INT,
    IN p_admin_id INT,
    IN p_stripe_refund_id VARCHAR(100),
    IN p_notes TEXT,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
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

-- =====================================================
-- 10. STORED PROCEDURE: Get Refund Eligibility
-- =====================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS `sp_check_refund_eligibility`$$

CREATE PROCEDURE `sp_check_refund_eligibility`(
    IN p_order_id INT,
    IN p_user_id INT,
    OUT p_eligible BOOLEAN,
    OUT p_message VARCHAR(255),
    OUT p_max_refund_amount DECIMAL(12,2)
)
BEGIN
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

DELIMITER ;

-- =====================================================
-- 11. SAMPLE DATA FOR TESTING (Optional)
-- =====================================================

-- Uncomment below to insert test refund request
/*
INSERT INTO `refund_requests` 
(order_id, user_id, tool_name, quantity, order_amount, refund_amount, refund_reason, reason_details, status)
VALUES 
(25, 1, 'Living Room Lamps', 1, 2000.00, 2000.00, 'PRODUCT_DEFECTIVE', 'The lamp stopped working after 2 days', 'PENDING');
*/

-- =====================================================
-- VERIFICATION QUERIES - Run these to verify setup
-- =====================================================

-- Check if all tables exist
SELECT 'refund_requests' AS table_name, COUNT(*) AS record_count FROM refund_requests
UNION ALL
SELECT 'refund_transactions', COUNT(*) FROM refund_transactions
UNION ALL
SELECT 'refund_policy_settings', COUNT(*) FROM refund_policy_settings
UNION ALL
SELECT 'refund_audit_log', COUNT(*) FROM refund_audit_log;

-- Check order table has new columns
DESCRIBE `order`;

-- Check views exist
SHOW FULL TABLES WHERE Table_type = 'VIEW' AND Tables_in_bafraco LIKE 'vw_refund%';

-- Check stored procedures exist
SHOW PROCEDURE STATUS WHERE Db = 'bafraco' AND Name LIKE 'sp_%refund%';

