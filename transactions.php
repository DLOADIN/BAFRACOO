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
  <link rel="stylesheet" href="./CSS/modern-tables.css">
  <link rel="shortcut icon" href="./images/Capture.JPG" type="image/x-icon">
  <script src="https://kit.fontawesome.com/14ff3ea278.js" crossorigin="anonymous"></script>
  <title>BAFRACOO - Transactions</title>
  <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
  <script src="./JS/file.js"></script>
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
              <a href="addtool.php" class="nav-link" data-tooltip="Add Tool">
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
              <a href="transactions.php" class="nav-link active" data-tooltip="Transactions">
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
          <h1 class="page-title">Transactions</h1>
        </div>
        <div class="header-right">
          <a href="logout.php" class="logout-btn">
            <ion-icon name="log-out-outline"></ion-icon>
            <span>Logout</span>
          </a>
        </div>
      </header>

      <div class="content-area">
        <!-- Transactions Summary Cards -->
        <div class="dashboard-grid">
          <!-- Total Revenue Card -->
          <div class="dashboard-card">
            <div class="card-header">
              <h3 class="card-title">Total Revenue</h3>
              <div class="card-icon success">
                <ion-icon name="trending-up-outline"></ion-icon>
              </div>
            </div>
            <div class="card-value">
              <?php
                $total_revenue = mysqli_query($con, "SELECT SUM(total_price) as total FROM `order` WHERE status='Completed'");
                $revenue = $total_revenue ? mysqli_fetch_assoc($total_revenue)['total'] ?? 0 : 0;
                echo number_format($revenue) . ' RWF';
              ?>
            </div>
            <div class="card-change positive">
              <ion-icon name="trending-up-outline"></ion-icon>
              <span>Money In</span>
            </div>
          </div>

          <!-- Total Transactions Card -->
          <div class="dashboard-card">
            <div class="card-header">
              <h3 class="card-title">Total Transactions</h3>
              <div class="card-icon primary">
                <ion-icon name="cash-outline"></ion-icon>
              </div>
            </div>
            <div class="card-value">
              <?php
                $total_transactions = mysqli_query($con, "SELECT COUNT(*) as count FROM `order`");
                echo $total_transactions ? mysqli_fetch_assoc($total_transactions)['count'] : '0';
              ?>
            </div>
            <div class="card-change positive">
              <ion-icon name="trending-up-outline"></ion-icon>
              <span>All orders</span>
            </div>
          </div>

          <!-- Today's Revenue Card -->
          <div class="dashboard-card">
            <div class="card-header">
              <h3 class="card-title">Today's Revenue</h3>
              <div class="card-icon accent">
                <ion-icon name="calendar-outline"></ion-icon>
              </div>
            </div>
            <div class="card-value">
              <?php
                $today_revenue = mysqli_query($con, "SELECT SUM(total_price) as total FROM `order` WHERE DATE(u_date) = CURDATE() AND status='Completed'");
                $today_total = $today_revenue ? mysqli_fetch_assoc($today_revenue)['total'] ?? 0 : 0;
                echo number_format($today_total) . ' RWF';
              ?>
            </div>
            <div class="card-change positive">
              <ion-icon name="time-outline"></ion-icon>
              <span>Today only</span>
            </div>
          </div>

          <!-- Pending Payments Card -->
          <div class="dashboard-card">
            <div class="card-header">
              <h3 class="card-title">Pending Payments</h3>
              <div class="card-icon warning">
                <ion-icon name="hourglass-outline"></ion-icon>
              </div>
            </div>
            <div class="card-value">
              <?php
                $pending_payments = mysqli_query($con, "SELECT SUM(total_price) as total FROM `order` WHERE status='Pending'");
                $pending_total = $pending_payments ? mysqli_fetch_assoc($pending_payments)['total'] ?? 0 : 0;
                echo number_format($pending_total) . ' RWF';
              ?>
            </div>
            <div class="card-change warning">
              <ion-icon name="time-outline"></ion-icon>
              <span>Awaiting payment</span>
            </div>
          </div>
        </div>

        <!-- Transactions Table -->
        <div class="dashboard-card">
          <div class="card-header">
            <h3 class="card-title">
              <ion-icon name="analytics-outline"></ion-icon>
              Transaction History
            </h3>
            <div class="table-actions">
              <button class="btn-secondary" style="width:20vh;height:5vh; border-radius:15px;">
                <ion-icon name="filter-outline"></ion-icon>
                Filter by Date
              </button>
              <button class="btn-secondary" style="width:20vh;height:5vh; border-radius:15px;">
                <ion-icon name="download-outline" ></ion-icon>
                Export
              </button>
            </div>
          </div>

          <div class="table-wrapper">
            <table class="modern-table">
              <thead>
                <tr>
                  <th>Transaction ID</th>
                  <th>Customer</th>
                  <th>Tool</th>
                  <th>Quantity</th>
                  <th>Amount</th>
                  <th>Type</th>
                  <th>Status</th>
                  <th>Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $sql = "SELECT `order`.*, user.u_name FROM `order` INNER JOIN user ON `order`.user_id = user.id ORDER BY `order`.u_date DESC";
                $result = mysqli_query($con, $sql);
                if ($result && mysqli_num_rows($result) > 0) {
                  while ($row = mysqli_fetch_array($result)) {
                    $status_text = $row['status'] ?? 'Pending';
                    switch(strtolower($status_text)) {
                      case 'completed':
                        $status_class = 'status-completed';
                        $type_icon = 'arrow-up-circle-outline';
                        $type_color = 'var(--success-color)';
                        break;
                      case 'pending':
                        $status_class = 'status-pending';
                        $type_icon = 'hourglass-outline';
                        $type_color = 'var(--warning-color)';
                        break;
                      default:
                        $status_class = 'status-pending';
                        $type_icon = 'hourglass-outline';
                        $type_color = 'var(--warning-color)';
                    }
                ?>
                <tr>
                  <td>
                    <div class="transaction-id">#<?php echo str_pad($row['id'], 6, '0', STR_PAD_LEFT); ?></div>
                  </td>
                  <td>
                    <div class="customer-info">
                      <div class="customer-avatar">
                        <?php echo strtoupper(substr($row['u_name'], 0, 2)); ?>
                      </div>
                      <div class="customer-name"><?php echo htmlspecialchars($row['u_name']); ?></div>
                    </div>
                  </td>
                  <td>
                    <div class="tool-name"><?php echo htmlspecialchars($row['u_toolname']); ?></div>
                  </td>
                  <td>
                    <div class="quantity"><?php echo $row['u_itemsnumber']; ?></div>
                  </td>
                  <td>
                    <div class="amount"><?php echo number_format($row['total_price'] ?? $row['u_totalprice']); ?> RWF</div>
                  </td>
                  <td>
                    <div class="transaction-type" style="color: <?php echo $type_color; ?>;">
                      <ion-icon name="<?php echo $type_icon; ?>"></ion-icon>
                      <span><?php echo $status_text == 'Completed' ? 'Income' : 'Pending'; ?></span>
                    </div>
                  </td>
                  <td>
                    <span class="status-badge <?php echo $status_class; ?>">
                      <?php echo ucfirst($status_text); ?>
                    </span>
                  </td>
                  <td>
                    <div class="transaction-date"><?php echo date('M d, Y', strtotime($row['u_date'])); ?></div>
                  </td>
                  <td>
                    <div class="action-buttons">
                      <button class="btn-icon" onclick="viewTransaction(<?php echo $row['id']; ?>)" data-tooltip="View Details">
                        <ion-icon name="eye-outline"></ion-icon>
                      </button>
                    </div>
                  </td>
                </tr>
                <?php
                  }
                } else {
                ?>
                <tr>
                  <td colspan="9" class="empty-state">
                    <ion-icon name="analytics-outline" class="empty-icon"></ion-icon>
                    <div class="empty-title">No transactions found</div>
                    <div class="empty-subtitle">Transactions will appear here once orders are processed.</div>
                  </td>
                </tr>
                <?php
                }
                ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </main>
  </div>

  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
  
  <script>
    function viewTransaction(transactionId) {
      // Add view functionality here
      alert('Transaction details: #' + transactionId);
    }
    
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
