<?php
    require_once('bookmark_fns.php');
    
    do_html_header('Acticing user');
    $verify = stripslashes(trim($_GET['verify'])); 
    $nowtime = time(); 
    $conn = db_connect();
    
    $query = "select username, token_exptime from user where status='0' and  
    `token`='$verify'";
    $result = $conn->query($query);
    
    $row = $result->fetch_object();
    if($row){ 
        if($nowtime>$row->token_exptime){ //24hour 
            $msg = 'Your activation has expired, please log in to your account to resend the activation e-mail.'; 
        }else{
            $query = "update user set status=1 where username='".$row->username."'";
            $conn->query($query);
            if($conn->affected_rows == -1) 
                die(0);  
            $msg = 'Activation successed！'; 
        } 
    }else{ 
        $msg = 'Activation failed.';     
    } 
    echo $msg;
    
    do_html_footer();
?>