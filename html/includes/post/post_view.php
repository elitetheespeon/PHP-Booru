<?php
$logger = new logger();
$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'POST_VIEW', $f3->get('PARAMS.id'));

//Number of comments/page
$limit = 10;

//Load required classes.
$post = new post();
$user = new user();
$tag = new tag();
$comment = new comment();
$misc = new misc();
$tagresult = array();
$commentsarr = array();
$f3->set('user',$user);
$f3->set('post',$post);
$f3->set('misc',$misc);

//Set headers for caching browser side and clean the id
header("Cache-Control: store, cache");
header("Pragma: cache");
$id = (int)$f3->get('PARAMS.id');
$date = date("Ymd");

//Load post_table data
$post_data = $post->show($id);

//Check if we got results back, if not send the user to the main post list.
if(!$post_data){
	$f3->reroute('/post/all');
}

//Store post data for template
$f3->set('postdata',$post_data);

//Load the previous next values in
$prev_next = $post->prev_next($id);
$f3->set('prev_next',$prev_next);

//Get list of tags for post
$ttags = $post->get_tags($id);
$tags = $misc->mb_trim(html_entity_decode($post_data[0]['tags'], ENT_QUOTES, "UTF-8"));
$tagsarr = explode(" ", $tags);

$f3->set('tagsarr',$tagsarr);

if($post_data[0]['rating'] == "e"){
    $rating = "Explicit";
}elseif($post_data[0]['rating'] == "q"){
    $rating = "Questionable";
}elseif($post_data[0]['rating'] == "s"){
    $rating = "Safe";
}

$f3->set('rating',$rating);

$source = preg_replace('/https?:\/\/[^\s<]+/i', '<a href="\0">'.substr($post_data[0]['source'],0,20).'...</a>', $post_data[0]['source']);
$f3->set('source',$source);

//Get list of users that favorited this post
$favs = $post->get_favorites($id);
//Add comma value
if ($favs){
    foreach ($favs as $key => $row){
        if (($key+1) == count($favs)){
            $favs[$key]["comma"] = "";
        }else{
            $favs[$key]["comma"] = ",";
        }
    }
}
$f3->set('favs',$favs);

//Check if user has favorited post
$favchk = $post->has_favorited($id);
$f3->set('favchk',$favchk);

//Get flagged data for post if it exists
$flagres = $post->is_flagged($id);
$f3->set('flagres',$flagres);

//Check if this post is part of any pools and store result
$poolinfo = $post->is_pooled($id);
$f3->set('poolinfo',$poolinfo);

//Grab the index count and tag type from database.
$taginc = 0;
foreach($ttags as $current){
	$tagcolor = $tag->tag_css_class($current["tag_type"]);
	
    //Store all tag vars
    $tagresult[$taginc]['color'] = $tagcolor;
    $tagresult[$taginc]['name'] = $current["name"];
    $tagresult[$taginc]['count'] = $current["post_count"];
    $taginc++;
}

//Store tags for template
$f3->set('tags',$tagresult);

//Store pool list for template
if ($user->gotpermission('edit_posts')){ 
    $poollist = $post->pool_list($f3->get('checked_user_id'));
    $f3->set('poollist',$poollist);
}

//Set the status boxes
if ($post_data[0]['status'] == 'active'){
    $status1 = 'selected="selected"';
    $status2 = '';
    $status3 = '';
}elseif($post_data[0]['status'] == 'deleted'){
    $status1 = '';
    $status2 = 'selected="selected"';
    $status3 = '';
}elseif($post_data[0]['status'] == 'pending'){
    $status1 = '';
    $status2 = '';
    $status3 = 'selected="selected"';
}

$f3->set('status1',$status1);
$f3->set('status2',$status2);
$f3->set('status3',$status3);

//Set DNP boxes			
if ($post_data[0]['dnp'] == 1){
    $dnp1 = 'selected="selected"';
    $dnp2 = '';
}else{
    $dnp1 = '';
    $dnp2 = 'selected="selected"';
}

$f3->set('dnp1',$dnp1);
$f3->set('dnp2',$dnp2);

//Check to get the status of image
if($post_data[0]['status'] == "deleted"){
	$imagestatus = "This post has been deleted. Reason: ".$flagres;
}elseif($post_data[0]['status'] == "pending"){
	$imagestatus = "This post is pending moderator approval.";
}elseif($post_data[0]['dnp'] == 1){
    $imagestatus = "This post is DNP.";
}elseif($flagres){
    $imagestatus = "This post has been reported. Reason: ".$flagres;
}else{
    $imagestatus = "";
}			

$f3->set('imagestatus',$imagestatus);		

//Get note data		
$note_data = $post->get_notes($id);
$f3->set('note_data',$note_data);

//Get comment count
$count = $comment->count($f3->get('PARAMS.id'),'post','view');
$f3->set('commentcount',$count);

//Query for the comments
$comments = $comment->get_comments($id);

//Start pagination for comments
$pages = new Pagination($count, $limit);
$pages->setTemplate('pagination.html');
$f3->set('pagebrowser', $pages->serve());
if ($f3->get('PARAMS.page') == ""){
	$f3->set('PARAMS.page',1);
}

$pg_start = $limit*($f3->get('PARAMS.page')-1);
$pg_curr = $pg_start;
$pg_end = $pg_start+$limit;
if ($pg_end > $count){
	$pg_end = $count;
}

//Make sure page is not higher than max rows
if ($pg_start > count($comments) || $pg_start < 0){
    $f3->reroute('/post/view/'.$f3->get('PARAMS.id'));
}

//Start loop through image comments
while ($pg_curr < $pg_end){
    //Store all post vars
    $commentsarr[$pg_curr]['id'] = $comments[$pg_curr]['id'];
    $commentsarr[$pg_curr]['user'] = $comments[$pg_curr]['user'];
    $commentsarr[$pg_curr]['score'] = $comments[$pg_curr]['score'];
    $commentsarr[$pg_curr]['spam'] = $comments[$pg_curr]['spam'];
    $commentsarr[$pg_curr]['comment'] = $comments[$pg_curr]['comment'];
    $commentsarr[$pg_curr]['posted_at'] = $comments[$pg_curr]['posted_at'];
    $commentsarr[$pg_curr]['usertitle'] = $user->get_user_title($comments[$pg_curr]['user']);
    $commentsarr[$pg_curr]['usersignature'] = $misc->swap_bbs_tags($misc->short_url($user->get_signature($comments[$pg_curr]['user'])));
    $pg_curr++;
}

//Store comments for template
$f3->set('comments',$commentsarr);
?>