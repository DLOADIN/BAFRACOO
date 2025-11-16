<?php
  require "connection.php";
  require "../InventoryManager.php"; // Include the inventory manager
  
  if(!empty($_SESSION["id"])){
    $id = $_SESSION["id"];
    $check = mysqli_query($con,"SELECT * FROM `user` WHERE id=$id ");
    $row = mysqli_fetch_array($check);
  }
  else{
    header('location:loginuser.php');
  } 
  
  // Initialize Inventory Manager
  $inventoryManager = new InventoryManager($con);
  
  // Handle form submission
  if(isset($_POST['order_tool'])){
    $user_id = mysqli_real_escape_string($con, $_POST['user_id']);
    $tool_id = mysqli_real_escape_string($con, $_POST['tool_id']);
    $tool_name = mysqli_real_escape_string($con, $_POST['u_toolname']);
    $quantity = (int)$_POST['u_itemsnumber'];
    $type = mysqli_real_escape_string($con, $_POST['u_type']);
    $description = mysqli_real_escape_string($con, $_POST['u_tooldescription']);
    $price = (float)$_POST['u_price'];
    $total_price = $price * $quantity;
    $order_date = date('Y-m-d');
    
    // Check available stock using inventory manager
    $available_stock = $inventoryManager->getAvailableStock($tool_id);
    
    if($available_stock < $quantity) {
      $error_message = "Insufficient stock! Only $available_stock items available.";
    } else {
      // Insert order first
      $order_query = "INSERT INTO `order` (user_id, u_toolname, u_itemsnumber, u_type, u_tooldescription, u_date, u_price, u_totalprice, status) 
                      VALUES ('$user_id', '$tool_name', '$quantity', '$type', '$description', '$order_date', '$price', '$total_price', 'PENDING')";
      
      if(mysqli_query($con, $order_query)) {
        $order_id = mysqli_insert_id($con);
        
        // Process inventory using FIFO/LIFO
        $result = $inventoryManager->processOrder($tool_id, $quantity, $order_id, $price);
        
        if($result['success']) {
          $success_message = "Order placed successfully! Method used: " . $result['method_used'] . ". Remaining stock: " . $result['remaining_stock'];
          // Redirect to prevent resubmission
          header("Location: orders.php?success=1");
          exit();
        } else {
          $error_message = $result['message'];
          // Delete the order since inventory couldn't be processed
          mysqli_query($con, "DELETE FROM `order` WHERE id = '$order_id'");
        }
      } else {
        $error_message = "Error placing order. Please try again.";
      }
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
  <link rel="stylesheet" href="../CSS/modern-forms.css">
  <link rel="shortcut icon" href="../images/Capture.JPG" type="image/x-icon">
  <script src="https://kit.fontawesome.com/14ff3ea278.js" crossorigin="anonymous"></script>
  <title>BAFRACOO - Browse Tools</title>
    <!-- <script src="../JS/file.js"></script> -->

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
              <a href="stock.php" class="nav-link active">
                <ion-icon name="cube-outline" class="nav-icon"></ion-icon>
                <span class="nav-text">Browse Tools</span>
              </a>
            </li>
            <li class="nav-item">
              <a href="orders.php" class="nav-link">
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
          <h1 class="page-title">Browse Tools</h1>
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
          <h2 class="content-title">Available Tools & Equipment</h2>
          <p class="content-subtitle">Browse and order construction tools</p>
        </div>
        
        <div class="table-container">
          <table class="modern-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Tool Name</th>
                <th>Type</th>
                <th>Description</th>
                <th>Available Stock</th>
                <th>Inventory Method</th>
                <th>Price</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
            <?php
            $sql=mysqli_query($con,"SELECT * FROM `tool`");
            $row_count = mysqli_num_rows($sql);
            if($row_count){
              while($tool_row=mysqli_fetch_array($sql))
              { 
                // Get available stock and inventory method
                $available_stock = $inventoryManager->getAvailableStock($tool_row['id']);
                $inventory_method = $inventoryManager->getInventoryMethod($tool_row['id']);
            ?>
            <tr>
              <td><?php echo $tool_row['id']?></td>
              <td><strong><?php echo htmlspecialchars($tool_row['u_toolname'])?></strong></td>
              <td>
                <span style="display: inline-block; padding: 4px 12px; background: var(--gray-100); color: var(--gray-700); border-radius: 12px; font-size: 0.875rem; font-weight: 500;">
                  <?php echo htmlspecialchars($tool_row['u_type'])?>
                </span>
              </td>
              <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                <?php echo htmlspecialchars($tool_row['u_tooldescription'])?>
              </td>
              <td>
                <div style="display: flex; align-items: center; gap: 6px;">
                  <span style="display: inline-block; padding: 4px 10px; background: <?php echo $available_stock > 0 ? '#10b981' : '#ef4444'; ?>; color: white; border-radius: 8px; font-size: 0.875rem; font-weight: 600;">
                    <?php echo $available_stock; ?> units
                  </span>
                </div>
              </td>
              <td>
                <span style="display: inline-block; padding: 4px 10px; background: <?php echo $inventory_method === 'FIFO' ? '#3b82f6' : '#8b5cf6'; ?>; color: white; border-radius: 6px; font-size: 0.75rem; font-weight: 600; letter-spacing: 0.5px;">
                  <?php echo $inventory_method; ?>
                </span>
              </td>
              <td>
                <strong style="color: var(--primary-color);">RWF <?php echo number_format($tool_row['u_price'])?></strong>
              </td>
              <td>  
                <?php if($available_stock > 0): ?>
                <a href="stock.php?id=<?php echo $tool_row['id']?>" 
                  style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 0.875rem; transition: all 0.2s; box-shadow: 0 2px 8px rgba(37, 99, 235, 0.25);"
                  onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(37, 99, 235, 0.35)';"
                  onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(37, 99, 235, 0.25)';">
                  <ion-icon name="cart-outline"></ion-icon>
                  Order Now
                </a>
                <?php else: ?>
                <span style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; background: var(--gray-300); color: var(--gray-600); border-radius: 8px; font-weight: 600; font-size: 0.875rem;">
                  <ion-icon name="ban-outline"></ion-icon>
                  Out of Stock
                </span>
                <?php endif; ?>
              </td>
              <?php
            }
          } else {
            ?>
            <tr>
              <td colspan="8" style="text-align: center; padding: var(--spacing-xl); color: var(--gray-500);">
                No tools available
              </td>
            </tr>
            <?php
          }
              ?>
            </tbody>
          </table>
        </div>
      </div>

        <!-- Order Form Modal/Section -->
        <?php
        if(isset($_GET['id'])){
          $tool_id = $_GET['id'];
          $tool_sql = mysqli_query($con,"SELECT * FROM tool WHERE id='$tool_id' ");
          $tool_row = mysqli_fetch_array($tool_sql);
          
          $user_sql = mysqli_query($con,"SELECT * FROM `user` WHERE id='$id' ");
          $user_row = mysqli_fetch_array($user_sql);
        ?>
        <div class="content-wrapper" style="margin-top: var(--spacing-xl);">
          <!-- Breadcrumb -->
          <div style="margin-bottom: var(--spacing-xl); display: flex; align-items: center; gap: var(--spacing-sm); color: var(--gray-600); font-size: 0.875rem;">
            <a href="stock.php" style="color: var(--primary-color); text-decoration: none;">
              <ion-icon name="cube-outline"></ion-icon> Browse Tools
            </a>
            <span>/</span>
            <span style="color: var(--gray-900); font-weight: 500;">Place Order</span>
          </div>

          <div class="dashboard-card" style="max-width: 900px; margin: 0 auto;">
            <div class="card-header">
              <h3 style="font-size: 1.25rem; font-weight: 600; color: var(--gray-900); margin: 0;">
                <ion-icon name="cart-outline" style="margin-right: var(--spacing-sm);"></ion-icon>
                Order Tool
              </h3>
              <p style="margin: var(--spacing-sm) 0 0 0; color: var(--gray-600); font-size: 0.875rem;">
                Complete the form below to place your order
              </p>
            </div>
            
            <div style="padding: var(--spacing-xl);">
              
              <!-- Display success/error messages -->
              <?php if(isset($success_message)): ?>
              <div style="padding: var(--spacing-md); margin-bottom: var(--spacing-lg); background: #dcfce7; border: 1px solid #16a34a; border-radius: var(--radius-md); color: #15803d;">
                <div style="display: flex; align-items: center; gap: var(--spacing-sm);">
                  <ion-icon name="checkmark-circle-outline"></ion-icon>
                  <span><?php echo $success_message; ?></span>
                </div>
              </div>
              <?php endif; ?>
              
              <?php if(isset($error_message)): ?>
              <div style="padding: var(--spacing-md); margin-bottom: var(--spacing-lg); background: #fef2f2; border: 1px solid #ef4444; border-radius: var(--radius-md); color: #dc2626;">
                <div style="display: flex; align-items: center; gap: var(--spacing-sm);">
                  <ion-icon name="alert-circle-outline"></ion-icon>
                  <span><?php echo $error_message; ?></span>
                </div>
              </div>
              <?php endif; ?>
              
              <!-- Stock Information -->
              <?php 
              $current_stock = $inventoryManager->getAvailableStock($tool_id);
              $current_method = $inventoryManager->getInventoryMethod($tool_id);
              ?>
              <div style="padding: var(--spacing-md); margin-bottom: var(--spacing-lg); background: #f8fafc; border: 1px solid #e2e8f0; border-radius: var(--radius-md);">
                <h4 style="margin: 0 0 var(--spacing-sm) 0; color: var(--gray-900);">Stock Information</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-md);">
                  <div>
                    <strong>Available Stock:</strong> 
                    <span style="color: <?php echo $current_stock > 0 ? '#16a34a' : '#dc2626'; ?>;">
                      <?php echo $current_stock; ?> units
                    </span>
                  </div>
                  <div>
                    <strong>Inventory Method:</strong> 
                    <span style="padding: 2px 8px; background: <?php echo $current_method === 'FIFO' ? '#3b82f6' : '#8b5cf6'; ?>; color: white; border-radius: 4px; font-size: 0.75rem;">
                      <?php echo $current_method; ?>
                    </span>
                  </div>
                </div>
              </div>

              <form method="POST" action="" id="orderToolForm">
                <input type="hidden" name="user_id" value="<?php echo $user_row['id']; ?>">
                <input type="hidden" name="tool_id" value="<?php echo $tool_id; ?>">
                <input type="hidden" name="u_price" value="<?php echo $tool_row['u_price']; ?>" id="unit_price">
                
                <!-- Tool Name and Quantity Row -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-lg); margin-bottom: var(--spacing-lg);">;
                  <!-- Tool Name -->
                  <div class="form-group">
                    <label for="u_toolname" class="form-label">
                      <ion-icon name="construct-outline" style="margin-right: 4px;"></ion-icon>
                      Tool Name *
                    </label>
                    <input 
                      type="text" 
                      id="u_toolname" 
                      name="u_toolname" 
                      class="form-control" 
                      value="<?php echo htmlspecialchars($tool_row['u_toolname']); ?>" 
                      readonly 
                      required
                      style="width: 100%; padding: var(--spacing-md); border: 1px solid var(--gray-300); border-radius: var(--radius-md); font-size: 1rem; background: var(--gray-50);">
                  </div>
                  
                  <!-- Number of Items -->
                  <div class="form-group">
                    <label for="u_itemsnumber" class="form-label">
                      <ion-icon name="layers-outline" style="margin-right: 4px;"></ion-icon>
                      Number of Items * (Max: <?php echo $current_stock; ?>)
                    </label>
                    <input 
                      type="number" 
                      id="u_itemsnumber" 
                      name="u_itemsnumber" 
                      class="form-control" 
                      min="1" 
                      max="<?php echo $current_stock; ?>"
                      value="1" 
                      placeholder="Enter quantity"
                      oninput="calculateTotal()"
                      required
                      <?php echo $current_stock == 0 ? 'disabled' : ''; ?>
                      style="width: 100%; padding: var(--spacing-md); border: 1px solid var(--gray-300); border-radius: var(--radius-md); font-size: 1rem;">
                  </div>
                </div>
                
                <!-- Type and Description Row -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-lg); margin-bottom: var(--spacing-lg);">
                  <!-- Type -->
                  <div class="form-group">
                    <label for="u_type" class="form-label">
                      <ion-icon name="pricetag-outline" style="margin-right: 4px;"></ion-icon>
                      Type/Category *
                    </label>
                    <input 
                      type="text" 
                      id="u_type" 
                      name="u_type" 
                      class="form-control" 
                      value="<?php echo htmlspecialchars($tool_row['u_type']); ?>" 
                      readonly 
                      required
                      style="width: 100%; padding: var(--spacing-md); border: 1px solid var(--gray-300); border-radius: var(--radius-md); font-size: 1rem; background: var(--gray-50);">
                  </div>
                  
                  <!-- Tool Description -->
                  <div class="form-group">
                    <label for="u_tooldescription" class="form-label">
                      <ion-icon name="document-text-outline" style="margin-right: 4px;"></ion-icon>
                      Description *
                    </label>
                    <input 
                      type="text" 
                      id="u_tooldescription" 
                      name="u_tooldescription" 
                      class="form-control" 
                      value="<?php echo htmlspecialchars($tool_row['u_tooldescription']); ?>" 
                      readonly 
                      required
                      style="width: 100%; padding: var(--spacing-md); border: 1px solid var(--gray-300); border-radius: var(--radius-md); font-size: 1rem; background: var(--gray-50);">
                  </div>
                </div>
                
                <!-- Price and Date Row -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-lg); margin-bottom: var(--spacing-xl);">
                  <!-- Unit Price -->
                  <div class="form-group">
                    <label for="u_price" class="form-label">
                      <ion-icon name="cash-outline" style="margin-right: 4px;"></ion-icon>
                      Unit Price (RWF) *
                    </label>
                    <input 
                      type="number" 
                      id="u_price" 
                      name="u_price" 
                      class="form-control" 
                      value="<?php echo $tool_row['u_price']; ?>" 
                      readonly
                      style="width: 100%; padding: var(--spacing-md); border: 1px solid var(--gray-300); border-radius: var(--radius-md); font-size: 1rem; background: var(--gray-50);">
                    <small style="display: block; margin-top: var(--spacing-xs); color: var(--gray-600);">
                      RWF <?php echo number_format($tool_row['u_price']); ?>
                    </small>
                  </div>
                  
                  <!-- Date -->
                  <div class="form-group">
                    <label for="u_date" class="form-label">
                      <ion-icon name="calendar-outline" style="margin-right: 4px;"></ion-icon>
                      Order Date *
                    </label>
                    <input 
                      type="text" 
                      id="u_date" 
                      name="u_date" 
                      class="form-control" 
                      value="<?php echo date('Y-m-d'); ?>" 
                      readonly
                      style="width: 100%; padding: var(--spacing-md); border: 1px solid var(--gray-300); border-radius: var(--radius-md); font-size: 1rem; background: var(--gray-50);">
                  </div>
                </div>
                
                <!-- Total Price Display -->
                <div style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); padding: var(--spacing-xl); border-radius: var(--radius-lg); margin-bottom: var(--spacing-xl); text-align: center;">
                  <div style="color: rgba(255, 255, 255, 0.9); font-size: 0.875rem; font-weight: 500; margin-bottom: var(--spacing-xs); text-transform: uppercase; letter-spacing: 0.5px;">
                    <ion-icon name="calculator-outline" style="vertical-align: middle; margin-right: 6px;"></ion-icon>
                    Total Amount
                  </div>
                  <div id="total_display" style="color: white; font-size: 2rem; font-weight: 700; letter-spacing: 1px;">
                    RWF <?php echo number_format($tool_row['u_price']); ?>
                  </div>
                </div>
                
                <!-- Terms and Conditions -->
                <div style="margin-bottom: var(--spacing-xl);">
                  <label style="display: flex; align-items: start; gap: var(--spacing-sm); cursor: pointer; padding: var(--spacing-md); background: var(--gray-50); border-radius: var(--radius-md); border: 1px solid var(--gray-200);">
                    <input type="checkbox" id="terms" name="terms" required 
                      style="width: 18px; height: 18px; margin-top: 2px; cursor: pointer; accent-color: var(--primary-color); flex-shrink: 0;">
                    <span style="color: var(--gray-700); font-size: 0.875rem; line-height: 1.5;">
                      I confirm that I have read and accepted the <a href="#" style="color: var(--primary-color); text-decoration: none; font-weight: 600;">terms and conditions</a> and <a href="#" style="color: var(--primary-color); text-decoration: none; font-weight: 600;">privacy policy</a>
                    </span>
                  </label>
                </div>
                
                <!-- Action Buttons -->
                <div style="display: flex; gap: var(--spacing-md); justify-content: flex-end; padding-top: var(--spacing-md); border-top: 1px solid var(--gray-200);">
                  <a href="stock.php" class="btn btn-secondary" style="padding: 12px 24px; background: white; color: var(--gray-700); border: 1px solid var(--gray-300); border-radius: var(--radius-md); font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s;">
                    <ion-icon name="close-outline"></ion-icon>
                    Cancel
                  </a>
                  <button type="submit" name="order_tool" class="btn btn-primary" 
                    style="padding: 12px 32px; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white; border: none; border-radius: var(--radius-md); font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);"
                    <?php echo $current_stock == 0 ? 'disabled' : ''; ?>
                    onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(37, 99, 235, 0.35)';"
                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(37, 99, 235, 0.25)';">
                    <ion-icon name="checkmark-circle-outline"></ion-icon>
                    <?php echo $current_stock > 0 ? 'Complete Purchase' : 'Out of Stock'; ?>
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
        
        <script>
        function calculateTotal() {
          const quantity = document.querySelector('input[name="u_itemsnumber"]').value || 1;
          const unitPrice = <?php echo $tool_row['u_price']; ?>;
          const total = quantity * unitPrice;
          document.getElementById('total_display').textContent = 'RWF ' + total.toLocaleString('en-US');
        }
        
        // Auto-scroll to form when order is selected
        window.addEventListener('load', function() {
          const form = document.getElementById('orderToolForm');
          if(form) {
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
          }
        });
        </script>
        <?php } ?>
      </div>
    </main>
  </div>

<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>
