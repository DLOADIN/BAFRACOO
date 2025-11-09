<?php
  require "connection.php";
  if(!empty($_SESSION["id"])){
  $id = $_SESSION["id"];
  $check = mysqli_query($con,"SELECT * FROM `user` WHERE id=$id ");
  $row = mysqli_fetch_array($check);
  }
  else{
  header('location:loginuser.php');
  } 
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
                <span class="nav-text">Browse Tools</span>
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
                <div style="font-weight: 600; font-size: 1.1rem;">Browse Tools</div>
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

        <!-- Recent Orders Section -->
        <div class="dashboard-card">
          <div class="card-header">
            <h3 style="font-size: 1.25rem; font-weight: 600; color: var(--gray-900); margin: 0;">Recent Orders</h3>
            <a href="orders.php" class="text-primary font-medium">View All</a>
          </div>
          <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
              <thead>
                <tr style="border-bottom: 1px solid var(--gray-200);">
                  <th style="padding: 12px; text-align: left; font-weight: 600; color: var(--gray-600);">Order ID</th>
                  <th style="padding: 12px; text-align: left; font-weight: 600; color: var(--gray-600);">Tool</th>
                  <th style="padding: 12px; text-align: left; font-weight: 600; color: var(--gray-600);">Amount</th>
                  <th style="padding: 12px; text-align: left; font-weight: 600; color: var(--gray-600);">Status</th>
                  <th style="padding: 12px; text-align: left; font-weight: 600; color: var(--gray-600);">Date</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $recent_user_orders = mysqli_query($con, "
                  SELECT o.*, t.t_name 
                  FROM `order` o 
                  LEFT JOIN `tool` t ON o.t_id = t.id 
                  WHERE o.user_id = '$id'
                  ORDER BY o.id DESC 
                  LIMIT 5
                ");
                
                if(mysqli_num_rows($recent_user_orders) > 0):
                  while($order = mysqli_fetch_assoc($recent_user_orders)):
                    $status_class = '';
                    switch($order['status']) {
                      case 'Completed': $status_class = 'text-success'; break;
                      case 'Pending': $status_class = 'text-warning'; break;
                      case 'Cancelled': $status_class = 'text-error'; break;
                      default: $status_class = 'text-primary';
                    }
                ?>
                <tr style="border-bottom: 1px solid var(--gray-100);">
                  <td style="padding: 12px; font-weight: 500;">#<?php echo $order['id']; ?></td>
                  <td style="padding: 12px;"><?php echo htmlspecialchars($order['t_name'] ?? 'N/A'); ?></td>
                  <td style="padding: 12px; font-weight: 600;"><?php echo number_format($order['u_price']); ?> RWF</td>
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
                    No orders found. <a href="stock.php" class="text-primary">Start shopping!</a>
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
  <script src="../JS/file.js"></script>
</body>
</html>
          <div class="base-1">
          <h3>TOTAL TOOLS REGISTERED</h3>
          <div class="grill">
            <div class="peng-black">
              <i class="fa-solid fa-box"></i></div>
              <h1><?php
                 $sql=mysqli_query($con,"SELECT * from `tool`" );
                 if($row=mysqli_num_rows($sql));
                 { 
               ?>
               <tr>
                 <td><?php echo $row?></td></tr>
                 <?php
               }
                ?></h1></div>
          </div>
          <div class="base-1">
          <h3>TOTAL USERS REGISTERED</h3>
          <div class="grill">
            <div class="peng-black">
              <i class="fa-solid fa-xmark"></i></div>
              <h1><?php
                 $sql=mysqli_query($con,"SELECT * from `user`" );
                 if($row=mysqli_num_rows($sql));
                 { 
               ?>
               <tr>
                 <td><?php echo $row?></td></tr>
                 <?php
               }
                ?></h1></div>
          </div>
          <div class="base-1">
          <h3>TOTAL REVENUE GENERATED BY YOU</h3>
          <div class="grill">
            <div class="peng-black">
              <i class="fa-solid fa-bag-shopping"></i></div>
              <h1><?php
                 $sql = mysqli_query($con, "SELECT SUM(u_totalprice) AS total FROM `order` WHERE user_id='$id'");
                 $row = mysqli_fetch_assoc($sql);
                 $total = $row['total'];
               ?>
               <tr>
                 <td><?php echo $total;?></td></tr>
                </h1></div>
          </div>
         </div>
        </div>
<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
<script src="../JS/file.js"></script>
</body>
</html>
