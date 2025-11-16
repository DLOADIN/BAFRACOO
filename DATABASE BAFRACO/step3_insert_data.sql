-- Step 5: Insert sample data (run this after the previous steps work)

-- Insert inventory methods for existing tools (FIFO by default)
INSERT INTO `inventory_method` (`tool_id`, `method`) 
SELECT `id`, 'FIFO' FROM `tool` 
WHERE `id` NOT IN (SELECT `tool_id` FROM `inventory_method` WHERE `tool_id` = `tool`.`id`);

-- Insert sample stock batches for existing tools
INSERT INTO `stock_batches` (`tool_id`, `batch_number`, `quantity_received`, `quantity_remaining`, `purchase_price`, `batch_date`, `supplier`) 
SELECT 
  `id`,
  CONCAT('BATCH-', LPAD(`id`, 4, '0'), '-001'),
  `u_itemsnumber`,
  `u_itemsnumber`,
  ROUND(`u_price` * 0.8, 2),
  DATE_SUB(`u_date`, INTERVAL 15 DAY),
  'Default Supplier'
FROM `tool` 
WHERE `id` NOT IN (SELECT `tool_id` FROM `stock_batches` WHERE `tool_id` = `tool`.`id`);

-- Insert initial stock movements
INSERT INTO `stock_movements` (`batch_id`, `movement_type`, `quantity`, `unit_cost`, `reference`, `notes`) 
SELECT 
  sb.`id`,
  'IN',
  sb.`quantity_received`,
  sb.`purchase_price`,
  CONCAT('INITIAL-', sb.`batch_number`),
  'Initial stock entry'
FROM `stock_batches` sb 
WHERE sb.`id` NOT IN (SELECT `batch_id` FROM `stock_movements` WHERE `batch_id` = sb.`id` AND `movement_type` = 'IN');