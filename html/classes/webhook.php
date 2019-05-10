<?php
class webhook{
    
	var $logger;
	var $template;
	var $http_client;
    var $f3;

	function __construct(){
	    $this->f3 = Base::instance();
		$this->logger = new logger();
		$this->template = new Template();
        $this->http_client = new GuzzleHttp\Client([
            'headers' => [ 'Content-Type' => 'application/json' ]
        ]);
        $this->f3->config('webhooks.ini',true);
	}

    function process_data($type, $data){
        //Check if hook type is defined in config
        if (count($this->f3->get('webhooks.'.$type)) !== 0){
            //Hook type defined, start building request
            $this->logger->log_action($this->f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'WEBHOOK_BUILD_REQ', 'START: '.$type);
            $push_data = new stdClass();
            
            //Add base info to webhook post
            $push_data->content = $this->f3->get('webhooks.'.$type.'.message');
            $push_data->username = $this->f3->get('webhooks.'.$type.'.bot_username');
            $push_data->avatar_url = $this->f3->get('webhooks.'.$type.'.bot_avatar_url');
            $push_data->tts = $this->f3->get('webhooks.'.$type.'.tts');

            //Check if embeds are defined and exist in data
            if($this->f3->get('webhooks.'.$type.'.embeds') != null && count($data['embeds']) > 0){
                //Add base embed structure
                $push_data->embeds = array();
                $i = 0;

                //Loop through each embed
                foreach ($data['embeds'] as $embed){
                    //Save current embed data so template vars can be accessed
                    $this->f3->set('webhook_embed', $embed);
                    //Read template data from webhook config
                    $this->f3->config('webhooks.ini',true);
                    //Add embed to post
                    $push_data->embeds[$i] = new stdClass();
                    //Add author
                    $push_data->embeds[$i]->author = new stdClass();
                    $push_data->embeds[$i]->author->name = $this->f3->get('webhooks.'.$type.'.embeds.author_name');
                    $push_data->embeds[$i]->author->url = $this->f3->get('webhooks.'.$type.'.embeds.author_link');
                    $push_data->embeds[$i]->author->icon_url = $this->f3->get('webhooks.'.$type.'.embeds.author_icon');
                    //Add thumbnail
                    $push_data->embeds[$i]->thumbnail = new stdClass();
                    $push_data->embeds[$i]->thumbnail->url = $this->f3->get('webhooks.'.$type.'.embeds.thumbnail_url');
                    $push_data->embeds[$i]->thumbnail->height = $this->f3->get('webhooks.'.$type.'.embeds.thumbnail_height');
                    $push_data->embeds[$i]->thumbnail->width = $this->f3->get('webhooks.'.$type.'.embeds.thumbnail_width');
                    //Add footer
                    $push_data->embeds[$i]->footer = new stdClass();
                    $push_data->embeds[$i]->footer->text = $this->f3->get('webhooks.'.$type.'.embeds.footer_text');
                    $push_data->embeds[$i]->footer->icon_url = $this->f3->get('webhooks.'.$type.'.embeds.footer_icon');
                    //Check title and set
                    if($embed['post_title'] != null){
                        $push_data->embeds[$i]->title = $this->f3->get('webhooks.'.$type.'.embeds.title');
                    }else{
                        $push_data->embeds[$i]->title = $this->f3->get('webhooks.'.$type.'.embeds.default_title');
                    }
                    //Check color for post and set
                    if(($embed['post_rating'] == 'e' || $embed['post_rating'] == 'q' || $embed['post_rating'] == 's') && $this->f3->get('webhooks.'.$type.'.embeds.color.'.$embed['post_rating']) != null){
                        $push_data->embeds[$i]->color = $this->f3->get('webhooks.'.$type.'.embeds.color.'.$embed['post_rating']);
                    }
                    $push_data->embeds[$i]->description = $this->f3->get('webhooks.'.$type.'.embeds.description');
                    $push_data->embeds[$i]->url = $this->f3->get('webhooks.'.$type.'.embeds.url');
                    $push_data->embeds[$i]->timestamp = $this->f3->get('webhooks.'.$type.'.embeds.timestamp');
                    
                    //Check if fields were sent
                    if(count($this->f3->get('webhooks.'.$type.'.embeds.fields')) > 0){
                        //Add base field structure
                        $push_data->embeds[$i]->fields = array();
                        $x = 0;
                        
                        //Loop through each field
                        foreach ($this->f3->get('webhooks.'.$type.'.embeds.fields') as $field){
                            $push_data->embeds[$i]->fields[$x] = new stdClass();
                            $push_data->embeds[$i]->fields[$x]->name = $field['name'];
                            $push_data->embeds[$i]->fields[$x]->value = $field['value'];
                            $x++;
                        }
                    }
                    $i++;
                }
            }
            
            //Send fomatted data back
            return $push_data;
        }else{
            //Hook type not defined, ignore
            $this->logger->log_action($this->f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'WEBHOOK_BUILD_REQ', 'TYPE NOT DEFINED: '.$type);
            return false;
        }
    }

	function push($type, $json){
		global $f3, $db;

		//Log action
        $this->logger->log_action($this->f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'WEBHOOK_PUSH', 'START: '.$type);
        $this->logger->log_action($this->f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'WEBHOOK_PUSH', 'JSON: '.$json);
    	
    	try{
            //Make call to webhook URL
            $request = $this->http_client->request('POST', $this->f3->get('webhooks.'.$type.'.hook_url'), [
                'body' => $json
            ]);
            $status = $request->getStatusCode();
    	}catch(Exception $e){
    	    //Log error
    	    $this->logger->log_action($this->f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'WEBHOOK_PUSH', 'ERROR: '.$e->getMessage());
    	}
    	
    	//Check status of call
        if($status < 200 || $status > 226){
            //Log error
            $this->logger->log_action($this->f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'WEBHOOK_PUSH', 'ERROR: Code '.$status);
        }else{
            //Log success
            $this->logger->log_action($this->f3->get('checked_user_id'), $_SERVER['REMOTE_ADDR'], 'WEBHOOK_PUSH', 'SUCCESS');
        }
	}
}
?>