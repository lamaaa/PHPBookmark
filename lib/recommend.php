<?php
    require_once('bookmark_fns.php');
    session_start();
    
    $valid_user = $_SESSION['valid_user'];
    
    do_html_header('Recommending URLs');
    try
    {
        check_valid_user();
        $urls = recommend_urls($valid_user);
        display_recommended_urls($urls);
    }
    catch(Exception $e)
    {
        echo $e->getMessage();
    }
    
    display_user_menu();
    do_html_footer();
?>