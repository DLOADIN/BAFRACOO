-- =====================================================
-- BAFRACOO E-Commerce Cart System Migration
-- Feature 3: Ordering Multiple Items (Cart)
-- =====================================================
-- Run this script to add cart functionality to your database
-- Date: 2026-02-17
-- =====================================================

-- --------------------------------------------------------
-- Table structure for table `cart`
-- Stores user shopping carts
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `cart` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `status` enum('ACTIVE','CHECKED_OUT','ABANDONED','EXPIRED') DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cart_user` (`user_id`),
  KEY `idx_cart_status` (`status`),
  CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `cart_items`
-- Stores items added to a cart with quantities
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `cart_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cart_id` int(11) NOT NULL,
  `tool_id` int(11) NOT NULL,
  `tool_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(12,2) NOT NULL COMMENT 'Sale price at time of adding',
  `total_price` decimal(12,2) GENERATED ALWAYS AS (`quantity` * `unit_price`) STORED,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_cart_tool` (`cart_id`, `tool_id`),
  KEY `idx_cart_items_cart` (`cart_id`),
  KEY `idx_cart_items_tool` (`tool_id`),
  CONSTRAINT `fk_cart_items_cart` FOREIGN KEY (`cart_id`) REFERENCES `cart` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cart_items_tool` FOREIGN KEY (`tool_id`) REFERENCES `tool` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `order_items`
-- Tracks individual items in an order with FIFO batch references
-- Links orders to specific stock batches that were used
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `tool_id` int(11) NOT NULL,
  `tool_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL COMMENT 'Sale price per unit',
  `total_price` decimal(12,2) NOT NULL COMMENT 'Quantity x Unit Price',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_order_items_order` (`order_id`),
  KEY `idx_order_items_tool` (`tool_id`),
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_items_tool` FOREIGN KEY (`tool_id`) REFERENCES `tool` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `order_item_batches`
-- Tracks which stock batches were used for each order item (FIFO tracking)
-- This is the key table for FIFO accounting - records Purchase Price + Sale Price per batch
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `order_item_batches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_item_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `quantity_from_batch` int(11) NOT NULL COMMENT 'How many units came from this batch',
  `purchase_price` decimal(12,2) NOT NULL COMMENT 'Cost price from batch (for profit calc)',
  `sale_price` decimal(12,2) NOT NULL COMMENT 'Sale price charged to customer',
  `profit_per_unit` decimal(12,2) GENERATED ALWAYS AS (`sale_price` - `purchase_price`) STORED,
  `total_profit` decimal(12,2) GENERATED ALWAYS AS (`quantity_from_batch` * (`sale_price` - `purchase_price`)) STORED,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_oib_order_item` (`order_item_id`),
  KEY `idx_oib_batch` (`batch_id`),
  CONSTRAINT `fk_oib_order_item` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_oib_batch` FOREIGN KEY (`batch_id`) REFERENCES `stock_batches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Stored Procedure: sp_add_to_cart
-- Adds an item to user's cart (creates cart if needed)
-- --------------------------------------------------------

DELIMITER $$

DROP PROCEDURE IF EXISTS `sp_add_to_cart`$$

CREATE PROCEDURE `sp_add_to_cart` (
    IN p_user_id INT,
    IN p_tool_id INT,
    IN p_quantity INT,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255),
    OUT p_cart_id INT
)
BEGIN
    DECLARE v_cart_id INT;
    DECLARE v_tool_name VARCHAR(100);
    DECLARE v_tool_price DECIMAL(12,2);
    DECLARE v_available_stock INT;
    DECLARE v_existing_qty INT;
    
    SET p_success = FALSE;
    
    -- Get tool details
    SELECT u_toolname, u_price, u_itemsnumber 
    INTO v_tool_name, v_tool_price, v_available_stock
    FROM tool WHERE id = p_tool_id;
    
    IF v_tool_name IS NULL THEN
        SET p_message = 'Tool not found';
    ELSEIF v_available_stock < p_quantity THEN
        SET p_message = CONCAT('Insufficient stock. Only ', v_available_stock, ' available.');
    ELSE
        -- Get or create active cart for user
        SELECT id INTO v_cart_id 
        FROM cart 
        WHERE user_id = p_user_id AND status = 'ACTIVE' 
        LIMIT 1;
        
        IF v_cart_id IS NULL THEN
            INSERT INTO cart (user_id, status, expires_at) 
            VALUES (p_user_id, 'ACTIVE', DATE_ADD(NOW(), INTERVAL 24 HOUR));
            SET v_cart_id = LAST_INSERT_ID();
        END IF;
        
        -- Check if item already in cart
        SELECT quantity INTO v_existing_qty 
        FROM cart_items 
        WHERE cart_id = v_cart_id AND tool_id = p_tool_id;
        
        IF v_existing_qty IS NOT NULL THEN
            -- Check total quantity doesn't exceed stock
            IF (v_existing_qty + p_quantity) > v_available_stock THEN
                SET p_message = CONCAT('Cannot add more. You have ', v_existing_qty, ' in cart, only ', v_available_stock, ' available.');
            ELSE
                -- Update existing cart item
                UPDATE cart_items 
                SET quantity = quantity + p_quantity,
                    unit_price = v_tool_price
                WHERE cart_id = v_cart_id AND tool_id = p_tool_id;
                
                SET p_success = TRUE;
                SET p_message = CONCAT('Updated cart. Now ', (v_existing_qty + p_quantity), ' x ', v_tool_name);
            END IF;
        ELSE
            -- Insert new cart item
            INSERT INTO cart_items (cart_id, tool_id, tool_name, quantity, unit_price)
            VALUES (v_cart_id, p_tool_id, v_tool_name, p_quantity, v_tool_price);
            
            SET p_success = TRUE;
            SET p_message = CONCAT('Added ', p_quantity, ' x ', v_tool_name, ' to cart');
        END IF;
        
        SET p_cart_id = v_cart_id;
    END IF;
END$$

-- --------------------------------------------------------
-- Stored Procedure: sp_process_cart_order_fifo
-- Processes entire cart using FIFO, creates order and order_items
-- --------------------------------------------------------

DROP PROCEDURE IF EXISTS `sp_process_cart_order_fifo`$$

CREATE PROCEDURE `sp_process_cart_order_fifo` (
    IN p_cart_id INT,
    IN p_user_id INT,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255),
    OUT p_order_id INT,
    OUT p_total_amount DECIMAL(12,2)
)
BEGIN
    DECLARE v_done INT DEFAULT FALSE;
    DECLARE v_cart_item_id INT;
    DECLARE v_tool_id INT;
    DECLARE v_tool_name VARCHAR(100);
    DECLARE v_quantity INT;
    DECLARE v_unit_price DECIMAL(12,2);
    DECLARE v_total_price DECIMAL(12,2);
    DECLARE v_order_item_id INT;
    DECLARE v_grand_total DECIMAL(12,2) DEFAULT 0;
    DECLARE v_items_count INT DEFAULT 0;
    
    -- Cursor for cart items
    DECLARE cart_cursor CURSOR FOR 
        SELECT id, tool_id, tool_name, quantity, unit_price, total_price
        FROM cart_items 
        WHERE cart_id = p_cart_id;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = TRUE;
    
    SET p_success = FALSE;
    SET p_order_id = NULL;
    SET p_total_amount = 0;
    
    -- Validate cart exists and belongs to user
    IF NOT EXISTS (SELECT 1 FROM cart WHERE id = p_cart_id AND user_id = p_user_id AND status = 'ACTIVE') THEN
        SET p_message = 'Invalid or inactive cart';
    ELSE
        -- Calculate grand total
        SELECT COALESCE(SUM(total_price), 0), COUNT(*) 
        INTO v_grand_total, v_items_count
        FROM cart_items WHERE cart_id = p_cart_id;
        
        IF v_items_count = 0 THEN
            SET p_message = 'Cart is empty';
        ELSE
            START TRANSACTION;
            
            -- Create the main order
            INSERT INTO `order` (user_id, tool_id, u_toolname, u_itemsnumber, u_type, u_tooldescription, u_date, u_price, u_totalprice, status)
            VALUES (p_user_id, NULL, CONCAT('Cart Order (', v_items_count, ' items)'), v_items_count, 'Cart Order', 
                    CONCAT('Multi-item order from cart #', p_cart_id), CURDATE(), 0, v_grand_total, 'Pending Payment');
            
            SET p_order_id = LAST_INSERT_ID();
            
            -- Process each cart item
            OPEN cart_cursor;
            
            cart_loop: LOOP
                FETCH cart_cursor INTO v_cart_item_id, v_tool_id, v_tool_name, v_quantity, v_unit_price, v_total_price;
                
                IF v_done THEN
                    LEAVE cart_loop;
                END IF;
                
                -- Create order item
                INSERT INTO order_items (order_id, tool_id, tool_name, quantity, unit_price, total_price)
                VALUES (p_order_id, v_tool_id, v_tool_name, v_quantity, v_unit_price, v_total_price);
                
                SET v_order_item_id = LAST_INSERT_ID();
                
                -- FIFO stock deduction will happen after payment (in success-payment.php)
                -- We just record the order items here
                
            END LOOP;
            
            CLOSE cart_cursor;
            
            -- Mark cart as checked out
            UPDATE cart SET status = 'CHECKED_OUT' WHERE id = p_cart_id;
            
            COMMIT;
            
            SET p_success = TRUE;
            SET p_total_amount = v_grand_total;
            SET p_message = CONCAT('Order created with ', v_items_count, ' items. Total: ', v_grand_total, ' RWF');
        END IF;
    END IF;
END$$

-- --------------------------------------------------------
-- Stored Procedure: sp_deduct_order_items_fifo
-- Deducts stock for all order items using FIFO method
-- Called after successful payment
-- --------------------------------------------------------

DROP PROCEDURE IF EXISTS `sp_deduct_order_items_fifo`$$

CREATE PROCEDURE `sp_deduct_order_items_fifo` (
    IN p_order_id INT,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255),
    OUT p_total_cost DECIMAL(12,2),
    OUT p_total_profit DECIMAL(12,2)
)
BEGIN
    DECLARE v_done INT DEFAULT FALSE;
    DECLARE v_order_item_id INT;
    DECLARE v_tool_id INT;
    DECLARE v_quantity INT;
    DECLARE v_unit_price DECIMAL(12,2);
    DECLARE v_remaining INT;
    DECLARE v_method VARCHAR(10);
    
    DECLARE v_batch_id INT;
    DECLARE v_batch_qty INT;
    DECLARE v_batch_purchase_price DECIMAL(12,2);
    DECLARE v_take_qty INT;
    
    DECLARE v_batch_done INT DEFAULT FALSE;
    
    DECLARE v_total_cost DECIMAL(12,2) DEFAULT 0;
    DECLARE v_total_revenue DECIMAL(12,2) DEFAULT 0;
    
    -- Cursor for order items
    DECLARE items_cursor CURSOR FOR 
        SELECT id, tool_id, quantity, unit_price
        FROM order_items 
        WHERE order_id = p_order_id;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = TRUE;
    
    SET p_success = FALSE;
    SET p_total_cost = 0;
    SET p_total_profit = 0;
    
    START TRANSACTION;
    
    OPEN items_cursor;
    
    items_loop: LOOP
        FETCH items_cursor INTO v_order_item_id, v_tool_id, v_quantity, v_unit_price;
        
        IF v_done THEN
            LEAVE items_loop;
        END IF;
        
        SET v_remaining = v_quantity;
        
        -- Get inventory method for this tool (default FIFO)
        SELECT COALESCE(method, 'FIFO') INTO v_method
        FROM inventory_method WHERE tool_id = v_tool_id;
        
        IF v_method IS NULL THEN
            SET v_method = 'FIFO';
        END IF;
        
        -- Process batches based on FIFO or LIFO
        SET v_batch_done = FALSE;
        
        batch_block: BEGIN
            DECLARE batch_cursor CURSOR FOR 
                SELECT id, quantity_remaining, purchase_price 
                FROM stock_batches 
                WHERE tool_id = v_tool_id AND quantity_remaining > 0
                ORDER BY CASE WHEN v_method = 'FIFO' THEN batch_date END ASC,
                         CASE WHEN v_method = 'LIFO' THEN batch_date END DESC;
            
            DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_batch_done = TRUE;
            
            OPEN batch_cursor;
            
            batch_loop: LOOP
                IF v_remaining <= 0 THEN
                    LEAVE batch_loop;
                END IF;
                
                FETCH batch_cursor INTO v_batch_id, v_batch_qty, v_batch_purchase_price;
                
                IF v_batch_done THEN
                    LEAVE batch_loop;
                END IF;
                
                SET v_take_qty = LEAST(v_remaining, v_batch_qty);
                
                -- Update batch quantity
                UPDATE stock_batches 
                SET quantity_remaining = quantity_remaining - v_take_qty 
                WHERE id = v_batch_id;
                
                -- Record stock movement
                INSERT INTO stock_movements (batch_id, order_id, movement_type, quantity, unit_cost, reference)
                VALUES (v_batch_id, p_order_id, 'OUT', v_take_qty, v_batch_purchase_price, 
                        CONCAT('ORDER-', LPAD(p_order_id, 6, '0')));
                
                -- Record order item batch (for profit tracking)
                INSERT INTO order_item_batches (order_item_id, batch_id, quantity_from_batch, purchase_price, sale_price)
                VALUES (v_order_item_id, v_batch_id, v_take_qty, v_batch_purchase_price, v_unit_price);
                
                SET v_total_cost = v_total_cost + (v_take_qty * v_batch_purchase_price);
                SET v_total_revenue = v_total_revenue + (v_take_qty * v_unit_price);
                
                SET v_remaining = v_remaining - v_take_qty;
                
            END LOOP;
            
            CLOSE batch_cursor;
        END batch_block;
        
        -- Also update tool table stock (legacy compatibility)
        UPDATE tool SET u_itemsnumber = u_itemsnumber - v_quantity WHERE id = v_tool_id;
        
        IF v_remaining > 0 THEN
            -- Not enough stock - rollback
            ROLLBACK;
            SET p_message = CONCAT('Insufficient stock for tool ID ', v_tool_id);
            SET p_success = FALSE;
            LEAVE items_loop;
        END IF;
        
    END LOOP;
    
    CLOSE items_cursor;
    
    IF p_success = FALSE AND p_message IS NOT NULL THEN
        -- Already rolled back
        SELECT 1;
    ELSE
        -- Update order status
        UPDATE `order` SET status = 'Paid', payment_date = NOW() WHERE id = p_order_id;
        
        COMMIT;
        
        SET p_success = TRUE;
        SET p_total_cost = v_total_cost;
        SET p_total_profit = v_total_revenue - v_total_cost;
        SET p_message = CONCAT('Stock deducted successfully. Profit: ', ROUND(v_total_revenue - v_total_cost, 2), ' RWF');
    END IF;
END$$

DELIMITER ;

-- --------------------------------------------------------
-- View: vw_cart_summary
-- Shows cart totals for each user
-- --------------------------------------------------------

CREATE OR REPLACE VIEW `vw_cart_summary` AS
SELECT 
    c.id as cart_id,
    c.user_id,
    u.u_name as user_name,
    COUNT(ci.id) as items_count,
    SUM(ci.quantity) as total_items,
    SUM(ci.total_price) as grand_total,
    c.status,
    c.created_at,
    c.updated_at
FROM cart c
JOIN `user` u ON c.user_id = u.id
LEFT JOIN cart_items ci ON c.id = ci.cart_id
GROUP BY c.id, c.user_id, u.u_name, c.status, c.created_at, c.updated_at;

-- --------------------------------------------------------
-- View: vw_order_profit_summary
-- Shows profit breakdown per order using FIFO batch data
-- --------------------------------------------------------

CREATE OR REPLACE VIEW `vw_order_profit_summary` AS
SELECT 
    o.id as order_id,
    o.user_id,
    u.u_name as customer_name,
    o.u_date as order_date,
    o.u_totalprice as order_total,
    COALESCE(SUM(oib.quantity_from_batch * oib.purchase_price), 0) as total_cost,
    COALESCE(SUM(oib.quantity_from_batch * oib.sale_price), 0) as total_revenue,
    COALESCE(SUM(oib.total_profit), 0) as total_profit,
    CASE 
        WHEN COALESCE(SUM(oib.quantity_from_batch * oib.sale_price), 0) > 0 
        THEN ROUND((COALESCE(SUM(oib.total_profit), 0) / COALESCE(SUM(oib.quantity_from_batch * oib.sale_price), 1)) * 100, 2)
        ELSE 0 
    END as profit_margin_percent,
    o.status
FROM `order` o
JOIN `user` u ON o.user_id = u.id
LEFT JOIN order_items oi ON o.id = oi.order_id
LEFT JOIN order_item_batches oib ON oi.id = oib.order_item_id
GROUP BY o.id, o.user_id, u.u_name, o.u_date, o.u_totalprice, o.status;

-- --------------------------------------------------------
-- Success Message
-- --------------------------------------------------------

SELECT 'Cart system tables and procedures created successfully!' as message;
SELECT 'Tables created: cart, cart_items, order_items, order_item_batches' as tables_created;
SELECT 'Procedures created: sp_add_to_cart, sp_process_cart_order_fifo, sp_deduct_order_items_fifo' as procedures_created;
SELECT 'Views created: vw_cart_summary, vw_order_profit_summary' as views_created;
