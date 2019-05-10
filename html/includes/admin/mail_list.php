<?php
//Load required classes
$user = new user();
$misc = new misc();
$dmail = new dmail();
$f3->set('user',$user);
$mailcount = 0;
$mailresult = array();

//Number of mail per page
$limit = 20;

//Check if user is an admin
if(!$user->gotpermission('is_admin')){
    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'ADMIN_DMAIL_LIST', 'NO_ACCESS');
	$template=new Template;
    echo $template->render('no_permission.html');
	exit();
}

//Get conversations count
$numrows = $dmail->count_mail();

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
    $f3->reroute('/admin/mail_list/');
}

//Get all mail sent to the current user
$mailinfo = $db->exec('SELECT * FROM '.$f3->get('dmail_table').' ORDER BY created_at DESC LIMIT ?,?',array(1=>$pg_start,2=>$limit));

//Start loop through mail
foreach($mailinfo as $mail){
    //Convert results for template
    $mailresult[$mailcount]['id'] = $mail['id'];
    $mailresult[$mailcount]['date_now'] = $misc->date_words(strtotime($mail["created_at"]));
    $mailresult[$mailcount]['from_id'] = $mail['from_id'];
    $mailresult[$mailcount]['to_id'] = $mail['to_id'];
    $mailresult[$mailcount]['from_username'] = $user->get_username($mail['from_id']);
    $mailresult[$mailcount]['to_username'] = $user->get_username($mail['to_id']);
    $mailresult[$mailcount]['title'] = $mail['title'];
    $mailresult[$mailcount]['has_seen'] = $mail['has_seen'];
    $mailcount++;
}

//Set mail for template
$f3->set('mail',$mailresult);
?>