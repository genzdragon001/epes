<?php
session_start();
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document'])) {
    $task_id   = intval($_POST['task_id']);
    $faculty_id = $_SESSION['login_id']; // current user
    $login_type = $_SESSION['login_type'];
    $upload_dir = "uploads/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $fileTmpPath   = $_FILES['document']['tmp_name'];
    $fileName      = (string)$_FILES['document']['name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $rating_period = $_SESSION['rating_period'];
    // Allowed file types
    $allowed = ["pdf", "doc", "docx", "xls", "xlsx", "png", "jpg"];
    if (!in_array($fileExtension, $allowed)) {
        echo "<script>alert('Invalid file type!'); window.history.back();</script>";
        exit;
    }

    // Basic upload hardening
    if (!is_uploaded_file($fileTmpPath) || ($_FILES['document']['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        echo "<script>alert('File upload failed.'); window.history.back();</script>";
        exit;
    }

    // Limit size (10MB)
    $maxBytes = 10 * 1024 * 1024;
    if (($_FILES['document']['size'] ?? 0) > $maxBytes) {
        echo "<script>alert('File too large.'); window.history.back();</script>";
        exit;
    }

    // Validate MIME type server-side (do not trust extension)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($fileTmpPath);
    $allowedMime = [
        'pdf'  => ['application/pdf'],
        'doc'  => ['application/msword', 'application/octet-stream'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'xls'  => ['application/vnd.ms-excel', 'application/octet-stream'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        'png'  => ['image/png'],
        'jpg'  => ['image/jpeg'],
    ];
    if (!isset($allowedMime[$fileExtension]) || !in_array($mime, $allowedMime[$fileExtension], true)) {
        echo "<script>alert('Invalid file content.'); window.history.back();</script>";
        exit;
    }

    // Generate unique file path (without extension stored in DB)
    // Store random filename (avoid original name, path tricks)
    $newFileName = $upload_dir . bin2hex(random_bytes(16));
    $dest_path   = $newFileName . "." . $fileExtension;

    // Move the uploaded file
    if (move_uploaded_file($fileTmpPath, $dest_path)) {
        // Insert new record into task_progress (like reupload logic)
        $stmt = $conn->prepare("
            INSERT INTO task_progress 
                (login_type,task_id, faculty_id, file_path, file_type, progress, date_created,rating_period) 
            VALUES (?,?, ?, ?, ?, 'For Verification', NOW(),?)
        ");
        $stmt->bind_param("iiisss", $login_type,$task_id, $faculty_id, $newFileName, $fileExtension,$rating_period);

        if ($stmt->execute()) {
            echo "<script>//alert('File uploaded successfully!');
            
            window.location.href='index.php?page=target_list';</script>";
        } else {
            echo "<script>alert('Database insert failed.'); window.history.back();</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('File upload failed.'); window.history.back();</script>";
    }
} else {
    echo "<script>alert('Invalid request.'); window.history.back();</script>";
}
?>
