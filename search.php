<?php /**/ ?><?php
include('inc/constants.inc.php');
include('inc/functions.php');

if($tags) {
	$tags = str_replace(',','/',$tags);
	$tags = str_replace(' ','_',$tags);
	// $tags = str_replace('_','',$tags);
	$tags = str_replace('-','_',$tags);
	header("Location: /tags/$tags");
}
?>