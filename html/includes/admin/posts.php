<?php
//Load required classes
$logger = new logger();
$user = new user();
$f3->set('user',$user);

//Number of reports/page
$limit = 20;

//Check if user is an admin
if(!$user->gotpermission('is_admin')){
    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'ADMIN_POSTS', 'NO_ACCESS');
	$template=new Template;
    echo $template->render('no_permission.html');
	exit();
}

//Check if unreport id was sent
if($f3->get('PARAMS.reportid') !== null && is_numeric($f3->get('PARAMS.reportid')) && $f3->get('PARAMS.type') == 'unreport'){
	//Store post id
	$post_id = $f3->get('PARAMS.reportid');
	//Update comment to unreported
	$update = $db->exec('DELETE FROM '.$f3->get('flagged_post_table').' WHERE post_id = ?',array(1=>$post_id));
	//Check if update query was successful
	if($update){
		//Post unflagged
		$message = "Unflagged post!";
		$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'ADMIN_UNFLAG_POST', 'SUCCESS', $post_id);
	}
}

//Query for total number of reported posts
$resultreportcount = $db->exec('SELECT COUNT(id) as count FROM '.$f3->get('flagged_post_table').' WHERE is_resolved = 0');
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
        $f3->reroute('/admin/reported_posts');
	}
	//Query for reported post information
	$resultreport = $db->exec('SELECT p.id, hash, reason, score, creation_date FROM '.$f3->get('flagged_post_table').' as fp LEFT JOIN '.$f3->get('post_table').' as p ON (fp.post_id = p.id) WHERE fp.is_resolved = 0 ORDER BY fp.id LIMIT ?,?',array(1=>$pg_start,2=>$limit));
}

//Pass vars to template
$f3->set('posts',$resultreport);
$f3->set('message',$message);
?>