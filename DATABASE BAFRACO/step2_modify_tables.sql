-- Step 4: Modify existing tables (run these separately if the first file works)
-- Add status column to order table (only run if you get an error, ignore if column exists)
ALTER TABLE `order` ADD COLUMN `status` enum('PENDING','PROCESSING','COMPLETED','CANCELLED') DEFAULT 'PENDING';

-- Add order_id column to transaction table (only run if you get an error, ignore if column exists)
ALTER TABLE `transaction` ADD COLUMN `order_id` int(11) DEFAULT NULL;

-- Add index for order_id (ignore error if index exists)
ALTER TABLE `transaction` ADD KEY `order_id` (`order_id`);

-- Add foreign key constraint (ignore error if constraint exists)
ALTER TABLE `transaction` ADD CONSTRAINT `transaction_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`) ON DELETE SET NULL;