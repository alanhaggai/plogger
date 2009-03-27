<?php

// Code by Mike Johnson -- mike@solanosystems.com October 23rd, 2004.
// This is the main administrative interface code.  To change the look of the interface, change ../css/admin.css.


// The initial tab is UPLOAD function.

require("plog-admin.php");
require_once("../plog-load_config.php");
require_once("../plog-functions.php");
include("plog-admin-functions.php");

global $inHead;

function generate_albums_menu($albums,$type = "multiple", $preselect) {
	$output = '';
	
	if ($type == "multiple")
		 $output .=  '<select name="destinations[]" onclick="var k=document.getElementsByName(\'destination_radio\');k[0].checked=true;" >';
	else
		$output .=  '<select name="destination" 
onclick="var k=document.getElementsByName(\'destination_radio\');k[0].checked=true;" >';
		
	foreach($albums as $album_id => $album_data) {
	    if ($preselect == $album_id) 
			$selected = " selected='selected'"; else $selected = "";
						
		$output .= "<option value=\"".$album_id."\"$selected>".$album_data['collection_name']." : ".$album_data['album_name']."" ;
        $output .= "</option>";
        }
	
	$output .=  "</select>";
	
	return $output;
}

function generate_collections_menu() {
	$collections = get_collections();
    $output = '<select name="collections_menu"
onclick="var k=document.getElementsByName(\'destination_radio\');k[1].checked=true;" >';
    foreach($collections as $collection) {
        $output .= "<option value=\"".$collection['id']."\">".$collection['name']."" ;
        $output .=  "</option>";
    }
	
	$output .= "</select>";
	
	return $output;
}

$output = '';

// Check if update has been clicked, handle erroneous conditions, or upload
//print_r($_POST);

if (isset($_POST["upload"])){
	
	$destinations = $_POST["destinations"];
	$captions = $_POST["captions"];
	$descriptions = $_POST["descriptions"];
	$files = $_POST["files"];
	$selected = $_POST["Selected"];
	
	$counter = $imported = 0;

	global $config;
  	
	$files = get_files($config['basedir'] . 'uploads');

	if ($_POST["destination_radio"] == "new" && $_POST["new_album_name"] == ""){
            $output .= '<p class="errors">' . plog_tr('New album name not specified!') . '</p>';
        }
	else {
		
		if ($_POST["destination_radio"] == "new"){
			// Create the new album
			$result = add_album($_POST["new_album_name"], NULL, $_POST["collections_menu"]);
			$album_id = $result["id"];
		}
		else
		{
			$album_id = $_POST["destination"];
		}

		
		if ($album_id) {
			foreach($files as $file) {
				$file_key = md5($file);
				if (in_array($file_key,$selected)) {
				
					$file_name = SmartStripSlashes($file);
					// fully qualified file name
					//$fqfn = $config["basedir"] . "uploads/".$file_name;
					$fqfn = $file;

					// attempt to chmod the pictures directory before moving them
					@chmod(dirname($fqfn), 0777);
					
					if (is_file($fqfn)) {
						$result = add_picture($album_id,$fqfn,basename($file_name),$captions[$file_key], $descriptions[$file_key]);
						if ($result["picture_id"] != false) {
							$imported++;
							// delete thumbnail file if it exists
							$thumbpath = $config['basedir'] . 'thumbs/import-' . basename($file_name);
							if (is_file($thumbpath) && is_readable($thumbpath))
							{
								unlink($thumbpath);
							};
						};
					}
						
					$counter++;
				};
			
			}
			
			// get album name for display
			$sql = "SELECT name FROM ".TABLE_PREFIX."albums WHERE id = $album_id";
			$result = run_query($sql);
			
			$row = mysql_fetch_assoc($result);
			 
			$output .= '<p class="actions">'.sprintf(plog_tr('%d picture(s) were successfully imported to album <strong>%s</strong>'),$imported,$row['name']). '</p>';
		
			if ($imported == 0)
				$output .= '<p class="errors">' . plog_tr('Make sure to CHMOD 777 your newly created folders within the uploads directory or else Plogger cannot access them.  Plogger cannot CHMOD the directory for you while PHP is in safe mode.') . '</p>';
		}
		else
			$output .= '<p class="errors">'.$result['output'].'</p>';

	}

	// read the list again, so any newly created directories show up
	$files = get_files($config['basedir'] . 'uploads');
  	
  	// build a list of unique directories from the filenames
  	$directories = array();
  	
  	foreach ($files as $file) {
  		 
  		 $dirname = dirname($file);
  		 
  		 if (!in_array($dirname, $directories))
  		 	$directories[md5($dirname)] = $dirname;
  	}  		 
  		
  	// here we will check which group of pictures we are editing, grouped by directory
  	if (count($directories) > 0) {
  		$output .= '<div class="actions">' . plog_tr('Would you like to import anything else?');
  		
  		$output .= '<ul>';			
  		
  		foreach ($directories as $dirkey => $group) {
  				 $output .= '<li><a class="folder" href="'.$_SERVER['PHP_SELF']."?directory=$dirkey".'">'.basename($group).'</a></li>';
  		}
		
  		$dirkey = md5($upload_directory);
		  $output .= '<li><a class="folder" href="'.$_SERVER['PHP_SELF']."?directory=$dirkey".'">' . plog_tr('All Pictures') . '</a></li>';
  
  		$output .= '</ul></div>';
  		
  	}
	
}
  else {
  $output .= '
  	<h1>' . plog_tr('Import Photos') . '</h1>';
  	

  	$upload_directory = $config['basedir'] . 'uploads';
  	if (!is_writable($upload_directory))
  		$output .= '<p class="errors">' . plog_tr('Your "Uploads" directory is NOT WRITABLE!  Use your FTP client to CHMOD the directory with the proper permissions or your import may fail!') . '</p>';
  
  	$files = get_files($upload_directory);

	
  	
  	// build a list of unique directories from the filenames
  	$directories = array();

  	foreach ($files as $file) {
  		 
  		 $dirname = dirname($file);

		 $dirkey = md5($dirname);
  		 
		// using md5 hashes for directory names allows for easier validation of given directory name
		// and also allows us to work with international characters in directory names
  		 if (!in_array($dirname, $directories))
  		 		$directories[md5($dirname)] = $dirname;
  	}

  	if (count($files) == 0) {
  		 $output .= '<div class="actions">' . plog_tr('No images found in the <strong>/uploads/</strong> directory. To mass import pictures into your gallery, simply:<ul> <li><strong>Open an FTP connection</strong> to your website</li> <li>Transfer photos you wish to publish to the <strong>/uploads/</strong> directory</li><li>Optionally, you can create folders within that directory to import in groups</li></ul>') . '</div>';
  	}

  	// here we will check which group of pictures we are editing, grouped by directory
  	if (!isset($_GET["directory"]) && count($directories) > 0) {
  		$output .= '<div class="actions">' . plog_tr('Choose a directory you wish to import from:');
  		
  		$output .= '<ul>';			
  		
  		foreach ($directories as $dirkey => $group) {
  			$output .= '<li><a class="folder" href="'.$_SERVER['PHP_SELF']."?directory=$dirkey".'">'.basename($group).'</a></li>';
  		}
  
  		$dirkey = md5($upload_directory);
  		// $output .= '<li><a class="folder" href="'.$_SERVER['PHP_SELF'].'?directory='.$dirkey.'">All pictures</a></li>';
  		$output .= '</ul></div>';
  		
  	}
  	else {
		// real_directory is the full path
		// show_directory is what the user sees, it's relative so the directory structure of the server
		// is not exposed
		$show_directory = "uploads";
  		if (isset($_GET["directory"]) && isset($directories[$_GET["directory"]])) {
			$real_directory = $directories[$_GET["directory"]];
			$show_directory .= substr($real_directory,strlen($upload_directory));
		}
  		else {
			$real_directory = $upload_directory;
  		}
  		
		$files = get_files($real_directory);
  		
  		if (count($files) > 0) 
			$output .= '<p class="actions">' . sprintf(plog_tr('You are currently looking at <strong>%d</strong> image(s) within the <strong>%s</strong> directory.<br/>Creating thumbnails: %s done.'),count($files),$show_directory,'<span id="progress">0%</span>') . '</p>';

  	
  		// check to make sure album is writable and readable, and issue warning
  		if (!is_writable($real_directory) || !is_readable($real_directory))
  			$output .= '<p class="actions">' . plog_tr('Warning: this directory does not have the proper permissions settings!  You must CHMOD 777 on this directory using your FTP software or import may fail.');	
  		
  		
		$albums = get_albums();
		$queue_func = "";
		$keys = array();
	    for($i=0; $i<count($files); $i++) {  
	    	$file_key = md5($files[$i]);
			$keys[] = "'$file_key'";
			$relative_name = substr($files[$i],strlen($upload_directory)+1);
	  		if ($i == 0)
	  		$output.= '<form id="uploadForm" action="'.$_SERVER["PHP_SELF"].'" method="post" enctype="multipart/form-data">
	  							<table><tr class="header"><td></td><td>' . plog_tr('Thumbnail') . '</td><td>' . plog_tr('Filename') . '</td><td>' . plog_tr('Caption &amp; Description (optional)') . '</td></tr>';
	  						
	  		// For each file within upload directory, list checkmark, thumbnail, caption box, album box

			$table_row_color = ($counter%2) ? "color-1" : "color-2";
	  		
	  		// start a new table row (alternating colors)
	  		$output .= "<tr class=\"$table_row_color\">";
	
			// generate XHTML with thumbnail and link to picture view.
			$imgtag = '<td><div class="img-shadow" id="pic_'.$file_key . '"><img src="../graphics/ajax-loader.gif"></td>';
			$output .= '<td width="15"><input type="checkbox" name="Selected[]" value="'.$file_key.'" checked="checked"/></td>';
			$output .= $imgtag;
			$output .= '</td>';
			$output .= '<td>'.basename($files[$i]).'</td>';
			$output .= '<td><input type="text" style="width: 400px" name="captions[' . $file_key . ']"/><br/>
			<textarea style="width: 400px" name="descriptions[' . $file_key . ']" rows="3"></textarea></td></tr>';
			$counter++;  
	    }

		if (count($files) != 0) {
		  	$output .= '</table><a href="#" onclick="checkAll(document.getElementById(\'uploadForm\')); return false; ">' . plog_tr('Invert Checkbox Selection') . '</a>'; 
		    
		    // here we can preselect some default options based on the structure of the import directory
		    // if pictures are within one directory, simply place the name of the album within the
		    // create new album selector and allow user to pick collection.
		    // if two levels deep, preselect appropriate existing album and collection
		    // or place album name in new box
		    
		    // break up directory name into parts
		    $directory_parts = explode("/", $show_directory);
		    
		    if (isset($_REQUEST['collection_name']) && isset($_REQUEST['album_name'])) {
			    $collection_name = $_REQUEST['collection_name'];
			    $album_name = $_REQUEST['album_name'];
		    }
		    else {
			    $collection_name = @$directory_parts[2];
			    $album_name = @$directory_parts[3];
		    }		    
	
		     // check if album exists
		    if (is_null($album_name)) // file is only one level deep, assume folder name is album name
		    	$sql = "SELECT id FROM ".TABLE_PREFIX."albums WHERE name = '".$collection_name."'";
			else 
		    	$sql = "SELECT id FROM ".TABLE_PREFIX."albums WHERE name = '".$album_name."'";
		    
		    
		    $result = run_query($sql);
			$row = mysql_fetch_assoc($result);
			
			if(!isset($row['id'])) { // album doesn't exist, place in new album box
				$existing = "";
				$new_album = "checked='checked'";
				if (is_null($album_name))
					$new_album_name = $collection_name;
				else
					$new_album_name = $album_name;
			}
			else {
				$existing = "checked='checked'";
				$new_album = "";
			}
		    
		    $output .=  '
		      <h1>' . plog_tr('Destination:') . '</h1>
		      <table>
		      <tr><td>
		      <table><tr valign="middle"><td width="20"><input accesskey="a" type="radio" name="destination_radio" 
		      value="existing" '.$existing.'></td><td><label>Existing <em>A</em>lbum</label></td></tr></table>
			  '.generate_albums_menu($albums,"single", $row['id']).'
			  <td><h3>OR</h3></td>
		      <td><table><tr valign="middle"><td width="20"><input accesskey="b" onclick="var k=document.getElementsByName(\'new_album_name\');k[0].focus()" 
		      type="radio" name="destination_radio" 
		      value="new" '.$new_album.'></td><td><label>Create a New Al<em>b</em>um</label></td></table><table>
		      <tr valign="middle"><td width="120"><label>' . plog_tr('New Album Name:') . '</label></td><td width="160"><input type="text" 
		      name="new_album_name" value="'.$new_album_name.'"
onclick="var k=document.getElementsByName(\'destination_radio\');k[1].checked=true;" /> 
		      <td width="90">' . plog_tr('In collection:') . '</td><td>
				'.generate_collections_menu().'</td></tr></table></td><tr>
		      <td><br/><input class="submit" type="submit" name="upload" value="' . plog_tr('Import') . '" /></td></tr>
		      </table></div>';
		
		    $output .= '</form>';
			$key_arr = join(",\n",$keys);

		$output .= "<script type='text/javascript'>\nvar importThumbs=[\n";
		$output .= $key_arr;
		$output .= "];\n";
		$output .="requestImportThumb();</script>";
		}
	}
}


$output_error = '<h1>' . plog_tr('Import') . '</h1><p class="actions">' . sprintf(plog_tr('Before you can begin importing photos to your gallery, you must create at least <strong>one collection</strong> AND <strong>one album</strong> within that collection.  Move over to the <a href="%s">"Manage"</a> tab to begin creating your organizational structure'),'plog-manage.php') . '</p>';

$num_albums = count_albums();

if ($num_albums > 0)
	 display($output, "import");
else
	 display($output_error, "import");
	 
?>
