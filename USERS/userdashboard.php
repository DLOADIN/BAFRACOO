<?php
  require "connection.php";
  require "../EnhancedInventoryManager.php"; // Add enhanced inventory manager
  
  if(!empty($_SESSION["id"])){
    $id = $_SESSION["id"];
    $check = mysqli_query($con,"SELECT * FROM `user` WHERE id=$id ");
    $row = mysqli_fetch_array($check);
  }
  else{
    header('location:loginuser.php');
  } 
  
  // Initialize Enhanced Inventory Manager
  $inventoryManager = new EnhancedInventoryManager($con);
  
  // Get user statistics
  $user_orders = mysqli_query($con, "SELECT COUNT(*) as order_count, SUM(u_totalprice) as total_spent FROM `order` WHERE user_id = $id");
  $user_stats = mysqli_fetch_array($user_orders);
  
  // Get available locations
  $locations = $inventoryManager->getAllLocations();
  $location_count = mysqli_num_rows($locations);
  
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
  <title>BAFRACOO - User Dashboard</title>
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
              <a href="userdashboard.php" class="nav-link active">
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
                <?php 
                $user_orders = mysqli_query($con,"SELECT * FROM `order` WHERE user_id='$id' AND status='Pending'");
                $order_count = $user_orders ? mysqli_num_rows($user_orders) : 0;
                if($order_count > 0): ?>
                  <span class="nav-badge"><?php echo $order_count; ?></span>
                <?php endif; ?>
              </a>
            </li>
            <!-- <li class="nav-item">
              <a href="transactions.php" class="nav-link">
                <ion-icon name="analytics-outline" class="nav-icon"></ion-icon>
                <span class="nav-text">Transactions</span>
              </a>
            </li> -->
            <li class="nav-item">
              <a href="refund-requests.php" class="nav-link">
                <ion-icon name="card-outline" class="nav-icon"></ion-icon>
                <span class="nav-text">Refund Requests</span>
                <?php 
                $pending_refunds = mysqli_query($con,"SELECT COUNT(*) as cnt FROM `refund_requests` WHERE user_id='$id' AND status IN ('PENDING', 'UNDER_REVIEW')");
                $refund_count = $pending_refunds ? mysqli_fetch_array($pending_refunds)['cnt'] : 0;
                if($refund_count > 0): ?>
                  <span class="nav-badge" style="background: #f59e0b;"><?php echo $refund_count; ?></span>
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
        <a href="logout.php" class="logout-btn">
          <ion-icon name="log-out-outline"></ion-icon>
          <span>Logout</span>
        </a>
      </div>
    </aside>

    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay"></div>

    <!-- Main Content -->
    <main class="main-content" style="background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);">
      <!-- Page Banner -->
      <div class="page-banner">
        <h1><ion-icon name="home-outline"></ion-icon> Welcome, <?php echo htmlspecialchars($row['u_name'] ?? 'User'); ?>!</h1>
        <p>Manage your orders and explore our product catalog</p>
      </div>
      
      <!-- Stats Row -->
      <div class="stats-row">
        <div class="stat-card">
          <div class="stat-icon blue">
            <ion-icon name="bag-handle-outline"></ion-icon>
          </div>
          <div class="stat-value">
            <?php
              $user_orders_query = mysqli_query($con, "SELECT COUNT(*) as total FROM `order` WHERE user_id='$id'");
              echo $user_orders_query ? number_format(mysqli_fetch_assoc($user_orders_query)['total']) : "0";
            ?>
          </div>
          <div class="stat-label">Total Orders</div>
          <div class="stat-change positive">
            <ion-icon name="trending-up-outline"></ion-icon>
            All time purchases
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon orange">
            <ion-icon name="time-outline"></ion-icon>
          </div>
          <div class="stat-value">
            <?php
              $pending_orders_query = mysqli_query($con, "SELECT COUNT(*) as total FROM `order` WHERE user_id='$id' AND (status='Pending' OR status='Pending Payment')");
              echo $pending_orders_query ? number_format(mysqli_fetch_assoc($pending_orders_query)['total']) : "0";
            ?>
          </div>
          <div class="stat-label">Pending</div>
          <div class="stat-change neutral">
            <ion-icon name="hourglass-outline"></ion-icon>
            Awaiting processing
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon green">
            <ion-icon name="checkmark-circle-outline"></ion-icon>
          </div>
          <div class="stat-value">
            <?php
              $completed_orders_query = mysqli_query($con, "SELECT COUNT(*) as total FROM `order` WHERE user_id='$id' AND (status='Completed' OR status='Paid')");
              echo $completed_orders_query ? number_format(mysqli_fetch_assoc($completed_orders_query)['total']) : "0";
            ?>
          </div>
          <div class="stat-label">Completed</div>
          <div class="stat-change positive">
            <ion-icon name="checkmark-done-outline"></ion-icon>
            Successfully delivered
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon purple">
            <ion-icon name="wallet-outline"></ion-icon>
          </div>
          <div class="stat-value">
            <?php
              $spent_query = mysqli_query($con, "SELECT SUM(u_totalprice) as total FROM `order` WHERE user_id='$id' AND (status='Completed' OR status='Paid')");
              $spent_result = mysqli_fetch_assoc($spent_query);
              echo number_format($spent_result['total'] ?? 0);
            ?>
          </div>
          <div class="stat-label">Total Spent (RWF)</div>
          <div class="stat-change positive">
            <ion-icon name="card-outline"></ion-icon>
            Lifetime spending
          </div>
        </div>
      </div>

      <div class="page-content">
        <!-- Quick Actions -->
        <div class="quick-actions">
          <a href="stock.php" class="quick-action-card">
            <div class="icon" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
              <ion-icon name="storefront-outline"></ion-icon>
            </div>
            <div class="text">
              <div class="title">Shop Products</div>
              <div class="subtitle">Browse available tools</div>
            </div>
          </a>
          
          <a href="orders.php" class="quick-action-card">
            <div class="icon" style="background: linear-gradient(135deg, #10b981, #059669);">
              <ion-icon name="bag-check-outline"></ion-icon>
            </div>
            <div class="text">
              <div class="title">My Orders</div>
              <div class="subtitle">Track your purchases</div>
            </div>
          </a>
          
          <a href="transactions.php" class="quick-action-card">
            <div class="icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
              <ion-icon name="receipt-outline"></ion-icon>
            </div>
            <div class="text">
              <div class="title">Transactions</div>
              <div class="subtitle">Payment history</div>
            </div>
          </a>
          
          <a href="userprofile.php" class="quick-action-card">
            <div class="icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
              <ion-icon name="person-outline"></ion-icon>
            </div>
            <div class="text">
              <div class="title">My Profile</div>
              <div class="subtitle">Update your account</div>
            </div>
          </a>
        </div>
        
        <!-- Available Products Table -->
        <div class="table-wrapper" style="margin-bottom: 2rem;">
          <div class="table-header">
            <h3 class="table-title">
              <ion-icon name="storefront-outline"></ion-icon>
              Available Products
            </h3>
            <a href="stock.php" class="btn btn-primary btn-sm">
              View All <ion-icon name="arrow-forward-outline"></ion-icon>
            </a>
          </div>
          <table class="enhanced-table">
            <thead>
              <tr>
                <th>Product</th>
                <th>Type</th>
                <th>Stock</th>
                <th>Price</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php
              // Get ALL products grouped by name with total stock from ALL batches (user/shop view)
              // This aggregates all quantities from products with the same name (like overall-stock on admin side)
              // No LIMIT - show all available products
              $available_products = mysqli_query($con, "
                SELECT t.u_toolname,
                       SUM(t.u_itemsnumber) as total_stock,
                       ROUND(AVG(t.u_price)) as avg_price,
                       MAX(t.u_type) as u_type,
                       MAX(t.image_url) as image_url,
                       MIN(t.id) as first_tool_id
                FROM `tool` t
                GROUP BY t.u_toolname
                HAVING SUM(t.u_itemsnumber) > 0
                ORDER BY t.u_toolname ASC
              ");
              
              if($available_products && mysqli_num_rows($available_products) > 0):
                while($product = mysqli_fetch_assoc($available_products)):
                  // Get first tool ID for ordering (based on FIFO - oldest first)
                  $first_tool = mysqli_query($con, "SELECT id FROM tool WHERE u_toolname = '" . mysqli_real_escape_string($con, $product['u_toolname']) . "' AND u_itemsnumber > 0 ORDER BY u_date ASC LIMIT 1");
                  $first_tool_result = mysqli_fetch_assoc($first_tool);
                  $tool_id = $first_tool_result['id'] ?? $product['first_tool_id'] ?? 0;
              ?>
              <tr>
                <td>
                  <div class="product-cell">
                    <?php if(!empty($product['image_url']) && file_exists('../' . $product['image_url'])): ?>
                    <img src="../<?php echo htmlspecialchars($product['image_url']); ?>" alt="" class="product-image">
                    <?php else: ?>
                    <div class="product-icon">
                      <ion-icon name="cube-outline"></ion-icon>
                    </div>
                    <?php endif; ?>
                    <span class="product-name"><?php echo htmlspecialchars($product['u_toolname']); ?></span>
                  </div>
                </td>
                <td><span class="badge badge-info"><?php echo htmlspecialchars($product['u_type']); ?></span></td>
                <td>
                  <span class="badge <?php echo $product['total_stock'] > 10 ? 'badge-success' : 'badge-warning'; ?>">
                    <?php echo number_format($product['total_stock']); ?> units
                  </span>
                </td>
                <td class="price"><?php echo number_format($product['avg_price']); ?><span class="currency">RWF</span></td>
                <td>
                  <a href="stock.php?id=<?php echo $tool_id; ?>" class="btn btn-primary btn-sm">
                    <ion-icon name="cart-outline"></ion-icon> Order
                  </a>
                </td>
              </tr>
              <?php endwhile; else: ?>
              <tr>
                <td colspan="5">
                  <div class="empty-state">
                    <div class="empty-state-icon">
                      <ion-icon name="cube-outline"></ion-icon>
                    </div>
                    <h3>No Products Available</h3>
                    <p>Check back later for new products</p>
                  </div>
                </td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Recent Orders Section -->
        <div class="table-wrapper">
          <div class="table-header">
            <h3 class="table-title">
              <ion-icon name="time-outline"></ion-icon>
              Recent Orders
            </h3>
            <a href="orders.php" class="btn btn-primary btn-sm">
              View All <ion-icon name="arrow-forward-outline"></ion-icon>
            </a>
          </div>
          <table class="enhanced-table">
            <thead>
              <tr>
                <th>Order ID</th>
                <th>Product</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $recent_user_orders = mysqli_query($con, "
                SELECT * 
                FROM `order` 
                WHERE user_id = '$id'
                ORDER BY id DESC 
                LIMIT 5
              ");
              
              if($recent_user_orders && mysqli_num_rows($recent_user_orders) > 0):
                while($order = mysqli_fetch_assoc($recent_user_orders)):
                  $status = $order['status'] ?? 'Pending';
                  $status_class = 'status-pending';
                  if(stripos($status, 'paid') !== false && stripos($status, 'pending') === false) {
                    $status_class = 'status-paid';
                  } elseif(stripos($status, 'complete') !== false) {
                    $status_class = 'status-completed';
                  } elseif(stripos($status, 'cancel') !== false || stripos($status, 'fail') !== false) {
                    $status_class = 'status-cancelled';
                  } elseif(stripos($status, 'pending payment') !== false) {
                    $status_class = 'status-pending-payment';
                  }
              ?>
              <tr>
                <td><strong>#<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?></strong></td>
                <td>
                  <div class="product-cell">
                    <div class="product-icon">
                      <ion-icon name="cube-outline"></ion-icon>
                    </div>
                    <span class="product-name"><?php echo htmlspecialchars($order['u_toolname'] ?? 'N/A'); ?></span>
                  </div>
                </td>
                <td class="price price-success"><?php echo number_format($order['u_totalprice'] ?? 0); ?><span class="currency">RWF</span></td>
                <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $status; ?></span></td>
                <td class="date-cell"><?php echo date('M d, Y', strtotime($order['u_date'] ?? 'now')); ?></td>
              </tr>
              <?php endwhile; else: ?>
              <tr>
                <td colspan="5">
                  <div class="empty-state">
                    <div class="empty-state-icon">
                      <ion-icon name="bag-outline"></ion-icon>
                    </div>
                    <h3>No Orders Yet</h3>
                    <p>Start shopping to see your orders here</p>
                    <a href="stock.php" class="btn btn-primary">
                      <ion-icon name="cart-outline"></ion-icon>
                      Browse Products
                    </a>
                  </div>
                </td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>

  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>
