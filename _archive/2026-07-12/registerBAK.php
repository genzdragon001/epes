<!DOCTYPE html>
<html lang="en">
<?php 
session_start();
include('./db_connect.php');
ob_start();

$system = $conn->query("SELECT * FROM system_settings")->fetch_array();
foreach($system as $k => $v){
  $_SESSION['system'][$k] = $v;
}
ob_end_flush();

if(isset($_SESSION['login_id']))
  header("location:index.php?page=home");
?>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo $_SESSION['system']['name'] ?> - Register</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="dist/css/adminlte.min.css">
  <style>
    body.register-page {
      background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
      min-height: 100vh;
      padding: 40px 0;
    }
    .register-box {
      width: 500px;
    }
    .card {
      border-radius: 15px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    }
    .card-body {
      border-radius: 15px;
      padding: 30px;
    }
    .register-logo {
      margin-bottom: 20px;
    }
    .register-logo h2 {
      color: white;
      text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
      font-weight: 700;
    }
    .btn-success {
      background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
      border: none;
      padding: 12px;
      font-weight: 600;
      transition: all 0.3s;
    }
    .btn-success:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(56, 239, 125, 0.4);
    }
    .btn-secondary {
      padding: 12px;
      font-weight: 600;
    }
    .form-control {
      border-radius: 8px;
      padding: 12px;
      border: 1px solid #e0e0e0;
    }
    .form-control:focus {
      border-color: #11998e;
      box-shadow: 0 0 0 0.2rem rgba(17, 153, 142, 0.25);
    }
    .input-group-text {
      border-radius: 0 8px 8px 0;
      background: #11998e;
      border: none;
      color: white;
    }
    .form-section-title {
      color: #11998e;
      font-weight: 600;
      font-size: 14px;
      margin-bottom: 15px;
      padding-bottom: 8px;
      border-bottom: 2px solid #11998e;
    }
  </style>
</head>
<body class="hold-transition register-page">
  <div class="register-box">
    <div class="register-logo text-center">
      <h2><i class="fas fa-graduation-cap"></i> <?php echo $_SESSION['system']['name'] ?></h2>
      <p class="text-white" style="opacity: 0.9;">Create Your Account</p>
    </div>

    <div class="card">
      <div class="card-body register-card-body">
        <form id="register-form">
          <div class="form-section-title"><i class="fa fa-id-card"></i> ID Verification</div>
          
          <div class="input-group mb-3">
            <input type="text" class="form-control" name="id_number" id="id_number" required placeholder="Enter your ID Number">
            <div class="input-group-append">
              <div class="input-group-text">
                <span class="fas fa-id-card"></span>
              </div>
            </div>
          </div>

          <div class="form-section-title"><i class="fa fa-user"></i> Personal Information</div>

          <div class="row">
            <div class="col-md-4 mb-3">
              <input type="text" class="form-control" name="firstname" id="firstname" readonly placeholder="First Name">
            </div>
            <div class="col-md-4 mb-3">
              <input type="text" class="form-control" name="middlename" id="middlename" readonly placeholder="Middle Name">
            </div>
            <div class="col-md-4 mb-3">
              <input type="text" class="form-control" name="lastname" id="lastname" readonly placeholder="Last Name">
            </div>
          </div>

          <div class="form-section-title"><i class="fa fa-lock"></i> Account Security</div>

          <div class="input-group mb-3">
            <input type="email" class="form-control" name="email" required placeholder="Email Address">
            <div class="input-group-append">
              <div class="input-group-text">
                <span class="fas fa-envelope"></span>
              </div>
            </div>
          </div>

          <div class="input-group mb-3">
            <input type="password" class="form-control" name="password" required placeholder="Password">
            <div class="input-group-append">
              <div class="input-group-text">
                <span class="fas fa-lock"></span>
              </div>
            </div>
          </div>

          <div class="input-group mb-4">
            <input type="password" class="form-control" name="cpassword" required placeholder="Confirm Password">
            <div class="input-group-append">
              <div class="input-group-text">
                <span class="fas fa-lock"></span>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-6">
              <a href="login.php" class="btn btn-secondary btn-block">
                <i class="fa fa-arrow-left"></i> Back
              </a>
            </div>
            <div class="col-6">
              <button type="submit" class="btn btn-success btn-block">
                <i class="fa fa-user-plus"></i> Register
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="dist/js/adminlte.min.js"></script>
<script>
$(document).ready(function() {
    $('#id_number').on('change', function() {
        var id_number = $(this).val();
        if (id_number !== '') {
            $.ajax({
                url: 'ajax.php?action=fetch_user_by_id',
                method: 'POST',
                data: { id_number: id_number },
                success: function(resp) {
                    try {
                        let data = JSON.parse(resp);
                        if (data.is_activated == '1') {
                            alert_toast('ID number is already Activated');
                        } else if (data.status === 'found') {
                            $('#firstname').val(data.firstname);
                            $('#middlename').val(data.middlename);
                            $('#lastname').val(data.lastname);
                        } else {
                            alert_toast('ID number is not yet in the system. Please contact the Administrator.');
                            $('#firstname, #middlename, #lastname').val('');
                        }
                    } catch (err) {
                        console.error(err);
                    }
                }
            });
        }
    });

    $('#register-form').submit(function(e) {
        e.preventDefault();
        
        if($('#password').val() !== $('#cpassword').val()){
            alert('Passwords do not match!');
            return;
        }
        
        $.ajax({
            url: 'ajax.php?action=register_user',
            method: 'POST',
            data: $(this).serialize(),
            success: function(resp) {
                try {
                    let res = JSON.parse(resp);
                    if (res.status === 'success') {
                        alert('Registration successful! Check your email to verify your account.');
                        $('#register-form')[0].reset();
                        $('#firstname, #middlename, #lastname').val('');
                    } else if (res.status === 'exists') {
                        alert('This email is already registered.');
                    } else {
                        alert('Registration failed: ' + res.message);
                    }
                } catch (err) {
                    alert('Unexpected response format.');
                }
            }
        });
    });
});
</script>
</body>
</html>
