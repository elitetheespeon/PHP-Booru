<?php
//Load required classes
$logger = new logger();
$user = new user();
$f3->set('user',$user);

//Check if user is an admin or mod
if(!$user->gotpermission('is_admin') || !$user->gotpermission('approve_posts')){
    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'ADMIN_HOME', 'NO_ACCESS');
	$template=new Template;
    echo $template->render('no_permission.html');
	exit();
}
?>