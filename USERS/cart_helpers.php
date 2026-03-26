<?php
/**
 * Cart Helper Functions
 * Aggregated stock & price lookups + FIFO deduction for multi-row products
 * 
 * Products with the same u_toolname can exist across multiple rows in the `tool` table.
 * These helpers treat all rows sharing a name as ONE logical product.
 */

/**
 * Get the aggregated (total) stock for a product across all rows sharing
 * the same u_toolname.
 *
 * @param mysqli $con       Database connection
 * @param mixed  $tool_id_or_name  Either a numeric tool ID or a string tool name
 * @return int  Total available stock
 */
function getAggregatedStock($con, $tool_id_or_name): int {
    if (is_numeric($tool_id_or_name)) {
        // Lookup the name first, then aggregate
        $name_q = mysqli_query($con, "SELECT u_toolname FROM tool WHERE id = " . (int)$tool_id_or_name);
        if (!$name_q || mysqli_num_rows($name_q) === 0) return 0;
        $tool_name = mysqli_fetch_assoc($name_q)['u_toolname'];
    } else {
        $tool_name = $tool_id_or_name;
    }

    $safe = mysqli_real_escape_string($con, $tool_name);
    $res  = mysqli_query($con, "SELECT SUM(u_itemsnumber) AS total_stock FROM tool WHERE u_toolname = '$safe'");
    if (!$res) return 0;
    return (int)(mysqli_fetch_assoc($res)['total_stock'] ?? 0);
}

/**
 * Get the weighted average unit selling price for a product across all rows sharing
 * the same u_toolname.
 *
 * Formula: SUM(u_price * u_itemsnumber) / SUM(u_itemsnumber)
 *
 * @param mysqli $con
 * @param mixed  $tool_id_or_name
 * @return float
 */
function getAggregatedAveragePrice($con, $tool_id_or_name): float {
    if (is_numeric($tool_id_or_name)) {
        $name_q = mysqli_query($con, "SELECT u_toolname FROM tool WHERE id = " . (int)$tool_id_or_name);
        if (!$name_q || mysqli_num_rows($name_q) === 0) return 0;
        $tool_name = mysqli_fetch_assoc($name_q)['u_toolname'];
    } else {
        $tool_name = $tool_id_or_name;
    }

    $safe = mysqli_real_escape_string($con, $tool_name);
    $res  = mysqli_query($con, "
        SELECT
            CASE
                WHEN SUM(u_itemsnumber) > 0 THEN SUM(u_price * u_itemsnumber) / SUM(u_itemsnumber)
                ELSE 0
            END AS avg_price
        FROM tool
        WHERE u_toolname = '$safe'
    ");
    if (!$res) return 0;
    return (float)(mysqli_fetch_assoc($res)['avg_price'] ?? 0);
}

/**
 * Backward-compatible wrapper for previous name.
 */
function getAggregatedMaxPrice($con, $tool_id_or_name): float {
    return getAggregatedAveragePrice($con, $tool_id_or_name);
}

/**
 * Backward-compatible wrapper.
 */
function getAggregatedAvgPrice($con, $tool_id_or_name): float {
    return getAggregatedAveragePrice($con, $tool_id_or_name);
}

/**
 * Deduct stock FIFO-style across every `tool` row that shares the given name.
 * Rows are consumed oldest `u_date` first.
 *
 * Also updates stock_batches if they exist for that tool row.
 *
 * @param mysqli $con
 * @param string $tool_name        The u_toolname value
 * @param int    $qty_to_deduct    How many units to remove
 * @param int    $order_id         (optional) For stock_movements reference
 * @param int    $order_item_id    (optional) For order_item_batches tracking
 * @param float  $sale_price       (optional) The sale price per unit
 * @return bool  true if fully deducted, false if insufficient stock
 */
function deductStockByNameFIFO($con, string $tool_name, int $qty_to_deduct, int $order_id = 0, int $order_item_id = 0, float $sale_price = 0): bool {
    $remaining = $qty_to_deduct;
    $safe_name = mysqli_real_escape_string($con, $tool_name);

    // Determine inventory method (check if any row for this product has a method set)
    $method = 'FIFO';
    $method_q = mysqli_query($con, "
        SELECT im.method FROM inventory_method im
        JOIN tool t ON t.id = im.tool_id
        WHERE t.u_toolname = '$safe_name'
        LIMIT 1
    ");
    if ($method_q && mysqli_num_rows($method_q) > 0) {
        $method = mysqli_fetch_assoc($method_q)['method'];
    }

    $order_dir = ($method === 'FIFO') ? 'ASC' : 'DESC';

    // --- Step 1: Deduct from the `tool` table rows, oldest u_date first ---
    $rows = mysqli_query($con, "
        SELECT id, u_itemsnumber, u_price
        FROM tool
        WHERE u_toolname = '$safe_name' AND u_itemsnumber > 0
        ORDER BY u_date $order_dir
    ");

    if ($rows) {
        while ($remaining > 0 && ($r = mysqli_fetch_assoc($rows))) {
            $row_id   = (int)$r['id'];
            $row_stock = (int)$r['u_itemsnumber'];
            $take     = min($remaining, $row_stock);

            mysqli_query($con, "UPDATE tool SET u_itemsnumber = u_itemsnumber - $take WHERE id = $row_id");
            $remaining -= $take;

            // --- Step 2: Also deduct from stock_batches for this tool row (if any) ---
            $batch_remaining = $take;
            $batches = mysqli_query($con, "
                SELECT id, quantity_remaining, purchase_price
                FROM stock_batches
                WHERE tool_id = $row_id AND quantity_remaining > 0
                ORDER BY batch_date $order_dir
            ");
            if ($batches) {
                while ($batch_remaining > 0 && ($b = mysqli_fetch_assoc($batches))) {
                    $batch_id  = (int)$b['id'];
                    $batch_qty = (int)$b['quantity_remaining'];
                    $purchase  = (float)$b['purchase_price'];
                    $btake     = min($batch_remaining, $batch_qty);

                    mysqli_query($con, "UPDATE stock_batches SET quantity_remaining = quantity_remaining - $btake WHERE id = $batch_id");

                    // Record stock movement
                    if ($order_id > 0) {
                        $reference = 'ORDER-' . str_pad($order_id, 6, '0', STR_PAD_LEFT);
                        mysqli_query($con, "INSERT INTO stock_movements (batch_id, order_id, movement_type, quantity, unit_cost, reference)
                                            VALUES ($batch_id, $order_id, 'OUT', $btake, $purchase, '$reference')");
                    }

                    // Record order item batch for profit tracking
                    if ($order_item_id > 0) {
                        $sp = (float)$sale_price;
                        mysqli_query($con, "INSERT INTO order_item_batches (order_item_id, batch_id, quantity_from_batch, purchase_price, sale_price)
                                            VALUES ($order_item_id, $batch_id, $btake, $purchase, $sp)");
                    }

                    $batch_remaining -= $btake;
                }
            }
        }
    }

    return ($remaining === 0);
}
?>
