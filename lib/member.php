<?php
    // include function files for this application
    require_once('bookmark_fns.php');
    session_start();
  
    // create short variable names 
    @$username = $_POST['username'];
    @$passwd = $_POST['passwd'];
    @$vcode = $_SESSION['VCODE'];
    @$enter_vcode = $_POST['veriftycode'];
    
    if($username && $passwd)
    {
        // they have just tried logging in 
        try
        {
            if($vcode != $enter_vcode)
            {
                throw new Exception('Please enter correct verifty code.');
            }
            
            login($username, $passwd);
            // if they are in the database register the user id 
            $_SESSION['valid_user'] = $username;
        }
        catch(Exception $e)
        {
            // unsuccessful login
            do_html_header('Problem:');
            echo $e->getMessage();
            do_html_url('login.php', 'Login');
            do_html_footer();
            exit;
        }
    }
    
    do_html_header('Home');
    check_valid_user();
    // get the bookmarks this user has saved
    if($url_array = get_user_urls($_SESSION['valid_user']))
    {
        display_user_urls($url_array);
    }
    // give menu of options
    display_user_menu();
        
    do_html_footer();

?>