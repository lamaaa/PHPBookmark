<?php
    require_once('bookmark_fns.php');
    require_once('class.phpmailer.php');
    require_once('class.smtp.php');
    
    function register($username, $email, $password, $token, $token_exptime, $regtime)
    {
        // register new person with db 
        // return true or error message
        
        // connect to db 
        $conn = db_connect();
        
        // check if username is unique
        $result = $conn->query("select * from user where username='".$username."'");
        if(!$result)
        {
            throw new Exception('Could not execute query');
        }
        if($result->num_rows > 0)
        {
            throw new Exception('That username is taken - go back and choose another one.');
        }
        // check if email is unique
        $result = $conn->query("select * from user where email='".$email."'");
        if(!$result)
        {
            throw new Exception('Could not execute query');
        }
        if($result->num_rows > 0)
        {
            throw new Exception('That email is taken - go back and choose another one.');
        }
        
        // if ok, put in db
        $sql = "insert into user (username, passwd, email, token, token_exptime, regtime)
        values ('".$username."', sha1('".$password."'), '".$email."', '".$token."', '".$token_exptime."', '".$regtime."')";
        
        $result = $conn->query($sql);
        if(!$result)
        {
            throw new Exception('Could not register you in database - please try again later.');
        }
        else
        {
            notify_register_mail($username, $token);
        }
        
        return true;
    }

    function login($username, $password)
    {
        // check username and password with db 
        // if yes, return true
        // else throw exception
        
        // connect to db 
        $conn = db_connect();
        
        // check if username is unique
        $result = $conn->query("select * from user 
                            where username='".$username."'
                            and passwd = sha1('".$password."')
                            and status = 1");
        
        if(!$result)
        {
            throw new Exception('Could not log you in.');
        }
        else if($result->num_rows == 0)
        {
            $result = $conn->query("select * from user 
                                where username='".$username."'
                                and passwd = sha1('".$password."')");
            if(!$result)
            {
                throw new Exception('Could not log you in');
            }
            
            if($result->num_rows == 0)
            {
                throw new Exception('Invalid username or password.');
            }
            
            else if($result->num_rows > 0)
            {
                $row = $result->fetch_object();
                $regtime = time();
                $password = md5(trim($password));
                $token = md5($username.$password.$regtime); // create time for activation
                $token_exptime = time() + 60 * 60 * 24;     // expired after 24 hours
                $result = $conn->query("update user
                                     set token = '".$token."', token_exptime = '".$token_exptime."'
                                     where username = '".$username."'");
                if(!$result)
                {
                    throw new Exception('You have not verifity your email, and now cannot verifity email.');
                }
                else
                {
                       notify_register_mail($username, $token);
                       throw new Exception('Please verifity your email.');
                }
            }
        }
        else if($result->num_rows > 0)
        {
            return true;
        }

    }

    function check_valid_user()
    {
        // see if somebody is logged in and notify them if not
        if(isset($_SESSION['valid_user']))
        {
            echo "Logged in as ".$_SESSION['valid_user'].".<br />";
        }
        else
        {
            // they are not logged in 
            do_html_heading('Problem');
            echo 'You are not logged in.<br />';
            do_html_url('login.php', 'Login');
            do_html_footer();
            exit;
        }
    }

    function change_password($username, $old_password, $new_password)
    {
        // change password for username/old_password to new_password
        // return true or false
        
        // if the old password is right
        // change their password to new_password and return true
        
        login($username, $old_password);
        $conn = db_connect();
        $result = $conn->query("update user 
                                set passwd = sha1('".$new_password."')
                                where username = '".$username."'");
        if(!$result)
        {
            throw new Exception('Password could not be changed.');
        }
        else
        {
            return true;  // change successfully
        }
    }

    function reset_password($username)
    {
        // set password for username to a random value
        // return the new password or false on failure
        // get a random dictionary word b/w and 13 chars in length
        $new_password = get_random_word(6, 13);
        
        if($new_password == false)
        {
            throw new Exception('Could not generate new password.');
        }
        
        // add a number between 0 and 999 to it 
        // to make it a slightly better password 
        $rand_number = rand(0, 999);
        $new_password .= $rand_number;
        
        // set user's password to this in database or return false
        $conn = db_connect();
        $result = $conn->query("update user 
                                set passwd = sha1('".$new_password."')
                                where username = '".$username."'");
        if(!$result)
        {
            throw new Exception('Could not change password.');  // not changed
        }
        else
        {
            return $new_password;   // changed successfully
        }
    }

    function get_random_word($min_length, $max_length)
    {
        // grab a random word from dictionary between the two lengths
        // and return it 
        
        // generate a random word 
        $word = '';
        // remember to change this path to suit your system 
        $dictionary = '/usr/share/dict/words';    // the ispell dictionary
        $fp = @fopen($dictionary, 'r');
        if(!$fp)
        {
            return false;
        }
        $size = filesize($dictionary);
        
        // go to a random location in dictionary
        $rand_location = rand(0, $size);
        
        fseek($fp, $rand_location);
        
        // get the next whole word of the right length in the file 
        while((strlen($word) < $min_length) || (strlen($word) > $max_length) || (strstr($word, "'")))
        {
            if(feof($fp))
            {
                fseek($fp, 0);  // if at end, go to start
            }
            $word = fgets($fp, 80); // skip first word as it could be partial
            $word = fgets($fp, 80); // the potential password 
        }
        
        $word = trim($word);    // trim the trailing \n from fgets 
        return $word;
    }

    function notify_password($username, $password)
    {
        // notify the user that their password has been changed
        
        $conn = db_connect();
        $result = $conn->query("select email from user 
                                where username = '".$username."'");
        if(!$result)
        {
            throw new Exception('Could not find email address');
        }
        else if ($result->num_rows == 0) 
        {
            throw new Exception('Could not find email address');
            // username not in db
        }
        else
        {
            $row = $result->fetch_object();
            $email = $row->email;
            

            $mesg = "Your PHPBookmark password has been changed to ".$password."\r\n"
                    ."Please change it next time you log in.\r\n";
            
            $mail  = new PHPMailer();
            
            set_mail_para($mail, $username);
            $mail->Subject    = 'PHPBookmark login information';    // 设置邮件标题
            $mail->MsgHTML($mesg);                                  // 设置邮件内容
            $mail->AddAddress($email, $username);
            
            if($mail->Send())
            {
                return true;
            }    
            else 
            {
                throw new Exception('Could not send email.');
            }
        }
    }
    
    function notify_register_mail($username, $token)
    {
        $conn = db_connect();
        $result = $conn->query("select email from user 
                                where username = '".$username."'");
        if(!$result)
        {
            throw new Exception('Could not find email address');
        }
        else if ($result->num_rows == 0) 
        {
            throw new Exception('Could not find email address');
            // username not in db
        }
        else
        {
            $row = $result->fetch_object();
            $email = $row->email;

            $mesg = "Hello! ".$username."：<br/>Thank you for registering a new account at our webmaster<br/>
                    Please click on the link to activate your account<br/> 
                    <a href='http://localhost/PHPbookmark/lib/active.php?verify=".$token."' target= 
                    '_blank'>http://localhost/PHPbookmark/lib/active.php?verify".$token."</a><br/> 
                    If the above link is not clickable, copy it into your browser address bar and enter access, within the link valid for 24 hours.";
            
            $mail  = new PHPMailer();
            
            set_mail_para($mail, $username);
            $mail->Subject    = 'PHPBookmark register information';     // 设置邮件标题
            $mail->MsgHTML($mesg);                                      // 设置邮件内容
            $mail->AddAddress($email, $username);
            
            if($mail->Send())
            {
                return true;
            }    
            else 
            {
                throw new Exception('Could not send email.');
            }
        }
        
    }


?>