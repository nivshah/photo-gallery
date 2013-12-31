<?php /**/ ?><?php
include('inc/constants.inc.php');
include('inc/functions.php');
$frm_tag = $_REQUEST['frm_tag'];
$image_id = $_REQUEST['image_id'];

	if($frm_tag) {
		$tags = explode(',',$frm_tag);
		foreach($tags as $tag) {
			addtag_to_db($tag,$image_id);
		}
	}
	
	// insert header that pushes me back to where i came from
	header("Location: $HTTP_REFERER");
?>
