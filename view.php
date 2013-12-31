<?php /**/ ?><?php
include('inc/constants.inc.php');
include('inc/functions.php');


$image = $_GET['image'];

if(!$image) {
	exit;
}

//DB connection
$db = mysql_connect(MYSQL_HOST,MYSQL_USER,MYSQL_PASSWD);
mysql_select_db(MYSQL_DB,$db);

$full_path = explode('/',$image);
$title = array_pop($full_path);
$filename = "src/" . $image;
$path = implode('/',$full_path);

//get image ID from DB
$sql = "select ID from image_index where img_local_path='".GALLERY_LOCAL_HOME."$filename'";
$result = mysql_query($sql);
$result = mysql_fetch_array($result);
$image_id = $result['ID'];

//get prev and next images

$dir = GALLERY_LOCAL_HOME . 'src/' . $path;
if($handle = opendir($dir)) {
	while(false !== ($file = readdir($handle))) {
		if($file != '..' && $file !='.' && $file !='.metadata') {
		       	// get the capture date
        	    $exif_data = exif_read_data($dir.'/'.$file);
	            $exif_datetime = $exif_data['DateTimeOriginal'];
    	        if($exif_datetime) {
        	    	$exif_datetime = preg_replace('/[: ]/','',$exif_datetime);
					$files[$exif_datetime.$file] = $file;
				}
            	else {
            		$files[filemtime($dir.'/'.$file).$file] = $file;
            	}
        }
	}
        ksort($files);
        
        while($x = current($files)) {
        	if(strstr($image,$x)) {
                    if(has_prev($files)) {
                        $prev_image = prev($files);
                        next($files);
                    }
                    if(has_next($files)) {
                        $next_image = next($files);
                    }
                    break;
		}
                next($files);
	}
       
}
// $prev_image and $next_image contain the filenames of the previous
// and next images, respectively.  need to figure out some way to make
// links
$a = explode('.',$prev_image);
$a = array_pop($a);
$prev_ext = strtolower($a);
$a = explode('.',$next_image);
$a = array_pop($a);
$next_ext = strtolower($a);
$directories = array();
$directories = explode('/',$dir);
$cache_subdir = base64_encode(array_pop($directories));

$prev_thumbnail = $cache_subdir . '/' . base64_encode($prev_image) . '.' . $prev_ext;
$next_thumbnail = $cache_subdir . '/' . base64_encode($next_image) . '.' . $next_ext;

?>
<html>
<head>
<title><?php print GALLERY_TITLE ?> - <?php print $title ?></title>
<link rel="stylesheet" href="/style.css" type="text/css" />
</head>
<body>
<h1><a href="/">photos</a>.<a href="http://elscorcho.org/">elscorcho.org</a></h1>
<div id="top">
<div id="navigation">
<?php
$nav_array = explode('/', $image);
print "<a href=\"/\">home</a>";
$nav = '/';
while($curr_nav = array_shift($nav_array)) {
        if(!(strstr($curr_nav,'.jpg') || strstr($curr_nav,'.png') || strstr($curr_nav,'.JPG'))) { $nav = $nav . $curr_nav . '/'; }
	else{ $nav = $nav . $curr_nav; }
	print " / <a href=\"$nav\">$curr_nav</a>";
}
print "\n";
?>
</div>
<div id="searchbox">
<?php drawSearchBox() ?>
</div>
</div>

<div id="wrapper">

<div id="imagebox"> <!-- contains image-nav and image elements -->
<div class="imagenav">
<?php
if($prev_image) { 
    $prev_image_link = $prev_image;
?>
<a href="/<?php print $path . '/'. $prev_image_link ?>"><img title="previous" src="<?php print GALLERY_WEB_CACHE . $prev_thumbnail ?>"></a>
<?php } ?>
</div>
<div id="image">
<img src="/image.php?img=<?php print $filename ?>" alt="<?php print $title ?>" />
</div>
<div class="imagenav">
<?php if($next_image) {
    $next_image_link = $next_image;
?>
<a href="/<?php print $path . '/'. $next_image_link ?>"><img title="next" src="<?php print GALLERY_WEB_CACHE . $next_thumbnail ?>"></a>
<?php } ?>
</div> 
</div> <!-- imagebox -->
<div id="sidebar">
<?
$exif_data = exif_read_data($filename);
// $embedded_keywords = extract_xmp_keywords($filename);
if($exif_data['Model']) {
print "<div id=\"exif-data\">\n";
print "filename: ".$exif_data['FileName']."<br />\n";
print "camera: ".strtolower($exif_data['Model'])."<br />\n";
print "exposure: ".$exif_data['ExposureTime']."<br />\n";
print "f-stop: ".$exif_data['COMPUTED']['ApertureFNumber']."<br />\n";
print "focal length: ";
$fl_string = split('/',$exif_data['FocalLength']);
$fl = $fl_string[0] / $fl_string[1];
print $fl;
switch($fl_string[1]) {
    case 1000:
        print 'mm'; break;
    case 100:
        print 'cm'; break;
    default:
        break;
}
print "<br />\n";
print "date: ";
$dt = $exif_data['DateTimeOriginal'];
$dt = split('[: ]',$dt);
print $dt[1].'-'.$dt[2].'-'.$dt[0].' '.$dt[3].':'.$dt[4].':'.$dt[5];
print "<br />\n";
if($exif_data['Flash'] % 2 == 0) {
    print "flash fired: no<br />\n";
}
else {
    print "flash fired: yes<br />\n";
}
print "</div>\n\n";

}
?>
<div id="tags">
<h3>tags</h3>
<?php
	
	$tag_output = "";
	$sql = "select t.tag from tags t, image_tags i where i.image_ID=$image_id and i.tag_ID=t.ID order by tag asc";
	$tags = mysql_query($sql);
	if(mysql_num_rows($tags)>0) {
		for($i=0;$i<mysql_num_rows($tags);$i++) {
			$tag = mysql_fetch_array($tags);
			// display tag here
			// we can eventually add links to tags.php like <a href="/tags/$tag">
			// once we write tags.php
			$clean_tag = str_replace(' ','_',stripslashes($tag['tag']));
			$tag_output .= "<a href=\"/tags/$clean_tag\">$clean_tag</a>, ";
		}
	}
	// need to remove last space and comma
	$tag_output = substr($tag_output,0,-2);
	print $tag_output;
?>
	<div id="tagsform">
	<h3>add tags</h3>
	<h4>comma separated</h4>
	<form name="addtag" action="/addtag.php">
		<input name="frm_tag" type="text" size="10" maxsize="255" />
		<input type="hidden" name="image_id" value="<?php print $image_id ?>" />
		<input name="frm_submit" type="submit" value="add" />
	</form>
	</div>
</div>
<?php
/*
print "<div id=\"orderbutton\">\n";
$foo = explode('.',$filename);
$this_ext = array_pop($foo);
$this_thumb = GALLERY_WEB_CACHE . base64_encode(GALLERY_LOCAL_HOME.$filename).'.'.$this_ext;
$this_file = GALLERY_WEB_HOME.$filename;
$this_width = '335';
$this_height = '500';

print "<form name=\"sflyc4p\" action=\"http://www.shutterfly.com/c4p/UpdateCart.jsp\" method=\"post\">\n";
print "<input type=\"hidden\" name=\"addim\" value=\"1\">\n";
print "<input type=\"hidden\" name=\"protocol\" value=\"SFP,100\">\n";
print "<input type=\"hidden\" name=\"pid\" value=\"C4P\">\n";
print "<input type=\"hidden\" name=\"psid\" value=\"AFFL\">\n";
print "<input type=\"hidden\" name=\"imnum\" value=\"1\">\n";
print "<input type=\"hidden\" name=\"imraw-1\" value=\"$this_file\">\n";
print "<input type=\"hidden\" name=\"imrawheight-1\" value=\"$this_height\">\n";
print "<input type=\"hidden\" name=\"imrawwidth-1\" value=\"$this_width\">\n";
print "<input type=\"hidden\" name=\"imthumb-1\" value=\"$this_thumb\">\n";
print "<input type=\"hidden\" name=\"imthumbheight-1\" value=\"75\">\n";
print "<input type=\"hidden\" name=\"imthumbwidth-1\" value=\"75\">\n";
print "<input type=\"hidden\" name=\"returl\" value=\"http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']."\">\n";
print "<input type=\"submit\" value=\"order prints\">\n";
print "</form>\n";
print "</div>\n";
*/
?>
</div> <!-- sidebar -->

<div id="comments">
<h3>comments</h3>
<?php
	$sql = "select ID,name,comment_date,comment_body from image_comments where image_ID=$image_id order by comment_date asc";
	$comments = mysql_query($sql);
	if(mysql_num_rows($comments)>0) {
		for($i=0;$i<mysql_num_rows($comments);$i++) {
			$comment = mysql_fetch_array($comments);
			// 	print comment here
			print "<a name=\"comment" . $comment['ID'] . "\"><div class=\"comment\"></a>\n";
			print "\t<p>" . stripslashes($comment['comment_body']) . "</p>\n";
			print "\t<h3>" . stripslashes($comment['name']) . "</h3>\n";
			print "\t<h3>" . $comment['comment_date'] . "</h3>\n";
			print "\t<h3><a href=\"" . $_SERVER['REQUEST_URI'] . "#comment" . $comment['ID'] . "\">permalink</a></h3>\n"; 
			print "</div>\n";
		}
	}
?>
<!-- comment form -->
	<div id="commentsform">
	<h3>add comment</h3>
	<form name="addcomment" action="/addcomment.php">
		<strong>name:</strong><br/><input name="frm_name" type="text" size="15" maxsize="255" /><br />
		<strong>email:</strong><br/><input name="frm_email" type="text" size="15" maxsize="255" /> (not visible to others)<br />
		<strong>comment:</strong><br />
		<textarea name="frm_comment" cols=40 rows=8></textarea><p />
		<input type="hidden" name="image_id" value="<?php print $image_id ?>" />
		<input type="submit" name="frm_submit" value="submit" />
		<input type="reset" name="frm_reset" value="reset" />
	</form>
	</div>
</div>

</div> <!-- wrapper -->
</body>
</html>
