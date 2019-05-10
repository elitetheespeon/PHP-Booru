<?php
//Tell PHP to ignore user abort case
ignore_user_abort(1);

//Load required classes
$misc = new misc();
$userc = new user();
$logger = new logger();
$image = new images();

$ip = $_SERVER['REMOTE_ADDR'];
$error1 = false;
$error = false;

//Check if user is banned
if($userc->banned_ip($ip)){
	$f3->set('is_banned',true);
    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'CHANGE_AVATAR', 'BANNED');
	exit;
}

//Check if user is logged in
if(!$userc->check_log()){
	$f3->set('no_upload',true);
    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'CHANGE_AVATAR', 'NO_PERMISSION');
	$template=new Template;
    echo $template->render('no_permission.html');
	exit();
}

//Get avatar info
$avaresult = $db->exec('SELECT * FROM '.$f3->get('avatar_table').' WHERE userid = ?',array(1=>$f3->get('checked_user_id')));
$avainfo = $avaresult[0];
$avacheck = count($avaresult);

//Check if data was posted
if(isset($_POST['submit'])){
	$uploaded_image = false;
	$parent = '';
	//Check for posted data and process
	if(!empty($_FILES['upload']) && $_FILES['upload']['error'] == 0){
		$iinfo = $image->avatar_thumb($_FILES['upload'], $f3->get('checked_user_id'));
		//Check processed data
		if($iinfo === false){
			$error = $image->geterror()."An error occured. The avatar could not be added because it already exists or it is corrupted.";
			$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'CHANGE_AVATAR', 'DUPE_OR_CORRUPT');
		}else{
            //Check if user already has an avatar
            if($avacheck == 1){
                //Avatar already exists, delete old image and remove existing info
                unlink("./avatars/".$f3->get('checked_user_id')."_".$avainfo["md5"].".".$avainfo["file_ext"]);
                $delete = $db->exec('DELETE FROM '.$f3->get('avatar_table').' WHERE id = ?',array(1=>$avainfo["id"]));
            }
            //Some final checks
            $isinfo = getimagesize("./avatars/".$f3->get('checked_user_id')."_".$iinfo);
    		$ext = explode(".", $iinfo);
    		$ext = $ext[count($ext) - 1];
            //Save avatar info to database
            $insert = $db->exec('INSERT INTO '.$f3->get('avatar_table').' (userid, file_ext, height, width, md5) VALUES(?, ?, ?, ?, ?)',array(1=>$f3->get('checked_user_id'),2=>$ext,3=>$isinfo[1],4=>$isinfo[0],5=>md5_file("./avatars/".$f3->get('checked_user_id')."_".$iinfo)));
            if($insert == false){
    			//Failed
    			$error1 = "Failed to upload image.".$query;
    			unlink("./avatars/".$f3->get('checked_user_id')."_".$iinfo);
                $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'CHANGE_AVATAR', 'UPLOAD_ERROR');
            }else{
    			//Success
                $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'CHANGE_AVATAR', 'SUCCESS');
                $f3->reroute('/user/avatar');
            }
		}
	//No valid data was processed, must be nothing
	}else{
		$error1 = "No avatar given for upload.";
        $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'CHANGE_AVATAR', 'NO_IMAGE');
	}
}

//Save output information for template
$f3->set('info_output',$error1);
$f3->set('info_error',$error);
$f3->set('avacheck',$avacheck);
$f3->set('avainfo',$avainfo);
?>