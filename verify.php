<?php
include 'db_connect.php';
$token = $_GET['code'];
if($token){
    $update = $conn->query("UPDATE employee_list SET is_activated=1 WHERE reset_token='$token' LIMIT 1");
    if($update){
        
       // after verification checks
            $_SESSION['verified'] = 'Account verified successfully';
            if (isset($_SESSION['verified'])) {
                echo "<script>alert('{$_SESSION['verified']}');</script>";
                unset($_SESSION['verified']);
            }
            // redirect to index
           header("Location: index.php");
           
    } else {
        $_SESSION['verified'] = 'Invalid verification code';

        // redirect to index
     header("Location: index.php");
    //   echo $_SESSION['verified'];
      
    }

    
 //   echo $_SESSION['verified'];
     
}
