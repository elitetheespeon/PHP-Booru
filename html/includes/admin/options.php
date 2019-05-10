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

//Check if new data was sent
	if(isset($_POST['submit'])){
		if(isset($_POST['users']) && $_POST['users'] != ""){
			//Change var in config
		}
		else{
			//Leave alone
		}
	}
?>