<?php
session_start();
include('connection.php');

// Check if user is logged in
if(!isset($_SESSION['id'])){
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Check if order ID is provided
if(!isset($_GET['id'])){
    http_response_code(400);
    echo json_encode(['error' => 'Order ID is required']);
    exit();
}

$order_id = mysqli_real_escape_string($con, $_GET['id']);

// Fetch order data
$query = "SELECT o.*, u.u_name 
          FROM `order` o 
          LEFT JOIN `user` u ON o.user_id = u.id 
          WHERE o.id = '$order_id'";

$result = mysqli_query($con, $query);

if($result && mysqli_num_rows($result) > 0){
    $order = mysqli_fetch_assoc($result);
    echo json_encode($order);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Order not found']);
}
?>
