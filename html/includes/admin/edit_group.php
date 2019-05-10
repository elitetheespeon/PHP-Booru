<?php
//Load required classes
$logger = new logger();
$user = new user();
$f3->set('user',$user);

//Check if user is an admin
if(!$user->gotpermission('is_admin')){
    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'ADMIN_EDIT_GROUP', 'NO_ACCESS');
	$template=new Template;
    echo $template->render('no_permission.html');
	exit();
}

//Check if group id to delete was passed and process
if($f3->get('PARAMS.delete') !== "" && is_numeric($f3->get('PARAMS.delete'))){
	//Store group id
	$del_id = $f3->get('PARAMS.delete');
	//Delete group id
	$delete = $db->exec('DELETE FROM '.$f3->get('group_table').' WHERE id = ?',array(1=>$del_id));
	$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'ADMIN_DELETE_GROUP', 'SUCCESS', $del_id);
	$error = "Group Deleted.";
}

//Check if posted data is valid and process
if(isset($_POST['check']) && $_POST['check'] == 1){
	//Fix up posted data
	(isset($_POST['delete_posts']) && $_POST['delete_posts'] == true) ? $dposts = TRUE : $dposts = FALSE;
	(isset($_POST['delete_comments']) && $_POST['delete_comments'] == true) ? $dcomments = TRUE : $dcomments = FALSE;
	(isset($_POST['admin_panel']) && $_POST['admin_panel'] == true) ? $apanel = TRUE : $apanel = FALSE;
	(isset($_POST['is_default']) && $_POST['is_default'] == true) ? $is_default = TRUE : $is_default = FALSE;
	(isset($_POST['rnotes']) && $_POST['rnotes'] == true) ? $rnotes = TRUE : $rnotes = FALSE;
	(isset($_POST['rtags']) && $_POST['rtags'] == true) ? $rtags = TRUE : $rtags = FALSE;
	(isset($_POST['fposts']) && $_POST['fposts'] == true) ? $fposts = TRUE : $fposts = FALSE;
	(isset($_POST['ftopics']) && $_POST['ftopics'] == true) ? $ftopics = TRUE : $ftopics = FALSE;
	(isset($_POST['flock']) && $_POST['flock'] == true) ? $flock = TRUE : $flock = FALSE;
	(isset($_POST['fedit']) && $_POST['fedit'] == true) ? $fedit = TRUE : $fedit = FALSE;
	(isset($_POST['fpin']) && $_POST['fpin'] == true) ? $fpin = TRUE : $fpin = FALSE;
	(isset($_POST['anotes']) && $_POST['anotes'] == true) ? $anotes = TRUE : $anotes = FALSE;
	(isset($_POST['cupload']) && $_POST['cupload'] == true) ? $cupload = TRUE : $cupload = FALSE;
	(isset($_POST['iadmin']) && $_POST['iadmin'] == true) ? $iadmin = TRUE : $iadmin = FALSE;
	(isset($_POST['appposts']) && $_POST['appposts'] == true) ? $appposts = TRUE : $appposts = FALSE;
	//Check if sent data shows group is default
	if($is_default == TRUE){
		//Group is default, remove default bit from all others
		$update2 = $db->exec('UPDATE '.$f3->get('group_table').' SET default_group = FALSE');
	}
	//Update group with any and all edits
	$update1 = $db->exec('UPDATE '.$f3->get('group_table').' SET delete_posts = ?, delete_comments = ?, admin_panel = ?, default_group = ?, reverse_notes = ?, reverse_tags = ?, delete_forum_posts = ?, delete_forum_topics = ?, lock_forum_topics = ?, edit_forum_posts = ?, pin_forum_topics = ?, alter_notes = ?, can_upload = ?, is_admin = ?, approve_posts =? WHERE id = ?',array(1=>$dposts,2=>$dcomments,3=>$apanel,4=>$is_default,5=>$rnotes,6=>$rtags,7=>$fposts,8=>$ftopics,9=>$flock,10=>$fedit,11=>$fpin,12=>$anotes,13=>$cupload,14=>$iadmin,15=>$appposts,16=>$_POST['group']));
	
	//Check if update ran successfully
	if($update1){
		//Success
		$error = "Permissions edited.";
		$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'ADMIN_EDIT_GROUP', 'SUCCESS', $_POST['group']);
	}else{
		//Fail
		$error = "Failed to edit permissions.";
		$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'ADMIN_EDIT_GROUP', 'DB_ERROR', $_POST['group']);
	}
}

//Check if first step (dropdown) or second step (editing groups)
if($_POST['group_name'] !== null){
	//Store group name
	$gname = $_POST['group_name'];
	//Get group information
	$groupinfo = $db->exec('SELECT * FROM '.$f3->get('group_table').' WHERE id = ?',array(1=>$gname));
	//Set second step var, group info, group name for template
	$f3->set('error1',$error);
	$f3->set('gname',$gname);
	$f3->set('secondstep',true);
	$f3->set('groupinfo',$groupinfo[0]);
}else{
	//Store user id
	$uid = $f3->get('checked_user_id');
	//Get group names
	$groupnames = $db->exec('SELECT group_name, id, (SELECT t1.is_admin FROM '.$f3->get('group_table').' AS t1 JOIN '.$f3->get('user_table').' AS t2 ON t2.id = ? WHERE t1.id=t2.ugroup) AS admin FROM '.$f3->get('group_table').' ORDER BY id ASC',array(1=>$uid));
	//Set group names for template
	$f3->set('groupnames',$groupnames);
	$f3->set('error1',$error);
}
?>