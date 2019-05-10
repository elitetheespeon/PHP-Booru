<?php
//Load required classes
$user = new user();
$logger = new logger();
$ip = $_SERVER['REMOTE_ADDR'];	

//Check if user is banned
if($user->banned_ip($ip)){
    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'FORUM_EDIT', 'BANNED');
	$template=new Template;
    echo $template->render('no_permission.html');
	exit();
}

//Check if user is logged in
if(!$user->check_log()){
    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'FORUM_EDIT', 'NOT_LOGGED_IN');
	$template=new Template;
    echo $template->render('no_permission.html');
	exit();
}

//Process data sent
if(isset($_POST['title']) && isset($_POST['post']) && $f3->get('PARAMS.option') == "edit" && $f3->get('PARAMS.topicid') !== "" && $f3->get('PARAMS.postid') !== "" && $f3->get('PARAMS.page') !== ""){
	//Store vars
	$pid = $f3->get('PARAMS.topicid');
	$cid = $f3->get('PARAMS.postid');
	$ppid = $f3->get('PARAMS.page');
	$uid = $f3->get('checked_user_id');
	//Fix page number
	if ($ppid == 0){
		$ppid = 1;
	}
	//Get the author of forum post
	$postauthorres = $db->exec('SELECT author FROM '.$f3->get('forum_post_table').' WHERE topic_id = ? AND id = ? LIMIT 1',array(1=>$pid,2=>$cid));
	$postauthor = $postauthorres[0]["author"];
	//Check if user is post author or has access to edit
	if($postauthor == $uid || $user->gotpermission('edit_forum_posts')){
		//Store additional vars
		$title = htmlentities($_POST['title'], ENT_QUOTES, 'UTF-8', FALSE);
		$post = htmlentities($_POST['post'], ENT_QUOTES, 'UTF-8', FALSE);
		//Get topic and first (creation) post
		$creationpostres = $db->exec('SELECT t.creation_post, p.id FROM '.$f3->get('forum_post_table').' as p LEFT JOIN '.$f3->get('forum_topic_table').' as t ON (p.topic_id = t.id) WHERE p.id = ? LIMIT 1',array(1=>$cid));
        $creationpost = $creationpostres[0]["creation_post"];
        $creationpostid = $creationpostres[0]["id"];
        //Check this is the first post for topic and update thread title to match post
        if($creationpost == $creationpostid){
            $update1 = $db->exec('UPDATE '.$f3->get('forum_topic_table').' SET topic = ? WHERE id = ?',array(1=>$title,2=>$pid));
        }
		//Update forum post
        $update2 = $db->exec('UPDATE '.$f3->get('forum_post_table').' SET title = ?, post = ? WHERE topic_id = ? AND id = ?',array(1=>$title,2=>$post,3=>$pid,4=>$cid));
		//Done, redirect the user to the page their post is on
		$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'FORUM_EDIT_POST', 'SUCCESS', $pid);
		$f3->reroute('/forum/view/'.$pid.'/'.$ppid.'#'.$cid);
	}else{
		//User does not have access to edit, redirect
		$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'FORUM_EDIT_POST', 'NO_ACCESS', $pid);
		$f3->reroute('/forum/view/'.$pid.'/'.$ppid.'#'.$cid);		
	}
}else if(($f3->get('PARAMS.option') == "pin" || $f3->get('PARAMS.option') == "unpin") && $f3->get('PARAMS.topicid') !== "" && is_numeric($f3->get('PARAMS.topicid')) && $f3->get('PARAMS.page') !== "" && is_numeric($f3->get('PARAMS.page'))){
	//Check if user has access to pin topic
	if($user->gotpermission('pin_forum_topics')){
		//Store vars
		$pin = $f3->get('PARAMS.option');
		$id = $f3->get('PARAMS.topicid');
		$pid = $f3->get('PARAMS.page');
		//Fix page number
		if ($pid == 0){
			$pid = 1;
		}
		//Check if we are unpinning or pinning and update
		if($pin == "pin"){
			$update = $db->exec('UPDATE '.$f3->get('forum_topic_table').' SET priority = \'1\' WHERE id = ?',array(1=>$id));
		}else{
			$update = $db->exec('UPDATE '.$f3->get('forum_topic_table').' SET priority = \'0\' WHERE id = ?',array(1=>$id));
		}
		//Done, redirect the user to the page their topic is on
		$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'FORUM_PIN_TOPIC', 'SUCCESS', $id);
		$f3->reroute('/forum/list/'.$pid);
	}else{
		//User does not have access to pin, redirect
		$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'FORUM_PIN_TOPIC', 'NO_ACCESS', $id);
		$f3->reroute('/forum/list/'.$pid);		
	}
}else if(($f3->get('PARAMS.option') == "lock" || $f3->get('PARAMS.option') == "unlock") && $f3->get('PARAMS.topicid') !== "" && is_numeric($f3->get('PARAMS.topicid')) && $f3->get('PARAMS.page') !== "" && is_numeric($f3->get('PARAMS.page'))){
	//Check if user has access to lock topic
	if($user->gotpermission('lock_forum_topics')){
		//Store vars
		$lock = $f3->get('PARAMS.option');
		$id = $f3->get('PARAMS.topicid');
		$pid = $f3->get('PARAMS.page');
		//Fix page number
		if ($pid == 0){
			$pid = 1;
		}
		//Check if we are unlocking or locking and update
		if($lock == "lock"){
			$update = $db->exec('UPDATE '.$f3->get('forum_topic_table').' SET locked = true WHERE id = ?',array(1=>$id));
		}else{
			$update = $db->exec('UPDATE '.$f3->get('forum_topic_table').' SET locked = false WHERE id = ?',array(1=>$id));
		}
		//Done, redirect the user to the page their topic is on
		$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'FORUM_LOCK_TOPIC', 'SUCCESS', $id);
		$f3->reroute('/forum/view/'.$id.'/'.$pid);
	}else{
		//User does not have access to lock, redirect
		$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'FORUM_LOCK_TOPIC', 'NO_ACCESS', $id);
		$f3->reroute('/forum/view/'.$id.'/'.$pid);		
	}
}else{
	//Mismatched info, reroute
	$f3->reroute('/forum/list');
}
?>