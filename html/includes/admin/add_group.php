<?php
//Load required classes
$logger = new logger();
$user = new user();
$f3->set('user',$user);

//Check if user is an admin
if(!$user->gotpermission('is_admin')){
    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'ADMIN_ADD_GROUP', 'NO_ACCESS');
	$template=new Template;
    echo $template->render('no_permission.html');
	exit();
}

//Check for posted data
if(isset($_POST['gname']) && $_POST['gname'] != ""){
	//Store group name
	$name = $_POST['gname'];
	//Get group name if it already exists
	$groupcount = $db->exec('SELECT COUNT(*) as count FROM '.$f3->get('group_table').' WHERE group_name = ?',array(1=>$name));
	//Check if group already exists.
	if($groupcount[0]["count"] > 0){
		//Group already in db
		$error = "Group already exists.";
		$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'ADMIN_ADD_GROUP', 'GROUP_EXISTS', $name);
	}else{
		//Group not found, continue on and check if default group
		if(isset($_POST['default']) && $_POST['default'] == true){
			//Default group selected, set all current groups to false
			$update = $db->exec('UPDATE '.$f3->get('group_table').' SET default_group = FALSE');
			//Add group and set as default
			$insert = $db->exec('INSERT INTO '.$f3->get('group_table').' (group_name, default_group) VALUES(?, TRUE)',array(1=>$name));
		}else{
			//Add group
			$insert = $db->exec('INSERT INTO '.$f3->get('group_table').' (group_name, default_group) VALUES(?, FALSE)',array(1=>$name));
		}
		//Check if last query was successful
		if($insert){
			//Success
			$error = "Group added.";
			$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'ADMIN_ADD_GROUP', 'SUCCESS', $name);
		}else{
			//Error
			$error = "Could not add group.";
			$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'ADMIN_ADD_GROUP', 'ERROR_DB', $name);
		}
	}
}

//Set error message for template
$f3->set('error1',$error);
?>