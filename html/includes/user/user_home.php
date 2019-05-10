<?php
//Load required class
$user = new user();
//Store class for template
$f3->set('user',$user);

//Get avatar and store for template
$f3->set('avatarinfo',$user->get_avatar($f3->get('checked_user_id')));

//Get username and store for template
$f3->set('username',$user->get_username($f3->get('checked_user_id')));
?>