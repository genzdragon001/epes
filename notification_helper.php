<?php
/**
 * Notification Helper
 * Functions to create and fetch notifications
 * Include this in files that need to trigger notifications
 */

/**
 * Create a notification for a user
 * @param int    $user_id   The target user ID
 * @param int    $user_type 0=faculty, 1=evaluator, 2=admin
 * @param string $title     Short title
 * @param string $message   Full message
 * @param string $type      Info|Warning|Success|Danger
 * @param string $link      Optional link (e.g., 'index.php?page=employee_eval_status')
 */
function create_notification($conn, $user_id, $user_type, $title, $message, $type = 'Info', $link = null) {
    $stmt = $conn->prepare("
        INSERT INTO notifications (user_id, user_type, title, message, type, link)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param('iissss', $user_id, $user_type, $title, $message, $type, $link);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

/**
 * Notify evaluator when faculty submits a task
 */
function notify_evaluator_on_submission($conn, $faculty_id, $task_id, $rating_period) {
    // Get faculty name
    $fq = $conn->query("SELECT CONCAT(firstname, ' ', lastname) as name FROM employee_list WHERE id = $faculty_id LIMIT 1");
    $faculty_name = $fq ? $fq->fetch_assoc()['name'] : 'A faculty member';
    
    // Get task name
    $tq = $conn->query("SELECT success_indicators FROM task_list WHERE id = $task_id LIMIT 1");
    $task_name = $tq ? ($tq->fetch_assoc()['success_indicators'] ?? 'a task') : 'a task';
    
    // Find the evaluator for this faculty
    $eq = $conn->query("SELECT evaluator_id FROM employee_list WHERE id = $faculty_id LIMIT 1");
    $eval_row = $eq ? $eq->fetch_assoc() : null;
    $evaluator_id = $eval_row ? $eval_row['evaluator_id'] : 0;
    
    if ($evaluator_id > 0) {
        $title = 'New Submission';
        $message = "{$faculty_name} submitted \"{$task_name}\" for {$rating_period}. Awaiting your verification.";
        create_notification($conn, $evaluator_id, 1, $title, $message, 'Info', 'index.php?page=employee_eval_status');
    }
    
    // Also notify all deans (evaluator_list type=1)
    $deans_qry = $conn->query("SELECT id FROM evaluator_list WHERE type = 1");
    while ($dean = $deans_qry->fetch_assoc()) {
        if ($dean['id'] != $evaluator_id) { // Don't duplicate if evaluator is also a dean
            $title = 'Faculty Submission';
            $message = "{$faculty_name} submitted \"{$task_name}\" for {$rating_period}.";
            create_notification($conn, $dean['id'], 1, $title, $message, 'Info', 'index.php?page=employee_eval_status');
        }
    }
}

/**
 * Notify faculty when their submission is verified/rejected
 */
function notify_faculty_on_verification($conn, $faculty_id, $task_id, $status, $evaluator_name) {
    $tq = $conn->query("SELECT success_indicators FROM task_list WHERE id = $task_id LIMIT 1");
    $task_name = $tq ? ($tq->fetch_assoc()['success_indicators'] ?? 'a task') : 'a task';
    
    $type = ($status === 'Verified') ? 'Success' : 'Warning';
    $title = ($status === 'Verified') ? 'Submission Verified' : 'Submission Rejected';
    $message = "{$evaluator_name} has {$status} your submission \"{$task_name}\".";
    $link = 'index.php?page=target_list';
    
    create_notification($conn, $faculty_id, 0, $title, $message, $type, $link);
}

/**
 * Get unread notification count for current user
 */
function get_unread_count($conn, $user_id, $user_type) {
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ? AND user_type = ? AND is_read = 0");
    $stmt->bind_param('ii', $user_id, $user_type);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['cnt'];
    $stmt->close();
    return $count;
}

/**
 * Get recent notifications for current user
 */
function get_recent_notifications($conn, $user_id, $user_type, $limit = 10) {
    $stmt = $conn->prepare("
        SELECT * FROM notifications 
        WHERE user_id = ? AND user_type = ?
        ORDER BY created_at DESC 
        LIMIT ?
    ");
    $stmt->bind_param('iii', $user_id, $user_type, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    $stmt->close();
    return $notifications;
}
