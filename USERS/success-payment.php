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
  <title>BAFRACOO - Payment Successful</title>
  <style>
    .success-container {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, #10b981, #06d6a0);
      padding: 2rem;
    }
    .success-card {
      background: white;
      padding: 3rem;
      border-radius: var(--radius-xl);
      box-shadow: var(--shadow-xl);
      max-width: 600px;
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
    .success-icon {
      font-size: 120px;
      color: var(--success-color);
      margin-bottom: 1.5rem;
      animation: scaleIn 0.6s ease-out 0.2s both;
    }
    @keyframes scaleIn {
      from {
        transform: scale(0);
      }
      to {
        transform: scale(1);
      }
    }
    .success-title {
      font-size: 2.5rem;
      font-weight: 700;
      color: var(--gray-900);
      margin-bottom: 1rem;
    }
    .success-text {
      font-size: 1.2rem;
      color: var(--gray-600);
      margin-bottom: 2.5rem;
      line-height: 1.6;
    }
    .action-buttons {
      display: flex;
      gap: 1rem;
      justify-content: center;
      flex-wrap: wrap;
    }
    .btn-primary-action, .btn-secondary-action {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 1rem 2rem;
      border-radius: var(--radius-lg);
      font-weight: 600;
      text-decoration: none;
      transition: var(--transition-base);
    }
    .btn-primary-action {
      background: var(--primary-color);
      color: white;
    }
    .btn-primary-action:hover {
      background: var(--primary-dark);
      transform: translateY(-2px);
    }
    .btn-secondary-action {
      background: var(--gray-200);
      color: var(--gray-700);
    }
    .btn-secondary-action:hover {
      background: var(--gray-300);
    }
  </style>
</head>
<body>
  <div class="success-container">
    <div class="success-card">
      <div class="success-icon">
        <i class="fa-solid fa-circle-check"></i>
      </div>
      <h1 class="success-title">Payment Successful!</h1>
      <p class="success-text">
        Your order has been received successfully.<br>
        Expect our call and product delivery in 2-3 business days.
      </p>
      <div class="action-buttons">
        <a href="orders.php" class="btn-primary-action">
          <ion-icon name="bag-handle-outline"></ion-icon>
          View My Orders
        </a>
        <a href="userdashboard.php" class="btn-secondary-action">
          <ion-icon name="home-outline"></ion-icon>
          Back to Dashboard
        </a>
      </div>
    </div>
  </div>

  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>