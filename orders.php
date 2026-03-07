<?php
  require "connection.php";
  if (session_status() === PHP_SESSION_NONE) {
    session_start();
  }
  if(!isset($_SESSION["id"])){
    header('location:loginadmin.php');
    exit();
  }
  $id = $_SESSION["id"];
  $check = mysqli_query($con,"SELECT * FROM `admin` WHERE id=$id ");
  if(!$check || mysqli_num_rows($check) == 0){
    header('location:loginadmin.php');
    exit();
  }
  $row = mysqli_fetch_array($check);
  error_reporting(0);
  $current_page = 'orders';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="./CSS/modern-dashboard.css">
  <link rel="stylesheet" href="./CSS/modern-tables.css">
  <link rel="stylesheet" href="./CSS/enhanced-pages.css">
  <link rel="shortcut icon" href="./images/Capture.JPG" type="image/x-icon">
  <script src="https://kit.fontawesome.com/14ff3ea278.js" crossorigin="anonymous"></script>
  <title>BAFRACOO - Orders</title>
  <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
  <!-- <script src="./JS/file.js"></script> -->
</head>
<body>
  <div class="dashboard-container">
    <!-- Sidebar -->
    <?php include 'includes/admin_sidebar.php'; ?>

    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay"></div>

    <!-- Main Content -->
    <main class="main-content">
      <header class="header">
        <div class="header-left">
          <button class="mobile-menu-btn">
            <ion-icon name="menu-outline"></ion-icon>
          </button>
          <button class="sidebar-toggle">
            <ion-icon name="chevron-back-outline"></ion-icon>
          </button>
        </div>
      </header>
      
      <!-- Page Banner -->
      <div class="page-banner">
        <h1 class="page-banner-title">
          <ion-icon name="bag-handle-outline"></ion-icon>
          Orders Management
        </h1>
        <p class="page-banner-subtitle">View, manage, and track all customer orders</p>
      </div>

      <div class="content-area">
        <!-- Orders Summary Cards -->
        <div class="dashboard-grid" style="margin-bottom: var(--spacing-xl);">
          <div class="dashboard-card">
            <div class="card-content">
              <div class="card-icon" style="background: var(--primary-color);">
                <ion-icon name="bag-handle-outline"></ion-icon>
              </div>
              <div class="card-info">
                <h3 style="margin: 0 0 var(--spacing-sm) 0; color: var(--gray-600); font-size: 0.875rem; font-weight: 500;">TOTAL ORDERS</h3>
                <div style="font-size: 2rem; font-weight: 700; color: var(--gray-900); margin-bottom: var(--spacing-sm);">
                  <?php
                    $total_orders = mysqli_query($con, "SELECT COUNT(*) as count FROM `order`");
                    echo $total_orders ? mysqli_fetch_assoc($total_orders)['count'] : '0';
                  ?>
                </div>
                <div style="font-size: 0.75rem; color: var(--success-color); font-weight: 500;">
                  <ion-icon name="trending-up-outline" style="margin-right: 4px;"></ion-icon>
                  +12% from last month
                </div>
              </div>
            </div>
          </div>

          <div class="dashboard-card">
            <div class="card-content">
              <div class="card-icon" style="background: var(--warning-color);">
                <ion-icon name="time-outline"></ion-icon>
              </div>
              <div class="card-info">
                <h3 style="margin: 0 0 var(--spacing-sm) 0; color: var(--gray-600); font-size: 0.875rem; font-weight: 500;">PENDING ORDERS</h3>
                <div style="font-size: 2rem; font-weight: 700; color: var(--gray-900); margin-bottom: var(--spacing-sm);">
                  <?php
                    $pending_orders = mysqli_query($con, "SELECT COUNT(*) as count FROM `order` WHERE status='Pending'");
                    echo $pending_orders ? mysqli_fetch_assoc($pending_orders)['count'] : '0';
                  ?>
                </div>
                <div style="font-size: 0.75rem; color: var(--warning-color); font-weight: 500;">
                  <ion-icon name="alert-circle-outline" style="margin-right: 4px;"></ion-icon>
                  Needs attention
                </div>
              </div>
            </div>
          </div>

          <div class="dashboard-card">
            <div class="card-content">
              <div class="card-icon" style="background: var(--success-color);">
                <ion-icon name="checkmark-circle-outline"></ion-icon>
              </div>
              <div class="card-info">
                <h3 style="margin: 0 0 var(--spacing-sm) 0; color: var(--gray-600); font-size: 0.875rem; font-weight: 500;">COMPLETED ORDERS</h3>
                <div style="font-size: 2rem; font-weight: 700; color: var(--gray-900); margin-bottom: var(--spacing-sm);">
                  <?php
                    $completed_orders = mysqli_query($con, "SELECT COUNT(*) as count FROM `order` WHERE status='Completed'");
                    echo $completed_orders ? mysqli_fetch_assoc($completed_orders)['count'] : '0';
                  ?>
                </div>
                <div style="font-size: 0.75rem; color: var(--success-color); font-weight: 500;">
                  <ion-icon name="trending-up-outline" style="margin-right: 4px;"></ion-icon>
                  +8% this week
                </div>
              </div>
            </div>
          </div>

          <div class="dashboard-card">
            <div class="card-content">
              <div class="card-icon" style="background: var(--info-color);">
                <ion-icon name="cash-outline"></ion-icon>
              </div>
              <div class="card-info">
                <h3 style="margin: 0 0 var(--spacing-sm) 0; color: var(--gray-600); font-size: 0.875rem; font-weight: 500;">TOTAL REVENUE</h3>
                <div style="font-size: 2rem; font-weight: 700; color: var(--gray-900); margin-bottom: var(--spacing-sm);">
                  <?php
                    $total_revenue = mysqli_query($con, "SELECT SUM(u_totalprice) as total FROM `order` WHERE status IN ('Completed','Paid')");
                    $revenue = $total_revenue ? mysqli_fetch_assoc($total_revenue)['total'] ?? 0 : 0;
                    echo number_format($revenue) . ' RWF';
                  ?>
                </div>
                <div style="font-size: 0.75rem; color: var(--success-color); font-weight: 500;">
                  <ion-icon name="trending-up-outline" style="margin-right: 4px;"></ion-icon>
                  +15% this month
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Orders Table -->
        <div class="dashboard-card">
          <div class="card-header">
            <h3 style="font-size: 1.25rem; font-weight: 600; color: var(--gray-900); margin: 0;">
              <ion-icon name="list-outline" style="margin-right: var(--spacing-sm);"></ion-icon>
              All Orders
            </h3>
            <div style="display: flex; gap: var(--spacing-md);">
              <button class="btn-secondary" onclick="openDateFilterModal()" style="width:20vh;height:5vh; border-radius:15px;">
                <ion-icon name="filter-outline"></ion-icon>
                Filter by Date
              </button>
              <button class="btn-secondary" onclick="exportOrdersPDF()" style="width:20vh;height:5vh; border-radius:15px;">
                <ion-icon name="download-outline"></ion-icon>
                Export PDF
              </button>
            </div>
          </div>

          <div class="table-container">
            <table class="modern-table">
              <thead>
                <tr>
                  <th>Order ID</th>
                  <th>Customer</th>
                  <th>Product</th>
                  <th>Type</th>
                  <th>Quantity</th>
                  <th>Description</th>
                  <th>Purchase Price (RWF)</th>
                  <th>Sale Price (RWF)</th>
                  <th>Total Purchase Value</th>
                  <th>Total Customer Paid</th>
                  <th>Total Sale Value (Profit)</th>
                  <th>Date</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
            <?php
             // Build SQL query with optional date filtering
             // Joins: tool (for purchase_price), order_items aggregate (for cart orders), order_item_batches aggregate (for FIFO cost)
             $sql = "SELECT o.*, u.u_name,
                t.purchase_price AS tool_purchase_price,
                COALESCE(oi_agg.total_sale_amount, 0) AS oi_total_sale,
                COALESCE(oi_agg.total_qty, 0) AS oi_total_qty,
                COALESCE(batch_agg.total_purchase_cost, 0) AS batch_total_cost,
                COALESCE(batch_agg.total_batch_qty, 0) AS batch_total_qty,
                COALESCE(tool_cost_agg.total_tool_purchase_cost, 0) AS tool_fallback_cost,
                COALESCE(tool_cost_agg.total_tool_qty, 0) AS tool_fallback_qty
              FROM `order` o
              INNER JOIN user u ON o.user_id = u.id
              LEFT JOIN tool t ON o.tool_id = t.id
              LEFT JOIN (
                SELECT order_id, 
                  SUM(total_price) AS total_sale_amount,
                  SUM(quantity) AS total_qty
                FROM order_items 
                GROUP BY order_id
              ) oi_agg ON o.id = oi_agg.order_id
              LEFT JOIN (
                SELECT oi.order_id,
                  SUM(oib.purchase_price * oib.quantity_from_batch) AS total_purchase_cost,
                  SUM(oib.quantity_from_batch) AS total_batch_qty
                FROM order_item_batches oib
                INNER JOIN order_items oi ON oib.order_item_id = oi.id
                GROUP BY oi.order_id
              ) batch_agg ON o.id = batch_agg.order_id
              LEFT JOIN (
                SELECT oi2.order_id,
                  SUM(COALESCE(t2.purchase_price, 0) * oi2.quantity) AS total_tool_purchase_cost,
                  SUM(oi2.quantity) AS total_tool_qty
                FROM order_items oi2
                LEFT JOIN tool t2 ON oi2.tool_id = t2.id
                GROUP BY oi2.order_id
              ) tool_cost_agg ON o.id = tool_cost_agg.order_id";
             
             // Add date filter if provided
             if(isset($_GET['start_date']) && isset($_GET['end_date'])){
               $start_date = mysqli_real_escape_string($con, $_GET['start_date']);
               $end_date = mysqli_real_escape_string($con, $_GET['end_date']);
               $sql .= " WHERE DATE(o.u_date) BETWEEN '$start_date' AND '$end_date'";
             }
             
             $sql .= " ORDER BY o.id DESC";
             $result = mysqli_query($con, $sql);
                // Running grand totals
                $grand_total_purchase = 0;
                $grand_total_sale = 0;
                $grand_total_profit = 0;
                $orders_with_cost_data = 0;

                if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_array($result)) {
                  $status_class = '';
                  $status_text = $row['status'] ?? 'Pending';
                  switch(strtolower($status_text)) {
                    case 'completed':
                      $status_class = 'status-completed';
                      break;
                    case 'pending':
                      $status_class = 'status-pending';
                      break;
                    case 'processing':
                      $status_class = 'status-processing';
                      break;
                    default:
                      $status_class = 'status-pending';
                  }
            ?>
                <?php
                  // === Compute purchase price, sale price, and totals ===
                  $is_cart = empty($row['tool_id']);
                  
                  if ($is_cart) {
                    // Cart order: prices come from order_items & order_item_batches
                    $total_customer_paid = ($row['oi_total_sale'] > 0) ? floatval($row['oi_total_sale']) : floatval($row['u_totalprice']);
                    $actual_qty = ($row['oi_total_qty'] > 0) ? intval($row['oi_total_qty']) : intval($row['u_itemsnumber']);
                    $sale_price_unit = ($actual_qty > 0) ? $total_customer_paid / $actual_qty : 0;
                    
                    // Purchase cost: prefer order_item_batches (FIFO), fallback to tool table
                    if ($row['batch_total_cost'] > 0) {
                      $total_purchase_cost = floatval($row['batch_total_cost']);
                      $purchase_price_unit = ($row['batch_total_qty'] > 0) ? $total_purchase_cost / floatval($row['batch_total_qty']) : 0;
                    } elseif (floatval($row['tool_fallback_cost']) > 0) {
                      // Fallback: get purchase price from tool table via order_items
                      $total_purchase_cost = floatval($row['tool_fallback_cost']);
                      $fallback_qty = (floatval($row['tool_fallback_qty']) > 0) ? floatval($row['tool_fallback_qty']) : $actual_qty;
                      $purchase_price_unit = ($fallback_qty > 0) ? $total_purchase_cost / $fallback_qty : 0;
                    } else {
                      // No purchase cost data at all
                      $purchase_price_unit = null;
                      $total_purchase_cost = null;
                    }
                  } else {
                    // Regular order: sale price from order, purchase from tool table
                    $sale_price_unit = floatval($row['u_price']);
                    $actual_qty = intval($row['u_itemsnumber']);
                    $total_customer_paid = floatval($row['u_totalprice']);
                    
                    if ($row['tool_purchase_price'] !== null && floatval($row['tool_purchase_price']) > 0) {
                      $purchase_price_unit = floatval($row['tool_purchase_price']);
                      $total_purchase_cost = $purchase_price_unit * $actual_qty;
                    } else {
                      $purchase_price_unit = null;
                      $total_purchase_cost = null;
                    }
                  }
                  
                  // Profit = Total Customer Paid - Total Purchase Cost
                  $profit = ($total_purchase_cost !== null) ? ($total_customer_paid - $total_purchase_cost) : null;
                  
                  // Accumulate grand totals
                  $grand_total_sale += $total_customer_paid;
                  if ($total_purchase_cost !== null) {
                    $grand_total_purchase += $total_purchase_cost;
                    $grand_total_profit += $profit;
                    $orders_with_cost_data++;
                  }
                ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td>
                      <div style="display: flex; align-items: center; gap: var(--spacing-sm);">
                        <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--primary-color); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 0.75rem;">
                          <?php echo strtoupper(substr($row['u_name'], 0, 2)); ?>
                        </div>
                        <div>
                          <div style="font-weight: 500;"><?php echo htmlspecialchars($row['u_name']); ?></div>
                        </div>
                      </div>
                    </td>
                    <td><?php echo htmlspecialchars($row['u_toolname']); ?></td>
                    <td><?php echo htmlspecialchars($row['u_type']); ?></td>
                    <td><?php echo number_format($actual_qty); ?></td>
                    <td><?php echo htmlspecialchars($row['u_tooldescription']); ?></td>
                    <td><?php echo ($purchase_price_unit !== null) ? number_format($purchase_price_unit) . ' RWF' : '<span style="color:#94a3b8;">N/A</span>'; ?></td>
                    <td><?php echo number_format($sale_price_unit) . ' RWF'; ?></td>
                    <td><?php echo ($total_purchase_cost !== null) ? number_format($total_purchase_cost) . ' RWF' : '<span style="color:#94a3b8;">N/A</span>'; ?></td>
                    <td><?php echo number_format($total_customer_paid) . ' RWF'; ?></td>
                    <td style="<?php echo ($profit !== null && $profit > 0) ? 'color: #10b981; font-weight: 600;' : (($profit !== null && $profit < 0) ? 'color: #ef4444; font-weight: 600;' : ''); ?>">
                      <?php echo ($profit !== null) ? number_format($profit) . ' RWF' : '<span style="color:#94a3b8;">N/A</span>'; ?>
                    </td>
                    <td><?php echo date('M d, Y', strtotime($row['u_date'])); ?></td>
                    <td>
                      <span class="status-badge <?php echo $status_class; ?>">
                        <?php echo ucfirst($status_text); ?>
                      </span>
                    </td>
                    <td>
                      <div class="action-buttons">
                        <button class="btn-icon btn-edit" onclick="editOrder(<?php echo $row['id']; ?>)">
                          <ion-icon name="create-outline"></ion-icon>
                        </button>
                        <button class="btn-icon btn-delete" onclick="confirmDelete(<?php echo $row['id']; ?>)">
                          <ion-icon name="trash-outline"></ion-icon>
                        </button>
                      </div>
                    </td>
                </tr>
              <?php
                }
              } else {
              ?>
                <tr>
                  <td colspan="14" style="text-align: center; padding: var(--spacing-xl); color: var(--gray-600);">
                    <ion-icon name="document-outline" style="font-size: 3rem; margin-bottom: var(--spacing-md);"></ion-icon>
                    <div>No orders found yet. Orders will appear here once customers start placing them.</div>
                  </td>
                </tr>
              <?php
              }
              ?>
              </tbody>
              <tfoot>
                <tr style="background: var(--gray-100); font-weight: 700; font-size: 0.95rem;">
                  <td colspan="8" style="text-align: right; padding: var(--spacing-md); color: var(--gray-900);">Grand Totals:</td>
                  <td style="padding: var(--spacing-md); color: var(--gray-900);"><?php echo number_format($grand_total_purchase) . ' RWF'; ?></td>
                  <td style="padding: var(--spacing-md); color: var(--gray-900);"><?php echo number_format($grand_total_sale) . ' RWF'; ?></td>
                  <td style="padding: var(--spacing-md); font-weight: 700; <?php echo ($grand_total_profit > 0) ? 'color: #10b981;' : (($grand_total_profit < 0) ? 'color: #ef4444;' : 'color: var(--gray-900);'); ?>">
                    <?php echo number_format($grand_total_profit) . ' RWF'; ?>
                  </td>
                  <td colspan="3"></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <!-- Financial Summary Cards -->
        <div class="dashboard-grid" style="margin-top: var(--spacing-xl); grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
          <div class="dashboard-card">
            <div class="card-content">
              <div class="card-icon" style="background: #6366f1;">
                <ion-icon name="cart-outline"></ion-icon>
              </div>
              <div class="card-info">
                <h3 style="margin: 0 0 var(--spacing-sm) 0; color: var(--gray-600); font-size: 0.875rem; font-weight: 500;">TOTAL PURCHASE COST</h3>
                <div style="font-size: 1.75rem; font-weight: 700; color: var(--gray-900); margin-bottom: var(--spacing-sm);">
                  <?php echo number_format($grand_total_purchase) . ' RWF'; ?>
                </div>
                <div style="font-size: 0.75rem; color: var(--gray-500); font-weight: 500;">
                  <ion-icon name="information-circle-outline" style="margin-right: 4px;"></ion-icon>
                  Cost of goods for <?php echo $orders_with_cost_data; ?> order(s) with cost data
                </div>
              </div>
            </div>
          </div>

          <div class="dashboard-card">
            <div class="card-content">
              <div class="card-icon" style="background: #0ea5e9;">
                <ion-icon name="cash-outline"></ion-icon>
              </div>
              <div class="card-info">
                <h3 style="margin: 0 0 var(--spacing-sm) 0; color: var(--gray-600); font-size: 0.875rem; font-weight: 500;">TOTAL SALES VALUE</h3>
                <div style="font-size: 1.75rem; font-weight: 700; color: var(--gray-900); margin-bottom: var(--spacing-sm);">
                  <?php echo number_format($grand_total_sale) . ' RWF'; ?>
                </div>
                <div style="font-size: 0.75rem; color: var(--gray-500); font-weight: 500;">
                  <ion-icon name="information-circle-outline" style="margin-right: 4px;"></ion-icon>
                  Total amount customers paid across all orders
                </div>
              </div>
            </div>
          </div>

          <div class="dashboard-card">
            <div class="card-content">
              <div class="card-icon" style="background: <?php echo ($grand_total_profit >= 0) ? '#10b981' : '#ef4444'; ?>;">
                <ion-icon name="trending-up-outline"></ion-icon>
              </div>
              <div class="card-info">
                <h3 style="margin: 0 0 var(--spacing-sm) 0; color: var(--gray-600); font-size: 0.875rem; font-weight: 500;">TOTAL PROFIT (SALE - PURCHASE)</h3>
                <div style="font-size: 1.75rem; font-weight: 700; color: <?php echo ($grand_total_profit >= 0) ? '#10b981' : '#ef4444'; ?>; margin-bottom: var(--spacing-sm);">
                  <?php echo number_format($grand_total_profit) . ' RWF'; ?>
                </div>
                <div style="font-size: 0.75rem; color: var(--gray-500); font-weight: 500;">
                  <?php 
                    $margin = ($grand_total_sale > 0) ? round(($grand_total_profit / $grand_total_sale) * 100, 1) : 0;
                  ?>
                  <ion-icon name="analytics-outline" style="margin-right: 4px;"></ion-icon>
                  Profit margin: <?php echo $margin; ?>%
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
  
  <!-- Date Filter Modal -->
  <div id="dateFilterModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">
          <ion-icon name="calendar-outline"></ion-icon>
          Filter by Date Range
        </h3>
        <button class="modal-close" onclick="closeDateFilterModal()">&times;</button>
      </div>
      <div class="modal-body">
        <form method="GET" action="">
          <div style="display: grid; gap: var(--spacing-md);">
            <div class="form-group">
              <label for="start_date" style="display: block; margin-bottom: var(--spacing-sm); font-weight: 500;">Start Date</label>
              <input type="date" id="start_date" name="start_date" class="form-control" required style="width: 100%; padding: var(--spacing-sm); border: 1px solid var(--gray-300); border-radius: var(--radius-md);">
            </div>
            <div class="form-group">
              <label for="end_date" style="display: block; margin-bottom: var(--spacing-sm); font-weight: 500;">End Date</label>
              <input type="date" id="end_date" name="end_date" class="form-control" required style="width: 100%; padding: var(--spacing-sm); border: 1px solid var(--gray-300); border-radius: var(--radius-md);">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="closeDateFilterModal()">Cancel</button>
            <button type="submit" class="btn-primary">Apply Filter</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    function confirmDelete(orderId) {
      if (confirm('Are you really sure you want to delete this order?')) {
        window.location.href = './delete/deleteorder.php?id=' + orderId;
      }
    }
    
    function editOrder(orderId) {
      window.location.href = 'addtool.php?id=' + orderId;
    }
    function openDateFilterModal() {
      document.getElementById('dateFilterModal').classList.add('active');
    }
    
    function closeDateFilterModal() {
      document.getElementById('dateFilterModal').classList.remove('active');
    }
    
    function exportOrdersPDF() {
      const urlParams = new URLSearchParams(window.location.search);
      const startDate = urlParams.get('start_date');
      const endDate = urlParams.get('end_date');
      
      let exportUrl = 'export_pdf.php?type=orders';
      if (startDate && endDate) {
        exportUrl += '&start_date=' + startDate + '&end_date=' + endDate;
      }
      
      window.open(exportUrl, '_blank');
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
      const modal = document.getElementById('dateFilterModal');
      if (event.target == modal) {
        closeDateFilterModal();
      }
    }
    
    // Initialize dashboard
    document.addEventListener('DOMContentLoaded', function() {
      if (typeof window.Dashboard !== 'undefined') {
        new window.Dashboard();
      }
    });
  </script>
</body>
</html>
