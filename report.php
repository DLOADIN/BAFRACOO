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
  <title>BAFRACOO - Reports</title>
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
                <span class="nav-text">Add Tool</span>
              </a>
            </li>
            <li class="nav-item">
              <a href="orders.php" class="nav-link">
                <ion-icon name="bag-handle-outline" class="nav-icon"></ion-icon>
                <span class="nav-text">Orders</span>
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
              <a href="report.php" class="nav-link active">
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
          <h1 class="page-title">Reports & Analytics</h1>
        </div>
        <div class="header-right">
          <a href="logout.php" class="logout-btn">
            <ion-icon name="log-out-outline"></ion-icon>
            <span>Logout</span>
          </a>
        </div>
      </header>

      <div class="content-area">
        <!-- Entry/Stock Data Table -->
        <div class="table-container">
          <div class="table-header">
            <h2 class="table-title">
              <ion-icon name="document-text-outline" style="margin-right: var(--spacing-sm);"></ion-icon>
              Entry/Stock Data Report
            </h2>
            <div class="table-actions">
              <button class="btn btn-primary btn-sm" style="width:20vh;height:5vh; border-radius:15px;">
                <ion-icon name="download-outline"></ion-icon>
                Export
              </button>
              <button class="btn btn-secondary btn-sm" style="width:20vh;height:5vh; border-radius:15px;">
                <ion-icon name="print-outline"></ion-icon>
                Print
              </button>
            </div>
          </div>
          
          <div class="table-responsive">
            <table class="modern-table">
              <thead>
                <tr>
                  <th class="sortable">#</th>
                  <th class="sortable">Customer Name</th>
                  <th class="sortable">Tool/Product</th>
                  <th class="sortable">Quantity</th>
                  <th class="sortable">Amount</th>
                  <th class="sortable">Status</th>
                  <th class="sortable">Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $sql = mysqli_query($con, "SELECT o.*, u.u_name FROM `order` o LEFT JOIN `user` u ON o.user_id = u.id ORDER BY o.u_totalprice DESC");
                if($sql && mysqli_num_rows($sql) > 0) {
                  while($data = mysqli_fetch_array($sql)) { 
                ?>
                <tr>
                  <td data-label="#"><?php echo htmlspecialchars($data['id']); ?></td>
                  <td data-label="Customer Name">
                    <div class="cell-avatar">
                      <div class="avatar-placeholder">
                        <?php echo strtoupper(substr($data['u_name'] ?? 'N', 0, 2)); ?>
                      </div>
                      <div>
                        <div style="font-weight: 500;"><?php echo htmlspecialchars($data['u_name'] ?? 'N/A'); ?></div>
                      </div>
                    </div>
                  </td>
                  <td data-label="Tool/Product"><?php echo htmlspecialchars($data['u_toolname'] ?? 'N/A'); ?></td>
                  <td data-label="Quantity" class="cell-number"><?php echo number_format($data['u_itemsnumber'] ?? 0); ?></td>
                  <td data-label="Amount" class="cell-currency"><?php echo number_format($data['u_totalprice'] ?? 0); ?> RWF</td>
                  <td data-label="Status">
                    <span class="cell-status status-completed">Completed</span>
                  </td>
                  <td data-label="Date" class="cell-date">
                    <?php echo date('M d, Y', strtotime($data['u_date'] ?? 'now')); ?>
                  </td>
                  <td data-label="Actions">
                    <div class="cell-actions">
                      <button class="cell-action action-edit" title="Edit Order" onclick="openEditModal(<?php echo $data['id']; ?>, '<?php echo addslashes($data['u_toolname']); ?>', <?php echo $data['u_itemsnumber']; ?>, <?php echo $data['u_price']; ?>, '<?php echo addslashes($data['u_tooldescription']); ?>')">
                        <ion-icon name="create-outline"></ion-icon>
                      </button>
                      <button class="cell-action action-delete" title="Delete Order" onclick="confirmDelete(<?php echo $data['id']; ?>)">
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
                  <td colspan="8">
                    <div class="table-empty">
                      <div class="table-empty-icon">
                        <ion-icon name="document-outline"></ion-icon>
                      </div>
                      <h3 class="table-empty-title">No Data Found</h3>
                      <p class="table-empty-text">
                        No entry/stock data available at the moment. Data will appear here once entries are made.
                      </p>
                      <a href="addtool.php" class="btn btn-primary" style="margin-top: var(--spacing-lg);">
                        <ion-icon name="add-outline"></ion-icon>
                        Add New Entry
                      </a>
                    </div>
                  </td>
                </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
          
          <div class="table-pagination">
            <div class="pagination-info">
              <?php
              $total_records = mysqli_query($con, "SELECT COUNT(*) as total FROM productentry");
              $total = $total_records ? mysqli_fetch_assoc($total_records)['total'] : 0;
              echo "Showing $total entries";
              ?>
            </div>
            <div class="pagination-controls">
              <a href="#" class="pagination-btn disabled">
                <ion-icon name="chevron-back-outline"></ion-icon>
              </a>
              <a href="#" class="pagination-btn active">1</a>
              <a href="#" class="pagination-btn disabled">
                <ion-icon name="chevron-forward-outline"></ion-icon>
              </a>
            </div>
          </div>
        </div>

        <!-- Summary Statistics -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: var(--spacing-xl); margin-top: var(--spacing-xl);">
          <div class="dashboard-card">
            <div class="card-header">
              <h3 class="card-title">Total Entries</h3>
              <div class="card-icon primary">
                <i class="fas fa-list-alt"></i>
              </div>
            </div>
            <div class="card-value">
              <?php
              $total_entries = mysqli_query($con, "SELECT COUNT(*) as total FROM productentry");
              echo $total_entries ? number_format(mysqli_fetch_assoc($total_entries)['total']) : '0';
              ?>
            </div>
            <div class="card-change positive">
              <ion-icon name="trending-up-outline"></ion-icon>
              <span>Total recorded entries</span>
            </div>
          </div>

          <div class="dashboard-card">
            <div class="card-header">
              <h3 class="card-title">Total Value</h3>
              <div class="card-icon success">
                <i class="fas fa-dollar-sign"></i>
              </div>
            </div>
            <div class="card-value">
              <?php
              $total_value = mysqli_query($con, "SELECT SUM(p_amount) as total FROM productentry");
              $value = $total_value ? mysqli_fetch_assoc($total_value)['total'] : 0;
              echo number_format($value);
              ?> RWF
            </div>
            <div class="card-change positive">
              <ion-icon name="cash-outline"></ion-icon>
              <span>Cumulative value</span>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
  <script src="./JS/file.js"></script>
  
  <script>
    function confirmDelete(id) {
      if (confirm('Are you sure you want to delete this entry?')) {
        // Add delete functionality here
        window.location.href = 'delete/deleteentry.php?id=' + id;
      }
    }

    // Table sorting functionality
    document.querySelectorAll('.sortable').forEach(header => {
      header.addEventListener('click', function() {
        // Add sorting logic here
        console.log('Sorting by:', this.textContent);
      });
    });
  </script>
</body>
</html>
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
          <a href="report.php">
            <ion-icon name="bar-chart-outline"></ion-icon>
            <span>REPORTS</span>
          </a>
        </li>
        <li>
          <a href="adminprofile.php">
            <ion-icon name="person-circle-outline"></ion-icon>
            <span>PROFILE</span>
          </a>
        </li>
        <li>
          <a href="website.php">
            <ion-icon name="planet-outline"></ion-icon>
            <span>HOME SITE</span>
          </a>
        </li>
    </ul>
  </div>

<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>
