<?php
// Redirect to the new unified login page
require 'connection.php';

// If already logged in as user, redirect to dashboard
if(isset($_SESSION["login"]) && $_SESSION["login"] === true && isset($_SESSION["role"]) && $_SESSION["role"] === "user"){
    header('location:userdashboard.php');
    exit();
}

// Redirect to unified login
header('location:../login.php');
exit();
?>
