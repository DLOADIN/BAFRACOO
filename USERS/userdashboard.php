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
            <li class="nav-item">
              <a href="transactions.php" class="nav-link">
                <ion-icon name="analytics-outline" class="nav-icon"></ion-icon>
                <span class="nav-text">Transactions</span>
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
    <main class="main-content">
      <header class="header">
        <div class="header-left">
          <button class="mobile-menu-btn">
            <ion-icon name="menu-outline"></ion-icon>
          </button>
          <button class="sidebar-toggle">
            <ion-icon name="chevron-back-outline"></ion-icon>
          </button>
          <h1 class="page-title">My Dashboard</h1>
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
          <!-- My Orders Card -->
          <div class="dashboard-card">
            <div class="card-header">
              <h3 class="card-title">My Orders</h3>
              <div class="card-icon primary">
                <i class="fas fa-shopping-bag"></i>
              </div>
            </div>
            <div class="card-value">
              <?php
                $user_orders_query = mysqli_query($con, "SELECT COUNT(*) as total FROM `order` WHERE user_id='$id'");
                if($user_orders_query) {
                  $user_orders_count = mysqli_fetch_assoc($user_orders_query)['total'];
                  echo number_format($user_orders_count);
                } else {
                  echo "0";
                }
              ?>
            </div>
            <div class="card-change positive">
              <ion-icon name="trending-up-outline"></ion-icon>
              <span>Total orders placed</span>
            </div>
          </div>

          <!-- Pending Orders Card -->
          <div class="dashboard-card">
            <div class="card-header">
              <h3 class="card-title">Pending Orders</h3>
              <div class="card-icon warning">
                <i class="fas fa-clock"></i>
              </div>
            </div>
            <div class="card-value">
              <?php
                $pending_orders_query = mysqli_query($con, "SELECT COUNT(*) as total FROM `order` WHERE user_id='$id' AND status='Pending'");
                if($pending_orders_query) {
                  $pending_orders_count = mysqli_fetch_assoc($pending_orders_query)['total'];
                  echo number_format($pending_orders_count);
                } else {
                  echo "0";
                }
              ?>
            </div>
            <div class="card-change">
              <ion-icon name="time-outline"></ion-icon>
              <span>Awaiting processing</span>
            </div>
          </div>

          <!-- Completed Orders Card -->
          <div class="dashboard-card">
            <div class="card-header">
              <h3 class="card-title">Completed Orders</h3>
              <div class="card-icon success">
                <i class="fas fa-check-circle"></i>
              </div>
            </div>
            <div class="card-value">
              <?php
                $completed_orders_query = mysqli_query($con, "SELECT COUNT(*) as total FROM `order` WHERE user_id='$id' AND status='Completed'");
                $completed_orders_count = mysqli_fetch_assoc($completed_orders_query)['total'];
                echo number_format($completed_orders_count);
              ?>
            </div>
            <div class="card-change positive">
              <ion-icon name="checkmark-circle-outline"></ion-icon>
              <span>Successfully delivered</span>
            </div>
          </div>

          <!-- Total Spent Card -->
          <div class="dashboard-card">
            <div class="card-header">
              <h3 class="card-title">Total Spent</h3>
              <div class="card-icon accent">
                <i class="fas fa-dollar-sign"></i>
              </div>
            </div>
            <div class="card-value">
              <?php
                $spent_query = mysqli_query($con, "SELECT SUM(u_price) as total FROM `order` WHERE user_id='$id' AND status='Completed'");
                $spent_result = mysqli_fetch_assoc($spent_query);
                $total_spent = $spent_result['total'] ?? 0;
                echo number_format($total_spent);
              ?> RWF
            </div>
            <div class="card-change positive">
              <ion-icon name="card-outline"></ion-icon>
              <span>Lifetime spending</span>
            </div>
          </div>
        </div>

        <!-- Quick Actions Section -->
        <div class="dashboard-card">
          <div class="card-header">
            <h3 style="font-size: 1.25rem; font-weight: 600; color: var(--gray-900); margin: 0;">Quick Actions</h3>
          </div>
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spacing-lg); padding: var(--spacing-lg);">
            <a href="stock.php" style="display: flex; align-items: center; padding: var(--spacing-lg); background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); color: white; border-radius: var(--radius-lg); text-decoration: none; transition: all var(--transition-base);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='var(--shadow-lg)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
              <ion-icon name="storefront-outline" style="font-size: 2rem; margin-right: var(--spacing-md);"></ion-icon>
              <div>
                <div style="font-weight: 600; font-size: 1.1rem;">Inter Purchases</div>
                <div style="font-size: 0.875rem; opacity: 0.9;">Explore available tools</div>
              </div>
            </a>
            
            <a href="orders.php" style="display: flex; align-items: center; padding: var(--spacing-lg); background: linear-gradient(135deg, var(--success-color), #059669); color: white; border-radius: var(--radius-lg); text-decoration: none; transition: all var(--transition-base);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='var(--shadow-lg)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
              <ion-icon name="bag-check-outline" style="font-size: 2rem; margin-right: var(--spacing-md);"></ion-icon>
              <div>
                <div style="font-weight: 600; font-size: 1.1rem;">View Orders</div>
                <div style="font-size: 0.875rem; opacity: 0.9;">Track your orders</div>
              </div>
            </a>
            
            <a href="userprofile.php" style="display: flex; align-items: center; padding: var(--spacing-lg); background: linear-gradient(135deg, var(--secondary-color), #4f46e5); color: white; border-radius: var(--radius-lg); text-decoration: none; transition: all var(--transition-base);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='var(--shadow-lg)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
              <ion-icon name="person-outline" style="font-size: 2rem; margin-right: var(--spacing-md);"></ion-icon>
              <div>
                <div style="font-weight: 600; font-size: 1.1rem;">Update Profile</div>
                <div style="font-size: 0.875rem; opacity: 0.9;">Manage your account</div>
              </div>
            </a>
          </div>
        </div>

        <!-- Available Locations Section -->
        <div class="dashboard-card" style="margin-top: var(--spacing-xl);">
          <div class="card-header">
            <h3 style="font-size: 1.25rem; font-weight: 600; color: var(--gray-900); margin: 0; display: flex; align-items: center; gap: 8px;">
              <ion-icon name="location-outline" style="color: var(--primary-color);"></ion-icon>
              Available Pickup Locations
            </h3>
            <p style="margin: 8px 0 0 0; color: var(--gray-600); font-size: 0.875rem;">Choose from <?php echo $location_count; ?> convenient locations across Rwanda</p>
          </div>
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: var(--spacing-md); padding: var(--spacing-lg);">
            <?php
            mysqli_data_seek($locations, 0); // Reset the result set
            while($location = mysqli_fetch_array($locations)):
              // Get total products available at this location
              $location_inventory = mysqli_query($con, "
                SELECT COUNT(DISTINCT sb.tool_id) as product_count, SUM(sb.quantity_remaining) as total_stock 
                FROM stock_batches sb 
                WHERE sb.location_id = {$location['id']} AND sb.quantity_remaining > 0
              ");
              $location_stats = mysqli_fetch_array($location_inventory);
            ?>
            <div style="padding: var(--spacing-lg); border: 1px solid var(--gray-200); border-radius: var(--radius-lg); background: white; transition: all 0.2s;" onmouseover="this.style.boxShadow='var(--shadow-md)'; this.style.transform='translateY(-2px)'" onmouseout="this.style.boxShadow='none'; this.style.transform='translateY(0)'">
              <div style="display: flex; justify-content: between; align-items: start; margin-bottom: 12px;">
                <div>
                  <h4 style="margin: 0; font-size: 1rem; font-weight: 600; color: var(--gray-900);">
                    <?php echo htmlspecialchars($location['location_name']); ?>
                  </h4>
                  <p style="margin: 4px 0 0 0; font-size: 0.875rem; color: var(--gray-600);">
                    <?php echo htmlspecialchars($location['address']); ?>
                  </p>
                </div>
                <div style="padding: 4px 8px; background: #06b6d4; color: white; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">
                  <?php echo $location['location_type']; ?>
                </div>
              </div>
              <div style="display: flex; justify-content: between; align-items: center;">
                <div style="display: flex; gap: 16px;">
                  <div>
                    <span style="font-size: 0.75rem; color: var(--gray-500); text-transform: uppercase; letter-spacing: 0.5px;">Products</span>
                    <div style="font-size: 1.25rem; font-weight: 700; color: var(--primary-color);">
                      <?php echo $location_stats['product_count'] ?? 0; ?>
                    </div>
                  </div>
                  <div>
                    <span style="font-size: 0.75rem; color: var(--gray-500); text-transform: uppercase; letter-spacing: 0.5px;">Stock</span>
                    <div style="font-size: 1.25rem; font-weight: 700; color: var(--success-color);">
                      <?php echo $location_stats['total_stock'] ?? 0; ?>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <?php endwhile; ?>
          </div>
        </div>

        <!-- Recent Orders Section -->
        <div class="dashboard-card" style="margin-top: var(--spacing-xl);">
          <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; padding: var(--spacing-lg); border-bottom: 1px solid var(--gray-200);">
            <div style="display: flex; align-items: center; gap: 12px;">
              <ion-icon name="time-outline" style="font-size: 1.5rem; color: var(--primary-color);"></ion-icon>
              <h3 style="font-size: 1.25rem; font-weight: 600; color: var(--gray-900); margin: 0;">Recent Orders</h3>
            </div>
            <a href="orders.php" style="color: var(--primary-color); font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 4px; font-size: 0.875rem; transition: all 0.2s;" onmouseover="this.style.gap='8px'" onmouseout="this.style.gap='4px'">
              View All <ion-icon name="arrow-forward-outline"></ion-icon>
            </a>
          </div>
          <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
              <thead>
                <tr style="border-bottom: 2px solid var(--gray-200); background: var(--gray-50);">
                  <th style="padding: 14px 12px; text-align: left; font-weight: 600; color: var(--gray-700); font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.5px;">Order ID</th>
                  <th style="padding: 14px 12px; text-align: left; font-weight: 600; color: var(--gray-700); font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.5px;">Tool Name</th>
                  <th style="padding: 14px 12px; text-align: left; font-weight: 600; color: var(--gray-700); font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.5px;">Amount</th>
                  <th style="padding: 14px 12px; text-align: left; font-weight: 600; color: var(--gray-700); font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.5px;">Status</th>
                  <th style="padding: 14px 12px; text-align: left; font-weight: 600; color: var(--gray-700); font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.5px;">Date</th>
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
                    $status_class = '';
                    $status_bg = '';
                    switch($status) {
                      case 'Completed': 
                        $status_class = 'color: #10b981;'; 
                        $status_bg = 'background: #d1fae5; padding: 4px 12px; border-radius: 12px;';
                        break;
                      case 'Pending': 
                        $status_class = 'color: #f59e0b;'; 
                        $status_bg = 'background: #fef3c7; padding: 4px 12px; border-radius: 12px;';
                        break;
                      case 'Cancelled': 
                        $status_class = 'color: #ef4444;'; 
                        $status_bg = 'background: #fee2e2; padding: 4px 12px; border-radius: 12px;';
                        break;
                      default: 
                        $status_class = 'color: #2563eb;'; 
                        $status_bg = 'background: #dbeafe; padding: 4px 12px; border-radius: 12px;';
                    }
                ?>
                <tr style="border-bottom: 1px solid var(--gray-100); transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#f9fafb'" onmouseout="this.style.backgroundColor='transparent'">
                  <td style="padding: 12px; font-weight: 500; color: var(--gray-900);">#<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?></td>
                  <td style="padding: 12px; color: var(--gray-700);"><?php echo htmlspecialchars($order['u_toolname'] ?? 'N/A'); ?></td>
                  <td style="padding: 12px; font-weight: 600; color: var(--gray-900);">
                    <?php echo number_format($order['u_totalprice'] ?? $order['u_price'] ?? 0); ?> <span style="color: var(--gray-500); font-size: 0.875rem;">RWF</span>
                  </td>
                  <td style="padding: 12px;">
                    <span style="<?php echo $status_class . $status_bg; ?> font-weight: 500; font-size: 0.875rem; display: inline-block;">
                      <?php echo $status; ?>
                    </span>
                  </td>
                  <td style="padding: 12px; color: var(--gray-500); font-size: 0.875rem;">
                    <?php echo date('M d, Y', strtotime($order['u_date'] ?? 'now')); ?>
                  </td>
                </tr>
                <?php endwhile; else: ?>
                <tr>
                  <td colspan="5" style="padding: 32px; text-align: center;">
                    <div style="display: flex; flex-direction: column; align-items: center; gap: 12px;">
                      <ion-icon name="bag-outline" style="font-size: 48px; color: var(--gray-400);"></ion-icon>
                      <p style="color: var(--gray-500); margin: 0; font-size: 1rem;">No orders yet</p>
                      <a href="stock.php" style="color: var(--primary-color); font-weight: 600; text-decoration: none; padding: 8px 16px; border: 2px solid var(--primary-color); border-radius: 8px; transition: all 0.2s;" onmouseover="this.style.background='var(--primary-color)'; this.style.color='white'" onmouseout="this.style.background='transparent'; this.style.color='var(--primary-color)'">
                        Start Shopping <ion-icon name="arrow-forward-outline" style="vertical-align: middle;"></ion-icon>
                      </a>
                    </div>
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
  <!--   <!-- <script src="../JS/file.js"></script> -->
 -->
