<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/config.php';

session_start();
ini_set('display_errors', 0); // Disable display in production
Class Action {
	private $db;

	public function __construct() {
   	include 'db_connect.php';
    
    global $conn;
    $this->db = $conn;
	}
	function __destruct() {
	    $this->db->close();
	}
	
	/**
	 * Get SMTP configuration from environment
	 */
	private function getSMTPConfig() {
		return [
			'host' => SMTP_HOST,
			'port' => SMTP_PORT,
			'user' => SMTP_USER,
			'pass' => SMTP_PASS,
			'from' => SMTP_FROM,
			'from_name' => SMTP_FROM_NAME
		];
	}
	
	/**
	 * Send email using PHPMailer with environment configuration
	 */
	private function sendEmail($to, $toName, $subject, $body) {
		try {
			require_once 'vendor/autoload.php';
			
			$config = $this->getSMTPConfig();
			$mail = new PHPMailer(true);
			
			$mail->isSMTP();
			$mail->Host = $config['host'];
			$mail->SMTPAuth = true;
			$mail->Username = $config['user'];
			$mail->Password = $config['pass'];
			$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
			$mail->Port = $config['port'];
			
			$mail->setFrom($config['from'], $config['from_name']);
			$mail->addAddress($to, $toName);
			
			$mail->isHTML(true);
			$mail->Subject = $subject;
			$mail->Body = $body;
			
			return $mail->send();
		} catch (Exception $e) {
			error_log("Mailer Error: " . $mail->ErrorInfo);
			return false;
		}
	}



	function forgot_password(){
		extract($_POST);
		$type = array("employee_list","evaluator_list","users");
		$table = $type[$login] ?? 'users';
		
		$stmt = $this->db->prepare("SELECT * FROM {$table} WHERE email = ? LIMIT 1");
		$stmt->bind_param('s', $email);
		$stmt->execute();
		$qry = $stmt->get_result();
		$stmt->close();
		
		if($qry->num_rows > 0){
			$user = $qry->fetch_array();
	
			$token = bin2hex(random_bytes(50));
			$expires = date("Y-m-d H:i:s", strtotime("+1 hour"));
	
			$uid = (int)$user['id'];
			$up = $this->db->prepare("UPDATE {$table} SET reset_token = ?, reset_expires = ? WHERE id = ?");
			$up->bind_param('ssi', $token, $expires, $uid);
			$update = $up->execute();
			$up->close();
	
			if($update){
				$reset_link = SYSTEM_URL . "/reset_password.php?token=" . $token;
				$system_name = $_SESSION['system']['name'];
				
				$body = "
				<!DOCTYPE html>
				<html>
				<head>
					<style>
						body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
						.container { max-width: 600px; margin: 0 auto; padding: 20px; }
						.header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
						.header h1 { margin: 0; font-size: 24px; }
						.content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
						.button { display: inline-block; background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; }
						.button:hover { background: #5568d3; }
						.link-text { word-break: break-all; color: #667eea; }
						.warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
						.footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #888; font-size: 12px; }
					</style>
				</head>
				<body>
					<div class='container'>
						<div class='header'>
							<h1>🔐 Password Reset Request</h1>
						</div>
						<div class='content'>
							<p>Dear <strong>" . $user['firstname'] . " " . $user['lastname'] . "</strong>,</p>
							
							<p>We received a request to reset your password for your account in <strong>" . $system_name . "</strong>.</p>
							
							<p>Click the button below to reset your password:</p>
							<p style='text-align: center;'>
								<a href='" . $reset_link . "' class='button'>Reset My Password</a>
							</p>
							
							<p>Or copy and paste this link into your browser:</p>
							<p class='link-text'>" . $reset_link . "</p>
							
							<div class='warning'>
								<strong>⚠️ Important:</strong> This link will expire in <strong>1 hour</strong>.
							</div>
							
							<p>If you did not request this password reset, please ignore this email or contact our support team.</p>
						</div>
						<div class='footer'>
							<p>&copy; " . date('Y') . " " . $system_name . ". All rights reserved.</p>
							<p>This is an automated message, please do not reply.</p>
						</div>
					</div>
				</body>
				</html>
				";
				
				if($this->sendEmail($user['email'], $user['firstname'], "Password Reset Request - " . $system_name, $body)){
					return 1;
				} else {
					return 3;
				}
			} else {
				return 0;
			}
	
		} else {
			return 2;
		}
	}
	public function reset_password() {
		extract($_POST);
	
		if ($password !== $confirm_password) {
			return 2;
		}
	
		$stmt = $this->db->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_expires > NOW() LIMIT 1");
		$stmt->bind_param('s', $token);
		$stmt->execute();
		$qry = $stmt->get_result();
		$stmt->close();
	
		if ($qry->num_rows == 0) {
			return 3;
		}
	
		$user = $qry->fetch_assoc();
		$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
	
		$update_stmt = $this->db->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
		$update_stmt->bind_param('si', $hashedPassword, $user['id']);
		$update = $update_stmt->execute();
		$update_stmt->close();
	
		return $update ? 1 : 4;
	}
	
	
	// function login(){
	// 	extract($_POST);
	// 	$type = array("employee_list","evaluator_list","users");
	// 		$qry = $this->db->query("SELECT *,concat(firstname,' ',lastname) as name FROM {$type[$login]} where email = '".$email."' and password = '".md5($password)."'  ");
	// 	if($qry->num_rows > 0){
	// 		foreach ($qry->fetch_array() as $key => $value) {
	// 			if($key != 'password' && !is_numeric($key))
	// 				$_SESSION['login_'.$key] = $value;
	// 		}
	// 				$_SESSION['login_type'] = $login;
	// 			return 1;
	// 	}else{
	// 		return 2;
	// 	}
	// }

	// function login(){
	// 	extract($_POST);
	// 	$type = array("employee_list","evaluator_list","users");
	
	// 	// Find user by email
	// 	$qry = $this->db->query("SELECT * FROM {$type[$login]} WHERE email = '".$email."'");
	// 	if($qry->num_rows > 0){
	// 		$user = $qry->fetch_array();
	
	// 		// Check if account is blocked
	// 		if($user['isBlocked'] == 1){
	// 			return 3; // Account blocked
	// 		}
	
	// 		// Verify password
	// 		if($user['password'] === md5($password)){
	// 			// Reset failed login count
	// 			$this->db->query("UPDATE {$type[$login]} SET failed_login = 0 WHERE id = '".$user['id']."'");
	
	// 			// Set session
	// 			foreach ($user as $key => $value) {
	// 				if($key != 'password' && !is_numeric($key))
	// 					$_SESSION['login_'.$key] = $value;
	// 			}
	// 			$_SESSION['login_type'] = $login;

	// 			return 1; // Success
	// 		} else {
	// 			// Increment failed login attempts
	// 			$failed = $user['failed_login'] + 1;
	// 			if($failed >= 5){
	// 				// Block user
	// 				$this->db->query("UPDATE {$type[$login]} SET failed_login = $failed, isBlocked = 1 WHERE id = '".$user['id']."'");
	// 			} else {
	// 				$this->db->query("UPDATE {$type[$login]} SET failed_login = $failed WHERE id = '".$user['id']."'");
	// 			}
	// 			return 2; // Wrong password
	// 		}
	// 	} else {
	// 		return 2; // Email not found
	// 	}
	// }
	
	function login() {
		extract($_POST);
		$tables = array("employee_list", "evaluator_list", "users");
		$ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
		$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
	
		// Choose table based on login type
		$table = $tables[$login] ?? "users";
	
		// Query user by email (prepared statement)
		$stmt = $this->db->prepare("SELECT *, CONCAT(firstname,' ',lastname) AS name FROM {$table} WHERE email = ? LIMIT 1");
		$stmt->bind_param('s', $email);
		$stmt->execute();
		$qry = $stmt->get_result();
		$stmt->close();
	
		if ($qry->num_rows === 0) {
			// Log attempt with unknown email
			$this->logAudit(NULL, $email, $ip_address, $user_agent, "FAILED", "Email not found");
			return 2; // Email not found
		}
	
		$user = $qry->fetch_array();
	
		// Check if account is blocked
		if ((int)$user['isBlocked'] === 1) {
			$this->logAudit($user['id'], $email, $ip_address, $user_agent, "FAILED", "Account blocked");
			return 3; // Account blocked
		}
		
		// Check if faculty account is activated (only for faculty login type 0)
		if ($login == 0 && isset($user['is_activated']) && (int)$user['is_activated'] === 0) {
			$this->logAudit($user['id'], $email, $ip_address, $user_agent, "FAILED", "Account not activated");
			return 4; // Account not activated
		}
	
		// Verify password (supports legacy md5 and modern password_hash)
		$passOk = false;
		if (!empty($user['password'])) {
			$stored = (string)$user['password'];
			if (preg_match('/^[a-f0-9]{32}$/i', $stored)) {
				$passOk = hash_equals($stored, md5((string)$password));
			} else {
				$passOk = password_verify((string)$password, $stored);
			}
		}
		if ($passOk) {
			// Reset failed login count
			$uid = (int)$user['id'];
			$rst = $this->db->prepare("UPDATE {$table} SET failed_login = 0 WHERE id = ?");
			$rst->bind_param('i', $uid);
			$rst->execute();
			$rst->close();
	
			// Log success
			$this->logAudit($user['id'], $email, $ip_address, $user_agent, "SUCCESS", "Login successful");
	
			// Set session variables
			foreach ($user as $key => $value) {
				if ($key !== 'password' && !is_numeric($key)) {
					$_SESSION['login_'.$key] = $value;
				}
			}
			$_SESSION['login_type'] = $login;

			// Session hardening: regenerate session id on privilege change/login
			session_regenerate_id(true);

			// Fetch the latest/current rating period
				$qry = $this->db->query("SELECT semester, year 
				FROM rating_period 
				ORDER BY id DESC 
				LIMIT 1");

				if ($qry && $qry->num_rows > 0) {
				$row = $qry->fetch_assoc();
				$_SESSION['current_semester'] = $row['semester'];
				$_SESSION['current_year']     = $row['year'];
				}
			return 1; // Success
		} else {
			// Wrong password: increment failed attempts
			$failed = (int)$user['failed_login'] + 1;
			if ($failed >= 5) {
				$uid = (int)$user['id'];
				$st = $this->db->prepare("UPDATE {$table} SET failed_login = ?, isBlocked = 1 WHERE id = ?");
				$st->bind_param('ii', $failed, $uid);
				$st->execute();
				$st->close();
				$reason = "Account locked due to too many failed attempts";
			} else {
				$uid = (int)$user['id'];
				$st = $this->db->prepare("UPDATE {$table} SET failed_login = ? WHERE id = ?");
				$st->bind_param('ii', $failed, $uid);
				$st->execute();
				$st->close();
				$reason = "Wrong password";
			}
	
			// Log failed attempt
			$this->logAudit($user['id'], $email, $ip_address, $user_agent, "FAILED", $reason);
			return 2; // Wrong password
		}
	}
	
	/**
	 * Helper function to insert into login_audit_trail
	 */
	private function logAudit($user_id, $username, $ip, $agent, $status, $reason = NULL) {
		$uid = $user_id ? (int)$user_id : null;
		$ip = $ip ?: '127.0.0.1';
		$agent = $agent ?: 'CLI';
		$stmt = $this->db->prepare("INSERT INTO login_audit_trail (user_id, username, ip_address, user_agent, login_status, failure_reason)
			VALUES (?, ?, ?, ?, ?, ?)");
		$stmt->bind_param(
			'isssss',
			$uid,
			$username,
			$ip,
			$agent,
			$status,
			$reason
		);
		$stmt->execute();
		$stmt->close();
	}
	
	
	public function logout() {
		// Start session if not already started
		if (session_status() === PHP_SESSION_NONE) {
			session_start();
		}
		if(isset($_SESSION['login_id'])){
			$user_id   = $_SESSION['login_id'];
			$username  = isset($_SESSION['login_email']) ? $_SESSION['login_email'] : 'Unknown';
			$ip_address = $_SERVER['REMOTE_ADDR'];
			$user_agent = $_SERVER['HTTP_USER_AGENT'];
	
			// Insert logout record
			$this->db->query("INSERT INTO login_audit_trail 
				(user_id, username, ip_address, user_agent, login_status, failure_reason) 
				VALUES ('".$user_id."', '".$username."', '".$ip_address."', '".$user_agent."', 'SUCCESS', 'User logged out')");
		}
		// Unset all session variables
		$_SESSION = [];
	
		// Destroy the session cookie
		if (ini_get("session.use_cookies")) {
			$params = session_get_cookie_params();
			setcookie(session_name(), '', time() - 42000,
				$params["path"], $params["domain"],
				$params["secure"], $params["httponly"]
			);
		}
	
		// Destroy the session
		session_destroy();
	
		// Optional: redirect to login page
		header("Location: login.php");
		exit();
	}
	
	
	function login2(){
		extract($_POST);
			$qry = $this->db->query("SELECT *,concat(lastname,', ',firstname,' ',middlename) as name FROM students where student_code = '".$student_code."' ");
		if($qry->num_rows > 0){
			foreach ($qry->fetch_array() as $key => $value) {
				if($key != 'password' && !is_numeric($key))
					$_SESSION['rs_'.$key] = $value;
			}
				return 1;
		}else{
			return 3;
		}
	}

	function reset_employee(){
		extract($_POST);
		//$tables = array("employee_list", "evaluator_list", "users");
		// Reset failed attempts and unblock account
		$reset = $this->db->query("
			UPDATE employee_list
			SET failed_login = 0, isBlocked = 0 
			WHERE id = '".$id."' 
		");
	
		if($reset){
			return 1; // success
		} else {
			return 0; // failed
		}
	}
	
	function reset_evaluator(){
		extract($_POST);
		//$tables = array("employee_list", "evaluator_list", "users");
		// Reset failed attempts and unblock account
		$reset = $this->db->query("
			UPDATE evaluator_list
			SET failed_login = 0, isBlocked = 0 
			WHERE id = '".$id."' 
		");
	
		if($reset){
			return 1; // success
		} else {
			return 0; // failed
		}
	}
	
	function save_user(){
		extract($_POST);
		
		$firstname = $this->db->real_escape_string($firstname);
		$lastname = $this->db->real_escape_string($lastname);
		$email = $this->db->real_escape_string($email);
		
		$check_stmt = $this->db->prepare("SELECT id FROM users WHERE email = ? " . (!empty($id) ? " AND id != ? " : ""));
		if(!empty($id)){
			$check_stmt->bind_param('si', $email, $id);
		} else {
			$check_stmt->bind_param('s', $email);
		}
		$check_stmt->execute();
		$check = $check_stmt->get_result();
		$check_stmt->close();
		
		if($check->num_rows > 0){
			return 2;
		}
		
		$avatar = 'no-image-available.png';
		if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
			$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
			$move = move_uploaded_file($_FILES['img']['tmp_name'],'assets/uploads/'. $fname);
			if($move) $avatar = $fname;
		}
		
		if(empty($id)){
			$password_hash = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : '';
			$stmt = $this->db->prepare("INSERT INTO users (firstname, lastname, email, password, avatar) VALUES (?, ?, ?, ?, ?)");
			$stmt->bind_param('sssss', $firstname, $lastname, $email, $password_hash, $avatar);
			$save = $stmt->execute();
			$stmt->close();
			
			if($save){
				$new_id = $this->db->insert_id;
				$this->logAudit($new_id, $email, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], 'SUCCESS', 'User account created');
			}
		} else {
			if(!empty($password)){
				$password_hash = password_hash($password, PASSWORD_DEFAULT);
				$stmt = $this->db->prepare("UPDATE users SET firstname=?, lastname=?, email=?, password=?, avatar=? WHERE id=?");
				$stmt->bind_param('sssssi', $firstname, $lastname, $email, $password_hash, $avatar, $id);
			} else {
				$stmt = $this->db->prepare("UPDATE users SET firstname=?, lastname=?, email=?, avatar=? WHERE id=?");
				$stmt->bind_param('ssssi', $firstname, $lastname, $email, $avatar, $id);
			}
			$save = $stmt->execute();
			$stmt->close();
			
			if($save){
				$this->logAudit($id, $email, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], 'SUCCESS', 'User account updated');
			}
		}
	
		return $save ? 1 : 0;
	}
	
	function signup(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id','cpass')) && !is_numeric($k)){
				if($k =='password'){
					if(empty($v))
						continue;
					$v = md5($v);

				}
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}

		$check = $this->db->query("SELECT * FROM users where email ='$email' ".(!empty($id) ? " and id != {$id} " : ''))->num_rows;
		if($check > 0){
			return 2;
			exit;
		}
		if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
			$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
			$move = move_uploaded_file($_FILES['img']['tmp_name'],'assets/uploads/'. $fname);
			$data .= ", avatar = '$fname' ";

		}
		if(empty($id)){
			if(!isset($_FILES['img']) || (isset($_FILES['img']) && $_FILES['img']['tmp_name'] == '')){	
			$data .= ", avatar = 'no-image-available.png' ";
		}
			$save = $this->db->query("INSERT INTO users set $data");

		}else{
			$save = $this->db->query("UPDATE users set $data where id = $id");
		}

		if($save){
			if(empty($id))
				$id = $this->db->insert_id;
			foreach ($_POST as $key => $value) {
				if(!in_array($key, array('id','cpass','password')) && !is_numeric($key))
					$_SESSION['login_'.$key] = $value;
			}
					$_SESSION['login_id'] = $id;
				if(isset($_FILES['img']) && !empty($_FILES['img']['tmp_name']))
					$_SESSION['login_avatar'] = $fname;
			return 1;
		}
	}

	function update_user(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id','cpass','table','password')) && !is_numeric($k)){
				
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}
		$type = array("employee_list","evaluator_list","users");
		$check = $this->db->query("SELECT * FROM {$type[$_SESSION['login_type']]} where email ='$email' ".(!empty($id) ? " and id != {$id} " : ''))->num_rows;
		if($check > 0){
			return 2;
			exit;
		}
		if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
			$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
			$move = move_uploaded_file($_FILES['img']['tmp_name'],'assets/uploads/'. $fname);
			$data .= ", avatar = '$fname' ";

		}
		if(!empty($password))
			$data .= " ,password=md5('$password') ";
		if(empty($id)){
			if(!isset($_FILES['img']) || (isset($_FILES['img']) && $_FILES['img']['tmp_name'] == '')){	
				$data .= ", avatar = 'no-image-available.png' ";
			}
			$save = $this->db->query("INSERT INTO {$type[$_SESSION['login_type']]} set $data");
		}else{
			$save = $this->db->query("UPDATE {$type[$_SESSION['login_type']]} set $data where id = $id");
		}

		if($save){
			foreach ($_POST as $key => $value) {
				if($key != 'password' && !is_numeric($key))
					$_SESSION['login_'.$key] = $value;
			}
			if(isset($_FILES['img']) && !empty($_FILES['img']['tmp_name']))
					$_SESSION['login_avatar'] = $fname;
			return 1;
		}
	}
	function delete_user(){
		extract($_POST);
		$delete = $this->db->query("DELETE FROM users where id = ".$id);
		if($delete)
			return 1;
	}
	function save_system_settings(){
		extract($_POST);
		$data = '';
		foreach($_POST as $k => $v){
			if(!is_numeric($k)){
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}
		if($_FILES['cover']['tmp_name'] != ''){
			$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['cover']['name'];
			$move = move_uploaded_file($_FILES['cover']['tmp_name'],'../assets/uploads/'. $fname);
			$data .= ", cover_img = '$fname' ";

		}
		$chk = $this->db->query("SELECT * FROM system_settings");
		if($chk->num_rows > 0){
			$save = $this->db->query("UPDATE system_settings set $data where id =".$chk->fetch_array()['id']);
		}else{
			$save = $this->db->query("INSERT INTO system_settings set $data");
		}
		if($save){
			foreach($_POST as $k => $v){
				if(!is_numeric($k)){
					$_SESSION['system'][$k] = $v;
				}
			}
			if($_FILES['cover']['tmp_name'] != ''){
				$_SESSION['system']['cover_img'] = $fname;
			}
			return 1;
		}
	}
	function save_image(){
		extract($_FILES['file']);
		if(!empty($tmp_name)){
			$fname = strtotime(date("Y-m-d H:i"))."_".(str_replace(" ","-",$name));
			$move = move_uploaded_file($tmp_name,'assets/uploads/'. $fname);
			$protocol = strtolower(substr($_SERVER["SERVER_PROTOCOL"],0,5))=='https'?'https':'http';
			$hostName = $_SERVER['HTTP_HOST'];
			$path =explode('/',$_SERVER['PHP_SELF']);
			$currentPath = '/'.$path[1]; 
			if($move){
				return $protocol.'://'.$hostName.$currentPath.'/assets/uploads/'.$fname;
			}
		}
	}
	function save_department(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id','user_ids')) && !is_numeric($k)){
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}
		$chk = $this->db->query("SELECT * FROM department_list where department = '$department' and id != '{$id}' ")->num_rows;
		if($chk > 0){
			return 2;
		}
		if(isset($user_ids)){
			$data .= ", user_ids='".implode(',',$user_ids)."' ";
		}
		if(empty($id)){
			$save = $this->db->query("INSERT INTO department_list set $data");
		}else{
			$save = $this->db->query("UPDATE department_list set $data where id = $id");
		}
		if($save){
			return 1;
		}
	}
	function delete_department(){
		extract($_POST);
		$delete = $this->db->query("DELETE FROM department_list where id = $id");
		if($delete){
			return 1;
		}
	}
	function save_designation(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id','user_ids')) && !is_numeric($k)){
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}
		$chk = $this->db->query("SELECT * FROM designation_list where designation = '$designation' and id != '{$id}' ")->num_rows;
		if($chk > 0){
			return 2;
		}
		if(isset($user_ids)){
			$data .= ", user_ids='".implode(',',$user_ids)."' ";
		}
		if(empty($id)){
			$save = $this->db->query("INSERT INTO designation_list set $data");
		}else{
			$save = $this->db->query("UPDATE designation_list set $data where id = $id");
		}
		if($save){
			return 1;
		}
	}
	function delete_designation(){
		extract($_POST);
		$delete = $this->db->query("DELETE FROM designation_list where id = $id");
		if($delete){
			return 1;
		}
	}

	function save_employee(){
		extract($_POST);
		
		$employee_id = $this->db->real_escape_string($employee_id);
		$firstname = $this->db->real_escape_string($firstname);
		$lastname = $this->db->real_escape_string($lastname);
		$email = $this->db->real_escape_string($email);
		
		$check_stmt = $this->db->prepare("SELECT id FROM employee_list WHERE email = ? " . (!empty($id) ? " AND id != ? " : ""));
		if(!empty($id)){
			$check_stmt->bind_param('si', $email, $id);
		} else {
			$check_stmt->bind_param('s', $email);
		}
		$check_stmt->execute();
		$check = $check_stmt->get_result();
		$check_stmt->close();
		
		if($check->num_rows > 0){
			return 2;
		}
		
		$avatar = 'no-image-available.png';
		if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
			$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
			if(move_uploaded_file($_FILES['img']['tmp_name'],'assets/uploads/'. $fname)){
				$avatar = $fname;
			}
		}
		
		if(empty($id)){
			$password_hash = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : '';
			$stmt = $this->db->prepare("INSERT INTO employee_list (employee_id, firstname, lastname, email, password, department_id, designation_id, evaluator_id, avatar) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
			$stmt->bind_param('ssssssiss', $employee_id, $firstname, $lastname, $email, $password_hash, $department_id, $designation_id, $evaluator_id, $avatar);
			$save = $stmt->execute();
			$stmt->close();
		} else {
			if(!empty($password)){
				$password_hash = password_hash($password, PASSWORD_DEFAULT);
				$stmt = $this->db->prepare("UPDATE employee_list SET employee_id=?, firstname=?, lastname=?, email=?, password=?, department_id=?, designation_id=?, evaluator_id=?, avatar=? WHERE id=?");
				$stmt->bind_param('ssssssissi', $employee_id, $firstname, $lastname, $email, $password_hash, $department_id, $designation_id, $evaluator_id, $avatar, $id);
			} else {
				$stmt = $this->db->prepare("UPDATE employee_list SET employee_id=?, firstname=?, lastname=?, email=?, department_id=?, designation_id=?, evaluator_id=?, avatar=? WHERE id=?");
				$stmt->bind_param('ssssissii', $employee_id, $firstname, $lastname, $email, $department_id, $designation_id, $evaluator_id, $avatar, $id);
			}
			$save = $stmt->execute();
			$stmt->close();
		}

		return $save ? 1 : 0;
	}
	function delete_employee(){
		extract($_POST);
		$id = intval($id);
		
		$this->db->query("DELETE FROM task_progress WHERE faculty_id = $id");
		$this->db->query("DELETE FROM ratings WHERE employee_id = $id");
		$this->db->query("DELETE FROM comments WHERE employee_id = $id");
		$this->db->query("DELETE FROM renewal_recommendations WHERE faculty_id = $id");
		
		$delete = $this->db->query("DELETE FROM employee_list WHERE id = $id");
		
		if($this->db->error) {
			return 0;
		}
		
		if($delete)
			return 1;
		return 0;
	}
	function save_evaluator(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id','cpass','password')) && !is_numeric($k)){
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}
		if(!empty($password)){
					$data .= ", password=md5('$password') ";

		}
		$check = $this->db->query("SELECT * FROM evaluator_list where email ='$email' ".(!empty($id) ? " and id != {$id} " : ''))->num_rows;
		if($check > 0){
			return 2;
			exit;
		}
		if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
			$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
			$move = move_uploaded_file($_FILES['img']['tmp_name'],'assets/uploads/'. $fname);
			$data .= ", avatar = '$fname' ";

		}
		if(empty($id)){
			if(!isset($_FILES['img']) || (isset($_FILES['img']) && $_FILES['img']['tmp_name'] == '')){	
				$data .= ", avatar = 'no-image-available.png' ";
			}
			$save = $this->db->query("INSERT INTO evaluator_list set $data");
		}else{
			$save = $this->db->query("UPDATE evaluator_list set $data where id = $id");
		}

		if($save){
			return 1;
		}
	}
	function delete_evaluator(){
		extract($_POST);
		$delete = $this->db->query("DELETE FROM evaluator_list where id = ".$id);
		if($delete)
			return 1;
	}
	function save_academic_rank(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id')) && !is_numeric($k)){
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}
		$chk = $this->db->query("SELECT * FROM position_list where position = '$position' and id != '{$id}' ")->num_rows;
		if($chk > 0){
			return 2;
		}
		if(empty($id)){
			$save = $this->db->query("INSERT INTO position_list set $data");
		}else{
			$save = $this->db->query("UPDATE position_list set $data where id = $id");
		}
		if($save){
			return 1;
		}
	}
	function delete_academic_rank(){
		extract($_POST);
		$delete = $this->db->query("DELETE FROM position_list where id = $id");
		if($delete){
			return 1;
		}
	}
	function update_evaluator_department(){
		extract($_POST);
		$update = $this->db->query("UPDATE evaluator_list set department_id = '$department_id' where id = $id");
		if($update)
			return 1;
	}
	function save_task(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id')) && !is_numeric($k)){
				// Escape quotes for safety
				$v = $this->db->real_escape_string($v);
	
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}
	
		if(empty($id)){
			// Insert new record, include date_created
			$save = $this->db->query("INSERT INTO task_list SET $data, date_created=NOW()");
		}else{
			// Update existing record (don’t touch date_created)
			$save = $this->db->query("UPDATE task_list SET $data WHERE id = $id");
		}
	
		if($save){
			return 1;
		}
	}
	
	function delete_task(){
		extract($_POST);
		$delete = $this->db->query("DELETE FROM task_list where id = $id");
		if($delete){
			return 1;
		}
	}
	
	function get_exemptions(){
		extract($_POST);
		$task_id = intval($task_id);
		
		$qry = $this->db->query("SELECT e.*, p.position as position_name 
			FROM target_exemptions e 
			LEFT JOIN position_list p ON e.position_id = p.id 
			WHERE e.task_id = $task_id 
			ORDER BY e.date_created DESC");
		
		$exemptions = [];
		while($row = $qry->fetch_assoc()){
			$exemptions[] = $row;
		}
		
		return json_encode(['status' => 'success', 'exemptions' => $exemptions]);
	}
	
	function save_exemption(){
		extract($_POST);
		$task_id = intval($task_id);
		$position_id = intval($position_id);
		
		$sql = "INSERT INTO target_exemptions (task_id, position_id) VALUES ($task_id, $position_id)";
		
		$save = $this->db->query($sql);
		if($save){
			return json_encode(['status' => 'success']);
		} else {
			return json_encode(['status' => 'error', 'message' => 'Failed to save exemption']);
		}
	}
	
	function delete_exemption(){
		extract($_POST);
		$id = intval($id);
		$delete = $this->db->query("DELETE FROM target_exemptions WHERE id = $id");
		if($delete){
			return json_encode(['status' => 'success']);
		} else {
			return json_encode(['status' => 'error']);
		}
	}
	function save_rating(){
		extract($_POST);
		$progress_id = intval($progress_id);
		$field = $this->db->real_escape_string($field);
		$value = floatval($value);
	
		// Get task_id and employee_id from task_progress
		$qry = $this->db->query("SELECT task_id, faculty_id FROM task_progress WHERE id = $progress_id");
		if($qry->num_rows > 0){
			$row = $qry->fetch_assoc();
			$task_id = $row['task_id'];
			$employee_id = $row['faculty_id'];
		}
	
		// Check if rating already exists
		$check = $this->db->query("SELECT id FROM ratings WHERE task_id = $task_id AND employee_id = $employee_id");
		if($check->num_rows > 0){
			$rating = $check->fetch_assoc();
			$rating_id = $rating['id'];
			// Update the specific field
			$save_rating = $this->db->query("UPDATE ratings SET $field = $value WHERE id = $rating_id");
			return 1;
		}else{
			// Insert new record
			$save_rating = $this->db->query("INSERT INTO ratings (task_id, employee_id, $field) VALUES ($task_id, $employee_id, $value)");
			return 1;
		}
	
		
	}

	function save_comment(){
		if(isset($_POST['faculty_id']) && isset($_POST['evaluator_id']) && isset($_POST['comment'])){
			$faculty_id = intval($_POST['faculty_id']);
			$evaluator_id = intval($_POST['evaluator_id']);
			$comment = $this->db->real_escape_string($_POST['comment']);
			
			
			// Check if comment already exists for this faculty-evaluator combination
			$check = $this->db->query("SELECT id FROM comments WHERE employee_id = $faculty_id AND rater_id = $evaluator_id");
			
			if($check->num_rows > 0){
				// Update existing comment
				$comment_row = $check->fetch_assoc();
				$comment_id = $comment_row['id'];
				$save_comment = $this->db->query("UPDATE comments SET comment_text = '$comment', date_updated = CURRENT_TIMESTAMP WHERE id = $comment_id");
			} else {
				// Insert new comment
				$save_comment = $this->db->query("INSERT INTO comments (employee_id, rater_id, comment_text) VALUES ($faculty_id, $evaluator_id, '$comment')");
			}
			
			if($save_comment){
				return 1;
			} else {
				return 0;
			}
		} else {
			return 0;
		}
	}
	function save_status() {
    header('Content-Type: application/json'); // make sure AJAX expects JSON

    // --- 1️⃣ Retrieve and sanitize POST inputs ---
    $id      = isset($_POST['id']) ? (int) $_POST['id'] : null;         // task_id
    $value   = isset($_POST['status']) ? trim($_POST['status']) : null;   // progress value
    $faculty = isset($_POST['faculty']) ? (int) $_POST['faculty'] : null; // faculty_id

    // 🪵 Debug: log raw POST data and processed vars
    error_log("RAW POST: " . json_encode($_POST));
    error_log("Processed => id: {$id}, faculty: {$faculty}, value: '{$value}'");

    // --- 2️⃣ Validate inputs ---
    if ($id === null || $faculty === null || $value === null || $value === '') {
        echo json_encode([
            "status" => "error",
            "message" => "Missing or invalid parameters.",
            "debug" => [
                "id" => $id,
                "faculty" => $faculty,
                "value" => $value,
                "_POST" => $_POST
            ]
        ]);
        return;
    }

    // --- 3️⃣ Get user/session info for audit trail ---
    $userId    = $_SESSION['login_id']   ?? null;
    $username  = $_SESSION['login_email'] ?? 'Unknown';
    $ip        = $_SERVER['REMOTE_ADDR']  ?? '0.0.0.0';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

    // --- 4️⃣ Escape strings safely ---
    $value      = $this->db->real_escape_string($value);
    $username   = $this->db->real_escape_string($username);
    $ip         = $this->db->real_escape_string($ip);
    $userAgent  = $this->db->real_escape_string($userAgent);
    $activity_log = '';

    // --- 4.5️⃣ Validate ratings before allowing status change to Verified ---
    if ($value === 'Verified') {
        $taskCheck = $this->db->query("SELECT efficiency AS task_eff, timeliness AS task_time, quality AS task_qual FROM task_list WHERE id = $id LIMIT 1");
        if (!$taskCheck || $taskCheck->num_rows == 0) {
            echo json_encode([
                "status" => "error",
                "message" => "Task not found."
            ]);
            return;
        }
        $taskRow = $taskCheck->fetch_assoc();
        
        $ratingCheck = $this->db->query("SELECT efficiency, timeliness, quality FROM ratings WHERE employee_id = $faculty AND task_id = $id LIMIT 1");
        if (!$ratingCheck || $ratingCheck->num_rows == 0) {
            $missingRatings = [];
            if ($taskRow['task_eff'] === 'Applicable' && empty($this->db->query("SELECT efficiency FROM ratings WHERE employee_id = $faculty AND task_id = $id")->fetch_assoc()['efficiency'] ?? null)) {
                $missingRatings[] = 'Efficiency';
            }
            if ($taskRow['task_time'] === 'Applicable' && empty($this->db->query("SELECT timeliness FROM ratings WHERE employee_id = $faculty AND task_id = $id")->fetch_assoc()['timeliness'] ?? null)) {
                $missingRatings[] = 'Timeliness';
            }
            if ($taskRow['task_qual'] === 'Applicable' && empty($this->db->query("SELECT quality FROM ratings WHERE employee_id = $faculty AND task_id = $id")->fetch_assoc()['quality'] ?? null)) {
                $missingRatings[] = 'Quality';
            }
            if (count($missingRatings) > 0) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Cannot verify without ratings. Please set " . implode(', ', $missingRatings) . " ratings first."
                ]);
                return;
            }
        } else {
            $ratingRow = $ratingCheck->fetch_assoc();
            $missingRatings = [];
            if ($taskRow['task_eff'] === 'Applicable' && (empty($ratingRow['efficiency']) || $ratingRow['efficiency'] === '')) {
                $missingRatings[] = 'Efficiency';
            }
            if ($taskRow['task_time'] === 'Applicable' && (empty($ratingRow['timeliness']) || $ratingRow['timeliness'] === '')) {
                $missingRatings[] = 'Timeliness';
            }
            if ($taskRow['task_qual'] === 'Applicable' && (empty($ratingRow['quality']) || $ratingRow['quality'] === '')) {
                $missingRatings[] = 'Quality';
            }
            if (count($missingRatings) > 0) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Cannot verify. Please complete " . implode(', ', $missingRatings) . " ratings first."
                ]);
                return;
            }
        }
    }

    // --- 5️⃣ Check if the record exists ---
    $check = $this->db->query("SELECT id, progress FROM task_progress WHERE task_id = $id AND faculty_id = $faculty LIMIT 1");

    if ($check && $check->num_rows > 0) {
        $row = $check->fetch_assoc();
        $progressId = (int) $row['id'];
        $oldStatus = $this->db->real_escape_string($row['progress']);
        $activity_log = "Changed status from '{$oldStatus}' to '{$value}' for faculty ID {$faculty} (task ID {$id})";
        
        if ($value === 'For Verification') {
            $this->db->query("DELETE FROM ratings WHERE employee_id = $faculty AND task_id = $id");
            $save_status = $this->db->query("UPDATE task_progress SET progress = '$value', date_verified = NULL WHERE id = $progressId");
        } elseif ($value === 'Verified') {
            $save_status = $this->db->query("UPDATE task_progress SET progress = '$value', date_verified = NOW() WHERE id = $progressId");
        } else {
            $save_status = $this->db->query("UPDATE task_progress SET progress = '$value' WHERE id = $progressId");
        }
    } else {
        $activity_log = "Created new status '{$value}' for faculty ID {$faculty} (task ID {$id})";
        if ($value === 'Verified') {
            $save_status = $this->db->query("INSERT INTO task_progress (task_id, faculty_id, progress, date_verified) VALUES ($id, $faculty, '$value', NOW())");
        } else {
            $save_status = $this->db->query("INSERT INTO task_progress (task_id, faculty_id, progress) VALUES ($id, $faculty, '$value')");
        }
    }

    // --- 6️⃣ Log to audit trail and send JSON result ---
    if ($save_status) {
        $activity_log = $this->db->real_escape_string($activity_log);
        $this->db->query("
            INSERT INTO login_audit_trail 
            (user_id, username, ip_address, user_agent, login_status, failure_reason)
            VALUES ('$userId', '$username', '$ip', '$userAgent', 'SUCCESS', '$activity_log')
        ");
        echo json_encode(["status" => "success", "message" => "Status saved successfully"]);
    } else {
        $error = $this->db->error;
        $error_msg = $this->db->real_escape_string("Failed to update status: $error");

        $this->db->query("
            INSERT INTO login_audit_trail 
            (user_id, username, ip_address, user_agent, login_status, failure_reason)
            VALUES ('$userId', '$username', '$ip', '$userAgent', 'FAILED', '$error_msg')
        ");
        echo json_encode([
            "status" => "error",
            "message" => "Database update failed",
            "debug" => $error
        ]);
    }
}

function submit_file() {
    header('Content-Type: application/json');
    
    $task_id = isset($_POST['task_id']) ? (int) $_POST['task_id'] : 0;
    $faculty_id = $_SESSION['login_id'] ?? 0;
    
    if ($task_id === 0 || $faculty_id === 0) {
        echo json_encode(["status" => "error", "message" => "Invalid task or faculty ID."]);
        return;
    }
    
    if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(["status" => "error", "message" => "No file uploaded or upload error."]);
        return;
    }
    
    $file = $_FILES['document'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    $allowedTypes = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif', 'xls', 'xlsx', 'ppt', 'pptx'];
    if (!in_array($fileType, $allowedTypes)) {
        echo json_encode(["status" => "error", "message" => "File type not allowed."]);
        return;
    }
    
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $newFileName = bin2hex(random_bytes(16));
    $destPath = $uploadDir . $newFileName . "." . $fileType;
    
    if (move_uploaded_file($fileTmpName, $destPath)) {
        $check = $this->db->query("SELECT id FROM task_progress WHERE task_id = $task_id AND faculty_id = $faculty_id LIMIT 1");
        
        if ($check && $check->num_rows > 0) {
            $row = $check->fetch_assoc();
            $progressId = (int) $row['id'];
            $this->db->query("UPDATE task_progress SET file_path = '$uploadDir$newFileName', file_type = '$fileType', progress = 'For Verification', date_created = NOW() WHERE id = $progressId");
        } else {
            $this->db->query("INSERT INTO task_progress (task_id, faculty_id, file_path, file_type, progress, date_created) VALUES ($task_id, $faculty_id, '$uploadDir$newFileName', '$fileType', 'For Verification', NOW())");
        }
        
        echo json_encode(["status" => "success", "message" => "File submitted successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to move uploaded file."]);
    }
}

	
	function delete_file(){
		extract($_POST);
		 // Get user info for audit
		 $userId   = $_SESSION['login_id'] ?? null;
		 $username = $_SESSION['login_email'] ?? 'Unknown';
		 $ip       = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
		 $userAgent= $_SERVER['HTTP_USER_AGENT'] ?? '';
		 // Safety check
		 if (empty($task_id) || empty($faculty_id)) {
			echo 0;
			exit;
		}
	
		// Delete record from task_progress
		$delete = $this->db->query("DELETE FROM task_progress WHERE task_id = $task_id AND faculty_id = $faculty_id");
		$activity_log = 'Deleted file successfuly';
		if ($delete) {
			$this->db->query("INSERT INTO login_audit_trail (user_id, username, ip_address, user_agent, login_status, failure_reason) 
			 VALUES ('$userId', '$username', '$ip', '$userAgent', 'SUCCESS', '$activity_log')");
			return 1; // success
		} else {
			return 0; // failure
		}
	
	}
	
	
	function save_progress(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id')) && !is_numeric($k)){
				if($k == 'progress')
					$v = htmlentities(str_replace("'","&#x2019;",$v));
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}
		if(!isset($is_complete))
			$data .= ", is_complete=0 ";
		if(empty($id)){
			$save = $this->db->query("INSERT INTO task_progress set $data");
		}else{
			$save = $this->db->query("UPDATE task_progress set $data where id = $id");
		}
		if($save){
		if(!isset($is_complete))
			$this->db->query("UPDATE task_list set status = 1 where id = $task_id ");
		else
			$this->db->query("UPDATE task_list set status = 2 where id = $task_id ");
			return 1;
		}
	}
	function delete_progress(){
		extract($_POST);
		$delete = $this->db->query("DELETE FROM task_progress where id = $id");
		if($delete){
			return 1;
		}
	}
	function save_evaluation(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id')) && !is_numeric($k)){
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}
		$data .= ", evaluator_id = {$_SESSION['login_id']} ";
		if(empty($id)){
			$save = $this->db->query("INSERT INTO ratings set $data");
		}else{
			$save = $this->db->query("UPDATE ratings set $data where id = $id");
		}
		if($save){
		if(!isset($is_complete))
			return 1;
		}
	}
	function delete_evaluation(){
		extract($_POST);
		$delete = $this->db->query("DELETE FROM ratings where id = $id");
		if($delete){
			return 1;
		}
	}
	function get_emp_tasks(){
		extract($_POST);
		if(!isset($task_id))
		$get = $this->db->query("SELECT * FROM task_list where employee_id = $employee_id and status = 2 and id not in (SELECT task_id FROM ratings) ");
		else
		$get = $this->db->query("SELECT * FROM task_list where employee_id = $employee_id and status = 2 and id not in (SELECT task_id FROM ratings where task_id !='$task_id') ");
		$data = array();
		while($row=$get->fetch_assoc()){
			$data[] = $row;
		}
		return json_encode($data);
	}
	function get_progress(){
		extract($_POST);
		$get = $this->db->query("SELECT p.*,concat(u.firstname,' ',u.lastname) as uname,u.avatar FROM task_progress p inner join task_list t on t.id = p.task_id inner join employee_list u on u.id = t.employee_id where p.task_id = $task_id order by unix_timestamp(p.date_created) desc ");
		$data = array();
		while($row=$get->fetch_assoc()){
			$row['uname'] = ucwords($row['uname']);
			$row['progress'] = html_entity_decode($row['progress']);
			$row['date_created'] = date("M d, Y",strtotime($row['date_created']));
			$data[] = $row;
		}
		return json_encode($data);
	}

	function get_renewal_recommendations(){
		extract($_POST);
		$evaluator_id = isset($evaluator_id) ? intval($evaluator_id) : $_SESSION['login_id'];
		$rating_period = isset($rating_period) ? $rating_period : (isset($_SESSION['rating_period']) ? $_SESSION['rating_period'] : date('Y'));

		$qry = $this->db->query("
			SELECT rr.*, e.firstname, e.middlename, e.lastname, d.department,
				   ev.firstname as eval_firstname, ev.lastname as eval_lastname
			FROM renewal_recommendations rr
			LEFT JOIN employee_list e ON rr.faculty_id = e.id
			LEFT JOIN department_list d ON e.department_id = d.id
			LEFT JOIN evaluator_list ev ON rr.evaluator_id = ev.id
			WHERE rr.rating_period = '$rating_period'
			ORDER BY rr.created_at DESC
		");
		$data = array();
		while($row = $qry->fetch_assoc()){
			$row['faculty_name'] = ucwords($row['lastname'] . ', ' . $row['firstname'] . ' ' . $row['middlename']);
			$row['evaluator_name'] = ucwords($row['eval_firstname'] . ' ' . $row['eval_lastname']);
			$data[] = $row;
		}
		return json_encode($data);
	}

	function get_rec_details(){
		extract($_POST);
		$id = intval($id);
		
		$qry = $this->db->query("
			SELECT rr.*, e.firstname, e.middlename, e.lastname, d.department,
				   ev.firstname as eval_firstname, ev.lastname as eval_lastname
			FROM renewal_recommendations rr
			LEFT JOIN employee_list e ON rr.faculty_id = e.id
			LEFT JOIN department_list d ON e.department_id = d.id
			LEFT JOIN evaluator_list ev ON rr.evaluator_id = ev.id
			WHERE rr.id = $id
		");
		
		if($qry && $qry->num_rows > 0){
			$row = $qry->fetch_assoc();
			$row['faculty_name'] = ucwords($row['lastname'] . ', ' . $row['firstname'] . ' ' . $row['middlename']);
			$row['evaluator_name'] = ucwords($row['eval_firstname'] . ' ' . $row['eval_lastname']);
			$row['created_at'] = date('M d, Y H:i', strtotime($row['created_at']));
			return json_encode($row);
		}
		return json_encode(['error' => 'Not found']);
	}

	function update_rec(){
		extract($_POST);
		$id = intval($id);
		$overall_score = floatval($overall_score);
		$instruction_ave = isset($instruction_ave) && $instruction_ave !== '' ? floatval($instruction_ave) : 'NULL';
		$support_ave = isset($support_ave) && $support_ave !== '' ? floatval($support_ave) : 'NULL';
		$recommendation_status = $this->db->real_escape_string($recommendation_status);
		$system_reason = $this->db->real_escape_string($system_reason);
		
		$sql = "UPDATE renewal_recommendations SET 
			overall_score = $overall_score,
			instruction_ave = $instruction_ave,
			support_ave = $support_ave,
			recommendation_status = '$recommendation_status',
			system_generated_reason = '$system_reason'
			WHERE id = $id";
		
		$save = $this->db->query($sql);
		
		if($this->db->error) {
			return "Error: " . $this->db->error;
		}
		
		return $save ? 1 : 0;
	}

	function delete_rec(){
		extract($_POST);
		$id = intval($id);
		
		$delete = $this->db->query("DELETE FROM renewal_recommendations WHERE id = $id");
		
		if($this->db->error) {
			return "Error: " . $this->db->error;
		}
		
		return $delete ? 1 : 0;
	}

	function save_renewal_recommendation(){
		// Debug log
		$debug = "save_renewal_recommendation called\n";
		$debug .= "POST: " . print_r($_POST, true) . "\n";
		file_put_contents('debug_log.txt', $debug);
		
		extract($_POST);
		
		$faculty_id = intval($faculty_id);
		$evaluator_id = intval($evaluator_id);
		$rating_period = isset($rating_period) ? $this->db->real_escape_string($rating_period) : date('Y');
		$overall_score = isset($overall_score) && is_numeric($overall_score) ? floatval($overall_score) : 0;
		$instruction_ave_val = isset($instruction_ave) && $instruction_ave !== '' && is_numeric($instruction_ave) ? floatval($instruction_ave) : 'NULL';
		$support_ave_val = isset($support_ave) && $support_ave !== '' && is_numeric($support_ave) ? floatval($support_ave) : 'NULL';
		$total_tasks = isset($total_tasks) && is_numeric($total_tasks) ? intval($total_tasks) : 0;
		$verified_tasks = isset($verified_tasks) && is_numeric($verified_tasks) ? intval($verified_tasks) : 0;
		$avg_efficiency_val = isset($avg_efficiency) && $avg_efficiency !== '' && is_numeric($avg_efficiency) ? floatval($avg_efficiency) : 'NULL';
		$avg_timeliness_val = isset($avg_timeliness) && $avg_timeliness !== '' && is_numeric($avg_timeliness) ? floatval($avg_timeliness) : 'NULL';
		$avg_quality_val = isset($avg_quality) && $avg_quality !== '' && is_numeric($avg_quality) ? floatval($avg_quality) : 'NULL';
		$recommendation_status = isset($recommendation_status) ? $this->db->real_escape_string($recommendation_status) : 'Pending';
		$system_reason = isset($system_reason) ? $this->db->real_escape_string($system_reason) : '';

		$check = $this->db->query("SELECT id FROM renewal_recommendations WHERE faculty_id = $faculty_id AND rating_period = '$rating_period' LIMIT 1");
		
		if($this->db->error) {
			return "Error check: " . $this->db->error;
		}
		
		if($check && $check->num_rows > 0){
			$row = $check->fetch_assoc();
			$id = $row['id'];
			$sql = "UPDATE renewal_recommendations SET 
				overall_score = $overall_score,
				instruction_ave = $instruction_ave_val,
				support_ave = $support_ave_val,
				total_tasks = $total_tasks,
				verified_tasks = $verified_tasks,
				avg_efficiency = $avg_efficiency_val,
				avg_timeliness = $avg_timeliness_val,
				avg_quality = $avg_quality_val,
				recommendation_status = '$recommendation_status',
				system_generated_reason = '$system_reason'
				WHERE id = $id";
			$save = $this->db->query($sql);
		} else {
			$sql = "INSERT INTO renewal_recommendations 
				(faculty_id, evaluator_id, rating_period, overall_score, instruction_ave, support_ave, total_tasks, verified_tasks, 
				avg_efficiency, avg_timeliness, avg_quality, recommendation_status, system_generated_reason)
				VALUES 
				($faculty_id, $evaluator_id, '$rating_period', $overall_score, $instruction_ave_val, $support_ave_val, $total_tasks, $verified_tasks,
				$avg_efficiency_val, $avg_timeliness_val, $avg_quality_val, '$recommendation_status', '$system_reason')";
			$save = $this->db->query($sql);
		}
		
		if($this->db->error) {
			return "SQL Error: " . $this->db->error . " | SQL: " . $sql;
		}
		
		$debug .= "SQL: $sql\n";
		$debug .= "Save result: " . ($save ? "success" : "failed") . "\n";
		file_put_contents('debug_log.txt', $debug);
		
		if($save){
			return 1;
		}
		return 0;
	}

	function submit_dean_decision(){
		extract($_POST);
		
		$id = intval($id);
		$dean_decision = $this->db->real_escape_string($dean_decision);
		$dean_reason = $this->db->real_escape_string($dean_reason);
		
		$save = $this->db->query("UPDATE renewal_recommendations SET 
			dean_decision = '$dean_decision',
			dean_reason = '$dean_reason',
			dean_decision_date = NOW()
			WHERE id = $id
		");
		
		if($save){
			return 1;
		}
		return 0;
	}
	
	function update_semester(){
		extract($_POST);
	
		if(empty($semester) || empty($year)){
			return 0; // missing data
		}
	
		// Always update the latest record in rating_period (highest ID)
		$qry = $this->db->query("UPDATE rating_period 
								 SET semester = '$semester', year = '$year' 
								 WHERE id = '1'");
	
		if($qry){
			$_SESSION['current_semester'] = $semester;
			$_SESSION['current_year'] = $year;
			return 1;
		} else {
			return 0;
		}
	}


	function fetch_user_by_id(){	
		$id_number = $_POST['id_number'];
		$qry = $this->db->query("SELECT firstname, middlename, lastname, is_activated FROM employee_list WHERE employee_id = '$id_number' LIMIT 1");
		if($qry && $qry->num_rows > 0){
			$row = $qry->fetch_assoc();
			echo json_encode(['status'=>'found','firstname'=>$row['firstname'],'middlename'=>$row['middlename'],'lastname'=>$row['lastname'],'is_activated'=>$row['is_activated']]);
		} else {
			echo json_encode(['status'=>'not_found']);
		}
	}
	function register_user(){
		extract($_POST);

    if($password !== $cpassword){
        echo json_encode(['status'=>'error','message'=>'Passwords do not match']);
        exit;
    }

    $id_number = $this->db->real_escape_string($id_number);
    $email = $this->db->real_escape_string($email);
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $firstname = $this->db->real_escape_string($firstname);
    $middlename = $this->db->real_escape_string($middlename);
    $lastname = $this->db->real_escape_string($lastname);
    $token = bin2hex(random_bytes(50));

    $chk_stmt = $this->db->prepare("SELECT id FROM employee_list WHERE email=? AND employee_id=?");
    $chk_stmt->bind_param('ss', $email, $id_number);
    $chk_stmt->execute();
    $chk = $chk_stmt->get_result();
    $chk_stmt->close();
    
    if($chk && $chk->num_rows > 0){
        echo json_encode(['status'=>'error','message'=>'Email or ID already registered']);
        exit;
    }

    $stmt = $this->db->prepare("UPDATE employee_list SET email = ?, password = ?, reset_token = ? WHERE employee_id = ?");
    $stmt->bind_param('ssss', $email, $password_hash, $token, $id_number);
    $register = $stmt->execute();
    $stmt->close();
    
	$verification_link = SYSTEM_URL . "/verify.php?code=" . $token;

    if($register){
        $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
                .email-container { max-width: 600px; margin: 30px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
                .email-header { background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); color: #ffffff; padding: 30px; text-align: center; }
                .email-header h2 { margin: 0; font-size: 24px; }
                .email-body { padding: 30px; }
                .email-body p { color: #333333; line-height: 1.6; margin-bottom: 20px; }
                .email-body .icon-box { text-align: center; margin: 20px 0; }
                .email-body .icon-box i { font-size: 48px; color: #007bff; }
                .btn-verify { display: inline-block; background: #007bff; color: #ffffff; text-decoration: none; padding: 12px 30px; border-radius: 5px; font-weight: bold; margin: 20px 0; }
                .btn-verify:hover { background: #0056b3; }
                .verification-link { word-break: break-all; color: #007bff; font-size: 14px; }
                .email-footer { background: #f8f9fa; padding: 20px; text-align: center; color: #6c757d; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='email-container'>
                <div class='email-header'>
                    <h2><i class='fa fa-envelope-open'></i> Activate Your Account</h2>
                </div>
                <div class='email-body'>
                    <div class='icon-box'>
                        <i class='fa fa-user-circle'></i>
                    </div>
                    <p>Hi <strong>" . $firstname . "</strong>,</p>
                    <p>Welcome to " . $_SESSION['system']['name'] . "! Please click the button below to activate your account:</p>
                    <p style='text-align: center;'>
                        <a href='" . $verification_link . "' class='btn-verify'>Activate Account</a>
                    </p>
                    <p>Or copy and paste this link into your browser:</p>
                    <p class='verification-link'>" . $verification_link . "</p>
                    <p>If you did not request this activation, please ignore this email.</p>
                </div>
                <div class='email-footer'>
                    <p>&copy; " . date('Y') . " " . $_SESSION['system']['name'] . ". All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        if($this->sendEmail($email, $firstname, "Account Verification", $body)){
            echo json_encode(['status'=>'success']);
        } else {
            echo json_encode(['status'=>'error','message'=>'Email sending failed']);
        }
    } else {
        echo json_encode(['status'=>'error','message'=>$this->db->error]);
    }
	}
	
	function save_function_category(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id')) && !is_numeric($k)){
				$v = $this->db->real_escape_string($v);
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}
		if(empty($id)){
			$save = $this->db->query("INSERT INTO function_categories SET $data");
		}else{
			$save = $this->db->query("UPDATE function_categories SET $data WHERE id = $id");
		}
		if($save){
			return 1;
		}
	}
	
	function get_function_category(){
		extract($_POST);
		$qry = $this->db->query("SELECT * FROM function_categories WHERE id = $id");
		echo json_encode($qry->fetch_assoc());
	}
	
	function delete_function_category(){
		extract($_POST);
		$delete = $this->db->query("DELETE FROM function_categories WHERE id = $id");
		if($delete){
			return 1;
		}
	}
	
	function save_percentage_allocation(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id')) && !is_numeric($k)){
				$v = $this->db->real_escape_string($v);
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}
		if(empty($id)){
			$save = $this->db->query("INSERT INTO percentage_allocation SET $data");
		}else{
			$save = $this->db->query("UPDATE percentage_allocation SET $data WHERE id = $id");
		}
		if($save){
			return 1;
		}
	}
	
	function get_percentage_allocation(){
		extract($_POST);
		$qry = $this->db->query("SELECT * FROM percentage_allocation WHERE id = $id");
		echo json_encode($qry->fetch_assoc());
	}
	
	function delete_percentage_allocation(){
		extract($_POST);
		$delete = $this->db->query("DELETE FROM percentage_allocation WHERE id = $id");
		if($delete){
			return 1;
		}
	}
	
	function save_percentage_allocation_quick(){
		extract($_POST);
		
		$position_id = intval($position_id);
		$designation_id = ($designation_id === '' || $designation_id === '0') ? 3 : intval($designation_id);
		$percentage = floatval($percentage);
		$category = $this->db->real_escape_string($category);
		$sub_category = !empty($sub_category) ? $this->db->real_escape_string($sub_category) : null;
		
		$sub_cond = $sub_category ? "sub_category = '$sub_category'" : "(sub_category IS NULL OR sub_category = '')";
		
		$check = $this->db->query("SELECT id FROM percentage_allocation 
			WHERE position_id = $position_id 
			AND designation_id = $designation_id
			AND category = '$category'
			AND $sub_cond");
		
		if($check && $check->num_rows > 0) {
			if($percentage > 0) {
				$this->db->query("UPDATE percentage_allocation SET percentage = $percentage 
					WHERE position_id = $position_id 
					AND designation_id = $designation_id
					AND category = '$category'
					AND $sub_cond");
			} else {
				$this->db->query("DELETE FROM percentage_allocation 
					WHERE position_id = $position_id 
					AND designation_id = $designation_id
					AND category = '$category'
					AND $sub_cond");
			}
		} else {
			if($percentage > 0) {
				$sub_val = $sub_category ? "'$sub_category'" : "NULL";
				$this->db->query("INSERT INTO percentage_allocation (position_id, designation_id, category, sub_category, percentage, is_active) 
					VALUES ($position_id, $designation_id, '$category', $sub_val, $percentage, 1)");
			}
		}
		return 1;
	}
	
	// MOV Management Functions
	function get_faculty_movs(){
		$faculty_id = $_SESSION['login_id'];
		$rating_period = $this->db->real_escape_string($_POST['rating_period'] ?? '');
		$status = $this->db->real_escape_string($_POST['status'] ?? '');
		$target_id = intval($_POST['target_id'] ?? 0);
		
		$where = "WHERE m.faculty_id = $faculty_id";
		if (!empty($rating_period)) {
			$where .= " AND m.rating_period = '$rating_period'";
		}
		if (!empty($status)) {
			$where .= " AND m.status = '$status'";
		}
		if ($target_id > 0) {
			$where .= " AND m.target_id = $target_id";
		}
		
		$query = $this->db->query("SELECT m.*, 
			COALESCE(t.major_output, t.success_indicators) as target_name,
			t.success_indicators,
			t.category,
			t.mfo,
			CONCAT(e.lastname, ', ', e.firstname, ' ', e.middlename) as faculty_name,
			DATE_FORMAT(m.date_submitted, '%Y-%m-%d %H:%i') as date_submitted
			FROM mov_uploads m
			LEFT JOIN task_list t ON m.target_id = t.id
			LEFT JOIN employee_list e ON m.faculty_id = e.id
			$where
			ORDER BY m.date_submitted DESC");
		
		$result = [];
		while ($row = $query->fetch_assoc()) {
			$file_size = $row['file_size'];
			$size_units = ['B', 'KB', 'MB', 'GB'];
			$size_index = 0;
			while ($file_size >= 1024 && $size_index < count($size_units) - 1) {
				$file_size /= 1024;
				$size_index++;
			}
			$row['file_size'] = round($file_size, 2) . ' ' . $size_units[$size_index];
			$result[] = $row;
		}
		
		echo json_encode($result);
	}
	
	function delete_mov(){
		extract($_POST);
		$id = intval($id);
		
		// Get file path before deleting
		$file = $this->db->query("SELECT file_path, file_type FROM mov_uploads WHERE id = $id")->fetch_assoc();
		
		$delete = $this->db->query("DELETE FROM mov_uploads WHERE id = $id");
		
		if ($delete) {
			// Delete physical file
			if ($file && file_exists($file['file_path'] . '.' . $file['file_type'])) {
				unlink($file['file_path'] . '.' . $file['file_type']);
			}
			return 1;
		}
		return 0;
	}
	
	function get_mov_summary(){
		$faculty_id = $_SESSION['login_id'];
		$rating_period = $this->db->real_escape_string($_POST['rating_period'] ?? '');
		
		$where = "WHERE faculty_id = $faculty_id";
		if (!empty($rating_period)) {
			$where .= " AND rating_period = '$rating_period'";
		}
		
		$query = $this->db->query("SELECT 
			rating_period,
			SUM(total_movs) as total_movs,
			SUM(verified_movs) as verified_movs,
			SUM(pending_movs) as pending_movs,
			SUM(rejected_movs) as rejected_movs,
			SUM(total_file_size) as total_file_size,
			MAX(last_submission) as last_submission
			FROM mov_summary
			$where
			GROUP BY rating_period
			ORDER BY rating_period DESC");
		
		$result = [];
		while ($row = $query->fetch_assoc()) {
			$file_size = $row['total_file_size'];
			$size_units = ['B', 'KB', 'MB', 'GB'];
			$size_index = 0;
			while ($file_size >= 1024 && $size_index < count($size_units) - 1) {
				$file_size /= 1024;
				$size_index++;
			}
			$row['total_file_size'] = round($file_size, 2) . ' ' . $size_units[$size_index];
			$result[] = $row;
		}
		
		echo json_encode($result);
	}
	
	function get_faculty_targets_with_movs(){
		$faculty_id = $_SESSION['login_id'];
		$rating_period = $this->db->real_escape_string($_POST['rating_period'] ?? '');
		$category = $this->db->real_escape_string($_POST['category'] ?? '');
		
		// Get faculty position and designation
		$faculty = $this->db->query("SELECT position_id, designation_id FROM employee_list WHERE id = $faculty_id")->fetch_assoc();
		$position_id = $faculty['position_id'] ?? 0;
		$designation_id = $faculty['designation_id'] ?? 0;
		$is_cos = ($position_id == 19);
		
		// Get percentage allocations
		$allocations = [];
		$alloc_qry = $this->db->query("SELECT * FROM percentage_allocation 
			WHERE position_id = $position_id 
			AND (designation_id IS NULL OR designation_id = $designation_id)
			AND is_active = 1");
		while ($row = $alloc_qry->fetch_assoc()) {
			$key = $row['category'];
			if ($row['sub_category']) {
				$key .= '_' . $row['sub_category'];
			}
			$allocations[$key] = floatval($row['percentage']);
		}
		
		// Build category filters (same as target_list.php)
		$cat_filters = [];
		$has_strategic = isset($allocations['strategic']) && $allocations['strategic'] > 0;
		if ($designation_id > 0) {
			$desig_qry = $this->db->query("SELECT designation FROM designation_list WHERE id = $designation_id");
			if ($desig_qry && $desig_row = $desig_qry->fetch_assoc()) {
				if (stripos($desig_row['designation'], 'Head') !== false || stripos($desig_row['designation'], 'Director') !== false) {
					$has_strategic = true;
				}
			}
		}
		$has_instructions = isset($allocations['core_instructions']) && $allocations['core_instructions'] > 0;
		$has_research = isset($allocations['core_research']) && $allocations['core_research'] > 0 && !$is_cos;
		$has_extension = isset($allocations['core_extension']) && $allocations['core_extension'] > 0 && !$is_cos;
		$has_support = isset($allocations['support']) && $allocations['support'] > 0;
		
		if ($has_strategic) $cat_filters[] = "t.category = 'strategic'";
		if ($has_instructions) $cat_filters[] = "(t.category = 'core' AND (t.sub_category IS NULL OR t.sub_category IN ('instructions','ter','instruction')))";
		if ($has_research) $cat_filters[] = "(t.category = 'core' AND t.sub_category = 'research')";
		if ($has_extension) $cat_filters[] = "(t.category = 'core' AND t.sub_category = 'extension')";
		if ($has_support) $cat_filters[] = "t.category = 'support'";
		
		$category_where = !empty($cat_filters) ? " AND (" . implode(" OR ", $cat_filters) . ")" : "";
		
		// Get all targets for this faculty (excluding exempted)
		$query = $this->db->query("SELECT DISTINCT t.id, 
			COALESCE(t.major_output, t.success_indicators) as target_display,
			t.major_output,
			t.success_indicators,
			t.category, 
			t.sub_category, 
			t.mfo
			FROM task_list t
			LEFT JOIN target_exemptions te ON t.id = te.task_id AND te.position_id = $position_id
			WHERE t.is_active = 1
			AND (t.academic_rank_id IS NULL OR t.academic_rank_id = 0 OR t.academic_rank_id = $position_id)
			AND (t.designation_id IS NULL OR t.designation_id = 0 OR t.designation_id = $designation_id)
			AND te.id IS NULL
			$category_where
			ORDER BY t.category, t.sub_category, t.mfo");
		
		$result = [];
		while ($row = $query->fetch_assoc()) {
			$target_id = $row['id'];
			
			// Count MOVs for this target
			$mov_where = "WHERE m.faculty_id = $faculty_id AND m.target_id = $target_id";
			if (!empty($rating_period)) {
				$mov_where .= " AND m.rating_period = '$rating_period'";
			}
			
			$mov_count = $this->db->query("SELECT COUNT(*) as c FROM mov_uploads m $mov_where")->fetch_assoc()['c'];
			
			$row['mov_count'] = $mov_count;
			$result[] = $row;
		}
		
		echo json_encode($result);
	}
	
}
