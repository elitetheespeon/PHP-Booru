<?php
//Load required classes
$logger = new logger();
$user = new user();
$f3->set('user',$user);

//Check if user is an admin
if(!$user->gotpermission('is_admin')){
    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'ADMIN_MASS_PARENT', 'NO_ACCESS');
	$template=new Template;
    echo $template->render('no_permission.html');
	exit();
}
//Check for posted data
if(!isset($_POST['start']) && !isset($_POST['end']) && !isset($_POST['parent'])){
	//Show default page
}else{
	$start = $_POST['start'];
	$end = $_POST['end'];
	$parent_id = $_POST['parent'];
	while($start<=$end){
		$parent_check1 = $db->exec('SELECT COUNT(*) as count FROM '.$f3->get('post_table').' WHERE id = ?',array(1=>$parent_id));
		if($parent_check1[0]["count"] > 0){
			$insert = $db->exec('INSERT INTO '.$f3->get('parent_child_table').' (parent,child) VALUES(?,?)',array(1=>$parent_id,2=>$start));
			$update = $db->exec('UPDATE '.$f3->get('post_table').' SET parent = ? WHERE id = ?',array(1=>$parent_id,2=>$start));
		}
		$start++;
	}
	$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'ADMIN_MASS_PARENT', 'SUCCESS');
}
?>