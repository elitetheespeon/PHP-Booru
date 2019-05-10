<?php
//Load required classes
$user = new user();
$misc = new misc();
$dmail = new dmail();
$logger = new logger();
$id = $_GET['id'];
$uname = $f3->get('checked_username');
$uid = $f3->get('checked_user_id');
$f3->set('user',$user);

//Check if user is an admin
if(!$user->gotpermission('is_admin')){
    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'ADMIN_DMAIL_VIEW', 'NO_ACCESS');
	$template=new Template;
    echo $template->render('no_permission.html');
	exit();
}

//Get message info
$messageinfo = $db->exec('SELECT * FROM '.$f3->get('dmail_table').' WHERE id = ?',array(1=>$f3->get('PARAMS.id')));
$id = $messageinfo[0]["id"];

//Convert results for template
$from_id = $messageinfo[0]["from_id"];
$from_username = $user->get_username($messageinfo[0]["from_id"]);
$from_avatarinfo = $user->get_avatar($messageinfo[0]["from_id"]);
$from_title = $user->get_user_title($messageinfo[0]["from_id"]);
$from_signature = $misc->short_url($misc->swap_bbs_tags($misc->linebreaks($user->get_signature($messageinfo[0]["from_id"])))); 
$to_id = $messageinfo[0]["to_id"];
$to_username = $user->get_username($messageinfo[0]["to_id"]);
$title = $messageinfo[0]["title"];
$body = $misc->short_url($misc->swap_bbs_tags($misc->linebreaks($messageinfo[0]["body"])));
$date_made = $misc->date_words(strtotime($messageinfo[0]["created_at"]));
$has_seen = $messageinfo[0]["has_seen"];
$parent_id = $messageinfo[0]["parent_id"];

//Set the right parent ID
if (!empty($parent_id)){
    $parentid = $parent_id;
}else{
    $parentid = $id;
}

//Set message info for template
$f3->set('id',$id);
$f3->set('from_id',$from_id);
$f3->set('from_username',$from_username);
$f3->set('from_avatarinfo',$from_avatarinfo);
$f3->set('from_title',$from_title);
$f3->set('from_signature',$from_signature);
$f3->set('to_id',$to_id);
$f3->set('to_username',$to_username);
$f3->set('title',$title);
$f3->set('body',$body);
$f3->set('date_made',$date_made);
$f3->set('has_seen',$has_seen);
$f3->set('parentid',$parentid);
?>