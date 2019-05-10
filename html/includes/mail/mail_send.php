<?php
//Init classes
$dmail = new dmail();
$user = new user();
$logger = new logger();

//Check if user is banned
if($user->banned_ip($_SERVER['REMOTE_ADDR'])){
    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'SEND_MAIL', 'BANNED');
	$template=new Template;
    echo $template->render('no_permission.html');
	exit();
}

//Check if user is logged in
if(!$user->check_log()){
    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'SEND_MAIL', 'NOT_LOGGED_IN');
	$template=new Template;
    echo $template->render('no_permission.html');
	exit();
}

//Check if post var is set and make sure nothing is null
if($_POST['conf'] == 1 && $_POST['to_user'] !== "" && $_POST['title'] !== "" && $_POST['post'] !== ""){
    //Look up username to id
    $to_id = $user->get_userid($_POST['to_user']);
    if ($to_id !== false){
        //Check if parent ID was sent
        if ($_POST['parentid'] !== "" && is_numeric($_POST['parentid'])){
            //Check to make sure the parentid is a post the user has access to view.
            if ($dmail->can_view_mail($_POST['parentid'], $f3->get('checked_user_id'))){
                //Put dmail into database
                $lastid = $dmail->send_mail($f3->get('checked_user_id'), $to_id, $_POST['title'], $_POST['post'], $_POST['parentid']);
                //Send notification email if user has it enabled.
                if ($user->get_notifications($to_id) == 1){
                    $dmail->notify_new_dmail($to_id, $f3->get('checked_user_id'), $_POST['title'], $lastid);
                }
            }
        }else{
            //Put dmail into database
            $lastid = $dmail->send_mail($f3->get('checked_user_id'), $to_id, $_POST['title'], $_POST['post']);
            //Send notification email if user has it enabled.
            if ($user->get_notifications($to_id) == 1){
                $dmail->notify_new_dmail($to_id, $f3->get('checked_user_id'), $_POST['title'], $lastid);
            }
        }
		//Mail was sent, send to mail message
		$f3->reroute('/mail/view/'.$lastid);
    }else{
		//Something went wrong
		$f3->reroute('/mail');            
    }
}else{
    //Nothing was passed, let's show the HTML form.
    //Check if the userid was passed and is a number.
    if($f3->get('PARAMS.id') !== "" && is_numeric($f3->get('PARAMS.id'))){
        $to_username = $user->get_username($f3->get('PARAMS.id'));
        $to_userid = $f3->get('PARAMS.id');
    }

    //Get info for current user
    $avatarinfo = $user->get_avatar($f3->get('checked_user_id'));
    
    //Store vars for template
    $f3->set('to_userid',$to_userid);
    $f3->set('to_username',$to_username);
    $f3->set('avatarinfo',$avatarinfo);
}