<?php
//Load required classes
$logger = new logger();
$user = new user();
$f3->set('user',$user);

//Check if user is an admin
if(!$user->gotpermission('is_admin')){
    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'ADMIN_VIEW_LOG_AUTOCOMPLETE', 'NO_ACCESS');
	$template=new Template;
    echo $template->render('no_permission.html');
	exit();
}

//Autocomplete processing
if ($_REQUEST["search"] !== "" && $_REQUEST["type"] !== ""){
    $search = $_REQUEST["search"];
    $type = $_REQUEST["type"];
    switch ($type){
        case 1:
            $search = $search."%";
            $info = $db->exec('SELECT user as search FROM '.$f3->get('user_table').' WHERE user LIKE ? LIMIT 15',array(1=>$search));
            break;
        case 2:
            $search = $search."%";
            $info = $db->exec('SELECT ip as search FROM  '.$f3->get('logs_table').' WHERE ip LIKE ? LIMIT 15',array(1=>$search));
            break;
        case 3:
            $search = $search."%";
            $info = $db->exec('SELECT id as search FROM '.$f3->get('user_table').' WHERE id LIKE ? LIMIT 15',array(1=>$search));
            break;
        default:
            exit();
            break;
    }

    $data = "<ul>";

    if(count($info !== 0)){
        foreach ($info as $return){
            $data .= "<li>".$return["search"]."</li>";    
        }
    }

    $data .= "</ul>";
    
    echo $data;
    exit();
}
?>