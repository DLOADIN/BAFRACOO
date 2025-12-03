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
  
  // Handle return request
  if(isset($_POST['request_return'])) {
    $order_id = (int)$_POST['order_id'];
    $return_reason = mysqli_real_escape_string($con, $_POST['return_reason']);
    
    // Get order details
    $order_query = "SELECT * FROM `order` WHERE id = $order_id AND user_id = $id";
    $order_result = mysqli_query($con, $order_query);
    
    if($order_row = mysqli_fetch_array($order_result)) {
      $tool_id = $order_row['tool_id'] ?? 0;
      $customer_name = $row['firstName'] . ' ' . $row['lastName'];
      $purchase_date = $order_row['u_date'];
      
      try {
        $result = $inventoryManager->registerReturn($tool_id, $customer_name, $purchase_date, $return_reason, '');
        if($result) {
          $success_message = "Return request submitted successfully!";
        } else {
          $error_message = "Failed to submit return request. Please try again.";
        }
      } catch(Exception $e) {
        $error_message = "Error: " . $e->getMessage();
      }
    } else {
      $error_message = "Invalid order selected.";
    }
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
                <span class="nav-text">Inter Purchases</span>
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
      
          <div class="content-wrapper">
            <?php if(isset($success_message)): ?>
            <div style="padding: 1rem; margin-bottom: 1rem; border-radius: 8px; background: #dcfce7; border: 1px solid #16a34a; color: #15803d;">
              <?php echo $success_message; ?>
            </div>
            <?php endif; ?>
            <?php if(isset($error_message)): ?>
            <div style="padding: 1rem; margin-bottom: 1rem; border-radius: 8px; background: #fef2f2; border: 1px solid #ef4444; color: #dc2626;">
              <?php echo $error_message; ?>
            </div>
            <?php endif; ?>
            
            <div class="content-header">
              <h2 class="content-title">Your Order History</h2>
              <p class="content-subtitle">Track and manage your orders across all locations</p>
            </div>
        
          <div class="table-container">
            <table class="modern-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Tool Name</th>
                  <th>Quantity</th>
                  <th>Location</th>
                  <th>Status</th>
                  <th>Unit Price</th>
                  <th>Total Price</th>
                  <th>Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
              <?php
              $number=0;
              // Updated query to include location information
              $sql = "SELECT `order`.*, user.u_name, user.firstName, user.lastName, l.location_name 
                      FROM `order` 
                      INNER JOIN user ON `order`.user_id = user.id 
                      LEFT JOIN locations l ON `order`.location_id = l.id
                      WHERE `order`.user_id = '$id' 
                      ORDER BY `order`.u_date DESC";
              $result = mysqli_query($con, $sql);
              if ($result && mysqli_num_rows($result) > 0) {
                while ($order_row = mysqli_fetch_array($result)) {
                  $number++;
                  $days_since_order = floor((time() - strtotime($order_row['u_date'])) / 86400);
                  $can_return = $days_since_order <= 30 && $order_row['status'] !== 'CANCELLED';
              ?>
              <tr>
                <td><strong>#<?php echo str_pad($number, 3, '0', STR_PAD_LEFT)?></strong></td>
                <td><strong><?php echo htmlspecialchars($order_row['u_toolname'])?></strong></td>
                <td>
                  <span style="display: inline-block; padding: 4px 12px; background: var(--primary-color); color: white; border-radius: 12px; font-size: 0.875rem; font-weight: 600;">
                    <?php echo $order_row['u_itemsnumber']?> items
                  </span>
                </td>
                <td>
                  <?php if($order_row['location_name']): ?>
                  <span style="display: inline-block; padding: 4px 10px; background: #06b6d4; color: white; border-radius: 6px; font-size: 0.75rem; font-weight: 600;">
                    <ion-icon name="location-outline" style="margin-right: 2px;"></ion-icon>
                    <?php echo htmlspecialchars($order_row['location_name']); ?>
                  </span>
                  <?php else: ?>
                  <span style="color: #6b7280; font-size: 0.875rem;">Location not specified</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php
                  $status = $order_row['status'] ?? 'Pending';
                  $status_colors = [
                    'Pending' => '#f59e0b',
                    'Pending Payment' => '#f97316',
                    'Paid' => '#10b981',
                    'Payment Cancelled' => '#ef4444',
                    'Payment Failed' => '#dc2626',
                    'CONFIRMED' => '#3b82f6', 
                    'SHIPPED' => '#8b5cf6',
                    'DELIVERED' => '#10b981',
                    'Completed' => '#059669',
                    'CANCELLED' => '#ef4444'
                  ];
                  $color = $status_colors[$status] ?? '#6b7280';
                  ?>
                  <span style="display: inline-block; padding: 4px 10px; background: <?php echo $color; ?>; color: white; border-radius: 6px; font-size: 0.75rem; font-weight: 600;">
                    <?php echo $status; ?>
                  </span>
                </td>
                <td><strong style="color: var(--primary-color);">RWF <?php echo number_format($order_row['u_price'])?></strong></td>
                <td><strong style="color: #059669;">RWF <?php echo number_format($order_row['u_totalprice'])?></strong></td>
                <td>
                  <div style="font-size: 0.875rem;">
                    <?php echo date('M d, Y', strtotime($order_row['u_date'])); ?>
                    <br><small style="color: #6b7280;"><?php echo $days_since_order; ?> days ago</small>
                  </div>
                </td>
                <td>
                  <div style="display: flex; gap: 4px; flex-direction: column;">
                    <?php 
                    // Show Pay button only for unpaid orders
                    $payment_statuses = ['Pending Payment', 'Payment Cancelled', 'Payment Failed'];
                    if(in_array($status, $payment_statuses)): 
                    ?>
                    <a href="pay.php?o_id=<?php echo $order_row['id']?>" 
                       style="padding: 6px 12px; background: linear-gradient(135deg, #10b981, #059669); color: white; text-decoration: none; border-radius: 4px; font-size: 0.75rem; text-align: center; white-space: nowrap; display: inline-flex; align-items: center; justify-content: center; gap: 4px;">
                      <ion-icon name="card-outline"></ion-icon> Pay Now
                    </a>
                    <?php elseif($status == 'Paid' || $status == 'Completed'): ?>
                    <span style="padding: 6px 12px; background: #dcfce7; color: #15803d; border-radius: 4px; font-size: 0.75rem; text-align: center; white-space: nowrap; display: inline-flex; align-items: center; justify-content: center; gap: 4px;">
                      <ion-icon name="checkmark-circle-outline"></ion-icon> Paid
                    </span>
                    <?php endif; ?>
                    
                    <?php if($can_return && ($status == 'Paid' || $status == 'Completed')): ?>
                    <button onclick="requestReturn(<?php echo $order_row['id']; ?>, '<?php echo addslashes($order_row['u_toolname']); ?>')" 
                            style="padding: 4px 8px; background: #f59e0b; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.75rem; white-space: nowrap;">
                      <ion-icon name="return-up-back-outline"></ion-icon> Return
                    </button>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
              <?php
                }
              } else {
                ?>
                <tr>
                  <td colspan="9" style="text-align: center; padding: var(--spacing-xl);">
                    <div style="display: flex; flex-direction: column; align-items: center; gap: var(--spacing-md); padding: var(--spacing-xl);">
                      <ion-icon name="bag-outline" style="font-size: 4rem; color: var(--gray-400);"></ion-icon>
                      <h3 style="color: var(--gray-700); margin: 0;">No Orders Yet</h3>
                      <p style="color: var(--gray-500); margin: 0;">You haven't placed any orders yet. Start shopping now!</p>
                      <a href="stock.php" style="margin-top: var(--spacing-sm); padding: 10px 20px; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white; text-decoration: none; border-radius: 8px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
                        <ion-icon name="cart-outline"></ion-icon>
                        Inter Purchases
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

  <!-- Return Request Modal -->
  <div id="returnModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
    <div style="background-color: white; margin: 10% auto; padding: 2rem; border-radius: 12px; width: 90%; max-width: 500px;">
      <h3>Request Return</h3>
      <form method="POST" id="returnForm">
        <input type="hidden" name="order_id" id="return_order_id">
        
        <div id="returnOrderDetails" style="background: #f8fafc; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
          <!-- Order details will be populated by JavaScript -->
        </div>
        
        <div style="margin-bottom: 1rem;">
          <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Return Reason:</label>
          <select name="return_reason" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            <option value="">Select reason</option>
            <option value="Defective Product">Defective Product</option>
            <option value="Wrong Item">Wrong Item Received</option>
            <option value="Not as Described">Not as Described</option>
            <option value="Changed Mind">Changed Mind</option>
            <option value="Damaged in Shipping">Damaged in Shipping</option>
            <option value="Quality Issues">Quality Issues</option>
            <option value="Other">Other</option>
          </select>
        </div>
        
        <div style="margin-bottom: 1rem;">
          <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Additional Notes:</label>
          <textarea name="additional_notes" rows="3" placeholder="Describe the issue in detail..." style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; resize: vertical;"></textarea>
        </div>
        
        <div style="background: #fef3c7; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
          <p style="margin: 0; font-size: 0.875rem; color: #92400e;">
            <ion-icon name="information-circle-outline"></ion-icon>
            <strong>Return Policy:</strong> Returns are accepted within 30 days of purchase. Refund amount depends on item condition.
          </p>
        </div>
        
        <div style="display: flex; gap: 1rem; justify-content: flex-end;">
          <button type="button" onclick="closeReturnModal()" style="padding: 10px 20px; background: #6b7280; color: white; border: none; border-radius: 6px; cursor: pointer;">
            Cancel
          </button>
          <button type="submit" name="request_return" style="padding: 10px 20px; background: #f59e0b; color: white; border: none; border-radius: 6px; cursor: pointer;">
            <ion-icon name="return-up-back-outline"></ion-icon> Submit Return Request
          </button>
        </div>
      </form>
    </div>
  </div>

  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
  
  <script>
    function requestReturn(orderId, toolName) {
      document.getElementById('return_order_id').value = orderId;
      document.getElementById('returnOrderDetails').innerHTML = 
        '<h4 style="margin: 0 0 0.5rem 0;">Order Details</h4>' +
        '<p style="margin: 0.25rem 0;"><strong>Tool:</strong> ' + toolName + '</p>' +
        '<p style="margin: 0.25rem 0;"><strong>Order ID:</strong> #' + orderId.toString().padStart(3, '0') + '</p>';
      document.getElementById('returnModal').style.display = 'block';
    }
    
    function closeReturnModal() {
      document.getElementById('returnModal').style.display = 'none';
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
      const modal = document.getElementById('returnModal');
      if (event.target === modal) {
        closeReturnModal();
      }
    }
  </script>
</body>
</html>
