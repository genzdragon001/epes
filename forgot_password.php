<!DOCTYPE html>
<html lang="en">
<?php 
session_start();
include('./db_connect.php');
ob_start();

// Load system settings
$system = $conn->query("SELECT * FROM system_settings")->fetch_array();
foreach($system as $k => $v){
  $_SESSION['system'][$k] = $v;
}
ob_end_flush();

// If logged in already, redirect to home
if(isset($_SESSION['login_id']))
  header("location:index.php?page=home");
?>
<?php include 'header.php' ?>
<body class="hold-transition login-page auth-page-bg">
<style>
  /* ── Auth Pages Shared Style ── */
  .auth-page-bg {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
  }
  .auth-system-name {
    text-align: center;
    margin-bottom: 1.5rem;
    width: 100%;
    max-width: 400px;
  }
  .auth-system-name h2 {
    font-size: 1.4rem;
    font-weight: 700;
    color: #fff;
    margin: 0;
    line-height: 1.4;
  }
  .auth-system-name .auth-subtitle {
    font-size: 0.85rem;
    color: rgba(255,255,255,0.5);
    margin-top: 0.3rem;
  }
  .auth-card {
    width: 400px;
    max-width: 100%;
    border: none;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0,0,0,0.4);
  }
  .auth-card-header {
    background: linear-gradient(135deg, #17a2b8 0%, #6610f2 100%) !important;
    color: #fff !important;
    border-bottom: none !important;
    padding: 1.2rem 1.5rem;
    text-align: center;
  }
  .auth-card-header h3 {
    font-size: 1.15rem;
    font-weight: 600;
    margin: 0;
    color: #fff !important;
  }
  .auth-card .card-body { padding: 1.8rem 1.5rem; }
  .auth-card .form-control {
    border-radius: 8px;
    border: 1px solid #e0e0e0;
    padding: 0.65rem 0.75rem;
    font-size: 0.95rem;
    transition: border-color 0.2s, box-shadow 0.2s;
  }
  .auth-card .form-control:focus {
    border-color: #17a2b8;
    box-shadow: 0 0 0 0.15rem rgba(23,162,184,0.15);
  }
  .auth-card .input-group-text {
    border-radius: 0 8px 8px 0;
    background: #f8f9fa;
    border: 1px solid #e0e0e0;
    border-left: none;
  }
  .auth-card .input-group .form-control:first-child { border-radius: 8px 0 0 8px; }
  .auth-card .btn-primary {
    background: linear-gradient(135deg, #17a2b8 0%, #6610f2 100%);
    border: none;
    border-radius: 8px;
    font-weight: 600;
    padding: 0.65rem;
    font-size: 1rem;
    transition: opacity 0.2s, box-shadow 0.2s;
  }
  .auth-card .btn-primary:hover {
    opacity: 0.92;
    box-shadow: 0 4px 15px rgba(23,162,184,0.3);
  }
  .auth-card .btn-secondary {
    border-radius: 8px;
    font-weight: 600;
    padding: 0.65rem;
    font-size: 1rem;
    background: #e9ecef;
    border: 1px solid #dee2e6;
    color: #495057;
  }
  .auth-card .btn-secondary:hover { background: #dee2e6; color: #1a1a2e; }
  .auth-card .custom-select {
    border-radius: 8px;
    border: 1px solid #e0e0e0;
    padding: 0.55rem 0.75rem;
    font-size: 0.95rem;
  }
  .auth-card .custom-select:focus {
    border-color: #17a2b8;
    box-shadow: 0 0 0 0.15rem rgba(23,162,184,0.15);
  }
  .auth-back-link {
    text-align: center;
    margin-top: 1.2rem;
    width: 100%;
    max-width: 400px;
  }
  .auth-back-link a {
    color: rgba(255,255,255,0.5);
    font-size: 0.9rem;
    text-decoration: none;
    transition: color 0.2s;
  }
  .auth-back-link a:hover { color: #17a2b8; }
  .auth-card .alert { border-radius: 8px; font-size: 0.9rem; }
  .auth-card .login-box-msg,
  .auth-card .text-muted { color: #6c757d !important; }
  @media (max-width: 768px) {
    .auth-system-name h2 { font-size: 1.15rem; }
    .auth-card { width: 100%; }
  }
</style>
  <div class="auth-system-name">
    <h2><?php echo htmlspecialchars($_SESSION['system']['name']); ?></h2>
    <div class="auth-subtitle">Reset your password</div>
  </div>

<div class="login-box" style="width:400px;max-width:100%;">
  <!-- /.login-logo -->

  <div class="card auth-card card-outline card-primary">
    <div class="card-header auth-card-header">
      <h3><i class="fa fa-key mr-2"></i> Password Reset</h3>
    </div>
    <div class="card-body login-card-body">
      <div class="text-center mb-4">
        <i class="fa fa-envelope-open fa-3x text-primary"></i>
        <p class="login-box-msg mt-3">
          Enter your email address and we'll send you a link to reset your password.
        </p>
      </div>

      <form action="" id="forgot-password-form" method="POST">
        <div class="input-group mb-3">
          <input type="email" class="form-control form-control-lg" name="email" required placeholder="Enter your email address">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-envelope"></span>
            </div>
          </div>
        </div>
        <div class="form-group mb-3">
          <label class="text-muted small"><i class="fa fa-user-tag"></i> Select your role:</label>
          <select name="login" id="" class="custom-select custom-select-sm">
            <option value="0">Faculty</option>
            <option value="1">Dean/Dept Head</option>
            <option value="2">Admin</option>
          </select>
        </div>
        <div class="row">
          <div class="col-6">
            <a href="landing" class="btn btn-secondary btn-block">
              <i class="fa fa-arrow-left"></i> Home
            </a>
          </div>
          <div class="col-6">
            <button type="submit" class="btn btn-primary btn-block">
              <i class="fa fa-paper-plane"></i> Send Reset Link
            </button>
          </div>
        </div>
      </form>
      
      <div class="text-center mt-4">
        <small class="text-muted">
          <i class="fa fa-info-circle"></i> Remember your password? 
          <a href="login" class="text-primary">Login here</a>
        </small>
      </div>
    </div>
    <!-- /.login-card-body -->
  </div>
</div>
<!-- /.login-box -->

<script>
  $(document).ready(function(){
    $('#forgot-password-form').submit(function(e){
    e.preventDefault()
    start_load()
    if($(this).find('.alert-danger').length > 0 )
      $(this).find('.alert-danger').remove();
    $.ajax({
      url:'ajax.php?action=forgot_password',
      method:'POST',
      data:$(this).serialize(),
      error:err=>{
        console.log(err)
        end_load();

      },
      success:function(resp){
        if(resp == 1){
          
          $('#forgot-password-form').prepend('<div class="alert alert-success">Password Reset link was sent your your email.</div>')
          end_load();
        }else{
          $('#forgot-password-form').prepend('<div class="alert alert-danger">Email not found.</div>')
          end_load();
        }
      }
    })
  })
  })
</script>

<?php include 'footer.php' ?>
</body>
</html>
