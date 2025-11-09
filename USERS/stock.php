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
  <link rel="stylesheet" href="../CSS/modern-tables.css">
  <link rel="shortcut icon" href="../images/Capture.JPG" type="image/x-icon">
  <script src="https://kit.fontawesome.com/14ff3ea278.js" crossorigin="anonymous"></script>
  <title>BAFRACOO - Browse Tools</title>
  <script src="../JS/file.js"></script>
  <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
</head>
<body>
  <div class="dashboard-container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="sidebar-logo">
        <img src="../images/Captured.JPG" alt="BAFRACOO Logo">
        <span class="logo-text">BAFRACOO</span>
      </div>
      <ul class="menu">
        <li>
          <a href="userdashboard.php">
            <ion-icon name="home-outline"></ion-icon>
            <span>DASHBOARD</span>
          </a>
        </li>
        <li>
          <a href="orders.php">
            <ion-icon name="bag-handle-outline"></ion-icon>
            <span>ORDERS</span>
          </a>
        </li>
        <li>
          <a href="stock.php">
            <ion-icon name="pricetags-outline"></ion-icon>
            <span>STOCK</span>
          </a>
        </li>
        <li>
          <a href="transactions.php">
            <ion-icon name="git-compare-outline"></ion-icon>
            <span>TRANSACTIONS</span>
          </a>
        </li>
        <li>
          <a href="userprofile.php">
            <ion-icon name="person-circle-outline"></ion-icon>
            <span>PROFILE</span>
          </a>
        </li>
        <li>
          <a href="../website.php">
            <ion-icon name="planet-outline"></ion-icon>
            <span>HOME SITE</span>
          </a>
        </li>
    </ul>
  </div>

    <div class="main-content" id="main-content">
      <div class="header-wrapper">
        <div class="header-title">
          <h1>ORDERS</h1>
        </div>
        <div class="user-info">
          <div class="gango">
          <h3 class="my-account-header">USER 001</h3>
          <p>USER PANEL</p></div>  
          <button name="submit" type="submit" class="btn-3" >
            <a href="logout.php">LOGOUT</a>
          </button>
        </div>       
         </div>
         <div class="new-amounts"> 
          <div class="title">
          <h2 class="h2">ENTRY/STOCK DATA</h2>
          </div><table><tr>
              <th>#</th>
              <TH>TOOL NAME</TH>
              <th>TYPE</th>
              <th>TOOL DESCRIPTION</th>
              <th>DATE</th>
              <th>PRICE</th>
              <th>ORDER NOW</th>
              </tr>
            <?php
            $sql=mysqli_query($con,"SELECT * FROM `tool`");
            $row = mysqli_num_rows($sql);
            if($row){
              while($row=mysqli_fetch_array($sql))
              { 
            ?>
            <tr>
              <td><?php echo $row['id']?></td>
              <td><?php echo $row['u_toolname']?></td>
              <td><?php echo $row['u_type']?></td>
              <td><?php echo $row['u_tooldescription']?></td>
              <td><?php echo $row['u_date']?></td>
              <td><?php echo $row['u_price']?></td>
              <td>  
                <button class="lebutton"><a href="stock.php?id=<?php echo $row['id']?>">ORDER</a></button>
              </td>
              <?php
            }
          }
              ?>
          </tr>
        </table>
        </div>

        <div class="graded" id="graded">
          <form action="" method="post">
          <div class="real-form">
          <?php
          $sql=mysqli_query($con,"SELECT * FROM `user` WHERE id='$id' ");
          $row = mysqli_fetch_array($sql);
          ?>
              <input type="text" name="user_id" value="<?php 
              error_reporting(0);
              echo $row['id']?> " read-only hidden>
            </div>
          <?php
          if(isset($_GET['id'])){
          $id=$_GET['id'];
        }
          $sql=mysqli_query($con,"SELECT * FROM tool WHERE id='$id' ");
          $row = mysqli_fetch_array($sql);
          ?>
            <div class="real-form">
              <label for="">Tool Name</label>
              <input type="text" name="u_toolname" placeholder="NAMES OF THE TOOL" class="moon-walk" value="<?php echo $row['u_toolname']?>" required>
              <label for="">Number Of Items Desired</label>
              <input type="number" name="u_itemsnumber" placeholder="ONE - MANY" value="<?php echo $row['u_itemsnumber']?>" required>
            </div>
            <div class="real-form">
              <label for="">TYPE</label>
              <input type="text" name="u_type" class="moon-walk" placeholder="SHOW TYPE" value="<?php echo $row['u_type']?>" required readonly>
              <label for="">Tool Description</label>
              <input type="text" name="u_tooldescription" class="moon-walk" placeholder="Please Tell Us More About The Product/Tool" value="<?php echo $row['u_tooldescription']?>" required readonly>
            </div>
            <div class="real-form">
              <label for="">PRICE</label>
              <input type="text" name="u_price" class="moon-walk" value="<?php echo $row['u_price']?>" readonly>
              <label for="">TODAY'S DATE</label>
              <input type="text" name="u_date" class="moon-walk" value="<?php echo date('Y-m-d')?>" readonly>
            </div>
        
            <div class="checkbox">
              <input type="checkbox" name="" id="" required>
              <h3>I confirm that I have read and accepted the terms and conditions and privacy policy</h3>
            </div>
            <div id="btn-2">
              <button name="submit" type="submit" class="button-1">
              PURCHASE</a></button>
            </div>
          </form></div>
        </div>
         </div>
<style>
  .button-1 a{
    text-decoration: none;
    color:white;
  }
</style>
<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>
<?php
  if(isset($_POST['submit'])){
    $user_id=$_POST['user_id'];
    $toolname = $_POST['u_toolname'];
    $nitems = $_POST['u_itemsnumber'];
    $type = $_POST['u_type'];
    $tooldescription = $_POST['u_tooldescription'];
    $date = date('Y-m-d',strtotime($_POST['u_date']));
    $price= $_POST['u_price'];
    $total_price = $nitems * $price;
    $sql=mysqli_query($con,"INSERT INTO `order` VALUES ('','$user_id', '$toolname', '$nitems', '$type', '$tooldescription', '$date', '$price','$total_price')");
    
    if($sql){
      echo "<script>alert('Recorded Successfully')</script>";
    }
    else{
      echo "<script>alert('failed to record')</script>";
    }
  }
?>
