<?php /**/ ?>#!/home/nivshah/php5/bin/php
<?php
//goes into src directory
// checks if file has a thumbnail
// if it doesn't, build a thumbnail

include('inc/constants.inc.php');
include('inc/functions.php');
function scangalleries($path) {
	$handle = opendir($path);
	for(;(false !== ($file = readdir($handle)));) {
		if($file != '.' && $file != '..') {
			$next_step = $path . '/' . $file;
			if(is_dir($next_step)) {
				scangalleries($next_step);
			}
			if(is_file($next_step)) {

				$extension = '';
				$filetype = exif_imagetype($next_step);
				if($filetype == IMAGETYPE_PNG) {
					$extension = '.png';
				} else if($filetype == IMAGETYPE_JPEG) {
					$extension = '.jpg';
				} else {
					$extension = '';
				}

				// check if a thumbnail exists
				// if it doesn't, make one
				// there are a crapload of files in the cache directory.  if we created a few random directories and
				// populated those with thumbnails, that might be better... how to do this?

				$directories = array();
				$directories = explode('/',$path);
				
				$cache_subdir = base64_encode(array_pop($directories));
				
				$thumb_name = $cache_subdir . '/' . base64_encode($file) . $extension;
				if(!is_file(GALLERY_LOCAL_CACHE . $thumb_name)) {
					// no thumbnail, create one
					// we have to make directories if they don't exist
					if(!is_dir(GALLERY_LOCAL_CACHE . $cache_subdir)) {
						mkdir(GALLERY_LOCAL_CACHE . $cache_subdir);
					}
					
					
					$thumb_img = imagecreatetruecolor(GALLERY_THUMBNAIL_WIDTH,GALLERY_THUMBNAIL_HEIGHT);
					if($filetype == IMAGETYPE_PNG) {
						$src_img = imagecreatefrompng($next_step);
					} else if($filetype == IMAGETYPE_JPEG) {
						$src_img = imagecreatefromjpeg($next_step);
					} else {
						// err
					}
					if(imagesy($src_img) > GALLERY_MAX_HEIGHT) {
						$new_width = (GALLERY_MAX_HEIGHT / imagesy($src_img)) * imagesx($src_img);
						$temp_img = imagecreatetruecolor($new_width,GALLERY_MAX_HEIGHT);
						imagecopyresampled($temp_img,$src_img,0,0,0,0,$new_width,GALLERY_MAX_HEIGHT,imagesx($src_img),imagesy($src_img));
						imagecopyresampled($thumb_img,$temp_img,0,0,(imagesx($temp_img) / 2) - (GALLERY_THUMBNAIL_WIDTH / 2),(imagesy($temp_img) / 2) - (GALLERY_THUMBNAIL_HEIGHT / 2),GALLERY_THUMBNAIL_WIDTH,GALLERY_THUMBNAIL_HEIGHT,GALLERY_THUMBNAIL_WIDTH,GALLERY_THUMBNAIL_HEIGHT);
					}
					else {
						imagecopyresampled($thumb_img,$src_img,0,0,(imagesx($src_img) / 2) - (GALLERY_THUMBNAIL_WIDTH / 2),(imagesy($src_img) / 2) - (GALLERY_THUMBNAIL_HEIGHT / 2),GALLERY_THUMBNAIL_WIDTH,GALLERY_THUMBNAIL_HEIGHT,GALLERY_THUMBNAIL_WIDTH,GALLERY_THUMBNAIL_HEIGHT);
					}
					
					if($filetype == IMAGETYPE_PNG) {
						imagepng($thumb_img,GALLERY_LOCAL_CACHE . $thumb_name);

					} else if($filetype == IMAGETYPE_JPEG) {
						imagejpeg($thumb_img,GALLERY_LOCAL_CACHE . $thumb_name);
					} else {
						// err
					}
						
					// could check if the file was in the database here
					// and if it wasn't, add it.
					$web_path = str_replace(GALLERY_LOCAL_HOME.'src/',GALLERY_WEB_HOME,$next_step);

					$sql = "select count(*) as c from image_index where img_local_path='$next_step'";
					$result = mysql_query($sql);
					$result = mysql_fetch_array($result);

					if($result['c']==0) {
						// add this file to the db
						$sql = "insert into image_index (img_web_path,img_local_path,img_thumbnail) VALUES ('$web_path','$next_step','$thumb_name')";
						mysql_query($sql);
						if(mysql_errno()) {
							print mysql_error();
							exit;
						}
						// extract key words and insert them into the db
						$keywords = array();
						$keywords = extract_xmp_keywords($next_step);
						$image_id = mysql_insert_id();
						if(!empty($keywords)) {
							foreach($keywords as $word) {
								addtag_to_db($word, $image_id);
							}
						}
					} else {
						// the file is in the database but its thumbnail didn't exist, so we should update the thumbnail
						$sql = "update image_index set img_thumbnail='$thumb_name', img_web_path='$web_path' where img_local_path='$next_step'";
						mysql_query($sql);
						if(mysql_errno()) {
							print mysql_error();
							exit;
						}
					}
				} else {
					// the file's thumbnail exists!
					// check if the file is in the database
					$web_path = str_replace(GALLERY_LOCAL_HOME.'src/',GALLERY_WEB_HOME,$next_step);

					$sql = "select count(*) as c from image_index where img_local_path='$next_step'";
					$result = mysql_query($sql);
					$result = mysql_fetch_array($result);

					if($result['c']==0) {
						// add this file to the db
						$sql = "insert into image_index (img_web_path,img_local_path,img_thumbnail) VALUES ('$web_path','$next_step','$thumb_name')";
						mysql_query($sql);
						if(mysql_errno()) {
							print mysql_error();
							exit;
						}
						// extract key words and insert them into the db
						$keywords = array();
						$keywords = extract_xmp_keywords($next_step);
						$image_id = mysql_insert_id();
						if(!empty($keywords)) {
							foreach($keywords as $word) {
								addtag_to_db($word, $image_id);
							}
						}
					}

					// update thumbnail name
					// just in case
					$sql = "update image_index set img_thumbnail='$thumb_name', img_web_path='$web_path' where img_local_path='$next_step'";
					mysql_query($sql);
					if(mysql_errno()) {
						print mysql_error();
						exit;
					}
					
					// extract key words and insert them into the db
					$keywords = array();
					$keywords = extract_xmp_keywords($next_step);
					$image_id = mysql_insert_id();
					if(!empty($keywords)) {
						foreach($keywords as $word) {
							addtag_to_db($word, $image_id);
						}
					}
				}
			}
		}
	}
	closedir($handle);
}

$db = mysql_connect(MYSQL_HOST,MYSQL_USER,MYSQL_PASSWD);
mysql_select_db(MYSQL_DB,$db);

if($argv[1]) {
	$year = $argv[1];
}

if($year) {
	scangalleries(GALLERY_LOCAL_HOME . "src/$year");
}
else {
	scangalleries(GALLERY_LOCAL_HOME . "src");
}

?>
