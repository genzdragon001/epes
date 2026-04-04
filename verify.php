<?php
include 'db_connect.php';
$code = $_GET['code'];
if($code){
    $update = $conn->query("UPDATE employee_list SET is_activated=1 WHERE verification_code='$code' LIMIT 1");
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
