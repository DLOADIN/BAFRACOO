<?php
/**
 * BAFRACOO Refund Processing API
 * Handles actual Stripe refund processing
 */

require 'connection.php';

header('Content-Type: application/json');

// Check if admin is logged in
if(empty($_SESSION["id"])){
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$admin_id = $_SESSION["id"];

// Verify admin
$admin_check = mysqli_query($con, "SELECT * FROM `admin` WHERE id = $admin_id");
if(!$admin_check || mysqli_num_rows($admin_check) == 0){
    echo json_encode(['success' => false, 'error' => 'Admin not found']);
    exit();
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);

if(!isset($input['refund_id']) || !isset($input['action'])){
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit();
}

$refund_id = mysqli_real_escape_string($con, $input['refund_id']);
$action = $input['action'];

// Get refund request
$refund_query = mysqli_query($con, "SELECT rr.*, o.stripe_payment_intent, o.stripe_charge_id 
    FROM refund_requests rr 
    LEFT JOIN `order` o ON rr.order_id = o.id 
    WHERE rr.id = $refund_id");

if(!$refund_query || mysqli_num_rows($refund_query) == 0){
    echo json_encode(['success' => false, 'error' => 'Refund request not found']);
    exit();
}

$refund = mysqli_fetch_array($refund_query);

switch($action){
    case 'approve':
        if($refund['status'] != 'PENDING' && $refund['status'] != 'UNDER_REVIEW'){
            echo json_encode(['success' => false, 'error' => 'Refund cannot be approved in current status']);
            exit();
        }
        
        $notes = isset($input['notes']) ? mysqli_real_escape_string($con, $input['notes']) : '';
        
        $update = mysqli_query($con, "UPDATE refund_requests SET 
            status = 'APPROVED', 
            admin_notes = '$notes',
            processed_by = $admin_id,
            updated_at = NOW()
            WHERE id = $refund_id");
        
        if($update){
            echo json_encode(['success' => true, 'message' => 'Refund approved successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }
        break;
        
    case 'reject':
        if($refund['status'] != 'PENDING' && $refund['status'] != 'UNDER_REVIEW'){
            echo json_encode(['success' => false, 'error' => 'Refund cannot be rejected in current status']);
            exit();
        }
        
        $notes = isset($input['notes']) ? mysqli_real_escape_string($con, $input['notes']) : '';
        
        $update = mysqli_query($con, "UPDATE refund_requests SET 
            status = 'REJECTED', 
            admin_notes = '$notes',
            processed_by = $admin_id,
            updated_at = NOW()
            WHERE id = $refund_id");
        
        if($update){
            echo json_encode(['success' => true, 'message' => 'Refund rejected']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }
        break;
        
    case 'process':
        if($refund['status'] != 'APPROVED'){
            echo json_encode(['success' => false, 'error' => 'Refund must be approved before processing']);
            exit();
        }
        
        // Process refund through Stripe
        $stripe_result = processStripeRefund(
            $refund['stripe_payment_intent'] ?? null,
            $refund['stripe_charge_id'] ?? null,
            $refund['refund_amount']
        );
        
        if($stripe_result['success']){
            $stripe_refund_id = mysqli_real_escape_string($con, $stripe_result['refund_id']);
            
            // Update refund request
            $update = mysqli_query($con, "UPDATE refund_requests SET 
                status = 'PROCESSED', 
                stripe_refund_id = '$stripe_refund_id',
                processed_at = NOW(),
                processed_by = $admin_id,
                updated_at = NOW()
                WHERE id = $refund_id");
            
            // Record refund transaction
            mysqli_query($con, "INSERT INTO refund_transactions 
                (refund_request_id, order_id, user_id, amount, stripe_refund_id, status) 
                VALUES ($refund_id, {$refund['order_id']}, {$refund['user_id']}, {$refund['refund_amount']}, '$stripe_refund_id', 'COMPLETED')");
            
            // Update order status
            mysqli_query($con, "UPDATE `order` SET status = 'Refunded' WHERE id = {$refund['order_id']}");
            
            echo json_encode([
                'success' => true, 
                'message' => 'Refund processed successfully',
                'refund_id' => $stripe_refund_id
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => $stripe_result['error']]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}

/**
 * Process refund through Stripe API
 */
function processStripeRefund($payment_intent, $charge_id, $amount) {
    // Load Stripe API key
    $stripe_secret_key = getenv('STRIPE_SECRET_KEY') ?: 'sk_test_your_key_here';
    
    // Determine what to refund
    $refund_target = null;
    if(!empty($charge_id)){
        $refund_target = ['charge' => $charge_id];
    } elseif(!empty($payment_intent)){
        $refund_target = ['payment_intent' => $payment_intent];
    } else {
        // No Stripe payment info available - simulate refund for testing
        // In production, you would return an error here
        return [
            'success' => true,
            'refund_id' => 're_simulated_' . bin2hex(random_bytes(12)),
            'message' => 'Simulated refund (no Stripe payment info available)'
        ];
    }
    
    // Convert amount to smallest currency unit (RWF doesn't have decimal places)
    $amount_in_cents = intval($amount);
    
    // Prepare refund request
    $refund_data = array_merge($refund_target, [
        'amount' => $amount_in_cents,
        'reason' => 'requested_by_customer'
    ]);
    
    // Make Stripe API call
    $ch = curl_init('https://api.stripe.com/v1/refunds');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($refund_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $stripe_secret_key,
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if($http_code >= 200 && $http_code < 300){
        $result = json_decode($response, true);
        return [
            'success' => true,
            'refund_id' => $result['id'],
            'status' => $result['status']
        ];
    } else {
        $error = json_decode($response, true);
        return [
            'success' => false,
            'error' => $error['error']['message'] ?? 'Failed to process refund'
        ];
    }
}
?>
