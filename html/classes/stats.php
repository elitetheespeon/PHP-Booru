<?php
class stats{

	var $user;
	var $logger;
	var $misc;

	function __construct(){
		$this->user = new user();
		$this->logger = new logger();
		$this->misc = new misc();
	}

    function render(){
        global $f3, $db;
        //Log page hit
        $this->logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'STATS', $f3->get('PARAMS.page'));
        
        //Number of stats/page
        $limit = 50;
        $statoutput = array();
        
        //Query for User Statistics
        $reqcount = $db->exec('SELECT COUNT(*) as count FROM '.$f3->get('user_table'));
        $numrows = $reqcount[0]["count"];
        
        //Set up pagination
        $pages = new pagination($numrows, $limit);
        $pages->setTemplate('pagination.html');

        $f3->set('pagebrowser', $pages->serve());
        if ($f3->get('PARAMS.page') == ""){
        	$f3->set('PARAMS.page',1);
        }
        
        //Get sort param
        switch ($f3->get('PARAMS.sort')){
            case 'userid':
                $sortq ="ORDER BY id ASC";
                $sort = 'userid';
                break;
            case 'username':
                $sortq ="ORDER BY user ASC";
                $sort = 'username';
                break;
            case 'group':
                $sortq ="ORDER BY u.ugroup DESC";
                $sort = 'group';
                break;
            case 'posts':
                $sortq ="ORDER BY u.post_count DESC";
                $sort = 'post_count';
                break;    
            case 'favorites':
                $sortq ="ORDER BY f.fcount DESC";
                $sort = 'favorites';
                break;
            case 'user_score':
                $sortq ="ORDER BY record_score DESC";
                $sort = 'user_score';
                break;
            case 'forum_posts':
                $sortq ="ORDER BY forum_post_count DESC";
                $sort = 'forum_posts';
                break;
            case 'tag_edits':
                $sortq ="ORDER BY tag_edit_count DESC";
                $sort = 'tag_edits';
                break;
            default:
                $sortq ="ORDER BY id ASC";
                $sort = 'userid';
                break;
        }
        
        //Check if there users       
        if($numrows == 0){
        	//No users found
        }else{
        	//Start the page on 1 silly
        	$pg_start = $limit*($f3->get('PARAMS.page')-1);
        	//Make sure page is not higher than max rows
            if ($pg_start > $numrows || $pg_start < 0){
                $f3->reroute('/stats/'.$sort.'/1');
        	}
        }
        
        //Get the user data
        $resultreq = $db->exec('SELECT u.id, u.user, g.group_name, coalesce(f.fcount, 0) as fcount, u.post_count, u.record_score, u.forum_post_count, u.tag_edit_count, u.signup_date, u.last_logged_in_at 
                            FROM '.$f3->get('user_table').' u
                            LEFT JOIN '.$f3->get('group_table').' g
                            	on (u.ugroup = g.id)
                            LEFT JOIN '.$f3->get('favorites_count_table').' f
                            	on (u.id = f.user_id)
                            '.$sortq.' 
                            LIMIT ?,?', array(1=>$pg_start,2=>$limit));
  
        //Store data for template
        $f3->set('statoutput',$resultreq);
        $f3->set('sort',$sort);
		//Process template
		$f3->set('pagename','stats_users');
		$template=new Template;
    	echo $template->render('stats_users.html');
    }
}
?>