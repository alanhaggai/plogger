<?php

// this file will load all the configuration elements from the database
// and place them into a global associative array called $config

$config = array();
$thumbnail_config = array();

$plog_basedir = dirname(__FILE__).DIRECTORY_SEPARATOR;

require_once($plog_basedir."plog-globals.php");
require_once($plog_basedir."plog-functions.php");
require_once($plog_basedir."plog-config.php");
connect_db();


$query = "SELECT * FROM `".TABLE_PREFIX."config`";
$result = mysql_query($query) or die(mysql_error() .'<br /><br />'.$query);

if (mysql_num_rows($result) == 0){
	die("No config information in the database.");
}

$config = mysql_fetch_assoc($result);

$config["basedir"] = $plog_basedir;

$url_parts = parse_url($_SERVER["REQUEST_URI"]);
$config["baseurl"] = $url_parts["path"];

$config["embedded"] = 0;

// try to figure out whether we are embedded (for example running from Wordpress)
// on windows/apache $_SERVER['PATH_TRANSLATED'] uses "/" for directory separators,
// __FILE__ has them the other way, realpath takes care of that.
if (dirname(__FILE__) != dirname(realpath($_SERVER["PATH_TRANSLATED"])) && strpos($_SERVER["PATH_TRANSLATED"],"admin") === false) {
	$config["embedded"] = 1;
	// disable our own cruft-free urls, because the URL has already been processed
	// by WordPress
	$config["use_mod_rewrite"] = 0;
} else {
	$config["baseurl"] = "http://".$_SERVER["HTTP_HOST"]. substr($_SERVER["PHP_SELF"],0,strrpos($_SERVER["PHP_SELF"],"/")) . "/"; 
}

// remove admin/ from the end, if present .. is there a better way to determine the full url?
if (substr($config["baseurl"],-6) == "admin/") {
	$config["baseurl"] = substr($config["baseurl"],0,-6);
}

$config["theme_url"] = $config["gallery_url"]."themes/".basename($config["theme_dir"])."/";
$config['charset'] = 'utf-8';
$config['version'] = 'Version 3.0 Beta';

// charset set with HTTP headers has higher priority that that set in HTML head section
// since some servers set their own charset for PHP files, this should take care of it
// and hopefully doesn't break anything

if (!headers_sent()){
	header('Content-Type: text/html; charset=' . $config['charset']);
}

$query = "SELECT * FROM `".TABLE_PREFIX."thumbnail_config`";
$result = mysql_query($query) or die(mysql_error() .'<br /><br />'.$query);

if (mysql_num_rows($result) == 0){
	die("No thumbnail config information in the database.");
}

$prefix_arr = array(1 => '',2 => 'lrg-',3 => 'rss-',4 => 'tn-');

while($row = mysql_fetch_assoc($result)) {
	$thumbnail_config[$row['id']] = array(
			'filename_prefix' => $prefix_arr[$row['id']],
			'size' => $row['max_size'],
			'timestamp' => $row['update_timestamp'],
			'disabled' => $row['disabled']);
}

// debugging function
function display_uservariables(){
	foreach ($config as $keys => $values) {
		echo "$keys = $values<br>";
	}
}

if (!isset($_SESSION["plogger_sortby"])){
	$_SESSION["plogger_sortby"] = $config['default_sortby'];
}

if (!isset($_SESSION["plogger_sortdir"])){
	$_SESSION["plogger_sortdir"] = $config['default_sortdir'];
}

if (!isset($_SESSION["plogger_details"])){
	$_SESSION["plogger_details"] = 0;

}

?>
