<?php
//Kickstart the framework
$f3=require('../lib/base.php');

//Load all the vendor files
require_once("../vendor/autoload.php");

//Load configuration
$f3->config('config.ini');

//Set debug level
$f3->set('DEBUG',$f3->get('debug_level'));

//Database connection
$db = new DB\SQL(sprintf('mysql:host=%s;dbname=%s', $f3->get('db_host'), $f3->get('db_name')), $f3->get('db_user'), $f3->get('db_pass'));

//Autoload classes
$f3->set('AUTOLOAD','classes/');

//Login stuff
$user = new user();
if(isset($_COOKIE['user_id']) && is_numeric($_COOKIE['user_id']) && isset($_COOKIE['pass_hash']) && $_COOKIE['pass_hash'] != ""){
	if(!$user->check_log()){
		setcookie("user_id","",time()-60*60*24*365);
		setcookie("pass_hash","",time()-60*60*24*365);
	}
}

//Set the theme dir for templates
$f3->set('UI','theme/'.$f3->get('theme').'/templates/');

//Init dmail
$dmail = new dmail;
$f3->set('dmailc',$dmail);

//Setup routes
include_once('includes/routes.php');

//Run dat code
$f3->run();