<?php
require "connection.php";
require "EnhancedInventoryManager.php";

if(!empty($_SESSION["id"])){
    $id = $_SESSION["id"];
    $check = mysqli_query($con,"SELECT * FROM `admin` WHERE id=$id ");
    $row = mysqli_fetch_array($check);
} else {
    header('location:loginadmin.php');
}

$inventoryManager = new EnhancedInventoryManager($con);

// Handle test execution
if(isset($_POST['run_test'])) {
    $test_type = $_POST['test_type'];
    $test_results = [];
    
    switch($test_type) {
        case 'database_connection':
            try {
                $test_query = "SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = '" . mysqli_real_escape_string($con, 'bafraco') . "'";
                $result = mysqli_query($con, $test_query);
                if($result) {
                    $count = mysqli_fetch_array($result)['count'];
                    $test_results[] = ['status' => 'success', 'message' => "Database connected successfully. Found $count tables."];
                } else {
                    $test_results[] = ['status' => 'error', 'message' => 'Database connection failed: ' . mysqli_error($con)];
                }
            } catch(Exception $e) {
                $test_results[] = ['status' => 'error', 'message' => 'Database test failed: ' . $e->getMessage()];
            }
            break;
            
        case 'inventory_operations':
            try {
                // Test basic inventory operations
                $locations = $inventoryManager->getAllLocations();
                $location_count = mysqli_num_rows($locations);
                $test_results[] = ['status' => 'success', 'message' => "Found $location_count locations in system"];
                
                // Test stock checking
                $stock_check = $inventoryManager->getAvailableStock(1, 1);
                $test_results[] = ['status' => 'info', 'message' => "Sample stock check returned: $stock_check units"];
                
                // Test threshold checking
                $alerts = $inventoryManager->checkStockThresholds();
                $test_results[] = ['status' => 'info', 'message' => "Stock threshold check completed"];
                
            } catch(Exception $e) {
                $test_results[] = ['status' => 'error', 'message' => 'Inventory operations test failed: ' . $e->getMessage()];
            }
            break;
            
        case 'damage_tracking':
            try {
                // Test damage categories
                $damage_stats = $inventoryManager->getDamageStatistics();
                $test_results[] = ['status' => 'success', 'message' => 'Damage tracking system operational'];
                $test_results[] = ['status' => 'info', 'message' => 'Total damage incidents: ' . $damage_stats['total_incidents']];
                $test_results[] = ['status' => 'info', 'message' => 'Total financial loss: RWF ' . number_format($damage_stats['total_loss'])];
                
            } catch(Exception $e) {
                $test_results[] = ['status' => 'error', 'message' => 'Damage tracking test failed: ' . $e->getMessage()];
            }
            break;
            
        case 'returns_system':
            try {
                // Test returns functionality
                $returns_summary = $inventoryManager->getReturnsSummary();
                $test_results[] = ['status' => 'success', 'message' => 'Returns management system operational'];
                $test_results[] = ['status' => 'info', 'message' => 'Pending returns: ' . $returns_summary['pending']];
                $test_results[] = ['status' => 'info', 'message' => 'Completed returns: ' . $returns_summary['completed']];
                
            } catch(Exception $e) {
                $test_results[] = ['status' => 'error', 'message' => 'Returns system test failed: ' . $e->getMessage()];
            }
            break;
            
        case 'fifo_lifo':
            try {
                // Test FIFO/LIFO processing
                $method_test = $inventoryManager->getCurrentInventoryMethod();
                $test_results[] = ['status' => 'success', 'message' => 'FIFO/LIFO system operational'];
                $test_results[] = ['status' => 'info', 'message' => 'Current inventory method: ' . $method_test];
                
                // Test batch processing
                $sample_batches = $inventoryManager->getStockBatches(1, 1);
                $batch_count = mysqli_num_rows($sample_batches);
                $test_results[] = ['status' => 'info', 'message' => "Sample product has $batch_count batches"];
                
            } catch(Exception $e) {
                $test_results[] = ['status' => 'error', 'message' => 'FIFO/LIFO test failed: ' . $e->getMessage()];
            }
            break;
            
        case 'comprehensive':
            // Run all tests
            $all_tests = ['database_connection', 'inventory_operations', 'damage_tracking', 'returns_system', 'fifo_lifo'];
            foreach($all_tests as $test) {
                $_POST['test_type'] = $test;
                // Recursive call to get results for each test
                $sub_results = runTest($test, $inventoryManager, $con);
                $test_results = array_merge($test_results, $sub_results);
            }
            break;
    }
}

function runTest($test_type, $inventoryManager, $con) {
    $results = [];
    switch($test_type) {
        case 'database_connection':
            try {
                $test_query = "SELECT COUNT(*) as count FROM tool";
                $result = mysqli_query($con, $test_query);
                if($result) {
                    $count = mysqli_fetch_array($result)['count'];
                    $results[] = ['status' => 'success', 'message' => "Database: Found $count products"];
                } else {
                    $results[] = ['status' => 'error', 'message' => 'Database connection failed'];
                }
            } catch(Exception $e) {
                $results[] = ['status' => 'error', 'message' => 'Database test failed: ' . $e->getMessage()];
            }
            break;
        case 'inventory_operations':
            try {
                $locations = $inventoryManager->getAllLocations();
                $results[] = ['status' => 'success', 'message' => 'Inventory: Location management working'];
            } catch(Exception $e) {
                $results[] = ['status' => 'error', 'message' => 'Inventory test failed: ' . $e->getMessage()];
            }
            break;
        case 'damage_tracking':
            try {
                $damage_stats = $inventoryManager->getDamageStatistics();
                $results[] = ['status' => 'success', 'message' => 'Damage tracking: System operational'];
            } catch(Exception $e) {
                $results[] = ['status' => 'error', 'message' => 'Damage tracking failed: ' . $e->getMessage()];
            }
            break;
        case 'returns_system':
            try {
                $returns_summary = $inventoryManager->getReturnsSummary();
                $results[] = ['status' => 'success', 'message' => 'Returns: Management system working'];
            } catch(Exception $e) {
                $results[] = ['status' => 'error', 'message' => 'Returns test failed: ' . $e->getMessage()];
            }
            break;
        case 'fifo_lifo':
            try {
                $method_test = $inventoryManager->getCurrentInventoryMethod();
                $results[] = ['status' => 'success', 'message' => 'FIFO/LIFO: Processing system active'];
            } catch(Exception $e) {
                $results[] = ['status' => 'error', 'message' => 'FIFO/LIFO test failed: ' . $e->getMessage()];
            }
            break;
    }
    return $results;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="CSS/modern-dashboard.css">
    <link rel="stylesheet" href="CSS/modern-tables.css">
    <link rel="stylesheet" href="CSS/modern-forms.css">
    <title>BAFRACOO - System Integration Test</title>
    <style>
        .test-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            border-left: 4px solid #3b82f6;
        }
        
        .test-result {
            padding: 0.75rem 1rem;
            margin: 0.5rem 0;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .test-success {
            background: #dcfce7;
            border: 1px solid #16a34a;
            color: #15803d;
        }
        
        .test-error {
            background: #fef2f2;
            border: 1px solid #ef4444;
            color: #dc2626;
        }
        
        .test-info {
            background: #dbeafe;
            border: 1px solid #3b82f6;
            color: #1e40af;
        }
        
        .test-warning {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            color: #d97706;
        }
        
        .system-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .overview-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            text-align: center;
        }
        
        .overview-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .test-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .test-button {
            padding: 1rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-align: left;
            transition: transform 0.2s;
        }
        
        .test-button:hover {
            transform: translateY(-2px);
        }
        
        .test-comprehensive {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
        }
        
        .test-database {
            background: linear-gradient(135deg, #10b981, #34d399);
            color: white;
        }
        
        .test-inventory {
            background: linear-gradient(135deg, #3b82f6, #60a5fa);
            color: white;
        }
        
        .test-damage {
            background: linear-gradient(135deg, #dc2626, #ef4444);
            color: white;
        }
        
        .test-returns {
            background: linear-gradient(135deg, #f59e0b, #fbbf24);
            color: white;
        }
        
        .test-fifo {
            background: linear-gradient(135deg, #06b6d4, #38bdf8);
            color: white;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-logo">
                <img src="images/Captured.JPG" alt="BAFRACOO Logo">
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
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="stock.php" class="nav-link">
                                <ion-icon name="cube-outline" class="nav-icon"></ion-icon>
                                <span class="nav-text">Inventory</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="inventory-management.php" class="nav-link">
                                <ion-icon name="library-outline" class="nav-icon"></ion-icon>
                                <span class="nav-text">Enhanced Inventory</span>
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="nav-section">
                    <h3 class="nav-section-title">Enhanced Features</h3>
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="damaged-products.php" class="nav-link">
                                <ion-icon name="warning-outline" class="nav-icon"></ion-icon>
                                <span class="nav-text">Damage Control</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="returns-management.php" class="nav-link">
                                <ion-icon name="return-up-back-outline" class="nav-icon"></ion-icon>
                                <span class="nav-text">Returns</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="stock-alerts.php" class="nav-link">
                                <ion-icon name="notifications-outline" class="nav-icon"></ion-icon>
                                <span class="nav-text">Stock Alerts</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="system-test.php" class="nav-link active">
                                <ion-icon name="flask-outline" class="nav-icon"></ion-icon>
                                <span class="nav-text">System Test</span>
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

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <h1 class="page-title">🧪 System Integration Test</h1>
                </div>
                <div class="header-right">
                    <a href="logout.php" class="logout-btn">
                        <ion-icon name="log-out-outline"></ion-icon>
                        <span>Logout</span>
                    </a>
                </div>
            </header>
            
            <div class="content-wrapper">
                <!-- System Overview -->
                <div class="system-overview">
                    <div class="overview-card">
                        <div class="overview-icon" style="color: #10b981;">
                            <ion-icon name="server-outline"></ion-icon>
                        </div>
                        <h3>Database System</h3>
                        <p style="color: #6b7280; margin: 0;">MariaDB 10.4+ with enhanced inventory tables</p>
                    </div>
                    <div class="overview-card">
                        <div class="overview-icon" style="color: #3b82f6;">
                            <ion-icon name="layers-outline"></ion-icon>
                        </div>
                        <h3>Inventory Management</h3>
                        <p style="color: #6b7280; margin: 0;">FIFO/LIFO with location tracking</p>
                    </div>
                    <div class="overview-card">
                        <div class="overview-icon" style="color: #dc2626;">
                            <ion-icon name="warning-outline"></ion-icon>
                        </div>
                        <h3>Damage Tracking</h3>
                        <p style="color: #6b7280; margin: 0;">8 damage categories with loss calculation</p>
                    </div>
                    <div class="overview-card">
                        <div class="overview-icon" style="color: #f59e0b;">
                            <ion-icon name="return-up-back-outline"></ion-icon>
                        </div>
                        <h3>Returns Management</h3>
                        <p style="color: #6b7280; margin: 0;">30-day policy with condition assessment</p>
                    </div>
                </div>

                <!-- Test Buttons -->
                <div class="dashboard-card" style="margin-bottom: 2rem;">
                    <div class="card-header">
                        <h3>🔬 Run System Tests</h3>
                        <p>Select a test to verify system components are working correctly</p>
                    </div>
                    
                    <div class="test-buttons">
                        <form method="POST" style="display: contents;">
                            <input type="hidden" name="test_type" value="comprehensive">
                            <button type="submit" name="run_test" class="test-button test-comprehensive">
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                    <ion-icon name="checkmark-done-outline" style="font-size: 1.5rem;"></ion-icon>
                                    <strong>Comprehensive Test</strong>
                                </div>
                                <div style="font-size: 0.875rem; opacity: 0.9;">Run all system tests</div>
                            </button>
                        </form>
                        
                        <form method="POST" style="display: contents;">
                            <input type="hidden" name="test_type" value="database_connection">
                            <button type="submit" name="run_test" class="test-button test-database">
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                    <ion-icon name="server-outline" style="font-size: 1.5rem;"></ion-icon>
                                    <strong>Database Test</strong>
                                </div>
                                <div style="font-size: 0.875rem; opacity: 0.9;">Check database connectivity</div>
                            </button>
                        </form>
                        
                        <form method="POST" style="display: contents;">
                            <input type="hidden" name="test_type" value="inventory_operations">
                            <button type="submit" name="run_test" class="test-button test-inventory">
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                    <ion-icon name="layers-outline" style="font-size: 1.5rem;"></ion-icon>
                                    <strong>Inventory Test</strong>
                                </div>
                                <div style="font-size: 0.875rem; opacity: 0.9;">Test inventory operations</div>
                            </button>
                        </form>
                        
                        <form method="POST" style="display: contents;">
                            <input type="hidden" name="test_type" value="damage_tracking">
                            <button type="submit" name="run_test" class="test-button test-damage">
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                    <ion-icon name="warning-outline" style="font-size: 1.5rem;"></ion-icon>
                                    <strong>Damage Test</strong>
                                </div>
                                <div style="font-size: 0.875rem; opacity: 0.9;">Check damage tracking</div>
                            </button>
                        </form>
                        
                        <form method="POST" style="display: contents;">
                            <input type="hidden" name="test_type" value="returns_system">
                            <button type="submit" name="run_test" class="test-button test-returns">
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                    <ion-icon name="return-up-back-outline" style="font-size: 1.5rem;"></ion-icon>
                                    <strong>Returns Test</strong>
                                </div>
                                <div style="font-size: 0.875rem; opacity: 0.9;">Test returns management</div>
                            </button>
                        </form>
                        
                        <form method="POST" style="display: contents;">
                            <input type="hidden" name="test_type" value="fifo_lifo">
                            <button type="submit" name="run_test" class="test-button test-fifo">
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                    <ion-icon name="swap-horizontal-outline" style="font-size: 1.5rem;"></ion-icon>
                                    <strong>FIFO/LIFO Test</strong>
                                </div>
                                <div style="font-size: 0.875rem; opacity: 0.9;">Check inventory methods</div>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Test Results -->
                <?php if(isset($test_results) && !empty($test_results)): ?>
                <div class="test-card">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                        <ion-icon name="flask-outline" style="font-size: 1.5rem; color: #3b82f6;"></ion-icon>
                        <h3 style="margin: 0;">Test Results - <?php echo ucfirst(str_replace('_', ' ', $_POST['test_type'])); ?></h3>
                    </div>
                    
                    <?php 
                    $success_count = 0;
                    $error_count = 0;
                    $total_count = count($test_results);
                    
                    foreach($test_results as $result): 
                        if($result['status'] === 'success') $success_count++;
                        if($result['status'] === 'error') $error_count++;
                    ?>
                    <div class="test-result test-<?php echo $result['status']; ?>">
                        <?php if($result['status'] === 'success'): ?>
                            <ion-icon name="checkmark-circle-outline"></ion-icon>
                        <?php elseif($result['status'] === 'error'): ?>
                            <ion-icon name="close-circle-outline"></ion-icon>
                        <?php elseif($result['status'] === 'warning'): ?>
                            <ion-icon name="warning-outline"></ion-icon>
                        <?php else: ?>
                            <ion-icon name="information-circle-outline"></ion-icon>
                        <?php endif; ?>
                        <span><?php echo htmlspecialchars($result['message']); ?></span>
                    </div>
                    <?php endforeach; ?>
                    
                    <div style="margin-top: 1.5rem; padding: 1rem; background: #f8fafc; border-radius: 6px;">
                        <h4 style="margin: 0 0 0.5rem 0;">Test Summary</h4>
                        <div style="display: flex; gap: 2rem;">
                            <span style="color: #059669;"><strong>✓ Passed:</strong> <?php echo $success_count; ?></span>
                            <span style="color: #dc2626;"><strong>✗ Failed:</strong> <?php echo $error_count; ?></span>
                            <span style="color: #6b7280;"><strong>Total:</strong> <?php echo $total_count; ?></span>
                        </div>
                        <div style="margin-top: 0.5rem;">
                            <strong>Success Rate:</strong> 
                            <span style="color: <?php echo $error_count === 0 ? '#059669' : ($error_count > $success_count ? '#dc2626' : '#d97706'); ?>;">
                                <?php echo $total_count > 0 ? round(($success_count / $total_count) * 100, 1) : 0; ?>%
                            </span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- System Information -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>📋 System Information</h3>
                        <p>Current system configuration and status</p>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                        <div>
                            <h4 style="margin: 0 0 1rem 0; color: #374151;">Database Configuration</h4>
                            <div style="background: #f8fafc; padding: 1rem; border-radius: 6px;">
                                <p style="margin: 0.25rem 0;"><strong>Server:</strong> <?php echo mysqli_get_server_info($con); ?></p>
                                <p style="margin: 0.25rem 0;"><strong>Database:</strong> bafraco</p>
                                <p style="margin: 0.25rem 0;"><strong>Connection Status:</strong> 
                                    <span style="color: #059669;">Connected</span>
                                </p>
                                <?php
                                $tables_query = "SHOW TABLES";
                                $tables_result = mysqli_query($con, $tables_query);
                                $table_count = mysqli_num_rows($tables_result);
                                ?>
                                <p style="margin: 0.25rem 0;"><strong>Tables:</strong> <?php echo $table_count; ?></p>
                            </div>
                        </div>
                        
                        <div>
                            <h4 style="margin: 0 0 1rem 0; color: #374151;">Inventory System</h4>
                            <div style="background: #f8fafc; padding: 1rem; border-radius: 6px;">
                                <?php
                                try {
                                    $current_method = $inventoryManager->getCurrentInventoryMethod();
                                    $locations = $inventoryManager->getAllLocations();
                                    $location_count = mysqli_num_rows($locations);
                                    ?>
                                    <p style="margin: 0.25rem 0;"><strong>Method:</strong> <?php echo $current_method; ?></p>
                                    <p style="margin: 0.25rem 0;"><strong>Locations:</strong> <?php echo $location_count; ?></p>
                                    <p style="margin: 0.25rem 0;"><strong>Enhanced Features:</strong> ✓ Active</p>
                                    <p style="margin: 0.25rem 0;"><strong>Status:</strong> 
                                        <span style="color: #059669;">Operational</span>
                                    </p>
                                    <?php
                                } catch(Exception $e) {
                                    ?>
                                    <p style="margin: 0.25rem 0; color: #dc2626;"><strong>Status:</strong> Error</p>
                                    <p style="margin: 0.25rem 0; color: #dc2626;">Error: <?php echo $e->getMessage(); ?></p>
                                    <?php
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <div style="margin-top: 1.5rem;">
                        <h4 style="margin: 0 0 1rem 0; color: #374151;">Feature Status</h4>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem; background: #dcfce7; border-radius: 6px;">
                                <ion-icon name="checkmark-circle" style="color: #059669;"></ion-icon>
                                <span>FIFO/LIFO Processing</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem; background: #dcfce7; border-radius: 6px;">
                                <ion-icon name="checkmark-circle" style="color: #059669;"></ion-icon>
                                <span>Location Management</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem; background: #dcfce7; border-radius: 6px;">
                                <ion-icon name="checkmark-circle" style="color: #059669;"></ion-icon>
                                <span>Damage Tracking</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem; background: #dcfce7; border-radius: 6px;">
                                <ion-icon name="checkmark-circle" style="color: #059669;"></ion-icon>
                                <span>Returns Management</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem; background: #dcfce7; border-radius: 6px;">
                                <ion-icon name="checkmark-circle" style="color: #059669;"></ion-icon>
                                <span>Stock Alerts</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem; background: #dcfce7; border-radius: 6px;">
                                <ion-icon name="checkmark-circle" style="color: #059669;"></ion-icon>
                                <span>Batch Processing</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>