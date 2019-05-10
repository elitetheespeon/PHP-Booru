<?php
$logger = new logger();
$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'MAIL_OUTBOX', $f3->get('PARAMS.id'));

//Number of mail per page
$limit = 20;

//Load required classes
$user = new user();
$misc = new misc();
$dmail = new dmail();
$mailcount = 0;
$mailresult = array();

//Check if user is logged in
if(!$user->check_log()){
    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'MAIL_OUTBOX', 'NOT_LOGGED_IN');
	$template=new Template;
    echo $template->render('no_permission.html');
	exit();
}

//Get total number of dmail for user
$numrows = $dmail->count_mail($f3->get('checked_user_id'),true);

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
    $f3->reroute('/mail/outbox/');
}

//Get all mail sent to the current user
$mailinfo = $db->exec('SELECT * FROM '.$f3->get('dmail_table').' WHERE from_id = ? ORDER BY created_at DESC LIMIT ?,?',array(1=>$f3->get('checked_user_id'),2=>$pg_start,3=>$limit));
$numrows = count($mailinfo);

//Start loop through mail
foreach($mailinfo as $mail){
    //Convert results for template
    $mailresult[$mailcount]['id'] = $mail['id'];
    $mailresult[$mailcount]['date_now'] = $misc->date_words(strtotime($mail["created_at"]));
    $mailresult[$mailcount]['to_id'] = $mail['to_id'];
    $mailresult[$mailcount]['to_username'] = $user->get_username($mail['to_id']);
    $mailresult[$mailcount]['title'] = $mail['title'];
    $mailresult[$mailcount]['has_seen'] = $mail['has_seen'];
    $mailcount++;
}

//Set mail for template
$f3->set('mail',$mailresult);
?>