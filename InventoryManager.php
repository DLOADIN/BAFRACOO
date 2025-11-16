<?php
/**
 * InventoryManager Class
 * Handles FIFO and LIFO inventory management for BAFRACOO
 */
class InventoryManager {
    private $connection;
    
    public function __construct($con) {
        $this->connection = $con;
    }
    
    /**
     * Get inventory method for a tool (FIFO or LIFO)
     */
    public function getInventoryMethod($tool_id) {
        $query = "SELECT method FROM inventory_method WHERE tool_id = ?";
        $stmt = mysqli_prepare($this->connection, $query);
        mysqli_stmt_bind_param($stmt, "i", $tool_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        
        return $row ? $row['method'] : 'FIFO'; // Default to FIFO
    }
    
    /**
     * Set inventory method for a tool
     */
    public function setInventoryMethod($tool_id, $method) {
        $query = "INSERT INTO inventory_method (tool_id, method) VALUES (?, ?) 
                  ON DUPLICATE KEY UPDATE method = ?, updated_at = CURRENT_TIMESTAMP";
        $stmt = mysqli_prepare($this->connection, $query);
        mysqli_stmt_bind_param($stmt, "iss", $tool_id, $method, $method);
        return mysqli_stmt_execute($stmt);
    }
    
    /**
     * Get available stock for a tool (total across all batches)
     */
    public function getAvailableStock($tool_id) {
        $query = "SELECT SUM(quantity_remaining) as total_stock FROM stock_batches WHERE tool_id = ?";
        $stmt = mysqli_prepare($this->connection, $query);
        mysqli_stmt_bind_param($stmt, "i", $tool_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        
        return $row['total_stock'] ?? 0;
    }
    
    /**
     * Get batches for a tool based on FIFO or LIFO method
     */
    public function getBatchesForSale($tool_id, $method = null) {
        if ($method === null) {
            $method = $this->getInventoryMethod($tool_id);
        }
        
        $orderBy = ($method === 'FIFO') ? 'batch_date ASC' : 'batch_date DESC';
        
        $query = "SELECT * FROM stock_batches 
                  WHERE tool_id = ? AND quantity_remaining > 0 
                  ORDER BY $orderBy";
        $stmt = mysqli_prepare($this->connection, $query);
        mysqli_stmt_bind_param($stmt, "i", $tool_id);
        mysqli_stmt_execute($stmt);
        
        return mysqli_stmt_get_result($stmt);
    }
    
    /**
     * Process order and deduct stock using FIFO or LIFO
     */
    public function processOrder($tool_id, $quantity_needed, $order_id, $selling_price) {
        $method = $this->getInventoryMethod($tool_id);
        $batches = $this->getBatchesForSale($tool_id, $method);
        
        $remaining_quantity = $quantity_needed;
        $total_cost = 0;
        $movements = [];
        
        // Start transaction
        mysqli_autocommit($this->connection, false);
        
        try {
            while ($remaining_quantity > 0 && $batch = mysqli_fetch_assoc($batches)) {
                $batch_id = $batch['id'];
                $available_in_batch = $batch['quantity_remaining'];
                $quantity_to_take = min($remaining_quantity, $available_in_batch);
                
                // Update batch quantity
                $new_remaining = $available_in_batch - $quantity_to_take;
                $update_query = "UPDATE stock_batches SET quantity_remaining = ? WHERE id = ?";
                $stmt = mysqli_prepare($this->connection, $update_query);
                mysqli_stmt_bind_param($stmt, "ii", $new_remaining, $batch_id);
                mysqli_stmt_execute($stmt);
                
                // Record stock movement
                $movement_query = "INSERT INTO stock_movements 
                                   (batch_id, order_id, movement_type, quantity, unit_cost, reference) 
                                   VALUES (?, ?, 'OUT', ?, ?, ?)";
                $stmt = mysqli_prepare($this->connection, $movement_query);
                $reference = "ORDER-" . str_pad($order_id, 4, '0', STR_PAD_LEFT);
                mysqli_stmt_bind_param($stmt, "iiids", $batch_id, $order_id, $quantity_to_take, $batch['purchase_price'], $reference);
                mysqli_stmt_execute($stmt);
                
                $movements[] = [
                    'batch_id' => $batch_id,
                    'batch_number' => $batch['batch_number'],
                    'quantity' => $quantity_to_take,
                    'cost' => $batch['purchase_price'] * $quantity_to_take
                ];
                
                $total_cost += $batch['purchase_price'] * $quantity_to_take;
                $remaining_quantity -= $quantity_to_take;
            }
            
            if ($remaining_quantity > 0) {
                // Not enough stock
                mysqli_rollback($this->connection);
                return [
                    'success' => false,
                    'message' => 'Insufficient stock. Only ' . ($quantity_needed - $remaining_quantity) . ' items available.',
                    'available_stock' => $this->getAvailableStock($tool_id)
                ];
            }
            
            // Update order status
            $update_order = "UPDATE `order` SET status = 'PROCESSING' WHERE id = ?";
            $stmt = mysqli_prepare($this->connection, $update_order);
            mysqli_stmt_bind_param($stmt, "i", $order_id);
            mysqli_stmt_execute($stmt);
            
            mysqli_commit($this->connection);
            
            return [
                'success' => true,
                'message' => 'Order processed successfully using ' . $method . ' method',
                'method_used' => $method,
                'total_cost' => $total_cost,
                'movements' => $movements,
                'remaining_stock' => $this->getAvailableStock($tool_id)
            ];
            
        } catch (Exception $e) {
            mysqli_rollback($this->connection);
            return [
                'success' => false,
                'message' => 'Error processing order: ' . $e->getMessage()
            ];
        } finally {
            mysqli_autocommit($this->connection, true);
        }
    }
    
    /**
     * Add new stock batch
     */
    public function addStockBatch($tool_id, $quantity, $purchase_price, $supplier = null, $expiry_date = null) {
        // Generate batch number
        $batch_query = "SELECT COUNT(*) as batch_count FROM stock_batches WHERE tool_id = ?";
        $stmt = mysqli_prepare($this->connection, $batch_query);
        mysqli_stmt_bind_param($stmt, "i", $tool_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $batch_count = mysqli_fetch_assoc($result)['batch_count'] + 1;
        
        $batch_number = 'BATCH-' . str_pad($tool_id, 4, '0', STR_PAD_LEFT) . '-' . str_pad($batch_count, 3, '0', STR_PAD_LEFT);
        
        $insert_query = "INSERT INTO stock_batches 
                         (tool_id, batch_number, quantity_received, quantity_remaining, purchase_price, supplier, expiry_date) 
                         VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($this->connection, $insert_query);
        mysqli_stmt_bind_param($stmt, "isiidss", $tool_id, $batch_number, $quantity, $quantity, $purchase_price, $supplier, $expiry_date);
        
        if (mysqli_stmt_execute($stmt)) {
            $batch_id = mysqli_insert_id($this->connection);
            
            // Record stock movement
            $movement_query = "INSERT INTO stock_movements 
                               (batch_id, movement_type, quantity, unit_cost, reference) 
                               VALUES (?, 'IN', ?, ?, ?)";
            $stmt = mysqli_prepare($this->connection, $movement_query);
            $reference = "STOCK-IN-" . $batch_number;
            mysqli_stmt_bind_param($stmt, "iids", $batch_id, $quantity, $purchase_price, $reference);
            mysqli_stmt_execute($stmt);
            
            return [
                'success' => true,
                'message' => 'Stock batch added successfully',
                'batch_id' => $batch_id,
                'batch_number' => $batch_number
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Error adding stock batch'
        ];
    }
    
    /**
     * Get stock summary for a tool
     */
    public function getStockSummary($tool_id) {
        $query = "SELECT 
                    sb.*,
                    (SELECT method FROM inventory_method WHERE tool_id = sb.tool_id) as inventory_method
                  FROM stock_batches sb 
                  WHERE sb.tool_id = ? AND sb.quantity_remaining > 0 
                  ORDER BY sb.batch_date ASC";
        
        $stmt = mysqli_prepare($this->connection, $query);
        mysqli_stmt_bind_param($stmt, "i", $tool_id);
        mysqli_stmt_execute($stmt);
        
        return mysqli_stmt_get_result($stmt);
    }
    
    /**
     * Get cost of goods sold (COGS) calculation
     */
    public function calculateCOGS($tool_id, $period_start, $period_end) {
        $query = "SELECT 
                    SUM(sm.quantity * sm.unit_cost) as total_cogs,
                    SUM(sm.quantity) as total_quantity_sold
                  FROM stock_movements sm
                  JOIN stock_batches sb ON sm.batch_id = sb.id
                  WHERE sb.tool_id = ? 
                    AND sm.movement_type = 'OUT' 
                    AND sm.created_at BETWEEN ? AND ?";
        
        $stmt = mysqli_prepare($this->connection, $query);
        mysqli_stmt_bind_param($stmt, "iss", $tool_id, $period_start, $period_end);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        return mysqli_fetch_assoc($result);
    }
}
?>