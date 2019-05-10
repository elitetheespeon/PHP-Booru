<?php
$logger = new logger();
$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'POOL_LIST', $f3->get('PARAMS.id'));

//Number of pools/page
$limit = 40;

//Load required classes
$misc = new misc();
$user = new user();

//Query for total number of pools
$resultpoolcount = $db->exec('SELECT COUNT(id) as count FROM '.$f3->get('pool_table'));
$numrows = $resultpoolcount[0]["count"];

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
        $f3->reroute('/pool/list/'.$f3->get('PARAMS.id'));
	}
	//Query for pool information
	$resultpool = $db->exec('SELECT id, name, created_at, updated_at, user_id, is_public, post_count, description, is_active, is_visible FROM '.$f3->get('pool_table').' ORDER BY id DESC LIMIT ?,?',array(1=>$pg_start,2=>$limit));
}

//Pass vars to template
$f3->set('pools',$resultpool);
$f3->set('userc',$user);
?>