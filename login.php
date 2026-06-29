<?php 
session_start();
include './db_connect.php';
include 'csrf_helper.php';

$system = $conn->query("SELECT * FROM system_settings")->fetch_array();
foreach($system as $k => $v){
  $_SESSION['system'][$k] = $v;
}

if(isset($_SESSION['login_id'])){
  header("location:index.php?page=home");
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'header.php'; ?>
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
  .auth-card .card-body {
    padding: 1.8rem 1.5rem;
  }
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
  .auth-card .input-group .form-control:first-child {
    border-radius: 8px 0 0 8px;
  }
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
  .auth-card .btn-secondary:hover {
    background: #dee2e6;
    color: #1a1a2e;
  }
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
    color: #6c757d;
    font-size: 0.9rem;
    text-decoration: none;
    transition: color 0.2s;
  }
  .auth-back-link a:hover {
    color: #17a2b8;
  }
  .auth-card .alert {
    border-radius: 8px;
    font-size: 0.9rem;
  }
  .auth-card .icheck-primary label { color: #495057; }
  .auth-card .login-box-msg,
  .auth-card .text-muted { color: #6c757d !important; }
  @media (max-width: 768px) {
    .auth-system-name h2 { font-size: 1.15rem; }
    .auth-card { width: 100%; }
  }
</style>
  <div class="auth-system-name">
    <h2><?php echo htmlspecialchars($_SESSION['system']['name']); ?></h2>
    <div class="auth-subtitle">Login to your account</div>
  </div>
<div class="login-box" style="width:400px;max-width:100%;">
  <!-- /.login-logo -->
  <div class="card auth-card">
    <div class="card-header auth-card-header">
      <h3><i class="fas fa-sign-in-alt mr-2"></i> Sign In</h3>
    </div>
    <div class="card-body login-card-body">
    <?php if (isset($_SESSION['verified'])): ?>
    <div class="alert alert-success text-center">
      <?php echo $_SESSION['verified']; unset($_SESSION['verified']); ?>
    </div>
  <?php endif; ?>

      <?php if (isset($_SESSION['verify_success'])): ?>
      <div class="alert alert-success text-center">
        <i class="fa fa-check-circle fa-2x mb-2"></i>
        <p class="mb-0"><?php echo $_SESSION['verify_success']; unset($_SESSION['verify_success']); ?></p>
      </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['verify_error'])): ?>
      <div class="alert alert-danger text-center">
        <i class="fa fa-exclamation-circle fa-2x mb-2"></i>
        <p class="mb-0"><?php echo $_SESSION['verify_error']; unset($_SESSION['verify_error']); ?></p>
      </div>
    <?php endif; ?>

    <?php if (isset($_GET['session']) && $_GET['session'] === 'expired'): ?>
      <div class="alert alert-warning text-center">
        <i class="fa fa-clock fa-2x mb-2"></i>
        <p class="mb-0"><strong>Session Expired</strong></p>
        <small>Your session was closed due to inactivity. Please log in again.</small>
      </div>
    <?php endif; ?>

      <form action="" id="login-form">
        <?php echo csrf_field(); ?>
        <div class="input-group mb-3">
          <input type="email" class="form-control" name="email" required placeholder="Email">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-envelope"></span>
            </div>
          </div>
        </div>
        <div class="input-group mb-3">
          <input type="password" class="form-control" name="password" id="password" required placeholder="Password">
          <div class="input-group-append">
            <div class="input-group-text">
              <!-- <span class="fas fa-lock"></span> -->
            </div>
            <div class="input-group-append">
              <button type="button" class="btn btn-outline-secondary" id="togglePassword" style="border-left: none;">
                <i class="fa fa-eye" id="toggleIcon"></i>
              </button>
            </div>
          </div>
        </div>
        <div class="icheck-primary mb-3">
          <input type="checkbox" name="remember" id="remember" value="1">
          <label for="remember">
            Remember Me
          </label>
        </div>
         <div class="row mt-3">
         <div class="col-12">
            <button type="submit" class="btn btn-primary btn-block">Sign In</button>
          </div>
  </div>
          <!-- /.col -->
      </form>

      <!-- Register button -->
      <div class="row mt-3">
        <div class="col-12">
          <a href="register" class="btn btn-secondary btn-block">Register</a>
        </div>
      </div>

       <!-- Forgot Password link -->
       <div class="row mt-3">
        <div class="col-12 text-center">
          <a href="forgot_password" class="text-primary">Forgot Password?</a>
        </div>
      </div>

      <!-- Back to Home link -->
      <div class="auth-back-link">
          <a href="landing"><i class="fas fa-arrow-left mr-1"></i> Back to Home</a>
      </div>
    </div>
    <!-- /.login-card-body -->
  </div>
</div>
<!-- /.login-box -->

<script>
  $(document).ready(function(){
    $('#togglePassword').click(function(){
      var password = $('#password');
      var icon = $('#toggleIcon');
      if(password.attr('type') === 'password'){
        password.attr('type', 'text');
        icon.removeClass('fa-eye').addClass('fa-eye-slash');
      }else{
        password.attr('type', 'password');
        icon.removeClass('fa-eye-slash').addClass('fa-eye');
      }
    });
    
    $('#login-form').submit(function(e){
    e.preventDefault()
    start_load()
    if($(this).find('.alert-danger').length > 0 )
      $(this).find('.alert-danger').remove();
    $.ajax({
      url:'ajax.php?action=login',
      method:'POST',
      data:$(this).serialize(),
      error:err=>{
        console.log(err)
        end_load();
        var msg = 'Unable to sign in. Please check your connection and try again.';
        if (err.status === 403) {
          msg = 'Session security token expired. Please refresh the page and try again.';
        } else if (err.status === 500) {
          msg = 'Server error. Please contact the administrator.';
        } else if (err.status === 0) {
          msg = 'Cannot reach server. Please make sure XAMPP is running.';
        }
        $('#login-form').prepend('<div class="alert alert-danger">' + msg + '</div>');
      },
      success:function(resp){
        if(resp == 1){
          location.href ='index.php?page=home';
        }else if(resp == 3){
          $('#login-form').prepend('<div class="alert alert-danger">Account is temporary blocked.</div>')
        }else if(resp == 4){
          $('#login-form').prepend('<div class="alert alert-warning"><i class="fa fa-exclamation-circle"></i> Your account is not yet activated. Please check your email and activate your account before logging in.</div>')
        }else if(resp == 5){
          $('#login-form').prepend('<div class="alert alert-warning"><i class="fa fa-exclamation-triangle"></i> Too many login attempts. Please wait a few minutes and try again.</div>')
        }else{
          $('#login-form').prepend('<div class="alert alert-danger">Username or password is incorrect.</div>')
          
        }
        end_load();
      }
    })
  })
  })
</script>
<?php include 'footer.php' ?>

</body>
</html>
