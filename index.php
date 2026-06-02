<!DOCTYPE html>
<html lang="en">
<?php
 session_start();
 include 'db_connect.php';

 // "Remember Me" auto-login: check for persistent cookie
 if (!isset($_SESSION['login_id']) && isset($_COOKIE['remember_me'])) {
     $parts = explode(':', $_COOKIE['remember_me']);
     if (count($parts) === 2) {
         $selector = $parts[0];
         $validator = $parts[1];
         
         // Look up token in database
         $stmt = $conn->prepare(
             "SELECT rt.*, 
              CASE rt.user_type
                  WHEN 0 THEN el.email
                  WHEN 1 THEN ev.email
                  WHEN 2 THEN u.email
              END as email
              FROM remember_tokens rt
              LEFT JOIN employee_list el ON rt.user_id = el.id AND rt.user_type = 0
              LEFT JOIN evaluator_list ev ON rt.user_id = ev.id AND rt.user_type = 1
              LEFT JOIN users u ON rt.user_id = u.id AND rt.user_type = 2
              WHERE rt.selector = ? AND rt.expires > NOW()
              LIMIT 1"
         );
         $stmt->bind_param('s', $selector);
         $stmt->execute();
         $result = $stmt->get_result();
         $stmt->close();
         
         if ($result && $result->num_rows > 0) {
             $token = $result->fetch_assoc();
             if (hash_equals($token['hashed_validator'], hash('sha256', $validator))) {
                 // Valid token — set session
                 $tables = ['employee_list', 'evaluator_list', 'users'];
                 $table = $tables[$token['user_type']];
                 
                 $stmt = $conn->prepare("SELECT *, CONCAT(firstname,' ',lastname) AS name FROM {$table} WHERE id = ? LIMIT 1");
                 $stmt->bind_param('i', $token['user_id']);
                 $stmt->execute();
                 $user = $stmt->get_result()->fetch_assoc();
                 $stmt->close();
                 
                 if ($user) {
                     // Set session variables
                     foreach ($user as $key => $value) {
                         if ($key !== 'password' && !is_numeric($key)) {
                             $_SESSION['login_'.$key] = $value;
                         }
                     }
                     $_SESSION['login_id'] = $token['user_id'];
                     $_SESSION['login_type'] = $token['user_type'];
                     
                     // Rotate token: delete old, insert new
                     $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE id = ?");
                     $stmt->bind_param('i', $token['id']);
                     $stmt->execute();
                     $stmt->close();
                     
                     $new_selector = bin2hex(random_bytes(16));
                     $new_validator = bin2hex(random_bytes(32));
                     $new_hashed = hash('sha256', $new_validator);
                     $new_expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                     
                     $stmt = $conn->prepare("INSERT INTO remember_tokens (user_id, user_type, selector, hashed_validator, expires) VALUES (?, ?, ?, ?, ?)");
                     $stmt->bind_param('iisss', $token['user_id'], $token['user_type'], $new_selector, $new_hashed, $new_expires);
                     $stmt->execute();
                     $stmt->close();
                     
                     setcookie('remember_me', $new_selector . ':' . $new_validator, [
                         'expires' => time() + (30 * 24 * 60 * 60),
                         'path' => '/',
                         'httponly' => true,
                         'samesite' => 'Lax'
                     ]);
                 }
             }
         }
     }
 }

 // Update timestamp for active user

	if(!isset($_SESSION['login_id']))
	    header('location:login.php');
    ob_start();
  if(!isset($_SESSION['system'])){

    $system = $conn->query("SELECT * FROM system_settings")->fetch_array();
    foreach($system as $k => $v){
      $_SESSION['system'][$k] = $v;
    }
  }
  if(!isset($_SESSION['login_type'])){
  // no-op: session type not set (handled by inactivity logic below)
  }
  ob_end_flush();

$inactivityLimit = 900; // 15 minutes

if (isset($_SESSION['last_activity'])) {
    $elapsed = time() - $_SESSION['last_activity'];
    
    if ($elapsed > $inactivityLimit) {
     $ip_address = $_SERVER['REMOTE_ADDR'];
     $user_agent = $_SERVER['HTTP_USER_AGENT'];
 
     if (isset($_SESSION['login_id'])) {
      include 'db_connect.php'; // ensure $conn is available
  
      $user_id   = $_SESSION['login_id'];
      $username  = isset($_SESSION['login_email']) ? $_SESSION['login_email'] : 'unknown';
      $ip_address = $_SERVER['REMOTE_ADDR'];
      $user_agent = $_SERVER['HTTP_USER_AGENT'];
  

      // Insert into audit trail (session expired) - prepared statement
      $stmt = $conn->prepare("INSERT INTO login_audit_trail (user_id, username, ip_address, user_agent, login_status, failure_reason) VALUES (?, ?, ?, ?, 'SUCCESS', 'Session expired due to inactivity')");
      $uid = (int)$user_id;
      $stmt->bind_param('isss', $uid, $username, $ip_address, $user_agent);
      $stmt->execute();
      $stmt->close();
  }
  
 
     // Destroy session
     session_unset();
     session_destroy();
 
     header("Location: login.php?session=expired");
     exit();
 }
 
 
}
$_SESSION['last_activity'] = time();
	include 'header.php' 
?>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed">
<div class="wrapper">
  <?php include 'topbar.php' ?>
  <?php include 'sidebar.php' ?>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
  	 <div class="toast" id="alert_toast" role="alert" aria-live="assertive" aria-atomic="true">
	    <div class="toast-body text-white">
	    </div>
	  </div>
    <div id="toastsContainerTopRight" class="toasts-top-right fixed"></div>
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0"><?php echo $title;?></h1>
          </div><!-- /.col -->

        </div><!-- /.row -->
            <hr class="border-primary">
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
         <?php 
            $page = isset($_GET['page']) ? $_GET['page'] : 'home';
            if(!file_exists($page.".php")){
                include '404.html';
            }else{
            include $page.'.php';

            }
          ?>
      </div><!--/. container-fluid -->
    </section>
    <!-- /.content -->
    <div class="modal fade" id="confirm_modal" role='dialog'>
    <div class="modal-dialog modal-md" role="document">
      <div class="modal-content">
        <div class="modal-header">
        <h5 class="modal-title">Confirmation</h5>
      </div>
      <div class="modal-body">
        <div id="delete_content"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id='confirm' onclick="">Continue</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
      </div>
    </div>
  </div>
  <div class="modal fade" id="uni_modal" role='dialog'>
    <div class="modal-dialog modal-md" role="document">
      <div class="modal-content">
        <div class="modal-header">
        <h5 class="modal-title"></h5>
      </div>
      <div class="modal-body">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id='submit' onclick="$('#uni_modal form').submit()">Save</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
      </div>
      </div>
    </div>
  </div>
  <div class="modal fade" id="uni_modal_right" role='dialog'>
    <div class="modal-dialog modal-full-height  modal-md" role="document">
      <div class="modal-content">
        <div class="modal-header">
        <h5 class="modal-title"></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span class="fa fa-arrow-right"></span>
        </button>
      </div>
      <div class="modal-body">
      </div>
      </div>
    </div>
  </div>
  <div class="modal fade" id="viewer_modal" role='dialog'>
    <div class="modal-dialog modal-md" role="document">
      <div class="modal-content">
              <button type="button" class="btn-close" data-dismiss="modal"><span class="fa fa-times"></span></button>
              <img src="" alt="">
      </div>
    </div>
  </div>
  </div>
  <!-- /.content-wrapper -->

  <!-- Control Sidebar -->
  <aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
  </aside>
  <!-- /.control-sidebar -->

  <!-- Main Footer -->
  <footer class="main-footer">
  
    <div class="float-right d-none d-sm-inline-block">
      <b><?php echo $_SESSION['system']['name'] ?></b>
    </div>
  </footer>
</div>
<!-- ./wrapper -->

<!-- REQUIRED SCRIPTS -->
<!-- jQuery -->
<!-- Bootstrap -->
<?php include 'footer.php' ?>
</body>
</html>
