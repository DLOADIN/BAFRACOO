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
$current_page = 'damaged-products';

// Handle damage reporting - removes items from stock
if(isset($_POST['report_damage'])) {
    $tool_id = (int)$_POST['tool_id'];
    $quantity_damaged = (int)$_POST['quantity_damaged'];
    $damage_reason = mysqli_real_escape_string($con, $_POST['damage_reason']);
    $damage_description = mysqli_real_escape_string($con, $_POST['damage_description'] ?? '');
    $auto_transfer = isset($_POST['auto_transfer']) && $_POST['auto_transfer'] == '1';
    
    // Get tool details
    $tool_query = mysqli_query($con, "SELECT * FROM tool WHERE id = $tool_id");
    if($tool_query && mysqli_num_rows($tool_query) > 0) {
        $tool_data = mysqli_fetch_assoc($tool_query);
        
        // Check if enough stock
        if($tool_data['u_itemsnumber'] >= $quantity_damaged) {
            // Calculate loss value
            $loss_value = $quantity_damaged * $tool_data['u_price'];
            
            // Insert into damaged_goods table
            $stmt = mysqli_prepare($con, "INSERT INTO damaged_goods (tool_id, tool_name, quantity_removed, damage_reason, reported_by, notes, original_value) VALUES (?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "isisssd", $tool_id, $tool_data['u_toolname'], $quantity_damaged, $damage_reason, $id, $damage_description, $loss_value);
            
            if(mysqli_stmt_execute($stmt)) {
                $damage_id = mysqli_insert_id($con);
                
                // Remove items from stock
                $new_quantity = $tool_data['u_itemsnumber'] - $quantity_damaged;
                mysqli_query($con, "UPDATE tool SET u_itemsnumber = $new_quantity WHERE id = $tool_id");
                
                $message = "Damage reported successfully. $quantity_damaged items removed from stock.";
                $message_type = "success";
                
                // Auto-transfer to returned stock if checkbox was checked
                if($auto_transfer) {
                    $transfer_notes = "Auto-transferred from Damage Control - " . $damage_description;
                    $transfer_stmt = mysqli_prepare($con, "INSERT INTO returned_stock (tool_id, tool_name, quantity_returned, return_reason, condition_status, processed_by, restock_status, notes, original_value) VALUES (?, ?, ?, ?, 'DAMAGED', ?, 'PENDING', ?, ?)");
                    mysqli_stmt_bind_param($transfer_stmt, "isisssd", $tool_id, $tool_data['u_toolname'], $quantity_damaged, $damage_reason, $id, $transfer_notes, $loss_value);
                    
                    if(mysqli_stmt_execute($transfer_stmt)) {
                        // Delete from damaged_goods since it's now in returned_stock
                        mysqli_query($con, "DELETE FROM damaged_goods WHERE id = $damage_id");
                        $message .= " Also transferred to Returned Stock for processing.";
                    }
                }
            } else {
                $message = "Error recording damage report";
                $message_type = "error";
            }
        } else {
            $message = "Error: Not enough stock. Available: " . $tool_data['u_itemsnumber'];
            $message_type = "error";
        }
    } else {
        $message = "Error: Tool not found";
        $message_type = "error";
    }
}

// Handle deleting damage record
if(isset($_POST['delete_damage'])) {
    $damage_id = (int)$_POST['damage_id'];
    mysqli_query($con, "DELETE FROM damaged_goods WHERE id = $damage_id");
    $message = "Damage record deleted";
    $message_type = "success";
}

// Handle bulk transfer to returned stock
if(isset($_POST['bulk_transfer'])) {
    $selected_ids = json_decode($_POST['selected_ids'], true);
    if(!empty($selected_ids)) {
        $success_count = 0;
        foreach($selected_ids as $damage_id) {
            $damage_id = (int)$damage_id;
            $damage_query = mysqli_query($con, "SELECT * FROM damaged_goods WHERE id = $damage_id");
            if($damage_query && mysqli_num_rows($damage_query) > 0) {
                $damage_data = mysqli_fetch_assoc($damage_query);
                
                // Insert into returned_stock
                $stmt = mysqli_prepare($con, "INSERT INTO returned_stock (tool_id, tool_name, quantity_returned, return_reason, condition_status, processed_by, restock_status, notes, original_value) VALUES (?, ?, ?, ?, 'DAMAGED', ?, 'PENDING', ?, ?)");
                $notes = "Bulk transferred from Damage Control - " . ($damage_data['notes'] ?? '');
                mysqli_stmt_bind_param($stmt, "isisssd", $damage_data['tool_id'], $damage_data['tool_name'], $damage_data['quantity_removed'], $damage_data['damage_reason'], $id, $notes, $damage_data['original_value']);
                
                if(mysqli_stmt_execute($stmt)) {
                    mysqli_query($con, "DELETE FROM damaged_goods WHERE id = $damage_id");
                    $success_count++;
                }
            }
        }
        $message = "$success_count record(s) transferred to Returned Stock";
        $message_type = "success";
    }
}

// Handle bulk delete
if(isset($_POST['bulk_delete'])) {
    $selected_ids = json_decode($_POST['selected_ids'], true);
    if(!empty($selected_ids)) {
        $ids_string = implode(',', array_map('intval', $selected_ids));
        mysqli_query($con, "DELETE FROM damaged_goods WHERE id IN ($ids_string)");
        $message = count($selected_ids) . " record(s) deleted";
        $message_type = "success";
    }
}

// Handle editing damage record
if(isset($_POST['edit_damage'])) {
    $damage_id = (int)$_POST['damage_id'];
    $edit_quantity = (int)$_POST['edit_quantity'];
    $edit_reason = mysqli_real_escape_string($con, $_POST['edit_reason']);
    $edit_notes = mysqli_real_escape_string($con, $_POST['edit_notes']);
    
    // Get current damage record to calculate difference
    $current_damage = mysqli_query($con, "SELECT * FROM damaged_goods WHERE id = $damage_id");
    if($current_damage && mysqli_num_rows($current_damage) > 0) {
        $current_data = mysqli_fetch_assoc($current_damage);
        $old_quantity = $current_data['quantity_removed'];
        $tool_id = $current_data['tool_id'];
        
        // Get tool price to recalculate loss
        $tool_query = mysqli_query($con, "SELECT u_price, u_itemsnumber FROM tool WHERE id = $tool_id");
        $tool_data = mysqli_fetch_assoc($tool_query);
        $new_loss_value = $edit_quantity * $tool_data['u_price'];
        
        // Adjust stock based on quantity change
        $quantity_diff = $old_quantity - $edit_quantity;
        $new_stock = $tool_data['u_itemsnumber'] + $quantity_diff;
        
        if($new_stock >= 0) {
            // Update damage record
            mysqli_query($con, "UPDATE damaged_goods SET quantity_removed = $edit_quantity, damage_reason = '$edit_reason', notes = '$edit_notes', original_value = $new_loss_value WHERE id = $damage_id");
            
            // Update tool stock
            mysqli_query($con, "UPDATE tool SET u_itemsnumber = $new_stock WHERE id = $tool_id");
            
            $message = "Damage record updated successfully";
            $message_type = "success";
        } else {
            $message = "Error: Cannot increase damage quantity beyond available stock";
            $message_type = "error";
        }
    }
}

// Handle transferring damage to returned stock
if(isset($_POST['transfer_to_returned'])) {
    $damage_id = (int)$_POST['damage_id'];
    
    $damage_query = mysqli_query($con, "SELECT * FROM damaged_goods WHERE id = $damage_id");
    if($damage_query && mysqli_num_rows($damage_query) > 0) {
        $damage_data = mysqli_fetch_assoc($damage_query);
        
        // Insert into returned_stock
        $stmt = mysqli_prepare($con, "INSERT INTO returned_stock (tool_id, tool_name, quantity_returned, return_reason, condition_status, processed_by, restock_status, notes, original_value) VALUES (?, ?, ?, ?, 'DAMAGED', ?, 'PENDING', ?, ?)");
        $notes = "Transferred from Damage Control - " . $damage_data['notes'];
        mysqli_stmt_bind_param($stmt, "isisssd", $damage_data['tool_id'], $damage_data['tool_name'], $damage_data['quantity_removed'], $damage_data['damage_reason'], $id, $notes, $damage_data['original_value']);
        
        if(mysqli_stmt_execute($stmt)) {
            // Delete from damaged_goods
            mysqli_query($con, "DELETE FROM damaged_goods WHERE id = $damage_id");
            $message = "Successfully transferred to Returned Stock for processing";
            $message_type = "success";
        } else {
            $message = "Error transferring record";
            $message_type = "error";
        }
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
    <title>BAFRACOO - Damage Control</title>
    <style>
        .damage-card {
            background: linear-gradient(135deg, #dc2626, #ef4444);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
        }
        .damage-reason {
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            background: #fef3c7;
            color: #92400e;
        }
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
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
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
        .loss-amount {
            color: #dc2626;
            font-weight: 700;
        }
        .stock-info {
            background: #f3f4f6;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }
        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.2s;
        }
        .btn-danger { background: #fee2e2; color: #dc2626; }
        .btn-danger:hover { background: #fecaca; }
        .btn-edit { background: #dbeafe; color: #2563eb; }
        .btn-edit:hover { background: #bfdbfe; }
        .btn-transfer { background: #d1fae5; color: #059669; }
        .btn-transfer:hover { background: #a7f3d0; }
        .btn-add-damage { background: #fef3c7; color: #92400e; }
        .btn-add-damage:hover { background: #fde68a; }
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
        /* Inventory Status Badges */
        .inventory-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .inventory-status.in-stock { background: #d1fae5; color: #065f46; }
        .inventory-status.low-stock { background: #fef3c7; color: #92400e; }
        .inventory-status.out-stock { background: #fee2e2; color: #991b1b; }
        /* Inventory table row hover */
        .inventory-row:hover { background: #f9fafb; }
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
                    <h1 class="page-title">⚠️ Damage Control</h1>
                </div>
                <div class="header-right">
                    <button onclick="openDamageModal()" class="btn btn-primary" style="margin-right: 1rem; padding: 8px 16px; border-radius: 8px; background: #dc2626; color: white; border: none; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                        <ion-icon name="alert-circle-outline"></ion-icon> Report Damage
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
                    <div class="summary-card" style="background: linear-gradient(135deg, #dc2626, #ef4444);">
                        <h4>Total Damaged Items</h4>
                        <div class="value">
                            <?php
                            $total_damaged = mysqli_query($con, "SELECT COALESCE(SUM(quantity_removed), 0) as total FROM damaged_goods");
                            echo mysqli_fetch_assoc($total_damaged)['total'];
                            ?>
                        </div>
                    </div>
                    <div class="summary-card" style="background: linear-gradient(135deg, #f59e0b, #fbbf24);">
                        <h4>Total Loss Value</h4>
                        <div class="value">
                            <?php
                            $total_loss = mysqli_query($con, "SELECT COALESCE(SUM(original_value), 0) as total FROM damaged_goods");
                            echo 'RWF ' . number_format(mysqli_fetch_assoc($total_loss)['total'], 0);
                            ?>
                        </div>
                    </div>
                    <div class="summary-card" style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
                        <h4>Damage Reports</h4>
                        <div class="value">
                            <?php
                            $reports_count = mysqli_query($con, "SELECT COUNT(*) as count FROM damaged_goods");
                            echo mysqli_fetch_assoc($reports_count)['count'];
                            ?>
                        </div>
                    </div>
                    <div class="summary-card" style="background: linear-gradient(135deg, #10b981, #34d399);">
                        <h4>This Month</h4>
                        <div class="value">
                            <?php
                            $this_month = mysqli_query($con, "SELECT COALESCE(SUM(quantity_removed), 0) as total FROM damaged_goods WHERE MONTH(damage_date) = MONTH(CURRENT_DATE()) AND YEAR(damage_date) = YEAR(CURRENT_DATE())");
                            echo mysqli_fetch_assoc($this_month)['total'];
                            ?>
                        </div>
                    </div>
                </div>

                <!-- All Inventory Items Table - For Adding Damages -->
                <div class="dashboard-card" style="margin-bottom: 2rem;">
                    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; border-bottom: 1px solid #e5e7eb; background: linear-gradient(135deg, #eff6ff, #dbeafe);">
                        <div>
                            <h3 style="margin: 0; font-size: 1.25rem; font-weight: 600; color: #1e40af;">
                                <ion-icon name="cube-outline" style="margin-right: 8px;"></ion-icon>
                                📦 All Inventory Items
                            </h3>
                            <p style="margin: 0.25rem 0 0 0; color: #3b82f6; font-size: 0.875rem;">Select a product and click "Add Damage" to report damaged items</p>
                        </div>
                        <div style="display: flex; gap: 1rem; align-items: center;">
                            <input type="text" id="inventorySearch" onkeyup="filterInventoryTable()" placeholder="Search products..." style="padding: 8px 14px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.875rem; width: 200px;">
                        </div>
                    </div>
                    <div class="table-container" style="padding: 0; max-height: 500px; overflow-y: auto;">
                        <table class="modern-table" style="width: 100%;" id="inventoryTable">
                            <thead style="position: sticky; top: 0; background: white; z-index: 10;">
                                <tr>
                                    <th>#</th>
                                    <th>Tool Name</th>
                                    <th>Type</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Total Value</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $inventory_query = mysqli_query($con, "SELECT * FROM tool ORDER BY u_toolname ASC");
                                if($inventory_query && mysqli_num_rows($inventory_query) > 0):
                                    while($item = mysqli_fetch_assoc($inventory_query)):
                                        $total_value = $item['u_itemsnumber'] * $item['u_price'];
                                        $status = $item['u_itemsnumber'] > 10 ? 'IN STOCK' : ($item['u_itemsnumber'] > 0 ? 'LOW STOCK' : 'OUT OF STOCK');
                                        $status_class = $item['u_itemsnumber'] > 10 ? 'in-stock' : ($item['u_itemsnumber'] > 0 ? 'low-stock' : 'out-stock');
                                ?>
                                <tr class="inventory-row">
                                    <td><?php echo $item['id']; ?></td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <?php if(!empty($item['u_image'])): ?>
                                            <img src="<?php echo htmlspecialchars($item['u_image']); ?>" alt="" style="width: 40px; height: 40px; border-radius: 8px; object-fit: cover;">
                                            <?php else: ?>
                                            <div style="width: 40px; height: 40px; border-radius: 8px; background: linear-gradient(135deg, #3b82f6, #1d4ed8); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600;">
                                                <?php echo strtoupper(substr($item['u_toolname'], 0, 1)); ?>
                                            </div>
                                            <?php endif; ?>
                                            <div>
                                                <strong><?php echo htmlspecialchars($item['u_toolname']); ?></strong>
                                                <div style="font-size: 0.7rem; color: #6b7280;">ID: <?php echo $item['id']; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['u_type'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span style="color: <?php echo $item['u_itemsnumber'] > 10 ? '#059669' : ($item['u_itemsnumber'] > 0 ? '#d97706' : '#dc2626'); ?>; font-weight: 600;">
                                            <?php echo number_format($item['u_itemsnumber']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo number_format($item['u_price']); ?> RWF</td>
                                    <td><strong><?php echo number_format($total_value); ?> RWF</strong></td>
                                    <td>
                                        <span class="inventory-status <?php echo $status_class; ?>">
                                            <?php echo $status; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($item['u_itemsnumber'] > 0): ?>
                                        <button type="button" onclick="openQuickDamageModal(<?php echo $item['id']; ?>, '<?php echo addslashes($item['u_toolname']); ?>', <?php echo $item['u_itemsnumber']; ?>, <?php echo $item['u_price']; ?>)" 
                                                class="action-btn btn-add-damage">
                                            <ion-icon name="add-circle-outline"></ion-icon> Add Damage
                                        </button>
                                        <?php else: ?>
                                        <span style="color: #9ca3af; font-size: 0.75rem;">No stock</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 2rem; color: #6b7280;">
                                        <ion-icon name="cube-outline" style="font-size: 3rem; color: #9ca3af;"></ion-icon>
                                        <p style="margin: 1rem 0 0 0;">No inventory items found.</p>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Info Banner -->
                <div class="dashboard-card" style="background: linear-gradient(135deg, #fef3c7, #fde68a); border: 1px solid #f59e0b; margin-bottom: 2rem;">
                    <div style="padding: 1rem; display: flex; align-items: center; gap: 1rem;">
                        <ion-icon name="information-circle" style="font-size: 2rem; color: #92400e;"></ion-icon>
                        <div>
                            <h4 style="margin: 0; color: #92400e;">How Damage Control Works</h4>
                            <p style="margin: 0.5rem 0 0 0; color: #78350f; font-size: 0.875rem;">
                                1️⃣ Click <strong>"Add Damage"</strong> on any product above to report damage. 
                                2️⃣ The damaged items are recorded below and automatically removed from stock.
                                3️⃣ <strong>Transfer</strong> to <a href="returned-stock.php" style="color: #dc2626; font-weight: 600;">Returned Stock</a> for write-off processing.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Damage Records Section Header -->
                <h2 style="margin: 0 0 1rem 0; font-size: 1.5rem; font-weight: 700; color: #1f2937; display: flex; align-items: center; gap: 10px;">
                    <ion-icon name="alert-circle" style="color: #dc2626;"></ion-icon>
                    Damage Records
                </h2>

                <!-- Filter & Search Section -->
                <div class="dashboard-card" style="margin-bottom: 1.5rem;">
                    <div style="padding: 1.25rem;">
                        <form method="GET" style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end;">
                            <div style="flex: 1; min-width: 200px;">
                                <label style="display: block; font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-bottom: 6px;">Search Product</label>
                                <input type="text" name="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" placeholder="Search by product name..." style="width: 100%; padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.875rem;">
                            </div>
                            <div style="min-width: 150px;">
                                <label style="display: block; font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-bottom: 6px;">Filter by Reason</label>
                                <select name="reason_filter" style="width: 100%; padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.875rem; background: white;">
                                    <option value="">All Reasons</option>
                                    <option value="Breakage" <?php echo (isset($_GET['reason_filter']) && $_GET['reason_filter'] == 'Breakage') ? 'selected' : ''; ?>>Breakage</option>
                                    <option value="Expiry" <?php echo (isset($_GET['reason_filter']) && $_GET['reason_filter'] == 'Expiry') ? 'selected' : ''; ?>>Expiry</option>
                                    <option value="Defect" <?php echo (isset($_GET['reason_filter']) && $_GET['reason_filter'] == 'Defect') ? 'selected' : ''; ?>>Defect</option>
                                    <option value="Water Damage" <?php echo (isset($_GET['reason_filter']) && $_GET['reason_filter'] == 'Water Damage') ? 'selected' : ''; ?>>Water Damage</option>
                                    <option value="Fire Damage" <?php echo (isset($_GET['reason_filter']) && $_GET['reason_filter'] == 'Fire Damage') ? 'selected' : ''; ?>>Fire Damage</option>
                                    <option value="Other" <?php echo (isset($_GET['reason_filter']) && $_GET['reason_filter'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div style="min-width: 150px;">
                                <label style="display: block; font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-bottom: 6px;">Sort By</label>
                                <select name="sort" style="width: 100%; padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.875rem; background: white;">
                                    <option value="date_desc" <?php echo (!isset($_GET['sort']) || $_GET['sort'] == 'date_desc') ? 'selected' : ''; ?>>Newest First</option>
                                    <option value="date_asc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'date_asc') ? 'selected' : ''; ?>>Oldest First</option>
                                    <option value="value_desc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'value_desc') ? 'selected' : ''; ?>>Highest Value</option>
                                    <option value="value_asc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'value_asc') ? 'selected' : ''; ?>>Lowest Value</option>
                                    <option value="qty_desc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'qty_desc') ? 'selected' : ''; ?>>Highest Quantity</option>
                                </select>
                            </div>
                            <div style="display: flex; gap: 8px;">
                                <button type="submit" style="padding: 10px 20px; background: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 6px;">
                                    <ion-icon name="filter-outline"></ion-icon> Filter
                                </button>
                                <a href="damaged-products.php" style="padding: 10px 20px; background: #f3f4f6; color: #374151; border: none; border-radius: 8px; text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 6px;">
                                    <ion-icon name="refresh-outline"></ion-icon> Reset
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Damaged Goods Table -->
                <div class="dashboard-card">
                    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; border-bottom: 1px solid #e5e7eb;">
                        <div>
                            <h3 style="margin: 0; font-size: 1.25rem; font-weight: 600;">📋 Damage Records</h3>
                            <p style="margin: 0.25rem 0 0 0; color: #6b7280; font-size: 0.875rem;">All reported damaged goods - Edit, Transfer, or Mark as Processed</p>
                        </div>
                        <div style="display: flex; gap: 1rem; align-items: center;">
                            <span style="font-size: 0.75rem; color: #6b7280;">
                                <?php
                                // Build filter query for count
                                $count_where = "1=1";
                                if(isset($_GET['search']) && !empty($_GET['search'])) {
                                    $search = mysqli_real_escape_string($con, $_GET['search']);
                                    $count_where .= " AND tool_name LIKE '%$search%'";
                                }
                                if(isset($_GET['reason_filter']) && !empty($_GET['reason_filter'])) {
                                    $reason = mysqli_real_escape_string($con, $_GET['reason_filter']);
                                    $count_where .= " AND damage_reason = '$reason'";
                                }
                                $count_result = mysqli_query($con, "SELECT COUNT(*) as total FROM damaged_goods WHERE $count_where");
                                $total_records = mysqli_fetch_assoc($count_result)['total'];
                                echo "Showing $total_records record(s)";
                                ?>
                            </span>
                            <a href="returned-stock.php" style="display: flex; align-items: center; gap: 6px; color: #3b82f6; text-decoration: none; font-weight: 500; font-size: 0.875rem;">
                                <ion-icon name="arrow-forward-outline"></ion-icon> View Returned Stock
                            </a>
                        </div>
                    </div>
                    <div class="table-container" style="padding: 0;">
                        <table class="modern-table" style="width: 100%;" id="damageTable">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="selectAll" onclick="toggleSelectAll()" title="Select All"></th>
                                    <th>ID</th>
                                    <th>Date</th>
                                    <th>Product</th>
                                    <th>Qty Removed</th>
                                    <th>Reason</th>
                                    <th>Loss Value</th>
                                    <th>Notes</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Build dynamic query based on filters
                                $where_clause = "1=1";
                                $order_clause = "dg.damage_date DESC";
                                
                                if(isset($_GET['search']) && !empty($_GET['search'])) {
                                    $search = mysqli_real_escape_string($con, $_GET['search']);
                                    $where_clause .= " AND dg.tool_name LIKE '%$search%'";
                                }
                                if(isset($_GET['reason_filter']) && !empty($_GET['reason_filter'])) {
                                    $reason = mysqli_real_escape_string($con, $_GET['reason_filter']);
                                    $where_clause .= " AND dg.damage_reason = '$reason'";
                                }
                                
                                // Sorting
                                if(isset($_GET['sort'])) {
                                    switch($_GET['sort']) {
                                        case 'date_asc': $order_clause = "dg.damage_date ASC"; break;
                                        case 'value_desc': $order_clause = "dg.original_value DESC"; break;
                                        case 'value_asc': $order_clause = "dg.original_value ASC"; break;
                                        case 'qty_desc': $order_clause = "dg.quantity_removed DESC"; break;
                                        default: $order_clause = "dg.damage_date DESC";
                                    }
                                }
                                
                                $damage_query = mysqli_query($con, "SELECT dg.*, a.u_name as reporter_name FROM damaged_goods dg LEFT JOIN admin a ON dg.reported_by = a.id WHERE $where_clause ORDER BY $order_clause");
                                if($damage_query && mysqli_num_rows($damage_query) > 0):
                                    while($damage = mysqli_fetch_assoc($damage_query)):
                                ?>
                                <tr>
                                    <td><input type="checkbox" class="row-checkbox" value="<?php echo $damage['id']; ?>" data-name="<?php echo htmlspecialchars($damage['tool_name']); ?>"></td>
                                    <td><span style="background: #f3f4f6; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem;">#<?php echo $damage['id']; ?></span></td>
                                    <td>
                                        <div style="font-weight: 500;"><?php echo date('M d, Y', strtotime($damage['damage_date'])); ?></div>
                                        <div style="font-size: 0.75rem; color: #6b7280;"><?php echo date('h:i A', strtotime($damage['damage_date'])); ?></div>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($damage['tool_name']); ?></strong>
                                        <?php if(!empty($damage['reporter_name'])): ?>
                                        <div style="font-size: 0.7rem; color: #9ca3af;">Reported by: <?php echo htmlspecialchars($damage['reporter_name']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span style="background: #fee2e2; color: #dc2626; padding: 4px 12px; border-radius: 20px; font-weight: 600;">
                                            -<?php echo $damage['quantity_removed']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="damage-reason"><?php echo htmlspecialchars($damage['damage_reason']); ?></span>
                                    </td>
                                    <td class="loss-amount">RWF <?php echo number_format($damage['original_value'] ?? 0, 0); ?></td>
                                    <td style="max-width: 200px;">
                                        <span style="font-size: 0.875rem; color: #4b5563;">
                                            <?php echo htmlspecialchars(substr($damage['notes'] ?? '-', 0, 50)); ?>
                                            <?php if(strlen($damage['notes'] ?? '') > 50) echo '...'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                            <button type="button" class="action-btn btn-edit" onclick="openEditModal(<?php echo $damage['id']; ?>, '<?php echo addslashes($damage['tool_name']); ?>', <?php echo $damage['quantity_removed']; ?>, '<?php echo addslashes($damage['damage_reason']); ?>', '<?php echo addslashes($damage['notes'] ?? ''); ?>')">
                                                <ion-icon name="create-outline"></ion-icon> Edit
                                            </button>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Transfer this to Returned Stock?');">
                                                <input type="hidden" name="damage_id" value="<?php echo $damage['id']; ?>">
                                                <button type="submit" name="transfer_to_returned" class="action-btn btn-transfer">
                                                    <ion-icon name="arrow-forward-outline"></ion-icon> Transfer
                                                </button>
                                            </form>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this record? This action cannot be undone.');">
                                                <input type="hidden" name="damage_id" value="<?php echo $damage['id']; ?>">
                                                <button type="submit" name="delete_damage" class="action-btn btn-danger">
                                                    <ion-icon name="trash-outline"></ion-icon>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 3rem; color: #6b7280;">
                                        <ion-icon name="checkmark-circle-outline" style="font-size: 4rem; color: #10b981; display: block; margin-bottom: 1rem;"></ion-icon>
                                        <h4 style="margin: 0 0 0.5rem 0;">No Damage Reports</h4>
                                        <p style="margin: 0;">
                                            <?php if(isset($_GET['search']) || isset($_GET['reason_filter'])): ?>
                                            No records match your filter criteria. <a href="damaged-products.php">Clear filters</a>
                                            <?php else: ?>
                                            Great news! No damaged goods have been reported yet.
                                            <?php endif; ?>
                                        </p>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Bulk Actions Bar -->
                    <div id="bulkActionsBar" style="display: none; padding: 1rem 1.5rem; background: #f3f4f6; border-top: 1px solid #e5e7eb;">
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                            <span style="font-weight: 600; color: #374151;">
                                <span id="selectedCount">0</span> item(s) selected
                            </span>
                            <div style="display: flex; gap: 0.75rem;">
                                <button type="button" onclick="bulkTransfer()" class="action-btn btn-transfer" style="padding: 8px 16px;">
                                    <ion-icon name="arrow-forward-outline"></ion-icon> Transfer Selected to Returned Stock
                                </button>
                                <button type="button" onclick="bulkDelete()" class="action-btn btn-danger" style="padding: 8px 16px;">
                                    <ion-icon name="trash-outline"></ion-icon> Delete Selected
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Bulk Transfer Form (hidden) -->
    <form method="POST" id="bulkTransferForm" style="display: none;">
        <input type="hidden" name="bulk_transfer" value="1">
        <input type="hidden" name="selected_ids" id="bulkTransferIds">
    </form>
    
    <!-- Bulk Delete Form (hidden) -->
    <form method="POST" id="bulkDeleteForm" style="display: none;">
        <input type="hidden" name="bulk_delete" value="1">
        <input type="hidden" name="selected_ids" id="bulkDeleteIds">
    </form>

    <!-- Report Damage Modal -->
    <div id="damageModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeDamageModal()">&times;</span>
            <h2 style="margin: 0 0 1.5rem 0; display: flex; align-items: center; gap: 10px;">
                <ion-icon name="alert-circle" style="color: #dc2626;"></ion-icon>
                Report Damaged Stock
            </h2>
            
            <form method="POST" id="damageForm">
                <div style="display: grid; gap: 1.25rem;">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">
                            Select Product: <span style="color: #dc2626;">*</span>
                        </label>
                        <select name="tool_id" id="tool_select" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1rem;" onchange="updateStockInfo()">
                            <option value="">-- Select a product --</option>
                            <?php
                            $tools_query = mysqli_query($con, "SELECT * FROM tool WHERE u_itemsnumber > 0 ORDER BY u_toolname");
                            while($tool = mysqli_fetch_array($tools_query)):
                            ?>
                            <option value="<?php echo $tool['id']; ?>" data-stock="<?php echo $tool['u_itemsnumber']; ?>" data-price="<?php echo $tool['u_price']; ?>">
                                <?php echo htmlspecialchars($tool['u_toolname']); ?> (Stock: <?php echo $tool['u_itemsnumber']; ?>)
                            </option>
                            <?php endwhile; ?>
                        </select>
                        <div id="stock_info" class="stock-info" style="display: none;">
                            Available stock: <strong id="available_stock">0</strong> | 
                            Unit price: <strong>RWF <span id="unit_price">0</span></strong>
                        </div>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">
                            Quantity Damaged: <span style="color: #dc2626;">*</span>
                        </label>
                        <input type="number" name="quantity_damaged" id="quantity_damaged" min="1" required 
                               style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1rem;"
                               placeholder="Enter quantity to remove" onchange="calculateLoss()">
                        <div id="loss_preview" style="margin-top: 0.5rem; color: #dc2626; font-weight: 600; display: none;">
                            Estimated Loss: RWF <span id="loss_amount">0</span>
                        </div>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">
                            Damage Reason: <span style="color: #dc2626;">*</span>
                        </label>
                        <select name="damage_reason" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1rem;">
                            <option value="">-- Select reason --</option>
                            <option value="Expired">Expired</option>
                            <option value="Physical Damage">Physical Damage</option>
                            <option value="Water Damage">Water Damage</option>
                            <option value="Manufacturing Defect">Manufacturing Defect</option>
                            <option value="Storage Issues">Storage Issues</option>
                            <option value="Pest Damage">Pest Damage</option>
                            <option value="Transport Damage">Transport Damage</option>
                            <option value="Theft/Loss">Theft/Loss</option>
                            <option value="Quality Issues">Quality Issues</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">
                            Additional Details:
                        </label>
                        <textarea name="damage_description" rows="3" 
                                  style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1rem; resize: vertical;"
                                  placeholder="Provide more details about the damage..."></textarea>
                    </div>
                    
                    <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 1rem; margin-top: 0.5rem;">
                        <div style="display: flex; align-items: center; gap: 8px; color: #991b1b;">
                            <ion-icon name="warning" style="font-size: 1.25rem;"></ion-icon>
                            <strong>Warning</strong>
                        </div>
                        <p style="margin: 0.5rem 0 0 0; color: #7f1d1d; font-size: 0.875rem;">
                            This action will immediately remove the specified quantity from your inventory. This cannot be undone automatically.
                        </p>
                    </div>
                    
                    <button type="submit" name="report_damage" 
                            style="padding: 12px 24px; background: #dc2626; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 1rem; display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <ion-icon name="alert-circle-outline"></ion-icon>
                        Report Damage & Remove from Stock
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    
    <script>
        function openDamageModal() {
            document.getElementById('damageModal').style.display = 'block';
        }
        
        function closeDamageModal() {
            document.getElementById('damageModal').style.display = 'none';
        }
        
        function updateStockInfo() {
            const select = document.getElementById('tool_select');
            const stockInfo = document.getElementById('stock_info');
            const selectedOption = select.options[select.selectedIndex];
            
            if (select.value) {
                const stock = selectedOption.getAttribute('data-stock');
                const price = selectedOption.getAttribute('data-price');
                document.getElementById('available_stock').textContent = stock;
                document.getElementById('unit_price').textContent = parseInt(price).toLocaleString();
                document.getElementById('quantity_damaged').max = stock;
                stockInfo.style.display = 'block';
            } else {
                stockInfo.style.display = 'none';
            }
            calculateLoss();
        }
        
        function calculateLoss() {
            const select = document.getElementById('tool_select');
            const quantity = document.getElementById('quantity_damaged').value;
            const lossPreview = document.getElementById('loss_preview');
            
            if (select.value && quantity) {
                const selectedOption = select.options[select.selectedIndex];
                const price = parseInt(selectedOption.getAttribute('data-price'));
                const loss = price * parseInt(quantity);
                document.getElementById('loss_amount').textContent = loss.toLocaleString();
                lossPreview.style.display = 'block';
            } else {
                lossPreview.style.display = 'none';
            }
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('damageModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
        
        // Form validation
        document.getElementById('damageForm').addEventListener('submit', function(e) {
            const select = document.getElementById('tool_select');
            const quantity = parseInt(document.getElementById('quantity_damaged').value);
            const selectedOption = select.options[select.selectedIndex];
            const availableStock = parseInt(selectedOption.getAttribute('data-stock'));
            
            if (quantity > availableStock) {
                e.preventDefault();
                alert('Error: Quantity exceeds available stock (' + availableStock + ')');
                return false;
            }
        });
        
        // Bulk Selection Functions
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.row-checkbox');
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
            updateBulkActionsBar();
        }
        
        function updateBulkActionsBar() {
            const checkboxes = document.querySelectorAll('.row-checkbox:checked');
            const bulkBar = document.getElementById('bulkActionsBar');
            const countSpan = document.getElementById('selectedCount');
            
            if(checkboxes.length > 0) {
                bulkBar.style.display = 'block';
                countSpan.textContent = checkboxes.length;
            } else {
                bulkBar.style.display = 'none';
            }
        }
        
        // Add event listeners to all checkboxes
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            checkboxes.forEach(cb => {
                cb.addEventListener('change', updateBulkActionsBar);
            });
        });
        
        function getSelectedIds() {
            const checkboxes = document.querySelectorAll('.row-checkbox:checked');
            return Array.from(checkboxes).map(cb => cb.value);
        }
        
        function bulkTransfer() {
            const ids = getSelectedIds();
            if(ids.length === 0) {
                alert('Please select at least one record');
                return;
            }
            if(confirm('Transfer ' + ids.length + ' record(s) to Returned Stock?')) {
                document.getElementById('bulkTransferIds').value = JSON.stringify(ids);
                document.getElementById('bulkTransferForm').submit();
            }
        }
        
        function bulkDelete() {
            const ids = getSelectedIds();
            if(ids.length === 0) {
                alert('Please select at least one record');
                return;
            }
            if(confirm('Are you sure you want to delete ' + ids.length + ' record(s)? This cannot be undone.')) {
                document.getElementById('bulkDeleteIds').value = JSON.stringify(ids);
                document.getElementById('bulkDeleteForm').submit();
            }
        }
        
        // Edit Modal Functions
        function openEditModal(id, toolName, quantity, reason, notes) {
            document.getElementById('edit_damage_id').value = id;
            document.getElementById('edit_tool_name').value = toolName;
            document.getElementById('edit_quantity').value = quantity;
            document.getElementById('edit_reason').value = reason;
            document.getElementById('edit_notes').value = notes;
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            const damageModal = document.getElementById('damageModal');
            const editModal = document.getElementById('editModal');
            if (event.target == damageModal) {
                damageModal.style.display = 'none';
            }
            if (event.target == editModal) {
                editModal.style.display = 'none';
            }
        }
    </script>
    
    <!-- Edit Damage Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2 style="margin: 0 0 1.5rem 0; display: flex; align-items: center; gap: 10px;">
                <ion-icon name="create" style="color: #2563eb;"></ion-icon>
                Edit Damage Record
            </h2>
            
            <form method="POST" id="editDamageForm">
                <input type="hidden" name="damage_id" id="edit_damage_id">
                
                <div style="display: grid; gap: 1.25rem;">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">
                            Product Name:
                        </label>
                        <input type="text" id="edit_tool_name" readonly
                               style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1rem; background: #f9fafb;">
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">
                            Quantity Damaged: <span style="color: #dc2626;">*</span>
                        </label>
                        <input type="number" name="edit_quantity" id="edit_quantity" min="1" required 
                               style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1rem;"
                               placeholder="Enter quantity">
                        <small style="color: #6b7280; display: block; margin-top: 4px;">
                            Note: Changing quantity will adjust stock accordingly
                        </small>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">
                            Damage Reason: <span style="color: #dc2626;">*</span>
                        </label>
                        <select name="edit_reason" id="edit_reason" required 
                                style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1rem;">
                            <option value="Physical Damage">Physical Damage</option>
                            <option value="Water Damage">Water Damage</option>
                            <option value="Expired">Expired</option>
                            <option value="Manufacturing Defect">Manufacturing Defect</option>
                            <option value="Theft/Loss">Theft/Loss</option>
                            <option value="Quality Issues">Quality Issues</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">
                            Notes/Details:
                        </label>
                        <textarea name="edit_notes" id="edit_notes" rows="3" 
                                  style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1rem; resize: vertical;"
                                  placeholder="Additional notes about the damage..."></textarea>
                    </div>
                    
                    <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                        <button type="button" onclick="closeEditModal()"
                                style="flex: 1; padding: 12px 24px; background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 1rem;">
                            Cancel
                        </button>
                        <button type="submit" name="edit_damage" 
                                style="flex: 1; padding: 12px 24px; background: #2563eb; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 1rem; display: flex; align-items: center; justify-content: center; gap: 8px;">
                            <ion-icon name="checkmark-outline"></ion-icon>
                            Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Quick Damage Modal (from Inventory Table) -->
    <div id="quickDamageModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeQuickDamageModal()">&times;</span>
            <h2 style="margin: 0 0 1.5rem 0; display: flex; align-items: center; gap: 10px;">
                <ion-icon name="alert-circle" style="color: #dc2626;"></ion-icon>
                Report Damage for: <span id="quick_product_name" style="color: #2563eb;"></span>
            </h2>
            
            <form method="POST" id="quickDamageForm" onsubmit="return validateQuickDamageForm()">
                <input type="hidden" name="tool_id" id="quick_tool_id">
                
                <div style="display: grid; gap: 1.25rem;">
                    <!-- Product Info Card -->
                    <div style="background: #f3f4f6; border-radius: 8px; padding: 1rem;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                            <div>
                                <span style="font-size: 0.75rem; color: #6b7280;">Current Stock</span>
                                <div style="font-size: 1.5rem; font-weight: 700; color: #059669;" id="quick_available_stock">0</div>
                            </div>
                            <div>
                                <span style="font-size: 0.75rem; color: #6b7280;">Unit Price</span>
                                <div style="font-size: 1.5rem; font-weight: 700; color: #374151;">RWF <span id="quick_unit_price">0</span></div>
                            </div>
                            <div>
                                <span style="font-size: 0.75rem; color: #6b7280;">After Damage</span>
                                <div style="font-size: 1.5rem; font-weight: 700; color: #dc2626;" id="quick_new_stock">-</div>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">
                            Quantity Damaged: <span style="color: #dc2626;">*</span>
                        </label>
                        <input type="number" name="quantity_damaged" id="quick_quantity" min="1" required 
                               style="width: 100%; padding: 12px; border: 2px solid #d1d5db; border-radius: 8px; font-size: 1.25rem; font-weight: 600;"
                               placeholder="Enter damaged quantity" oninput="calculateQuickLoss()">
                        <small style="color: #6b7280; display: block; margin-top: 4px;">
                            Enter how many items were damaged. This will be subtracted from inventory.
                        </small>
                        <div id="quick_loss_preview" style="margin-top: 0.75rem; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 0.75rem; display: none;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 0.875rem; color: #991b1b;">Estimated Loss Value: </span>
                                <strong style="color: #dc2626; font-size: 1.25rem;">RWF <span id="quick_loss_amount">0</span></strong>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">
                            Damage Reason: <span style="color: #dc2626;">*</span>
                        </label>
                        <select name="damage_reason" id="quick_reason" required style="width: 100%; padding: 12px; border: 2px solid #d1d5db; border-radius: 8px; font-size: 1rem;">
                            <option value="">-- Select damage reason --</option>
                            <option value="Expired">Expired / Past Expiry Date</option>
                            <option value="Physical Damage">Physical Damage / Broken</option>
                            <option value="Water Damage">Water Damage</option>
                            <option value="Manufacturing Defect">Manufacturing Defect</option>
                            <option value="Storage Issues">Storage Issues / Spoilage</option>
                            <option value="Pest Damage">Pest Damage</option>
                            <option value="Transport Damage">Transport Damage</option>
                            <option value="Theft/Loss">Theft / Loss</option>
                            <option value="Quality Issues">Quality Issues / Defective</option>
                            <option value="Fire Damage">Fire Damage</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">
                            Additional Details / Notes:
                        </label>
                        <textarea name="damage_description" id="quick_notes" rows="3" 
                                  style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1rem; resize: vertical;"
                                  placeholder="Describe the damage in detail (e.g., how it happened, condition of items, etc.)"></textarea>
                    </div>

                    <!-- Transfer to Returned Stock Option -->
                    <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 1rem;">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" name="auto_transfer" value="1" id="quick_auto_transfer" style="width: 18px; height: 18px;">
                            <div>
                                <strong style="color: #1e40af;">Also transfer to Returned Stock</strong>
                                <div style="font-size: 0.75rem; color: #3b82f6;">Automatically add to Returned Stock page for write-off processing</div>
                            </div>
                        </label>
                    </div>
                    
                    <!-- Summary Box -->
                    <div id="quick_summary" style="background: linear-gradient(135deg, #fef3c7, #fde68a); border: 1px solid #f59e0b; border-radius: 8px; padding: 1rem; display: none;">
                        <h4 style="margin: 0 0 0.5rem 0; color: #92400e; display: flex; align-items: center; gap: 8px;">
                            <ion-icon name="document-text-outline"></ion-icon> Summary of Changes
                        </h4>
                        <ul style="margin: 0; padding-left: 1.25rem; color: #78350f; font-size: 0.875rem;">
                            <li>Stock will change: <strong><span id="summary_stock_change"></span></strong></li>
                            <li>Loss value: <strong>RWF <span id="summary_loss"></span></strong></li>
                            <li>Reason: <strong><span id="summary_reason">-</span></strong></li>
                        </ul>
                    </div>
                    
                    <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 1rem;">
                        <div style="display: flex; align-items: center; gap: 8px; color: #991b1b;">
                            <ion-icon name="warning" style="font-size: 1.25rem;"></ion-icon>
                            <strong>This action will update your inventory</strong>
                        </div>
                        <p style="margin: 0.5rem 0 0 0; color: #7f1d1d; font-size: 0.875rem;">
                            The damaged quantity will be immediately subtracted from your stock and recorded in Damage Control.
                        </p>
                    </div>
                    
                    <div style="display: flex; gap: 1rem;">
                        <button type="button" onclick="closeQuickDamageModal()"
                                style="flex: 1; padding: 14px 24px; background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 1rem;">
                            Cancel
                        </button>
                        <button type="submit" name="report_damage" 
                                style="flex: 1; padding: 14px 24px; background: #dc2626; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 1rem; display: flex; align-items: center; justify-content: center; gap: 8px;">
                            <ion-icon name="checkmark-circle-outline"></ion-icon>
                            Confirm & Update Inventory
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Quick Damage Modal Functions
        var quickPrice = 0;
        var quickStock = 0;
        
        function openQuickDamageModal(toolId, toolName, stock, price) {
            document.getElementById('quick_tool_id').value = toolId;
            document.getElementById('quick_product_name').textContent = toolName;
            document.getElementById('quick_available_stock').textContent = stock.toLocaleString();
            document.getElementById('quick_unit_price').textContent = price.toLocaleString();
            document.getElementById('quick_quantity').max = stock;
            document.getElementById('quick_quantity').value = '';
            document.getElementById('quick_reason').value = '';
            document.getElementById('quick_notes').value = '';
            document.getElementById('quick_auto_transfer').checked = false;
            document.getElementById('quick_loss_preview').style.display = 'none';
            document.getElementById('quick_new_stock').textContent = '-';
            document.getElementById('quick_summary').style.display = 'none';
            quickPrice = price;
            quickStock = stock;
            document.getElementById('quickDamageModal').style.display = 'block';
        }
        
        function closeQuickDamageModal() {
            document.getElementById('quickDamageModal').style.display = 'none';
        }
        
        function calculateQuickLoss() {
            const quantity = parseInt(document.getElementById('quick_quantity').value) || 0;
            const lossPreview = document.getElementById('quick_loss_preview');
            const summaryBox = document.getElementById('quick_summary');
            const reason = document.getElementById('quick_reason').value;
            
            if (quantity > 0) {
                const loss = quantity * quickPrice;
                const newStock = quickStock - quantity;
                
                // Update loss preview
                document.getElementById('quick_loss_amount').textContent = loss.toLocaleString();
                lossPreview.style.display = 'block';
                
                // Update new stock preview
                document.getElementById('quick_new_stock').textContent = newStock.toLocaleString();
                document.getElementById('quick_new_stock').style.color = newStock > 0 ? '#059669' : '#dc2626';
                
                // Update summary
                document.getElementById('summary_stock_change').textContent = quickStock.toLocaleString() + ' → ' + newStock.toLocaleString();
                document.getElementById('summary_loss').textContent = loss.toLocaleString();
                document.getElementById('summary_reason').textContent = reason || '-';
                summaryBox.style.display = 'block';
            } else {
                lossPreview.style.display = 'none';
                document.getElementById('quick_new_stock').textContent = '-';
                summaryBox.style.display = 'none';
            }
        }
        
        // Update summary when reason changes
        document.getElementById('quick_reason').addEventListener('change', function() {
            document.getElementById('summary_reason').textContent = this.value || '-';
            calculateQuickLoss();
        });
        
        // Form validation
        function validateQuickDamageForm() {
            const quantity = parseInt(document.getElementById('quick_quantity').value) || 0;
            const reason = document.getElementById('quick_reason').value;
            
            if (quantity <= 0) {
                alert('Please enter a valid quantity greater than 0');
                return false;
            }
            
            if (quantity > quickStock) {
                alert('Error: Quantity (' + quantity + ') exceeds available stock (' + quickStock + ')');
                return false;
            }
            
            if (!reason) {
                alert('Please select a damage reason');
                return false;
            }
            
            return confirm('Are you sure you want to report ' + quantity + ' item(s) as damaged?\n\nThis will:\n- Subtract ' + quantity + ' from inventory\n- Record the damage with reason: ' + reason + '\n- Loss value: RWF ' + (quantity * quickPrice).toLocaleString());
        }
        
        // Inventory table search filter
        function filterInventoryTable() {
            const input = document.getElementById('inventorySearch');
            const filter = input.value.toLowerCase();
            const table = document.getElementById('inventoryTable');
            const rows = table.getElementsByClassName('inventory-row');
            
            for (let i = 0; i < rows.length; i++) {
                const productName = rows[i].getElementsByTagName('td')[1].textContent.toLowerCase();
                if (productName.indexOf(filter) > -1) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
                }
            }
        }
        
        // Update window.onclick to include quickDamageModal
        var originalWindowClick = window.onclick;
        window.onclick = function(event) {
            const damageModal = document.getElementById('damageModal');
            const editModal = document.getElementById('editModal');
            const quickModal = document.getElementById('quickDamageModal');
            
            if (event.target == damageModal) {
                damageModal.style.display = 'none';
            }
            if (event.target == editModal) {
                editModal.style.display = 'none';
            }
            if (event.target == quickModal) {
                quickModal.style.display = 'none';
            }
        }
    </script>
</body>
</html>