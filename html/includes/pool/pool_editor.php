<?php
$logger = new logger();
$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'POOL_EDITOR', $f3->get('PARAMS.id'));

//Number of images/page
$limit = 300;

//Load required classes
$tag = new tag();
$misc = new misc();
$post = new post();

//Check pool ID and set
if($f3->get('PARAMS.id') !== "" && is_numeric($f3->get('PARAMS.id')) && $f3->get('PARAMS.id') >= 0){
	$poolid = $f3->get('PARAMS.id');
}else{
	//Redirect to main listing
    $f3->reroute('/pool/list');
}

//Level check for DNP
if ($f3->get('checked_user_group') >= $f3->get('dnp_level')){
    $dnp = "";
}else{
    $dnp = "AND pi.dnp=0 AND pi.status = 'active'";
}

//Query for pool information
$resultpool = $db->exec('SELECT id, name, description FROM '.$f3->get('pool_table').' WHERE id = ?',array(1=>$poolid));

//Query for total number of posts
$resultpoolcount = $db->exec('SELECT COUNT(id) as count FROM '.$f3->get('pool_post_table').' WHERE pool_id = ?',array(1=>$poolid));
$numrows = $resultpoolcount[0]["count"];

//Pagination
$pages = new Pagination($numrows, $limit);
$pages->setTemplate('pagination.html');
$f3->set('pagebrowser', $pages->serve());
if ($f3->get('PARAMS.page') == ""){
	$f3->reroute('/pool/editor/'.$poolid.'/1');
}

//Check if pool is valid
$isvalid = $post->validate_pool($poolid);
$f3->set('isvalid',$isvalid);

//Check if our search returned no images.
if($numrows == 0){
	//No results found.
}else{
    //Results were found.
	$postresult = array();
	$tagresult = array();
	$gtags = array();
	$images = '';
	$tcount = 0;
	$resultcount = 0;
	$pg_start = $limit*($f3->get('PARAMS.page')-1);
	$pg_curr = $pg_start;
	$pg_end = $pg_start+$limit;
	if ($pg_end > $numrows){
		$pg_end = $numrows;
	}

	//Make sure page is not higher than max rows
    if ($pg_start > $numrows || $pg_start < 0){
        $f3->reroute('/pool/editor/'.$f3->get('PARAMS.id'));
	}

    //Query for pool post information
    $resultpost = $db->exec('SELECT pi.id, pi.hash, pi.ext, pi.score, pi.rating, pi.tags, pi.owner, pi.status, pi.dnp FROM '.$f3->get('post_table').' AS pi LEFT JOIN '.$f3->get('pool_post_table').' AS pp ON (pp.post_id = pi.id) WHERE pp.pool_id = ? '.$dnp.' ORDER BY pp.sequence ASC LIMIT ?,?',array(1=>$poolid,2=>$pg_start,3=>$limit));

	//Start loop through image results.
	foreach($resultpost as $r){
		//Format related tags and limit main tag listing to 40.
	    $tags = $misc->mb_trim($r['tags']);
		if($tcount <= 30){	
			$ttags = explode(" ",$tags);
			foreach($ttags as $current){
				if($current != "" && $current != " "){
					$gtags[$current] = $current;
					$tcount++;
				}
			}
		}
		
		//Get the thumbnail image
	    $imagestr = $post->get_thumbnail($r['ext'],$r['hash']);
	    
	    //Check to get the status of image
	    if($r['status'] == "deleted"){
			$imageclass = "deleted";
		}elseif($r['status'] == "pending"){
			$imageclass = "pending";
		}elseif($r['dnp'] == 1){
	        $imageclass = "dnp";
	    }else{
		    $imageclass = "";
		}
	    
	    //Store all post vars
	    $postresult[$resultcount]['id'] = $r['id'];
	    $postresult[$resultcount]['imageclass'] = $imageclass;
	    $postresult[$resultcount]['imagestr'] = $imagestr;
	    $postresult[$resultcount]['tags'] = $r['tags'];
	    $postresult[$resultcount]['score'] = $r['score'];
	    $postresult[$resultcount]['rating'] = $r['rating'];
	    $postresult[$resultcount]['owner'] = $r['owner'];
	    
	    $tcount++;
	    $resultcount++;
	}

	//Store posts for template
	$f3->set('posts',$postresult);
	
	//Store pool vars for template
	$f3->set('poolinfo',$resultpool);
	$f3->set('limit',$limit);
	$f3->set('page',$pg_start);
	$f3->set('poolid',$poolid);
}