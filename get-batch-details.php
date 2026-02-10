<?php
require "connection.php";
require "EnhancedInventoryManager.php";

if(!empty($_SESSION["id"]) && isset($_GET['tool_id'])){
    $tool_id = (int)$_GET['tool_id'];
    $inventoryManager = new EnhancedInventoryManager($con);
    
    // Get inventory method for this tool
    $method = $inventoryManager->getInventoryMethod($tool_id);
    
    // Get all batches for this tool
    $query = "SELECT sb.*, COALESCE(im.method, 'FIFO') as inventory_method 
              FROM stock_batches sb 
              LEFT JOIN inventory_method im ON sb.tool_id = im.tool_id 
              WHERE sb.tool_id = ? 
              ORDER BY sb.batch_date " . ($method === 'FIFO' ? 'ASC' : 'DESC');
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "i", $tool_id);
    mysqli_stmt_execute($stmt);
    $batches = mysqli_stmt_get_result($stmt);
    
    // Get tool info (correct column names)
    $toolQuery = "SELECT u_toolname FROM tool WHERE id = ?";
    $toolStmt = mysqli_prepare($con, $toolQuery);
    mysqli_stmt_bind_param($toolStmt, "i", $tool_id);
    mysqli_stmt_execute($toolStmt);
    $toolResult = mysqli_stmt_get_result($toolStmt);
    $toolInfo = mysqli_fetch_assoc($toolResult);
    
    if($batches && mysqli_num_rows($batches) > 0):
        $batchCount = 0;
?>
<div style="margin-bottom: 1rem;">
    <h3 style="margin: 0 0 0.5rem 0; color: #1e293b;">
        <?php echo htmlspecialchars($toolInfo['u_toolname'] ?? 'Tool'); ?> - All Stock Batches
    </h3>
    <div style="display: flex; align-items: center; gap: 1rem;">
        <span style="padding: 4px 12px; background: <?php echo $method === 'FIFO' ? '#3b82f6' : '#8b5cf6'; ?>; color: white; border-radius: 20px; font-size: 0.75rem; font-weight: 600;">
            <?php echo $method; ?> Method
        </span>
        <span style="color: #6b7280; font-size: 0.875rem;">
            <?php echo $method === 'FIFO' ? 'Oldest stock sold first' : 'Newest stock sold first'; ?>
        </span>
    </div>
</div>

<table style="width: 100%; border-collapse: collapse;">
    <thead>
        <tr style="background: linear-gradient(135deg, #3b82f6, #1e40af);">
            <th style="padding: 12px; text-align: left; color: white; font-weight: 600;">Priority</th>
            <th style="padding: 12px; text-align: left; color: white; font-weight: 600;">Batch #</th>
            <th style="padding: 12px; text-align: left; color: white; font-weight: 600;">Received</th>
            <th style="padding: 12px; text-align: left; color: white; font-weight: 600;">Remaining</th>
            <th style="padding: 12px; text-align: left; color: white; font-weight: 600;">Purchase Price</th>
            <th style="padding: 12px; text-align: left; color: white; font-weight: 600;">Date Received</th>
            <th style="padding: 12px; text-align: left; color: white; font-weight: 600;">Supplier</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $firstAvailable = true;
        while($batch = mysqli_fetch_assoc($batches)): 
            $batchCount++;
            $isNextToSell = $firstAvailable && $batch['quantity_remaining'] > 0;
            if ($isNextToSell) $firstAvailable = false;
        ?>
        <tr style="background: <?php echo $isNextToSell ? '#f0fdf4' : ($batchCount % 2 == 0 ? '#f8fafc' : 'white'); ?>; border-left: 4px solid <?php echo $isNextToSell ? '#10b981' : 'transparent'; ?>;">
            <td style="padding: 12px; border-bottom: 1px solid #e2e8f0;">
                <?php if($isNextToSell): ?>
                    <span style="display: flex; align-items: center; gap: 6px; color: #059669; font-weight: 600;">
                        <ion-icon name="arrow-forward-circle" style="font-size: 1.2rem;"></ion-icon>
                        Next to Sell
                    </span>
                <?php elseif($batch['quantity_remaining'] > 0): ?>
                    <span style="color: #6b7280;">#<?php echo $batchCount; ?></span>
                <?php else: ?>
                    <span style="color: #9ca3af; text-decoration: line-through;">Depleted</span>
                <?php endif; ?>
            </td>
            <td style="padding: 12px; border-bottom: 1px solid #e2e8f0; font-weight: 500;">
                <?php echo htmlspecialchars($batch['batch_number']); ?>
            </td>
            <td style="padding: 12px; border-bottom: 1px solid #e2e8f0;">
                <?php echo number_format($batch['quantity_received']); ?>
            </td>
            <td style="padding: 12px; border-bottom: 1px solid #e2e8f0;">
                <span style="padding: 4px 10px; background: <?php echo $batch['quantity_remaining'] > 0 ? '#dcfce7' : '#fee2e2'; ?>; color: <?php echo $batch['quantity_remaining'] > 0 ? '#166534' : '#991b1b'; ?>; border-radius: 20px; font-size: 0.875rem; font-weight: 600;">
                    <?php echo number_format($batch['quantity_remaining']); ?>
                </span>
            </td>
            <td style="padding: 12px; border-bottom: 1px solid #e2e8f0;">
                RWF <?php echo number_format($batch['purchase_price'], 0); ?>
            </td>
            <td style="padding: 12px; border-bottom: 1px solid #e2e8f0;">
                <?php echo date('M d, Y', strtotime($batch['batch_date'])); ?>
            </td>
            <td style="padding: 12px; border-bottom: 1px solid #e2e8f0;">
                <?php echo htmlspecialchars($batch['supplier'] ?? 'N/A'); ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<div style="margin-top: 1.5rem; padding: 1rem; background: linear-gradient(135deg, #f0f9ff, #e0f2fe); border-radius: 8px; border-left: 4px solid #3b82f6;">
    <h4 style="margin: 0 0 0.5rem 0; color: #1e40af; display: flex; align-items: center; gap: 8px;">
        <ion-icon name="information-circle"></ion-icon>
        How <?php echo $method; ?> Works
    </h4>
    <p style="margin: 0; color: #334155; font-size: 0.875rem; line-height: 1.6;">
        <?php if($method === 'FIFO'): ?>
            <strong>First In, First Out:</strong> The oldest batch (earliest date) is sold first. This method is ideal for perishable goods or products that may become obsolete over time. It ensures older inventory is used before newer stock.
        <?php else: ?>
            <strong>Last In, First Out:</strong> The newest batch (latest date) is sold first. This method can be beneficial during inflation periods as it matches recent costs against revenues. However, it may result in older stock remaining in inventory.
        <?php endif; ?>
    </p>
</div>

<?php else: ?>
<div style="text-align: center; padding: 3rem; color: #6b7280;">
    <ion-icon name="cube-outline" style="font-size: 3rem; color: #d1d5db; margin-bottom: 1rem;"></ion-icon>
    <p style="margin: 0; font-size: 1rem;">No stock batches found for this tool.</p>
    <p style="margin: 0.5rem 0 0 0; font-size: 0.875rem;">Add stock batches from the Inventory Management page to track FIFO/LIFO.</p>
</div>
<?php endif; ?>

<?php
} else {
    echo '<div style="text-align: center; padding: 2rem; color: #ef4444;">Access denied or invalid request.</div>';
}
?>