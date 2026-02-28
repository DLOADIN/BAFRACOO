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
  
  $current_page = 'overall-stock';
  
  // Get overall statistics
  $total_products_query = mysqli_query($con, "SELECT COUNT(DISTINCT u_toolname) as count FROM `tool`");
  $total_products = $total_products_query ? mysqli_fetch_assoc($total_products_query)['count'] : 0;
  
  $total_items_query = mysqli_query($con, "SELECT SUM(u_itemsnumber) as total FROM `tool`");
  $total_items = $total_items_query ? mysqli_fetch_assoc($total_items_query)['total'] ?? 0 : 0;
  
  $total_value_query = mysqli_query($con, "SELECT SUM(u_itemsnumber * u_price) as total FROM `tool`");
  $total_value = $total_value_query ? mysqli_fetch_assoc($total_value_query)['total'] ?? 0 : 0;
  
  $low_stock_query = mysqli_query($con, "SELECT COUNT(DISTINCT u_toolname) as count FROM `tool` WHERE u_itemsnumber < 10 AND u_itemsnumber > 0");
  $low_stock_count = $low_stock_query ? mysqli_fetch_assoc($low_stock_query)['count'] : 0;
  
  $out_of_stock_query = mysqli_query($con, "SELECT COUNT(DISTINCT u_toolname) as count FROM `tool` WHERE u_itemsnumber = 0");
  $out_of_stock_count = $out_of_stock_query ? mysqli_fetch_assoc($out_of_stock_query)['count'] : 0;
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
  <title>BAFRACOO - Overall Stock</title>
  <style>
    .stock-summary-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }
    .summary-card {
      background: white;
      border-radius: 16px;
      padding: 20px;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
      border: 1px solid var(--gray-100);
      transition: all 0.3s ease;
    }
    .summary-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.15);
    }
    .summary-card .icon {
      width: 48px;
      height: 48px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      margin-bottom: 12px;
    }
    .summary-card .icon.primary { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; }
    .summary-card .icon.success { background: linear-gradient(135deg, #10b981, #059669); color: white; }
    .summary-card .icon.warning { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
    .summary-card .icon.danger { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; }
    .summary-card .icon.purple { background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; }
    .summary-card .value {
      font-size: 1.75rem;
      font-weight: 700;
      color: var(--gray-900);
      margin-bottom: 4px;
    }
    .summary-card .label {
      font-size: 0.8rem;
      color: var(--gray-500);
      font-weight: 500;
    }
    .search-filter-bar {
      display: flex;
      gap: 16px;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }
    .search-input {
      flex: 1;
      min-width: 250px;
      padding: 12px 16px;
      border: 2px solid var(--gray-200);
      border-radius: 10px;
      font-size: 0.95rem;
      transition: all 0.3s ease;
    }
    .search-input:focus {
      outline: none;
      border-color: var(--primary-color);
      box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
    }
    .filter-select {
      padding: 12px 16px;
      border: 2px solid var(--gray-200);
      border-radius: 10px;
      font-size: 0.95rem;
      min-width: 180px;
      cursor: pointer;
    }
    .stock-table-wrapper {
      background: white;
      border-radius: 16px;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
      border: 1px solid var(--gray-100);
      overflow: hidden;
    }
    .stock-table-header {
      padding: 20px 24px;
      border-bottom: 1px solid var(--gray-100);
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: linear-gradient(135deg, #f8fafc, #f1f5f9);
    }
    .stock-table-title {
      font-size: 1.1rem;
      font-weight: 600;
      color: var(--gray-900);
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .stock-table {
      width: 100%;
      border-collapse: collapse;
    }
    .stock-table thead th {
      background: var(--gray-50);
      padding: 14px 16px;
      text-align: left;
      font-weight: 600;
      color: var(--gray-600);
      font-size: 0.8rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      border-bottom: 2px solid var(--gray-200);
    }
    .stock-table tbody tr {
      transition: all 0.2s ease;
    }
    .stock-table tbody tr:hover {
      background: var(--gray-50);
    }
    .stock-table tbody td {
      padding: 16px;
      border-bottom: 1px solid var(--gray-100);
      color: var(--gray-800);
      font-size: 0.9rem;
    }
    .product-cell {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .product-icon {
      width: 42px;
      height: 42px;
      border-radius: 10px;
      background: linear-gradient(135deg, #3b82f6, #2563eb);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 1.2rem;
    }
    .product-name {
      font-weight: 600;
      color: var(--gray-900);
    }
    .product-type {
      font-size: 0.8rem;
      color: var(--gray-500);
    }
    .stock-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 600;
    }
    .stock-badge.in-stock { background: #d1fae5; color: #065f46; }
    .stock-badge.low-stock { background: #fef3c7; color: #92400e; }
    .stock-badge.out-of-stock { background: #fee2e2; color: #991b1b; }
    .batch-badge {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 4px 10px;
      background: var(--gray-100);
      border-radius: 20px;
      font-size: 0.75rem;
      color: var(--gray-600);
      font-weight: 500;
    }
    .price-range {
      font-size: 0.85rem;
      color: var(--gray-600);
    }
    .value-cell {
      font-weight: 700;
      color: var(--gray-900);
    }
    .stock-number {
      font-weight: 700;
      font-size: 1rem;
    }
    .stock-number.success { color: #059669; }
    .stock-number.warning { color: #d97706; }
    .stock-number.danger { color: #dc2626; }
    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: var(--gray-500);
    }
    .empty-state ion-icon {
      font-size: 4rem;
      color: var(--gray-300);
      margin-bottom: 16px;
    }
    .table-footer {
      padding: 16px 24px;
      background: var(--gray-50);
      border-top: 1px solid var(--gray-100);
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 0.875rem;
      color: var(--gray-600);
    }
    @media (max-width: 768px) {
      .stock-table-wrapper {
        overflow-x: auto;
      }
      .stock-table {
        min-width: 900px;
      }
    }
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
          <a href="stock.php" class="btn btn-primary" style="margin-right: 12px; padding: 10px 20px; background: var(--primary-color); color: white; border: none; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
            <ion-icon name="list-outline"></ion-icon>
            View Detailed Inventory
          </a>
          <a href="logout.php" class="logout-btn">
            <ion-icon name="log-out-outline"></ion-icon>
            <span>Logout</span>
          </a>
        </div>
      </header>
      
      <!-- Page Banner -->
      <div class="page-banner">
        <h1 class="page-banner-title">
          <ion-icon name="layers-outline"></ion-icon>
          Overall Stock
        </h1>
        <p class="page-banner-subtitle">Consolidated view of all inventory items - same products grouped together</p>
      </div>

      <div class="content-area">
        <!-- Summary Cards -->
        <div class="stock-summary-grid">
          <div class="summary-card">
            <div class="icon primary">
              <ion-icon name="cube-outline"></ion-icon>
            </div>
            <div class="value"><?php echo number_format($total_products); ?></div>
            <div class="label">Unique Products</div>
          </div>
          
          <div class="summary-card">
            <div class="icon success">
              <ion-icon name="layers-outline"></ion-icon>
            </div>
            <div class="value"><?php echo number_format($total_items); ?></div>
            <div class="label">Total Items</div>
          </div>
          
          <div class="summary-card">
            <div class="icon purple">
              <ion-icon name="cash-outline"></ion-icon>
            </div>
            <div class="value"><?php echo number_format($total_value); ?></div>
            <div class="label">Total Value (RWF)</div>
          </div>
          
          <div class="summary-card">
            <div class="icon warning">
              <ion-icon name="alert-circle-outline"></ion-icon>
            </div>
            <div class="value"><?php echo number_format($low_stock_count); ?></div>
            <div class="label">Low Stock</div>
          </div>
          
          <div class="summary-card">
            <div class="icon danger">
              <ion-icon name="close-circle-outline"></ion-icon>
            </div>
            <div class="value"><?php echo number_format($out_of_stock_count); ?></div>
            <div class="label">Out of Stock</div>
          </div>
        </div>

        <!-- Search and Filter -->
        <div class="search-filter-bar">
          <input type="text" id="searchInput" class="search-input" placeholder="🔍 Search products by name or type..." onkeyup="filterTable()">
          <select id="stockFilter" class="filter-select" onchange="filterTable()">
            <option value="all">All Stock Levels</option>
            <option value="in-stock">In Stock (10+)</option>
            <option value="low-stock">Low Stock (1-9)</option>
            <option value="out-of-stock">Out of Stock (0)</option>
          </select>
        </div>

        <!-- Stock Table -->
        <div class="stock-table-wrapper">
          <div class="stock-table-header">
            <div class="stock-table-title">
              <ion-icon name="grid-outline"></ion-icon>
              Consolidated Inventory Table
            </div>
            <div class="batch-badge">
              <ion-icon name="information-circle-outline"></ion-icon>
              Items with same name are grouped
            </div>
          </div>
          
          <div style="overflow-x: auto;">
            <table class="stock-table" id="stockTable">
              <thead>
                <tr>
                  <th style="width: 50px;">#</th>
                  <th>Product Name</th>
                  <th>Type</th>
                  <th>Total Quantity</th>
                  <th>Batches</th>
                  <th>Price (RWF)</th>
                  <th>Total Value (RWF)</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody id="tableBody">
                <?php
                // Get all products grouped by name with aggregated data
                $products_query = mysqli_query($con, "
                  SELECT 
                    u_toolname,
                    SUM(u_itemsnumber) as total_stock,
                    COUNT(*) as batch_count,
                    MIN(u_price) as min_price,
                    MAX(u_price) as max_price,
                    ROUND(AVG(u_price)) as avg_price,
                    SUM(u_itemsnumber * u_price) as total_value,
                    MAX(u_type) as u_type,
                    MAX(u_tooldescription) as u_tooldescription,
                    MAX(u_date) as last_updated
                  FROM `tool`
                  GROUP BY u_toolname
                  ORDER BY u_toolname ASC
                ");
                
                $row_num = 0;
                $displayed_count = 0;
                
                if($products_query && mysqli_num_rows($products_query) > 0):
                  while($product = mysqli_fetch_assoc($products_query)):
                    $row_num++;
                    $displayed_count++;
                    $stock = (int)$product['total_stock'];
                    
                    // Determine status
                    $status_class = 'in-stock';
                    $status_text = 'In Stock';
                    $stock_color = 'success';
                    $filter_class = 'in-stock';
                    
                    if($stock == 0) {
                      $status_class = 'out-of-stock';
                      $status_text = 'Out of Stock';
                      $stock_color = 'danger';
                      $filter_class = 'out-of-stock';
                    } elseif($stock < 10) {
                      $status_class = 'low-stock';
                      $status_text = 'Low Stock';
                      $stock_color = 'warning';
                      $filter_class = 'low-stock';
                    }
                ?>
                <tr data-name="<?php echo strtolower(htmlspecialchars($product['u_toolname'])); ?>" 
                    data-type="<?php echo strtolower(htmlspecialchars($product['u_type'] ?? '')); ?>" 
                    data-filter="<?php echo $filter_class; ?>">
                  <td style="font-weight: 600; color: var(--gray-400);"><?php echo $row_num; ?></td>
                  <td>
                    <div class="product-cell">
                      <div class="product-icon">
                        <ion-icon name="construct-outline"></ion-icon>
                      </div>
                      <div>
                        <div class="product-name"><?php echo htmlspecialchars($product['u_toolname']); ?></div>
                        <div class="product-type"><?php echo htmlspecialchars($product['u_tooldescription'] ?? 'No description'); ?></div>
                      </div>
                    </div>
                  </td>
                  <td><?php echo htmlspecialchars($product['u_type'] ?? 'General'); ?></td>
                  <td>
                    <span class="stock-number <?php echo $stock_color; ?>"><?php echo number_format($stock); ?></span>
                  </td>
                  <td>
                    <span class="batch-badge">
                      <ion-icon name="copy-outline"></ion-icon>
                      <?php echo $product['batch_count']; ?>
                    </span>
                  </td>
                  <td class="price-range"><?php echo number_format($product['avg_price']); ?></td>
                  <td class="value-cell"><?php echo number_format($product['total_value']); ?></td>
                  <td>
                    <span class="stock-badge <?php echo $status_class; ?>">
                      <ion-icon name="<?php echo $stock == 0 ? 'close-circle' : ($stock < 10 ? 'alert-circle' : 'checkmark-circle'); ?>-outline"></ion-icon>
                      <?php echo $status_text; ?>
                    </span>
                  </td>
                </tr>
                <?php endwhile; else: ?>
                <tr id="emptyRow">
                  <td colspan="8">
                    <div class="empty-state">
                      <ion-icon name="cube-outline"></ion-icon>
                      <h3>No Products Found</h3>
                      <p>Add products to your inventory to see them here.</p>
                      <a href="addorder.php" class="btn btn-primary" style="margin-top: 16px; display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; background: var(--primary-color); color: white; text-decoration: none; border-radius: 8px; font-weight: 600;">
                        <ion-icon name="add-circle-outline"></ion-icon>
                        Add First Product
                      </a>
                    </div>
                  </td>
                </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
          
          <div class="table-footer">
            <div>
              Showing <strong id="visibleCount"><?php echo $displayed_count; ?></strong> of <strong><?php echo $row_num; ?></strong> products
            </div>
            <div>
              Last updated: <?php echo date('M d, Y h:i A'); ?>
            </div>
          </div>
        </div>
        
        <div id="noResults" class="empty-state" style="display: none; background: white; border-radius: 16px; margin-top: 20px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
          <ion-icon name="search-outline"></ion-icon>
          <h3>No Matching Products</h3>
          <p>Try adjusting your search or filter criteria.</p>
        </div>
      </div>
    </main>
  </div>

  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
  
  <script>
    function filterTable() {
      const searchValue = document.getElementById('searchInput').value.toLowerCase();
      const stockFilter = document.getElementById('stockFilter').value;
      
      const rows = document.querySelectorAll('#tableBody tr[data-name]');
      const noResults = document.getElementById('noResults');
      const tableWrapper = document.querySelector('.stock-table-wrapper');
      
      let visibleCount = 0;
      
      rows.forEach(row => {
        const name = row.dataset.name;
        const type = row.dataset.type;
        const filterClass = row.dataset.filter;
        
        const matchesSearch = name.includes(searchValue) || type.includes(searchValue);
        const matchesFilter = stockFilter === 'all' || filterClass === stockFilter;
        
        if (matchesSearch && matchesFilter) {
          row.style.display = '';
          visibleCount++;
        } else {
          row.style.display = 'none';
        }
      });
      
      // Update visible count
      document.getElementById('visibleCount').textContent = visibleCount;
      
      // Show/hide no results message
      if (visibleCount === 0 && rows.length > 0) {
        noResults.style.display = 'block';
        tableWrapper.style.display = 'none';
      } else {
        noResults.style.display = 'none';
        tableWrapper.style.display = 'block';
      }
    }
    
    // Mobile menu toggle
    document.querySelector('.mobile-menu-btn')?.addEventListener('click', function() {
      document.querySelector('.sidebar').classList.toggle('mobile-open');
      document.querySelector('.sidebar-overlay').classList.toggle('active');
    });
    
    document.querySelector('.sidebar-overlay')?.addEventListener('click', function() {
      document.querySelector('.sidebar').classList.remove('mobile-open');
      this.classList.remove('active');
    });
  </script>
</body>
</html>
