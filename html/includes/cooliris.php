<?php
//Init classes
$user = new user();
$search = new search();
$limit = 20;

//Set query
if ($f3->get('PARAMS.query') == null){
    $f3->set('PARAMS.query','-all-');
}

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

//Get posts
if ($f3->get('PARAMS.next') !== null){
    if($f3->get('PARAMS.query') == "-all-"){
        $query = $f3->get('PARAMS.query');
        $next = $f3->get('PARAMS.next') + 20;
        $curr = (int)$f3->get('PARAMS.next');
        //Run the query for count
        $numrows = $db->exec("SELECT count(id) as count FROM ".$f3->get('post_table')." $dnp ORDER BY id DESC");
        
        //Check if we got results
        if($numrows !== 0){
            //Query for our selected data
            $postinfo = $db->exec("SELECT id, creation_date, hash, score, rating, tags, owner, ext, status, dnp FROM ".$f3->get('post_table')." $dnp ORDER BY id DESC LIMIT ?,?",array(1=>$curr,2=>$limit));
        }else{
            $postinfo = array();
        }
    }elseif ($f3->get('PARAMS.query') !== null){
        $query = $f3->get('PARAMS.query');
        $next = $f3->get('PARAMS.next') + 20;
        $curr = (int)$f3->get('PARAMS.next');
        //Run the query for count        
        $resultnum = $search->preprare_search($f3->get('PARAMS.query'));
        $numrows = $resultnum[0]["count"];
        
        //Check if we got results
        if($numrows !== 0){
            //Query for our selected data
            $postinfo = $search->search($f3->get('PARAMS.query'),$curr,$limit);
        }else{
            $postinfo = array();
        }
    }
}else{
    if($f3->get('PARAMS.query') == "-all-"){
        $curr = 0;
        $query = $f3->get('PARAMS.query');
        //Run the query for count
        $numrows = $db->exec("SELECT count(id) as count FROM ".$f3->get('post_table')." $dnp ORDER BY id DESC");
        
        //Check if we got results
        if($numrows !== 0){
            //Query for our selected data
            $postinfo = $db->exec("SELECT id, creation_date, hash, score, rating, tags, owner, ext, status, dnp FROM ".$f3->get('post_table')." $dnp ORDER BY id DESC LIMIT ?,?",array(1=>$curr,2=>$limit));
        }else{
            $postinfo = array();
        }
        $next = 20;
    }elseif ($f3->get('PARAMS.query') !== null){
        $curr = 0;
        $query = $f3->get('PARAMS.query');
        //Run the query for count        
        $resultnum = $search->preprare_search($f3->get('PARAMS.query'));
        $numrows = $resultnum[0]["count"];
        
        //Check if we got results
        if($numrows !== 0){
            //Query for our selected data
            $postinfo = $search->search($f3->get('PARAMS.query'),$curr,$limit);
        }else{
            $postinfo = array();
        }
        $next = 20;
    }
}

//Fix date and add username
if (count($postinfo) !== 0){
    foreach($postinfo as $key => $post){
        $postinfo[$key]["date"] = date("Y-m-d", strtotime($post["creation_date"]))."T".date("H:i:s", strtotime($post["creation_date"]))."Z";
        $postinfo[$key]["username"] = $user->get_username($post["owner"]);
    }
}

//Get current date
$currdate = date("Y-m-d")."T".date("H:i:s")."Z";

//Store posts for template
$f3->set('posts',$postinfo);
$f3->set('currdate',$currdate);
$f3->set('next',$next);
$f3->set('query',$query);
?>