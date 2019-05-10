<?php
class api{

	var $tag;
	var $user;
	var $logger;
	var $search;

	function __construct(){
		$this->user = new user();
		$this->logger = new logger();
		$this->search = new search();
		$this->tag = new tag();
		$this->template = new Template();
	}
	
	function search(){
		global $f3, $db;

		//Log action
        $this->logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'API_SEARCH', $f3->get('POST.tags'), $f3->get('POST.api_key'));
        
        //Check if call was made from whitelisted IP
        if(count($f3->get('api_allowed_ips')) > 0){
            //Loop through each IP and check
            foreach($f3->get('api_allowed_ips') as $allowed_ip){
                //Check for trusted IP from Cloudflare
                if($_SERVER["HTTP_CF_CONNECTING_IP"]){
                    //Cloudflare enabled, check CF header
                    if ($_SERVER["HTTP_CF_CONNECTING_IP"] == $allowed_ip){
                        //Trusted IP
                        $trusted = true;
                    }
                }else{
                    //Cloudflare disabled, check actual IP
                    if ($_SERVER['REMOTE_ADDR'] == $allowed_ip){
                        //Trusted IP
                        $trusted = true;
                    }
                }
            }
        }
		
		//Get API key
		if(!empty($f3->get('POST.api_key'))){
		    $api_key = $this->user->validate_api_key($f3->get('POST.api_key'));
		}else{
		    $api_key = false;
		}
		
		//Check if IP is trusted or API key is valid
        if(!$api_key && !$trusted){
        	header('HTTP/1.0 403 Forbidden');
        	exit();
        }
        
        //Check if limit was sent
		if(!empty($f3->get('POST.limit') && $f3->get('POST.limit') <= 50) && is_numeric($f3->get('POST.limit'))){
		    $limit = (int)$f3->get('POST.limit');
		}else{
		    $limit = 30;
		}
		
        //Check if start was sent
		if(!empty($f3->get('POST.start')) && is_numeric($f3->get('POST.limit'))){
		    $start = (int)$f3->get('POST.start');
		}else{
		    $start = 0;
		}		
        
		//Set DNP
        $dnp = "WHERE dnp=0 AND status = 'active'";

        //Check for sent tags
        if($f3->get('POST.tags') == null){
        	//Empty tags
        	$f3->set('result',array('Error'=>'No tags sent!'));
        	echo $this->template->render('api.html', 'application/json');
        	exit();
        }
 
        //Break down the tags into array
        $posted_tags = $f3->decode(urldecode($f3->get('POST.tags')));
        $tags = explode(" ",$posted_tags);
        $tagssel = $tags;
        
		//Run the query for post count               
		$result = $this->search->preprare_search(implode(" ",$tags));
        $numrows = $result[0]["count"];
        
        //Check if our search returned no images.
        if($numrows == 0){
        	//No results
        	$f3->set('result',array());
        	echo $this->template->render('api.html', 'application/json');
        	exit();
        }else{
         	//Query for our selected data
        	$result = $this->search->search(implode(" ",$tagssel),$start,$limit);
            
            //Check for valid post data
            if(count($result) > 0 && $result !== null){
                //Loop through results
                foreach($result as $post){
                    //Add owner name
                    $post['owner_name'] = $this->user->get_username($post['owner']);
                }
				
            	//Return posts
            	$f3->set('result',$result);
            	echo $this->template->render('api.html', 'application/json');
            	exit();
            }else{
            	//Invalid data
            	$f3->set('result',array());
            	echo $this->template->render('api.html', 'application/json');
            	exit();
            }
        }
	}
	
	function search_all(){
		global $f3, $db;

		//Log action
        $this->logger->log_action($f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'API_SEARCH', $f3->get('POST.tags'), $f3->get('POST.api_key'));
        
        //Check if call was made from whitelisted IP
        if(count($f3->get('api_allowed_ips')) > 0){
            //Loop through each IP and check
            foreach($f3->get('api_allowed_ips') as $allowed_ip){
                //Check for trusted IP from Cloudflare
                if($_SERVER["HTTP_CF_CONNECTING_IP"]){
                    //Cloudflare enabled, check CF header
                    if ($_SERVER["HTTP_CF_CONNECTING_IP"] == $allowed_ip){
                        //Trusted IP
                        $trusted = true;
                    }
                }else{
                    //Cloudflare disabled, check actual IP
                    if ($_SERVER['REMOTE_ADDR'] == $allowed_ip){
                        //Trusted IP
                        $trusted = true;
                    }
                }
            }
        }
		
		//Get API key
		if(!empty($f3->get('POST.api_key'))){
		    $api_key = $this->user->validate_api_key($f3->get('POST.api_key'));
		}else{
		    $api_key = false;
		}
		
		//Check if IP is trusted or API key is valid
        if(!$api_key && !$trusted){
        	header('HTTP/1.0 403 Forbidden');
        	exit();
        }
        
        //Check if limit was sent
		if(!empty($f3->get('POST.limit') && $f3->get('POST.limit') <= 50) && is_numeric($f3->get('POST.limit'))){
		    $limit = (int)$f3->get('POST.limit');
		}else{
		    $limit = 30;
		}
		
        //Check if start was sent
		if(!empty($f3->get('POST.start')) && is_numeric($f3->get('POST.limit'))){
		    $start = (int)$f3->get('POST.start');
		}else{
		    $start = 0;
		}		
        
		//Set DNP
        $dnp = "WHERE dnp=0 AND status = 'active'";

    	//Query for our selected data
    	$result = $db->exec("SELECT id, creation_date, hash, score, rating, tags, owner, ext, status, dnp FROM ".$f3->get('post_table')." $dnp ORDER BY id DESC LIMIT $start, $limit");
        
        //Check if our search returned no images.
        if(count($result) == 0){
        	//No results
        	$f3->set('result',array());
        	echo $this->template->render('api.html', 'application/json');
        	exit();
        }else{
            //Check for valid post data
            if(count($result) > 0 && $result !== null){
                //Loop through results
                foreach($result as $post){
                    //Add owner name
                    $post['owner_name'] = $this->user->get_username($post['owner']);
                }
				
            	//Return posts
            	$f3->set('result',$result);
            	echo $this->template->render('api.html', 'application/json');
            	exit();
            }else{
            	//Invalid data
            	$f3->set('result',array());
            	echo $this->template->render('api.html', 'application/json');
            	exit();
            }
        }
	}
}
?>