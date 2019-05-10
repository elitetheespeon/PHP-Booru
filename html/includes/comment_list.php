<?php
$logger = new logger();
$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'COMMENT_LIST', $f3->get('PARAMS.page'));

//Number of comments/page
$limit = 15;

//Load required classes
$user = new user();
$misc = new misc();
$tag = new tag();
$comment = new comment();
$post = new post();
$ip = $_SERVER['REMOTE_ADDR'];

//Level check for DNP
if ($f3->get('checked_user_group') >= $f3->get('dnp_level')){
    $dnp = "";
}else{
    $dnp = "AND dnp=0 AND status = 'active'";
}

//Get the total number of comments
$result = $db->exec('SELECT count(t1.id) as count FROM '.$f3->get('comment_table').' AS t1 JOIN '.$f3->get('post_table').' AS t2 ON t2.id=t1.post_id JOIN '.$f3->get('user_table').' AS t3 ON t3.id=t2.owner JOIN '.$f3->get('user_table').' AS t4 ON t4.id=t1.user '.$dnp);
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
    $f3->reroute('/comment/list/');
}

$resultcount = 0;
$commentresult = array();

$img = '';
$ccount = 0;
$ptcount = 0;
$lastpid = '';
$tcount = 0;

//Get comment info
$commentres = $db->exec('SELECT t1.id, t1.comment, t1.user, t1.posted_at, t1.score, t1.post_id, t1.spam, t2.hash, t2.tags, t2.rating, t2.score as p_score, t2.owner, t2.creation_date, t2.ext, t2.dnp, t3.user as pname, t4.user as cname FROM '.$f3->get('comment_table').' AS t1 JOIN '.$f3->get('post_table').' AS t2 ON t2.id=t1.post_id JOIN '.$f3->get('user_table').' AS t3 ON t3.id=t2.owner JOIN '.$f3->get('user_table').' AS t4 ON t4.id=t1.user '.$dnp.' ORDER BY t2.last_comment DESC,t1.id DESC LIMIT ?,?',array(1=>$pg_start,2=>$limit));

//Start loop through comments
foreach($commentres as $r){
	//Check if hash matches last in previous loop
	if($img !== $r['hash']){
    	//First run through (new) post
    	$commentresult[$r['hash']]['comment'][$resultcount]['id'] = $r['id'];
    	$commentresult[$r['hash']]['comment'][$resultcount]['comment'] = $misc->swap_bbs_tags($misc->short_url($misc->linebreaks($r['comment'])));
    	$commentresult[$r['hash']]['comment'][$resultcount]['user'] = $r['user'];
    	$commentresult[$r['hash']]['comment'][$resultcount]['userjs'] = str_replace('\\',"&#92;",str_replace(' ','%20',str_replace("'","&#039;",$r['user'])));
    	$commentresult[$r['hash']]['comment'][$resultcount]['cname'] = $r['cname'];
    	$commentresult[$r['hash']]['comment'][$resultcount]['posted_at'] = $r['posted_at'];
    	$commentresult[$r['hash']]['comment'][$resultcount]['spam'] = $r['spam'];
    	$commentresult[$r['hash']]['comment'][$resultcount]['score'] = $r['score'];
        $commentresult[$r['hash']]['post_id'] = $r['post_id'];
		$commentresult[$r['hash']]['pat'] = $r['creation_date'];
		$commentresult[$r['hash']]['rating'] = $r['rating'];
		$commentresult[$r['hash']]['score'] = $r['p_score'];
		$commentresult[$r['hash']]['user'] = $r['pname'];
		$commentresult[$r['hash']]['tags'] = $misc->mb_trim($r['tags']);
		$commentresult[$r['hash']]['tagsjs'] = str_replace('\\',"&#92;",str_replace("'","&#039;",$r['tags']));
	}else{
		//Subsequent runs through the (same) post
    	$commentresult[$r['hash']]['comment'][$resultcount]['id'] = $r['id'];
    	$commentresult[$r['hash']]['comment'][$resultcount]['comment'] = $misc->swap_bbs_tags($misc->short_url($misc->linebreaks($r['comment'])));
    	$commentresult[$r['hash']]['comment'][$resultcount]['user'] = $r['user'];
    	$commentresult[$r['hash']]['comment'][$resultcount]['userjs'] = str_replace('\\',"&#92;",str_replace(' ','%20',str_replace("'","&#039;",$r['user'])));
    	$commentresult[$r['hash']]['comment'][$resultcount]['cname'] = $r['cname'];
    	$commentresult[$r['hash']]['comment'][$resultcount]['posted_at'] = $r['posted_at'];
    	$commentresult[$r['hash']]['comment'][$resultcount]['spam'] = $r['spam'];
    	$commentresult[$r['hash']]['comment'][$resultcount]['score'] = $r['score'];
	}

	//Make sure this is not the first post listed and insert javascript vars
	if($img !== ""){
		$commentresult[$r['hash']]['lastpid'] = $lastpid;
		$commentresult[$r['hash']]['ptcount'] = $ptcount;
	}

	//Reset posts.totalcount var
	$ptcount = 0;

	//Get the thumbnail image
    $commentresult[$r['hash']]['imagestr'] = $post->get_thumbnail($r['ext'],$r['hash']);

	//Set img var to the current hash
	$img = $r['hash'];

	//Format tag information
    $ttags = explode(" ",$r['tags']);
	$commentresult[$r['hash']]['tcount'] = $tcount;
	$ttcount = 0;
	foreach($ttags as $current){
		if($ttcount < 15){
		    $result = $db->exec('SELECT id,tag_type FROM '.$f3->get('tags_table').' WHERE name = ? LIMIT 1',array(1=>$current));
			$tagcolor = $tag->tag_css_class($result[0]["tag_type"]);
			$commentresult[$r['hash']]['taglist'][$ttcount]['color'] = $tagcolor;
			$commentresult[$r['hash']]['taglist'][$ttcount]['name'] = $current;
			++$ttcount;
		}
	}
	
	//Set lastpid var to the current post id
	$lastpid = $row['post_id'];
   
    //Increment loop
	$ccount++;
	$ptcount++;
	$tcount++;
    $resultcount++;
}

//Store comments for template
$f3->set('comments',$commentresult);
?>