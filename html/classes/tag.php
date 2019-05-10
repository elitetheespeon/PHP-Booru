<?php
	class tag{
		function __construct()
		{}
		
        static $tag_cat = array(
        //array('id' => 0, 'name' => 'general', 'sname' => ''),
        array('id' => 1, 'name' => 'artist', 'sname' => 'art'),
        array('id' => 3, 'name' => 'copyright', 'sname' => 'copy'),
        array('id' => 4, 'name' => 'character', 'sname' => 'char'),
        array('id' => 5, 'name' => 'species', 'sname' => 'spec')    
        );
        
		function addindextag($tagid){
			global $db, $f3;
			if($tagid != ""){
                $result = $db->exec('SELECT id FROM '.$f3->get('tags_table')." WHERE id = ?",array(1=>$tagid));
                $numrows = count($result);
                if($numrows == 1){
					$insert = $db->exec('UPDATE '.$f3->get('tags_table')." SET post_count = post_count + 1 WHERE id = ?",array(1=>$tagid));
				}
			}
		}
		
		function deleteindextag($tagid){
			global $db, $f3;
			if($tagid != ""){
                $result = $db->exec('SELECT id, post_count FROM '.$f3->get('tags_table')." WHERE id = ?",array(1=>$tagid));
                if($result[0]['post_count'] > 1){
					$update = $db->exec('UPDATE '.$f3->get('tags_table')." SET post_count = post_count - 1 WHERE id = ?",array(1=>$tagid));
				}else{
					$delete = $db->exec('DELETE FROM '.$f3->get('tags_table')." WHERE id = ?",array(1=>$tagid));
                }
			}else{
                return false;
			}
		}
		
		function alias($tag){
			global $f3, $db;
			$result = $db->exec('SELECT tag FROM '.$f3->get('alias_table')." WHERE alias=? AND status='accepted'",array(1=>$tag));
            foreach ($result as $row){
    			if($row['tag'] != "" && $row['tag'] != NULL)
    				return $row['tag'];
            }
			return false;
		}
		
		function filter_tags($tags, $current, $ttags){
			if(substr_count($tags, $current) > 1)
			{
				$temp_array = array();
				$key_array = array_keys($ttags, $current);
				$count = count($key_array)-1;
				for($i = 1; $i <= $count; $i++)
					$ttags[$key_array[$i]] = '';
				foreach($ttags as $current)
				{
					if($current != "" && $current != " ")
						$temp_array[] = $current;
				}
				$ttags = $temp_array;
			}
			return $ttags;
		}
	
		function tag_lookup($tag){
    		global $f3, $db;
            //Check for tag type (catagory)
            if ($this->tag_catagory($tag) !== false){
                $tagtype = $this->tag_catagory($tag);
        		//Flatten tag
                $tag = $this->strip_catagory($tag);
                //Check if tag is an alias
                $alias = $db->exec('SELECT tag FROM '.$f3->get('alias_table').' WHERE alias = ? AND status = \'accepted\' LIMIT 1',array(1=>$tag));
                $aliasnum = count($alias);
                //Check if we got tag name from alias
                if ($aliasnum !== 0){
                    //Get tag id
                    $result = $db->exec('SELECT id, name FROM '.$f3->get('tags_table').' WHERE name = ? LIMIT 1',array(1=>$alias[0]["tag"]));
                    //Send back tag id
                    return array("found"=>true,"id"=>$result[0]['id'],"name"=>$result[0]['name']);              
                }else{
                    //Search for the tag
                    $result = $db->exec('SELECT id, name FROM '.$f3->get('tags_table').' WHERE name = ? LIMIT 1',array(1=>$tag));
                    $numrows = count($result);
            		//Check if tag is in database
                    if($numrows == "0"){
                        //Tag not in database, create it
                        $insert = $db->exec('INSERT INTO '.$f3->get('tags_table').' (name,tag_type,post_count) VALUES(?, ?, 0)',array(1=>$tag, 2=>$tagtype));
                        //Find the id for the tag we just created
                        $idresult = $db->exec('SELECT id, name FROM '.$f3->get('tags_table').' WHERE name = ? LIMIT 1',array(1=>$tag));
                        //Send back tag id
                        return array("found"=>false,"id"=>$idresult[0]['id'],"name"=>$idresult[0]['name']);
                    }else{
                        //Tag is in database, send back tag id
                        return array("found"=>true,"id"=>$result[0]['id'],"name"=>$result[0]['name']);
                    }                    
                }
            }else{
                return false;
            }
        }
        
        function is_catagory($tag){
            //Split up tag into catagory/name format
            $tagpart = explode(':',$tag, 2);
            //Check if we were passed a catagory/name tag or a flat tag
            if (count($tagpart) == 1){
                //Flat tag
                return false;
            }elseif(count($tagpart) == 2){
                //Catagory/name
                return true;           
            }else{
                //Something goofed
                return false;               
            }
        }
        
		function tag_catagory($tag){
            //Check for empty tag
            if ($tag == ""){
                return false;
            }
            //Split up tag into catagory/name format
            $tagpart = explode(':',$tag, 2);
            //Check if we were passed a catagory/name tag or a flat tag
            if (count($tagpart) == 1){
                //Flat tag passed
                return 0;
            }elseif (count($tagpart) == 2){
                //Catagory/name passed, run check
                foreach(self::$tag_cat as $row){
                    if ($tagpart[0] == $row['name'] || $tagpart[0] == $row['sname']){
                        //Catagory was found
                        return $row['id'];
                    }
                }
                //No catagory was found
                return false;
            }else{
                //Something goofed
                return false;
            }
        }

		function tag_catagory_id($id){
            //Check for empty id
            if ($id == ""){
                return false;
            }
            //Catagory id passed, run check
            foreach(self::$tag_cat as $row){
                if ($id == $row['id']){
                    //Catagory was found
                    return $row['name'];
                }
            }
            //No catagory was found
            return false;
        }
        
        function strip_catagory($tag){
            //Check for empty tag
            if ($tag == ""){
                return false;
            }
            //Split up tag into catagory/name format
            $tagpart = explode(':',$tag, 2);
            //Check if we were passed a catagory/name tag or a flat tag
            if (count($tagpart) == 1){
                //Flat tag passed
                return $tag;
            }elseif (count($tagpart) == 2){
                //Catagory id passed, run check
                foreach(self::$tag_cat as $row){
                    if ($tagpart[0] == $row['name'] || $tagpart[0] == $row['sname']){
                        //Catagory was found
                        $tag = str_replace($row['name'].":", "", $tag);
                        $tag = str_replace($row['sname'].":", "", $tag);
                        return $tag;
                    }
                }
                //No catagory was found
                return false;
            }else{
                //Something goofed
                return false;
            }       
        }
            
        function tag_css_class($catid){
            if (self::tag_catagory_id($catid)){
                return "tag-type-".self::tag_catagory_id($catid);
            }else{
                return false;
            }
        }

        function autocomplete(){
		    global $db, $f3;
		    if ($f3->get('POST.term') == ""){
			   exit();
		    }
		    $tagsearch = "{$f3->get('POST.term')}%";
		    $result = $db->exec('SELECT id,name,tag_type FROM '.$f3->get('tags_table').' WHERE name LIKE ? ORDER BY tag_type DESC LIMIT 30',array(1=>$tagsearch));
		    $data = array();
		    
		    foreach ($result as $r){
		       	switch ($r["tag_type"]){
				case 0:
					$tagcolor = "tag-type-general";
					break;
				case 1:
					$tagcolor = "tag-type-artist";
					break;
				case 3:
		    		$tagcolor = "tag-type-copyright";
					break;
				case 4:
			   	   $tagcolor = "tag-type-character";
					break;
				case 5:
			   	   $tagcolor = "tag-type-species";
					break;
				}
		        $data[] = array('label' => $tagcolor, 'value' => $r["name"]);
			}
		    //Format that JSON
		    echo json_encode($data);
		}
    }
?>