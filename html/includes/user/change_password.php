<?php
//Load required classes
$user = new user();
$sentdata = false;
$error = "";

//Check if user is logged in
if(!$user->check_log()){
	$f3->reroute('/user/home');
}

//Check for sent data	
if(isset($_POST['new_password']) && $_POST['new_password'] != ""){
	$sentdata = true;
	//Check if new password matches confirmed password
	if ($_POST['new_password'] !== $_POST['confirm_p2']){
        //Fail
        $error = "The two passwords you submitted do not match.";
	}else{
        //Success, change that password
        $pass = $_POST['new_password'];
        $id = $f3->get('checked_user_id');
    	$user->update_password($id,$pass);
    	$error = "Your password has been changed successfuly, you can now log back in.";
	}
}

//Save vars for template
$f3->set('sentdata',$sentdata);
$f3->set('error',$error);
?>