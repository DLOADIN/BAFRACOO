<?php
/**
 * Receipt Generator
 * Generates a printable receipt for paid/completed orders
 * Accessible by both admin and user (with ownership check)
 */
require "connection.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Determine if admin or user
$is_admin = false;
$logged_in_user_id = null;

if (isset($_SESSION["id"])) {
    $sid = $_SESSION["id"];
    // Check admin
    $admin_check = mysqli_query($con, "SELECT * FROM `admin` WHERE id=$sid");
    if ($admin_check && mysqli_num_rows($admin_check) > 0) {
        $is_admin = true;
    } else {
        // Check user
        $user_check = mysqli_query($con, "SELECT * FROM `user` WHERE id=$sid");
        if ($user_check && mysqli_num_rows($user_check) > 0) {
            $logged_in_user_id = $sid;
        } else {
            header('location:login.php');
            exit();
        }
    }
} else {
    header('location:login.php');
    exit();
}

// Get order ID
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($order_id == 0) {
    echo "<h2>Invalid order ID.</h2>";
    exit();
}

// Fetch order with user info
$order_sql = "SELECT o.*, u.u_name, u.u_email, u.u_phonenumber, u.u_address 
              FROM `order` o 
              INNER JOIN `user` u ON o.user_id = u.id 
              WHERE o.id = $order_id";

// Non-admin users can only see their own orders
if (!$is_admin) {
    $order_sql .= " AND o.user_id = $logged_in_user_id";
}

$order_result = mysqli_query($con, $order_sql);
if (!$order_result || mysqli_num_rows($order_result) == 0) {
    echo "<h2>Order not found or access denied.</h2>";
    exit();
}

$order = mysqli_fetch_assoc($order_result);

// Only generate receipts for paid/completed orders
$allowed_statuses = ['Paid', 'Completed', 'Refunded'];
if (!in_array($order['status'], $allowed_statuses)) {
    echo "<h2>Receipt is only available for paid or completed orders. Current status: " . htmlspecialchars($order['status']) . "</h2>";
    exit();
}

// Fetch order items (for cart orders)
$order_items = [];
$items_query = mysqli_query($con, "SELECT oi.*, t.purchase_price 
                                    FROM order_items oi 
                                    LEFT JOIN tool t ON oi.tool_id = t.id 
                                    WHERE oi.order_id = $order_id ORDER BY oi.id");
if ($items_query && mysqli_num_rows($items_query) > 0) {
    while ($item = mysqli_fetch_assoc($items_query)) {
        $order_items[] = $item;
    }
}

$is_cart_order = !empty($order_items);

// Fetch company info
$company_name = "BAFRACOO";
$company_tagline = "Your Trusted E-Commerce Partner";
$company_address = "Kigali, Rwanda";
$company_phone = "+250 788 123 456";
$company_email = "info@bafracoo.com";

// Receipt number
$receipt_number = 'RCP-' . str_pad($order['id'], 6, '0', STR_PAD_LEFT);
$payment_date = $order['payment_date'] ? date('M d, Y h:i A', strtotime($order['payment_date'])) : date('M d, Y', strtotime($order['u_date']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - <?php echo $receipt_number; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
            padding: 2rem;
        }
        
        .receipt-actions {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .receipt-actions button {
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            margin: 0 0.5rem;
            transition: all 0.2s;
        }
        
        .btn-print {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }
        
        .btn-print:hover {
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
            transform: translateY(-1px);
        }
        
        .btn-back {
            background: #e2e8f0;
            color: #475569;
        }
        
        .btn-back:hover {
            background: #cbd5e1;
        }
        
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        
        .receipt-header {
            background: linear-gradient(135deg, #1e40af, #3b82f6);
            color: white;
            padding: 2rem 2.5rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        
        .receipt-header .company-info h1 {
            font-size: 1.75rem;
            font-weight: 800;
            margin-bottom: 0.25rem;
        }
        
        .receipt-header .company-info p {
            opacity: 0.85;
            font-size: 0.9rem;
        }
        
        .receipt-header .receipt-label {
            text-align: right;
        }
        
        .receipt-header .receipt-label h2 {
            font-size: 1.5rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 0.5rem;
        }
        
        .receipt-header .receipt-label .receipt-no {
            font-size: 0.95rem;
            opacity: 0.9;
            background: rgba(255,255,255,0.2);
            padding: 0.3rem 0.75rem;
            border-radius: 6px;
            display: inline-block;
        }
        
        .receipt-body {
            padding: 2rem 2.5rem;
        }
        
        .receipt-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px dashed #e2e8f0;
        }
        
        .meta-section h3 {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
            margin-bottom: 0.75rem;
            font-weight: 600;
        }
        
        .meta-section p {
            font-size: 0.95rem;
            margin-bottom: 0.35rem;
            color: #334155;
        }
        
        .meta-section p strong {
            color: #1e293b;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.5rem;
        }
        
        .items-table thead {
            background: #f8fafc;
        }
        
        .items-table th {
            padding: 0.85rem 1rem;
            text-align: left;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            font-weight: 600;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .items-table th:last-child,
        .items-table td:last-child {
            text-align: right;
        }
        
        .items-table td {
            padding: 0.85rem 1rem;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.95rem;
        }
        
        .items-table tbody tr:last-child td {
            border-bottom: 2px solid #e2e8f0;
        }
        
        .totals-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 2rem;
        }
        
        .totals-table {
            width: 300px;
        }
        
        .totals-table .total-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            font-size: 0.95rem;
        }
        
        .totals-table .total-row.grand-total {
            border-top: 2px solid #1e293b;
            margin-top: 0.5rem;
            padding-top: 0.75rem;
            font-size: 1.2rem;
            font-weight: 700;
            color: #1e40af;
        }
        
        .totals-table .total-row .label {
            color: #64748b;
        }
        
        .totals-table .total-row .value {
            font-weight: 600;
            color: #1e293b;
        }
        
        .payment-info {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 10px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .payment-info .check-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #10b981;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
            flex-shrink: 0;
        }
        
        .payment-info .payment-text strong {
            display: block;
            color: #166534;
            margin-bottom: 0.2rem;
        }
        
        .payment-info .payment-text span {
            color: #15803d;
            font-size: 0.875rem;
        }
        
        .receipt-footer {
            text-align: center;
            padding: 1.5rem 2.5rem;
            border-top: 2px dashed #e2e8f0;
            color: #94a3b8;
            font-size: 0.85rem;
        }
        
        .receipt-footer p {
            margin-bottom: 0.25rem;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.3rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-paid {
            background: #d1fae5;
            color: #047857;
        }
        
        .status-refunded {
            background: #fee2e2;
            color: #b91c1c;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .receipt-actions {
                display: none !important;
            }
            
            .receipt-container {
                box-shadow: none;
                border-radius: 0;
            }
        }
        
        @media (max-width: 600px) {
            body {
                padding: 1rem;
            }
            
            .receipt-header {
                flex-direction: column;
                gap: 1rem;
            }
            
            .receipt-header .receipt-label {
                text-align: left;
            }
            
            .receipt-meta {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .receipt-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>

    <div class="receipt-actions">
        <button class="btn-print" onclick="window.print();">
            &#128438; Print Receipt
        </button>
        <button class="btn-back" onclick="history.back();">
            &larr; Go Back
        </button>
    </div>

    <div class="receipt-container">
        <!-- Header -->
        <div class="receipt-header">
            <div class="company-info">
                <h1><?php echo $company_name; ?></h1>
                <p><?php echo $company_tagline; ?></p>
                <p style="margin-top: 0.5rem; font-size: 0.85rem; opacity: 0.8;">
                    <?php echo $company_address; ?><br>
                    <?php echo $company_phone; ?> | <?php echo $company_email; ?>
                </p>
            </div>
            <div class="receipt-label">
                <h2>Receipt</h2>
                <div class="receipt-no"><?php echo $receipt_number; ?></div>
            </div>
        </div>

        <!-- Body -->
        <div class="receipt-body">
            <!-- Meta Info -->
            <div class="receipt-meta">
                <div class="meta-section">
                    <h3>Bill To</h3>
                    <p><strong><?php echo htmlspecialchars($order['u_name']); ?></strong></p>
                    <p><?php echo htmlspecialchars($order['u_email']); ?></p>
                    <p><?php echo htmlspecialchars($order['u_phonenumber']); ?></p>
                    <p><?php echo htmlspecialchars($order['u_address']); ?></p>
                </div>
                <div class="meta-section" style="text-align: right;">
                    <h3>Receipt Details</h3>
                    <p><strong>Receipt #:</strong> <?php echo $receipt_number; ?></p>
                    <p><strong>Order ID:</strong> #<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?></p>
                    <p><strong>Date:</strong> <?php echo $payment_date; ?></p>
                    <p><strong>Status:</strong> 
                        <span class="status-badge <?php echo ($order['status'] == 'Refunded') ? 'status-refunded' : 'status-paid'; ?>">
                            <?php echo $order['status']; ?>
                        </span>
                    </p>
                </div>
            </div>

            <!-- Items Table -->
            <table class="items-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($is_cart_order): ?>
                        <?php $i = 1; foreach ($order_items as $item): ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td><?php echo htmlspecialchars($item['tool_name']); ?></td>
                            <td><?php echo number_format($item['quantity']); ?></td>
                            <td><?php echo number_format($item['unit_price']) . ' RWF'; ?></td>
                            <td><?php echo number_format($item['total_price']) . ' RWF'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td>1</td>
                            <td><?php echo htmlspecialchars($order['u_toolname']); ?></td>
                            <td><?php echo number_format($order['u_itemsnumber']); ?></td>
                            <td><?php echo number_format($order['u_price']) . ' RWF'; ?></td>
                            <td><?php echo number_format($order['u_totalprice']) . ' RWF'; ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Totals -->
            <div class="totals-section">
                <div class="totals-table">
                    <?php
                    $subtotal = $is_cart_order 
                        ? array_sum(array_column($order_items, 'total_price'))
                        : $order['u_totalprice'];
                    $refunded = floatval($order['refunded_amount'] ?? 0);
                    $final_total = $subtotal - $refunded;
                    ?>
                    <div class="total-row">
                        <span class="label">Subtotal</span>
                        <span class="value"><?php echo number_format($subtotal) . ' RWF'; ?></span>
                    </div>
                    <?php if ($refunded > 0): ?>
                    <div class="total-row" style="color: #ef4444;">
                        <span class="label">Refunded</span>
                        <span class="value">-<?php echo number_format($refunded) . ' RWF'; ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="total-row grand-total">
                        <span class="label">Total</span>
                        <span class="value"><?php echo number_format($final_total) . ' RWF'; ?></span>
                    </div>
                </div>
            </div>

            <!-- Payment Confirmation -->
            <div class="payment-info">
                <div class="check-icon">&#10003;</div>
                <div class="payment-text">
                    <strong>Payment Received</strong>
                    <span>
                        <?php if (!empty($order['stripe_payment_intent'])): ?>
                            Paid via Stripe (Ref: <?php echo htmlspecialchars(substr($order['stripe_payment_intent'], 0, 20)); ?>...)
                        <?php else: ?>
                            Payment confirmed on <?php echo $payment_date; ?>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="receipt-footer">
            <p><strong>Thank you for your purchase!</strong></p>
            <p>If you have any questions about this receipt, please contact us at <?php echo $company_email; ?></p>
            <p style="margin-top: 0.75rem; font-size: 0.75rem;">This is a computer-generated receipt. No signature required.</p>
        </div>
    </div>

</body>
</html>
