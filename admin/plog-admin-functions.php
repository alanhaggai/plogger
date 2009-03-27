<?php


function get_files($directory) {
	// Try to open the directory
	if($dir = opendir($directory)) {
	// Create an array for all files found
	$tmp = Array();

	// Add the files
	while($file = readdir($dir)) {
		// Make sure the file exists
		if($file != "." && $file != ".." && $file[0] != '.') {
			// If it's a directiry, list all files within it
			if(is_dir($directory . "/" . $file)) {
				$tmp2 = get_files($directory . "/" . $file);
				if(is_array($tmp2)) {
					$tmp = array_merge($tmp, $tmp2);
				}
			} else if (is_readable($directory . "/" . $file)) {
				$filename = basename(stripslashes($file));
				$pi = pathinfo($file);
				if (is_allowed_extension($pi["extension"])) {
					array_push($tmp, $directory . "/" . $file);
				}
			}
           }
       }
       // Finish off the function
       closedir($dir);
       return $tmp;
   }
}

function add_picture($album_id,$tmpname,$filename,$caption,$desc) {
	global $config;

	
	$filename_parts = explode(".",strrev($filename),2);
	$filename_base = strrev($filename_parts[1]);
	$filename_ext = strrev($filename_parts[0]);

	$result = array(
		'output' => '',
		'picture_id' => false,
	);

	$i = 0;

	$unique_filename_base = strtolower(sanitize_filename($filename_base));

	// now get the name of the collection

	$sql = "SELECT c.path AS collection_path, c.id AS collection_id,
			a.path AS album_path, a.id AS album_id
			FROM ".TABLE_PREFIX."albums a, ".TABLE_PREFIX."collections c
			WHERE c.id = a.parent_id AND a.id = '$album_id'";

	$sql_result = run_query($sql);
	$albumdata = mysql_fetch_assoc($sql_result);

	// this shouldn't happen in normal cases
	if (empty($albumdata)) {
		$result['errors'] .= plog_tr('No such album!');
		return $result;
	}

	$dest_album_name = SmartStripSlashes($albumdata["album_path"]);
	$dest_collection_name = SmartStripSlashes($albumdata["collection_path"]);

	$create_path = $dest_collection_name."/".$dest_album_name;

	while (is_file('../images/'.$create_path."/".$unique_filename_base . "." . $filename_ext)){
		$unique_filename_base = $filename_base . " (" . ++$i .")";
	}

	$final_filename = $unique_filename_base . "." . $filename_ext;

	// final fully qualified file name
	$final_fqfn = $config["basedir"].'images/'.$create_path.'/'.$final_filename;

	if (!makeDirs($config['basedir'].'images/'.$create_path, 0777)) {
		$result['errors'] .= sprintf(plog_tr('Could not create directory %s!'),$create_path);
		return $result;
	};

	if (is_uploaded_file($tmpname)) {
		if (!move_uploaded_file($tmpname,$final_fqfn)) {
			$result['errors'] .= sprintf(plog_tr('Could not move uploaded file! %s to %s'),$tmpname,$final_fqfn);
			return $result;
		} 
	}
	else
	if (!rename($tmpname,$final_fqfn)) {
		$result['errors'] .= sprintf(plog_tr('Could not move file! %s to %s'),$tmpname,$final_fqfn);
		return $result;
	};

	@unlink($tmpname);
	$res = chmod($final_fqfn, 0755);

	// Get the EXIF data.
	require_once(PLOGGER_DIR . "/lib/exifer1_5/exif.php");
	$exif_raw = read_exif_data_raw($final_fqfn,false);
	$exif = array();

	$exif["date_taken"] = (isset($exif_raw["SubIFD"]["DateTimeOriginal"])) ? trim($exif_raw["SubIFD"]["DateTimeOriginal"]) : '';
	$exif["camera"] = (isset($exif_raw["IFD0"]["Make"]) && isset($exif_raw["IFD0"]["Model"])) ? trim($exif_raw["IFD0"]["Make"]) . " " . trim($exif_raw["IFD0"]["Model"]) : '';
	$exif["shutter_speed"] = (isset($exif_raw["SubIFD"]["ExposureTime"])) ? $exif_raw["SubIFD"]["ExposureTime"] : '';
	$exif["focal_length"] = (isset($exif_raw["SubIFD"]["FocalLength"])) ? $exif_raw["SubIFD"]["FocalLength"] : '';
	$exif["flash"] = (isset($exif_raw["SubIFD"]["Flash"])) ? $exif_raw["SubIFD"]["Flash"] : '';
	$exif["aperture"] = (isset($exif_raw["SubIFD"]["FNumber"])) ? $exif_raw["SubIFD"]["FNumber"] : '';

	$picture_path = $create_path . "/" . $final_filename;

	$query = "INSERT INTO `".TABLE_PREFIX."pictures`
		(`parent_collection`,
		`parent_album`,
		`path`,
		`date_modified`,
		`date_submitted`,
		`allow_comments`,
		`EXIF_date_taken`,
		`EXIF_camera`,
		`EXIF_shutterspeed`,
		`EXIF_focallength`,
		`EXIF_flash`,
		`EXIF_aperture`,
		`caption`,
		`description`)
		VALUES
          ('".$albumdata['collection_id']."',
           '".$albumdata['album_id']."','".mysql_escape_string($picture_path)."',
           NOW(),
           NOW(),
           1,
           '".mysql_escape_string($exif["date_taken"])."',
           '".mysql_escape_string($exif["camera"])."',
           '".mysql_escape_string($exif["shutter_speed"])."',
           '".mysql_escape_string($exif["focal_length"])."',
           '".mysql_escape_string($exif["flash"])."',
           '".mysql_escape_string($exif["aperture"])."',
           '".mysql_escape_string($caption)."',
           '".mysql_escape_string($desc)."')";
           
	$sql_result = run_query($query);

	$result['output'] .= sprintf(plog_tr('Your photo (%s) was uploaded successfully.'),$filename);
	$result['picture_id'] = mysql_insert_id();
	
	// let's generate the thumbnail and the large thumbnail right away.
	// this way, the user won't see any latency from the thumbnail generation
	// when viewing the gallery for the first time
	// this also helps with the image pre-loading problem introduced
	// by a javascript slideshow.
	
	$thumbpath = generate_thumb($picture_path, $result['picture_id'],THUMB_SMALL);
	#$thumbpath = generate_thumb($picture_path, $result['picture_id'],THUMB_LARGE);
	
	return $result;
};

function update_picture($id,$caption,$allow_comments,$description) {
	$id = intval($id);
	$caption = mysql_real_escape_string($caption);
	$description = mysql_real_escape_string($description);
	$allow_comments = intval($allow_comments);
	$query = "UPDATE ".TABLE_PREFIX."pictures SET
			caption = '$caption',
			description = '$description',
			allow_comments = '$allow_comments'
		WHERE id='$id'";
	$result = mysql_query($query);
	if ($result) 
		return array('output' => plog_tr('You have successfully modified the selected picture.'));
	else
		return array('errors' => mysql_error());
}

function update_picture_field($picture_id, $field, $value) {
	$fields = array('caption','description');
	if (!in_array($field,$fields)) {
		return array('errors' => plog_tr('Invalid action'));
	};

	$errors = $output = "";
	
	$picture_id = intval($picture_id);
	$value = mysql_real_escape_string($value);

	$query = "UPDATE ".TABLE_PREFIX."pictures SET $field = '$value' WHERE id='$picture_id'";
	
	$result = mysql_query($query);
	if ($result) {
		return array('output' => plog_tr('You have successfully modified the selected picture.'));
	} else {
		return array('errors' => plog_tr('Could not modify selected picture'));
	};
	
}

function move_picture($pic_id,$to_album) {
	global $config;
	// we need the parent_id from the album we're changing to
	$to_album = intval($to_album);
	$pic_id = intval($pic_id);

	$query = "SELECT * FROM ".TABLE_PREFIX."albums WHERE `id` = '$to_album'";
	$result = run_query($query);
	$row = mysql_fetch_assoc($result);

	if (!is_array($row)) {
		return array('errors' => sprintf(plog_tr('There is no album with id %d'),$to_album));
	};
		
	$new_collection = $row['parent_id'];
	

	// move picture to new location
	// we need to query to get collection names and album names to find new directory path

	$picture = get_picture_by_id($pic_id);
	$album = get_album_by_id($to_album);

	$filename = SmartStripSlashes(basename($picture['path']));
	$directory = SmartStripSlashes($album['collection_path'])."/".SmartStripSlashes($album['album_path'])."/";
	$new_path = $directory.$filename;

	if (!rename($config['basedir']."images/".SmartStripSlashes($picture['path']), $config['basedir']."images/".$new_path)) {
		return array('errors' => sprintf(plog_tr("Could not move file! %s to %s"),$picture["path"],$new_path));
	};

	$new_path = mysql_real_escape_string($new_path);
	
	// update database
	$sql = "UPDATE ".TABLE_PREFIX."pictures SET
			path = '$new_path',
			parent_album = '$to_album',
			parent_collection = '$new_collection'
		WHERE id = '$pic_id'";
	if (!mysql_query($sql)) {
		return array('errors' => mysql_error());
	};
	return array('output' => plog_tr('Success'));
}
		
function delete_picture($del_id) {
	global $config;
	$del_id = intval($del_id);
	global $thumbnail_config;
	$picture = get_picture_by_id($del_id);
	if ($picture) {
		
		$query = "DELETE FROM ".TABLE_PREFIX."pictures WHERE `id`= '" . $picture['id'] . "'";
		run_query($query);
		
		// delete all comments for the picture
		$query = "DELETE FROM ".TABLE_PREFIX."comments WHERE `parent_id`= '" . $picture['id'] . "'";
		run_query($query);

		// make sure that the file is actually located inside our images directory
		$full_path = realpath($config['basedir'] . 'images/' . $picture['path']);
		// also check whether this image is in the correct folder
		$relative_path = substr($full_path,0,strlen($config['basedir']));
		$basename = basename($picture['path']);
		if ($relative_path == $config['basedir']) {
			foreach($thumbnail_config as $tkey => $tval) {
				$thumbpath = $config['basedir'].'thumbs/'.$tval['filename_prefix'].$picture['id'].'-'.$basename;
				if (file_exists($thumbpath) && is_writable($thumbpath)) {
					//print "deleting $thumbpath<br/>";
					@chmod($thumbpath, 0777);
					unlink($thumbpath);
				};
			};
			if (is_file($full_path)) {
				// print "deleting $full_path<br/>";
				@chmod($full_path, 0777);
				
				if (!unlink($full_path))
					 return array('errors' => plog_tr('Could not physically delete file from disk!'));
			};
		} else {
			return array('errors' => plog_tr('Picture has invalid path, ignoring delete request'));
		};
	} else {
		return array('errors' => sprintf(plog_tr('There is no picture with id %d'),$del_id));
	};
};

function add_collection($collection_name, $description) {
	global $config;
	$output = $errors = "";
	$id = 0;
	$collection_name = trim(SmartStripSlashes($collection_name));
	if (empty($collection_name)) {
		return array("errors" => plog_tr("Please enter a valid name for the collection"));
	};

	// do not allow collections with duplicate names, otherwise mod_rewritten links will start
	// to behave weird.
	$collection_exists = get_collection_by_name($collection_name);
	if ($collection_exists) {
		return array("errors" => sprintf(plog_tr('New collection could not be created, because there already is one named `%s`!'),$collection_exists));
	}

	$collection_folder = strtolower(sanitize_filename($collection_name));
	// first try to create the directory, and only if that succeeds, then insert
	// a new row into collections table, otherwise the collection will not be usable
	// anyway
	$create_path = $config["basedir"] . "/images/".$collection_folder;

	// create directory
	if (!makeDirs($create_path, 0777)) {
		$errors .= sprintf(plog_tr("Could not create directory %s!"),$create_path);
	} else {
		$sql_name = mysql_real_escape_string($collection_name);
		$description = mysql_real_escape_string($description);
		$collection_folder = mysql_real_escape_string($collection_folder);
		$query = "INSERT INTO ".TABLE_PREFIX."collections  (`name`,`description`,`path`) VALUES ('$sql_name', '$description', '$collection_folder')";
		$result = run_query($query);
		$id = mysql_insert_id();

		$output .= sprintf(plog_tr('You have successfully created the collection <strong>%s</strong>'),$collection_name);    
	};

	// caller can check the value of id, if it is zero, then collection creation failed
	// errors and output are separate, because this way the caller can format the return value
	// as it needs
	$result = array(
		"output" => $output,
		"errors" => $errors,
		"id" => $id,
	);
	return $result;

}

function update_collection($collection_id,$name,$description,$thumbnail_id = 0) {
	global $config;

	$errors = $output = "";
	
	$name = trim(SmartStripSlashes($name));
	if (empty($name)) {
		return array("errors" => plog_tr("Please enter a valid name for the collection"));
	};

	$target_name = strtolower(sanitize_filename($name));
	

	$errors = $output = "";

	$collection_id = intval($collection_id);
	$thumbnail_id = intval($thumbnail_id);

	$name = mysql_real_escape_string($name);
	$description = mysql_real_escape_string($description);

	// rename the directory
	// first, get the collection name of our source collection
	$sql = "SELECT c.path as collection_path,name
			FROM ".TABLE_PREFIX."collections c
			WHERE c.id = '$collection_id'";

	$result = run_query($sql);
	$row = mysql_fetch_assoc($result);
	
	// do not allow collections with duplicate names, otherwise mod_rewritten links will start
	// to behave weird.
	$collection_exists = get_collection_by_name($name);
	if ($row["name"] != $name && $collection_exists) {
		return array("errors" => sprintf(plog_tr('Collection `%s could not be renamed to `%s, because there is another collection with that name'),$row['name'],$name));
	}

	$source_collection_name = SmartStripSlashes($row["collection_path"]);
	$source_path = $config["basedir"] . "images/".$source_collection_name;
	$target_path = $config["basedir"] . "images/".$target_name;
	
	// perform the rename on the directory
	if (!rename($source_path, $target_path)) {
		return array("errors" => sprintf(plog_tr("Error renaming directory! (%s to %s)"),$source_path,$target_path));
	};

	$target_name = mysql_real_escape_string($target_name);

	$query = "UPDATE ".TABLE_PREFIX."collections SET name = '$name', path = '$target_name', description = '$description', thumbnail_id = '$thumbnail_id' WHERE id='$collection_id'";
	$result = mysql_query($query);
	if (!$result) {
		return array("errors" => mysql_error());
	};


	$output = plog_tr('You have successfully modified the selected collection.');

	// XXX: Update the path only if a collection was actually renamed

	// update the path field for all pictures within that collection
	// now we need to update the database paths of all pictures within source album
	$sql = "SELECT p.id AS id,p.path AS path, c.name AS collection_name, a.path AS album_path
		FROM ".TABLE_PREFIX."albums a, ".TABLE_PREFIX."pictures p, ".TABLE_PREFIX."collections c
		WHERE p.parent_album = a.id AND p.parent_collection = c.id AND p.parent_collection = '$collection_id'";

	$result = run_query($sql);

	while($row = mysql_fetch_assoc($result)) {

		$filename = basename(SmartStripSlashes($row['path']));
		$album_path = $row['album_path'];

		$new_path = mysql_escape_string($target_name."/".$album_path."/".$filename);

		// update database
		$sql = "UPDATE ".TABLE_PREFIX."pictures SET path = '$new_path' WHERE id = '$row[id]'";
		mysql_query($sql) or ($output .= mysql_error());
	}

	return array(
		"errors" => $errors,
		"output" => $output,
	);
}

function update_collection_field($collection_id, $field, $value) {
	$fields = array('name','description');
	if (!in_array($field,$fields)) {
		return array('errors' => plog_tr('Invalid action'));
	};

	$errors = $output = "";
	
	$collection_id = intval($collection_id);
	$value = mysql_real_escape_string($value);

	$query = "UPDATE ".TABLE_PREFIX."collections SET $field = '$value' WHERE id='$collection_id'";
	
	$result = mysql_query($query);
	if ($result) {
		return array('output' => plog_tr('You have successfully modified the selected collection.'));
	} else {
		return array('errors' => plog_tr('Could not modify selected collection'));
	};
	
}

function delete_collection($del_id) {
	global $config;
	$sql = "SELECT c.name AS collection_name, c.path AS collection_path, c.id AS collection_id
		FROM ".TABLE_PREFIX."collections c
		WHERE c.id = '$del_id'";

	$result = run_query($sql);
	$collection = mysql_fetch_assoc($result);

	if (!$collection) {
		return array('errors' => plog_tr('No such collection'));
	};

	// first delete all albums registered with this album
	$sql = 'SELECT * FROM '.TABLE_PREFIX.'albums WHERE parent_id = ' . $collection['collection_id'];
	$result = run_query($sql);
	while ($row = mysql_fetch_assoc($result)) {
		delete_album($row['id']);
	};
			
	// XXX: un-register collection
	$query = "DELETE FROM ".TABLE_PREFIX."collections WHERE `id`= '" . $collection['collection_id'] . "'";
	run_query($query);

	// finally try to delete the directory itself. It will succeed, if there are no files left inside it ..
	// if there are then .. how did they get there? Probably not through plogger and in this case do we 
	// really want to delete those?
	$source_collection_name = $collection["collection_path"];

	$collection_directory = realpath($config['basedir'] . 'images/'.$source_collection_name);
	$relative_path = substr($collection_directory,0,strlen($config['basedir']));
	$collection_path = explode('/',substr($collection_directory,strlen($config['basedir'])));
	// it needs to have 2 parts - images and collection name, if it doesn't, then there is something
	// wrong with collection name and it's probably not safe to try to delete the directory
	if ($relative_path == $config['basedir'] && sizeof($collection_path) == 2) {
		@chmod($collection_directory,0777);
		$delete_result = rmdir($collection_directory);
		if (!$delete_result) {
			return array('errors' => plog_tr('Collection directory still contains files after all albums have been deleted.'));
		};
		
	} else {
		return array('errors' => plog_tr('Collection has invalid path, not deleting directory'));
	};
	return array();
}

function add_album($album_name, $description, $pid) {
	global $config;
	$output = $errors = "";
	$id = 0;
	$album_name = trim(SmartStripSlashes($album_name));
	if (empty($album_name)) {
		return array("errors" => plog_tr("Please enter a valid name for the album"));
	};
	// get the parent collection name
	$query = "SELECT c.path as collection_path FROM ". TABLE_PREFIX."collections c WHERE id = '$pid'";

	$result = run_query($query);
	$row = mysql_fetch_assoc($result);

	// this shouldn't happen
	if (empty($row)) {
		return array("errors" => plog_tr("No such collection"));
	};

	$album_folder = strtolower(sanitize_filename($album_name));

	// first try to create the directory to hold the images, if that fails, then the album
	// will be unusable anyway
	$create_path = $config["basedir"] . "/images/".$row["collection_path"]."/".$album_folder;

	if (!makeDirs($create_path, 0777)) {
		$errors .= sprintf(plog_tr("Could not create directory %s!"),$path);
	} else {
		$sql_name = mysql_real_escape_string($album_name);
		$description = mysql_real_escape_string($description);
		$album_folder = mysql_real_escape_string($album_folder);
		$query = "INSERT INTO ".TABLE_PREFIX."albums (`name`,`description`,`parent_id`,`path`) VALUES ('$sql_name', '$description', '$pid','$album_folder')";
		$result = run_query($query);
		$id = mysql_insert_id();

		$output .= sprintf(plog_tr('You have successfully created the album <strong>%s</strong>'),$album_name);
	};
	// caller can check the value of id, if it is zero, then album creation failed
	// errors and output are separate, because this way the caller can format the return value
	// as it needs
	$result = array(
		"output" => $output,
		"errors" => $errors,
		"id" => $id,
	);
	return $result;
}

function update_album($album_id,$name,$description,$thumbnail_id = 0) {
	global $config;

	$errors = $output = "";

	$target_name = strtolower(sanitize_filename($name));

	$album_id = intval($album_id);
	$thumbnail_id = intval($thumbnail_id);
	$name = mysql_real_escape_string(SmartStripSlashes($name));
	$description = mysql_real_escape_string(SmartStripSlashes($description));
	

	 // first, get the album name and collection name of our source album
	$sql = "SELECT c.path AS collection_path, a.path AS album_path
			FROM ".TABLE_PREFIX."albums a, ".TABLE_PREFIX."collections c
			WHERE c.id = a.parent_id AND a.id = '$album_id'";

	$result = run_query($sql);
	$row = mysql_fetch_assoc($result);

	$source_album_name = SmartStripSlashes($row["album_path"]);
	$source_collection_name = SmartStripSlashes($row["collection_path"]);     


	$source_path = $config['basedir'] . "images/".$source_collection_name."/".$source_album_name;
	$target_path = $config['basedir'] . "images/".$source_collection_name."/".$target_name;

	// perform the rename on the directory
	if (!rename($source_path, $target_path))
	{
		return array(
			"errors" => sprintf(plog_tr("Error renaming directory! (%s to %s)"),$source_path,$target_path));
	};

	$target_name = mysql_real_escape_string($target_name);

	// proceed only if rename succeeded
	$query = "UPDATE ".TABLE_PREFIX."albums SET
			name = '$name',
			description = '$description',
			thumbnail_id = '$thumbnail_id',
			path = '$target_name'
		 WHERE id='$album_id'";

	
	$result = mysql_query($query);
	if (!$result) {
		return array("errors" => mysql_error());
	};


	$output .= plog_tr('You have successfully modified the selected album.');

	// update the path field for all pictures within that album
	$sql = "SELECT p.path AS path, p.id AS id,c.name AS collection_name, a.name AS album_name
			FROM ".TABLE_PREFIX."albums a, ".TABLE_PREFIX."pictures p, ".TABLE_PREFIX."collections c
			WHERE p.parent_album = a.id AND p.parent_collection = c.id AND p.parent_album = '$album_id'";

	$result = run_query($sql);

	while($row = mysql_fetch_assoc($result)) {

		$filename = basename($row['path']);
		$new_path = $source_collection_name."/".$target_name."/".$filename;

		// update database
		$sql = "UPDATE ".TABLE_PREFIX."pictures SET path = '$new_path' WHERE id = '$row[id]'";
		mysql_query($sql) or ($errors .= mysql_error());
	}

	return array(
		"errors" => $errors,
		"output" => $output,
	);
}

function update_album_field($album_id, $field, $value) {
	$fields = array('name','description');
	if (!in_array($field,$fields)) {
		return array('errors' => plog_tr('Invalid action'));
	};
	
	$value = mysql_real_escape_string(SmartStripSlashes($value));
	$errors = $output = "";
	$album_id = intval($album_id);

	 
	// proceed only if rename succeeded
	$query = "UPDATE ".TABLE_PREFIX."albums SET
			$field = '$value'
		 WHERE id='$album_id'";

	$result = mysql_query($query);

	if ($result) {
		return array('output' => plog_tr('You have successfully modified the selected album.'));
	} else {
		return array('errors' => plog_tr('Could not modify selected album'));
	};
}

function move_album($album_id,$to_collection) {
	global $config;

	$res = array(
		'errors' => '',
		'output' => '',
	);

	$album_id = intval($album_id);
	$to_collection = intval($to_collection);

	$sql = "SELECT c.path as collection_path, a.path as album_path
			FROM ".TABLE_PREFIX."albums a, ".TABLE_PREFIX."collections c
			WHERE c.id = a.parent_id AND a.id = '$album_id'";

	$result = run_query($sql);
	$row = mysql_fetch_assoc($result);

	$source_album_name = SmartStripSlashes($row["album_path"]);
	$source_collection_name = SmartStripSlashes($row["collection_path"]);

	// next, get the collection name of our destination collection
	$sql = "SELECT c.path as collection_path
			FROM ".TABLE_PREFIX."collections c
			WHERE c.id = '$to_collection'";

	$result = run_query($sql);
	$row = mysql_fetch_assoc($result);

	$target_collection_name = SmartStripSlashes($row["collection_path"]);
	$source_path = $config['basedir']."images/".$source_collection_name."/".$source_album_name;
	$target_path = $config['basedir']."images/".$target_collection_name."/".$source_album_name;

	// attempt to make new album directory in target collection
	@mkdir($target_path, 0775);

	//if (!rename($source_path, $target_path))
	//  $output .= '<p class="errors">Could not rename directory!</p>';

	// now we need to update the database paths of all pictures within source album
	$sql = "SELECT p.path as path, p.id as picture_id, c.name as collection_name, a.name as album_name
		FROM ".TABLE_PREFIX."albums a, ".TABLE_PREFIX."pictures p, ".TABLE_PREFIX."collections c
		WHERE p.parent_album = a.id AND p.parent_collection = c.id AND p.parent_album = '$album_id'";

	$result = run_query($sql);

	while($row = mysql_fetch_assoc($result)) {
		$filename = basename($row['path']);

		$old_path = $source_path."/".$filename;
		$new_path = $target_path."/".$filename;

		if (!rename($old_path, $new_path))
			$res['errors'] .=  sprintf(plog_tr("Could not move file! %s to %s"),$old_path,$new_path);
		
		$path_insert = mysql_real_escape_string($target_collection_name."/".$source_album_name."/".$filename);

		$sql = "UPDATE ".TABLE_PREFIX."pictures SET
				parent_collection = '$to_collection',
				path = '$path_insert'
			WHERE id = '$row[picture_id]'";
		mysql_query($sql) or ($res['errors'] .= mysql_error());
	}

	// update the parent id of the moved album
	$query = "UPDATE ".TABLE_PREFIX."albums SET `parent_id` = '$to_collection' WHERE `id`='$album_id'";
	$result = run_query($query);

	return $res;
}

function delete_album($del_id) {
	global $config;
	$sql = "SELECT c.name AS collection_name, a.name AS album_name, a.id AS album_id, c.path AS collection_path, a.path AS album_path
		FROM ".TABLE_PREFIX."albums a, ".TABLE_PREFIX."collections c
		WHERE c.id = a.parent_id AND a.id = '$del_id'";

	$result = run_query($sql);
	$album = mysql_fetch_assoc($result);

	if (!$album) {
		return array('errors' => plog_tr('No such album'));
	};

	// first delete all pictures registered with this album
	$sql = 'SELECT * FROM '.TABLE_PREFIX.'pictures WHERE parent_album = ' . $album['album_id'];
	$result = run_query($sql);
	while ($row = mysql_fetch_assoc($result)) {
		delete_picture($row['id']);
	};
			
	// XXX: un-register album
	$query = "DELETE FROM ".TABLE_PREFIX."albums WHERE `id`= '" . $album['album_id'] . "'";
	run_query($query);

	// finally try to delete the directory itself. It will succeed, if there are no files left inside it ..
	// if there are then .. how did they get there? Probably not through plogger and in this case do we 
	// really want to delete those?
	$source_album_name = $album["album_path"];
	$source_collection_name = $album["collection_path"];

	$album_directory = realpath($config['basedir'] . 'images/'.$source_collection_name."/".$source_album_name);
	$relative_path = substr($album_directory,0,strlen($config['basedir']));
	$album_path = explode('/',substr($album_directory,strlen($config['basedir'])));
	// it needs to have 3 parts - images, collection name and album name, if it doesn't, then there is something
	// wrong with either collectio or album name and it's probably not safe to try to delete the directory
	if ($relative_path == $config['basedir'] && sizeof($album_path) == 3) {
		@chmod($album_directory,0777);
		$delete_result = rmdir($album_directory);
		if (!$delete_result) {
			return array('errors' => plog_tr('Album directory still contains files after all pictures have been deleted.'));
		};
		
	} else {
		return array('errors' => plog_tr('Album has invalid path, not deleting directory'));
	};
	return array();
}



function update_comment($id,$author,$email,$url,$comment) {
	$id = intval($id);
	$author = mysql_real_escape_string($author);
	$email = mysql_real_escape_string($email);
	$url = mysql_real_escape_string($url);
	$comment = mysql_real_escape_string($comment);

	$query = "UPDATE ".TABLE_PREFIX."comments SET author = '$author', comment = '$comment',
			url = '$url', email = '$email' WHERE id='$id'";
	$result = mysql_query($query);
	if ($result) {
		return array('output' => plog_tr('You have successfully modified the selected comment.'));
	} else {
		return array('errors' => plog_tr('Could not modify selected comment'));
	};
}

function update_comment_field($id, $field, $value) {
	$allowed_fields = array('author','email','url','comment');
	if (!in_array($field,$allowed_fields)) {
		return array('errors' => plog_tr('Invalid action'));
	};

	$id = intval($id);
	$value = mysql_real_escape_string($value);

	$query = "UPDATE ".TABLE_PREFIX."comments SET $field = '$value' WHERE id='$id'";
	$result = mysql_query($query);
	if ($result) {
		return array('output' => plog_tr('You have successfully modified the selected comment.'));
	} else {
		return array('errors' => plog_tr('Could not modify selected comment'));
	};
}

function count_albums($parent_id = 0) {
	if (!$parent_id)
		$numquery = "SELECT COUNT(*) AS `num_albums` FROM `".TABLE_PREFIX."albums`";
	else
		$numquery = "SELECT COUNT(*) AS `num_albums` FROM `".TABLE_PREFIX."albums` WHERE parent_id = '$parent_id'";
		
	$numresult = run_query($numquery);
	$num_albums = mysql_result($numresult, 'num_albums');
	return $num_albums;
}

function count_collections() {
	
	$numquery = "SELECT COUNT(*) AS `num_collections` FROM `".TABLE_PREFIX."collections`";
		
	$numresult = run_query($numquery);
	$num_albums = mysql_result($numresult, 'num_collections');
	return $num_albums;
}

function count_pictures($parent_id = 0) {
	if (!$parent_id)
		$numquery = "SELECT COUNT(*) AS `num_pics` FROM `".TABLE_PREFIX."pictures`";
	else
		$numquery = "SELECT COUNT(*) AS `num_pics` FROM `".TABLE_PREFIX."pictures` WHERE parent_album = '$parent_id'";
		
	$numresult = run_query($numquery);
	$num_pics = mysql_result($numresult, 'num_pics');
	return $num_pics;
}

function count_comments($parent_id = 0) {
	if (!$parent_id)
		$numquery = "SELECT COUNT(*) AS `num_comments` FROM `".TABLE_PREFIX."comments`";
	else
		$numquery = "SELECT COUNT(*) AS `num_comments` FROM `".TABLE_PREFIX."comments` WHERE parent_id = '$parent_id'";
		
	$numresult = run_query($numquery);
	$num_comments = mysql_result($numresult, 'num_comments');
	return $num_comments;
}

function edit_comment_form($comment_id)
{
	$output = '';
	$comment_id = intval($comment_id);
	$sql = "SELECT * FROM ".TABLE_PREFIX."comments c WHERE c.id = '$comment_id'";
	$result = run_query($sql);
	$comment = mysql_fetch_assoc($result);
	if (!is_array($comment))
	{
		// XXX: return an error message instead
		return false;
	}
	$output .= '<form class="edit" action="'.$_SERVER["PHP_SELF"].'" method="post"><table>';
	                $output .= '<tr><td>' . plog_tr('Author:') . '<br/><input size="30" name="author" id="author" value="'.SmartStripSlashes($comment['author']).'"></td>
                                    <td>' . plog_tr('Email:') . '<br/><input size="30" name="email" id="email" value="'.SmartStripSlashes($comment['email']).'"></td>
                                        <td>' . plog_tr('Website:') . '<br/><input size="30" name="url" id="url" value="'.SmartStripSlashes($comment['url']).'"></td></tr>
                                        <tr><td colspan="3">' . plog_tr('Comment:') . '<br/> <textarea cols="70" rows="4" name="comment" id="comment">'.
                                        SmartStripSlashes($comment['comment']).'</textarea></td></tr></table>';
                                        
                $output .= '<input type="hidden" name="pid" value="'.$comment['id'].'">
					<input type="hidden" name="action" value="update-comment">
					<button class="submit" type="submit">' . plog_tr('Update') . '</button>';
	
		if (isset($_REQUEST["level"]))
		{
			$output .= '<input type="hidden" name="level"  value="'.$_REQUEST['level'].'">';
		}
		
		if (isset($_REQUEST["id"]))
		{
			$output .= '<input type="hidden" name="id"  value="'.$_REQUEST['id'].'">';
		}
                
                $output .= '</form>';
		return $output;
}


function makeDirs($strPath, $mode = 0777) //creates directory tree recursively
{
   return is_dir($strPath) or ( makeDirs(dirname($strPath), $mode) and mkdir($strPath, $mode) );
}

// 
function configure_htaccess_fullpic($allow = false) {
	$cfg = "";
	$placeholder_start = "# BEGIN Plogger";
	$placeholder_end = "# END Plogger";
	$thisfile =  "/admin/" . basename(__FILE__);
	$adm = strpos($_SERVER["PHP_SELF"],"/admin");
	$rewritebase = substr($_SERVER["PHP_SELF"],0,$adm);
	if (!$allow) {
		$cfg .= "\n";
		$cfg .= "deny from all";
	};	
	// read the file
	global $config;
	$fpath = $config["basedir"] . "images/.htaccess"; 
	$htaccess_lines = (is_file($fpath)) ? @file($fpath) : array();

	$output = "";
	$configuration_placed = false;
	$between_placeholders = false;
	foreach($htaccess_lines as $line) {
		$tline = trim($line);
		if ($placeholder_start == $tline) {
			$between_placeholders = true;
			$output .= $line . $cfg;
			$configuration_placed = true;
			continue;
		}
		if ($placeholder_end == $tline) {
			$between_placeholders = false;
			$output .= $line;
			continue;
		}
		if ($between_placeholders) continue;

		$output .= $line;
	};

	// no placeholders? append to the end
	if (!$configuration_placed) {
		$output .= $placeholder_start . "\n" . $cfg . $placeholder_end . "\n";
 	};

	$fh = @fopen($fpath,"w");
	// write changes out if the file can be opened.
	// XXX: perhaps plog-options.php should check whether settings can be written and warn the user if not?
	$success = false;
	if ($fh) {
		$success = true;
		fwrite($fh,$output);
		fclose($fh);
	};
	return $success;
}

function configure_mod_rewrite($enable = false) {
	$cfg = "";
	$placeholder_start = "# BEGIN Plogger";
	$placeholder_end = "# END Plogger";
	$thisfile =  "/admin/" . basename(__FILE__);
	$adm = strpos($_SERVER["PHP_SELF"],"/admin");
	$rewritebase = substr($_SERVER["PHP_SELF"],0,$adm);
	if ($enable) {
		$cfg .= "\n";
		if (empty($rewritebase))
		{
			$rewritebase = "/";
		};
		$cfg .= "<IfModule mod_rewrite.c>\n";
		$cfg .= "RewriteEngine on\n";
		$cfg .= "RewriteBase $rewritebase\n";
		$cfg .= "RewriteCond %{REQUEST_FILENAME} -d [OR]\n";
		$cfg .= "RewriteCond %{REQUEST_FILENAME} -f\n";
		$cfg .= "RewriteRule ^.*$ - [S=2]\n";
		$cfg .= "RewriteRule feed/$ plog-rss.php?path=%{REQUEST_URI} [L]\n";
		$cfg .= "RewriteRule ^.*$ index.php?path=%{REQUEST_URI} [L]\n";
		$cfg .= "</IfModule>\n";
	};	
	// read the file
	global $config;
	$fpath = $config["basedir"] . ".htaccess"; 
	$htaccess_lines = @file($fpath);

	$output = "";
	$configuration_placed = false;
	$between_placeholders = false;
	foreach($htaccess_lines as $line) {
		$tline = trim($line);
		if ($placeholder_start == $tline) {
			$between_placeholders = true;
			$output .= $line . $cfg;
			$configuration_placed = true;
			continue;
		}
		if ($placeholder_end == $tline) {
			$between_placeholders = false;
			$output .= $line;
			continue;
		}
		if ($between_placeholders) continue;

		$output .= $line;
	};

	// no placeholders? append to the end
	if (!$configuration_placed) {
		$output .= $placeholder_start . "\n" . $cfg . $placeholder_end . "\n";
 	};

	$fh = @fopen($fpath,"w");
	// write changes out if the file can be opened.
	// XXX: perhaps plog-options.php should check whether settings can be written and warn the user if not?
	$success = false;
	if ($fh) {
		$success = true;
		fwrite($fh,$output);
		fclose($fh);
	};
	return $success;
}

// makes sure that argument does not contain characters that cannot be allowed, like . or /, which
// could be used to point to directory or file names outside the Plogger directory
function is_valid_directory($str) 
{
	// allow only alfanumeric characters, hyphen, [, ], dot, apostrophe  and space in collection names
	return !preg_match("/[^\w|\.|'|\-|\[|\] ]/",$str);
}

/// XXX: something for the future: perhaps hooks for plugins should be implemented,
// so plugis could add new fields to all those forms.
function plog_add_collection_form() {
	$output = '<input type="button" class="submit" id="show-collection" onclick="toggle(\'create-collection\'); toggle(\'show-collection\')" value="' . plog_tr('Create a Collection') . '">';
	$output .= '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
	$output .= '<div id="create-collection" class="cssbox-green" style="width: 385px !important; display: none;">  
		<div class="cssbox_head-green"><h2>' . plog_tr('Create a Collection') . '</h2></div>  
		<div class="cssbox_body-green"><label accesskey="n" for="name"><em>N</em>ame:</label><br/><input name="name" id="name"/>
	 <br/><label accesskey="d" for="description"><em>D</em>escription:</label><br/><input name="description" id="description" size="50">
	 <input name="action" type="hidden" value="add-collection">
	 <input class="submit" type="submit" value="' . plog_tr('Add Collection') . '">
	 </div></div></form>';
	 return $output;
}

function plog_add_album_form($parent_collection) {
	$parent_collection = intval($parent_collection);
	$output = '<input type="button" class="submit" id="show-album" onclick="toggle(\'create-album\'); toggle(\'show-album\')" value="' . plog_tr('Create an Album') . '">';
	$output .= '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
	$output .= '<div id="create-album" class="cssbox-green" style="width: 385px !important; display: none;">  
		<div class="cssbox_head-green"><h2>' . plog_tr('Create an Album') . '</h2></div>  
		<div class="cssbox_body-green"><label accesskey="n" for="name"><em>N</em>ame:</label><br/><input name="name" id="name">
	 <br/><label accesskey="d" for="description"><em>D</em>escription:</label><br/><input name="description" id="description" size="50">
	 <input name="action" type="hidden" value="add-album">
	 <input type="hidden" name="parent_collection" value="' . $parent_collection . '"/>
	 <input class="submit" type="submit" value="' . plog_tr('Add Album') . '"></div></div></form>';
	 return $output;
}

function plog_edit_collection_form($collection_id) {
	global $thumbnail_config;
	$output = '';
	$collection_id = intval($collection_id);
	$output .= '<form class="edit" action="'.$_SERVER["PHP_SELF"].'" method="post">';
	$collection = get_collection_by_id($collection_id);

	$auto_graphic = "../graphics/auto.gif";
		
	$images = '<option class="thumboption" value="0" style="padding-left: 100px; background-image: url('.$auto_graphic.'); background-repeat: no-repeat;">' . plog_tr('automatic') . '</option>';
		
	// create a list of all pictures in the collection. Should I create a separate
	// function for this as well?
	$sql = "SELECT p.id AS id,caption,p.path AS path,a.name AS album_name
			FROM ".TABLE_PREFIX."pictures p
			LEFT JOIN " . TABLE_PREFIX . "albums AS a ON p.parent_album = a.id
			WHERE p.parent_collection = '" . $collection_id . "'
			ORDER BY a.name,p.date_submitted";
				
	$result = run_query($sql);
	while($row = mysql_fetch_assoc($result)) {
		$selected = ($row["id"] == $collection["thumbnail_id"]) ? " selected" : "";
		$style = 'class="thumboption" style="padding-left: '.($thumbnail_config[THUMB_SMALL]["size"] + 5).'px; background-image: url('.generate_thumb(SmartStripSlashes($row["path"]), $row["id"]).'); background-repeat: no-repeat;"';
			
		$images .= "<option $style value='" . $row["id"] . "'" . $selected . ">";
		$images .= SmartStripSlashes($row["album_name"]) . " : ";
		$images .= !empty($row["caption"]) ? SmartStripSlashes($row["caption"]) : SmartStripSlashes(basename($row["path"]));
		$images .= "</option>\n";
	};


	$output .= '<label accesskey="n" for="name"><em>N</em>ame:</label><br/><input size="30" name="name" id="name" value="'.SmartStripSlashes($collection['name']).'"><br/>
				    <label accesskey="d" for="description"><em>D</em>escription:</label><br/><input size="80" name="description" id="description" value="'.SmartStripSlashes($collection['description']).'"><br/>
				    Thumbnail:<br/><select name="thumbnail_id" onchange="updateThumbPreview(this)" 
				    class="thumbselect" id="thumbselect">' . $images . '</select>
				    <script type="text/javascript">updateThumbPreview(document.getElementById(\'thumbselect\'));</script>';
					
		$output .= '<input type="hidden" name="pid" value="'.$collection_id.'">
					<input type="hidden" name="action" value="update-collection">
					<button class="submit" type="submit">' . plog_tr('Update') . '</button>';
		
		$output .= '</form>';
		return $output;
}

function plog_edit_album_form($album_id) {
	global $thumbnail_config;
	
	$album_id = intval($album_id);
	
	$album = get_album_by_id($album_id);
	$auto_graphic = "../graphics/auto.gif";
	
	$output .= '<form class="edit" action="'.$_SERVER["PHP_SELF"].'?level=albums&amp;id='.$album["parent_id"].'" method="post">';
	
	$images = '<option class="thumboption" value="0" style="padding-left: 100px; background-image: url('.$auto_graphic.'); 
		background-repeat: no-repeat;">' . plog_tr('automatic') . '</option>';
	
	$sql = "SELECT id,caption,path FROM ".TABLE_PREFIX."pictures p WHERE p.parent_album = '" . $album_id . "'";
	
	$result = run_query($sql);
	while($row = mysql_fetch_assoc($result)) {
			$selected = ($row["id"] == $album["thumbnail_id"]) ? " selected" : "";
			$style = 'class="thumboption" style="padding-left: '.($thumbnail_config[THUMB_SMALL]["size"] + 5).'px; background-image: 
			url('.generate_thumb(SmartStripSlashes($row["path"]), $row["id"]).'); background-repeat: no-repeat;"';
			
			$images .= "<option $style value='" . $row["id"] . "'" . $selected . ">";
			$images .= !empty($row["caption"]) ? SmartStripSlashes($row["caption"]) : SmartStripSlashes(basename($row["path"]));
			$images .= "</option>\n";
		};
		

		$output .= '<label for="name" accesskey="n"><em>N</em>ame:</label><br/><input size="30" name="name" id="name" value="'.SmartStripSlashes($album['name']).'"><br/>
				    <label for="description" accesskey="d"><em>D</em>escription:</label><br/><input size="80" name="description" id="description" value="'.SmartStripSlashes($album['description']).'"><br/>
				    Thumbnail:<br/><select name="thumbnail_id" class="thumbselect" id="thumbselect" 
				    onchange="updateThumbPreview(this)">' . $images . '</select>
				    <script type="text/javascript">updateThumbPreview(document.getElementById(\'thumbselect\'));</script>';
					
		$output .= '<input type="hidden" name="pid" value="'.$album_id.'">
					<input type="hidden" name="action" value="update-album">
					<tr><td><button class="submit" type="submit">' . plog_tr('Update') . '</button>';
		
		$output .= '</form>';
		return $output;

}

function plog_picture_manager($id,$from,$limit) {

	plogger_init_pictures(array(
			'type' => 'album',
			'value' => $id,
			'from' => $from,
			'limit' => $limit,
	));

	// create javascript initiation function for editable elements
	if (plogger_has_pictures()) {
		$output .= '<script type="text/javascript">';
		$output .= "Event.observe(window, 'load', init, false);";
		$output .= "function init() {";
		
		while(plogger_has_pictures()) {
			plogger_load_picture();
			$output .= "makeEditable('picture-description-".plogger_get_picture_id()."'); makeEditable('picture-caption-".plogger_get_picture_id()."');";
		}
		$output .= "}";
		$output .= '</script>';
	}
	
	// reset the picture array
		plogger_init_pictures(array(
			'type' => 'album',
			'value' => $id,
			'from' => $from,
			'limit' => $limit,
	));
	
	if (plogger_has_pictures()) {
		$output .= '<table style="width: 100%" cellpadding="4">
			   <col style="width: 15px;"/><tr class="header"><td class="table-header-left">&nbsp;</td>';
		$output .= '<td width="65" class="table-header-middle">' . plog_tr('Thumb') . '</td>';
		$output .= '<td class="table-header-middle">' . plog_tr('Filename') . '</td>';
		$output .= '<td class="table-header-middle">' . plog_tr('Caption') . '</td>';
		$output .= '<td class="table-header-middle">' . plog_tr('Description') . '</td>';
		$output .= '<td class="table-header-middle">' . plog_tr('Allow comments') . '</td>';
		$output .= '<td class="table-header-right">' . plog_tr('Actions') . '</td></tr>';
		$counter = 0;
		while(plogger_has_pictures()) {
			if ($counter%2 == 0) $table_row_color = "color-1";
			else $table_row_color = "color-2";
			$counter++;
			plogger_load_picture();
			$id = plogger_get_picture_id();
			$output .= "<tr class='$table_row_color'>";
			$output .= "<td><input type='checkbox' name='selected[]' value='" . $id . "'/></td>";

			$thumbpath = plogger_get_picture_thumb();

			$imgtag = '<div class="img-shadow"><img src="'.$thumbpath.'" title="'.plogger_get_picture_caption().'" alt="'.plogger_get_picture_caption().'" /></div>';
			//$target = 'plog-thumbpopup.php?src='.$id;
			//$java = "javascript:this.ThumbPreviewPopup('$target')";
			
			$output .= '<td><a href="'.plogger_get_picture_thumb(THUMB_LARGE).'" rel="lightbox" title="'.plogger_get_picture_caption().'">'.$imgtag.'</a></td>';

			$output .= "<td><strong><a class='folder' href='?level=comments&amp;id=" . $id . "'>" . basename(plogger_get_source_picture_path()) . "</a></strong></td>";

			$output .= "<td><p id=\"picture-caption-" . plogger_get_picture_id() ."\">" . plogger_get_picture_caption() . "&nbsp;</p></td>";
			$output .= "<td><p id=\"picture-description-" . plogger_get_picture_id()  ."\">" . plogger_get_picture_description() . "&nbsp;</p></td>";
			
			$allow_comments = (1 == plogger_picture_allows_comments()) ? "Yes" : "No";


			$output .= "<td>" . $allow_comments . "</td>";

			$output .= '<td><a href="?action=edit-picture&amp;id=' . $id;
			
			if (isset($_GET["entries_per_page"])) $output .= '&amp;entries_per_page=' . intval($_GET["entries_per_page"]);
			if (isset($_GET["plog_page"])) $output .= '&amp;plog_page=' . intval($_GET["plog_page"]);
			
			$output .= '"><img style="display:inline" src="../graphics/edit.gif" alt="' . plog_tr('Edit') . '" title="' . plog_tr('Edit') . '"></a>';
		
			$parent_id = $_REQUEST["id"];
			$output .= '<a href="?action=1&amp;selected%5B%5D=' . $id . '&amp;level=pictures&amp;delete_checked=1&amp;id='.$parent_id;
			
			if (isset($_GET["entries_per_page"])) $output .= '&amp;entries_per_page=' . intval($_GET["entries_per_page"]);
			if (isset($_GET["plog_page"])) $output .= '&amp;plog_page=' . intval($_GET["plog_page"]);
			
			$output .= '"
		onClick="return confirm(\'' . plog_tr('Are you sure you want to delete this item?') . '\');"><img style="display:inline" src="../graphics/x.gif" alt="' . plog_tr('Delete') . '" 					title="' . plog_tr('Delete') . '"></a></td>';

		
			$output .= "</tr>";

		};
		$output .= '<tr class="header"><td colspan="7"></td></tr></table>';
		$output .= "</table>";
	} else {
		$output .= '<p class="actions">' . sprintf(plog_tr('Sadly, there are no pictures yet.  Why don\'t you <a href="%s">upload some?</a>'),'plog-upload.php') . '</p>';
	};
	return $output;
}

function plog_album_manager($id,$from,$limit) {
	
	
	plogger_init_albums(array(
		'from' => $from,
		'collection_id' => $id,
		'limit' => $limit,
		'all_albums' => 1,
		'sortby' => 'id',
		'sortdir' => 'asc'
	));
	
		// create javascript initiation function for editable elements
	if (plogger_has_albums()) {
		$output .= '<script type="text/javascript">';
		$output .= "Event.observe(window, 'load', init, false);";
		$output .= "function init() {";
		
		while(plogger_has_albums()) {
			plogger_load_album();
			// makeEditable('album-name-".plogger_get_album_id()."');
			$output .= "makeEditable('album-description-".plogger_get_album_id()."');";
		}
		$output .= "}";
		$output .= '</script>';
	}
	
	plogger_init_albums(array(
		'from' => $from,
		'collection_id' => $id,
		'limit' => $limit,
		'all_albums' => 1,
		'sortby' => 'id',
		'sortdir' => 'asc'
	));
	
	if (plogger_has_albums()) {

		$output .= '<table style="width: 100%" cellpadding="4">
		       <col style="width: 15px;"/><tr class="header"><td class="table-header-left">&nbsp;</td>';
		$output .= '<td class="table-header-middle">' . plog_tr('Name') . '</td>';
		$output .= '<td class="table-header-middle">' . plog_tr('Description') . '</td>';
		$output .= '<td class="table-header-right">' . plog_tr('Actions') . '</td></tr>';
		$counter = 0;

		while(plogger_has_albums()) {
			plogger_load_album();
			$id = plogger_get_album_id();
			if ($counter%2 == 0) $table_row_color = "color-1";
			else $table_row_color = "color-2";
			$counter++;

			$output .= "<tr class='$table_row_color'>";
			$output .= "<td><input type='checkbox' name='selected[]' value='" . $id . "'/></td>";

			$output .= "<td><a class='folder' href='?level=pictures&amp;id=" .$id . "'><span id='album-name-" . plogger_get_album_id(). "'><strong>" . plogger_get_album_name() . "</span></a></strong> &#8212; " . sprintf(plog_tr('contains %d picture(s)'),plogger_album_picture_count()) . "</td>";

			$output .= "<td><p id='album-description-" . plogger_get_album_id() . "'>" . plogger_get_album_description() . "&nbsp;</p></td>";

			$output .= '<td><a href="?action=edit-album&amp;id=' . $id . '"><img style="display:inline" src="../graphics/edit.gif" alt="' . plog_tr('Edit') . '" title="' . plog_tr('Edit') . '"></a>';
		$output .= '<a href="?action=1&amp;selected%5B%5D=' . $id . '&amp;level=albums&amp;delete_checked=1&amp;id='.$_REQUEST["id"].'" 
		onClick="return confirm(\'' . plog_tr('Are you sure you want to delete this item?') . '\');"><img style="display:inline" src="../graphics/x.gif" alt="' . plog_tr('Delete') . '" 					title="' . plog_tr('Delete') . '"></a></td>';

			$output .= "</tr>";

			
		};
		$output .= '<tr class="header"><td colspan="7"></td></tr></table>';
		$output .= "</table>";
	} else {
		$output .= "<p class='actions'>" . plog_tr("There are no albums in this collection yet, why don't you create one?") . "</p>";
	};
	return $output;


}

function plog_collection_manager($from,$limit) {

	plogger_init_collections(array(
		'from' => $from,
		'limit' => $limit,
		'all_collections' => 1,
		'sortby' => 'id',
		'sortdir' => 'asc'
	));
	
			// create javascript initiation function for editable elements
	if (plogger_has_collections()) {
		$output .= '<script type="text/javascript">';
		$output .= "Event.observe(window, 'load', init, false);";
		$output .= "function init() {";
		
		while(plogger_has_collections()) {
			plogger_load_collection();
			// makeEditable('collection-name-".plogger_get_collection_id()."');
			$output .= "makeEditable('collection-description-".plogger_get_collection_id()."');";
		}
		$output .= "}";
		$output .= '</script>';
	}
	
	plogger_init_collections(array(
		'from' => $from,
		'limit' => $limit,
		'all_collections' => 1,
		'sortby' => 'id',
		'sortdir' => 'asc'
	));

	if (plogger_has_collections()) {
		$output .= '<table style="width: 100%" cellpadding="4">
		<col style="width: 15px;"/><tr class="header"><td class="table-header-left"></td>';
		$output .= '<td class="table-header-middle">' . plog_tr('Name') . '</td>';
		$output .= '<td class="table-header-middle">' . plog_tr('Description') . '</td>';
		$output .= '<td class="table-header-right">' . plog_tr('Actions') . '</td></tr>';
		$counter = 0;
		while(plogger_has_collections()) {
			plogger_load_collection();
			if ($counter%2 == 0) $table_row_color = "color-1";
			else $table_row_color = "color-2";
			$counter++;
			$id = plogger_get_collection_id();

			$output .= "<tr class='$table_row_color'>";
			$output .= "<td><input type='checkbox' name='selected[]' value='" . $id . "'/></td>";

			$output .= "<td><a class='folder' href='?level=albums&amp;id=" .$id . "'><strong><span id='collection-name-" . plogger_get_collection_id() . "'>" . plogger_get_collection_name() . "</span></a></strong> &#8212; " . sprintf(plog_tr('contains %d albums'),plogger_collection_album_count()) . "</td>";
			
			$output .= "<td><p id='collection-description-" . plogger_get_collection_id() . "'>" . plogger_get_collection_description() . "&nbsp;</p></td>";

			$output .= '<td><a href="?action=edit-collection&amp;id=' . $id . '"><img style="display:inline" src="../graphics/edit.gif" alt="' . plog_tr('Edit') . '" title="' . plog_tr('Edit') . '"></a>';
		
			$output .= '<a href="?action=1&amp;selected%5B%5D=' . $id . '&amp;level=collections&amp;delete_checked=1&amp;id='.@$_REQUEST["id"].'" 
			onClick="return confirm(\'' . plog_tr('Are you sure you want to delete this item?') . '\');"><img style="display:inline" src="../graphics/x.gif" alt="' . plog_tr('Delete') . '" 					title="' . plog_tr('Delete') . '"></a></td>';

			$output .= "</tr>";
		};
		$output .= '<tr class="header"><td colspan="7"></td></tr></table>';
		$output .= "</table>";
	} else {
		$output .= "<p class='actions'>" . plog_tr('There are no collections yet') . "</p>";
	};
	return $output;
}

function plog_comment_manager($id,$from,$limit) {

	plogger_init_picture(array(
			'id' => $id,
	));
	
	// create javascript initiation function for editable elements
	if (plogger_picture_has_comments()) {
		$output .= '<script type="text/javascript">';
		$output .= "Event.observe(window, 'load', init, false);";
		$output .= "function init() {";
		
		while(plogger_picture_has_comments()) {
			plogger_load_comment();
			// makeEditable('collection-name-".plogger_get_collection_id()."');
			$output .= "makeEditable('comment-comment-".plogger_get_comment_id()."');
						makeEditable('comment-author-".plogger_get_comment_id()."');
						makeEditable('comment-url-".plogger_get_comment_id()."');
						makeEditable('comment-email-".plogger_get_comment_id()."');";
		}
		$output .= "}";
		$output .= '</script>';
	}
	
	plogger_init_picture(array(
			'id' => $id,
	));
	
	if (plogger_picture_has_comments()) {
		$output .= '<table style="width: 100%" cellpadding="4">
		<col style="width: 15px;"/><tr class="header"><td class="table-header-left">&nbsp;</td>';
		$output .= '<td class="table-header-middle">' . plog_tr('Author') . '</td>';
		$output .= '<td class="table-header-middle">' . plog_tr('E-mail') . '</td>';
		$output .= '<td class="table-header-middle">' . plog_tr('URL') . '</td>';
		$output .= '<td class="table-header-middle">' . plog_tr('Date') . '</td>';
		$output .= '<td class="table-header-middle">' . plog_tr('Comment') . '</td>';
		$output .= '<td class="table-header-right">' . plog_tr('Actions') . '</td></tr>';
		$counter = 0;
		while(plogger_picture_has_comments()) {
			plogger_load_comment();
			if ($counter%2 == 0) $table_row_color = "color-1";
			else $table_row_color = "color-2";

			$id = plogger_get_comment_id();



			$output .= "<tr class='$table_row_color'>";
			$output .= "<td><input type='checkbox' name='selected[]' value='" . $id . "'/></td>";
			$output .= "<td><p id=\"comment-author-" . $id ."\">" . plogger_get_comment_author() . "&nbsp;</p></td>";
			$email = plogger_get_comment_email();
			$output .= "<td><p id=\"comment-email-" . $id ."\">" . $email . "&nbsp;</p></td>";
			$output .= "<td><p id=\"comment-url-" . $id ."\">" . plogger_get_comment_url() . "&nbsp;</p></td>";
			$output .= "<td>" . plogger_get_comment_date("n.j.Y H:i:s") . "</td>";
			$output .= "<td><p id=\"comment-comment-" . $id ."\">" . plogger_get_comment_text() . "&nbsp;</p></td>";

			$output .= '<td><a href="?action=edit-comment&amp;id=' . $id . '"><img style="display:inline" src="../graphics/edit.gif" alt="' . plog_tr('Edit') . '" title="' . plog_tr('Edit') . '"></a>';
		$output .= '<a href="?action=delete-comment&amp;id=' . $id . '" 
		onClick="return confirm(\'' . plog_tr('Are you sure you want to delete this item?') . '\');"><img style="display:inline" src="../graphics/x.gif" alt="' . plog_tr('Delete') . '" title="' . plog_tr('Delete') . '"></a></td>';

			$output .= "</tr>";




	};
	$output .= '<tr class="header"><td colspan="7"></td></tr></table>';
	$output .= "</table>";


	} else {
		$output .= "<p class='actions'>" . plog_tr('This picture has no comments on it.') . "</p>";
	};

	return $output;
}

function generate_ajax_picture_editing_init() {
	
	$output = '<script type="text/javascript">';
}
?>
