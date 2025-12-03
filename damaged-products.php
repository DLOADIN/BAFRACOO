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
    $damage_description = mysqli_real_escape_string($con, $_POST['damage_description']);
    
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
                // Remove items from stock
                $new_quantity = $tool_data['u_itemsnumber'] - $quantity_damaged;
                mysqli_query($con, "UPDATE tool SET u_itemsnumber = $new_quantity WHERE id = $tool_id");
                
                $message = "Damage reported successfully. $quantity_damaged items removed from stock.";
                $message_type = "success";
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
        }
        .btn-danger { background: #fee2e2; color: #dc2626; }
        .btn-danger:hover { background: #fecaca; }
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

                <!-- Info Banner -->
                <div class="dashboard-card" style="background: linear-gradient(135deg, #fef3c7, #fde68a); border: 1px solid #f59e0b; margin-bottom: 2rem;">
                    <div style="padding: 1rem; display: flex; align-items: center; gap: 1rem;">
                        <ion-icon name="information-circle" style="font-size: 2rem; color: #92400e;"></ion-icon>
                        <div>
                            <h4 style="margin: 0; color: #92400e;">How Damage Control Works</h4>
                            <p style="margin: 0.5rem 0 0 0; color: #78350f; font-size: 0.875rem;">
                                When you report damage, items are immediately removed from stock and recorded here. 
                                These items can then be processed in <a href="returned-stock.php" style="color: #dc2626; font-weight: 600;">Returned Stock</a> to be written off permanently.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Damaged Goods Table -->
                <div class="dashboard-card">
                    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; border-bottom: 1px solid #e5e7eb;">
                        <div>
                            <h3 style="margin: 0; font-size: 1.25rem; font-weight: 600;">📋 Damage Records</h3>
                            <p style="margin: 0.25rem 0 0 0; color: #6b7280; font-size: 0.875rem;">All reported damaged goods and stock removals</p>
                        </div>
                        <a href="returned-stock.php" style="display: flex; align-items: center; gap: 6px; color: #3b82f6; text-decoration: none; font-weight: 500;">
                            <ion-icon name="arrow-forward-outline"></ion-icon> Process in Returned Stock
                        </a>
                    </div>
                    <div class="table-container" style="padding: 0;">
                        <table class="modern-table" style="width: 100%;">
                            <thead>
                                <tr>
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
                                $damage_query = mysqli_query($con, "SELECT dg.*, a.u_name as reporter_name FROM damaged_goods dg LEFT JOIN admin a ON dg.reported_by = a.id ORDER BY dg.damage_date DESC");
                                if($damage_query && mysqli_num_rows($damage_query) > 0):
                                    while($damage = mysqli_fetch_assoc($damage_query)):
                                ?>
                                <tr>
                                    <td><span style="background: #f3f4f6; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem;">#<?php echo $damage['id']; ?></span></td>
                                    <td>
                                        <div style="font-weight: 500;"><?php echo date('M d, Y', strtotime($damage['damage_date'])); ?></div>
                                        <div style="font-size: 0.75rem; color: #6b7280;"><?php echo date('h:i A', strtotime($damage['damage_date'])); ?></div>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($damage['tool_name']); ?></strong>
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
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this record? This action cannot be undone.');">
                                            <input type="hidden" name="damage_id" value="<?php echo $damage['id']; ?>">
                                            <button type="submit" name="delete_damage" class="action-btn btn-danger">
                                                <ion-icon name="trash-outline"></ion-icon> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 3rem; color: #6b7280;">
                                        <ion-icon name="checkmark-circle-outline" style="font-size: 4rem; color: #10b981; display: block; margin-bottom: 1rem;"></ion-icon>
                                        <h4 style="margin: 0 0 0.5rem 0;">No Damage Reports</h4>
                                        <p style="margin: 0;">Great news! No damaged goods have been reported yet.</p>
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
    </script>
</body>
</html>