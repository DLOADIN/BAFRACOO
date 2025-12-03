<?php
require "connection.php";

// Check if user is logged in
if(empty($_SESSION["id"])){
    header('location:loginuser.php');
    exit();
}

$id = $_SESSION["id"];
$check = mysqli_query($con,"SELECT * FROM `user` WHERE id=$id ");
$row = mysqli_fetch_array($check);

// Get order details if available
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$status = isset($_GET['status']) ? $_GET['status'] : '';

$order_details = null;
if($order_id > 0) {
    $order_query = mysqli_query($con, "SELECT * FROM `order` WHERE id = '$order_id' AND user_id = '$id'");
    $order_details = mysqli_fetch_array($order_query);
}

// Check session for payment success
$payment_success = isset($_SESSION['payment_success']) && $_SESSION['payment_success'];
$payment_amount = isset($_SESSION['payment_amount']) ? $_SESSION['payment_amount'] : 0;

// Clear session variables
unset($_SESSION['payment_success']);
unset($_SESSION['payment_order_id']);
unset($_SESSION['payment_amount']);
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
  <title>BAFRACOO - Payment Successful</title>
  <style>
    .success-container {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, #10b981, #059669);
      padding: 2rem;
    }
    .success-card {
      background: white;
      padding: 3rem;
      border-radius: var(--radius-xl);
      box-shadow: var(--shadow-xl);
      max-width: 550px;
      width: 100%;
      text-align: center;
    }
    .success-logo {
      width: 100px;
      height: auto;
      margin-bottom: 1.5rem;
    }
    .success-icon {
      width: 100px;
      height: 100px;
      background: linear-gradient(135deg, #10b981, #059669);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1.5rem;
      animation: scaleIn 0.5s ease-out;
    }
    .success-icon ion-icon {
      font-size: 50px;
      color: white;
    }
    @keyframes scaleIn {
      0% { transform: scale(0); }
      50% { transform: scale(1.1); }
      100% { transform: scale(1); }
    }
    .success-title {
      font-size: 2rem;
      font-weight: 700;
      color: var(--gray-900);
      margin-bottom: 0.75rem;
    }
    .success-subtitle {
      font-size: 1.1rem;
      color: var(--gray-600);
      margin-bottom: 2rem;
    }
    .order-summary {
      background: var(--gray-50);
      padding: 1.5rem;
      border-radius: var(--radius-lg);
      margin-bottom: 2rem;
      text-align: left;
    }
    .order-summary-title {
      font-size: 0.85rem;
      color: var(--gray-600);
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 1rem;
      font-weight: 600;
      text-align: center;
    }
    .order-summary-row {
      display: flex;
      justify-content: space-between;
      padding: 0.75rem 0;
      border-bottom: 1px solid var(--gray-200);
    }
    .order-summary-row:last-child {
      border-bottom: none;
      font-weight: 700;
      color: #10b981;
      font-size: 1.1rem;
    }
    .order-summary-label {
      color: var(--gray-600);
    }
    .order-summary-value {
      color: var(--gray-900);
      font-weight: 600;
    }
    .action-buttons {
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }
    .primary-btn {
      width: 100%;
      padding: 1rem 2rem;
      background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
      color: white;
      border: none;
      border-radius: var(--radius-lg);
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition-base);
      text-decoration: none;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
    }
    .primary-btn:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-lg);
    }
    .secondary-btn {
      width: 100%;
      padding: 1rem 2rem;
      background: white;
      color: var(--gray-700);
      border: 1px solid var(--gray-300);
      border-radius: var(--radius-lg);
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition-base);
      text-decoration: none;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
    }
    .secondary-btn:hover {
      background: var(--gray-50);
      border-color: var(--gray-400);
    }
    .confirmation-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      background: #dcfce7;
      color: #15803d;
      padding: 0.5rem 1rem;
      border-radius: 20px;
      font-size: 0.875rem;
      font-weight: 600;
      margin-bottom: 1.5rem;
    }
  </style>
</head>
<body>
  <div class="success-container">
    <div class="success-card">
      <img src="../images/Captured.JPG" alt="BAFRACOO Logo" class="success-logo">
      
      <div class="success-icon">
        <ion-icon name="checkmark-outline"></ion-icon>
      </div>
      
      <div class="confirmation-badge">
        <ion-icon name="shield-checkmark-outline"></ion-icon>
        Payment Verified
      </div>
      
      <h1 class="success-title">Payment Successful!</h1>
      <p class="success-subtitle">Thank you for your purchase, <?php echo htmlspecialchars($row['u_name'] ?? 'Customer'); ?>!</p>
      
      <?php if($order_details): ?>
      <div class="order-summary">
        <div class="order-summary-title">Order Confirmation</div>
        <div class="order-summary-row">
          <span class="order-summary-label">Order ID</span>
          <span class="order-summary-value">#<?php echo $order_details['id']; ?></span>
        </div>
        <div class="order-summary-row">
          <span class="order-summary-label">Item</span>
          <span class="order-summary-value"><?php echo htmlspecialchars($order_details['u_toolname']); ?></span>
        </div>
        <div class="order-summary-row">
          <span class="order-summary-label">Quantity</span>
          <span class="order-summary-value"><?php echo number_format($order_details['u_itemsnumber']); ?> units</span>
        </div>
        <div class="order-summary-row">
          <span class="order-summary-label">Status</span>
          <span class="order-summary-value" style="color: #10b981;"><?php echo $order_details['status']; ?></span>
        </div>
        <div class="order-summary-row">
          <span class="order-summary-label">Total Paid</span>
          <span class="order-summary-value">RWF <?php echo number_format($order_details['u_totalprice']); ?></span>
        </div>
      </div>
      <?php endif; ?>
      
      <div class="action-buttons">
        <a href="orders.php" class="primary-btn">
          <ion-icon name="bag-handle-outline"></ion-icon>
          View My Orders
        </a>
        <a href="stock.php" class="secondary-btn">
          <ion-icon name="cart-outline"></ion-icon>
          Continue Shopping
        </a>
        <a href="transactions.php" class="secondary-btn">
          <ion-icon name="receipt-outline"></ion-icon>
          View Transactions
        </a>
      </div>
    </div>
  </div>

  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>