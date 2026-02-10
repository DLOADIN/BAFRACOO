<?php
/**
 * Stripe Payment Redirect Handler
 * This page handles the callback from Stripe after payment
 * 
 * On successful payment:
 * 1. Verify payment with Stripe
 * 2. Update order status to 'Paid'
 * 3. Deduct stock from inventory
 * 4. Record transaction
 * 5. Redirect to success page
 */

require "connection.php";
require_once "stripe_helper.php";

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

// Get payment status and session ID from Stripe
$status = isset($_GET['status']) ? $_GET['status'] : '';
$session_id = isset($_GET['session_id']) ? $_GET['session_id'] : '';
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

// If we have a session_id, verify payment with Stripe
$payment_verified = false;
$stripe_transaction_id = '';

if (!empty($session_id) && $status == 'success') {
    // Retrieve the checkout session from Stripe
    $checkout_session = getStripeCheckoutSession($session_id);
    
    if (!isset($checkout_session['error']) && isset($checkout_session['payment_status'])) {
        // Get order_id from metadata
        if (isset($checkout_session['metadata']['order_id'])) {
            $order_id = (int)$checkout_session['metadata']['order_id'];
        }
        
        // Check if payment was successful
        if ($checkout_session['payment_status'] == 'paid' && $checkout_session['status'] == 'complete') {
            $payment_verified = true;
            $stripe_transaction_id = $checkout_session['payment_intent'] ?? $session_id;
        }
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
function recordTransaction($con, $order_id, $user_id, $stripe_transaction_id, $status) {
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
if ($status == 'success' && $payment_verified) {
    // Payment was successful and verified with Stripe
    
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
                recordTransaction($con, $order_id, $user_id, $stripe_transaction_id, 'Completed');
                
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
    
} else {
    // Payment failed or unknown status
    
    if($order_id > 0) {
        // Update order status to indicate failure
        mysqli_query($con, "UPDATE `order` SET status = 'Payment Failed' WHERE id = '$order_id' AND user_id = '$user_id' AND status = 'Pending Payment'");
    }
    
    // Store failure message in session
    $_SESSION['payment_failed'] = true;
    
    // Redirect to failed payment page
    header('Location: failed-payment.php?status=failed&order_id=' . $order_id);
    exit();
}
?>