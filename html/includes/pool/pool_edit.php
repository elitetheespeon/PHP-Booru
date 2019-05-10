<?php
//Load required classes
$user = new user();
$logger = new logger();
$ip = $_SERVER['REMOTE_ADDR'];

//Check if user is banned
if($user->banned_ip($ip)){
    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'EDIT_POOL', 'BANNED');
	$template=new Template;
    echo $template->render('no_permission.html');
	exit();
}

//Check if user is logged in
if(!$user->check_log()){
    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'EDIT_POOL', 'NOT_LOGGED_IN');
	$template=new Template;
    echo $template->render('no_permission.html');
	exit();
}

//Check pool ID and set
if($f3->get('PARAMS.id') !== "" && is_numeric($f3->get('PARAMS.id')) && $f3->get('PARAMS.id') >= 0){
	$poolid = $f3->get('PARAMS.id');
}else{
	//Redirect to main listing
    $f3->reroute('/pool/list');
}

//Query for pool information
$resultpool = $db->exec('SELECT id, name, user_id, is_public, description, is_active FROM '.$f3->get('pool_table').' WHERE id = ? LIMIT 1',array(1=>$poolid));
$numrows = count($resultpool);
    
//Check for bad pool ID
if($numrows == 0){
	//Redirect to main listing
	$f3->reroute('/pool/list');
}

//Check if user has permission to edit this pool
if(!$user->gotpermission('delete_posts')){
    //Check if user created pool
    if($resultpool[0]["user_id"] == $f3->get('checked_user_id')){
        //User created pool, allow edit
    }else{
        $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'EDIT_POOL', 'NO_ACCESS');
    	$template=new Template;
        echo $template->render('no_permission.html');
    	exit();
    }
}

//Check if data was posted back or not
if(isset($_POST['submit']) && $_POST['name'] !== ""){
    if(isset($_POST['public'])){
        $public = 1;
    }else{
        $public = 0;
    }
    if(isset($_POST['active'])){
        $active = 1;
    }else{
        $active = 0;
    }
    $update = $db->exec('UPDATE '.$f3->get('pool_table').' SET name = ?, description = ?, is_public = ?, is_active = ? WHERE id = ?',array(1=>$_POST['name'],2=>$_POST['desc'],3=>$public,4=>$active,5=>$poolid));
    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'EDIT_POOL', 'SUCCESS', $poolid);
    $f3->reroute('/pool/view/'.$poolid);
}
//Public and active are checkboxes so we need some extra validation.
if($resultpool[0]["is_public"] == 1){
    $public = "checked";
}else{
    $public = "";
}
if($resultpool[0]["is_active"] == 1){
    $active = "checked";
}else{
    $active = "";
}

//Pass vars to template
$f3->set('resultpool',$resultpool);
$f3->set('public',$public);
$f3->set('active',$active);
$f3->set('poolid',$poolid);