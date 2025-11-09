<?php
session_start();
include('connection.php');

// Check if user is logged in
if(!isset($_SESSION['id'])){
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Check if tool ID is provided
if(!isset($_GET['id'])){
    http_response_code(400);
    echo json_encode(['error' => 'Tool ID is required']);
    exit();
}

$tool_id = mysqli_real_escape_string($con, $_GET['id']);

// Fetch tool data
$query = "SELECT * FROM `tool` WHERE id = '$tool_id'";

$result = mysqli_query($con, $query);

if($result && mysqli_num_rows($result) > 0){
    $tool = mysqli_fetch_assoc($result);
    echo json_encode($tool);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Tool not found']);
}
?>
