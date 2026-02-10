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

// Get summary statistics
$pending_count = mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) as cnt FROM refund_requests WHERE user_id = $id AND status = 'PENDING'"))['cnt'] ?? 0;
$approved_count = mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) as cnt FROM refund_requests WHERE user_id = $id AND status = 'APPROVED'"))['cnt'] ?? 0;
$processed_count = mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) as cnt FROM refund_requests WHERE user_id = $id AND status = 'PROCESSED'"))['cnt'] ?? 0;
$total_refunded = mysqli_fetch_array(mysqli_query($con, "SELECT COALESCE(SUM(refund_amount), 0) as total FROM refund_requests WHERE user_id = $id AND status = 'PROCESSED'"))['total'] ?? 0;

error_reporting(0);
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
        /* Page-specific styles */
        .refund-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            padding: 0 24px 24px;
        }
        
        @media (max-width: 1200px) {
            .refund-container {
                grid-template-columns: 1fr;
            }
        }
        
        .refund-form-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .refund-form-card h3 {
            font-size: 1.1rem;
            color: #1e293b;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .refund-form-card h3 ion-icon {
            color: #f59e0b;
            font-size: 1.25rem;
        }
        
        .form-group {
            margin-bottom: 18px;
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
            padding: 12px 14px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.2s;
            font-family: inherit;
            background: #f9fafb;
        }
        
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3b82f6;
            background: white;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .order-select-info {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 12px;
            margin-top: 10px;
            font-size: 0.85rem;
            color: #1e40af;
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
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .requests-list h3 {
            font-size: 1.1rem;
            color: #1e293b;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .requests-list h3 ion-icon {
            color: #3b82f6;
            font-size: 1.25rem;
        }
        
        .request-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            transition: all 0.2s;
        }
        
        .request-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        
        .request-id {
            font-weight: 700;
            color: #1e293b;
            font-size: 0.95rem;
        }
        
        .request-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-under_review { background: #dbeafe; color: #1e40af; }
        .status-approved { background: #d1fae5; color: #065f46; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        .status-processed { background: #c7d2fe; color: #3730a3; }
        .status-cancelled { background: #f3f4f6; color: #6b7280; }
        
        .request-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            font-size: 0.85rem;
            color: #64748b;
            margin-bottom: 12px;
        }
        
        .request-details span {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .request-amount {
            font-size: 1.15rem;
            font-weight: 700;
            color: #059669;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .request-reason {
            background: white;
            padding: 10px;
            border-radius: 8px;
            font-size: 0.85rem;
            color: #475569;
            margin-bottom: 10px;
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
            margin-bottom: 10px;
        }
        
        .btn-cancel-refund {
            padding: 8px 14px;
            background: #fee2e2;
            color: #991b1b;
            border: none;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-cancel-refund:hover {
            background: #fecaca;
        }
        
        .no-requests {
            text-align: center;
            padding: 40px 20px;
            color: #94a3b8;
        }
        
        .no-requests ion-icon {
            font-size: 3rem;
            margin-bottom: 16px;
            color: #cbd5e1;
        }
        
        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin: 0 24px 20px;
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
        
        /* Stats row matching dashboard */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            padding: 24px;
        }
        
        @media (max-width: 1100px) {
            .stats-row {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 600px) {
            .stats-row {
                grid-template-columns: 1fr;
            }
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            text-align: center;
            border: 1px solid #f1f5f9;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            font-size: 1.5rem;
        }
        
        .stat-icon.orange { background: #fff7ed; color: #f59e0b; }
        .stat-icon.green { background: #ecfdf5; color: #10b981; }
        .stat-icon.purple { background: #f5f3ff; color: #6366f1; }
        .stat-icon.blue { background: #eff6ff; color: #3b82f6; }
        
        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 4px;
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-change {
            margin-top: 8px;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }
        
        .stat-change.positive { color: #10b981; }
        .stat-change.neutral { color: #f59e0b; }
    </style>
</head>
<body>
  <div class="dashboard-container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="sidebar-logo">
        <img src="../images/Captured.JPG" alt="BAFRACOO Logo">
        <span class="logo-text">BAFRACOO</span>
      </div>
      
      <nav class="sidebar-nav">
        <div class="nav-section">
          <h3 class="nav-section-title">Main Menu</h3>
          <ul class="nav-menu">
            <li class="nav-item">
              <a href="userdashboard.php" class="nav-link">
                <ion-icon name="home-outline" class="nav-icon"></ion-icon>
                <span class="nav-text">Dashboard</span>
              </a>
            </li>
            <li class="nav-item">
              <a href="stock.php" class="nav-link">
                <ion-icon name="cube-outline" class="nav-icon"></ion-icon>
                <span class="nav-text">Inter Purchases</span>
              </a>
            </li>
            <li class="nav-item">
              <a href="orders.php" class="nav-link">
                <ion-icon name="bag-handle-outline" class="nav-icon"></ion-icon>
                <span class="nav-text">My Orders</span>
              </a>
            </li>
            <li class="nav-item">
              <a href="transactions.php" class="nav-link">
                <ion-icon name="analytics-outline" class="nav-icon"></ion-icon>
                <span class="nav-text">Transactions</span>
              </a>
            </li>
            <li class="nav-item">
              <a href="refund-requests.php" class="nav-link active">
                <ion-icon name="wallet-outline" class="nav-icon"></ion-icon>
                <span class="nav-text">Refund Requests</span>
                <?php if($pending_count > 0): ?>
                  <span class="nav-badge" style="background: #f59e0b;"><?php echo $pending_count; ?></span>
                <?php endif; ?>
              </a>
            </li>
          </ul>
        </div>
        
        <div class="nav-section">
          <h3 class="nav-section-title">Account</h3>
          <ul class="nav-menu">
            <li class="nav-item">
              <a href="userprofile.php" class="nav-link">
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
              <a href="../website.php" class="nav-link">
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
            <?php echo strtoupper(substr($row['u_name'] ?? 'U', 0, 2)); ?>
          </div>
          <div class="user-info">
            <div class="user-name"><?php echo htmlspecialchars($row['u_name'] ?? 'User'); ?></div>
            <div class="user-role">Customer</div>
          </div>
        </div>
      </div>
    </aside>

    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay"></div>

    <!-- Main Content -->
    <main class="main-content" style="background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);">
      <!-- Page Banner -->
      <div class="page-banner">
        <h1><ion-icon name="wallet-outline"></ion-icon> Refund Requests</h1>
        <p>Request and track your refunds</p>
      </div>
      
      <?php if(!empty($success_message)): ?>
      <div class="alert alert-success">
          <ion-icon name="checkmark-circle-outline"></ion-icon>
          <?php echo $success_message; ?>
      </div>
      <?php endif; ?>
      
      <?php if(!empty($error_message)): ?>
      <div class="alert alert-error">
          <ion-icon name="alert-circle-outline"></ion-icon>
          <?php echo $error_message; ?>
      </div>
      <?php endif; ?>

      <!-- Stats Row -->
      <div class="stats-row">
        <div class="stat-card">
          <div class="stat-icon orange">
            <ion-icon name="time-outline"></ion-icon>
          </div>
          <div class="stat-value"><?php echo $pending_count; ?></div>
          <div class="stat-label">Pending</div>
          <div class="stat-change neutral">
            <ion-icon name="hourglass-outline"></ion-icon>
            Awaiting review
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon green">
            <ion-icon name="checkmark-circle-outline"></ion-icon>
          </div>
          <div class="stat-value"><?php echo $approved_count; ?></div>
          <div class="stat-label">Approved</div>
          <div class="stat-change positive">
            <ion-icon name="thumbs-up-outline"></ion-icon>
            Ready to process
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon purple">
            <ion-icon name="checkmark-done-outline"></ion-icon>
          </div>
          <div class="stat-value"><?php echo $processed_count; ?></div>
          <div class="stat-label">Processed</div>
          <div class="stat-change positive">
            <ion-icon name="wallet-outline"></ion-icon>
            Refund completed
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon blue">
            <ion-icon name="cash-outline"></ion-icon>
          </div>
          <div class="stat-value"><?php echo number_format($total_refunded); ?></div>
          <div class="stat-label">Total Refunded (RWF)</div>
          <div class="stat-change positive">
            <ion-icon name="trending-up-outline"></ion-icon>
            Lifetime refunds
          </div>
        </div>
      </div>

      <!-- Refund Form and Requests -->
      <div class="refund-container">
        <!-- Request Form -->
        <div class="refund-form-card">
          <h3><ion-icon name="add-circle-outline"></ion-icon> Submit New Refund Request</h3>
          
          <form method="POST" action="">
            <div class="form-group">
              <label>Select Order</label>
              <select name="order_id" id="orderSelect" required onchange="showOrderInfo()">
                <option value="">-- Select an order --</option>
                <?php 
                if($eligible_orders && mysqli_num_rows($eligible_orders) > 0):
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
                <?php endif; endwhile; endif; ?>
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
              <ion-icon name="paper-plane-outline"></ion-icon>
              Submit Refund Request
            </button>
          </form>
        </div>
        
        <!-- Requests List -->
        <div class="requests-list">
          <h3><ion-icon name="list-outline"></ion-icon> Your Refund Requests</h3>
          
          <?php if($refund_requests && mysqli_num_rows($refund_requests) > 0): ?>
            <?php mysqli_data_seek($refund_requests, 0); while($request = mysqli_fetch_array($refund_requests)): ?>
            <div class="request-card">
              <div class="request-header">
                <span class="request-id">Request #<?php echo $request['id']; ?></span>
                <span class="request-status status-<?php echo strtolower($request['status']); ?>">
                  <?php echo str_replace('_', ' ', $request['status']); ?>
                </span>
              </div>
              
              <div class="request-details">
                <span><ion-icon name="bag-handle-outline"></ion-icon> Order #<?php echo $request['order_id']; ?></span>
                <span><ion-icon name="cube-outline"></ion-icon> <?php echo htmlspecialchars($request['tool_name']); ?></span>
                <span><ion-icon name="calendar-outline"></ion-icon> <?php echo date('M d, Y', strtotime($request['created_at'])); ?></span>
                <span><ion-icon name="layers-outline"></ion-icon> Qty: <?php echo $request['quantity']; ?></span>
              </div>
              
              <div class="request-amount">
                <ion-icon name="cash-outline"></ion-icon> Refund Amount: <?php echo number_format($request['refund_amount']); ?> RWF
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
                  <ion-icon name="close-circle-outline"></ion-icon> Cancel Request
                </button>
              </form>
              <?php endif; ?>
              
              <?php if($request['status'] == 'PROCESSED' && !empty($request['stripe_refund_id'])): ?>
              <div style="margin-top: 10px; padding: 8px 12px; background: #d1fae5; border-radius: 6px; font-size: 0.85rem; color: #065f46; display: flex; align-items: center; gap: 8px;">
                <ion-icon name="checkmark-circle"></ion-icon> Refund processed on <?php echo date('M d, Y', strtotime($request['processed_at'])); ?>
              </div>
              <?php endif; ?>
            </div>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="no-requests">
              <ion-icon name="wallet-outline"></ion-icon>
              <p>No refund requests yet</p>
              <p style="font-size: 0.85rem; margin-top: 8px;">Submit a request using the form on the left</p>
            </div>
          <?php endif; ?>
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
  
  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>
