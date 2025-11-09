<?php
require "connection.php";

// Check if user is logged in
if(empty($_SESSION["id"])){
  header('Location: loginadmin.php');
  exit();
}

// Get export type from URL
$export_type = isset($_GET['type']) ? $_GET['type'] : '';

// Set header for HTML display
header('Content-Type: text/html; charset=utf-8');

// Determine title and query based on export type
$title = '';
$table_html = '';

switch($export_type){
    case 'orders':
        $title = 'Orders Report';
        $query = "SELECT o.id, u.u_name, o.u_toolname, o.u_itemsnumber, o.u_totalprice, o.u_date 
                  FROM `order` o 
                  LEFT JOIN `user` u ON o.user_id = u.id";
        
        // Add date filter if provided
        if(isset($_GET['start_date']) && isset($_GET['end_date'])){
            $start_date = mysqli_real_escape_string($con, $_GET['start_date']);
            $end_date = mysqli_real_escape_string($con, $_GET['end_date']);
            $query .= " WHERE DATE(o.u_date) BETWEEN '$start_date' AND '$end_date'";
            $title .= ' (' . date('M d, Y', strtotime($start_date)) . ' - ' . date('M d, Y', strtotime($end_date)) . ')';
        }
        
        $query .= " ORDER BY o.u_date DESC";
        $result = mysqli_query($con, $query);
        
        $table_html = '<thead><tr>
            <th>Order ID</th>
            <th>Customer Name</th>
            <th>Tool/Product</th>
            <th>Quantity</th>
            <th>Total Price</th>
            <th>Date</th>
        </tr></thead><tbody>';
        
        if($result && mysqli_num_rows($result) > 0){
            while($row = mysqli_fetch_assoc($result)){
                $table_html .= '<tr>
                    <td>' . htmlspecialchars($row['id']) . '</td>
                    <td>' . htmlspecialchars($row['u_name'] ?? 'N/A') . '</td>
                    <td>' . htmlspecialchars($row['u_toolname'] ?? 'N/A') . '</td>
                    <td>' . htmlspecialchars($row['u_itemsnumber'] ?? '0') . '</td>
                    <td>' . number_format($row['u_totalprice'] ?? 0) . ' RWF</td>
                    <td>' . date('M d, Y', strtotime($row['u_date'] ?? 'now')) . '</td>
                </tr>';
            }
        } else {
            $table_html .= '<tr><td colspan="6" style="text-align: center;">No orders found</td></tr>';
        }
        $table_html .= '</tbody>';
        break;
        
    case 'stock':
        $title = 'Stock Inventory Report';
        $query = "SELECT id, u_toolname, u_itemsnumber, u_type, u_tooldescription, u_price, u_date 
                  FROM `tool`";
        
        // Add date filter if provided
        if(isset($_GET['start_date']) && isset($_GET['end_date'])){
            $start_date = mysqli_real_escape_string($con, $_GET['start_date']);
            $end_date = mysqli_real_escape_string($con, $_GET['end_date']);
            $query .= " WHERE DATE(u_date) BETWEEN '$start_date' AND '$end_date'";
            $title .= ' (' . date('M d, Y', strtotime($start_date)) . ' - ' . date('M d, Y', strtotime($end_date)) . ')';
        }
        
        $query .= " ORDER BY u_date DESC";
        $result = mysqli_query($con, $query);
        
        $table_html = '<thead><tr>
            <th>ID</th>
            <th>Tool Name</th>
            <th>Quantity</th>
            <th>Type</th>
            <th>Price</th>
            <th>Date</th>
        </tr></thead><tbody>';
        
        if($result && mysqli_num_rows($result) > 0){
            while($row = mysqli_fetch_assoc($result)){
                $table_html .= '<tr>
                    <td>' . htmlspecialchars($row['id']) . '</td>
                    <td>' . htmlspecialchars($row['u_toolname']) . '</td>
                    <td>' . htmlspecialchars($row['u_itemsnumber']) . '</td>
                    <td>' . htmlspecialchars($row['u_type']) . '</td>
                    <td>' . number_format($row['u_price']) . ' RWF</td>
                    <td>' . date('M d, Y', strtotime($row['u_date'])) . '</td>
                </tr>';
            }
        } else {
            $table_html .= '<tr><td colspan="6" style="text-align: center;">No stock items found</td></tr>';
        }
        $table_html .= '</tbody>';
        break;
        
    case 'transactions':
        $title = 'Transactions Report';
        $query = "SELECT o.id, u.u_name, o.u_toolname, o.u_itemsnumber, o.u_totalprice, o.status, o.u_date 
                  FROM `order` o 
                  LEFT JOIN `user` u ON o.user_id = u.id";
        
        // Add date filter if provided
        if(isset($_GET['start_date']) && isset($_GET['end_date'])){
            $start_date = mysqli_real_escape_string($con, $_GET['start_date']);
            $end_date = mysqli_real_escape_string($con, $_GET['end_date']);
            $query .= " WHERE DATE(o.u_date) BETWEEN '$start_date' AND '$end_date'";
            $title .= ' (' . date('M d, Y', strtotime($start_date)) . ' - ' . date('M d, Y', strtotime($end_date)) . ')';
        }
        
        $query .= " ORDER BY o.u_date DESC";
        $result = mysqli_query($con, $query);
        
        $table_html = '<thead><tr>
            <th>Transaction ID</th>
            <th>Customer</th>
            <th>Tool</th>
            <th>Quantity</th>
            <th>Amount</th>
            <th>Type</th>
            <th>Status</th>
            <th>Date</th>
        </tr></thead><tbody>';
        
        if($result && mysqli_num_rows($result) > 0){
            while($row = mysqli_fetch_assoc($result)){
                $status = $row['status'] ?? 'Pending';
                $type = ($status == 'Completed') ? 'Income' : 'Pending';
                
                $table_html .= '<tr>
                    <td>#' . str_pad($row['id'], 6, '0', STR_PAD_LEFT) . '</td>
                    <td>' . htmlspecialchars($row['u_name'] ?? 'N/A') . '</td>
                    <td>' . htmlspecialchars($row['u_toolname'] ?? 'N/A') . '</td>
                    <td>' . htmlspecialchars($row['u_itemsnumber'] ?? '0') . '</td>
                    <td>' . number_format($row['u_totalprice'] ?? 0) . ' RWF</td>
                    <td>' . $type . '</td>
                    <td>' . htmlspecialchars($status) . '</td>
                    <td>' . date('M d, Y', strtotime($row['u_date'] ?? 'now')) . '</td>
                </tr>';
            }
        } else {
            $table_html .= '<tr><td colspan="8" style="text-align: center;">No transactions found</td></tr>';
        }
        $table_html .= '</tbody>';
        break;
        
    case 'report':
    case 'reports':
        $title = 'Entry/Stock Data Report';
        $query = "SELECT o.id, u.u_name, o.u_toolname, o.u_itemsnumber, o.u_totalprice, o.u_date 
                  FROM `order` o 
                  LEFT JOIN `user` u ON o.user_id = u.id";
        
        // Add date filter if provided
        if(isset($_GET['start_date']) && isset($_GET['end_date'])){
            $start_date = mysqli_real_escape_string($con, $_GET['start_date']);
            $end_date = mysqli_real_escape_string($con, $_GET['end_date']);
            $query .= " WHERE DATE(o.u_date) BETWEEN '$start_date' AND '$end_date'";
            $title .= ' (' . date('M d, Y', strtotime($start_date)) . ' - ' . date('M d, Y', strtotime($end_date)) . ')';
        }
        
        $query .= " ORDER BY o.u_totalprice DESC";
        $result = mysqli_query($con, $query);
        
        $table_html = '<thead><tr>
            <th>#</th>
            <th>Customer Name</th>
            <th>Tool/Product</th>
            <th>Quantity</th>
            <th>Amount</th>
            <th>Date</th>
        </tr></thead><tbody>';
        
        if($result && mysqli_num_rows($result) > 0){
            while($row = mysqli_fetch_assoc($result)){
                $table_html .= '<tr>
                    <td>' . htmlspecialchars($row['id']) . '</td>
                    <td>' . htmlspecialchars($row['u_name'] ?? 'N/A') . '</td>
                    <td>' . htmlspecialchars($row['u_toolname'] ?? 'N/A') . '</td>
                    <td>' . htmlspecialchars($row['u_itemsnumber'] ?? '0') . '</td>
                    <td>' . number_format($row['u_totalprice'] ?? 0) . ' RWF</td>
                    <td>' . date('M d, Y', strtotime($row['u_date'] ?? 'now')) . '</td>
                </tr>';
            }
        } else {
            $table_html .= '<tr><td colspan="6" style="text-align: center;">No data found</td></tr>';
        }
        $table_html .= '</tbody>';
        break;
        
    default:
        $title = 'BAFRACOO Report';
        $table_html = '<thead><tr><th>Error</th></tr></thead><tbody><tr><td>Invalid export type</td></tr></tbody>';
}

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
    