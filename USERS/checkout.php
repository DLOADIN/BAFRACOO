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
  <link rel="shortcut icon" href="../images/Capture.JPG" type="image/x-icon">
  <script src="https://kit.fontawesome.com/14ff3ea278.js" crossorigin="anonymous"></script>
  <title>BAFRACOO - Checkout</title>
  <style>
    .checkout-container {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
      padding: 2rem;
    }
    .checkout-card {
      background: white;
      padding: 3rem;
      border-radius: var(--radius-xl);
      box-shadow: var(--shadow-xl);
      max-width: 500px;
      width: 100%;
      text-align: center;
    }
    .checkout-logo {
      width: 120px;
      height: auto;
      margin-bottom: 2rem;
    }
    .checkout-icon {
      font-size: 80px;
      color: var(--primary-color);
      margin-bottom: 1.5rem;
    }
    .checkout-title {
      font-size: 2rem;
      font-weight: 700;
      color: var(--gray-900);
      margin-bottom: 1rem;
    }
    .checkout-text {
      font-size: 1.1rem;
      color: var(--gray-600);
      margin-bottom: 2rem;
      line-height: 1.6;
    }
    .checkout-divider {
      border: none;
      border-top: 2px solid var(--gray-200);
      margin: 2rem 0;
    }
    .back-btn {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.875rem 2rem;
      background: var(--primary-color);
      color: white;
      text-decoration: none;
      border-radius: var(--radius-lg);
      font-weight: 600;
      transition: var(--transition-base);
    }
    .back-btn:hover {
      background: var(--primary-dark);
      transform: translateY(-2px);
    }
  </style>
</head>
<body>
  <div class="checkout-container">
    <div class="checkout-card">
      <img src="../images/Captured.JPG" alt="BAFRACOO Logo" class="checkout-logo">
      <div class="checkout-icon">
        <ion-icon name="pricetags-outline"></ion-icon>
      </div>
      <h1 class="checkout-title">Thank You For Your Order!</h1>
      <p class="checkout-text">
        To finalize your purchase, kindly complete your payment. 
        If you have a discount code, please apply it during payment.
      </p>
      <hr class="checkout-divider">
      <a href="orders.php" class="back-btn">
        <ion-icon name="arrow-back-outline"></ion-icon>
        Return to Orders
      </a>
    </div>
  </div>

  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>