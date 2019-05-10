<?php
$logger = new logger();
$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'POST_ALL');

//Number of images/page
$limit = 30;

//Load required classes
$tag = new tag();
$misc = new misc();
$search = new search();
$post = new post();

//Level check for DNP
if ($f3->get('checked_user_group') >= $f3->get('dnp_level')){
    $dnp = "";
}else{
    if ($f3->get('checked_user_id') !== null){
    	$dnp = "WHERE (dnp=0 AND status = 'active') OR (owner = ".$f3->get('checked_user_id').')';
    }else{
    	$dnp = "WHERE dnp=0 AND status = 'active'";
    }
}

//Run the query for posts                
$query = "SELECT count(id) as count FROM ".$f3->get('post_table')." $dnp ORDER BY id DESC";
$result = $db->exec($query);
$numrows = $result[0]["count"];
$tags = $new_tag_cache;

$pages = new Pagination($numrows, $limit);
$pages->setTemplate('pagination.html');
$f3->set('pagebrowser', $pages->serve());
if ($f3->get('PARAMS.page') == ""){
	$f3->set('PARAMS.page',1);
}

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
        $f3->reroute('/post/all/');
	}
	
	//Query for our selected data               
	$query = "SELECT id, creation_date, hash, score, rating, tags, owner, ext, status, dnp FROM ".$f3->get('post_table')." $dnp ORDER BY id DESC LIMIT $pg_start, $limit";
	$result = $db->exec($query);
	
	//Start loop through image results.
	foreach($result as $r){
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
	unset($result);
	
	//Clean up tags and sort them in ascending order.
	$ttags = "";
	asort($gtags);
	//Grab the index count and tag type from database.
	$taginc = 0;
	foreach($gtags as $current){
        $result = $db->exec('SELECT id,tag_type,post_count FROM '.$f3->get('tags_table').' WHERE name=? LIMIT 1',array(1=>$current));
		$tagcolor = $tag->tag_css_class($result[0]["tag_type"]);
		//$t_decode = urlencode(html_entity_decode($ttags,ENT_NOQUOTES,"UTF-8"));
		//$c_decode = urlencode(html_entity_decode($current,ENT_NOQUOTES,"UTF-8"));
		
	    //Store all tag vars
	    $tagresult[$taginc]['color'] = $tagcolor;
	    $tagresult[$taginc]['name'] = $current;
	    $tagresult[$taginc]['count'] = $result[0]["post_count"];
	    $taginc++;
    }
	//Store tags for template
	$f3->set('tags',$tagresult);
}
?>