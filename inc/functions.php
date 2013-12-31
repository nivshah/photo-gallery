<?php /**/ ?><?php
function removeEvilTags($source) {
	//stolen from niv.elscorcho.org/addresponse.php
	$allowedTags='<a><br><b><blockquote><em><h1><h2><h3><h4><i>' .
             '<img><li><ol><p><strong><table>' .
             '<tr><td><th><u><ul>';
	$source = strip_tags($source, $allowedTags);
	return preg_replace('/<(.*?)>/ie', "'<'.removeEvilAttributes('\\1').'>'", $source);
}

function drawSearchBox() {
	print "<form action=\"/search.php\" method=\"post\">\n";
	print "search by comma-separated <a href=\"/tags/\">tags</a>: <input type=\"text\" name=\"tags\" size=\"20\" maxsize=\"255\" />\n";
	print "</form>\n";
}

function has_next(&$array) {
	$A_work=$array;  //$A_work is a copy of $array but with its internal pointer set to the first element.
	$PTR=current($array);
	array_set_pointer($A_work, $PTR);

	if(is_array($A_work))
	{
		if(next($A_work)===false)
		return false;
		else
		return true;
	}
	else
	return false;
}

function has_prev(&$array) {
	$A_work=$array;
	$PTR=current($array);
	array_set_pointer($A_work,$PTR);

	if(is_array($A_work))
	{
		if(prev($A_work)===false)
		return false;
		else
		return true;
	}
	else
	return true;
}

function array_set_pointer(&$array, $value) {
	reset($array);
	while($val=current($array))
	{
		if($val==$value)
		break;

		next($array);
	}
}

function mysql_to_epoch($datestr) {
	// need this for the exif datestamps
	list($year,$month,$day,$hour,$minute,$second) = split("([^0-9])",$datestr);
	return date("U",mktime($hour,$minute,$second,$month,$day,$year));
}

function extract_xmp_keywords ($filename) {

	// edited from code by Pekka Saarinen http://photography-on-the.net
	// reads XMP keywords from a file

	$xmp_matches = array();
	$xmp_unfiltered_matches = array();

	ob_start();
	readfile($filename);
	$source = ob_get_contents();
	ob_end_clean();

	$xmpdata_start = strpos($source,"<x:xmpmeta");
	$xmpdata_end = strpos($source,"</x:xmpmeta>");
	if($xmpdata_start === false || $xmpdata_end === false) {
		// not found, so return an empty array
		return($xmp_matches);
	}
	$xmplength = $xmpdata_end-$xmpdata_start;
	$xmpdata = substr($source,$xmpdata_start,$xmplength+12);

	//inside the rdf:Bag is where the keywords are
	$xmpkeyword_start = strpos($xmpdata,"<rdf:Bag>");
	$xmpkeyword_end = strpos($xmpdata,"</rdf:Bag>");
	if($xmpkeyword_start === false || $xmpkeyword_end === false) {
		// not found, so return an empty array
		return($xmp_matches);
	}
	$xmpkeywordlength = $xmpkeyword_end - 9 - $xmpkeyword_start;
	$xmp_keywords = substr($xmpdata,$xmpkeyword_start+9,$xmpkeywordlength);
	
	// there is a lot of data inside the rdf:Bag, but we only want rdf:li items inside the Bag
	preg_match_all('/<rdf:li>(.+)<\/rdf:li>/',$xmp_keywords,$xmp_unfiltered_matches);
	
	foreach($xmp_unfiltered_matches as $m) {
		foreach($m as $match) {
			if(!strstr($match,'rdf:li')) {
				array_push($xmp_matches,$match);
			}
		}
	}
	return($xmp_matches);
}

function addtag_to_db($tag, $image_id) {
	//DB connection
	$db = mysql_connect(MYSQL_HOST,MYSQL_USER,MYSQL_PASSWD);
	mysql_select_db(MYSQL_DB,$db);

	$tag = trim($tag);
	$tag = removeEvilTags($tag);
	$tag = htmlspecialchars($tag);
	$tag = str_replace(' ','_',$tag);
	$tag = str_replace('-','_',$tag);
	$tag = addslashes($tag);
		
	// now search and see if $tag is already in the tags table
	$sql = "select ID from tags where tag='$tag'";
	$result = mysql_query($sql);
	if(mysql_num_rows($result) > 0) {
		$tag_id = mysql_fetch_array($result);
		$tag_id = $tag_id['ID'];
		// if it is, see if there is a link between image_id and tag_id
		$sql = "select count(*) as c from image_tags where tag_id=$tag_id and image_id=$image_id";
		$is_link = mysql_query($sql);
		$is_link = mysql_fetch_array($is_link);
		$is_link = $is_link['c'];
		if($is_link==0) {
			// if there isn't, add that link
				
			$sql = "insert into image_tags (image_id,tag_id) VALUES ('$image_id','$tag_id')";
			mysql_query($sql);
			if(mysql_errno()) {
				print mysql_error();
				exit;
			}
		}
		// if there is, do nothing

	} else {
		// if it isn't in the tags table, add it to the tags table and link it to image_id
		$sql = "insert into tags (tag) VALUES ('$tag')";
		if(!mysql_query($sql)) { print mysql_error(); exit; }
		$tag_id = mysql_insert_id();

		$sql = "insert into image_tags (image_id,tag_id) VALUES ('$image_id','$tag_id')";
		if(!mysql_query($sql)) { print mysql_error(); exit; }
	}
}
?>
