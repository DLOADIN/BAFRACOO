<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../CSS/loginandregistration.css">
  <link rel="shortcut icon" href="../images/Capture.JPG" type="image/x-icon" class="link">
  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
  <!--   <!-- <script src="../JS/file.js"></script> -->
 -->
</body>
  <title>REGISTRATION PAGE</title>
</head>
<body>
  <div class="pooh-shiesty">
    <img src="../images/Captured.JPG" alt="">
    <div class="flower-outline">
    <h1> <ion-icon name="flower-outline"></ion-icon>  Seamless Online Transaction System</h1>
  </div>
  </div>
  <div class="grided">
    <div class="grid-1">
      <div class="text">
      </div>
    </div>
    <div class="grid-2" id="grid-2">
      <div class="text-1">
        <h1>CREATE ACCOUNT</h1>
        <form action="" method="post">
          
          <div class="inputbox">
            <ion-icon name="person-outline"></ion-icon>
          <input type="text" name="u_name" required>
          <label for="">FULL NAMES</label></div>

          <div class="inputbox">
          <ion-icon name="mail-outline"></ion-icon>
          <input type="email" name="u_email" required>
          <label for="">E-MAIL</label></div>

          <div class="inputbox">
            <ion-icon name="phone-portrait-outline"></ion-icon>
          <input type="text" name="u_phonenumber" required maxlength="15">
          <label for="">PHONENUMBER</label></div>

          <div class="inputbox">
            <ion-icon name="location-outline"></ion-icon>
          <input type="text" name="u_address" required>
          <label for="">ADDRESS</label></div>

          <div class="inputbox">
          <ion-icon name="lock-closed-outline"></ion-icon>
          <input type="text" name="u_password" required>
          <label for="">PASSWORD</label></div>

          <button name="submit" type="submit" class="btn-2">SIGN IN</button>
          <h3 class="heading-3"> Do you have an account? <a href="loginuser.php">Sign In</a></h3>
        </form>
      </div>
    </div>
  </div>
</body>
</html>
<?php
  require 'connection.php';
  
  if(isset($_POST['submit'])){
    $name = $_POST['u_name'];
    $email = $_POST['u_email'];
    $phonenumber = $_POST['u_phonenumber'];
    $address = $_POST['u_address'];
    $password = $_POST['u_password'];
    $sql=mysqli_query($con,"INSERT INTO `user` VALUES('','$name','$email','$phonenumber','$address','$password')");
    
    if($sql){
      echo "<script>alert('Registered Successfully| Please Head to the Login ')</script>";
    }
    else{
      echo "<script>alert('failed to register')</script>";
    }
  }
?>
