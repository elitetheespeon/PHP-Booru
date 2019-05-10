<?php
//Load required classes
$user = new user();
$logger = new logger();
$ip = $_SERVER['REMOTE_ADDR'];	

//Check if user is banned
if($user->banned_ip($ip)){
    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'FORUM_ADD', 'BANNED');
	$template=new Template;
    echo $template->render('no_permission.html');
	exit();
}

//Check if user is logged in
if(!$user->check_log()){
    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'FORUM_ADD', 'NOT_LOGGED_IN');
	$template=new Template;
    echo $template->render('no_permission.html');
	exit();
}

//Check for posted data
if($f3->get('PARAMS.type') == "post"){
	//Check if we are adding a post to a thread
	if($f3->get('PARAMS.id') !== "" && is_numeric($f3->get('PARAMS.id')) && isset($_POST['conf']) && $_POST['conf'] == 1){
		//Store vars
		$title = stripslashes(htmlentities($_POST['title'], ENT_QUOTES, 'UTF-8', FALSE));
		$post = stripslashes(htmlentities($_POST['post'], ENT_QUOTES, 'UTF-8', FALSE));
		$pid = $f3->get('PARAMS.id');
		$limit = $_POST['l'];
		$uid = $f3->get('checked_user_id');           
        //Check if topic is locked
        $locked = $db->exec('SELECT locked FROM '.$f3->get('forum_topic_table').' WHERE id = ?',array(1=>$pid));
        $is_locked = $locked[0]["locked"];
		//If locked, redirect
        if($is_locked == 1){
			$f3->reroute('/forum/list');
		}
        //Check if user has access to add forum post
        $canpost = $db->exec('SELECT forum_can_post FROM '.$f3->get('user_table').' WHERE id = ?',array(1=>$uid));
		$can_post = $canpost[0]["forum_can_post"];
		//If no access, redirect
		if($can_post == 0){
			$f3->reroute('/forum/list');
		}
		//Add post and save id
        $insert = $db->exec('INSERT INTO '.$f3->get('forum_post_table').' (title, post, author, creation_date, topic_id) VALUES(?, ?, ?, NOW(), ?)',array(1=>$title,2=>$post,3=>$uid,4=>$pid));
        $id = $db->lastInsertId();
        //Update main thread last updated date
        $update1 = $db->exec('UPDATE '.$f3->get('forum_topic_table').' SET last_updated = NOW() WHERE id = ?',array(1=>$pid));
		//Update forum post count for user          
		$update2 = $db->exec('UPDATE '.$f3->get('user_table').' SET forum_post_count = forum_post_count + 1 WHERE id = ?',array(1=>$f3->get('checked_user_id')));
		//Get the number for total posts for topic
		$count = $db->exec('SELECT COUNT(*) as count FROM '.$f3->get('forum_post_table').' WHERE topic_id = ?',array(1=>$pid));
		$numrows = $count[0]["count"];
		//Calculate what page number the post will be on
		$ppid = ceil($numrows/$limit);
		if ($ppid == 0){
			$ppid = 1;
		}
		//Done, redirect the user to the page their post is on
		$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'FORUM_ADD_POST', 'SUCCESS', $pid);
		$f3->reroute('/forum/view/'.$pid.'/'.$ppid.'#'.$id);
	}	
}else{
	//Check if we are making a new thread
	if(isset($_POST['topic']) && $_POST['topic'] != "" && isset($_POST['post']) && $_POST['post'] != "" && isset($_POST['conf']) && $_POST['conf'] == 1){
		$topic = stripslashes(htmlentities($_POST['topic'], ENT_QUOTES, 'UTF-8', FALSE));
		$post = stripslashes(htmlentities($_POST['post'], ENT_QUOTES, 'UTF-8', FALSE));
		$uid = $f3->get('checked_user_id');			
        //Check if user has access to add forum topic
        $canaddtopic = $db->exec('SELECT forum_can_create_topic FROM '.$f3->get('user_table').' WHERE id = ?',array(1=>$uid));
        $can_create_topic = $canaddtopic[0]["forum_can_create_topic"];
		//If no access, redirect
		if($can_create_topic == 0){
			$f3->reroute('/forum/list');
		}
        //Add forum topic and save id
        $insert1 = $db->exec('INSERT INTO '.$f3->get('forum_topic_table').' (topic, author, creation_post, last_updated) VALUES(?, ?, \'0\', NOW())',array(1=>$topic,2=>$uid));
        $pid = $db->lastInsertId();
		//Add first forum post for topic and save id
        $insert2 = $db->exec('INSERT INTO '.$f3->get('forum_post_table').' (title, post, author, creation_date, topic_id) VALUES(?, ?, ?, NOW(), ?)',array(1=>$topic,2=>$post,3=>$uid,4=>$pid));
        $id = $db->lastInsertId();
		//Update creation post id for topic now that we have it
        $update = $db->exec('UPDATE '.$f3->get('forum_topic_table').' SET creation_post = ? WHERE id = ?',array(1=>$id,2=>$pid));
        //Done, redirect the user to the page their post is on
        $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'FORUM_ADD_TOPIC', 'SUCCESS', $pid);
        $f3->reroute('/forum/view/'.$pid.'#'.$id);
	}
}
?>