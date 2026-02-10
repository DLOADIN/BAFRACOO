<?php
/**
 * BAFRACOO Refund System Setup
 * Run this file once to create the refund_requests table
 */

require 'connection.php';

echo "<h2>BAFRACOO Refund System Setup</h2>";
echo "<pre>";

// Create refund_requests table
$sql_refund_requests = "
CREATE TABLE IF NOT EXISTS `refund_requests` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `order_id` INT(11) NOT NULL,
    `user_id` INT(11) NOT NULL,
    `tool_name` VARCHAR(100) NOT NULL,
    `quantity` INT(11) NOT NULL,
    `order_amount` DECIMAL(12,2) NOT NULL,
    `refund_amount` DECIMAL(12,2) NOT NULL,
    `refund_reason` ENUM('PAYMENT_FAILED', 'DUPLICATE_CHARGE', 'PRODUCT_NOT_RECEIVED', 'PRODUCT_DEFECTIVE', 'WRONG_PRODUCT', 'CHANGED_MIND', 'OTHER') NOT NULL,
    `reason_details` TEXT,
    `evidence_file` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('PENDING', 'UNDER_REVIEW', 'APPROVED', 'REJECTED', 'PROCESSED', 'CANCELLED') DEFAULT 'PENDING',
    `admin_notes` TEXT,
    `processed_by` INT(11) DEFAULT NULL,
    `stripe_refund_id` VARCHAR(100) DEFAULT NULL,
    `stripe_payment_intent` VARCHAR(100) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `processed_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_status` (`status`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if(mysqli_query($con, $sql_refund_requests)){
    echo "✓ refund_requests table created successfully\n";
} else {
    echo "✗ Error creating refund_requests table: " . mysqli_error($con) . "\n";
}

// Add stripe_payment_intent column to order table if not exists
$check_column = mysqli_query($con, "SHOW COLUMNS FROM `order` LIKE 'stripe_payment_intent'");
if(mysqli_num_rows($check_column) == 0){
    $sql_add_column = "ALTER TABLE `order` ADD COLUMN `stripe_payment_intent` VARCHAR(100) DEFAULT NULL AFTER `status`";
    if(mysqli_query($con, $sql_add_column)){
        echo "✓ Added stripe_payment_intent column to order table\n";
    } else {
        echo "✗ Error adding stripe_payment_intent column: " . mysqli_error($con) . "\n";
    }
} else {
    echo "✓ stripe_payment_intent column already exists in order table\n";
}

// Add stripe_charge_id column to order table if not exists
$check_column2 = mysqli_query($con, "SHOW COLUMNS FROM `order` LIKE 'stripe_charge_id'");
if(mysqli_num_rows($check_column2) == 0){
    $sql_add_column2 = "ALTER TABLE `order` ADD COLUMN `stripe_charge_id` VARCHAR(100) DEFAULT NULL AFTER `stripe_payment_intent`";
    if(mysqli_query($con, $sql_add_column2)){
        echo "✓ Added stripe_charge_id column to order table\n";
    } else {
        echo "✗ Error adding stripe_charge_id column: " . mysqli_error($con) . "\n";
    }
} else {
    echo "✓ stripe_charge_id column already exists in order table\n";
}

// Create refund_transactions table for tracking
$sql_refund_transactions = "
CREATE TABLE IF NOT EXISTS `refund_transactions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `refund_request_id` INT(11) NOT NULL,
    `order_id` INT(11) NOT NULL,
    `user_id` INT(11) NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `stripe_refund_id` VARCHAR(100) DEFAULT NULL,
    `status` VARCHAR(50) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_refund_request_id` (`refund_request_id`),
    KEY `idx_order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if(mysqli_query($con, $sql_refund_transactions)){
    echo "✓ refund_transactions table created successfully\n";
} else {
    echo "✗ Error creating refund_transactions table: " . mysqli_error($con) . "\n";
}

echo "\n</pre>";
echo "<h3 style='color: green;'>✓ Refund system setup complete!</h3>";
echo "<p><a href='admindashboard.php'>Go to Admin Dashboard</a></p>";
?>
