<?php
//Load required classes
$logger = new logger();
$user = new user();
$f3->set('user',$user);
set_time_limit(0);

//Check if user is an admin
if(!$user->gotpermission('is_admin')){
    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'ADMIN_BAN_USER', 'NO_ACCESS');
	$template=new Template;
    echo $template->render('no_permission.html');
	exit();
}

//Check if id was sent and process
if($f3->get('PARAMS.id') !== null && is_numeric($f3->get('PARAMS.id'))){
	//Store user id
	$ban_id = $f3->get('PARAMS.id');
	//Get info for user id
	$userinfo = $db->exec('SELECT id, user, ip FROM '.$f3->get('user_table').' WHERE id = ? LIMIT 1',array(1=>$ban_id));
	$userresult = $userinfo[0];
	//Check if there is a ban reason
	if(!isset($_POST['ban_reason'])){
		//Make sure user is not banning themselves
		if(mb_strtolower($userresult['user']) == "anonymous" || mb_strtolower($userresult['user']) == mb_strtolower($f3->get('checked_username'))){
			//Redirect
		}
		//Set second step for template
		$f3->set('userresult',$userresult);
		$f3->set('secondstep',true);
	}else{
		//Store posted data
		$ban_reason = $_POST['ban_reason'];
		$ban_id = $userresult['id'];
		$ban_username = $userresult['user'];
		$ban_ip = $userresult['ip'];
	
		//Gather logged IPs from all other site functions
		//Now attempting to ban IP address in user table
		$insert1 = $db->exec('INSERT INTO '.$f3->get('banned_ip_table').' (ip,user,reason,date_added) VALUES(?, ?, ?, ?)',array(1=>$ban_ip,2=>$f3->get('checked_username'),3=>$ban_reason,4=>time()));
		/**
		//Now attempting to ban IP addresses in comment_vote table
		$info2 = $db->exec('SELECT * FROM '.$f3->get('comment_vote_table').' WHERE user_id = ? GROUP BY ip',array(1=>$ban_id));
		foreach($info2 as $row){
			$ban_ip = $row['ip'];
			if ($ban_ip !== ""){
				$insert2 = $db->exec('INSERT INTO '.$f3->get('banned_ip_table').' (ip,user,reason,date_added) VALUES(?, ?, ?, ?)',array(1=>$ban_ip,2=>$f3->get('checked_username'),3=>$ban_reason,4=>time()));
			}
		}
		
		//Now attempting to ban IP addresses in comment table
		$info3 = $db->exec('SELECT * FROM '.$f3->get('comment_table').' WHERE user = ? GROUP BY ip',array(1=>$ban_id));
		foreach($info3 as $row){
			$ban_ip = $row['ip'];
			if ($ban_ip !== ""){
				$insert3 = $db->exec('INSERT INTO '.$f3->get('banned_ip_table').' (ip,user,reason,date_added) VALUES(?, ?, ?, ?)',array(1=>$ban_ip,2=>$f3->get('checked_username'),3=>$ban_reason,4=>time()));
			}
		}	
		
		//Now attempting to ban IP addresses in note table
		$info4 = $db->exec('SELECT * FROM '.$f3->get('note_table').' WHERE user_id = ? GROUP BY ip',array(1=>$ban_id));	
		foreach($info4 as $row){
			$ban_ip = $row['ip'];
			if ($ban_ip !== ""){
				$insert4 = $db->exec('INSERT INTO '.$f3->get('banned_ip_table').' (ip,user,reason,date_added) VALUES(?, ?, ?, ?)',array(1=>$ban_ip,2=>$f3->get('checked_username'),3=>$ban_reason,4=>time()));
			}
		}
		
		//Now attempting to ban IP addresses in post vote table
		$info5 = $db->exec('SELECT * FROM '.$f3->get('post_vote_table').' WHERE user_id = ? GROUP BY ip',array(1=>$ban_id));
		foreach($info5 as $row){
			$ban_ip = $row['ip'];
			if ($ban_ip !== ""){
				$insert5 = $db->exec('INSERT INTO '.$f3->get('banned_ip_table').' (ip,user,reason,date_added) VALUES(?, ?, ?, ?)',array(1=>$ban_ip,2=>$f3->get('checked_username'),3=>$ban_reason,4=>time()));
			}
		}		
		
		//Now attempting to ban IP addresses in tag history table
		$info6 = $db->exec('SELECT * FROM '.$f3->get('tag_history_table').' WHERE user_id = ? GROUP BY ip',array(1=>$ban_id));
		foreach($info6 as $row){
			$ban_ip = $row['ip'];
			if ($ban_ip !== ""){
				$insert6 = $db->exec('INSERT INTO '.$f3->get('banned_ip_table').' (ip,user,reason,date_added) VALUES(?, ?, ?, ?)',array(1=>$ban_ip,2=>$f3->get('checked_username'),3=>$ban_reason,4=>time()));
			}
		}	
		
		//Now attempting to ban IP addresses in post table	
		$info7 = $db->exec('SELECT * FROM '.$f3->get('post_table').' WHERE owner = ? ORDER BY id DESC',array(1=>$ban_id));
		foreach($info7 as $row){
			$ban_ip = $row['ip'];
			if ($ban_ip !== ""){
				$insert7 = $db->exec('INSERT INTO '.$f3->get('banned_ip_table').' (ip,user,reason,date_added) VALUES(?, ?, ?, ?)',array(1=>$ban_ip,2=>$f3->get('checked_username'),3=>$ban_reason,4=>time()));
			}
		}
		**/
		//Log it!
		$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'ADMIN_BAN_USER', 'SUCCESS', $ban_id);
	}
}else{
	//No id sent, give default page.
}
?>