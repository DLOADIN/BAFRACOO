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

// Handle inventory method change
if(isset($_POST['update_method'])){
    $tool_id = (int)$_POST['tool_id'];
    $method = $_POST['method'];
    $result = $inventoryManager->setInventoryMethod($tool_id, $method);
    $message = $result ? "Inventory method updated successfully!" : "Error updating inventory method.";
}

// Handle adding stock batch
if(isset($_POST['add_batch'])){
    $tool_id = (int)$_POST['tool_id'];
    $quantity = (int)$_POST['quantity'];
    $purchase_price = (float)$_POST['purchase_price'];
    $location_id = isset($_POST['location_id']) ? (int)$_POST['location_id'] : 1; // Default to Kigali Central
    $supplier = $_POST['supplier'];
    $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
    
    $result = $inventoryManager->addStockBatch($tool_id, $quantity, $purchase_price, $location_id, $supplier, $expiry_date);
    $batch_message = $result['message'];
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
    <script src="https://kit.fontawesome.com/14ff3ea278.js" crossorigin="anonymous"></script>
    <title>BAFRACOO - Inventory Management</title>
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
                            <a href="stock.php" class="nav-link">
                                <ion-icon name="cube-outline" class="nav-icon"></ion-icon>
                                <span class="nav-text">Tools</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="inventory-management.php" class="nav-link active">
                                <ion-icon name="layers-outline" class="nav-icon"></ion-icon>
                                <span class="nav-text">Inventory Management</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="orders.php" class="nav-link">
                                <ion-icon name="bag-handle-outline" class="nav-icon"></ion-icon>
                                <span class="nav-text">Orders</span>
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
                    <h1 class="page-title">Inventory Management (FIFO/LIFO)</h1>
                </div>
                <div class="header-right">
                    <a href="logout.php" class="logout-btn">
                        <ion-icon name="log-out-outline"></ion-icon>
                        <span>Logout</span>
                    </a>
                </div>
            </header>
            
            <div class="content-wrapper">
                <!-- Messages -->
                <?php if(isset($message)): ?>
                <div style="padding: var(--spacing-md); margin-bottom: var(--spacing-lg); background: #dcfce7; border: 1px solid #16a34a; border-radius: var(--radius-md); color: #15803d;">
                    <?php echo $message; ?>
                </div>
                <?php endif; ?>
                
                <?php if(isset($batch_message)): ?>
                <div style="padding: var(--spacing-md); margin-bottom: var(--spacing-lg); background: #dcfce7; border: 1px solid #16a34a; border-radius: var(--radius-md); color: #15803d;">
                    <?php echo $batch_message; ?>
                </div>
                <?php endif; ?>

                <!-- Tools with Inventory Methods -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>Tools & Inventory Methods</h3>
                        <p>Manage FIFO/LIFO methods and stock levels for each tool</p>
                    </div>
                    
                    <div class="table-container">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Tool ID</th>
                                    <th>Tool Name</th>
                                    <th>Current Stock</th>
                                    <th>Inventory Method</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $tools_sql = mysqli_query($con, "SELECT * FROM tool");
                                while($tool = mysqli_fetch_array($tools_sql)):
                                    $available_stock = $inventoryManager->getAvailableStock($tool['id']);
                                    $current_method = $inventoryManager->getInventoryMethod($tool['id']);
                                ?>
                                <tr>
                                    <td><?php echo $tool['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($tool['u_toolname']); ?></strong></td>
                                    <td>
                                        <span style="padding: 4px 10px; background: <?php echo $available_stock > 0 ? '#10b981' : '#ef4444'; ?>; color: white; border-radius: 6px; font-weight: 600;">
                                            <?php echo $available_stock; ?> units
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="tool_id" value="<?php echo $tool['id']; ?>">
                                            <select name="method" onchange="this.form.submit();" style="padding: 4px 8px; border: 1px solid #ccc; border-radius: 4px;">
                                                <option value="FIFO" <?php echo $current_method === 'FIFO' ? 'selected' : ''; ?>>FIFO (First In, First Out)</option>
                                                <option value="LIFO" <?php echo $current_method === 'LIFO' ? 'selected' : ''; ?>>LIFO (Last In, First Out)</option>
                                            </select>
                                            <input type="hidden" name="update_method" value="1">
                                        </form>
                                    </td>
                                    <td>
                                        <button onclick="showAddBatchModal(<?php echo $tool['id']; ?>, '<?php echo htmlspecialchars($tool['u_toolname']); ?>')" 
                                                style="padding: 6px 12px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.875rem;">
                                            <ion-icon name="add-outline"></ion-icon> Add Stock
                                        </button>
                                        <button onclick="showBatchDetails(<?php echo $tool['id']; ?>)" 
                                                style="padding: 6px 12px; background: #6b7280; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.875rem; margin-left: 4px;">
                                            <ion-icon name="list-outline"></ion-icon> View Batches
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Add Stock Batch Modal -->
                <div id="addBatchModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; width: 90%; max-width: 500px;">
                        <h3 style="margin: 0 0 1rem 0;">Add Stock Batch</h3>
                        <form method="POST" id="addBatchForm">
                            <input type="hidden" name="tool_id" id="modal_tool_id">
                            
                            <div style="margin-bottom: 1rem;">
                                <label>Tool: <strong id="modal_tool_name"></strong></label>
                            </div>
                            
                            <div style="margin-bottom: 1rem;">
                                <label>Location *</label>
                                <select name="location_id" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                                    <?php 
                                    $locations = $inventoryManager->getAllLocations();
                                    while($location = mysqli_fetch_array($locations)): ?>
                                        <option value="<?php echo $location['id']; ?>">
                                            <?php echo htmlspecialchars($location['location_name']); ?> 
                                            (<?php echo $location['location_type']; ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div style="margin-bottom: 1rem;">
                                <label>Quantity *</label>
                                <input type="number" name="quantity" min="1" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                            </div>
                            
                            <div style="margin-bottom: 1rem;">
                                <label>Purchase Price (per unit) *</label>
                                <input type="number" name="purchase_price" step="0.01" min="0" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                            </div>
                            
                            <div style="margin-bottom: 1rem;">
                                <label>Supplier</label>
                                <input type="text" name="supplier" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                            </div>
                            
                            <div style="margin-bottom: 1rem;">
                                <label>Expiry Date (optional)</label>
                                <input type="date" name="expiry_date" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                            </div>
                            
                            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                                <button type="button" onclick="hideAddBatchModal()" style="padding: 8px 16px; background: #6b7280; color: white; border: none; border-radius: 4px; cursor: pointer;">Cancel</button>
                                <button type="submit" name="add_batch" style="padding: 8px 16px; background: #16a34a; color: white; border: none; border-radius: 4px; cursor: pointer;">Add Stock</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Batch Details Modal -->
                <div id="batchDetailsModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; width: 90%; max-width: 800px; max-height: 80%; overflow-y: auto;">
                        <h3 style="margin: 0 0 1rem 0;">Stock Batches</h3>
                        <div id="batchDetailsContent">
                            <!-- Content will be loaded here -->
                        </div>
                        <button onclick="hideBatchDetailsModal()" style="margin-top: 1rem; padding: 8px 16px; background: #6b7280; color: white; border: none; border-radius: 4px; cursor: pointer;">Close</button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    
    <script>
        function showAddBatchModal(toolId, toolName) {
            document.getElementById('modal_tool_id').value = toolId;
            document.getElementById('modal_tool_name').textContent = toolName;
            document.getElementById('addBatchModal').style.display = 'block';
        }

        function hideAddBatchModal() {
            document.getElementById('addBatchModal').style.display = 'none';
        }

        function showBatchDetails(toolId) {
            // You can implement AJAX call here to load batch details
            fetch('get-batch-details.php?tool_id=' + toolId)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('batchDetailsContent').innerHTML = data;
                    document.getElementById('batchDetailsModal').style.display = 'block';
                })
                .catch(error => {
                    alert('Error loading batch details');
                });
        }

        function hideBatchDetailsModal() {
            document.getElementById('batchDetailsModal').style.display = 'none';
        }
    </script>
</body>
</html>