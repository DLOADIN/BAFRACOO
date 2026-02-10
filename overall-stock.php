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
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }
    .summary-card {
      background: white;
      border-radius: 16px;
      padding: 24px;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
      border: 1px solid var(--gray-100);
      transition: all 0.3s ease;
    }
    .summary-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.15);
    }
    .summary-card .icon {
      width: 56px;
      height: 56px;
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.75rem;
      margin-bottom: 16px;
    }
    .summary-card .icon.primary { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; }
    .summary-card .icon.success { background: linear-gradient(135deg, #10b981, #059669); color: white; }
    .summary-card .icon.warning { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
    .summary-card .icon.danger { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; }
    .summary-card .icon.purple { background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; }
    .summary-card .value {
      font-size: 2rem;
      font-weight: 700;
      color: var(--gray-900);
      margin-bottom: 4px;
    }
    .summary-card .label {
      font-size: 0.875rem;
      color: var(--gray-500);
      font-weight: 500;
    }
    .product-card {
      background: white;
      border-radius: 12px;
      border: 1px solid var(--gray-200);
      overflow: hidden;
      transition: all 0.3s ease;
    }
    .product-card:hover {
      box-shadow: 0 8px 16px -4px rgba(0, 0, 0, 0.1);
      border-color: var(--primary-color);
    }
    .product-card-header {
      padding: 20px;
      border-bottom: 1px solid var(--gray-100);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .product-info {
      display: flex;
      align-items: center;
      gap: 16px;
    }
    .product-icon {
      width: 50px;
      height: 50px;
      border-radius: 12px;
      background: linear-gradient(135deg, #3b82f6, #2563eb);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 1.5rem;
    }
    .product-name {
      font-weight: 600;
      color: var(--gray-900);
      font-size: 1.1rem;
    }
    .product-type {
      color: var(--gray-500);
      font-size: 0.875rem;
    }
    .product-card-body {
      padding: 20px;
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 16px;
    }
    .stat-item {
      text-align: center;
      padding: 12px;
      background: var(--gray-50);
      border-radius: 10px;
    }
    .stat-item .stat-value {
      font-size: 1.25rem;
      font-weight: 700;
      color: var(--gray-900);
    }
    .stat-item .stat-label {
      font-size: 0.75rem;
      color: var(--gray-500);
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-top: 4px;
    }
    .stat-item.success .stat-value { color: var(--success-color); }
    .stat-item.warning .stat-value { color: var(--warning-color); }
    .stat-item.danger .stat-value { color: var(--error-color); }
    .products-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
      gap: 20px;
    }
    @media (max-width: 768px) {
      .products-grid {
        grid-template-columns: 1fr;
      }
      .product-card-body {
        grid-template-columns: repeat(2, 1fr);
      }
    }
    .search-filter-bar {
      display: flex;
      gap: 16px;
      margin-bottom: 24px;
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
    .batch-count {
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
            <div class="label">Total Items in Stock</div>
          </div>
          
          <div class="summary-card">
            <div class="icon purple">
              <ion-icon name="cash-outline"></ion-icon>
            </div>
            <div class="value"><?php echo number_format($total_value); ?> RWF</div>
            <div class="label">Total Inventory Value</div>
          </div>
          
          <div class="summary-card">
            <div class="icon warning">
              <ion-icon name="alert-circle-outline"></ion-icon>
            </div>
            <div class="value"><?php echo number_format($low_stock_count); ?></div>
            <div class="label">Low Stock Products</div>
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
          <input type="text" id="searchInput" class="search-input" placeholder="🔍 Search products by name or type..." onkeyup="filterProducts()">
          <select id="stockFilter" class="filter-select" onchange="filterProducts()">
            <option value="all">All Stock Levels</option>
            <option value="in-stock">In Stock (10+)</option>
            <option value="low-stock">Low Stock (1-9)</option>
            <option value="out-of-stock">Out of Stock (0)</option>
          </select>
          <select id="sortBy" class="filter-select" onchange="filterProducts()">
            <option value="name">Sort by Name</option>
            <option value="stock-high">Stock: High to Low</option>
            <option value="stock-low">Stock: Low to High</option>
            <option value="value-high">Value: High to Low</option>
          </select>
        </div>

        <!-- Products Grid -->
        <div class="products-grid" id="productsGrid">
          <?php
          // Get all products grouped by name with aggregated data
          $products_query = mysqli_query($con, "
            SELECT 
              u_toolname,
              SUM(u_itemsnumber) as total_stock,
              COUNT(*) as batch_count,
              MIN(u_price) as min_price,
              MAX(u_price) as max_price,
              AVG(u_price) as avg_price,
              SUM(u_itemsnumber * u_price) as total_value,
              MAX(u_type) as u_type,
              MAX(u_tooldescription) as u_tooldescription,
              MAX(u_date) as last_updated,
              MAX(image_url) as image_url
            FROM `tool`
            GROUP BY u_toolname
            ORDER BY u_toolname ASC
          ");
          
          if($products_query && mysqli_num_rows($products_query) > 0):
            while($product = mysqli_fetch_assoc($products_query)):
              $stock = (int)$product['total_stock'];
              $stock_status = 'success';
              $stock_label = 'In Stock';
              $stock_filter = 'in-stock';
              
              if($stock == 0) {
                $stock_status = 'danger';
                $stock_label = 'Out of Stock';
                $stock_filter = 'out-of-stock';
              } elseif($stock < 10) {
                $stock_status = 'warning';
                $stock_label = 'Low Stock';
                $stock_filter = 'low-stock';
              }
              
              $avg_price = round($product['avg_price']);
              $total_value = $product['total_value'];
          ?>
          <div class="product-card" data-name="<?php echo strtolower(htmlspecialchars($product['u_toolname'])); ?>" data-type="<?php echo strtolower(htmlspecialchars($product['u_type'] ?? '')); ?>" data-stock="<?php echo $stock; ?>" data-value="<?php echo $total_value; ?>" data-filter="<?php echo $stock_filter; ?>">
            <div class="product-card-header">
              <div class="product-info">
                <div class="product-icon">
                  <ion-icon name="construct-outline"></ion-icon>
                </div>
                <div>
                  <div class="product-name"><?php echo htmlspecialchars($product['u_toolname']); ?></div>
                  <div class="product-type"><?php echo htmlspecialchars($product['u_type'] ?? 'General'); ?></div>
                </div>
              </div>
              <div class="batch-count">
                <ion-icon name="copy-outline"></ion-icon>
                <?php echo $product['batch_count']; ?> batch<?php echo $product['batch_count'] > 1 ? 'es' : ''; ?>
              </div>
            </div>
            <div class="product-card-body">
              <div class="stat-item <?php echo $stock_status; ?>">
                <div class="stat-value"><?php echo number_format($stock); ?></div>
                <div class="stat-label"><?php echo $stock_label; ?></div>
              </div>
              <div class="stat-item">
                <div class="stat-value"><?php echo number_format($avg_price); ?></div>
                <div class="stat-label">Avg Price (RWF)</div>
              </div>
              <div class="stat-item">
                <div class="stat-value"><?php echo number_format($total_value); ?></div>
                <div class="stat-label">Total Value</div>
              </div>
              <div class="stat-item">
                <div class="stat-value"><?php echo $product['min_price'] == $product['max_price'] ? number_format($product['min_price']) : number_format($product['min_price']) . '-' . number_format($product['max_price']); ?></div>
                <div class="stat-label">Price Range</div>
              </div>
            </div>
          </div>
          <?php endwhile; else: ?>
          <div class="empty-state" style="grid-column: 1 / -1;">
            <ion-icon name="cube-outline"></ion-icon>
            <h3>No Products Found</h3>
            <p>Add products to your inventory to see them here.</p>
            <a href="addorder.php" class="btn btn-primary" style="margin-top: 16px; display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; background: var(--primary-color); color: white; text-decoration: none; border-radius: 8px; font-weight: 600;">
              <ion-icon name="add-circle-outline"></ion-icon>
              Add First Product
            </a>
          </div>
          <?php endif; ?>
        </div>
        
        <div id="noResults" class="empty-state" style="display: none;">
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
    function filterProducts() {
      const searchValue = document.getElementById('searchInput').value.toLowerCase();
      const stockFilter = document.getElementById('stockFilter').value;
      const sortBy = document.getElementById('sortBy').value;
      
      const cards = document.querySelectorAll('.product-card');
      const grid = document.getElementById('productsGrid');
      const noResults = document.getElementById('noResults');
      
      let visibleCards = [];
      
      cards.forEach(card => {
        const name = card.dataset.name;
        const type = card.dataset.type;
        const filterClass = card.dataset.filter;
        
        const matchesSearch = name.includes(searchValue) || type.includes(searchValue);
        const matchesFilter = stockFilter === 'all' || filterClass === stockFilter;
        
        if (matchesSearch && matchesFilter) {
          card.style.display = '';
          visibleCards.push(card);
        } else {
          card.style.display = 'none';
        }
      });
      
      // Sort visible cards
      visibleCards.sort((a, b) => {
        switch(sortBy) {
          case 'stock-high':
            return parseInt(b.dataset.stock) - parseInt(a.dataset.stock);
          case 'stock-low':
            return parseInt(a.dataset.stock) - parseInt(b.dataset.stock);
          case 'value-high':
            return parseInt(b.dataset.value) - parseInt(a.dataset.value);
          default:
            return a.dataset.name.localeCompare(b.dataset.name);
        }
      });
      
      // Re-append in sorted order
      visibleCards.forEach(card => grid.appendChild(card));
      
      // Show/hide no results message
      noResults.style.display = visibleCards.length === 0 ? 'block' : 'none';
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
