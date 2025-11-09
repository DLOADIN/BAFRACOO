<?php
  // Start session and require database connection
  require "connection.php";
  
  // Initialize variables
  $success_message = '';
  $error_message = '';
  $row = array();
  
  // Check if user is logged in
  if(!empty($_SESSION["id"])){
    $id = intval($_SESSION["id"]); // Ensure ID is integer
    
    // Fetch current admin data
    $check = mysqli_query($con, "SELECT * FROM `admin` WHERE id = $id");
    
    if($check && mysqli_num_rows($check) > 0){
      $row = mysqli_fetch_assoc($check);
    } else {
      // If user not found, redirect to login
      session_destroy();
      header('Location: loginadmin.php');
      exit();
    }
  } else {
    // Not logged in, redirect to login
    header('Location: loginadmin.php');
    exit();
  }
  
  // Handle profile update
  if(isset($_POST['submit']) && $_SERVER['REQUEST_METHOD'] === 'POST'){
    
    // Validate and sanitize inputs
    $name = trim($_POST['u_name']);
    $email = trim($_POST['u_email']);
    $phonenumber = trim($_POST['u_phonenumber']);
    $address = trim($_POST['u_address']);
    $password = trim($_POST['u_password']);
    
    // Validation
    $errors = array();
    
    if(empty($name)){
      $errors[] = "Name is required.";
    }
    
    if(empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)){
      $errors[] = "Valid email is required.";
    }
    
    if(empty($phonenumber)){
      $errors[] = "Phone number is required.";
    } elseif(!is_numeric($phonenumber)){
      $errors[] = "Phone number must contain only numbers.";
    }
    
    if(empty($address)){
      $errors[] = "Address is required.";
    }
    
    // If no validation errors, proceed with update
    if(empty($errors)){
      
      // Escape strings for SQL
      $name = mysqli_real_escape_string($con, $name);
      $email = mysqli_real_escape_string($con, $email);
      $phonenumber = intval($phonenumber); // Convert to integer for database
      $address = mysqli_real_escape_string($con, $address);
      
      // Build update query based on whether password is provided
      if(!empty($password)){
        // Update with new password
        $password = mysqli_real_escape_string($con, $password);
        $update_query = "UPDATE `admin` SET 
                        u_name = '$name', 
                        u_email = '$email', 
                        u_phonenumber = $phonenumber, 
                        u_address = '$address', 
                        u_password = '$password' 
                        WHERE id = $id";
      } else {
        // Update without changing password
        $update_query = "UPDATE `admin` SET 
                        u_name = '$name', 
                        u_email = '$email', 
                        u_phonenumber = $phonenumber, 
                        u_address = '$address' 
                        WHERE id = $id";
      }
      
      // Execute update query
      $update_result = mysqli_query($con, $update_query);
      
      if($update_result){
        if(mysqli_affected_rows($con) > 0){
          $success_message = "Profile updated successfully!";
          
          // Refresh admin data to show updated information
          $check = mysqli_query($con, "SELECT * FROM `admin` WHERE id = $id");
          if($check && mysqli_num_rows($check) > 0){
            $row = mysqli_fetch_assoc($check);
          }
        } else {
          $success_message = "No changes were made to your profile.";
        }
      } else {
        $error_message = "Database error: " . mysqli_error($con);
      }
      
    } else {
      // Display validation errors
      $error_message = implode("<br>", $errors);
    }
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="./CSS/modern-dashboard.css">
  <link rel="stylesheet" href="./CSS/modern-tables.css">
  <link rel="stylesheet" href="./CSS/modern-forms.css">
  <link rel="shortcut icon" href="./images/Capture.JPG" type="image/x-icon">
  <script src="https://kit.fontawesome.com/14ff3ea278.js" crossorigin="anonymous"></script>
  <title>BAFRACOO - Admin Profile</title>
</head>
<body>
  <div class="dashboard-container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="sidebar-logo">
        <img src="./images/Captured.JPG" alt="BAFRACOO Logo">
        <span class="logo-text">BAFRACOO</span>
      </div>
      
      <nav class="sidebar-nav">
        <div class="nav-section">
          <h3 class="nav-section-title">Main Menu</h3>
          <ul class="nav-menu">
            <li class="nav-item">
              <a href="admindashboard.php" class="nav-link">
                <ion-icon name="home-outline" class="nav-icon"></ion-icon>
                <span class="nav-text">Dashboard</span>
              </a>
            </li>
            <li class="nav-item">
              <a href="addtool.php" class="nav-link">
                <ion-icon name="add-circle-outline" class="nav-icon"></ion-icon>
                <span class="nav-text">Add Order</span>
              </a>
            </li>
            <li class="nav-item">
              <a href="addorder.php" class="nav-link">
                <ion-icon name="construct-outline" class="nav-icon"></ion-icon>
                <span class="nav-text">Add Tool</span>
              </a>
            </li>
            <li class="nav-item">
              <a href="orders.php" class="nav-link">
                <ion-icon name="bag-handle-outline" class="nav-icon"></ion-icon>
                <span class="nav-text">Orders</span>
                <?php 
                $pending_orders = mysqli_query($con,"SELECT * FROM `order` WHERE status='Pending'");
                if($pending_orders === false) {
                  error_log("Adminprofile query failed: " . mysqli_error($con));
                  $pending_count = 0;
                } else {
                  $pending_count = mysqli_num_rows($pending_orders);
                }
                if($pending_count > 0): ?>
                  <span class="nav-badge"><?php echo $pending_count; ?></span>
                <?php endif; ?>
              </a>
            </li>
            <li class="nav-item">
              <a href="stock.php" class="nav-link">
                <ion-icon name="cube-outline" class="nav-icon"></ion-icon>
                <span class="nav-text">Inventory</span>
              </a>
            </li>
          </ul>
        </div>
        
        <div class="nav-section">
          <h3 class="nav-section-title">Management</h3>
          <ul class="nav-menu">
            <li class="nav-item">
              <a href="transactions.php" class="nav-link">
                <ion-icon name="analytics-outline" class="nav-icon"></ion-icon>
                <span class="nav-text">Transactions</span>
              </a>
            </li>
            <li class="nav-item">
              <a href="report.php" class="nav-link">
                <ion-icon name="document-text-outline" class="nav-icon"></ion-icon>
                <span class="nav-text">Reports</span>
              </a>
            </li>
            <li class="nav-item">
              <a href="adminprofile.php" class="nav-link active">
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
              <a href="website.php" class="nav-link">
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
            <?php echo strtoupper(substr($row['u_name'] ?? 'A', 0, 2)); ?>
          </div>
          <div class="user-info">
            <div class="user-name"><?php echo htmlspecialchars($row['u_name'] ?? 'Admin'); ?></div>
            <div class="user-role">Administrator</div>
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
          <h1 class="page-title">Admin Profile</h1>
        </div>
        <div class="header-right">
          <a href="logout.php" class="logout-btn">
            <ion-icon name="log-out-outline"></ion-icon>
            <span>Logout</span>
          </a>
        </div>
      </header>

        <div class="profile-layout">
          
          <!-- Profile Card -->
          <div class="dashboard-card profile-card">
            <div class="profile-header">
              <div class="profile-avatar">
                <?php echo strtoupper(substr($row['u_name'] ?? 'A', 0, 2)); ?>
              </div>
              
              <h2 class="profile-name">
                <?php echo htmlspecialchars($row['u_name'] ?? 'Admin'); ?>
              </h2>
              
              <p class="profile-role">Administrator</p>
              
              <div class="profile-details">
                <div class="profile-detail">
                  <ion-icon name="mail-outline"></ion-icon>
                  <span><?php echo htmlspecialchars($row['u_email'] ?? 'N/A'); ?></span>
                </div>
                
                <div class="profile-detail">
                  <ion-icon name="call-outline"></ion-icon>
                  <span><?php echo htmlspecialchars($row['u_phonenumber'] ?? 'N/A'); ?></span>
                </div>
                
                <div class="profile-detail">
                  <ion-icon name="location-outline"></ion-icon>
                  <span><?php echo htmlspecialchars($row['u_address'] ?? 'N/A'); ?></span>
                </div>
                
                <div class="profile-detail">
                  <ion-icon name="shield-checkmark-outline"></ion-icon>
                  <span>Active Admin</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Edit Profile Form -->
          <div class="dashboard-card form-card">
            <div class="card-header">
              <h3>
                <ion-icon name="create-outline"></ion-icon>
                Edit Profile Information
              </h3>
            </div>
            
            <form method="post" class="form-modern">
              <div class="form-row">
                <div class="form-group">
                  <label for="u_name">Full Name</label>
                  <input type="text" id="u_name" name="u_name" value="<?php echo htmlspecialchars($row['u_name'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                  <label for="u_email">Email Address</label>
                  <input type="email" id="u_email" name="u_email" value="<?php echo htmlspecialchars($row['u_email'] ?? ''); ?>" required>
                </div>
              </div>
              
              <div class="form-row">
                <div class="form-group">
                  <label for="u_phonenumber">Phone Number</label>
                  <input type="text" id="u_phonenumber" name="u_phonenumber" value="<?php echo htmlspecialchars($row['u_phonenumber'] ?? ''); ?>" required maxlength="15">
                </div>
                
                <div class="form-group">
                  <label for="u_address">Address</label>
                  <input type="text" id="u_address" name="u_address" value="<?php echo htmlspecialchars($row['u_address'] ?? ''); ?>" required>
                </div>
              </div>
              
              <div class="form-group">
                <label for="u_password">Password</label>
                <input type="password" id="u_password" name="u_password" placeholder="Leave blank to keep current password">
                <small class="form-text">Leave blank if you don't want to change your password</small>
              </div>
              
              <div class="form-actions">
                <button type="submit" name="submit" class="btn btn-primary btn-lg">
                  <ion-icon name="save-outline"></ion-icon>
                  <span>Save Changes</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </main>
  </div>

  <style>
    /* Profile page specific styles */
    .profile-layout {
      display: grid;
      grid-template-columns: 1fr 2fr;
      gap: var(--spacing-xl);
      align-items: start;
    }

    .profile-card .profile-header {
      text-align: center;
      padding: var(--spacing-lg);
    }

    .profile-avatar {
      width: 120px;
      height: 120px;
      margin: 0 auto var(--spacing-lg);
      background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
      border-radius: var(--radius-full);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 2.5rem;
      font-weight: 700;
      box-shadow: var(--shadow-lg);
    }

    .profile-name {
      font-size: 1.5rem;
      font-weight: 600;
      margin-bottom: var(--spacing-sm);
      color: var(--gray-900);
    }

    .profile-role {
      color: var(--gray-600);
      font-weight: 500;
      margin-bottom: var(--spacing-lg);
    }

    .profile-details {
      border-top: 1px solid var(--gray-200);
      padding-top: var(--spacing-lg);
      text-align: left;
    }

    .profile-detail {
      display: flex;
      align-items: center;
      margin-bottom: var(--spacing-md);
      color: var(--gray-600);
    }

    .profile-detail ion-icon {
      margin-right: var(--spacing-md);
      font-size: 1.25rem;
    }

    .profile-detail span {
      font-size: 0.875rem;
    }

    .alert {
      padding: var(--spacing-md);
      border-radius: var(--radius-md);
      margin-bottom: var(--spacing-lg);
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: var(--spacing-sm);
    }

    .alert-success {
      background: var(--success-color);
      color: white;
    }

    .alert-error {
      background: var(--error-color);
      color: white;
    }

    @media (max-width: 768px) {
      .profile-layout {
        grid-template-columns: 1fr !important;
      }
    }
  </style>

  <script>
    // Form validation and enhancement
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.querySelector('.form-modern');
      const submitBtn = form.querySelector('button[type="submit"]');
      
      // Add form submission handling
      form.addEventListener('submit', function(e) {
        // Add loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<ion-icon name="hourglass-outline"></ion-icon><span>Saving...</span>';
        
        // Validate email format
        const email = form.querySelector('input[name="u_email"]').value;
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(email)) {
          e.preventDefault();
          alert('Please enter a valid email address.');
          submitBtn.disabled = false;
          submitBtn.innerHTML = '<ion-icon name="save-outline"></ion-icon><span>Save Changes</span>';
          return;
        }
        
        // Validate phone number
        const phone = form.querySelector('input[name="u_phonenumber"]').value;
        const phonePattern = /^[\d\s\-\+\(\)]+$/;
        if (phone && !phonePattern.test(phone)) {
          e.preventDefault();
          alert('Please enter a valid phone number.');
          submitBtn.disabled = false;
          submitBtn.innerHTML = '<ion-icon name="save-outline"></ion-icon><span>Save Changes</span>';
          return;
        }
      });

      // Enhanced sidebar functionality
      const sidebar = document.getElementById('sidebar');
      const toggleBtn = document.querySelector('.sidebar-toggle');
      const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
      const overlay = document.querySelector('.sidebar-overlay');

      function toggleSidebar() {
        sidebar.classList.toggle('collapsed');
        localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
      }

      function showSidebar() {
        sidebar.classList.add('show');
        overlay.style.display = 'block';
      }

      function hideSidebar() {
        sidebar.classList.remove('show');
        overlay.style.display = 'none';
      }

      // Initialize sidebar state
      if (localStorage.getItem('sidebarCollapsed') === 'true') {
        sidebar.classList.add('collapsed');
      }

      if (toggleBtn) {
        toggleBtn.addEventListener('click', toggleSidebar);
      }

      if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', showSidebar);
      }

      if (overlay) {
        overlay.addEventListener('click', hideSidebar);
      }

      // Mobile responsiveness
      function handleResize() {
        if (window.innerWidth <= 768) {
          sidebar.classList.remove('collapsed');
        }
      }

      window.addEventListener('resize', handleResize);
      handleResize();
    });
  </script>

  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
  <!-- <script src="./JS/file.js"></script> -->
</body>
</html>
