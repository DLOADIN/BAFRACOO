<?php
require "connection.php";
require "InventoryManager.php";

if(!empty($_SESSION["id"]) && isset($_GET['tool_id'])){
    $tool_id = (int)$_GET['tool_id'];
    $inventoryManager = new InventoryManager($con);
    
    $batches = $inventoryManager->getStockSummary($tool_id);
    
    if(mysqli_num_rows($batches) > 0):
?>
<table style="width: 100%; border-collapse: collapse;">
    <thead>
        <tr style="background: #f8fafc;">
            <th style="padding: 8px; text-align: left; border: 1px solid #e2e8f0;">Batch #</th>
            <th style="padding: 8px; text-align: left; border: 1px solid #e2e8f0;">Received</th>
            <th style="padding: 8px; text-align: left; border: 1px solid #e2e8f0;">Remaining</th>
            <th style="padding: 8px; text-align: left; border: 1px solid #e2e8f0;">Purchase Price</th>
            <th style="padding: 8px; text-align: left; border: 1px solid #e2e8f0;">Date</th>
            <th style="padding: 8px; text-align: left; border: 1px solid #e2e8f0;">Supplier</th>
        </tr>
    </thead>
    <tbody>
        <?php while($batch = mysqli_fetch_assoc($batches)): ?>
        <tr>
            <td style="padding: 8px; border: 1px solid #e2e8f0;"><?php echo htmlspecialchars($batch['batch_number']); ?></td>
            <td style="padding: 8px; border: 1px solid #e2e8f0;"><?php echo $batch['quantity_received']; ?></td>
            <td style="padding: 8px; border: 1px solid #e2e8f0;">
                <span style="padding: 2px 6px; background: <?php echo $batch['quantity_remaining'] > 0 ? '#10b981' : '#ef4444'; ?>; color: white; border-radius: 4px; font-size: 0.75rem;">
                    <?php echo $batch['quantity_remaining']; ?>
                </span>
            </td>
            <td style="padding: 8px; border: 1px solid #e2e8f0;">RWF <?php echo number_format($batch['purchase_price'], 2); ?></td>
            <td style="padding: 8px; border: 1px solid #e2e8f0;"><?php echo date('M d, Y', strtotime($batch['batch_date'])); ?></td>
            <td style="padding: 8px; border: 1px solid #e2e8f0;"><?php echo htmlspecialchars($batch['supplier'] ?? 'N/A'); ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<div style="margin-top: 1rem; padding: 1rem; background: #f8fafc; border-radius: 6px;">
    <h4 style="margin: 0 0 0.5rem 0;">Inventory Method: 
        <span style="padding: 4px 8px; background: <?php echo $batch['inventory_method'] === 'FIFO' ? '#3b82f6' : '#8b5cf6'; ?>; color: white; border-radius: 4px; font-size: 0.875rem;">
            <?php echo $batch['inventory_method'] ?? 'FIFO'; ?>
        </span>
    </h4>
    <p style="margin: 0; color: #6b7280; font-size: 0.875rem;">
        <?php if(($batch['inventory_method'] ?? 'FIFO') === 'FIFO'): ?>
            Oldest stock will be sold first (First In, First Out)
        <?php else: ?>
            Newest stock will be sold first (Last In, First Out)
        <?php endif; ?>
    </p>
</div>

<?php else: ?>
<p style="text-align: center; padding: 2rem; color: #6b7280;">No stock batches found for this tool.</p>
<?php endif; ?>

<?php
} else {
    echo "Access denied or invalid request.";
}
?>