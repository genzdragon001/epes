<?php
session_start();
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document'])) {
    $faculty_id = intval($_SESSION['login_id']);
    $title = $conn->real_escape_string(trim($_POST['title']));
    $target_id = intval($_POST['target_id']);
    $description = $conn->real_escape_string(trim($_POST['description']));
    $date_submitted = $_POST['date_submitted'];
    $rating_period = $conn->real_escape_string($_POST['rating_period']);
    $deadline = isset($_POST['deadline']) && !empty($_POST['deadline']) ? $_POST['deadline'] : null;
    $mov_type = isset($_POST['mov_type']) && !empty($_POST['mov_type']) ? $conn->real_escape_string($_POST['mov_type']) : null;
    
    // Validate required fields
    if (empty($title) || empty($rating_period)) {
        echo 0;
        exit;
    }
    
    $upload_dir = "uploads/mov/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $fileTmpPath = $_FILES['document']['tmp_name'];
    $fileName = $_FILES['document']['name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $fileSize = $_FILES['document']['size'];
    
    // Fix date_submitted format - convert from datetime-local to MySQL datetime
    // datetime-local returns "2025-08-15T10:30" format
    if (!empty($date_submitted) && $date_submitted !== '0000-00-00 00:00:00') {
        $date_submitted = str_replace('T', ' ', $date_submitted);
        $date_obj = DateTime::createFromFormat('Y-m-d H:i', $date_submitted);
        if ($date_obj) {
            $date_submitted = $date_obj->format('Y-m-d H:i:s');
        } else {
            $date_obj = DateTime::createFromFormat('Y-m-d', $date_submitted);
            if ($date_obj) {
                $date_submitted = $date_obj->format('Y-m-d H:i:s');
            } else {
                $date_submitted = date('Y-m-d H:i:s');
            }
        }
    } else {
        $date_submitted = date('Y-m-d H:i:s');
    }
    
    // Allowed file types
    $allowed = ["pdf", "doc", "docx", "xls", "xlsx", "png", "jpg", "jpeg"];
    if (!in_array($fileExtension, $allowed)) {
        echo 0;
        exit;
    }
    
    // Basic upload hardening
    if (!is_uploaded_file($fileTmpPath) || ($_FILES['document']['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        echo 0;
        exit;
    }
    
    // Limit size (10MB)
    $maxBytes = 10 * 1024 * 1024;
    if ($fileSize > $maxBytes) {
        echo 0;
        exit;
    }
    
    // Validate MIME type
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
        'jpeg' => ['image/jpeg'],
    ];
    
    if (!isset($allowedMime[$fileExtension]) || !in_array($mime, $allowedMime[$fileExtension], true)) {
        echo 0;
        exit;
    }
    
    // Generate unique file path
    $newFileName = $upload_dir . bin2hex(random_bytes(16));
    $dest_path = $newFileName . "." . $fileExtension;
    
    // Allow multiple MOVs for the same target (removed duplicate check)
    // Faculty can upload multiple files as evidence for the same target
    
    // Move the uploaded file
    if (move_uploaded_file($fileTmpPath, $dest_path)) {
        $stmt = $conn->prepare("
            INSERT INTO mov_uploads 
            (faculty_id, task_id, target_id, title, description, file_path, file_type, file_name, file_size, 
             date_submitted, rating_period, mov_type) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $task_id = 0;
        $stmt->bind_param("iiissssissss", 
            $faculty_id, $task_id, $target_id, $title, $description, 
            $newFileName, $fileExtension, $fileName, $fileSize, 
            $date_submitted, $rating_period, $mov_type);
        
        if ($stmt->execute()) {
            // Save deadline if provided
            if (!empty($deadline)) {
                $conn->query("INSERT INTO target_deadlines (target_id, deadline) VALUES ($target_id, '$deadline') ON DUPLICATE KEY UPDATE deadline = '$deadline'");
            }
            
            // If efficiency type, also save to efficiency_attendance
            if ($mov_type === 'efficiency') {
                $percentage = isset($_POST['percentage']) && !empty($_POST['percentage']) ? floatval($_POST['percentage']) : 100;
                $rating = 5;
                if ($percentage == 100) $rating = 5;
                elseif ($percentage >= 75) $rating = 4;
                elseif ($percentage >= 63) $rating = 3;
                elseif ($percentage >= 51) $rating = 2;
                else $rating = 1;
                
                $date_conducted = isset($_POST['date_conducted']) && !empty($_POST['date_conducted']) ? $_POST['date_conducted'] : $date_submitted;
                
                $conn->query("INSERT INTO efficiency_attendance (faculty_id, target_id, rating_period, activity_title, date_conducted, percentage, rating) 
                    VALUES ($faculty_id, $target_id, '$rating_period', '$title', '$date_conducted', $percentage, $rating)");
            }
            
            // Update summary table
            update_mov_summary($conn, $faculty_id, $rating_period, $target_id);
            echo 1;
        } else {
            echo 0;
        }
        $stmt->close();
    } else {
        echo 0;
    }
} else {
    echo 0;
}

function update_mov_summary($conn, $faculty_id, $rating_period, $target_id) {
    // Calculate summary stats
    $target_clause = $target_id > 0 ? "AND target_id = $target_id" : "";
    
    $summary = $conn->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Verified' THEN 1 ELSE 0 END) as verified,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected,
        SUM(file_size) as total_size,
        MAX(date_submitted) as last_submission
        FROM mov_uploads 
        WHERE faculty_id = $faculty_id 
        AND rating_period = '$rating_period' 
        $target_clause")->fetch_assoc();
    
    $total = intval($summary['total']);
    $verified = intval($summary['verified']);
    $pending = intval($summary['pending']);
    $rejected = intval($summary['rejected']);
    $total_size = intval($summary['total_size']);
    $last_sub = $summary['last_submission'];
    
    // Upsert summary
    $conn->query("
        INSERT INTO mov_summary 
        (faculty_id, rating_period, target_id, total_movs, verified_movs, pending_movs, rejected_movs, total_file_size, last_submission)
        VALUES ($faculty_id, '$rating_period', " . ($target_id > 0 ? $target_id : 'NULL') . ", 
                $total, $verified, $pending, $rejected, $total_size, '$last_sub')
        ON DUPLICATE KEY UPDATE
        total_movs = $total,
        verified_movs = $verified,
        pending_movs = $pending,
        rejected_movs = $rejected,
        total_file_size = $total_size,
        last_submission = '$last_sub',
        date_updated = NOW()
    ");
}
?>
