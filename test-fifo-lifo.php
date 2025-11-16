<?php
require "connection.php";
require "InventoryManager.php";

// Initialize Inventory Manager
$inventoryManager = new InventoryManager($con);

// Test different scenarios
echo "<h1>FIFO/LIFO System Testing</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .test-section { background: #f8f9fa; padding: 15px; margin: 15px 0; border-radius: 8px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #e9ecef; }
    .fifo { background-color: #e3f2fd; }
    .lifo { background-color: #f3e5f5; }
</style>";

echo "<div class='test-section'>";
echo "<h2>📊 Current System Status</h2>";

// Get all tools and their current status
$tools_query = "SELECT * FROM tool ORDER BY id";
$tools_result = mysqli_query($con, $tools_query);

echo "<table>";
echo "<tr><th>Tool ID</th><th>Tool Name</th><th>Available Stock</th><th>Inventory Method</th><th>Original Stock</th><th>Status</th></tr>";

while($tool = mysqli_fetch_array($tools_result)) {
    $tool_id = $tool['id'];
    $available_stock = $inventoryManager->getAvailableStock($tool_id);
    $method = $inventoryManager->getInventoryMethod($tool_id);
    
    $status_class = $available_stock > 0 ? 'success' : 'error';
    $method_class = $method === 'FIFO' ? 'fifo' : 'lifo';
    
    echo "<tr class='$method_class'>";
    echo "<td>{$tool['id']}</td>";
    echo "<td>{$tool['u_toolname']}</td>";
    echo "<td class='$status_class'>{$available_stock} units</td>";
    echo "<td><strong>{$method}</strong></td>";
    echo "<td>{$tool['u_itemsnumber']} units</td>";
    echo "<td>" . ($available_stock > 0 ? '✅ In Stock' : '❌ Out of Stock') . "</td>";
    echo "</tr>";
}
echo "</table>";
echo "</div>";

// Show stock batches
echo "<div class='test-section'>";
echo "<h2>📦 Stock Batches Details</h2>";

$batches_query = "SELECT sb.*, t.u_toolname, im.method 
                  FROM stock_batches sb 
                  JOIN tool t ON sb.tool_id = t.id 
                  LEFT JOIN inventory_method im ON sb.tool_id = im.tool_id 
                  ORDER BY sb.tool_id, sb.batch_date";
$batches_result = mysqli_query($con, $batches_query);

echo "<table>";
echo "<tr><th>Tool</th><th>Batch Number</th><th>Received</th><th>Remaining</th><th>Purchase Price</th><th>Batch Date</th><th>Method</th></tr>";

while($batch = mysqli_fetch_array($batches_result)) {
    $method_class = $batch['method'] === 'FIFO' ? 'fifo' : 'lifo';
    $status_class = $batch['quantity_remaining'] > 0 ? 'success' : 'error';
    
    echo "<tr class='$method_class'>";
    echo "<td>{$batch['u_toolname']}</td>";
    echo "<td>{$batch['batch_number']}</td>";
    echo "<td>{$batch['quantity_received']}</td>";
    echo "<td class='$status_class'>{$batch['quantity_remaining']}</td>";
    echo "<td>RWF " . number_format($batch['purchase_price'], 2) . "</td>";
    echo "<td>" . date('M d, Y', strtotime($batch['batch_date'])) . "</td>";
    echo "<td><strong>{$batch['method']}</strong></td>";
    echo "</tr>";
}
echo "</table>";
echo "</div>";

// Test simulation section
echo "<div class='test-section'>";
echo "<h2>🧪 FIFO/LIFO Test Simulation</h2>";
echo "<p>Let's simulate what happens when we process orders using different methods:</p>";

// Get first tool for testing
$first_tool_query = "SELECT * FROM tool ORDER BY id LIMIT 1";
$first_tool = mysqli_fetch_array(mysqli_query($con, $first_tool_query));
$test_tool_id = $first_tool['id'];

echo "<h3>Testing with Tool: {$first_tool['u_toolname']} (ID: {$test_tool_id})</h3>";

// Show current batches for this tool
echo "<h4>Current Batches for this tool:</h4>";
$tool_batches = $inventoryManager->getBatchesForSale($test_tool_id, 'FIFO');
echo "<table>";
echo "<tr><th>Batch</th><th>Available</th><th>Purchase Price</th><th>Date</th></tr>";
$fifo_batches = [];
mysqli_data_seek($tool_batches, 0);
while($batch = mysqli_fetch_array($tool_batches)) {
    $fifo_batches[] = $batch;
    echo "<tr>";
    echo "<td>{$batch['batch_number']}</td>";
    echo "<td>{$batch['quantity_remaining']}</td>";
    echo "<td>RWF " . number_format($batch['purchase_price'], 2) . "</td>";
    echo "<td>" . date('M d, Y', strtotime($batch['batch_date'])) . "</td>";
    echo "</tr>";
}
echo "</table>";

// Simulate FIFO vs LIFO order processing
if (!empty($fifo_batches)) {
    $available_stock = $inventoryManager->getAvailableStock($test_tool_id);
    $test_quantity = min(5, floor($available_stock * 0.5)); // Test with small quantity
    
    if ($test_quantity > 0) {
        echo "<h4>Simulation: Ordering $test_quantity units</h4>";
        
        // Show FIFO order
        echo "<h5>🔄 FIFO Order (Oldest First):</h5>";
        $fifo_batches_sorted = $inventoryManager->getBatchesForSale($test_tool_id, 'FIFO');
        echo "<table>";
        echo "<tr><th>Batch Order</th><th>Batch Date</th><th>Available</th><th>Would Use</th></tr>";
        $remaining_need = $test_quantity;
        $batch_count = 0;
        mysqli_data_seek($fifo_batches_sorted, 0);
        while($batch = mysqli_fetch_array($fifo_batches_sorted) && $remaining_need > 0) {
            $batch_count++;
            $would_use = min($remaining_need, $batch['quantity_remaining']);
            $remaining_need -= $would_use;
            echo "<tr class='fifo'>";
            echo "<td>#{$batch_count} (Oldest)</td>";
            echo "<td>" . date('M d, Y', strtotime($batch['batch_date'])) . "</td>";
            echo "<td>{$batch['quantity_remaining']}</td>";
            echo "<td class='success'>{$would_use} units</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Show LIFO order
        echo "<h5>🔄 LIFO Order (Newest First):</h5>";
        $lifo_batches_sorted = $inventoryManager->getBatchesForSale($test_tool_id, 'LIFO');
        echo "<table>";
        echo "<tr><th>Batch Order</th><th>Batch Date</th><th>Available</th><th>Would Use</th></tr>";
        $remaining_need = $test_quantity;
        $batch_count = 0;
        mysqli_data_seek($lifo_batches_sorted, 0);
        while($batch = mysqli_fetch_array($lifo_batches_sorted) && $remaining_need > 0) {
            $batch_count++;
            $would_use = min($remaining_need, $batch['quantity_remaining']);
            $remaining_need -= $would_use;
            echo "<tr class='lifo'>";
            echo "<td>#{$batch_count} (Newest)</td>";
            echo "<td>" . date('M d, Y', strtotime($batch['batch_date'])) . "</td>";
            echo "<td>{$batch['quantity_remaining']}</td>";
            echo "<td class='success'>{$would_use} units</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>Not enough stock to simulate ordering.</p>";
    }
} else {
    echo "<p class='error'>No batches available for testing.</p>";
}

echo "</div>";

// Method change testing
echo "<div class='test-section'>";
echo "<h2>⚙️ Change Inventory Method</h2>";
echo "<p>You can change between FIFO and LIFO for any tool:</p>";

if (isset($_POST['change_method'])) {
    $tool_id = (int)$_POST['tool_id'];
    $new_method = $_POST['method'];
    $result = $inventoryManager->setInventoryMethod($tool_id, $new_method);
    if ($result) {
        echo "<p class='success'>✅ Successfully changed method for Tool ID $tool_id to $new_method</p>";
    } else {
        echo "<p class='error'>❌ Failed to change method</p>";
    }
}

echo "<form method='POST'>";
echo "<label>Select Tool: </label>";
echo "<select name='tool_id' required>";
$tools_query = "SELECT * FROM tool ORDER BY id";
$tools_result = mysqli_query($con, $tools_query);
while($tool = mysqli_fetch_array($tools_result)) {
    $current_method = $inventoryManager->getInventoryMethod($tool['id']);
    echo "<option value='{$tool['id']}'>{$tool['u_toolname']} (Currently: $current_method)</option>";
}
echo "</select>";
echo " <label>New Method: </label>";
echo "<select name='method' required>";
echo "<option value='FIFO'>FIFO (First In, First Out)</option>";
echo "<option value='LIFO'>LIFO (Last In, First Out)</option>";
echo "</select>";
echo " <button type='submit' name='change_method'>Change Method</button>";
echo "</form>";
echo "</div>";

// Recent stock movements
echo "<div class='test-section'>";
echo "<h2>📈 Recent Stock Movements</h2>";
$movements_query = "SELECT sm.*, sb.batch_number, t.u_toolname 
                    FROM stock_movements sm 
                    JOIN stock_batches sb ON sm.batch_id = sb.id 
                    JOIN tool t ON sb.tool_id = t.id 
                    ORDER BY sm.created_at DESC 
                    LIMIT 10";
$movements_result = mysqli_query($con, $movements_query);

echo "<table>";
echo "<tr><th>Date</th><th>Tool</th><th>Batch</th><th>Type</th><th>Quantity</th><th>Reference</th></tr>";
while($movement = mysqli_fetch_array($movements_result)) {
    $type_class = $movement['movement_type'] === 'IN' ? 'success' : 'error';
    echo "<tr>";
    echo "<td>" . date('M d, Y H:i', strtotime($movement['created_at'])) . "</td>";
    echo "<td>{$movement['u_toolname']}</td>";
    echo "<td>{$movement['batch_number']}</td>";
    echo "<td class='$type_class'>{$movement['movement_type']}</td>";
    echo "<td>{$movement['quantity']}</td>";
    echo "<td>{$movement['reference']}</td>";
    echo "</tr>";
}
echo "</table>";
echo "</div>";

echo "<div class='test-section'>";
echo "<h2>🚀 Next Steps</h2>";
echo "<ol>";
echo "<li><strong>Test Frontend:</strong> Go to <a href='USERS/stock.php' target='_blank'>USERS/stock.php</a> and try placing orders</li>";
echo "<li><strong>Admin Interface:</strong> Go to <a href='inventory-management.php' target='_blank'>inventory-management.php</a> to manage inventory</li>";
echo "<li><strong>Change Methods:</strong> Use the form above to switch between FIFO and LIFO</li>";
echo "<li><strong>Add Stock:</strong> Use the admin interface to add new stock batches</li>";
echo "<li><strong>Monitor Orders:</strong> Check how orders are processed in the database</li>";
echo "</ol>";
echo "</div>";
?>