<?php
/**
 * Notification System
 * Email reminders and in-app notifications
 */

require_once 'config.php';
require_once 'db_connect.php';

class NotificationSystem {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Send in-app notification
     */
    public function sendNotification($user_id, $title, $message, $type = 'Info', $link = null) {
        $stmt = $this->db->prepare("
            INSERT INTO notifications (user_id, title, message, type, link)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('issss', $user_id, $title, $message, $type, $link);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Send deadline reminder emails
     */
    public function sendDeadlineReminders() {
        // Get active rating period
        $period = $this->db->query("SELECT * FROM rating_period WHERE is_active = 1 LIMIT 1")->fetch_assoc();
        if (!$period) return 0;
        
        // Get faculty with pending tasks
        $stmt = $this->db->prepare("
            SELECT DISTINCT e.id, e.email, e.firstname, 
                   COUNT(tp.id) as pending_count
            FROM employee_list e
            LEFT JOIN task_progress tp ON e.id = tp.faculty_id AND tp.progress != 'Verified'
            WHERE e.is_activated = 1
            GROUP BY e.id
            HAVING pending_count > 0
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $sent = 0;
        while ($faculty = $result->fetch_assoc()) {
            $subject = "EPES Deadline Reminder - {$period['semester']} {$period['year']}";
            $body = $this->buildReminderEmail($faculty, $period);
            
            if ($this->sendEmail($faculty['email'], $faculty['firstname'], $subject, $body)) {
                $this->sendNotification(
                    $faculty['id'],
                    'Deadline Reminder',
                    "You have {$faculty['pending_count']} pending task(s) for {$period['semester']}",
                    'Warning',
                    'index.php?page=target_list'
                );
                $sent++;
            }
        }
        $stmt->close();
        
        return $sent;
    }
    
    /**
     * Send verification reminder to evaluators
     */
    public function sendVerificationReminders() {
        $stmt = $this->db->prepare("
            SELECT DISTINCT ev.id, ev.email, ev.firstname,
                   COUNT(tp.id) as pending_count
            FROM evaluator_list ev
            INNER JOIN employee_list e ON ev.id = e.evaluator_id
            INNER JOIN task_progress tp ON e.id = tp.faculty_id AND tp.progress = 'For Verification'
            GROUP BY ev.id
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $sent = 0;
        while ($evaluator = $result->fetch_assoc()) {
            $subject = "Pending Verifications - EPES";
            $body = $this->buildVerificationEmail($evaluator);
            
            if ($this->sendEmail($evaluator['email'], $evaluator['firstname'], $subject, $body)) {
                $this->sendNotification(
                    $evaluator['id'],
                    'Verification Pending',
                    "You have {$evaluator['pending_count']} submission(s) awaiting verification",
                    'Warning',
                    'index.php?page=employee_eval_status'
                );
                $sent++;
            }
        }
        $stmt->close();
        
        return $sent;
    }
    
    /**
     * Build reminder email body
     */
    private function buildReminderEmail($faculty, $period) {
        return "
            <p>Dear {$faculty['firstname']},</p>
            <p>This is a friendly reminder that you have pending tasks for the <strong>{$period['semester']} {$period['year']}</strong> rating period.</p>
            <p>Please log in to the EPES system and submit your accomplishments before the deadline.</p>
            <p><a href='" . SYSTEM_URL . "'>Login to EPES</a></p>
            <br>
            <p>Best regards,<br>EPES Team</p>
        ";
    }
    
    /**
     * Build verification email body
     */
    private function buildVerificationEmail($evaluator) {
        return "
            <p>Dear {$evaluator['firstname']},</p>
            <p>You have faculty submissions awaiting your verification in the EPES system.</p>
            <p>Please review and verify the submissions at your earliest convenience.</p>
            <p><a href='" . SYSTEM_URL . "'>Access EPES</a></p>
            <br>
            <p>Best regards,<br>EPES Team</p>
        ";
    }
    
    /**
     * Send email using PHPMailer
     */
    private function sendEmail($to, $toName, $subject, $body) {
        try {
            require_once 'vendor/autoload.php';
            
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS;
            $mail->SMTPSecure = PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = SMTP_PORT;
            
            $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
            $mail->addAddress($to, $toName);
            
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            
            return $mail->send();
        } catch (Exception $e) {
            error_log("Notification Email Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get unread notifications for user
     */
    public function getUnreadNotifications($user_id) {
        $stmt = $this->db->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? AND is_read = 0
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
        $stmt->close();
        
        return $notifications;
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead($notification_id) {
        $stmt = $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
        $stmt->bind_param('i', $notification_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    /**
     * Mark all notifications as read for user
     */
    public function markAllAsRead($user_id) {
        $stmt = $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->bind_param('i', $user_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    /**
     * Get notification count
     */
    public function getUnreadCount($user_id) {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row['count'];
    }
}
