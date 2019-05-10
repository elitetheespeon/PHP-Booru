<?php
$user = new user();
//If user is logged in, reroute to home
if($user->check_log()){
	$f3->reroute('/user/home');
}
if(isset($_POST['user']) && $_POST['user'] != "" && isset($_POST['pass']) && $_POST['pass'] != ""){
	//Put login info into vars
	$username = htmlentities($_POST['user'], ENT_QUOTES, 'UTF-8');
	$password = $_POST['pass'];
	//Atttempt login with credentials passed
	if(!$user->login($username, $password)){
		//Bad login, back to login page
		$f3->reroute('/user/login/00');
	}else{
		//Successful login, route to home
		$f3->reroute('/user/home');
	}
}