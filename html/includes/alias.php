<?php
//Number of aliases/page
$limit = 40;

//Load required classes
$user = new user();
$misc = new misc();
$logger = new logger();

//Check for sent alias information and process
if(isset($_POST['tag']) && $_POST['tag'] != "" && isset($_POST['alias']) && $_POST['alias'] != ""){
	//Format posted data
	$tag = str_replace(" ","_",$misc->mb_trim(htmlentities($_POST['tag'], ENT_QUOTES, 'UTF-8', FALSE)));
	$alias = str_replace(" ","_",$misc->mb_trim(htmlentities($_POST['alias'], ENT_QUOTES, 'UTF-8', FALSE)));
	//See if alias exists already
	$aliascheck = $db->exec('SELECT COUNT(*) as count FROM '.$f3->get('alias_table').' WHERE tag = ? AND alias = ?',array(1=>$tag,2=>$alias));
	//Check what we got back
	if($aliascheck[0]["count"] > 0){
		//Alias already exists
		$message = "Tag/alias combination has already been requested.";
	}else{
		//Add alias
		$insert = $db->exec('INSERT INTO '.$f3->get('alias_table').' (tag, alias, status) VALUES(?, ?, \'pending\')',array(1=>$tag,2=>$alias));
		$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'ALIAS_ADD', 'SUCCESS', $db->lastInsertId());
		$message = "Tag/alias combination has been requested successfully.";
	}
}

//Get total number of aliases
$result = $db->exec('SELECT COUNT(*) as count FROM '.$f3->get('alias_table').' WHERE status !=\'rejected\'');
$numrows = $result[0]["count"];

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
    $f3->reroute('/alias');
}

//Get alias info
$aliasresult = $db->exec('SELECT * FROM '.$f3->get('alias_table').' WHERE status != \'rejected\' ORDER BY alias ASC LIMIT ?,?',array(1=>$pg_start,2=>$limit));

//Loop through aliases
foreach($aliasresult as $key => $a){
	//Add statuscss
	if($a['status'] == "pending"){
		$aliasresult[$key]["statuscss"] = "pending-tag";
	}

}

//Store alias info for template
$f3->set('alias',$aliasresult);
$f3->set('message',$message);