<?php /**/ ?><?php
include('inc/constants.inc.php');
include('inc/functions.php');

$path = $_GET['path'];
$page = $_GET['page'];
if(!$page) { $page = 1; }

$root = GALLERY_LOCAL_HOME . 'src/';
$web_root = GALLERY_WEB_HOME . 'src/';
$orig_path = $path; //just for safekeeping
if(!$path) { 
	$path = $root; $web_path = $web_root; $title = 'home'; 
}
else {
	$title_array = explode('/', $path);
	$title = implode(' / ',$title_array);
	$title = 'home / ' . $title;
	$web_path = $web_root . $path . '/';
	$path = $root . $path . '/'; 
}

?>
<html>
<head>
<title><?php print GALLERY_TITLE ?> - <?php print $title ." - page " . $page ?>
</title>
<link rel="stylesheet" href="/style.css" type="text/css" />
</head>
<body>
<h1><a href="/">photos</a>.<a href="http://elscorcho.org/">elscorcho.org</a></h1>
<div id="top">
	<div id="navigation">
	<?php
	$nav_array = explode('/', $orig_path);
	print "<a href=\"/\">home</a>";
	$nav = '/';
	while($curr_nav = array_shift($nav_array)) {
		$nav = $nav . $curr_nav . '/';
		print " / <a href=\"$nav\">$curr_nav</a>";
	}
	?>
	</div>
	<div id="searchbox">
	<?php drawSearchBox() ?>
	</div>
</div>

<div id="main">
<ul>
<?php
if($handle = opendir($path)) {
    while(false !== ($file = readdir($handle))) {
        // set up a $dirs and $files hash
        if(is_dir($path.$file)) {
            $dirs[$file] = $file;
        }
        else if (filesize($path.$file)>3 && exif_imagetype($path.$file)>0) {
            // get the capture date
            $exif_data = exif_read_data($path.$file);
            $exif_datetime = $exif_data['DateTimeOriginal'];
            if($exif_datetime) {
                $exif_datetime = preg_replace('/[: ]/','',$exif_datetime);
                $files[$exif_datetime.$file] = $file;
            }
            else {
                $files[filemtime($path.$file).$file] = $file;
            }
        }
    }
    //only sort and display if there are any files or dirs
    if($dirs) {
    	$metadata = "";
    	// check if there is a .metadata file
		// and if there is read it
		// and expound on each dir
		if(is_file($path.'.metadata')) {
			$metadata = array();
			$directory_metadata = file($path.'.metadata'); 
			foreach($directory_metadata as $line) {
				$line = rtrim($line);
				list ($dir,$info) = split(',',$line);
				$metadata[$dir] = $info;
			}
		}
    	ksort($dirs);
        foreach($dirs as $dir) {
        	if ($dir == '.' || $dir == '..') {
            }
            else {
            	print "<li class=\"directory\"><a href=\"$dir/\">$dir</a>";
                $srcless = str_replace('src/','',$web_path);
/*                $sql = "select count(*) as c from photos where img_web_path like '$srcless$dir%'";
                $num_photos = mysql_query($sql);
                $num_photos = mysql_fetch_array($num_photos);
                $num_photos = $num_photos['c'];
*/
            	if($metadata[$dir]) {
            		print "<br /><span class=\"metadata\">".$metadata[$dir]."</span>";
            	}
            	print "</li>\n";
            }
        }
    }

	if($files) {
            ksort($files);
            $per_page = 50; // 50 thumbnails per page

            $num_pages = count($files) / $per_page;
            if(count($files) % $per_page) {
                $num_pages++;
            }

            $startpoint = $per_page * ($page - 1);
            if($startpoint > 0) {
                for($m=0;$m<$startpoint;$m++) {
                    array_shift($files);
                }
            }

            // set up a loop for pages
            for($m=0;$m<$per_page;$m++) {
                $file = array_shift($files);
            //foreach($files as $file) {
                if ($file == '.' || $file == '..' || $file == '.metadata' || $file == '') {
                }
                else  {
                    //draw thumbnails
                    //link to full out view	
                    // we should get thumbnails from the database, i think.
                    $directories = array();
                    $directories = explode('/',substr($path,0,-1));
                	$cache_subdir = base64_encode(array_pop($directories));
                    
					$thumb_name = $cache_subdir . '/' . base64_encode($file);
					if(exif_imagetype($path.$file) == IMAGETYPE_JPEG) {
                        $thumb_name .= '.jpg';
                    }
                    else if(exif_imagetype($path.$file) == IMAGETYPE_PNG) {
                        $thumb_name .= '.png';
                    }
                    if(is_file(GALLERY_LOCAL_CACHE . $thumb_name)) {
                        // display the thumbnail!
                        print "<li class=\"image\"><a href=\"$file\"><img src=\"" . GALLERY_WEB_CACHE . $thumb_name. "\"></a></li>\n";
                    }
                    else {
                        print "<li class=\"text-link\"><a href=\"$file\">$file</a></li>\n";
                    }
                }
            }
            print "</ul>\n";
            print "</div>\n";

            print "<div id=\"pages\">\n";
            print "page: ";
            /*
            if($page<=2) {
                for($m=1;$m<=5;$m++) {
                    if($page==$m) {
                        print "<strong>&gt;$m&lt;</strong>";
                    } else {
                        print "<a href=\"/$orig_path/$m\">$m</a>";
                    }
                    print " ";
                }
            }
            elseif ($page+2>$num_pages) {
                for($m=$num_pages-4;$m<=$num_pages;$m++) {
                    if($page==$m) {
                        print "<strong>&gt;$m&lt;</strong>";
                    } else {
                        print "<a href=\"/$orig_path/$m\">$m</a>";
                    }
                    print " ";
                }
            }
            else {
                for($m=$page-2;$m<=$page+2;$m++) {
                    if($page==$m) {
                        print "<strong>&gt;$m&lt;</strong>";
                    } else {
                        print "<a href=\"/$orig_path/$m\">$m</a>";
                    }
                    print " ";
                }
            }
*/
            for($m=1;$m<=$num_pages;$m++) {
                if($page==$m) {
                    print "<strong>$m</strong>";
                } else {
                    print "<a href=\"/$orig_path/$m\">$m</a>";
                }
                print " ";
            }
	}
        print "</div>\n";
	closedir($handle);
}
print "<div id=\"footer\">\n";
print "all images and text &copy;2001-2009 niv shah\n";
print "</div>\n";
?>
</body>
</html>
