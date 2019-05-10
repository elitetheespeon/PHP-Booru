<?php
	class user{
		private $user_id;
        private $torh;
        private $logger;
        public function __construct() {
			$this->torh = tor::getInstance();
			$this->logger = new logger;
        }

		function hashpass($pass){
			if(!function_exists('hash')){
				return sha1("choujin-steiner--$pass--");
			}else{
				return hash('sha1',"choujin-steiner--$pass--");
			}
		}

		function username_exists($user){
			global $db, $f3;
			$result = $db->exec('SELECT COUNT(*) as count FROM '.$f3->get('user_table')." WHERE user = ?",array(1=>$user));
			if($result[0]['count'] == 0 && strtolower($user) != "anonymous" && strtolower($user) != "admin"){
				return false;
			}else{
				return true;
			}
		}

		function userid_exists($user){
			global $db, $f3;
			$result = $db->exec('SELECT COUNT(*) as count FROM '.$f3->get('user_table')." WHERE id = ?",array(1=>$user));
			if($result[0]['count'] == 0 && strtolower($user) != 0 && strtolower($user) != ""){
				return false;
			}else{
				return true;
			}
		}

		function signup($user,$pass,$email = ""){
			global $db, $f3;
            $ip = $_SERVER['REMOTE_ADDR'];
            $tor = $this->torh;
            //Check if user is using TOR
			try{
	            if($tor->setTarget($_SERVER['REMOTE_ADDR'])->isTorActive()){
	                $this->logger->log_action(0, $_SERVER['REMOTE_ADDR'], 'REGISTER', 'FAIL_TOR', $user." ".$email);
	                return false;
	            }
			}catch(Exception $e){
				//Probably an ipv6 address
				$this->logger->log_action(0, $_SERVER['REMOTE_ADDR'], 'REGISTER', 'TOR_EXCEPTION', $user." ".$email);
			}	        
            //Check if user is in StopForumSpam database
            if ($this->SpammerCheck("ip", $_SERVER['REMOTE_ADDR'], 50) == true || $this->SpammerCheck("username", $user, 50) == true){
                $this->logger->log_action(0, $_SERVER['REMOTE_ADDR'], 'REGISTER', 'FAIL_SPAM', $user." ".$email);
                return false;
            }
            //Check for username with disallowed characters or too short
            if(strpos($user,' ') !== false || strpos($user,'	') !== false || strpos($user,';') !== false || strpos($user,',') !== false || strlen($user) < 3){
                $this->logger->log_action(0, $_SERVER['REMOTE_ADDR'], 'REGISTER', 'FAIL_INVALID', $user." ".$email);
                return false;
            }
			$domain = explode("@",$email);
			//Check for invalid email address
            if($domain[1] == "" || $domain[1] == "asdf.com"){
                $email = "";
            }
			//Check if already exists in database
            if($this->username_exists($user)){
                $this->logger->log_action(0, $_SERVER['REMOTE_ADDR'], 'REGISTER', 'FAIL_EXISTS', $user." ".$email);
                return false;
            }
			//Add the user into the database
			$result = $db->exec('SELECT id FROM '.$f3->get('group_table')." WHERE default_group = TRUE");
			$gid = $result[0]['id'];
			$insert = $db->exec('INSERT INTO '.$f3->get('user_table')." (user, pass, email, ip, ugroup, mail_reset_code, signup_date) VALUES(?, ?, ?, ?, ?, '', NOW())",array(1=>$user,2=>$this->hashpass($pass),3=>$email,4=>$ip,5=>$gid));
			if($insert){
				$this->logger->log_action(0, $_SERVER['REMOTE_ADDR'], 'REGISTER', 'SUCCESS', $user." ".$email);
                return true;
			}else{
				$this->logger->log_action(0, $_SERVER['REMOTE_ADDR'], 'REGISTER', 'FAIL_DB', $user." ".$email);
                return false;
            }
		}

        function SpammerCheck($type, $userinfo, $spam_chance = 50){
            //TYPES: ip email username
            $spammer = false;
            $xmlResult = file_get_contents("http://www.stopforumspam.com/api?$type=" . urlencode($userinfo) . "&confidence");
            $xml = new SimpleXMLElement($xmlResult);
            
            if ($xml->appears == "yes"){
                if ($xml->confidence >= $spam_chance){
                    $spammer = true;
                    $this->logger->log_action(0, $_SERVER['REMOTE_ADDR'], 'SPAMMER_CHECK', 'TRUE', $type." ".$userinfo);
                }else{
                	$this->logger->log_action(0, $_SERVER['REMOTE_ADDR'], 'SPAMMER_CHECK', 'FALSE', $type." ".$userinfo);
                }
            }
            return $spammer;
        }

		function login($user, $pass){
            global $db, $f3;
            $tor = $this->torh;
			try{
	            if($tor->setTarget($_SERVER['REMOTE_ADDR'])->isTorActive()){
	                $this->logger->log_action(0, $_SERVER['REMOTE_ADDR'], 'LOGIN', 'FAIL_TOR',$user);
	                return false;
	            }
			}catch(Exception $e){
				//Probably an ipv6 address
				$this->logger->log_action(0, $_SERVER['REMOTE_ADDR'], 'LOGIN', 'TOR_EXCEPTION', $user);
			}
			$pass = $this->hashpass($pass);
			$result = $db->exec('SELECT * FROM '.$f3->get('user_table')." WHERE user = ? AND pass = ?",array(1=>$user,2=>$pass));
			$numrows = count($result);
			if($numrows == 1){
		        $f3->set('COOKIE.user_id',$result[0]['id'],60*60*24*365);
		        $f3->set('COOKIE.pass_hash',$pass,60*60*24*365);
		        $f3->set('COOKIE.tags',$result[0]['my_tags'],60*60*24*365);
				if(!isset($_COOKIE['tag_blacklist']) && $result[0]['tags'] != "")
					$f3->set('COOKIE.tag_blacklist',$result[0]['tags']);
				$this->logger->log_action($result[0]['id'], $_SERVER['REMOTE_ADDR'], 'LOGIN', 'SUCCESS');
                return true;
			}else{
				$this->logger->log_action(0, $_SERVER['REMOTE_ADDR'], 'LOGIN', 'FAIL_BADUSER',$user);
                return false;
			}
		}

		function logout(){
			global $f3;
            $f3->set('COOKIE.user_id', '', 0);
            $f3->set('COOKIE.pass_hash', '', 0);
            $f3->set('COOKIE.tags', '', 0);
			$this->logger->log_action(0, $_SERVER['REMOTE_ADDR'], 'LOGOUT', 'SUCCESS');
            $f3->reroute('/post/all');
		}
		
		function check_log(){
            global $db,$f3;
            $tor = $this->torh;
			try{
	            if($tor->setTarget($_SERVER['REMOTE_ADDR'])->isTorActive()){
	                $this->logger->log_action($f3->get('COOKIE.user_id'), $_SERVER['REMOTE_ADDR'], 'CHECK_LOGIN', 'FAILED_TOR');
	                return false;
            	}
			}catch(Exception $e){
				//Probably an ipv6 address
				$this->logger->log_action(0, $_SERVER['REMOTE_ADDR'], 'CHECK_LOGIN', 'TOR_EXCEPTION');
			}
			$result = $db->exec('SELECT * FROM '.$f3->get('user_table').' WHERE id=? AND pass=?',array(1=>$f3->get('COOKIE.user_id'),2=>$f3->get('COOKIE.pass_hash')));
			if(count($result) == 1){
				foreach($result as $row){
					$f3->set('checked_username',$row['user']);
					$f3->set('checked_user_id',$row['id']);
					$f3->set('checked_user_group',$row['ugroup']);
				}
				return true;
			}else{
				$f3->set('checked_username','Anonymous');
				$f3->set('checked_user_id',0);
				return false;
			}
		}
		
		function gotpermission($column){
			if($this->check_log()){
				global $f3, $db;
				$column = str_replace('`','``',$column);
				$result = $db->exec('SELECT `'.$column.'` FROM '.$f3->get('group_table').' WHERE id = :id',array(':id'=>$f3->get('checked_user_group')));
				$numrows = count($result);
				if($result[0][$column] == true){
					return true;
				}else{
				    return false;
				}
			}else{
				return false;
			}
		}
		
		function loadpermissions(){
			global $db,$f3;
			$user_id = $_COOKIE['user_id'];
			if(isset($_COOKIE['user_id'])){
				$user_id = $_COOKIE['user_id'];
				$result = $db->exec('SELECT * FROM '.$f3->get('group_table').' AS t1 JOIN '.$f3->get('user_table').' AS t2 ON t2.id = ? WHERE t1.id = t2.ugroup',array(1=>$user_id));
				return $result[0];
			}else{
				$result = $db->exec('SELECT * FROM '.$f3->get('group_table').' WHERE default_group = true');
				return $result[0];
			}
		}
		
		function update_password($id, $pass){
			global $db,$f3;
			$pass = $this->hashpass($pass);
			$update = $db->exec('UPDATE '.$f3->get('user_table').' SET pass = ? WHERE id = ?',array(1=>$pass,2=>$id));
			if($update){
				return true;
			}else{
				return false;
			}
		}

		function update_email($id, $email){
			global $db,$f3;
			$update = $db->exec('UPDATE '.$f3->get('user_table').' SET email = ? WHERE id = ?',array(1=>$email,2=>$id));
			if($update){
				return true;
			}else{
				return false;
			}
		}
        
		function update_notifications($id, $pref){
			global $db,$f3;
			$update = $db->exec('UPDATE '.$f3->get('user_table').' SET notifications = ? WHERE id = ?',array(1=>$pref,2=>$id));
			if($update){
				return true;
			}else{
				return false;
			}
		}
		
		function get_username($id){
			global $f3, $db;
			$result = $db->exec('SELECT id,user FROM '.$f3->get('user_table').' WHERE id = ?',array(1=>$id));
			$numrows = count($result);
			if($numrows == 0){
				return false;
			}else{
			    return $result[0]["user"];
			}
		}

		function get_userid($user){
			global $f3, $db;
			$result = $db->exec('SELECT COUNT(*) as count, id FROM '.$f3->get('user_table').' WHERE user = ?',array(1=>$user));
			if($result[0]['count'] == 0 && strtolower($user) != "anonymous" && strtolower($user) != "admin"){
				return false;
			}else{
				return $result[0]['id'];
			}
		}

		function get_email($id){
            global $f3, $db;
            $result = $db->exec('SELECT email FROM '.$f3->get('user_table').' WHERE id = ?',array(1=>$id));
            $numrows = count($result);
			if($numrows == 0){
				return false;
			}else{
			    return $result[0]["email"];
			}
		}

		function get_notifications($id){
            global $f3, $db;
            $result = $db->exec('SELECT notifications FROM '.$f3->get('user_table').' WHERE id = ?',array(1=>$id));
            $numrows = count($result);
			if($numrows == 0){
				return false;
			}else{
			    return $result[0]["notifications"];
			}
		}
		
        function get_avatar($userid){
			global $f3, $db;
			$result = $db->exec('SELECT * FROM '.$f3->get('avatar_table').' WHERE userid = ?',array(1=>$userid));
			$numrows = count($result);
			if($numrows == 1){
				return $result[0];
			}elseif($numrows > 1){
			    return false;
			}else{
				return false;
			}
        }
		
        function get_user_title($id){
			global $f3, $db;
			$result = $db->exec('SELECT title, ugroup FROM '.$f3->get('user_table').' WHERE id = ? LIMIT 1',array(1=>$id));
			//Check and see if there is a custom title defined, if not then display their level title
			if ($result[0]["title"] == "" && $result[0]["ugroup"] !== ""){
				$tresult = $db->exec('SELECT group_name FROM '.$f3->get('group_table').' WHERE id = ? LIMIT 1',array(1=>$result[0]["ugroup"]));
				return $tresult[0]["group_name"];
			}else{
                return $result[0]["title"];
            }
		}
		
		function get_signature($id){
			global $f3, $db;
			$result = $db->exec('SELECT sig FROM '.$f3->get('user_table').' WHERE id = ? LIMIT 1',array(1=>$id));
            $numrows = count($result);
			if($numrows == 0){
				return false;
			}else{
			    return $result[0]["sig"];
			}
		}

		function get_api_key($id){
			global $f3, $db;
			$result = $db->exec('SELECT api_key FROM '.$f3->get('user_table').' WHERE id = ? LIMIT 1',array(1=>$id));
            $numrows = count($result);
			if($numrows == 0){
				return false;
			}else{
			    return $result[0]["api_key"];
			}
		}

        function set_api_key($id, $remove = false){
			global $f3, $db;
			if($remove){
				$uuid = NULL;
			}else{
				$uuid = uniqid(rand(), true);
			}
			$update = $db->exec('UPDATE '.$f3->get('user_table').' SET api_key = ? WHERE id = ?',array(1=>$uuid,$id));
			if($update){
				return true;
			}else{
			    return false;
			}
		}

        function set_user_title($id, $title){
			global $f3, $db;
			$result = $db->exec('SELECT ugroup FROM '.$f3->get('user_table').' WHERE id = ? LIMIT 1',array(1=>$id));
			//Check if no title was passed and default to user group
			if ($title == ""){
				$update = $db->exec('UPDATE '.$f3->get('user_table').' SET title = ? WHERE id = ?',array(1=>$result[0]["ugroup"],$id));
			}else{
				$update = $db->exec('UPDATE '.$f3->get('user_table').' SET title = ? WHERE id = ?',array(1=>$title,$id));
			}
			if($update){
				return true;
			}else{
			    return false;
			}			
		}
		
		function set_signature($id, $sig){
			global $f3, $db;
			$update = $db->exec('UPDATE '.$f3->get('user_table').' SET sig = ? WHERE id = ?',array(1=>$sig,2=>$id));
			if($update){
				return true;
			}else{
			    return false;
			}
		}
		
		function banned_ip($ip){
			global $f3, $db;
			$result = $db->exec('SELECT ip FROM '.$f3->get('banned_ip_table').' WHERE ip = ? LIMIT 1',array(1=>$ip));
			$numrows = count($result);
			if($numrows == 0){
				return false;
			}else{
			    return true;
			}
		}
		
		function validate_user(){
			//Check if user is banned
			if($this->banned_ip($_SERVER['REMOTE_ADDR'])){
			    return false;
			}
			
			//Check if user is logged in
			if(!$this->check_log()){
				return false;
			}
			
			//If still here, we have passed checks
			return true;
		}
		
		function validate_api_key($key){
			global $f3, $db;
			$result = $db->exec('SELECT api_key FROM '.$f3->get('user_table').' WHERE api_key = ? LIMIT 1',array(1=>$key));
			$numrows = count($result);
			if($numrows == 0){
				return false;
			}else{
			    return true;
			}
		}
	}
?>