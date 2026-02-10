<?php
require 'connection.php';

if(!empty($_SESSION["id"])){
    $id = $_SESSION["id"];
    $check = mysqli_query($con, "SELECT * FROM `user` WHERE id=$id");
    $row = mysqli_fetch_array($check);
} else {
    header('location:../login.php');
    exit();
}

$success_message = '';
$error_message = '';

// Handle refund request submission
if(isset($_POST['submit_refund'])){
    $order_id = mysqli_real_escape_string($con, $_POST['order_id']);
    $refund_reason = mysqli_real_escape_string($con, $_POST['refund_reason']);
    $reason_details = mysqli_real_escape_string($con, $_POST['reason_details']);
    
    // Get order details
    $order_query = mysqli_query($con, "SELECT * FROM `order` WHERE id = $order_id AND user_id = $id");
    
    if($order_query && mysqli_num_rows($order_query) > 0){
        $order = mysqli_fetch_array($order_query);
        
        // Check if refund already requested for this order
        $existing = mysqli_query($con, "SELECT * FROM `refund_requests` WHERE order_id = $order_id AND status NOT IN ('REJECTED', 'CANCELLED')");
        
        if(mysqli_num_rows($existing) > 0){
            $error_message = "A refund request already exists for this order.";
        } else {
            // Calculate refund amount (full refund for now)
            $refund_amount = $order['u_totalprice'];
            
            // Insert refund request
            $insert = mysqli_query($con, "INSERT INTO `refund_requests` 
                (order_id, user_id, tool_name, quantity, order_amount, refund_amount, refund_reason, reason_details, stripe_payment_intent) 
                VALUES (
                    $order_id, 
                    $id, 
                    '{$order['u_toolname']}', 
                    {$order['u_itemsnumber']}, 
                    {$order['u_totalprice']}, 
                    $refund_amount, 
                    '$refund_reason', 
                    '$reason_details',
                    '" . (isset($order['stripe_payment_intent']) ? $order['stripe_payment_intent'] : '') . "'
                )");
            
            if($insert){
                $success_message = "Your refund request has been submitted successfully. We will review it and get back to you within 2-3 business days.";
            } else {
                $error_message = "Failed to submit refund request. Please try again.";
            }
        }
    } else {
        $error_message = "Invalid order selected.";
    }
}

// Handle refund cancellation
if(isset($_POST['cancel_refund'])){
    $refund_id = mysqli_real_escape_string($con, $_POST['refund_id']);
    $update = mysqli_query($con, "UPDATE `refund_requests` SET status = 'CANCELLED' WHERE id = $refund_id AND user_id = $id AND status = 'PENDING'");
    if($update && mysqli_affected_rows($con) > 0){
        $success_message = "Refund request cancelled successfully.";
    } else {
        $error_message = "Cannot cancel this refund request.";
    }
}

// Get user's orders eligible for refund (Paid, Payment Failed, or completed within 30 days)
$eligible_orders = mysqli_query($con, "SELECT * FROM `order` WHERE user_id = $id AND (status IN ('Paid', 'Payment Failed', 'Completed') OR (status = 'Completed' AND u_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY))) ORDER BY u_date DESC");

// Get user's refund requests
$refund_requests = mysqli_query($con, "SELECT rr.*, o.status as order_status FROM `refund_requests` rr LEFT JOIN `order` o ON rr.order_id = o.id WHERE rr.user_id = $id ORDER BY rr.created_at DESC");

$current_page = 'refunds';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../CSS/modern-dashboard.css">
    <link rel="stylesheet" href="../CSS/enhanced-pages.css">
    <link rel="shortcut icon" href="../images/Capture.JPG" type="image/x-icon">
    <script src="https://kit.fontawesome.com/14ff3ea278.js" crossorigin="anonymous"></script>
    <title>BAFRACOO - Refund Requests</title>
    <style>
        .refund-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        @media (max-width: 1200px) {
            .refund-container {
                grid-template-columns: 1fr;
            }
        }
        
        .refund-form-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .refund-form-card h3 {
            font-size: 1.25rem;
            color: #1e293b;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .refund-form-card h3 i {
            color: #f59e0b;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }
        
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.2s;
            font-family: inherit;
        }
        
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .order-select-info {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 8px;
            padding: 12px;
            margin-top: 8px;
            font-size: 0.85rem;
            color: #0369a1;
            display: none;
        }
        
        .order-select-info.show {
            display: block;
        }
        
        .btn-submit-refund {
            width: 100%;
            padding: 14px 24px;
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-submit-refund:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.4);
        }
        
        .requests-list {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .requests-list h3 {
            font-size: 1.25rem;
            color: #1e293b;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .requests-list h3 i {
            color: #2563eb;
        }
        
        .request-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            transition: all 0.2s;
        }
        
        .request-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }
        
        .request-id {
            font-weight: 700;
            color: #1e293b;
        }
        
        .request-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-under_review {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .status-approved {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-rejected {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .status-processed {
            background: #c7d2fe;
            color: #3730a3;
        }
        
        .status-cancelled {
            background: #f3f4f6;
            color: #6b7280;
        }
        
        .request-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            font-size: 0.9rem;
            color: #64748b;
            margin-bottom: 12px;
        }
        
        .request-details span {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .request-amount {
            font-size: 1.25rem;
            font-weight: 700;
            color: #059669;
        }
        
        .request-reason {
            background: white;
            padding: 10px;
            border-radius: 8px;
            font-size: 0.85rem;
            color: #475569;
            margin-bottom: 12px;
        }
        
        .request-reason strong {
            color: #1e293b;
        }
        
        .admin-notes {
            background: #fef3c7;
            border-left: 3px solid #f59e0b;
            padding: 10px 12px;
            border-radius: 0 8px 8px 0;
            font-size: 0.85rem;
            color: #92400e;
            margin-bottom: 12px;
        }
        
        .admin-notes strong {
            display: block;
            margin-bottom: 4px;
        }
        
        .btn-cancel-refund {
            padding: 8px 16px;
            background: #fee2e2;
            color: #991b1b;
            border: none;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-cancel-refund:hover {
            background: #fecaca;
        }
        
        .no-requests {
            text-align: center;
            padding: 40px 20px;
            color: #94a3b8;
        }
        
        .no-requests i {
            font-size: 3rem;
            margin-bottom: 16px;
            color: #cbd5e1;
        }
        
        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        @media (max-width: 900px) {
            .summary-cards {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        .summary-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        }
        
        .summary-card .label {
            font-size: 0.85rem;
            color: #64748b;
            margin-bottom: 8px;
        }
        
        .summary-card .value {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1e293b;
        }
        
        .summary-card.pending .value { color: #f59e0b; }
        .summary-card.approved .value { color: #10b981; }
        .summary-card.processed .value { color: #6366f1; }
        .summary-card.total .value { color: #2563eb; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="../images/Captured.JPG" alt="BAFRACOO" class="sidebar-logo">
                <h2>BAFRACOO</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="userdashboard.php" class="nav-item">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="stock.php" class="nav-item">
                    <i class="fas fa-box"></i>
                    <span>Shop Products</span>
                </a>
                <a href="orders.php" class="nav-item">
                    <i class="fas fa-shopping-bag"></i>
                    <span>My Orders</span>
                </a>
                <a href="transactions.php" class="nav-item">
                    <i class="fas fa-receipt"></i>
                    <span>Transactions</span>
                </a>
                <a href="refund-requests.php" class="nav-item active">
                    <i class="fas fa-undo-alt"></i>
                    <span>Refund Requests</span>
                </a>
                <a href="userprofile.php" class="nav-item">
                    <i class="fas fa-user"></i>
                    <span>My Profile</span>
                </a>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <div class="header-left">
                    <h1>Refund Requests</h1>
                    <p class="breadcrumb">Request and track your refunds</p>
                </div>
                <div class="header-right">
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($row['u_fullname']); ?></span>
                        <span class="user-role">Customer</span>
                    </div>
                </div>
            </header>

            <div class="content-body">
                <?php if(!empty($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success_message; ?>
                </div>
                <?php endif; ?>
                
                <?php if(!empty($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
                <?php endif; ?>

                <!-- Summary Cards -->
                <?php
                $pending_count = mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) as cnt FROM refund_requests WHERE user_id = $id AND status = 'PENDING'"))['cnt'];
                $approved_count = mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) as cnt FROM refund_requests WHERE user_id = $id AND status = 'APPROVED'"))['cnt'];
                $processed_count = mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) as cnt FROM refund_requests WHERE user_id = $id AND status = 'PROCESSED'"))['cnt'];
                $total_refunded = mysqli_fetch_array(mysqli_query($con, "SELECT COALESCE(SUM(refund_amount), 0) as total FROM refund_requests WHERE user_id = $id AND status = 'PROCESSED'"))['total'];
                ?>
                <div class="summary-cards">
                    <div class="summary-card pending">
                        <div class="label">Pending Requests</div>
                        <div class="value"><?php echo $pending_count; ?></div>
                    </div>
                    <div class="summary-card approved">
                        <div class="label">Approved</div>
                        <div class="value"><?php echo $approved_count; ?></div>
                    </div>
                    <div class="summary-card processed">
                        <div class="label">Processed</div>
                        <div class="value"><?php echo $processed_count; ?></div>
                    </div>
                    <div class="summary-card total">
                        <div class="label">Total Refunded</div>
                        <div class="value"><?php echo number_format($total_refunded); ?> RWF</div>
                    </div>
                </div>

                <div class="refund-container">
                    <!-- Request Form -->
                    <div class="refund-form-card">
                        <h3><i class="fas fa-plus-circle"></i> Submit New Refund Request</h3>
                        
                        <form method="POST" action="">
                            <div class="form-group">
                                <label>Select Order</label>
                                <select name="order_id" id="orderSelect" required onchange="showOrderInfo()">
                                    <option value="">-- Select an order --</option>
                                    <?php 
                                    mysqli_data_seek($eligible_orders, 0);
                                    while($order = mysqli_fetch_array($eligible_orders)): 
                                        // Check if already has pending refund
                                        $has_refund = mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) as cnt FROM refund_requests WHERE order_id = {$order['id']} AND status NOT IN ('REJECTED', 'CANCELLED')"))['cnt'] > 0;
                                        if(!$has_refund):
                                    ?>
                                    <option value="<?php echo $order['id']; ?>" 
                                            data-name="<?php echo htmlspecialchars($order['u_toolname']); ?>"
                                            data-qty="<?php echo $order['u_itemsnumber']; ?>"
                                            data-amount="<?php echo $order['u_totalprice']; ?>"
                                            data-status="<?php echo $order['status']; ?>"
                                            data-date="<?php echo $order['u_date']; ?>">
                                        Order #<?php echo $order['id']; ?> - <?php echo htmlspecialchars($order['u_toolname']); ?> (<?php echo number_format($order['u_totalprice']); ?> RWF) - <?php echo $order['status']; ?>
                                    </option>
                                    <?php endif; endwhile; ?>
                                </select>
                                <div class="order-select-info" id="orderInfo">
                                    <strong id="infoName"></strong><br>
                                    Quantity: <span id="infoQty"></span> | 
                                    Amount: <span id="infoAmount"></span> RWF<br>
                                    Status: <span id="infoStatus"></span> | 
                                    Date: <span id="infoDate"></span>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Reason for Refund</label>
                                <select name="refund_reason" required>
                                    <option value="">-- Select reason --</option>
                                    <option value="PAYMENT_FAILED">Payment Failed / Charged but not processed</option>
                                    <option value="DUPLICATE_CHARGE">Duplicate Charge</option>
                                    <option value="PRODUCT_NOT_RECEIVED">Product Not Received</option>
                                    <option value="PRODUCT_DEFECTIVE">Product Defective</option>
                                    <option value="WRONG_PRODUCT">Received Wrong Product</option>
                                    <option value="CHANGED_MIND">Changed My Mind</option>
                                    <option value="OTHER">Other</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Additional Details</label>
                                <textarea name="reason_details" placeholder="Please provide more details about your refund request. Include order issues, dates, and any relevant information that will help us process your request faster."></textarea>
                            </div>
                            
                            <button type="submit" name="submit_refund" class="btn-submit-refund">
                                <i class="fas fa-paper-plane"></i>
                                Submit Refund Request
                            </button>
                        </form>
                    </div>
                    
                    <!-- Requests List -->
                    <div class="requests-list">
                        <h3><i class="fas fa-list"></i> Your Refund Requests</h3>
                        
                        <?php if(mysqli_num_rows($refund_requests) > 0): ?>
                            <?php mysqli_data_seek($refund_requests, 0); while($request = mysqli_fetch_array($refund_requests)): ?>
                            <div class="request-card">
                                <div class="request-header">
                                    <span class="request-id">Request #<?php echo $request['id']; ?></span>
                                    <span class="request-status status-<?php echo strtolower($request['status']); ?>">
                                        <?php echo str_replace('_', ' ', $request['status']); ?>
                                    </span>
                                </div>
                                
                                <div class="request-details">
                                    <span><i class="fas fa-shopping-bag"></i> Order #<?php echo $request['order_id']; ?></span>
                                    <span><i class="fas fa-box"></i> <?php echo htmlspecialchars($request['tool_name']); ?></span>
                                    <span><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($request['created_at'])); ?></span>
                                    <span><i class="fas fa-layer-group"></i> Qty: <?php echo $request['quantity']; ?></span>
                                </div>
                                
                                <div class="request-amount">
                                    Refund Amount: <?php echo number_format($request['refund_amount']); ?> RWF
                                </div>
                                
                                <div class="request-reason">
                                    <strong>Reason:</strong> <?php echo str_replace('_', ' ', $request['refund_reason']); ?><br>
                                    <?php if(!empty($request['reason_details'])): ?>
                                    <em><?php echo htmlspecialchars($request['reason_details']); ?></em>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if(!empty($request['admin_notes'])): ?>
                                <div class="admin-notes">
                                    <strong>Admin Response:</strong>
                                    <?php echo htmlspecialchars($request['admin_notes']); ?>
                                </div>
                                <?php endif; ?>
                                
                                <?php if($request['status'] == 'PENDING'): ?>
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="refund_id" value="<?php echo $request['id']; ?>">
                                    <button type="submit" name="cancel_refund" class="btn-cancel-refund" onclick="return confirm('Are you sure you want to cancel this refund request?')">
                                        <i class="fas fa-times"></i> Cancel Request
                                    </button>
                                </form>
                                <?php endif; ?>
                                
                                <?php if($request['status'] == 'PROCESSED' && !empty($request['stripe_refund_id'])): ?>
                                <div style="margin-top: 10px; padding: 8px 12px; background: #d1fae5; border-radius: 6px; font-size: 0.85rem; color: #065f46;">
                                    <i class="fas fa-check-circle"></i> Refund processed on <?php echo date('M d, Y', strtotime($request['processed_at'])); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="no-requests">
                                <i class="fas fa-inbox"></i>
                                <p>No refund requests yet</p>
                                <p style="font-size: 0.9rem;">Submit a request using the form on the left</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        function showOrderInfo() {
            const select = document.getElementById('orderSelect');
            const info = document.getElementById('orderInfo');
            const option = select.options[select.selectedIndex];
            
            if(option.value) {
                document.getElementById('infoName').textContent = option.dataset.name;
                document.getElementById('infoQty').textContent = option.dataset.qty;
                document.getElementById('infoAmount').textContent = parseInt(option.dataset.amount).toLocaleString();
                document.getElementById('infoStatus').textContent = option.dataset.status;
                document.getElementById('infoDate').textContent = option.dataset.date;
                info.classList.add('show');
            } else {
                info.classList.remove('show');
            }
        }
    </script>
</body>
</html>
