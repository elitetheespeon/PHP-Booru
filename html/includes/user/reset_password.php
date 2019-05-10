<?php
	//Start session and init classes
	session_start();
	$user = new user();
	$misc = new misc();
	$secondstep = false;
	
	//Check if user is logged in
	if($user->check_log()){
		$f3->reroute('/user/home');
	}
	
	//Process first step of password reset - [sent username] [sends reset email]
	if(isset($_POST['username']) && $_POST['username'] != ""){
		$user = stripslashes(htmlentities($_POST['username'], ENT_QUOTES, 'UTF-8'));
		$result = $db->exec('SELECT email, id FROM '.$f3->get('user_table').' WHERE user = ? LIMIT 1',array(1=>$user));
        $count = count($result);
		if($count > 0){	
			$row = $result[0];
			if($row['email'] != "" && $row['email'] != NULL && strpos($row['email'],"@") !== false && strpos($row['email'],".") !== false && strlen($row['email']) > 2){		
				$code = hash('sha256',rand(132,1004958327747882664857));
				$link = $f3->get('site_url')."user/resetpass/".$code."/".$row['id'];
				$body = 'A password reset has been requested for your account.<br /><br /> If you didn\'t request this, please ignore this email.<br /><br />To reset your password, please click on this link: <a href="'.$link.'">'.$link.'</a>';
				$misc->send_mail($row['email'],$email_recovery_subject,$body);
				$update = $db->exec('UPDATE '.$f3->get('user_table').' SET mail_reset_code = ? WHERE id = ?',array(1=>$code,2=>$row['id']));
				$message = "An email with a reset link has been sent to your mailbox.";
			}else{
				$message = "No email has been added to this account.";
            }
		}else{
			$message = "No email has been added to this account.";
        }
	}
	
	//Process second step of password reset - [sent reset code and id] [sets code in session]
	if($f3->get('PARAMS.code') != "" && $f3->get('PARAMS.id') != "" && is_numeric($f3->get('PARAMS.id'))){
		$id = $f3->get('PARAMS.id');
		$code = $f3->get('PARAMS.code');
		$result = $db->exec('SELECT id FROM '.$f3->get('user_table').' WHERE id = ? AND mail_reset_code = ? LIMIT 1',array(1=>$id,2=>$code));
		if(count($result) > 0){
			$_SESSION['reset_code'] = $code;
			$_SESSION['tmp_id'] = $id;
			$secondstep = true;
		}else{
			$message = "Invalid reset link.";
		}
	}
	
	//Process last step of password reset - [sent new password, session id, reset code] [changes password]
	if(isset($_POST['new_password']) && $_POST['new_password'] != "" && isset($_SESSION['tmp_id']) && $_SESSION['tmp_id'] != "" && is_numeric($_SESSION['tmp_id']) && isset($_SESSION['reset_code']) && $_SESSION['reset_code'] != ""){
		$code = $_SESSION['reset_code'];
		$id = $_SESSION['tmp_id'];
		$pass = $_POST['new_password'];
		$result = $db->exec('SELECT id FROM '.$f3->get('user_table').' WHERE id = ? AND mail_reset_code = ? LIMIT 1',array(1=>$id,2=>$code));
		if(count($result) > 0){
			$user->update_password($id,$pass);
			$update = $db->exec('UPDATE '.$f3->get('user_table').' SET mail_reset_code = \'\' WHERE id = ? AND mail_reset_code = ?',array(1=>$id,2=>$code));
			unset($_SESSION['tmp_id']);
			unset($_SESSION['reset_code']);
			$message = "Your password has been changed successfully.";
		}else{
			$message = "There was an error resetting your password.";
		}
	}
	
	//Store vars for template
	$f3->set('message',$message);
	$f3->set('secondstep',$secondstep);
?>