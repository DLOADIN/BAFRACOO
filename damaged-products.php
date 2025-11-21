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

// Handle damage reporting
if(isset($_POST['report_damage'])) {
    $batch_id = (int)$_POST['batch_id'];
    $quantity_damaged = (int)$_POST['quantity_damaged'];
    $damage_reason = $_POST['damage_reason'];
    $location_id = (int)$_POST['location_id'];
    $damage_description = $_POST['damage_description'];
    
    $result = $inventoryManager->reportDamage($batch_id, $quantity_damaged, $damage_reason, $location_id, $id, $damage_description);
    $message = $result['message'];
    $message_type = $result['success'] ? 'success' : 'error';
}

// Get filter parameters
$filter_location = $_GET['location'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="CSS/modern-dashboard.css">
    <link rel="stylesheet" href="CSS/modern-tables.css">
    <link rel="stylesheet" href="CSS/modern-forms.css">
    <title>BAFRACOO - Damaged Products Management</title>
    <style>
        .damage-card {
            background: linear-gradient(135deg, #dc2626, #ef4444);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }
        .damage-reason {
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .reason-BROKEN_SHIPPING { background: #ef4444; color: white; }
        .reason-MANUFACTURING_DEFECT { background: #f97316; color: white; }
        .reason-CUSTOMER_RETURN_DAMAGED { background: #eab308; color: white; }
        .reason-WAREHOUSE_ACCIDENT { background: #8b5cf6; color: white; }
        .reason-EXPIRED { background: #6b7280; color: white; }
        .reason-WATER_DAMAGE { background: #06b6d4; color: white; }
        .reason-THEFT_VANDALISM { background: #dc2626; color: white; }
        .reason-QUALITY_CONTROL_FAIL { background: #ec4899; color: white; }
        
        .loss-amount {
            color: #dc2626;
            font-weight: 700;
            font-size: 1.1rem;
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
            width: 80%;
            max-width: 600px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: #000;
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
                            <a href="inventory-management.php" class="nav-link">
                                <ion-icon name="layers-outline" class="nav-icon"></ion-icon>
                                <span class="nav-text">Inventory</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="damaged-products.php" class="nav-link active">
                                <ion-icon name="warning-outline" class="nav-icon"></ion-icon>
                                <span class="nav-text">Damaged Products</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="returns-management.php" class="nav-link">
                                <ion-icon name="return-up-back-outline" class="nav-icon"></ion-icon>
                                <span class="nav-text">Returns</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="stock-alerts.php" class="nav-link">
                                <ion-icon name="notifications-outline" class="nav-icon"></ion-icon>
                                <span class="nav-text">Stock Alerts</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <h1 class="page-title">🚨 Damaged Products Management</h1>
                </div>
                <div class="header-right">
                    <button onclick="openDamageModal()" style="padding: 8px 16px; background: #dc2626; color: white; border: none; border-radius: 6px; cursor: pointer; margin-right: 1rem;">
                        <ion-icon name="warning-outline"></ion-icon> Report Damage
                    </button>
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

                <!-- Summary Card -->
                <div class="damage-card">
                    <h2 style="margin: 0 0 1rem 0; display: flex; align-items: center; gap: 0.5rem;">
                        <ion-icon name="alert-triangle-outline" style="font-size: 2rem;"></ion-icon>
                        Damage Overview
                    </h2>
                    
                    <?php
                    $summary_query = "SELECT 
                                        COUNT(*) as total_incidents,
                                        SUM(quantity_damaged) as total_damaged_items,
                                        SUM(estimated_loss) as total_loss,
                                        COUNT(DISTINCT location_id) as affected_locations
                                      FROM damaged_products 
                                      WHERE damage_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                    $summary = mysqli_fetch_assoc(mysqli_query($con, $summary_query));
                    ?>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <div>
                            <h3 style="margin: 0; font-size: 2rem;"><?php echo $summary['total_incidents'] ?? 0; ?></h3>
                            <p style="margin: 0; opacity: 0.9;">Incidents (Last 30 Days)</p>
                        </div>
                        <div>
                            <h3 style="margin: 0; font-size: 2rem;"><?php echo number_format($summary['total_damaged_items'] ?? 0); ?></h3>
                            <p style="margin: 0; opacity: 0.9;">Items Damaged</p>
                        </div>
                        <div>
                            <h3 style="margin: 0; font-size: 2rem;">RWF <?php echo number_format($summary['total_loss'] ?? 0, 0); ?></h3>
                            <p style="margin: 0; opacity: 0.9;">Estimated Loss</p>
                        </div>
                        <div>
                            <h3 style="margin: 0; font-size: 2rem;"><?php echo $summary['affected_locations'] ?? 0; ?></h3>
                            <p style="margin: 0; opacity: 0.9;">Locations Affected</p>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="dashboard-card" style="margin-bottom: 2rem;">
                    <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
                        <div>
                            <label>Location:</label>
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
                            <label>From Date:</label>
                            <input type="date" name="date_from" value="<?php echo $filter_date_from; ?>" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                        </div>
                        <div>
                            <label>To Date:</label>
                            <input type="date" name="date_to" value="<?php echo $filter_date_to; ?>" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                        </div>
                        <div>
                            <button type="submit" style="padding: 8px 16px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer;">
                                <ion-icon name="search-outline"></ion-icon> Filter
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Damaged Products List -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>Damaged Products Report</h3>
                    </div>
                    
                    <div class="table-container">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Product</th>
                                    <th>Batch</th>
                                    <th>Quantity</th>
                                    <th>Reason</th>
                                    <th>Location</th>
                                    <th>Reported By</th>
                                    <th>Estimated Loss</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $damaged_products = $inventoryManager->getDamagedProducts($filter_location, $filter_date_from, $filter_date_to);
                                if(mysqli_num_rows($damaged_products) > 0):
                                    while($damage = mysqli_fetch_array($damaged_products)):
                                ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($damage['damage_date'])); ?></td>
                                    <td><strong><?php echo htmlspecialchars($damage['u_toolname']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($damage['batch_number']); ?></td>
                                    <td>
                                        <span style="color: #dc2626; font-weight: 600;"><?php echo $damage['quantity_damaged']; ?></span>
                                    </td>
                                    <td>
                                        <span class="damage-reason reason-<?php echo $damage['damage_reason']; ?>">
                                            <?php echo str_replace('_', ' ', $damage['damage_reason']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($damage['location_name']); ?></td>
                                    <td><?php echo htmlspecialchars($damage['reported_by_name']); ?></td>
                                    <td class="loss-amount">RWF <?php echo number_format($damage['estimated_loss'], 0); ?></td>
                                    <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">
                                        <?php echo htmlspecialchars($damage['damage_description'] ?? 'No description'); ?>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 2rem; color: #6b7280;">
                                        <ion-icon name="checkmark-circle-outline" style="font-size: 3rem; color: #10b981;"></ion-icon>
                                        <p style="margin: 1rem 0 0 0;">No damaged products found for the selected criteria.</p>
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
            <h2>🚨 Report Damaged Products</h2>
            
            <form method="POST">
                <div style="display: grid; gap: 1rem;">
                    <div>
                        <label>Stock Batch:</label>
                        <select name="batch_id" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                            <option value="">Select Batch</option>
                            <?php
                            $batches_query = "SELECT sb.*, t.u_toolname, l.location_name 
                                            FROM stock_batches sb 
                                            JOIN tool t ON sb.tool_id = t.id 
                                            JOIN locations l ON sb.location_id = l.id 
                                            WHERE sb.quantity_remaining > 0 
                                            ORDER BY t.u_toolname, sb.batch_date";
                            $batches = mysqli_query($con, $batches_query);
                            while($batch = mysqli_fetch_array($batches)):
                            ?>
                            <option value="<?php echo $batch['id']; ?>">
                                <?php echo $batch['u_toolname']; ?> - <?php echo $batch['batch_number']; ?> 
                                (<?php echo $batch['quantity_remaining']; ?> available at <?php echo $batch['location_name']; ?>)
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label>Quantity Damaged:</label>
                        <input type="number" name="quantity_damaged" min="1" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                    
                    <div>
                        <label>Damage Reason:</label>
                        <select name="damage_reason" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                            <option value="BROKEN_SHIPPING">Broken During Shipping</option>
                            <option value="MANUFACTURING_DEFECT">Manufacturing Defect</option>
                            <option value="CUSTOMER_RETURN_DAMAGED">Customer Return - Damaged</option>
                            <option value="WAREHOUSE_ACCIDENT">Warehouse Accident</option>
                            <option value="EXPIRED">Expired/Outdated</option>
                            <option value="WATER_DAMAGE">Water Damage</option>
                            <option value="THEFT_VANDALISM">Theft/Vandalism</option>
                            <option value="QUALITY_CONTROL_FAIL">Quality Control Failure</option>
                        </select>
                    </div>
                    
                    <div>
                        <label>Location:</label>
                        <select name="location_id" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                            <?php
                            $locations = $inventoryManager->getAllLocations();
                            while($location = mysqli_fetch_array($locations)):
                            ?>
                            <option value="<?php echo $location['id']; ?>">
                                <?php echo $location['location_name']; ?> - <?php echo $location['city']; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label>Description (Optional):</label>
                        <textarea name="damage_description" rows="3" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; resize: vertical;" placeholder="Describe the damage details..."></textarea>
                    </div>
                </div>
                
                <div style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" onclick="closeDamageModal()" style="padding: 10px 20px; background: #6b7280; color: white; border: none; border-radius: 6px; cursor: pointer;">
                        Cancel
                    </button>
                    <button type="submit" name="report_damage" style="padding: 10px 20px; background: #dc2626; color: white; border: none; border-radius: 6px; cursor: pointer;">
                        <ion-icon name="warning-outline"></ion-icon> Report Damage
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
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('damageModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>