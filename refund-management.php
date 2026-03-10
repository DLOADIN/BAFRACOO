<?php
require "connection.php";

if(!empty($_SESSION["id"])){
    $id = $_SESSION["id"];
    $check = mysqli_query($con, "SELECT * FROM `admin` WHERE id=$id");
    $row = mysqli_fetch_array($check);
} else {
    header('location:loginadmin.php');
    exit();
}

$success_message = '';
$error_message = '';

// Handle status update
if(isset($_POST['update_status'])){
    $refund_id = mysqli_real_escape_string($con, $_POST['refund_id']);
    $new_status = mysqli_real_escape_string($con, $_POST['new_status']);
    $admin_notes = mysqli_real_escape_string($con, $_POST['admin_notes']);
    
    $update = mysqli_query($con, "UPDATE `refund_requests` SET 
        status = '$new_status', 
        admin_notes = '$admin_notes',
        processed_by = $id
        WHERE id = $refund_id");
    
    if($update){
        $success_message = "Refund request #$refund_id status updated to $new_status";
    } else {
        $error_message = "Failed to update status";
    }
}

// Handle refund processing (simulated - in production would call Stripe)
if(isset($_POST['process_refund'])){
    $refund_id = mysqli_real_escape_string($con, $_POST['refund_id']);
    
    // Get refund request details
    $refund = mysqli_fetch_array(mysqli_query($con, "SELECT * FROM `refund_requests` WHERE id = $refund_id"));
    
    if($refund && $refund['status'] == 'APPROVED'){
        // In production, this would call the Stripe Refund API
        // For now, we simulate a successful refund
        $stripe_refund_id = 're_' . bin2hex(random_bytes(12));
        
        $update = mysqli_query($con, "UPDATE `refund_requests` SET 
            status = 'PROCESSED',
            stripe_refund_id = '$stripe_refund_id',
            processed_at = NOW(),
            processed_by = $id
            WHERE id = $refund_id");
        
        // Record refund transaction
        mysqli_query($con, "INSERT INTO `refund_transactions` 
            (refund_request_id, order_id, user_id, amount, stripe_refund_id, status) 
            VALUES ($refund_id, {$refund['order_id']}, {$refund['user_id']}, {$refund['refund_amount']}, '$stripe_refund_id', 'COMPLETED')");
        
        // Update order status
        mysqli_query($con, "UPDATE `order` SET status = 'Refunded' WHERE id = {$refund['order_id']}");
        
        if($update){
            $success_message = "Refund processed successfully! Refund ID: $stripe_refund_id";
        } else {
            $error_message = "Failed to process refund";
        }
    } else {
        $error_message = "Refund must be approved before processing";
    }
}

// Filter handling
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($con, $_GET['status']) : '';
$search = isset($_GET['search']) ? mysqli_real_escape_string($con, $_GET['search']) : '';

$where_clause = "1=1";
if(!empty($status_filter)) {
    $where_clause .= " AND rr.status = '$status_filter'";
}
if(!empty($search)) {
    $where_clause .= " AND (rr.id LIKE '%$search%' OR rr.tool_name LIKE '%$search%' OR u.u_name LIKE '%$search%')";
}

// Get refund requests with user info
$refund_requests = mysqli_query($con, "
    SELECT rr.*, u.u_name, u.u_email, o.status as order_status 
    FROM `refund_requests` rr 
    LEFT JOIN `user` u ON rr.user_id = u.id 
    LEFT JOIN `order` o ON rr.order_id = o.id 
    WHERE $where_clause 
    ORDER BY 
        CASE rr.status 
            WHEN 'PENDING' THEN 1 
            WHEN 'UNDER_REVIEW' THEN 2 
            WHEN 'APPROVED' THEN 3 
            WHEN 'PROCESSED' THEN 4 
            WHEN 'REJECTED' THEN 5 
            WHEN 'CANCELLED' THEN 6 
        END,
        rr.created_at DESC
");

// Statistics
$stats = [
    'pending' => mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) as cnt FROM refund_requests WHERE status = 'PENDING'"))['cnt'],
    'under_review' => mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) as cnt FROM refund_requests WHERE status = 'UNDER_REVIEW'"))['cnt'],
    'approved' => mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) as cnt FROM refund_requests WHERE status = 'APPROVED'"))['cnt'],
    'processed' => mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) as cnt FROM refund_requests WHERE status = 'PROCESSED'"))['cnt'],
    'rejected' => mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) as cnt FROM refund_requests WHERE status = 'REJECTED'"))['cnt'],
    'total_amount' => mysqli_fetch_array(mysqli_query($con, "SELECT COALESCE(SUM(refund_amount), 0) as total FROM refund_requests WHERE status = 'PROCESSED'"))['total'],
    'pending_amount' => mysqli_fetch_array(mysqli_query($con, "SELECT COALESCE(SUM(refund_amount), 0) as total FROM refund_requests WHERE status IN ('PENDING', 'UNDER_REVIEW', 'APPROVED')"))['total'],
];

$current_page = 'refunds';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./CSS/modern-dashboard.css">
    <link rel="stylesheet" href="./CSS/enhanced-pages.css">
    <link rel="shortcut icon" href="./images/Capture.JPG" type="image/x-icon">
    <script src="https://kit.fontawesome.com/14ff3ea278.js" crossorigin="anonymous"></script>
    <title>BAFRACOO - Refund Management</title>
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        @media (max-width: 1400px) {
            .stats-grid { grid-template-columns: repeat(3, 1fr); }
        }
        
        @media (max-width: 800px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }
        
        .stat-icon.pending { background: #fef3c7; color: #d97706; }
        .stat-icon.review { background: #dbeafe; color: #2563eb; }
        .stat-icon.approved { background: #d1fae5; color: #059669; }
        .stat-icon.processed { background: #c7d2fe; color: #6366f1; }
        .stat-icon.rejected { background: #fee2e2; color: #dc2626; }
        .stat-icon.amount { background: #e0e7ff; color: #4f46e5; }
        
        .stat-info .label {
            font-size: 0.8rem;
            color: #64748b;
            margin-bottom: 4px;
        }
        
        .stat-info .value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
        }
        
        .filter-bar {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
            display: flex;
            gap: 16px;
            align-items: center;
            flex-wrap: wrap;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        }
        
        .filter-bar input,
        .filter-bar select {
            padding: 10px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.2s;
        }
        
        .filter-bar input:focus,
        .filter-bar select:focus {
            outline: none;
            border-color: #2563eb;
        }
        
        .filter-bar input {
            flex: 1;
            min-width: 200px;
        }
        
        .btn-filter {
            padding: 10px 20px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-filter:hover {
            background: #1d4ed8;
        }
        
        .refunds-table {
            background: white;
            border-radius: 16px;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .refunds-table table {
            width: 100%;
            min-width: 750px;
            border-collapse: collapse;
        }
        
        .refunds-table th {
            background: #f8fafc;
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: #475569;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .refunds-table td {
            padding: 16px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        
        .refunds-table tr:hover {
            background: #f8fafc;
        }
        
        .customer-info {
            display: flex;
            flex-direction: column;
        }
        
        .customer-name {
            font-weight: 600;
            color: #1e293b;
        }
        
        .customer-email {
            font-size: 0.8rem;
            color: #64748b;
        }
        
        .order-info {
            display: flex;
            flex-direction: column;
        }
        
        .order-id {
            font-weight: 600;
            color: #2563eb;
        }
        
        .order-product {
            font-size: 0.85rem;
            color: #64748b;
        }
        
        .amount-info {
            text-align: right;
        }
        
        .amount-value {
            font-weight: 700;
            font-size: 1.1rem;
            color: #059669;
        }
        
        .amount-label {
            font-size: 0.75rem;
            color: #94a3b8;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-under_review { background: #dbeafe; color: #1e40af; }
        .status-approved { background: #d1fae5; color: #065f46; }
        .status-processed { background: #c7d2fe; color: #3730a3; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        .status-cancelled { background: #f3f4f6; color: #6b7280; }
        
        .reason-cell {
            max-width: 200px;
        }
        
        .reason-type {
            font-weight: 600;
            color: #1e293b;
            font-size: 0.85rem;
        }
        
        .reason-details {
            font-size: 0.8rem;
            color: #64748b;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 180px;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn-action {
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-view {
            background: #e0e7ff;
            color: #4f46e5;
        }
        
        .btn-approve {
            background: #d1fae5;
            color: #059669;
        }
        
        .btn-reject {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .btn-process {
            background: #c7d2fe;
            color: #4f46e5;
        }
        
        .btn-action:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 16px;
            padding: 30px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            animation: modalSlide 0.3s ease;
        }
        
        @keyframes modalSlide {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .modal-header h2 {
            font-size: 1.25rem;
            color: #1e293b;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #94a3b8;
            cursor: pointer;
        }
        
        .modal-close:hover {
            color: #64748b;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .detail-label {
            color: #64748b;
            font-size: 0.9rem;
        }
        
        .detail-value {
            font-weight: 600;
            color: #1e293b;
        }
        
        .modal-form-group {
            margin-top: 20px;
        }
        
        .modal-form-group label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }
        
        .modal-form-group select,
        .modal-form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
        }
        
        .modal-form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .modal-actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }
        
        .modal-actions button {
            flex: 1;
            padding: 14px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-modal-primary {
            background: #2563eb;
            color: white;
            border: none;
        }
        
        .btn-modal-primary:hover {
            background: #1d4ed8;
        }
        
        .btn-modal-secondary {
            background: #f1f5f9;
            color: #475569;
            border: none;
        }
        
        .btn-modal-secondary:hover {
            background: #e2e8f0;
        }
        
        .btn-modal-success {
            background: #059669;
            color: white;
            border: none;
        }
        
        .btn-modal-success:hover {
            background: #047857;
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
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 16px;
            color: #cbd5e1;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include 'includes/admin_sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <div class="header-left">
                    <h1>Refund Management</h1>
                </div>
                <div class="header-right">
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($row['u_name'] ?? 'Admin'); ?></span>
                        <span class="user-role">Administrator</span>
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

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon pending">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <div class="label">Pending</div>
                            <div class="value"><?php echo $stats['pending']; ?></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon review">
                            <i class="fas fa-search"></i>
                        </div>
                        <div class="stat-info">
                            <div class="label">Under Review</div>
                            <div class="value"><?php echo $stats['under_review']; ?></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon approved">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="stat-info">
                            <div class="label">Approved</div>
                            <div class="value"><?php echo $stats['approved']; ?></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon processed">
                            <i class="fas fa-check-double"></i>
                        </div>
                        <div class="stat-info">
                            <div class="label">Processed</div>
                            <div class="value"><?php echo $stats['processed']; ?></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon rejected">
                            <i class="fas fa-times"></i>
                        </div>
                        <div class="stat-info">
                            <div class="label">Rejected</div>
                            <div class="value"><?php echo $stats['rejected']; ?></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon amount">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="stat-info">
                            <div class="label">Total Refunded</div>
                            <div class="value" style="font-size: 1.1rem;"><?php echo number_format($stats['total_amount']); ?> RWF</div>
                        </div>
                    </div>
                </div>

                <!-- Filter Bar -->
                <form class="filter-bar" method="GET">
                    <input type="text" name="search" placeholder="Search by ID, product, or customer..." value="<?php echo htmlspecialchars($search); ?>">
                    <select name="status">
                        <option value="">All Statuses</option>
                        <option value="PENDING" <?php echo $status_filter == 'PENDING' ? 'selected' : ''; ?>>Pending</option>
                        <option value="UNDER_REVIEW" <?php echo $status_filter == 'UNDER_REVIEW' ? 'selected' : ''; ?>>Under Review</option>
                        <option value="APPROVED" <?php echo $status_filter == 'APPROVED' ? 'selected' : ''; ?>>Approved</option>
                        <option value="PROCESSED" <?php echo $status_filter == 'PROCESSED' ? 'selected' : ''; ?>>Processed</option>
                        <option value="REJECTED" <?php echo $status_filter == 'REJECTED' ? 'selected' : ''; ?>>Rejected</option>
                        <option value="CANCELLED" <?php echo $status_filter == 'CANCELLED' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                    <button type="submit" class="btn-filter">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <button type="button" onclick="exportRefundsPDF()" class="btn-filter" style="background: #3b82f6; color: white;">
                        <i class="fas fa-download"></i> Export PDF
                    </button>
                    <?php if(!empty($status_filter) || !empty($search)): ?>
                    <a href="refund-management.php" style="color: #64748b; text-decoration: none; font-size: 0.9rem;">
                        <i class="fas fa-times"></i> Clear
                    </a>
                    <?php endif; ?>
                </form>

                <!-- Refunds Table -->
                <div class="refunds-table">
                    <?php if($refund_requests && mysqli_num_rows($refund_requests) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Order</th>
                                <th>Reason</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($request = mysqli_fetch_array($refund_requests)): ?>
                            <tr>
                                <td><strong>#<?php echo $request['id']; ?></strong></td>
                                <td>
                                    <div class="customer-info">
                                        <span class="customer-name"><?php echo htmlspecialchars($request['u_name'] ?? 'N/A'); ?></span>
                                        <span class="customer-email"><?php echo htmlspecialchars($request['u_email'] ?? ''); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="order-info">
                                        <span class="order-id">#<?php echo $request['order_id']; ?></span>
                                        <span class="order-product"><?php echo htmlspecialchars($request['tool_name']); ?></span>
                                    </div>
                                </td>
                                <td class="reason-cell">
                                    <div class="reason-type"><?php echo str_replace('_', ' ', $request['refund_reason']); ?></div>
                                    <?php if(!empty($request['reason_details'])): ?>
                                    <div class="reason-details" title="<?php echo htmlspecialchars($request['reason_details']); ?>">
                                        <?php echo htmlspecialchars($request['reason_details']); ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="amount-info">
                                        <div class="amount-value"><?php echo number_format($request['refund_amount']); ?></div>
                                        <div class="amount-label">RWF</div>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($request['status']); ?>">
                                        <?php echo str_replace('_', ' ', $request['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($request['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-action btn-view" onclick="viewRefund(<?php echo htmlspecialchars(json_encode($request)); ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if($request['status'] == 'PENDING' || $request['status'] == 'UNDER_REVIEW'): ?>
                                        <button class="btn-action btn-approve" onclick="updateStatus(<?php echo $request['id']; ?>, 'APPROVED')">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="btn-action btn-reject" onclick="updateStatus(<?php echo $request['id']; ?>, 'REJECTED')">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if($request['status'] == 'APPROVED'): ?>
                                        <button class="btn-action btn-process" onclick="processRefund(<?php echo $request['id']; ?>)">
                                            <i class="fas fa-credit-card"></i> Process
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>No Refund Requests</h3>
                        <p>There are no refund requests matching your criteria.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- View Refund Modal -->
    <div class="modal" id="viewModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-undo-alt"></i> Refund Request Details</h2>
                <button class="modal-close" onclick="closeModal('viewModal')">&times;</button>
            </div>
            <div id="refundDetails"></div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal" id="statusModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> Update Refund Status</h2>
                <button class="modal-close" onclick="closeModal('statusModal')">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="refund_id" id="statusRefundId">
                <input type="hidden" name="new_status" id="newStatus">
                
                <div class="modal-form-group">
                    <label>New Status</label>
                    <div id="statusDisplay" style="font-size: 1.25rem; font-weight: 700; padding: 10px 0;"></div>
                </div>
                
                <div class="modal-form-group">
                    <label>Admin Notes (will be visible to customer)</label>
                    <textarea name="admin_notes" placeholder="Add notes about this decision..."></textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-modal-secondary" onclick="closeModal('statusModal')">Cancel</button>
                    <button type="submit" name="update_status" class="btn-modal-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Process Refund Modal -->
    <div class="modal" id="processModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-credit-card"></i> Process Refund</h2>
                <button class="modal-close" onclick="closeModal('processModal')">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="refund_id" id="processRefundId">
                
                <p style="margin-bottom: 20px; color: #475569;">
                    Are you sure you want to process this refund? This will initiate the actual refund through Stripe and cannot be undone.
                </p>
                
                <div style="background: #fef3c7; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
                    <strong style="color: #92400e;"><i class="fas fa-exclamation-triangle"></i> Important:</strong>
                    <p style="color: #92400e; font-size: 0.9rem; margin-top: 8px;">
                        The refund will be processed through Stripe and credited back to the customer's original payment method within 5-10 business days.
                    </p>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-modal-secondary" onclick="closeModal('processModal')">Cancel</button>
                    <button type="submit" name="process_refund" class="btn-modal-success">
                        <i class="fas fa-check"></i> Confirm & Process Refund
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function exportRefundsPDF() {
            window.open('export_pdf.php?type=refunds', '_blank');
        }
        
        function viewRefund(data) {
            const details = `
                <div class="detail-row">
                    <span class="detail-label">Request ID</span>
                    <span class="detail-value">#${data.id}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Customer</span>
                    <span class="detail-value">${data.u_name || 'N/A'} (${data.u_email || ''})</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Order ID</span>
                    <span class="detail-value">#${data.order_id}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Product</span>
                    <span class="detail-value">${data.tool_name} (Qty: ${data.quantity})</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Order Amount</span>
                    <span class="detail-value">${parseInt(data.order_amount).toLocaleString()} RWF</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Refund Amount</span>
                    <span class="detail-value" style="color: #059669; font-size: 1.2rem;">${parseInt(data.refund_amount).toLocaleString()} RWF</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Reason</span>
                    <span class="detail-value">${data.refund_reason.replace(/_/g, ' ')}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Details</span>
                    <span class="detail-value">${data.reason_details || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status</span>
                    <span class="detail-value">
                        <span class="status-badge status-${data.status.toLowerCase()}">${data.status.replace(/_/g, ' ')}</span>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Submitted</span>
                    <span class="detail-value">${new Date(data.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</span>
                </div>
                ${data.admin_notes ? `
                <div class="detail-row">
                    <span class="detail-label">Admin Notes</span>
                    <span class="detail-value">${data.admin_notes}</span>
                </div>
                ` : ''}
                ${data.stripe_refund_id ? `
                <div class="detail-row">
                    <span class="detail-label">Stripe Refund ID</span>
                    <span class="detail-value" style="font-family: monospace;">${data.stripe_refund_id}</span>
                </div>
                ` : ''}
            `;
            
            document.getElementById('refundDetails').innerHTML = details;
            document.getElementById('viewModal').classList.add('active');
        }
        
        function updateStatus(refundId, status) {
            document.getElementById('statusRefundId').value = refundId;
            document.getElementById('newStatus').value = status;
            
            const statusColors = {
                'APPROVED': '#059669',
                'REJECTED': '#dc2626',
                'UNDER_REVIEW': '#2563eb'
            };
            
            document.getElementById('statusDisplay').innerHTML = `<span style="color: ${statusColors[status]}">${status.replace(/_/g, ' ')}</span>`;
            document.getElementById('statusModal').classList.add('active');
        }
        
        function processRefund(refundId) {
            document.getElementById('processRefundId').value = refundId;
            document.getElementById('processModal').classList.add('active');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
        
        // Close modal when clicking outside
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if(e.target === modal) {
                    modal.classList.remove('active');
                }
            });
        });
    </script>
    
    <!-- Ionicons for sidebar icons -->
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>
