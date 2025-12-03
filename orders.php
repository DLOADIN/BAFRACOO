<?php
  require "connection.php";
  if(!empty($_SESSION["id"])){
  $id = $_SESSION["id"];
  $check = mysqli_query($con,"SELECT * FROM `admin` WHERE id=$id ");
  $row = mysqli_fetch_array($check);
  }
  else{
  header('location:loginadmin.php');
  exit();
  } 
  error_reporting(0);
  $current_page = 'orders';
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
  <title>BAFRACOO - Orders</title>
  <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
  <!-- <script src="./JS/file.js"></script> -->
</head>
<body>
  <div class="dashboard-container">
    <!-- Sidebar -->
    <?php include 'includes/admin_sidebar.php'; ?>

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
          <h1 class="page-title">Orders Management</h1>
        </div>
        <div class="header-right">
          <a href="logout.php" class="logout-btn">
            <ion-icon name="log-out-outline"></ion-icon>
            <span>Logout</span>
          </a>
        </div>
      </header>

      <div class="content-area">
        <!-- Orders Summary Cards -->
        <div class="dashboard-grid" style="margin-bottom: var(--spacing-xl);">
          <div class="dashboard-card">
            <div class="card-content">
              <div class="card-icon" style="background: var(--primary-color);">
                <ion-icon name="bag-handle-outline"></ion-icon>
              </div>
              <div class="card-info">
                <h3 style="margin: 0 0 var(--spacing-sm) 0; color: var(--gray-600); font-size: 0.875rem; font-weight: 500;">TOTAL ORDERS</h3>
                <div style="font-size: 2rem; font-weight: 700; color: var(--gray-900); margin-bottom: var(--spacing-sm);">
                  <?php
                    $total_orders = mysqli_query($con, "SELECT COUNT(*) as count FROM `order`");
                    echo $total_orders ? mysqli_fetch_assoc($total_orders)['count'] : '0';
                  ?>
                </div>
                <div style="font-size: 0.75rem; color: var(--success-color); font-weight: 500;">
                  <ion-icon name="trending-up-outline" style="margin-right: 4px;"></ion-icon>
                  +12% from last month
                </div>
              </div>
            </div>
          </div>

          <div class="dashboard-card">
            <div class="card-content">
              <div class="card-icon" style="background: var(--warning-color);">
                <ion-icon name="time-outline"></ion-icon>
              </div>
              <div class="card-info">
                <h3 style="margin: 0 0 var(--spacing-sm) 0; color: var(--gray-600); font-size: 0.875rem; font-weight: 500;">PENDING ORDERS</h3>
                <div style="font-size: 2rem; font-weight: 700; color: var(--gray-900); margin-bottom: var(--spacing-sm);">
                  <?php
                    $pending_orders = mysqli_query($con, "SELECT COUNT(*) as count FROM `order` WHERE status='Pending'");
                    echo $pending_orders ? mysqli_fetch_assoc($pending_orders)['count'] : '0';
                  ?>
                </div>
                <div style="font-size: 0.75rem; color: var(--warning-color); font-weight: 500;">
                  <ion-icon name="alert-circle-outline" style="margin-right: 4px;"></ion-icon>
                  Needs attention
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
                <h3 style="margin: 0 0 var(--spacing-sm) 0; color: var(--gray-600); font-size: 0.875rem; font-weight: 500;">COMPLETED ORDERS</h3>
                <div style="font-size: 2rem; font-weight: 700; color: var(--gray-900); margin-bottom: var(--spacing-sm);">
                  <?php
                    $completed_orders = mysqli_query($con, "SELECT COUNT(*) as count FROM `order` WHERE status='Completed'");
                    echo $completed_orders ? mysqli_fetch_assoc($completed_orders)['count'] : '0';
                  ?>
                </div>
                <div style="font-size: 0.75rem; color: var(--success-color); font-weight: 500;">
                  <ion-icon name="trending-up-outline" style="margin-right: 4px;"></ion-icon>
                  +8% this week
                </div>
              </div>
            </div>
          </div>

          <div class="dashboard-card">
            <div class="card-content">
              <div class="card-icon" style="background: var(--info-color);">
                <ion-icon name="cash-outline"></ion-icon>
              </div>
              <div class="card-info">
                <h3 style="margin: 0 0 var(--spacing-sm) 0; color: var(--gray-600); font-size: 0.875rem; font-weight: 500;">TOTAL REVENUE</h3>
                <div style="font-size: 2rem; font-weight: 700; color: var(--gray-900); margin-bottom: var(--spacing-sm);">
                  <?php
                    $total_revenue = mysqli_query($con, "SELECT SUM(total_price) as total FROM `order` WHERE status='Completed'");
                    $revenue = $total_revenue ? mysqli_fetch_assoc($total_revenue)['total'] ?? 0 : 0;
                    echo number_format($revenue) . ' RWF';
                  ?>
                </div>
                <div style="font-size: 0.75rem; color: var(--success-color); font-weight: 500;">
                  <ion-icon name="trending-up-outline" style="margin-right: 4px;"></ion-icon>
                  +15% this month
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Orders Table -->
        <div class="dashboard-card">
          <div class="card-header">
            <h3 style="font-size: 1.25rem; font-weight: 600; color: var(--gray-900); margin: 0;">
              <ion-icon name="list-outline" style="margin-right: var(--spacing-sm);"></ion-icon>
              All Orders
            </h3>
            <div style="display: flex; gap: var(--spacing-md);">
              <button class="btn-secondary" onclick="openDateFilterModal()" style="width:20vh;height:5vh; border-radius:15px;">
                <ion-icon name="filter-outline"></ion-icon>
                Filter by Date
              </button>
              <button class="btn-secondary" onclick="exportOrdersPDF()" style="width:20vh;height:5vh; border-radius:15px;">
                <ion-icon name="download-outline"></ion-icon>
                Export PDF
              </button>
              <button type="submit" name="submit" class="btn-primary" style="width:20vh;height:5vh; border-radius:15px;">
                <a href="addtool.php" class="btn-primary" style="text-decoration:none; color:black;">
                <ion-icon name="add-outline"></ion-icon>
                Add Order
              </a>
            </div>
          </div>

          <div class="table-container">
            <table class="modern-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Customer</th>
                  <th>Tool Name</th>
                  <th>Type</th>
                  <th>Quantity</th>
                  <th>Description</th>
                  <th>Unit Price</th>
                  <th>Total Price</th>
                  <th>Date</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
            <?php
             // Build SQL query with optional date filtering
             $sql = "SELECT `order`.*, user.u_name FROM `order` INNER JOIN user ON `order`.user_id = user.id";
             
             // Add date filter if provided
             if(isset($_GET['start_date']) && isset($_GET['end_date'])){
               $start_date = mysqli_real_escape_string($con, $_GET['start_date']);
               $end_date = mysqli_real_escape_string($con, $_GET['end_date']);
               $sql .= " WHERE DATE(`order`.date) BETWEEN '$start_date' AND '$end_date'";
             }
             
             $sql .= " ORDER BY `order`.id DESC";
             $result = mysqli_query($con, $sql);
                if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_array($result)) {
                  $status_class = '';
                  $status_text = $row['status'] ?? 'Pending';
                  switch(strtolower($status_text)) {
                    case 'completed':
                      $status_class = 'status-completed';
                      break;
                    case 'pending':
                      $status_class = 'status-pending';
                      break;
                    case 'processing':
                      $status_class = 'status-processing';
                      break;
                    default:
                      $status_class = 'status-pending';
                  }
            ?>
                <tr>
                  <td><?php echo $row['id']; ?></td>
                  <td>
                    <div style="display: flex; align-items: center; gap: var(--spacing-sm);">
                      <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--primary-color); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 0.75rem;">
                        <?php echo strtoupper(substr($row['u_name'], 0, 2)); ?>
                      </div>
                      <div>
                        <div style="font-weight: 500;"><?php echo htmlspecialchars($row['u_name']); ?></div>
                      </div>
                    </div>
                  </td>
                  <td><?php echo htmlspecialchars($row['u_toolname']); ?></td>
                  <td><?php echo htmlspecialchars($row['u_type']); ?></td>
                  <td><?php echo $row['u_itemsnumber']; ?></td>
                  <td><?php echo htmlspecialchars($row['u_tooldescription']); ?></td>
                  <td><?php echo number_format($row['u_price']); ?> RWF</td>
                  <td style="font-weight: 600;"><?php echo number_format($row['total_price'] ?? $row['u_totalprice']); ?> RWF</td>
                  <td><?php echo date('M d, Y', strtotime($row['u_date'])); ?></td>
                  <td>
                    <span class="status-badge <?php echo $status_class; ?>">
                      <?php echo ucfirst($status_text); ?>
                    </span>
                  </td>
                  <td>
                    <div class="action-buttons">
                      <button class="btn-icon btn-edit" onclick="editOrder(<?php echo $row['id']; ?>)">
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
                  <td colspan="11" style="text-align: center; padding: var(--spacing-xl); color: var(--gray-600);">
                    <ion-icon name="document-outline" style="font-size: 3rem; margin-bottom: var(--spacing-md);"></ion-icon>
                    <div>No orders found yet. Orders will appear here once customers start placing them.</div>
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
  
  <!-- Date Filter Modal -->
  <div id="dateFilterModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">
          <ion-icon name="calendar-outline"></ion-icon>
          Filter by Date Range
        </h3>
        <button class="modal-close" onclick="closeDateFilterModal()">&times;</button>
      </div>
      <div class="modal-body">
        <form method="GET" action="">
          <div style="display: grid; gap: var(--spacing-md);">
            <div class="form-group">
              <label for="start_date" style="display: block; margin-bottom: var(--spacing-sm); font-weight: 500;">Start Date</label>
              <input type="date" id="start_date" name="start_date" class="form-control" required style="width: 100%; padding: var(--spacing-sm); border: 1px solid var(--gray-300); border-radius: var(--radius-md);">
            </div>
            <div class="form-group">
              <label for="end_date" style="display: block; margin-bottom: var(--spacing-sm); font-weight: 500;">End Date</label>
              <input type="date" id="end_date" name="end_date" class="form-control" required style="width: 100%; padding: var(--spacing-sm); border: 1px solid var(--gray-300); border-radius: var(--radius-md);">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="closeDateFilterModal()">Cancel</button>
            <button type="submit" class="btn-primary">Apply Filter</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    function confirmDelete(orderId) {
      if (confirm('Are you really sure you want to delete this order?')) {
        window.location.href = './delete/deleteorder.php?id=' + orderId;
      }
    }
    
    function editOrder(orderId) {
      window.location.href = 'addtool.php?id=' + orderId;
    }
    function openDateFilterModal() {
      document.getElementById('dateFilterModal').classList.add('active');
    }
    
    function closeDateFilterModal() {
      document.getElementById('dateFilterModal').classList.remove('active');
    }
    
    function exportOrdersPDF() {
      const urlParams = new URLSearchParams(window.location.search);
      const startDate = urlParams.get('start_date');
      const endDate = urlParams.get('end_date');
      
      let exportUrl = 'export_pdf.php?type=orders';
      if (startDate && endDate) {
        exportUrl += '&start_date=' + startDate + '&end_date=' + endDate;
      }
      
      window.open(exportUrl, '_blank');
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
      const modal = document.getElementById('dateFilterModal');
      if (event.target == modal) {
        closeDateFilterModal();
      }
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
