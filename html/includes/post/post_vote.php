<?php
	if($f3->get('PARAMS.id') !== "" && is_numeric($f3->get('PARAMS.id')) && $f3->get('PARAMS.type') !== ""){
		$user = new user();
		$id = $f3->get('PARAMS.id');
		$type = $f3->get('PARAMS.type');
		$ip = $_SERVER['REMOTE_ADDR'];
		$user_id ="0";
		$query_part = "";
		if($user->check_log()){
			$user_id = $f3->get('checked_user_id');
			$query_part = " OR post_id='$id' AND user_id='$user_id'";
			$result = $db->exec('SELECT COUNT(*), id, rated FROM '.$f3->get('post_vote_table').' WHERE (post_id = ? AND ip = ?) OR (post_id = ? AND user_id = ?)',array(1=>$id,2=>$ip,3=>$id,4=>$user_id));
		}else{
			$result = $db->exec('SELECT COUNT(*), id, rated FROM '.$f3->get('post_vote_table').' WHERE post_id = ? AND ip = ?',array(1=>$id,2=>$ip));
		}
		if($result[0]['COUNT(*)'] < 1){
			if($type == "up"){
				$update = $db->exec('UPDATE '.$f3->get('post_table').' SET score = score + 1 WHERE id = ?',array(1=>$id));
			}else if($type == "down"){
				$update = $db->exec('UPDATE '.$f3->get('post_table').' SET score = score - 1 WHERE id = ?',array(1=>$id));
			}else{
				exit();
			}
			$insert = $db->exec('INSERT INTO '.$f3->get('post_vote_table').' (rated, ip, post_id, user_id,created_at) VALUES(?, ?, ?, ?, NOW())',array(1=>$type, 2=>$ip, 3=>$id, 4=>$user_id));
    		$resultscore = $db->exec('SELECT score FROM '.$f3->get('post_table').' WHERE id = ?',array(1=>$id));
    		echo $resultscore[0]['score'];
		}else{
            $voteid = $result[0]['id'];
            $voterated = $result[0]['rated'];
            if ($voterated !== $type){
                if($type == "up"){
                    $update1 = $db->exec('UPDATE '.$f3->get('post_table').' SET score = score + 1 WHERE id = ?',array(1=>$id));
                    $update2 = $db->exec('UPDATE '.$f3->get('post_vote_table').' SET rated = \'up\', created_at = NOW(), ip = ? WHERE id = ?',array(1=>$ip, 2=>$voteid));
    			}else if($type == "down"){
                    $update1 = $db->exec('UPDATE '.$f3->get('post_table').' SET score = score - 1 WHERE id = ?',array(1=>$id));
                    $update2 = $db->exec('UPDATE '.$f3->get('post_vote_table').' SET rated = \'down\', created_at = NOW(), ip = ? WHERE id = ?',array(1=>$ip, 2=>$voteid));
    			}
            }       
			$resultscore = $db->exec('SELECT score FROM '.$f3->get('post_table').' WHERE id = ?',array(1=>$id));
    		echo $resultscore[0]['score'];
        }
	}
?>