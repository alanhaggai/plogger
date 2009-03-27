<?php
#error_reporting(E_ALL);
include("plog-globals.php");
include_once("plog-load_config.php");
include_once("plog-functions.php");

global $config;

// process path here - is set if mod_rewrite is in use
if (!empty($_REQUEST["path"])) {
	// the followling line calculates the path in the album and excludes any subdirectories if 
	// Plogger is installed in one
	$path = join("/",array_diff(explode("/",$_SERVER["REQUEST_URI"]),explode("/",$_SERVER["PHP_SELF"])));
	$resolved_path = resolve_path($path);
	if (is_array($resolved_path)) {
		$_GET["level"] = $resolved_path["level"];
		$_GET["id"] = $resolved_path["id"];
		if (isset($resolved_path['mode'])) {
			$_GET['mode'] = $resolved_path['mode'];
		};

		// get page number from url, if present
		$parts = parse_url($_SERVER["REQUEST_URI"]);
		if (isset($parts["query"])) {
			parse_str($parts["query"],$query_parts);
			if (!empty($query_parts["plog_page"])) $_GET["plog_page"] = $query_parts["plog_page"];
		};
		$path = $parts["path"];
	};
};

// Set sorting session variables if they are passed
if (isset($_GET['sortby'])) {
	$_SESSION['plogger_sortby'] = $_GET['sortby'];
};

if (isset($_GET['sortdir'])) {
	$_SESSION['plogger_sortdir'] = $_GET['sortdir'];
};

// The three GET parameters that it accepts are
// $level = "collection", "album", or "picture"
// $id = id number of collection, album, or picture
// $n = starting element (for pagination) go from n to n + max_thumbs (in global config)

// use plogger specific variables to avoid name clashes if Plogger is embedded



$GLOBALS['plogger_level'] = isset($_GET["level"]) ? $_GET["level"] : '';
$GLOBALS['plogger_id'] = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
$GLOBALS['plogger_mode'] = isset($_GET["mode"]) ? $_GET["mode"] : '';

$allowed_levels = array('collections','collection','album','picture','search');
if (!in_array($GLOBALS['plogger_level'],$allowed_levels)) {
	$GLOBALS['plogger_level'] = 'collections';
};

// niisiis, mul on need 2 funktsiooni ja ma pean need mergema
// vana mingi teise failiga, et ma saaks plogger korrektselt integreerida
// noh, proovime siis.

define('THEME_DIR', dirname(__FILE__) . '/themes/' . $config['theme_dir']);
define('THEME_URL', $config['theme_url']);

function the_gallery_head() {
	plogger_head();

	$use_file = 'head.php';
    if (file_exists(THEME_DIR . "/" . $use_file)) {
		include(THEME_DIR . "/" . $use_file);
	} else {
		include(dirname(__FILE__).'/themes/default/'.$use_file);
	}
}

function the_gallery(){
	// collections mode (show all albums within a collection)
	// it's the default
	$use_file = "collections.php";
	if ($GLOBALS['plogger_level'] == "picture"){
		$use_file = 'picture.php';
	}
	elseif ($GLOBALS['plogger_level'] == "search"){
		if ($GLOBALS['plogger_mode'] == "slideshow") {
			$use_file = 'slideshow.php';
		} else {
			$use_file = 'search.php';
		};
	}
	elseif ($GLOBALS['plogger_level'] == "album") {
		// Album level display mode (display all pictures within album)
		if ($GLOBALS['plogger_mode'] == "slideshow") {
			$use_file = 'slideshow.php';
		} else {
			$use_file = 'album.php';
		};
	}
	else if ($GLOBALS['plogger_level'] == "collection") {
		$use_file = 'collection.php';
	};

	// if the theme does not have the requested file, then use the one from the default template
	if (file_exists(THEME_DIR . "/" . $use_file)) {
		include(THEME_DIR . "/" . $use_file);
	} else {
		include(dirname(__FILE__).'/themes/default/'.$use_file);
	}
} 
?>
