<?php
//Load required classes
$logger = new logger();
$user = new user();
$f3->set('user',$user);

//Number of reports/page
$limit = 50;

//Check if user is an admin
if(!$user->gotpermission('is_admin')){
    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'ADMIN_COMMENT', 'NO_ACCESS');
	$template=new Template;
    echo $template->render('no_permission.html');
	exit();
}

//Check if unreport id was sent
if($f3->get('PARAMS.reportid') !== null && is_numeric($f3->get('PARAMS.reportid')) && $f3->get('PARAMS.type') == 'unreport'){
	//Store comment id
	$comment_id = $f3->get('PARAMS.reportid');
	//Update comment to unreported
	$update = $db->exec('UPDATE '.$f3->get('comment_table').' SET spam=\'0\' WHERE id = ?',array(1=>$comment_id));
	//Check if update query was successful
	if($update){
		//Comment unflagged
		$message = "Unflagged comment!";
		$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'ADMIN_UNFLAG_COMMENT', 'SUCCESS', $comment_id);
	}
}

//Query for total number of reported comments
$resultreportcount = $db->exec('SELECT COUNT(id) as count FROM '.$f3->get('comment_table').' WHERE spam=\'1\' ORDER BY id ASC');
$numrows = $resultreportcount[0]["count"];

//Pagination
$pages = new Pagination($numrows, $limit);
$pages->setTemplate('pagination.html');
$f3->set('pagebrowser', $pages->serve());
if ($f3->get('PARAMS.page') == ""){
	$f3->set('PARAMS.page',1);
}	

//Check if there are no results.
if($numrows == 0){
	//No results found.
}else{
	//Results found
	$pg_start = $limit*($f3->get('PARAMS.page')-1);
	//Make sure page is not higher than max rows
    if ($pg_start > $numrows || $pg_start < 0){
        $f3->reroute('/admin/reported_comments');
	}
	//Query for reported comment information
	$resultreport = $db->exec('SELECT id, comment, ip, user, posted_at, score, post_id FROM '.$f3->get('comment_table').' WHERE spam=\'1\' ORDER BY id LIMIT ?,?',array(1=>$pg_start,2=>$limit));
	//Fix up date and username
	foreach($resultreport as $key => $row){
		$resultreport[$key]["username"] = $user->get_username($row['user']);
		$resultreport[$key]["date"] = $row['posted_at'];
		if($row['user'] == null){
			$resultreport[$key]["username"] = 'Anonymous';
		}
	}
}

//Pass vars to template
$f3->set('comments',$resultreport);
$f3->set('message',$message);
?>