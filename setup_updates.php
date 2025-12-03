<?php
// Run this file once to setup all database updates and create necessary directories
// Access: http://localhost/BAFRACOO/setup_updates.php

require "connection.php";

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>BAFRACOO System Update Setup</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f3f4f6; }
        .container { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h1 { color: #111827; margin-bottom: 30px; }
        .log { background: #1f2937; color: #10b981; padding: 20px; border-radius: 8px; font-family: monospace; white-space: pre-wrap; margin-bottom: 20px; }
        .success { color: #10b981; }
        .error { color: #ef4444; }
        .info { color: #60a5fa; }
        .warning { color: #fbbf24; }
        .links { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin-top: 20px; }
        .links a { display: block; padding: 12px 20px; background: #3b82f6; color: white; text-decoration: none; border-radius: 8px; text-align: center; }
        .links a:hover { background: #2563eb; }
        .danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 15px; border-radius: 8px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔧 BAFRACOO System Update Setup</h1>
        <div class='log'>";

$errors = 0;

// 1. Create uploads directory
$upload_dir = 'uploads/tools/';
if (!file_exists($upload_dir)) {
    if (mkdir($upload_dir, 0777, true)) {
        echo "<span class='success'>✅ Created uploads/tools/ directory</span>\n";
    } else {
        echo "<span class='error'>❌ Failed to create uploads/tools/ directory</span>\n";
        $errors++;
    }
} else {
    echo "<span class='info'>✓ uploads/tools/ directory already exists</span>\n";
}

// 2. Create includes directory
$includes_dir = 'includes/';
if (!file_exists($includes_dir)) {
    if (mkdir($includes_dir, 0777, true)) {
        echo "<span class='success'>✅ Created includes/ directory</span>\n";
    } else {
        echo "<span class='error'>❌ Failed to create includes/ directory</span>\n";
        $errors++;
    }
} else {
    echo "<span class='info'>✓ includes/ directory already exists</span>\n";
}

// 3. Add image_url column to tool table
$check_column = mysqli_query($con, "SHOW COLUMNS FROM `tool` LIKE 'image_url'");
if ($check_column && mysqli_num_rows($check_column) == 0) {
    $alter_result = mysqli_query($con, "ALTER TABLE `tool` ADD COLUMN `image_url` VARCHAR(255) DEFAULT NULL");
    if ($alter_result) {
        echo "<span class='success'>✅ Added image_url column to tool table</span>\n";
    } else {
        echo "<span class='error'>❌ Failed to add image_url column: " . mysqli_error($con) . "</span>\n";
        $errors++;
    }
} else {
    echo "<span class='info'>✓ image_url column already exists in tool table</span>\n";
}

// 4. Create damaged_goods table
$create_damaged = mysqli_query($con, "CREATE TABLE IF NOT EXISTS `damaged_goods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tool_id` int(11) NOT NULL,
  `tool_name` varchar(80) NOT NULL,
  `quantity_removed` int(11) NOT NULL,
  `damage_reason` varchar(255) NOT NULL,
  `damage_date` datetime DEFAULT current_timestamp(),
  `reported_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `original_value` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tool_id` (`tool_id`),
  KEY `damage_date` (`damage_date`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci");

if ($create_damaged) {
    echo "<span class='success'>✅ Created/verified damaged_goods table</span>\n";
} else {
    echo "<span class='error'>❌ Failed to create damaged_goods table: " . mysqli_error($con) . "</span>\n";
    $errors++;
}

// 5. Create returned_stock table
$create_returned = mysqli_query($con, "CREATE TABLE IF NOT EXISTS `returned_stock` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tool_id` int(11) NOT NULL,
  `tool_name` varchar(80) NOT NULL,
  `quantity_returned` int(11) NOT NULL,
  `return_reason` varchar(255) NOT NULL,
  `return_date` datetime DEFAULT current_timestamp(),
  `condition_status` enum('GOOD','DAMAGED','UNUSABLE') DEFAULT 'GOOD',
  `processed_by` int(11) DEFAULT NULL,
  `restock_status` enum('PENDING','RESTOCKED','WRITTEN_OFF') DEFAULT 'PENDING',
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tool_id` (`tool_id`),
  KEY `return_date` (`return_date`),
  KEY `restock_status` (`restock_status`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci");

if ($create_returned) {
    echo "<span class='success'>✅ Created/verified returned_stock table</span>\n";
} else {
    echo "<span class='error'>❌ Failed to create returned_stock table: " . mysqli_error($con) . "</span>\n";
    $errors++;
}

echo "\n========================================\n";
if ($errors == 0) {
    echo "<span class='success'>✅ Setup completed successfully!</span>\n";
} else {
    echo "<span class='warning'>⚠️ Setup completed with $errors error(s)</span>\n";
}
echo "========================================";

echo "</div>";

echo "<h2>📋 What's New</h2>
<ul>
    <li><strong>Image Upload:</strong> You can now add images to tools in Add Tool page</li>
    <li><strong>Damage Control:</strong> Report damaged items and remove them from stock</li>
    <li><strong>Returned Stock:</strong> Track and manage returned items - restock or write off</li>
    <li><strong>Consistent Sidebar:</strong> All admin pages now have the same navigation</li>
</ul>";

echo "<h2>🔗 Quick Links</h2>
<div class='links'>
    <a href='admindashboard.php'>Dashboard</a>
    <a href='addorder.php'>Add Tool (with Image)</a>
    <a href='damaged-products.php'>Damage Control</a>
    <a href='returned-stock.php'>Returned Stock</a>
    <a href='stock.php'>Inventory</a>
</div>";

echo "<div class='danger'>
    <strong>⚠️ Security Warning:</strong> Delete this file (setup_updates.php) after running it!
</div>";

echo "</div></body></html>";
?>
