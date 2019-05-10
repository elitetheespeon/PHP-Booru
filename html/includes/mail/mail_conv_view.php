<?php
$logger = new logger();
$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'MAIL_CONV_VIEW', $f3->get('PARAMS.id'));

//Number of messages per page
$limit = 10;

//Load required classes
$user = new user();
$misc = new misc();
$dmail = new dmail();
$id = $f3->get('PARAMS.id');
$uname = $f3->get('checked_username');
$uid = $f3->get('checked_user_id');
$mailcount = 0;
$mailresult = array();

//Check if user is logged in
if(!$user->check_log()){
    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'MAIL_CONV_VIEW', 'NOT_LOGGED_IN');
	$template=new Template;
    echo $template->render('no_permission.html');
	exit();
} 

//Check to see if the mail was intended for this user
if(!$dmail->can_view_mail($id, $uid)){
    $f3->reroute('/mail/inbox');
}

//Get messages count
$messagecount = $db->exec('SELECT COUNT(id) as count FROM '.$f3->get('dmail_table').' WHERE id = ? OR parent_id = ?',array(1=>$id,2=>$id));
$numrows = $messagecount[0]["count"];

//Pagination
$pages = new Pagination($numrows, $limit);
$pages->setTemplate('pagination.html');
$f3->set('pagebrowser', $pages->serve());
if ($f3->get('PARAMS.page') == ""){
	$f3->set('PARAMS.page',1);
}

//Convert page number for db query
$pg_start = $limit*($f3->get('PARAMS.page')-1);
$pg_curr = $pg_start;
$pg_end = $pg_start+$limit;
if ($pg_end > $numrows){
	$pg_end = $numrows;
}

//Make sure page is not higher than max rows
if ($pg_start > $numrows || $pg_start < 0){
    $f3->reroute('/mail/conv_view/'.$f3->get('PARAMS.id'));
}

//Get messages related to conversation
$mailinfo = $db->exec('SELECT * FROM '.$f3->get('dmail_table').' WHERE id = ? OR parent_id = ? LIMIT ?,?',array(1=>$id,2=>$id,3=>$pg_start,4=>$limit));

//Start loop through mail
foreach($mailinfo as $mail){
    //Check for the first reply and store
    if ($mailcount == 1){
        //Set the right userid for replying
        $to_uid = $mail["to_id"];
        //Make sure we are not replying to ourselves!
        if ($to_uid == $uid){
            $to_uid = $mail["from_id"];
        }
        //Get username for replying
        $to_uname = $user->get_username($to_uid);
        //Get title for replying
        $titlereply = $mail["title"];
        //Set the right parent ID
        if (!empty($mail["parent_id"])){
            $parentid = $mail["parent_id"];
        }else{
            $parentid = $id;
        }
    }
    //Convert results for template
    $mailresult[$mailcount]['from_id'] = $mail["from_id"];
    $mailresult[$mailcount]['from_username'] = $user->get_username($mail["from_id"]);
    $mailresult[$mailcount]['from_avatarinfo'] = $user->get_avatar($mail["from_id"]);
    $mailresult[$mailcount]['from_title'] = $user->get_user_title($mail["from_id"]);
    $mailresult[$mailcount]['from_signature'] = $misc->short_url($misc->swap_bbs_tags($misc->linebreaks($user->get_signature($mail["from_id"])))); 
    $mailresult[$mailcount]['to_id'] = $mail["to_id"];
    $mailresult[$mailcount]['to_username'] = $user->get_username($mail["to_id"]);
    $mailresult[$mailcount]['title'] = $mail["title"];
    $mailresult[$mailcount]['body'] = $misc->short_url($misc->swap_bbs_tags($misc->linebreaks($mail["body"])));
    $mailresult[$mailcount]['date_made'] = $misc->date_words(strtotime($mail["created_at"]));
    $mailresult[$mailcount]['parent_id'] = $parentid;
    $mailcount++;
}

//Convert results for template
$avatarinfo = $user->get_avatar($f3->get('checked_user_id'));
$utitle = $user->get_user_title($f3->get('checked_user_id'));

//Set vars for template
$f3->set('mail',$mailresult);
$f3->set('parentid',$parentid);
$f3->set('to_uid',$to_uid);
$f3->set('to_uname',$to_uname);
$f3->set('avatarinfo',$avatarinfo);
$f3->set('utitle',$utitle);
$f3->set('titlereply',$titlereply);
?>