<?php
session_start();
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document'])) {
    $task_id     = intval($_POST['task_id']);
    $faculty_id  = $_SESSION['login_id']; // current user
    $login_type  = $_SESSION['login_type'];
    $upload_dir  = "uploads/";
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

    // ===== DUPLICATE SUBMISSION CHECK =====
    // Check if faculty already submitted this task for this period
    $dup_stmt = $conn->prepare("
        SELECT id, file_path, progress, date_created 
        FROM task_progress 
        WHERE faculty_id = ? AND task_id = ? AND rating_period = ?
        ORDER BY id DESC LIMIT 1
    ");
    $dup_stmt->bind_param("iis", $faculty_id, $task_id, $rating_period);
    $dup_stmt->execute();
    $dup_result = $dup_stmt->get_result();
    $existing = $dup_result->fetch_assoc();
    $dup_stmt->close();

    // If user explicitly chose to overwrite (via ?overwrite=1 in form action)
    $overwrite = isset($_POST['overwrite']) && $_POST['overwrite'] == '1';

    if ($existing && !$overwrite) {
        // Show confirmation dialog — existing submission found
        $existing_date = date('M d, Y h:i A', strtotime($existing['date_created']));
        $existing_status = htmlspecialchars($existing['progress']);
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
            <style>
                body { background: #f4f6f9; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
                .dup-card { max-width: 550px; width: 100%; }
            </style>
        </head>
        <body>
        <div class="card dup-card shadow">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fa fa-exclamation-triangle"></i> Duplicate Submission Detected</h5>
            </div>
            <div class="card-body">
                <p>You have already submitted this task for the current rating period.</p>
                <table class="table table-sm table-bordered">
                    <tr><th width="40%">Previous Submission</th><td><?= $existing_date ?></td></tr>
                    <tr><th>Status</th><td><span class="badge badge-info"><?= $existing_status ?></span></td></tr>
                    <tr><th>Rating Period</th><td><?= htmlspecialchars($rating_period) ?></td></tr>
                </table>
                <p class="text-muted"><small>Re-submitting will replace the previous file. The existing record will be updated.</small></p>
                <div class="d-flex justify-content-between mt-3">
                    <a href="index.php?page=target_list" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> Cancel — Keep Existing
                    </a>
                    <form method="POST" enctype="multipart/form-data" style="display:inline;">
                        <input type="hidden" name="task_id" value="<?= $task_id ?>">
                        <input type="hidden" name="overwrite" value="1">
                        <input type="file" name="document" required style="display:none;" id="reupload_file">
                        <button type="button" class="btn btn-warning" onclick="document.getElementById('reupload_file').click()">
                            <i class="fa fa-redo"></i> Choose File to Replace
                        </button>
                        <button type="submit" class="btn btn-danger" id="submit_btn" style="display:none;">
                            <i class="fa fa-upload"></i> Confirm Overwrite
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <script>
        document.getElementById('reupload_file').addEventListener('change', function() {
            if (this.files.length > 0) {
                document.getElementById('submit_btn').style.display = 'inline-block';
                this.nextElementSibling.textContent = 'Replace with: ' + this.files[0].name;
            }
        });
        </script>
        </body>
        </html>
        <?php
        exit;
    }

    // Generate unique file path
    $newFileName = $upload_dir . bin2hex(random_bytes(16));
    $dest_path   = $newFileName . "." . $fileExtension;

    // Move the uploaded file
    if (move_uploaded_file($fileTmpPath, $dest_path)) {
        if ($existing && $overwrite) {
            // Update existing record (overwrite mode)
            $stmt = $conn->prepare("
                UPDATE task_progress 
                SET file_path = ?, file_type = ?, progress = 'For Verification', 
                    date_created = NOW(), date_verified = NULL
                WHERE id = ?
            ");
            $stmt->bind_param("ssi", $newFileName, $fileExtension, $existing['id']);
        } else {
            // Insert new record
            $stmt = $conn->prepare("
                INSERT INTO task_progress 
                    (login_type, task_id, faculty_id, file_path, file_type, progress, date_created, rating_period) 
                VALUES (?, ?, ?, ?, ?, 'For Verification', NOW(), ?)
            ");
            $stmt->bind_param("iiisss", $login_type, $task_id, $faculty_id, $newFileName, $fileExtension, $rating_period);
        }

        if ($stmt->execute()) {
            // Trigger notification to evaluator
            require_once __DIR__ . '/notification_helper.php';
            notify_evaluator_on_submission($conn, $faculty_id, $task_id, $rating_period);
            
            echo "<script>
            window.location.href='index.php?page=target_list';
            </script>";
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
