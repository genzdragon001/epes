<?php 
session_start();
include('./db_connect.php');
include 'csrf_helper.php';
ob_start();

$system = $conn->query("SELECT * FROM system_settings")->fetch_array();
foreach($system as $k => $v){
    $_SESSION['system'][$k] = $v;
}
ob_end_flush();

if(isset($_SESSION['login_id']))
    header("location:index.php?page=home");
?>
<?php include 'header.php' ?>

<body class="hold-transition register-page bg-black">
  <h2><b><?php echo $_SESSION['system']['name'] ?> - Register</b></h2>

<div class="register-box">
  <div class="register-logo">
    <a href="#" class="text-white"></a>
  </div>

  <div class="card card-outline card-primary">
    <div class="card-header text-center">
      <h3 class="card-title"><i class="fa fa-user-plus"></i> Create Account</h3>
    </div>
    <div class="card-body register-card-body">
      <p class="login-box-msg">Enter your ID number to register your account</p>

      <form id="register-form">
        <?php echo csrf_field(); ?>
        
        <div class="input-group mb-3">
          <input type="text" class="form-control" name="id_number" id="id_number" required placeholder="ID Number">
          <div class="input-group-append">
            <div class="input-group-text"><span class="fas fa-id-card"></span></div>
          </div>
        </div>

        <div class="input-group mb-3">
          <input type="text" class="form-control" name="firstname" id="firstname" readonly placeholder="First Name">
          <input type="text" class="form-control" name="middlename" id="middlename" readonly placeholder="Middle Name">
          <input type="text" class="form-control" name="lastname" id="lastname" readonly placeholder="Last Name">
        </div>

        <div class="input-group mb-3">
          <input type="email" class="form-control" name="email" required placeholder="Email">
          <div class="input-group-append">
            <div class="input-group-text"><span class="fas fa-envelope"></span></div>
          </div>
        </div>

        <div class="input-group mb-3">
          <input type="password" class="form-control" name="password" id="password" required placeholder="Password">
          <div class="input-group-append">
            <button type="button" class="btn btn-outline-secondary" id="togglePassword">
              <i class="fa fa-eye" id="toggleIcon"></i>
            </button>
          </div>
        </div>

        <div class="input-group mb-3">
          <input type="password" class="form-control" name="cpassword" id="cpassword" required placeholder="Confirm Password">
          <div class="input-group-append">
            <button type="button" class="btn btn-outline-secondary" id="toggleCPassword">
              <i class="fa fa-eye" id="toggleCIcon"></i>
            </button>
          </div>
        </div>

        <div class="row">
          <div class="col-6">
            <a href="index.php" class="btn btn-secondary btn-block">
              <i class="fa fa-arrow-left"></i> Back
            </a>
          </div>
          <div class="col-6">
            <button type="submit" class="btn btn-primary btn-block">
              <i class="fa fa-user-plus"></i> Register
            </button>
          </div>
        </div>
      </form>

      <div class="text-center mt-4">
        <small class="text-muted">
          <i class="fa fa-sign-in-alt"></i> Already have an account? 
          <a href="login.php" class="text-primary">Login here</a>
        </small>
      </div>
    </div>
  </div>
</div>

<script>
$(document).ready(function() {
    // Toggle password visibility
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
    
    $('#toggleCPassword').click(function(){
        var cpassword = $('#cpassword');
        var icon = $('#toggleCIcon');
        if(cpassword.attr('type') === 'password'){
            cpassword.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        }else{
            cpassword.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    $('#id_number').on('change', function() {
        var id_number = $(this).val();

        if (id_number !== '') {
            if($(this).closest('form').find('.alert-danger').length > 0)
                $(this).closest('form').find('.alert-danger').remove();
            if($(this).closest('form').find('.alert-success').length > 0)
                $(this).closest('form').find('.alert-success').remove();
            
            $.ajax({
                url: 'ajax.php?action=fetch_user_by_id',
                method: 'POST',
                data: { id_number: id_number, csrf_token: $('input[name="csrf_token"]').val() },
                success: function(resp) {
                    try {
                        let data = JSON.parse(resp);

                        if (data.is_activated == '1') {
                            $('#register-form').prepend('<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> This ID number is already activated. Please login instead.</div>');
                        } else if (data.status === 'found') {
                            $('#firstname').val(data.firstname);
                            $('#middlename').val(data.middlename);
                            $('#lastname').val(data.lastname);
                        } else {
                            $('#register-form').prepend('<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> ID number is not yet in the system. Please contact the Administrator.</div>');
                            $('#firstname, #middlename, #lastname').val('');
                        }

                    } catch (err) {
                        console.error('JSON parse error:', err);
                        $('#register-form').prepend('<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> Invalid response from server.</div>');
                    }
                },
                error: function(xhr, status, error) {
                    $('#register-form').prepend('<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> Error fetching user data. Please try again.</div>');
                }
            });
        }
    });

    $('#register-form').submit(function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        
        start_load();
        
        $.ajax({
            url: 'ajax.php?action=register_user',
            method: 'POST',
            data: formData,
            success: function(resp) {
                end_load();
                console.log('Response:', resp);
                try {
                    var res = JSON.parse(resp);
                    
                    if (res.status === 'success') {
                        $('#register-form').html(`
                            <div class="alert alert-success text-center">
                                <i class="fa fa-check-circle fa-3x mb-3"></i>
                                <h5>Registration Successful!</h5>
                                <p>Check your email to verify your account.</p>
                                <a href="login.php" class="btn btn-primary mt-3">
                                    <i class="fa fa-sign-in-alt"></i> Click here to Login
                                </a>
                            </div>
                        `);
                    } else if (res.status === 'error') {
                        $('#register-form').prepend('<div class="alert alert-danger">' + (res.message || 'Registration failed. Please try again.') + '</div>');
                    } else {
                        $('#register-form').prepend('<div class="alert alert-danger">Unexpected response from the server.</div>');
                    }
                } catch (err) {
                    console.error('JSON parse error:', err);
                    $('#register-form').prepend('<div class="alert alert-danger">Unexpected response. Please try again.</div>');
                }
            },
            error: function(xhr, status, error) {
                end_load();
                console.error('AJAX Error:', error);
                $('#register-form').prepend('<div class="alert alert-danger">A network or server error occurred. Please try again.</div>');
            }
        });
    });
});
</script>


<?php include 'footer.php' ?>
</body>
</html>
