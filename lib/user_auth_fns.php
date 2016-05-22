<?php
    require_once('bookmark_fns.php');
    require_once('class.phpmailer.php');
    require_once('class.smtp.php');
    
    function register($username, $email, $password)
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
        
        // if ok, put in db 
        $result = $conn->query("insert into user values
                            ('".$username."', sha1('".$password."'), '".$email."')");
        if(!$result)
        {
            throw new Exception('Could not register you in database - please try again later.');
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
                            and passwd = sha1('".$password."')");
        
        if(!$result)
        {
            throw new Exception('Could not log you in.');
        }
        
        if($result->num_rows > 0)
        {
            return true;
        }
        else {
            throw new Exception('Could not log in in.');
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
            
            
            $from = "From: 554026560@qq.com \r\n";
            $mesg = "Your PHPBookmark password has been changed to ".$password."\r\n"
                    ."Please change it next time you log in.\r\n";
            
            $mail  = new PHPMailer();
     
            $mail->CharSet ="UTF-8";                        //设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置为 UTF-8
            $mail->IsSMTP();                                // 设定使用SMTP服务
            $mail->SMTPAuth   = true;                       // 启用 SMTP 验证功能
            $mail->SMTPSecure = "ssl";                      // SMTP 安全协议
            $mail->Host       = "smtp.163.com";             // SMTP 服务器
            $mail->Port       = 465;                        // SMTP服务器的端口号
            $mail->Username   = "yang-19950427@163.com";    // SMTP服务器用户名
            $mail->Password   = "yang942628986";            // SMTP服务器密码
            $mail->SetFrom('yang-19950427@163.com', 'PHPBookmark'); // 设置发件人地址和名称
            $mail->AddReplyTo("yang-19950427@163.com", $username); 
                                                            // 设置邮件回复人地址和名称
            $mail->Subject    = 'PHPBookmark login information';                     // 设置邮件标题
            $mail->AltBody    = "为了查看该邮件，请切换到支持 HTML 的邮件客户端"; 
                                                        // 可选项，向下兼容考虑
            $mail->MsgHTML($mesg);                         // 设置邮件内容
            $mail->AddAddress('554026560@qq.com', "lam");
            
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