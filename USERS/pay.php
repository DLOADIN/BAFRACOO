<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../CSS/modern-dashboard.css">
  <link rel="shortcut icon" href="../images/Capture.JPG" type="image/x-icon">
  <script src="https://kit.fontawesome.com/14ff3ea278.js" crossorigin="anonymous"></script>
  <title>BAFRACOO - Checkout Payment</title>
  <style>
    .payment-container {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
      padding: 2rem;
    }
    .payment-card {
      background: white;
      padding: 3rem;
      border-radius: var(--radius-xl);
      box-shadow: var(--shadow-xl);
      max-width: 500px;
      width: 100%;
      text-align: center;
    }
    .payment-logo {
      width: 100px;
      height: auto;
      margin-bottom: 2rem;
    }
    .payment-icon {
      font-size: 80px;
      color: var(--success-color);
      margin-bottom: 1.5rem;
    }
    .payment-title {
      font-size: 2rem;
      font-weight: 700;
      color: var(--gray-900);
      margin-bottom: 1rem;
    }
    .payment-subtitle {
      font-size: 1.1rem;
      color: var(--gray-600);
      margin-bottom: 2rem;
    }
    .amount-display {
      background: var(--gray-50);
      padding: 1.5rem;
      border-radius: var(--radius-lg);
      margin-bottom: 2rem;
    }
    .amount-label {
      font-size: 0.9rem;
      color: var(--gray-600);
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 0.5rem;
    }
    .amount-value {
      font-size: 2.5rem;
      font-weight: 700;
      color: var(--primary-color);
    }
    .order-details {
      background: var(--gray-50);
      padding: 1rem 1.5rem;
      border-radius: var(--radius-lg);
      margin-bottom: 1.5rem;
      text-align: left;
    }
    .order-details-title {
      font-size: 0.85rem;
      color: var(--gray-600);
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 0.75rem;
      font-weight: 600;
    }
    .order-detail-row {
      display: flex;
      justify-content: space-between;
      padding: 0.5rem 0;
      border-bottom: 1px solid var(--gray-200);
    }
    .order-detail-row:last-child {
      border-bottom: none;
    }
    .order-detail-label {
      color: var(--gray-600);
      font-size: 0.9rem;
    }
    .order-detail-value {
      color: var(--gray-900);
      font-weight: 600;
      font-size: 0.9rem;
    }
    .payment-btn {
      width: 100%;
      padding: 1.25rem 2rem;
      background: var(--success-color);
      color: white;
      border: none;
      border-radius: var(--radius-lg);
      font-size: 1.1rem;
      font-weight: 700;
      cursor: pointer;
      transition: var(--transition-base);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.75rem;
    }
    .payment-btn:hover {
      background: #059669;
      transform: translateY(-2px);
      box-shadow: var(--shadow-lg);
    }
    .cancel-link {
      display: inline-block;
      margin-top: 1.5rem;
      color: var(--gray-600);
      text-decoration: none;
      font-size: 0.95rem;
      transition: var(--transition-fast);
    }
    .cancel-link:hover {
      color: var(--gray-900);
    }
    .error-message {
      background: #fef2f2;
      border: 1px solid #fecaca;
      color: #dc2626;
      padding: 1rem;
      border-radius: var(--radius-md);
      margin-bottom: 1.5rem;
    }
  </style>
</head>
<body>
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

  // Get order ID from URL
  $O_id = isset($_GET['o_id']) ? (int)$_GET['o_id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
  $is_cart_order = isset($_GET['cart']) && $_GET['cart'] == '1';
  
  if($O_id == 0) {
    echo '<div class="payment-container"><div class="payment-card"><div class="error-message">Invalid order. Please try again.</div><a href="orders.php" class="cancel-link">Return to Orders</a></div></div>';
    exit();
  }
  
  // Fetch order details
  $sql = mysqli_query($con, "SELECT * FROM `order` WHERE id='$O_id' AND user_id='$id'");
  $row_order = mysqli_fetch_array($sql);
  
  if(!$row_order) {
    echo '<div class="payment-container"><div class="payment-card"><div class="error-message">Order not found or you do not have permission to pay for this order.</div><a href="orders.php" class="cancel-link">Return to Orders</a></div></div>';
    exit();
  }
  
  // Check if already paid
  if($row_order['status'] == 'Paid' || $row_order['status'] == 'Completed') {
    echo '<div class="payment-container"><div class="payment-card"><div class="error-message">This order has already been paid.</div><a href="orders.php" class="cancel-link">Return to Orders</a></div></div>';
    exit();
  }
  
  $total = $row_order['u_totalprice'];
  $tool_name = $row_order['u_toolname'];
  $quantity = $row_order['u_itemsnumber'];
  $unit_price = $row_order['u_price'];
  
  // Check if this is a cart order (has order_items)
  $order_items = [];
  $order_items_query = mysqli_query($con, "SELECT * FROM order_items WHERE order_id = $O_id ORDER BY id");
  if ($order_items_query && mysqli_num_rows($order_items_query) > 0) {
    $is_cart_order = true;
    while ($oi = mysqli_fetch_assoc($order_items_query)) {
      $order_items[] = $oi;
    }
  }
  
  // Include Stripe helper
  require_once 'stripe_helper.php';
  
  // Build the redirect URLs dynamically
  $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'];
  $base_path = dirname($_SERVER['PHP_SELF']);
  $success_url = $protocol . '://' . $host . $base_path . '/redirect.php?session_id={CHECKOUT_SESSION_ID}&status=success';
  $cancel_url = $protocol . '://' . $host . $base_path . '/redirect.php?status=cancelled&order_id=' . $O_id;
  
  // Create Stripe Checkout Session
  $checkout_session = createStripeCheckoutSession(
    $total,
    'RWF', // Currency
    $O_id,
    $row['u_email'],
    $row['u_name'],
    $tool_name . ' (Qty: ' . $quantity . ')',
    $success_url,
    $cancel_url
  );
  
  // Check for errors
  if (isset($checkout_session['error'])) {
    echo '<div class="payment-container"><div class="payment-card"><div class="error-message">Payment Error: ' . htmlspecialchars($checkout_session['error']) . '</div><a href="orders.php" class="cancel-link">Return to Orders</a></div></div>';
    exit();
  }
  
  $stripe_session_id = $checkout_session['id'];
  $stripe_checkout_url = $checkout_session['url'];
?>

  <div class="payment-container">
    <div class="payment-card">
      <img src="../images/Captured.JPG" alt="BAFRACOO Logo" class="payment-logo">
      
      <div class="payment-icon">
        <ion-icon name="card-outline"></ion-icon>
      </div>
      
      <h1 class="payment-title">Complete Your Payment</h1>
      <p class="payment-subtitle">You're one step away from completing your order</p>
      
      <!-- Order Details -->
      <div class="order-details">
        <div class="order-details-title">Order Summary</div>
        <div class="order-detail-row">
          <span class="order-detail-label">Order ID</span>
          <span class="order-detail-value">#<?php echo $O_id; ?></span>
        </div>
        
        <?php if($is_cart_order && count($order_items) > 0): ?>
        <!-- Cart Order - Multiple Items -->
        <div style="margin: 12px 0; padding: 12px; background: #f0fdf4; border-radius: 8px; border: 1px solid #86efac;">
          <div style="font-size: 0.85rem; font-weight: 600; color: #166534; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
            <ion-icon name="cart-outline"></ion-icon>
            Cart Order (<?php echo count($order_items); ?> items)
          </div>
          <?php foreach($order_items as $oi): ?>
          <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px dashed #bbf7d0; font-size: 0.875rem;">
            <span style="color: #374151;"><?php echo htmlspecialchars($oi['tool_name']); ?> × <?php echo $oi['quantity']; ?></span>
            <span style="font-weight: 600; color: #166534;">RWF <?php echo number_format($oi['total_price']); ?></span>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <!-- Single Item Order -->
        <div class="order-detail-row">
          <span class="order-detail-label">Item</span>
          <span class="order-detail-value"><?php echo htmlspecialchars($tool_name); ?></span>
        </div>
        <div class="order-detail-row">
          <span class="order-detail-label">Quantity</span>
          <span class="order-detail-value"><?php echo number_format($quantity); ?> units</span>
        </div>
        <div class="order-detail-row">
          <span class="order-detail-label">Unit Price</span>
          <span class="order-detail-value">RWF <?php echo number_format($unit_price); ?></span>
        </div>
        <?php endif; ?>
      </div>
      
      <div class="amount-display">
        <div class="amount-label">Total Amount</div>
        <div class="amount-value">RWF <?php echo number_format($total); ?></div>
      </div>
      
      <button type="button" class="payment-btn" id="start-payment-button" onclick="window.location.href='<?php echo htmlspecialchars($stripe_checkout_url); ?>'">
        <ion-icon name="lock-closed-outline"></ion-icon>
        Proceed to Secure Payment
      </button>
      
      <a href="orders.php" class="cancel-link">
        <ion-icon name="arrow-back-outline"></ion-icon>
        Cancel and Return to Orders
      </a>
    </div>
  </div>

  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>