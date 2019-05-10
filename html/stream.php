<?php
//Load required classes
$user = new user();
$logger = new logger();
$ip = $_SERVER['REMOTE_ADDR'];
		
$cache_seconds = 2592000; // 30 days

//Level check for DNP
if($f3->get('checked_user_group') >= $f3->get('dnp_level')){
    $access = true;
}else{
    $access = false;
}
//Check if post exists for image
if(isset($_GET['mode']) && $_GET['mode'] == "img"){
    $path = "../images/";
}elseif(isset($_GET['mode']) && $_GET['mode'] == "thumb"){
    $path = "../thumbnails/";
    $fext = "jpg";
}else{
   die("Invalid mode.");
}

if(isset($_GET['hash']) && $_GET['hash'] != ""){
	//Lookup hash in database
    $hash = $_GET['hash'];
    $hash = explode(".", $hash);
    $hash = $hash[0];
    $hashinfo = $db->exec('SELECT hash,ext,dnp FROM '.$f3->get('post_table').' WHERE hash = ? LIMIT 1',array(1=>$hash));
    $hashchk = $hashinfo[0];
    
    if(count($hashinfo) !== 0){
        //Image was found in DB
        if($hashchk['dnp'] == 1){
            //Is DNP run checks
            if($access == true){
                //User has access
                if(!isset($fext)){
                   $fext = $hashchk['ext']; 
                }
                $file = $path.$hashchk['hash'].".".$fext;
                if (file_exists($file)){
                    //Stream File
                    if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && (strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == filemtime($file))){
                      header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($file)).' GMT', true, 304);
                      exit;
                    }
                    header("Cache-Control: private");
                    header('Content-Transfer-Encoding: binary');
                    header('Content-Type: image/'.$fext);
                    header('Content-Length: ' . filesize($file));
                    header('Content-Disposition: inline; filename="'.$hashchk['hash'].'.'.$fext.'"');
                    header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($file)));
                    ob_clean();
                    flush();
                    readfile($file);
                    exit;
                }else{
                    //Bad File
                    die("File not found.".$file);    
                }                    
            }else{
                //User does not have access
                die("You do not have access to view this image.");                    
            }    
        }else{
            //Not DNP go on normally
            if(!isset($fext)){
               $fext = $hashchk['ext']; 
            }
            $file = $path.$hashchk['hash'].".".$fext;
            if (file_exists($file)){
                //Stream File
                if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && (strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == filemtime($file))){
                  header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($file)).' GMT', true, 304);
                  exit;
                }
                header("Cache-Control: private");
                header('Content-Transfer-Encoding: binary');
                header('Content-Type: image/'.$fext);
                header('Content-Length: ' . filesize($file));
                header('Content-Disposition: inline; filename="'.$hashchk['hash'].'.'.$fext.'"');
                header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($file)));
                ob_clean();
                flush();
                readfile($file);
                exit;
            }else{
                //Bad File
                die("File not found.".$file);    
            }                
        }
    }else{
        //Bad File
        die("File not found.");
    }
}else{
   die("Invalid Hash.");
}
?>