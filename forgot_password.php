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

  <div class="card">
    <div class="card-body login-card-body">
      <p class="login-box-msg">Enter your email address to reset your password</p>

      <form action="" id="forgot-password-form" method="POST">
        <div class="input-group mb-3">
          <input type="email" class="form-control" name="email" required placeholder="Email">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-envelope"></span>
            </div>
          </div>
        </div>
        <div class="form-group mb-3">
          <select name="login" id="" class="custom-select custom-select-sm">
            <option value="0">Faculty</option>
            <option value="1">Dean/Dept Head</option>
            <option value="2">Admin</option>
          </select>
        </div>
        <div class="row">
          <div class="col-6">
            <a href="index.php" class="btn btn-secondary btn-block">Back</a>
          </div>
          <div class="col-6">
            <button type="submit" class="btn btn-primary btn-block">Submit</button>
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
