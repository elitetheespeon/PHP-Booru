<?php
	class post{
		function show($id){
			global $f3, $db;
            //Level check for DNP
            if ($f3->get('checked_user_group') >= $f3->get('dnp_level')){
                $dnp = "";
            }else{
			    if ($f3->get('checked_user_id') !== null){
			    	$dnp = "AND (dnp=0 AND status = 'active' OR owner = ".$f3->get('checked_user_id').')';
			    }else{
			    	$dnp = "AND (dnp=0 AND status = 'active')";
			    }
            }
			$result = $db->exec('SELECT * FROM '.$f3->get('post_table').' WHERE id = ? '.$dnp.' LIMIT 1',array(1=>$id));
			$numrows = count($result);
			if($numrows == 0){
				return false;
			}else{
			    return $result;
			}
		}

		function get_thumbnail($ext,$hash){
			global $f3;
			//Check extension
			switch ($ext) {
			    //Send flash placeholder
			    case 'swf':
			        return $f3->get('site_url').'theme/'.$f3->get('theme').'/images/flash.gif';
			        break;
			    //Send video placeholder
			    case 'webm':
			        return $f3->get('site_url').'theme/'.$f3->get('theme').'/images/video.gif';
			        break;
			    //Send video placeholder
			    case 'mp4':
			        return $f3->get('site_url').'theme/'.$f3->get('theme').'/images/video.gif';
			        break;
			    //Send thumbnail or failsafe placeholder
			    default:
			        //Check if thumbnail exists
			        if(file_exists('thumbnails/'.$hash.'.jpg') && filesize('thumbnails/'.$hash.'.jpg') > 0){
			    		//Send thumbnail
			    		return $f3->get('thumbnail_url').'/'.$hash.'.jpg';
			        }else{
			        	//No thumbnail, send failsafe placeholder
			        	return $f3->get('site_url').'theme/'.$f3->get('theme').'/images/other.gif';
			        }
			}
		}

		function get_tags($id){
			global $f3, $db;
			$result = $db->exec('SELECT * FROM '.$f3->get('poststags_table').' LEFT JOIN '.$f3->get('tags_table').' ON '.$f3->get('poststags_table').'.tag_id='.$f3->get('tags_table').'.id WHERE '.$f3->get('poststags_table').'.post_id = ? ORDER BY '.$f3->get('tags_table').'.name',array(1=>$id));
			$numrows = count($result);
			if($numrows == 0){
				return false;
			}else{
			    return $result;
			}
		}
		
		function get_notes($id){
			global $f3, $db;
			$result = $db->exec('SELECT * FROM '.$f3->get('note_table').' WHERE post_id = ?',array(1=>$id));
			$numrows = count($result);
			if($numrows == 0){
				return false;
			}else{
			    return $result;
			}
		}
		
		function prev_next($id){
			global $f3, $db;
			$result = $db->exec('SELECT (SELECT id FROM '.$f3->get('post_table').' WHERE id < ? ORDER BY id DESC LIMIT 1) as prev, (SELECT id FROM '.$f3->get('post_table').' WHERE id > ? ORDER BY id ASC LIMIT 1) as next',array(1=>$id,2=>$id));
			if(count($result) > 0){
				return ['prev' => $result[0]['prev'], 'next' => $result[0]['next']];
			}else{
			    return ['prev' => null, 'next' => null];
			}
		}
		
		function has_children($id){
			global $f3, $db;
			$result = $db->exec('SELECT * FROM '.$f3->get('post_table').' WHERE parent = ? LIMIT 1',array(1=>$id));
			$numrows = count($result);
			if($numrows == 0){
				return false;
			}else{
			    return true;
			}
		}
		
		function index_count($current){
			global $f3, $db;
			$current = htmlentities($current, ENT_QUOTES, "UTF-8", FALSE);
			$result = $db->exec('SELECT index_count FROM '.$f3->get('tag_index_table').' WHERE tag = ? LIMIT 1',array(1=>$current));
			$numrows = count($result);
			if($numrows == 0){
				return false;
			}else{
			    return $result;
			}
		}
		function get_favorites($id){
			global $f3, $db;
			$result = $db->exec('SELECT u.id, u.user FROM '.$f3->get('favorites_table').' as f LEFT JOIN '.$f3->get('user_table').' as u ON (f.user_id=u.id) WHERE f.post_id = ?',array(1=>$id));
			$numrows = count($result);
			if($numrows == 0){
				return false;
			}else{
			    return $result;
			}
		}
		function has_favorited($id){
			global $f3, $db;
			$result = $db->exec('SELECT * FROM '.$f3->get('favorites_table').' WHERE post_id = ? AND user_id = ?',array(1=>$id,2=>$f3->get('checked_user_id')));
			$numrows = count($result);
			if($numrows == 0){
				return false;
			}else{
			    return $result;
			}
		}
		function is_flagged($id){
			global $f3, $db;
			$result = $db->exec('SELECT reason FROM '.$f3->get('flagged_post_table').'  WHERE post_id = ?',array(1=>$id));
			$numrows = count($result);
			if($numrows == 0){
				return false;
			}else{
			    return $result[0]["reason"];
			}
		}
		function get_dimensions($id){
			global $f3, $db;
			$result = $db->exec('SELECT width,height FROM '.$f3->get('post_table').' WHERE id = ? LIMIT 1',array(1=>$id));
			$numrows = count($result);
			if($numrows == 0){
				return false;
			}else{
			    return $result;
			}
		}
        function is_pooled($id){
            global $f3, $db;
            $pdata = $db->exec('SELECT p.id, p.name, pp.sequence FROM '.$f3->get('pool_post_table').' AS pp LEFT JOIN '.$f3->get('pool_table').' AS p ON (pp.pool_id = p.id) WHERE post_id = ?',array(1=>$id));
            $numrows = count($pdata);
            if ($numrows == 0){
                return false;
            }else{
                $return = array();
                foreach ($pdata as $pd){
                    $result = $db->exec('SELECT (SELECT post_id FROM pools_posts WHERE pool_id = ? AND sequence < ? ORDER BY sequence DESC LIMIT 1) AS prev_post, (SELECT post_id FROM pools_posts WHERE pool_id = ? AND sequence > ? ORDER BY sequence ASC LIMIT 1) AS next_post',array(1=>$pd['id'],2=>$pd['sequence'],3=>$pd['id'],4=>$pd['sequence']));
                    foreach ($result as $pn){
                    	$return[] = array('p_id' => $pd['id'], 'p_name' => $pd['name'], 'p_next' => $pn['next_post'], 'p_prev' => $pn['prev_post']);
                    }
                }
                return $return;
            }
        }
        function pool_list($uid){
			global $f3, $db;
			$result = $db->exec('SELECT id, name FROM '.$f3->get('pool_table').' WHERE is_public = 1 OR user_id = ?',array(1=>$uid));
			$numrows = count($result);
			$return = array();
			if($numrows == 0){
				return false;
			}else{
			    foreach ($result as $row){
			    	$return[] = array('p_id' => $row['id'], 'p_name' => $row['name']);
			    }
			    return $return;
			}
        }
        function validate_post($id){
			global $f3, $db;
            //Level check for DNP
            if ($f3->get('checked_user_group') >= $f3->get('dnp_level')){
                $dnp = "";
            }else{
                $dnp = "AND dnp = 0 AND status = 'active'";
            }
            $result = $db->exec('SELECT id FROM '.$f3->get('post_table')." WHERE id = ? $dnp LIMIT 1",array(1=>$id));
            $numrows = count($result);
            if ($numrows == 0){
                return false;
            }else{
                return true;
            }
        }
        function validate_pool($id){
			global $f3, $db;
            //Group permissions check
            if ($f3->get('checked_user_group') >= 40){
                $result = $db->exec('SELECT id FROM '.$f3->get('pool_table')." WHERE id = ? LIMIT 1",array(1=>$id));
            }else{
                $result = $db->exec('SELECT id FROM '.$f3->get('pool_table')." WHERE id = ? AND (is_public = 1 OR user_id = ?) LIMIT 1",array(1=>$id,2=>$f3->get('checked_user_id')));
            }
			$numrows = count($result);
            if($numrows == 0){
                return false;
            }else{
                return true;
            }
        }
        function get_random(){
			global $f3, $db;
            //Level check for DNP
            if ($f3->get('checked_user_group') >= $f3->get('dnp_level')){
                $dnp = "";
                $dnp2 = "";
            }else{
                $dnp = " WHERE dnp=0 AND status = 'active'";
                $dnp2 = " AND dnp=0 AND status = 'active'";
            }
            //Run query and check
			$result = $db->exec('SELECT count(id) FROM '.$f3->get('post_table').$dnp);
			$numrows = $result[0]['count(id)'];
			if($numrows < 1){
				return false;
			}
			$valid_post_found = false;
			//Process cookie with blacklist
			if(isset($_COOKIE['tag_blacklist'])){
				$blacklist = str_replace('&#92;',"\\",str_replace("&#039;","'",str_replace("%20"," ",$_COOKIE['tag_blacklist'])));
			}else{
				$blacklist = "";
			}
			//Check for 'safe mode' cookie
			if(isset($_COOKIE['safe_only'])){
				$blacklist = explode(" ",$blacklist);
				if(!in_array("rating:explicit",$blacklist))
					$blacklist[] = "rating:explicit";
				if(!in_array("rating:questionable",$blacklist))
					$blacklist[] = "rating:questionable";
				$blacklist = implode(" ",$blacklist);
			}
			//Prevent idiots from getting stuck in an infinity loop
			if(mb_strpos($blacklist,'rating:explicit',0,'UTF-8') !== false && mb_strpos($blacklist,'rating:questionable',0,'UTF-8') !== false && mb_strpos($blacklist,'rating:safe',0,'UTF-8') !== false){
				$override = true;
			}else{
				$override = false;
			}
			//Looks for a post with an acceptable rating to prevent eternal loop on missing ratings in combination with banned existing ratings
			$i = 0;
			$blacklist_array = explode(" ",$blacklist);
			
			if ($blacklist !== ""){
				while(!$valid_post_found){
					$rand = mt_rand(1,$numrows);	
					$result = $db->exec('SELECT id, rating, tags FROM '.$f3->get('post_table').' WHERE id = '.$rand.$dnp2.' LIMIT 1');
					$id = $result[0]['id'];
					if(strpos($blacklist,'rating:'.strtolower($row['rating']),0) === false || $override || $i > 20){
						$valid_post_found = true;
					}
					if($i < 20 && $valid_post_found == true){
						foreach($blacklist_array as $current){
							if(in_array($current,explode(" ",$row['tags'])) !== false){
								return $id;
								$valid_post_found = false;
								break;
							}
						}
					}
					$i++;
				}
			}else{
				$rand = mt_rand(1,$numrows);	
				$result = $db->exec('SELECT id, rating, tags FROM '.$f3->get('post_table').' WHERE id = '.$rand.$dnp2.' LIMIT 1');
				$id = $result[0]['id'];
				return $id;			
			}
        }
        function edit_post(){
			global $f3, $db;
			$logger = new logger();
			$misc = new misc();
			$user = new user();
			$tagc = new tag();
			$webhook = new webhook();
			$ip = $_SERVER['REMOTE_ADDR'];	
			
			//Make sure pconf was posted
			if($_POST['pconf'] !="1"){
				$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'EDIT_POST', 'MISSING_PCONF');
				$template=new Template;
			    echo $template->render('no_permission.html');
				exit();
			}
			
			//Check if user is banned
			if($user->banned_ip($ip)){
			    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'EDIT_POST', 'BANNED');
				$template=new Template;
			    echo $template->render('no_permission.html');
				exit();
			}
			
			//Check if user is logged in
			if(!$user->check_log()){
			    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'EDIT_POST', 'NOT_LOGGED_IN');
				$template=new Template;
			    echo $template->render('no_permission.html');
				exit();
			}
			
			$user_id = $f3->get('checked_user_id');
			$id = $_POST['id'];
			
			//New tags submitted by user
			$ttags = array();
			foreach ($_POST['tags'] as $atag){
			    //Decode and clean tag
			    $ntag = mb_strtolower(str_replace('%','',$f3->decode($atag)));
			    
                //Get tag id
                $tagid = $tagc->tag_lookup($ntag);
                
                //Check if tag lookup returned valid tag
                if($tagid['id']){
                	//Add tag to array
                	$ttags[$tagid['id']] = $tagid['name'];
                }
			}
			
			//Clean the new tags up
			asort($ttags);
			$tags = implode(" ",$ttags);
			$tags = $misc->mb_trim(str_replace("  ","",$tags));
			if(substr($tags,0,1) != " ")
				$tags = " $tags";
			if(substr($tags,-1,1) != " ")
				$tags = "$tags ";
			
			//Get the current tags from database
			$resultname = $db->exec('SELECT name FROM '.$f3->get('poststags_table').' AS pt LEFT JOIN '.$f3->get('tags_table').' AS t ON pt.tag_id = t.id WHERE post_id = ?',array(1=>$id));
			$tagname = $resultname[0]["name"];
			
			//Create array with original tags
			$tagsold = array();
			$i = 0;
			foreach($resultname as $t){
			    $tagsold['old'.$i++] = $t["name"];
			}
			
			//Create array with new tags
			$tagsnewa = $ttags;
			$i = 0;
			foreach($tagsnewa as $value){
			    $tagsnew['new'.$i++] = $value;
			}
			
			//Check if tags have been removed
			$removed = (array_diff($tagsold,$tagsnew));
			
			//Remove tags from post that were removed
			if(!empty($removed)){
			    foreach($removed as $tag){                   
				    $tagid = $tagc->tag_lookup($tag);
			        $delete = $db->exec('DELETE FROM '.$f3->get('poststags_table').' WHERE post_id = ? AND tag_id = ?',array(1=>$id,2=>$tagid['id']));
			        $tagc->deleteindextag($tagid['id']);
			    }
			}    
			
			//Check if tags have been added
			$added = array();
			$addeda = array_unique($tagsold+$tagsnew,SORT_STRING);
			foreach($addeda as $key => $value) {
			        if(preg_match('/^new/',$key)) {
			                $added[$key] = $value;
			        }
			}
			
			//Add tags to post that were added
			if(!empty($added)){
			    foreach($added as $tag){
			        if ($tagid = $tagc->tag_lookup($tag)){
			            $insert1 = $db->exec('INSERT INTO '.$f3->get('poststags_table').' (post_id, tag_id) VALUES(?, ?)',array(1=>$id,2=>$tagid['id']));
			            $tagc->addindextag($tagid['id']);    
			        }
			    }
			}
			
			//Tag array clean up for logs and tag list on post
			$cleantags = array();
			foreach($ttags as $tag){
			    if(strpos($tag,'artist:') !== false || strpos($tag,'art:') !== false){
			        $tag = str_replace("artist:", "", $tag);
			        $tag = str_replace("art:", "", $tag);
			    }elseif(strpos($tag,'copyright:') !== false || strpos($tag,'copy:') !== false){
			        $tag = str_replace("copyright:", "", $tag);
			        $tag = str_replace("copy:", "", $tag);				        
			    }elseif(strpos($tag,'character:') !== false || strpos($tag,'char:') !== false){
			        $tag = str_replace("character:", "", $tag);
			        $tag = str_replace("char:", "", $tag);				        
			    }elseif(strpos($tag,'species:') !== false || strpos($tag,'spec:') !== false){
			        $tag = str_replace("species:", "", $tag);
			        $tag = str_replace("spec:", "", $tag);				        
			    }
			    $cleantags[] = $tag;
			}
			$cleantaglist = implode(" ",$cleantags);
			
			//Update tag history and the user tag edit count if tags were edited
			if (!empty($added) || !empty($removed)){
			    $curversion = $db->exec('SELECT version FROM '.$f3->get('tag_history_table').' WHERE id = ? ORDER BY version DESC LIMIT 1',array(1=>$id));
			    $insert2 = $db->exec('INSERT INTO '.$f3->get('tag_history_table').' (id, tags, version, user_id, updated_at, ip) VALUES(?, ?, ?, ?, NOW(), ?)',array(1=>$id,2=>$cleantaglist,3=>$curversion[0]["version"]+1,4=>$user_id,5=>$ip));
			    $update1 = $db->exec('UPDATE '.$f3->get('user_table').' SET tag_edit_count = tag_edit_count + 1 WHERE id = ?',array(1=>$id));
			}
			
			//Grab the other information sent and clean them up
			$title = $_POST['title'];
			$rating = $_POST['rating'];
			$source = stripslashes(htmlentities($_POST['source'], ENT_QUOTES, "UTF-8", FALSE));
			$parent = $_POST['parent'];
			$description = $_POST['description'];
			//Check if parent post is actually valid
			if(is_numeric($parent)){
			    $parentresult = $db->exec('SELECT id FROM '.$f3->get('post_table').' WHERE id = ?',array(1=>$parent));
			    $parentcheck = count($parentresult);
			    if($parentcheck !== 0){
			        $parent = $parent;    
			    }else{
			        $parent = 0;
			    }
			}else{
			    $parent = 0;
			}
			$update2 = $db->exec('UPDATE '.$f3->get('post_table').' SET title = ?, description = ?, tags = ?, rating = ?, source = ?, parent = ? WHERE id = ?',array(1=>$title,2=>$description,3=>$cleantaglist,4=>$rating,5=>$source,6=>$parent,7=>$id));
			$logger->log_action($user_id, $ip, 'EDIT_POST', 'SUCCESS', $id);

			//Check if webhook array is defined for any tags
			if(count($f3->get('webhooks.tags')) !== 0){
				//Loop through defined webhook tags
				foreach($f3->get('webhooks.tags') as $webhook_tag => $webhook_url){
					//Check if webhook tag was found in tags
					if(array_search($webhook_tag, $cleantags) !== false){
						//Get user avatar info
						$avatarinfo = $userc->get_avatar($f3->get('checked_user_id'));
						
						//Add post data to webhook request
						$f3->set('webhook_data_tg.embeds.0.author_name', $f3->get('checked_username'));
						$f3->set('webhook_data_tg.embeds.0.author_id', $f3->get('checked_user_id'));
						$f3->set('webhook_data_tg.embeds.0.author_avatar_md5', $avatarinfo['md5']);
						$f3->set('webhook_data_tg.embeds.0.author_avatar_ext', $avatarinfo['file_ext']);
						$f3->set('webhook_data_tg.embeds.0.post_id', $id);
						$f3->set('webhook_data_tg.embeds.0.post_rating', $rating);
						$f3->set('webhook_data_tg.embeds.0.post_title', $title);
						$f3->set('webhook_data_tg.embeds.0.post_description', $description);
						$f3->set('webhook_data_tg.embeds.0.post_tags', $cleantaglist);
						$f3->set('webhook_data_tg.embeds.0.post_thumbnail_url', $post->get_thumbnail($post_data[0]['ext'], $post_data[0]['hash']));
						$f3->set('webhook_data_tg.embeds.0.post_date', gmdate('Y-m-d\TH:i:s', time()));

						//Process webhook data and save
						$webhook_data = $webhook->process_data('tags.'.$webhook_tag, $f3->get('webhook_data_tg'));
						
						//Push data to webhook
						$webhook->push('tags.'.$webhook_tag, json_encode($webhook_data));
					}
				}
			}
			
			$f3->reroute('/post/view/'.$id);
        }
		function note_save(){
			global $f3, $db;
			//Load required classes
			$user = new user();
			$logger = new logger();
			$ip = $_SERVER['REMOTE_ADDR'];
			
			//Check if user is banned
			if($user->banned_ip($ip)){
			    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'NOTE_SAVE', 'BANNED');
				$template=new Template;
			    echo $template->render('no_permission.html');
				exit();
			}
			
			//Check if user is logged in and has access to alter notes
			if(!$user->check_log() || !$user->gotpermission('alter_notes')){
			    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'NOTE_SAVE', 'NO_ACCESS');
				$template=new Template;
			    echo $template->render('no_permission.html');
				exit();
			}
			
			//Save userid to var
			$user_id = $f3->get('checked_user_id');
			
			//Check if info passed is good and process
			if(is_numeric($f3->get('PARAMS.id')) && is_numeric($_GET['note']['post_id']) && is_numeric($_GET['note']['x']) && is_numeric($_GET['note']['y']) && is_numeric($_GET['note']['width']) && is_numeric($_GET['note']['height'])){
				//Store data passed
				$id = $f3->get('PARAMS.id');
				$x = $_GET['note']['x'];
				$y = $_GET['note']['y'];
				$width = $_GET['note']['width'];
				$height = $_GET['note']['height'];
				$post_id = $_GET['note']['post_id'];
				$angle = $_GET['note']['angle'];
				//Convert body characters to HTML equivalent
				$body = htmlentities($_GET['note']['body'], ENT_QUOTES,'UTF-8', FALSE);
				$body = str_replace("&lt;tn&gt;","<tn>", $body);
				$body = str_replace("&lt;/tn&gt;","</tn>", $body);
				$body = str_replace("&lt;br /&gt;","<br />",$body);
				$body = str_replace("&lt;br&gt;","<br />",$body);
				$body = str_replace("&lt;b&gt;","<b>",$body);
				$body = str_replace("&lt;/b&gt;","</b>",$body);
				$body = str_replace("&lt;i&gt;","<i>",$body);
				$body = str_replace("&lt;/i&gt;","</i>",$body);
				//Get any notes for post with given id
			    $result = $db->exec('SELECT COUNT(id) as count FROM '.$f3->get('note_table').' WHERE post_id = ? AND id = ?',array(1=>$post_id,2=>$id));
			    //Check if there are any notes for the post and id is a valid number
				if($result[0]["count"] == 1 && $id > 0){
					//Note exists, so it is being modified
					//Get current note info
					$noteinfo = $db->exec('SELECT x, y, width, height, x, y, body, created_at, updated_at, ip, version, user_id, id FROM '.$f3->get('note_table').' WHERE id = ? AND post_id = ? LIMIT 1',array(1=>$id,2=>$post_id));
					//Insert note history
					$insert = $db->exec('INSERT INTO '.$f3->get('note_history_table').' (x, y, width, height, angle, body, created_at, updated_at, ip, user_id, version, post_id, note_id) VALUES(?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?)',array(1=>$x,2=>$y,3=>$width,4=>$height,5=>$angle,6=>$body,7=>$noteinfo[0]['created_at'],8=>$ip,9=>$f3->get('checked_user_id'),10=>($noteinfo[0]['version']+1),11=>$post_id,12=>$noteinfo[0]['id']));
			        //Update note
					$update = $db->exec('UPDATE '.$f3->get('note_table').' SET x = ?, y = ?, width = ?, height = ?, angle = ?, body = ?, updated_at=NOW(), user_id = ?, ip = ?, version=version + 1 WHERE post_id = ? AND id = ?',array(1=>$x,2=>$y,3=>$width,4=>$height,5=>$angle,6=>$body,7=>$f3->get('checked_user_id'),8=>$ip,9=>$post_id,10=>$id));
					$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'NOTE_SAVE', 'SUCCESS_EDIT', $id);
				}else{
					//Note does not exist, so it is being added
					//Insert note
					$insert = $db->exec('INSERT INTO '.$f3->get('note_table').' (x, y, width, height, angle, body, post_id, ip, user_id, created_at, updated_at) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())',array(1=>$x,2=>$y,3=>$width,4=>$height,5=>$angle,6=>$body,7=>$post_id,8=>$ip,9=>$f3->get('checked_user_id')));
			        //Get note id
			        $lastid = $db->lastInsertId();
					//Insert note history
					$noteinfo = $db->exec('INSERT INTO '.$f3->get('note_history_table').' (x, y, width, height, angle, body, created_at, updated_at, ip, user_id, version, post_id, note_id) VALUES(?, ?, ?, ?, ?, ?, NOW(), NOW(), ?, ?, \'1\', ?, ?)',array(1=>$x,2=>$y,3=>$width,4=>$height,5=>$angle,6=>$body,7=>$ip,8=>$f3->get('checked_user_id'),9=>$post_id,10=>$lastid));
			        $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'NOTE_SAVE', 'SUCCESS_ADD', $lastid);
			        //Send data back for javascript
			        print $lastid.":".$id;
				}
			}			
		}
		
		function soft_delete(){
			global $f3, $db;
			//Load required classes
			$user = new user();
			$logger = new logger();
			$ip = $_SERVER['REMOTE_ADDR'];
			
			//Check if user is logged in and can delete posts
			if(!$user->check_log() || !$user->gotpermission('delete_posts')){
			    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'REMOVE', 'NO_ACCESS');
				$template=new Template;
			    echo $template->render('no_permission.html');
				exit();
			}
			
			//Check if posted data is valid
			if(isset($_POST['id']) && is_numeric($_POST['id']) && isset($_POST['reason'])){
			    //Make sure post ID exists
			    $checkidres = $db->exec('SELECT COUNT(*) as count FROM '.$f3->get('post_table').' WHERE id = ?',array(1=>$_POST['id']));
			    $numrows = $checkidres[0]["count"];
			
			    //Check if post id is valid
				if($numrows !== 0){
			        //Make sure the post is not already deleted
			        $getdeleted = $db->exec('SELECT status FROM '.$f3->get('post_table').' WHERE id = ?',array(1=>$_POST['id']));
			        $isdeleted = $getdeleted[0]["status"];
			
			        //Post already deleted
			        if ($isdeleted == "deleted"){
			            $f3->reroute('/post/view/'.$_POST['id']);
			        }
			
			        //Check if there is already a flag reason
			        $getflagged = $db->exec('SELECT COUNT(*) as count FROM '.$f3->get('flagged_post_table').' as f LEFT JOIN '.$f3->get('post_table').' as p ON (p.id=f.post_id) WHERE p.id = ?',array(1=>$_POST['id']));
			        $flagreason = $getflagged[0]["count"];
			        
			        //Start the changes
			        if ($flagreason !== "0"){
			            //Post has flag reason, update
			            $update1 = $db->exec('UPDATE '.$f3->get('flagged_post_table').' SET created_at = NOW(), reason = ?, user_id = ? WHERE post_id = ?',array(1=>$_POST['reason'],2=>$f3->get('checked_user_id'),3=>$_POST['id']));
			            
			            //Update post status
			            $setstatus = "deleted";
			            $update2 = $db->exec('UPDATE '.$f3->get('post_table').' SET status = ? WHERE id = ?',array(1=>$setstatus,2=>$_POST['id']));
			
			            //Success, redirect back to post
			            $logger->log_action($f3->get('checked_user_id'), $ip, 'SOFTDELETE_POST', 'SUCCESS', $_POST['id']);                
			            $f3->reroute('/post/view/'.$_POST['id']);
			        }else{
			            //Post does not have flag reason, insert
			            $resolved = 1;
			            $insert = $db->exec('INSERT INTO '.$f3->get('flagged_post_table').' (created_at, post_id, reason, user_id, is_resolved) VALUES(NOW(), ?, ?, ?, ?)',array(1=>$_POST['id'],2=>$_POST['reason'],3=>$f3->get('checked_user_id'),4=>$resolved));
			
			            //Update post status
			            $setstatus = "deleted";
			            $update = $db->exec('UPDATE '.$f3->get('post_table').' SET status = ? WHERE id = ?',array(1=>$setstatus,2=>$_POST['id']));
			
			            //Success, redirect back to post
			            $logger->log_action($f3->get('checked_user_id'), $ip, 'SOFTDELETE_POST', 'SUCCESS', $_POST['id']);                              
			            $f3->reroute('/post/view/'.$_POST['id']);
			        }
				}else{
			        //Invalid post id passed, redirect
			        $f3->reroute('/post/all/');
			    }
			}
		}
	
		function soft_restore(){
			global $f3, $db;
			//Load required classes
			$user = new user();
			$logger = new logger();
			$ip = $_SERVER['REMOTE_ADDR'];
			
			//Check if user is banned
			if($user->banned_ip($ip)){
			    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'EDIT_POOL', 'BANNED');
				$template=new Template;
			    echo $template->render('no_permission.html');
				exit();
			}
			
			//Check if user is logged in and can delete posts
			if(!$user->check_log() || !$user->gotpermission('delete_posts')){
			    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'EDIT_POOL', 'NOT_LOGGED_IN');
				$template=new Template;
			    echo $template->render('no_permission.html');
				exit();
			}
			
			//Check if a valid id was sent
			if(isset($_POST['id']) && is_numeric($_POST['id'])){
			    //Make sure post ID exists
			    $postcheck = $db->exec('SELECT COUNT(id) as count FROM '.$f3->get('post_table').' WHERE id = ?',array(1=>$_POST['id']));
			    $numrows = $postcheck[0]["count"];
				$id = $_POST['id'];
				
			    //Check if post id is valid
				if($numrows !== 0){
			        //Make sure the post is deleted
			        $deletecheck = $db->exec('SELECT status FROM '.$f3->get('post_table').' WHERE id = ?',array(1=>$_POST['id']));
			        $isdeleted = $deletecheck[0]["status"];
			
			        //Check if post is deleted
			        if ($isdeleted !== "deleted"){
			            //Post is not deleted, reroute
			            $f3->reroute('/post/view/'.$id);
			        }
			
			        //Check if there is already a flag reason
			        $flagcheck = $db->exec('SELECT COUNT(p.id) as count FROM '.$f3->get('flagged_post_table').' as f LEFT JOIN '.$f3->get('post_table').' as p ON (p.id=f.post_id) WHERE p.id = ?',array(1=>$_POST['id']));
			        $flagreason = $flagcheck[0]["count"];
			        
			        //Start the changes
			        if ($flagreason !== 0){	
			            //Post has flag reason, delete flag reason
			            $delete = $db->exec('DELETE FROM '.$f3->get('flagged_post_table').' WHERE post_id = ?',array(1=>$_POST['id']));
			            
			            //Update post status to active
			            $setstatus = "active"; 
			            $update = $db->exec('UPDATE '.$f3->get('post_table').' SET status = ? WHERE id = ?',array(1=>$setstatus,2=>$_POST['id']));
			
			            //Success, we are out like trout
			            $logger->log_action($f3->get('checked_user_id'), $ip, 'SOFTRESTORE_POST', 'SUCCESS', $_POST['id']);
			            $f3->reroute('/post/view/'.$id);
			        }else{
			            //Post does not have flag reason, update flag reason
			            $setstatus = "active";
			            $update = $db->exec('UPDATE '.$f3->get('post_table').' SET status = ? WHERE id = ?',array(1=>$setstatus,2=>$_POST['id']));
			            
			            //Success, we are out like trout
			            $logger->log_action($f3->get('checked_user_id'), $ip, 'SOFTRESTORE_POST', 'SUCCESS', $_POST['id']);
			            $f3->reroute('/post/view/'.$id);
			        }
				}else{
			        //Something went wrong
			        $f3->reroute('/post/all/');
			    }
			}
		}
		
		function add_to_pool(){
			global $f3, $db;
			//Load required classes
			$user = new user();
			$logger = new logger();
			$ip = $_SERVER['REMOTE_ADDR'];
			
			//Check if user is banned
			if($user->banned_ip($ip)){
			    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'ADD_TO_POOL', 'BANNED');
				$template=new Template;
			    echo $template->render('no_permission.html');
				exit();
			}
			
			//Check if user is logged in and can edit posts
			if(!$user->check_log() || !$user->gotpermission('edit_posts')){
			    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'ADD_TO_POOL', 'NOT_LOGGED_IN');
				$template=new Template;
			    echo $template->render('no_permission.html');
				exit();
			}
			
			//Check if posted data is valid
			if (isset($_POST['postid']) && is_numeric($_POST['postid']) && isset($_POST['poolid']) && is_numeric($_POST['poolid'])){
			    //Make sure post ID exists
			    $postcheck = $db->exec('SELECT COUNT(id) as count FROM '.$f3->get('post_table').' WHERE id = ?',array(1=>$_POST['postid']));
			    $postnumrows = $postcheck[0]["count"];
				//Check that post exists
				if($postnumrows !== 0){
			        //Make sure pool ID exists
			        $poolcheck = $db->exec('SELECT COUNT(id) as count FROM '.$f3->get('pool_table').' WHERE id = ?',array(1=>$_POST['poolid']));
			        //Get pool info
			        $pooldata = $db->exec('SELECT p.id as pid, pp.id as ppid, pp.sequence FROM '.$f3->get('pool_post_table').' AS pp LEFT JOIN '.$f3->get('pool_table').' AS p ON (pp.pool_id = p.id) WHERE p.id = ? ORDER BY pp.sequence DESC LIMIT 1',array(1=>$_POST['poolid']));
			        //Store vars
			        $poolnumrows = count($poolcheck);
			        $prev_id = $pooldata[0]["ppid"];
			        $sequence = $pooldata[0]["sequence"];
			        //Check if we have a valid pool id
			        if($poolnumrows !== 0){
			            //Get info to check if post is in pool
			            $postpoolcheck = $db->exec('SELECT COUNT(id) as count FROM '.$f3->get('pool_post_table').' WHERE post_id = ? AND pool_id = ?',array(1=>$_POST['postid'],2=>$_POST['poolid']));
			            $postpoolcount = $postpoolcheck[0]["count"];
			            //Check if post is already part of pool
			            if ($postpoolcount == "0"){
				            //Generate sequence number
				            if ($sequence == ""){
				                $nextseq = 0;
				            }else{
				                $nextseq = $sequence + 1;
				            }
				            //Add post to pool
				            $insert = $db->exec('INSERT INTO '.$f3->get('pool_post_table').' (sequence, pool_id, post_id) VALUES (?, ?, ?)',array(1=>$nextseq,2=>$_POST['poolid'],3=>$_POST['postid']));
				            $update = $db->exec('UPDATE '.$f3->get('pool_table').' SET post_count = post_count + 1 WHERE id = ?',array(1=>$_POST['poolid']));
				            $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'ADD_TO_POOL', 'SUCCESS', $_POST['poolid']);
			            }else{
			            	$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'ADD_TO_POOL', 'POST_IN_POOL', $_POST['poolid']);
			            }
			        }
			    }
			    //We're done here, send back to post
			    $f3->reroute('/post/view/'.$_POST['postid']);
			}else{
			    //Invalid data, send back to post
			    $f3->reroute('/post/view/'.$_POST['postid']);
			}
		}
		
		function approve(){
			global $f3, $db;
			//Load required classes
			$user = new user();
			$logger = new logger();
			$ip = $_SERVER['REMOTE_ADDR'];
			
			//Check if user is logged in and has access
			if(!$user->check_log() || !$user->gotpermission('approve_posts')){
			    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'POST_APPROVE', 'NO_ACCESS');
				$template=new Template;
			    echo $template->render('no_permission.html');
				exit();
			}
			
			if(isset($_POST['id']) && is_numeric($_POST['id'])){
			    //Make sure post ID exists
			    $id = $_POST['id'];
			    $countresult = $db->exec('SELECT COUNT(*) as count FROM '.$f3->get('post_table').' WHERE id = ?',array(1=>$_POST['id']));
			    $numrows = $countresult[0]["count"];
			
				if($numrows !== 0){
			        //Make sure the post is pending
			        $pendingresult = $db->exec('SELECT status FROM '.$f3->get('post_table').' WHERE id = ?',array(1=>$_POST['id']));
			        $ispending = $pendingresult[0]["status"];
			
			        //Post not pending
			        if ($ispending !== "pending"){
			            $f3->reroute('/post/view/'.$id);
			        }
			
			        //Start the changes
			        $setstatus = "active";
			        $update = $db->exec('UPDATE '.$f3->get('post_table').' SET status = ? WHERE id = ?',array(1=>$setstatus,2=>$_POST['id']));
			        
			        $logger->log_action($f3->get('checked_user_id'), $ip, 'POST_APPROVE', 'SUCCESS', $_POST['id']);
			        $f3->reroute('/post/view/'.$id);
				}else{
			        $f3->reroute('/post/view/'.$id);
			    }
			}			
		}
		
		function change_status(){
			global $f3, $db;
			//Load required classes
			$user = new user();
			$logger = new logger();
			$ip = $_SERVER['REMOTE_ADDR'];
			
			//Check if user is logged in and has access to the admin panel
			if(!$user->check_log() || !$user->gotpermission('admin_panel')){
			    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'CHANGE_POST_STATUS', 'NOT_LOGGED_IN');
				$template=new Template;
			    echo $template->render('no_permission.html');
				exit();
			}
			
			//Check for valid id
			if(is_numeric($f3->get('PARAMS.id')) && $f3->get('PARAMS.id') != ""){
				//Store id
				$id = $f3->get('PARAMS.id');
				//Check for valid status
				if(isset($_POST['status']) && is_numeric($_POST['status']) && $_POST['status'] != ""){
			        //Check for status types
			        if($_POST['status'] == 1){
			            //Active status sent, get current status of post
			    		$postinfo = $db->exec('SELECT * FROM '.$f3->get('post_table').' WHERE id = ? LIMIT 1',array(1=>$id));
			    		//Check if post is valid
			    		if(count($postinfo) == "1"){
			                $update = $db->exec('UPDATE '.$f3->get('post_table').' SET status=\'active\' WHERE id = ?',array(1=>$id));
			                $logger->log_action($f3->get('checked_user_id'), $ip, 'CHANGE_POST_STATUS', 'SUCCESS_ACTIVE', $f3->get('PARAMS.id'));
			            }
			            //Send to post
			            $f3->reroute('/post/view/'.$id);
			        }elseif($_POST['status'] == 2){
			            //Deleted status sent, get current status of post
			    		$postinfo = $db->exec('SELECT * FROM '.$f3->get('post_table').' WHERE id = ? LIMIT 1',array(1=>$id));
			    		//Check if post is valid
			    		if(count($postinfo) == "1"){
			                $update = $db->exec('UPDATE '.$f3->get('post_table').' SET status=\'deleted\' WHERE id = ?',array(1=>$id));
			                $logger->log_action($f3->get('checked_user_id'), $ip, 'CHANGE_POST_STATUS', 'SUCCESS_DELETED', $f3->get('PARAMS.id'));
			            }
			            //Send to post
			            $f3->reroute('/post/view/'.$id);
			        }elseif($_POST['status'] == 3){
			            //Pending status sent, get current status of post
			    		$postinfo = $db->exec('SELECT * FROM '.$f3->get('post_table').' WHERE id = ? LIMIT 1',array(1=>$id));
			    		//Check if post is valid
			    		if(count($postinfo) == "1"){
			                $update = $db->exec('UPDATE '.$f3->get('post_table').' SET status=\'pending\' WHERE id = ?',array(1=>$id));
			                $logger->log_action($f3->get('checked_user_id'), $ip, 'CHANGE_POST_STATUS', 'SUCCESS_PENDING', $f3->get('PARAMS.id'));
			            }
			            //Send to post
			            $f3->reroute('/post/view/'.$id);
			        }
			    }
			}			
		}
		
		function change_dnp(){
			global $f3, $db;
			//Load required classes
			$user = new user();
			$logger = new logger();
			$ip = $_SERVER['REMOTE_ADDR'];
			
			//Check if user is logged in and has access to the admin panel
			if(!$user->check_log() || !$user->gotpermission('admin_panel')){
			    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'CHANGE_DNP', 'NOT_LOGGED_IN');
				$template=new Template;
			    echo $template->render('no_permission.html');
				exit();
			}
			
			//Check for valid id
			if(is_numeric($f3->get('PARAMS.id')) && $f3->get('PARAMS.id') != ""){
				//Store id
				$id = $f3->get('PARAMS.id');
				//Check for valid status
				if(isset($_POST['dnp']) && is_numeric($_POST['dnp']) && $_POST['dnp'] != ""){
			        //Check for dnp types
			        if($_POST['dnp'] == "0" || $_POST['dnp'] == "1"){
			            //Store dnp
			            $dnpid = $_POST['dnp'];
			    		//Get post info for id
			    		$postinfo = $db->exec('SELECT * FROM '.$f3->get('post_table').' WHERE id = ? LIMIT 1',array(1=>$id));
						//Check if post is valid
			    		if(count($postinfo) == "1"){
			                $update = $db->exec('UPDATE '.$f3->get('post_table').' SET dnp = ? WHERE id = ?',array(1=>$dnpid,2=>$id));
			                $logger->log_action($f3->get('checked_user_id'), $ip, 'CHANGE_DNP', 'SUCCESS', $id);
			            }
			            //Send to post
			            $f3->reroute('/post/view/'.$id);
			        }
			    }
			}
		}
		
		function edit_pool(){
			global $f3, $db;
			//Load required classes
			$misc = new misc();
			$user = new user();
			$post = new post();
			$logger = new logger();
			$ip = $_SERVER['REMOTE_ADDR'];
			$posts = array();
			
			//Check if user is banned
			if($user->banned_ip($ip)){
			    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'POOL_EDITOR', 'BANNED');
				$template=new Template;
			    echo $template->render('no_permission.html');
				exit();
			}
			
			//Check if user is logged in
			if(!$user->check_log()){
			    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'POOL_EDITOR', 'NOT_LOGGED_IN');
				$template=new Template;
			    echo $template->render('no_permission.html');
				exit();
			}
			
			//Check to make sure Pool ID is valid
			if ($post->validate_pool($_POST['poolid'])){
			   $poolid = $_POST['poolid'];
			}else{
			   $logger->log_action($f3->get('checked_user_id'), $ip, 'POOL_EDITOR', 'INVALID_ID', $_POST['poolid']);
			   echo "ERROR: INVALID ID OR NO ACCESS TO EDIT POOL";
			   exit();
			}
			    
			//Find out what mode we are in
			//Re-order mode
			if ($f3->get('PARAMS.mode') == "order"){
				$startnum = $_POST['page'];
			    $limit = $_POST['limit'];
			    //Clean up post IDs and put in array
			    $postids = explode(",",$_POST['ids']);
			    //Make sure each post is valid and add to array
			    foreach($postids as $postid){
			        if ($post->validate_post($postid)){
			            $posts[$startnum] = $postid;
			        }
			        $startnum++;
			    }
			    //Loop through results and save to database
			    foreach($posts as $postnum => $postid){
			        $update = $db->exec('UPDATE '.$f3->get('pool_post_table').' SET sequence = ? WHERE pool_id = ? AND post_id = ?',array(1=>$postnum,2=>$poolid,3=>$postid));
			        if (!$update) {
			            $logger->log_action($f3->get('checked_user_id'), $ip, 'EDIT_POOL', 'DB_ERROR', $_POST['poolid']);
			        }
			    }
			    //Finish dat shit
			    $logger->log_action($f3->get('checked_user_id'), $ip, 'EDIT_POOL_ORDER', 'SUCCESS', $_POST['poolid']);
				echo "OK";
			//Delete mode
			}elseif($f3->get('PARAMS.mode') == "delete"){
				$startnum = $_POST['page'];
			    //Clean up post IDs and put in array
			    $postids = explode(",",$_POST['ids']);
			    //Make sure each post is valid and add to array
			    foreach($postids as $postid){
			        if ($post->validate_post($postid)){
			            $posts[$startnum] = $postid;
			        }
			        $startnum++;
			    }
			    //Delete the posts out of selected pool
			    if (count($posts) !== 0){
				    foreach($posts as $post){
				    	 $delete = $db->exec('DELETE FROM '.$f3->get('pool_post_table').' WHERE pool_id = ? AND post_id = ?',array(1=>$poolid,2=>$postid));
				    	 $logger->log_action($f3->get('checked_user_id'), $ip, 'DELETE_FROM_POOL', 'SUCCESS - POOL: '.$poolid, $postid);
				    }
			    }
			    //Get all posts in pool
			    $allposts = $db->exec('SELECT * FROM '.$f3->get('pool_post_table').' WHERE pool_id = ?',array(1=>$poolid));
			    $totalposts = count($allposts);
			    //Re-index sequence number on each post
			    foreach($allposts as $key => $post){
			    	$key = $key + 1;
			    	$update = $db->exec('UPDATE '.$f3->get('pool_post_table').' SET sequence = ? WHERE pool_id = ? AND post_id = ?',array(1=>$postnum,2=>$poolid,3=>$postid));
			    }
			    //Finish dat shit
			    $db->exec('UPDATE '.$f3->get('pool_table').' SET post_count = ? WHERE id = ?',array(1=>$totalposts,2=>$poolid));
			    $logger->log_action($f3->get('checked_user_id'), $ip, 'DELETE_FROM_POOL', 'SUCCESS', $_POST['poolid']);
				echo "OK";
			}
		}
		function main_page(){
		    global $f3, $db;
			//Load required classes
			$user = new user();
            //Level check for DNP
            if ($f3->get('checked_user_group') >= $f3->get('dnp_level')){
                $dnp = "";
            }else{
                $dnp = " WHERE dnp=0 AND status = 'active'";
            }
		    //Get total number of posts
		    $postcount = $db->exec('SELECT (SELECT COUNT(id) as count FROM '.$f3->get('post_table').' '.$dnp.') as count, (SELECT `value` FROM settings WHERE `key` = \'version\') as ver',array(1=>$postnum,2=>$poolid,3=>$postid));
		    $totalposts = $postcount[0]["count"];
		    $version = $postcount[0]["ver"];
			$digits = array();
			//Loop through each number and store
			for ($i=0;$i<strlen($totalposts);$i++) {
				$digits[$i]=substr($totalposts,$i,1);
			}
			//Store info for template
			$f3->set('digits',$digits);
			$f3->set('totalposts',number_format($totalposts));
			$f3->set('ver',$version);
			$f3->set('user',$user);
			//Process template
			$template=new Template;
        	echo $template->render('main_page.html');
		}
	}
?>