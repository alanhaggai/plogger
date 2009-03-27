<?php

require("plog-admin.php");
require_once("../plog-load_config.php"); 					// load configuration variables from database
require_once("plog-admin-functions.php");


global $inHead;
global $config;

function read_dir($path){

   static $dir_arr = array () ;
  
   $handle = opendir($path);

   while ($file = readdir($handle)) {
	   if (is_dir($path.$file) && substr($file,0,1) != '.') {
		   $dir_arr[] = $path . $file . "/" ;
	   };
   }
  
   return $dir_arr ;
 
}


$inHead = '<script type="text/javascript" src="js/plogger.js"></script>';

$output.= "<h1>" . plog_tr("Manage Themes") . "</h1>";

$output.= "<p>$theme_url</p>";

$theme_dir = $config["basedir"] . 'themes/';

// scan list of folders within theme directory
$theme_list = read_dir($theme_dir);

if ($_REQUEST["activate"]) { // activate new theme by setting configuration dir
	// insert into database
	$new_theme_dir = basename($_REQUEST["activate"]);
	$metafile = $config['basedir'] . '/themes/' . $new_theme_dir . '/meta.php';
	if (file_exists($metafile)) {
		include($metafile);
		$sql = 'UPDATE '.TABLE_PREFIX.'config SET `theme_dir` = \''.$new_theme_dir.'\'';
		$name = $theme_name . ' ' . $version;
		if (mysql_query($sql)) {
			$output .= '<p class="actions">' . sprintf(plog_tr("Activated New Theme <strong>%s</strong>"),$name). '</p>';
		} else {
			$output .= '<p class="errors">' . plog_tr("Error Activating Theme!") . '</p>';
		};

		// update config variable if page doesn't refresh
		$config["theme_dir"] = $new_theme_dir;
	} else {
			$output .= '<p class="errors">' . plog_tr("No such theme") . '</p>';
	};
}

// Output table header
$output.= '<table id="theme-table" cellpadding="5" width="100%"><tr class="header"><td class="table-header-left">Theme</td><td class="table-header-middle">Description</td><td class="table-header-middle">Author</td><td class="table-header-right">&nbsp;</td></tr>';
$counter = 0;

foreach($theme_list as $theme_folder_name) {
	$meta_file = $theme_folder_name . "meta.php";

	
	$theme_folder_basename = basename($theme_folder_name);
	
	// only display theme as available if meta information exists for it
	if (is_file($meta_file)) {
		
		// pull in meta information
		include($meta_file);
		
		if ($counter%2 == 0) $table_row_color = "color-1";
		else $table_row_color = "color-2";
		
		// start a new table row (alternating colors)
		if ($config["theme_dir"] == $theme_folder_basename)
			$output .= "<tr class=\"activated\">";

		else
			$output .= "<tr class=\"$table_row_color\">";
	
		
		$output .= "<td><strong>$theme_name</strong><br/> Version $version</td><td>$description</td><td><a href=\"$url\">$author</a></td>";
		
		if ($config["theme_dir"] == $theme_folder_basename)
			$output .= "<td>" .plog_tr("Active Theme") . "</td>";
		else
			$output .= "<td><a href=\"${_SERVER[PHP_SELF]}?activate=$theme_folder_basename\">Activate</a></td>";
		
		
		$output .= "</tr>";
		
	
		$counter++;
	}
	
	
}

$output .= "<tr class=\"header\"><td colspan=\"5\"></td></tr></table>";

display($output, "themes");

?>
