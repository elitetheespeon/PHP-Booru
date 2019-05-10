<?php
//Load required classes
$user = new user();
$logger = new logger();
$ip = $_SERVER['REMOTE_ADDR'];
//Check if user is banned
if($user->banned_ip($ip)){
    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'POOL_ADD', 'BANNED');
	$template=new Template;
    echo $template->render('no_permission.html');
	exit();
}
//Check if user is logged in and has access
if(!$user->check_log() && !$user->gotpermission('edit_posts')){
    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'POOL_ADD', 'NO_PERMISSON');
	$template=new Template;
    echo $template->render('no_permission.html');
	exit();
}
//Public is a checkbox so we need some extra validation.
if(isset($_POST['public'])){
    $public = 1;
}else{
    $public = 0;
}
//Check if data has been posted and process.
if(isset($_POST['submit']) && $_POST['name'] !== ""){
    $insert = $db->exec('INSERT INTO '.$f3->get('pool_table').' (name, created_at, updated_at, user_id, is_public, post_count, description, is_active, is_visible) VALUES (?, NOW(), NOW(), ?, ?, 0, ?, 1, ?)',array(1=>$_POST['name'],2=>$f3->get('checked_user_id'),3=>$public,4=>$_POST['desc'],5=>$public));
    $insid = $db->lastInsertId();
    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'ADD_POOL', 'SUCCESS', $insid);
    $f3->reroute('/pool/view/'.$insid);
}
?>