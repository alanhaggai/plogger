<?php

include("plog-functions.php");
include("plog-globals.php");
require_once("plog-load_config.php");

// this is our comment script, it simply writes the comment information
// to our flat-file database and links it to the picture using the 
// pictures id.

// Loosly validate url string format without actually checking the link (cause that takes time)
function check_url($url) {
    if (preg_match('#^http\\:\\/\\/[a-z0-9\-]+\.([a-z0-9\-]+\.)?[a-z]+#i', $url)) {
        return "http";
    } 
    else if (preg_match('#^[a-z0-9\-]+\.([a-z0-9\-]+\.)?[a-z]+#i', $url)) {
        return "nohttp";
    }
    else {
        return "badurl";
    }
} 

// first get all the neccessary variables

if (check_url($_POST["url"]) == "http"){
    $url = $_POST["url"];
}
else if (check_url($_POST["url"]) == "nohttp"){
    $url = "http://".$_POST["url"];
}
else{
    $url = "";
}

global $config;
$parent_id = intval($_POST["parent"]);
require_once('plog-functions.php');
$redirect = str_replace("&amp;","&",generate_url("picture",$parent_id, array(), true));

// If the captcha is required, check it here
if ($_SESSION['require_captcha'] == true) {
	if (($_POST['captcha'] != $_SESSION['captcha']) || !$_POST['captcha']) {
        $_SESSION["comment_post_error"] = "CAPTCHA check failed!";
		header("Location: $redirect");
		exit;
    }
}
	
$rv = add_comment($parent_id,$_POST["author"],$_POST["email"],$url,$_POST["comment"]);

// redirect back to picture page
if ($rv["errors"]) {
	// will this work?
	$_SESSION["comment_post_error"] = $rv["errors"];
}
else if ($config['comments_moderate']) {
	$_SESSION["comment_moderated"] = 1;
}

header("Location: $redirect");
?>
