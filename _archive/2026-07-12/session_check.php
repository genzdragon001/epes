<?php 
$inactivityLimit = 30; // 15 minutes

if (isset($_SESSION['last_activity'])) {
    $elapsed = time() - $_SESSION['last_activity'];
    
    if ($elapsed > $inactivityLimit) {
     $ip_address = $_SERVER['REMOTE_ADDR'];
     $user_agent = $_SERVER['HTTP_USER_AGENT'];
 
     if (isset($_SESSION['login_id'])) {
         $user_id = $_SESSION['login_id'];
         $username = isset($_SESSION['login_email']) ? $_SESSION['login_email'] : 'unknown';
 
         // Insert into audit trail as session expired
         $this->db->query("
             INSERT INTO login_audit_trail 
             (user_id, username, ip_address, user_agent, login_status, failure_reason) 
             VALUES (
                 '".$user_id."', 
                 '".$username."', 
                 '".$ip_address."', 
                 '".$user_agent."', 
                 'LOGOUT', 
                 'Session expired due to inactivity'
             )
         ");
     }
 
     // Destroy session
     session_unset();
     session_destroy();
 
     header("Location: login.php?session=expired");
     exit();
 }
 
}
?>