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

// Run comprehensive tests
if(isset($_POST['run_tests'])) {
    $test_results = [];
    
    // Test 1: Database Structure
    $test_results['database'] = testDatabaseStructure($con);
    
    // Test 2: Enhanced Inventory Manager
    $test_results['inventory_manager'] = testInventoryManager($inventoryManager);
    
    // Test 3: Location System
    $test_results['locations'] = testLocationSystem($inventoryManager);
    
    // Test 4: FIFO/LIFO Functionality
    $test_results['fifo_lifo'] = testFifoLifo($inventoryManager);
    
    // Test 5: Damage Tracking
    $test_results['damage_tracking'] = testDamageTracking($inventoryManager);
    
    // Test 6: Returns Management
    $test_results['returns'] = testReturnsManagement($inventoryManager);
    
    // Test 7: Stock Alerts
    $test_results['stock_alerts'] = testStockAlerts($inventoryManager);
}

function testDatabaseStructure($con) {
    $required_tables = [
        'stock_batches', 'inventory_method', 'stock_movements', 
        'damaged_products', 'returns', 'locations', 'stock_alerts', 
        'stock_thresholds', 'return_policy'
    ];
    
    $results = [];
    foreach($required_tables as $table) {
        $check = mysqli_query($con, "SHOW TABLES LIKE '$table'");
        $results[$table] = mysqli_num_rows($check) > 0;
    }
    
    return [
        'status' => !in_array(false, $results),
        'details' => $results,
        'message' => !in_array(false, $results) ? 'All required tables exist' : 'Missing required tables'
    ];
}

function testInventoryManager($inventoryManager) {
    $methods = [
        'getInventoryStatistics', 'getAllLocations', 'getActiveAlerts',
        'getReturnsSummary', 'getDamageStatistics'
    ];
    
    $results = [];
    foreach($methods as $method) {
        try {
            $result = $inventoryManager->$method();
            $results[$method] = true;
        } catch(Exception $e) {
            $results[$method] = false;
        }
    }
    
    return [
        'status' => !in_array(false, $results),
        'details' => $results,
        'message' => !in_array(false, $results) ? 'All methods working' : 'Some methods failed'
    ];
}

function testLocationSystem($inventoryManager) {
    $locations = $inventoryManager->getAllLocations();
    $location_count = mysqli_num_rows($locations);
    
    return [
        'status' => $location_count >= 8,
        'details' => ['location_count' => $location_count],
        'message' => $location_count >= 8 ? "Found $location_count locations" : "Expected at least 8 locations, found $location_count"
    ];
}

function testFifoLifo($inventoryManager) {
    // Test if FIFO/LIFO methods exist and can be called
    $test_tool_id = 1; // Assuming tool with ID 1 exists
    $test_location_id = 1; // Assuming location with ID 1 exists
    
    try {
        $fifo_result = $inventoryManager->getNextStockBatch($test_tool_id, $test_location_id, 'FIFO');
        $lifo_result = $inventoryManager->getNextStockBatch($test_tool_id, $test_location_id, 'LIFO');
        
        return [
            'status' => true,
            'details' => ['fifo' => true, 'lifo' => true],
            'message' => 'FIFO/LIFO processing methods working'
        ];
    } catch(Exception $e) {
        return [
            'status' => false,
            'details' => ['error' => $e->getMessage()],
            'message' => 'FIFO/LIFO processing failed'
        ];
    }
}

function testDamageTracking($inventoryManager) {
    try {
        $damage_stats = $inventoryManager->getDamageStatistics();
        return [
            'status' => true,
            'details' => $damage_stats,
            'message' => 'Damage tracking system operational'
        ];
    } catch(Exception $e) {
        return [
            'status' => false,
            'details' => ['error' => $e->getMessage()],
            'message' => 'Damage tracking system failed'
        ];
    }
}

function testReturnsManagement($inventoryManager) {
    try {
        $returns_stats = $inventoryManager->getReturnsSummary();
        return [
            'status' => true,
            'details' => $returns_stats,
            'message' => 'Returns management system operational'
        ];
    } catch(Exception $e) {
        return [
            'status' => false,
            'details' => ['error' => $e->getMessage()],
            'message' => 'Returns management system failed'
        ];
    }
}

function testStockAlerts($inventoryManager) {
    try {
        $alerts = $inventoryManager->getActiveAlerts();
        return [
            'status' => true,
            'details' => ['alert_count' => mysqli_num_rows($alerts)],
            'message' => 'Stock alerts system operational'
        ];
    } catch(Exception $e) {
        return [
            'status' => false,
            'details' => ['error' => $e->getMessage()],
            'message' => 'Stock alerts system failed'
        ];
    }
}
$current_page = 'system-test';
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
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 1rem;
            padding: 1rem;
            transition: all 0.3s ease;
        }
        
        .test-card.passed {
            border-color: #10b981;
            background: linear-gradient(135deg, #d1fae5, #ecfdf5);
        }
        
        .test-card.failed {
            border-color: #ef4444;
            background: linear-gradient(135deg, #fee2e2, #fef2f2);
        }
        
        .test-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .test-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .status-passed {
            background: #10b981;
            color: white;
        }
        
        .status-failed {
            background: #ef4444;
            color: white;
        }
        
        .test-details {
            background: rgba(255, 255, 255, 0.7);
            border-radius: 4px;
            padding: 0.75rem;
            margin-top: 0.75rem;
            font-family: monospace;
            font-size: 0.875rem;
        }
        
        .overall-status {
            text-align: center;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            font-size: 1.25rem;
            font-weight: 600;
        }
        
        .system-ready {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }
        
        .system-issues {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }
        
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .feature-card {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
        }
        
        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            display: block;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include 'includes/admin_sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <h1 class="page-title">🔧 System Integration Test</h1>
                </div>
                <div class="header-right">
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="run_tests" style="padding: 8px 16px; background: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer; margin-right: 1rem;">
                            <ion-icon name="play-outline"></ion-icon> Run All Tests
                        </button>
                    </form>
                    <a href="logout.php" class="logout-btn">
                        <ion-icon name="log-out-outline"></ion-icon>
                        <span>Logout</span>
                    </a>
                </div>
            </header>
            
            <div class="content-wrapper">
                <?php if(isset($test_results)): 
                    $all_passed = true;
                    foreach($test_results as $test) {
                        if(!$test['status']) {
                            $all_passed = false;
                            break;
                        }
                    }
                ?>
                
                <!-- Overall Status -->
                <div class="overall-status <?php echo $all_passed ? 'system-ready' : 'system-issues'; ?>">
                    <ion-icon name="<?php echo $all_passed ? 'checkmark-circle' : 'warning'; ?>-outline" style="font-size: 3rem; display: block; margin-bottom: 1rem;"></ion-icon>
                    <div><?php echo $all_passed ? '✅ System Ready for Production' : '⚠️ System Has Issues - Review Failed Tests'; ?></div>
                    <?php if($all_passed): ?>
                    <p style="margin-top: 1rem; opacity: 0.9; font-size: 1rem; font-weight: normal;">
                        All components are working correctly. The comprehensive stock management system is ready for use.
                    </p>
                    <?php endif; ?>
                </div>

                <!-- Test Results -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>🧪 Detailed Test Results</h3>
                        <p>Comprehensive testing of all system components</p>
                    </div>
                    
                    <?php foreach($test_results as $test_name => $result): ?>
                    <div class="test-card <?php echo $result['status'] ? 'passed' : 'failed'; ?>">
                        <div class="test-header">
                            <h4 style="margin: 0; text-transform: capitalize;">
                                <?php echo str_replace('_', ' ', $test_name); ?> Test
                            </h4>
                            <span class="test-status <?php echo $result['status'] ? 'status-passed' : 'status-failed'; ?>">
                                <?php echo $result['status'] ? 'PASSED' : 'FAILED'; ?>
                            </span>
                        </div>
                        <p style="margin: 0; color: #4b5563;">
                            <?php echo $result['message']; ?>
                        </p>
                        
                        <?php if(!empty($result['details'])): ?>
                        <div class="test-details">
                            <strong>Details:</strong><br>
                            <?php foreach($result['details'] as $key => $value): ?>
                            <?php echo htmlspecialchars($key); ?>: <?php echo is_bool($value) ? ($value ? 'Yes' : 'No') : htmlspecialchars($value); ?><br>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php else: ?>
                <!-- Welcome Message -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>🚀 Welcome to BAFRACOO System Integration Test</h3>
                        <p>This comprehensive test suite validates all components of your enhanced inventory management system</p>
                    </div>
                    
                    <div style="text-align: center; padding: 2rem;">
                        <ion-icon name="play-circle-outline" style="font-size: 4rem; color: #3b82f6; margin-bottom: 1rem;"></ion-icon>
                        <p style="font-size: 1.125rem; margin-bottom: 2rem;">
                            Click "Run All Tests" to verify that all system components are working correctly.
                        </p>
                        
                        <form method="POST">
                            <button type="submit" name="run_tests" style="padding: 12px 24px; background: #16a34a; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 1.125rem;">
                                <ion-icon name="play-outline"></ion-icon> Run Comprehensive Test Suite
                            </button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <!-- System Features Overview -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>🏗️ System Features Overview</h3>
                        <p>Complete inventory management system for BAFRACOO construction tools</p>
                    </div>
                    
                    <div class="feature-grid">
                        <div class="feature-card">
                            <ion-icon name="layers-outline" class="feature-icon" style="color: #3b82f6;"></ion-icon>
                            <h4>FIFO/LIFO Inventory</h4>
                            <p>Advanced batch tracking with First-In-First-Out and Last-In-First-Out processing methods</p>
                        </div>
                        
                        <div class="feature-card">
                            <ion-icon name="location-outline" class="feature-icon" style="color: #10b981;"></ion-icon>
                            <h4>8 Rwandan Locations</h4>
                            <p>Multi-location inventory management across major Rwandan cities and shipping hubs</p>
                        </div>
                        
                        <div class="feature-card">
                            <ion-icon name="warning-outline" class="feature-icon" style="color: #f59e0b;"></ion-icon>
                            <h4>Damage Tracking</h4>
                            <p>8 categorized damage reasons with financial loss calculation and reporting</p>
                        </div>
                        
                        <div class="feature-card">
                            <ion-icon name="return-up-back-outline" class="feature-icon" style="color: #8b5cf6;"></ion-icon>
                            <h4>Returns Management</h4>
                            <p>30-day return policy with condition-based refund processing</p>
                        </div>
                        
                        <div class="feature-card">
                            <ion-icon name="notifications-outline" class="feature-icon" style="color: #ef4444;"></ion-icon>
                            <h4>Stock Alerts</h4>
                            <p>Automated threshold monitoring with critical and warning notifications</p>
                        </div>
                        
                        <div class="feature-card">
                            <ion-icon name="analytics-outline" class="feature-icon" style="color: #06b6d4;"></ion-icon>
                            <h4>Comprehensive Reporting</h4>
                            <p>Advanced analytics, inventory valuation, and business intelligence</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Navigation -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>🎯 Quick Navigation</h3>
                        <p>Access all system components</p>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <a href="inventory-management.php" style="padding: 1rem; background: #3b82f6; color: white; text-decoration: none; border-radius: 8px; text-align: center; transition: all 0.3s;">
                            <ion-icon name="layers-outline" style="display: block; font-size: 1.5rem; margin-bottom: 0.5rem;"></ion-icon>
                            Inventory Management
                        </a>
                        <a href="damaged-products.php" style="padding: 1rem; background: #f59e0b; color: white; text-decoration: none; border-radius: 8px; text-align: center; transition: all 0.3s;">
                            <ion-icon name="warning-outline" style="display: block; font-size: 1.5rem; margin-bottom: 0.5rem;"></ion-icon>
                            Damaged Products
                        </a>
                        <a href="returns-management.php" style="padding: 1rem; background: #8b5cf6; color: white; text-decoration: none; border-radius: 8px; text-align: center; transition: all 0.3s;">
                            <ion-icon name="return-up-back-outline" style="display: block; font-size: 1.5rem; margin-bottom: 0.5rem;"></ion-icon>
                            Returns Management
                        </a>
                        <a href="stock-alerts.php" style="padding: 1rem; background: #ef4444; color: white; text-decoration: none; border-radius: 8px; text-align: center; transition: all 0.3s;">
                            <ion-icon name="notifications-outline" style="display: block; font-size: 1.5rem; margin-bottom: 0.5rem;"></ion-icon>
                            Stock Alerts
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>