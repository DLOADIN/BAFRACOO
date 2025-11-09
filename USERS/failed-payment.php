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
  <title>BAFRACOO - Payment Failed</title>
  <style>
    .error-container {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, #ef4444, #dc2626);
      padding: 2rem;
    }
    .error-card {
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
    .error-icon {
      font-size: 120px;
      color: var(--error-color);
      margin-bottom: 1.5rem;
      animation: shake 0.6s ease-out 0.2s both;
    }
    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-10px); }
      75% { transform: translateX(10px); }
    }
    .error-title {
      font-size: 2.5rem;
      font-weight: 700;
      color: var(--gray-900);
      margin-bottom: 1rem;
    }
    .error-text {
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
    .btn-retry, .btn-contact, .btn-back {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 1rem 2rem;
      border-radius: var(--radius-lg);
      font-weight: 600;
      text-decoration: none;
      transition: var(--transition-base);
    }
    .btn-retry {
      background: var(--primary-color);
      color: white;
    }
    .btn-retry:hover {
      background: var(--primary-dark);
      transform: translateY(-2px);
    }
    .btn-contact {
      background: var(--success-color);
      color: white;
    }
    .btn-contact:hover {
      background: #059669;
    }
    .btn-back {
      background: var(--gray-200);
      color: var(--gray-700);
    }
    .btn-back:hover {
      background: var(--gray-300);
    }
  </style>
</head>
<body>
  <div class="error-container">
    <div class="error-card">
      <div class="error-icon">
        <i class="fa-solid fa-circle-exclamation"></i>
      </div>
      <h1 class="error-title">Payment Failed</h1>
      <p class="error-text">
        We encountered an issue processing your payment.<br>
        Please try again or contact our support team for assistance.
      </p>
      <div class="action-buttons">
        <a href="orders.php" class="btn-retry">
          <ion-icon name="refresh-outline"></ion-icon>
          Try Again
        </a>
        <a href="../website.php" class="btn-contact">
          <ion-icon name="call-outline"></ion-icon>
          Contact Us
        </a>
        <a href="userdashboard.php" class="btn-back">
          <ion-icon name="home-outline"></ion-icon>
          Dashboard
        </a>
      </div>
    </div>
  </div>

  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>