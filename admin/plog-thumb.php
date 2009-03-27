<?php
// this will create a picture thumbnail on disk
// first it will be used for import only.

if (empty($_GET["img"])) {
	return "No such image";
};

require_once("../plog-functions.php");
require_once("../plog-globals.php");
require_once("../plog-load_config.php");
require_once("plog-admin-functions.php");


$files = get_files($config['basedir'] . 'uploads');



$found = false;

$up_dir = $config['basedir'] . 'uploads';

foreach($files as $file) {
	if (md5($file) == $_GET["img"]) {
		$found = true;
		$rname = substr($file,strlen($up_dir)+1);

		$thumbpath = generate_thumb($up_dir.'/'.$rname,"import",THUMB_SMALL);
		print '<img src="'.$thumbpath.'" /></div>';
		//print "found $relative_name!";
		break;
	};
};

?>