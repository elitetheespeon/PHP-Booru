<?php
	class search{
		function __construct()
		{
		
		}

		function search($search, $start, $limit){
			global $db, $f3;
			$tclass = new tag();
			$tuser = new user();
			$tags = '';
			$aliased_tags = '';
			$original_tags = '';
			$parent = '';
			$ttags = array_filter(array_map('trim', explode(" ",trim($search))));
			$g_rating = '';
			$g_owner = '';
			$g_tags = '';
			$ga_tags = array();
			$ga_other = array();
			$i = 0;
			$k = 0;
			$ttagslen = count($ttags);
			$tagcount = 0;
			
            //Level check for DNP
            if ($f3->get('checked_user_group') >= $f3->get('dnp_level')){
                $dnp = "";
            }else{
			    if ($f3->get('checked_user_id') !== null){
			    	$dnp = "AND (dnp=0 AND status = 'active' OR owner = ".$f3->get('checked_user_id').')';
			    }else{
			    	$dnp = "AND dnp=0 AND status = 'active'";
			    }
            }
			
			//Break out each tag
            foreach($ttags as $current){
				//Check if parent delimiter
                if(strpos(strtolower($current),'parent:') !== false){
					$parent = str_replace("parent:","",$current);
 					if(!is_numeric($parent)){
						$g_parent = '';					
					}else{
						$g_parent = " AND parent = ?";
						$ga_other[$k+1] = $parent; $k++;
					}
					$current = '';
				}
				//Check if not whitespace
                if($current != "" && $current != " "){
					$len = strlen($current);
					$count = substr_count($current, '*', 0, $len);
					//Check if greater than or equal to 2 characters w/ wildcard stripped
                    if(($len - $count) >= 2){
					    //Check if last in array or not, used for union join
					    if ($i == $ttagslen - 1 || $current == "+" || $current == "-"){
							$union = "";
					    }else{
					        $union = "UNION ALL";
					    }
						//Check if score delimiter
                        if(strpos(strtolower($current),'score:')  !== false){
							$score = str_replace('score:','',$current);
							//Check operator, if negative or positive
							if(substr($current,0,1) == "-"){
								$score = substr($score,1,strlen($score)-1);
								//Check if score is a number
								if(is_numeric($score)){
									$g_owner = " AND score != ?";
									$ga_other[$k+1] = (int)$score; $k++;
								}
							}else{
								//Check for second operator
								if(substr($score,0,1) == "<"){
									$score = substr($score,1,strlen($score)-1);
									//Check if score is a number
									if(is_numeric($score)){
										$g_owner = " AND score < ?";
										$ga_other[$k+1] = (int)$score; $k++;
									}				
								}elseif(substr($score,0,1) == ">"){
									$score = substr($score,1,strlen($score)-1);
									//Check if score is a number
									if(is_numeric($score)){
										$g_owner = " AND score > ?";
										$ga_other[$k+1] = (int)$score; $k++;
									}				
								}else{
									//Check if score is a number
									if(is_numeric($score)){
										$g_owner = " AND score = ?";
										$ga_other[$k+1] = (int)$score; $k++;
									}									
								}
							}
						}
						//Check if rating delimiter
                        else if(strpos(strtolower($current),'rating:')  !== false){
							$rating = str_replace('rating:','',$current);
							//Check operator, if negative or positive
							if(substr($current,0,1) == "-"){
								//Make sure rating fits types, if not then set query to null
								if($rating == "safe" || $rating == "s"){
									$g_rating .= " AND rating != 's'";
								}elseif($rating == "questionable" || $rating == "q"){
									$g_rating .= " AND rating != 'q'";
								}elseif($rating == "explicit" || $rating == "e"){
									$g_rating .= " AND rating != 'e'";
								}else{
									$g_rating .= "";
								}
							}else{
								//Make sure rating fits types, if not then set query to null
								if($rating == "safe" || $rating == "s"){
									$g_rating .= " AND rating = 's'";
								}elseif($rating == "questionable" || $rating == "q"){
									$g_rating .= " AND rating = 'q'";
								}elseif($rating == "explicit" || $rating == "e"){
									$g_rating .= " AND rating = 'e'";
								}else{
									$g_rating .= "";
								}
							}
						}
						//Check if user delimiter
                        else if(strpos(strtolower($current),'user:')  !== false){
							$owner = str_replace('user:','',$current);
							if(substr($current,0,1) == "-"){
								$owner = substr($owner,1,strlen($owner)-1);
								//Check if userid or username
								if(is_numeric($owner)){
									//Search by userid
									if ($tuser->userid_exists($owner)){
										$g_owner = " AND owner != ?";
										$ga_other[$k+1] = (int)$owner; $k++;
									}
								}else{
									//Search by user name
									if ($owner = $tuser->get_userid($owner)){
										$g_owner = " AND owner != ?";
										$ga_other[$k+1] = $owner; $k++;
									}
								}
							}else{
								//Check if userid or username
								if(is_numeric($owner)){
									//Search by userid
									if ($tuser->userid_exists($owner)){
										$g_owner = " AND owner = ?";
										$ga_other[$k+1] = (int)$owner; $k++;
									}
								}else{
									//Search by user name
									if ($owner = $tuser->get_userid($owner)){
										$g_owner = " AND owner = ?";
										$ga_other[$k+1] = $owner; $k++;
									}
								}
							}
						}
						//No delimiters, pass on
						else{
							//Check if using negative operator and format query
                            if(substr($current,0,1) == "-"){
								$current = substr($current,1,strlen($current)-1);
								$wildcard = strpos($current,"*");
								$alias = $tclass->alias($current);
								//Replace with alias to if it exists
                                if($alias !== false){
									if($wildcard === false){
										//$g_tags .= ' SELECT ? AS tag_name, 0 AS include '.$union;
										$g_tags .= ' SELECT ? AS tag_name, 0 AS include '.$union;
										$ga_tags[$i+1] = $alias; $i++;
										//$ga_tags[] = $current;
									}else{
										//$g_tags .= ' SELECT ? AS tag_name, 0 AS include '.$union;
										$g_tags .= ' SELECT ? AS tag_name, 0 AS include '.$union;
										$ga_tags[$i+1] = $alias; $i++;
										//$ga_tags[] = $current;
									}
								}
								//No alias, format normally
                                else{
									if($wildcard == false){
										$g_tags .= ' SELECT ? AS tag_name, 0 AS include '.$union;
										$ga_tags[$i+1] = $current; $i++;
									}else{
										$g_tags .= ' SELECT ? AS tag_name, 0 AS include '.$union;
										$ga_tags[$i+1] = $current; $i++;
									}
								}
							}
							//Check if using wildcard operator and format query
							/**
                            else if(substr($current,0,1) == "~"){
								$current = substr($current,1,strlen($current)-1);
								$alias = $tclass->alias($current);
								//Replace with alias to if it exists
                                if($alias !== false){
									$g_tags .= " $alias";
									$g_tags .= " $current";
								}
								//No alias, format normally
                                else{
									$g_tags .= " $current";
                                }
							}
							**/
							//No operators added, assume + and format query
                            else{
								$wildcard = strpos($current,"*");
								$alias = $tclass->alias($current);
								//Replace with alias to if it exists
                                if($alias !== false){
									if($wildcard == false){
										$g_tags .= ' SELECT ? AS tag_name, 1 AS include '.$union;
										$ga_tags[$i+1] = $alias; $i++; $tagcount++;
									}else{
										$g_tags .= ' SELECT ? AS tag_name, 1 AS include '.$union;
										$ga_tags[$i+1] = $alias; $i++; $tagcount++;
									}
								}
								//No alias, format normally
                                else{
									if($wildcard === false){
										$g_tags .= ' SELECT ? AS tag_name, 1 AS include '.$union;
										$ga_tags[$i+1] = $current; $i++; $tagcount++;
									}else{
										$g_tags .= ' SELECT ? AS tag_name, 1 AS include '.$union;
										$ga_tags[$i+1] = $current; $i++; $tagcount++;
									}
								}	
							}
						}
					}
				}
            }
			//Strip spare union all from end of tags string if it exists
			if (count($ga_tags) == substr_count($g_tags, 'UNION ALL')){
				$g_tags = substr($g_tags, 0, -9);
			}
			//Search contains tags, process
            if($g_tags != ""){
				//Create temp table for tags and ids
				$tmptblcreate = $db->exec("
				CREATE TEMPORARY TABLE temp_tagid (
					tag_id INT,
					tag_name VARCHAR(50),
					include TINYINT(1)
				)");
				
				//Insert tags and ids into temp table
				$tmptblinsert = $db->exec("
				INSERT INTO temp_tagid (tag_id, tag_name, include)
				SELECT t.id, v.tag_name, v.include
				FROM ".$f3->get('tags_table')." AS t 
				INNER JOIN (
					$g_tags
				) AS v ON v.tag_name = t.name
				",$ga_tags);

				//Run actual query to get the post data
				$searchresult = $db->exec("
				SELECT id, creation_date, source, title, description, hash, score, rating, tags, owner, ext, status, dnp FROM
				(SELECT pt1.post_id
				FROM ".$f3->get('poststags_table')." pt1
				INNER JOIN temp_tagid t1
					ON pt1.tag_id = t1.tag_id
				GROUP BY pt1.post_id
				HAVING  COUNT(DISTINCT CASE WHEN t1.include = 1 THEN tag_name ELSE NULL END) = $tagcount AND 
						COUNT(DISTINCT CASE WHEN t1.include = 0 THEN tag_name ELSE NULL END) = 0
				) AS postids
				INNER JOIN ".$f3->get('post_table')." AS po ON (postids.post_id = po.id)
				WHERE 1=1 $dnp $g_parent $g_owner $g_rating $parent_patch
				$dnp
				ORDER BY id DESC LIMIT $start, $limit
				",$ga_other);
				
				//Drop temp table
				$tmptblremove = $db->exec("DROP TABLE temp_tagid");
			}
			//Search doesn't contain tags, check if it specifies owner, rating, or parent
            else if($g_parent != "" || $g_owner != "" || $g_rating != ""){
				//Run actual query to get the post data
				$searchresult = $db->exec("
				SELECT id, creation_date, source, title, description, hash, score, rating, tags, owner, ext, status, dnp FROM
				".$f3->get('post_table')."
				WHERE 1=1 $g_parent $g_owner $g_rating $parent_patch
				$dnp
				ORDER BY id DESC LIMIT $start, $limit
				",$ga_other);
			}
			return $searchresult;
		}

		function preprare_search($search){
			global $db, $f3;
			$tclass = new tag();
			$tuser = new user();
			$tags = '';
			$aliased_tags = '';
			$original_tags = '';
			$parent = '';
			$ttags = array_filter(array_map('trim', explode(" ",trim($search))));
			$g_rating = '';
			$g_owner = '';
			$g_tags = '';
			$ga_tags = array();
			$ga_other = array();
			$g_parent = '';
			$i = 0;
			$k = 0;
			$ttagslen = count($ttags);
			$tagcount = 0;
			
            //Level check for DNP
            if ($f3->get('checked_user_group') >= $f3->get('dnp_level')){
                $dnp = "";
            }else{
			    if ($f3->get('checked_user_id') !== null){
			    	$dnp = "AND (dnp=0 AND status = 'active' OR owner = ".$f3->get('checked_user_id').')';
			    }else{
			    	$dnp = "AND dnp=0 AND status = 'active'";
			    }
            }
			
			//Break out each tag
            foreach($ttags as $current){
				//Check if parent delimiter
                if(strpos(strtolower($current),'parent:') !== false){
					$parent = str_replace("parent:","",$current);
 					if(!is_numeric($parent)){
						$g_parent = '';					
					}else{
						$g_parent = " AND parent = ?";
						$ga_other[$k+1] = $parent; $k++;
					}
					$current = '';
				}
				//Check if not whitespace
                if($current != "" && $current != " "){
					$len = strlen($current);
					$count = substr_count($current, '*', 0, $len);
					//Check if greater than or equal to 2 characters w/ wildcard stripped
                    if(($len - $count) >= 2){
					    //Check if last in array or not, used for union join
					    if ($i == $ttagslen - 1 || $current == "+" || $current == "-"){
							$union = "";
					    }else{
					        $union = "UNION ALL";
					    }
						//Check if score delimiter
                        if(strpos(strtolower($current),'score:')  !== false){
							$score = str_replace('score:','',$current);
							//Check operator, if negative or positive
							if(substr($current,0,1) == "-"){
								$score = substr($score,1,strlen($score)-1);
								//Check if score is a number
								if(is_numeric($score)){
									$g_owner = " AND score != ?";
									$ga_other[$k+1] = (int)$score; $k++;
								}
							}else{
								//Check for second operator
								if(substr($score,0,1) == "<"){
									$score = substr($score,1,strlen($score)-1);
									//Check if score is a number
									if(is_numeric($score)){
										$g_owner = " AND score < ?";
										$ga_other[$k+1] = (int)$score; $k++;
									}				
								}elseif(substr($score,0,1) == ">"){
									$score = substr($score,1,strlen($score)-1);
									//Check if score is a number
									if(is_numeric($score)){
										$g_owner = " AND score > ?";
										$ga_other[$k+1] = (int)$score; $k++;
									}				
								}else{
									//Check if score is a number
									if(is_numeric($score)){
										$g_owner = " AND score = ?";
										$ga_other[$k+1] = (int)$score; $k++;
									}									
								}
							}
						}
						//Check if rating delimiter
                        else if(strpos(strtolower($current),'rating:')  !== false){
							$rating = str_replace('rating:','',$current);
							//Check operator, if negative or positive
							if(substr($current,0,1) == "-"){
								//Make sure rating fits types, if not then set query to null
								if($rating == "safe" || $rating == "s"){
									$g_rating .= " AND rating != 's'";
								}elseif($rating == "questionable" || $rating == "q"){
									$g_rating .= " AND rating != 'q'";
								}elseif($rating == "explicit" || $rating == "e"){
									$g_rating .= " AND rating != 'e'";
								}else{
									$g_rating .= "";
								}
							}else{
								//Make sure rating fits types, if not then set query to null
								if($rating == "safe" || $rating == "s"){
									$g_rating .= " AND rating = 's'";
								}elseif($rating == "questionable" || $rating == "q"){
									$g_rating .= " AND rating = 'q'";
								}elseif($rating == "explicit" || $rating == "e"){
									$g_rating .= " AND rating = 'e'";
								}else{
									$g_rating .= "";
								}
							}
						}
						//Check if user delimiter
                        else if(strpos(strtolower($current),'user:')  !== false){
							$owner = str_replace('user:','',$current);
							if(substr($current,0,1) == "-"){
								$owner = substr($owner,1,strlen($owner)-1);
								//Check if userid or username
								if(is_numeric($owner)){
									//Search by userid
									if ($tuser->userid_exists($owner)){
										$g_owner = " AND owner != ?";
										$ga_other[$k+1] = (int)$owner; $k++;
									}
								}else{
									//Search by user name
									if ($owner = $tuser->get_userid($owner)){
										$g_owner = " AND owner != ?";
										$ga_other[$k+1] = $owner; $k++;
									}
								}
							}else{
								//Check if userid or username
								if(is_numeric($owner)){
									//Search by userid
									if ($tuser->userid_exists($owner)){
										$g_owner = " AND owner = ?";
										$ga_other[$k+1] = (int)$owner; $k++;
									}
								}else{
									//Search by user name
									if ($owner = $tuser->get_userid($owner)){
										$g_owner = " AND owner = ?";
										$ga_other[$k+1] = $owner; $k++;
									}
								}
							}
						}
						//No delimiters, pass on
						else{
							//Check if using negative operator and format query
                            if(substr($current,0,1) == "-"){
								$current = substr($current,1,strlen($current)-1);
								$wildcard = strpos($current,"*");
								$alias = $tclass->alias($current);
								//Replace with alias to if it exists
                                if($alias !== false){
									if($wildcard === false){
										//$g_tags .= ' SELECT ? AS tag_name, 0 AS include '.$union;
										$g_tags .= ' SELECT ? AS tag_name, 0 AS include '.$union;
										$ga_tags[$i+1] = $alias; $i++;
										//$ga_tags[] = $current;
									}else{
										//$g_tags .= ' SELECT ? AS tag_name, 0 AS include '.$union;
										$g_tags .= ' SELECT ? AS tag_name, 0 AS include '.$union;
										$ga_tags[$i+1] = $alias; $i++;
										//$ga_tags[] = $current;
									}
								}
								//No alias, format normally
                                else{
									if($wildcard == false){
										$g_tags .= ' SELECT ? AS tag_name, 0 AS include '.$union;
										$ga_tags[$i+1] = $current; $i++;
									}else{
										$g_tags .= ' SELECT ? AS tag_name, 0 AS include '.$union;
										$ga_tags[$i+1] = $current; $i++;
									}
								}
							}
							//Check if using wildcard operator and format query
                            /**
                            else if(substr($current,0,1) == "~"){
								$current = substr($current,1,strlen($current)-1);
								$alias = $tclass->alias($current);
								//Replace with alias to if it exists
                                if($alias !== false){
									$g_tags .= " $alias";
									$g_tags .= " $current";
								}
								//No alias, format normally
                                else{
									$g_tags .= " $current";
                                }
							}
							**/
							//No operators added, assume + and format query
                            else{
								$wildcard = strpos($current,"*");
								$alias = $tclass->alias($current);
								//Replace with alias to if it exists
                                if($alias !== false){
									if($wildcard == false){
										$g_tags .= ' SELECT ? AS tag_name, 1 AS include '.$union;
										$ga_tags[$i+1] = $alias; $i++; $tagcount++;
									}else{
										$g_tags .= ' SELECT ? AS tag_name, 1 AS include '.$union;
										$ga_tags[$i+1] = $alias; $i++; $tagcount++;
									}
								}
								//No alias, format normally
                                else{
									if($wildcard === false){
										$g_tags .= ' SELECT ? AS tag_name, 1 AS include '.$union;
										$ga_tags[$i+1] = $current; $i++; $tagcount++;
									}else{
										$g_tags .= ' SELECT ? AS tag_name, 1 AS include '.$union;
										$ga_tags[$i+1] = $current; $i++; $tagcount++;
									}
								}	
							}
						}
					}
				}
            }
			//Strip spare union all from end of tags string if it exists
			if (count($ga_tags) == substr_count($g_tags, 'UNION ALL')){
				$g_tags = substr($g_tags, 0, -9);
			}
			//Search contains tags, process
            if($g_tags != ""){
				//Create temp table for tags and ids
				$tmptblcreate = $db->exec("
				CREATE TEMPORARY TABLE temp_tagid (
					tag_id INT,
					tag_name VARCHAR(50),
					include TINYINT(1)
				)");
				
				//Insert tags and ids into temp table
				$tmptblinsert = $db->exec("
				INSERT INTO temp_tagid (tag_id, tag_name, include)
				SELECT t.id, v.tag_name, v.include
				FROM ".$f3->get('tags_table')." AS t 
				INNER JOIN (
					$g_tags
				) AS v ON v.tag_name = t.name
				",$ga_tags);

				//Run actual query to get the post data
				$searchresult = $db->exec("
				SELECT count(id) as count FROM
				(SELECT pt1.post_id
				FROM ".$f3->get('poststags_table')." pt1
				INNER JOIN temp_tagid t1
					ON pt1.tag_id = t1.tag_id
				GROUP BY pt1.post_id
				HAVING  COUNT(DISTINCT CASE WHEN t1.include = 1 THEN tag_name ELSE NULL END) = $tagcount AND 
						COUNT(DISTINCT CASE WHEN t1.include = 0 THEN tag_name ELSE NULL END) = 0
				) AS postids
				INNER JOIN ".$f3->get('post_table')." AS po ON (postids.post_id = po.id)
				WHERE 1=1 $dnp $g_parent $g_owner $g_rating $parent_patch
				$dnp
				",$ga_other);
				
				//Drop temp table
				$tmptblremove = $db->exec("DROP TABLE temp_tagid");
			}
			//Search doesn't contain tags, check if it specifies owner, rating, or parent
            else if($g_parent != "" || $g_owner != "" || $g_rating != ""){
				//Run actual query to get the post data
				$searchresult = $db->exec("
				SELECT count(id) as count FROM
				".$f3->get('post_table')."
				WHERE 1=1 $g_parent $g_owner $g_rating $parent_patch
				$dnp
				",$ga_other);
			}
			return $searchresult;
		}
		
		function search_tags_count($search){
			global $f3;
			$date = date("Ymd");
			$query = "SELECT COUNT(*) FROM ".$f3->get('post_table')."".$search;
			return $query;
		}
		
		function search_tags($search,$condition){
			global $f3;
			$date = date("Ymd");
			$query = "SELECT id, hash, score, rating, tags, owner, status, dnp FROM ".$f3->get('post_table').$search.$condition;
			return $query;
		}
		function autocomplete(){
		    global $db, $f3;
		    if ($f3->get('POST.tags') == ""){
			   exit();
		    }
		    $tagsearch = "{$f3->get('POST.tags')}%";
			$result = $db->exec('SELECT id,name,tag_type FROM '.$f3->get('tags_table').' WHERE name LIKE ? ORDER BY tag_type DESC LIMIT 30',array(1=>$tagsearch));
			$data = "<ul>";
		    
		    foreach($result as $r){
		       	switch ($r['tag_type']) {
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
		        $data .= "<li class=\"$tagcolor\">".$r['name']."</li>";
			}
			$data .= "</ul>";
		    //Format that JSON
		    echo $data;
		}
	}
?>