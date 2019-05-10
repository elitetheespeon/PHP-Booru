<?php
class misc{
	public function short_url($text){
		$urlRegex = "((?:https?|ftp)\:\/\/)"; /// Scheme
		$urlRegex .= "([a-zA-Z0-9+!*(),;?&=\$_.-]+(\:[a-zA-Z0-9+!*(),;?&=\$_.-]+)?@)?"; /// User and Password
		$urlRegex .= "([a-zA-Z0-9.-]*)\.([a-zA-Z]{2,3})"; /// Domain or IP
		$urlRegex .= "(\:[0-9]{2,5})?"; /// Port
		$urlRegex .= "(\/([a-zA-Z0-9+\$_-]\.?)+)*\/?"; /// Path
		$urlRegex .= "(\?[a-zA-Z+&\$_.-][a-zA-Z0-9;:@&%=+\/\$_.-]*)?"; /// GET Query
		$urlRegex .= "(#[a-zA-Z_.-][a-zA-Z0-9+\$_.-]*)?"; /// Anchor
		
		$linkRegex = '/"(.+)"\:('. $urlRegex . ')/ms';
		
		$fullUrlRegex = "/^"; /// Start Regex (PHP is stupid)
		$fullUrlRegex .= "("; /// Catch whole url except garbage
		$fullUrlRegex .= $urlRegex;
		$fullUrlRegex .= ").*"; /// End of catching whole url
		$fullUrlRegex .= "$/"; /// End Regex
		$fullUrlRegex .= "m"; /// Allow multi line match (and ^ and & )
		$fullUrlRegex .= "s"; /// Don't stop when finding an \n.

		$links = array();
		$urls = array();
		 
		if(!$this->isUrlPossible($text)){
            /// Do nothing, because the text is too small for urls.
        }else{
            $allTheWords = preg_split('/\s|(\<br ?\/\>)/', $text);

            foreach($allTheWords as $word){
                if($this->isUrlPossible($word)){
                    $matches = array();
                    $ambigiousResultFullUrl = preg_match($fullUrlRegex, $word, $matches);
                    if($ambigiousResultFullUrl === TRUE || $ambigiousResultFullUrl === 1){
                        $embeddedLinks[] = $word;
                    }

                    $ambigiousResultLink = preg_match($linkRegex, $word, $matches);
                    if($ambigiousResultLink === TRUE || $ambigiousResultLink === 1){
                        $description = $matches[1];
                        $url = $matches[2];
                        $urls[$word] = '<a href="' . $url . '" rel="nofollow">' . $description . '</a>';
                    }
                }
            }

            
            //Shorten each found embedded url longer than 60 chars with ellipses.
            //Otherwise, show them completely.
			//Added a check to see if embeddedLinks actually contained data
			if ($embeddedLinks){
	            foreach( $embeddedLinks as $url ){
	                $linkLength = strlen( $url );
	
	                if($linkLength > 60){
	                    $urlFirstPart = substr( $url, 0, 25 );
	                    $urlSecondPart = substr( $url, -25, $linkLength );
	                    $displayUrl = '<a href="' . $url . '" rel="nofollow">' . $urlFirstPart . '...' . $urlSecondPart . '</a>';
	                }else{
	                    $displayUrl = '<a href="' . $url . '" rel="nofollow">' . $url . '</a>';
	                }
	                $urls[$url] = $displayUrl;
	            }
	

	            //Replace each embedded url with its displayUrl:
	            foreach($urls as $url => $displayUrl){
	                $text = str_replace($url, $displayUrl, $text);
	            }
			}
        }
        return $text;
	}
	
	/**
     * We assume 3 char links (like ftp) with 1 domain char with a tld of 2 chars are the shortest urls atm:
     * ftp + :// + v + . + ee
     * thus
     * 3   + 3   + 1 + 1 + 2 = 10
     * */
    private function isUrlPossible($text){
        return 10 <= strlen($text);
    }

	function linebreaks($text){
		if(strpos($text,"\r\n") !== false){
			return str_replace("\r\n","<br />",$text);
		}
		return $text;
	}

	function send_mail($reciver, $subject, $body){
		global $f3;
		$headers = "";
		$eol = "\r\n";
		$headers .= "From: no-reply at ".$f3->get('site_url3')." <".$f3->get('site_url3').">".$eol; 
		$headers .= "X-Mailer:  Microsoft Office Outlook 12.0".$eol;
		$headers .= "MIME-Version: 1.0".$eol;
		$headers .= 'Content-Type: text/html; charset="UTF-8"'.$eol;
		$headers .= "Content-Transfer-Encoding: 8bit".$eol.$eol;
		if(substr($body,-8,strlen($body)) != $eol.$eol){
			$body = $body.$eol.$eol;
		}
		if(@mail($reciver,$subject,$body,$headers)){
			return true;
		}else{
			return false;
		}
	}

	function windows_filename_fix($new_tag_cache){
		if(strpos($new_tag_cache,";") !== false)
			$new_tag_cache = str_replace(";","&#059;",$new_tag_cache);
		if(strpos($new_tag_cache,".") !== false)
			$new_tag_cache = str_replace(".","&#046;",$new_tag_cache);
		if(strpos($new_tag_cache,"*") !== false)
			$new_tag_cache = str_replace("*","&#042;",$new_tag_cache);
		if(strpos($new_tag_cache,"|") !== false)
			$new_tag_cache = str_replace("|","&#124;",$new_tag_cache);
		if(strpos($new_tag_cache,"\\") !== false)
			$new_tag_cache = str_replace("\\","&#092;",$new_tag_cache);
		if(strpos($new_tag_cache,"/") !== false)
			$new_tag_cache = str_replace("/","&#047;",$new_tag_cache);
		if(strpos($new_tag_cache,":") !== false)
			$new_tag_cache = str_replace(":","&#058;",$new_tag_cache);
		if(strpos($new_tag_cache,'"') !== false)
			$new_tag_cache = str_replace('"',"&quot;",$new_tag_cache);
		if(strpos($new_tag_cache,"<") !== false)
			$new_tag_cache = str_replace("<","&lt;",$new_tag_cache);
		if(strpos($new_tag_cache,">") !== false)
			$new_tag_cache = str_replace(">","&gt;",$new_tag_cache);
		if(strpos($new_tag_cache,"?") !== false)
			$new_tag_cache = str_replace("?","&#063;",$new_tag_cache);
		return $new_tag_cache;	
	}
	
	function ReadHeader($socket){
		$i=0;
		$header = "";
		while(true && $i<20 && !feof($socket)){
		   $s = fgets( $socket, 4096 );
		   $header .= $s;
		   if(strcmp($s, "\r\n") == 0 || strcmp($s, "\n") == 0){
			   break;
		   }
		   $i++;
		}
		if($i >= 20){
		   return false;
		}
		return $header;
	}

	function getRemoteFileSize($header){
		if(strpos($header,"Content-Length:") === false){
			return 0;
		}
		$count = preg_match($header,'/Content-Length:\s([0-9].+?)\s/',$matches);
		if($count > 0){
			if(is_numeric($matches[1])){
				return $matches[1];
			}else{
				return 0;
			}
		}else{
			return 0;
		}
	}
	
	/*
	The swap_bbs_tags function thoughts
	If we changed the way comments/forum posts are stored in
	the database, we need to account for the changes here or else 
	text will not render correctly. We need to make a post
	that exhibits examples of all pattern replacements
	for debug. -Assimi
	*/
	function swap_bbs_tags($data){
		$pattern = array();
		$replace = array();
		$pattern[] = '/\[quote\](.*?)\[\/quote\]/i';
		$replace[] = '<div class="quote">$1</div>';
		$pattern[] = '/\[b\](.*?)\[\/b\]/i';
		$replace[] = '<b>$1</b>';
		$pattern[] = '/\[i\](.*?)\[\/i\]/i';
		$replace[] = '<i>$1</i>';
		$pattern[] = '/\[s\](.*?)\[\/s\]/i';
		$replace[] = '<del>$1</del>';
		$pattern[] = '/\[spoiler\](.*?)\[\/spoiler\]/i';
		$replace[] = '<span class="spoiler">$1</span>';
		$pattern[] = '/\[post\](.*?)\[\/post\]/i';
		$replace[] = '<a href="/index.php?page=post&s=view&id=$1">Post #$1</a>';
		$pattern[] = '/\[forum\](.*?)\[\/forum\]/i';
		$replace[] = '<a href="/index.php?page=forum&s=view&id=$1">Forum #$1</a>';
		$count = count($pattern)-1;
		for($i=0;$i<=$count;$i++){
			while(preg_match($pattern[$i],$data) == 1){
				$data =  preg_replace($pattern[$i], $replace[$i], $data);
			}
		}
		return $data;
	}
	
	function date_words($date_now){
	  $hour_now = date('g:i:s A',$date_now);
		if($date_now+60 >= time()){
            $a = time()-$date_now; 
			$a = (int)($a+1);
            if($a == 1){
            $date_now = $a." second ago";
            }else if($a >= 1){
            $date_now = $a." seconds ago";
            }
        }else if($date_now+60*59 >= time()){
            $a = time()-$date_now; 
			$a = (int)($a/(60));
            if($a == 1){
                $date_now = $a." minute ago";
            }else if($a >= 1){
                $date_now = $a." minutes ago";
            }
        }
        else if($date_now+60*60*24 >= time()){
        	$a = time()-$date_now; 
			$a = (int)($a/(60*60));
            if($a == 1){
                $date_now = $a." hour ago";
            }else if($a >= 1){
                $date_now = $a." hours ago";
            }
		}else if($date_now+60*60*48 >= time()){
			$date_now = "Yesterday";
        }else if(((int)((time()-$date_now)/(24*60*60)))<=7){
			$a = time()-$date_now; 
			$a = (int)($a/(24*60*60));
			if($a == 1){
                $date_now = $a." day ago";
            }else if($a >= 1){
            $date_now = $a." days ago";
            } 
		}else if(((int)((time()-$date_now)/(24*60*60)))<=31){
			$a = time()-$date_now; 
			$a = (int)($a/(24*60*60*7));
			if($a == 1){
                $date_now = $a." week ago";
            }else if($a >= 1){
            $date_now = $a." weeks ago";
            }
		}else if(((int)((time()-$date_now)/(24*60*60)))<=365){
			$a = time()-$date_now; 
			$a = (int)($a/(24*60*60*31));
			if($a == 1){
                $date_now = $a." month ago";
            }else if($a >= 1){
            $date_now = $a." months ago";
            }
		}else{
			$a = time()-$date_now;
			$a = ((int)($a/(24*60*60*365)));
			if($a == 1){
                $date_now = $a." year ago";
            }else if($a >= 1){
            $date_now = $a." years ago";
            }
		}
		$date_now = '<span title="'.$hour_now.'">'.$date_now.'</span>';
		return $date_now;
	}
	
	public function is_html($data){
		if(preg_match("#<script|<html|<head|<title|<body|<pre|<table|<a\s+href|<img|<plaintext|<div|<frame|<iframe|<li|type=#si", $data) == 1){
			return true;
		}else{
			return false;
		}
	}
	
	function mb_trim($string, $charlist='\\\\s', $ltrim=true, $rtrim=true){
        $both_ends = $ltrim && $rtrim;

        $char_class_inner = preg_replace(
            array( '/[\^\-\]\\\]/S', '/\\\{4}/S' ),
            array( '\\\\\\0', '\\' ),
            $charlist
        );

        $work_horse = '[' . $char_class_inner . ']+';
        $ltrim && $left_pattern = '^' . $work_horse;
        $rtrim && $right_pattern = $work_horse . '$';

        if($both_ends){
            $pattern_middle = $left_pattern . '|' . $right_pattern;
        }elseif($ltrim){
            $pattern_middle = $left_pattern;
        }else{
            $pattern_middle = $right_pattern;
        }

        return preg_replace("/$pattern_middle/usSD", '', $string);
    }

	function report(){
		global $f3, $db;
		//Load required classes
		$user = new user();
		$logger = new logger();
		$ip = $_SERVER['REMOTE_ADDR'];
		
		//Check if user is banned
		if($user->banned_ip($ip)){
		    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'REPORT', 'BANNED');
			$template=new Template;
		    echo $template->render('no_permission.html');
			exit();
		}
		
		//Check if user is logged in and anon report is enabled
		if(!$user->check_log() && !$f3->get('anon_report')){
		    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'REPORT', 'NOT_LOGGED_IN');
			$template=new Template;
		    echo $template->render('no_permission.html');
			exit();
		}
		
		//Check for valid data
		if($f3->get('PARAMS.type') != "" && $f3->get('PARAMS.rid') != "" && is_numeric($f3->get('PARAMS.rid'))){
			//Store type and reported id
			$type = $f3->get('PARAMS.type');
			$rid = $f3->get('PARAMS.rid');
			//Check for different types
			if($type == "comment"){
				//Comment passed, mark as spam
				$update = $db->exec('UPDATE '.$f3->get('comment_table').' SET spam = TRUE WHERE id = ?',array(1=>$rid));
				//Check if update succeeded
				if($update){
					//Success
					$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'REPORT_COMMENT', 'SUCCESS', $rid);
					print "pass";
				}else{
					//Failed
					$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'REPORT_COMMENT', 'ERROR_DB', $rid);
					print "fail";
				}
			}else if($type == "post"){
				//Post passed, store reason
				$reason = htmlentities($_POST['reason'], ENT_QUOTES, 'UTF-8', FALSE);
				//Check if reason is more than 0 characters
				if(strlen($reason) > 0){
					$insert = $db->exec('INSERT INTO '.$f3->get('flagged_post_table').' (created_at, post_id, reason, user_id, is_resolved) VALUES(NOW(), ?, ?, ?, \'0\')',array(1=>$rid,2=>$reason,3=>$f3->get('checked_user_id')));
					$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'REPORT_POST', 'SUCCESS', $rid);
				}
				//Success, reroute
				$f3->reroute('/post/view/'.$rid);
			}else{
				//Error, reroute
				$f3->reroute('/post/all/');
			}
		}		
	}

	function remove(){
		global $f3, $db;
		//Load required classes
		$user = new user();
		$logger = new logger();
		$images = new images();
		$ip = $_SERVER['REMOTE_ADDR'];
		
		//Check if user is logged in
		if(!$user->check_log()){
		    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'REMOVE', 'NO_ACCESS');
			$template=new Template;
		    echo $template->render('no_permission.html');
			exit();
		}
		//Check if we have a valid id
		if(is_numeric($f3->get('PARAMS.id')) && $f3->get('PARAMS.id') !== ""){
			//Store id from sent data
			$id = $f3->get('PARAMS.id');
			//Loop though the types of removals
			if($f3->get('PARAMS.type') == "note" && is_numeric($f3->get('PARAMS.altid')) && $f3->get('PARAMS.altid') != ""){
				//Check if user has access to delete notes
				if(!$user->gotpermission('alter_notes')){
				    $logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'REMOVE_NOTE', 'NO_ACCESS');
					exit();
				}
				//Store the note id
				$note_id = $f3->get('PARAMS.altid');
				//Get note count matching the post id and note id passed
				$notecountres = $db->exec('SELECT COUNT(*) as count FROM '.$f3->get('note_table').' WHERE post_id = ? AND id = ?',array(1=>$id,2=>$note_id));
				if($notecountres[0]["count"] == 1){
					//Delete note
					$delete1 = $db->exec('DELETE FROM '.$f3->get('note_table').' WHERE post_id = ? AND id = ?',array(1=>$id,2=>$note_id));
					//Delete note history
					$delete2 = $db->exec('DELETE FROM '.$f3->get('note_history_table').' WHERE post_id = ? AND note_id = ?',array(1=>$id,2=>$note_id));
					//Return note id
					print $note_id;
					$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'REMOVE_NOTE', 'SUCCESS', $note_id);
				}
			}else if($f3->get('PARAMS.type') == "post"){
				//Call removeimage function and check if we got a good response
				if($images->removeimage($id) == true){
					//Success, redirect to post list
					$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'REMOVE_POST', 'SUCCESS', $id);
					$f3->reroute('/post/all');
				}else{
					//Error, redirect to post list
					$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'REMOVE_POST', 'ERROR', $id);
					$f3->reroute('/post/all');
				}
			}else if($f3->get('PARAMS.type') == "comment"){
				//Store the post id
				$post_id = $f3->get('PARAMS.altid');
				//Get data for comment id
				$commentres = $db->exec('SELECT * FROM '.$f3->get('comment_table').' WHERE id = ? LIMIT 1',array(1=>$id));
				//Check if we have a valid comment id
				if(count($commentres) == "1"){
					//Check if user has access to remove comments or is the creator
					if($user->gotpermission('delete_comments') || $commentres[0]["user"] == $f3->get('checked_user_id')){
						//Delete comment
						$delete1 = $db->exec('DELETE FROM '.$f3->get('comment_table').' WHERE id = ?',array(1=>$id));
						//Delete comment votes
						$delete2 = $db->exec('DELETE FROM '.$f3->get('comment_vote_table').' WHERE comment_id = ?',array(1=>$id));
						//Update post count
						$update = $db->exec('UPDATE '.$f3->get('post_count_table').' SET pcount = pcount - 1 WHERE access_key = \'comment_count\'');
						$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'REMOVE_COMMENT', 'SUCCESS', $id);
					}
				}else{
					//User does not have access
					$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'REMOVE_POST', 'NO_ACCESS', $id);
				}
				//Reroute to post view
				$f3->reroute('/post/view/'.$post_id);
			}
		}
	}
	function fix_upload_array(&$file_post) {
	    //Fix up those file arrays
	    $file_ary = array();
	    $file_count = count($file_post['name']);
	    $file_keys = array_keys($file_post);
	
	    for ($i=0; $i<$file_count; $i++) {
	        foreach ($file_keys as $key) {
	            $file_ary[$i][$key] = $file_post[$key][$i];
	        }
	    }
	    return $file_ary;
	}

	function tag_pagination($tags){
		global $f3, $db;
		
		//Get current route path without any args
		$base_path = preg_replace("/\/\@\w+/", "", $f3->get('PATTERN'));
		
		//Set cleaned route for template
		$f3->set('T_ROUTE', $base_path);
		
		//Set url encoded tags for template
		$f3->set('T_ARGS', (urlencode($tags)));
	}

	function fix_keys($array){
	    //Resort multi-dimensional array
	    foreach ($array as $k => $val){
		    if (is_array($val)){
		    	$array[$k] = fix_keys($val); //recurse
		    }
		}
	  	return array_values($array);
	}
}
?>