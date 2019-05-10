<?php
//Load required classes.
$user = new user();
$post = new post();

//Check if we got a username or an id
if($f3->get('PARAMS.uname') !== NULL){
	$uname = $f3->get('PARAMS.uname');
	$resultid = $db->exec('SELECT id FROM '.$f3->get('user_table').' WHERE user = ?',array(1=>$uname));

	//Check for valid username
	if(count($resultid) == 0){
		$f3->reroute('/');
	}else{
		$id = $resultid[0]['id'];
	}
}else{
	$id = $f3->get('PARAMS.id');
}

//Get user info from db
$resultuser = $db->exec('SELECT t1.user, t1.id, t1.record_score, t1.post_count, t1.comment_count, t1.tag_edit_count, t1.forum_post_count, t1.signup_date, t2.group_name FROM '.$f3->get('user_table').' as t1 JOIN '.$f3->get('group_table').' AS t2 ON t2.id=t1.ugroup WHERE t1.id = ?',array(1=>$id));

//Check for valid user id
if(count($resultuser) == 0){
	$f3->reroute('/');
}

//Get favorite count for user
$resultfcount = $db->exec('SELECT fcount FROM '.$f3->get('favorites_count_table').' WHERE user_id = ?',array(1=>$id));
if(count($resultfcount) == 0){
	$fcount = 0;
}else{
	$fcount = $resultfcount[0]["fcount"];
}

//Level check for DNP
if ($f3->get('checked_user_group') >= $f3->get('dnp_level')){
    $dnp = "";
}else{
    $dnp = "AND dnp=0 AND status = 'active'";
}

//Get last 5 favorite posts info
$resultfavinfo = $db->exec('SELECT p.id, hash, tags, owner, rating, score, ext, dnp, status FROM '.$f3->get('favorites_table').' AS f LEFT JOIN '.$f3->get('post_table').' AS p ON (f.post_id = p.id) WHERE user_id = ? '.$dnp.' ORDER BY added DESC LIMIT 5',array(1=>$id));

//Add image string
foreach($resultfavinfo as $key => $row){
	//Get the thumbnail image
    $resultfavinfo[$key]["imagestr"] = $post->get_thumbnail($row['ext'],$row['hash']);
	//Clean for javascript
	$resultfavinfo[$key]["ownerjs"] = mb_strtolower(str_replace('\\',"&#92;",str_replace(' ','%20',str_replace("'","&#039;",$row['owner']))),'UTF-8');
	$resultfavinfo[$key]["ratingjs"] = mb_strtolower($row['rating'],'UTF-8');
	$resultfavinfo[$key]["tagsjs"] = mb_strtolower(str_replace('\\',"&#92;",str_replace("'","&#039;",substr($row['tags'],1,strlen($row['tags'])-2))),'UTF-8');
}

//Get last 5 uploaded posts info
$resultupinfo = $db->exec('SELECT id, hash, tags, rating, score, owner, ext FROM '.$f3->get('post_table').' WHERE owner = ? '.$dnp.' ORDER BY id DESC LIMIT 5',array(1=>$id));

//Add image string
foreach($resultupinfo as $key => $row){
	//Get the thumbnail image
    $resultupinfo[$key]["imagestr"] = $post->get_thumbnail($row['ext'],$row['hash']);
	//Clean for javascript
	$resultupinfo[$key]["ownerjs"] = mb_strtolower(str_replace('\\',"&#92;",str_replace(' ','%20',str_replace("'","&#039;",$row['owner']))),'UTF-8');
	$resultupinfo[$key]["ratingjs"] = mb_strtolower($row['rating'],'UTF-8');
	$resultupinfo[$key]["tagsjs"] = mb_strtolower(str_replace('\\',"&#92;",str_replace("'","&#039;",substr($row['tags'],1,strlen($row['tags'])-2))),'UTF-8');
}

//Fix up signup date
if(!is_null($resultuser[0]['signup_date']) && $resultuser[0]['signup_date']!=""){
	$signupdate = mb_substr($resultuser[0]['signup_date'],0,strlen($resultuser[0]['signup_date'])-9,'UTF-8');
}else{
	$signupdate = "N/A";
}

//Fix up group name
$groupname = ucfirst(mb_strtolower($resultuser[0]['group_name'],'UTF-8'));

//Get avatar info
$avainfo = $user->get_avatar($id);

//Pass to template
$f3->set('userinfo',$resultuser[0]);
$f3->set('favinfo',$resultfavinfo);
$f3->set('upinfo',$resultupinfo);
$f3->set('fcount',$fcount);
$f3->set('user',$user);
$f3->set('avatar',$avainfo);
$f3->set('signupdate',$signupdate);
$f3->set('groupname',$groupname);
?>