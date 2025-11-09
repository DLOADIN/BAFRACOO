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
  error_reporting(0);
  
  // Handle profile update
  if(isset($_POST['submit'])){
    $name = mysqli_real_escape_string($con, $_POST['u_name']);
    $email = mysqli_real_escape_string($con, $_POST['u_email']);
    $phonenumber = mysqli_real_escape_string($con, $_POST['u_phonenumber']);
    $address = mysqli_real_escape_string($con, $_POST['u_address']);
    $password = mysqli_real_escape_string($con, $_POST['u_password']);
    
    $update_sql = mysqli_query($con,"UPDATE `user` SET u_name='$name', u_email='$email', u_phonenumber='$phonenumber', u_address='$address', u_password='$password' WHERE id='$id'");
    
    if($update_sql){
      $success_message = "Profile updated successfully!";
      // Refresh the data
      $check = mysqli_query($con,"SELECT * FROM `user` WHERE id=$id ");
      $row = mysqli_fetch_array($check);
    } else {
      $error_message = "Failed to update profile. Please try again.";
    }
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
  <title>BAFRACOO - User Profile</title>
</head>
<body>
  <div class="dashboard-container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="sidebar-logo">
        <img src="../images/Captured.JPG" alt="BAFRACOO Logo">
        <span class="logo-text">BAFRACOO</span>
      </div>
      
      <nav class="sidebar-nav">
        <div class="nav-section">
          <h3 class="nav-section-title">Main Menu</h3>
          <ul class="nav-menu">
            <li class="nav-item">
              <a href="userdashboard.php" class="nav-link">
                <ion-icon name="home-outline" class="nav-icon"></ion-icon>
                <span class="nav-text">Dashboard</span>
              </a>
            </li>
            <li class="nav-item">
              <a href="stock.php" class="nav-link">
                <ion-icon name="cube-outline" class="nav-icon"></ion-icon>
                <span class="nav-text">Browse Tools</span>
              </a>
            </li>
            <li class="nav-item">
              <a href="orders.php" class="nav-link">
                <ion-icon name="bag-handle-outline" class="nav-icon"></ion-icon>
                <span class="nav-text">My Orders</span>
              </a>
            </li>
            <li class="nav-item">
              <a href="transactions.php" class="nav-link">
                <ion-icon name="analytics-outline" class="nav-icon"></ion-icon>
                <span class="nav-text">Transactions</span>
              </a>
            </li>
          </ul>
        </div>
        
        <div class="nav-section">
          <h3 class="nav-section-title">Account</h3>
          <ul class="nav-menu">
            <li class="nav-item">
              <a href="userprofile.php" class="nav-link active">
                <ion-icon name="person-circle-outline" class="nav-icon"></ion-icon>
                <span class="nav-text">Profile</span>
              </a>
            </li>
          </ul>
        </div>
        
        <div class="nav-section">
          <h3 class="nav-section-title">Website</h3>
          <ul class="nav-menu">
            <li class="nav-item">
              <a href="../website.php" class="nav-link">
                <ion-icon name="globe-outline" class="nav-icon"></ion-icon>
                <span class="nav-text">Visit Website</span>
              </a>
            </li>
          </ul>
        </div>
      </nav>
      
      <div class="sidebar-footer">
        <div class="sidebar-user">
          <div class="user-avatar">
            <?php echo strtoupper(substr($row['u_name'] ?? 'U', 0, 2)); ?>
          </div>
          <div class="user-info">
            <div class="user-name"><?php echo htmlspecialchars($row['u_name'] ?? 'User'); ?></div>
            <div class="user-role">Customer</div>
          </div>
        </div>
      </div>
    </aside>

    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay"></div>

    <!-- Main Content -->
    <main class="main-content">
      <header class="header">
        <div class="header-left">
          <button class="mobile-menu-btn">
            <ion-icon name="menu-outline"></ion-icon>
          </button>
          <button class="sidebar-toggle">
            <ion-icon name="chevron-back-outline"></ion-icon>
          </button>
          <h1 class="page-title">My Profile</h1>
        </div>
        <div class="header-right">
          <a href="logout.php" class="logout-btn">
            <ion-icon name="log-out-outline"></ion-icon>
            <span>Logout</span>
          </a>
        </div>
      </header>

      <div class="content-area">
        <?php if(isset($success_message)): ?>
          <div style="background: var(--success-color); color: white; padding: var(--spacing-md); border-radius: var(--radius-md); margin-bottom: var(--spacing-lg); font-weight: 500;">
            <ion-icon name="checkmark-circle-outline" style="margin-right: var(--spacing-sm);"></ion-icon>
            <?php echo $success_message; ?>
          </div>
        <?php endif; ?>

        <?php if(isset($error_message)): ?>
          <div style="background: var(--error-color); color: white; padding: var(--spacing-md); border-radius: var(--radius-md); margin-bottom: var(--spacing-lg); font-weight: 500;">
            <ion-icon name="alert-circle-outline" style="margin-right: var(--spacing-sm);"></ion-icon>
            <?php echo $error_message; ?>
          </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: var(--spacing-xl); align-items: start;">
          
          <!-- Profile Card -->
          <div class="dashboard-card">
            <div style="text-align: center; padding: var(--spacing-lg);">
              <div style="width: 120px; height: 120px; margin: 0 auto var(--spacing-lg); background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); border-radius: var(--radius-full); display: flex; align-items: center; justify-content: center; color: white; font-size: 2.5rem; font-weight: 700; box-shadow: var(--shadow-lg);">
                <?php echo strtoupper(substr($row['u_name'] ?? 'U', 0, 2)); ?>
              </div>
              
              <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: var(--spacing-sm); color: var(--gray-900);">
                <?php echo htmlspecialchars($row['u_name'] ?? 'User'); ?>
              </h2>
              
              <p style="color: var(--gray-600); font-weight: 500; margin-bottom: var(--spacing-lg);">Customer</p>
              
              <div style="border-top: 1px solid var(--gray-200); padding-top: var(--spacing-lg); text-align: left;">
                <div style="display: flex; align-items: center; margin-bottom: var(--spacing-md); color: var(--gray-600);">
                  <ion-icon name="mail-outline" style="margin-right: var(--spacing-md); font-size: 1.25rem;"></ion-icon>
                  <span style="font-size: 0.875rem;"><?php echo htmlspecialchars($row['u_email'] ?? 'N/A'); ?></span>
                </div>
                
                <div style="display: flex; align-items: center; margin-bottom: var(--spacing-md); color: var(--gray-600);">
                  <ion-icon name="call-outline" style="margin-right: var(--spacing-md); font-size: 1.25rem;"></ion-icon>
                  <span style="font-size: 0.875rem;"><?php echo htmlspecialchars($row['u_phonenumber'] ?? 'N/A'); ?></span>
                </div>
                
                <div style="display: flex; align-items: center; margin-bottom: var(--spacing-md); color: var(--gray-600);">
                  <ion-icon name="location-outline" style="margin-right: var(--spacing-md); font-size: 1.25rem;"></ion-icon>
                  <span style="font-size: 0.875rem;"><?php echo htmlspecialchars($row['u_address'] ?? 'N/A'); ?></span>
                </div>
                
                <div style="display: flex; align-items: center; color: var(--gray-600);">
                  <ion-icon name="person-outline" style="margin-right: var(--spacing-md); font-size: 1.25rem;"></ion-icon>
                  <span style="font-size: 0.875rem;">Active Customer</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Edit Profile Form -->
          <div class="dashboard-card">
            <div class="card-header">
              <h3 style="font-size: 1.25rem; font-weight: 600; color: var(--gray-900); margin: 0;">
                <ion-icon name="create-outline" style="margin-right: var(--spacing-sm);"></ion-icon>
                Edit Profile Information
              </h3>
            </div>
            
            <form method="post" style="padding: var(--spacing-lg);">
              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-lg); margin-bottom: var(--spacing-lg);">
                <div>
                  <label style="display: block; font-weight: 500; color: var(--gray-700); margin-bottom: var(--spacing-sm);">Full Name</label>
                  <input type="text" name="u_name" value="<?php echo htmlspecialchars($row['u_name'] ?? ''); ?>" required 
                         style="width: 100%; padding: var(--spacing-md); border: 1px solid var(--gray-300); border-radius: var(--radius-md); font-size: 0.875rem; transition: all var(--transition-fast); background: var(--white);">
                </div>
                
                <div>
                  <label style="display: block; font-weight: 500; color: var(--gray-700); margin-bottom: var(--spacing-sm);">Email Address</label>
                  <input type="email" name="u_email" value="<?php echo htmlspecialchars($row['u_email'] ?? ''); ?>" required 
                         style="width: 100%; padding: var(--spacing-md); border: 1px solid var(--gray-300); border-radius: var(--radius-md); font-size: 0.875rem; transition: all var(--transition-fast); background: var(--white);">
                </div>
              </div>
              
              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-lg); margin-bottom: var(--spacing-lg);">
                <div>
                  <label style="display: block; font-weight: 500; color: var(--gray-700); margin-bottom: var(--spacing-sm);">Phone Number</label>
                  <input type="text" name="u_phonenumber" value="<?php echo htmlspecialchars($row['u_phonenumber'] ?? ''); ?>" required maxlength="15"
                         style="width: 100%; padding: var(--spacing-md); border: 1px solid var(--gray-300); border-radius: var(--radius-md); font-size: 0.875rem; transition: all var(--transition-fast); background: var(--white);">
                </div>
                
                <div>
                  <label style="display: block; font-weight: 500; color: var(--gray-700); margin-bottom: var(--spacing-sm);">Address</label>
                  <input type="text" name="u_address" value="<?php echo htmlspecialchars($row['u_address'] ?? ''); ?>" required 
                         style="width: 100%; padding: var(--spacing-md); border: 1px solid var(--gray-300); border-radius: var(--radius-md); font-size: 0.875rem; transition: all var(--transition-fast); background: var(--white);">
                </div>
              </div>
              
              <div style="margin-bottom: var(--spacing-xl);">
                <label style="display: block; font-weight: 500; color: var(--gray-700); margin-bottom: var(--spacing-sm);">Password</label>
                <input type="password" name="u_password" value="<?php echo htmlspecialchars($row['u_password'] ?? ''); ?>" required 
                       style="width: 100%; padding: var(--spacing-md); border: 1px solid var(--gray-300); border-radius: var(--radius-md); font-size: 0.875rem; transition: all var(--transition-fast); background: var(--white);">
                <small style="color: var(--gray-500); font-size: 0.75rem; margin-top: var(--spacing-xs); display: block;">
                  Leave unchanged if you don't want to update your password
                </small>
              </div>
              
              <div style="text-align: right;">
                <button type="submit" name="submit" 
                        style="background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); color: white; border: none; padding: var(--spacing-md) var(--spacing-xl); border-radius: var(--radius-md); font-weight: 500; cursor: pointer; transition: all var(--transition-base); display: inline-flex; align-items: center; gap: var(--spacing-sm);"
                        onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='var(--shadow-lg)'"
                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                  <ion-icon name="save-outline"></ion-icon>
                  <span>Save Changes</span>
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Account Statistics -->
        <div class="dashboard-card" style="margin-top: var(--spacing-xl);">
          <div class="card-header">
            <h3 style="font-size: 1.25rem; font-weight: 600; color: var(--gray-900); margin: 0;">Account Statistics</h3>
          </div>
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spacing-lg); padding: var(--spacing-lg);">
            <div style="text-align: center; padding: var(--spacing-lg); background: var(--gray-50); border-radius: var(--radius-lg);">
              <div style="font-size: 2rem; font-weight: 700; color: var(--primary-color); margin-bottom: var(--spacing-sm);">
                <?php
                  $total_orders = mysqli_query($con, "SELECT COUNT(*) as count FROM `order` WHERE user_id='$id'");
                  echo $total_orders ? mysqli_fetch_assoc($total_orders)['count'] : '0';
                ?>
              </div>
              <div style="font-size: 0.875rem; color: var(--gray-600); font-weight: 500;">Total Orders</div>
            </div>
            
            <div style="text-align: center; padding: var(--spacing-lg); background: var(--gray-50); border-radius: var(--radius-lg);">
              <div style="font-size: 2rem; font-weight: 700; color: var(--success-color); margin-bottom: var(--spacing-sm);">
                <?php
                  $completed_orders = mysqli_query($con, "SELECT COUNT(*) as count FROM `order` WHERE user_id='$id' AND status='Completed'");
                  echo $completed_orders ? mysqli_fetch_assoc($completed_orders)['count'] : '0';
                ?>
              </div>
              <div style="font-size: 0.875rem; color: var(--gray-600); font-weight: 500;">Completed Orders</div>
            </div>
            
            <div style="text-align: center; padding: var(--spacing-lg); background: var(--gray-50); border-radius: var(--radius-lg);">
              <div style="font-size: 1.5rem; font-weight: 700; color: var(--accent-color); margin-bottom: var(--spacing-sm);">
                <?php
                  $total_spent = mysqli_query($con, "SELECT SUM(u_price) as total FROM `order` WHERE user_id='$id' AND status='Completed'");
                  $spent = mysqli_fetch_assoc($total_spent)['total'] ?? 0;
                  echo number_format($spent);
                ?> RWF
              </div>
              <div style="font-size: 0.875rem; color: var(--gray-600); font-weight: 500;">Total Spent</div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <style>
    input:focus {
      outline: none;
      border-color: var(--primary-color) !important;
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1) !important;
    }
    
    @media (max-width: 768px) {
      .content-area > div:first-child {
        grid-template-columns: 1fr !important;
      }
    }
  </style>

  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <!-- <script src="../JS/file.js"></script -->
</body>
<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>
<?php
  if(isset($_POST['submit'])){
    $name = $_POST['u_name'];
    $email = $_POST['u_email'];
    $phonenumber = $_POST['u_phonenumber'];
    $address = $_POST['u_address'];
    $password = $_POST['u_password'];
    $sql=mysqli_query($con,"UPDATE `user` SET u_name='$name', u_email='$email', u_phonenumber='$phonenumber',u_address='$address',u_password='$password' WHERE id='$id' ");
    
    if($sql){
      echo "<script>alert('Updated Successfully')</script>";
    }
    else{
      echo "<script>alert('failed to update')</script>";
    }
  }
?>
