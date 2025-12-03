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

// Handle return processing
if(isset($_POST['process_return'])) {
    $return_id = (int)$_POST['return_id'];
    $approved = $_POST['approved'];
    $admin_notes = mysqli_real_escape_string($con, $_POST['admin_notes']);
    
    $result = $inventoryManager->processReturn($return_id, $approved, $admin_notes, $id);
    if($result['success']) {
        $message = $result['message'];
        $message_type = "success";
    } else {
        $message = $result['message'];
        $message_type = "error";
    }
}

// Handle new return initiation
if(isset($_POST['initiate_return'])) {
    $tool_id = (int)$_POST['tool_id'];
    $customer_id = (int)$_POST['customer_id'];
    $quantity = (int)$_POST['quantity'];
    $return_reason = mysqli_real_escape_string($con, $_POST['return_reason']);
    $condition = $_POST['condition'];
    
    $result = $inventoryManager->initiateReturn($tool_id, $customer_id, $quantity, $return_reason, $condition);
    if($result['success']) {
        $message = "Return initiated successfully - Return ID: " . $result['return_id'];
        $message_type = "success";
    } else {
        $message = $result['message'];
        $message_type = "error";
    }
}

$filter_status = $_GET['status'] ?? '';
$filter_condition = $_GET['condition'] ?? '';
$current_page = 'returns';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="CSS/modern-dashboard.css">
    <link rel="stylesheet" href="CSS/modern-tables.css">
    <link rel="stylesheet" href="CSS/modern-forms.css">
    <title>BAFRACOO - Returns Management</title>
    <style>
        .return-status-pending { background: #fef3c7; color: #92400e; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; }
        .return-status-approved { background: #dcfce7; color: #15803d; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; }
        .return-status-rejected { background: #fecaca; color: #dc2626; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; }
        
        .condition-like-new { background: #d1fae5; color: #065f46; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; }
        .condition-minor-wear { background: #fed7aa; color: #9a3412; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; }
        .condition-damaged { background: #fecaca; color: #991b1b; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; }
        
        .refund-amount { font-weight: 700; color: #059669; }
        
        .return-summary-card {
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
            border: 1px solid #0ea5e9;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .summary-item {
            text-align: center;
            padding: 1rem;
            background: white;
            border-radius: 6px;
        }
        
        .summary-number {
            font-size: 2rem;
            font-weight: 700;
            color: #0369a1;
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
            max-height: 80vh;
            overflow-y: auto;
        }
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
                    <h1 class="page-title">↩️ Returns Management</h1>
                </div>
                <div class="header-right">
                    <button onclick="openNewReturnModal()" style="padding: 8px 16px; background: #16a34a; color: white; border: none; border-radius: 6px; cursor: pointer; margin-right: 1rem;">
                        <ion-icon name="add-outline"></ion-icon> New Return
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

                <!-- Returns Summary -->
                <div class="return-summary-card">
                    <h3 style="margin-top: 0; color: #0369a1;">📊 Returns Overview</h3>
                    
                    <?php
                    $summary = $inventoryManager->getReturnsSummary();
                    ?>
                    
                    <div class="summary-grid">
                        <div class="summary-item">
                            <div class="summary-number"><?php echo $summary['total_returns']; ?></div>
                            <div style="color: #6b7280; font-weight: 500;">Total Returns</div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-number" style="color: #f59e0b;"><?php echo $summary['pending_returns']; ?></div>
                            <div style="color: #6b7280; font-weight: 500;">Pending Review</div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-number" style="color: #10b981;"><?php echo $summary['approved_returns']; ?></div>
                            <div style="color: #6b7280; font-weight: 500;">Approved</div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-number" style="color: #dc2626;"><?php echo $summary['rejected_returns']; ?></div>
                            <div style="color: #6b7280; font-weight: 500;">Rejected</div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-number" style="color: #059669;">$<?php echo number_format($summary['total_refund_amount'], 2); ?></div>
                            <div style="color: #6b7280; font-weight: 500;">Total Refunded</div>
                        </div>
                    </div>
                </div>

                <!-- Filter -->
                <div class="dashboard-card" style="margin-bottom: 2rem;">
                    <form method="GET" style="display: flex; gap: 1rem; align-items: end; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 200px;">
                            <label>Filter by Status:</label>
                            <select name="status" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                                <option value="">All Status</option>
                                <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="approved" <?php echo $filter_status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="rejected" <?php echo $filter_status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </div>
                        <div style="flex: 1; min-width: 200px;">
                            <label>Filter by Condition:</label>
                            <select name="condition" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                                <option value="">All Conditions</option>
                                <option value="like_new" <?php echo $filter_condition === 'like_new' ? 'selected' : ''; ?>>Like New</option>
                                <option value="minor_wear" <?php echo $filter_condition === 'minor_wear' ? 'selected' : ''; ?>>Minor Wear</option>
                                <option value="damaged" <?php echo $filter_condition === 'damaged' ? 'selected' : ''; ?>>Damaged</option>
                            </select>
                        </div>
                        <div>
                            <button type="submit" style="padding: 8px 16px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer;">
                                <ion-icon name="search-outline"></ion-icon> Filter
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Returns Table -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>📦 Return Requests</h3>
                        <p>Manage customer return requests and process refunds</p>
                    </div>
                    
                    <div class="table-container">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Return ID</th>
                                    <th>Customer</th>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Condition</th>
                                    <th>Reason</th>
                                    <th>Refund Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $returns = $inventoryManager->getReturns($filter_status, $filter_condition);
                                if($returns && mysqli_num_rows($returns) > 0):
                                    while($return = mysqli_fetch_array($returns)):
                                ?>
                                <tr>
                                    <td><strong>#<?php echo str_pad($return['id'], 4, '0', STR_PAD_LEFT); ?></strong></td>
                                    <td>
                                        <?php echo htmlspecialchars($return['customer_name']); ?><br>
                                        <small style="color: #6b7280;"><?php echo htmlspecialchars($return['customer_email']); ?></small>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($return['u_toolname']); ?></strong></td>
                                    <td><?php echo $return['quantity']; ?> units</td>
                                    <td>
                                        <span class="condition-<?php echo str_replace('_', '-', strtolower($return['condition'])); ?>">
                                            <?php echo ucwords(str_replace('_', ' ', $return['condition'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($return['return_reason']); ?></td>
                                    <td class="refund-amount">$<?php echo number_format($return['refund_amount'], 2); ?></td>
                                    <td>
                                        <span class="return-status-<?php echo strtolower($return['status']); ?>">
                                            <?php echo ucfirst($return['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($return['created_at'])); ?><br>
                                        <small style="color: #6b7280;"><?php echo date('H:i', strtotime($return['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <?php if($return['status'] === 'pending'): ?>
                                        <button onclick="processReturn(<?php echo $return['id']; ?>, '<?php echo addslashes($return['u_toolname']); ?>', <?php echo $return['quantity']; ?>, <?php echo $return['refund_amount']; ?>)" 
                                                style="padding: 4px 8px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.75rem;">
                                            <ion-icon name="checkmark-outline"></ion-icon> Process
                                        </button>
                                        <?php else: ?>
                                        <span style="color: #6b7280; font-size: 0.75rem;">Processed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile;
                                elseif($returns === false):
                                ?>
                                <tr>
                                    <td colspan="10" style="text-align: center; padding: 2rem; color: #dc2626;">
                                        ⚠️ Error loading returns. Please check the database connection.
                                    </td>
                                </tr>
                                <?php else: ?>
                                <tr>
                                    <td colspan="10" style="text-align: center; padding: 2rem; color: #6b7280;">
                                        No returns found. <?php echo $filter_status || $filter_condition ? 'Try adjusting your filters.' : 'Customer returns will appear here.'; ?>
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

    <!-- Process Return Modal -->
    <div id="processModal" class="modal">
        <div class="modal-content">
            <h3>Process Return Request</h3>
            <form method="POST" id="processForm">
                <input type="hidden" name="return_id" id="process_return_id">
                
                <div id="returnDetails" style="background: #f8fafc; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                    <!-- Return details will be populated by JavaScript -->
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Decision:</label>
                    <div style="display: flex; gap: 1rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="radio" name="approved" value="1" required>
                            <span style="color: #059669;">✓ Approve Return</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="radio" name="approved" value="0" required>
                            <span style="color: #dc2626;">✗ Reject Return</span>
                        </label>
                    </div>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Admin Notes:</label>
                    <textarea name="admin_notes" rows="3" placeholder="Add notes about your decision..." style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; resize: vertical;"></textarea>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" onclick="closeProcessModal()" style="padding: 10px 20px; background: #6b7280; color: white; border: none; border-radius: 6px; cursor: pointer;">
                        Cancel
                    </button>
                    <button type="submit" name="process_return" style="padding: 10px 20px; background: #16a34a; color: white; border: none; border-radius: 6px; cursor: pointer;">
                        <ion-icon name="checkmark-outline"></ion-icon> Process Return
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- New Return Modal -->
    <div id="newReturnModal" class="modal">
        <div class="modal-content">
            <h3>Initiate New Return</h3>
            <form method="POST">
                <div style="display: grid; gap: 1rem;">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Customer:</label>
                        <select name="customer_id" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                            <option value="">Select Customer</option>
                            <?php
                            $customers_query = "SELECT * FROM users ORDER BY firstName, lastName";
                            $customers = mysqli_query($con, $customers_query);
                            while($customer = mysqli_fetch_array($customers)):
                            ?>
                            <option value="<?php echo $customer['id']; ?>">
                                <?php echo htmlspecialchars($customer['firstName'] . ' ' . $customer['lastName']); ?> - <?php echo htmlspecialchars($customer['email']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Product:</label>
                        <select name="tool_id" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                            <option value="">Select Product</option>
                            <?php
                            $tools_query = "SELECT * FROM tool ORDER BY u_toolname";
                            $tools = mysqli_query($con, $tools_query);
                            while($tool = mysqli_fetch_array($tools)):
                            ?>
                            <option value="<?php echo $tool['id']; ?>">
                                <?php echo htmlspecialchars($tool['u_toolname']); ?> - $<?php echo $tool['u_price']; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Quantity:</label>
                        <input type="number" name="quantity" min="1" value="1" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Return Reason:</label>
                        <textarea name="return_reason" rows="2" placeholder="Describe the reason for return..." required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; resize: vertical;"></textarea>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Product Condition:</label>
                        <select name="condition" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                            <option value="">Select Condition</option>
                            <option value="like_new">Like New (100% refund)</option>
                            <option value="minor_wear">Minor Wear (90% refund)</option>
                            <option value="damaged">Damaged (50% refund)</option>
                        </select>
                    </div>
                </div>
                
                <div style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" onclick="closeNewReturnModal()" style="padding: 10px 20px; background: #6b7280; color: white; border: none; border-radius: 6px; cursor: pointer;">
                        Cancel
                    </button>
                    <button type="submit" name="initiate_return" style="padding: 10px 20px; background: #16a34a; color: white; border: none; border-radius: 6px; cursor: pointer;">
                        <ion-icon name="add-outline"></ion-icon> Initiate Return
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    
    <script>
        function processReturn(returnId, productName, quantity, refundAmount) {
            document.getElementById('process_return_id').value = returnId;
            document.getElementById('returnDetails').innerHTML = 
                '<h4 style="margin: 0 0 1rem 0;">Return Details</h4>' +
                '<p><strong>Product:</strong> ' + productName + '</p>' +
                '<p><strong>Quantity:</strong> ' + quantity + ' units</p>' +
                '<p><strong>Refund Amount:</strong> $' + refundAmount.toFixed(2) + '</p>';
            document.getElementById('processModal').style.display = 'block';
        }
        
        function closeProcessModal() {
            document.getElementById('processModal').style.display = 'none';
        }
        
        function openNewReturnModal() {
            document.getElementById('newReturnModal').style.display = 'block';
        }
        
        function closeNewReturnModal() {
            document.getElementById('newReturnModal').style.display = 'none';
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const processModal = document.getElementById('processModal');
            const newReturnModal = document.getElementById('newReturnModal');
            if (event.target === processModal) {
                closeProcessModal();
            }
            if (event.target === newReturnModal) {
                closeNewReturnModal();
            }
        }
    </script>
</body>
</html>