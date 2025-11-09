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
  } 
$customer_phone = 2507;
$transaction_id = rand(1, 999999) . 'code' . date('ymdhis') . rand(10000, 999999);
?>

  <div class="payment-container">
    <div class="payment-card">
      <img src="../images/Captured.JPG" alt="BAFRACOO Logo" class="payment-logo">
      
      <div class="payment-icon">
        <ion-icon name="card-outline"></ion-icon>
      </div>
      
      <h1 class="payment-title">Complete Your Payment</h1>
      <p class="payment-subtitle">You're one step away from completing your order</p>
      
      <div class="amount-display">
        <div class="amount-label">Total Amount</div>
        <div class="amount-value">
          RWF <?php 
            $O_id=$_GET['o_id'];
            $sql = mysqli_query($con, "SELECT u_totalprice AS total FROM `order` WHERE id='$O_id'");
            $row_order = mysqli_fetch_array($sql);
            $total = $row_order['total'];
            echo number_format($total);
          ?>
        </div>
      </div>
      
      <form class="FinalForm" method="POST" action="https://checkout.flutterwave.com/v3/hosted/pay">
        <input type="hidden" name="public_key" value="FLWPUBK-fd9a72fe52fbf0bd373323b44d7e2097-X" />
        <input type="hidden" name="customizations[title]" value="BAFRACOO" />
        <input type="hidden" name="customizations[description]" value="Construction Tools Payment" />
        <input type="hidden" name="customizations[logo]" value="" />
        <input type="hidden" name="customer[email]" value="<?php echo $row['u_email']; ?>" />
        <input type="hidden" name="customer[name]" value="<?php echo $row['u_name']; ?>" />
        <input type="hidden" name="tx_ref" value="<?php echo $transaction_id; ?>" />
        <input type="hidden" name="amount" value="<?php echo $total; ?>" />
        <input type="hidden" name="currency" value="RWF" />
        <input type="hidden" name="redirect_url" value="/project-hydra/users/redirect.php" />
        
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