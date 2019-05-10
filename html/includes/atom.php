<?php
//Init classes
$user = new user();

//Get posts
$postinfo = $db->exec('SELECT id,creation_date,owner,source,title,description,hash,ext,tags FROM '.$f3->get('post_table').' WHERE status = \'active\' AND dnp = 0 ORDER BY creation_date DESC LIMIT 15');

//Fix date and add username
foreach($postinfo as $key => $post){
    $postinfo[$key]["date"] = date("Y-m-d", strtotime($post["creation_date"]))."T".date("H:i:s", strtotime($post["creation_date"]))."Z";
    $postinfo[$key]["username"] = $user->get_username($post["owner"]);
}

//Get current date
$currdate = date("Y-m-d")."T".date("H:i:s")."Z";

//Store posts for template
$f3->set('posts',$postinfo);
$f3->set('currdate',$currdate);
?>