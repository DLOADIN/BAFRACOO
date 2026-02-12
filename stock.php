<?php
  require "connection.php";
  require "EnhancedInventoryManager.php";
  
  if(!empty($_SESSION["id"])){
  $id = $_SESSION["id"];
  $check = mysqli_query($con,"SELECT * FROM `admin` WHERE id=$id ");
  $row = mysqli_fetch_array($check);
  }
  else{
  header('location:loginadmin.php');
  exit();
  }
  
  // Initialize Inventory Manager
  $inventoryManager = new EnhancedInventoryManager($con);
  
  // Handle AJAX request to update inventory method
  if(isset($_POST['action']) && $_POST['action'] == 'update_method') {
    header('Content-Type: application/json');
    $tool_id = (int)$_POST['tool_id'];
    $method = mysqli_real_escape_string($con, $_POST['method']);
    
    if($inventoryManager->setInventoryMethod($tool_id, $method)) {
      echo json_encode(['success' => true, 'message' => 'Inventory method updated to ' . $method]);
    } else {
      echo json_encode(['success' => false, 'message' => 'Failed to update inventory method']);
    }
    exit();
  }
  
  // Get inventory method statistics
  $methodStats = $inventoryManager->getInventoryMethodStats();
  
  $current_page = 'stock';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="./CSS/modern-dashboard.css">
  <link rel="stylesheet" href="./CSS/modern-tables.css">
  <link rel="stylesheet" href="./CSS/enhanced-pages.css">
  <link rel="shortcut icon" href="./images/Capture.JPG" type="image/x-icon">
  <script src="https://kit.fontawesome.com/14ff3ea278.js" crossorigin="anonymous"></script>
  <title>BAFRACOO - Inventory</title>
  <!-- <script src="./JS/file.js"></script> -->
  <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
  <?php error_reporting(0);
  ?>
  <style>
    .method-toggle {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 4px;
      background: var(--gray-100);
      border-radius: 8px;
    }
    .method-btn {
      padding: 6px 12px;
      border: none;
      border-radius: 6px;
      font-size: 0.75rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      background: transparent;
      color: var(--gray-600);
    }
    .method-btn.active {
      background: var(--primary-color);
      color: white;
      box-shadow: 0 2px 4px rgba(59, 130, 246, 0.3);
    }
    .method-btn.fifo.active {
      background: #3b82f6;
    }
    .method-btn.lifo.active {
      background: #8b5cf6;
    }
    .method-btn:hover:not(.active) {
      background: var(--gray-200);
    }
    .batch-info {
      font-size: 0.7rem;
      color: var(--gray-500);
      margin-top: 4px;
    }
    .batch-indicator {
      display: inline-block;
      width: 8px;
      height: 8px;
      border-radius: 50%;
      margin-right: 4px;
    }
    .batch-indicator.fifo { background: #3b82f6; }
    .batch-indicator.lifo { background: #8b5cf6; }
  </style>
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
        </div>
        <div class="header-right">
          <a href="logout.php" class="logout-btn">
            <ion-icon name="log-out-outline"></ion-icon>
            <span>Logout</span>
          </a>
        </div>
      </header>
      
      <!-- Page Banner -->
      <div class="page-banner">
        <h1 class="page-banner-title">
          <ion-icon name="cube-outline"></ion-icon>
          Inventory Management
        </h1>
        <p class="page-banner-subtitle">Manage your stock levels, products, and inventory methods</p>
      </div>

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
                    $low_stock = mysqli_query($con, "SELECT COUNT(*) as count FROM `tool` WHERE u_itemsnumber < 10");
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
                    $in_stock = mysqli_query($con, "SELECT COUNT(*) as count FROM `tool` WHERE u_itemsnumber >= 10");
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
                    $total_value = mysqli_query($con, "SELECT SUM(u_price * u_itemsnumber) as total FROM `tool`");
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

        <!-- FIFO/LIFO Summary Cards -->
        <div class="dashboard-grid" style="margin-bottom: var(--spacing-xl); grid-template-columns: repeat(2, 1fr);">
          <div class="dashboard-card">
            <div class="card-content">
              <div class="card-icon" style="background: #3b82f6;">
                <ion-icon name="arrow-forward-outline"></ion-icon>
              </div>
              <div class="card-info">
                <h3 style="margin: 0 0 var(--spacing-sm) 0; color: var(--gray-600); font-size: 0.875rem; font-weight: 500;">FIFO PRODUCTS</h3>
                <div style="font-size: 2rem; font-weight: 700; color: var(--gray-900); margin-bottom: var(--spacing-sm);">
                  <?php echo $methodStats['fifo_count'] ?? 0; ?>
                </div>
                <div style="font-size: 0.75rem; color: #3b82f6; font-weight: 500;">
                  <ion-icon name="time-outline" style="margin-right: 4px;"></ion-icon>
                  First In, First Out - Oldest sold first
                </div>
              </div>
            </div>
          </div>

          <div class="dashboard-card">
            <div class="card-content">
              <div class="card-icon" style="background: #8b5cf6;">
                <ion-icon name="arrow-back-outline"></ion-icon>
              </div>
              <div class="card-info">
                <h3 style="margin: 0 0 var(--spacing-sm) 0; color: var(--gray-600); font-size: 0.875rem; font-weight: 500;">LIFO PRODUCTS</h3>
                <div style="font-size: 2rem; font-weight: 700; color: var(--gray-900); margin-bottom: var(--spacing-sm);">
                  <?php echo $methodStats['lifo_count'] ?? 0; ?>
                </div>
                <div style="font-size: 0.75rem; color: #8b5cf6; font-weight: 500;">
                  <ion-icon name="time-outline" style="margin-right: 4px;"></ion-icon>
                  Last In, First Out - Newest sold first
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
                  <th>Inventory Method</th>
                  <th>Date Added</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php
                // Build SQL query with optional date filtering
                $sql = "SELECT t.*, COALESCE(im.method, 'FIFO') as inventory_method 
                        FROM `tool` t 
                        LEFT JOIN `inventory_method` im ON t.id = im.tool_id";
                
                // Add date filter if provided
                if(isset($_GET['start_date']) && isset($_GET['end_date'])){
                  $start_date = mysqli_real_escape_string($con, $_GET['start_date']);
                  $end_date = mysqli_real_escape_string($con, $_GET['end_date']);
                  $sql .= " WHERE DATE(t.u_date) BETWEEN '$start_date' AND '$end_date'";
                }
                
                $sql .= " ORDER BY t.u_date DESC";
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
                    
                    // Get current inventory method
                    $current_method = $row['inventory_method'];
                ?>
                <tr>
                  <td><?php echo $row['id']; ?></td>
                  <td>
                    <div style="display: flex; align-items: center; gap: var(--spacing-sm);">
                      <?php if(!empty($row['image_url']) && file_exists($row['image_url'])): ?>
                      <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="<?php echo htmlspecialchars($row['u_toolname']); ?>" style="width: 50px; height: 50px; border-radius: var(--radius-md); object-fit: cover; border: 1px solid var(--gray-200);">
                      <?php else: ?>
                      <div style="width: 50px; height: 50px; border-radius: var(--radius-md); background: var(--primary-light); display: flex; align-items: center; justify-content: center; color: var(--primary-color); font-weight: 600;">
                        <ion-icon name="construct-outline" style="font-size: 1.5rem;"></ion-icon>
                      </div>
                      <?php endif; ?>
                      <div>
                        <div style="font-weight: 500;"><?php echo htmlspecialchars($row['u_toolname']); ?></div>
                        <div style="font-size: 0.75rem; color: var(--gray-500);">ID: <?php echo $row['id']; ?></div>
                      </div>
                    </div>
                  </td>
                  <td><?php echo htmlspecialchars($row['u_type']); ?></td>
                  <td>
                    <span style="font-weight: 600; color: <?php echo $row['u_itemsnumber'] < 10 ? 'var(--warning-color)' : 'var(--success-color)'; ?>;">
                      <?php echo number_format($row['u_itemsnumber']); ?>
                    </span>
                  </td>
                  <td><?php echo number_format($row['u_price']); ?> RWF</td>
                  <td style="font-weight: 600;"><?php echo number_format($row['u_price'] * $row['u_itemsnumber']); ?> RWF</td>
                  <td>
                    <div class="method-toggle" data-tool-id="<?php echo $row['id']; ?>">
                      <button class="method-btn fifo <?php echo $current_method === 'FIFO' ? 'active' : ''; ?>" 
                              onclick="updateInventoryMethod(<?php echo $row['id']; ?>, 'FIFO', this)"
                              title="First In, First Out - Oldest stock sold first">
                        FIFO
                      </button>
                      <button class="method-btn lifo <?php echo $current_method === 'LIFO' ? 'active' : ''; ?>"
                              onclick="updateInventoryMethod(<?php echo $row['id']; ?>, 'LIFO', this)"
                              title="Last In, First Out - Newest stock sold first">
                        LIFO
                      </button>
                    </div>
                    <?php 
                    // Get batch count for this tool
                    $batch_count_query = mysqli_query($con, "SELECT COUNT(*) as cnt FROM stock_batches WHERE tool_id = " . $row['id'] . " AND quantity_remaining > 0");
                    $batch_count = mysqli_fetch_assoc($batch_count_query)['cnt'] ?? 0;
                    ?>
                    <div class="batch-info" style="display: flex; align-items: center; gap: 4px;">
                      <span class="batch-indicator <?php echo strtolower($current_method); ?>"></span>
                      <?php echo $current_method === 'FIFO' ? 'Oldest first' : 'Newest first'; ?>
                      <?php if($batch_count > 0): ?>
                      <span style="margin-left: 4px; padding: 2px 6px; background: #e0f2fe; color: #0369a1; border-radius: 10px; font-size: 0.65rem; font-weight: 600;">
                        <?php echo $batch_count; ?> batch<?php echo $batch_count > 1 ? 'es' : ''; ?>
                      </span>
                      <?php endif; ?>
                    </div>
                  </td>
                  <td><?php echo date('M d, Y', strtotime($row['u_date'])); ?></td>
                  <td>
                    <span class="status-badge <?php echo $status_class; ?>">
                      <?php echo $status_text; ?>
                    </span>
                  </td>
                  <td>
                    <div class="action-buttons">
                      <button class="btn-icon" onclick="showBatchDetails(<?php echo $row['id']; ?>, '<?php echo addslashes($row['u_toolname']); ?>')" title="View All Batches" style="background: linear-gradient(135deg, #3b82f6, #1e40af); color: white;">
                        <ion-icon name="layers-outline"></ion-icon>
                      </button>
                      <button class="btn-icon btn-edit" onclick="editTool(<?php echo $row['id']; ?>)" title="Edit Tool">
                        <ion-icon name="create-outline"></ion-icon>
                      </button>
                      <button class="btn-icon btn-delete" onclick="confirmDelete(<?php echo $row['id']; ?>)" title="Delete Tool">
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
                  <td colspan="9" style="text-align: center; padding: var(--spacing-xl); color: var(--gray-600);">
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
    
    // Update inventory method via AJAX
    function updateInventoryMethod(toolId, method, btn) {
      const toggle = btn.closest('.method-toggle');
      const buttons = toggle.querySelectorAll('.method-btn');
      const batchInfo = toggle.nextElementSibling;
      
      // Disable buttons during update
      buttons.forEach(b => b.disabled = true);
      
      fetch('stock.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update_method&tool_id=${toolId}&method=${method}`
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Update button states
          buttons.forEach(b => {
            b.classList.remove('active');
            if ((b.classList.contains('fifo') && method === 'FIFO') ||
                (b.classList.contains('lifo') && method === 'LIFO')) {
              b.classList.add('active');
            }
          });
          
          // Update batch info text
          const indicator = batchInfo.querySelector('.batch-indicator');
          indicator.className = 'batch-indicator ' + method.toLowerCase();
          batchInfo.innerHTML = `<span class="batch-indicator ${method.toLowerCase()}"></span>${method === 'FIFO' ? 'Oldest first' : 'Newest first'}`;
          
          // Show success feedback
          showToast('Inventory method updated to ' + method, 'success');
        } else {
          showToast('Failed to update: ' + data.message, 'error');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showToast('Error updating inventory method', 'error');
      })
      .finally(() => {
        buttons.forEach(b => b.disabled = false);
      });
    }
    
    // Show batch details modal
    function showBatchDetails(toolId, toolName) {
      // Create modal if it doesn't exist
      let modal = document.getElementById('batchModal');
      if (!modal) {
        modal = document.createElement('div');
        modal.id = 'batchModal';
        modal.innerHTML = `
          <div style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9998; display: flex; align-items: center; justify-content: center;">
            <div style="background: white; border-radius: 12px; width: 90%; max-width: 900px; max-height: 85vh; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
              <div style="padding: 20px; background: linear-gradient(135deg, #3b82f6, #1e40af); color: white; display: flex; justify-content: space-between; align-items: center;">
                <h3 id="batchModalTitle" style="margin: 0; font-size: 1.25rem;"></h3>
                <button onclick="closeBatchModal()" style="background: rgba(255,255,255,0.2); border: none; color: white; width: 36px; height: 36px; border-radius: 50%; cursor: pointer; font-size: 1.25rem; display: flex; align-items: center; justify-content: center;">
                  <ion-icon name="close"></ion-icon>
                </button>
              </div>
              <div id="batchModalContent" style="padding: 20px; overflow-y: auto; max-height: calc(85vh - 70px);">
                <div style="text-align: center; padding: 40px;">
                  <ion-icon name="hourglass-outline" style="font-size: 3rem; color: #3b82f6; animation: spin 1s linear infinite;"></ion-icon>
                  <p style="margin-top: 16px; color: #6b7280;">Loading batch details...</p>
                </div>
              </div>
            </div>
          </div>
        `;
        document.body.appendChild(modal);
      }
      
      document.getElementById('batchModalTitle').innerHTML = '<ion-icon name="layers-outline" style="margin-right: 8px;"></ion-icon> All Batches for: ' + toolName;
      modal.style.display = 'block';
      
      // Fetch batch details
      fetch('get-batch-details.php?tool_id=' + toolId)
        .then(response => response.text())
        .then(data => {
          document.getElementById('batchModalContent').innerHTML = data;
        })
        .catch(error => {
          document.getElementById('batchModalContent').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading batch details</div>';
        });
    }
    
    function closeBatchModal() {
      const modal = document.getElementById('batchModal');
      if (modal) modal.style.display = 'none';
    }
    
    // Toast notification function
    function showToast(message, type = 'info') {
      const toast = document.createElement('div');
      toast.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        padding: 12px 24px;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
        color: white;
        border-radius: 8px;
        font-weight: 500;
        z-index: 9999;
        animation: slideIn 0.3s ease;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      `;
      toast.textContent = message;
      document.body.appendChild(toast);
      
      setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
      }, 3000);
    }
    
    // Initialize dashboard
    document.addEventListener('DOMContentLoaded', function() {
      if (typeof window.Dashboard !== 'undefined') {
        new window.Dashboard();
      }
    });
    
    // Export Stock PDF function
    function exportStockPDF() {
      window.open('export_pdf.php?type=stock', '_blank');
    }
  </script>
  
  <style>
    @keyframes slideIn {
      from { transform: translateX(100%); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
      from { transform: translateX(0); opacity: 1; }
      to { transform: translateX(100%); opacity: 0; }
    }
    @keyframes spin {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }
  </style>
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
