<?php
	class images{
		private $image_path;
		private $thumbnail_path;
        private $thumbnail_path_ava;
		private $dimension;
        private $dimensionava;
		public $error;
		function __construct(){
			global $f3;
			$this->image_path = $f3->get('image_folder');
			$this->thumbnail_path = $f3->get('thumbnail_folder');
            $this->thumbnail_path_ava = $f3->get('thumbnail_folder_ava');
			$this->dimension = $f3->get('dimension');
            $this->dimensionava = $f3->get('dimensionava');
		}
		function ImageCreateFromBMP($filename){
		/*********************************************/
		/* Fonction: ImageCreateFromBMP              */
		/* Author:   DHKold                          */
		/* Contact:  admin@dhkold.com                */
		/* Date:     The 15th of June 2005           */
		/* Version:  2.0B                            */
		/*********************************************/
		 //Ouverture du fichier en mode binaire
		   if (! $f1 = fopen($filename,"rb")) return FALSE;

		 //1 : Chargement des ent?tes FICHIER
		   $FILE = unpack("vfile_type/Vfile_size/Vreserved/Vbitmap_offset", fread($f1,14));
		   if ($FILE['file_type'] != 19778) return FALSE;

		 //2 : Chargement des ent?tes BMP
		   $BMP = unpack('Vheader_size/Vwidth/Vheight/vplanes/vbits_per_pixel'.
						 '/Vcompression/Vsize_bitmap/Vhoriz_resolution'.
						 '/Vvert_resolution/Vcolors_used/Vcolors_important', fread($f1,40));
		   $BMP['colors'] = pow(2,$BMP['bits_per_pixel']);
		   if ($BMP['size_bitmap'] == 0) $BMP['size_bitmap'] = $FILE['file_size'] - $FILE['bitmap_offset'];
		   $BMP['bytes_per_pixel'] = $BMP['bits_per_pixel']/8;
		   $BMP['bytes_per_pixel2'] = ceil($BMP['bytes_per_pixel']);
		   $BMP['decal'] = ($BMP['width']*$BMP['bytes_per_pixel']/4);
		   $BMP['decal'] -= floor($BMP['width']*$BMP['bytes_per_pixel']/4);
		   $BMP['decal'] = 4-(4*$BMP['decal']);
		   if ($BMP['decal'] == 4) $BMP['decal'] = 0;

		 //3 : Chargement des couleurs de la palette
		   $PALETTE = array();
		   if ($BMP['colors'] < 16777216 && $BMP['colors'] != 65536)
		   {
			$PALETTE = unpack('V'.$BMP['colors'], fread($f1,$BMP['colors']*4));
			#nei file a 16bit manca la palette,
		   }

		 //4 : Cr?ation de l'image
		   $IMG = fread($f1,$BMP['size_bitmap']);
		   $VIDE = chr(0);

		   $res = imagecreatetruecolor($BMP['width'],$BMP['height']);
		   $P = 0;
		   $Y = $BMP['height']-1;
		   while ($Y >= 0)
		   {
			$X=0;
			while ($X < $BMP['width'])
			{
			 if ($BMP['bits_per_pixel'] == 24)
				$COLOR = unpack("V",substr($IMG,$P,3).$VIDE);
			 elseif ($BMP['bits_per_pixel'] == 16)
			 { 
				$COLOR = unpack("n",substr($IMG,$P,2));
				$blue  = (($COLOR[1] & 0x001f) << 3) + 7;
				$green = (($COLOR[1] & 0x03e0) >> 2) + 7;
				$red   = (($COLOR[1] & 0xfc00) >> 7) + 7;
				$COLOR[1] = $red * 65536 + $green * 256 + $blue;
			 }
			 elseif ($BMP['bits_per_pixel'] == 8)
			 { 
				$COLOR = unpack("n",$VIDE.substr($IMG,$P,1));
				$COLOR[1] = $PALETTE[$COLOR[1]+1];
			 }
			 elseif ($BMP['bits_per_pixel'] == 4)
			 {
				$COLOR = unpack("n",$VIDE.substr($IMG,floor($P),1));
				if (($P*2)%2 == 0) $COLOR[1] = ($COLOR[1] >> 4) ; else $COLOR[1] = ($COLOR[1] & 0x0F);
				$COLOR[1] = $PALETTE[$COLOR[1]+1];
			 }
			 elseif ($BMP['bits_per_pixel'] == 1)
			 {
				$COLOR = unpack("n",$VIDE.substr($IMG,floor($P),1));
				if     (($P*8)%8 == 0) $COLOR[1] =  $COLOR[1]        >>7;
				elseif (($P*8)%8 == 1) $COLOR[1] = ($COLOR[1] & 0x40)>>6;
				elseif (($P*8)%8 == 2) $COLOR[1] = ($COLOR[1] & 0x20)>>5;
				elseif (($P*8)%8 == 3) $COLOR[1] = ($COLOR[1] & 0x10)>>4;
				elseif (($P*8)%8 == 4) $COLOR[1] = ($COLOR[1] & 0x8)>>3;
				elseif (($P*8)%8 == 5) $COLOR[1] = ($COLOR[1] & 0x4)>>2;
				elseif (($P*8)%8 == 6) $COLOR[1] = ($COLOR[1] & 0x2)>>1;
				elseif (($P*8)%8 == 7) $COLOR[1] = ($COLOR[1] & 0x1);
				$COLOR[1] = $PALETTE[$COLOR[1]+1];
			 }
			 else
				return FALSE;
			 imagesetpixel($res,$X,$Y,$COLOR[1]);
			 $X++;
			 $P += $BMP['bytes_per_pixel'];
			}
			$Y--;
			$P+=$BMP['decal'];
		   }

		 //Fermeture du fichier
		   fclose($f1);

		 return $res;
		}

		function validate_video($file){
			//Set data array
			$video_info = array();
			try{
				//Init FFMpeg
				$ffprobe = FFMpeg\FFProbe::create(array(
				    'ffmpeg.binaries'  => '../ffmpeg/ffmpeg',
				    'ffprobe.binaries' => '../ffmpeg/ffprobe',
				    'timeout'          => 3600, // The timeout for the underlying process
				    'ffmpeg.threads'   => 12,   // The number of threads that FFMpeg should use
				));
				//Pull video information
				$video_data = $ffprobe->streams($file);
				$video_format = $ffprobe->format($file);
				//Check for valid container (webm or mp4)
				if(strpos($video_format->get('format_name'),'webm') !== false || $video_format->get('tags')['major_brand'] == 'mp42'){
	    			//Save out data we are going to use
	    			$video_info['codec_name'] = $video_data->videos()->first()->get('codec_name');
	    			$video_info['codec_type'] = $video_data->videos()->first()->get('codec_type');
	    			$video_info['width'] = $video_data->videos()->first()->get('width');
	    			$video_info['height'] = $video_data->videos()->first()->get('height');
				}else{
				    return false;
				}
			}catch(Exception $e){
				//Error getting video data
				return false;	
			}
			//Check for valid data
			if(!empty($video_info)){
				//Send back video data
				return $video_info;
			}else{
				//Error getting video data
				return false;
			}
		}
		
		function avatar_thumb($image, $uid){
			if($image == "")
				return false;
			$ext = explode('.',$image['name']);
			$count = count($ext);
			$ext = $ext[$count-1];
			$ext = strtolower($ext);
			if($ext != "jpg" && $ext != "jpeg" && $ext != "gif" && $ext != "png" && $ext != "bmp")
				return false;
			$ext = ".".$ext;
			$fname = hash_file('md5',$image['tmp_name']);
			move_uploaded_file($image['tmp_name'],"./tmp/".$fname.$ext);
			$f = fopen("./tmp/".$fname.$ext,"rb");
			if($f == "")
				return false;
			$data = '';
			while(!feof($f))
				$data .= fread($f,4096);
			fclose($f);
            if(preg_match("#<script|<html|<head|<title|<body|<pre>|<table|<a\s+href|<img|<plaintext#si", $data) == 1)
			{	
				unlink("./tmp/".$fname.$ext);
				return false;
			}
			$iinfo = getimagesize("./tmp/".$fname.$ext);
			if(substr($iinfo['mime'],0,5) != "image" || !$this->checksum("./tmp/".$fname.$ext))
			{
				unlink("./tmp/".$fname.$ext);
				return false;
			}
            $image = "./tmp/".$fname.$ext;
			$flname = md5($data).$ext;
            $timage = explode("/",$image);
 
			$ext = explode(".",$image);
			$count = count($ext);
			$ext = $ext[$count-1];
			$hash = str_replace('.','',str_replace($ext, '', $timage[1]));
            $ext = ".".$ext;
			$thumbnail_name = "".$image;
			$imginfo = getimagesize($image);
			$tmp_ext = ".".str_replace("image/","",$imginfo['mime']);

			if($tmp_ext != $ext)
			{
				$ext = $tmp_ext;
			}
			if($ext == ".jpg" || $ext == ".jpeg")
				$img = imagecreatefromjpeg($image);
			else if($ext == ".gif")
				$img = imagecreatefromgif($image);
			else if($ext == ".png")
				$img = imagecreatefrompng($image);
			else if($ext == ".bmp")
				$img = $this->imagecreatefrombmp($image);
			else
				return false;
			
			if($img == NULL)
				return false;
				
			$imginfo = getimagesize($image);
			$max = ($imginfo[0] > $imginfo[1]) ? $imginfo[0] : $imginfo[1];
			$scale = ($max < $this->dimensionava) ? 1 : $this->dimensionava / $max;
			$width = $imginfo[0] * $scale;
			$height = $imginfo[1] * $scale;
			$thumbnail = imagecreatetruecolor($width,$height);
			imagecopyresampled($thumbnail,$img,0,0,0,0,$width,$height,$imginfo[0],$imginfo[1]);

            if($ext == ".jpg" || $ext == ".jpeg"){
                ob_start();
                imagejpeg($thumbnail);
                $data = ob_get_clean();
                $avamd5 = md5($data);
                file_put_contents("./".$this->thumbnail_path_ava."/".$uid."_".$avamd5.$ext, $data);
                //imagejpeg($thumbnail,"./".$this->thumbnail_path_ava."/".$uid."_".$avamd5.".jpg",95);
			}else if($ext == ".gif"){
                ob_start();
                imagejpeg($thumbnail);
                $data = ob_get_clean();
                $avamd5 = md5($data);
                file_put_contents("./".$this->thumbnail_path_ava."/".$uid."_".$avamd5.".gif", $data);
                //imagegif($thumbnail,"./".$this->thumbnail_path_ava."/".$uid."_".$avamd5.".gif");
			}else if($ext == ".png"){
                ob_start();
                imagejpeg($thumbnail);
                $data = ob_get_clean();
                $avamd5 = md5($data);
                file_put_contents("./".$this->thumbnail_path_ava."/".$uid."_".$avamd5.".png", $data);
                //imagepng($thumbnail,"./".$this->thumbnail_path_ava."/".$uid."_".$avamd5.".png");
			}else if($ext == ".bmp"){
                ob_start();
                imagejpeg($thumbnail);
                $data = ob_get_clean();
                $avamd5 = md5($data);
                file_put_contents("./".$this->thumbnail_path_ava."/".$uid."_".$avamd5.".jpg", $data);
				//imagejpeg($thumbnail,"./".$this->thumbnail_path_ava."/".$uid."_".$avamd5.".jpg",95);
			}else{
				return false;
            }
			imagedestroy($img);
			imagedestroy($thumbnail);
			
            unlink("./tmp/".$fname.$ext);                
            return $avamd5.$ext;
		}

		function thumbnail($image){
				$ext = explode(".",$image);
				$count = count($ext);
				$ext = $ext[$count-1];
				$hash = str_replace('.','',str_replace($ext, '', $image));
                $ext = ".".$ext;
				$thumbnail_name = "".$image;
				$image = "./".$this->image_path."/".$image;
				$imginfo = getimagesize($image);
				$tmp_ext = ".".str_replace("image/","",$imginfo['mime']);
				if($tmp_ext != $ext){
					$ext = $tmp_ext;
				}
				if($ext == ".jpg" || $ext == ".jpeg"){
					$img = imagecreatefromjpeg($image);
				}else if($ext == ".gif"){
					$img = imagecreatefromgif($image);
				}else if($ext == ".png"){
					$img = imagecreatefrompng($image);
				}else if($ext == ".bmp"){
					$img = $this->imagecreatefrombmp($image);
				}else{
					$this->error = "Unsupported image extension, upload failed.";
					return false;
				}
				if($img == NULL){
					$this->error = "Thumbnail generation failed, invalid image data.";
					return false;
				}
				$imginfo = getimagesize($image);
				$max = ($imginfo[0] > $imginfo[1]) ? $imginfo[0] : $imginfo[1];
				$scale = ($max < $this->dimension) ? 1 : $this->dimension / $max;
				$width = $imginfo[0] * $scale;
				$height = $imginfo[1] * $scale;
				$thumbnail = imagecreatetruecolor($width,$height);
				imagecopyresampled($thumbnail,$img,0,0,0,0,$width,$height,$imginfo[0],$imginfo[1]);
				imagejpeg($thumbnail,"./".$this->thumbnail_path."/".$hash.".jpg",95);
				imagedestroy($img);
				imagedestroy($thumbnail);
				return true;
		}
		
		function getremoteimage($url){
			global $f3;
			$swf = new swfheader(false);
			$misc = new misc();
			//Check if valid URL
			if($url == "" || $url == " "){
				$this->error = "Invalid URL, upload failed.";
				return false;
			}
			//Format file extension
			$ext = explode('.',$url);
			$count = count($ext);
			$ext = $ext[$count-1];
			$ext = strtolower($ext);
			//Check for supported filetypes
			if($ext != "jpg" && $ext != "jpeg" && $ext != "gif" && $ext != "png" && $ext != "bmp" && $ext != "swf" && $ext != "webm" && $ext != "mp4"){
				$this->error = "Unsupported image extension, upload failed.";
				return false;
			}
			$ext = ".".$ext;
			$valid_download = false;
			$dl_count = 0;
			$name = basename($url);
			$host = parse_url($url, PHP_URL_HOST);
			//Start image download
			while(!$valid_download){
				//Open handle for cURL
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:39.0) Gecko/20100101 Firefox/39.0 Waterfox/39.0');
				//Set URL, referrer, and other options
				if(strpos($url,'pixiv') !== false){
					curl_setopt($ch, CURLOPT_REFERER, 'http://www.pixiv.net/');					
				}
				//Grab file from URL and store in var
				$data = curl_exec($ch);
				//Close cURL handle
				curl_close($ch);
				//Write data into temp file
				if($dl_count == 0){				
					$l = fopen("./tmp/".$name."0".$ext,"w");
					fwrite($l,$data);
					fclose($l);
				}
				if($dl_count == 1){
					$l = fopen("./tmp/".$name."1".$ext,"w");
					fwrite($l,$data);
					fclose($l);
				}
				//Check to make sure we have run through the loop twice and compare downloaded files
				if($dl_count == 1){
					//Calculate file sizes of both downloads
					$tmp_size = filesize("./tmp/".$name."0".$ext);
					$size = filesize("./tmp/".$name."1".$ext);
					//Compare file sizes
					if($tmp_size >= $size){
						//Hash temp file and store hash
						$filename = hash_file('md5',"./tmp/".$name.$dl_count.$ext);
						//Both files match, its all good. Cleanup and move on
						$valid_download = true;
						unlink("./tmp/".$name."0".$ext);
						unlink("./tmp/".$name."1".$ext);
					}else{
						//Something went wrong, files do not match. Cleanup and move on.
						unlink("./tmp/".$name."0".$ext);
						copy("./tmp/".$name."1".$ext,"./tmp/".$name."0".$ext);
						unlink("./tmp/".$name."1".$ext);
						$dl_count = 0;
					}
				}
				//Loop again until we have met the required download times or get a valid download
				$dl_count++;
			}
			//Check for exploits & shit
			if(preg_match("#<script|<html|<head|<title|<body|<pre>|<table|<a\s+href|<img|<plaintext#si", $data) == 1){
				$this->error = "Found malicious data in image, upload failed.";
				return false;
			}
			//Open file handle for actual image
			$f = fopen("./images/".$filename.$ext,"w");
			//Exit if we can't open for writing
			if($f == ""){
				$this->error = "Unable to read image data while moving to images folder, upload failed.";
				return false;
			}
			//Write image
			fwrite($f,$data);
			fclose($f);
			//Check MIME type, image sizes and hash
			//Check if flash file / video or regular image
			if ($ext == ".swf"){
				//Open the swf file
				$swf->loadswf("./images/".$filename.$ext);
				if (!$swf->valid || !$this->checksum("./images/".$filename.$ext)){
					$this->error = "Not a valid flash file, upload failed.";
					return false;
				}
			}elseif($ext == ".webm" || $ext == ".mp4"){
				//Get video headers and check
				$videodata = $this->validate_video("./tmp/".$fname.$ext);
				if (!$videodata || !$this->checksum("./tmp/".$fname.$ext)){
					$this->error = "Not a valid mp4 or webm file, upload failed.";
					return false;
				}
			}else{
				$iinfo = getimagesize("./images/".$filename.$ext);
				$ichecksum = $this->checksum("./images/".$filename.$ext);
				if(substr($iinfo['mime'],0,5) != "image" || $iinfo[0] < $f3->get('min_upload_width') && $f3->get('min_upload_width') != 0 || $iinfo[0] > $f3->get('max_upload_width') && $f3->get('max_upload_width') != 0 || $iinfo[1] < $f3->get('min_upload_height') && $f3->get('min_upload_height') != 0 || $iinfo[1] > $f3->get('max_upload_height') && $f3->get('max_upload_height') != 0 || !$ichecksum){
					if ($ichecksum){
						$this->error = "Unsupported image extension or image too big, upload failed.";
					}
					unlink("./images/".$filename.$ext);
					return false;
				}
			}
			//Return filename
			$ext = substr($ext, 1);
			return $filename.":".$ext;
		}
		
		function process_upload($upload){
			global $f3;
			$swf = new swfheader(false);
			//Check if valid data
			if($upload == ""){
				$this->error = "No image data sent, upload failed.";
				return false;
			}
			$ext = explode('.',$upload['name']);
			$count = count($ext);
			$ext = $ext[$count-1];
			$ext = strtolower($ext);
			//Check for supported filetypes
			if($ext != "jpg" && $ext != "jpeg" && $ext != "gif" && $ext != "png" && $ext != "bmp" && $ext != "swf" && $ext != "webm" && $ext != "mp4"){
				$this->error = "Unsupported image extension, upload failed.";
				return false;
			}
			//Break extension out
			$ext = ".".$ext;
			//Hash file and move into tmp folder
			$fname = hash_file('md5',$upload['tmp_name']);
			move_uploaded_file($upload['tmp_name'],"./tmp/".$fname.$ext);
			//Import file handle for what we just moved and check to make sure its valid
			$f = fopen("./tmp/".$fname.$ext,"rb");
			if($f == ""){
				$this->error = "Unable to read image data, upload failed.";
				return false;
			}
			//Read file contents into var
			$data = '';
			while(!feof($f)){
				$data .= fread($f,4096);
			}
			fclose($f);
			//Check for exploits & shit
			if(preg_match("#<script|<html|<head|<title|<body|<pre>|<table|<a\s+href|<img|<plaintext#si", $data) == 1){	
				unlink("./tmp/".$fname.$ext);
				$this->error = "Found malicious data in image, upload failed.";
				return false;
			}
			//Check if flash file / video or regular image
			if ($ext == ".swf"){
				//Open the swf file
				$swf->loadswf("./tmp/".$fname.$ext);
				if (!$swf->valid || !$this->checksum("./tmp/".$fname.$ext)){
					$this->error = "Not a valid flash file, upload failed.";
					return false;
				}
			}elseif($ext == ".webm" || $ext == ".mp4"){
				//Get video headers and check
				$videodata = $this->validate_video("./tmp/".$fname.$ext);
				if (!$videodata || !$this->checksum("./tmp/".$fname.$ext)){
					$this->error = "Not a valid mp4 or webm file, upload failed.";
					return false;
				}
			}else{
				//Make sure image conforms to site width & height policies
				$iinfo = getimagesize("./tmp/".$fname.$ext);
				$ichecksum = $this->checksum("./tmp/".$fname.$ext);
				if(substr($iinfo['mime'],0,5) != "image" || $iinfo[0] < $f3->get('min_upload_width') && $f3->get('min_upload_width') != 0 || $iinfo[0] > $f3->get('max_upload_width') && $f3->get('max_upload_width') != 0 || $iinfo[1] < $f3->get('min_upload_height') && $f3->get('min_upload_height') != 0 || $iinfo[1] > $f3->get('max_upload_height') && $f3->get('max_upload_height') != 0 || !$ichecksum){
					if ($ichecksum){
						$this->error = "Unsupported image extension or image too big/too small, upload failed.";
					}
					unlink("./tmp/".$fname.$ext);
					return false;
				}
			}
			$ffname = $fname;
			$i = 0;
			while(file_exists("./images/".$fname.$ext)){
				$i++;
				$fname = hash('md5',$fname.$i);
			}
			$f = fopen("./images/".$fname.$ext,"w");
			if($f == ""){
				$this->error = "Unable to read image data while moving to images folder, upload failed.";
				return false;
			}
			fwrite($f,$data);
			fclose($f);
			unlink("./tmp/".$ffname.$ext);
			$ext = str_replace('.', '' , $ext);
            return $fname.":".$ext;
		}
		
		function removeimage($id){
			global $db, $f3;
			$can_delete = false;
			//Get post id
			$result = $db->exec('SELECT owner, tags, hash, ext FROM '.$f3->get('post_table').' WHERE id = ?',array(1=>$id));
			//Make sure post id is valid
			if (count($result) == 0){
				//Invalid id
				$f3->reroute('/post/all/');
			}else{
				//Valid id, store info
				$owner = $result[0]['owner'];
				$tags = $result[0]['tags'];
				$hash = $result[0]['hash'];
				$ext = $result[0]['ext'];
			}
			
			//Check if user has access to delete
			if(isset($_COOKIE['user_id']) && is_numeric($_COOKIE['user_id']) && isset($_COOKIE['pass_hash'])){
				$user_id = $_COOKIE['user_id'];
				$pass_hash = $_COOKIE['pass_hash'];
				$result = $db->exec('SELECT user FROM '.$f3->get('user_table').' WHERE id = ? AND pass = ?',array(1=>$user_id,2=>$pass_hash));
				$user = $result[0]['user'];
				
				$result = $db->exec('SELECT t2.delete_posts FROM '.$f3->get('user_table').' AS t1 JOIN '.$f3->get('group_table').' AS t2 ON t2.id=t1.ugroup WHERE t1.id = ? AND t1.pass = ?',array(1=>$user_id,2=>$pass_hash));
				if(strtolower($user) == strtolower($owner) && $user != "Anonymous" || $result[0]['delete_posts'] == true){
					$can_delete = true;
				}
			}
			
			if($can_delete == true){
				//Get child posts
				$result = $db->exec('SELECT parent FROM '.$f3->get('post_table').' WHERE id = ?',array(1=>$id));
				//Delete post
				$delete1 = $db->exec('DELETE FROM '.$f3->get('post_table').' WHERE id = ?',array(1=>$id));
				//Delete notes
				$delete2 = $db->exec('DELETE FROM '.$f3->get('note_table').' WHERE post_id = ?',array(1=>$id));
				//Delete note history
				$delete3 = $db->exec('DELETE FROM '.$f3->get('note_history_table').' WHERE post_id = ?',array(1=>$id));
				//Delete comments
				$delete4 = $db->exec('DELETE FROM '.$f3->get('comment_table').' WHERE post_id = ?',array(1=>$id));
				//Delete comment votes
				$delete5 = $db->exec('DELETE FROM '.$f3->get('comment_vote_table').' WHERE post_id = ?',array(1=>$id));
				//Get users that favorited post
				$result2 = $db->exec('SELECT user_id FROM '.$f3->get('favorites_table').' WHERE post_id = ? ORDER BY user_id',array(1=>$id));
				//Loop though each user
				foreach($result2 as $r){
					//Decrement user favorite count
					$update = $db->exec('UPDATE '.$f3->get('favorites_count_table').' SET fcount = fcount - 1 WHERE user_id = ?',array(1=>$r['user_id']));
				}
				//Delete favorites
				$delete6 = $db->exec('DELETE FROM '.$f3->get('favorites_table').' WHERE post_id = ?',array(1=>$id));
				//Set child posts (if any) to have no parent
				$update1 = $db->exec('UPDATE '.$f3->get('post_table').' SET parent=\'\' WHERE parent = ?',array(1=>$id));
				//Delete image and thumbnail
				unlink("images/".$hash.".".$ext);
				unlink("thumbnails/".$hash.".jpg");
				//Start tag information
				$itag = new tag();
				$tags = explode(" ",$tags);
				//Loop through each tag
				$misc = new misc();				
				foreach($tags as $tag){
					//Delete refs to post
					if($tag != ""){
						$itag->deleteindextag($tag);
					}
				}
				return true;
			}
			return false;
		}
		
		function checksum($file){
			global $db, $f3;
			$i = 0;
			$tmp_md5_sum = md5_file($file);
			$result = $db->exec('SELECT id FROM '.$f3->get('post_table').' WHERE hash = ? LIMIT 1',array(1=>$tmp_md5_sum));
			$i = $result[0]['id'];
			
			if($i != "" && $i != NULL){
				$this->error = "That image already exists. You can find it <a href=\"/post/view/$i\">here</a><br />";
				return false;
			}else{
				return true;
			}
		}
		
		function geterror(){
			return $this->error;
		} 
	}
?>
