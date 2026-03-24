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
  $current_page = 'sales-report';

  // ── Date & status filters ──
  $where_clauses = [];
  $filter_start = isset($_GET['start_date']) ? mysqli_real_escape_string($con, $_GET['start_date']) : '';
  $filter_end   = isset($_GET['end_date'])   ? mysqli_real_escape_string($con, $_GET['end_date'])   : '';
  $filter_status = isset($_GET['status'])     ? mysqli_real_escape_string($con, $_GET['status'])     : '';

  if ($filter_start && $filter_end) {
    $where_clauses[] = "DATE(COALESCE(o.payment_date, o.u_date)) BETWEEN '$filter_start' AND '$filter_end'";
  }
  if ($filter_status) {
    $where_clauses[] = "o.status = '$filter_status'";
  }
  $where_sql = count($where_clauses) ? ' WHERE ' . implode(' AND ', $where_clauses) : '';

  // ── Main query (same cost logic as orders.php) ──
  $sql = "SELECT o.*, u.u_name, u.u_email,
            t.purchase_price AS tool_purchase_price,
            COALESCE(oi_agg.total_sale_amount, 0) AS oi_total_sale,
            COALESCE(oi_agg.total_qty, 0) AS oi_total_qty,
            oi_agg.item_names AS cart_item_names,
            COALESCE(batch_agg.total_purchase_cost, 0) AS batch_total_cost,
            COALESCE(batch_agg.total_batch_qty, 0) AS batch_total_qty,
            COALESCE(tool_cost_agg.total_tool_purchase_cost, 0) AS tool_fallback_cost,
            COALESCE(tool_cost_agg.total_tool_qty, 0) AS tool_fallback_qty,
            COALESCE(o.refunded_amount, 0) AS refund_amt,
            o.refund_status,
            COALESCE(o.payment_date, o.u_date) AS transaction_date
          FROM `order` o
          INNER JOIN user u ON o.user_id = u.id
          LEFT JOIN tool t ON o.tool_id = t.id
          LEFT JOIN (
            SELECT order_id,
              SUM(total_price) AS total_sale_amount,
              SUM(quantity)    AS total_qty,
              GROUP_CONCAT(DISTINCT tool_name ORDER BY tool_name SEPARATOR ', ') AS item_names
            FROM order_items GROUP BY order_id
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
              SUM(COALESCE(t2.purchase_price,0) * oi2.quantity) AS total_tool_purchase_cost,
              SUM(oi2.quantity) AS total_tool_qty
            FROM order_items oi2
            LEFT JOIN tool t2 ON oi2.tool_id = t2.id
            GROUP BY oi2.order_id
          ) tool_cost_agg ON o.id = tool_cost_agg.order_id
          $where_sql
          ORDER BY o.id DESC";

  $result = mysqli_query($con, $sql);

  // ── Build rows array + grand totals ──
  $rows_data = [];
  $grand_total_purchase = 0;
  $grand_total_sale     = 0;
  $grand_total_profit   = 0;
  $grand_total_refunds  = 0;
  $grand_total_net      = 0;
  $total_orders         = 0;
  $orders_with_cost     = 0;

  if ($result) {
    while ($r = mysqli_fetch_assoc($result)) {
      $is_cart = empty($r['tool_id']);

      if ($is_cart) {
        $total_customer_paid = ($r['oi_total_sale'] > 0) ? floatval($r['oi_total_sale']) : floatval($r['u_totalprice']);
        $actual_qty = ($r['oi_total_qty'] > 0) ? intval($r['oi_total_qty']) : intval($r['u_itemsnumber']);
        $sale_price_unit = ($actual_qty > 0) ? $total_customer_paid / $actual_qty : 0;

        if ($r['batch_total_cost'] > 0) {
          $total_purchase_cost = floatval($r['batch_total_cost']);
          $purchase_price_unit = ($r['batch_total_qty'] > 0) ? $total_purchase_cost / floatval($r['batch_total_qty']) : 0;
        } elseif (floatval($r['tool_fallback_cost']) > 0) {
          $total_purchase_cost = floatval($r['tool_fallback_cost']);
          $fallback_qty = (floatval($r['tool_fallback_qty']) > 0) ? floatval($r['tool_fallback_qty']) : $actual_qty;
          $purchase_price_unit = ($fallback_qty > 0) ? $total_purchase_cost / $fallback_qty : 0;
        } else {
          $purchase_price_unit = null;
          $total_purchase_cost = null;
        }
      } else {
        $sale_price_unit     = floatval($r['u_price']);
        $actual_qty          = intval($r['u_itemsnumber']);
        $total_customer_paid = floatval($r['u_totalprice']);

        if ($r['tool_purchase_price'] !== null && floatval($r['tool_purchase_price']) > 0) {
          $purchase_price_unit = floatval($r['tool_purchase_price']);
          $total_purchase_cost = $purchase_price_unit * $actual_qty;
        } else {
          $purchase_price_unit = null;
          $total_purchase_cost = null;
        }
      }

      $gross_profit   = ($total_purchase_cost !== null) ? ($total_customer_paid - $total_purchase_cost) : null;
      $refund_amt     = floatval($r['refund_amt']);
      $net_revenue    = $total_customer_paid - $refund_amt;
      $net_profit     = ($gross_profit !== null) ? ($gross_profit - $refund_amt) : null;
      $margin_pct     = ($total_customer_paid > 0 && $gross_profit !== null) ? round(($gross_profit / $total_customer_paid) * 100, 1) : null;

      $product_name = $is_cart && !empty($r['cart_item_names']) ? $r['cart_item_names'] : $r['u_toolname'];

      $rows_data[] = [
        'id'                  => $r['id'],
        'customer_name'       => $r['u_name'],
        'customer_email'      => $r['u_email'],
        'product_name'        => $product_name,
        'type'                => $r['u_type'],
        'quantity'            => $actual_qty,
        'purchase_price_unit' => $purchase_price_unit,
        'sale_price_unit'     => $sale_price_unit,
        'total_purchase_cost' => $total_purchase_cost,
        'total_customer_paid' => $total_customer_paid,
        'gross_profit'        => $gross_profit,
        'refund_amt'          => $refund_amt,
        'refund_status'       => $r['refund_status'],
        'net_revenue'         => $net_revenue,
        'net_profit'          => $net_profit,
        'margin_pct'          => $margin_pct,
        'date'                => $r['transaction_date'],
        'status'              => $r['status'],
        'payment_date'        => $r['payment_date'],
        'stripe_pi'           => $r['stripe_payment_intent'],
      ];

      $total_orders++;
      $grand_total_sale    += $total_customer_paid;
      $grand_total_refunds += $refund_amt;
      if ($total_purchase_cost !== null) {
        $grand_total_purchase += $total_purchase_cost;
        $grand_total_profit   += $gross_profit;
        $orders_with_cost++;
      }
      $grand_total_net += ($net_profit !== null ? $net_profit : 0);
    }
  }

  $grand_margin = ($grand_total_sale > 0) ? round(($grand_total_profit / $grand_total_sale) * 100, 1) : 0;
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
  <title>BAFRACOO - Sales Report</title>
  <style>
    .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: var(--spacing-lg); margin-bottom: var(--spacing-xl); }
    .summary-card { background: white; border-radius: var(--radius-lg); padding: var(--spacing-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--gray-200); }
    .summary-card .label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--gray-500); font-weight: 600; margin-bottom: var(--spacing-xs); }
    .summary-card .value { font-size: 1.6rem; font-weight: 700; color: var(--gray-900); }
    .summary-card .sub { font-size: 0.75rem; color: var(--gray-500); margin-top: var(--spacing-xs); }
    .filter-bar { display: flex; flex-wrap: wrap; gap: var(--spacing-md); align-items: flex-end; margin-bottom: var(--spacing-xl); background: white; padding: var(--spacing-lg); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--gray-200); }
    .filter-bar .fg { display: flex; flex-direction: column; gap: 4px; }
    .filter-bar label { font-size: 0.8rem; font-weight: 600; color: var(--gray-600); }
    .filter-bar input, .filter-bar select { padding: 8px 12px; border: 1px solid var(--gray-300); border-radius: var(--radius-md); font-size: 0.875rem; }
    /* Force horizontal scrollbar to always be visible on the sales table */
    .sales-table-scroll {
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }
    .sales-table-scroll::-webkit-scrollbar {
      height: 10px;
    }
    .sales-table-scroll::-webkit-scrollbar-track {
      background: #f1f5f9;
      border-radius: 8px;
    }
    .sales-table-scroll::-webkit-scrollbar-thumb {
      background: #94a3b8;
      border-radius: 8px;
      border: 2px solid #f1f5f9;
    }
    .sales-table-scroll::-webkit-scrollbar-thumb:hover {
      background: #64748b;
    }
    /* Firefox visible scrollbar */
    .sales-table-scroll {
      scrollbar-width: auto;
      scrollbar-color: #94a3b8 #f1f5f9;
    }
    #salesTable {
      min-width: 1400px;
    }
    .profit-pos { color: #10b981; font-weight: 600; }
    .profit-neg { color: #ef4444; font-weight: 600; }
    .badge-refund { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 0.7rem; font-weight: 600; }
    .badge-refund.none   { background: #f3f4f6; color: #6b7280; }
    .badge-refund.partial { background: #fef3c7; color: #92400e; }
    .badge-refund.full   { background: #fee2e2; color: #991b1b; }
    .badge-refund.pending{ background: #e0e7ff; color: #3730a3; }
    .detail-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.45); z-index: 1000; justify-content: center; align-items: center; }
    .detail-modal-overlay.active { display: flex; }
    .detail-modal { background: white; border-radius: var(--radius-lg); width: 90%; max-width: 720px; max-height: 85vh; overflow-y: auto; padding: var(--spacing-xl); box-shadow: var(--shadow-lg); }
    .detail-modal h2 { margin: 0 0 var(--spacing-lg) 0; font-size: 1.25rem; color: var(--gray-900); display: flex; align-items: center; gap: 8px; }
    .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-md); }
    .detail-grid .dg-item { padding: var(--spacing-sm) 0; border-bottom: 1px solid var(--gray-100); }
    .detail-grid .dg-label { font-size: 0.75rem; text-transform: uppercase; color: var(--gray-500); font-weight: 600; }
    .detail-grid .dg-value { font-size: 0.95rem; font-weight: 500; color: var(--gray-900); margin-top: 2px; }
    @media (max-width: 640px) { .detail-grid { grid-template-columns: 1fr; } }
  </style>
</head>
<body>
  <div class="dashboard-container">
    <?php include 'includes/admin_sidebar.php'; ?>
    <div class="sidebar-overlay"></div>

    <main class="main-content">
      <header class="header">
        <div class="header-left">
          <button class="mobile-menu-btn"><ion-icon name="menu-outline"></ion-icon></button>
          <button class="sidebar-toggle"><ion-icon name="chevron-back-outline"></ion-icon></button>
        </div>
      </header>

      <!-- Page Banner -->
      <div class="page-banner">
        <h1 class="page-banner-title">
          <ion-icon name="stats-chart-outline"></ion-icon>
          Sales Report
        </h1>
        <p class="page-banner-subtitle">Detailed financial breakdown per order — purchase cost, revenue, profit &amp; refunds</p>
      </div>

      <div class="content-area">

        <!-- Filter Bar -->
        <form method="GET" class="filter-bar">
          <div class="fg">
            <label>Start Date</label>
            <input type="date" name="start_date" value="<?php echo htmlspecialchars($filter_start); ?>">
          </div>
          <div class="fg">
            <label>End Date</label>
            <input type="date" name="end_date" value="<?php echo htmlspecialchars($filter_end); ?>">
          </div>
          <div class="fg">
            <label>Status</label>
            <select name="status">
              <option value="">All Statuses</option>
              <?php foreach(['Pending','Pending Payment','Paid','Completed','Payment Cancelled','Refunded','Payment Failed'] as $s): ?>
                <option value="<?php echo $s; ?>" <?php echo ($filter_status === $s) ? 'selected' : ''; ?>><?php echo $s; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="btn-primary" style="padding: 8px 20px; border-radius: var(--radius-md); border: none; background: var(--primary-color); color: white; font-weight: 600; cursor: pointer;">
            <ion-icon name="filter-outline" style="margin-right: 4px;"></ion-icon> Apply
          </button>
          <?php if ($filter_start || $filter_end || $filter_status): ?>
            <a href="sales-report.php" style="padding: 8px 16px; border-radius: var(--radius-md); background: var(--gray-200); color: var(--gray-700); text-decoration: none; font-weight: 500; font-size: 0.875rem;">Clear</a>
          <?php endif; ?>
          <button type="button" onclick="exportSalesReportPDF()" class="btn-secondary" style="margin-left: auto; padding: 8px 20px; border-radius: var(--radius-md); border: 1px solid var(--gray-300); background: white; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 6px;">
            <ion-icon name="download-outline"></ion-icon> Export PDF
          </button>
        </form>

        <!-- Summary Cards -->
        <div class="summary-grid">
          <div class="summary-card">
            <div class="label">Total Orders</div>
            <div class="value"><?php echo number_format($total_orders); ?></div>
            <div class="sub"><?php echo $orders_with_cost; ?> with cost data</div>
          </div>
          <div class="summary-card">
            <div class="label">Total Revenue (Sales)</div>
            <div class="value"><?php echo number_format($grand_total_sale); ?> <small style="font-size:.7rem;">RWF</small></div>
            <div class="sub">Total amount customers paid</div>
          </div>
          <div class="summary-card">
            <div class="label">Total Purchase Cost</div>
            <div class="value"><?php echo number_format($grand_total_purchase); ?> <small style="font-size:.7rem;">RWF</small></div>
            <div class="sub">Cost of goods sold</div>
          </div>
          <div class="summary-card">
            <div class="label">Gross Profit</div>
            <div class="value" style="color: <?php echo ($grand_total_profit >= 0) ? '#10b981' : '#ef4444'; ?>;">
              <?php echo number_format($grand_total_profit); ?> <small style="font-size:.7rem;">RWF</small>
            </div>
            <div class="sub">Margin: <?php echo $grand_margin; ?>%</div>
          </div>
          <div class="summary-card">
            <div class="label">Total Refunds</div>
            <div class="value" style="color: #ef4444;"><?php echo number_format($grand_total_refunds); ?> <small style="font-size:.7rem;">RWF</small></div>
            <div class="sub">Returned to customers</div>
          </div>
          <div class="summary-card">
            <div class="label">Net Profit</div>
            <div class="value" style="color: <?php echo ($grand_total_net >= 0) ? '#10b981' : '#ef4444'; ?>;">
              <?php echo number_format($grand_total_net); ?> <small style="font-size:.7rem;">RWF</small>
            </div>
            <div class="sub">After refunds &amp; costs</div>
          </div>
        </div>

        <!-- Sales Table -->
        <div class="dashboard-card">
          <div class="card-header">
            <h3 style="font-size: 1.25rem; font-weight: 600; color: var(--gray-900); margin: 0;">
              <ion-icon name="list-outline" style="margin-right: var(--spacing-sm);"></ion-icon>
              Order-Level Sales Breakdown
            </h3>
          </div>

          <div class="table-container sales-table-scroll">
            <table class="modern-table" id="salesTable">
              <thead>
                <tr>
                  <th>Order #</th>
                  <th>Date</th>
                  <th>Customer</th>
                  <th>Product(s)</th>
                  <th>Qty</th>
                  <th>Purchase Price/Unit</th>
                  <th>Sale Price/Unit</th>
                  <th>Total Cost (RWF)</th>
                  <th>Total Revenue (RWF)</th>
                  <th>Gross Profit (RWF)</th>
                  <th>Refund (RWF)</th>
                  <th>Net Profit (RWF)</th>
                  <th>Margin %</th>
                  <th>Status</th>
                  <th>Details</th>
                </tr>
              </thead>
              <tbody>
                <?php if (count($rows_data) > 0): ?>
                  <?php foreach ($rows_data as $rd): ?>
                    <?php
                      $status_class = '';
                      switch(strtolower($rd['status'])) {
                        case 'completed': $status_class = 'status-completed'; break;
                        case 'paid':      $status_class = 'status-completed'; break;
                        case 'pending':   $status_class = 'status-pending'; break;
                        default:          $status_class = 'status-pending';
                      }
                      $refund_badge = 'none';
                      if ($rd['refund_status'] === 'PARTIAL') $refund_badge = 'partial';
                      elseif ($rd['refund_status'] === 'FULL') $refund_badge = 'full';
                      elseif ($rd['refund_status'] === 'PENDING') $refund_badge = 'pending';
                    ?>
                    <tr>
                      <td>#<?php echo str_pad($rd['id'], 5, '0', STR_PAD_LEFT); ?></td>
                      <td><?php echo date('M d, Y H:i', strtotime($rd['date'])); ?></td>
                      <td>
                        <div style="display:flex;align-items:center;gap:6px;">
                          <div style="width:30px;height:30px;border-radius:50%;background:var(--primary-color);display:flex;align-items:center;justify-content:center;color:white;font-weight:600;font-size:.7rem;">
                            <?php echo strtoupper(substr($rd['customer_name'], 0, 2)); ?>
                          </div>
                          <span style="font-weight:500;"><?php echo htmlspecialchars($rd['customer_name']); ?></span>
                        </div>
                      </td>
                      <td style="max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?php echo htmlspecialchars($rd['product_name']); ?>"><?php echo htmlspecialchars($rd['product_name']); ?></td>
                      <td><?php echo number_format($rd['quantity']); ?></td>
                      <td><?php echo ($rd['purchase_price_unit'] !== null) ? number_format($rd['purchase_price_unit']) : '<span style="color:#94a3b8;">N/A</span>'; ?></td>
                      <td><?php echo number_format($rd['sale_price_unit']); ?></td>
                      <td><?php echo ($rd['total_purchase_cost'] !== null) ? number_format($rd['total_purchase_cost']) : '<span style="color:#94a3b8;">N/A</span>'; ?></td>
                      <td><?php echo number_format($rd['total_customer_paid']); ?></td>
                      <td class="<?php echo ($rd['gross_profit'] !== null && $rd['gross_profit'] >= 0) ? 'profit-pos' : (($rd['gross_profit'] !== null) ? 'profit-neg' : ''); ?>">
                        <?php echo ($rd['gross_profit'] !== null) ? number_format($rd['gross_profit']) : '<span style="color:#94a3b8;">N/A</span>'; ?>
                      </td>
                      <td>
                        <?php if ($rd['refund_amt'] > 0): ?>
                          <span style="color:#ef4444;font-weight:600;">-<?php echo number_format($rd['refund_amt']); ?></span>
                        <?php else: ?>
                          <span style="color:#94a3b8;">0</span>
                        <?php endif; ?>
                      </td>
                      <td class="<?php echo ($rd['net_profit'] !== null && $rd['net_profit'] >= 0) ? 'profit-pos' : (($rd['net_profit'] !== null) ? 'profit-neg' : ''); ?>">
                        <?php echo ($rd['net_profit'] !== null) ? number_format($rd['net_profit']) : '<span style="color:#94a3b8;">N/A</span>'; ?>
                      </td>
                      <td><?php echo ($rd['margin_pct'] !== null) ? $rd['margin_pct'] . '%' : '<span style="color:#94a3b8;">—</span>'; ?></td>
                      <td><span class="status-badge <?php echo $status_class; ?>"><?php echo ucfirst($rd['status']); ?></span></td>
                      <td>
                        <button class="btn-icon btn-edit" onclick='showOrderDetail(<?php echo json_encode($rd, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' title="View Details">
                          <ion-icon name="eye-outline"></ion-icon>
                        </button>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="15" style="text-align:center;padding:var(--spacing-xl);color:var(--gray-600);">
                      <ion-icon name="document-outline" style="font-size:3rem;margin-bottom:var(--spacing-md);"></ion-icon>
                      <div>No orders match the current filters.</div>
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
              <?php if (count($rows_data) > 0): ?>
              <tfoot>
                <tr style="background:var(--gray-100);font-weight:700;font-size:.9rem;">
                  <td colspan="7" style="text-align:right;padding:var(--spacing-md);color:var(--gray-900);">Grand Totals:</td>
                  <td style="padding:var(--spacing-md);"><?php echo number_format($grand_total_purchase); ?></td>
                  <td style="padding:var(--spacing-md);"><?php echo number_format($grand_total_sale); ?></td>
                  <td class="<?php echo ($grand_total_profit >= 0) ? 'profit-pos' : 'profit-neg'; ?>" style="padding:var(--spacing-md);"><?php echo number_format($grand_total_profit); ?></td>
                  <td style="padding:var(--spacing-md);color:#ef4444;"><?php echo number_format($grand_total_refunds); ?></td>
                  <td class="<?php echo ($grand_total_net >= 0) ? 'profit-pos' : 'profit-neg'; ?>" style="padding:var(--spacing-md);"><?php echo number_format($grand_total_net); ?></td>
                  <td style="padding:var(--spacing-md);"><?php echo $grand_margin; ?>%</td>
                  <td colspan="2"></td>
                </tr>
              </tfoot>
              <?php endif; ?>
            </table>
          </div>
        </div>
      </div><!-- /content-area -->
    </main>
  </div>

  <!-- Order Detail Modal -->
  <div class="detail-modal-overlay" id="detailOverlay">
    <div class="detail-modal">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:var(--spacing-lg);">
        <h2><ion-icon name="receipt-outline"></ion-icon> <span id="dm-title">Order Details</span></h2>
        <button onclick="closeDetail()" style="background:none;border:none;font-size:1.5rem;cursor:pointer;color:var(--gray-500);">&times;</button>
      </div>
      <div class="detail-grid" id="dm-body"></div>
      <div style="margin-top:var(--spacing-lg);text-align:right;">
        <button onclick="closeDetail()" style="padding:8px 20px;border-radius:var(--radius-md);border:1px solid var(--gray-300);background:var(--gray-100);cursor:pointer;font-weight:500;">Close</button>
      </div>
    </div>
  </div>

  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>

  <script>
    function fmt(n){ return n !== null && n !== undefined ? Number(n).toLocaleString() + ' RWF' : 'N/A'; }

    function showOrderDetail(d) {
      document.getElementById('dm-title').textContent = 'Order #' + String(d.id).padStart(5,'0');
      var html = '';
      var items = [
        ['Order ID',            '#' + String(d.id).padStart(5,'0')],
        ['Order Date',          d.date ? new Date(d.date).toLocaleString('en-US',{year:'numeric',month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'}) : '—'],
        ['Customer Name',       d.customer_name || '—'],
        ['Customer Email',      d.customer_email || '—'],
        ['Product(s)',          d.product_name || '—'],
        ['Category/Type',       d.type || '—'],
        ['Quantity',            Number(d.quantity).toLocaleString()],
        ['Purchase Price/Unit', fmt(d.purchase_price_unit)],
        ['Sale Price/Unit',     fmt(d.sale_price_unit)],
        ['Total Purchase Cost', fmt(d.total_purchase_cost)],
        ['Total Customer Paid', fmt(d.total_customer_paid)],
        ['Gross Profit',        fmt(d.gross_profit)],
        ['Refund Amount',       fmt(d.refund_amt)],
        ['Refund Status',       d.refund_status || 'NONE'],
        ['Net Revenue',         fmt(d.net_revenue)],
        ['Net Profit',          fmt(d.net_profit)],
        ['Profit Margin',       d.margin_pct !== null ? d.margin_pct + '%' : '—'],
        ['Order Status',        d.status],
        ['Payment Date',        d.payment_date || '—'],
        ['Stripe Payment ID',   d.stripe_pi || '—'],
      ];
      items.forEach(function(pair){
        html += '<div class="dg-item"><div class="dg-label">' + pair[0] + '</div><div class="dg-value">' + pair[1] + '</div></div>';
      });
      document.getElementById('dm-body').innerHTML = html;
      document.getElementById('detailOverlay').classList.add('active');
    }

    function closeDetail() {
      document.getElementById('detailOverlay').classList.remove('active');
    }

    document.getElementById('detailOverlay').addEventListener('click', function(e){
      if(e.target === this) closeDetail();
    });

    function exportSalesReportPDF() {
      var params = new URLSearchParams(window.location.search);
      var url = 'export_pdf.php?type=sales_report';
      if (params.get('start_date')) url += '&start_date=' + params.get('start_date');
      if (params.get('end_date'))   url += '&end_date='   + params.get('end_date');
      if (params.get('status'))     url += '&status='      + params.get('status');
      window.open(url, '_blank');
    }

    // Dashboard initialisation
    document.addEventListener('DOMContentLoaded', function() {
      if (typeof window.Dashboard !== 'undefined') { new window.Dashboard(); }
    });
  </script>
</body>
</html>
