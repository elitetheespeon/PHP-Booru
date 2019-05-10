<?php
class favorites{
	function __construct()
	{
	
	}
	
	function fav_view(){
	    global $f3,$db;
	    $logger = new logger();
		$post = new post();
		$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'FAVORITES_VIEW', $f3->get('PARAMS.id'));
		//Number of images/page
		$limit = 50;

	    //Level check for DNP
		if ($f3->get('checked_user_group') >= $f3->get('dnp_level')){
            $dnp = "";
        }else{
            $dnp = "AND dnp=0 AND status = 'active'";
        }
        
        //Save userid
        $id = $f3->get('PARAMS.id');
        
		//Get the total number of favorites
		$result = $db->exec('SELECT COUNT(f.id) as count FROM '.$f3->get('favorites_table').' AS f LEFT JOIN '.$f3->get('post_table').' AS p ON (f.post_id = p.id) WHERE user_id = ? '.$dnp,array(1=>$id));
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
        
        //Make sure page is not higher than max rows
        if ($pg_start > $numrows || $pg_start < 0){
            $f3->reroute('/favorites/view/'.$id);
        }
        
        //Store numrows for template
        $f3->set('numrows',$numrows);        
        
        //Check for results
		if($numrows !== 0){
            //Get favorites info
            $favres = $db->exec('SELECT t2.id, t2.hash, t2.tags, t2.owner, t2.score, t2.rating,t2.ext FROM '.$f3->get('favorites_table').' as t1 JOIN '.$f3->get('post_table').' AS t2 ON t2.id=t1.post_id WHERE t1.user_id = ? '.$dnp.' ORDER BY added DESC LIMIT ?,?',array(1=>$id,2=>$pg_start,3=>$limit));
            
            //Start loop through favorites and add stuff
            foreach($favres as $key=>$f){
        		//Get the thumbnail image
        	    $favres[$key]["imagestr"] = $post->get_thumbnail($f['ext'],$f['hash']);
                //Convert tags and user for js
                $favres[$key]["tagsjs"] = str_replace('\\',"&#92;",str_replace(' ','%20',str_replace("'","&#039;",$f['tags'])));
                $favres[$key]["userjs"] = str_replace('\\',"&#92;",str_replace(' ','%20',str_replace("'","&#039;",$f['owner'])));
            }
        }
        
        //Store favorites for template
        $f3->set('favorites',$favres);
        
        //Render template
        $f3->set('pagename','favorites_view');
    	$template=new Template;
        echo $template->render('favorites_view.html');
	}
	
	function fav_list(){
	    global $f3,$db;
	    $logger = new logger();
		$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'FAVORITES_LIST', $f3->get('PARAMS.page'));
		//Number of users/page
		$limit = 50;	    

		//Get the total number of users with favorites
		$result = $db->exec('SELECT COUNT(user_id) as count FROM '.$f3->get('favorites_count_table').' ORDER BY user_id');
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
        
        //Make sure page is not higher than max rows
        if ($pg_start > $numrows || $pg_start < 0){
            $f3->reroute('/favorites/list/');
        }
        
        //Store numrows for template
        $f3->set('numrows',$numrows);

        //Check for results
		if($numrows !== 0){		
    		//Get user favorite info
    		$userfavres = $db->exec('SELECT t2.user, t1.user_id, t1.fcount FROM '.$f3->get('favorites_count_table').' AS t1 JOIN '.$f3->get('user_table').' AS t2 ON t2.id=t1.user_id ORDER BY t2.user ASC LIMIT ?,?',array(1=>$pg_start,2=>$limit));
		}
		
		//Store favorite info for template
        $f3->set('userfavinfo',$userfavres);
        
        //Render template
        $f3->set('pagename','favorites_list');
    	$template=new Template;
        echo $template->render('favorites_list.html');
    }
    
    function fav_delete(){
	    global $f3,$db;
        //Load required classes
        $user = new user();
        $logger = new logger();
        
		//Store vars
		$page = $f3->get('PARAMS.page');
		$id = $f3->get('PARAMS.id');
		$user_id = $f3->get('checked_user_id');
		
        //Check if user is logged in
        if(!$user->check_log()){
            $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'FAVORITES_DELETE', 'NOT_LOGGED_IN');
        	$template=new Template;
            echo $template->render('no_permission.html');
        	exit();
        }
		
		//Get favorite count for current user
		$result = $db->exec('SELECT fcount FROM '.$f3->get('favorites_count_table').' WHERE user_id = ?',array(1=>$user_id));
		$count = $result[0]['fcount'];
		
		//Check if we got results back and it is positive
		if($count > 0){
			//Delete favorite from favorites table
			$delete = $db->exec('DELETE FROM '.$f3->get('favorites_table').' WHERE user_id = ? AND post_id = ?',array(1=>$user_id,2=>$id));
			//Update favorites count for user
			$update = $db->exec('UPDATE '.$f3->get('favorites_count_table').' SET fcount = fcount - 1 WHERE user_id = ?',array(1=>$user_id));
            //Log action
        	$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'FAVORITES_DELETE', 'SUCCESS', $id);
		}
		//Back to the page we came from
		$f3->reroute('/favorites/view/'.$user_id.'/'.$page);
    }
    function fav_add(){
    	global $f3,$db;
    	//Load required classes
    	$user = new user();
    	$logger = new logger();
    	
        //Check if user is banned
        if($user->banned_ip($_SERVER['REMOTE_ADDR'])){
            $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'FAVORITES_ADD', 'BANNED');
        	exit();
        }
        
        //Check if user is logged in
        if(!$user->check_log()){
            $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'FAVORITES_ADD', 'NOT_LOGGED_IN');
        	echo "2";
        	exit();
        }    	
    	
    	//Check if id is valid
    	if(is_numeric($f3->get('PARAMS.id'))){
			//Store id for query
			$id = $f3->get('PARAMS.id');
			//Get count to see if favorite exists
			$result = $db->exec('SELECT COUNT(*) as count FROM '.$f3->get('favorites_table').' WHERE user_id = ? AND post_id = ?',array(1=>$f3->get('checked_user_id'),2=>$id));
            $numrows = $result[0]["count"];
			//Check if post already is favorited
			if($numrows < 1){
                //Add favorite to favorite table
                $insert1 = $db->exec('INSERT INTO '.$f3->get('favorites_table').' (user_id, post_id) VALUES(?, ?)',array(1=>$f3->get('checked_user_id'),2=>$id));
				//Check if query was successful
				if($insert1){
					//Check if user has a favorite count
					$favcount = $db->exec('SELECT COUNT(*) as count FROM '.$f3->get('favorites_count_table').' WHERE user_id = ?',array(1=>$f3->get('checked_user_id')));
					if($favcount[0]["count"] < 1){
						//Insert favorite count for user
						$insert2 = $db->exec('INSERT INTO '.$f3->get('favorites_count_table').' (user_id, fcount) VALUES(?, \'1\')',array(1=>$f3->get('checked_user_id')));
					}else{
						//Update favorite count for user
						$update = $db->exec('UPDATE '.$f3->get('favorites_count_table').' SET fcount = fcount + 1 WHERE user_id = ?',array(1=>$f3->get('checked_user_id')));
					}
					//Success
					$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'FAVORITES_ADD', 'SUCCESS');
					echo "3";
				}
			}else{
				//Favorite already exists
				$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'FAVORITES_ADD', 'FAV_EXISTS');
				echo "1";
			}
    	}
    }
}
?>