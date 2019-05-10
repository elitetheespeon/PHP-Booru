<?php
$logger = new logger();
$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'FORUM_LIST', $f3->get('PARAMS.page'));

//Number of topics/page
$limit = 40;

//Load required classes
$misc = new misc();
$user = new user();
$resultcount = 0;
$threadresult = array();

//Query for total number of topics
$topiccount = $db->exec('SELECT COUNT(id) as count FROM '.$f3->get('forum_topic_table'));
$numrows = $topiccount[0]["count"];

//Check if user is logged in and set username+id
if($user->check_log()){
	$uname = $f3->get('checked_username');
	$uid = $f3->get('checked_user_id');
	$canpin = $user->gotpermission('pin_forum_topics');
	$candelete = $user->gotpermission('delete_forum_topics');
	$canlock = $user->gotpermission('lock_forum_topics');
}else{
    $canpin = false;
    $canlock = false;
    $candelete = false;
}

//Pagination stuff	
$pages = new Pagination($numrows, $limit);
$pages->setTemplate('pagination.html');
$f3->set('pagebrowser', $pages->serve());
if ($f3->get('PARAMS.page') == ""){
	$f3->set('PARAMS.page',1);
}

//Convert page number for db query
if ($f3->get('PARAMS.page') == 0){
    $f3->set('PARAMS.page',1);
}
$pg_start = $limit*($f3->get('PARAMS.page')-1);
$pg_curr = $pg_start;
$pg_end = $pg_start+$limit;
if ($pg_end > $numrows){
	$pg_end = $numrows;
}

//Make sure page is not higher than max rows
if ($pg_start > $numrows || $pg_start < 0){
    $f3->reroute('/forum/list/');
}

//Get forum thread info
$fthreadinfo = $db->exec('SELECT id, topic, unix_timestamp(last_updated) as last_updated, author, locked, priority FROM '.$f3->get('forum_topic_table').' ORDER BY priority DESC, last_updated DESC LIMIT ?, ?',array(1=>$pg_start,2=>$limit));

//Start loop through threads
foreach($fthreadinfo as $thread){
	//Get post count for topic
	$repliescount = $db->exec('SELECT COUNT(id) as count FROM '.$f3->get('forum_post_table').' WHERE topic_id = ?',array(1=>$thread['id']));
	
	//Get last poster
	$lastpostedid = $db->exec('SELECT author FROM '.$f3->get('forum_post_table').' WHERE topic_id = ? ORDER BY creation_date DESC LIMIT 1',array(1=>$thread['id']));

    //Convert results for template
    $threadresult[$resultcount]['id'] = $thread['id'];
    $threadresult[$resultcount]['replies'] = ($repliescount[0]["count"])-1;
    $threadresult[$resultcount]['last_updated_by'] = $lastpostedid[0]['author'];
    $threadresult[$resultcount]['last_updated_by_name'] = $user->get_username($lastpostedid[0]['author']);
    $threadresult[$resultcount]['author'] = $thread['author'];
    $threadresult[$resultcount]['authorname'] = $user->get_username($thread['author']);    
    $threadresult[$resultcount]['date_now'] = $misc->date_words($thread['last_updated']);
    $threadresult[$resultcount]['priority'] = $thread['priority'];
    $threadresult[$resultcount]['locked'] = $thread['locked'];
    $threadresult[$resultcount]['topic'] = $thread['topic'];
    $threadresult[$resultcount]['lastpage'] = ceil($repliescount[0]["count"] / 20);
    $resultcount++;
}

//Set post thread for template
$f3->set('threads',$threadresult);
$f3->set('canpin',$canpin);
$f3->set('candelete',$candelete);
$f3->set('canlock',$canlock);
?>