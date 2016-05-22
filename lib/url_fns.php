<?php
    require_once('bookmark_fns.php');
    
    function add_bm($new_url)
    {
        // Add new bookmark to the database
        echo "Attempting to add ".htmlspecialchars($new_url)."<br />";
        $valid_user = $_SESSION['valid_user'];
        
        $conn = db_connect();
        
        // check not a repeat bookmark
        $result = $conn->query("select * from bookmark
                                where username = '$valid_user'
                                and bm_URL='".$new_url."'");
        
        if($result && ($result->num_rows > 0))
        {
            throw new Exception('Bookmark already exists.');
        }
        
        if(!$conn->query("insert into bookmark values
            ('".$valid_user."', '".$new_url."')"))
        {
            throw new Exception('Bookmark could not be inserted.');
        }
        
        // insert the new bookmark
        return true;
    }
    
    function get_user_urls($username)
    {
        // extract from the database all the URLs this user has stored
        
        $conn = db_connect();
        $result = $conn->query("select bm_URL
                                from bookmark
                                where username = '".$username."'");
        if(!$result)
        {
            return false;
        }
        
        // create an array of the URLs 
        $url_array = array();
        for($count = 1; $row = $result->fetch_row(); ++$count)
        {
            $url_array[$count] = $row[0];
        }
        
        return $url_array;
    }
    
    function delete_bm($user, $url)
    {
        // delete one URL from the database
        $conn = db_connect();
        
        // delete the bookmark
        if(!$conn->query("delete from bookmark
         where username = '".$user."' and bm_url = '".$url."'"))
         {
             throw new Exception('Bookmark could not be deleted');
         }
         
         return true;
    }
    
    function recommend_urls($valid_user, $popularity = 1)
    {
        // We will provide semi intelligent recommendations to people
        // If they have an URL in common with other users, they may like
        // other URLs that these people like
        
        $conn = db_connect();
        
        // find other matching users
        // with an url the same as you
        // as a simple way of excluding people's private pages, and 
        // increasing the chance of recommending appealing URLs, we 
        // specif a minimum popularity level
        // if $popularity = 1, then more than one person must have
        // an URL before we will recommend it 
        
          
        // distinct use for Remove duplicate values
        $query = "select bm_URL
                from bookmark
                where username in
                (select distinct(b2.username)
                from bookmark b1, bookmark b2
                where b1.username = '".$valid_user."'
                and b1.username != b2.username
                and b1.bm_URL = b2.bm_URL) 
                and bm_URL not in 
                (select bm_URL
                from bookmark
                where username = '".$valid_user."')
                group by bm_URL
                having count(bm_URL) > ".$popularity;
       if(!($result = $conn->query($query)))
       {
           throw new Exception('Could not find any bookmarks to recommend.');
       }
       
       if($result->num_rows == 0)
       {
           throw new Exception('Could not find any bookmarks to recommend.');
       }
       
       $urls = array();
       // build an array of the relevant urls 
       for($count = 0; $row = $result->fetch_object(); $count++)
       {
           $urls[$count] = $row->bm_URL;
       }
       
       return $urls;
    }
?>