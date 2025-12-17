<?php 
    session_start();
    
    if(isset($_SESSION['auth_user'])){
        unset($_SESSION['auth']);
        unset($_SESSION['auth_user']);
        session_destroy();
        $_SESSION['message'] = "Logged Out successfully!";
    }
    header('Location: login.php');
    exit;

?>