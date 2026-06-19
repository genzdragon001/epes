<?php
session_start();
include 'db_connect.php';

$token = isset($_GET['code']) ? trim($_GET['code']) : '';

if (!empty($token)) {
    $stmt = $conn->prepare("UPDATE employee_list SET is_activated = 1 WHERE reset_token = ? LIMIT 1");
    $stmt->bind_param('s', $token);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $_SESSION['verify_success'] = 'Account verified successfully! You can now login.';
    } else {
        $_SESSION['verify_error'] = 'Invalid or expired verification link. Please register again or contact support.';
    }

    $stmt->close();
} else {
    $_SESSION['verify_error'] = 'No verification code provided.';
}

header("Location: login.php");
exit;
