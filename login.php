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
<body class="hold-transition login-page bg-black">
  <h2><b><?php echo $_SESSION['system']['name'] ?> - Admin</b></h2>
<div class="login-box">
  <div class="login-logo">
    <a href="#" class="text-white"></a>
  </div>
  <!-- /.login-logo -->
  <div class="card">
    <div class="card-body login-card-body">
    <?php if (isset($_SESSION['verified'])): ?>
    <div class="alert alert-success text-center">
      <?php echo $_SESSION['verified']; unset($_SESSION['verified']); ?>
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
        <div class="form-group mb-3">
          <label for="">Login As</label>
          <select name="login" id="" class="custom-select custom-select-sm">
            <option value="0">Faculty</option>
            <option value="1">Dean/Dept Head</option>
            <option value="2">Admin</option>
          </select>
        </div>
        <div class="row">
          <div class="col-8">
            <div class="icheck-primary">
              <input type="checkbox" name="remember" id="remember" value="1">
              <label for="remember">
                Remember Me
              </label>
            </div>
          </div>
      
        </div>
         <!-- /.col -->
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
          <a href="register.php" class="btn btn-secondary btn-block">Register</a>
        </div>
      </div>

       <!-- Forgot Password link -->
       <div class="row mt-3">
        <div class="col-12 text-center">
          <a href="forgot_password.php" class="text-primary">Forgot Password?</a>
        </div>
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

      },
      success:function(resp){
        if(resp == 1){
          location.href ='index.php?page=home';
        }else if(resp == 3){
          $('#login-form').prepend('<div class="alert alert-danger">Account is temporary blocked.</div>')
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
