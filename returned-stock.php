<?php
require "connection.php";
require "EnhancedInventoryManager.php";

if(!empty($_SESSION["id"])){
    $id = $_SESSION["id"];
    $check = mysqli_query($con,"SELECT * FROM `admin` WHERE id=$id ");
    $row = mysqli_fetch_array($check);
} else {
    header('location:loginadmin.php');
    exit();
}

$inventoryManager = new EnhancedInventoryManager($con);
$current_page = 'returned-stock';

// Handle marking damaged items as returned/written off
if(isset($_POST['process_damaged'])) {
    $damaged_id = (int)$_POST['damaged_id'];
    $action = $_POST['action']; // 'restock' or 'write_off'
    
    // Get damaged item details
    $damage_query = mysqli_query($con, "SELECT * FROM damaged_goods WHERE id = $damaged_id");
    if($damage_query && mysqli_num_rows($damage_query) > 0) {
        $damage_data = mysqli_fetch_assoc($damage_query);
        
        if($action == 'write_off') {
            // Move to returned_stock as written off
            $stmt = mysqli_prepare($con, "INSERT INTO returned_stock (tool_id, tool_name, quantity_returned, return_reason, condition_status, processed_by, restock_status, notes) VALUES (?, ?, ?, ?, 'UNUSABLE', ?, 'WRITTEN_OFF', ?)");
            $notes = "Written off from damaged goods - " . $damage_data['damage_reason'];
            mysqli_stmt_bind_param($stmt, "isisss", $damage_data['tool_id'], $damage_data['tool_name'], $damage_data['quantity_removed'], $damage_data['damage_reason'], $id, $notes);
            mysqli_stmt_execute($stmt);
            
            // Delete from damaged_goods
            mysqli_query($con, "DELETE FROM damaged_goods WHERE id = $damaged_id");
            
            $message = "Damaged items written off successfully";
            $message_type = "success";
        }
    }
}

// Handle adding items back to stock from returns
if(isset($_POST['restock_return'])) {
    $return_id = (int)$_POST['return_id'];
    
    $return_query = mysqli_query($con, "SELECT * FROM returned_stock WHERE id = $return_id AND restock_status = 'PENDING'");
    if($return_query && mysqli_num_rows($return_query) > 0) {
        $return_data = mysqli_fetch_assoc($return_query);
        
        // Add quantity back to tool
        $update_stock = mysqli_query($con, "UPDATE tool SET u_itemsnumber = u_itemsnumber + " . $return_data['quantity_returned'] . " WHERE id = " . $return_data['tool_id']);
        
        if($update_stock) {
            // Update return status
            mysqli_query($con, "UPDATE returned_stock SET restock_status = 'RESTOCKED', processed_by = $id WHERE id = $return_id");
            $message = "Items restocked successfully";
            $message_type = "success";
        }
    }
}

// Handle new return entry
if(isset($_POST['add_return'])) {
    $tool_id = (int)$_POST['tool_id'];
    $quantity = (int)$_POST['quantity_returned'];
    $return_reason = mysqli_real_escape_string($con, $_POST['return_reason']);
    $condition_status = mysqli_real_escape_string($con, $_POST['condition_status']);
    $notes = mysqli_real_escape_string($con, $_POST['notes']);
    
    // Get tool name
    $tool_query = mysqli_query($con, "SELECT u_toolname FROM tool WHERE id = $tool_id");
    $tool_name = mysqli_fetch_assoc($tool_query)['u_toolname'];
    
    $stmt = mysqli_prepare($con, "INSERT INTO returned_stock (tool_id, tool_name, quantity_returned, return_reason, condition_status, processed_by, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "isissss", $tool_id, $tool_name, $quantity, $return_reason, $condition_status, $id, $notes);
    
    if(mysqli_stmt_execute($stmt)) {
        $message = "Return recorded successfully";
        $message_type = "success";
    } else {
        $message = "Error recording return";
        $message_type = "error";
    }
}

// Handle write off from returns
if(isset($_POST['write_off_return'])) {
    $return_id = (int)$_POST['return_id'];
    
    $return_query = mysqli_query($con, "SELECT * FROM returned_stock WHERE id = $return_id AND restock_status = 'PENDING'");
    if($return_query && mysqli_num_rows($return_query) > 0) {
        mysqli_query($con, "UPDATE returned_stock SET restock_status = 'WRITTEN_OFF', processed_by = $id WHERE id = $return_id");
        $message = "Items written off successfully";
        $message_type = "success";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="CSS/modern-dashboard.css">
    <link rel="stylesheet" href="CSS/modern-tables.css">
    <link rel="stylesheet" href="CSS/modern-forms.css">
    <link rel="shortcut icon" href="./images/Capture.JPG" type="image/x-icon">
    <script src="https://kit.fontawesome.com/14ff3ea278.js" crossorigin="anonymous"></script>
    <title>BAFRACOO - Returned Stock</title>
    <style>
        .return-card {
            background: linear-gradient(135deg, #f59e0b, #fbbf24);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-restocked { background: #d1fae5; color: #065f46; }
        .status-written-off { background: #fee2e2; color: #991b1b; }
        .condition-good { background: #d1fae5; color: #065f46; }
        .condition-damaged { background: #fef3c7; color: #92400e; }
        .condition-unusable { background: #fee2e2; color: #991b1b; }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover { color: #000; }
        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.75rem;
            font-weight: 600;
            margin-right: 4px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-restock { background: #10b981; color: white; }
        .btn-restock:hover { background: #059669; }
        .btn-write-off { background: #ef4444; color: white; }
        .btn-write-off:hover { background: #dc2626; }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .summary-card {
            padding: 1.5rem;
            border-radius: 12px;
            color: white;
        }
        .summary-card h4 {
            margin: 0 0 0.5rem 0;
            font-size: 0.875rem;
            opacity: 0.9;
        }
        .summary-card .value {
            font-size: 2rem;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include 'includes/admin_sidebar.php'; ?>

        <!-- Sidebar Overlay for Mobile -->
        <div class="sidebar-overlay"></div>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <button class="mobile-menu-btn">
                        <ion-icon name="menu-outline"></ion-icon>
                    </button>
                    <h1 class="page-title">📦 Returned Stock Management</h1>
                </div>
                <div class="header-right">
                    <button onclick="openReturnModal()" class="btn btn-primary" style="margin-right: 1rem; padding: 8px 16px; border-radius: 8px; background: #f59e0b; color: white; border: none; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                        <ion-icon name="add-outline"></ion-icon> Record Return
                    </button>
                    <a href="logout.php" class="logout-btn">
                        <ion-icon name="log-out-outline"></ion-icon>
                        <span>Logout</span>
                    </a>
                </div>
            </header>

            <div class="content-area">
                <?php if(isset($message)): ?>
                <div class="alert" style="padding: 1rem; border-radius: 8px; margin-bottom: 1rem; background: <?php echo $message_type == 'success' ? '#d1fae5' : '#fee2e2'; ?>; color: <?php echo $message_type == 'success' ? '#065f46' : '#991b1b'; ?>;">
                    <ion-icon name="<?php echo $message_type == 'success' ? 'checkmark-circle' : 'alert-circle'; ?>-outline" style="margin-right: 8px;"></ion-icon>
                    <?php echo $message; ?>
                </div>
                <?php endif; ?>

                <!-- Summary Cards -->
                <div class="summary-grid">
                    <div class="summary-card" style="background: linear-gradient(135deg, #f59e0b, #fbbf24);">
                        <h4>Pending Returns</h4>
                        <div class="value">
                            <?php
                            $pending_query = mysqli_query($con, "SELECT COUNT(*) as count FROM returned_stock WHERE restock_status = 'PENDING'");
                            echo mysqli_fetch_assoc($pending_query)['count'];
                            ?>
                        </div>
                    </div>
                    <div class="summary-card" style="background: linear-gradient(135deg, #10b981, #34d399);">
                        <h4>Restocked Items</h4>
                        <div class="value">
                            <?php
                            $restocked_query = mysqli_query($con, "SELECT COALESCE(SUM(quantity_returned), 0) as total FROM returned_stock WHERE restock_status = 'RESTOCKED'");
                            echo mysqli_fetch_assoc($restocked_query)['total'];
                            ?>
                        </div>
                    </div>
                    <div class="summary-card" style="background: linear-gradient(135deg, #ef4444, #f87171);">
                        <h4>Written Off</h4>
                        <div class="value">
                            <?php
                            $written_off_query = mysqli_query($con, "SELECT COALESCE(SUM(quantity_returned), 0) as total FROM returned_stock WHERE restock_status = 'WRITTEN_OFF'");
                            echo mysqli_fetch_assoc($written_off_query)['total'];
                            ?>
                        </div>
                    </div>
                    <div class="summary-card" style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
                        <h4>From Damage Control</h4>
                        <div class="value">
                            <?php
                            $from_damage = mysqli_query($con, "SELECT COUNT(*) as count FROM damaged_goods");
                            echo mysqli_fetch_assoc($from_damage)['count'];
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Damaged Items Pending Processing -->
                <div class="dashboard-card" style="margin-bottom: 2rem;">
                    <div class="card-header" style="padding: 1.5rem; border-bottom: 1px solid #e5e7eb;">
                        <h3 style="margin: 0; font-size: 1.25rem; font-weight: 600;">🚨 Damaged Items Pending Processing</h3>
                        <p style="margin: 0.25rem 0 0 0; color: #6b7280; font-size: 0.875rem;">Items reported as damaged that need to be written off</p>
                    </div>
                    <div class="table-container" style="padding: 0;">
                        <table class="modern-table" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Damage Reason</th>
                                    <th>Estimated Loss</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $damaged_query = mysqli_query($con, "SELECT * FROM damaged_goods ORDER BY damage_date DESC");
                                if($damaged_query && mysqli_num_rows($damaged_query) > 0):
                                    while($damage = mysqli_fetch_assoc($damaged_query)):
                                ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($damage['damage_date'])); ?></td>
                                    <td><strong><?php echo htmlspecialchars($damage['tool_name']); ?></strong></td>
                                    <td><span style="color: #dc2626; font-weight: 600;"><?php echo $damage['quantity_removed']; ?></span></td>
                                    <td><?php echo htmlspecialchars($damage['damage_reason']); ?></td>
                                    <td style="font-weight: 600;">RWF <?php echo number_format($damage['original_value'] ?? 0, 0); ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="damaged_id" value="<?php echo $damage['id']; ?>">
                                            <input type="hidden" name="action" value="write_off">
                                            <button type="submit" name="process_damaged" class="action-btn btn-write-off" onclick="return confirm('Write off these damaged items?')">
                                                <ion-icon name="trash-outline"></ion-icon> Write Off
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 2rem; color: #6b7280;">
                                        <ion-icon name="checkmark-circle-outline" style="font-size: 3rem; color: #10b981;"></ion-icon>
                                        <p style="margin: 1rem 0 0 0;">No pending damaged items to process.</p>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Returned Stock List -->
                <div class="dashboard-card">
                    <div class="card-header" style="padding: 1.5rem; border-bottom: 1px solid #e5e7eb;">
                        <h3 style="margin: 0; font-size: 1.25rem; font-weight: 600;">📋 Returned Stock History</h3>
                        <p style="margin: 0.25rem 0 0 0; color: #6b7280; font-size: 0.875rem;">All recorded stock returns and their status</p>
                    </div>
                    <div class="table-container" style="padding: 0;">
                        <table class="modern-table" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Reason</th>
                                    <th>Condition</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $returns_query = mysqli_query($con, "SELECT * FROM returned_stock ORDER BY return_date DESC");
                                if($returns_query && mysqli_num_rows($returns_query) > 0):
                                    while($return = mysqli_fetch_assoc($returns_query)):
                                ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($return['return_date'])); ?></td>
                                    <td><strong><?php echo htmlspecialchars($return['tool_name']); ?></strong></td>
                                    <td><?php echo $return['quantity_returned']; ?></td>
                                    <td><?php echo htmlspecialchars($return['return_reason']); ?></td>
                                    <td>
                                        <span class="status-badge condition-<?php echo strtolower($return['condition_status']); ?>">
                                            <?php echo $return['condition_status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower(str_replace('_', '-', $return['restock_status'])); ?>">
                                            <?php echo str_replace('_', ' ', $return['restock_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($return['restock_status'] == 'PENDING' && $return['condition_status'] == 'GOOD'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="return_id" value="<?php echo $return['id']; ?>">
                                            <button type="submit" name="restock_return" class="action-btn btn-restock" onclick="return confirm('Restock these items?')">
                                                <ion-icon name="arrow-up-outline"></ion-icon> Restock
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                        <?php if($return['restock_status'] == 'PENDING'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="return_id" value="<?php echo $return['id']; ?>">
                                            <button type="submit" name="write_off_return" class="action-btn btn-write-off" onclick="return confirm('Write off these items?')">
                                                <ion-icon name="trash-outline"></ion-icon> Write Off
                                            </button>
                                        </form>
                                        <?php elseif($return['restock_status'] != 'PENDING'): ?>
                                        <span style="color: #6b7280; font-size: 0.75rem;">Processed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 2rem; color: #6b7280;">
                                        <ion-icon name="cube-outline" style="font-size: 3rem; color: #9ca3af;"></ion-icon>
                                        <p style="margin: 1rem 0 0 0;">No returned stock records found.</p>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Return Modal -->
    <div id="returnModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeReturnModal()">&times;</span>
            <h2 style="margin: 0 0 1.5rem 0; display: flex; align-items: center; gap: 10px;">
                <ion-icon name="return-up-back" style="color: #f59e0b;"></ion-icon>
                Record Stock Return
            </h2>
            
            <form method="POST">
                <div style="display: grid; gap: 1rem;">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Product:</label>
                        <select name="tool_id" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1rem;">
                            <option value="">Select Product</option>
                            <?php
                            $tools_query = mysqli_query($con, "SELECT * FROM tool ORDER BY u_toolname");
                            while($tool = mysqli_fetch_array($tools_query)):
                            ?>
                            <option value="<?php echo $tool['id']; ?>">
                                <?php echo htmlspecialchars($tool['u_toolname']); ?> (Stock: <?php echo $tool['u_itemsnumber']; ?>)
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Quantity Returned:</label>
                        <input type="number" name="quantity_returned" min="1" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1rem;">
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Return Reason:</label>
                        <select name="return_reason" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1rem;">
                            <option value="Customer Return">Customer Return</option>
                            <option value="Defective Product">Defective Product</option>
                            <option value="Wrong Item Shipped">Wrong Item Shipped</option>
                            <option value="Order Cancelled">Order Cancelled</option>
                            <option value="Excess Inventory">Excess Inventory</option>
                            <option value="Supplier Return">Supplier Return</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Condition:</label>
                        <select name="condition_status" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1rem;">
                            <option value="GOOD">Good - Can be restocked</option>
                            <option value="DAMAGED">Damaged - Needs repair</option>
                            <option value="UNUSABLE">Unusable - Write off</option>
                        </select>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Notes:</label>
                        <textarea name="notes" rows="3" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1rem; resize: vertical;" placeholder="Additional notes about the return..."></textarea>
                    </div>
                    
                    <button type="submit" name="add_return" style="padding: 12px; background: #f59e0b; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <ion-icon name="save-outline"></ion-icon> Record Return
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    
    <script>
        function openReturnModal() {
            document.getElementById('returnModal').style.display = 'block';
        }
        
        function closeReturnModal() {
            document.getElementById('returnModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('returnModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>
