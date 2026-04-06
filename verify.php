<?php
session_start();
include 'db_connect.php';
$token = $_GET['code'];
if($token){
    $update = $conn->query("UPDATE employee_list SET is_activated=1 WHERE reset_token='$token' LIMIT 1");
    if($update && $conn->affected_rows > 0){
        $_SESSION['verify_success'] = 'Account verified successfully! You can now login.';
        header("Location: login.php");
        exit;
    } else {
        $_SESSION['verify_error'] = 'Invalid or expired verification link. Please register again or contact support.';
        header("Location: login.php");
        exit;
    }
}
