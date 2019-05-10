<?php
//Load required class
$user = new user();
$misc = new misc();
$logger = new logger();
$ip = $_SERVER['REMOTE_ADDR'];

//Check if registration is enabled
if($f3->get('registration_allowed') != true){
  $template=new Template;
  echo $template->render('register_disabled.html');
  exit();
}

//Check if user is banned
if($user->banned_ip($ip)){
  $template=new Template;
  echo $template->render('no_permission.html');
  exit();
}

//Check if user is already logged in
if($user->check_log()){
	$f3->reroute('/user/home');
	exit();
}

//Setup array of images and answers for captcha
$captchas = array('fork'=>'a6239bb0afe6dd.jpg', 'spoon'=>'af983cc8e17239.jpg', 'chair'=>'7cda6149dd1f6e.jpg');

//Check if everything is sent and process
if(isset($_POST['user']) && $_POST['user'] != "" && isset($_POST['pass']) && $_POST['pass'] != "" && isset($_POST['conf_pass']) && $_POST['conf_pass'] != "" && $_POST['cap'] != "" && $_POST['capval'] != ""){
  //Set and fix up our posted data
  $username = str_replace(" ",'_',htmlentities($_POST['user'], ENT_QUOTES, 'UTF-8'));
	$password = $_POST['pass'];
	$conf_password = $_POST['conf_pass'];
	$email = $_POST['email'];	
  
  //Check if captcha is valid
  if($captchas[strtolower($_POST['capval'])] !== strtolower($_POST['cap'])){
      //Fail to get captcha correct
      $logger->log_action(0, $ip, 'REGISTER', 'FAIL_CAPTCHA',$_POST['capval']);
      $info_error = "Signup failed. You did not submit the correct answer to the image.";
  }else{
    //Check if password matches confirmed password
    if($password == $conf_password){
    	//Attempt user registration
    	if(!$user->signup($username,$password,$email)){
        //Failed
        $info_error = "Registration failed. This can be caused by: a database error, a user with that username already exists, or your nick contains characters that are not allowed. Please make sure that your nick doesn't contain space, tab, ; or ,. Please also makes sure that your nick is at least 3 characters.";
      }else{
    		//Success, GTFO
    		$user->login($username,$password);
    		$f3->reroute('/user/home');
    		exit();
    	}
    }else{
      //Passwords do not match
      $info_error = "Passwords do not match.";
    }    
  }
}

//Grab random image from captcha list
$capval = array_rand($captchas,1);

//Set variables for template
$f3->set('captchas',$captchas);
$f3->set('capval',$capval);
$f3->set('info_error',$info_error);
?>