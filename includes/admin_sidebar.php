<?php
// Shared Admin Sidebar Component
// Usage: Set $current_page variable before including this file
// Example: $current_page = 'dashboard'; include 'includes/admin_sidebar.php';

// Get pending orders count
$pending_orders_query = mysqli_query($con, "SELECT COUNT(*) as count FROM `order` WHERE status='Pending'");
$pending_count = $pending_orders_query ? mysqli_fetch_assoc($pending_orders_query)['count'] : 0;

// Get low stock alerts count
$low_stock_query = mysqli_query($con, "SELECT COUNT(*) as count FROM `tool` WHERE u_itemsnumber < 10");
$low_stock_count = $low_stock_query ? mysqli_fetch_assoc($low_stock_query)['count'] : 0;
?>
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
                    <a href="admindashboard.php" class="nav-link <?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>" data-tooltip="Dashboard">
                        <ion-icon name="home-outline" class="nav-icon"></ion-icon>
                        <span class="nav-text">Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="addtool.php" class="nav-link <?php echo ($current_page == 'addorder') ? 'active' : ''; ?>" data-tooltip="Add Order">
                        <ion-icon name="add-circle-outline" class="nav-icon"></ion-icon>
                        <span class="nav-text">Add Order</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="addorder.php" class="nav-link <?php echo ($current_page == 'addtool') ? 'active' : ''; ?>" data-tooltip="Add Tool">
                        <ion-icon name="construct-outline" class="nav-icon"></ion-icon>
                        <span class="nav-text">Add Tool</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="orders.php" class="nav-link <?php echo ($current_page == 'orders') ? 'active' : ''; ?>" data-tooltip="Orders">
                        <ion-icon name="bag-handle-outline" class="nav-icon"></ion-icon>
                        <span class="nav-text">Orders</span>
                        <?php if($pending_count > 0): ?>
                            <span class="nav-badge"><?php echo $pending_count; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="stock.php" class="nav-link <?php echo ($current_page == 'stock') ? 'active' : ''; ?>" data-tooltip="Inventory">
                        <ion-icon name="cube-outline" class="nav-icon"></ion-icon>
                        <span class="nav-text">Inventory</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="inventory-management.php" class="nav-link <?php echo ($current_page == 'inventory-management') ? 'active' : ''; ?>" data-tooltip="Enhanced Inventory">
                        <ion-icon name="library-outline" class="nav-icon"></ion-icon>
                        <span class="nav-text">Enhanced Inventory</span>
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="nav-section">
            <h3 class="nav-section-title">Stock Management</h3>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="damaged-products.php" class="nav-link <?php echo ($current_page == 'damaged-products') ? 'active' : ''; ?>" data-tooltip="Damage Control">
                        <ion-icon name="warning-outline" class="nav-icon"></ion-icon>
                        <span class="nav-text">Damage Control</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="returned-stock.php" class="nav-link <?php echo ($current_page == 'returned-stock') ? 'active' : ''; ?>" data-tooltip="Returned Stock">
                        <ion-icon name="return-up-back-outline" class="nav-icon"></ion-icon>
                        <span class="nav-text">Returned Stock</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="stock-alerts.php" class="nav-link <?php echo ($current_page == 'stock-alerts') ? 'active' : ''; ?>" data-tooltip="Stock Alerts">
                        <ion-icon name="notifications-outline" class="nav-icon"></ion-icon>
                        <span class="nav-text">Stock Alerts</span>
                        <?php if($low_stock_count > 0): ?>
                            <span class="nav-badge" style="background: var(--warning-color);"><?php echo $low_stock_count; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="nav-section">
            <h3 class="nav-section-title">Management</h3>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="transactions.php" class="nav-link <?php echo ($current_page == 'transactions') ? 'active' : ''; ?>" data-tooltip="Transactions">
                        <ion-icon name="analytics-outline" class="nav-icon"></ion-icon>
                        <span class="nav-text">Transactions</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="report.php" class="nav-link <?php echo ($current_page == 'report') ? 'active' : ''; ?>" data-tooltip="Reports">
                        <ion-icon name="document-text-outline" class="nav-icon"></ion-icon>
                        <span class="nav-text">Reports</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="adminprofile.php" class="nav-link <?php echo ($current_page == 'profile') ? 'active' : ''; ?>" data-tooltip="Profile">
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
                    <a href="website.php" class="nav-link <?php echo ($current_page == 'website') ? 'active' : ''; ?>" data-tooltip="Visit Website">
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
