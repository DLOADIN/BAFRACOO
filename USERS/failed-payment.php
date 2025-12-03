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
  
  // Get order details if available
  $order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
  $status = isset($_GET['status']) ? $_GET['status'] : 'failed';
  
  $order_details = null;
  if($order_id > 0) {
      $order_query = mysqli_query($con, "SELECT * FROM `order` WHERE id = '$order_id' AND user_id = '$id'");
      $order_details = mysqli_fetch_array($order_query);
  }
  
  // Determine the message based on status
  $title = "Payment Failed";
  $message = "We encountered an issue processing your payment.";
  $icon = "close-circle-outline";
  $icon_color = "#ef4444";
  
  if($status == 'cancelled') {
      $title = "Payment Cancelled";
      $message = "Your payment was cancelled. No charges were made to your account.";
      $icon = "close-circle-outline";
      $icon_color = "#f59e0b";
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
  <title>BAFRACOO - <?php echo $title; ?></title>
  <style>
    .error-container {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, <?php echo $status == 'cancelled' ? '#f59e0b, #d97706' : '#ef4444, #dc2626'; ?>);
      padding: 2rem;
    }
    .error-card {
      background: white;
      padding: 3rem;
      border-radius: var(--radius-xl);
      box-shadow: var(--shadow-xl);
      max-width: 550px;
      width: 100%;
      text-align: center;
      animation: slideUp 0.5s ease-out;
    }
    @keyframes slideUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    .error-logo {
      width: 100px;
      height: auto;
      margin-bottom: 1.5rem;
    }
    .error-icon-wrapper {
      width: 100px;
      height: 100px;
      background: <?php echo $icon_color; ?>;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1.5rem;
      animation: shake 0.6s ease-out 0.2s both;
    }
    .error-icon-wrapper ion-icon {
      font-size: 50px;
      color: white;
    }
    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-8px); }
      75% { transform: translateX(8px); }
    }
    .error-title {
      font-size: 2rem;
      font-weight: 700;
      color: var(--gray-900);
      margin-bottom: 0.75rem;
    }
    .error-text {
      font-size: 1.1rem;
      color: var(--gray-600);
      margin-bottom: 2rem;
      line-height: 1.6;
    }
    .order-info {
      background: var(--gray-50);
      padding: 1rem 1.5rem;
      border-radius: var(--radius-lg);
      margin-bottom: 2rem;
      text-align: left;
    }
    .order-info-title {
      font-size: 0.85rem;
      color: var(--gray-600);
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 0.75rem;
      font-weight: 600;
    }
    .order-info-row {
      display: flex;
      justify-content: space-between;
      padding: 0.5rem 0;
      border-bottom: 1px solid var(--gray-200);
    }
    .order-info-row:last-child {
      border-bottom: none;
    }
    .order-info-label {
      color: var(--gray-600);
      font-size: 0.9rem;
    }
    .order-info-value {
      color: var(--gray-900);
      font-weight: 600;
      font-size: 0.9rem;
    }
    .action-buttons {
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }
    .btn-retry {
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      padding: 1rem 2rem;
      background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
      color: white;
      border: none;
      border-radius: var(--radius-lg);
      font-size: 1rem;
      font-weight: 600;
      text-decoration: none;
      transition: var(--transition-base);
    }
    .btn-retry:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-lg);
    }
    .btn-secondary {
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      padding: 1rem 2rem;
      background: white;
      color: var(--gray-700);
      border: 1px solid var(--gray-300);
      border-radius: var(--radius-lg);
      font-size: 1rem;
      font-weight: 600;
      text-decoration: none;
      transition: var(--transition-base);
    }
    .btn-secondary:hover {
      background: var(--gray-50);
      border-color: var(--gray-400);
    }
    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      background: <?php echo $status == 'cancelled' ? '#fef3c7' : '#fef2f2'; ?>;
      color: <?php echo $status == 'cancelled' ? '#d97706' : '#dc2626'; ?>;
      padding: 0.5rem 1rem;
      border-radius: 20px;
      font-size: 0.875rem;
      font-weight: 600;
      margin-bottom: 1.5rem;
    }
  </style>
</head>
<body>
  <div class="error-container">
    <div class="error-card">
      <img src="../images/Captured.JPG" alt="BAFRACOO Logo" class="error-logo">
      
      <div class="error-icon-wrapper">
        <ion-icon name="<?php echo $icon; ?>"></ion-icon>
      </div>
      
      <div class="status-badge">
        <ion-icon name="alert-circle-outline"></ion-icon>
        <?php echo $status == 'cancelled' ? 'Payment Cancelled' : 'Payment Failed'; ?>
      </div>
      
      <h1 class="error-title"><?php echo $title; ?></h1>
      <p class="error-text"><?php echo $message; ?></p>
      
      <?php if($order_details): ?>
      <div class="order-info">
        <div class="order-info-title">Order Details</div>
        <div class="order-info-row">
          <span class="order-info-label">Order ID</span>
          <span class="order-info-value">#<?php echo $order_details['id']; ?></span>
        </div>
        <div class="order-info-row">
          <span class="order-info-label">Item</span>
          <span class="order-info-value"><?php echo htmlspecialchars($order_details['u_toolname']); ?></span>
        </div>
        <div class="order-info-row">
          <span class="order-info-label">Amount</span>
          <span class="order-info-value">RWF <?php echo number_format($order_details['u_totalprice']); ?></span>
        </div>
        <div class="order-info-row">
          <span class="order-info-label">Status</span>
          <span class="order-info-value" style="color: <?php echo $status == 'cancelled' ? '#d97706' : '#dc2626'; ?>;">
            <?php echo $order_details['status']; ?>
          </span>
        </div>
      </div>
      <?php endif; ?>
      
      <div class="action-buttons">
        <?php if($order_id > 0): ?>
        <a href="pay.php?o_id=<?php echo $order_id; ?>" class="btn-retry">
          <ion-icon name="refresh-outline"></ion-icon>
          Try Payment Again
        </a>
        <?php endif; ?>
        <a href="orders.php" class="btn-secondary">
          <ion-icon name="bag-handle-outline"></ion-icon>
          View My Orders
        </a>
        <a href="stock.php" class="btn-secondary">
          <ion-icon name="cart-outline"></ion-icon>
          Continue Shopping
        </a>
      </div>
    </div>
  </div>

  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>