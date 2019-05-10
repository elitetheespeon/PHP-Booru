<?php
//Tell PHP to ignore user abort case
ignore_user_abort(1);

//Load required classes
$misc = new misc();
$userc = new user();
$logger = new logger();
$tclass = new tag();
$swf = new swfheader(false);
$imgc = new images();
$webhook = new webhook();
$post = new post();
$no_upload = false;
$ip = $_SERVER['REMOTE_ADDR'];
$error = array();
$remote_upload = false;

//Check if user is banned
if($userc->banned_ip($ip)){
	$f3->set('is_banned',true);
    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'ADD_POST', 'BANNED');
	exit;
}

//Check if user has permission to upload
if(!$userc->check_log()){
	if(!$anon_can_upload){
		$no_upload = true;
	}
}else{
	if(!$userc->gotpermission('can_upload')){
		$no_upload = true;
	}
}
if($no_upload){
	$f3->set('no_upload',true);
    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'ADD_POST', 'NO_PERMISSION');
	$template=new Template;
    echo $template->render('no_permission.html');
	exit();
}

//Check if data was posted
if(isset($_POST['submit'])){
	$image = new images();
	$uploaded_image = false;
	$parent = '';
	//Check for remote image download and process
	if(empty($_FILES['file']) && isset($_POST['source']) && $_POST['source'] != "" && substr($_POST['source'],0,4) == "http" || $_FILES['upload']['error'] != 0 && isset($_POST['source']) && $_POST['source'] != "" && substr($_POST['source'],0,4) == "http"){
		$iinfo = $image->getremoteimage($_POST['source']);
		if($iinfo === false){
			$error[0]["error"] = $image->geterror()."Could not add the image.";
			$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'ADD_POST', 'ERROR_WITH_REMOTE');
		}else{
			$uploaded_image = true;
			$remote_upload = true;
			//Send to upload array
			$uploadarr[] = $iinfo;
		}
	}
	//Reorganize file array if it exists
	if(!empty($_FILES['file'])){
		$uploadarr = $misc->fix_upload_array($_FILES['file']);
	}elseif(!$uploadarr){
		$uploadarr = array();
	}
	//Make sure the file array is not empty before we start
	if(!empty($uploadarr)){
		//Loop through array of files
		foreach ($uploadarr as $ukey => $file){
			//Check for normal image upload and process
			if (!$remote_upload){
				$iinfo = $image->process_upload($file);
				//Check if file upload was successful
				if ($file['error'] !== 0 || $iinfo === false){
					//Error, send to array.
					$error[$ukey]["error"] = $image->geterror();
					$uploaded_image = false;
					$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'ADD_POST', $image->geterror());
				}else{
					//Upload was successfully processed
					$uploaded_image = true;
				}
			}
			//Go fourth with processing if image upload was successful
			if($uploaded_image == true){
				$iinfo = explode(":",$iinfo);
				$ext = strtolower($iinfo[1]);
				$source = htmlentities($_POST['source'],ENT_QUOTES,'UTF-8', FALSE);
				$title = $_POST['title'];
		        $description = $_POST['description'];
		        
		        //Process tags
		        $ttags = array();
		        $postedtags = $_POST['tags'];
		        //Check for valid tags
		        if ($postedtags !== null){
				    //Tags found, process
				    foreach ($postedtags as $atag){
				        $ntag = mb_strtolower(str_replace('%','',$f3->decode($atag)));
				        $ttags[] = $ntag;
				    }
					
					//Clean the tags up
				    asort($ttags);
					$tags = implode(" ",$ttags);
					$tags = $misc->mb_trim(str_replace("  ","",$tags));
					if(substr($tags,0,1) != " "){
						$tags = " $tags";
					}
					if(substr($tags,-1,1) != " "){
						$tags = "$tags ";  
					}
					
					//Count tags and add tagme if less than 5
					$tag_count = count($ttags);
					if($tag_count == 0){
						$ttags[] = "tagme";
					}
					if($tag_count < 5 && strpos(implode(" ",$ttags),"tagme") === false){
						$ttags[] = "tagme";
					}
					
					//Loop through each tag and process
					foreach($ttags as $current){
						if(strpos($current,'parent:') !== false){
							$current = '';
							$parent = str_replace("parent:","",$current);
							if(!is_numeric($parent)){
								$parent = '';
							}
						}
						if($current != "" && $current != " " && !$misc->is_html($current)){
							$ttags = $tclass->filter_tags($tags,$current, $ttags);
							$alias = $tclass->alias($current);
							if($alias !== false){
								$key_array = array_keys($ttags, $current);
								foreach($key_array as $key){
									$ttags[$key] = $alias;
								}
							}
						}
					}
					
					//Sort tag list and put back into string
					asort($ttags);
					$tags = implode(" ",$ttags);
		        }else{
		        	//No tags, just set tags as tagme
		        	$ttags[] = "tagme";
		        	$tags = "tagme";
		        }
		
				$rating = $_POST['rating'];
				if($userc->check_log()){
					$user = $f3->get('checked_user_id');
				}
		
				//Save data for the next part
				$ip = $_SERVER['REMOTE_ADDR'];
				$filehash = md5_file("./images/".$iinfo[0].".".$iinfo[1]);
							
				//Check if flash file / video or regular image
				if ($iinfo[1] == "swf"){
					//Open the swf file
					$swf->loadswf("./images/".$iinfo[0].".".$iinfo[1]);
					//Store height and width
					if ($swf->valid){
						$isinfo = array();
						$isinfo[0] = $swf->width;
						$isinfo[1] = $swf->height;
					}
				}elseif ($iinfo[1] == "webm" || $iinfo[1] == "mp4"){
					//Open video file
					$videodata = $imgc->validate_video("./images/".$iinfo[0].".".$iinfo[1]);
					//Store height and width
					if ($videodata){
						$isinfo = array();
						$isinfo[0] = $videodata['width'];
						$isinfo[1] = $videodata['height'];
					}
				}else{
					$isinfo = getimagesize("./images/".$iinfo[0].".".$iinfo[1]);
				}
				
				//Check to see if we auto approve this post or not
				if ($f3->get('checked_user_group') >= $f3->get('min_auto_approve_level')){
					//Post is from a user in or above auto approve group
					$status = "active";
				}else{
					//Post is from a user below auto approve group
					$status = "pending";
				}
				//Insert post into database
				$insert = $db->exec('INSERT INTO '.$f3->get('post_table').' (creation_date, hash, title, description, owner, height, width, ext, rating, tags, source, ip, status) VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',array(1=>$filehash,2=>$title,3=>$description,4=>$user,5=>$isinfo[1],6=>$isinfo[0],7=>$ext,8=>$rating,9=>$tags,10=>$source,11=>$ip,12=>$status));
		
				//Check if we can thumbnail
				if ($iinfo[1] !== "swf" && $iinfo[1] !== "webm" && $iinfo[1] !== "mp4"){
					//Attempt to generate thumbnail for image
					try{
						if(!$image->thumbnail($iinfo[0].".".$iinfo[1])){
							$error[$ukey]["error"] = $image->geterror();
				            $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'ADD_POST', 'THUMBNAIL_ERROR: '.$image->geterror());
						}
					}catch(Exception $e){
						$error[$ukey]["error"] = $e->getMessage();
				    	$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'ADD_POST', 'THUMBNAIL_ERROR: '.$e->getMessage());
					}
				}
				
				//Continue to process image if database call was successful
				if(!$insert){
					$error[$ukey]["error"] = "Failed to upload image due to database error.";
					unlink("./images/".$iinfo[0].".".$iinfo[1]);
					$ttags = explode(" ",$tags);
					foreach($ttags as $current){
						$tclass->deleteindextag($current);
					}
		            $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'ADD_POST', 'UPLOAD_ERROR_DB');	
				}else{
					//Grab tags and id for uploaded post
					$presult = $db->exec('SELECT id, tags FROM '.$f3->get('post_table').' WHERE hash = ? LIMIT 1',array(1=>$filehash));
					$tags = $presult[0]['tags'];
					$taglist = array();
					$date = date("Y-m-d H:i:s");
		            
		            //Loop through each tag from user
		            foreach($ttags as $tag){
		                //Get tag id
		                $tagid = $tclass->tag_lookup($tag);
		                
		                //Check if tag lookup returned valid tag
		                if($tagid['id']){
		                	//Add tag to array
		                	$taglist[$tagid['id']] = $tagid['name'];
		                }
		            }
		            
		            //Insert each tag into database
	                foreach($taglist as $tag_id => $tag_name){
	                    $insert = $db->exec('INSERT INTO '.$f3->get('poststags_table').' (post_id,tag_id) VALUES (?, ?)',array(1=>$presult[0]['id'], 2=>$tag_id));
	                    $tclass->addindextag($tag_id);
	                }
		            
		            //Tag array clean up for logs and tag list on post
		            $cleantaglist = implode(" ",$taglist);

					//Insert first tag history entry for post
					$insert = $db->exec('INSERT INTO '.$f3->get('tag_history_table').' (id,tags,user_id,updated_at,ip) VALUES (?, ?, ?, NOW(), ?)',array(1=>$presult[0]['id'], 2=>$cleantaglist, 3=>$f3->get('checked_user_id'), 4=>$ip));

		            //Update tags with clean tag list
		            $update = $db->exec('UPDATE '.$f3->get('post_table').' SET tags = ? WHERE id = ? LIMIT 1',array(1=>$cleantaglist,2=>$presult[0]['id']));
		
		            //Check if parent post is actually valid
		            if(is_numeric($parent)){
						$paresult = $db->exec('SELECT id FROM '.$f3->get('post_table').' WHERE id = ?',array(1=>$parent));
						$numrows = count($paresult);
						if($numrows !== 0){
		                    $parent = $parent;    
		                }else{
		                    $parent = 0;
		                }
		            }else{
		                $parent = 0;
		            }
					
					//Get id before last
					$idresult = $db->exec('SELECT id FROM '.$f3->get('post_table').' WHERE id < ? ORDER BY id DESC LIMIT 1',array(1=>$presult[0]['id']));
					
					//Update user post count
					$update = $db->exec('UPDATE '.$f3->get('user_table').' SET post_count = post_count + 1 WHERE id = ?',array(1=>$f3->get('checked_user_id')));
		
		            //Success!
					$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'ADD_POST', 'SUCCESS', $presult[0]['id']);
					
					//Check for post error
					if (isset($file['name'])){
						//Check for create post notification webhook URL
						if($f3->get('webhooks.add_post') != null){
							//Get user avatar info
							$avatarinfo = $userc->get_avatar($f3->get('checked_user_id'));
							
							//Add post data to webhook request
							$f3->set('webhook_data_np.embeds.'.$ukey.'.author_name', $f3->get('checked_username'));
							$f3->set('webhook_data_np.embeds.'.$ukey.'.author_id', $f3->get('checked_user_id'));
							$f3->set('webhook_data_np.embeds.'.$ukey.'.author_avatar_md5', $avatarinfo['md5']);
							$f3->set('webhook_data_np.embeds.'.$ukey.'.author_avatar_ext', $avatarinfo['file_ext']);
							$f3->set('webhook_data_np.embeds.'.$ukey.'.post_id', $presult[0]['id']);
							$f3->set('webhook_data_np.embeds.'.$ukey.'.post_rating', $rating);
							$f3->set('webhook_data_np.embeds.'.$ukey.'.post_title', $title);
							$f3->set('webhook_data_np.embeds.'.$ukey.'.post_description', $description);
							$f3->set('webhook_data_np.embeds.'.$ukey.'.post_tags', $cleantaglist);
							$f3->set('webhook_data_np.embeds.'.$ukey.'.post_thumbnail_url', $post->get_thumbnail($iinfo[1], $filehash));
							$f3->set('webhook_data_np.embeds.'.$ukey.'.post_date', gmdate('Y-m-d\TH:i:s', time()));
						}
	
						//Check if webhook array is defined for any tags
						if(count($f3->get('webhooks.tags')) !== 0){
							//Loop through defined webhook tags
							foreach($f3->get('webhooks.tags') as $webhook_tag => $webhook_url){
								//Check if webhook tag was found in tags
								if(array_search($webhook_tag, $taglist) !== false){
									//Get user avatar info
									$avatarinfo = $userc->get_avatar($f3->get('checked_user_id'));
									
									//Add post data to webhook request
									$f3->set('webhook_data_tg.embeds.0.author_name', $f3->get('checked_username'));
									$f3->set('webhook_data_tg.embeds.0.author_id', $f3->get('checked_user_id'));
									$f3->set('webhook_data_tg.embeds.0.author_avatar_md5', $avatarinfo['md5']);
									$f3->set('webhook_data_tg.embeds.0.author_avatar_ext', $avatarinfo['file_ext']);
									$f3->set('webhook_data_tg.embeds.0.post_id', $presult[0]['id']);
									$f3->set('webhook_data_tg.embeds.0.post_rating', $rating);
									$f3->set('webhook_data_tg.embeds.0.post_title', $title);
									$f3->set('webhook_data_tg.embeds.0.post_description', $description);
									$f3->set('webhook_data_tg.embeds.0.post_tags', $cleantaglist);
									$f3->set('webhook_data_tg.embeds.0.post_thumbnail_url', $post->get_thumbnail($iinfo[1], $filehash));
									$f3->set('webhook_data_tg.embeds.0.post_date', gmdate('Y-m-d\TH:i:s', time()));
	
									//Process webhook data and save
									$webhook_data = $webhook->process_data('tags.'.$webhook_tag, $f3->get('webhook_data_tg'));
									
									//Push data to webhook
									$webhook->push('tags.'.$webhook_tag, json_encode($webhook_data));
								}
							}
						}
					}

					//Check if remote upload
					if(isset($file['name'])){
						//Uploaded file
						$error[$ukey]["error"] = "success";
						$error[$ukey]["postid"] = $presult[0]['id'];
						$error[$ukey]["filename"] = $file['name'];
					}else{
						//Remote upload
						$f3->reroute('/post/all/');
					}
		        }
			}
		}

		//Check for post error
		if (isset($error[$ukey]["postid"]) != null){
			//Check for create post notification webhook URL
			if($f3->get('webhooks.add_post') != null && count($f3->get('webhook_data_np') > 0)){
				//Process webhook data and save
				$webhook_data = $webhook->process_data('add_post', $f3->get('webhook_data_np'));
				
				//Check if we have at lease one valid post
				if (!empty($webhook_data)){
					//Push data to webhook
					$webhook->push('add_post', json_encode($webhook_data));
				}
			}
		}
	}else{
		//No valid data was processed, must be nothing
		$error[0]["error"] = "No valid image/source given for upload.";
        $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'ADD_POST', 'NO_IMAGE');
	}
}

//Save output information for template
$f3->set('info',json_encode($error));
//Set and save max uploads
if ($f3->get('checked_user_group') >= 33){
	$usrmaxup = 30;
}else{
	$usrmaxup = 15;
}
$f3->set('usrmaxup',$usrmaxup);
?>