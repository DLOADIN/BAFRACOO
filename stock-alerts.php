<?php
require "connection.php";
require "EnhancedInventoryManager.php";

if(!empty($_SESSION["id"])){
    $id = $_SESSION["id"];
    $check = mysqli_query($con,"SELECT * FROM `admin` WHERE id=$id ");
    $row = mysqli_fetch_array($check);
} else {
    header('location:loginadmin.php');
}

$inventoryManager = new EnhancedInventoryManager($con);

// Handle alert resolution
if(isset($_POST['resolve_alert'])) {
    $alert_id = (int)$_POST['alert_id'];
    $update_query = "UPDATE stock_alerts SET is_resolved = 1, resolved_by = ?, resolved_date = NOW() WHERE id = ?";
    $stmt = mysqli_prepare($con, $update_query);
    mysqli_stmt_bind_param($stmt, "ii", $id, $alert_id);
    mysqli_stmt_execute($stmt);
    $message = "Alert resolved successfully";
    $message_type = "success";
}

// Handle threshold updates
if(isset($_POST['update_thresholds'])) {
    $tool_id = (int)$_POST['tool_id'];
    $location_id = (int)$_POST['location_id'];
    $minimum_stock = (int)$_POST['minimum_stock'];
    $reorder_level = (int)$_POST['reorder_level'];
    $maximum_stock = (int)$_POST['maximum_stock'];
    
    $threshold_query = "INSERT INTO stock_thresholds (tool_id, location_id, minimum_stock, reorder_level, maximum_stock) 
                       VALUES (?, ?, ?, ?, ?) 
                       ON DUPLICATE KEY UPDATE 
                       minimum_stock = VALUES(minimum_stock), 
                       reorder_level = VALUES(reorder_level), 
                       maximum_stock = VALUES(maximum_stock)";
    $stmt = mysqli_prepare($con, $threshold_query);
    mysqli_stmt_bind_param($stmt, "iiiii", $tool_id, $location_id, $minimum_stock, $reorder_level, $maximum_stock);
    mysqli_stmt_execute($stmt);
    $message = "Stock thresholds updated successfully";
    $message_type = "success";
}

$filter_location = $_GET['location'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="CSS/modern-dashboard.css">
    <link rel="stylesheet" href="CSS/modern-tables.css">
    <link rel="stylesheet" href="CSS/modern-forms.css">
    <title>BAFRACOO - Stock Alerts</title>
    <style>
        .alert-critical { background: linear-gradient(135deg, #dc2626, #ef4444); color: white; }
        .alert-warning { background: linear-gradient(135deg, #f59e0b, #fbbf24); color: white; }
        .alert-info { background: linear-gradient(135deg, #3b82f6, #60a5fa); color: white; }
        
        .alert-card {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .alert-icon {
            font-size: 1.5rem;
            margin-right: 0.5rem;
        }
        
        .threshold-form {
            background: #f8fafc;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-logo">
                <img src="images/Captured.JPG" alt="BAFRACOO Logo">
                <span class="logo-text">BAFRACOO</span>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <h3 class="nav-section-title">Main Menu</h3>
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="admindashboard.php" class="nav-link">
                                <ion-icon name="home-outline" class="nav-icon"></ion-icon>
                                <span class="nav-text">Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="addtool.php" class="nav-link">
                                <ion-icon name="add-circle-outline" class="nav-icon"></ion-icon>
                                <span class="nav-text">Add Order</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="addorder.php" class="nav-link">
                                <ion-icon name="construct-outline" class="nav-icon"></ion-icon>
                                <span class="nav-text">Add Tool</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="orders.php" class="nav-link">
                                <ion-icon name="bag-handle-outline" class="nav-icon"></ion-icon>
                                <span class="nav-text">Orders</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="stock.php" class="nav-link">
                                <ion-icon name="cube-outline" class="nav-icon"></ion-icon>
                                <span class="nav-text">Inventory</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="inventory-management.php" class="nav-link">
                                <ion-icon name="library-outline" class="nav-icon"></ion-icon>
                                <span class="nav-text">Enhanced Inventory</span>
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="nav-section">
                    <h3 class="nav-section-title">Enhanced Features</h3>
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="damaged-products.php" class="nav-link">
                                <ion-icon name="warning-outline" class="nav-icon"></ion-icon>
                                <span class="nav-text">Damage Control</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="returns-management.php" class="nav-link">
                                <ion-icon name="return-up-back-outline" class="nav-icon"></ion-icon>
                                <span class="nav-text">Returns</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="stock-alerts.php" class="nav-link active">
                                <ion-icon name="notifications-outline" class="nav-icon"></ion-icon>
                                <span class="nav-text">Stock Alerts</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="system-test.php" class="nav-link">
                                <ion-icon name="flask-outline" class="nav-icon"></ion-icon>
                                <span class="nav-text">System Test</span>
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="nav-section">
                    <h3 class="nav-section-title">Management</h3>
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="transactions.php" class="nav-link">
                                <ion-icon name="analytics-outline" class="nav-icon"></ion-icon>
                                <span class="nav-text">Transactions</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="report.php" class="nav-link">
                                <ion-icon name="document-text-outline" class="nav-icon"></ion-icon>
                                <span class="nav-text">Reports</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="adminprofile.php" class="nav-link">
                                <ion-icon name="person-circle-outline" class="nav-icon"></ion-icon>
                                <span class="nav-text">Profile</span>
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="nav-section">
                    <h3 class="nav-section-title">Website</h3>
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="website.php" class="nav-link">
                                <ion-icon name="globe-outline" class="nav-icon"></ion-icon>
                                <span class="nav-text">Visit Website</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
            
            <div class="sidebar-footer">
                <div class="sidebar-user">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($row['u_name'] ?? 'A', 0, 2)); ?>
                    </div>
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($row['u_name'] ?? 'Admin'); ?></div>
                        <div class="user-role">Administrator</div>
                    </div>
                </div>
            </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <h1 class="page-title">🔔 Stock Alerts & Thresholds</h1>
                </div>
                <div class="header-right">
                    <a href="logout.php" class="logout-btn">
                        <ion-icon name="log-out-outline"></ion-icon>
                        <span>Logout</span>
                    </a>
                </div>
            </header>
            
            <div class="content-wrapper">
                <?php if(isset($message)): ?>
                <div style="padding: 1rem; margin-bottom: 1rem; border-radius: 8px; <?php echo $message_type === 'success' ? 'background: #dcfce7; border: 1px solid #16a34a; color: #15803d;' : 'background: #fef2f2; border: 1px solid #ef4444; color: #dc2626;'; ?>">
                    <?php echo $message; ?>
                </div>
                <?php endif; ?>

                <!-- Filter -->
                <div class="dashboard-card" style="margin-bottom: 2rem;">
                    <form method="GET" style="display: flex; gap: 1rem; align-items: end;">
                        <div style="flex: 1;">
                            <label>Filter by Location:</label>
                            <select name="location" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                                <option value="">All Locations</option>
                                <?php
                                $locations = $inventoryManager->getAllLocations();
                                while($location = mysqli_fetch_array($locations)):
                                ?>
                                <option value="<?php echo $location['id']; ?>" <?php echo $filter_location == $location['id'] ? 'selected' : ''; ?>>
                                    <?php echo $location['location_name']; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div>
                            <button type="submit" style="padding: 8px 16px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer;">
                                <ion-icon name="search-outline"></ion-icon> Filter
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Active Alerts -->
                <div class="dashboard-card" style="margin-bottom: 2rem;">
                    <div class="card-header">
                        <h3>🚨 Active Alerts</h3>
                        <p>Critical and warning notifications that require attention</p>
                    </div>
                    
                    <?php
                    $alerts = $inventoryManager->getActiveAlerts($filter_location);
                    if(mysqli_num_rows($alerts) > 0):
                        while($alert = mysqli_fetch_array($alerts)):
                            $alert_class = 'alert-' . strtolower($alert['alert_level']);
                            $icon = $alert['alert_level'] === 'CRITICAL' ? 'alert-circle' : ($alert['alert_level'] === 'WARNING' ? 'warning' : 'information-circle');
                    ?>
                    <div class="alert-card <?php echo $alert_class; ?>">
                        <div style="display: flex; align-items: center;">
                            <ion-icon name="<?php echo $icon; ?>-outline" class="alert-icon"></ion-icon>
                            <div>
                                <h4 style="margin: 0; font-size: 1.1rem;">
                                    <?php echo $alert['u_toolname']; ?> - <?php echo $alert['location_name']; ?>
                                </h4>
                                <p style="margin: 0.25rem 0 0 0; opacity: 0.9;">
                                    <?php echo $alert['alert_message']; ?>
                                </p>
                                <small style="opacity: 0.8;">
                                    Alert created: <?php echo date('M d, Y H:i', strtotime($alert['created_at'])); ?>
                                </small>
                            </div>
                        </div>
                        <div>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="alert_id" value="<?php echo $alert['id']; ?>">
                                <button type="submit" name="resolve_alert" style="padding: 6px 12px; background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3); border-radius: 4px; cursor: pointer;">
                                    <ion-icon name="checkmark-outline"></ion-icon> Resolve
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php 
                        endwhile;
                    else:
                    ?>
                    <div style="text-align: center; padding: 2rem; color: #6b7280;">
                        <ion-icon name="checkmark-circle-outline" style="font-size: 3rem; color: #10b981;"></ion-icon>
                        <p style="margin: 1rem 0 0 0;">No active alerts. All stock levels are within normal ranges!</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Stock Thresholds Management -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>📊 Stock Threshold Management</h3>
                        <p>Set minimum, reorder, and maximum stock levels for each product and location</p>
                    </div>
                    
                    <div class="table-container">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Location</th>
                                    <th>Current Stock</th>
                                    <th>Minimum Stock</th>
                                    <th>Reorder Level</th>
                                    <th>Maximum Stock</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $threshold_query = "SELECT st.*, t.u_toolname, l.location_name, t.id as tool_id, l.id as location_id
                                                  FROM stock_thresholds st
                                                  JOIN tool t ON st.tool_id = t.id
                                                  JOIN locations l ON st.location_id = l.id
                                                  " . ($filter_location ? "WHERE l.id = $filter_location" : "") . "
                                                  ORDER BY t.u_toolname, l.location_name";
                                $thresholds = mysqli_query($con, $threshold_query);
                                
                                if(mysqli_num_rows($thresholds) > 0):
                                    while($threshold = mysqli_fetch_array($thresholds)):
                                        $current_stock = $inventoryManager->getAvailableStock($threshold['tool_id'], $threshold['location_id']);
                                        $status_color = $current_stock <= $threshold['minimum_stock'] ? '#dc2626' : 
                                                       ($current_stock <= $threshold['reorder_level'] ? '#f59e0b' : '#10b981');
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($threshold['u_toolname']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($threshold['location_name']); ?></td>
                                    <td>
                                        <span style="color: <?php echo $status_color; ?>; font-weight: 600;">
                                            <?php echo $current_stock; ?> units
                                        </span>
                                    </td>
                                    <td><?php echo $threshold['minimum_stock']; ?></td>
                                    <td><?php echo $threshold['reorder_level']; ?></td>
                                    <td><?php echo $threshold['maximum_stock']; ?></td>
                                    <td>
                                        <button onclick="editThreshold(<?php echo $threshold['tool_id']; ?>, <?php echo $threshold['location_id']; ?>, <?php echo $threshold['minimum_stock']; ?>, <?php echo $threshold['reorder_level']; ?>, <?php echo $threshold['maximum_stock']; ?>)" 
                                                style="padding: 4px 8px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.875rem;">
                                            <ion-icon name="create-outline"></ion-icon> Edit
                                        </button>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 2rem; color: #6b7280;">
                                        No thresholds configured. Configure thresholds to enable stock alerts.
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Add New Threshold Form -->
                <div class="dashboard-card" style="margin-top: 2rem;">
                    <div class="card-header">
                        <h3>➕ Configure New Threshold</h3>
                    </div>
                    
                    <form method="POST" class="threshold-form">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <div>
                                <label>Product:</label>
                                <select name="tool_id" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                                    <option value="">Select Product</option>
                                    <?php
                                    $tools_query = "SELECT * FROM tool ORDER BY u_toolname";
                                    $tools = mysqli_query($con, $tools_query);
                                    while($tool = mysqli_fetch_array($tools)):
                                    ?>
                                    <option value="<?php echo $tool['id']; ?>">
                                        <?php echo htmlspecialchars($tool['u_toolname']); ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div>
                                <label>Location:</label>
                                <select name="location_id" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                                    <option value="">Select Location</option>
                                    <?php
                                    $locations = $inventoryManager->getAllLocations();
                                    while($location = mysqli_fetch_array($locations)):
                                    ?>
                                    <option value="<?php echo $location['id']; ?>">
                                        <?php echo htmlspecialchars($location['location_name']); ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div>
                                <label>Minimum Stock:</label>
                                <input type="number" name="minimum_stock" min="0" value="10" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                            </div>
                            <div>
                                <label>Reorder Level:</label>
                                <input type="number" name="reorder_level" min="0" value="50" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                            </div>
                            <div>
                                <label>Maximum Stock:</label>
                                <input type="number" name="maximum_stock" min="0" value="1000" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                            </div>
                            <div style="display: flex; align-items: end;">
                                <button type="submit" name="update_thresholds" style="padding: 8px 16px; background: #16a34a; color: white; border: none; border-radius: 4px; cursor: pointer; width: 100%;">
                                    <ion-icon name="save-outline"></ion-icon> Save Threshold
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <!-- Edit Threshold Modal -->
    <div id="editModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
        <div style="background-color: white; margin: 10% auto; padding: 2rem; border-radius: 12px; width: 80%; max-width: 500px;">
            <h3>Edit Stock Threshold</h3>
            <form method="POST" id="editForm">
                <input type="hidden" name="tool_id" id="edit_tool_id">
                <input type="hidden" name="location_id" id="edit_location_id">
                
                <div style="display: grid; gap: 1rem;">
                    <div>
                        <label>Minimum Stock:</label>
                        <input type="number" name="minimum_stock" id="edit_minimum_stock" min="0" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                    <div>
                        <label>Reorder Level:</label>
                        <input type="number" name="reorder_level" id="edit_reorder_level" min="0" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                    <div>
                        <label>Maximum Stock:</label>
                        <input type="number" name="maximum_stock" id="edit_maximum_stock" min="0" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                </div>
                
                <div style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" onclick="closeEditModal()" style="padding: 10px 20px; background: #6b7280; color: white; border: none; border-radius: 6px; cursor: pointer;">
                        Cancel
                    </button>
                    <button type="submit" name="update_thresholds" style="padding: 10px 20px; background: #16a34a; color: white; border: none; border-radius: 6px; cursor: pointer;">
                        <ion-icon name="save-outline"></ion-icon> Update
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    
    <script>
        function editThreshold(toolId, locationId, minStock, reorderLevel, maxStock) {
            document.getElementById('edit_tool_id').value = toolId;
            document.getElementById('edit_location_id').value = locationId;
            document.getElementById('edit_minimum_stock').value = minStock;
            document.getElementById('edit_reorder_level').value = reorderLevel;
            document.getElementById('edit_maximum_stock').value = maxStock;
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        // Auto-refresh alerts every 30 seconds
        setInterval(function() {
            // You can add AJAX refresh here if needed
        }, 30000);
    </script>
</body>
</html>