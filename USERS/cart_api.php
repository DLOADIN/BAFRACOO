<?php
/**
 * Cart API - AJAX endpoints for cart operations
 * Feature 3: Ordering Multiple Items (Cart)
 */
header('Content-Type: application/json');
require "connection.php";

// Check if user is logged in
if (empty($_SESSION["id"])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$user_id = (int)$_SESSION["id"];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

/**
 * Get or create active cart for user
 */
function getOrCreateCart($con, $user_id) {
    $result = mysqli_query($con, "SELECT id FROM cart WHERE user_id = $user_id AND status = 'ACTIVE' LIMIT 1");
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result)['id'];
    }
    
    // Create new cart
    mysqli_query($con, "INSERT INTO cart (user_id, status, expires_at) VALUES ($user_id, 'ACTIVE', DATE_ADD(NOW(), INTERVAL 24 HOUR))");
    return mysqli_insert_id($con);
}

/**
 * Get cart count
 */
function getCartCount($con, $user_id) {
    $result = mysqli_query($con, "SELECT COUNT(ci.id) as cnt FROM cart c JOIN cart_items ci ON c.id = ci.cart_id WHERE c.user_id = $user_id AND c.status = 'ACTIVE'");
    if ($result) {
        return mysqli_fetch_assoc($result)['cnt'] ?? 0;
    }
    return 0;
}

/**
 * Get cart total
 */
function getCartTotal($con, $cart_id) {
    $result = mysqli_query($con, "SELECT SUM(total_price) as total FROM cart_items WHERE cart_id = $cart_id");
    if ($result) {
        return mysqli_fetch_assoc($result)['total'] ?? 0;
    }
    return 0;
}

switch ($action) {
    case 'add':
        // Add item to cart
        $tool_id = (int)($_POST['tool_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 1);
        
        if ($tool_id <= 0 || $quantity <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid tool or quantity']);
            exit();
        }
        
        // Get tool details
        $tool_result = mysqli_query($con, "SELECT u_toolname, u_price, u_itemsnumber FROM tool WHERE id = $tool_id");
        if (!$tool_result || mysqli_num_rows($tool_result) == 0) {
            echo json_encode(['success' => false, 'message' => 'Tool not found']);
            exit();
        }
        
        $tool = mysqli_fetch_assoc($tool_result);
        $available = (int)$tool['u_itemsnumber'];
        
        $cart_id = getOrCreateCart($con, $user_id);
        
        // Check if already in cart
        $existing = mysqli_query($con, "SELECT id, quantity FROM cart_items WHERE cart_id = $cart_id AND tool_id = $tool_id");
        
        if ($existing && mysqli_num_rows($existing) > 0) {
            $existing_data = mysqli_fetch_assoc($existing);
            $new_qty = $existing_data['quantity'] + $quantity;
            
            if ($new_qty > $available) {
                echo json_encode([
                    'success' => false, 
                    'message' => "Cannot add more. You have {$existing_data['quantity']} in cart, only $available available."
                ]);
                exit();
            }
            
            mysqli_query($con, "UPDATE cart_items SET quantity = $new_qty, unit_price = {$tool['u_price']} WHERE id = {$existing_data['id']}");
            $message = "Updated: Now $new_qty × {$tool['u_toolname']} in cart";
        } else {
            if ($quantity > $available) {
                echo json_encode(['success' => false, 'message' => "Only $available available"]);
                exit();
            }
            
            $tool_name = mysqli_real_escape_string($con, $tool['u_toolname']);
            mysqli_query($con, "INSERT INTO cart_items (cart_id, tool_id, tool_name, quantity, unit_price) 
                               VALUES ($cart_id, $tool_id, '$tool_name', $quantity, {$tool['u_price']})");
            $message = "Added $quantity × {$tool['u_toolname']} to cart";
        }
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'cart_count' => getCartCount($con, $user_id),
            'cart_total' => getCartTotal($con, $cart_id)
        ]);
        break;
        
    case 'update':
        // Update item quantity
        $cart_item_id = (int)($_POST['cart_item_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 0);
        
        if ($cart_item_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid cart item']);
            exit();
        }
        
        // Get cart item and verify ownership
        $item_result = mysqli_query($con, "
            SELECT ci.*, c.user_id, t.u_itemsnumber as available 
            FROM cart_items ci 
            JOIN cart c ON ci.cart_id = c.id 
            JOIN tool t ON ci.tool_id = t.id
            WHERE ci.id = $cart_item_id AND c.user_id = $user_id AND c.status = 'ACTIVE'
        ");
        
        if (!$item_result || mysqli_num_rows($item_result) == 0) {
            echo json_encode(['success' => false, 'message' => 'Cart item not found']);
            exit();
        }
        
        $item = mysqli_fetch_assoc($item_result);
        
        if ($quantity <= 0) {
            // Remove item
            mysqli_query($con, "DELETE FROM cart_items WHERE id = $cart_item_id");
            $message = "Item removed from cart";
        } elseif ($quantity > $item['available']) {
            echo json_encode(['success' => false, 'message' => "Only {$item['available']} available"]);
            exit();
        } else {
            mysqli_query($con, "UPDATE cart_items SET quantity = $quantity WHERE id = $cart_item_id");
            $message = "Quantity updated to $quantity";
        }
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'cart_count' => getCartCount($con, $user_id),
            'cart_total' => getCartTotal($con, $item['cart_id'])
        ]);
        break;
        
    case 'remove':
        // Remove item from cart
        $cart_item_id = (int)($_POST['cart_item_id'] ?? 0);
        
        // Verify ownership
        $verify = mysqli_query($con, "
            SELECT ci.cart_id FROM cart_items ci 
            JOIN cart c ON ci.cart_id = c.id 
            WHERE ci.id = $cart_item_id AND c.user_id = $user_id AND c.status = 'ACTIVE'
        ");
        
        if (!$verify || mysqli_num_rows($verify) == 0) {
            echo json_encode(['success' => false, 'message' => 'Cart item not found']);
            exit();
        }
        
        $cart_id = mysqli_fetch_assoc($verify)['cart_id'];
        mysqli_query($con, "DELETE FROM cart_items WHERE id = $cart_item_id");
        
        echo json_encode([
            'success' => true,
            'message' => 'Item removed from cart',
            'cart_count' => getCartCount($con, $user_id),
            'cart_total' => getCartTotal($con, $cart_id)
        ]);
        break;
        
    case 'clear':
        // Clear entire cart
        $cart_result = mysqli_query($con, "SELECT id FROM cart WHERE user_id = $user_id AND status = 'ACTIVE'");
        
        if ($cart_result && mysqli_num_rows($cart_result) > 0) {
            $cart_id = mysqli_fetch_assoc($cart_result)['id'];
            mysqli_query($con, "DELETE FROM cart_items WHERE cart_id = $cart_id");
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Cart cleared',
            'cart_count' => 0,
            'cart_total' => 0
        ]);
        break;
        
    case 'get':
        // Get cart contents
        $cart_result = mysqli_query($con, "SELECT id FROM cart WHERE user_id = $user_id AND status = 'ACTIVE'");
        
        if (!$cart_result || mysqli_num_rows($cart_result) == 0) {
            echo json_encode([
                'success' => true,
                'items' => [],
                'cart_count' => 0,
                'cart_total' => 0
            ]);
            exit();
        }
        
        $cart_id = mysqli_fetch_assoc($cart_result)['id'];
        
        $items_result = mysqli_query($con, "
            SELECT ci.*, t.u_itemsnumber as available, t.image_url, t.u_type,
                   COALESCE(im.method, 'FIFO') as inventory_method
            FROM cart_items ci 
            JOIN tool t ON ci.tool_id = t.id 
            LEFT JOIN inventory_method im ON t.id = im.tool_id
            WHERE ci.cart_id = $cart_id
            ORDER BY ci.added_at DESC
        ");
        
        $items = [];
        while ($item = mysqli_fetch_assoc($items_result)) {
            $items[] = $item;
        }
        
        echo json_encode([
            'success' => true,
            'items' => $items,
            'cart_count' => count($items),
            'cart_total' => getCartTotal($con, $cart_id)
        ]);
        break;
        
    case 'count':
        // Just get cart count
        echo json_encode([
            'success' => true,
            'cart_count' => getCartCount($con, $user_id)
        ]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
