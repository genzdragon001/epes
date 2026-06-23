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

	/**
	 * Validate uploaded file extension against allowed types
	 */
	private function validateFileExtension($filename) {
		$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		return in_array($ext, ALLOWED_FILE_TYPES);
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
	
		// Search all 3 user tables for the reset token (forgot_password() supports all 3)
		$tables = ["employee_list", "evaluator_list", "users"];
		$user = null;
		$found_table = null;
	
		foreach ($tables as $table) {
			$stmt = $this->db->prepare("SELECT * FROM {$table} WHERE reset_token = ? AND reset_expires > NOW() LIMIT 1");
			$stmt->bind_param('s', $token);
			$stmt->execute();
			$qry = $stmt->get_result();
			$stmt->close();
	
			if ($qry->num_rows > 0) {
				$user = $qry->fetch_assoc();
				$found_table = $table;
				break;
			}
		}
	
		if (!$user) {
			return 3;
		}
	
		$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
	
		$update_stmt = $this->db->prepare("UPDATE {$found_table} SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
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
		$tables = array("employee_list", "users");
		$ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
		$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
	
		// IP-based rate limiting: max 10 attempts per IP per 5 minutes
		$rate_windows = 300;    // 5 minutes
		$rate_limit = 10;       // max attempts per window
		$cutoff = date("Y-m-d H:i:s", time() - $rate_windows);

		$stmt_rl = $this->db->prepare(
			"SELECT COUNT(*) AS cnt FROM login_audit_trail 
			 WHERE ip_address = ? AND login_time > ? AND login_status = 'FAILED'"
		);
		$stmt_rl->bind_param('ss', $ip_address, $cutoff);
		$stmt_rl->execute();
		$rl = $stmt_rl->get_result()->fetch_assoc();
		$stmt_rl->close();

		if ((int)$rl['cnt'] >= $rate_limit) {
			$this->logAudit(NULL, $email ?? 'unknown', $ip_address, $user_agent, "FAILED", "IP rate limit exceeded");
			return 5; // Rate limited — too many attempts from this IP
		}
	
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
		// After successful MD5 login, auto-migrates to bcrypt
		$passOk = false;
		$isMd5 = false;
		if (!empty($user['password'])) {
			$stored = (string)$user['password'];
			if (preg_match('/^[a-f0-9]{32}$/i', $stored)) {
				$passOk = hash_equals($stored, md5((string)$password));
				$isMd5 = true;
			} else {
				$passOk = password_verify((string)$password, $stored);
			}
		}
		if ($passOk) {
			$uid = (int)$user['id'];

			// Auto-migrate MD5 passwords to bcrypt on successful login
			if ($isMd5) {
				$newHash = password_hash((string)$password, PASSWORD_DEFAULT);
				$rh = $this->db->prepare("UPDATE {$table} SET password = ? WHERE id = ?");
				$rh->bind_param('si', $newHash, $uid);
				$rh->execute();
				$rh->close();
			}

			// Reset failed login count
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

			// If faculty login, check if they have an evaluator designation (Dean, Dept Head, VP, Director)
			$_SESSION['is_evaluator'] = false;
			$_SESSION['evaluator_role'] = '';
			if ($login == 0) {
				$desig_id = intval($user['designation_id'] ?? 0);
				$desig_qry = $this->db->query("SELECT designation FROM designation_list WHERE id = $desig_id");
				if ($desig_qry && $desig_row = $desig_qry->fetch_assoc()) {
					$desig_name = strtolower($desig_row['designation']);
					if (strpos($desig_name, 'dean') !== false) {
						$_SESSION['is_evaluator'] = true;
						$_SESSION['evaluator_role'] = 'dean';
					} elseif (strpos($desig_name, 'department head') !== false) {
						$_SESSION['is_evaluator'] = true;
						$_SESSION['evaluator_role'] = 'dept_head';
					} elseif (strpos($desig_name, 'vice president') !== false) {
						$_SESSION['is_evaluator'] = true;
						$_SESSION['evaluator_role'] = 'vp';
					} elseif (strpos($desig_name, 'director') !== false) {
						$_SESSION['is_evaluator'] = true;
						$_SESSION['evaluator_role'] = 'director';
					}
				}
			}

			// Session hardening: regenerate session id on privilege change/login
			session_regenerate_id(true);

			// "Remember Me" - set persistent cookie token
			if (isset($_POST['remember']) && $_POST['remember'] == '1') {
				$selector = bin2hex(random_bytes(16));
				$validator = bin2hex(random_bytes(32));
				$hashed_validator = hash('sha256', $validator);
				$expires = date('Y-m-d H:i:s', strtotime('+30 days'));
				
				$stmt = $this->db->prepare("INSERT INTO remember_tokens (user_id, user_type, selector, hashed_validator, expires) VALUES (?, ?, ?, ?, ?)");
				$stmt->bind_param('iisss', $uid, $login, $selector, $hashed_validator, $expires);
				$stmt->execute();
				$stmt->close();
				
				// Set cookie: selector:validator, expires in 30 days, httponly
				setcookie('remember_me', $selector . ':' . $validator, [
					'expires' => time() + (30 * 24 * 60 * 60),
					'path' => '/',
					'httponly' => true,
					'samesite' => 'Lax'
				]);
			}

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
			$user_id   = (int)$_SESSION['login_id'];
			$username  = isset($_SESSION['login_email']) ? $_SESSION['login_email'] : 'Unknown';
			$ip_address = $_SERVER['REMOTE_ADDR'];
			$user_agent = $_SERVER['HTTP_USER_AGENT'];
	
			// Insert logout record (prepared statement)
			$status = 'SUCCESS';
			$reason = 'User logged out';
			$lstmt = $this->db->prepare("INSERT INTO login_audit_trail 
				(user_id, username, ip_address, user_agent, login_status, failure_reason) 
				VALUES (?, ?, ?, ?, ?, ?)");
			$lstmt->bind_param('isssss', $user_id, $username, $ip_address, $user_agent, $status, $reason);
			$lstmt->execute();
			$lstmt->close();
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
		// Reset failed attempts and unblock account (prepared statement)
		$id_int = intval($id);
		$reset_stmt = $this->db->prepare("
			UPDATE employee_list
			SET failed_login = 0, isBlocked = 0 
			WHERE id = ? 
		");
		$reset_stmt->bind_param('i', $id_int);
		$reset = $reset_stmt->execute();
		$reset_stmt->close();
	
		if($reset){
			return 1; // success
		} else {
			return 0; // failed
		}
	}
	
	function reset_evaluator(){
		extract($_POST);
		// Reset failed attempts and unblock account (prepared statement)
		$id_int = intval($id);
		$reset_stmt = $this->db->prepare("
			UPDATE evaluator_list
			SET failed_login = 0, isBlocked = 0 
			WHERE id = ? 
		");
		$reset_stmt->bind_param('i', $id_int);
		$reset = $reset_stmt->execute();
		$reset_stmt->close();
	
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
		
		// Check for duplicate email using prepared statement
		if (!empty($id)) {
			$stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? AND id != ?");
			$stmt->bind_param('si', $email, $id);
		} else {
			$stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
			$stmt->bind_param('s', $email);
		}
		$stmt->execute();
		$check = $stmt->get_result()->num_rows;
		$stmt->close();
		
		if($check > 0){
			return 2;
		}
		
		$avatar = 'no-image-available.png';
		if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
			$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
			$move = move_uploaded_file($_FILES['img']['tmp_name'],'assets/uploads/'. $fname);
			$avatar = $fname;
		}
		
		$password_hash = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : '';
		$firstname = $_POST['firstname'] ?? '';
		$lastname = $_POST['lastname'] ?? '';
		
		if(empty($id)){
			$stmt = $this->db->prepare("INSERT INTO users (firstname, lastname, email, password, avatar) VALUES (?, ?, ?, ?, ?)");
			$stmt->bind_param('sssss', $firstname, $lastname, $email, $password_hash, $avatar);
		} else {
			if(!empty($password)){
				$stmt = $this->db->prepare("UPDATE users SET firstname=?, lastname=?, email=?, password=?, avatar=? WHERE id=?");
				$stmt->bind_param('sssssi', $firstname, $lastname, $email, $password_hash, $avatar, $id);
			} else {
				$stmt = $this->db->prepare("UPDATE users SET firstname=?, lastname=?, email=?, avatar=? WHERE id=?");
				$stmt->bind_param('ssssi', $firstname, $lastname, $email, $avatar, $id);
			}
		}
		$save = $stmt->execute();
		$stmt->close();

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
		
		// Whitelist table names
		$allowed_tables = ["employee_list", "evaluator_list", "users"];
		$type = ["employee_list", "evaluator_list", "users"];
		$table = $allowed_tables[$_SESSION['login_type']] ?? 'users';
		
		// Check for duplicate email using prepared statement
		if (!empty($id)) {
			$stmt = $this->db->prepare("SELECT * FROM {$table} WHERE email = ? AND id != ?");
			$stmt->bind_param('si', $email, $id);
		} else {
			$stmt = $this->db->prepare("SELECT * FROM {$table} WHERE email = ?");
			$stmt->bind_param('s', $email);
		}
		$stmt->execute();
		$check = $stmt->get_result()->num_rows;
		$stmt->close();
		
		if($check > 0){
			return 2;
		}
		
		$avatar = 'no-image-available.png';
		if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
			$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
			$move = move_uploaded_file($_FILES['img']['tmp_name'],'assets/uploads/'. $fname);
			$avatar = $fname;
		}
		
		$password_hash = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : '';
		$firstname = $_POST['firstname'] ?? '';
		$lastname = $_POST['lastname'] ?? '';
		
		if(empty($id)){
			if(!empty($password)){
				$stmt = $this->db->prepare("INSERT INTO {$table} (firstname, lastname, email, password, avatar) VALUES (?, ?, ?, ?, ?)");
				$stmt->bind_param('sssss', $firstname, $lastname, $email, $password_hash, $avatar);
			} else {
				$stmt = $this->db->prepare("INSERT INTO {$table} (firstname, lastname, email, avatar) VALUES (?, ?, ?, ?)");
				$stmt->bind_param('ssss', $firstname, $lastname, $email, $avatar);
			}
		} else {
			if(!empty($password)){
				$stmt = $this->db->prepare("UPDATE {$table} SET firstname=?, lastname=?, email=?, password=?, avatar=? WHERE id=?");
				$stmt->bind_param('sssssi', $firstname, $lastname, $email, $password_hash, $avatar, $id);
			} else {
				$stmt = $this->db->prepare("UPDATE {$table} SET firstname=?, lastname=?, email=?, avatar=? WHERE id=?");
				$stmt->bind_param('ssssi', $firstname, $lastname, $email, $avatar, $id);
			}
		}
		$save = $stmt->execute();
		$stmt->close();

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
		// Build safe key-value pairs for known system settings
		$allowed_keys = ['name', 'email', 'contact', 'address', 'cover_img'];
		$data = [];
		foreach($_POST as $k => $v){
			if(!is_numeric($k) && in_array($k, $allowed_keys)){
				$data[$k] = $this->db->real_escape_string($v);
			}
		}
		if(isset($_FILES['cover']) && $_FILES['cover']['tmp_name'] != ''){
			$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['cover']['name'];
			$move = move_uploaded_file($_FILES['cover']['tmp_name'],'../assets/uploads/'. $fname);
			if($move) $data['cover_img'] = $fname;
		}
		
		$chk = $this->db->query("SELECT * FROM system_settings");
		if($chk->num_rows > 0){
			$settings_id = intval($chk->fetch_array()['id']);
			$sets = [];
			foreach($data as $k => $v){
				$sets[] = "$k = '$v'";
			}
			$set_clause = implode(', ', $sets);
			if(!empty($set_clause)){
				$save = $this->db->query("UPDATE system_settings SET $set_clause WHERE id = $settings_id");
			}
		} else {
			$keys = implode(', ', array_keys($data));
			$vals = "'" . implode("', '", array_values($data)) . "'";
			if(!empty($keys)){
				$save = $this->db->query("INSERT INTO system_settings ($keys) VALUES ($vals)");
			}
		}
		if($save){
			foreach($_POST as $k => $v){
				if(!is_numeric($k)){
					$_SESSION['system'][$k] = $v;
				}
			}
			if(isset($fname)){
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
		$department_name = $_POST['department'] ?? '';
		$id = isset($id) ? intval($id) : null;
		
		$chk_stmt = $this->db->prepare("SELECT * FROM department_list WHERE department = ? AND id != ?");
		$chk_stmt->bind_param('si', $department_name, $id);
		$chk_stmt->execute();
		$chk = $chk_stmt->get_result()->num_rows;
		$chk_stmt->close();
		
		if($chk > 0){
			return 2;
		}
		$user_ids_str = isset($user_ids) ? implode(',', $user_ids) : '';
		
		if(empty($id)){
			$stmt = $this->db->prepare("INSERT INTO department_list (department, user_ids) VALUES (?, ?)");
			$stmt->bind_param('ss', $department_name, $user_ids_str);
		} else {
			$stmt = $this->db->prepare("UPDATE department_list SET department=?, user_ids=? WHERE id=?");
			$stmt->bind_param('ssi', $department_name, $user_ids_str, $id);
		}
		$save = $stmt->execute();
		$stmt->close();
		if($save){
			return 1;
		}
	}
	function delete_department(){
		extract($_POST);
		$id = intval($id);
		$stmt = $this->db->prepare("DELETE FROM department_list WHERE id = ?");
		$stmt->bind_param('i', $id);
		$delete = $stmt->execute();
		$stmt->close();
		if($delete){
			return 1;
		}
	}
	function save_designation(){
		extract($_POST);
		$designation_name = $_POST['designation'] ?? '';
		$id = isset($id) ? intval($id) : null;
		
		$chk_stmt = $this->db->prepare("SELECT * FROM designation_list WHERE designation = ? AND id != ?");
		$chk_stmt->bind_param('si', $designation_name, $id);
		$chk_stmt->execute();
		$chk = $chk_stmt->get_result()->num_rows;
		$chk_stmt->close();
		
		if($chk > 0){
			return 2;
		}
		$user_ids_str = isset($user_ids) ? implode(',', $user_ids) : '';
		
		if(empty($id)){
			$stmt = $this->db->prepare("INSERT INTO designation_list (designation, user_ids) VALUES (?, ?)");
			$stmt->bind_param('ss', $designation_name, $user_ids_str);
		} else {
			$stmt = $this->db->prepare("UPDATE designation_list SET designation=?, user_ids=? WHERE id=?");
			$stmt->bind_param('ssi', $designation_name, $user_ids_str, $id);
		}
		$save = $stmt->execute();
		$stmt->close();
		if($save){
			return 1;
		}
	}
	function delete_designation(){
		extract($_POST);
		$id = intval($id);
		$stmt = $this->db->prepare("DELETE FROM designation_list WHERE id = ?");
		$stmt->bind_param('i', $id);
		$delete = $stmt->execute();
		$stmt->close();
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
			$stmt = $this->db->prepare("INSERT INTO employee_list (employee_id, firstname, lastname, email, password, department_id, position_id, designation_id, evaluator_id, avatar) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
			$stmt->bind_param('sssssissis', $employee_id, $firstname, $lastname, $email, $password_hash, $department_id, $position_id, $designation_id, $evaluator_id, $avatar);
			$save = $stmt->execute();
			$stmt->close();
		} else {
			if(!empty($password)){
				$password_hash = password_hash($password, PASSWORD_DEFAULT);
				$stmt = $this->db->prepare("UPDATE employee_list SET employee_id=?, firstname=?, lastname=?, email=?, password=?, department_id=?, position_id=?, designation_id=?, evaluator_id=?, avatar=? WHERE id=?");
				$stmt->bind_param('ssssssissii', $employee_id, $firstname, $lastname, $email, $password_hash, $department_id, $position_id, $designation_id, $evaluator_id, $avatar, $id);
			} else {
				$stmt = $this->db->prepare("UPDATE employee_list SET employee_id=?, firstname=?, lastname=?, email=?, department_id=?, position_id=?, designation_id=?, evaluator_id=?, avatar=? WHERE id=?");
				$stmt->bind_param('ssssissiii', $employee_id, $firstname, $lastname, $email, $department_id, $position_id, $designation_id, $evaluator_id, $avatar, $id);
			}
			$save = $stmt->execute();
			$stmt->close();
		}

		return $save ? 1 : 0;
	}
	function delete_employee(){
		extract($_POST);
		$id = intval($id);
		
		$tables = ['task_progress' => 'faculty_id', 'ratings' => 'employee_id', 'comments' => 'employee_id', 'renewal_recommendations' => 'faculty_id'];
		foreach($tables as $table => $col){
			$stmt = $this->db->prepare("DELETE FROM {$table} WHERE {$col} = ?");
			$stmt->bind_param('i', $id);
			$stmt->execute();
			$stmt->close();
		}
		
		$stmt = $this->db->prepare("DELETE FROM employee_list WHERE id = ?");
		$stmt->bind_param('i', $id);
		$delete = $stmt->execute();
		$stmt->close();
		
		if($this->db->error) {
			return 0;
		}
		
		if($delete)
			return 1;
		return 0;
	}
	function save_evaluator(){
		extract($_POST);
		
		// Check for duplicate email using prepared statement
		if (!empty($id)) {
			$stmt = $this->db->prepare("SELECT * FROM evaluator_list WHERE email = ? AND id != ?");
			$stmt->bind_param('si', $email, $id);
		} else {
			$stmt = $this->db->prepare("SELECT * FROM evaluator_list WHERE email = ?");
			$stmt->bind_param('s', $email);
		}
		$stmt->execute();
		$check = $stmt->get_result()->num_rows;
		$stmt->close();
		
		if($check > 0){
			return 2;
		}
		
		$avatar = 'no-image-available.png';
		if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
			$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
			$move = move_uploaded_file($_FILES['img']['tmp_name'],'assets/uploads/'. $fname);
			$avatar = $fname;
		}
		
		$password_hash = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : '';
		$firstname = $_POST['firstname'] ?? '';
		$lastname = $_POST['lastname'] ?? '';
		$employee_id = $_POST['employee_id'] ?? '';
		
		if(empty($id)){
			if(!empty($password)){
				$stmt = $this->db->prepare("INSERT INTO evaluator_list (employee_id, firstname, lastname, email, password, avatar) VALUES (?, ?, ?, ?, ?, ?)");
				$stmt->bind_param('ssssss', $employee_id, $firstname, $lastname, $email, $password_hash, $avatar);
			} else {
				$stmt = $this->db->prepare("INSERT INTO evaluator_list (employee_id, firstname, lastname, email, avatar) VALUES (?, ?, ?, ?, ?)");
				$stmt->bind_param('sssss', $employee_id, $firstname, $lastname, $email, $avatar);
			}
		} else {
			if(!empty($password)){
				$stmt = $this->db->prepare("UPDATE evaluator_list SET employee_id=?, firstname=?, lastname=?, email=?, password=?, avatar=? WHERE id=?");
				$stmt->bind_param('ssssssi', $employee_id, $firstname, $lastname, $email, $password_hash, $avatar, $id);
			} else {
				$stmt = $this->db->prepare("UPDATE evaluator_list SET employee_id=?, firstname=?, lastname=?, email=?, avatar=? WHERE id=?");
				$stmt->bind_param('sssssi', $employee_id, $firstname, $lastname, $email, $avatar, $id);
			}
		}
		$save = $stmt->execute();
		$stmt->close();

		if($save){
			return 1;
		}
	}
	function delete_evaluator(){
		extract($_POST);
		$id = intval($id);
		$stmt = $this->db->prepare("DELETE FROM evaluator_list WHERE id = ?");
		$stmt->bind_param('i', $id);
		$delete = $stmt->execute();
		$stmt->close();
		if($delete)
			return 1;
	}
	function save_academic_rank(){
		extract($_POST);
		$position = $_POST['position'] ?? '';
		$id = isset($id) ? intval($id) : null;
		
		$stmt = $this->db->prepare("SELECT * FROM position_list WHERE position = ? AND id != ?");
		$stmt->bind_param('si', $position, $id);
		$stmt->execute();
		$chk = $stmt->get_result()->num_rows;
		$stmt->close();
		
		if($chk > 0){
			return 2;
		}
		if(empty($id)){
			$stmt = $this->db->prepare("INSERT INTO position_list (position) VALUES (?)");
			$stmt->bind_param('s', $position);
		} else {
			$stmt = $this->db->prepare("UPDATE position_list SET position=? WHERE id=?");
			$stmt->bind_param('si', $position, $id);
		}
		$save = $stmt->execute();
		$stmt->close();
		if($save){
			return 1;
		}
	}
	function delete_academic_rank(){
		extract($_POST);
		$id = intval($id);
		$stmt = $this->db->prepare("DELETE FROM position_list WHERE id = ?");
		$stmt->bind_param('i', $id);
		$delete = $stmt->execute();
		$stmt->close();
		if($delete){
			return 1;
		}
	}
	function update_evaluator_department(){
		extract($_POST);
		$id = intval($id);
		$department_id = intval($department_id);
		$stmt = $this->db->prepare("UPDATE evaluator_list SET department_id = ? WHERE id = ?");
		$stmt->bind_param('ii', $department_id, $id);
		$update = $stmt->execute();
		$stmt->close();
		if($update)
			return 1;
	}
	function save_task(){
		extract($_POST);
		$task_name = $_POST['task'] ?? '';
		$id = isset($id) ? intval($id) : null;
		$category = $_POST['category'] ?? '';
		$efficiency = $_POST['efficiency'] ?? '';
		$timeliness = $_POST['timeliness'] ?? '';
		$quality = $_POST['quality'] ?? '';
		
		if(empty($id)){
			$stmt = $this->db->prepare("INSERT INTO task_list (task, category, efficiency, timeliness, quality, date_created) VALUES (?, ?, ?, ?, ?, NOW())");
			$stmt->bind_param('sssss', $task_name, $category, $efficiency, $timeliness, $quality);
		} else {
			$stmt = $this->db->prepare("UPDATE task_list SET task=?, category=?, efficiency=?, timeliness=?, quality=? WHERE id=?");
			$stmt->bind_param('sssssi', $task_name, $category, $efficiency, $timeliness, $quality, $id);
		}
		$save = $stmt->execute();
		$stmt->close();
	
		if($save){
			return 1;
		}
	}
	
	function delete_task(){
		extract($_POST);
		$id = intval($id);
		$stmt = $this->db->prepare("DELETE FROM task_list WHERE id = ?");
		$stmt->bind_param('i', $id);
		$delete = $stmt->execute();
		$stmt->close();
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
		
		$stmt = $this->db->prepare("INSERT INTO target_exemptions (task_id, position_id) VALUES (?, ?)");
		$stmt->bind_param('ii', $task_id, $position_id);
		$save = $stmt->execute();
		$stmt->close();
		if($save){
			return json_encode(['status' => 'success']);
		} else {
			return json_encode(['status' => 'error', 'message' => 'Failed to save exemption']);
		}
	}
	
	function delete_exemption(){
		extract($_POST);
		$id = intval($id);
		$stmt = $this->db->prepare("DELETE FROM target_exemptions WHERE id = ?");
		$stmt->bind_param('i', $id);
		$delete = $stmt->execute();
		$stmt->close();
		if($delete){
			return json_encode(['status' => 'success']);
		} else {
			return json_encode(['status' => 'error']);
		}
	}
	function save_rating(){
		// Admin (login_type 2) is view-only — reject all rating operations
		if (($login_type = ($_SESSION['login_type'] ?? -1)) == 2) {
			return 0;
		}
		extract($_POST);
		$task_id = intval($task_id ?? 0);
		$employee_id = intval($faculty_id ?? 0);
		$value = floatval($value);
		
		// Whitelist allowed field names to prevent SQL injection through $field
		$allowed_fields = ['efficiency', 'timeliness', 'quality'];
		$field = in_array($field, $allowed_fields) ? $field : null;
		if ($field === null) {
			return 0;
		}
	
		if ($task_id === 0 || $employee_id === 0) {
			return 0;
		}

		// Get rating_period from POST or fall back to session/DB
		$rating_period = $rating_period ?? '';
		if (empty($rating_period)) {
			$rating_period = $_SESSION['rating_period'] ?? '';
		}
		if (empty($rating_period)) {
			// Fall back to latest active rating period
			$rp_qry = $this->db->query("SELECT code FROM rating_period ORDER BY id DESC LIMIT 1");
			if ($rp_qry && $rp_qry->num_rows > 0) {
				$rating_period = $rp_qry->fetch_assoc()['code'] ?? '';
			}
		}
		if (empty($rating_period)) {
			$rating_period = 'UNKNOWN';
		}

		// Individual ratings are always IPCR-level (DP/OPCR are computed aggregates)
		$period_type = 'IPCR';
	
		// Check if rating already exists
		$stmt = $this->db->prepare("SELECT id FROM ratings WHERE task_id = ? AND employee_id = ?");
		$stmt->bind_param('ii', $task_id, $employee_id);
		$stmt->execute();
		$check = $stmt->get_result();
		$stmt->close();
		if($check->num_rows > 0){
			$rating = $check->fetch_assoc();
			$rating_id = $rating['id'];
			$stmt = $this->db->prepare("UPDATE ratings SET {$field} = ?, rating_period = ?, period_type = ? WHERE id = ?");
			$stmt->bind_param('dssi', $value, $rating_period, $period_type, $rating_id);
			$stmt->execute();
			$stmt->close();
			return 1;
		} else {
			$stmt = $this->db->prepare("INSERT INTO ratings (task_id, employee_id, {$field}, rating_period, period_type) VALUES (?, ?, ?, ?, ?)");
			$stmt->bind_param('iidss', $task_id, $employee_id, $value, $rating_period, $period_type);
			$stmt->execute();
			$stmt->close();
			return 1;
		}
	
		
	}

	function save_comment(){
		// Admin (login_type 2) is view-only — reject all comment operations
		if (($login_type = ($_SESSION['login_type'] ?? -1)) == 2) {
			return 0;
		}
		if(isset($_POST['faculty_id']) && isset($_POST['evaluator_id']) && isset($_POST['comment'])){
			$faculty_id = intval($_POST['faculty_id']);
			$evaluator_id = intval($_POST['evaluator_id']);
			$comment = $_POST['comment'];
			
			// Check if comment already exists for this faculty-evaluator combination
			$stmt = $this->db->prepare("SELECT id FROM comments WHERE employee_id = ? AND rater_id = ?");
			$stmt->bind_param('ii', $faculty_id, $evaluator_id);
			$stmt->execute();
			$check = $stmt->get_result();
			$stmt->close();
			
			if($check->num_rows > 0){
				// Update existing comment
				$comment_row = $check->fetch_assoc();
				$comment_id = $comment_row['id'];
				$stmt = $this->db->prepare("UPDATE comments SET comment_text = ? WHERE id = ?");
				$stmt->bind_param('si', $comment, $comment_id);
			} else {
				// Insert new comment
				$stmt = $this->db->prepare("INSERT INTO comments (employee_id, rater_id, comment_text) VALUES (?, ?, ?)");
				$stmt->bind_param('iis', $faculty_id, $evaluator_id, $comment);
			}
			$save_comment = $stmt->execute();
			$stmt->close();
			
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
    // Admin (login_type 2) is view-only — reject all status operations
    if (($login_type = ($_SESSION['login_type'] ?? -1)) == 2) {
        echo json_encode(["status" => "error", "message" => "Administrator accounts are view-only."]);
        return;
    }
    header('Content-Type: application/json');

    $id      = isset($_POST['id']) ? (int) $_POST['id'] : null;
    $value   = isset($_POST['status']) ? trim($_POST['status']) : null;
    $faculty = isset($_POST['faculty']) ? (int) $_POST['faculty'] : null;

    error_log("Processed => id: {$id}, faculty: {$faculty}, value: '{$value}'");

    if ($id === null || $faculty === null || $value === null || $value === '') {
        echo json_encode(["status" => "error", "message" => "Missing or invalid parameters."]);
        return;
    }

    $userId    = $_SESSION['login_id']   ?? null;
    $username  = $_SESSION['login_email'] ?? 'Unknown';
    $ip        = $_SERVER['REMOTE_ADDR']  ?? '0.0.0.0';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $activity_log = '';

    // N/A Verified: skip rating requirement, set progress to 'Verified'
    $skip_auto_score = false;
    if ($value === 'N/A Verified') {
        $value = 'Verified';
        $skip_auto_score = true;
        // Skip the rating check below
    } elseif ($value === 'Verified') {
        $stmt = $this->db->prepare("SELECT efficiency AS task_eff, timeliness AS task_time, quality AS task_qual FROM task_list WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $taskCheck = $stmt->get_result();
        $stmt->close();
        if (!$taskCheck || $taskCheck->num_rows == 0) {
            echo json_encode(["status" => "error", "message" => "Task not found."]);
            return;
        }
        $taskRow = $taskCheck->fetch_assoc();
        
        $stmt = $this->db->prepare("SELECT efficiency, timeliness, quality FROM ratings WHERE employee_id = ? AND task_id = ? LIMIT 1");
        $stmt->bind_param('ii', $faculty, $id);
        $stmt->execute();
        $ratingCheck = $stmt->get_result();
        $stmt->close();
        if (!$ratingCheck || $ratingCheck->num_rows == 0) {
            $missingRatings = [];
            $stmt = $this->db->prepare("SELECT efficiency FROM ratings WHERE employee_id = ? AND task_id = ?");
            $stmt->bind_param('ii', $faculty, $id);
            $stmt->execute();
            if ($taskRow['task_eff'] === 'Applicable' && empty($stmt->get_result()->fetch_assoc()['efficiency'] ?? null)) {
                $missingRatings[] = 'Efficiency';
            }
            $stmt->close();
            $stmt = $this->db->prepare("SELECT timeliness FROM ratings WHERE employee_id = ? AND task_id = ?");
            $stmt->bind_param('ii', $faculty, $id);
            $stmt->execute();
            if ($taskRow['task_time'] === 'Applicable' && empty($stmt->get_result()->fetch_assoc()['timeliness'] ?? null)) {
                $missingRatings[] = 'Timeliness';
            }
            $stmt->close();
            $stmt = $this->db->prepare("SELECT quality FROM ratings WHERE employee_id = ? AND task_id = ?");
            $stmt->bind_param('ii', $faculty, $id);
            $stmt->execute();
            if ($taskRow['task_qual'] === 'Applicable' && empty($stmt->get_result()->fetch_assoc()['quality'] ?? null)) {
                $missingRatings[] = 'Quality';
            }
            $stmt->close();
            if (count($missingRatings) > 0) {
                echo json_encode(["status" => "error", "message" => "Cannot verify without ratings. Please set " . implode(', ', $missingRatings) . " ratings first."]);
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
                echo json_encode(["status" => "error", "message" => "Cannot verify. Please complete " . implode(', ', $missingRatings) . " ratings first."]);
                return;
            }
        }
    }

    // Check if record exists
    $stmt = $this->db->prepare("SELECT id, progress FROM task_progress WHERE task_id = ? AND faculty_id = ? LIMIT 1");
    $stmt->bind_param('ii', $id, $faculty);
    $stmt->execute();
    $check = $stmt->get_result();
    $stmt->close();

    if ($check && $check->num_rows > 0) {
        $row = $check->fetch_assoc();
        $progressId = (int) $row['id'];
        $oldStatus = $row['progress'];
        $activity_log = "Changed status from '{$oldStatus}' to '{$value}' for faculty ID {$faculty} (task ID {$id})";
        
        if ($value === 'For Verification') {
            $stmt = $this->db->prepare("DELETE FROM ratings WHERE employee_id = ? AND task_id = ?");
            $stmt->bind_param('ii', $faculty, $id);
            $stmt->execute();
            $stmt->close();
            $stmt = $this->db->prepare("UPDATE task_progress SET progress = ?, date_verified = NULL WHERE id = ?");
            $stmt->bind_param('si', $value, $progressId);
        } elseif ($value === 'Verified') {
            $stmt = $this->db->prepare("UPDATE task_progress SET progress = ?, date_verified = NOW() WHERE id = ?");
            $stmt->bind_param('si', $value, $progressId);
        } else {
            $stmt = $this->db->prepare("UPDATE task_progress SET progress = ? WHERE id = ?");
            $stmt->bind_param('si', $value, $progressId);
        }
        $save_status = $stmt->execute();
        $stmt->close();
    } else {
        $activity_log = "Created new status '{$value}' for faculty ID {$faculty} (task ID {$id})";
        if ($value === 'Verified') {
            $stmt = $this->db->prepare("INSERT INTO task_progress (task_id, faculty_id, progress, date_verified) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param('iis', $id, $faculty, $value);
        } else {
            $stmt = $this->db->prepare("INSERT INTO task_progress (task_id, faculty_id, progress) VALUES (?, ?, ?)");
            $stmt->bind_param('iis', $id, $faculty, $value);
        }
        $save_status = $stmt->execute();
        $stmt->close();
    }

    if ($save_status) {
    	// Auto-compute timeliness and efficiency when a task is verified
    	if ($value === 'Verified' && !$skip_auto_score) {
    		$this->auto_score_progress($id, $faculty);
    	}
    	$stmt = $this->db->prepare("INSERT INTO login_audit_trail (user_id, username, ip_address, user_agent, login_status, failure_reason) VALUES (?, ?, ?, ?, 'SUCCESS', ?)");
    	$stmt->bind_param('issss', $userId, $username, $ip, $userAgent, $activity_log);
    	$stmt->execute();
    	$stmt->close();
    	echo json_encode(["status" => "success", "message" => "Status saved successfully"]);
    } else {
    	$error = $this->db->error;
    	$stmt = $this->db->prepare("INSERT INTO login_audit_trail (user_id, username, ip_address, user_agent, login_status, failure_reason) VALUES (?, ?, ?, ?, 'FAILED', ?)");
    	$error_msg = "Failed to update status: $error";
    	$stmt->bind_param('issss', $userId, $username, $ip, $userAgent, $error_msg);
    	$stmt->execute();
    	$stmt->close();
    	echo json_encode(["status" => "error", "message" => "Database update failed"]);
    }
    }

    /**
    * Compute and store task-level timeliness_rating and efficiency_rating
    * when a task is verified. Also update the ratings table accordingly.
    */
    function auto_score_progress($task_id, $faculty_id) {
    // Fetch task deadline and submission date
    $stmt = $this->db->prepare("
    	SELECT t.deadline, t.efficiency AS task_eff_applicable, t.timeliness AS task_time_applicable,
    		   tp.date_submitted, tp.date_verified, tp.id AS progress_id, tp.rating_period
    	FROM task_list t
    	LEFT JOIN task_progress tp ON t.id = tp.task_id AND tp.faculty_id = ?
    	WHERE t.id = ?
    	LIMIT 1
    ");
    $stmt->bind_param('ii', $faculty_id, $task_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    if (!$result || $result->num_rows == 0) {
    	return;
    }
    $row = $result->fetch_assoc();
    $progress_id   = intval($row['progress_id'] ?? 0);
    $deadline    = $row['deadline'];
    $submitted   = $row['date_submitted'];
    $verified    = $row['date_verified'] ?: date('Y-m-d H:i:s');
    $eff_appl    = strtoupper($row['task_eff_applicable'] ?? '');
    $time_appl   = strtoupper($row['task_time_applicable'] ?? '');
    $rating_period = $row['rating_period'] ?? ($_SESSION['rating_period'] ?? '');

    if (!$progress_id || $deadline === null || $submitted === null) {
    	return;
    }

    $dl   = new DateTime($deadline . ' 23:59:59');
    $sub  = new DateTime($submitted);
    $diff = $dl->diff($sub);
    $days_late = ($sub > $dl) ? $diff->days : -$diff->days;

    // Timeliness rating (5-point SPMS scale)
    if ($time_appl !== 'APPLICABLE') {
    	$timeliness_rating = null;
    } elseif ($days_late <= 0) {
    	$timeliness_rating = 5;
    } elseif ($days_late <= 2) {
    	$timeliness_rating = 4;
    } elseif ($days_late <= 5) {
    	$timeliness_rating = 3;
    } elseif ($days_late <= 10) {
    	$timeliness_rating = 2;
    } else {
    	$timeliness_rating = 1;
    }

    // Efficiency rating: full (5) if submitted before deadline, scaled down by tardiness
    if ($eff_appl !== 'APPLICABLE') {
    	$efficiency_rating = null;
    } elseif ($days_late <= 0) {
    	$efficiency_rating = 5;
    } elseif ($days_late <= 2) {
    	$efficiency_rating = 4;
    } elseif ($days_late <= 5) {
    	$efficiency_rating = 3;
    } elseif ($days_late <= 10) {
    	$efficiency_rating = 2;
    } else {
    	$efficiency_rating = 1;
    }

    // Store into task_progress
    $stmt = $this->db->prepare("
    	UPDATE task_progress
    	SET timeliness_rating = ?, efficiency_rating = ?
    	WHERE id = ?
    ");
    $stmt->bind_param('ddi', $timeliness_rating, $efficiency_rating, $progress_id);
    $stmt->execute();
    $stmt->close();

    // Mirror into ratings table so IPCR/DPCR/OPCR views pick them up
    $quality_default = 5;
    $stmt = $this->db->prepare("
    	SELECT id FROM ratings
    	WHERE task_id = ? AND employee_id = ?
    	LIMIT 1
    ");
    $stmt->bind_param('ii', $task_id, $faculty_id);
    $stmt->execute();
    $check = $stmt->get_result();
    $stmt->close();

    $period_type = 'IPCR';

    if ($check && $check->num_rows > 0) {
    	$rating_id = $check->fetch_assoc()['id'];
    	$stmt = $this->db->prepare("
    		UPDATE ratings
    		SET efficiency = ?, timeliness = ?, quality = ?, rating_period = ?, period_type = ?
    		WHERE id = ?
    	");
    	$stmt->bind_param('ddsssi', $efficiency_rating, $timeliness_rating, $quality_default, $rating_period, $period_type, $rating_id);
    } else {
    	$stmt = $this->db->prepare("
    		INSERT INTO ratings (task_id, employee_id, efficiency, timeliness, quality, rating_period, period_type)
    		VALUES (?, ?, ?, ?, ?, ?, ?)
    	");
    	$stmt->bind_param('iidddss', $task_id, $faculty_id, $efficiency_rating, $timeliness_rating, $quality_default, $rating_period, $period_type);
    }
    $stmt->execute();
    $stmt->close();
    }

function submit_file() {
    header('Content-Type: application/json');
    
    $task_id = isset($_POST['task_id']) ? (int) $_POST['task_id'] : 0;
    $faculty_id = $_SESSION['login_id'] ?? 0;
    $rating_period = isset($_POST['rating_period']) ? $this->db->real_escape_string($_POST['rating_period']) : '';
    
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
            $this->db->query("UPDATE task_progress SET file_path = '$uploadDir$newFileName', file_type = '$fileType', progress = 'For Verification', date_created = NOW(), rating_period = '$rating_period' WHERE id = $progressId");
        } else {
            $this->db->query("INSERT INTO task_progress (task_id, faculty_id, file_path, file_type, progress, date_created, rating_period) VALUES ($task_id, $faculty_id, '$uploadDir$newFileName', '$fileType', 'For Verification', NOW(), '$rating_period')");
        }
        
        echo json_encode(["status" => "success", "message" => "File submitted successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to move uploaded file."]);
    }
}

	
	function submit_na() {
	    header('Content-Type: application/json');

	    $task_id = isset($_POST['task_id']) ? (int) $_POST['task_id'] : 0;
	    $faculty_id = $_SESSION['login_id'] ?? 0;
	    $rating_period = isset($_POST['rating_period']) ? $this->db->real_escape_string($_POST['rating_period']) : '';

	    if ($task_id === 0 || $faculty_id === 0) {
	        echo json_encode(["status" => "error", "message" => "Invalid task or faculty ID."]);
	        return;
	    }

	    // Check if a submission already exists
	    $check = $this->db->query("SELECT id FROM task_progress WHERE task_id = $task_id AND faculty_id = $faculty_id LIMIT 1");
	    if ($check && $check->num_rows > 0) {
	        $row = $check->fetch_assoc();
	        $progressId = (int) $row['id'];
	        $this->db->query("UPDATE task_progress SET file_path = '', file_type = '', progress = 'N/A', date_created = NOW(), rating_period = '$rating_period' WHERE id = $progressId");
	    } else {
	        $this->db->query("INSERT INTO task_progress (task_id, faculty_id, file_path, file_type, progress, date_created, rating_period) VALUES ($task_id, $faculty_id, '', '', 'N/A', NOW(), '$rating_period')");
	    }

	    echo json_encode(["status" => "success", "message" => "Target marked as N/A."]);
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
			// Duplicate prevention: check if this faculty already submitted this task
			$faculty_id = intval($_POST['faculty_id'] ?? $_SESSION['login_id'] ?? 0);
			$task_id    = intval($_POST['task_id'] ?? 0);
			if ($faculty_id > 0 && $task_id > 0) {
				$check = $this->db->query("SELECT id FROM task_progress WHERE task_id = $task_id AND faculty_id = $faculty_id LIMIT 1");
				if ($check && $check->num_rows > 0) {
					$existing = $check->fetch_assoc();
					$id = (int) $existing['id'];
				}
			}
		}
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
		$recommendation_status = $_POST['recommendation_status'] ?? 'Pending';
		$system_reason = $_POST['system_reason'] ?? '';
		
		$stmt = $this->db->prepare("UPDATE renewal_recommendations SET overall_score=?, recommendation_status=?, system_generated_reason=? WHERE id=?");
		$stmt->bind_param('dssi', $overall_score, $recommendation_status, $system_reason, $id);
		$save = $stmt->execute();
		$stmt->close();
		
		if($this->db->error) {
			return "Error: " . $this->db->error;
		}
		
		return $save ? 1 : 0;
	}

	function delete_rec(){
		extract($_POST);
		$id = intval($id);
		
		$stmt = $this->db->prepare("DELETE FROM renewal_recommendations WHERE id = ?");
		$stmt->bind_param('i', $id);
		$delete = $stmt->execute();
		$stmt->close();
		
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
	
		$period_id = intval($_POST['period_id'] ?? 0);
		$start_date = $this->db->real_escape_string($_POST['start_date'] ?? '');
		$end_date = $this->db->real_escape_string($_POST['end_date'] ?? '');
		$non_desig_start_date = $this->db->real_escape_string($_POST['non_desig_start_date'] ?? '');
		$non_desig_end_date = $this->db->real_escape_string($_POST['non_desig_end_date'] ?? '');
		$auto_cascade = intval($_POST['auto_cascade'] ?? 0);
		$code = $this->db->real_escape_string($_POST['code'] ?? ($semester . '-' . $year));
		
		// Deactivate all periods, then activate the target one
		$this->db->query("UPDATE rating_period SET is_active = 0");
		
		if ($period_id > 0) {
			// Update existing period
			$qry = $this->db->query("UPDATE rating_period 
				SET semester = '$semester', year = '$year', code = '$code',
				    start_date = " . ($start_date ? "'$start_date'" : "NULL") . ",
				    end_date = " . ($end_date ? "'$end_date'" : "NULL") . ",
				    non_desig_start_date = " . ($non_desig_start_date ? "'$non_desig_start_date'" : "NULL") . ",
			    non_desig_end_date = " . ($non_desig_end_date ? "'$non_desig_end_date'" : "NULL") . ",
				    auto_cascade = $auto_cascade,
				    is_active = 1
				WHERE id = $period_id");
		} else {
			// Insert new period
			$qry = $this->db->query("INSERT INTO rating_period 
				(semester, year, code, start_date, end_date, non_desig_start_date, non_desig_end_date, auto_cascade, is_active)
				VALUES ('$semester', '$year', '$code', " . 
				($start_date ? "'$start_date'" : "NULL") . ", " .
				($end_date ? "'$end_date'" : "NULL") . ", " .
				($non_desig_start_date ? "'$non_desig_start_date'" : "NULL") . ", " .
				($non_desig_end_date ? "'$non_desig_end_date'" : "NULL") . ", $auto_cascade, 1)");
		}
	
		if($qry){
			$_SESSION['rating_period'] = $code;
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
		$name = $_POST['name'] ?? '';
		$id = isset($id) ? intval($id) : null;
		
		if(empty($id)){
			$stmt = $this->db->prepare("INSERT INTO function_categories (name) VALUES (?)");
			$stmt->bind_param('s', $name);
		} else {
			$stmt = $this->db->prepare("UPDATE function_categories SET name=? WHERE id=?");
			$stmt->bind_param('si', $name, $id);
		}
		$save = $stmt->execute();
		$stmt->close();
		if($save){
			return 1;
		}
	}
	
	function get_function_category(){
		extract($_POST);
		$id = intval($id);
		$stmt = $this->db->prepare("SELECT * FROM function_categories WHERE id = ?");
		$stmt->bind_param('i', $id);
		$stmt->execute();
		$qry = $stmt->get_result();
		$stmt->close();
		echo json_encode($qry->fetch_assoc());
	}
	
	function delete_function_category(){
		extract($_POST);
		$id = intval($id);
		$stmt = $this->db->prepare("DELETE FROM function_categories WHERE id = ?");
		$stmt->bind_param('i', $id);
		$delete = $stmt->execute();
		$stmt->close();
		if($delete){
			return 1;
		}
	}
	
	function save_percentage_allocation(){
		extract($_POST);
		$position_id = intval($_POST['position_id'] ?? 0);
		$designation_id = intval($_POST['designation_id'] ?? 0);
		$category = $_POST['category'] ?? '';
		$sub_category = $_POST['sub_category'] ?? '';
		$percentage = floatval($_POST['percentage'] ?? 0);
		$function_category = $_POST['function_category'] ?? '';
		$id = isset($id) ? intval($id) : null;
		
		if(empty($id)){
			$stmt = $this->db->prepare("INSERT INTO percentage_allocation (position_id, designation_id, category, sub_category, percentage, function_category) VALUES (?, ?, ?, ?, ?, ?)");
			$stmt->bind_param('iissds', $position_id, $designation_id, $category, $sub_category, $percentage, $function_category);
		} else {
			$stmt = $this->db->prepare("UPDATE percentage_allocation SET position_id=?, designation_id=?, category=?, sub_category=?, percentage=?, function_category=? WHERE id=?");
			$stmt->bind_param('iissdsi', $position_id, $designation_id, $category, $sub_category, $percentage, $function_category, $id);
		}
		$save = $stmt->execute();
		$stmt->close();
		if($save){
			return 1;
		}
	}
	
	function get_percentage_allocation(){
		extract($_POST);
		$id = intval($id);
		$stmt = $this->db->prepare("SELECT * FROM percentage_allocation WHERE id = ?");
		$stmt->bind_param('i', $id);
		$stmt->execute();
		$qry = $stmt->get_result();
		$stmt->close();
		echo json_encode($qry->fetch_assoc());
	}
	
	function delete_percentage_allocation(){
		extract($_POST);
		$id = intval($id);
		$stmt = $this->db->prepare("DELETE FROM percentage_allocation WHERE id = ?");
		$stmt->bind_param('i', $id);
		$delete = $stmt->execute();
		$stmt->close();
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

	/**
	 * Compute cascading: IPCR → DP (per-department) + IPCR → OPCR (office-wide)
	 * Both DP and OPCR aggregate from individual IPCR ratings (not chained).
	 */
	function cascade_compute(){
		header('Content-Type: application/json');
		
		// Get active period
		$period = $this->db->query(
			"SELECT id, semester, year, auto_cascade 
			 FROM rating_period 
			 WHERE is_active = 1
			 LIMIT 1"
		)->fetch_assoc();
		
		if (!$period) {
			echo json_encode(['status' => 'error', 'message' => 'No active rating period']);
			return;
		}
		
		$dp_count = 0;
		$opcr_count = 0;
		$intervention_count = 0;
		
		$ipcr_code = $period['semester'] . '-' . $period['year'];
		
		// ---- STEP 1: IPCR → DP ----
		if($period['auto_cascade']){
			$period_id = $period['id'];
			
			// Get all departments that have rated faculty
			$dept_qry = $this->db->query("
				SELECT DISTINCT e.department_id, dl.department 
				FROM employee_list e
				INNER JOIN ratings r ON r.employee_id = e.id
				LEFT JOIN department_list dl ON e.department_id = dl.id
				WHERE r.rating_period = '$ipcr_code' AND e.department_id IS NOT NULL
			");
			
			while($dept = $dept_qry->fetch_assoc()){
				$dept_id = $dept['department_id'];
				
				// Get average ratings for faculty in this department
				$avg_qry = $this->db->query("
					SELECT 
						AVG(r.efficiency) as avg_eff,
						AVG(r.timeliness) as avg_time,
						AVG(r.quality) as avg_qual,
						COUNT(DISTINCT r.employee_id) as faculty_count
					FROM ratings r
					INNER JOIN employee_list e ON r.employee_id = e.id
					WHERE e.department_id = $dept_id
					AND r.rating_period = '$ipcr_code'
					AND r.efficiency > 0 AND r.timeliness > 0 AND r.quality > 0
				");
				$avg = $avg_qry->fetch_assoc();
				
				if($avg['faculty_count'] > 0){
					$eff = round($avg['avg_eff'], 2);
					$time = round($avg['avg_time'], 2);
					$qual = round($avg['avg_qual'], 2);
					$overall = round(($eff + $time + $qual) / 3, 2);
					
					// Upsert cascading_ratings
					$stmt = $this->db->prepare("
						INSERT INTO cascading_ratings 
							(source_period_id, target_period_id, department_id, level, 
							 avg_efficiency, avg_timeliness, avg_quality, overall_rating)
						VALUES (?, ?, ?, 'DP', ?, ?, ?, ?)
						ON DUPLICATE KEY UPDATE
							avg_efficiency = VALUES(avg_efficiency),
							avg_timeliness = VALUES(avg_timeliness),
							avg_quality = VALUES(avg_quality),
							overall_rating = VALUES(overall_rating),
							computed_at = CURRENT_TIMESTAMP
					");
					$stmt->bind_param('iiidddd', $period_id, $period_id, $dept_id, $eff, $time, $qual, $overall);
					$stmt->execute();
					$stmt->close();
					$dp_count++;
				}
			}
		}
		
		// ---- STEP 2: IPCR → OPCR (directly from ALL individual IPCR ratings) ----
		// OPCR is the office-wide average of ALL faculty IPCR ratings —
		// not an average of department DP averages. DP is per-department only.
		if($period['auto_cascade']){
			$ipcr_id = $period['id'];
			
			// Aggregate ALL individual IPCR ratings office-wide
			$opcr_qry = $this->db->query("
				SELECT 
					AVG(r.efficiency) as avg_eff,
					AVG(r.timeliness) as avg_time,
					AVG(r.quality) as avg_qual,
					COUNT(DISTINCT r.employee_id) as faculty_count
				FROM ratings r
				WHERE r.rating_period = '$ipcr_code'
				AND r.efficiency > 0 AND r.timeliness > 0 AND r.quality > 0
			");
			$opcr_avg = $opcr_qry->fetch_assoc();
			
			if($opcr_avg['faculty_count'] > 0){
				$eff = round($opcr_avg['avg_eff'], 2);
				$time = round($opcr_avg['avg_time'], 2);
				$qual = round($opcr_avg['avg_qual'], 2);
				$overall = round(($eff + $time + $qual) / 3, 2);
				
				// Office-level OPCR (department_id = 0 means "all departments")
				$stmt = $this->db->prepare("
					INSERT INTO cascading_ratings 
						(source_period_id, target_period_id, department_id, level,
						 avg_efficiency, avg_timeliness, avg_quality, overall_rating)
					VALUES (?, ?, 0, 'OPCR', ?, ?, ?, ?)
					ON DUPLICATE KEY UPDATE
						avg_efficiency = VALUES(avg_efficiency),
						avg_timeliness = VALUES(avg_timeliness),
						avg_quality = VALUES(avg_quality),
						overall_rating = VALUES(overall_rating),
						computed_at = CURRENT_TIMESTAMP
				");
				$stmt->bind_param('iidddd', $ipcr_id, $ipcr_id, $eff, $time, $qual, $overall);
				$stmt->execute();
				$stmt->close();
				$opcr_count = 1;
			}
		}
		
		// ---- STEP 3: Intervention Flags (3 consecutive low IPCR) ----
		// Get all periods ordered by year, semester
		$ipcr_periods_qry = $this->db->query("
			SELECT id, semester, year, CONCAT(semester, '-', year) as period_code
			FROM rating_period 
			WHERE is_active = 1
			ORDER BY year ASC, FIELD(semester, '1st Semester', '2nd Semester')
		");
		$ipcr_periods = [];
		while($rp = $ipcr_periods_qry->fetch_assoc()){
			$ipcr_periods[] = $rp;
		}
		
		if(count($ipcr_periods) >= 3){
			// Get all faculty with ratings
			$fac_qry = $this->db->query("SELECT DISTINCT employee_id FROM ratings WHERE efficiency > 0 AND timeliness > 0 AND quality > 0");
			while($fac = $fac_qry->fetch_assoc()){
				$eid = $fac['employee_id'];
				$ratings_history = [];
				
				// Get rating for each period for this faculty
				for($i = 0; $i < count($ipcr_periods); $i++){
					$pc = $ipcr_periods[$i]['period_code'];
					$avg_r = $this->db->query("
						SELECT AVG((efficiency + timeliness + quality) / 3) as overall,
							   COUNT(*) as rated_count
						FROM ratings 
						WHERE employee_id = $eid 
						AND rating_period = '$pc'
						AND efficiency > 0 AND timeliness > 0 AND quality > 0
					")->fetch_assoc();
					
					if($avg_r['rated_count'] > 0){
						$ratings_history[] = [
							'period_id' => $ipcr_periods[$i]['id'],
							'period_label' => $pc,
							'overall' => round($avg_r['overall'], 2)
						];
					}
				}
				
				// Check for 3 consecutive LOW ratings (<= 2.60 = SATISFACTORY or lower)
				$consecutive_low = [];
				foreach($ratings_history as $rh){
					if($rh['overall'] <= 2.60){
						$consecutive_low[] = $rh;
					} else {
						$consecutive_low = []; // Reset on non-low
					}
					
					if(count($consecutive_low) >= 3){
						// Flag this faculty for intervention
						$periods_json = json_encode(array_column($consecutive_low, 'period_id'));
						$ratings_json = json_encode($consecutive_low);
						
						$stmt = $this->db->prepare("
							INSERT IGNORE INTO intervention_flags 
								(employee_id, flag_type, consecutive_periods, overall_ratings)
							VALUES (?, '3_CONSECUTIVE_LOW', ?, ?)
						");
						$stmt->bind_param('iss', $eid, $periods_json, $ratings_json);
						$stmt->execute();
						if($stmt->affected_rows > 0) $intervention_count++;
						$stmt->close();
						
						// Remove oldest to slide window for next check
						array_shift($consecutive_low);
					}
				}
			}
		}
		
		echo json_encode([
			'status' => 'success',
			'dp_count' => $dp_count,
			'opcr_count' => $opcr_count,
			'intervention_count' => $intervention_count,
			'period' => $period
		]);
	}

}
