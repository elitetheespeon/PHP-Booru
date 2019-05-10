<?php
$logger = new logger();
$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'FORUM_VIEW', $f3->get('PARAMS.id'));

//Number of topics/page
$limit = 20;

//Load required classes
$user = new user();
$misc = new misc();
$resultcount = 0;
$postresult = array();
$id = $f3->get('PARAMS.id');

//Get number of posts for forum topic
$fpostcount = $db->exec('SELECT COUNT(*) as count FROM '.$f3->get('forum_post_table').' WHERE topic_id = ?',array(1=>$id));
$numrows = $fpostcount[0]['count'];

//Check for invalid topic number
if($numrows == 0){
	$f3->reroute('/forum/list');
}

//Check if user is logged in and set username+id
if($user->check_log()){
	$uname = $f3->get('checked_username');
	$uid = $f3->get('checked_user_id');
	$canedit = $user->gotpermission('edit_forum_posts');
	$candelete = $user->gotpermission('delete_forum_posts');
	$canlock = $user->gotpermission('lock_forum_topics');
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
    $f3->reroute('/forum/view/'.$f3->get('PARAMS.id'));
}

//Get forum post info
$fpostinfo = $db->exec('SELECT t1.id, t1.title, t1.post, t1.author, unix_timestamp(t1.creation_date) as creation_date, t2.creation_post FROM '.$f3->get('forum_post_table').' AS t1 JOIN '.$f3->get('forum_topic_table').' AS t2 ON t2.id=t1.topic_id WHERE t1.topic_id = ? ORDER BY id LIMIT ?, ?',array(1=>$id,2=>$pg_start,3=>$limit));

//Start loop through posts
foreach($fpostinfo as $post){
    //Convert results for template
    $postresult[$resultcount]['id'] = $post['id'];
    $postresult[$resultcount]['author'] = $post['author'];
    $postresult[$resultcount]['authorname'] = $user->get_username($post['author']);
    $postresult[$resultcount]['authoravatar'] = $user->get_avatar($post['author']);
    $postresult[$resultcount]['authortitle'] = $user->get_user_title($post['author']);
    $postresult[$resultcount]['authorsignature'] = $misc->short_url($misc->swap_bbs_tags($misc->linebreaks($user->get_signature($post['author']))));
    $postresult[$resultcount]['date_made'] = $misc->date_words($post['creation_date']);
    $postresult[$resultcount]['title'] = $post['title'];
    $postresult[$resultcount]['body'] = $misc->short_url($misc->swap_bbs_tags($misc->linebreaks($post['post'])));
    $postresult[$resultcount]['body_quoted'] = str_replace("'","\'",str_replace("\r\n",'\r\n',str_replace('&#039;',"'",$post['post'])));
    $postresult[$resultcount]['body_normal'] = $post['post'];
    $postresult[$resultcount]['creation_post'] = $post['creation_post'];
    $resultcount++;
}

//Get lock status of thread
$lockstatus = $db->exec('SELECT locked FROM '.$f3->get('forum_topic_table').' WHERE id = ? LIMIT 1',array(1=>$id));
if ($lockstatus[0]["locked"] == 0){
    $locked = false;
}else{
    $locked = true;
}

//Set post info for template
$f3->set('posts',$postresult);
$f3->set('canedit',$canedit);
$f3->set('candelete',$candelete);
$f3->set('canlock',$canlock);
$f3->set('pgnum',$pg_start);
$f3->set('limit',$limit);
$f3->set('selfavatar',$user->get_avatar($f3->get('checked_user_id')));
$f3->set('locked',$locked);
?>