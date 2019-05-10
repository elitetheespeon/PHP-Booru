<?php
	class dmail{
		function __construct()
		{
			
		}

		function send_mail($from_id, $to_id, $title, $body, $parent_id = null){
			global $f3, $db;
            if ($parent_id == null){
                $insert = $db->exec('INSERT INTO '.$f3->get('dmail_table').' (from_id, to_id, title, body, created_at) VALUES (?, ?, ?, ?, NOW())',array(1=>$from_id, 2=>$to_id, 3=>$title, 4=>$body));
            }else{
                $insert = $db->exec('INSERT INTO '.$f3->get('dmail_table').' (from_id, to_id, title, body, created_at, parent_id) VALUES (?, ?, ?, ?, NOW(), ?)',array(1=>$from_id, 2=>$to_id, 3=>$title, 4=>$body, 5=>$parent_id));
            }
            if($insert){
                return $db->lastInsertId();
            }else{
                return false;
            }
		}
		function can_view_mail($mailid, $userid){
			global $f3, $db;
            $result = $db->exec('SELECT id, from_id, to_id FROM '.$f3->get('dmail_table').' WHERE id = ?',array(1=>$mailid));
            if($result[0]["from_id"] == $userid){
                return true;
            }elseif($result[0]["to_id"] == $userid){
                return true;
            }else{
                return false;
            }
		}
		function mark_read($mailid){
			global $f3, $db;
            $update = $db->exec('UPDATE '.$f3->get('dmail_table').' SET has_seen = 1 WHERE id = ?',array(1=>$mailid));
		}
		function count_mail($userid = false, $sent = false, $id = false){
			global $f3, $db;
            if ($userid == FALSE){
                $result = $db->exec('SELECT COUNT(*) as count FROM '.$f3->get('dmail_table'));
            }else{
                if ($sent == FALSE){
                    $result = $db->exec('SELECT COUNT(*) as count FROM '.$f3->get('dmail_table').' WHERE to_id = ?',array(1=>$userid));
                }else{
                    $result = $db->exec('SELECT COUNT(*) as count FROM '.$f3->get('dmail_table').' WHERE from_id = ?',array(1=>$userid));
                }
            }
			$numrows = count($result);
			if($numrows == 0){
				return false;
			}else{
			    return $result[0]["count"];
			}
		}
		function notify_new_dmail($to_userid, $from_userid, $title, $dmail_id){
			global $f3, $db;
            $user = new user;
            $result = $db->exec('SELECT email FROM '.$f3->get('user_table').' WHERE id = ?',array(1=>$to_userid));
            if ($result[0]["email"] == ""){
                return false;
            }else{
                $subject = $f3->get('site_url3').' - new dmail notification';
                $headers = "From: " . strip_tags($f3->get('site_email')) . "\r\n";
                $headers .= "Reply-To: ". strip_tags($f3->get('site_email')) . "\r\n";
                $headers .= "MIME-Version: 1.0\r\n";
                $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
                
                $message = '<html><body>';              
                $message .= '<h3>You have received a new dmail on '.$f3->get('site_url3').'!</h3>';
                $message .= '<p>'.strip_tags($user->get_username($from_userid)).' has sent you a dmail with the subject <i>'.strip_tags($title).'</i>. Click <a href="'.$f3->get('site_url').'mail/view/'.$dmail_id.'">here</a> to view the dmail message in your browser.</p>';
                $message .= '<br /><br /><p><i>You were sent this email because you have opted to be notified when a new dmail is sent to you on '.$f3->get('site_url3').'. If you wish to not receive these notifications, you can change user preferences <a href="'.$f3->get('site_url').'user/home">here</a>.</i></p>';            
                $message .= '</body></html>';
                if (!mail($email, $subject, $message, $headers)) {
                    return false;
                }else{
                    return true;
                }
            }
		}
		function has_new_mail($userid){
			global $db, $f3;
            $result = $db->exec('SELECT COUNT(id) as count FROM '.$f3->get('dmail_table').' WHERE to_id = ? AND has_seen = 0',array(1=>$userid));
            $count = $result[0]["count"];
            if ($count == 0){
                return false;
            }else{
                return true;
            }
		}
        function autocomplete(){
            global $db, $f3;
            //Make sure user is logged in
            $user = new user();
            if(!$user->check_log()){
                $logger->log_action('', $_SERVER['REMOTE_ADDR'], 'DMAIL_AUTOCOMPLETE', 'NO_PERMISSION');
                $template=new Template;
                echo $template->render('no_permission.html');
                exit();
            }
            //Autocomplete processing
            if ($_REQUEST["to_user"] !== ""){
                $to_user = $_REQUEST["to_user"];
                $to_user = $to_user."%";
                $result = $db->exec('SELECT user FROM '.$f3->get('user_table').' WHERE user LIKE ? LIMIT 15',array(1=>$to_user));
                $data = "<ul>";
                foreach ($result as $r) {
                    $data .= "<li>".$r["user"]."</li>";    
                }
                $data .= "</ul>";
                
                echo $data;
                exit();
            }
        }
        function admin_autocomplete(){
            global $db, $f3;
            //Load required classes
            $user = new user();
            $f3->set('user',$user);
            
            //Check if user is an admin
            if(!$user->gotpermission('is_admin')){
                $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'ADMIN_DMAIL_AUTOCOMPLETE', 'NO_ACCESS');
            	$template=new Template;
                echo $template->render('no_permission.html');
            	exit();
            }
            //Autocomplete processing
            if ($_REQUEST["search"] !== ""){
                $search = $_REQUEST["search"];
                $search = $to_user."%";
                $result = $db->exec('SELECT user FROM '.$f3->get('user_table').' WHERE user LIKE ? LIMIT 15',array(1=>$search));
                $data = "<ul>";
                foreach ($result as $r) {
                    $data .= "<li>".$r["user"]."</li>";    
                }
                $data .= "</ul>";
                
                echo $data;
                exit();
            }
        }
	}
?>