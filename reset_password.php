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

if(!isset($_GET['token'])){
  die("Invalid or missing reset token.");
}

$token = $_GET['token'];

// Validate token from database
$qry = $conn->query("SELECT * FROM users WHERE reset_token = '$token' AND reset_expires > NOW()");
if($qry->num_rows == 0){
  die("Invalid or expired token.");
}

$user = $qry->fetch_assoc();
?>
<?php include 'header.php' ?>
<body class="hold-transition login-page bg-black">
  <h2><b><?php echo $_SESSION['system']['name'] ?> - Reset Password</b></h2>

<div class="login-box">
  <div class="login-logo">
    <a href="#" class="text-white"></a>
  </div>
  <!-- /.login-logo -->

  <div class="card">
    <div class="card-body login-card-body">
      <p class="login-box-msg">Enter your new password</p>

      <form action="" id="reset-password-form" method="POST">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token) ?>">

        <div class="input-group mb-3">
          <input type="password" class="form-control" name="password" required placeholder="New Password" minlength="6">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-lock"></span>
            </div>
          </div>
        </div>

        <div class="input-group mb-3">
          <input type="password" class="form-control" name="confirm_password" required placeholder="Confirm Password" minlength="6">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-lock"></span>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-6">
            <a href="index.php" class="btn btn-secondary btn-block">Back</a>
          </div>
          <div class="col-6">
            <button type="submit" class="btn btn-primary btn-block">Reset</button>
          </div>
        </div>
      </form>
    </div>
    <!-- /.login-card-body -->
  </div>
</div>
<!-- /.login-box -->

<script>
  $(document).ready(function(){
    $('#reset-password-form').submit(function(e){
      e.preventDefault()
      start_load()
      if($(this).find('.alert').length > 0 )
        $(this).find('.alert').remove();

        $.ajax({
  url:'ajax.php?action=reset_password',
  method:'POST',
  data:$(this).serialize(),
  error:err=>{
    console.log(err)
    end_load();
   
  },
  success:function(resp){
    alert(resp);
    if(resp == 1){
      $('#reset-password-form').prepend('<div class="alert alert-success">Password reset successful. <a href="index.php">Login here</a></div>')
     
    }else if(resp == 2){
      $('#reset-password-form').prepend('<div class="alert alert-danger">Passwords do not match.</div>')
     
    }else if(resp == 3){
      $('#reset-password-form').prepend('<div class="alert alert-danger">Invalid or expired reset token.</div>')
     
    }else if(resp == 4){
      $('#reset-password-form').prepend('<div class="alert alert-danger">Failed to update password. Please try again later.</div>')
      
    }else{
      $('#reset-password-form').prepend('<div class="alert alert-danger">Something went wrong. Try again.</div>')
    
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
