<?php
// Code by Mike Johnson -- mike@solanosystems.com October 23rd, 2004.
// This is the main administrative interface code.  To change the look of the interface, change ../css/admin.css.

// The initial tab is UPLOAD function.

require_once("../plog-load_config.php");
require("plog-admin.php");
include("plog-admin-functions.php");

function generate_albums_menu($albums) {
	$output =  '<select name="albums_menu" onclick="var k=document.getElementsByName(\'destination_radio\');k[0].checked=true;">';
	foreach($albums as $album_id => $album) {

		if ($_REQUEST["albums_menu"] == $album_id || $_REQUEST["new_album_name"] == $album['album_name']) 
						$selected = " selected='selected'"; else $selected = "";
						
						$output .= "<option value=\"".$album_id."\"$selected>".SmartStripSlashes($album['collection_name'])." : ".SmartStripSlashes($album['album_name'])."" ;
            $output .= "</option>";
    }
	
	$output .=  "</select>";
	
	return $output;
}

function generate_collections_menu() {
	$collections = get_collections();
    $output = "<select name=\"collections_menu\">";
    foreach($collections as $collection) {
        $output .= "<option value=\"".$collection['id']."\">".SmartStripSlashes($collection['name'])."" ;
        $output .=  "</option>";
    }
	
	$output .= "</select>";
	
	return $output;
}

// Check if update has been clicked, handle erroneous conditions, or upload
if (isset($_REQUEST["upload"])){
	foreach($_REQUEST as $key => $val) $_REQUEST[$key] = stripslashes($val);
	
	$pi = pathinfo($_FILES['userfile']['name']);
	
	if ($_FILES["userfile"]["name"] == ""){
		$output .= '<p class="errors">' . plog_tr("No file name specified!") . '</p>';
	} else if ($pi["extension"] == "zip") {
		// let's decompress the zip file into the uploads folder and then redirect
		// the user to plog-import.php
	
		include(PLOGGER_DIR . 'lib/pclzip-2-4/pclzip.lib.php');
		//zip file to extract
		$archive = new PclZip($_FILES["userfile"]["tmp_name"]);
		
		//extract to uploads folder
		
		$results = $archive->extract(PCLZIP_OPT_REMOVE_ALL_PATH, PCLZIP_OPT_PATH, $config["basedir"].'uploads/');
		
		if ($results == 0){
			//failed
			$output .= '<p class="errors">' . plog_tr('Error: ') . $archive->errorInfo(true).'</p>';
		} else {
			// unzip succeeded
			// Doesn't necessarily mean that the saving the images succeeded.
			
			$errors = array();
			
			foreach ($results as $r){
				if ($r["status"] != "ok"){
					$errors[] = $r;
				}
			}
			
			if (empty($errors)){
				// let's redirect to the import interface.
				header("location: plog-import.php?directory=bda1146671668d77b2da21c84146056a");
				exit;
			} else {
				$output .= '<p class="errors">';
				$output .= plog_tr('There were some problems importing the files:') . '<br/><br />';
				
				foreach ($errors as $e){
					$output .= $e["stored_filename"].": ".$e["status"].'<br />';
				}
				
				$output .= '<br />' .
					sprintf(plog_tr('You can proceed to the <a href="%s">Import</a> section to view any files that were successfully uploaded.'),"plog-import.php") . '</p>';
			}
		}
		
    	
    } else if (!is_allowed_extension($pi["extension"])) {
    	$output .= '<p class="errors">' . plog_tr('Plogger cannot handle this type of file') . '</p>';
    } else if ($_FILES['userfile']['size'] == 0) {
    	$output .= '<p class="errors">' . plog_tr('File does not exist!') . '</p>';
    } else if (!isset($_REQUEST["destination_radio"])) {
        $output .= '<p class="errors">' . plog_tr('No destination album specified!') . '</p>';    
    } else {
        if ($_REQUEST["destination_radio"] == "new" && $_REQUEST["new_album_name"] == ""){
           	$output .= '<p class="errors">' . plog_tr('New album name not specified!') . '</p>';
        } else { 
		if ($_REQUEST["destination_radio"] == "new"){
			// Create the new album
			$result = add_album(mysql_escape_string($_REQUEST["new_album_name"]), NULL, $_REQUEST["collections_menu"]);
			$album_id = $result["id"];
		} else {
			$album_id = $_REQUEST["albums_menu"];
		}
			
		$result = add_picture($album_id,$_FILES["userfile"]["tmp_name"],$_FILES["userfile"]["name"],$_REQUEST["caption"], $_REQUEST["description"]);
		$output .= '<p class="actions">'.$result["output"].'</p>';
		
	}
    }
}

$output .= '
	<h1>' . plog_tr('Upload Photos') . '</h1>
	<form id="uploadForm" action="'.$_SERVER["PHP_SELF"].'" method="post" enctype="multipart/form-data">
	<table><tr><td valign="top" style="padding-right: 20px">
	<div class="cssbox">  
		<div class="cssbox_head"><h2>' . plog_tr('Choose an Image or ZIP Archive') . '</h2></div>  
		<div class="cssbox_body"><p>
			<label for="userfile">' . sprintf(plog_tr('File<em>n</em>ame: (%s limit)'),ini_get('upload_max_filesize')) .'</label>
			<input accesskey="n" id="userfile" name="userfile" value="Vali fail" type="file" onchange="checkArchive(this)"/>
			<label accesskey="c" for="caption">' . plog_tr('Picture <em>C</em>aption (optional):') . '</label><input style="width: 320px" name="caption" id="caption"/>
			<label accesskey="d" for="description">' . plog_tr('<em>D</em>escription (optional):') . '</label><textarea name="description" id="description" cols="53" rows="6"></textarea><br/></p>  
		</div> 
	</div></td>';

$albums = get_albums();
$output .=  '
      <td valign="top">
      <div class="cssbox-green">  
		<div class="cssbox_head-green"><h2>' . plog_tr('Choose a Destination Album') . '</h2></div>  
		<div class="cssbox_body-green">
      <table><tr valign="middle"><td><input onclick="var k=document.getElementsByName(\'albums_menu\');k[0].focus();"
      type="radio" name="destination_radio" id="destination_radio" accesskey="a" value="existing" checked="checked"/></td>
      <td><label for="destination_radio">' . plog_tr('Existing <em>A</em>lbum') . '</label><br/></td></tr></table>
	  '.generate_albums_menu($albums).'
			<h3>' . plog_tr('OR') . '</h3>
      <table><tr valign="middle"><td><input onclick="var k=document.getElementsByName(\'new_album_name\');k[0].focus();" 
      type="radio" name="destination_radio" accesskey="b" value="new"/></td>
      <td><label for="new_album_name">' . plog_tr('Create a New Al<em>b</em>um') . '</label></td></tr></table>
      <table><tr valign="middle"><td>' . plog_tr('New Album Name:') . '</td>
      <td><input onclick="var k=document.getElementsByName(\'destination_radio\');k[1].checked=true;" type="text" id="new_album_name" name="new_album_name"/></td></tr>
      <tr valign="middle"><td>' . plog_tr('In Collection:') . '</td><td>
		'.generate_collections_menu().'
      </td></tr></table>
      <br/>
      <input class="submit" type="submit" name="upload" value="' . plog_tr('Upload') . '" />
	  </div> 
	  </div>
	  </td></tr></table>
</form>';

$output_error = '<h1>' . plog_tr('Upload Photos') . '</h1><p class="actions">' . sprintf(plog_tr('Before you can begin uploading photos to your gallery, you must create at least <strong>one collection</strong> AND <strong>one album</strong> within that collection.  Move over to the <a href="%s">"Manage"</a> tab to begin creating your organizational structure.'),"plog-manage.php") . '</p>';

require_once(PLOGGER_DIR . 'lib/plogger/install_functions.php');

if (gd_missing()) {
	$output_error = '<h1>' . plog_tr('Upload Photos') . '</h1><p class="actions">' . plog_tr('PHP GD extension is not installed, it is required to upload images.') . '</p>';
	display($output_error, "upload");
} else {
	$num_albums = count_albums();
	if ($num_albums > 0)
		 display($output, "upload");
	else
		 display($output_error, "upload");
};
?>
