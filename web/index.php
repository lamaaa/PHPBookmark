<?php
    require_once('../lib/bookmark_fns.php');
    
    session_start();
    
    if(isset($_SESSION['valid_user']))
    {
        header("location: ../lib/member.php");
        exit; 
    }
    else 
    {
        header("location: ../lib/login.php");
        exit; 
    }

?>