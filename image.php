<?php /**/ ?><?php
include('inc/constants.inc.php');
$img = $_GET['img'];

$img = GALLERY_LOCAL_HOME . $img;
$img_type = exif_imagetype($img);

if($img_type == IMAGETYPE_JPEG) {
    $mime_type = 'image/jpeg';
} else if ($img_type == IMAGETYPE_PNG) {
    $mime_type = 'image/png';
}

// if the file is too big, then we need to resize it on the fly
$imagesize = getimagesize($img);
$img_width = $imagesize[0];
$img_height = $imagesize[1];
if($img_width <= 500) {
    //header("Content-Type: text/plain");
    header("Content-Type: " . $mime_type);
    header("Content-Length: " . filesize($img));

    $fp = fopen($img, 'r');
    fpassthru($fp);
} else {
    // too damn wide.  need to resize the picture
    if ($img_width > $img_height) {
        $percentage = (500 / $img_width);
    } else {
        $percentage = (500 / $img_height);
    }

    $new_width = round($img_width * $percentage);
    $new_height = round($img_height * $percentage);

    $temp_img = imagecreatetruecolor($new_width,$new_height);
    $orig_img = imagecreatefromjpeg($img);
	
	imagecopyresampled($temp_img,$orig_img,0,0,0,0,$new_width,$new_height,$img_width,$img_height);
	
	header('Content-type: image/jpeg');
	imagejpeg($temp_img,NULL,100);
	
}
exit;

?>
