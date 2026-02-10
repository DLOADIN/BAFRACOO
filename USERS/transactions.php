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
  <link rel="stylesheet" href="../CSS/enhanced-pages.css">
  <link rel="shortcut icon" href="../images/Capture.JPG" type="image/x-icon">
  <script src="https://kit.fontawesome.com/14ff3ea278.js" crossorigin="anonymous"></script>
  <title>BAFRACOO - Transactions</title>
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
            <li class="nav-item">
              <a href="refund-requests.php" class="nav-link">
                <ion-icon name="card-outline" class="nav-icon"></ion-icon>
                <span class="nav-text">Refund Requests</span>
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
    <main class="main-content" style="background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);">
      <!-- Page Banner -->
      <div class="page-banner">
        <h1><ion-icon name="receipt-outline"></ion-icon> Transaction History</h1>
        <p>View your payment history and order transactions</p>
      </div>
      
      <!-- Stats Row -->
      <?php
      $total_trans = mysqli_query($con, "SELECT COUNT(*) as cnt FROM `transaction` WHERE u_id = '$id'");
      $total_count = mysqli_fetch_assoc($total_trans)['cnt'] ?? 0;
      
      $total_paid = mysqli_query($con, "SELECT SUM(u_amount) as total FROM `transaction` WHERE u_id = '$id' AND u_status = 'Completed'");
      $paid_amount = mysqli_fetch_assoc($total_paid)['total'] ?? 0;
      ?>
      <div class="stats-row">
        <div class="stat-card">
          <div class="stat-icon blue">
            <ion-icon name="receipt-outline"></ion-icon>
          </div>
          <div class="stat-value"><?php echo number_format($total_count); ?></div>
          <div class="stat-label">Total Transactions</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon green">
            <ion-icon name="checkmark-circle-outline"></ion-icon>
          </div>
          <div class="stat-value"><?php echo number_format($paid_amount); ?></div>
          <div class="stat-label">Total Paid (RWF)</div>
        </div>
      </div>
      
      <!-- Transactions Table -->
      <div class="page-content">
        <div class="table-wrapper">
          <div class="table-header">
            <h3 class="table-title">
              <ion-icon name="analytics-outline"></ion-icon>
              Payment Transactions
            </h3>
          </div>
          <table class="enhanced-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Order Code</th>
                <th>Item</th>
                <th>Quantity</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
          <?php
          $number=0;
          $sql=mysqli_query($con,"SELECT `transaction`.*, `order`.`id` AS order_id, `order`.`u_totalprice`, `order`.`u_date` 
                                  FROM `transaction` 
                                  INNER JOIN `order` ON `transaction`.order_id = `order`.id 
                                  WHERE `transaction`.u_id = '$id' 
                                  ORDER BY `transaction`.id DESC");
          $row_count = mysqli_num_rows($sql);
          if($row_count > 0){
            while($trans_row=mysqli_fetch_array($sql))
            { 
            $number++;
            $status = $trans_row['u_status'] ?? 'Completed';
            $status_class = 'status-completed';
            if(stripos($status, 'pending') !== false) $status_class = 'status-pending';
            elseif(stripos($status, 'fail') !== false) $status_class = 'status-cancelled';
            elseif(stripos($status, 'refund') !== false) $status_class = 'status-processing';
          ?>
          <tr>
            <td><strong>#<?php echo str_pad($number, 3, '0', STR_PAD_LEFT)?></strong></td>
            <td><span class="badge badge-primary">ORDER-<?php echo str_pad($trans_row['order_id'], 4, '0', STR_PAD_LEFT)?></span></td>
            <td>
              <div class="product-cell">
                <div class="product-icon">
                  <ion-icon name="cube-outline"></ion-icon>
                </div>
                <span class="product-name"><?php echo htmlspecialchars($trans_row['u_toolname'] ?? 'N/A')?></span>
              </div>
            </td>
            <td><span class="badge badge-info"><?php echo number_format($trans_row['u_item'] ?? 0)?> items</span></td>
            <td class="price price-success"><?php echo number_format($trans_row['u_amount'] ?? $trans_row['u_totalprice'])?><span class="currency">RWF</span></td>
            <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $status; ?></span></td>
            <td class="date-cell"><?php echo date('M d, Y', strtotime($trans_row['u_date']))?></td>
          </tr>
            <?php
            }
          } else {
            ?>
            <tr>
              <td colspan="7">
                <div class="empty-state">
                  <div class="empty-state-icon">
                    <ion-icon name="receipt-outline"></ion-icon>
                  </div>
                  <h3>No Transactions Yet</h3>
                  <p>You haven't made any payments yet.</p>
                  <a href="orders.php" class="btn btn-primary">
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
