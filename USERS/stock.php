<?php
  require "connection.php";
  require "../EnhancedInventoryManager.php"; // Include the enhanced inventory manager
  require_once "cart_helpers.php";
  
  // Ensure session is started (connection.php should handle this, but double-check)
  if (session_status() == PHP_SESSION_NONE) {
    session_start();
  }
  
  if(!empty($_SESSION["id"])){
    $id = (int)$_SESSION["id"]; // Ensure it's an integer
    $check = mysqli_query($con,"SELECT * FROM `user` WHERE id=$id ");
    if($check && mysqli_num_rows($check) > 0) {
      $row = mysqli_fetch_array($check);
    } else {
      // User ID in session doesn't exist in database - clear session and redirect
      session_destroy();
      header('location:loginuser.php');
      exit();
    }
  }
  else{
    header('location:loginuser.php');
    exit();
  } 
  
  // Initialize Enhanced Inventory Manager
  $inventoryManager = new EnhancedInventoryManager($con);
  
  // Get cart count for navigation badge
  $cart_count = 0;
  $cart_check = mysqli_query($con, "SELECT COUNT(ci.id) as cnt FROM cart c JOIN cart_items ci ON c.id = ci.cart_id WHERE c.user_id = $id AND c.status = 'ACTIVE'");
  if ($cart_check) {
      $cart_count = mysqli_fetch_assoc($cart_check)['cnt'] ?? 0;
  }
  
  // Handle Add to Cart
  if(isset($_POST['add_to_cart'])){
    $tool_id = (int)$_POST['tool_id'];
    $quantity = (int)$_POST['quantity'];

    // Get tool details and aggregate stock for all rows with same tool name
    $tool_check = mysqli_query($con, "SELECT u_toolname FROM tool WHERE id = $tool_id");
    $tool_data = mysqli_fetch_assoc($tool_check);
    $available = 0;
    $tool_price = 0;
    if($tool_data) {
      $tool_name = mysqli_real_escape_string($con, $tool_data['u_toolname']);
      // Aggregate stock and get weighted average price for all rows with this tool name
      $agg_query = mysqli_query($con, "
        SELECT
          SUM(u_itemsnumber) as total_stock,
          CASE
            WHEN SUM(u_itemsnumber) > 0 THEN SUM(u_price * u_itemsnumber) / SUM(u_itemsnumber)
            ELSE 0
          END as avg_price
        FROM tool
        WHERE u_toolname = '$tool_name'
      ");
      $agg_data = mysqli_fetch_assoc($agg_query);
      $available = (int)$agg_data['total_stock'];
      $tool_price = (float)$agg_data['avg_price'];

      // Get or create active cart
      $cart_result = mysqli_query($con, "SELECT id FROM cart WHERE user_id = $id AND status = 'ACTIVE' LIMIT 1");
      if($cart_result && mysqli_num_rows($cart_result) > 0) {
        $cart_id = mysqli_fetch_assoc($cart_result)['id'];
      } else {
        mysqli_query($con, "INSERT INTO cart (user_id, status, expires_at) VALUES ($id, 'ACTIVE', DATE_ADD(NOW(), INTERVAL 24 HOUR))");
        $cart_id = mysqli_insert_id($con);
      }

      // Check if item already in cart (by tool name)
      $existing = mysqli_query($con, "SELECT id, quantity FROM cart_items WHERE cart_id = $cart_id AND tool_name = '$tool_name'");
      if($existing && mysqli_num_rows($existing) > 0) {
        $existing_data = mysqli_fetch_assoc($existing);
        $new_qty = $existing_data['quantity'] + $quantity;
        if($new_qty > $available) {
          $cart_message = "Cannot add more. You already have {$existing_data['quantity']} in cart. Only $available available.";
          $cart_message_type = 'error';
        } else {
          mysqli_query($con, "UPDATE cart_items SET quantity = $new_qty, unit_price = $tool_price WHERE id = {$existing_data['id']}");
          $cart_message = "Updated cart: Now $new_qty × $tool_name";
          $cart_message_type = 'success';
          $cart_count++;
        }
      } else {
        if($quantity > $available) {
          $cart_message = "Cannot add $quantity. Only $available available.";
          $cart_message_type = 'error';
        } else {
          mysqli_query($con, "INSERT INTO cart_items (cart_id, tool_id, tool_name, quantity, unit_price) VALUES ($cart_id, $tool_id, '$tool_name', $quantity, $tool_price)");
          $cart_message = "Added $quantity × $tool_name to cart";
          $cart_message_type = 'success';
          $cart_count++;
        }
      }
    }
  }
  
  // Handle form submission
  if(isset($_POST['order_tool'])){
    // Use the session user ID (already verified at top of file)
    if(empty($id)) {
      $error_message = "User session invalid. Please <a href='loginuser.php'>login again</a>.";
    } else {
      $user_id = (int)$id;
      $tool_id = mysqli_real_escape_string($con, $_POST['tool_id']);
      $tool_name = mysqli_real_escape_string($con, $_POST['u_toolname']);
      $quantity = (int)$_POST['u_itemsnumber'];
      $location_id = isset($_POST['location_id']) ? (int)$_POST['location_id'] : 1;
      $type = mysqli_real_escape_string($con, $_POST['u_type']);
      $description = mysqli_real_escape_string($con, $_POST['u_tooldescription']);
      $price = (float)$_POST['u_price'];
      $total_price = $price * $quantity;
      $order_date = date('Y-m-d');

      // Check available stock from tool table - AGGREGATED across all rows with same name
      $available_stock = getAggregatedStock($con, $tool_name);

      // Allow user to purchase any quantity up to available stock
      if($quantity < 1) {
        $error_message = "Please select at least 1 item.";
      } else if($quantity > $available_stock) {
        $error_message = "Cannot order $quantity items. Only " . number_format($available_stock) . " available.";
      } else {
        $price_int = (int)round($price);
        $total_price_int = (int)round($total_price);
        $user_id_escaped = (int)$user_id;
        $tool_id_escaped = mysqli_real_escape_string($con, $tool_id);
        $tool_name_escaped = mysqli_real_escape_string($con, $tool_name);
        $type_escaped = mysqli_real_escape_string($con, $type);
        $description_escaped = mysqli_real_escape_string($con, $description);
        $order_date_escaped = mysqli_real_escape_string($con, $order_date);

        $order_query = "INSERT INTO `order` (user_id, tool_id, u_toolname, u_itemsnumber, u_type, u_tooldescription, u_date, u_price, u_totalprice, status) 
                        VALUES ('$user_id_escaped', '$tool_id_escaped', '$tool_name_escaped', '$quantity', '$type_escaped', '$description_escaped', '$order_date_escaped', '$price_int', '$total_price_int', 'Pending Payment')";

        if(mysqli_query($con, $order_query)) {
          $order_id = mysqli_insert_id($con);
          header("Location: pay.php?o_id=" . $order_id);
          exit();
        } else {
          $mysql_error = mysqli_error($con);
          $error_message = "Error placing order: " . htmlspecialchars($mysql_error) . ". Please try again or contact support.";
          error_log("Order insertion failed: " . $mysql_error . " | Query: " . $order_query);
        }
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
  <title>BAFRACOO - Inter Purchases</title>
    <!-- <script src="../JS/file.js"></script> -->

  <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
  <style>
    .method-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 12px;
      border-radius: 8px;
      font-size: 0.75rem;
      font-weight: 600;
      letter-spacing: 0.5px;
    }
    .method-badge.fifo {
      background: linear-gradient(135deg, #3b82f6, #2563eb);
      color: white;
    }
    .method-badge.lifo {
      background: linear-gradient(135deg, #8b5cf6, #7c3aed);
      color: white;
    }
    .batch-preview {
      margin-top: 6px;
      font-size: 0.7rem;
      color: var(--gray-500);
      display: flex;
      align-items: center;
      gap: 4px;
    }
    .batch-preview ion-icon {
      font-size: 0.85rem;
    }
    .stock-info-card {
      background: linear-gradient(135deg, #f8fafc, #f1f5f9);
      border: 1px solid var(--gray-200);
      border-radius: 12px;
      padding: 16px;
      margin-bottom: 16px;
    }
    .stock-info-card h4 {
      margin: 0 0 12px 0;
      color: var(--gray-900);
      font-size: 0.95rem;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .stock-info-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 12px;
    }
    .stock-info-item {
      text-align: center;
      padding: 8px;
      background: white;
      border-radius: 8px;
      border: 1px solid var(--gray-100);
    }
    .stock-info-item .label {
      font-size: 0.7rem;
      color: var(--gray-500);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .stock-info-item .value {
      font-size: 1.1rem;
      font-weight: 700;
      color: var(--gray-900);
      margin-top: 4px;
    }
  </style>
        <style>
        .cart-table-container {
          margin: 2.5rem auto 0 auto;
          max-width: 900px;
          background: #fff;
          border-radius: 16px;
          box-shadow: 0 2px 16px rgba(0,0,0,0.07);
          padding: 1.5rem 1.2rem;
          overflow-x: auto;
          -webkit-overflow-scrolling: touch;
        }
        .cart-table-title {
          font-size: 1.2rem;
          font-weight: 700;
          color: #1e293b;
          margin-bottom: 1rem;
        }
        .cart-table {
          width: 100%;
          min-width: 600px;
          border-collapse: collapse;
        }
        .cart-table th, .cart-table td {
          padding: 0.7rem 0.5rem;
          text-align: left;
        }
        .cart-table th {
          background: #f1f5f9;
          color: #475569;
          font-size: 1rem;
          font-weight: 600;
        }
        .cart-table td {
          border-bottom: 1px solid #e5e7eb;
          font-size: 0.98rem;
        }
        .cart-table img {
          width: 48px;
          height: 48px;
          border-radius: 8px;
          object-fit: cover;
          border: 1px solid #e5e7eb;
        }
        .cart-table .remove-btn {
          background: none;
          border: none;
          color: #dc2626;
          font-size: 1.2rem;
          cursor: pointer;
          transition: color 0.15s;
        }
        .cart-table .remove-btn:hover {
          color: #b91c1c;
        }
        .cart-table tfoot td {
          font-weight: 700;
          font-size: 1.05rem;
          color: #2563eb;
        }
        @media (max-width: 600px) {
          .cart-table-container { padding: 1rem 0.3rem; }
          .cart-table th, .cart-table td { font-size: 0.93rem; }
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
              <a href="stock.php" class="nav-link active">
                <ion-icon name="cube-outline" class="nav-icon"></ion-icon>
                <span class="nav-text">Inter Purchases</span>
              </a>
            </li>
            <li class="nav-item">
              <a href="cart.php" class="nav-link">
                <ion-icon name="cart-outline" class="nav-icon"></ion-icon>
                <span class="nav-text">Shopping Cart</span>
                <?php if($cart_count > 0): ?>
                <span class="nav-badge" style="background: #10b981;"><?php echo $cart_count; ?></span>
                <?php endif; ?>
              </a>
            </li>
            <li class="nav-item">
              <a href="orders.php" class="nav-link">
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
    <main class="main-content">
      <header class="header">
        <div class="header-left">
          <button class="mobile-menu-btn">
            <ion-icon name="menu-outline"></ion-icon>
          </button>
          <button class="sidebar-toggle">
            <ion-icon name="chevron-back-outline"></ion-icon>
          </button>
          <h1 class="page-title">Inter Purchases</h1>
        </div>
        
      </header>
      
      <!-- Page Content -->
      <div class="content-wrapper">
        
        <div class="content-header" style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
          <div>
            <h2 class="content-title">Available Tools & Equipment</h2>
            <p class="content-subtitle">Browse and order construction tools</p>
          </div>
          <button onclick="exportAvailableProductsPDF()" style="padding: 10px 20px; border-radius: 10px; background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; font-weight: 600; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);">
            <ion-icon name="download-outline"></ion-icon>
            Export Catalog PDF
          </button>
        </div>
        
        <style>
        .product-grid {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(270px, 1fr));
          gap: 2rem;
          margin-top: 2rem;
        }
        @media (max-width: 900px) {
          .product-grid {
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 1.2rem;
          }
        }
        @media (max-width: 600px) {
          .product-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
          }
        }
        .product-card {
          background: #fff;
          border-radius: 18px;
          box-shadow: 0 4px 24px rgba(0,0,0,0.08), 0 1.5px 6px rgba(0,0,0,0.03);
          padding: 1.5rem 1.2rem 1.2rem 1.2rem;
          display: flex;
          flex-direction: column;
          align-items: center;
          transition: box-shadow 0.2s, transform 0.2s;
          position: relative;
        }
        .product-card:hover {
          box-shadow: 0 8px 32px rgba(59,130,246,0.18), 0 2px 8px rgba(0,0,0,0.06);
          transform: translateY(-4px) scale(1.02);
        }
        .product-image {
          width: 160px;
          height: 160px;
          border-radius: 12px;
          object-fit: cover;
          background: #f3f4f6;
          border: 1.5px solid #e5e7eb;
          margin-bottom: 1rem;
          display: flex;
          align-items: center;
          justify-content: center;
        }
        @media (max-width: 600px) {
          .product-image {
            width: 120px;
            height: 120px;
          }
        }
        .product-title {
          font-size: 1.1rem;
          font-weight: 700;
          color: #1e293b;
          margin-bottom: 0.3rem;
          text-align: center;
        }
        .product-type {
          font-size: 0.85rem;
          color: #64748b;
          background: #f1f5f9;
          border-radius: 8px;
          padding: 2px 10px;
          margin-bottom: 0.5rem;
        }
        .product-description {
          font-size: 0.93rem;
          color: #475569;
          margin-bottom: 0.7rem;
          text-align: center;
          min-height: 38px;
        }
        .product-stock {
          font-size: 0.95rem;
          font-weight: 600;
          color: #059669;
          margin-bottom: 0.5rem;
        }
        .product-stock.low {
          color: #f59e0b;
        }
        .product-stock.out {
          color: #dc2626;
        }
        .product-price {
          font-size: 1.1rem;
          font-weight: 700;
          color: #2563eb;
          margin-bottom: 1rem;
        }
        .product-actions {
          display: flex;
          gap: 0.7rem;
          width: 100%;
          justify-content: center;
        }
        .product-actions form,
        .product-actions a {
          flex: 1;
        }
        .add-cart-btn, .buy-now-btn {
          display: flex;
          align-items: center;
          justify-content: center;
          gap: 6px;
          padding: 10px 8px;
          border-radius: 8px;
          font-weight: 700;
          font-size: 0.98rem;
          border: none;
          cursor: pointer;
          margin-top: 1rem;
          transition: background 0.18s, box-shadow 0.18s;
        }
        .add-cart-btn {
          background: linear-gradient(135deg, #10b981, #059669);
          color: #fff;
        }
        .add-cart-btn:hover {
          background: linear-gradient(135deg, #059669, #10b981);
        }
        .buy-now-btn {
          background: linear-gradient(135deg, #3b82f6, #2563eb);
          color: #fff;
        }
        .buy-now-btn:hover {
          background: linear-gradient(135deg, #2563eb, #3b82f6);
        }
        </style>
        <div class="product-grid">
        <?php
        $sql = mysqli_query($con, "
            SELECT 
                t.u_toolname,
                SUM(t.u_itemsnumber) as total_stock,
                ROUND(SUM(t.u_price * t.u_itemsnumber) / NULLIF(SUM(t.u_itemsnumber), 0)) as avg_price,
                MAX(t.u_type) as u_type,
                MAX(t.u_tooldescription) as u_tooldescription,
                MAX(t.image_url) as image_url,
                MIN(t.id) as first_tool_id,
                COALESCE(MAX(im.method), 'FIFO') as inventory_method,
                COUNT(*) as batch_count
            FROM `tool` t
            LEFT JOIN inventory_method im ON t.id = im.tool_id
            GROUP BY t.u_toolname
            HAVING SUM(t.u_itemsnumber) > 0
            ORDER BY t.u_toolname ASC
        ");
        $row_count = mysqli_num_rows($sql);
        if($row_count){
          while($product=mysqli_fetch_array($sql))
          { 
            $product_name = $product['u_toolname'];
            $total_stock = (int)$product['total_stock'];
            $display_price = (int)$product['avg_price'];
            $display_type = $product['u_type'];
            $display_description = $product['u_tooldescription'];
            $display_image = $product['image_url'];
            $inventory_method = $product['inventory_method'] ?? 'FIFO';
            $batch_count = (int)$product['batch_count'];
            $order_direction = ($inventory_method === 'FIFO') ? 'ASC' : 'DESC';
            $first_available = mysqli_query($con, "
                SELECT id, u_date FROM tool 
                WHERE u_toolname = '" . mysqli_real_escape_string($con, $product_name) . "' 
                AND u_itemsnumber > 0
                ORDER BY u_date $order_direction
                LIMIT 1
            ");
            $first_tool = mysqli_fetch_assoc($first_available);
            $display_tool_id = $first_tool['id'] ?? $product['first_tool_id'];
            $oldest_date = $first_tool['u_date'] ?? date('Y-m-d');
        ?>
        <div class="product-card">
          <?php if(!empty($display_image) && file_exists('../' . $display_image)): ?>
            <img src="../<?php echo htmlspecialchars($display_image); ?>" alt="<?php echo htmlspecialchars($product_name); ?>" class="product-image">
          <?php else: ?>
            <div class="product-image" style="justify-content:center;align-items:center;display:flex;">
              <ion-icon name="construct-outline" style="font-size: 3.5rem; color: #3b82f6;"></ion-icon>
            </div>
          <?php endif; ?>
          <div class="product-title"><?php echo htmlspecialchars($product_name); ?></div>
          <div class="product-type"><?php echo htmlspecialchars($display_type); ?></div>
          <div class="product-description"><?php echo htmlspecialchars($display_description); ?></div>
          <div class="product-stock <?php echo $total_stock <= 10 ? ($total_stock == 0 ? 'out' : 'low') : ''; ?>">
            <?php if($total_stock > 0): ?>
              <?php echo number_format($total_stock); ?> units<?php if($total_stock <= 10): ?> (Low Stock)<?php endif; ?>
            <?php else: ?>
              Out of Stock
            <?php endif; ?>
          </div>
          <div class="product-price">RWF <?php echo number_format($display_price); ?></div>
          <div class="product-actions">
            <?php if($total_stock > 0): ?>
              <form method="POST">
                <input type="hidden" name="tool_id" value="<?php echo $display_tool_id; ?>">
                <input type="number" name="quantity" value="1" min="1" max="<?php echo $total_stock; ?>" style="width: 60px; padding: 6px 8px; border: 1px solid #e5e7eb; border-radius: 6px; text-align: center; font-weight: 600; margin-right: 6px;">
                <button type="submit" name="add_to_cart" class="add-cart-btn">
                  <ion-icon name="cart-outline"></ion-icon> Add to Cart
                </button>
              </form>
              <a href="stock.php?id=<?php echo $display_tool_id; ?>" class="buy-now-btn">
                <ion-icon name="flash-outline"></ion-icon> Buy Now
              </a>
            <?php else: ?>
              <span class="product-stock out" style="width:100%;text-align:center;display:block;background:#f3f4f6;padding:10px 0;border-radius:8px;">Out of Stock</span>
            <?php endif; ?>
          </div>
        </div>
        <?php
          }
        } else {
        ?>
        <div style="grid-column: 1/-1; text-align: center; color: #64748b; font-size: 1.1rem; padding: 2.5rem 0;">No tools available</div>
        <?php } ?>
        </div>

        <!-- Live Cart Table (now under product cards) -->
        <div class="cart-table-container" id="cart-table-section" style="display:none; margin-bottom:2.5rem;">
          <div class="cart-table-title"><ion-icon name="cart-outline"></ion-icon> Your Cart</div>
          <div id="cart-table-wrapper">
            <!-- Cart table will be loaded here by JS -->
          </div>
        </div>

        <!-- Cart Message Toast (centered under cart table) -->
        <?php if(isset($cart_message)): ?>
        <div id="cart-toast" style="max-width: 900px; margin: 0 auto 20px auto; padding: 16px 24px; border-radius: 12px; display: flex; align-items: center; justify-content: center; gap: 12px; box-shadow: 0 4px 16px rgba(0,0,0,0.1); animation: fadeIn 0.3s ease-out; <?php echo $cart_message_type == 'success' ? 'background: linear-gradient(135deg, #10b981, #059669); color: white;' : 'background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;'; ?>">
          <ion-icon name="<?php echo $cart_message_type == 'success' ? 'checkmark-circle' : 'alert-circle'; ?>-outline" style="font-size: 1.5rem;"></ion-icon>
          <span style="font-weight: 500;"><?php echo htmlspecialchars($cart_message); ?></span>
          <?php if($cart_message_type == 'success'): ?>
          <a href="cart.php" style="margin-left: 8px; padding: 6px 12px; background: rgba(255,255,255,0.2); border-radius: 6px; color: white; text-decoration: none; font-weight: 600; font-size: 0.875rem;">View Cart</a>
          <?php endif; ?>
          <button onclick="this.parentElement.style.display='none'" style="margin-left: 8px; background: none; border: none; cursor: pointer; opacity: 0.7;">
            <ion-icon name="close-outline" style="font-size: 1.25rem; color: <?php echo $cart_message_type == 'success' ? 'white' : '#dc2626'; ?>;"></ion-icon>
          </button>
        </div>
        <style>
          @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        </style>
        <script>setTimeout(() => { const toast = document.getElementById('cart-toast'); if(toast) { toast.style.transition = 'opacity 0.3s'; toast.style.opacity = '0'; setTimeout(() => toast.style.display = 'none', 300); } }, 5000);</script>
        <?php endif; ?>

        <!-- Cart Summary Bar (under Your Cart) -->
        <?php if($cart_count > 0): ?>
        <div style="background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 12px 20px; border-radius: 12px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
          <div style="display: flex; align-items: center; gap: 12px;">
            <ion-icon name="cart" style="font-size: 1.5rem;"></ion-icon>
            <span><strong><?php echo $cart_count; ?> item<?php echo $cart_count > 1 ? 's' : ''; ?></strong> in your cart</span>
          </div>
          <a href="cart.php" style="padding: 8px 20px; background: white; color: #059669; text-decoration: none; border-radius: 8px; font-weight: 600; display: flex; align-items: center; gap: 6px;">
            <ion-icon name="eye-outline"></ion-icon>
            View Cart & Checkout
          </a>
        </div>
        <?php endif; ?>

        <script>
        function renderCartTable(cart) {
          const wrapper = document.getElementById('cart-table-wrapper');
          if (!cart.items || cart.items.length === 0) {
            wrapper.innerHTML = '<div style="color:#64748b;text-align:center;padding:1.5rem 0;">Your cart is empty.</div>';
            document.getElementById('cart-table-section').style.display = 'none';
            return;
          }
          let html = `<table class="cart-table"><thead><tr>
            <th>Item</th><th>Type</th><th>Price</th><th>Quantity</th><th>Total</th><th></th>
          </tr></thead><tbody>`;
          for (const item of cart.items) {
            html += `<tr>
              <td style="display:flex;align-items:center;gap:10px;">
                ${item.image_url ? `<img src="../${item.image_url}" alt="${item.tool_name}">` : `<div style='width:48px;height:48px;border-radius:8px;background:#f3f4f6;display:flex;align-items:center;justify-content:center;'><ion-icon name='construct-outline' style='font-size:1.5rem;color:#3b82f6;'></ion-icon></div>`}
                <span>${item.tool_name}</span>
              </td>
              <td>${item.u_type || ''}</td>
              <td>RWF ${parseInt(item.unit_price).toLocaleString('en-US')}</td>
              <td>
                <input type="number" min="1" max="${item.available}" value="${item.quantity}" style="width:55px;padding:3px 6px;border-radius:6px;border:1px solid #e5e7eb;text-align:center;font-weight:600;" onchange="updateCartItem(${item.id}, this.value)">
              </td>
              <td>RWF ${(item.unit_price * item.quantity).toLocaleString('en-US')}</td>
              <td><button class="remove-btn" onclick="removeCartItem(${item.id})"><ion-icon name='trash-outline'></ion-icon></button></td>
            </tr>`;
          }
          html += `</tbody><tfoot><tr><td colspan="4" style="text-align:right;">Total:</td><td colspan="2">RWF ${parseInt(cart.cart_total).toLocaleString('en-US')}</td></tr></tfoot></table>`;
          wrapper.innerHTML = html;
          document.getElementById('cart-table-section').style.display = '';
        }

        function fetchCartTable() {
          fetch('cart_api.php?action=get')
            .then(r => r.json())
            .then(data => renderCartTable(data));
        }

        function updateCartItem(cart_item_id, quantity) {
          fetch('cart_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=update&cart_item_id=${cart_item_id}&quantity=${quantity}`
          })
          .then(r => r.json())
          .then(data => fetchCartTable());
        }

        function removeCartItem(cart_item_id) {
          fetch('cart_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=remove&cart_item_id=${cart_item_id}`
          })
          .then(r => r.json())
          .then(data => fetchCartTable());
        }

        // Hook into Add to Cart forms to refresh cart table after add
        document.addEventListener('DOMContentLoaded', function() {
          fetchCartTable();
          document.querySelectorAll('form').forEach(form => {
            if (form.querySelector('button[name="add_to_cart"]')) {
              form.addEventListener('submit', function(e) {
                setTimeout(fetchCartTable, 500); // Delay to allow PHP to process
              });
            }
          });
        });
        </script>
      </div>

        <!-- Order Form Modal/Section -->
        <?php
        if(isset($_GET['id'])){
          $tool_id = $_GET['id'];
          $tool_sql = mysqli_query($con,"SELECT * FROM tool WHERE id='$tool_id' ");
          $tool_row = mysqli_fetch_array($tool_sql);
          if ($tool_row && !empty($tool_row['u_toolname'])) {
            $safe_tool_name = mysqli_real_escape_string($con, $tool_row['u_toolname']);
            $avg_price_query = mysqli_query($con, "
              SELECT
                CASE
                  WHEN SUM(u_itemsnumber) > 0 THEN SUM(u_price * u_itemsnumber) / SUM(u_itemsnumber)
                  ELSE 0
                END AS avg_price
              FROM tool
              WHERE u_toolname = '$safe_tool_name'
            ");
            if ($avg_price_query) {
              $avg_price_data = mysqli_fetch_assoc($avg_price_query);
              if (isset($avg_price_data['avg_price']) && $avg_price_data['avg_price'] !== null) {
                $tool_row['u_price'] = (int)round($avg_price_data['avg_price']);
              }
            }
          }
          
          $user_sql = mysqli_query($con,"SELECT * FROM `user` WHERE id='$id' ");
          $user_row = mysqli_fetch_array($user_sql);
        ?>
        <div class="content-wrapper" style="margin-top: var(--spacing-xl);">
          <!-- Breadcrumb -->
          <div style="margin-bottom: var(--spacing-xl); display: flex; align-items: center; gap: var(--spacing-sm); color: var(--gray-600); font-size: 0.875rem;">
            <a href="stock.php" style="color: var(--primary-color); text-decoration: none;">
              <ion-icon name="cube-outline"></ion-icon> Inter Purchases
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
              // Get inventory method
              $current_method = 'FIFO';
              $method_query = mysqli_query($con, "SELECT method FROM inventory_method WHERE tool_id = " . $tool_id);
              if($method_query && mysqli_num_rows($method_query) > 0) {
                  $method_row = mysqli_fetch_array($method_query);
                  $current_method = $method_row['method'];
              }
              
              // Get next batch to be sold based on FIFO/LIFO
              $next_batch = $inventoryManager->getNextBatchToSell($tool_id);
              
              // User can only order from the CURRENT batch (FIFO/LIFO)
              // This ensures old stock is sold first (FIFO) or new stock first (LIFO)
              if($next_batch && $next_batch['quantity_remaining'] > 0) {
                  $current_stock = (int)$next_batch['quantity_remaining'];
                  $current_batch_number = $next_batch['batch_number'];
                  $current_batch_date = $next_batch['batch_date'];
                  $has_batch_system = true;
              } else {
                  // Fallback to tool table if no batches
                  $current_stock = (int)$tool_row['u_itemsnumber'];
                  $current_batch_number = 'Main Stock';
                  $current_batch_date = $tool_row['u_date'];
                  $has_batch_system = false;
              }
              
              // Check total batches waiting
              $total_batches_query = mysqli_query($con, "SELECT COUNT(*) as cnt, SUM(quantity_remaining) as total FROM stock_batches WHERE tool_id = " . $tool_id . " AND quantity_remaining > 0");
              $total_batches_data = mysqli_fetch_assoc($total_batches_query);
              $total_batches = (int)$total_batches_data['cnt'];
              $total_all_stock = (int)$total_batches_data['total'];
              
              // Get all available batches for this tool
              $batches_result = $inventoryManager->getStockBatchesByMethod($tool_id, $current_method);
              ?>
              
              <!-- Stock and FIFO/LIFO Information Card -->
              <div class="stock-info-card">
                <h4>
                  <ion-icon name="information-circle-outline"></ion-icon>
                  Current Batch Information
                </h4>
                <div class="stock-info-grid">
                  <div class="stock-info-item">
                    <div class="label">Available to Order</div>
                    <div class="value" style="color: <?php echo $current_stock > 0 ? '#16a34a' : '#dc2626'; ?>;">
                      <?php echo number_format($current_stock); ?> units
                    </div>
                  </div>
                  <div class="stock-info-item">
                    <div class="label">Inventory Method</div>
                    <div class="value">
                      <span class="method-badge <?php echo strtolower($current_method); ?>" style="font-size: 0.85rem;">
                        <?php echo $current_method; ?>
                      </span>
                    </div>
                  </div>
                  <div class="stock-info-item">
                    <div class="label">Current Batch</div>
                    <div class="value" style="font-size: 0.85rem;">
                      <?php echo htmlspecialchars($current_batch_number); ?>
                    </div>
                  </div>
                  <div class="stock-info-item">
                    <div class="label">Batch Date</div>
                    <div class="value" style="font-size: 0.85rem;">
                      <?php echo date('M d, Y', strtotime($current_batch_date)); ?>
                    </div>
                  </div>
                </div>
                
                <?php if($has_batch_system && $total_batches > 1): ?>
                <!-- Note about more batches -->
                <div style="margin-top: 16px; padding: 12px; background: rgba(245, 158, 11, 0.1); border-radius: 8px; border-left: 4px solid #f59e0b;">
                  <div style="font-size: 0.8rem; color: #92400e;">
                    <ion-icon name="information-circle-outline" style="margin-right: 4px;"></ion-icon>
                    <strong>Note:</strong> You're ordering from the <?php echo $current_method === 'FIFO' ? 'oldest' : 'newest'; ?> batch. 
                    There are <?php echo $total_batches - 1; ?> more batch(es) with <?php echo number_format($total_all_stock - $current_stock); ?> additional units that will become available once this batch is depleted.
                  </div>
                </div>
                <?php endif; ?>
                
                <?php if($next_batch): ?>
                <!-- Current Batch Details -->
                <div style="margin-top: 16px; padding: 12px; background: <?php echo $current_method === 'FIFO' ? 'rgba(59, 130, 246, 0.1)' : 'rgba(139, 92, 246, 0.1)'; ?>; border-radius: 8px; border-left: 4px solid <?php echo $current_method === 'FIFO' ? '#3b82f6' : '#8b5cf6'; ?>;">
                  <div style="font-size: 0.8rem; font-weight: 600; color: var(--gray-700); margin-bottom: 8px;">
                    <ion-icon name="cube-outline" style="margin-right: 4px;"></ion-icon>
                    <?php echo $current_method === 'FIFO' ? 'Current Stock (Oldest Batch First)' : 'Current Stock (Newest Batch First)'; ?>
                  </div>
                  <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; font-size: 0.75rem;">
                    <div>
                      <span style="color: var(--gray-500);">Batch:</span><br>
                      <strong><?php echo htmlspecialchars($next_batch['batch_number']); ?></strong>
                    </div>
                    <div>
                      <span style="color: var(--gray-500);">Qty Available:</span><br>
                      <strong><?php echo number_format($next_batch['quantity_remaining']); ?></strong>
                    </div>
                    <div>
                      <span style="color: var(--gray-500);">Batch Date:</span><br>
                      <strong><?php echo date('M d, Y', strtotime($next_batch['batch_date'])); ?></strong>
                    </div>
                    <div>
                      <span style="color: var(--gray-500);">Location:</span><br>
                      <strong><?php echo htmlspecialchars($next_batch['location_name'] ?? 'N/A'); ?></strong>
                    </div>
                  </div>
                </div>
                <?php endif; ?>
              </div>

              <form method="POST" action="" id="orderToolForm">
                <input type="hidden" name="user_id" value="<?php echo $user_row['id']; ?>">
                <input type="hidden" name="tool_id" value="<?php echo $tool_id; ?>">
                <input type="hidden" name="u_price" value="<?php echo $tool_row['u_price']; ?>" id="unit_price">
                <input type="hidden" name="location_id" value="1">
                
                <!-- Tool Name and Quantity Row -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-lg); margin-bottom: var(--spacing-lg);">
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
        
        <script>
        // Location stock data
        const locationStocks = {
          <?php
          $locations_result = $inventoryManager->getAllLocations();
          $stock_data = [];
          while($location = mysqli_fetch_array($locations_result)) {
            $location_stock = $inventoryManager->getAvailableStock($tool_id, $location['id']);
            $stock_data[] = $location['id'] . ': {stock: ' . $location_stock . ', name: "' . addslashes($location['location_name']) . '"}';
          }
          echo implode(",\n          ", $stock_data);
          ?>
        };
        
        function updateLocationStock() {
          const locationSelect = document.getElementById('location_id');
          const stockInfo = document.getElementById('location-stock-info');
          const stockMessage = document.getElementById('stock-message');
          const quantityInput = document.getElementById('u_itemsnumber');
          const submitButton = document.querySelector('button[name="order_tool"]');
          
          const locationId = locationSelect.value;
          
          if (locationId && locationStocks[locationId]) {
            const location = locationStocks[locationId];
            stockInfo.style.display = 'block';
            stockMessage.innerHTML = `${location.stock} units available at ${location.name}`;
            
            // Update quantity max
            quantityInput.max = location.stock;
            
            // Enable/disable submit button
            if (location.stock > 0) {
              quantityInput.disabled = false;
              submitButton.disabled = false;
              submitButton.innerHTML = '<ion-icon name="checkmark-circle-outline"></ion-icon> Complete Purchase';
              stockInfo.style.background = '#dcfce7';
              stockInfo.style.color = '#15803d';
            } else {
              quantityInput.disabled = true;
              submitButton.disabled = true;
              submitButton.innerHTML = '<ion-icon name="ban-outline"></ion-icon> Out of Stock';
              stockInfo.style.background = '#fef2f2';
              stockInfo.style.color = '#dc2626';
            }
          } else {
            stockInfo.style.display = 'none';
            quantityInput.disabled = true;
            submitButton.disabled = true;
          }
          
          calculateTotal();
        }
        </script>
        <?php } ?>
      </div>
    </main>
  </div>

<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
<script>
  function exportAvailableProductsPDF() {
    window.open('export_pdf.php?type=available_products', '_blank');
  }
</script>
</body>
</html>