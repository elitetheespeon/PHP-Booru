<?php
    //Make sure user is logged in
	$user = new user();
    if(!$user->check_log()){
        $f3->reroute('/user/login/00');
    }

	if(isset($_POST['submit'])){
		//User blacklist
        if(isset($_POST['users']) && $_POST['users'] != ""){
			setcookie("user_blacklist",strtolower(str_replace('\\',"&#92;",str_replace(' ','%20',str_replace("'","&#039;",$_POST['users'])))),time()+60*60*24*365);
			$new_user_list = $_POST['users'];
		}else{
			setcookie("user_blacklist",'',time()-60*60*24*365);
			$new_user_list = " ";
		}
		//Tag blacklist
        if(isset($_POST['tags']) && $_POST['tags']){
			setcookie("tag_blacklist",str_replace('\\',"&#92;",str_replace(' ','%20',str_replace("'","&#039;",$_POST['tags']))),time()+60*60*24*365);
			$new_tag_list = $_POST['tags'];
		}else{
			setcookie("tag_blacklist","",time()-60*60*24*365);
			$new_tag_list = " ";
		}
		//Comment threshold
        if(isset($_POST['cthreshold']) && $_POST['cthreshold'] != ""){
			if(!is_numeric($_POST['cthreshold'])){
				setcookie('comment_threshold',0,time()+60*60*24*365);
				$new_cthreshold = 0;
			}else{
				setcookie('comment_threshold',$_POST['cthreshold'],time()+60*60*24*365);
				$new_cthreshold = $_POST['cthreshold'];
			}
		}else{
			setcookie('comment_threshold',"",time()-60*60*24*365);
			$new_cthreshold = 0;
		}
        //Post threshold
		if(isset($_POST['pthreshold']) && $_POST['pthreshold'] != ""){
			if(!is_numeric($_POST['pthreshold'])){
				setcookie('post_threshold',0,time()+60*60*24*365);
				$new_pthreshold = 0;
			}else{
				setcookie('post_threshold',$_POST['pthreshold'],time()+60*60*24*365);
				$new_pthreshold = $_POST['pthreshold'];
			}
		}else{
			setcookie('post_threshold',"",time()-60*60*24*365);
			$new_pthreshold = 0;
		}
		//My tags
        if(isset($_POST['my_tags']) && $_POST['my_tags'] != ""){
			setcookie("tags",str_replace(" ","%20",str_replace("'","&#039;",$_POST['my_tags'])),time()+60*60*24*365);
			$new_my_tags = $_POST['my_tags'];
			if($user->check_log()){
				$my_tags = $_POST['my_tags'];			
				$update = $db->exec('UPDATE '.$f3->get('user_table').' SET my_tags = ? WHERE id = ?',array(1=>$my_tags,2=>$f3->get('checked_user_id')));
			}
		}else{
			setcookie("tags",'',time()-60*60*24*365);
			$new_my_tags = " ";
		}
		//Email address
        if(isset($_POST['email']) && $_POST['email'] != ""){
            $email = $_POST['email'];
            //Check for valid email
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                //Change email address
                $user->update_email($f3->get('checked_user_id'),$email);
            }
		}
		//Notifications
        if(isset($_POST['notify']) && $_POST['notify'] != ""){
            if ($_POST['notify'] == 0 || $_POST['notify'] == 1) {
                //Update prefs
                $user->update_notifications($f3->get('checked_user_id'),$_POST['notify']);
            }
		}
        //Signature
        if(isset($_POST['signature']) && $_POST['signature'] != ""){
            //Update prefs
            $user->set_signature($f3->get('checked_user_id'),$_POST['signature']);
		}
		//User title
        if(isset($_POST['title'])){
            //Update prefs
            $user->set_user_title($f3->get('checked_user_id'),$_POST['title']);
		}		
		//API key
        if(isset($_POST['api_access'])){
        	if ($_POST['api_access'] == 0) {
	            //Update prefs
	            $user->set_api_key($f3->get('checked_user_id'), true);
        	}elseif ($_POST['api_access'] == 1){
	            //Update prefs
	            $user->set_api_key($f3->get('checked_user_id'));
        	}
		}
	}

//Comment threshold for template
if($new_cthreshold == "" && !isset($_COOKIE['comment_threshold'])){
    $comment_threshold = 0;
}elseif($new_threshold != "" && is_int($new_threshold)){
    $comment_threshold = $new_cthreshold;
}else{
    $comment_threshold = $_COOKIE['comment_threshold'];
}
$f3->set('comment_threshold',$comment_threshold);

//Post threshold for template
if($new_pthreshold == "" && !isset($_COOKIE['post_threshold'])){
    $post_threshold = 0;
}elseif($new_pthreshold != "" && is_int($new_pthreshold)){
    $post_threshold = $new_pthreshold;
}else{
    $post_threshold = $_COOKIE['post_threshold'];
}
$f3->set('post_threshold',$post_threshold);

//Tag blacklist for template
if($new_tag_list != ""){
    $tag_blacklist = $new_tag_list;
}else{
    $tag_blacklist = str_replace('%20',' ', str_replace("&#039;","'",$_COOKIE['tag_blacklist']));
}
$f3->set('tag_blacklist',$tag_blacklist);

//User blacklist for template
if($new_user_list != ""){
    $user_blacklist = $new_user_list; 
}else{
    $user_blacklist = str_replace('%20',' ', str_replace("&#039;","'", $_COOKIE['user_blacklist']));
}
$f3->set('user_blacklist',$user_blacklist);

//My tags for template
if($new_my_tags != ""){
    $tags = $new_my_tags;
}else{    
    $tags = str_replace("%20", " ",str_replace('&#039;',"'",$_COOKIE['tags']));
}
$f3->set('tags',$tags);

//Email for template
$email = $user->get_email($f3->get('checked_user_id'));
$f3->set('email',$email);

//Notifications for template
$notifynum = $user->get_notifications($f3->get('checked_user_id'));
if($notifynum == 0){ 
    $notify1 = 'selected="selected"';
}else{
    $notify2 = 'selected="selected"';
}
$f3->set('notify1',$notify1);
$f3->set('notify2',$notify2);

//User title for template
$title = $user->get_user_title($f3->get('checked_user_id'));
$f3->set('title',$title);

//Signature for template
$signature = $user->get_signature($f3->get('checked_user_id'));
$f3->set('signature',$signature);

//API key for template
$api_key = $user->get_api_key($f3->get('checked_user_id'));
$f3->set('api_key',$api_key);

//API access for template
if(!$api_key){ 
    $api_access1 = 'selected="selected"';
}else{
    $api_access3 = 'selected="selected"';
}
$f3->set('api_access1',$api_access1);
$f3->set('api_access2',$api_access2);
$f3->set('api_access3',$api_access3);
?>