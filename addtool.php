<?php
  require "connection.php";
  if(!empty($_SESSION["id"])){
  $id = $_SESSION["id"];
  $check = mysqli_query($con,"SELECT * FROM `admin` WHERE id=$id ");
  $row = mysqli_fetch_array($check);
  }
  else{
  header('location:loginadmin.php');
  }
  ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="./CSS/modern-dashboard.css">
  <link rel="stylesheet" href="./CSS/modern-forms.css">
  <link rel="stylesheet" href="./CSS/modern-tables.css">
  <link rel="shortcut icon" href="./images/Capture.JPG" type="image/x-icon">
  <script src="https://kit.fontawesome.com/14ff3ea278.js" crossorigin="anonymous"></script>
  <script src="./JS/file.js"></script>
  <title>BAFRACOO - Add Tool</title>
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
              <a href="admindashboard.php" class="nav-link" data-tooltip="Dashboard">
                <ion-icon name="home-outline" class="nav-icon"></ion-icon>
                <span class="nav-text">Dashboard</span>
              </a>
            </li>
            <li class="nav-item">
              <a href="addtool.php" class="nav-link active" data-tooltip="Add Tool">
                <ion-icon name="add-circle-outline" class="nav-icon"></ion-icon>
                <span class="nav-text">Add Tool</span>
              </a>
            </li>
            <li class="nav-item">
              <a href="orders.php" class="nav-link" data-tooltip="Orders">
                <ion-icon name="bag-handle-outline" class="nav-icon"></ion-icon>
                <span class="nav-text">Orders</span>
                <?php 
                $pending_orders = mysqli_query($con,"SELECT * FROM `order` WHERE status='Pending'");
                $pending_count = $pending_orders ? mysqli_num_rows($pending_orders) : 0;
                if($pending_count > 0): ?>
                  <span class="nav-badge"><?php echo $pending_count; ?></span>
                <?php endif; ?>
              </a>
            </li>
            <li class="nav-item">
              <a href="stock.php" class="nav-link" data-tooltip="Inventory">
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
              <a href="transactions.php" class="nav-link" data-tooltip="Transactions">
                <ion-icon name="analytics-outline" class="nav-icon"></ion-icon>
                <span class="nav-text">Transactions</span>
              </a>
            </li>
            <li class="nav-item">
              <a href="report.php" class="nav-link" data-tooltip="Reports">
                <ion-icon name="document-text-outline" class="nav-icon"></ion-icon>
                <span class="nav-text">Reports</span>
              </a>
            </li>
            <li class="nav-item">
              <a href="adminprofile.php" class="nav-link" data-tooltip="Profile">
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
              <a href="website.php" class="nav-link" data-tooltip="Visit Website">
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
          <h1 class="page-title">Add Tool</h1>
        </div>
        <div class="header-right">
          <a href="logout.php" class="logout-btn">
            <ion-icon name="log-out-outline"></ion-icon>
            <span>Logout</span>
          </a>
        </div>
      </header>

      <div class="content-area">
        <!-- Add Tool Form -->
        <div class="dashboard-card">
          <div class="card-header">
            <h3 class="card-title">
              <ion-icon name="add-circle-outline"></ion-icon>
              Add New Tool
            </h3>
            <p class="card-description">Fill in the details below to add a new tool to your inventory</p>
          </div>
          
          <form action="" method="post" class="modern-form">
            <div class="form-grid">
              <div class="form-group">
                <label class="form-label">Tool Name</label>
                <input type="text" name="u_toolname" placeholder="Enter tool name" required class="form-input">
              </div>
              
              <div class="form-group">
                <label class="form-label">Number of Items</label>
                <input type="number" name="u_itemsnumber" placeholder="Enter quantity" required class="form-input">
              </div>
            </div>

            <div class="form-grid">
              <div class="form-group">
                <label class="form-label">Tool Type</label>
                <input type="text" name="u_type" placeholder="Enter tool type" required class="form-input">
              </div>
              
              <div class="form-group">
                <label class="form-label">Price (RWF)</label>
                <input type="number" name="u_price" placeholder="Enter price in RWF" required class="form-input"> 
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Tool Description</label>
              <textarea name="u_tooldescription" placeholder="Please tell us more about the product/tool" required rows="4" class="form-textarea"></textarea>
            </div>

            <div class="form-grid">
              <div class="form-group">
                <label class="form-label">Today's Date</label>
                <input type="date" name="u_date" value="<?php echo date('Y-m-d'); ?>" required class="form-input">
              </div>
            </div>

            <div class="form-group">
              <div class="form-checkbox">
                <input type="checkbox" id="terms" required>
                <label for="terms">I confirm that I have read and accepted the terms and conditions and privacy policy</label>
              </div>
            </div>

            <div class="form-actions">
              <button type="reset" class="btn-secondary" style="width:20vh;height:5vh; border-radius:15px;">
                <ion-icon name="refresh-outline"></ion-icon>
                Reset Form
              </button>
              <button type="submit" name="submit" class="btn-primary" style="width:20vh;height:5vh; border-radius:15px;">
                <ion-icon name="add-outline"></ion-icon>
                Add Tool
              </button>
            </div>
          </form>
        </div>
      </div>
    </main>
  </div>

  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
  
  <script>
    // Initialize dashboard functionality
    document.addEventListener('DOMContentLoaded', function() {
      // Initialize Dashboard if available
      if (typeof window.Dashboard !== 'undefined') {
        new window.Dashboard();
      } else {
        // Fallback sidebar functionality
        const sidebar = document.querySelector('.sidebar');
        const sidebarToggle = document.querySelector('.sidebar-toggle');
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        const sidebarOverlay = document.querySelector('.sidebar-overlay');
        const mainContent = document.querySelector('.main-content');
        
        // Sidebar toggle functionality
        if (sidebarToggle) {
          sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
          });
        }
        
        // Mobile menu functionality
        if (mobileMenuBtn) {
          mobileMenuBtn.addEventListener('click', () => {
            sidebar.classList.add('mobile-open');
            sidebarOverlay.classList.add('active');
          });
        }
        
        // Sidebar overlay functionality
        if (sidebarOverlay) {
          sidebarOverlay.addEventListener('click', () => {
            sidebar.classList.remove('mobile-open');
            sidebarOverlay.classList.remove('active');
          });
        }
        
        // Handle window resize
        window.addEventListener('resize', () => {
          if (window.innerWidth > 768) {
            sidebar.classList.remove('mobile-open');
            sidebarOverlay.classList.remove('active');
          }
        });
        
        // Tooltip functionality
        const tooltipElements = document.querySelectorAll('[data-tooltip]');
        tooltipElements.forEach(element => {
          element.addEventListener('mouseenter', function() {
            const tooltipText = this.getAttribute('data-tooltip');
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = tooltipText;
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.left = rect.right + 10 + 'px';
            tooltip.style.top = rect.top + (rect.height / 2) - (tooltip.offsetHeight / 2) + 'px';
          });
          
          element.addEventListener('mouseleave', function() {
            const tooltip = document.querySelector('.tooltip');
            if (tooltip) {
              tooltip.remove();
            }
          });
        });
      }
    });
  </script>
</body>
</html>
              <input type="text" name="u_date" class="moon-walk" value="<?php echo date('Y-m-d')?>" readonly>
            </div>
        
            <div class="checkbox">
              <input type="checkbox" name="" id="" required>
              <!-- <h3>I confirm that I have read and accepted the terms and conditions and privacy policy</h3> -->
            </div>
            <div id="btn-2">
              <button name="submit" type="submit" class="button-1">SUBMIT</button>
            </div>
          </form></div>
        </div>
<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>
<?php
  if(isset($_POST['submit'])){
    $toolname = $_POST['u_toolname'];
    $nitems = $_POST['u_itemsnumber'];
    $type = $_POST['u_type'];
    $tooldescription = $_POST['u_tooldescription'];
    $date = date('Y-m-d',strtotime($_POST['u_date']));
    $price= $_POST['u_price'];
    $sql=mysqli_query($con,"INSERT INTO `tool` VALUES('','$toolname','$nitems','$type','$tooldescription','$date','$price')");
    
    if($sql){
      echo "<script>alert('Recorded Successfully')</script>";
    }
    else{
      echo "<script>alert('failed to record')</script>";
    }
  }
?>