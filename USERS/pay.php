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
  
  // Generate unique transaction reference with order_id encoded
  $transaction_id = 'BAFRACOO-' . $O_id . '-' . date('YmdHis') . '-' . rand(1000, 9999);
  
  // Build the redirect URL dynamically
  $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'];
  $base_path = dirname($_SERVER['PHP_SELF']);
  $redirect_url = $protocol . '://' . $host . $base_path . '/redirect.php';
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
      </div>
      
      <div class="amount-display">
        <div class="amount-label">Total Amount</div>
        <div class="amount-value">RWF <?php echo number_format($total); ?></div>
      </div>
      
      <form class="FinalForm" method="POST" action="https://checkout.flutterwave.com/v3/hosted/pay">
        <input type="hidden" name="public_key" value="FLWPUBK-fd9a72fe52fbf0bd373323b44d7e2097-X" />
        <input type="hidden" name="customizations[title]" value="BAFRACOO" />
        <input type="hidden" name="customizations[description]" value="Order #<?php echo $O_id; ?> - <?php echo htmlspecialchars($tool_name); ?>" />
        <input type="hidden" name="customizations[logo]" value="" />
        <input type="hidden" name="customer[email]" value="<?php echo htmlspecialchars($row['u_email']); ?>" />
        <input type="hidden" name="customer[name]" value="<?php echo htmlspecialchars($row['u_name']); ?>" />
        <input type="hidden" name="tx_ref" value="<?php echo $transaction_id; ?>" />
        <input type="hidden" name="amount" value="<?php echo $total; ?>" />
        <input type="hidden" name="currency" value="RWF" />
        <input type="hidden" name="redirect_url" value="<?php echo htmlspecialchars($redirect_url); ?>" />
        <input type="hidden" name="meta[order_id]" value="<?php echo $O_id; ?>" />
        <input type="hidden" name="meta[user_id]" value="<?php echo $id; ?>" />
        
        <button type="submit" class="payment-btn" id="start-payment-button">
          <ion-icon name="lock-closed-outline"></ion-icon>
          Proceed to Secure Payment
        </button>
      </form>
      
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