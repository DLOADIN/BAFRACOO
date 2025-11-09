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
  <title>BAFRACOO - Inventory</title>
  <!-- <script src="./JS/file.js"></script> -->
  <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
  <?php error_reporting(0);
  ?>
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
                $pending_count = ($pending_orders && $pending_orders !== false) ? mysqli_num_rows($pending_orders) : 0;
                if($pending_count > 0): ?>
                  <span class="nav-badge"><?php echo $pending_count; ?></span>
                <?php endif; ?>
              </a>
            </li>
            <li class="nav-item">
              <a href="stock.php" class="nav-link active">
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
              <a href="adminprofile.php" class="nav-link">
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
          <h1 class="page-title">Inventory Management</h1>
        </div>
        <div class="header-right">
          <a href="logout.php" class="logout-btn">
            <ion-icon name="log-out-outline"></ion-icon>
            <span>Logout</span>
          </a>
        </div>
      </header>

      <div class="content-area">
        <!-- Inventory Summary Cards -->
        <div class="dashboard-grid" style="margin-bottom: var(--spacing-xl);">
          <div class="dashboard-card">
            <div class="card-content">
              <div class="card-icon" style="background: var(--primary-color);">
                <ion-icon name="cube-outline"></ion-icon>
              </div>
              <div class="card-info">
                <h3 style="margin: 0 0 var(--spacing-sm) 0; color: var(--gray-600); font-size: 0.875rem; font-weight: 500;">TOTAL TOOLS</h3>
                <div style="font-size: 2rem; font-weight: 700; color: var(--gray-900); margin-bottom: var(--spacing-sm);">
                  <?php
                    $total_tools = mysqli_query($con, "SELECT COUNT(*) as count FROM `tool`");
                    echo $total_tools ? mysqli_fetch_assoc($total_tools)['count'] : '0';
                  ?>
                </div>
                <div style="font-size: 0.75rem; color: var(--success-color); font-weight: 500;">
                  <ion-icon name="trending-up-outline" style="margin-right: 4px;"></ion-icon>
                  Active inventory
                </div>
              </div>
            </div>
          </div>

          <div class="dashboard-card">
            <div class="card-content">
              <div class="card-icon" style="background: var(--warning-color);">
                <ion-icon name="alert-circle-outline"></ion-icon>
              </div>
              <div class="card-info">
                <h3 style="margin: 0 0 var(--spacing-sm) 0; color: var(--gray-600); font-size: 0.875rem; font-weight: 500;">LOW STOCK</h3>
                <div style="font-size: 2rem; font-weight: 700; color: var(--gray-900); margin-bottom: var(--spacing-sm);">
                  <?php
                    $low_stock = mysqli_query($con, "SELECT COUNT(*) as count FROM `tool` WHERE t_itemsnumber < 10");
                    echo $low_stock ? mysqli_fetch_assoc($low_stock)['count'] : '0';
                  ?>
                </div>
                <div style="font-size: 0.75rem; color: var(--warning-color); font-weight: 500;">
                  <ion-icon name="warning-outline" style="margin-right: 4px;"></ion-icon>
                  Need restocking
                </div>
              </div>
            </div>
          </div>

          <div class="dashboard-card">
            <div class="card-content">
              <div class="card-icon" style="background: var(--success-color);">
                <ion-icon name="checkmark-circle-outline"></ion-icon>
              </div>
              <div class="card-info">
                <h3 style="margin: 0 0 var(--spacing-sm) 0; color: var(--gray-600); font-size: 0.875rem; font-weight: 500;">IN STOCK</h3>
                <div style="font-size: 2rem; font-weight: 700; color: var(--gray-900); margin-bottom: var(--spacing-sm);">
                  <?php
                    $in_stock = mysqli_query($con, "SELECT COUNT(*) as count FROM `tool` WHERE t_itemsnumber >= 10");
                    echo $in_stock ? mysqli_fetch_assoc($in_stock)['count'] : '0';
                  ?>
                </div>
                <div style="font-size: 0.75rem; color: var(--success-color); font-weight: 500;">
                  <ion-icon name="checkmark-outline" style="margin-right: 4px;"></ion-icon>
                  Well stocked
                </div>
              </div>
            </div>
          </div>

          <div class="dashboard-card">
            <div class="card-content">
              <div class="card-icon" style="background: var(--info-color);">
                <ion-icon name="pricetag-outline"></ion-icon>
              </div>
              <div class="card-info">
                <h3 style="margin: 0 0 var(--spacing-sm) 0; color: var(--gray-600); font-size: 0.875rem; font-weight: 500;">TOTAL VALUE</h3>
                <div style="font-size: 2rem; font-weight: 700; color: var(--gray-900); margin-bottom: var(--spacing-sm);">
                  <?php
                    $total_value = mysqli_query($con, "SELECT SUM(t_price * t_itemsnumber) as total FROM `tool`");
                    $value = $total_value ? mysqli_fetch_assoc($total_value)['total'] ?? 0 : 0;
                    echo number_format($value) . ' RWF';
                  ?>
                </div>
                <div style="font-size: 0.75rem; color: var(--info-color); font-weight: 500;">
                  <ion-icon name="trending-up-outline" style="margin-right: 4px;"></ion-icon>
                  Inventory worth
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Tools Table -->
        <div class="dashboard-card">
          <div class="card-header">
            <h3 style="font-size: 1.25rem; font-weight: 600; color: var(--gray-900); margin: 0;">
              <ion-icon name="cube-outline" style="margin-right: var(--spacing-sm);"></ion-icon>
              All Tools in Inventory
            </h3>
            <div style="display: flex; gap: var(--spacing-md);">
              <button class="btn-secondary" onclick="openDateFilterModal()" style="width:20vh;height:5vh; border-radius:15px;">
                <ion-icon name="filter-outline"></ion-icon>
                Filter by Date
              </button>
              <button class="btn-secondary" onclick="exportStockPDF()" style="width:20vh;height:5vh; border-radius:15px;">
                <ion-icon name="download-outline"></ion-icon>
                Export PDF
              </button>
              <button type="submit" name="submit" class="btn-primary" style="width:20vh;height:5vh; border-radius:15px;">
                <a href="addorder.php" class="btn-primary" style="text-decoration:none; color:black;">
                <ion-icon name="add-outline"></ion-icon>
                Add Tool
              </a>
              </button>
            </div>
          </div>

          <div class="table-container">
            <table class="modern-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Tool Name</th>
                  <th>Type</th>
                  <th>Quantity</th>
                  <th>Unit Price</th>
                  <th>Total Value</th>
                  <th>Description</th>
                  <th>Date Added</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php
                // Build SQL query with optional date filtering
                $sql = "SELECT * FROM `tool`";
                
                // Add date filter if provided
                if(isset($_GET['start_date']) && isset($_GET['end_date'])){
                  $start_date = mysqli_real_escape_string($con, $_GET['start_date']);
                  $end_date = mysqli_real_escape_string($con, $_GET['end_date']);
                  $sql .= " WHERE DATE(u_date) BETWEEN '$start_date' AND '$end_date'";
                }
                
                $sql .= " ORDER BY u_date DESC";
                $result = mysqli_query($con, $sql);
                if ($result && mysqli_num_rows($result) > 0) {
                  while ($row = mysqli_fetch_array($result)) {
                    $status_class = '';
                    $status_text = '';
                    if ($row['u_itemsnumber'] <= 0) {
                      $status_class = 'status-out-of-stock';
                      $status_text = 'Out of Stock';
                    } elseif ($row['u_itemsnumber'] < 10) {
                      $status_class = 'status-low-stock';
                      $status_text = 'Low Stock';
                    } else {
                      $status_class = 'status-in-stock';
                      $status_text = 'In Stock';
                    }
                ?>
                <tr>
                  <td><?php echo $row['id']; ?></td>
                  <td>
                    <div style="display: flex; align-items: center; gap: var(--spacing-sm);">
                      <div style="width: 40px; height: 40px; border-radius: var(--radius-md); background: var(--primary-light); display: flex; align-items: center; justify-content: center; color: var(--primary-color); font-weight: 600;">
                        <ion-icon name="construct-outline"></ion-icon>
                      </div>
                      <div>
                        <div style="font-weight: 500;"><?php echo htmlspecialchars($row['u_toolname']); ?></div>
                        <div style="font-size: 0.75rem; color: var(--gray-500);">ID: <?php echo $row['id']; ?></div>
                      </div>
                    </div>
                  </td>
                  <td><?php echo htmlspecialchars($row['u_type']); ?></td>
                  <td>
                    <span style="font-weight: 600; color: <?php echo $row['u_itemsnumber'] < 10 ? 'var(--warning-color)' : 'var(--success-color)'; ?>;">
                      <?php echo $row['u_itemsnumber']; ?>
                    </span>
                  </td>
                  <td><?php echo number_format($row['u_price']); ?> RWF</td>
                  <td style="font-weight: 600;"><?php echo number_format($row['u_price'] * $row['u_itemsnumber']); ?> RWF</td>
                  <td><?php echo htmlspecialchars(substr($row['u_tooldescription'], 0, 50)) . '...'; ?></td>
                  <td><?php echo date('M d, Y', strtotime($row['u_date'])); ?></td>
                  <td>
                    <span class="status-badge <?php echo $status_class; ?>">
                      <?php echo $status_text; ?>
                    </span>
                  </td>
                  <td>
                    <div class="action-buttons">
                      <button class="btn-icon btn-edit" onclick="editTool(<?php echo $row['id']; ?>)">
                        <ion-icon name="create-outline"></ion-icon>
                      </button>
                      <button class="btn-icon btn-delete" onclick="confirmDelete(<?php echo $row['id']; ?>)">
                        <ion-icon name="trash-outline"></ion-icon>
                      </button>
                    </div>
                  </td>
                </tr>
                <?php
                  }
                } else {
                ?>
                <tr>
                  <td colspan="10" style="text-align: center; padding: var(--spacing-xl); color: var(--gray-600);">
                    <ion-icon name="cube-outline" style="font-size: 3rem; margin-bottom: var(--spacing-md);"></ion-icon>
                    <div>No tools found in inventory. <a href="addorder.php" style="color: var(--primary-color); text-decoration: none;">Add your first tool</a></div>
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
    function confirmDelete(toolId) {
      if (confirm('Are you really sure you want to delete this tool?')) {
        window.location.href = './delete/deletestock.php?id=' + toolId;
      }
    }
    
    function editTool(toolId) {
      window.location.href = 'addorder.php?id=' + toolId;
    }
    // Initialize dashboard
    document.addEventListener('DOMContentLoaded', function() {
      if (typeof window.Dashboard !== 'undefined') {
        new window.Dashboard();
      }
    });
  </script>
</body>
</html>

<?php
// Handle form submission for updating tools
if(isset($_POST['submit']) && isset($_GET['id'])){
  $id = mysqli_real_escape_string($con, $_GET['id']);
  $toolname = mysqli_real_escape_string($con, $_POST['u_toolname']);
  $nitems = mysqli_real_escape_string($con, $_POST['u_itemsnumber']);
  $type = mysqli_real_escape_string($con, $_POST['u_type']);
  $tooldescription = mysqli_real_escape_string($con, $_POST['u_tooldescription']);
  $date = date('Y-m-d',strtotime($_POST['u_date']));
  $price = mysqli_real_escape_string($con, $_POST['u_price']);
  
  $sql = mysqli_query($con, "UPDATE tool SET u_toolname='$toolname', u_itemsnumber='$nitems', u_type='$type', u_tooldescription='$tooldescription', u_date='$date', u_price='$price' WHERE id='$id'");
  
  if($sql){
    header('location:stock.php');
    exit();
  }
}
?>
