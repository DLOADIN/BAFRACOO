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
  <link rel="stylesheet" href="../CSS/modern-tables.css">
  <link rel="shortcut icon" href="../images/Capture.JPG" type="image/x-icon">
  <script src="https://kit.fontawesome.com/14ff3ea278.js" crossorigin="anonymous"></script>
  <title>BAFRACOO - Transactions</title>
  <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
  <style>
    /* Ensure Ionicons display properly */
    ion-icon {
      display: inline-block;
      vertical-align: middle;
    }
    
    .nav-icon {
      font-size: 1.25rem;
      width: 24px;
      height: 24px;
      display: inline-flex !important;
      align-items: center;
      justify-content: center;
    }
    
    .nav-link ion-icon {
      flex-shrink: 0;
    }
  </style>
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
              <a href="userdashboard.php" class="nav-link">
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
              </a>
            </li>
            <li class="nav-item">
              <a href="transactions.php" class="nav-link active">
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
          <h1 class="page-title">Transaction History</h1>
        </div>
        <div class="header-right">
          <a href="logout.php" class="logout-btn">
            <ion-icon name="log-out-outline"></ion-icon>
            <span>Logout</span>
          </a>
        </div>
      </header>
      
      <!-- Page Content -->
      <div class="content-wrapper">
        <div class="content-header">
          <h2 class="content-title">Payment Transactions</h2>
          <p class="content-subtitle">View your payment history</p>
        </div>
        
        <div class="table-container">
          <table class="modern-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Order Code</th>
                <th>Amount Paid</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
          <?php
          $number=0;
          $sql=mysqli_query($con,"SELECT `transaction`.*, `order`.`id`,`order`.`u_totalprice`, `order`.`u_date` FROM `transaction`INNER JOIN `order` ON `transaction`.order_id = `order`.id WHERE `order`.user_id = '$id' ORDER BY `transaction`.id DESC;");
          $row = mysqli_num_rows($sql);
          if($row > 0){
            while($row=mysqli_fetch_array($sql))
            { 
            $number++;
          ?>
          <tr>
            <td><strong>#<?php echo str_pad($number, 3, '0', STR_PAD_LEFT)?></strong></td>
            <td>
              <span style="display: inline-block; padding: 6px 14px; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white; border-radius: 8px; font-size: 0.875rem; font-weight: 600; letter-spacing: 0.5px;">
                ORDER-<?php echo str_pad($row['order_id'], 4, '0', STR_PAD_LEFT)?>
              </span>
            </td>
            <td>
              <div style="display: flex; align-items: center; gap: 8px;">
                <ion-icon name="cash-outline" style="color: #10b981; font-size: 1.25rem;"></ion-icon>
                <strong style="color: #10b981; font-size: 1rem;">RWF <?php echo number_format($row['u_totalprice'])?></strong>
              </div>
            </td>
            <td>
              <div style="display: flex; align-items: center; gap: 8px; color: var(--gray-600);">
                <ion-icon name="calendar-outline" style="font-size: 1rem;"></ion-icon>
                <?php echo date('M d, Y', strtotime($row['u_date']))?>
              </div>
            </td>
            <?php
            }
          } else {
            ?>
            <tr>
              <td colspan="4" style="text-align: center; padding: var(--spacing-xl);">
                <div style="display: flex; flex-direction: column; align-items: center; gap: var(--spacing-md); padding: var(--spacing-xl);">
                  <ion-icon name="receipt-outline" style="font-size: 4rem; color: var(--gray-400);"></ion-icon>
                  <h3 style="color: var(--gray-700); margin: 0;">No Transactions Yet</h3>
                  <p style="color: var(--gray-500); margin: 0;">You haven't made any payments yet.</p>
                  <a href="orders.php" style="margin-top: var(--spacing-sm); padding: 10px 20px; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white; text-decoration: none; border-radius: 8px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
                    <ion-icon name="bag-handle-outline"></ion-icon>
                    View Orders
                  </a>
                </div>
              </td>
            </tr>
            <?php
          }
            ?>
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
