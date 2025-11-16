-- FIFO/LIFO Testing Queries
-- Run these in phpMyAdmin to verify the system is working

-- 1. Check inventory methods for all tools
SELECT 
    t.id as tool_id,
    t.u_toolname as tool_name,
    im.method as inventory_method,
    t.u_itemsnumber as original_stock,
    t.u_price as selling_price
FROM tool t
LEFT JOIN inventory_method im ON t.id = im.tool_id
ORDER BY t.id;

-- 2. Check stock batches
SELECT 
    sb.id as batch_id,
    sb.tool_id,
    t.u_toolname as tool_name,
    sb.batch_number,
    sb.quantity_received,
    sb.quantity_remaining,
    sb.purchase_price,
    sb.batch_date,
    sb.supplier
FROM stock_batches sb
JOIN tool t ON sb.tool_id = t.id
ORDER BY sb.tool_id, sb.batch_date;

-- 3. Check stock movements
SELECT 
    sm.id as movement_id,
    sb.tool_id,
    t.u_toolname as tool_name,
    sb.batch_number,
    sm.movement_type,
    sm.quantity,
    sm.unit_cost,
    sm.reference,
    sm.created_at
FROM stock_movements sm
JOIN stock_batches sb ON sm.batch_id = sb.id
JOIN tool t ON sb.tool_id = t.id
ORDER BY sm.created_at DESC;

-- 4. Check current available stock per tool
SELECT 
    t.id as tool_id,
    t.u_toolname as tool_name,
    SUM(sb.quantity_remaining) as total_available_stock,
    im.method as inventory_method
FROM tool t
LEFT JOIN stock_batches sb ON t.id = sb.tool_id
LEFT JOIN inventory_method im ON t.id = im.tool_id
GROUP BY t.id, t.u_toolname, im.method
ORDER BY t.id;