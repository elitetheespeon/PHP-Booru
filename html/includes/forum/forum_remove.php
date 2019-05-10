 <?php
//Load required classes
$user = new user();
$logger = new logger();
$ip = $_SERVER['REMOTE_ADDR'];	

//Check if user is banned
if($user->banned_ip($ip)){
    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'FORUM_REMOVE', 'BANNED');
	$template=new Template;
    echo $template->render('no_permission.html');
	exit();
}

//Check if user is logged in
if(!$user->check_log()){
    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'FORUM_REMOVE', 'NOT_LOGGED_IN');
	$template=new Template;
    echo $template->render('no_permission.html');
	exit();
}
		
if($f3->get('PARAMS.type') == "post" && $f3->get('PARAMS.page') !== "" && $f3->get('PARAMS.id') !== ""){
	//Note: page = topic id AND id = post id in this loop
	//Store vars
	$pid = $f3->get('PARAMS.id');
	$cid = $f3->get('PARAMS.page');
	$uid = $f3->get('checked_user_id');
	$uname = $f3->get('checked_username');
	//Get author of thread and first (creation) post
	$creationpostres = $db->exec('SELECT t1.author, t2.creation_post FROM '.$f3->get('forum_post_table').' AS t1 JOIN '.$f3->get('forum_topic_table').' AS t2 ON t2.id=t1.topic_id WHERE t1.topic_id = ? LIMIT 1',array(1=>$pid));
    $creationpost = $creationpostres[0]["creation_post"];
    $author = $creationpostres[0]["author"];
	//Check if user created this post or if they have access to delete forum posts
	if($author == $uid || $user->gotpermission('delete_forum_posts')){
		//Make sure we don't erase the first post of a topic as this will break things.
		if($creationpost !== $cid){
			//Delete post
			$delete = $db->exec('DELETE FROM '.$f3->get('forum_post_table').' WHERE id = ?',array(1=>$cid));
		}
		//Done, redirect the user back to the thread
		$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'FORUM_DELETE_POST', 'SUCCESS', $pid);
		$f3->reroute('/forum/view/'.$pid);
	}else{
		//User does not have access to delete post, redirect
		$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'FORUM_DELETE_POST', 'NO_ACCESS', $pid);
		$f3->reroute('/forum/view/'.$pid);
	}
}else if($f3->get('PARAMS.type') == "topic" && $f3->get('PARAMS.page') !== "" && $f3->get('PARAMS.id') !== ""){
	//Check if user has access to delete posts
	if($user->gotpermission('delete_forum_topics')){
		//Store vars
		$fid = $f3->get('PARAMS.id');
		$pid = $f3->get('PARAMS.page');
		//Delete forum topic and posts
		$delete1 = $db->exec('DELETE FROM '.$f3->get('forum_post_table').' WHERE topic_id = ?',array(1=>$fid));
		$delete2 = $db->exec('DELETE FROM '.$f3->get('forum_topic_table').' WHERE id = ?',array(1=>$fid));
		//Done, redirect the user to the page their topic is on
		$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'FORUM_DELETE_TOPIC', 'SUCCESS', $fid);
		$f3->reroute('/forum/list/'.$pid);
	}else{
		//User does not have access to delete, redirect
		$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'FORUM_DELETE_TOPIC', 'NO_ACCESS', $fid);
		$f3->reroute('/forum/list/'.$pid);	
	}
}else{
	//Mismatched info, reroute
	$f3->reroute('/forum/list');
}
?>