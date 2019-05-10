<?php
	class logger
	{
		function __construct()
		{
			
		}

		public function log_action($uid = 0, $ip, $action, $result = null, $cid = null)
		{
			global $db,$f3;
            $result = $db->exec('INSERT INTO '.$f3->get('logs_table').' (uid, ip, action, result, date, cid) VALUES (?, ?, ?, ?, NOW(), ?)',array(1=>$uid,2=>$ip,3=>$action,4=>$result,5=>$cid));
		}	
	}
?>