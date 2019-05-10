<?php
	class comment{
		function __construct()
		{
		
		}
		//Add comments. Should follow rules set in the config as to anonymous commenting early on.
		function add(){
			global $f3, $db;
			$user = new user;
			$logger = new logger();
			$ip = $_SERVER['REMOTE_ADDR'];
			//Make sure a valid id and comment was passed
			if(!is_numeric($f3->get('PARAMS.id')) || $_POST['comment'] == ""){
				$f3->reroute('/post/all');
			}else{
				$id = $f3->get('PARAMS.id');
				$comment = $_POST['comment'];
			}
			//Also validate form data (to stop spammers!) and check for anon users
			if($_POST['conf'] !== "1" || !$user->check_log()){
				$f3->reroute('/post/view/'.$id);
			}
			//Get length of comment and count words
			$len = strlen($comment);
			$count = substr_count($comment, ' ', 0, $len);
			//Process the comment if it meets requirements
			if($comment != "" && ($len - $count) >= 3){
				$comment = htmlentities($comment,ENT_QUOTES,'UTF-8', FALSE);
				$insert = $db->exec('INSERT INTO '.$f3->get('comment_table').' (comment, ip, user, posted_at, post_id) VALUES(?, ?, ?, NOW(), ?)',array(1=>$comment,2=>$ip,3=>$f3->get('checked_user_id'),4=>$id));
				$update1 = $db->exec('UPDATE '.$f3->get('post_table').' SET last_comment = NOW() WHERE id = ?',array(1=>$id));
				$update2 = $db->exec('UPDATE '.$f3->get('post_count_table').' SET pcount = pcount + 1 WHERE access_key = \'comment_count\'');
				if($user != "Anonymous"){
					$update1 = $db->exec('UPDATE '.$f3->get('user_table').' SET comment_count = comment_count + 1 WHERE id = ?',array(1=>$f3->get('checked_user_id')));
				}
				$logger->log_action($f3->get('checked_user_id'), $ip, 'COMMENT_ADD', 'SUCCESS', $id);
				$f3->reroute('/post/view/'.$id);
			}else{
				$f3->reroute('/post/view/'.$id);
			}
		}
		//Edit comments, there is a limit to how many minutes you have to comment as well as a 3 character minimum.
		function edit(){
			global $f3, $db;
			$logger = new logger();
			$user = new user;
			//Make sure a valid id and comment was passed
			if(!is_numeric($f3->get('PARAMS.id')) || $_POST['comment'] == ""){
				$f3->reroute('/post/all');
			}else{
				$id = $f3->get('PARAMS.id');
				$comment = $_POST['comment'];
			}
			//Also validate form data (to stop spammers!) and check for anon users
			if($_POST['conf'] !== 1 || !$user->check_log()){
				$f3->reroute('/post/view/'.$id);
			}
			//Get length of comment and count words
			$len = strlen($comment);
			$count = substr_count($comment, ' ', 0, $len);
			//Process the edit if it meets requirements
			if($comment != "" && ($len - $count) >= 3){	
				$comment = htmlentities($comment,ENT_QUOTES,'UTF-8', FALSE);
				$result = $db->exec('SELECT posted_at FROM '.$f3->get('comment_table').' WHERE id = ? LIMIT 1',array(1=>$id));
				$posted_at = $result[0]['posted_at'];
				$edit_limit = ($f3->get('edit_limit') * 60) + $posted_at;
				$update = $db->exec('UPDATE '.$f3->get('comment_table').' SET comment = ?, edited_at = NOW() WHERE user = ? AND id = ? AND posted_at <= ?',array(1=>$comment,2=>$f3->get('checked_user_id'),3=>$id,4=>$edit_limit));
				$logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'COMMENT_EDIT', 'SUCCESS', $id);
				$f3->reroute('/post/view/'.$id);
			}else{
				$f3->reroute('/post/view/'.$id);
			}
		}
		//Just the voting function. Nothing much needed to be edited here unless you want to change the vote score values...
		function vote(){
			global $f3, $db;
			$logger = new logger();
			//Make sure we have valid information passed
			if(is_numeric($f3->get('PARAMS.id')) && ($f3->get('PARAMS.vote') == "up" || $f3->get('PARAMS.vote') == "down") && is_numeric($f3->get('PARAMS.cid'))){
				$vote = $f3->get('PARAMS.vote');
				$id = $f3->get('PARAMS.id');
				$cid = $f3->get('PARAMS.cid');				
			}else{
				$f3->reroute('/post/all');
			}
			//Get user and IP
			$user = $f3->get('checked_user_id');
			$ip = $_SERVER['REMOTE_ADDR'];
			//Count votes for comment
			if($user !== 0){
				$result = $db->exec('SELECT COUNT(*) as count FROM '.$f3->get('comment_vote_table').' WHERE comment_id = ? AND post_id = ? AND ip = ? OR comment_id = ? AND post_id = ? AND user_id = ?',array(1=>$cid,2=>$id,3=>$ip,4=>$cid,5=>$id,6=>$f3->get('checked_user_id')));
			}else{
				$result = $db->exec('SELECT COUNT(*) as count FROM '.$f3->get('comment_vote_table').' WHERE comment_id = ? AND post_id = ? AND ip = ?',array(1=>$cid,2=>$id,3=>$ip));
			}
			$count = $result[0]['count'];
			//Check if user has already voted
			if($count < 1){
				//Add vote
				$insert = $db->exec('INSERT INTO '.$f3->get('comment_vote_table').' (ip,post_id,comment_id) VALUES(?, ?, ?)',array(1=>$ip,2=>$id,3=>$cid));
				$logger->log_action($f3->get('checked_user_id'), $ip, 'COMMENT_VOTE', 'SUCCESS_NEW', $id, $cid);
				//Change vote count
				if($vote == "up"){
					$update = $db->exec('UPDATE '.$f3->get('comment_table').' SET score=score + 1 WHERE id = ? AND post_id = ?',array(1=>$cid,2=>$id));
					$logger->log_action($f3->get('checked_user_id'), $ip, 'COMMENT_VOTE', 'SUCCESS_UP', $id, $cid);
				}else{
					$update = $db->exec('UPDATE '.$f3->get('comment_table').' SET score=score - 1 WHERE id = ? AND post_id = ?',array(1=>$cid,2=>$id));
					$logger->log_action($f3->get('checked_user_id'), $ip, 'COMMENT_VOTE', 'SUCCESS_DOWN', $id, $cid);
				}
			}
			//Get the result and send to client
			$scoreresult = $db->exec('SELECT score FROM '.$f3->get('comment_table').' WHERE id = ? AND post_id = ?',array(1=>$cid,2=>$id));
			echo $scoreresult[0]['score'];
		}
		
		//How many comments are set for this page, sub page, and post id?
		function count($id,$page,$sub){
			global $f3, $db;
			$result = $db->exec('SELECT id FROM '.$f3->get('comment_table').' WHERE post_id = ?',array(1=>$id));
			$numrows = count($result);
			return $numrows;
		}
		
		function list_comment(){
			global $f3, $db;
    		$f3->set('pagename','comment_list');
			//Load vars and class
			$misc = new misc();
			$cid = $f3->get('PARAMS.cid');
			//Get comment
			$commentres = $db->exec('SELECT post_id, comment, user, posted_at, score FROM '.$f3->get('comment_table').' WHERE id = ?',array(1=>$cid));
			//Store comment data for template
			$post_id = $commentres[0]["post_id"];
			$comment = $misc->swap_bbs_tags($misc->linebreaks($misc->short_url(htmlentities($row['comment'],ENT_QUOTES,"UTF-8", FALSE))));
			$username = $user->get_username($commentres[0]["user"]);
			$posted_at = $commentres[0]["posted_at"];
			$score = $commentres[0]["score"];
			$f3->set('post_id',$post_id);
			$f3->set('comment',$comment);
			$f3->set('username',$username);
			$f3->set('posted_at',$posted_at);
			$f3->set('score',$score);
			$template=new Template;
		    echo $template->render('comment_list.html');
		}
		
		function get_comments($id){
			global $f3, $db;
			$result = $db->exec('SELECT SQL_NO_CACHE id, comment, user, posted_at, score, spam FROM '.$f3->get('comment_table').' WHERE post_id = ? ORDER BY posted_at ASC',array(1=>$id));
			$numrows = count($result);
			if($numrows == 0){
				return false;
			}else{
			    return $result;
			}
		}
	}
?>