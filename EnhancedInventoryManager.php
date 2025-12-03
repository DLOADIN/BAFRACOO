<?php
/**
 * Enhanced InventoryManager Class with Damage Tracking, Returns, and Location Management
 * Handles FIFO/LIFO inventory management plus comprehensive stock control for BAFRACOO
 */
class EnhancedInventoryManager {
    private $connection;
    
    public function __construct($con) {
        $this->connection = $con;
    }
    
    // ============== BASIC INVENTORY METHODS ==============
    
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
        
        return $row ? $row['method'] : 'FIFO';
    }
    
    /**
     * Set inventory method for a tool (FIFO or LIFO)
     */
    public function setInventoryMethod($tool_id, $method) {
        $method = strtoupper($method);
        if (!in_array($method, ['FIFO', 'LIFO'])) {
            return false;
        }
        
        $query = "INSERT INTO inventory_method (tool_id, method) 
                  VALUES (?, ?) 
                  ON DUPLICATE KEY UPDATE method = ?";
        $stmt = mysqli_prepare($this->connection, $query);
        mysqli_stmt_bind_param($stmt, "iss", $tool_id, $method, $method);
        
        return mysqli_stmt_execute($stmt);
    }
    
    /**
     * Get current inventory method status (general system overview)
     */
    public function getCurrentInventoryMethod() {
        // Get the most common method used across all tools
        $query = "SELECT method, COUNT(*) as count FROM inventory_method GROUP BY method ORDER BY count DESC LIMIT 1";
        $result = mysqli_query($this->connection, $query);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $dominant_method = $row['method'];
            $count = $row['count'];
            
            // Get total tools count
            $total_query = "SELECT COUNT(*) as total FROM inventory_method";
            $total_result = mysqli_query($this->connection, $total_query);
            $total_tools = mysqli_fetch_assoc($total_result)['total'];
            
            if ($count == $total_tools) {
                return "All {$dominant_method}";
            } else {
                return "Mixed ({$dominant_method} dominant)";
            }
        }
        
        return "FIFO (Default)";
    }
    
    /**
     * Get available stock for a tool across all locations
     * Falls back to tool table u_itemsnumber if no batches exist
     */
    public function getAvailableStock($tool_id, $location_id = null) {
        if ($location_id) {
            $query = "SELECT SUM(quantity_remaining) as total_stock 
                      FROM stock_batches 
                      WHERE tool_id = ? AND location_id = ? AND quantity_remaining > 0";
            $stmt = mysqli_prepare($this->connection, $query);
            mysqli_stmt_bind_param($stmt, "ii", $tool_id, $location_id);
        } else {
            $query = "SELECT SUM(quantity_remaining) as total_stock 
                      FROM stock_batches 
                      WHERE tool_id = ? AND quantity_remaining > 0";
            $stmt = mysqli_prepare($this->connection, $query);
            mysqli_stmt_bind_param($stmt, "i", $tool_id);
        }
        
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        
        $batch_stock = $row['total_stock'] ?? 0;
        
        // If no batch stock found, fall back to tool table
        if ($batch_stock == 0) {
            $tool_query = "SELECT u_itemsnumber FROM tool WHERE id = ?";
            $tool_stmt = mysqli_prepare($this->connection, $tool_query);
            mysqli_stmt_bind_param($tool_stmt, "i", $tool_id);
            mysqli_stmt_execute($tool_stmt);
            $tool_result = mysqli_stmt_get_result($tool_stmt);
            $tool_row = mysqli_fetch_assoc($tool_result);
            return $tool_row['u_itemsnumber'] ?? 0;
        }
        
        return $batch_stock;
    }
    
    // ============== LOCATION MANAGEMENT ==============
    
    /**
     * Get all locations
     */
    public function getAllLocations() {
        $query = "SELECT * FROM locations WHERE is_active = 1 ORDER BY location_type, location_name";
        return mysqli_query($this->connection, $query);
    }
    
    /**
     * Get location details
     */
    public function getLocation($location_id) {
        $query = "SELECT * FROM locations WHERE id = ?";
        $stmt = mysqli_prepare($this->connection, $query);
        mysqli_stmt_bind_param($stmt, "i", $location_id);
        mysqli_stmt_execute($stmt);
        return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    }
    
    // ============== STOCK MANAGEMENT ==============
    
    /**
     * Add new stock batch with location
     */
    public function addStockBatch($tool_id, $quantity, $purchase_price, $location_id, $supplier = null, $expiry_date = null) {
        // Generate batch number
        $batch_query = "SELECT COUNT(*) as batch_count FROM stock_batches WHERE tool_id = ?";
        $stmt = mysqli_prepare($this->connection, $batch_query);
        mysqli_stmt_bind_param($stmt, "i", $tool_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $batch_count = mysqli_fetch_assoc($result)['batch_count'] + 1;
        
        $batch_number = 'BATCH-' . str_pad($tool_id, 4, '0', STR_PAD_LEFT) . '-' . str_pad($batch_count, 3, '0', STR_PAD_LEFT);
        
        $insert_query = "INSERT INTO stock_batches 
                         (tool_id, batch_number, quantity_received, quantity_remaining, purchase_price, location_id, supplier, expiry_date) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($this->connection, $insert_query);
        mysqli_stmt_bind_param($stmt, "isiidiis", $tool_id, $batch_number, $quantity, $quantity, $purchase_price, $location_id, $supplier, $expiry_date);
        
        if (mysqli_stmt_execute($stmt)) {
            $batch_id = mysqli_insert_id($this->connection);
            
            // Record stock movement
            $this->recordStockMovement($batch_id, null, 'IN', $quantity, $purchase_price, "STOCK-IN-" . $batch_number);
            
            // Check and create stock threshold alert if needed
            $this->checkStockThresholds($tool_id, $location_id);
            
            return [
                'success' => true,
                'message' => 'Stock batch added successfully',
                'batch_id' => $batch_id,
                'batch_number' => $batch_number
            ];
        }
        
        return ['success' => false, 'message' => 'Error adding stock batch'];
    }
    
    // ============== DAMAGE TRACKING ==============
    
    /**
     * Report damaged products
     */
    public function reportDamage($batch_id, $quantity_damaged, $damage_reason, $location_id, $reported_by, $damage_description = null) {
        mysqli_autocommit($this->connection, false);
        
        try {
            // Get batch info
            $batch_query = "SELECT * FROM stock_batches WHERE id = ?";
            $stmt = mysqli_prepare($this->connection, $batch_query);
            mysqli_stmt_bind_param($stmt, "i", $batch_id);
            mysqli_stmt_execute($stmt);
            $batch = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            
            if (!$batch || $batch['quantity_remaining'] < $quantity_damaged) {
                throw new Exception('Insufficient stock in batch or batch not found');
            }
            
            // Calculate estimated loss
            $estimated_loss = $quantity_damaged * $batch['purchase_price'];
            
            // Insert damage record
            $damage_query = "INSERT INTO damaged_products 
                           (batch_id, quantity_damaged, damage_reason, damage_description, location_id, reported_by, damage_date, estimated_loss) 
                           VALUES (?, ?, ?, ?, ?, ?, CURDATE(), ?)";
            $stmt = mysqli_prepare($this->connection, $damage_query);
            mysqli_stmt_bind_param($stmt, "iissiid", $batch_id, $quantity_damaged, $damage_reason, $damage_description, $location_id, $reported_by, $estimated_loss);
            mysqli_stmt_execute($stmt);
            
            // Update batch quantity
            $new_quantity = $batch['quantity_remaining'] - $quantity_damaged;
            $update_query = "UPDATE stock_batches SET quantity_remaining = ? WHERE id = ?";
            $stmt = mysqli_prepare($this->connection, $update_query);
            mysqli_stmt_bind_param($stmt, "ii", $new_quantity, $batch_id);
            mysqli_stmt_execute($stmt);
            
            // Record stock movement
            $this->recordStockMovement($batch_id, null, 'OUT', $quantity_damaged, $batch['purchase_price'], "DAMAGE-" . $damage_reason);
            
            mysqli_commit($this->connection);
            
            return [
                'success' => true,
                'message' => 'Damage reported successfully',
                'estimated_loss' => $estimated_loss
            ];
            
        } catch (Exception $e) {
            mysqli_rollback($this->connection);
            return [
                'success' => false,
                'message' => 'Error reporting damage: ' . $e->getMessage()
            ];
        } finally {
            mysqli_autocommit($this->connection, true);
        }
    }
    
    /**
     * Get damaged products report
     */
    public function getDamagedProducts($location_id = null, $start_date = null, $end_date = null) {
        $where_clauses = [];
        $params = [];
        $param_types = "";
        
        if ($location_id) {
            $where_clauses[] = "dp.location_id = ?";
            $params[] = $location_id;
            $param_types .= "i";
        }
        
        if ($start_date) {
            $where_clauses[] = "dp.damage_date >= ?";
            $params[] = $start_date;
            $param_types .= "s";
        }
        
        if ($end_date) {
            $where_clauses[] = "dp.damage_date <= ?";
            $params[] = $end_date;
            $param_types .= "s";
        }
        
        $where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";
        
        $query = "SELECT dp.*, sb.batch_number, t.u_toolname, l.location_name, a.u_name as reported_by_name
                  FROM damaged_products dp
                  JOIN stock_batches sb ON dp.batch_id = sb.id
                  JOIN tool t ON sb.tool_id = t.id
                  JOIN locations l ON dp.location_id = l.id
                  JOIN admin a ON dp.reported_by = a.id
                  $where_sql
                  ORDER BY dp.damage_date DESC";
        
        if (!empty($params)) {
            $stmt = mysqli_prepare($this->connection, $query);
            mysqli_stmt_bind_param($stmt, $param_types, ...$params);
            mysqli_stmt_execute($stmt);
            return mysqli_stmt_get_result($stmt);
        } else {
            return mysqli_query($this->connection, $query);
        }
    }
    
    // ============== RETURNS MANAGEMENT ==============
    
    /**
     * Process return request
     */
    public function processReturn($order_id, $user_id, $tool_name, $quantity_returned, $return_reason, $item_condition, $return_description = null) {
        // Calculate refund based on condition
        $order_query = "SELECT u_price FROM `order` WHERE id = ? AND user_id = ?";
        $stmt = mysqli_prepare($this->connection, $order_query);
        mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
        mysqli_stmt_execute($stmt);
        $order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        
        if (!$order) {
            return ['success' => false, 'message' => 'Order not found'];
        }
        
        // Calculate refund based on condition
        $base_refund = $order['u_price'] * $quantity_returned;
        $refund_percentage = 1.0; // Default 100%
        $restocking_fee = 0;
        
        switch ($item_condition) {
            case 'LIKE_NEW':
                $refund_percentage = 1.0; // 100%
                break;
            case 'MINOR_WEAR':
                $refund_percentage = 0.9; // 90%
                $restocking_fee = $base_refund * 0.1;
                break;
            case 'DAMAGED':
                $refund_percentage = 0.5; // 50% - case by case
                $restocking_fee = $base_refund * 0.5;
                break;
        }
        
        $refund_amount = $base_refund * $refund_percentage;
        
        $insert_query = "INSERT INTO returns 
                        (order_id, user_id, tool_name, quantity_returned, return_reason, item_condition, return_description, return_date, refund_amount, restocking_fee) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, ?)";
        
        $stmt = mysqli_prepare($this->connection, $insert_query);
        mysqli_stmt_bind_param($stmt, "iisisssdd", $order_id, $user_id, $tool_name, $quantity_returned, $return_reason, $item_condition, $return_description, $refund_amount, $restocking_fee);
        
        if (mysqli_stmt_execute($stmt)) {
            $return_id = mysqli_insert_id($this->connection);
            return [
                'success' => true,
                'message' => 'Return request submitted successfully',
                'return_id' => $return_id,
                'refund_amount' => $refund_amount,
                'restocking_fee' => $restocking_fee
            ];
        }
        
        return ['success' => false, 'message' => 'Error processing return'];
    }
    
    /**
     * Approve return and restock if applicable
     */
    public function approveReturn($return_id, $processed_by, $restock_location_id = null) {
        mysqli_autocommit($this->connection, false);
        
        try {
            // Get return details
            $return_query = "SELECT * FROM returns WHERE id = ?";
            $stmt = mysqli_prepare($this->connection, $return_query);
            mysqli_stmt_bind_param($stmt, "i", $return_id);
            mysqli_stmt_execute($stmt);
            $return_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            
            if (!$return_data) {
                throw new Exception('Return not found');
            }
            
            // Update return status
            $update_query = "UPDATE returns 
                           SET return_status = 'APPROVED', processed_by = ?, processed_date = CURDATE(), restock_location_id = ?
                           WHERE id = ?";
            $stmt = mysqli_prepare($this->connection, $update_query);
            mysqli_stmt_bind_param($stmt, "iii", $processed_by, $restock_location_id, $return_id);
            mysqli_stmt_execute($stmt);
            
            // If item is in good condition, add back to stock
            if ($return_data['item_condition'] === 'LIKE_NEW' && $restock_location_id) {
                // Find the tool ID
                $tool_query = "SELECT id, u_price FROM tool WHERE u_toolname = ?";
                $stmt = mysqli_prepare($this->connection, $tool_query);
                mysqli_stmt_bind_param($stmt, "s", $return_data['tool_name']);
                mysqli_stmt_execute($stmt);
                $tool = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
                
                if ($tool) {
                    // Add back to stock with estimated purchase price
                    $purchase_price = $tool['u_price'] * 0.8; // Estimated
                    $this->addStockBatch($tool['id'], $return_data['quantity_returned'], $purchase_price, $restock_location_id, 'CUSTOMER_RETURN');
                }
            }
            
            mysqli_commit($this->connection);
            
            return [
                'success' => true,
                'message' => 'Return approved successfully'
            ];
            
        } catch (Exception $e) {
            mysqli_rollback($this->connection);
            return [
                'success' => false,
                'message' => 'Error approving return: ' . $e->getMessage()
            ];
        } finally {
            mysqli_autocommit($this->connection, true);
        }
    }
    
    /**
     * Get returns summary statistics
     */
    public function getReturnsSummary() {
        $summary = [];
        
        // Total returns
        $query = "SELECT COUNT(*) as count FROM returns";
        $result = mysqli_query($this->connection, $query);
        $summary['total_returns'] = mysqli_fetch_assoc($result)['count'];
        
        // Pending returns
        $query = "SELECT COUNT(*) as count FROM returns WHERE return_status = 'PENDING'";
        $result = mysqli_query($this->connection, $query);
        $summary['pending_returns'] = mysqli_fetch_assoc($result)['count'];
        
        // Approved returns
        $query = "SELECT COUNT(*) as count FROM returns WHERE return_status = 'APPROVED'";
        $result = mysqli_query($this->connection, $query);
        $summary['approved_returns'] = mysqli_fetch_assoc($result)['count'];
        
        // Rejected returns  
        $query = "SELECT COUNT(*) as count FROM returns WHERE return_status = 'REJECTED'";
        $result = mysqli_query($this->connection, $query);
        $summary['rejected_returns'] = mysqli_fetch_assoc($result)['count'];
        
        // Total refund amount
        $query = "SELECT COALESCE(SUM(refund_amount), 0) as total FROM returns WHERE return_status = 'APPROVED'";
        $result = mysqli_query($this->connection, $query);
        $summary['total_refund_amount'] = mysqli_fetch_assoc($result)['total'];
        
        // Recent returns (last 30 days)
        $query = "SELECT COUNT(*) as count FROM returns WHERE return_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        $result = mysqli_query($this->connection, $query);
        $summary['recent_returns'] = mysqli_fetch_assoc($result)['count'];
        
        return $summary;
    }
    
    /**
     * Get returns with optional filtering
     */
    public function getReturns($filter_status = '', $filter_condition = '') {
        $query = "SELECT r.*, u.u_name as customer_name, l.location_name 
                  FROM returns r 
                  LEFT JOIN user u ON r.user_id = u.id 
                  LEFT JOIN locations l ON r.restock_location_id = l.id 
                  WHERE 1=1";
        
        $params = [];
        $types = "";
        
        // Add status filter
        if (!empty($filter_status)) {
            $query .= " AND r.return_status = ?";
            $params[] = $filter_status;
            $types .= "s";
        }
        
        // Add condition filter
        if (!empty($filter_condition)) {
            $query .= " AND r.item_condition = ?";
            $params[] = $filter_condition;
            $types .= "s";
        }
        
        $query .= " ORDER BY r.return_date DESC, r.created_at DESC";
        
        // Use prepared statement if there are parameters
        if (!empty($params)) {
            $stmt = mysqli_prepare($this->connection, $query);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, $types, ...$params);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                return $result;
            } else {
                error_log("Failed to prepare returns query: " . mysqli_error($this->connection));
                return false;
            }
        } else {
            // No parameters, use simple query
            $result = mysqli_query($this->connection, $query);
            if (!$result) {
                error_log("Returns query failed: " . mysqli_error($this->connection));
                return false;
            }
            return $result;
        }
    }
    
    // ============== STOCK ALERTS ==============
    
    /**
     * Check stock thresholds and create alerts
     */
    public function checkStockThresholds($tool_id, $location_id) {
        $current_stock = $this->getAvailableStock($tool_id, $location_id);
        
        $threshold_query = "SELECT * FROM stock_thresholds WHERE tool_id = ? AND location_id = ?";
        $stmt = mysqli_prepare($this->connection, $threshold_query);
        mysqli_stmt_bind_param($stmt, "ii", $tool_id, $location_id);
        mysqli_stmt_execute($stmt);
        $threshold = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        
        if (!$threshold) return;
        
        $alert_type = null;
        $alert_level = 'INFO';
        $alert_message = '';
        
        if ($current_stock == 0) {
            $alert_type = 'OUT_OF_STOCK';
            $alert_level = 'CRITICAL';
            $alert_message = 'Product is completely out of stock';
        } elseif ($current_stock <= $threshold['minimum_stock']) {
            $alert_type = 'LOW_STOCK';
            $alert_level = 'CRITICAL';
            $alert_message = "Stock is critically low: {$current_stock} units remaining";
        } elseif ($current_stock <= $threshold['reorder_level']) {
            $alert_type = 'LOW_STOCK';
            $alert_level = 'WARNING';
            $alert_message = "Stock is below reorder level: {$current_stock} units remaining";
        }
        
        if ($alert_type) {
            // Check if alert already exists and is unresolved
            $existing_alert_query = "SELECT id FROM stock_alerts 
                                   WHERE tool_id = ? AND location_id = ? AND alert_type = ? AND is_resolved = 0";
            $stmt = mysqli_prepare($this->connection, $existing_alert_query);
            mysqli_stmt_bind_param($stmt, "iis", $tool_id, $location_id, $alert_type);
            mysqli_stmt_execute($stmt);
            
            if (mysqli_num_rows(mysqli_stmt_get_result($stmt)) == 0) {
                // Create new alert
                $alert_query = "INSERT INTO stock_alerts 
                              (tool_id, location_id, alert_type, alert_level, threshold_value, current_value, alert_message) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($this->connection, $alert_query);
                mysqli_stmt_bind_param($stmt, "iissii s", $tool_id, $location_id, $alert_type, $alert_level, $threshold['minimum_stock'], $current_stock, $alert_message);
                mysqli_stmt_execute($stmt);
            }
        }
    }
    
    /**
     * Get active alerts
     */
    public function getActiveAlerts($location_id = null) {
        $where_sql = $location_id ? "WHERE sa.location_id = ? AND sa.is_resolved = 0" : "WHERE sa.is_resolved = 0";
        
        $query = "SELECT sa.*, t.u_toolname, l.location_name
                  FROM stock_alerts sa
                  JOIN tool t ON sa.tool_id = t.id
                  JOIN locations l ON sa.location_id = l.id
                  $where_sql
                  ORDER BY sa.alert_level DESC, sa.created_at DESC";
        
        if ($location_id) {
            $stmt = mysqli_prepare($this->connection, $query);
            mysqli_stmt_bind_param($stmt, "i", $location_id);
            mysqli_stmt_execute($stmt);
            return mysqli_stmt_get_result($stmt);
        } else {
            return mysqli_query($this->connection, $query);
        }
    }
    
    // ============== UTILITY METHODS ==============
    
    /**
     * Record stock movement
     */
    private function recordStockMovement($batch_id, $order_id, $movement_type, $quantity, $unit_cost, $reference, $notes = null) {
        $movement_query = "INSERT INTO stock_movements 
                          (batch_id, order_id, movement_type, quantity, unit_cost, reference, notes) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($this->connection, $movement_query);
        mysqli_stmt_bind_param($stmt, "iisisss", $batch_id, $order_id, $movement_type, $quantity, $unit_cost, $reference, $notes);
        mysqli_stmt_execute($stmt);
    }
    
    /**
     * Check if order quantity is available
     */
    public function canFulfillOrder($tool_id, $quantity_needed, $location_id = null) {
        $available = $this->getAvailableStock($tool_id, $location_id);
        
        // Check minimum stock threshold
        if ($location_id) {
            $threshold_query = "SELECT minimum_stock FROM stock_thresholds WHERE tool_id = ? AND location_id = ?";
            $stmt = mysqli_prepare($this->connection, $threshold_query);
            mysqli_stmt_bind_param($stmt, "ii", $tool_id, $location_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $threshold = mysqli_fetch_assoc($result);
            
            $minimum_required = $threshold ? $threshold['minimum_stock'] : 0;
            
            // Ensure we don't go below minimum stock
            return ($available - $quantity_needed) >= $minimum_required;
        }
        
        return $available >= $quantity_needed;
    }
    
    /**
     * Process order with enhanced stock management
     */
    public function processOrder($tool_id, $quantity_needed, $order_id, $selling_price, $preferred_location_id = null) {
        if (!$this->canFulfillOrder($tool_id, $quantity_needed, $preferred_location_id)) {
            return [
                'success' => false,
                'message' => 'Insufficient stock or would go below minimum threshold'
            ];
        }
        
        $method = $this->getInventoryMethod($tool_id);
        $location_filter = $preferred_location_id ? "AND location_id = $preferred_location_id" : "";
        $orderBy = ($method === 'FIFO') ? 'batch_date ASC' : 'batch_date DESC';
        
        $query = "SELECT * FROM stock_batches 
                  WHERE tool_id = ? AND quantity_remaining > 0 $location_filter
                  ORDER BY $orderBy";
        $stmt = mysqli_prepare($this->connection, $query);
        mysqli_stmt_bind_param($stmt, "i", $tool_id);
        mysqli_stmt_execute($stmt);
        $batches = mysqli_stmt_get_result($stmt);
        
        $remaining_quantity = $quantity_needed;
        $movements = [];
        
        mysqli_autocommit($this->connection, false);
        
        try {
            while ($remaining_quantity > 0 && $batch = mysqli_fetch_assoc($batches)) {
                $quantity_to_take = min($remaining_quantity, $batch['quantity_remaining']);
                
                // Update batch quantity
                $new_remaining = $batch['quantity_remaining'] - $quantity_to_take;
                $update_query = "UPDATE stock_batches SET quantity_remaining = ? WHERE id = ?";
                $stmt = mysqli_prepare($this->connection, $update_query);
                mysqli_stmt_bind_param($stmt, "ii", $new_remaining, $batch['id']);
                mysqli_stmt_execute($stmt);
                
                // Record movement
                $this->recordStockMovement($batch['id'], $order_id, 'OUT', $quantity_to_take, $batch['purchase_price'], "ORDER-" . str_pad($order_id, 4, '0', STR_PAD_LEFT));
                
                $movements[] = [
                    'batch_id' => $batch['id'],
                    'location_id' => $batch['location_id'],
                    'quantity' => $quantity_to_take
                ];
                
                $remaining_quantity -= $quantity_to_take;
                
                // Check thresholds after stock reduction
                $this->checkStockThresholds($tool_id, $batch['location_id']);
            }
            
            mysqli_commit($this->connection);
            
            return [
                'success' => true,
                'message' => 'Order processed successfully',
                'method_used' => $method,
                'movements' => $movements
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
}
?>