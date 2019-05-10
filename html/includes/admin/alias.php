<?php
//Load required classes
$logger = new logger();
$user = new user();
$tagc = new tag();
$misc = new misc();
$f3->set('user',$user);

//Check if user is an admin
if(!$user->gotpermission('is_admin')){
    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'ADMIN_ALIAS', 'NO_ACCESS');
	$template=new Template;
    echo $template->render('no_permission.html');
	exit();
}

//Check if tag and alias was sent or not
if($f3->get('PARAMS.tag') !== null && $f3->get('PARAMS.alias') !== null){
	//Check if post is being accepted or rejected
	if(isset($_POST['accept']) && is_numeric($_POST['accept'])){
		//Store sent data
		$tag = $f3->get('PARAMS.tag');
		$alias = $f3->get('PARAMS.alias');
		//Check if alias is being accepted
		if($_POST['accept'] == 1){
			//Update alias status to accepted
			$update1 = $db->exec('UPDATE '.$f3->get('alias_table').' SET status=\'accepted\' WHERE tag = ? AND alias = ?',array(1=>$tag,2=>$alias));
			//Get all posts with tag alias
			$cleanalias = str_replace('%','\%',str_replace('_','\_',$alias));
	        $searchp1 = "% ".$cleanalias;
	        $searchp2 = "% ".$cleanalias." %";
	        $searchp3 = $cleanalias." %";
			$postresult = $db->exec('SELECT id, tags FROM '.$f3->get('post_table').' WHERE (tags LIKE ? OR tags LIKE ? OR tags LIKE ?)',array(1=>$searchp1,2=>$searchp2,3=>$searchp3));
			//Convert all current posts from the alias to the real tag.
			foreach($postresult as $row){
				//Split tags into array
				$tags = explode(" ",$row['tags']);
				//Loop through tag array and delete index
				foreach($tags as $current){
				    $tagid = $tagc->tag_lookup($current);
				    $delete = $db->exec('DELETE FROM '.$f3->get('poststags_table').' WHERE post_id = ? AND tag_id = ?',array(1=>$row['id'], 2=>$tagid['id']));
				    $tagc->deleteindextag($tagid['id']);
				}
				//Replace any instances of the aliased tag with the real tag
				$tmp = str_replace(' '.$alias.' ',' '.$tag.' ',$row['tags']);
				//Build new tag list from the replaced list
				$tags = implode(" ",$tagc->filter_tags($tmp,$tag,explode(" ",$tmp)));
				$tags = $misc->mb_trim(str_replace("  ","",$tags));
				$tags2 = explode(" ",$tags);
				//Loop through new tag array and add index
				foreach($tags2 as $current){
				    $tagid = $tagc->tag_lookup($current);
				    $insert = $db->exec('INSERT INTO '.$f3->get('poststags_table').' (post_id,tag_id) VALUES (?, ?)',array(1=>$row['id'], 2=>$tagid['id']));
				    $tagc->addindextag($tagid['id']);
				}
				$tags = " $tags ";
				//Update tags for post
				$update2 = $db->exec('UPDATE '.$f3->get('post_table').' SET tags = ? WHERE id = ?',array(1=>$tags,2=>$row['id']));
			}
			$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'ADMIN_ALIAS', 'ALIAS_APPROVED', $alias);
		}else if($_POST['accept'] == 2){
			//Update alias status to rejected
			$update1 = $db->exec('UPDATE '.$f3->get('alias_table').' SET status=\'rejected\' WHERE tag = ? AND alias = ?',array(1=>$tag,2=>$alias));
			$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'ADMIN_ALIAS', 'ALIAS_REJECTED', $alias);
		}
		//Redirect
		$f3->reroute('/admin/alias');
	}else{
		//Set approve/reject var for template as nothing was passed
		$f3->set('app_rej_menu',true);
	}
}else{
	//Get any tag aliases that are pending
	$pendingresult = $db->exec('SELECT tag, alias FROM '.$f3->get('alias_table').' WHERE status=\'pending\'');
	//Store data for template
	$f3->set('pendingresult', $pendingresult);
}
?>