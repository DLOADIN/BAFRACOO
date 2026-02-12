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

// Handle AJAX request to update inventory method
if(isset($_POST['action']) && $_POST['action'] == 'update_method') {
    header('Content-Type: application/json');
    $tool_id = (int)$_POST['tool_id'];
    $method = mysqli_real_escape_string($con, $_POST['method']);
    
    if($inventoryManager->setInventoryMethod($tool_id, $method)) {
        echo json_encode(['success' => true, 'message' => 'Inventory method updated to ' . $method]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update inventory method']);
    }
    exit();
}

// Handle inventory method change (form submit)
if(isset($_POST['update_method'])){
    $tool_id = (int)$_POST['tool_id'];
    $method = $_POST['method'];
    $result = $inventoryManager->setInventoryMethod($tool_id, $method);
    $message = $result ? "Inventory method updated successfully!" : "Error updating inventory method.";
}

// Handle bulk update
if(isset($_POST['bulk_update'])) {
    $method = mysqli_real_escape_string($con, $_POST['bulk_method']);
    $result = mysqli_query($con, "SELECT id FROM tool");
    $success_count = 0;
    while($tool = mysqli_fetch_assoc($result)) {
        if($inventoryManager->setInventoryMethod($tool['id'], $method)) {
            $success_count++;
        }
    }
    $message = "Updated $success_count tools to $method method.";
}

// Handle adding stock batch
if(isset($_POST['add_batch'])){
    $tool_id = (int)$_POST['tool_id'];
    $quantity = (int)$_POST['quantity'];
    $purchase_price = (float)$_POST['purchase_price'];
    $location_id = isset($_POST['location_id']) ? (int)$_POST['location_id'] : 1;
    $supplier = $_POST['supplier'];
    $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
    
    $result = $inventoryManager->addStockBatch($tool_id, $quantity, $purchase_price, $location_id, $supplier, $expiry_date);
    $batch_message = $result['message'];
}

// Get inventory method statistics
$methodStats = $inventoryManager->getInventoryMethodStats();

$current_page = 'enhanced-inventory';
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
    <title>BAFRACOO - Inventory Management (FIFO/LIFO)</title>
    <style>
        .method-toggle {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px;
            background: var(--gray-100);
            border-radius: 8px;
        }
        .method-btn {
            padding: 8px 14px;
            border: none;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            background: transparent;
            color: var(--gray-600);
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .method-btn.active {
            color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .method-btn.fifo.active {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
        }
        .method-btn.lifo.active {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        }
        .method-btn:hover:not(.active) {
            background: var(--gray-200);
        }
        .explanation-card {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
        }
        .explanation-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 16px;
        }
        .method-explanation {
            padding: 16px;
            border-radius: 10px;
            border: 2px solid;
        }
        .method-explanation.fifo {
            background: rgba(59, 130, 246, 0.1);
            border-color: #3b82f6;
        }
        .method-explanation.lifo {
            background: rgba(139, 92, 246, 0.1);
            border-color: #8b5cf6;
        }
        .method-explanation h4 {
            margin: 0 0 8px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .method-explanation ul {
            margin: 0;
            padding-left: 20px;
            font-size: 0.85rem;
            color: var(--gray-600);
        }
        .bulk-action-bar {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            background: var(--gray-50);
            border-radius: 8px;
            margin-bottom: 16px;
        }
        .batch-priority {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            font-size: 0.7rem;
            font-weight: bold;
            margin-right: 8px;
        }
        .batch-priority.fifo { background: #3b82f6; color: white; }
        .batch-priority.lifo { background: #8b5cf6; color: white; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include 'includes/admin_sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <button class="mobile-menu-btn">
                        <ion-icon name="menu-outline"></ion-icon>
                    </button>
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
                    <ion-icon name="checkmark-circle-outline" style="margin-right: 8px;"></ion-icon>
                    <?php echo $message; ?>
                </div>
                <?php endif; ?>
                
                <?php if(isset($batch_message)): ?>
                <div style="padding: var(--spacing-md); margin-bottom: var(--spacing-lg); background: #dcfce7; border: 1px solid #16a34a; border-radius: var(--radius-md); color: #15803d;">
                    <ion-icon name="checkmark-circle-outline" style="margin-right: 8px;"></ion-icon>
                    <?php echo $batch_message; ?>
                </div>
                <?php endif; ?>

                <!-- FIFO/LIFO Explanation Card -->
                <div class="explanation-card">
                    <h3 style="margin: 0 0 8px 0; color: var(--gray-900);">
                        <ion-icon name="information-circle-outline" style="margin-right: 8px;"></ion-icon>
                        Understanding FIFO & LIFO Inventory Methods
                    </h3>
                    <p style="margin: 0; color: var(--gray-600); font-size: 0.9rem;">
                        Choose the right inventory method for each product to optimize stock management and accounting.
                    </p>
                    
                    <div class="explanation-grid">
                        <div class="method-explanation fifo">
                            <h4 style="color: #3b82f6;">
                                <ion-icon name="arrow-forward-outline"></ion-icon>
                                FIFO - First In, First Out
                            </h4>
                            <ul>
                                <li><strong>Oldest stock is sold first</strong></li>
                                <li>Best for perishable goods (cement, paint, food)</li>
                                <li>Reduces waste from expired products</li>
                                <li>Reflects actual physical flow of goods</li>
                            </ul>
                            <div style="margin-top: 12px; padding: 8px; background: white; border-radius: 6px; font-size: 0.8rem;">
                                <strong>SQL:</strong> <code>ORDER BY batch_date ASC</code>
                            </div>
                        </div>
                        
                        <div class="method-explanation lifo">
                            <h4 style="color: #8b5cf6;">
                                <ion-icon name="arrow-back-outline"></ion-icon>
                                LIFO - Last In, First Out
                            </h4>
                            <ul>
                                <li><strong>Newest stock is sold first</strong></li>
                                <li>Best for non-perishable items (tools, hardware)</li>
                                <li>Easier warehouse management</li>
                                <li>Cost of goods reflects recent prices</li>
                            </ul>
                            <div style="margin-top: 12px; padding: 8px; background: white; border-radius: 6px; font-size: 0.8rem;">
                                <strong>SQL:</strong> <code>ORDER BY batch_date DESC</code>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="dashboard-grid" style="margin-bottom: var(--spacing-xl); grid-template-columns: repeat(3, 1fr);">
                    <div class="dashboard-card">
                        <div class="card-content">
                            <div class="card-icon" style="background: var(--primary-color);">
                                <ion-icon name="cube-outline"></ion-icon>
                            </div>
                            <div class="card-info">
                                <h3 style="margin: 0 0 var(--spacing-sm) 0; color: var(--gray-600); font-size: 0.875rem;">TOTAL PRODUCTS</h3>
                                <div style="font-size: 2rem; font-weight: 700; color: var(--gray-900);">
                                    <?php echo $methodStats['total_tools']; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="dashboard-card">
                        <div class="card-content">
                            <div class="card-icon" style="background: #3b82f6;">
                                <ion-icon name="arrow-forward-outline"></ion-icon>
                            </div>
                            <div class="card-info">
                                <h3 style="margin: 0 0 var(--spacing-sm) 0; color: var(--gray-600); font-size: 0.875rem;">FIFO PRODUCTS</h3>
                                <div style="font-size: 2rem; font-weight: 700; color: #3b82f6;">
                                    <?php echo $methodStats['fifo_count']; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="dashboard-card">
                        <div class="card-content">
                            <div class="card-icon" style="background: #8b5cf6;">
                                <ion-icon name="arrow-back-outline"></ion-icon>
                            </div>
                            <div class="card-info">
                                <h3 style="margin: 0 0 var(--spacing-sm) 0; color: var(--gray-600); font-size: 0.875rem;">LIFO PRODUCTS</h3>
                                <div style="font-size: 2rem; font-weight: 700; color: #8b5cf6;">
                                    <?php echo $methodStats['lifo_count']; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bulk Actions -->
                <div class="bulk-action-bar">
                    <span style="font-weight: 600; color: var(--gray-700);">
                        <ion-icon name="flash-outline" style="margin-right: 4px;"></ion-icon>
                        Bulk Actions:
                    </span>
                    <form method="POST" style="display: flex; gap: 8px; align-items: center;">
                        <select name="bulk_method" style="padding: 8px 12px; border: 1px solid var(--gray-300); border-radius: 6px;">
                            <option value="FIFO">Set All to FIFO</option>
                            <option value="LIFO">Set All to LIFO</option>
                        </select>
                        <button type="submit" name="bulk_update" class="btn-primary" style="padding: 8px 16px; border-radius: 6px;" 
                                onclick="return confirm('Are you sure you want to update all products?')">
                            Apply to All
                        </button>
                    </form>
                </div>

                <!-- Tools with Inventory Methods -->
                <div class="dashboard-card">
                    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                        <div>
                            <h3>
                                <ion-icon name="list-outline" style="margin-right: 8px;"></ion-icon>
                                Tools & Inventory Methods
                            </h3>
                            <p style="margin: 4px 0 0 0; color: var(--gray-500); font-size: 0.875rem;">Manage FIFO/LIFO methods and stock levels for each tool</p>
                        </div>
                        <button onclick="exportInventoryManagementPDF()" style="padding: 8px 16px; border-radius: 8px; background: #3b82f6; color: white; border: none; cursor: pointer; display: flex; align-items: center; gap: 6px; font-weight: 600;">
                            <ion-icon name="download-outline"></ion-icon>
                            Export PDF
                        </button>
                    </div>
                    
                    <div class="table-container">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Tool ID</th>
                                    <th>Tool Name</th>
                                    <th>Current Stock</th>
                                    <th>Inventory Method</th>
                                    <th>Next Batch to Sell</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $tools_sql = mysqli_query($con, "SELECT * FROM tool");
                                while($tool = mysqli_fetch_array($tools_sql)):
                                    // Try to get stock from batches first, fall back to tool table
                                    $batch_stock = $inventoryManager->getAvailableStock($tool['id']);
                                    $available_stock = $batch_stock > 0 ? $batch_stock : $tool['u_itemsnumber'];
                                    $current_method = $inventoryManager->getInventoryMethod($tool['id']);
                                    $next_batch = $inventoryManager->getNextBatchToSell($tool['id']);
                                ?>
                                <tr>
                                    <td><?php echo $tool['id']; ?></td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <?php if(!empty($tool['image_url']) && file_exists($tool['image_url'])): ?>
                                            <img src="<?php echo htmlspecialchars($tool['image_url']); ?>" alt="" style="width: 40px; height: 40px; border-radius: 6px; object-fit: cover;">
                                            <?php else: ?>
                                            <div style="width: 40px; height: 40px; border-radius: 6px; background: var(--primary-light); display: flex; align-items: center; justify-content: center; color: var(--primary-color);">
                                                <ion-icon name="cube-outline"></ion-icon>
                                            </div>
                                            <?php endif; ?>
                                            <strong><?php echo htmlspecialchars($tool['u_toolname']); ?></strong>
                                        </div>
                                    </td>
                                    <td>
                                        <span style="padding: 4px 10px; background: <?php echo $available_stock > 0 ? '#10b981' : '#ef4444'; ?>; color: white; border-radius: 6px; font-weight: 600;">
                                            <?php echo number_format($available_stock); ?> units
                                        </span>
                                    </td>
                                    <td>
                                        <div class="method-toggle" data-tool-id="<?php echo $tool['id']; ?>">
                                            <button class="method-btn fifo <?php echo $current_method === 'FIFO' ? 'active' : ''; ?>" 
                                                    onclick="updateMethod(<?php echo $tool['id']; ?>, 'FIFO', this)">
                                                <ion-icon name="arrow-forward-outline"></ion-icon> FIFO
                                            </button>
                                            <button class="method-btn lifo <?php echo $current_method === 'LIFO' ? 'active' : ''; ?>"
                                                    onclick="updateMethod(<?php echo $tool['id']; ?>, 'LIFO', this)">
                                                <ion-icon name="arrow-back-outline"></ion-icon> LIFO
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if($next_batch): ?>
                                        <div style="font-size: 0.8rem;">
                                            <div style="display: flex; align-items: center;">
                                                <span class="batch-priority <?php echo strtolower($current_method); ?>">1</span>
                                                <strong><?php echo htmlspecialchars($next_batch['batch_number']); ?></strong>
                                            </div>
                                            <div style="color: var(--gray-500); margin-top: 2px;">
                                                <?php echo number_format($next_batch['quantity_remaining']); ?> units • 
                                                <?php echo date('M d, Y', strtotime($next_batch['batch_date'])); ?>
                                            </div>
                                        </div>
                                        <?php else: ?>
                                        <span style="color: var(--gray-400); font-style: italic; font-size: 0.85rem;">No batches</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button onclick="showAddBatchModal(<?php echo $tool['id']; ?>, '<?php echo htmlspecialchars($tool['u_toolname']); ?>')" 
                                                style="padding: 6px 12px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.8rem;">
                                            <ion-icon name="add-outline"></ion-icon> Add Stock
                                        </button>
                                        <button onclick="showBatchDetails(<?php echo $tool['id']; ?>)" 
                                                style="padding: 6px 12px; background: #6b7280; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.8rem; margin-left: 4px;">
                                            <ion-icon name="list-outline"></ion-icon> Batches
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
        function exportInventoryManagementPDF() {
            window.open('export_pdf.php?type=inventory_management', '_blank');
        }
        
        // Update inventory method via AJAX
        function updateMethod(toolId, method, btn) {
            const toggle = btn.closest('.method-toggle');
            const buttons = toggle.querySelectorAll('.method-btn');
            const row = btn.closest('tr');
            
            // Disable buttons during update
            buttons.forEach(b => b.disabled = true);
            
            fetch('inventory-management.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update_method&tool_id=${toolId}&method=${method}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update button states
                    buttons.forEach(b => {
                        b.classList.remove('active');
                        if ((b.classList.contains('fifo') && method === 'FIFO') ||
                            (b.classList.contains('lifo') && method === 'LIFO')) {
                            b.classList.add('active');
                        }
                    });
                    
                    // Update batch priority indicator
                    const batchPriority = row.querySelector('.batch-priority');
                    if (batchPriority) {
                        batchPriority.className = 'batch-priority ' + method.toLowerCase();
                    }
                    
                    showToast(data.message, 'success');
                    
                    // Reload page after short delay to update next batch info
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error updating inventory method', 'error');
            })
            .finally(() => {
                buttons.forEach(b => b.disabled = false);
            });
        }
        
        // Toast notification
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                padding: 12px 24px;
                background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
                color: white;
                border-radius: 8px;
                font-weight: 500;
                z-index: 9999;
                animation: slideIn 0.3s ease;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            `;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        function showAddBatchModal(toolId, toolName) {
            document.getElementById('modal_tool_id').value = toolId;
            document.getElementById('modal_tool_name').textContent = toolName;
            document.getElementById('addBatchModal').style.display = 'block';
        }

        function hideAddBatchModal() {
            document.getElementById('addBatchModal').style.display = 'none';
        }

        function showBatchDetails(toolId) {
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
    
    <style>
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    </style>
</body>
</html>