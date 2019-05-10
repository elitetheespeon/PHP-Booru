<?php
	//Start the classes we need
	$userc = new user();
	$tclass = new tag();
	if($f3->get('PARAMS.type') !== "" && $f3->get('PARAMS.id') !== "" && is_numeric($f3->get('PARAMS.id'))){
		//Store params for later
		$type = $f3->get('PARAMS.type');
		$id = $f3->get('PARAMS.id');
		$pid = $f3->get('PARAMS.pid');
		$version = $f3->get('PARAMS.version');
		//Type loop
		if($type == "note" || $type == ""){
            //Get note history
			$result = $db->exec('SELECT updated_at, user_id, version, body FROM '.$f3->get('note_history_table').' WHERE note_id = ? AND post_id = ? ORDER BY version DESC',array(1=>$id,2=>$pid));
			$count = count($result);
			//Loop through each note
			$notedata = array();
			$notecount = 0;
			foreach($result as $row){
				//Query for username
				$resultuser = $db->exec('SELECT user FROM '.$f3->get('user_table').' WHERE id = ?',array(1=>$row['user_id']));
				$uname = $resultuser[0]['user'];
				$updatedat = date("m/d/y", strtotime($row['updated_at']));
                //Check for anonymous users
                if($uname == "" || $uname == null){
					$user = "Anonymous";
				}else{
					$user = $uname;
				}
			    //Store all note vars
			    $notedata[$notecount]['body'] = $row['body'];
			    $notedata[$notecount]['updated_at'] = $updatedat;
			    $notedata[$notecount]['user'] = $user;
			    $notedata[$notecount]['version'] = $row['version'];
				$notecount++;
			}
			//Set note data for template
			$f3->set('notedata',$notedata);
			$f3->set('notecount',$count);
	    	//Render template
	    	$template=new Template;
	        echo $template->render('note_history.html');		
		}else if($type == "page_notes"){
			//Get note history
			$result = $db->exec('SELECT id, updated_at, user_id, version, body, note_id FROM '.$f3->get('note_history_table').' WHERE post_id = ? ORDER BY id,version DESC',array(1=>$id));
			$count = count($result);
			//Loop through each note
			$notedata = array();
			$notecount = 0;
			foreach($result as $row){
				//Query for username
				$resultuser = $db->exec('SELECT user FROM '.$f3->get('user_table').' WHERE id = ?',array(1=>$row['user_id']));
				$uname = $resultuser[0]['user'];
				$updatedat = date("m/d/y", strtotime($row['updated_at']));
                //Check for anonymous users
                if($uname == "" || $uname == null){
					$user = "Anonymous";
				}else{
					$user = $uname;
				}
				//Store all note vars
			    $notedata[$notecount]['id'] = $row['id'];
			    $notedata[$notecount]['body'] = $row['body'];
			    $notedata[$notecount]['updated_at'] = $updatedat;
			    $notedata[$notecount]['note_id'] = $row['note_id'];
			    $notedata[$notecount]['user'] = $user;
			    $notedata[$notecount]['version'] = $row['version'];
				$notecount++;
			}
			//Set note data for template
			$f3->set('notedata',$notedata);
			$f3->set('notecount',$count);
	    	//Render template
	    	$template=new Template;
	        echo $template->render('post_note_history.html');
		}else if($type == "tag_history"){
			//Get tag history
			$result = $db->exec('SELECT tags, version, user_id, updated_at FROM '.$f3->get('tag_history_table').' WHERE id = ? AND active = \'1\' ORDER BY total_amount DESC',array(1=>$id));
			$count = count($result);			
			//Loop through tag set
			$tagdata = array();
			$tagcount = 0;
			foreach($result as $row){
				//Query for username
				$resultuser = $db->exec('SELECT user FROM '.$f3->get('user_table').' WHERE id = ?',array(1=>$row['user_id']));
				$uname = $resultuser[0]['user'];
				$updatedat = date("m/d/y", strtotime($row['updated_at']));
                //Check for anonymous users
                if($uname == "" || $uname == null){
					$user = "Anonymous";
				}else{
					$user = $uname;
				}
				//Store all tag vars
			    $tagdata[$tagcount]['tags'] = $row['tags'];
			    $tagdata[$tagcount]['updated_at'] = $updatedat;
			    $tagdata[$tagcount]['user'] = $user;
			    $tagdata[$tagcount]['version'] = $row['version'];
				$tagcount++;
			}
			//Set tag data for template
			$f3->set('tagdata',$tagdata);
			$f3->set('tagcount',$count);
	    	//Render template
	    	$template=new Template;
	        echo $template->render('tag_history.html');
        }else if($type == "revert"){
			//Check if user has permission to revert
			if($userc->gotpermission('reverse_notes')){
					$result1 = $db->exec('SELECT updated_at, x, y, width, height, body, user_id, ip FROM '.$f3->get('note_history_table').' WHERE id = ? AND post_id = ? AND version = ?',array(1=>$id,2=>$pid,3=>$version));
					$update = $db->exec('UPDATE '.$f3->get('note_table').' SET updated_at = ?, x = ?, y = ?, width = ?, height = ?, body = ?, user_id = ?, ip = ?, version = ? WHERE id = ? AND post_id = ?',array(1=>$result1[0]['updated_at'],2=>$result1[0]['x'],3=>$result1[0]['y'],4=>$result1[0]['width'],5=>$result1[0]['height'],6=>$result1[0]['body'],7=>$result1[0]['user_id'],8=>$result1[0]['ip'],9=>$version,10=>$id,11=>$pid));
					$delete = $db->exec('DELETE FROM '.$f3->get('note_history_table').' WHERE id = ? AND post_id = ? AND version >= ? ',array(1=>$id,1=>$pid,1=>$version));
					//Redirect to post
					$f3->reroute('/post/view/'.$pid);
			}else{
				//Redirect to post
				$f3->reroute('/post/view/'.$pid);
			}
		}else if($type == "revert_tags"){
			//Check if user has permission to revert
			if($userc->gotpermission('reverse_tags')){
					$misc = new misc();
					$result1 = $db->exec('SELECT t1.tags, t2.tags AS t2_tags FROM '.$f3->get('tag_history_table').' AS t1 JOIN '.$f3->get('post_table').' AS t2 ON t2.id = ? WHERE t1.id = ? AND t1.version = ?',array(1=>$id,2=>$id,3=>$version));
					$tmp = explode(" ",$misc->mb_trim($result1[0]['t2_tags']));
					foreach($tmp as $current){
						$tclass->deleteindextag($current);							
					}
					$tmp = explode(" ",$misc->mb_trim($result1[0]['tags']));
					foreach($tmp as $current){
						$tclass->addindextag($current);							
					}
					$update1 = $db->exec('UPDATE '.$f3->get('post_table').' SET tags = ?, recent_tags = ?, tags_version = ? WHERE id = ?',array(1=>$result1[0]['tags'],2=>$result1[0]['tags'],3=>$version,4=>$id));
					$update2 = $db->exec('UPDATE '.$f3->get('tag_history_table').' SET active=\'0\' WHERE id = ? AND version > ?',array(1=>$id,2=>$version));
					//Redirect to post
					$f3->reroute('/post/view/'.$pid);
			}else{
				//Redirect to post
				$f3->reroute('/post/view/'.$pid);
			}
		}
	}else{
		//Redirect to all posts
		$f3->reroute('/post/all/'.$pid);
	}
?>