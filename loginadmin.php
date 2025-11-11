<?php
  require 'connection.php';

  // Handle login before any HTML output so header() redirects work reliably
  $login_error = false;
  if(isset($_POST['submit'])){
    $email = mysqli_real_escape_string($con, $_POST['u_email']);
    $password = mysqli_real_escape_string($con, $_POST['u_password']);

    $sql = mysqli_query($con, "SELECT * FROM `admin` WHERE u_email = '$email' AND u_password = '$password'");

    if($sql && mysqli_num_rows($sql) > 0){
      $query = mysqli_fetch_array($sql);
      $_SESSION["login"] = true;
      $_SESSION["id"] = $query["id"];
      header('location:admindashboard.php');
      exit();
    } else {
      $login_error = true;
    }
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="./CSS/loginandregistration.css">
  <link rel="shortcut icon" href="./images/Capture.JPG" type="image/x-icon">
  <title>LOGIN PAGE</title>
</head>
<body>
  <div class="pooh-shiesty">
    <img src="./images/Captured.JPG" alt="">
    <div class="flower-outline">
    <h1> <ion-icon name="flower-outline"></ion-icon>  Seamless Online Transaction System</h1>
  </div>
  </div>
  <div class="grided">
    <div class="grid-1">
      <div class="text">
      </div>
    </div>
    <div class="grid-2">
      <div class="text-1">
        <div class="mr-crabs">
        <h1>LOGIN</h1>
        <p>Welcome back! Please Enter Your Details</p>
      </div>
        <form action="" method="post">
          
          <div class="inputbox">
          <ion-icon name="mail-outline"></ion-icon>
          <input type="email" name="u_email" required>
          <label for="email">E-MAIL</label></div>

          <div class="inputbox">
          <ion-icon name="lock-closed-outline"></ion-icon>
          <input type="text" name="u_password" required>
          <label for="password">PASSWORD</label></div>

          <button name="submit" type="submit" class="btn-2">SIGN IN</button>
          <h3 class="heading-3"> Don't have an account? <a href="registrationadmin.php">Sign Up for Free</a></h3>
          <h3 class="heading-3"><a href="./users/loginuser.php">USER SIGN-IN</a></h3>
        </form>
      </div>
    </div>
  </div>
  
  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
<?php if(!empty(
  /* show alert after HTML if login failed */
  $login_error
)) echo "<script>alert('WRONG USERNAME OR PASSWORD');</script>"; ?>
</html>