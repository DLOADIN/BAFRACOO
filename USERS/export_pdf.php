<?php
require "connection.php";

// Check if user is logged in
if(empty($_SESSION["id"])){
  header('Location: loginuser.php');
  exit();
}

$user_id = $_SESSION['id'];

// Get export type from URL
$export_type = isset($_GET['type']) ? $_GET['type'] : '';

// Set header for HTML display
header('Content-Type: text/html; charset=utf-8');

// Determine title and query based on export type
$title = '';
$table_html = '';

switch($export_type){
    case 'my_orders':
        $title = 'My Orders Report';
        $query = "SELECT * FROM `order` WHERE user_id = '$user_id' ORDER BY u_date DESC";
        $result = mysqli_query($con, $query);
        
        $table_html = '<thead><tr>
            <th>Order ID</th>
            <th>Product</th>
            <th>Quantity</th>
            <th>Unit Price</th>
            <th>Total</th>
            <th>Status</th>
            <th>Date</th>
        </tr></thead><tbody>';
        
        if($result && mysqli_num_rows($result) > 0){
            while($row = mysqli_fetch_assoc($result)){
                $table_html .= '<tr>
                    <td>#' . str_pad($row['id'], 4, '0', STR_PAD_LEFT) . '</td>
                    <td>' . htmlspecialchars($row['u_toolname']) . '</td>
                    <td>' . number_format($row['u_itemsnumber']) . '</td>
                    <td>' . number_format($row['u_price']) . ' RWF</td>
                    <td>' . number_format($row['u_totalprice']) . ' RWF</td>
                    <td>' . htmlspecialchars($row['status']) . '</td>
                    <td>' . date('M d, Y', strtotime($row['u_date'])) . '</td>
                </tr>';
            }
        } else {
            $table_html .= '<tr><td colspan="7" style="text-align: center;">No orders found</td></tr>';
        }
        $table_html .= '</tbody>';
        break;
        
    case 'my_transactions':
        $title = 'My Transactions Report';
        $query = "SELECT t.*, o.u_totalprice, o.u_date as order_date 
                  FROM `transaction` t 
                  INNER JOIN `order` o ON t.order_id = o.id 
                  WHERE t.u_id = '$user_id' 
                  ORDER BY t.id DESC";
        $result = mysqli_query($con, $query);
        
        $table_html = '<thead><tr>
            <th>#</th>
            <th>Order Code</th>
            <th>Item</th>
            <th>Quantity</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Date</th>
        </tr></thead><tbody>';
        
        $row_num = 0;
        if($result && mysqli_num_rows($result) > 0){
            while($row = mysqli_fetch_assoc($result)){
                $row_num++;
                $table_html .= '<tr>
                    <td>#' . str_pad($row_num, 3, '0', STR_PAD_LEFT) . '</td>
                    <td>ORDER-' . str_pad($row['order_id'], 4, '0', STR_PAD_LEFT) . '</td>
                    <td>' . htmlspecialchars($row['u_toolname'] ?? 'N/A') . '</td>
                    <td>' . number_format($row['u_item'] ?? 0) . ' items</td>
                    <td>' . number_format($row['u_amount'] ?? $row['u_totalprice']) . ' RWF</td>
                    <td>' . htmlspecialchars($row['u_status'] ?? 'Completed') . '</td>
                    <td>' . date('M d, Y', strtotime($row['order_date'])) . '</td>
                </tr>';
            }
        } else {
            $table_html .= '<tr><td colspan="7" style="text-align: center;">No transactions found</td></tr>';
        }
        $table_html .= '</tbody>';
        break;
        
    case 'available_products':
        $title = 'Available Products Catalog';
        $query = "SELECT 
                    u_toolname,
                    SUM(u_itemsnumber) as total_stock,
                    ROUND(AVG(u_price)) as avg_price,
                    MAX(u_type) as u_type,
                    MAX(u_tooldescription) as description
                  FROM `tool`
                  WHERE u_itemsnumber > 0
                  GROUP BY u_toolname
                  ORDER BY u_toolname ASC";
        $result = mysqli_query($con, $query);
        
        $table_html = '<thead><tr>
            <th>#</th>
            <th>Product Name</th>
            <th>Type</th>
            <th>Available Stock</th>
            <th>Price (RWF)</th>
            <th>Description</th>
        </tr></thead><tbody>';
        
        $row_num = 0;
        if($result && mysqli_num_rows($result) > 0){
            while($row = mysqli_fetch_assoc($result)){
                $row_num++;
                $table_html .= '<tr>
                    <td>' . $row_num . '</td>
                    <td>' . htmlspecialchars($row['u_toolname']) . '</td>
                    <td>' . htmlspecialchars($row['u_type'] ?? 'General') . '</td>
                    <td>' . number_format($row['total_stock']) . ' units</td>
                    <td>' . number_format($row['avg_price']) . '</td>
                    <td>' . htmlspecialchars($row['description'] ?? '-') . '</td>
                </tr>';
            }
        } else {
            $table_html .= '<tr><td colspan="6" style="text-align: center;">No products available</td></tr>';
        }
        $table_html .= '</tbody>';
        break;
        
    case 'my_refunds':
        $title = 'My Refund Requests';
        $query = "SELECT rr.*, o.u_toolname 
                  FROM `refund_requests` rr 
                  LEFT JOIN `order` o ON rr.order_id = o.id 
                  WHERE rr.user_id = '$user_id' 
                  ORDER BY rr.created_at DESC";
        $result = mysqli_query($con, $query);
        
        $table_html = '<thead><tr>
            <th>Request ID</th>
            <th>Order ID</th>
            <th>Product</th>
            <th>Reason</th>
            <th>Amount (RWF)</th>
            <th>Status</th>
            <th>Date</th>
        </tr></thead><tbody>';
        
        if($result && mysqli_num_rows($result) > 0){
            while($row = mysqli_fetch_assoc($result)){
                $table_html .= '<tr>
                    <td>#' . $row['id'] . '</td>
                    <td>#' . $row['order_id'] . '</td>
                    <td>' . htmlspecialchars($row['tool_name']) . '</td>
                    <td>' . str_replace('_', ' ', $row['refund_reason']) . '</td>
                    <td>' . number_format($row['refund_amount']) . '</td>
                    <td>' . str_replace('_', ' ', $row['status']) . '</td>
                    <td>' . date('M d, Y', strtotime($row['created_at'])) . '</td>
                </tr>';
            }
        } else {
            $table_html .= '<tr><td colspan="7" style="text-align: center;">No refund requests found</td></tr>';
        }
        $table_html .= '</tbody>';
        break;
        
    default:
        $title = 'BAFRACOO Report';
        $table_html = '<thead><tr><th>Error</th></tr></thead><tbody><tr><td>Invalid export type</td></tr></tbody>';
}

// Get user info
$user_query = mysqli_query($con, "SELECT u_name FROM `user` WHERE id = '$user_id'");
$user_info = mysqli_fetch_assoc($user_query);
$user_name = $user_info['u_name'] ?? 'Customer';

// Output complete HTML page
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>BAFRACOO - <?php echo htmlspecialchars($title); ?></title>
    <style>
        @page {
            margin: 15mm;
            size: A4;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 11px;
            padding: 20px;
            background: #ffffff;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 15px;
        }
        .logo {
            margin-bottom: 10px;
        }
        .header h1 {
            color: #2563eb;
            margin: 10px 0;
            font-size: 26px;
            font-weight: 700;
        }
        .header .subtitle {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        .header .date {
            color: #888;
            font-size: 11px;
            margin-top: 8px;
        }
        .header .customer-info {
            margin-top: 10px;
            padding: 8px;
            background: #f0f7ff;
            border-radius: 6px;
            display: inline-block;
        }
        .print-button {
            padding: 12px 30px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .print-button:hover {
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            transform: translateY(-1px);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        th {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            padding: 12px 10px;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            border: 1px solid #1d4ed8;
        }
        td {
            padding: 10px;
            border: 1px solid #e5e7eb;
            font-size: 11px;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        tr:hover {
            background-color: #eff6ff;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #9ca3af;
            border-top: 2px solid #e5e7eb;
            padding-top: 15px;
        }
        .footer strong {
            color: #2563eb;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 0;
            }
            table {
                page-break-inside: auto;
            }
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            thead {
                display: table-header-group;
            }
            tfoot {
                display: table-footer-group;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">
            <strong style="color: #2563eb; font-size: 20px;">BAFRACOO</strong>
        </div>
        <h1><?php echo htmlspecialchars($title); ?></h1>
        <div class="subtitle">Business Administration & Facility Resources Company</div>
        <div class="date">Generated on: <?php echo date('F d, Y \a\t H:i:s'); ?></div>
        <div class="customer-info">
            <strong>Customer:</strong> <?php echo htmlspecialchars($user_name); ?>
        </div>
    </div>

    <button class="print-button no-print" onclick="window.print()">
        🖨️ Print / Save as PDF
    </button>

    <table>
        <?php echo $table_html; ?>
    </table>

    <div class="footer">
        <p>&copy; <?php echo date('Y'); ?> <strong>BAFRACOO</strong> - All Rights Reserved</p>
        <p style="margin-top: 5px;">This is a computer-generated document. No signature is required.</p>
    </div>

    <script>
        // Auto-focus print dialog on page load if URL parameter is set
        if(window.location.search.includes('auto=1')) {
            window.onload = function() {
                setTimeout(function() {
                    window.print();
                }, 500);
            };
        }
    </script>
</body>
</html>
