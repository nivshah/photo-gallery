<?php /**/ ?><?php
include('inc/constants.inc.php');
include('inc/functions.php');

	//DB connection
	$db = mysql_connect(MYSQL_HOST,MYSQL_USER,MYSQL_PASSWD);
	mysql_select_db(MYSQL_DB,$db);
	
	$tags = str_replace(' ','_',$tags);
	$tags = str_replace('-','_',$tags);
	$tags = str_replace('%20','_',$tags);
	
	$tags_array = explode('/',$tags);
	$tags_comma = implode("','",$tags_array);

	$tag_ids = array();
	$image_ids = array();
	// first get tag_ids for all tags in tags_array
	$sql = "select ID from tags where tag in ('$tags_comma')";
	
	$result = mysql_query($sql);
	if(mysql_num_rows($result)) {
		for($i=0;$i<mysql_num_rows($result);$i++) {
			$row = mysql_fetch_array($result);
			array_push($tag_ids,$row['ID']);
		}
	}
	

	// then get all images with a link in tag_ids
	$tag_ids_comma = implode("','",$tag_ids);
	$sql = "select image_id from image_tags where tag_id in ('$tag_ids_comma')";
	
	$result = mysql_query($sql);
	if(mysql_num_rows($result)) {
		for($i=0;$i<mysql_num_rows($result);$i++) {
			$row = mysql_fetch_array($result);
			array_push($image_ids,$row['image_id']);
		}
	}
	
	$nav = "<a href=\"/tags/\">tags</a> / " . str_replace(' ','_',implode(' / ',$tags_array));
	$title = "tags / " . str_replace(' ','_',implode(' / ',$tags_array));
?>
<html>
<head>
<title><?php print GALLERY_TITLE ?> - <?php print "home / ". $title ?></title>
<link rel="stylesheet" href="/style.css" type="text/css" />
</head>
<body>
<h1><a href="/">photos</a>.<a href="http://elscorcho.org/">elscorcho.org</a></h1>
<div id="top">
	<div id="navigation">
	<?php
		print "<a href=\"/\">home</a> / $nav";
	?>
	</div>
	<div id="searchbox">
	<?php drawSearchBox() ?>
	</div>
</div>
<div id="main">
<ul>
<?php

    if($tags != '') {
	$image_ids_comma = implode("','",$image_ids);
	$sql = "select ID,img_web_path,img_local_path,img_thumbnail from image_index where ID in ('$image_ids_comma')";
	$result = mysql_query($sql);
	if(mysql_num_rows($result)) {
		for($i=0;$i<mysql_num_rows($result);$i++) {
			$row = mysql_fetch_array($result);
			$file = $row['img_web_path'];
			$thumb_name = $row['img_thumbnail'];
			if(is_file(GALLERY_LOCAL_CACHE.$thumb_name)) {
				print "<li class=\"image\"><a href=\"$file\"><img src=\"" . GALLERY_WEB_CACHE . $thumb_name. "\"></a></li>\n";
			} else {
				// text link
				print "<li class=\"text-link\"><a href=\"$file\">$file</a></li>\n";
			}
		}
	}
    } else {
        $sql = "select t.tag, count(l.tag_id) from tags t, image_tags l where l.tag_id=t.ID group by t.tag order by t.tag";
        $result = mysql_query($sql);
        while($row = mysql_fetch_array($result)) {
            $tag = stripslashes($row['tag']);
            $count = $row['count(l.tag_id)'];
            $tag_cloud[$tag] = $count;
        }

        $max_count = max($tag_cloud);
        $min_count = min($tag_cloud);
        $dist = ($max_count-$min_count)/3;

        foreach($tag_cloud as $tag=>$count) {
            if($count == $min_count) {
                $size = 'xx-small';
            } elseif ($count == $max_count) {
                $size = 'xx-large';
            } elseif ($count > ($min_count + ($dist*2))) {
                $size = 'large';
            } elseif ($count > ($min_count + $dist)) {
                $size = 'medium';
            } else {
                $size = 'small';
            }
            $tag_output = str_replace(' ','_',$tag);
            print "<a style=\"font-size:$size\" href=\"/tags/$tag\">$tag_output</a>\n";
        }
    }
?>
</ul>
</div>
<div id="footer">
all images and text &copy;2001-2009 niv shah
</div>
</body>
</html>
