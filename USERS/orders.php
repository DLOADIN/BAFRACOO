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
  <title>BAFRACOO - My Orders</title>
    <!-- <script src="../JS/file.js"></script>
 -->
  <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
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
                <span class="nav-text">Browse Tools</span>
              </a>
            </li>
            <li class="nav-item">
              <a href="orders.php" class="nav-link active">
                <ion-icon name="bag-handle-outline" class="nav-icon"></ion-icon>
                <span class="nav-text">My Orders</span>
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
          <h1 class="page-title">My Orders</h1>
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
          <h2 class="content-title">Your Order History</h2>
          <p class="content-subtitle">Track and manage your orders</p>
        </div>
        
        <div class="table-container">
          <table class="modern-table">
            <thead>
              <tr>
                <th>#</th>
                <th>User Name</th>
                <th>Tool Name</th>
                <th>Quantity</th>
                <th>Type</th>
                <th>Description</th>
                <th>Unit Price</th>
                <th>Total Price</th>
                <th>Date</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
            <?php
            $number=0;
             $sql = "SELECT `order`.*, user.u_name FROM `order`INNER JOIN user ON `order`.user_id = user.id WHERE `order`.user_id = '$id'";
                $result = mysqli_query($con, $sql);
                if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_array($result)) {
                  $number++;
            ?>
            <tr>
              <td><strong>#<?php echo str_pad($number, 3, '0', STR_PAD_LEFT)?></strong></td>
              <td><?php echo htmlspecialchars($row['u_name'])?></td>
              <td><strong><?php echo htmlspecialchars($row['u_toolname'])?></strong></td>
              <td>
                <span style="display: inline-block; padding: 4px 12px; background: var(--primary-color); color: white; border-radius: 12px; font-size: 0.875rem; font-weight: 600;">
                  <?php echo $row['u_itemsnumber']?> items
                </span>
              </td>
              <td>
                <span style="display: inline-block; padding: 4px 12px; background: var(--gray-100); color: var(--gray-700); border-radius: 12px; font-size: 0.875rem; font-weight: 500;">
                  <?php echo htmlspecialchars($row['u_type'])?>
                </span>
              </td>
              <td style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                <?php echo htmlspecialchars($row['u_tooldescription'])?>
              </td>
              <td>
                <span style="color: var(--gray-600); font-size: 0.875rem;">RWF <?php echo number_format($row['u_price'])?></span>
              </td>
              <td>
                <strong style="color: var(--primary-color); font-size: 1rem;">RWF <?php echo number_format($row['u_totalprice'])?></strong>
              </td>
              <td style="color: var(--gray-600);"><?php echo date('M d, Y', strtotime($row['u_date']))?></td>
              <td>  
                <a href="pay.php?o_id=<?php echo $row['id']?>" 
                  style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; background: linear-gradient(135deg, #10b981, #059669); color: white; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 0.875rem; transition: all 0.2s; box-shadow: 0 2px 8px rgba(16, 185, 129, 0.25);"
                  onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(16, 185, 129, 0.35)';"
                  onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(16, 185, 129, 0.25)';">
                  <ion-icon name="card-outline"></ion-icon>
                  Pay Now
                </a>
              </td>
              <?php
                }
              } else {
                ?>
                <tr>
                  <td colspan="10" style="text-align: center; padding: var(--spacing-xl);">
                    <div style="display: flex; flex-direction: column; align-items: center; gap: var(--spacing-md); padding: var(--spacing-xl);">
                      <ion-icon name="bag-outline" style="font-size: 4rem; color: var(--gray-400);"></ion-icon>
                      <h3 style="color: var(--gray-700); margin: 0;">No Orders Yet</h3>
                      <p style="color: var(--gray-500); margin: 0;">You haven't placed any orders yet. Start shopping now!</p>
                      <a href="stock.php" style="margin-top: var(--spacing-sm); padding: 10px 20px; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white; text-decoration: none; border-radius: 8px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
                        <ion-icon name="cart-outline"></ion-icon>
                        Browse Tools
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
