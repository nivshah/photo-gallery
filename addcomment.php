<?php /**/ ?><?php
include('inc/constants.inc.php');

function removeEvilTags($source) {
	//stolen from niv.elscorcho.org/addresponse.php
   $allowedTags='<a><br><b><blockquote><em><h1><h2><h3><h4><i>' .
             '<img><li><ol><p><strong><table>' .
             '<tr><td><th><u><ul>';
   $source = strip_tags($source, $allowedTags);
   return preg_replace('/<(.*?)>/ie', "'<'.removeEvilAttributes('\\1').'>'", $source);
}

	if($frm_comment && $frm_name && $frm_email) {
		//DB connection
		$db = mysql_connect(MYSQL_HOST,MYSQL_USER,MYSQL_PASSWD);
		mysql_select_db(MYSQL_DB,$db);
		
		//clean up $frm_name
		$frm_name = htmlspecialchars($frm_name);
		$frm_name = addslashes($frm_name);
		
		//clean up $frm_email
		$frm_email = htmlspecialchars($frm_email);
		$frm_email = addslashes($frm_email);
		
		// clean up $frm_comment
		$frm_comment = removeEvilTags($frm_comment);
		$frm_comment = str_replace("\r","<br />",$frm_comment);
		$frm_comment = addslashes($frm_comment);
		
		//insert into db
		$sql = "insert into image_comments (image_ID,name,email,comment_date,comment_body)".
			"VALUES ($image_id,'$frm_name','$frm_email',NOW(),'$frm_comment')";
		mysql_query($sql);
		if(mysql_errno()) {
			print mysql_error();
			exit;
		}
	}
	
	// insert header that pushes me back to where i came from
	header("Location: $HTTP_REFERER");
?>