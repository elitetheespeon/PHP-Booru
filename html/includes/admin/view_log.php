<?php
//Load required classes
$logger = new logger();
$user = new user();
$misc = new misc();
$f3->set('user',$user);

//Number of results per page
$limit = 50;

//Check if user is an admin
if(!$user->gotpermission('is_admin')){
    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'ADMIN_ADD_GROUP', 'NO_ACCESS');
	$template=new Template;
    echo $template->render('no_permission.html');
	exit();
}

//Save sent information
$search = $_REQUEST["search"];
$type = $_REQUEST["type"];
//Check if searching or viewing all
if ($search !== ""){
    //Check which type of search
    switch ($type){
        case 1:
            $searching = true;
            $reqcount = $db->exec('SELECT COUNT(*) as count FROM '.$f3->get('logs_table').' AS l LEFT JOIN '.$f3->get('user_table').' AS u ON (u.id = l.uid) WHERE u.user = ?',array(1=>$search));
            $mod = "u.user";
            $type1 = "selected='selected'";
            $type2 = "";
            $type3 = "";
            break;
        case 2:
            $searching = true;
            $reqcount = $db->exec('SELECT COUNT(*) as count FROM '.$f3->get('logs_table').' WHERE ip = ?',array(1=>$search));
            $mod = "l.ip";
            $type1 = "";
            $type2 = "selected='selected'";
            $type3 = "";
            break;
        case 3:
            $searching = true;
            $reqcount = $db->exec('SELECT COUNT(*) as count FROM '.$f3->get('logs_table').' WHERE result = ?',array(1=>$search));
            $mod = "l.result";
            $type1 = "";
            $type2 = "";
            $type3 = "selected='selected'";
            break;
        default:
            $searching = false;
            break;                
    }
}else{
    //Not searching
    $searching = false;
}

//Check if not searching
if ($searching == true){
    //Searching, data is already processed
}else{
    //Not searching
    $reqcount = $db->exec('SELECT COUNT(*) as count FROM '.$f3->get('logs_table'));
}

$numrows = $reqcount[0]["count"];

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
        $f3->reroute('/admin/view_log');
	}
	//Query for log information
    if ($searching == true){
        $resultreq = $db->exec('SELECT l.date, u.user, l.uid, l.ip, l.action, l.result, l.cid  FROM '.$f3->get('logs_table').' AS l LEFT JOIN '.$f3->get('user_table').' AS u ON (u.id = l.uid) WHERE '.$mod.' = ? ORDER BY l.date DESC LIMIT ?,?',array(1=>$search,2=>$pg_start,3=>$limit));
    }else{
        $resultreq = $db->exec('SELECT l.date, u.user, l.uid, l.ip, l.action, l.result, l.cid  FROM '.$f3->get('logs_table').' AS l LEFT JOIN '.$f3->get('user_table').' AS u ON (u.id = l.uid) ORDER BY l.date DESC LIMIT ?,?',array(1=>$pg_start,2=>$limit));
    }
}

//Pass vars to template
$f3->set('loginfo',$resultreq);
$f3->set('type1',$type1);
$f3->set('type2',$type2);
$f3->set('type3',$type3);
?>