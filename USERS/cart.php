<?php
/**
 * Shopping Cart Page
 * Feature 3: Ordering Multiple Items (Cart)
 * Allows users to view, modify, and checkout their shopping cart
 */
require "connection.php";
require "../EnhancedInventoryManager.php";

// Check if user is logged in
if (empty($_SESSION["id"])) {
    header('location:loginuser.php');
    exit();
}

$id = $_SESSION["id"];
$check = mysqli_query($con, "SELECT * FROM `user` WHERE id=$id");
$row = mysqli_fetch_array($check);

if (!$row) {
    session_destroy();
    header('location:loginuser.php');
    exit();
}

$inventoryManager = new EnhancedInventoryManager($con);

// Handle cart actions
$message = '';
$message_type = '';

// Get or create active cart
function getActiveCart($con, $user_id) {
    $result = mysqli_query($con, "SELECT id FROM cart WHERE user_id = $user_id AND status = 'ACTIVE' LIMIT 1");
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result)['id'];
    }
    return null;
}

// Handle Remove Item
if (isset($_POST['remove_item'])) {
    $cart_item_id = (int)$_POST['cart_item_id'];
    $cart_id = getActiveCart($con, $id);
    
    if ($cart_id) {
        $delete = mysqli_query($con, "DELETE FROM cart_items WHERE id = $cart_item_id AND cart_id = $cart_id");
        if ($delete) {
            $message = 'Item removed from cart';
            $message_type = 'success';
        }
    }
}

// Handle Update Quantity
if (isset($_POST['update_quantity'])) {
    $cart_item_id = (int)$_POST['cart_item_id'];
    $new_quantity = (int)$_POST['quantity'];
    $tool_id = (int)$_POST['tool_id'];
    $cart_id = getActiveCart($con, $id);
    if ($cart_id && $new_quantity > 0) {
        // Aggregate available stock for all rows with same tool name
        $tool_name_query = mysqli_query($con, "SELECT tool_name FROM cart_items WHERE id = $cart_item_id");
        $tool_name = mysqli_fetch_assoc($tool_name_query)['tool_name'];
        $agg_query = mysqli_query($con, "SELECT SUM(u_itemsnumber) as total_stock FROM tool WHERE u_toolname = '" . mysqli_real_escape_string($con, $tool_name) . "'");
        $available = (int)mysqli_fetch_assoc($agg_query)['total_stock'];
        if ($new_quantity > $available) {
            $message = "Cannot set quantity to $new_quantity. Only $available available.";
            $message_type = 'error';
        } else {
            // Get average price for all rows with this tool name
            $price_query = mysqli_query($con, "SELECT ROUND(AVG(u_price)) as avg_price FROM tool WHERE u_toolname = '" . mysqli_real_escape_string($con, $tool_name) . "'");
            $current_price = (float)mysqli_fetch_assoc($price_query)['avg_price'];
            $update = mysqli_query($con, "UPDATE cart_items SET quantity = $new_quantity, unit_price = $current_price WHERE id = $cart_item_id AND cart_id = $cart_id");
            if ($update) {
                $message = 'Quantity updated';
                $message_type = 'success';
            }
        }
    }
}

// Handle Clear Cart
if (isset($_POST['clear_cart'])) {
    $cart_id = getActiveCart($con, $id);
    if ($cart_id) {
        mysqli_query($con, "DELETE FROM cart_items WHERE cart_id = $cart_id");
        $message = 'Cart cleared';
        $message_type = 'success';
    }
}

// Handle Checkout - Create Order from Cart
if (isset($_POST['checkout'])) {
    $cart_id = getActiveCart($con, $id);
    
    if ($cart_id) {
        // Get cart items
        $cart_items = mysqli_query($con, "SELECT ci.*, t.u_itemsnumber as available_stock 
                                          FROM cart_items ci 
                                          JOIN tool t ON ci.tool_id = t.id 
                                          WHERE ci.cart_id = $cart_id");
        
        $items_valid = true;
        $validation_errors = [];
        $grand_total = 0;
        $items_count = 0;
        
        // Validate all items have enough stock
        while ($item = mysqli_fetch_assoc($cart_items)) {
            if ($item['quantity'] > $item['available_stock']) {
                $items_valid = false;
                $validation_errors[] = $item['tool_name'] . " only has " . $item['available_stock'] . " available";
            }
            $grand_total += $item['total_price'];
            $items_count++;
        }
        
        if (!$items_valid) {
            $message = "Stock issues: " . implode(", ", $validation_errors);
            $message_type = 'error';
        } elseif ($items_count == 0) {
            $message = 'Your cart is empty';
            $message_type = 'error';
        } else {
            // Create the main order
            $order_date = date('Y-m-d');
            $order_description = "Multi-item order from cart #$cart_id";
            
            $insert_order = mysqli_query($con, "INSERT INTO `order` 
                (user_id, tool_id, u_toolname, u_itemsnumber, u_type, u_tooldescription, u_date, u_price, u_totalprice, status)
                VALUES ($id, NULL, 'Cart Order ($items_count items)', $items_count, 'Cart Order', '$order_description', '$order_date', 0, $grand_total, 'Pending Payment')");
            
            if ($insert_order) {
                $order_id = mysqli_insert_id($con);
                
                // Create order items
                mysqli_data_seek($cart_items, 0); // Reset cursor
                $cart_items = mysqli_query($con, "SELECT * FROM cart_items WHERE cart_id = $cart_id");
                
                while ($item = mysqli_fetch_assoc($cart_items)) {
                    $tool_id = $item['tool_id'];
                    $tool_name = mysqli_real_escape_string($con, $item['tool_name']);
                    $quantity = $item['quantity'];
                    $unit_price = $item['unit_price'];
                    $total_price = $item['total_price'];
                    
                    mysqli_query($con, "INSERT INTO order_items (order_id, tool_id, tool_name, quantity, unit_price, total_price)
                                        VALUES ($order_id, $tool_id, '$tool_name', $quantity, $unit_price, $total_price)");
                }
                
                // Mark cart as checked out
                mysqli_query($con, "UPDATE cart SET status = 'CHECKED_OUT' WHERE id = $cart_id");
                
                // Redirect to payment
                header("Location: pay.php?o_id=$order_id&cart=1");
                exit();
            } else {
                $message = 'Error creating order: ' . mysqli_error($con);
                $message_type = 'error';
            }
        }
    }
}

// Get current cart items
$cart_id = getActiveCart($con, $id);
$cart_items = [];
$cart_total = 0;
$cart_count = 0;

if ($cart_id) {
    // Aggregate available stock for each cart item by tool name
    $items_query = mysqli_query($con, "
        SELECT ci.*, 
            (SELECT SUM(u_itemsnumber) FROM tool WHERE u_toolname = ci.tool_name) as available_stock,
            t.image_url, t.u_type,
            COALESCE(im.method, 'FIFO') as inventory_method
        FROM cart_items ci 
        JOIN tool t ON ci.tool_id = t.id 
        LEFT JOIN inventory_method im ON t.id = im.tool_id
        WHERE ci.cart_id = $cart_id
        ORDER BY ci.added_at DESC
    ");
    if ($items_query) {
        while ($item = mysqli_fetch_assoc($items_query)) {
            $cart_items[] = $item;
            $cart_total += $item['total_price'];
            $cart_count += $item['quantity'];
        }
    }
}

// Get cart count for badge
$badge_count = count($cart_items);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../CSS/modern-dashboard.css">
    <link rel="stylesheet" href="../CSS/modern-tables.css">
    <link rel="shortcut icon" href="../images/Capture.JPG" type="image/x-icon">
    <script src="https://kit.fontawesome.com/14ff3ea278.js" crossorigin="anonymous"></script>
    <title>BAFRACOO - Shopping Cart</title>
    <style>
        .cart-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-xl);
            flex-wrap: wrap;
            gap: var(--spacing-md);
        }
        .cart-title {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
        }
        .cart-title h2 {
            margin: 0;
            font-size: 1.5rem;
            color: var(--gray-900);
        }
        .cart-badge {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        .cart-item {
            background: white;
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            margin-bottom: var(--spacing-md);
            display: grid;
            grid-template-columns: 100px 1fr auto auto;
            gap: var(--spacing-lg);
            align-items: center;
            box-shadow: var(--shadow-sm);
            transition: all 0.2s;
        }
        .cart-item:hover {
            box-shadow: var(--shadow-md);
        }
        .cart-item-image {
            width: 100px;
            height: 100px;
            border-radius: var(--radius-md);
            object-fit: cover;
            background: var(--gray-100);
        }
        .cart-item-image-placeholder {
            width: 100px;
            height: 100px;
            border-radius: var(--radius-md);
            background: linear-gradient(135deg, var(--primary-light), #e0e7ff);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            font-size: 2rem;
        }
        .cart-item-details h3 {
            margin: 0 0 var(--spacing-xs) 0;
            font-size: 1.1rem;
            color: var(--gray-900);
        }
        .cart-item-meta {
            display: flex;
            gap: var(--spacing-md);
            flex-wrap: wrap;
            margin-bottom: var(--spacing-sm);
        }
        .meta-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .meta-badge.type {
            background: var(--gray-100);
            color: var(--gray-700);
        }
        .meta-badge.fifo {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }
        .meta-badge.lifo {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            color: white;
        }
        .meta-badge.stock {
            background: #dcfce7;
            color: #166534;
        }
        .meta-badge.low-stock {
            background: #fef3c7;
            color: #92400e;
        }
        .cart-item-price {
            text-align: center;
        }
        .unit-price {
            font-size: 0.875rem;
            color: var(--gray-500);
        }
        .total-price {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        .cart-item-quantity {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: var(--spacing-sm);
        }
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
        }
        .qty-btn {
            width: 36px;
            height: 36px;
            border: 1px solid var(--gray-300);
            background: white;
            border-radius: var(--radius-md);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        .qty-btn:hover {
            background: var(--gray-100);
            border-color: var(--primary-color);
        }
        .qty-input {
            width: 60px;
            height: 36px;
            text-align: center;
            border: 1px solid var(--gray-300);
            border-radius: var(--radius-md);
            font-weight: 600;
        }
        .cart-item-actions {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-sm);
        }
        .btn-remove {
            padding: 8px 16px;
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
            transition: all 0.2s;
        }
        .btn-remove:hover {
            background: #fee2e2;
            border-color: #dc2626;
        }
        .cart-summary {
            background: white;
            border-radius: var(--radius-lg);
            padding: var(--spacing-xl);
            box-shadow: var(--shadow-md);
            position: sticky;
            top: var(--spacing-xl);
        }
        .summary-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: var(--spacing-lg);
            padding-bottom: var(--spacing-md);
            border-bottom: 1px solid var(--gray-200);
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: var(--spacing-sm);
            color: var(--gray-600);
        }
        .summary-row.total {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-top: var(--spacing-md);
            padding-top: var(--spacing-md);
            border-top: 2px solid var(--gray-200);
        }
        .summary-row.total .amount {
            color: var(--primary-color);
        }
        .btn-checkout {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: var(--spacing-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-sm);
            transition: all 0.2s;
        }
        .btn-checkout:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.35);
        }
        .btn-checkout:disabled {
            background: var(--gray-300);
            cursor: not-allowed;
            transform: none;
        }
        .btn-clear {
            width: 100%;
            padding: 12px;
            background: white;
            color: var(--gray-600);
            border: 1px solid var(--gray-300);
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            cursor: pointer;
            margin-top: var(--spacing-md);
            transition: all 0.2s;
        }
        .btn-clear:hover {
            background: var(--gray-50);
            border-color: var(--gray-400);
        }
        .empty-cart {
            text-align: center;
            padding: var(--spacing-xxl);
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
        }
        .empty-cart ion-icon {
            font-size: 4rem;
            color: var(--gray-300);
            margin-bottom: var(--spacing-md);
        }
        .empty-cart h3 {
            color: var(--gray-600);
            margin-bottom: var(--spacing-sm);
        }
        .empty-cart p {
            color: var(--gray-500);
            margin-bottom: var(--spacing-lg);
        }
        .btn-shop {
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-sm);
            padding: 12px 24px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            text-decoration: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-shop:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.35);
        }
        .cart-layout {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: var(--spacing-xl);
        }
        @media (max-width: 992px) {
            .cart-layout {
                grid-template-columns: 1fr;
            }
            .cart-item {
                grid-template-columns: 80px 1fr;
                grid-template-rows: auto auto;
            }
            .cart-item-price,
            .cart-item-actions {
                grid-column: 2;
            }
        }
        @media (max-width: 576px) {
            .cart-item {
                grid-template-columns: 1fr;
                text-align: center;
            }
            .cart-item-image,
            .cart-item-image-placeholder {
                margin: 0 auto;
            }
        }
        .message {
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-lg);
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }
        .message.success {
            background: #dcfce7;
            border: 1px solid #16a34a;
            color: #15803d;
        }
        .message.error {
            background: #fef2f2;
            border: 1px solid #ef4444;
            color: #dc2626;
        }
        .fifo-info {
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
            border: 1px solid #93c5fd;
            border-radius: var(--radius-md);
            padding: var(--spacing-md);
            margin-top: var(--spacing-md);
            font-size: 0.875rem;
            color: #1e40af;
        }
        .fifo-info ion-icon {
            color: #3b82f6;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-logo">
                <img src="../images/Captured.JPG" alt="BAFRACOO Logo">
                <span class="logo-text">BAFRACOO</span>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <h3 class="nav-section-title">Main Menu</h3>
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="userdashboard.php" class="nav-link">
                                <ion-icon name="home-outline" class="nav-icon"></ion-icon>
                                <span class="nav-text">Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="stock.php" class="nav-link">
                                <ion-icon name="cube-outline" class="nav-icon"></ion-icon>
                                <span class="nav-text">Inter Purchases</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="cart.php" class="nav-link active">
                                <ion-icon name="cart-outline" class="nav-icon"></ion-icon>
                                <span class="nav-text">Shopping Cart</span>
                                <?php if($badge_count > 0): ?>
                                <span class="nav-badge"><?php echo $badge_count; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="orders.php" class="nav-link">
                                <ion-icon name="bag-handle-outline" class="nav-icon"></ion-icon>
                                <span class="nav-text">My Orders</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="refund-requests.php" class="nav-link">
                                <ion-icon name="card-outline" class="nav-icon"></ion-icon>
                                <span class="nav-text">Refund Requests</span>
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="nav-section">
                    <h3 class="nav-section-title">Account</h3>
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="userprofile.php" class="nav-link">
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
                            <a href="../website.php" class="nav-link">
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
                        <?php echo strtoupper(substr($row['u_name'] ?? 'U', 0, 2)); ?>
                    </div>
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($row['u_name'] ?? 'User'); ?></div>
                        <div class="user-role">Customer</div>
                    </div>
                </div>
                <a href="logout.php" class="logout-btn">
                    <ion-icon name="log-out-outline"></ion-icon>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- Sidebar Overlay for Mobile -->
        <div class="sidebar-overlay"></div>

        <!-- Main Content -->
        <main class="main-content" style="background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);">
            <!-- Page Banner -->
            <div class="page-banner">
                <h1><ion-icon name="cart-outline"></ion-icon> Shopping Cart</h1>
                <p>Review and manage items in your cart before checkout</p>
            </div>
            
            <div class="content-wrapper">
                <div class="cart-container">
                    <!-- Messages -->
                    <?php if($message): ?>
                    <div class="message <?php echo $message_type; ?>">
                        <ion-icon name="<?php echo $message_type == 'success' ? 'checkmark-circle' : 'alert-circle'; ?>-outline"></ion-icon>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if(count($cart_items) > 0): ?>
                    <!-- Cart Header -->
                    <div class="cart-header">
                        <div class="cart-title">
                            <h2>Your Cart</h2>
                            <span class="cart-badge"><?php echo count($cart_items); ?> item<?php echo count($cart_items) > 1 ? 's' : ''; ?></span>
                        </div>
                        <a href="stock.php" class="btn-shop" style="background: var(--gray-100); color: var(--gray-700);">
                            <ion-icon name="add-outline"></ion-icon>
                            Continue Shopping
                        </a>
                    </div>
                    
                    <div class="cart-layout">
                        <!-- Cart Items -->
                        <div class="cart-items">
                            <?php foreach($cart_items as $item): ?>
                            <div class="cart-item">
                                <!-- Product Image -->
                                <?php if(!empty($item['image_url']) && file_exists('../' . $item['image_url'])): ?>
                                <img src="../<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['tool_name']); ?>" class="cart-item-image">
                                <?php else: ?>
                                <div class="cart-item-image-placeholder">
                                    <ion-icon name="cube-outline"></ion-icon>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Product Details -->
                                <div class="cart-item-details">
                                    <h3><?php echo htmlspecialchars($item['tool_name']); ?></h3>
                                    <div class="cart-item-meta">
                                        <span class="meta-badge type"><?php echo htmlspecialchars($item['u_type']); ?></span>
                                        <span class="meta-badge <?php echo strtolower($item['inventory_method']); ?>">
                                            <ion-icon name="<?php echo $item['inventory_method'] === 'FIFO' ? 'arrow-forward' : 'arrow-back'; ?>-outline"></ion-icon>
                                            <?php echo $item['inventory_method']; ?>
                                        </span>
                                        <?php if($item['available_stock'] <= 10): ?>
                                        <span class="meta-badge low-stock">
                                            <ion-icon name="warning-outline"></ion-icon>
                                            Only <?php echo $item['available_stock']; ?> left
                                        </span>
                                        <?php else: ?>
                                        <span class="meta-badge stock">
                                            <?php echo number_format($item['available_stock']); ?> in stock
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Quantity Controls (Mobile & Desktop) -->
                                    <form method="POST" class="quantity-controls" style="display: inline-flex;">
                                        <input type="hidden" name="cart_item_id" value="<?php echo $item['id']; ?>">
                                        <input type="hidden" name="tool_id" value="<?php echo $item['tool_id']; ?>">
                                        <button type="submit" name="update_quantity" class="qty-btn" onclick="this.form.quantity.value = Math.max(1, parseInt(this.form.quantity.value) - 1);">
                                            <ion-icon name="remove-outline"></ion-icon>
                                        </button>
                                        <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['available_stock']; ?>" class="qty-input" onchange="this.form.submit();">
                                        <button type="submit" name="update_quantity" class="qty-btn" onclick="this.form.quantity.value = Math.min(<?php echo $item['available_stock']; ?>, parseInt(this.form.quantity.value) + 1);">
                                            <ion-icon name="add-outline"></ion-icon>
                                        </button>
                                    </form>
                                </div>
                                
                                <!-- Price -->
                                <div class="cart-item-price">
                                    <div class="unit-price">RWF <?php echo number_format($item['unit_price']); ?> each</div>
                                    <div class="total-price">RWF <?php echo number_format($item['total_price']); ?></div>
                                </div>
                                
                                <!-- Actions -->
                                <div class="cart-item-actions">
                                    <form method="POST">
                                        <input type="hidden" name="cart_item_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" name="remove_item" class="btn-remove">
                                            <ion-icon name="trash-outline"></ion-icon>
                                            Remove
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Cart Summary -->
                        <div class="cart-summary">
                            <h3 class="summary-title">Order Summary</h3>
                            
                            <div class="summary-row">
                                <span>Items (<?php echo $cart_count; ?>)</span>
                                <span>RWF <?php echo number_format($cart_total); ?></span>
                            </div>
                            
                            <div class="summary-row total">
                                <span>Grand Total</span>
                                <span class="amount">RWF <?php echo number_format($cart_total); ?></span>
                            </div>
                            
                            <form method="POST">
                                <button type="submit" name="checkout" class="btn-checkout">
                                    <ion-icon name="card-outline"></ion-icon>
                                    if (isset($_POST['checkout'])) {
                                        $cart_id = getActiveCart($con, $id);
                                        if ($cart_id) {
                                            // Get cart items with aggregated stock
                                            $cart_items = mysqli_query($con, "SELECT ci.*, (SELECT SUM(u_itemsnumber) FROM tool WHERE u_toolname = ci.tool_name) as available_stock FROM cart_items ci WHERE ci.cart_id = $cart_id");
                                            $items_valid = true;
                                            $validation_errors = [];
                                            $grand_total = 0;
                                            $items_count = 0;
                                            // Validate all items have enough stock
                                            while ($item = mysqli_fetch_assoc($cart_items)) {
                                                if ($item['quantity'] > $item['available_stock']) {
                                                    $items_valid = false;
                                                    $validation_errors[] = $item['tool_name'] . " only has " . $item['available_stock'] . " available";
                                                }
                                                $grand_total += $item['total_price'];
                                                $items_count++;
                                            }
                                            if (!$items_valid) {
                                                $message = "Stock issues: " . implode(", ", $validation_errors);
                                                $message_type = 'error';
                                            } elseif ($items_count == 0) {
                                                $message = 'Your cart is empty';
                                                $message_type = 'error';
                                            } else {
                                                // Create the main order
                                                $order_date = date('Y-m-d');
                                                $order_description = "Multi-item order from cart #$cart_id";
                                                $insert_order = mysqli_query($con, "INSERT INTO `order` 
                                                    (user_id, tool_id, u_toolname, u_itemsnumber, u_type, u_tooldescription, u_date, u_price, u_totalprice, status)
                                                    VALUES ($id, NULL, 'Cart Order ($items_count items)', $items_count, 'Cart Order', '$order_description', '$order_date', 0, $grand_total, 'Pending Payment')");
                                                if ($insert_order) {
                                                    $order_id = mysqli_insert_id($con);
                                                    // Create order items
                                                    mysqli_data_seek($cart_items, 0); // Reset cursor
                                                    $cart_items = mysqli_query($con, "SELECT * FROM cart_items WHERE cart_id = $cart_id");
                                                    while ($item = mysqli_fetch_assoc($cart_items)) {
                                                        $tool_id = $item['tool_id'];
                                                        $tool_name = mysqli_real_escape_string($con, $item['tool_name']);
                                                        $quantity = $item['quantity'];
                                                        $unit_price = $item['unit_price'];
                                                        $total_price = $item['total_price'];
                                                        mysqli_query($con, "INSERT INTO order_items (order_id, tool_id, tool_name, quantity, unit_price, total_price)
                                                                            VALUES ($order_id, $tool_id, '$tool_name', $quantity, $unit_price, $total_price)");
                                                    }
                                                    // Mark cart as checked out
                                                    mysqli_query($con, "UPDATE cart SET status = 'CHECKED_OUT' WHERE id = $cart_id");
                                                    // Redirect to payment
                                                    header("Location: pay.php?o_id=$order_id&cart=1");
                                                    exit();
                                                } else {
                                                    $message = 'Error creating order: ' . mysqli_error($con);
                                                    $message_type = 'error';
                                                }
                                            }
                                        }
                                    }
                sidebar.classList.remove('active');
