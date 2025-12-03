<?php
/**
 * Flutterwave Payment Redirect Handler
 * This page handles the callback from Flutterwave after payment
 * 
 * On successful payment:
 * 1. Update order status to 'Paid'
 * 2. Deduct stock from inventory
 * 3. Record transaction
 * 4. Redirect to success page
 */

require "connection.php";

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if(empty($_SESSION["id"])){
    header('location:loginuser.php');
    exit();
}

$user_id = $_SESSION["id"];

// Get payment status and transaction reference from Flutterwave
$status = isset($_GET['status']) ? $_GET['status'] : '';
$tx_ref = isset($_GET['tx_ref']) ? $_GET['tx_ref'] : '';
$transaction_id = isset($_GET['transaction_id']) ? $_GET['transaction_id'] : '';

// Extract order_id from transaction reference (format: BAFRACOO-{order_id}-{timestamp}-{random})
$order_id = 0;
if(!empty($tx_ref) && strpos($tx_ref, 'BAFRACOO-') === 0) {
    $parts = explode('-', $tx_ref);
    if(isset($parts[1])) {
        $order_id = (int)$parts[1];
    }
}

// Function to deduct stock after successful payment
function deductStock($con, $order_id) {
    // Get order details
    $order_query = mysqli_query($con, "SELECT * FROM `order` WHERE id = '$order_id'");
    $order = mysqli_fetch_array($order_query);
    
    if(!$order) {
        return false;
    }
    
    $tool_id = isset($order['tool_id']) ? (int)$order['tool_id'] : 0;
    $quantity = (int)$order['u_itemsnumber'];
    $tool_name = $order['u_toolname'];
    
    // If tool_id is not set, try to find it by tool name
    if($tool_id == 0) {
        $tool_query = mysqli_query($con, "SELECT id FROM tool WHERE u_toolname = '" . mysqli_real_escape_string($con, $tool_name) . "'");
        if($tool_row = mysqli_fetch_array($tool_query)) {
            $tool_id = (int)$tool_row['id'];
        }
    }
    
    if($tool_id > 0 && $quantity > 0) {
        // Get current stock
        $stock_query = mysqli_query($con, "SELECT u_itemsnumber FROM tool WHERE id = '$tool_id'");
        $stock_row = mysqli_fetch_array($stock_query);
        $current_stock = (int)$stock_row['u_itemsnumber'];
        
        // Deduct stock (ensure it doesn't go negative)
        $new_stock = max(0, $current_stock - $quantity);
        mysqli_query($con, "UPDATE tool SET u_itemsnumber = '$new_stock' WHERE id = '$tool_id'");
        
        return true;
    }
    
    return false;
}

// Function to record transaction
function recordTransaction($con, $order_id, $user_id, $tx_ref, $flw_transaction_id, $status) {
    // Get order details
    $order_query = mysqli_query($con, "SELECT * FROM `order` WHERE id = '$order_id'");
    $order = mysqli_fetch_array($order_query);
    
    if(!$order) {
        return false;
    }
    
    $tool_name = mysqli_real_escape_string($con, $order['u_toolname']);
    $quantity = (int)$order['u_itemsnumber'];
    $type = mysqli_real_escape_string($con, $order['u_type']);
    $amount = (float)$order['u_totalprice'];
    $date = date('Y-m-d');
    
    // Check if transaction already recorded
    $check = mysqli_query($con, "SELECT id FROM `transaction` WHERE order_id = '$order_id'");
    if(mysqli_num_rows($check) > 0) {
        return true; // Already recorded
    }
    
    // Insert transaction record
    $insert = mysqli_query($con, "INSERT INTO `transaction` (u_toolname, u_item, u_type, u_amount, u_status, u_date, u_id, order_id) 
                                  VALUES ('$tool_name', '$quantity', '$type', '$amount', '$status', '$date', '$user_id', '$order_id')");
    
    return $insert;
}

// Handle payment status
if ($status == 'successful' || $status == 'completed') {
    // Payment was successful
    
    if($order_id > 0) {
        // Verify the order belongs to this user
        $verify = mysqli_query($con, "SELECT * FROM `order` WHERE id = '$order_id' AND user_id = '$user_id'");
        
        if(mysqli_num_rows($verify) > 0) {
            $order = mysqli_fetch_array($verify);
            
            // Check if order is not already processed
            if($order['status'] != 'Paid' && $order['status'] != 'Completed') {
                // Update order status to 'Paid'
                mysqli_query($con, "UPDATE `order` SET status = 'Paid' WHERE id = '$order_id'");
                
                // Deduct stock from inventory
                deductStock($con, $order_id);
                
                // Record transaction
                recordTransaction($con, $order_id, $user_id, $tx_ref, $transaction_id, 'Completed');
                
                // Store success message in session
                $_SESSION['payment_success'] = true;
                $_SESSION['payment_order_id'] = $order_id;
                $_SESSION['payment_amount'] = $order['u_totalprice'];
            }
        }
    }
    
    // Redirect to success page
    header('Location: checkout.php?status=success&order_id=' . $order_id);
    exit();
    
} elseif ($status == 'cancelled') {
    // Payment was cancelled by user
    
    if($order_id > 0) {
        // Update order status to indicate cancellation
        mysqli_query($con, "UPDATE `order` SET status = 'Payment Cancelled' WHERE id = '$order_id' AND user_id = '$user_id' AND status = 'Pending Payment'");
    }
    
    // Store cancellation message in session
    $_SESSION['payment_cancelled'] = true;
    
    // Redirect to failed payment page
    header('Location: failed-payment.php?status=cancelled&order_id=' . $order_id);
    exit();
    
} elseif ($status == 'failed') {
    // Payment failed
    
    if($order_id > 0) {
        // Update order status to indicate failure
        mysqli_query($con, "UPDATE `order` SET status = 'Payment Failed' WHERE id = '$order_id' AND user_id = '$user_id' AND status = 'Pending Payment'");
    }
    
    // Store failure message in session
    $_SESSION['payment_failed'] = true;
    
    // Redirect to failed payment page
    header('Location: failed-payment.php?status=failed&order_id=' . $order_id);
    exit();
    
} else {
    // Unknown status - redirect to orders page
    header('Location: orders.php');
    exit();
}
?>