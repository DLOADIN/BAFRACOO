<?php
  require "connection.php";
  if(!empty($_SESSION["id"])){
  $id = $_SESSION["id"];
  $check = mysqli_query($con,"SELECT * FROM `admin` WHERE id=$id ");
  $row = mysqli_fetch_array($check);
  }
  else{
  header('location:loginadmin.php');
  }
  $current_page = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="./CSS/modern-dashboard.css">
  <link rel="shortcut icon" href="./images/Capture.JPG" type="image/x-icon">
  <script src="https://kit.fontawesome.com/14ff3ea278.js" crossorigin="anonymous"></script>
  <title>BAFRACOO - Admin Dashboard</title>
  <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
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
          <h1 class="page-title">Dashboard Overview</h1>
        </div>
        <div class="header-right">
          <a href="logout.php" class="logout-btn">
            <ion-icon name="log-out-outline"></ion-icon>
            <span>Logout</span>
          </a>
        </div>
      </header>

      <div class="content-area">
        <div class="dashboard-grid">
          <!-- Total Orders Card -->
          <div class="dashboard-card">
            <div class="card-header">
              <h3 class="card-title">Total Orders</h3>
              <div class="card-icon primary">
                <i class="fas fa-shopping-cart"></i>
              </div>
            </div>
            <div class="card-value">
              <?php
                $total_orders_query = mysqli_query($con, "SELECT COUNT(*) as total FROM `order`");
                if($total_orders_query) {
                  $total_orders = mysqli_fetch_assoc($total_orders_query)['total'];
                  echo number_format($total_orders);
                } else {
                  echo "0";
                }
              ?>
            </div>
            <div class="card-change positive">
              <ion-icon name="trending-up-outline"></ion-icon>
              <span>+12% from last month</span>
            </div>
          </div>

          <!-- Total Tools Card -->
          <div class="dashboard-card">
            <div class="card-header">
              <h3 class="card-title">Available Tools</h3>
              <div class="card-icon success">
                <i class="fas fa-tools"></i>
              </div>
            </div>
            <div class="card-value">
              <?php
                $total_tools_query = mysqli_query($con, "SELECT COUNT(*) as total FROM `tool`");
                if($total_tools_query) {
                  $total_tools = mysqli_fetch_assoc($total_tools_query)['total'];
                  echo number_format($total_tools);
                } else {
                  echo "0";
                }
              ?>
            </div>
            <div class="card-change positive">
              <ion-icon name="trending-up-outline"></ion-icon>
              <span>+3 new tools added</span>
            </div>
          </div>

          <!-- Total Users Card -->
          <div class="dashboard-card">
            <div class="card-header">
              <h3 class="card-title">Registered Users</h3>
              <div class="card-icon accent">
                <i class="fas fa-users"></i>
              </div>
            </div>
            <div class="card-value">
              <?php
                $total_users_query = mysqli_query($con, "SELECT COUNT(*) as total FROM `user`");
                if($total_users_query) {
                  $total_users = mysqli_fetch_assoc($total_users_query)['total'];
                  echo number_format($total_users);
                } else {
                  echo "0";
                }
              ?>
            </div>
            <div class="card-change positive">
              <ion-icon name="trending-up-outline"></ion-icon>
              <span>+8% this week</span>
            </div>
          </div>

          <!-- Total Revenue Card -->
          <div class="dashboard-card">
            <div class="card-header">
              <h3 class="card-title">Total Revenue</h3>
              <div class="card-icon warning">
                <i class="fas fa-dollar-sign"></i>
              </div>
            </div>
            <div class="card-value">
              <?php
                $revenue_query = mysqli_query($con, "SELECT SUM(u_price) as total FROM `order` WHERE status = 'Completed'");
                if($revenue_query) {
                  $revenue_result = mysqli_fetch_assoc($revenue_query);
                  $total_revenue = $revenue_result['total'] ?? 0;
                  echo number_format($total_revenue);
                } else {
                  echo "0";
                }
              ?> RWF
            </div>
            <div class="card-change positive">
              <ion-icon name="trending-up-outline"></ion-icon>
              <span>+15% this month</span>
            </div>
          </div>
        </div>

        <!-- Recent Orders Section -->
        <div class="dashboard-card">
          <div class="card-header">
            <h3 class="card-title">
              <ion-icon name="bag-handle-outline" style="margin-right: 8px;"></ion-icon>
              All Orders
            </h3>
            <a href="orders.php" class="text-primary font-medium" style="color: var(--primary-color); text-decoration: none; font-weight: 600;">View All →</a>
          </div>
          <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
              <thead>
                <tr style="border-bottom: 2px solid var(--gray-200);">
                  <th style="padding: 12px; text-align: left; font-weight: 600; color: var(--gray-600); font-size: 0.875rem; text-transform: uppercase;">Order ID</th>
                  <th style="padding: 12px; text-align: left; font-weight: 600; color: var(--gray-600); font-size: 0.875rem; text-transform: uppercase;">Customer</th>
                  <th style="padding: 12px; text-align: left; font-weight: 600; color: var(--gray-600); font-size: 0.875rem; text-transform: uppercase;">Product</th>
                  <th style="padding: 12px; text-align: left; font-weight: 600; color: var(--gray-600); font-size: 0.875rem; text-transform: uppercase;">Quantity</th>
                  <th style="padding: 12px; text-align: left; font-weight: 600; color: var(--gray-600); font-size: 0.875rem; text-transform: uppercase;">Total Price</th>
                  <th style="padding: 12px; text-align: left; font-weight: 600; color: var(--gray-600); font-size: 0.875rem; text-transform: uppercase;">Status</th>
                  <th style="padding: 12px; text-align: left; font-weight: 600; color: var(--gray-600); font-size: 0.875rem; text-transform: uppercase;">Date</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $recent_orders = mysqli_query($con, "
                  SELECT o.*, u.u_name 
                  FROM `order` o 
                  LEFT JOIN `user` u ON o.user_id = u.id 
                  ORDER BY o.id DESC 
                  LIMIT 10
                ");
                
                if($recent_orders === false) {
                  echo "<tr><td colspan='7' style='text-align: center; color: red;'>Database error: " . htmlspecialchars(mysqli_error($con)) . "</td></tr>";
                } else if(mysqli_num_rows($recent_orders) > 0):
                  while($order = mysqli_fetch_assoc($recent_orders)):
                    $status_class = '';
                    $status_bg = '';
                    switch($order['status'] ?? 'Pending') {
                      case 'Completed': 
                        $status_class = 'var(--success-color)'; 
                        $status_bg = 'rgba(5, 150, 105, 0.1)';
                        break;
                      case 'Pending': 
                        $status_class = 'var(--warning-color)'; 
                        $status_bg = 'rgba(245, 158, 11, 0.1)';
                        break;
                      case 'Cancelled': 
                        $status_class = 'var(--error-color)'; 
                        $status_bg = 'rgba(239, 68, 68, 0.1)';
                        break;
                      default: 
                        $status_class = 'var(--primary-color)';
                        $status_bg = 'rgba(37, 99, 235, 0.1)';
                    }
                ?>
                <tr style="border-bottom: 1px solid var(--gray-100); transition: background 0.2s;">
                  <td style="padding: 16px; font-weight: 600; color: var(--primary-color);">#<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?></td>
                  <td style="padding: 16px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                      <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--primary-color); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 0.75rem;">
                        <?php echo strtoupper(substr($order['u_name'] ?? 'N', 0, 2)); ?>
                      </div>
                      <span style="font-weight: 500;"><?php echo htmlspecialchars($order['u_name'] ?? 'N/A'); ?></span>
                    </div>
                  </td>
                  <td style="padding: 16px; color: var(--gray-800);"><?php echo htmlspecialchars($order['u_toolname'] ?? 'N/A'); ?></td>
                  <td style="padding: 16px; font-weight: 600; color: var(--gray-800);"><?php echo $order['u_itemsnumber'] ?? 0; ?></td>
                  <td style="padding: 16px; font-weight: 700; color: var(--gray-900);"><?php echo number_format($order['u_totalprice'] ?? 0); ?> RWF</td>
                  <td style="padding: 16px;">
                    <span style="background: <?php echo $status_bg; ?>; color: <?php echo $status_class; ?>; padding: 6px 12px; border-radius: 20px; font-weight: 600; font-size: 0.75rem; display: inline-block;">
                      <?php echo $order['status'] ?? 'Pending'; ?>
                    </span>
                  </td>
                  <td style="padding: 16px; color: var(--gray-600); font-size: 0.875rem;">
                    <?php echo date('M d, Y', strtotime($order['u_date'] ?? 'now')); ?>
                  </td>
                </tr>
                <?php endwhile; else: ?>
                <tr>
                  <td colspan="7" style="padding: 48px; text-align: center;">
                    <ion-icon name="bag-handle-outline" style="font-size: 3rem; color: var(--gray-400); margin-bottom: 16px;"></ion-icon>
                    <div style="color: var(--gray-600); font-size: 1rem; margin-bottom: 8px;">No orders found yet</div>
                    <div style="color: var(--gray-500); font-size: 0.875rem;">Orders will appear here once customers start placing them</div>
                    <a href="addtool.php" style="display: inline-block; margin-top: 16px; padding: 10px 24px; background: var(--primary-color); color: white; border-radius: 8px; text-decoration: none; font-weight: 600;">
                      <ion-icon name="add-outline" style="margin-right: 4px;"></ion-icon>
                      Add First Order
                    </a>
                  </td>
                </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Tools Inventory Section -->
        <div class="dashboard-card" style="margin-top: 32px;">
          <div class="card-header">
            <h3 class="card-title">
              <ion-icon name="cube-outline" style="margin-right: 8px;"></ion-icon>
              Tools Inventory
            </h3>
            <a href="stock.php" class="text-primary font-medium" style="color: var(--primary-color); text-decoration: none; font-weight: 600;">View All →</a>
          </div>
          <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
              <thead>
                <tr style="border-bottom: 2px solid var(--gray-200);">
                  <th style="padding: 12px; text-align: left; font-weight: 600; color: var(--gray-600); font-size: 0.875rem; text-transform: uppercase;">ID</th>
                  <th style="padding: 12px; text-align: left; font-weight: 600; color: var(--gray-600); font-size: 0.875rem; text-transform: uppercase;">Tool Name</th>
                  <th style="padding: 12px; text-align: left; font-weight: 600; color: var(--gray-600); font-size: 0.875rem; text-transform: uppercase;">Type</th>
                  <th style="padding: 12px; text-align: left; font-weight: 600; color: var(--gray-600); font-size: 0.875rem; text-transform: uppercase;">Quantity</th>
                  <th style="padding: 12px; text-align: left; font-weight: 600; color: var(--gray-600); font-size: 0.875rem; text-transform: uppercase;">Unit Price</th>
                  <th style="padding: 12px; text-align: left; font-weight: 600; color: var(--gray-600); font-size: 0.875rem; text-transform: uppercase;">Total Value</th>
                  <th style="padding: 12px; text-align: left; font-weight: 600; color: var(--gray-600); font-size: 0.875rem; text-transform: uppercase;">Status</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $tools_query = mysqli_query($con, "
                  SELECT * FROM `tool` 
                  ORDER BY u_date DESC 
                  LIMIT 10
                ");
                
                if($tools_query === false) {
                  echo "<tr><td colspan='7' style='text-align: center; color: red;'>Database error: " . htmlspecialchars(mysqli_error($con)) . "</td></tr>";
                } else if(mysqli_num_rows($tools_query) > 0):
                  while($tool = mysqli_fetch_assoc($tools_query)):
                    $status_class = '';
                    $status_bg = '';
                    $status_text = '';
                    if ($tool['u_itemsnumber'] <= 0) {
                      $status_class = 'var(--error-color)';
                      $status_bg = 'rgba(239, 68, 68, 0.1)';
                      $status_text = 'Out of Stock';
                    } elseif ($tool['u_itemsnumber'] < 10) {
                      $status_class = 'var(--warning-color)';
                      $status_bg = 'rgba(245, 158, 11, 0.1)';
                      $status_text = 'Low Stock';
                    } else {
                      $status_class = 'var(--success-color)';
                      $status_bg = 'rgba(5, 150, 105, 0.1)';
                      $status_text = 'In Stock';
                    }
                    $total_value = $tool['u_price'] * $tool['u_itemsnumber'];
                ?>
                <tr style="border-bottom: 1px solid var(--gray-100); transition: background 0.2s;">
                  <td style="padding: 16px; font-weight: 600; color: var(--primary-color);">#<?php echo str_pad($tool['id'], 3, '0', STR_PAD_LEFT); ?></td>
                  <td style="padding: 16px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                      <div style="width: 40px; height: 40px; border-radius: 8px; background: var(--primary-light); display: flex; align-items: center; justify-content: center; color: var(--primary-color);">
                        <ion-icon name="construct-outline" style="font-size: 1.25rem;"></ion-icon>
                      </div>
                      <span style="font-weight: 500; color: var(--gray-900);"><?php echo htmlspecialchars($tool['u_toolname']); ?></span>
                    </div>
                  </td>
                  <td style="padding: 16px; color: var(--gray-700);"><?php echo htmlspecialchars($tool['u_type']); ?></td>
                  <td style="padding: 16px; font-weight: 700; color: <?php echo $tool['u_itemsnumber'] < 10 ? 'var(--warning-color)' : 'var(--success-color)'; ?>;">
                    <?php echo $tool['u_itemsnumber']; ?>
                  </td>
                  <td style="padding: 16px; font-weight: 600; color: var(--gray-800);"><?php echo number_format($tool['u_price']); ?> RWF</td>
                  <td style="padding: 16px; font-weight: 700; color: var(--gray-900);"><?php echo number_format($total_value); ?> RWF</td>
                  <td style="padding: 16px;">
                    <span style="background: <?php echo $status_bg; ?>; color: <?php echo $status_class; ?>; padding: 6px 12px; border-radius: 20px; font-weight: 600; font-size: 0.75rem; display: inline-block;">
                      <?php echo $status_text; ?>
                    </span>
                  </td>
                </tr>
                <?php endwhile; else: ?>
                <tr>
                  <td colspan="7" style="padding: 48px; text-align: center;">
                    <ion-icon name="cube-outline" style="font-size: 3rem; color: var(--gray-400); margin-bottom: 16px;"></ion-icon>
                    <div style="color: var(--gray-600); font-size: 1rem; margin-bottom: 8px;">No tools in inventory yet</div>
                    <div style="color: var(--gray-500); font-size: 0.875rem;">Add tools to start managing your inventory</div>
                    <a href="addorder.php" style="display: inline-block; margin-top: 16px; padding: 10px 24px; background: var(--primary-color); color: white; border-radius: 8px; text-decoration: none; font-weight: 600;">
                      <ion-icon name="add-outline" style="margin-right: 4px;"></ion-icon>
                      Add First Tool
                    </a>
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

  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
  <!-- <script src="./JS/file.js"></script> -->
</body>
</html>
