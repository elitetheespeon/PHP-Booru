<?php
//Load required classes
$logger = new logger();
$user = new user();
$f3->set('user',$user);

//Check if user is an admin
if(!$user->gotpermission('is_admin')){
    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'ADMIN_EDIT_USER', 'NO_ACCESS');
	$template=new Template;
    echo $template->render('no_permission.html');
	exit();
}

//Check for posted user information
if(isset($_POST['password']) && isset($_POST['group']) && is_numeric($_POST['group'])){
	//Store posted data
	$userid = $_POST['uid'];
	$pass = $_POST['password'];
	$group = $_POST['group'];
	//Check if password was sent
	if($pass !== ""){
		//Update hashed password with group id
		$update = $db->exec('UPDATE '.$f3->get('user_table').' SET pass = ?, ugroup = ? WHERE id = ?',array(1=>$user->hashpass($pass),2=>$group,3=>$userid));
	}else{
		//Update only group id
		$update = $db->exec('UPDATE '.$f3->get('user_table').' SET ugroup = ? WHERE id = ?',array(1=>$group,2=>$userid));
	}
	
	//Check if update query was successful
	if($update){
		//Success
		$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'ADMIN_EDIT_USER', 'SUCCESS', $userid);
		$f3->reroute('/admin/edit_user');
	}else{
		//Fail
		$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'ADMIN_EDIT_USER', 'DB_ERROR', $userid);
		$f3->reroute('/admin/edit_user');
	}
}else if(isset($_POST['user']) && $_POST['user'] !== ""){
	//Store user id
	$userid = $_POST['user'];
	$username = $user->get_username($userid);
	$cgroup = false;
	//Get user/group information
	$groupinfo = $db->exec('SELECT t1.ugroup, t2.id, t2.group_name FROM '.$f3->get('user_table').' AS t1 JOIN '.$f3->get('group_table').' AS t2 WHERE t1.id = ?',array(1=>$userid));
	//Set second step var
	$f3->set('secondstep',true);
	$f3->set('groupinfo',$groupinfo);
	$f3->set('userid',$userid);
	$f3->set('username',$username);
}else{
	//Show default page
}
?>