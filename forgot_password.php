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
<body class="hold-transition login-page bg-black">
  <h2><b><?php echo $_SESSION['system']['name'] ?> - Forgot Password</b></h2>

<div class="login-box">
  <div class="login-logo">
    <a href="#" class="text-white"></a>
  </div>
  <!-- /.login-logo -->

  <div class="card card-outline card-primary">
    <div class="card-header text-center">
      <h3 class="card-title"><i class="fa fa-key"></i> Password Reset</h3>
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
            <a href="index.php" class="btn btn-secondary btn-block">
              <i class="fa fa-arrow-left"></i> Back
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
          <a href="login.php" class="text-primary">Login here</a>
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
