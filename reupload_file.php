<?php
session_start();
include 'db_connect.php'; // DB connection file

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_id = intval($_POST['task_id']);

    if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath   = $_FILES['document']['tmp_name'];
        $fileName      = $_FILES['document']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Ensure uploads folder exists
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Generate unique file name with extension
        $newFileName = $uploadDir . uniqid("file_", true) ;
        $dest_path   = $newFileName. "." . $fileExtension;

        // Get old file path before updating
        $oldFilePath = '';
        $oldFileType = '';

        $oldFileStmt = $conn->prepare("SELECT file_path, file_type FROM task_progress WHERE task_id = ?");
        $oldFileStmt->bind_param("i", $task_id);
        $oldFileStmt->execute();
        $oldFileStmt->bind_result($oldFilePath, $oldFileType);
        $oldFileStmt->fetch();
        $oldFileStmt->close();

        // Construct full path of old file
        $fullOldFilePath = '';
        if (!empty($oldFilePath) && !empty($oldFileType)) {
            $fullOldFilePath = $uploadDir . basename($oldFilePath) . "." . $oldFileType;
        }
        // Move the new file
        if (move_uploaded_file($fileTmpPath, $dest_path)) {
            // Update database with the new file
            $stmt = $conn->prepare("UPDATE task_progress SET file_path = ?, file_type = ? WHERE task_id = ?");
            $stmt->bind_param("ssi", $newFileName, $fileExtension, $task_id);

            if ($stmt->execute()) {
                $_SESSION['message'] = "File re-uploaded successfully!";

                // Delete old file if it exists
                if (!empty($fullOldFilePath) && file_exists($fullOldFilePath)) {
                    unlink($fullOldFilePath);
                }
            } else {
                $_SESSION['message'] = "Database update failed.";
            }
            $stmt->close();
        } else {
            $_SESSION['message'] = "Error moving the uploaded file.";
        }
    } else {
        $_SESSION['message'] = "No file selected or upload error.";
    }
} else {
    $_SESSION['message'] = "Invalid request.";
}
?>

<script>
    alert("<?php echo $_SESSION['message']; ?>");
    window.location.href = "index.php?page=submission";
</script>
