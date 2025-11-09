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
    <aside class="sidebar">
      <div class="sidebar-logo">
        <img src="./images/Captured.JPG" alt="BAFRACOO Logo">
        <span class="logo-text">BAFRACOO</span>
      </div>
      
      <nav class="sidebar-nav">
        <div class="nav-section">
          <h3 class="nav-section-title">Main Menu</h3>
          <ul class="nav-menu">
            <li class="nav-item">
              <a href="admindashboard.php" class="nav-link active" data-tooltip="Dashboard">
                <ion-icon name="home-outline" class="nav-icon"></ion-icon>
                <span class="nav-text">Dashboard</span>
              </a>
            </li>
            <li class="nav-item">
              <a href="addtool.php" class="nav-link" data-tooltip="Add Tool">
                <ion-icon name="add-circle-outline" class="nav-icon"></ion-icon>
                <span class="nav-text">Add Tool</span>
              </a>
            </li>
            <li class="nav-item">
              <a href="orders.php" class="nav-link" data-tooltip="Orders">
                <ion-icon name="bag-handle-outline" class="nav-icon"></ion-icon>
                <span class="nav-text">Orders</span>
                <?php 
                $pending_orders = mysqli_query($con,"SELECT * FROM `order` WHERE status='Pending'");
                $pending_count = $pending_orders ? mysqli_num_rows($pending_orders) : 0;
                if($pending_count > 0): ?>
                  <span class="nav-badge"><?php echo $pending_count; ?></span>
                <?php endif; ?>
              </a>
            </li>
            <li class="nav-item">
              <a href="stock.php" class="nav-link" data-tooltip="Inventory">
                <ion-icon name="cube-outline" class="nav-icon"></ion-icon>
                <span class="nav-text">Inventory</span>
              </a>
            </li>
          </ul>
        </div>
        
        <div class="nav-section">
          <h3 class="nav-section-title">Management</h3>
          <ul class="nav-menu">
            <li class="nav-item">
              <a href="transactions.php" class="nav-link" data-tooltip="Transactions">
                <ion-icon name="analytics-outline" class="nav-icon"></ion-icon>
                <span class="nav-text">Transactions</span>
              </a>
            </li>
            <li class="nav-item">
              <a href="report.php" class="nav-link" data-tooltip="Reports">
                <ion-icon name="document-text-outline" class="nav-icon"></ion-icon>
                <span class="nav-text">Reports</span>
              </a>
            </li>
            <li class="nav-item">
              <a href="adminprofile.php" class="nav-link" data-tooltip="Profile">
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
              <a href="website.php" class="nav-link" data-tooltip="Visit Website">
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
            <?php echo strtoupper(substr($row['u_name'] ?? 'A', 0, 2)); ?>
          </div>
          <div class="user-info">
            <div class="user-name"><?php echo htmlspecialchars($row['u_name'] ?? 'Admin'); ?></div>
            <div class="user-role">Administrator</div>
          </div>
        </div>
      </div>
    </aside>

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

        <!-- Recent Activity Section -->
        <div class="dashboard-card">
          <div class="card-header">
            <h3 class="card-title">Recent Orders</h3>
            <a href="orders.php" class="text-primary font-medium">View All</a>
          </div>
          <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
              <thead>
                <tr style="border-bottom: 1px solid var(--gray-200);">
                  <th style="padding: 12px; text-align: left; font-weight: 600; color: var(--gray-600);">Order ID</th>
                  <th style="padding: 12px; text-align: left; font-weight: 600; color: var(--gray-600);">Customer</th>
                  <th style="padding: 12px; text-align: left; font-weight: 600; color: var(--gray-600);">Amount</th>
                  <th style="padding: 12px; text-align: left; font-weight: 600; color: var(--gray-600);">Status</th>
                  <th style="padding: 12px; text-align: left; font-weight: 600; color: var(--gray-600);">Date</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $recent_orders = mysqli_query($con, "
                  SELECT o.*, u.u_name, u.email 
                  FROM `order` o 
                  LEFT JOIN `user` u ON o.user_id = u.id 
                  ORDER BY o.id DESC 
                  LIMIT 5
                ");
                
                if($recent_orders && mysqli_num_rows($recent_orders) > 0):
                  while($order = mysqli_fetch_assoc($recent_orders)):
                    $status_class = '';
                    switch($order['status'] ?? 'Pending') {
                      case 'Completed': $status_class = 'text-success'; break;
                      case 'Pending': $status_class = 'text-warning'; break;
                      case 'Cancelled': $status_class = 'text-error'; break;
                      default: $status_class = 'text-primary';
                    }
                ?>
                <tr style="border-bottom: 1px solid var(--gray-100);">
                  <td style="padding: 12px; font-weight: 500;">#<?php echo $order['id']; ?></td>
                  <td style="padding: 12px;"><?php echo htmlspecialchars($order['u_name'] ?? 'N/A'); ?></td>
                  <td style="padding: 12px; font-weight: 600;"><?php echo number_format($order['u_price'] ?? 0); ?> RWF</td>
                  <td style="padding: 12px;">
                    <span class="<?php echo $status_class; ?>" style="font-weight: 500;">
                      <?php echo $order['status'] ?? 'Pending'; ?>
                    </span>
                  </td>
                  <td style="padding: 12px; color: var(--gray-500);">
                    <?php echo date('M d, Y', strtotime($order['created_at'] ?? 'now')); ?>
                  </td>
                </tr>
                <?php endwhile; else: ?>
                <tr>
                  <td colspan="5" style="padding: 24px; text-align: center; color: var(--gray-500);">
                    No orders found yet. Orders will appear here once customers start placing them.
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
  <script src="./JS/file.js"></script>
</body>
</html>
