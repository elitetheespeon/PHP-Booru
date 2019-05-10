<?php
class wiki{

	var $post;
	var $tag;
	var $user;
	var $logger;
	var $search;

	function __construct(){
		$this->post = new post();
		$this->user = new user();
		$this->logger = new logger();
		$this->search = new search();
		$this->tag = new tag();
	}

	function list_all(){
		global $f3, $db;
        //Number of wiki articles/page
        $limit = 40;
	    //Log page hit
	    $this->logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'WIKI_LIST', $f3->get('PARAMS.page'));
	    //Get total number of wiki articles
	    $wikicount = $db->exec('SELECT COUNT(id) as count FROM '.$f3->get('wiki_table'),array(1=>$postnum,2=>$poolid,3=>$postid));
	    $numrows = $wikicount[0]["count"];
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
                $f3->reroute('/wiki/list/'.$f3->get('PARAMS.id'));
        	}
        	//Query for wiki article information
        	$resultwiki = $db->exec('SELECT id, created_at, updated_at, title, user_id FROM '.$f3->get('wiki_table').' ORDER BY title ASC LIMIT ?,?',array(1=>$pg_start,2=>$limit));
        }

		//Get recently edited
		$recent = $this->get_recent_updated();

        //Pass vars to template
        $f3->set('wikiinfo',$resultwiki);
        $f3->set('userc',$this->user);
		$f3->set('recent',$recent);
		//Process template
		$f3->set('pagename','wiki_list');
		$template=new Template;
    	echo $template->render('wiki_list.html');
	}

	function get_recent_updated(){
		global $f3, $db;
		//Get last 20 updated wiki articles
		$recent = $db->exec('SELECT DISTINCT w.title, w.id, t.tag_type FROM (SELECT title, id, updated_at FROM '.$f3->get('wiki_table').' ORDER BY updated_at DESC LIMIT 20) as w LEFT JOIN '.$f3->get('tags_table').' as t ON (w.title = t.name) GROUP BY w.title ORDER BY w.updated_at DESC');
		//Add color for category in
		foreach($recent as $key => $tag){
		    $recent[$key]['color'] = $this->tag->tag_css_class($tag['tag_type']);
		}
		if(count($recent !== 0)){
			return $recent;
		}else{
			return false;
		}
	}

	function save(){
		global $f3, $db;
		$ip = $_SERVER['REMOTE_ADDR'];
		
		//Check if user is banned/not logged in
		if(!$this->user->validate_user() || !$this->user->gotpermission('edit_wiki')){
		    $this->logger->log_action($f3->get('checked_user_id'), $ip, 'EDIT_WIKI', 'NO_ACCESS');
	        echo "ERROR: NO ACCESS TO EDIT WIKI";
	        exit();
		}

		//Check for valid POST data
		if(!$f3->get('POST.editor') || $f3->get('POST.editor') == ''){
			$this->logger->log_action($f3->get('checked_user_id'), $ip, 'EDIT_WIKI', 'INVALID_DATA');
	        echo "ERROR: INVALID OR EMPTY DATA";
	        exit();
		}
		
		//Store wiki id
		$id = $f3->get('PARAMS.id');
		//Check if id was passed and is valid
		if ($f3->get('PARAMS.id') !== "" && is_numeric($f3->get('PARAMS.id')) && $f3->get('PARAMS.id') >= 0){
			//An id was passed, check if it exists
	    	$resultwiki = $db->exec('SELECT id, created_at, updated_at, user_id, body, title, version, is_locked FROM '.$f3->get('wiki_table').' WHERE id = ?',array(1=>$id));
			//Check if we got results back, if not send the user to the main wiki list.
			if(count($resultwiki) == 0){
		        $this->logger->log_action($f3->get('checked_user_id'), $ip, 'EDIT_WIKI', 'INVALID_ID');
		        echo "ERROR: INVALID WIKI PAGE";
		        exit();
			}

			//Check if wiki is locked
			if($resultwiki[0]['is_locked'] == 1 && !$this->user->gotpermission('lock_wiki')){
			    $this->logger->log_action($f3->get('checked_user_id'), $ip, 'EDIT_WIKI', 'LOCKED');
		        echo "ERROR: WIKI IS LOCKED";
		        exit();
			}

			//Check if title was sent
			if($f3->get('POST.title') != ''){
				//Check if user has access to change title
				if(!$this->user->gotpermission('change_wiki_title')){
			        //User doesn't have access to change title
			        $this->logger->log_action($f3->get('checked_user_id'), $ip, 'EDIT_WIKI', 'NO_ACCESS_TITLE_EDIT');
					//Don't change title
					$newtitle = $resultwiki[0]['title'];
				}else{
					//User is mod, check if title has changed
					if($f3->get('POST.title') != $resultwiki[0]['title']){
						//Title has changed, set new title
						$newtitle = $f3->get('POST.title');
					}else{
						//Don't change title
						$newtitle = $resultwiki[0]['title'];
					}					
				}
			}else{
				//Don't change title
				$newtitle = $resultwiki[0]['title'];
			}

			//Update wiki info
			$version = $db->exec('INSERT INTO '.$f3->get('wiki_version_table').' (created_at, updated_at, version, title, body, user_id, ip_addr, wiki_page_id, is_locked) VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, ?)',array(1=>$resultwiki[0]['created_at'],2=>($resultwiki[0]['version']+1),3=>$newtitle,4=>$f3->get('POST.editor'),5=>$f3->get('checked_user_id'),6=>$ip,7=>$resultwiki[0]['id'],8=>$resultwiki[0]['is_locked']));
		    $update = $db->exec('UPDATE '.$f3->get('wiki_table').' SET updated_at = NOW(), title = ? , version = ?, body = ?, user_id = ?, ip_addr = ? WHERE id = ?',array(1=>$newtitle,2=>($resultwiki[0]['version']+1),3=>$f3->get('POST.editor'),4=>$f3->get('checked_user_id'),5=>$ip,6=>$resultwiki[0]['id']));
		    $this->logger->log_action($f3->get('checked_user_id'), $ip, 'EDIT_WIKI', 'SUCCESS', $resultwiki[0]['id']);
		    
		    //Send back ok code
		    echo "OK";
		    exit();
		}else{
			//No id passed, query for title of page
			$resultwiki = $db->exec('SELECT id, title FROM '.$f3->get('wiki_table').' WHERE title = ?',array(1=>$f3->get('POST.title')));
			
			//Check if page title exists already
			if(count($resultwiki) !== 0){
		        $this->logger->log_action($f3->get('checked_user_id'), $ip, 'EDIT_WIKI', 'TITLE_EXITS');
		        echo "ERROR: WIKI TITLE ALREADY EXISTS, PLEASE CHOOSE A DIFFERENT TITLE";
		        exit();
			}
			
			//Create new wiki page
			$insert = $db->exec('INSERT INTO '.$f3->get('wiki_table').' (created_at, updated_at, version, title, body, user_id, ip_addr, is_locked) VALUES (NOW(), NOW(), ?, ?, ?, ?, ?, ?)',array(1=>1,2=>$f3->get('POST.title'),3=>$f3->get('POST.editor'),4=>$f3->get('checked_user_id'),5=>$ip,6=>0));
			$wiki_id = $db->lastInsertId();
			$version = $db->exec('INSERT INTO '.$f3->get('wiki_version_table').' (created_at, updated_at, version, title, body, user_id, ip_addr, wiki_page_id, is_locked) VALUES (NOW(), NOW(), ?, ?, ?, ?, ?, ?, ?)',array(1=>1,2=>$f3->get('POST.title'),3=>$f3->get('POST.editor'),4=>$f3->get('checked_user_id'),5=>$ip,6=>$wiki_id,7=>0));
			
			//Send new wiki page
			echo $wiki_id;
		}
	}

	function page_not_found($title){
		global $f3, $db;
		//Get recently edited
		$recent = $this->get_recent_updated();
		
		//Store info for template
		$f3->set('recent',$recent);
		$f3->set('title',$title);
		
		//Process template
		$f3->set('pagename','wiki_not_found');
		$template=new Template;
    	echo $template->render('wiki_not_found.html');	
    	exit();
	}

	function view_title(){
		global $f3, $db;
	    //Log page hit
	    $this->logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'WIKI_VIEW', $f3->get('PARAMS.title'));
    	//Save title from page
    	$id = $f3->get('PARAMS.title');
		//Query for wiki article information at latest version number
		$resultwiki = $db->exec('SELECT id, created_at, updated_at, title, user_id, body, title, version, is_locked FROM '.$f3->get('wiki_table').' WHERE title = ?',array(1=>$id));
		//Check if id is valid, if not send the user to the main wiki list.
		if(count($resultwiki) == 0){
			$this->page_not_found($id);
		}
		//Level check for DNP
		if ($f3->get('checked_user_group') >= $f3->get('dnp_level')){
		    $dnp = "";
		}else{
		    $dnp = "AND dnp=0 AND status = 'active'";
		}

        //Get last 5 uploaded posts for tag
		$resultupinfo = $db->exec('SELECT p.id, hash, tags, owner, rating, score, ext, dnp, status FROM '.$f3->get('poststags_table').' as pt JOIN '.$f3->get('post_table').' as p ON (p.id = pt.post_id) WHERE pt.tag_id = (SELECT id FROM '.$f3->get('tags_table').' WHERE name = ? LIMIT 1) '.$dnp.' ORDER BY p.id DESC LIMIT 9',array(1=>$resultwiki[0]["title"]));

		//Get count of posts
		$resultcount = $db->exec('SELECT count(p.id) as count FROM '.$f3->get('poststags_table').' as pt JOIN '.$f3->get('post_table').' as p ON (p.id = pt.post_id) WHERE pt.tag_id = (SELECT id FROM '.$f3->get('tags_table').' WHERE name = ? LIMIT 1) '.$dnp.'',array(1=>$resultwiki[0]["title"]));
        
        //Make sure we got posts back
        if ($resultcount[0]['count'] !== 0){
	        //Add data to posts
	        foreach($resultupinfo as $key => $row){
				//Get the thumbnail image
			    $resultupinfo[$key]["imagestr"] = $this->post->get_thumbnail($row['ext'],$row['hash']);
	        	//Clean for javascript
	        	$resultupinfo[$key]["ownerjs"] = mb_strtolower(str_replace('\\',"&#92;",str_replace(' ','%20',str_replace("'","&#039;",$row['owner']))),'UTF-8');
	        	$resultupinfo[$key]["ratingjs"] = mb_strtolower($row['rating'],'UTF-8');
	        	$resultupinfo[$key]["tagsjs"] = mb_strtolower(str_replace('\\',"&#92;",str_replace("'","&#039;",substr($row['tags'],1,strlen($row['tags'])-2))),'UTF-8');
	        }
        }
		//Get recently edited
		$recent = $this->get_recent_updated();

    	//Get locked status
    	if($resultwiki[0]["is_locked"] == 0){
    		$islocked = false;
    	}else{
    		$islocked = true;
    	}
		//Get permissions
		if($this->user->gotpermission('edit_wiki')){
			$f3->set('canedit',true);
		}
		if($this->user->gotpermission('reverse_wiki')){
			$f3->set('canrevert',true);
		}
		if($this->user->gotpermission('delete_wiki')){
			$f3->set('candelete',true);
		}
		if($this->user->gotpermission('lock_wiki')){
			$f3->set('canlock',true);
		}

		//Store info for template
		if ($resultwiki[0]["body"] !== ""){
			$body = Markdown::instance()->convert($f3->clean($resultwiki[0]["body"]));	
		}else{
			$body = false;
		}
		$f3->set('PARAMS.id',$resultwiki[0]['id']);
		$f3->set('wikibody',$body);
		$f3->set('wikiinfo',$resultwiki);
		$f3->set('islocked',$islocked);
		$f3->set('user',$this->user);
		$f3->set('posts',$resultupinfo);
		$f3->set('title',$resultwiki[0]["title"]);
		$f3->set('term_name',$resultwiki[0]["title"]);
		$f3->set('post_count',$resultcount[0]['count']);
		$f3->set('recent',$recent);
		//Process template
		$f3->set('pagename','wiki_view');
		$template=new Template;
    	echo $template->render('wiki_view.html');
	}

	function view(){
		global $f3, $db;
	    //Log page hit
	    $this->logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'WIKI_VIEW', $f3->get('PARAMS.id'));
    	//Save id from page
    	$id = (int)$f3->get('PARAMS.id');
		//Check for version number
		if($f3->get('PARAMS.version') != null && $this->user->gotpermission('reverse_wiki')){
	    	//Query for wiki article information for specific version number
	    	$resultwiki = $db->exec('SELECT wiki_page_id as id, created_at, updated_at, title, user_id, body, title, version, is_locked FROM '.$f3->get('wiki_version_table').' WHERE wiki_page_id = ? AND version = ?',array(1=>$id,2=>(int)$f3->get('PARAMS.version')));			
		}else{
			//Query for wiki article information at latest version number
			$resultwiki = $db->exec('SELECT id, created_at, updated_at, title, user_id, body, title, version, is_locked FROM '.$f3->get('wiki_table').' WHERE id = ?',array(1=>$id));
		}
		//Check if id is valid, if not send the user to the main wiki list.
		if(count($resultwiki) == 0){
			$f3->reroute('/wiki/list');
		}
		//Level check for DNP
		if ($f3->get('checked_user_group') >= $f3->get('dnp_level')){
		    $dnp = "";
		}else{
		    $dnp = "AND dnp=0 AND status = 'active'";
		}

        //Get last 5 uploaded posts for tag
		$resultupinfo = $db->exec('SELECT p.id, hash, tags, owner, rating, score, ext, dnp, status FROM '.$f3->get('poststags_table').' as pt JOIN '.$f3->get('post_table').' as p ON (p.id = pt.post_id) WHERE pt.tag_id = (SELECT id FROM '.$f3->get('tags_table').' WHERE name = ?  LIMIT 1) '.$dnp.' ORDER BY p.id DESC LIMIT 9',array(1=>$resultwiki[0]["title"]));

		//Get count of posts
		$resultcount = $db->exec('SELECT count(p.id) as count FROM '.$f3->get('poststags_table').' as pt JOIN '.$f3->get('post_table').' as p ON (p.id = pt.post_id) WHERE pt.tag_id = (SELECT id FROM '.$f3->get('tags_table').' WHERE name = ?  LIMIT 1) '.$dnp.'',array(1=>$resultwiki[0]["title"]));
        
        //Make sure we got posts back
        if ($resultcount[0]['count'] !== 0){
	        //Add data to posts
	        foreach($resultupinfo as $key => $row){
				//Get the thumbnail image
			    $resultupinfo[$key]["imagestr"] = $this->post->get_thumbnail($row['ext'],$row['hash']);
	        	//Clean for javascript
	        	$resultupinfo[$key]["ownerjs"] = mb_strtolower(str_replace('\\',"&#92;",str_replace(' ','%20',str_replace("'","&#039;",$row['owner']))),'UTF-8');
	        	$resultupinfo[$key]["ratingjs"] = mb_strtolower($row['rating'],'UTF-8');
	        	$resultupinfo[$key]["tagsjs"] = mb_strtolower(str_replace('\\',"&#92;",str_replace("'","&#039;",substr($row['tags'],1,strlen($row['tags'])-2))),'UTF-8');
	        }
        }
		//Get recently edited
		$recent = $this->get_recent_updated();

    	//Get locked status
    	if($resultwiki[0]["is_locked"] == 0){
    		$islocked = false;
    	}else{
    		$islocked = true;
    	}
		//Get permissions
		if($this->user->gotpermission('edit_wiki')){
			$f3->set('canedit',true);
		}
		if($this->user->gotpermission('reverse_wiki')){
			$f3->set('canrevert',true);
		}
		if($this->user->gotpermission('delete_wiki')){
			$f3->set('candelete',true);
		}
		if($this->user->gotpermission('lock_wiki')){
			$f3->set('canlock',true);
		}

		//Store info for template
		if ($resultwiki[0]["body"] !== ""){
			$body = Markdown::instance()->convert($f3->clean($resultwiki[0]["body"]));	
		}else{
			$body = false;
		}
		$f3->set('wikibody',$body);
		$f3->set('wikiinfo',$resultwiki);
		$f3->set('islocked',$islocked);
		$f3->set('user',$this->user);
		$f3->set('posts',$resultupinfo);
		$f3->set('title',$resultwiki[0]["title"]);
		$f3->set('term_name',$resultwiki[0]["title"]);
		$f3->set('post_count',$resultcount[0]['count']);
		$f3->set('recent',$recent);
		//Process template
		$f3->set('pagename','wiki_view');
		$template=new Template;
    	echo $template->render('wiki_view.html');
	}

	function remove(){
		global $f3, $db;
		$ip = $_SERVER['REMOTE_ADDR'];

		//Check if user has access to delete
		if(!$this->user->gotpermission('delete_wiki')){
			$this->logger->log_action($f3->get('checked_user_id'), $ip, 'DELETE_WIKI', 'NO_ACCESS');
			$template=new Template;
			echo $template->render('no_permission.html');
			exit();
		}
		
    	//Save id from page
    	$id = (int)$f3->get('PARAMS.id');
    	//Query for ID
    	$resultwiki = $db->exec('SELECT id FROM '.$f3->get('wiki_table').' WHERE id = ?',array(1=>$id));
    	//Check for valid ID
    	if(count($resultwiki) !== 0){
    		//Delete wiki article ID
    		$deletewiki = $db->exec('DELETE FROM '.$f3->get('wiki_table').' WHERE id = ?',array(1=>$id));
    		//Delete wiki article history
    		$deletewikihist = $db->exec('DELETE FROM '.$f3->get('wiki_version_table').' WHERE wiki_page_id = ?',array(1=>$id));
    		//Log action
    		$this->logger->log_action($f3->get('checked_user_id'), $ip, 'DELETE_WIKI', 'SUCCESS', $id);
    		//Send back to wiki list
    		$f3->reroute('/wiki/list/');
    	}else{
    		//Invalid ID, redirect
    		$this->logger->log_action($f3->get('checked_user_id'), $ip, 'DELETE_WIKI', 'INVALID_ID', $id);
    		$f3->reroute('/wiki/list/');
    	}
	}
	
	function version_history(){
		global $f3, $db;
	    //Log page hit
	    $this->logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'WIKI_VERSION_HISTORY', $f3->get('PARAMS.id'));
    	//Save id from page
    	$id = (int)$f3->get('PARAMS.id');
    	//Query for wiki article information
    	$version_hist = $db->exec('SELECT created_at, updated_at, version, title, body, user_id, ip_addr, wiki_page_id, is_locked FROM '.$f3->get('wiki_version_table').' WHERE wiki_page_id = ?',array(1=>$id));
    	//Check for valid ID
    	if(count($version_hist) !== 0){
			//Get recently edited
			$recent = $this->get_recent_updated();

			//Store info for template
			$f3->set('user',$this->user);
			$f3->set('recent',$recent);
			$f3->set('wikihist',$version_hist);
			$f3->set('title',$version_hist[0]["title"]);
			//Process template
			$f3->set('pagename','wiki_history');
			$template=new Template;
	    	echo $template->render('wiki_history.html');
    	}else{
    		//Invalid ID, redirect
    		$f3->reroute('/wiki/list/');
    	}
	}

	function revert_history(){
		global $f3, $db;
		$ip = $_SERVER['REMOTE_ADDR'];

		//Check if user has access to revert
		if(!$this->user->gotpermission('reverse_wiki')){
			$this->logger->log_action($f3->get('checked_user_id'), $ip, 'REVERT_WIKI', 'NO_ACCESS');
			$template=new Template;
			echo $template->render('no_permission.html');
			exit();
		}
	    //Log page hit
	    $this->logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'WIKI_REVERT_HISTORY', $f3->get('PARAMS.id'));
    	//Save id from page
    	$id = (int)$f3->get('PARAMS.id');
    	//Query for wiki article information
    	$version_hist = $db->exec('SELECT created_at, updated_at, version, title, body, user_id, ip_addr, wiki_page_id, is_locked FROM '.$f3->get('wiki_version_table').' WHERE wiki_page_id = ?',array(1=>$id));
    	//Check for valid ID
    	if(count($version_hist) !== 0){
			//Get recently edited
			$recent = $this->get_recent_updated();

			//Store info for template
			$f3->set('count',count($version_hist));
			$f3->set('user',$this->user);
			$f3->set('recent',$recent);
			$f3->set('wikihist',$version_hist);
			$f3->set('title',$version_hist[0]["title"]);
			//Process template
			$f3->set('pagename','wiki_revert_history');
			$template=new Template;
	    	echo $template->render('wiki_revert_history.html');
    	}else{
    		//Invalid ID, redirect
    		$f3->reroute('/wiki/list/');
    	}
	}
	
	function revert(){
		global $f3, $db;
		$ip = $_SERVER['REMOTE_ADDR'];

		//Check if user has access to revert
		if(!$this->user->gotpermission('reverse_wiki')){
			$this->logger->log_action($f3->get('checked_user_id'), $ip, 'REVERT_WIKI', 'NO_ACCESS');
			$template=new Template;
			echo $template->render('no_permission.html');
			exit();
		}
		
    	//Save ids from page
    	$wiki_id = (int)$f3->get('PARAMS.id');
    	$version = (int)$f3->get('POST.version');

    	//Query for wiki article information
    	$wiki_info = $db->exec('SELECT created_at, version FROM '.$f3->get('wiki_table').' WHERE id = ?',array(1=>$wiki_id));

    	//Check for valid wiki ID
    	if(count($wiki_info) == 0){
    		//Invalid ID, redirect
    		$f3->reroute('/wiki/list/');
    	}

    	//Query for wiki history information
    	$wiki_history = $db->exec('SELECT title, body, user_id, ip_addr, is_locked, (SELECT count(id) FROM '.$f3->get('wiki_version_table').' WHERE wiki_page_id = ?) as count FROM '.$f3->get('wiki_version_table').' WHERE wiki_page_id = ? AND version = ?',array(1=>$wiki_id,2=>$wiki_id,3=>$version));
    	
		//Check for valid version number
    	if(count($wiki_history) == 0){
    		//Invalid ID, redirect
    		$f3->reroute('/wiki/list/');
    	}else{
			//Check version isn't higher or equal than total results
			if(($version-1) >= $wiki_history[0]['count']){
				//Version number out of range
				$f3->reroute('/wiki/list/');
			}

	    	//Save wiki info
	    	$current_version = $wiki_info[0]['version'];
	    	$created_at = $wiki_info[0]['created_at'];
	    	$title = $wiki_history[0]['title'];
	    	$body = $wiki_history[0]['body'];
	    	$user_id = $wiki_history[0]['user_id'];
	    	$ip_addr = $wiki_history[0]['ip_addr'];
	    	$is_locked = $wiki_history[0]['is_locked'];

	    	//Create new version history
	    	$version = $db->exec('INSERT INTO '.$f3->get('wiki_version_table').' (created_at, updated_at, version, title, body, user_id, ip_addr, wiki_page_id, is_locked) VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, ?)',array(1=>$created_at,2=>($current_version+1),3=>$title,4=>$body,5=>$f3->get('checked_user_id'),6=>$ip,7=>$wiki_id,8=>$is_locked));
	    	
	    	//Revert wiki post to version number
	    	$update_wiki = $db->exec('UPDATE '.$f3->get('wiki_table').' SET version = ?, title = ?, body = ?, user_id = ?, ip_addr = ?, is_locked = ? WHERE id = ?',array(1=>($current_version+1),2=>$title,3=>$body,4=>$f3->get('checked_user_id'),5=>$ip_addr,6=>$is_locked,7=>$wiki_id));
			
    		//Log action
    		$this->logger->log_action($f3->get('checked_user_id'), $ip, 'REVERT_WIKI', 'SUCCESS', $wiki_id);
    		
    		//Send back to wiki page
    		$f3->reroute('/wiki/view/'.$wiki_id);
    	}
	}

	function lock(){
		global $f3, $db;
		$ip = $_SERVER['REMOTE_ADDR'];

		//Check if user has access to revert
		if(!$this->user->gotpermission('lock_wiki')){
			$this->logger->log_action($f3->get('checked_user_id'), $ip, 'LOCK_WIKI', 'NO_ACCESS');
			$template=new Template;
			echo $template->render('no_permission.html');
			exit();
		}
		
    	//Save id from page
    	$wiki_id = (int)$f3->get('PARAMS.id');

		//Lock the wiki id
		$update_wiki = $db->exec('UPDATE '.$f3->get('wiki_table').' SET is_locked = ? WHERE id = ?',array(1=>1,2=>$wiki_id));
		
		//Log action
		$this->logger->log_action($f3->get('checked_user_id'), $ip, 'LOCK_WIKI', 'SUCCESS', $wiki_id);
		
		//Return to wiki page
		$f3->reroute('/wiki/view/'.$wiki_id);
	}

	function unlock(){
		global $f3, $db;
		$ip = $_SERVER['REMOTE_ADDR'];

		//Check if user has access to revert
		if(!$this->user->gotpermission('lock_wiki')){
			$this->logger->log_action($f3->get('checked_user_id'), $ip, 'UNLOCK_WIKI', 'NO_ACCESS');
			$template=new Template;
			echo $template->render('no_permission.html');
			exit();
		}
		
    	//Save id from page
    	$wiki_id = (int)$f3->get('PARAMS.id');

		//Unlock the wiki id
		$update_wiki = $db->exec('UPDATE '.$f3->get('wiki_table').' SET is_locked = ? WHERE id = ?',array(1=>0,2=>$wiki_id));
		
		//Log action
		$this->logger->log_action($f3->get('checked_user_id'), $ip, 'UNLOCK_WIKI', 'SUCCESS', $wiki_id);
		
		//Return to wiki page
		$f3->reroute('/wiki/view/'.$wiki_id);
	}

	function compare(){
		global $f3, $db;

    	//Save ids from page
    	$wiki_id = (int)$f3->get('PARAMS.id');
    	$before_id = (int)$f3->get('PARAMS.before_id');
     	$after_id = (int)$f3->get('PARAMS.after_id');	

    	//Query for wiki article information
    	$wiki_info = $db->exec('SELECT title, user_id FROM '.$f3->get('wiki_table').' WHERE id = ?',array(1=>$wiki_id));

    	//Check for valid wiki ID
    	if(count($wiki_info) == 0){
    		//Invalid ID, redirect
    		$f3->reroute('/wiki/list/');
    	}	

    	//Query for wiki history information using before and after id
    	$wiki_history = $db->exec('SELECT (SELECT body FROM '.$f3->get('wiki_version_table').' WHERE wiki_page_id = ? AND version = ?) as `before`, (SELECT body FROM '.$f3->get('wiki_version_table').' WHERE wiki_page_id = ? AND version = ?) as `after`;',array(1=>$wiki_id,2=>$before_id,3=>$wiki_id,4=>$after_id));
    	
		//Check for valid before/after wiki history data
    	if(count($wiki_history) == 0 || $wiki_history[0]['before'] == null || $wiki_history[0]['after'] == null){
    		//Invalid ID, redirect
    		$f3->reroute('/wiki/list/');
    	}else{
    		//Save before/after edit data
    		$wiki_before = $wiki_history[0]['before'];
    		$wiki_after = $wiki_history[0]['after'];
    	}
		
		//Format before and after version text
		$wiki_before = explode("\n", $wiki_before);
		$wiki_after = explode("\n", $wiki_after);
		
		//Get diff information
		try{
			$diff = new Diff($wiki_before, $wiki_after);
			$renderer = new Diff_Renderer_Html_SideBySide;
			$render = $diff->Render($renderer);
		}catch(Exception $e){
			//Bad data
			$renderer = null;
			$this->logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'WIKI_COMPARE', 'ERROR_DIFF', $before_id.'->'.$after_id);
		}

		//Check if diff info is valid
		if(!$render){
    		//Bad data, redirect
    		$f3->reroute('/wiki/list/');			
		}

		//Get recently edited
		$recent = $this->get_recent_updated();
		
		//Store info for template
		$f3->set('recent',$recent);
		$f3->set('title',$wiki_info[0]["title"]);
		$f3->set('before_id',$before_id);
		$f3->set('after_id',$after_id);
		$f3->set('diff_info',$render);

	    //Log page hit
	    $this->logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'WIKI_COMPARE', 'RENDERED', $before_id.'->'.$after_id);

		//Process template
		$f3->set('pagename','wiki_compare');
		$template=new Template;
    	echo $template->render('wiki_compare.html');
	}

	function compare_lookup(){
		global $f3, $db;
		
    	//Save id from page
    	$wiki_id = (int)$f3->get('PARAMS.id');		
		
		//Check for valid POST data
		if(!$f3->get('POST.from') || $f3->get('POST.from') == '' || !$f3->get('POST.to') || $f3->get('POST.to') == ''){
    		//Bad data, redirect
    		$f3->reroute('/wiki/list/');			
		}else{
			//Store to/from version ids
			$before = (int)$f3->get('POST.from');
			$after = (int)$f3->get('POST.to');
		}
		
		//Redirect to correct URL to compare
		$f3->reroute('/wiki/compare/'.$wiki_id.'/'.$before.'/'.$after);
	}
	
	function create(){
		global $f3, $db;
		//Check if user has access to create
		if(!$this->user->gotpermission('edit_wiki')){
			$this->logger->log_action($f3->get('checked_user_id'), $ip, 'CREATE_WIKI', 'NO_ACCESS');
			$template=new Template;
			echo $template->render('no_permission.html');
			exit();
		}

		//Check if title was passed
		if($f3->get('PARAMS.title') !== ''){
			//Set title
			$f3->set('title',$f3->get('PARAMS.title'));
		}

		//Get recently edited
		$recent = $this->get_recent_updated();
		
		//Store info for template
		$f3->set('recent',$recent);
		
		//Process template
		$f3->set('pagename','wiki_add');
		$template=new Template;
    	echo $template->render('wiki_add.html');		
	}
	
	function edit(){
		global $f3, $db;
		//Check if user has access to edit
		if(!$this->user->gotpermission('edit_wiki')){
			$this->logger->log_action($f3->get('checked_user_id'), $ip, 'EDIT_WIKI', 'NO_ACCESS');
			$template=new Template;
			echo $template->render('no_permission.html');
			exit();
		}
	    //Log page hit
	    $this->logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'WIKI_EDIT_PAGE', $f3->get('PARAMS.id'));
    	//Save id from page
    	$id = (int)$f3->get('PARAMS.id');
    	//Query for wiki article information
    	$resultwiki = $db->exec('SELECT id, created_at, updated_at, title, user_id, body, title, version, is_locked FROM '.$f3->get('wiki_table').' WHERE id = ?',array(1=>$id));
 		//Check if wiki is locked
		if($resultwiki[0]['is_locked'] == 1 && !$this->user->gotpermission('lock_wiki')){
		    $this->logger->log_action($f3->get('checked_user_id'), $ip, 'EDIT_WIKI', 'LOCKED');
			$template=new Template;
			echo $template->render('no_permission.html');
			exit();
		}
    	//Check for valid ID
    	if(count($resultwiki) !== 0){
	    	//Get locked status
	    	if($resultwiki[0]["is_locked"] == 0){
	    		$islocked = true;
	    	}else{
	    		$islocked = false;
	    	}
			//Store info for template
			if ($resultwiki[0]["body"] !== ""){
				$body = Markdown::instance()->convert($f3->clean($resultwiki[0]["body"]));	
			}else{
				$body = false;
			}
			$f3->set('wikibody',$body);
			$f3->set('wikiinfo',$resultwiki);
			$f3->set('islocked',$islocked);
			$f3->set('user',$this->user);
			$f3->set('title',$resultwiki[0]["title"]);
			$f3->set('term_name',$resultwiki[0]["title"]);
			$f3->set('post_count',$resultcount[0]['count']);
			//Process template
			$f3->set('pagename','wiki_edit');
			$template=new Template;
	    	echo $template->render('wiki_edit.html');
    	}else{
    		//Invalid ID, redirect
    		$f3->reroute('/wiki/list/');
    	}
	}
}
?>