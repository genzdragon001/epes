<?php
session_start();
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_id = intval($_POST['task_id']);
    $faculty_id = $_SESSION['login_id'];

  // Fetch file_path and file_type first
$stmt = $conn->prepare("SELECT file_path, file_type FROM task_progress WHERE task_id = ? AND faculty_id = ?");
$stmt->bind_param("ii", $task_id, $faculty_id);
$stmt->execute();
$stmt->bind_result($file_path, $file_type);
$stmt->fetch();
$stmt->close();

if ($file_path && $file_type) {
    $fullFilePath = $file_path . "." . $file_type; // complete file path
    if (file_exists($fullFilePath)) {
        unlink($fullFilePath); // delete file physically
    }
}


    // Delete record from task_progress
    $stmt2 = $conn->prepare("DELETE FROM task_progress WHERE task_id = ? AND faculty_id = ?");
    $stmt2->bind_param("ii", $task_id, $faculty_id);
    if ($stmt2->execute()) {
        echo "<script>alert('File deleted successfully!'); window.location.href='index.php?page=task_list';</script>";
    } else {
        echo "<script>alert('Failed to delete file record.'); window.history.back();</script>";
    }
    $stmt2->close();
}
?>
