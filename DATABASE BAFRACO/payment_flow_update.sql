-- Payment Flow Database Update
-- Run this SQL to add tool_id column to order table for payment tracking

-- Add tool_id column to order table if it doesn't exist
ALTER TABLE `order` ADD COLUMN IF NOT EXISTS `tool_id` INT(11) DEFAULT NULL AFTER `user_id`;

-- Add index on tool_id for better performance
ALTER TABLE `order` ADD INDEX IF NOT EXISTS `idx_order_tool_id` (`tool_id`);

-- Update existing orders to set tool_id based on tool name (if needed)
UPDATE `order` o
INNER JOIN `tool` t ON o.u_toolname = t.u_toolname
SET o.tool_id = t.id
WHERE o.tool_id IS NULL;

-- Add status values for payment flow
-- Status values: 'Pending Payment', 'Paid', 'Payment Cancelled', 'Payment Failed', 'Completed', 'Pending'
