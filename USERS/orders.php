<?php
  require "connection.php";
  
  if(!empty($_SESSION["id"])){
    $id = $_SESSION["id"];
    $check = mysqli_query($con,"SELECT * FROM `user` WHERE id=$id ");
    $row = mysqli_fetch_array($check);
  }
  else{
    header('location:loginuser.php');
    exit();
  } 
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
  <title>BAFRACOO - My Orders</title>
  <style>
    .orders-page {
      min-height: 100vh;
      background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    }
    
    .orders-header {
      background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
      padding: 2rem;
      color: white;
      margin-bottom: 2rem;
      border-radius: 0 0 20px 20px;
    }
    
    .orders-header h1 {
      margin: 0 0 0.5rem 0;
      font-size: 1.75rem;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    
    .orders-header p {
      margin: 0;
      opacity: 0.9;
      font-size: 0.95rem;
    }
    
    .orders-stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
      gap: 1rem;
      margin-bottom: 2rem;
      padding: 0 1.5rem;
    }
    
    .stat-card {
      background: white;
      padding: 1.25rem;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
      text-align: center;
      transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .stat-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    }
    
    .stat-value {
      font-size: 1.75rem;
      font-weight: 700;
      margin-bottom: 0.25rem;
    }
    
    .stat-label {
      font-size: 0.8rem;
      color: #64748b;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .stat-card.total .stat-value { color: #3b82f6; }
    .stat-card.pending .stat-value { color: #f59e0b; }
    .stat-card.paid .stat-value { color: #10b981; }
    .stat-card.cancelled .stat-value { color: #ef4444; }
    
    .orders-container {
      padding: 0 1.5rem 2rem;
    }
    
    .orders-table-wrapper {
      background: white;
      border-radius: 16px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
      overflow: hidden;
    }
    
    .orders-table {
      width: 100%;
      border-collapse: collapse;
    }
    
    .orders-table thead {
      background: linear-gradient(135deg, #1e293b, #334155);
    }
    
    .orders-table th {
      padding: 1rem 1.25rem;
      text-align: left;
      color: white;
      font-weight: 600;
      font-size: 0.85rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .orders-table tbody tr {
      border-bottom: 1px solid #f1f5f9;
      transition: background-color 0.2s;
    }
    
    .orders-table tbody tr:hover {
      background-color: #f8fafc;
    }
    
    .orders-table tbody tr:last-child {
      border-bottom: none;
    }
    
    .orders-table td {
      padding: 1rem 1.25rem;
      vertical-align: middle;
    }
    
    .order-id {
      font-weight: 700;
      color: #1e293b;
      font-size: 0.95rem;
    }
    
    .product-info {
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }
    
    .product-icon {
      width: 40px;
      height: 40px;
      border-radius: 10px;
      background: linear-gradient(135deg, #3b82f6, #6366f1);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 1.1rem;
    }
    
    .product-name {
      font-weight: 600;
      color: #1e293b;
    }
    
    .product-type {
      font-size: 0.8rem;
      color: #64748b;
    }
    
    .quantity-badge {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 0.35rem 0.75rem;
      background: linear-gradient(135deg, #3b82f6, #6366f1);
      color: white;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
    }
    
    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 0.4rem 0.85rem;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
    }
    
    .status-pending {
      background: #fef3c7;
      color: #b45309;
    }
    
    .status-pending-payment {
      background: #ffedd5;
      color: #c2410c;
    }
    
    .status-paid {
      background: #d1fae5;
      color: #047857;
    }
    
    .status-completed {
      background: #cffafe;
      color: #0e7490;
    }
    
    .status-cancelled {
      background: #fee2e2;
      color: #b91c1c;
    }
    
    .price-cell {
      font-weight: 600;
      color: #1e293b;
    }
    
    .price-cell .currency {
      font-size: 0.75rem;
      color: #64748b;
      margin-left: 2px;
    }
    
    .total-price {
      color: #10b981;
      font-weight: 700;
    }
    
    .date-cell {
      color: #64748b;
      font-size: 0.9rem;
    }
    
    .action-btn {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 0.5rem 1rem;
      border-radius: 8px;
      font-size: 0.8rem;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.2s;
      border: none;
      cursor: pointer;
    }
    
    .action-btn.pay {
      background: linear-gradient(135deg, #10b981, #059669);
      color: white;
    }
    
    .action-btn.pay:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(16, 185, 129, 0.35);
    }
    
    .action-btn.view {
      background: #e0f2fe;
      color: #0284c7;
    }
    
    .action-btn.view:hover {
      background: #bae6fd;
    }
    
    .empty-state {
      text-align: center;
      padding: 4rem 2rem;
    }
    
    .empty-state-icon {
      width: 100px;
      height: 100px;
      background: linear-gradient(135deg, #e2e8f0, #cbd5e1);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1.5rem;
    }
    
    .empty-state-icon ion-icon {
      font-size: 3rem;
      color: #94a3b8;
    }
    
    .empty-state h3 {
      margin: 0 0 0.5rem 0;
      color: #1e293b;
      font-size: 1.25rem;
    }
    
    .empty-state p {
      margin: 0 0 1.5rem 0;
      color: #64748b;
    }
    
    .empty-state .shop-btn {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.75rem 1.5rem;
      background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
      color: white;
      text-decoration: none;
      border-radius: 10px;
      font-weight: 600;
      transition: all 0.2s;
    }
    
    .empty-state .shop-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3);
    }
    
    @media (max-width: 768px) {
      .orders-table-wrapper {
        overflow-x: auto;
      }
      
      .orders-table {
        min-width: 700px;
      }
      
      .orders-stats {
        grid-template-columns: repeat(2, 1fr);
      }
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
              <a href="cart.php" class="nav-link">
                <ion-icon name="cart-outline" class="nav-icon"></ion-icon>
                <span class="nav-text">Shopping Cart</span>
                <?php 
                $cart_count_query = mysqli_query($con,"SELECT COUNT(ci.id) as cnt FROM cart c JOIN cart_items ci ON c.id = ci.cart_id WHERE c.user_id='$id' AND c.status='ACTIVE'");
                $cart_count = $cart_count_query ? mysqli_fetch_array($cart_count_query)['cnt'] : 0;
                if($cart_count > 0): ?>
                  <span class="nav-badge" style="background: #10b981;"><?php echo $cart_count; ?></span>
                <?php endif; ?>
              </a>
            </li>
            <li class="nav-item">
              <a href="orders.php" class="nav-link active">
                <ion-icon name="bag-handle-outline" class="nav-icon"></ion-icon>
                <span class="nav-text">My Orders</span>
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
    <main class="main-content orders-page">
      <!-- Header -->
      <div class="orders-header">
        <h1><ion-icon name="bag-handle-outline"></ion-icon> My Orders</h1>
        <p>Track and manage all your purchases</p>
      </div>
      
      <!-- Order Statistics -->
      <?php
      // Get order statistics for this user - FIXED QUERY
      $stats_query = mysqli_query($con, "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'Pending Payment' THEN 1 ELSE 0 END) as pending_payment,
        SUM(CASE WHEN status = 'Paid' OR status = 'Completed' THEN 1 ELSE 0 END) as paid,
        SUM(CASE WHEN status LIKE '%Cancel%' OR status LIKE '%Failed%' THEN 1 ELSE 0 END) as cancelled
        FROM `order` WHERE user_id = '$id'");
      $stats = mysqli_fetch_assoc($stats_query);
      ?>
      <div class="orders-stats">
        <div class="stat-card total">
          <div class="stat-value"><?php echo $stats['total'] ?? 0; ?></div>
          <div class="stat-label">Total Orders</div>
        </div>
        <div class="stat-card pending">
          <div class="stat-value"><?php echo ($stats['pending'] ?? 0) + ($stats['pending_payment'] ?? 0); ?></div>
          <div class="stat-label">Pending</div>
        </div>
        <div class="stat-card paid">
          <div class="stat-value"><?php echo $stats['paid'] ?? 0; ?></div>
          <div class="stat-label">Paid</div>
        </div>
        <div class="stat-card cancelled">
          <div class="stat-value"><?php echo $stats['cancelled'] ?? 0; ?></div>
          <div class="stat-label">Cancelled</div>
        </div>
      </div>
      
      <!-- Orders Table -->
      <div class="orders-container">
        <div style="display: flex; justify-content: flex-end; margin-bottom: 1rem;">
          <button onclick="exportMyOrdersPDF()" style="padding: 10px 20px; border-radius: 10px; background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; font-weight: 600; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); transition: all 0.2s;">
            <ion-icon name="download-outline"></ion-icon>
            Export PDF
          </button>
        </div>
        <div class="orders-table-wrapper">
          <table class="orders-table">
            <thead>
              <tr>
                <th>Order ID</th>
                <th>Product</th>
                <th>Quantity</th>
                <th>Unit Price</th>
                <th>Total</th>
                <th>Status</th>
                <th>Date</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php
              // FIXED QUERY - Only select from order table (no joins to non-existent columns)
              $sql = "SELECT * FROM `order` WHERE user_id = '$id' ORDER BY id DESC";
              $result = mysqli_query($con, $sql);
              
              if ($result && mysqli_num_rows($result) > 0) {
                while ($order = mysqli_fetch_array($result)) {
                  $status = $order['status'] ?? 'Pending';
                  $status_class = 'status-pending';
                  
                  if(stripos($status, 'paid') !== false && stripos($status, 'pending') === false) {
                    $status_class = 'status-paid';
                  } elseif(stripos($status, 'pending payment') !== false) {
                    $status_class = 'status-pending-payment';
                  } elseif(stripos($status, 'complete') !== false) {
                    $status_class = 'status-completed';
                  } elseif(stripos($status, 'cancel') !== false || stripos($status, 'fail') !== false) {
                    $status_class = 'status-cancelled';
                  }
              ?>
              <tr>
                <td class="order-id">#<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?></td>
                <td>
                  <div class="product-info">
                    <div class="product-icon">
                      <ion-icon name="cube-outline"></ion-icon>
                    </div>
                    <div>
                      <div class="product-name"><?php echo htmlspecialchars($order['u_toolname']); ?></div>
                      <div class="product-type"><?php echo htmlspecialchars($order['u_type']); ?></div>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="quantity-badge">
                    <ion-icon name="layers-outline"></ion-icon>
                    <?php echo number_format($order['u_itemsnumber']); ?>
                  </span>
                </td>
                <td class="price-cell">
                  <?php echo number_format($order['u_price']); ?><span class="currency">RWF</span>
                </td>
                <td class="price-cell total-price">
                  <?php echo number_format($order['u_totalprice']); ?><span class="currency">RWF</span>
                </td>
                <td>
                  <span class="status-badge <?php echo $status_class; ?>">
                    <?php echo $status; ?>
                  </span>
                </td>
                <td class="date-cell">
                  <?php echo date('M d, Y', strtotime($order['u_date'])); ?>
                </td>
                <td>
                  <?php if($status == 'Pending Payment' || $status == 'Payment Failed' || $status == 'Payment Cancelled'): ?>
                  <a href="pay.php?o_id=<?php echo $order['id']; ?>" class="action-btn pay">
                    <ion-icon name="card-outline"></ion-icon>
                    Pay Now
                  </a>
                  <?php elseif($status == 'Paid' || $status == 'Completed'): ?>
                  <span class="action-btn view" style="cursor: default;">
                    <ion-icon name="checkmark-circle-outline"></ion-icon>
                    Completed
                  </span>
                  <?php else: ?>
                  <span class="action-btn view" style="cursor: default;">
                    <ion-icon name="time-outline"></ion-icon>
                    Processing
                  </span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php
                }
              } else {
              ?>
              <tr>
                <td colspan="8">
                  <div class="empty-state">
                    <div class="empty-state-icon">
                      <ion-icon name="bag-outline"></ion-icon>
                    </div>
                    <h3>No Orders Yet</h3>
                    <p>You haven't placed any orders yet. Start shopping now!</p>
                    <a href="stock.php" class="shop-btn">
                      <ion-icon name="cart-outline"></ion-icon>
                      Browse Products
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
  
  <script>
    function exportMyOrdersPDF() {
      window.open('export_pdf.php?type=my_orders', '_blank');
    }
    
    // Mobile sidebar toggle
    document.querySelector('.mobile-menu-btn')?.addEventListener('click', function() {
      document.querySelector('.sidebar').classList.toggle('active');
      document.querySelector('.sidebar-overlay').classList.toggle('active');
    });
    
    document.querySelector('.sidebar-overlay')?.addEventListener('click', function() {
      document.querySelector('.sidebar').classList.remove('active');
      this.classList.remove('active');
    });
  </script>
</body>
</html>