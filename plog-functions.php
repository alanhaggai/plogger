<?php

function generate_breadcrumb(){
	global $config;
	
	$id = $GLOBALS["plogger_id"];
	
	switch ($GLOBALS['plogger_level']) {
		case 'collection':
			$row = get_collection_by_id($id);
			
			$breadcrumbs = ' <a accesskey="/" href="'.$config["baseurl"].'">Collections</a> &raquo; <b>' . SmartStripSlashes($row["name"]) . '</b>';
			if ($GLOBALS['plogger_mode'] == "slideshow") $breadcrumbs .= ' &raquo; Slideshow';
			
			break;
		case 'slideshow':
		case 'album':
			$row = get_album_by_id($id);
			
			$album_name = SmartStripSlashes($row["name"]);
			$album_link = generate_url("album",$row["id"]);
			
			$row = get_collection_by_id($row["parent_id"]);
			
			$collection_link = '<a accesskey="/" href="' . generate_url("collection",$row["id"]) . '">' . $row["name"] . '</a>';
			
			if ($GLOBALS['plogger_mode'] == "slideshow") {
				
				$breadcrumbs = ' <a href="'.$config["baseurl"].'">Collections</a> &raquo; ' . $collection_link . ' &raquo; ' . '<a href="'.$album_link.'">'.$album_name.'</a> &raquo; ' . ' <b>Slideshow</b>';
			} else {
				
				$breadcrumbs = ' <a href="'.$config["baseurl"].'">Collections</a> &raquo; ' . $collection_link . ' &raquo; ' . '<b>'.$album_name.'</b>';
			}
			
			break;
		case 'picture':
			$row = get_picture_by_id($id);
			$picture_name = SmartStripSlashes(basename($row["path"]));
			
			$row = get_album_by_id($row["parent_album"]);
			
			$album_link = '<a accesskey="/" href="' . generate_url("album",$row["id"]) . '">' . SmartStripSlashes($row["name"]) . '</a>';
			
			$row = get_collection_by_id($row["parent_id"]);
			
			$collection_link = ' <a href="'.$config["baseurl"].'">Collections</a> ' . ' &raquo; ' . '<a href="' . generate_url("collection",$row["id"]) . '">' . SmartStripSlashes($row["name"]) . '</a>';
			
			$breadcrumbs = $collection_link . ' &raquo; ' . $album_link . ' &raquo; ' . '<span id="image_name"><b>' . $picture_name.'</b></span>';
			
			if ($GLOBALS['plogger_mode'] == "slideshow") $breadcrumbs .= ' &raquo; Slideshow';
			
			break;
		case 'search':
			$breadcrumbs = 'You searched for <b>'.htmlspecialchars($_GET["searchterms"]).'</b>.';
			break;
		default:
			$breadcrumbs = ' <b>Collections</b>';
			break;
	}
	
	return '<div id="breadcrumb_links">'.$breadcrumbs.'</div>';
}

function generate_title(){
	switch ($GLOBALS['plogger_level']) {
		case 'collection':
			$row = get_collection_by_id($GLOBALS['plogger_id']);
			
			$breadcrumbs = SmartStripSlashes($row["name"]);
			if ($GLOBALS['plogger_mode'] == "slideshow") $breadcrumbs .= ' &raquo; Slideshow';
			
			break;
		case 'slideshow':
		case 'album':
			$row = get_album_by_id($GLOBALS['plogger_id']);
			$album_name = SmartStripSlashes($row["name"]);
						
			$row = get_collection_by_id($row["parent_id"]);
			
			if ($GLOBALS['plogger_mode'] == "slideshow") {
				$breadcrumbs = SmartStripSlashes($row["name"]) . ' &raquo; ' . $album_name.' &raquo; ' . ' Slideshow';
			} else {
				$breadcrumbs = SmartStripSlashes($row["name"]) . ' &raquo; ' . $album_name;
			}
			
			break;
		case 'picture':
			$row = get_picture_by_id($GLOBALS['plogger_id']);
			$picture_name = basename($row["path"]);
			
			$row = get_album_by_id($row["parent_album"]);
			$album_name = SmartStripSlashes($row["name"]);
			
			$row = get_collection_by_id($row["parent_id"]);
			
			$collection_name = SmartStripSlashes($row["name"]);
			
			$breadcrumbs = $collection_name . ' &raquo; ' . $album_name . ' &raquo; ' . $picture_name;
			
			if ($GLOBALS['plogger_mode'] == "slideshow") $breadcrumbs .= ' &raquo; Slideshow';
			
			break;
		default:
			$breadcrumbs = ' Collections';
	}
	
	return $breadcrumbs;
}

function generate_jump_menu() {
   	global $config;
	
	$output = '';
	$image_count = array();
	
	$output .=  '<form id="jump_menu" name="jump_menu" action="#" method="get"><div>';
	$output .=  '<select name="jump_menu" onchange="document.location.href = this.options[this.selectedIndex].value;"><option value="#">Jump to...</option>';
	
	// 1. create a list of all albums with at least one photo
	$sql = "SELECT
			`parent_album`,
			COUNT(*) AS `imagecount`
		FROM `".TABLE_PREFIX."pictures`
		GROUP BY `parent_album`";
	$result = run_query($sql);
	
	while($row = mysql_fetch_assoc($result)) {
		$image_count[$row["parent_album"]] = $row["imagecount"];
	}
	
	// 2. get a list of all albums and collections
	$sqlCollection = "SELECT
		`a`.id AS `album_id`,
		`a`.name AS `album_name`,
		`c`.id AS `collection_id`,
		`c`.name AS `collection_name`
	FROM `".TABLE_PREFIX."albums` AS `a`
		LEFT JOIN `".TABLE_PREFIX."collections` AS `c` ON `a`.`parent_id`=`c`.`id`
	ORDER BY `c`.`name` ASC, `a`.`name` ASC";
	$result = run_query($sqlCollection);
	
	$last_collection = "";
	
	while ($row = mysql_fetch_assoc($result)){
		// skip albums with no images
		if (empty($image_count[$row["album_id"]])) {
			continue;
		}
		
		if ($row["collection_id"] != $last_collection) {
			$output .= '<option value="'.generate_url("collection",$row["collection_id"]).'">'.$row["collection_name"].'</option>';
			$last_collection = $row["collection_id"];
		}
		
		$output .=  '<option value="'.generate_url("album",$row["album_id"]).'">'.SmartStripSlashes($row["collection_name"]).' : '.SmartStripSlashes($row["album_name"]);
		$output .=  '</option>';
	}
	
	$output .= '</select></div></form>';
	
	return $output;
}

function generate_exif_table($id, $condensed = 0){
	global $config;
	
	$query = "SELECT * FROM `".TABLE_PREFIX."pictures` WHERE `id`=".intval($id);
	$result = run_query($query);
	
	if (mysql_num_rows($result) > 0){
		$row = mysql_fetch_assoc($result);
		
		foreach($row as $key => $val) if (trim($row[$key]) == '') $row[$key] = '&nbsp;';
		
		$table_data = '<div id="exif_table"><table id="exif_data"';
		
		if (!$_SESSION["plogger_details"]){
			$table_data .= ' style="display: none;"';
		}
		
		// get image size
		$img = $config['basedir'] . 'images/' . SmartStripSlashes($row['path']);
		list($width, $height, $type, $attr) = getimagesize($img);
		$size = round(filesize($img) / 1024, 2);
		
		if (!$condensed) {
			$table_data .= '>
					<tr>
						<td><strong>Dimensions</strong></td>
						<td>'.$width .' x ' .$height.'</td>
					</tr>
					<tr>
						<td><strong>File size</strong></td>
						<td>'.$size.' kbytes</td>
					</tr>
					<tr>
						<td><strong>Taken on</strong></td>
						<td>'.$row["EXIF_date_taken"].'</td>
					</tr>
					<tr>
						<td><strong>Camera model</strong></td>
						<td>'.$row["EXIF_camera"].'</td>
					</tr>
					<tr>
						<td><strong>Shutter speed</strong></td>
						<td>'.$row["EXIF_shutterspeed"].'</td>
					</tr>
					<tr>
						<td><strong>Focal length</strong></td>
						<td>'.$row["EXIF_focallength"].'</td>
					</tr>
					<tr>
						<td><strong>Aperture</strong></td>
						<td>'.$row["EXIF_aperture"].'</td>
					</tr>
				</table></div>';
		} else {
			$table_data .= '><tr><td><strong>Dimensions</strong></td><td>'.$width .' x ' .$height.'</td></tr><tr><td><strong>File size</strong></td><td>'.$size.' kbytes</td></tr><tr><td><strong>Taken on</strong></td><td>'.$row["EXIF_date_taken"].'</td></tr><tr><td><strong>Camera model</strong></td><td>'.$row["EXIF_camera"].'</td></tr><tr><td><strong>Shutter speed</strong></td><td>'.$row["EXIF_shutterspeed"].'</td></tr><tr><td><strong>Focal length</strong></td><td>'.$row["EXIF_focallength"].'</td></tr><tr><td><strong>Aperture</strong></td><td>'.$row["EXIF_aperture"].'</td></tr></table></div>';
		}
	}
	
	return $table_data;
}

function plogger_display_comments() {
	global $config;
	
	if (file_exists(THEME_DIR . '/comments.php')) {
		include(THEME_DIR . '/comments.php');
	} else {
		include($config['basedir'] . '/themes/default/comments.php');
	}
}

function plogger_require_captcha() {
	$_SESSION['require_captcha'] = true;
}

// generate header produces the Gallery Name, The Jump Menu, and the Breadcrumb trail
// at the top of the image

function generate_header() {
	global $config;
	
	$output = '<h1 id="gallery-name">'.stripslashes($config["gallery_name"]).'</h1>';
	return $output;
}

function generate_sortby($level,$id){
	global $config;
	
	$output = '';
	
	$id = $GLOBALS["plogger_id"];
	
	$fields = array(
		'date' => 'Date Submitted',
		'date_taken' => 'Date Taken',
		'caption' => 'Caption',
		'filename' => 'Filename',
		'number_of_comments' => 'Number of Comments',
	);
	
	if ($level == "album"){
		// I think this should be a single form and I really should move the javascript functions
		// into a separate file
		
		// I need to merge those 2 forms. and since I'm realoading stuff anyway, I can just
		// create correct urls. oh yeah, baby.
		
		$output .= '
			<form action="#" method="get">
				<span><label for="change_sortby">Sort by:</label>
				<select id="change_sortby" name="change_sortby" 
				onchange="document.location.href = this.options[this.selectedIndex].value;">
				';
		
		foreach($fields as$fkey => $fval) {
			$value = generate_url("album",$id,array(1 => 'sorted','sortby' => $fkey,'sortdir' => $_SESSION['plogger_sortdir']));
			
			$output .= '<option value="'.$value.'"';
			
			if ($_SESSION["plogger_sortby"] == $fkey) {
				$output .= " selected='selected'";
			}
			
			$output .= ">$fval</option>\n";
		}
		
		$output .= "</select></span></form>\n";
	}
	
	return $output;
}

function generate_sortdir($level,$id){
	global $config;
	
	$output = '';
	$id = $GLOBALS["plogger_id"];
	
	$orders = array(
		'asc' => 'Ascending',
		'desc' => 'Descending',
	);
	
	if ($level == "album") {
		$output .= '
			<form action="#" method="get">
				<span><select id="change_sortdir" name="change_sortdir" 
				onchange="document.location.href = this.options[this.selectedIndex].value;">';
		
		foreach($orders as $okey => $oval) {
			$value = generate_url("album",$id,array(1 => 'sorted','sortby' => $_SESSION['plogger_sortby'],'sortdir' => $okey));
			
			$output .= "<option value=\"$value\"";
			
			if(strcasecmp($_SESSION["plogger_sortdir"], $okey) === 0) {
				$output .= ' selected="selected"';
			}
			
			$output .= ">$oval</option>\n";
		}
		
		$output .= '</select></span></form>';		
	}
	
	return $output;
}

function generate_search_box(){
	global $config;
	
	$output = '
		<form action="'.$config["baseurl"].'" method="get">
			<div>
			<input type="hidden" name="level" value="search" />
			<input type="text" name="searchterms" />
			<input class="submit" type="submit" value="Search" />
			</div>
		</form>';
	
	return $output;
}

// benchmark timing
function getmicrotime($t) {
	list($usec, $sec) = explode(" ",$t);
	return ((float)$usec + (float)$sec);
}

// function for generating the slideshow javascript
function generate_slideshow_js($id, $mode) {
	global $config;
	
	// output the link to the slideshow javascript
	$output = '<script type="text/javascript" src="'.$config['gallery_url'].'slideshow.js"></script>';
	return $output;
}

function preload_album_images(){
	global $thumbnail_config;
	$script = "<script type='text/javascript' language='JavaScript'>
	    <!--
	    function preload_images() {
	    if (document.images)
	    {
		preload_image_object = new Image();
		// set image url
		image_url = new Array();";
	      $pic_array = get_picture_by_id($GLOBALS["image_list"]);
	      $i = 0;
	      foreach($pic_array as $pic) {
		      unset($path);
		      $url = generate_thumb($pic["path"],$thumbnail_config[THUMB_LARGE]["prefix"],THUMB_LARGE);
		      $script .= "\t\timage_url[$i] = '$url'\n";
		      $i++;
	      }
	   $script .= "var i = 0;
	       for(i=0; i<image_url.length; i++) 
		 preload_image_object.src = image_url[i];
	    }
	}
	//-->
	</SCRIPT>";
    return $script;
}

function plogger_head() {
	global $config;
	
	$title = generate_title($GLOBALS['plogger_level'], $GLOBALS['plogger_id']);
	
	if ($config["embedded"] == 0) {
		print "<title>" . SmartStripSlashes($config["gallery_name"]) . ": $title </title>\n";
	}
	
	print generate_slideshow_js($GLOBALS["plogger_id"], "album");
	print "\n";
	
	// Embed URL to RSS feed for proper level.
	print '<link rel="alternate" type="application/rss+xml" title="RSS Feed" href="'.plogger_rss_link().'" />' . "\n";
	print '<meta http-equiv="Content-Type" content="text/html;charset=' . $config['charset'] . '"/>' . "\n";
}


function connect_db() {

	if (!PLOGGER_DB_HOST) {
		die("Please run _install.php to set up Plogger.  If you are upgrading from a previous version, please run _upgrade.php.");
	}

	global $PLOGGER_DBH;
	
	$PLOGGER_DBH = mysql_connect(PLOGGER_DB_HOST, PLOGGER_DB_USER, PLOGGER_DB_PW) or die ("Plogger cannot connect to the database because: " . mysql_error());

	mysql_select_db(PLOGGER_DB_NAME);
	
	$mysql_version = mysql_get_server_info();
	$mysql_charset_support = "4.1";
	
	if (1 == version_compare($mysql_version, $mysql_charset_support)) {
	        mysql_query("SET NAMES utf8");
	}

}

function run_query($query) {
	global $PLOGGER_DBH;
	
	$result = @mysql_query($query, $PLOGGER_DBH);
	
	if (!$result){
		$trace = debug_backtrace();
		
		die(mysql_error($PLOGGER_DBH) . '<br /><br />' . $query . '<br /><br />In file: '.$_SERVER["PHP_SELF"] . '<br /><br />On line: ' . $trace[0]["line"]);
	} else {
		return $result;
	}
}

function generate_thumb($path, $prefix, $type = THUMB_SMALL) {
	global $config;
	global $thumbnail_config;
	
	// for relative paths assume that they are relative to images directory,
	// otherwise just use the given pat
	if (file_exists($path)) {
		$source_file_name = $path;
	} else {
		$source_file_name = $config['basedir'] . 'images/' . SmartStripSlashes($path);
	}
	
	// the file might have been deleted and since phpThumb dies in that case
	// try to do something sensible so that the rest of the images can still be seen
	
	// there is a problem in safe mode - if the script and picture file are owned by
	// different users, then the file can not be read.
	
	if (!is_readable($source_file_name)) {
		return false;
	}
	
	$imgdata = @getimagesize($source_file_name);
	
	if (!$imgdata) {
		// unknown image format, bail out
		return false;
	}
	
	// attributes of original image
	$orig_width = $imgdata[0];
	$orig_height = $imgdata[1];
	
	// XXX: food for thought - maybe we can return URL to some kind of error image 
	// if this function fails? 
	
	$base_filename = sanitize_filename(basename($path));
	
	$thumb_config = $thumbnail_config[$type];
	
	if (1 == $thumb_config['disabled']) {
		return $config['baseurl'] . '/images/' . $path;
	}
	
	$prefix = $thumb_config['filename_prefix'] . $prefix . "-";
	
	$thumbpath = $config['basedir'] . 'thumbs/'.$prefix.$base_filename;
	$thumburl = $config['gallery_url'] . 'thumbs/'.$prefix.$base_filename;
	
	// if thumbnail file already exists and is generated after data for a thumbnail type
	// has been changed, then we assume that the thumbnail is valid.
	$thumbnail_timestamp = @filemtime($thumbpath);
	
	if (file_exists($thumbpath) && $thumb_config['timestamp'] < $thumbnail_timestamp) {
		return $thumburl;
	}
	
	// if dimensions of source image are smaller than those of the requested
	// thumbnail, then use the original image as thumbnail
	if ($orig_width <= $thumb_config['size'] && $orig_height <= $thumb_config['size']) {
		copy($source_file_name,$thumbpath);
		return $thumburl;
	}
	
	// no existing thumbnail found or thumbnail config has changed,
	// generate new thumbnail file
	
	list($width, $height, $thumb_type, $attr) = @getimagesize($thumbpath);
	
	require_once($config['basedir'] . 'lib/phpthumb/phpthumb.class.php');
	
	$phpThumb = new phpThumb();
	
	// set data
	$phpThumb->setSourceFileName($source_file_name);
	if ($imgdata[0] > $imgdata[1]) {
		$phpThumb->w = $thumb_config['size'];
	} else {
		$phpThumb->h = $thumb_config['size'];
	}
	
	$phpThumb->q = $config['compression'];
	
	// set zoom crop flag to get image squared off
	if ($type == THUMB_SMALL && $config['square_thumbs']) {
		$phpThumb->zc = 1;
		$phpThumb->h = $thumb_config['size'];
		$phpThumb->w = $thumb_config['size'];
	}
	
	$phpThumb->config_use_exif_thumbnail_for_speed = false;
	
	// Set image height instead of width if not using square thumbs
	if ($type == THUMB_SMALL && !$config['square_thumbs']) {
		$phpThumb->h = $thumb_config['size'];
		$phpThumb->w = '';
	}
	
	if($type == THUMB_NAV) {
		$phpThumb->zc = 1;
		$phpThumb->h = $thumb_config['size'];
		$phpThumb->w = $thumb_config['size'];
	}
	
	// set options (see phpThumb.config.php)
	// here you must preface each option with "config_"
	
	// Set error handling (optional)
	$phpThumb->config_error_die_on_error = false;
	
	// generate & output thumbnail
	if ($phpThumb->GenerateThumbnail()) {
		$phpThumb->RenderToFile($thumbpath);
	} else {
		// do something with debug/error messages
		die('Failed: '.implode("\n", $phpThumb->debugmessages));
	}
	
	return $thumburl;
}

function get_picture_by_id($id, $album_id = null) {
	global $config;
	
	if(is_array($id)) {
		$where_cond = "IN ('".implode("', '",$id)."');";
	} else {
		$where_cond = "= ".intval($id).";";
	}
	
	$sql = "SELECT
			`p`.*,
			`a`.`path` AS `album_path`,
			`c`.`path` AS `collection_path`
		FROM `".TABLE_PREFIX."pictures` AS `p`
			LEFT JOIN `".TABLE_PREFIX."albums` AS `a` ON `p`.`parent_album`=`a`.`id`
			LEFT JOIN `".TABLE_PREFIX."collections` AS `c` ON `p`.`parent_collection`=`c`.`id`
		WHERE `p`.`id` ".$where_cond;
	
	if ($album_id){
		$sql .= " AND `p`.`parent_album`=".intval($album_id);
	}
	
	$resultPicture = run_query($sql);
	
	if (is_array($id) && mysql_num_rows($resultPicture) > 0) {
		$picdata = array();
		while ($row = mysql_fetch_assoc($resultPicture)) {
			$row['url'] = $config['gallery_url'].'images/'.$row['collection_path'].'/'.$row['album_path'].'/'.basename($row['path']);
			array_unshift($picdata,$row);
		}
	}
	elseif (!is_array($id) && mysql_num_rows($resultPicture) > 0){
		$picdata = mysql_fetch_assoc($resultPicture);
		
		// eventually I want to get rid of the full path in pictures tables to avoid useless data duplication
		// the following is a temporary solution so I don't have to break all the functionality at once
		$picdata['url'] = $config['gallery_url'].'images/'.$picdata['collection_path'].'/'.$picdata['album_path'].'/'.basename($picdata['path']);
	} else {
		$picdata = false;
	}
	
	return $picdata;
}

function get_pictures($album_id, $order = "alpha", $sort = "DESC"){
	global $config;
	
	$query = "SELECT
			`p`.*,
			`a`.`path` AS `album_path`,
			`c`.`path` AS `collection_path`
		FROM `".TABLE_PREFIX."pictures` AS `p`
			LEFT JOIN `".TABLE_PREFIX."albums` AS `a` ON `p`.`parent_album`=`a`.`id`
			LEFT JOIN `".TABLE_PREFIX."collections` AS `c` ON `p`.`parent_collection`=`c`.`id`
		WHERE `a`.`id`=".intval($album_id);
	
	if ($order == "mod"){
		$query .= "	ORDER BY `p`.`date_submitted` ";
	}
	else {
		$query .= "	ORDER BY `p`.`caption` ";
	}
	
	if ($sort == "ASC"){
		$query .= " ASC ";
	} else {
		$query .= " DESC ";
	}
	
	$result = run_query($query);
	
	$pictures = array();
	
	while ($row = mysql_fetch_assoc($result)){
		// See comment in get_picture_by_id
		$row['url'] = $config['gallery_url'].'images/'.$row['collection_path'].'/'.$row['album_path'].'/'.basename($row['path']);
		$pictures[$row["id"]] = $row;
	}
	
	return $pictures;
}

function get_album_by_id($id) {
	global $config;
	
	$sql = "SELECT
			`a`.*,
			`c`.`path` AS `collection_path`,
			`a`.`path` AS `album_path`,
			`c`.`name` AS `collection_name`,
			`a`.`name` AS `album_name`
		FROM `".TABLE_PREFIX."albums` AS `a`
			LEFT JOIN `".TABLE_PREFIX."collections` AS `c` ON `a`.`parent_id`=`c`.`id`
			LEFT JOIN `".TABLE_PREFIX."pictures` AS `i` ON `a`.`thumbnail_id`=`i`.`id`
		WHERE `a`.`id` = ".intval($id);
	$result = run_query($sql);
	
	if (mysql_num_rows($result) > 0){
		$album = mysql_fetch_assoc($result);
		
		if ($album["thumbnail_id"] == 0){
			$query = "SELECT
					`id`,
					`path`
				FROM `".TABLE_PREFIX."pictures`
				WHERE `parent_album`=".intval($album["id"])."
				ORDER BY `date_submitted` DESC
				LIMIT 1";
			$result = run_query($query);
			
			if (mysql_num_rows($result) > 0){
				$row = mysql_fetch_assoc($result);
				$album["thumbnail_id"] = $row["id"];
			}
		}
	} else {
		$album = false;
	}
	
	return $album;
}

function get_collection_by_id($id) {
	global $config;
	
	$sqlCollection = "SELECT
			`c`.*,
			`c`.`path` AS `collection_path`
		FROM `".TABLE_PREFIX."collections` AS `c`
			LEFT JOIN `".TABLE_PREFIX."pictures` AS `i` ON `c`.`thumbnail_id`=`i`.`id`
		WHERE `c`.`id`=".intval($id)."
		ORDER BY `c`.`name` ASC";
	$resultCollection = run_query($sqlCollection);
	
	if (mysql_num_rows($resultCollection) == 0){
		$collection = false;
	}
	else {
		$collection = mysql_fetch_assoc($resultCollection);
		
		if ($collection["thumbnail_id"] == 0){
			$query = "SELECT
					`id`,
					`path`
				FROM `".TABLE_PREFIX."pictures`
				WHERE `parent_collection`=".intval($collection["id"])."
				ORDER BY `date_submitted` DESC
				LIMIT 1";
			$result = run_query($query);
			
			if (mysql_num_rows($result) > 0){
				$row = mysql_fetch_assoc($result);
				$collection["thumbnail_id"] = $row["id"];
			}
		}
	}
	
	return $collection;
}

function get_collection_by_name($name) {
	$sql = "SELECT *
		FROM `".TABLE_PREFIX."collections`
		WHERE name = '".mysql_real_escape_string($name)."'";
	$result = run_query($sql);
	
	if (mysql_num_rows($result) > 0){
		$collection = mysql_fetch_assoc($result);
	} else {
		$collection = false;
	}
	
	return $collection;
}

function get_albums($collection_id = null, $sort = "alpha", $order = "DESC") {
	global $config;
	
	$albums = array();
	
	if ($sort == "mod"){
		$query = "SELECT
				`a`.`id` AS `album_id`,
				`a`.`name` AS `album_name`,
				`c`.`id` AS `collection_id`,
				`c`.`name` AS `collection_name`,
				`a`.`description`,
				`a`.`thumbnail_id`
			FROM `".TABLE_PREFIX."pictures` AS `i`
				LEFT JOIN `".TABLE_PREFIX."albums` AS `a` ON `i`.`parent_album`=`a`.`id`
				LEFT JOIN `".TABLE_PREFIX."collections` AS `c` ON `i`.`parent_collection`=`c`.`id`
				LEFT JOIN `".TABLE_PREFIX."pictures` AS `i2` ON `a`.`thumbnail_id`=`i2`.`id` ";
		
		if ($collection_id){
			$query .= " WHERE `i`.`parent_collection`=".intval($collection_id);
		}
		
		$query .= "
			GROUP BY `i`.`parent_album`
			ORDER BY `i`.`date_submitted` ";
		
		if ($order == "ASC"){
			$query .= " ASC ";
		} else {
			$query .= " DESC ";
		}
	} else {
		$query = "SELECT
				`a`.`id` AS `album_id`,
				`a`.`name` AS `album_name`,
				`c`.`id` AS `collection_id`,
				`c`.`name` AS `collection_name`,
				`a`.`description`,
				`a`.`thumbnail_id`
			FROM `".TABLE_PREFIX."albums` AS `a`
				LEFT JOIN `".TABLE_PREFIX."collections` AS `c` ON `a`.`parent_id`=`c`.`id`
				LEFT JOIN `".TABLE_PREFIX."pictures` AS `i` ON `a`.`thumbnail_id`=`i`.`id`";
		
		if ($collection_id){
			$query .= " WHERE `c`.id=".intval($collection_id)." ";
		}
		
		$query .= " ORDER BY `c`.`name` ASC, `a`.`name` ASC";
	}
	
	$result = run_query($query);
	
	while ($album = mysql_fetch_assoc($result)) {
		if ($album["thumbnail_id"] == 0){
			$query = "SELECT
					`id`,
					`path`
				FROM `".TABLE_PREFIX."pictures`
				WHERE `parent_album`=".intval($album["album_id"])."
				ORDER BY `date_submitted` DESC
				LIMIT 1";
			$thumb_result = run_query($query);
			
			if (mysql_num_rows($thumb_result) > 0){
				$row = mysql_fetch_assoc($thumb_result);
				$album["thumbnail_id"] = $row["id"];
			}
		}
		
		$albums[$album["album_id"]] = $album;
	}
	
	return $albums;
}

function get_collections($sort = "alpha", $order = "DESC") {
	global $config;
	
	if ($sort == "mod"){
		$query = "SELECT
				`c`.*
			FROM `".TABLE_PREFIX."pictures` AS `i`
				LEFT JOIN `".TABLE_PREFIX."collections` AS `c` ON `i`.`parent_collection`=`c`.`id`
				LEFT JOIN `".TABLE_PREFIX."pictures` AS `i2` ON `c`.`thumbnail_id`=`i2`.`id`
			GROUP BY `i`.`parent_collection`
			ORDER BY `i`.`date_submitted` ";
		
		if ($order == "ASC"){
			$query .= " ASC ";
		} else {
			$query .= " DESC ";
		}
	} else {
		$query = "SELECT
			`c`.*
		FROM `".TABLE_PREFIX."collections` AS `c`
		ORDER BY `c`.`name` ";
		
		if ($order == "ASC"){
			$query .= " ASC ";
		} else {
			$query .= " DESC ";
		}
	}
	
	$resultCollection = run_query($query);
	
	$collections = array();
	
	while ($collection = mysql_fetch_assoc($resultCollection)){
		if ($collection["thumbnail_id"] == 0){
			$query = "SELECT
					`id`,
					`path`
				FROM `".TABLE_PREFIX."pictures`
				WHERE `parent_collection`=".intval($collection["id"])."
				ORDER BY `date_submitted` DESC
				LIMIT 1";
			$result = run_query($query);
			
			if (mysql_num_rows($result) > 0){
				$row = mysql_fetch_assoc($result);
				$collection["thumbnail_id"] = $row["id"];
			}
		}
		
		$collections[$collection["id"]] = $collection;
	}
	
	return $collections;
}

function SmartAddSlashes($str){
	if (get_magic_quotes_gpc()){
		return $str;
	} else {
		return addslashes($str);
	}
}

function SmartStripSlashes($str){
	if (get_magic_quotes_gpc()){
		return stripslashes($str);
	} else {
		return $str;
	}
}

// this tries hard to figure out level and object id from textual path to a resource, used 
// mostly if mod_rewrite is in use
function resolve_path($str = "") {
	$rv = array();
	$path_parts = explode("/",$str);
	
	$levels = array("collection","album","picture","arg1","arg2");
	
	$current_level = "";
	
	$names = array();
	
	foreach($levels as $key => $level) {
		if (isset($path_parts[$key])) {
			$names[$level] = mysql_escape_string(urldecode(SmartStripSlashes($path_parts[$key])));
			$current_level = $level;
		}
	}
	
	if (!empty($names["collection"])) {
		$sql = "SELECT *
		 	FROM `".TABLE_PREFIX."collections`
			WHERE `path`='".mysql_real_escape_string($names["collection"])."'";
		$result = run_query($sql);
		
		if (mysql_num_rows($result) == 0){
		 	return $rv;
		}
		
		$collection = mysql_fetch_assoc($result);
		
		// what if there are multiple collections with same names? I hope there aren't .. this would
		// suck. But here is an idea, we shouldn't allow the user to enter similar names
		$rv = array("level" => "collection","id" => $collection["id"]);
	}
	
	if (!empty($names['album'])) {
		$sql = "SELECT *
			FROM `".TABLE_PREFIX."albums`
			WHERE `path`='".mysql_real_escape_string($names["album"])."'
				AND `parent_id`=".intval($collection["id"]);
		$result = run_query($sql);
		
		if (mysql_num_rows($result) == 0){
			// no such album, fall back to collection
			return $rv;
		}
		
		$album = mysql_fetch_assoc($result);
		
		// try to detect slideshow. Downside is that you cannot have a picture with that name
		if ('slideshow' == $names['picture']) {
			return array('level' => 'album','mode' => 'slideshow','id' => $album['id']);
		}
		
		// deal with http://plogger/collection/album/sorted/field/asc and friends
		if ('sorted' == $names['picture']) {
			if (isset($names['arg1'])) {
				$_SESSION['plogger_sortby'] = $names['arg1'];
			}
			
			if (isset($names['arg2'])) {
				$_SESSION['plogger_sortdir'] = $names['arg2'];
			}
			
			return array('level' => 'album','id' => $album['id']);
		}
		
		$rv = array('level' => 'album','id' => $album['id']);
	}
	
	if (!empty($names["picture"])) {
		$sql = "SELECT *
			FROM `".TABLE_PREFIX."pictures`
			WHERE `caption`='".mysql_real_escape_string($names["picture"])."'
				AND `parent_album`=".intval($album["id"]);
		$result = run_query($sql);
		
		$picture = mysql_fetch_assoc($result);
		
		// no such caption, perhaps we have better luck with path?
		if (!$picture) {
			$filepath = join("/",$names);
			$sql = "SELECT *
				FROM `".TABLE_PREFIX."pictures`
				WHERE `path`='" . mysql_real_escape_string($filepath)."'
					AND `parent_album`=".intval($album["id"]);
			$result = run_query($sql);
			$picture = mysql_fetch_assoc($result);
		}
		
		// no dice, fall back to album
		if (!$picture) {
			return $rv;
		}
		
		$rv = array("level" => "picture", "id" => $picture["id"]);
	}
	
	return $rv;
}

function generate_pagination($url, $current_page, $items_total, $items_on_page, $extra_params = ''){
	$output = '';
	
	if (!isset($GLOBALS["total_pictures"])) $GLOBALS["total_pictures"] = 0;
	
	if (($items_total == 0) && ($GLOBALS["total_pictures"] > 0)) {
		$items_total = $GLOBALS["total_pictures"];
	}
	
	$num_pages = ceil($items_total / $items_on_page);
	
	// if adding arguments to mod_rewritten urls, then I need ? (question mark) before the arguments
	// otherwise I want &amp;
	$last = substr($url,-1);
	
	if ($last == "/") {
		//$url = substr($url,0,-1);
		$separator = "?";
	} else {
		$separator = "&amp;";
	}
	
	if ($num_pages > 1){
		if ($current_page > 1){
			$output .= ' <a accesskey="," class="pagPrev" href="'.$url.$separator.'plog_page='.($current_page - 1).$extra_params.'"><span>&laquo;</span></a> ';
		}
		
		for ($i = 1; $i <= $num_pages; $i++){
			if ($i == $current_page){
				$output .= '<span class="page_link"> ['.$i.'] </span>';
			} else{
				$output .= '<a href="'.$url.$separator.'plog_page='.$i.$extra_params.'" class="page_link">'.$i.'</a> ';
			}
		}
		
		if ($current_page != $num_pages){
			$output .= ' <a accesskey="." class="pagNext" href="'.$url.$separator.'plog_page='.($current_page + 1).$extra_params.'"><span>&raquo;</span></a> ';
		}
	}
	
	return $output;
}

// sanitize filename by replacing international characters with underscores
function sanitize_filename($str) {
	// allow only alphanumeric characters, hyphen, [, ], dot and apostrophe in file names
	// the rest will be replaced
	return preg_replace("/[^\w|\.|'|\-|\[|\]]/","_",$str);
}

function generate_url($level, $id, $arg = array(), $plaintext = false){
	global $config;
	
	$rv = '';
	
	if ($config["use_mod_rewrite"]){
		if ($level == "collection"){
			$query = "SELECT `path` FROM `".TABLE_PREFIX."collections` WHERE `id`=".intval($id);
			$result = run_query($query);
			$row = mysql_fetch_assoc($result);
			$rv = $config["baseurl"].rawurlencode($row["path"]);
		} else if ($level == "album") {
			$query = "SELECT
					`c`.`path` AS `collection_path`,
					`a`.`path` AS `album_path`
				FROM `".TABLE_PREFIX."albums` AS `a`
					LEFT JOIN `".TABLE_PREFIX."collections` AS `c` ON `a`.`parent_id`=`c`.`id`
				WHERE `a`.`id`=".intval($id);
			$result = run_query($query);
			$row = mysql_fetch_assoc($result);
			
			$rv = $config["baseurl"].rawurlencode($row["collection_path"]) . '/' . rawurlencode($row["album_path"]);
			
			// I need to give additional arguments to the url-s
			if (sizeof($arg) > 0) {
				foreach($arg as $aval) {
					$rv .= "/".$aval;
				}
			}
		} else if ($level == "picture") {
			$pic = get_picture_by_id($id);
			$album = $pic["parent_album"];
			$rv = $config["baseurl"].$pic["path"];
		}
	} else {
		if ($level == "collection"){
			return $config['baseurl'].'?level=collection&amp;id='.$id;
		} else if ($level == "album") {
			$rv = $config['baseurl'].'?level=album&amp;id='.$id;
			if (sizeof($arg) > 0) {
				foreach($arg as $akey => $aval) {
					// mod_rewrite url-s need /sorted in them, the old style ones do not.
					// this temporary workaround removes the 'sorted' string
					if ($aval != 'sorted') {
						$rv .= "&amp;$akey=$aval";
					}
				}
			}
		} else if ($level == "picture") {
			$rv = $config['baseurl'].'?level=picture&amp;id='.$id;
		}
	}

	/*	
	if (!$plaintext){
		$rv = str_replace("&amp;","&",$rv);
	}
	*/
	
	return $rv;
}

function add_comment($parent_id,$author,$email,$url,$comment) {
	global $config;
	
	if (empty($config["allow_comments"])) {
		return array("errors" => "Comments disabled");
	}
	
	if (empty($author) || empty($email)) {
		return array("errors" => "Your comment did not post!  Please fill the required fields.");
	}
	
	$ip = $_SERVER["REMOTE_ADDR"];
	$host = gethostbyaddr($ip);
	
	// I want to use the original unescaped values later - to send the email
	$sql_author = mysql_real_escape_string($author);
	$sql_email = mysql_real_escape_string($email);
	$sql_url = mysql_real_escape_string($url);
	$sql_comment = mysql_real_escape_string($comment);
	$sql_ip = mysql_real_escape_string($ip);
	
	$parent_id = intval($parent_id);
	
	$result = array();
	
	$picdata = get_picture_by_id($parent_id);
	
	if (empty($picdata)) {
		return array("errors" => "Could not post comment - no such picture");
	}
	
	if (empty($picdata["allow_comments"])) {
		return array("errors" => "Comments disabled");
	}
	
	if ($config["comments_moderate"] == 1) {
		$approved = 0;
		$notify_msg = " (awaiting your approval)";
	} else {
		$approved = 1;
	}
	
	// right now all comments will be approved, spam protection can be implemented later
	$query = "INSERT INTO `".TABLE_PREFIX."comments` SET 
			`author`='$sql_author', 
			`email`='$sql_email', 
			`url`='$sql_url', 
			`date`=NOW(),
			`comment`='$sql_comment',
			`parent_id`='$parent_id', 
			`approved` = '$approved', 
			`ip` = '$ip'";
	$result = mysql_query($query);
	
	if (!$result) {
		return array("errors" => "Could not post comment " . mysql_error());
	}
	
	// XXX: admin e-mail address should be validated
	if ($config["comments_notify"] && $config["admin_email"]) {
		// create and send notify mail message
		$msg = "New comment posted for picture " . basename($picdata['path']) . " $notify_msg\n\n";
		$msg .= "Author: $author (IP: $ip, $host)\n";
		$msg .= "E-mail: $email\n";
		$msg .= "URI: $url\n\n";
		$msg .= "Comment:\n$comment\n\n";
		$msg .= "You can see all the comments for this picture here:\n";
		$picurl = generate_url("picture",$parent_id);
		$msg .= $picurl;
		mail($config['admin_email'],SmartStripSlashes($config['gallery_name']) . ': new comment from '.$author,$msg,"From: $email");
	}
	
	return array("result" => "Comment added.");
}

// Begin basic Plogger API functions

// plogger_list_categories()
// This function will create a list of nested categorical links
// for use in sidebars

function plogger_list_categories($class) {
	// first select id and name for all collections
	$query = "SELECT * FROM ".TABLE_PREFIX."collections";
	$result = run_query($query);
	
	$output = "<ul class=\"$class\">";
	
	// loop through each collection, output child albums
	while ($row = mysql_fetch_assoc($result)) {
		// output collection name
		$collection_link = '<a href="'.generate_url("collection",$row['id']).'">'.$row['name'].'</a>';
		$output .= "<li>$collection_link</li>";
		
		// loop through child albums
		$query = "SELECT * FROM ".TABLE_PREFIX."albums WHERE parent_id = '$row[id]' ORDER BY name DESC";
		
		$output .= '<ul>';
		while ($albums = mysql_fetch_assoc($result)) {
			$album_link = '<a href="'.generate_url("albums",$albums['id']).'">'.$albums['name'].'</a>';
			$output .= "<li>$album_link</li>";
		}
		
		$output .= '</ul>';
	}
	
	$output .= '</ul>';
	
	echo $output;
}

function is_allowed_extension($ext) {
	return in_array(strtolower($ext),array("jpg","gif","png","bmp"));
}

function plogger_init() {
	global $config;
	
	$_SESSION['require_captcha'] = false;
	
	$page = isset($_GET["plog_page"]) ? intval($_GET["plog_page"]) : 1;
	$from = ($page - 1) * $config["thumb_num"];
	
	if ($from < 0) {
		$from = 0;
	}
	
	// we shouldn't set a limit for the slideshow
	if( $GLOBALS["plogger_mode"] == 'slideshow')
		$lim = 0;
	else
		$lim = $config["thumb_num"];
		
	$id = $GLOBALS["plogger_id"];
	
	if ($GLOBALS["plogger_level"] == "search")	{
		plogger_init_search(array(
			'searchterms' => $_GET['searchterms'],
			'from' => $from,
			'limit' => $lim));
	} else if ($GLOBALS["plogger_level"] == "album") {
		plogger_init_pictures(array(
			'type' => 'album',
			'value' => $id,
			'from' => $from,
			'limit' => $lim,
			'sortby' => isset($_SESSION['plogger_sortby']) ? $_SESSION['plogger_sortby'] : '',
			'sortdir' => isset($_SESSION['plogger_sortdir']) ? $_SESSION['plogger_sortdir'] : ''));
	} else if ($GLOBALS["plogger_level"] == "collection") {
		plogger_init_albums(array(
			'from' => $from,
			'limit' => $lim,
			'collection_id' => $id,
			'sortby' => !empty($config['album_sortby']) ? $config['album_sortby'] : 'id',
			'sortdir' => !empty($config['album_sortdir']) ? $config['album_sortdir'] : 'DESC'));
	} else if ($GLOBALS["plogger_level"] == "picture") {
		// first lets load the thumbnail of the picture at the correct size
		plogger_init_picture(array('id' => $id));
	} else {
		// Show all of the collections	
		plogger_init_collections(array(
			'from' => $from,
			'limit' => $lim,
			'sortby' => !empty($config['collection_sortby']) ? $config['collection_sortby'] : 'id',
			'sortdir' => !empty($config['collection_sortdir']) ? $config['collection_sortdir'] : 'DESC'));
	}
}

function plogger_init_picture($arr) {
	$id = intval($arr['id']);
	$sql = "SELECT `id`, `parent_album` FROM `".TABLE_PREFIX."pictures` WHERE `id`=".$id;
	$result = run_query($sql);
	
	$row = mysql_fetch_assoc($result);
	
	if (!$row) {
		return false;
	}
	
	// generate a list of all image id-s so proper prev/next links can be created. This should be a 
	// fast query, even for big albums.
	$image_list = array();
	
	$sql = "SELECT `id` FROM `".TABLE_PREFIX."pictures` WHERE `parent_album`=".$row["parent_album"];
	
	// determine sort ordering
	switch ($_SESSION["plogger_sortby"]){
		case 'number_of_comments':
			$sql = "SELECT
					`p`.`id`,
					COUNT(`comment`) AS `num_comments`
				FROM `".TABLE_PREFIX."pictures` AS `p`
					LEFT JOIN `".TABLE_PREFIX."comments` AS `c` ON `p`.`id`=`c`.`parent_id`
				WHERE `parent_album`=".$row["parent_album"]."
				GROUP BY `p`.`id`
				ORDER BY `num_comments` ";
			break;
		case 'caption':
			$sql .= " ORDER BY `caption` ";
			break;
		case 'date_taken':
			$sql .= " ORDER BY `EXIF_date_taken` ";
			break;
		case 'filename':
			$sql .= " ORDER BY `path` ";
			break;
		case 'date':
		default:
			$sql .= " ORDER BY `date_submitted` ";
			break;
	}
	
	switch (strtoupper($_SESSION["plogger_sortdir"])){
		case 'ASC':
			$sql .= " ASC";
			break;
		case 'DESC':
		default:
			$sql .= " DESC";
			break;
	}
	
	$result = run_query($sql);
	
	while ($image = mysql_fetch_assoc($result)) {
		$image_list[] = $image["id"];
	}
	
	$GLOBALS["image_list"] = $image_list;
	
	// first lets load the thumbnail of the picture at the correct size
	$sql = "SELECT *,
			UNIX_TIMESTAMP(`date_submitted`) AS `unix_date_submitted`,
			UNIX_TIMESTAMP(`EXIF_date_taken`) AS `unix_exif_date_taken`
		FROM `".TABLE_PREFIX."pictures`
		WHERE `id`=$id";
	$result = run_query($sql);
	
	$GLOBALS["available_pictures"] = mysql_num_rows($result);
	$GLOBALS["picture_counter"] = 0;
	$GLOBALS["picture_dbh"] = $result;
	
	// lets load up the comments for the current picture here as well
	$query = "SELECT *,
			UNIX_TIMESTAMP(`date`) AS `unix_date`
		FROM `".TABLE_PREFIX."comments` 
		WHERE `parent_id`=".intval($id)."
			AND `approved`=1";
	$result = run_query($query) or die(mysql_error());
	
	$GLOBALS["available_comments"] = mysql_num_rows($result);
	$GLOBALS["comment_counter"] = 0;
	$GLOBALS["comment_dbh"] = $result;
}

// arr['type'] id|album|collection|latest
// arr['value'] - argument to 

// arr['sortby'] - what field is used for sorting
// arr['sortdir'] - asc|desc

// arr['from'] - where to start in the result set- default to 0
// arr['limit'] - how many pictures to return

function plogger_init_pictures($arr) {
	$sql = "
		FROM `".TABLE_PREFIX."pictures` `p`
			LEFT JOIN `".TABLE_PREFIX."comments` `c` ON `p`.`id`=`c`.`parent_id`";
	
	$type = $arr['type'];
	
	// right now only single id is supported, maybe I want to specify multiple id-s as well
	$value = ($arr['value'] > 0) ? $arr['value'] : -1;
	
	if ('collection' == $type) {
		$sql .= " WHERE p.`parent_collection` = ".$value;
	} elseif ('id' == $type) {
		$sql .= " WHERE p.`id` = ".$value;
	} elseif ('album' == $type) {
		$sql .= " WHERE p.`parent_album` = ".$value;
	} elseif ('latest' == $type) {
		// add nothing, only limit takes effect
	} else {
		// so what do you want anyway? 
		return 0;
	}
	
	$from = 0;
	$limit = 20;
	
	if (isset($arr["from"]) && $arr["from"] > 0) {
		$from = $arr["from"];
	}
	
	// enforce hard-coded max limit
	if (isset($arr['limit']) && $arr['limit'] > 0 && $arr['limit'] <= 100) {
		$limit = $arr['limit'];
	}
	
	$result = run_query("SELECT COUNT(DISTINCT p.`id`) AS cnt " . $sql);
	$row = mysql_fetch_assoc($result);
	
	$GLOBALS["total_pictures"] = $row["cnt"];
	
	// grouping is needed to get comment count
	$sql .= " GROUP BY p.`id`";
	
	// query database and retreive all pictures withing selected album
	// and what about searching? well, what about it ..
	$sort_fields = array(
		'number_of_comments' => 'num_comments',
		'caption' => 'caption',
		'date_taken' => 'EXIF_date_taken',
		'filename' => 'path',
		'date' => 'date_submitted',
		'id' => 'id',
	);
	
	// this is the default
	$sortby = 'date';
	if (isset($arr['sortby']) && isset($sort_fields[$arr['sortby']])) {
		$sortby = $arr['sortby'];
	}
	
	$sortby = $sort_fields[$sortby];
	$sql .= " ORDER BY `$sortby` ";
	
	$sortdir = ' DESC';
	
	if (isset($arr['sortdir']) && (strcasecmp('asc',$arr['sortdir']) === 0)) {
		$sortdir = ' ASC';
	}
	
	$sql .= $sortdir;
	
	// again, this is needed because of the comment counting
	$sql .= ",p.`id` DESC ";
	
	$sql .= " LIMIT ".$from.",".$limit;
	
	$result = run_query("SELECT p.*,
			UNIX_TIMESTAMP(`date_submitted`) AS `unix_date_submitted`,
			UNIX_TIMESTAMP(`EXIF_date_taken`) AS `unix_exif_date_taken`,
			COUNT(`comment`) AS `num_comments` " . $sql);
	
	$GLOBALS["available_pictures"] = mysql_num_rows($result);
	$GLOBALS["picture_counter"] = 0;
	$GLOBALS["picture_dbh"] = $result;
}

// arr['searchterms'] - what to search for, space separates different serach terms
// arr['from'] - where to start in the result set, default 0
// arr['limit'] - and how many items to return, default 20
// arr['sortby'] -
// arr['sortdir'] - 

function plogger_init_search($arr) {
	$terms = explode(" ",$arr['searchterms']);
	$from = 0;
	$limit = 20;
	
	if (isset($arr['from']) && $arr['from'] > 0) {
		$from = $arr['from'];
	}
	
	// enforce hard-coded max limit
	if (isset($arr['limit']) && $arr['limit'] > 0 && $arr['limit'] <= 100) {
		$limit = $arr['limit'];
	}
	
	$query = " FROM `".TABLE_PREFIX."pictures` p LEFT JOIN `".TABLE_PREFIX."comments` c
		ON p.`id` = c.`parent_id` ";
	
	if ((count($terms) != 1) || ($terms[0] != '')){
		$query .= " WHERE ( ";
		foreach ($terms as $term) {
			$query .= "
				`path` LIKE '%".mysql_escape_string($term)."%' OR
				`description` LIKE '%".mysql_escape_string($term)."%' OR
				`comment` LIKE '%".mysql_escape_string($term)."%' OR
				`caption` LIKE '%".mysql_escape_string($term)."%' OR ";
		}
		
		$query = substr($query, 0, strlen($query) - 3) .") ";
	} else {
		// no search terms? no results either
		$query .= " WHERE 1 = 0";
	}
	
	$sort_fields = array('date_submitted','id');
	$sortby = 'date_submitted';
	
	if (isset($arr['sortby']) && in_array($arr['sortby'],$sort_fields)) {
		$sortby = $arr['sortby'];
	}
	
	$sortdir = ' DESC';
	
	if (isset($arr['sortdir']) && 'asc' == $arr['sortdir']) {
		$sortdir = ' ASC';
	}
	
	$result = run_query("SELECT COUNT(DISTINCT p.`id`) AS cnt " . $query);
	$row = mysql_fetch_assoc($result);
	
	$GLOBALS["total_pictures"] = $row["cnt"];
	// and I need sort order here as well
	// from and limit too
	$result = run_query("SELECT `caption`,`path`,p.`id`,c.`comment`,
				UNIX_TIMESTAMP(`date_submitted`) AS `unix_date_submitted` ".$query .
				" GROUP BY p.`id` ORDER BY `$sortby` $sortdir LIMIT $from,$limit");
	
	$GLOBALS["available_pictures"] = mysql_num_rows($result);
	$GLOBALS["picture_counter"] = 0;
	$GLOBALS["picture_dbh"] = $result;
}

function plogger_init_collections($arr) {
	$sql = "SELECT COUNT(DISTINCT `parent_collection`) AS `num_items`
				FROM `".TABLE_PREFIX."pictures`";
	$result = run_query($sql);
	$num_items = mysql_result($result, 'num_items');
	$GLOBALS["total_pictures"] = $num_items;
	
	// create a list of all non-empty collections. Could be done with subqueries, but
	// MySQL 4.0 does not support those
	
	// -1 is just for the case there are no collections at all
	$image_count = array(-1 => -1);
	$album_count = array();
	
	// 1. create a list of all albums with at least one photo
	$sql = "SELECT parent_collection,COUNT(*) AS imagecount
				FROM `".TABLE_PREFIX."pictures` GROUP BY parent_collection";
	$result = run_query($sql);
	while($row = mysql_fetch_assoc($result)) {
		$image_count[$row["parent_collection"]] = $row["imagecount"];
	}
	
	$imlist = join(",",array_keys($image_count));
	
	$cond = '';
	
	if (empty($arr['all_collections'])) {
		$cond = " WHERE parent_id IN ($imlist) ";
	}
	
	$sql = "SELECT parent_id,COUNT(*) AS albumcount
				FROM `".TABLE_PREFIX."albums`
				$cond
				GROUP BY parent_id";
	
	$result = run_query($sql);
	while($row = mysql_fetch_assoc($result)) {
		$album_count[$row["parent_id"]] = $row["albumcount"];
	}
	
	$GLOBALS["album_count"] = $album_count;
	
	// I need to determine correct arguments for LIMIT from the given page number
	$from = $arr['from'];
	$lim = $arr['limit'];
	
	$cond = "";
	
	// by default only collections with pictures are returned
	// override that with passing all_collections as an argument to 
	// this function
	if (empty($arr['all_collections'])) {
		$cond = " WHERE id IN ($imlist) ";
	}
	
	$ordering = " ORDER BY $arr[sortby] $arr[sortdir] ";
	
	$sql = "SELECT * FROM `".TABLE_PREFIX."collections`
				$cond $ordering LIMIT $from,$lim";
	$result = run_query($sql);
	
	$GLOBALS["available_collections"] = mysql_num_rows($result);
	$GLOBALS["collection_counter"] = 0;
	$GLOBALS["collection_dbh"] = $result;
}

function plogger_init_albums($arr) {
	$collection_id = intval($arr['collection_id']);
	$sql = "SELECT COUNT(DISTINCT `parent_album`) AS `num_items`
				FROM `".TABLE_PREFIX."pictures`
				WHERE `parent_collection` = '" . $collection_id . "'";

	$result = run_query($sql);
	$num_items = mysql_result($result, 'num_items');
	
	$GLOBALS["total_pictures"] = $num_items;	

	// create a list of all non-empty albums. Could be done with subqueries, but
	// MySQL 4.0 does not support those

	// -1 is just for the case there are no albums at all. Shouldn't happen if user
	// follows links, but let's deal with it anyway
	$image_count = array(-1 => -1);
	// 1. create a list of all albums with at least one photo
	$sql = "SELECT parent_album,COUNT(*) AS imagecount FROM `".TABLE_PREFIX."pictures` GROUP BY parent_album";
	$result = run_query($sql);
	while($row = mysql_fetch_assoc($result)) {
		$image_count[$row["parent_album"]] = $row["imagecount"];
	}

	$imlist = join(",",array_keys($image_count));

	$from = intval($arr['from']);
	$lim = intval($arr['limit']);

	$cond = '';
	if (empty($arr['all_albums'])) {
		$cond = " AND id IN ($imlist) ";
	}

	$order = " ORDER BY $arr[sortby] $arr[sortdir] ";

	$sql = "SELECT * FROM `".TABLE_PREFIX."albums`
			WHERE `parent_id` = '$collection_id' $cond $order LIMIT $from,$lim";

	$result = run_query($sql);
	$GLOBALS["available_albums"] = mysql_num_rows($result);
	$GLOBALS["album_counter"] = 0;
	$GLOBALS["album_dbh"] = $result;

}

function plogger_get_header() {
	global $config;
	if (file_exists(THEME_DIR . '/header.php')) {
		include(THEME_DIR . '/header.php');
	} else {
		include($config['basedir'] . '/themes/default/header.php');
	}
}

function plogger_get_footer() {
	global $config;
	if (file_exists(THEME_DIR . '/footer.php')) {
		include(THEME_DIR . '/footer.php');
	} else {
		include($config['basedir'] . '/themes/default/footer.php');
	}
}

function plogger_download_selected_button() {
	global $config;
	
	if ($GLOBALS['plogger_level'] != "picture" && $GLOBALS['plogger_mode'] != "slideshow" && $config["allow_dl"]) {
			return '<input id="download_selected_button" class="submit" type="submit" name="download_selected" value="Download Selected" />';
	}
}

function plogger_download_selected_form_start() {
	global $config;
	
	if ($GLOBALS['plogger_level'] != "picture" && $GLOBALS['plogger_mode'] != "slideshow" && $config["allow_dl"]) {
			return '<form action="'.$config['gallery_url'].'plog-download.php" method="post"><input type="hidden" name="dl_type" value="'.$GLOBALS['plogger_level'].'" />';
	}

}

function plogger_download_selected_form_end() {
	global $config;
	
	if ($GLOBALS['plogger_level'] != "picture" && $GLOBALS['plogger_mode'] != "slideshow" && $config["allow_dl"]) {
			return '</form>';
	}

}

function plogger_print_button() {
	global $config;
	$id = $GLOBALS["plogger_id"];
	
	if ($GLOBALS['plogger_level'] == "picture" && $config["allow_print"]) {
  		return '<a class="print" href="' . $config["gallery_url"] . 'plog-print.php?id='.$id.'">Print Image</a>';
	}
}

function plogger_link_back () {
	return	'<div id="link-back"><a href="http://www.plogger.org/">Powered by Plogger!</a></div>';
}

function plogger_rss_link() {
	global $config;
	
	if ($config["use_mod_rewrite"]) {
		global $path;
		if (isset($path)) 
			$rss_link .= "http://".$_SERVER["SERVER_NAME"]."/".SmartStripSlashes(substr($path,1))."/feed/";
		else
			$rss_link .= $config['gallery_url']."feed/";
				
	} 
	else {
		$rss_link .= $config["gallery_url"] . "plog-rss.php?level=".$GLOBALS['plogger_level']."&amp;id=".$GLOBALS['plogger_id'];
	}

	if ($GLOBALS['plogger_level'] == "search") { // append the search terms
		$separator = $config["use_mod_rewrite"] ? "?" : "&amp;";
		$rss_link .= $separator . "searchterms=".htmlspecialchars($_GET["searchterms"]);
	}
	
	return $rss_link;
}

function plogger_rss_feed_button () {
	global $config;
	
	if ($GLOBALS['plogger_mode'] != "slideshow" && $GLOBALS['plogger_level'] != "picture") {
		$rss_link = "";
		// change the tooltip message to reflect the nature of the RSS aggregate link.
		if ($GLOBALS['plogger_level'] != "") 
				$rss_tooltip = "RSS 2.0 subscribe to this " .$GLOBALS["plogger_level"];
		else
				$rss_tooltip = "RSS 2.0 subscribe to all images";
	
	
		$rss_link = plogger_rss_link();
	
		$rss_tag = '<a href="'.$rss_link.'"><img id="rss-image" src="' . $config["gallery_url"] . 'graphics/rss.gif" title="'.					$rss_tooltip.'" alt="RSS 2.0 Feed"/></a>';
		
		return $rss_tag;
	}

}

function plogger_slideshow_link() {
	
	global $config;
	$id = $GLOBALS["plogger_id"];
	$ss_tag = "";
	if ($GLOBALS['plogger_mode'] != "slideshow") {
		if ($GLOBALS['plogger_level'] == "album") {
			$ss_url = generate_url('album',$GLOBALS['plogger_id'],array('mode' => 'slideshow'));
			$ss_tag = "<a href=\"$ss_url\">View as Slideshow</a>";
		}

		if ($GLOBALS['plogger_level'] == "search") {
			$ss_url = $config['baseurl'] . '?level=search&amp;searchterms=' . htmlspecialchars($_GET['searchterms']);
			$ss_url .= "&amp;mode=slideshow";
			$ss_tag = "<a href=\"$ss_url\">View as Slideshow</a>";
		}
		
	}
	return $ss_tag;
}

function plogger_sort_control() {

	if ($GLOBALS['plogger_mode'] != 'slideshow')
		return generate_sortby($GLOBALS['plogger_level'],$GLOBALS['plogger_id']).generate_sortdir($GLOBALS['plogger_level'],$GLOBALS['plogger_id']);
	
}

function plogger_pagination_control() {
	global $config;
	
	if ($GLOBALS['plogger_mode'] != 'slideshow') {
		$page = isset($_GET["plog_page"]) ? intval($_GET["plog_page"]) : 1;
		
		if ($GLOBALS['plogger_level'] == "search") {
			$searchterms = urlencode($_GET["searchterms"]);
			$p_url = $config['baseurl']."?level=search&amp;searchterms=$searchterms&amp;id=".$GLOBALS["plogger_id"];
		} 
		else {
			if ($GLOBALS['plogger_level']) {
				$p_url = generate_url($GLOBALS['plogger_level'], $GLOBALS['plogger_id']);
				if ($config["use_mod_rewrite"]) {
					$p_url .= "/";
				}
			}
			else {
				$p_url = $config["baseurl"];
			}
		}
		
		switch($GLOBALS['plogger_level']) {
			case 'search':
				$num_items = $GLOBALS["total_pictures"];
				break;

			case 'album':
				$num_items = plogger_album_picture_count();
				break;

			case 'collection':
				$num_items = plogger_collection_album_count();
				break;

			default:
				$num_items = plogger_count_collections();
				break;
		}
		
		return generate_pagination($p_url, $page, $num_items, $config["thumb_num"]);
	}

}

/*** The following functions can only be used inside the Picture loop ***/
function plogger_has_pictures() {
	return $GLOBALS["picture_counter"] < $GLOBALS["available_pictures"];
}

function plogger_comments_on() {
	global $config;
	return $config["allow_comments"];
}

function plogger_picture_allows_comments() {
		
	$picture = $GLOBALS["current_picture"];
	$id = $picture["id"];
 	return $picture["allow_comments"];
}

function plogger_load_picture() {
	$rv = mysql_fetch_assoc($GLOBALS["picture_dbh"]);
	$GLOBALS["picture_counter"]++;
	$GLOBALS["current_picture"] = $rv;
	return $rv;
}

function plogger_picture_has_comments() {
	return $GLOBALS["comment_counter"] < $GLOBALS["available_comments"];
}

function plogger_load_comment() {
	$rv = mysql_fetch_assoc($GLOBALS["comment_dbh"]);
	$GLOBALS["comment_counter"]++;
	$GLOBALS["current_comment"] = $rv;
	return $rv;
}

function plogger_get_comment_date($format = false) {
	global $config;
	$comment = $GLOBALS["current_comment"];
	if (empty($format)) {
		$format = $config["date_format"];
	}
	return date($format,$comment["unix_date"]);
}

function plogger_get_comment_id() {
	$comment = $GLOBALS["current_comment"];
	return $comment["id"];

}

function plogger_get_comment_email() {
	$comment = $GLOBALS["current_comment"];
	return $comment["email"];

}

function plogger_get_comment_url() {
	$comment = $GLOBALS["current_comment"];
	return htmlspecialchars($comment["url"]);
}

function plogger_get_comment_author() {
	$comment = $GLOBALS["current_comment"];
	return htmlspecialchars(SmartStripSlashes($comment["author"]));
}

function plogger_get_comment_text() {
	$comment = $GLOBALS["current_comment"];
	return htmlspecialchars(SmartStripSlashes($comment["comment"]));
}

function plogger_comment_post_error() {
	if (isset($_SESSION['comment_post_error'])) {
		unset($_SESSION['comment_post_error']);
		return 1;
	}
	else
		return 0;
}

function plogger_comment_moderated() {
	if (isset($_SESSION['comment_moderated'])) {
		unset($_SESSION['comment_moderated']);
		return 1;
	}
	else
		return 0;
}


function plogger_get_picture_url() {
	$row = $GLOBALS["current_picture"];
	return generate_url("picture",$row["id"]);
}

function plogger_get_picture_id() {
	$row = $GLOBALS["current_picture"];
	return $row["id"];
}

function plogger_get_picture_thumb($type = THUMB_SMALL) {
	$pic = $GLOBALS["current_picture"];
	return generate_thumb($pic['path'], $pic['id'], $type);
}

function plogger_get_picture_caption() {
	if (!empty($GLOBALS["current_picture"]["caption"]))
		return SmartStripSlashes($GLOBALS["current_picture"]["caption"]);
	else
		return "&nbsp;";
}

function plogger_get_thumbnail_info() {
	global $thumbnail_config;
	global $config;
	
	$thumb_config = $thumbnail_config[THUMB_SMALL];
	
	$base_filename = sanitize_filename(basename($GLOBALS["current_picture"]["path"]));
	$prefix = $thumb_config['filename_prefix'] . $GLOBALS["current_picture"]["id"] . "-";
	
	$thumbpath = $config['basedir'] . 'thumbs/'.$prefix.$base_filename;
	$image_info = getimagesize($thumbpath);
	
	return $image_info;
}

function plogger_get_source_picture_path() {
	global $config;
	return $config['basedir'].'images/'.SmartStripSlashes($GLOBALS["current_picture"]["path"]);
}

function plogger_get_picture_description() {
	return htmlspecialchars(SmartStripSlashes($GLOBALS["current_picture"]["description"]));
}

function plogger_picture_comment_count() {
	$row = $GLOBALS["current_picture"];
	$comment_query = "SELECT COUNT(*) AS `num_comments` FROM `".TABLE_PREFIX."comments` 
	WHERE approved = 1 AND `parent_id`='".$row["id"]."'";
	
	$comment_result = run_query($comment_query);
	$num_comments = mysql_result($comment_result, 0, 'num_comments');
	return $num_comments;
}

function plogger_get_picture_date($format = '',$submitted = 0) {
	global $config;
	$row = $GLOBALS['current_picture'];
	if ($submitted) {
		$date_taken = $row['unix_date_submitted'];
	}
	else {
		$date_taken = !empty($row['unix_exif_date_taken']) ? $row['unix_exif_date_taken'] : $row['unix_date_submitted'];
	}
	if (!$format) {
		$format = $config['date_format'];
	}
	return date($format,$date_taken);
}

function plogger_get_source_picture_url() {
	global $config;
	return (!empty($config['allow_fullpic'])) ? $config["baseurl"].'images/'.SmartStripSlashes($GLOBALS["current_picture"]["path"]) : "#";
}

/**
 * @author derek@plogger.org
 * @return string html list of thumbnails
 */
function plogger_get_thumbnail_nav() {
	global $config;
	if(empty($config["enable_thumb_nav"])) return ''; // return if thumbnail nav turned off
	$image_list = $GLOBALS["image_list"];
	$array_length = count($image_list); // store array length
	$curr = $GLOBALS["current_picture"];
	$pos_array = array_keys($image_list,$curr["id"]);
	$curr_pos = $pos_array[0];
	$range = (isset($config["thumb_nav_range"])) ? $config["thumb_nav_range"] : 0;
	if($range == 0) { // if length is 0, use all thumbs
		// get_picture_by_id modified to take arrays, so pass the whole array
		$thumb_pic_array = get_picture_by_id($image_list);
	} else { // else, add a thumb each side of current for each value of $config['thumb_nav_range']
		$thumb_nav_array = array($curr['id']);
		for($i=1;$i<$range+1;$i++) {		
			// use unshift() to add to the beginning, push() to add to the end
			// check that we haven't run out of images below: that (current image - offset) >= 0
			if($curr_pos - $i >= 0) {
				unset($newpic); // php has problems with reassigning via iteration
				$newpic = $image_list[$curr_pos-$i];
				if(!empty($newpic)) array_unshift($thumb_nav_array, $newpic);
			} else { // grab from the end of the array
				unset($newpic); // php has problems with reassigning via iteration
				$newpic = $image_list[$array_length+($curr_pos-$i)]; // adding a negative value, don't fret
				if(!empty($newpic)) array_unshift($thumb_nav_array, $newpic);
			}
			// check that we haven't run out of images above: (current image + offset) <= (total images)
			if($array_length-1 >= ($curr_pos+$i)) {
				unset($newpic); // php has problems with reassigning via iteration
				$newpic = $image_list[$curr_pos+$i];
				if(!empty($newpic)) array_push($thumb_nav_array, $newpic);
			} else { // grab from the beginning of the array
				unset($newpic); // php has problems with reassigning via iteration
				$newpic = $image_list[($curr_pos + $i) - ($array_length)];
				if(!empty($newpic)) array_push($thumb_nav_array, $newpic);
			}
		}
		$thumb_pic_array = array();
		foreach($thumb_nav_array as $thumb_nav_value) {
			
			array_push($thumb_pic_array,get_picture_by_id($thumb_nav_value));
		}
	}
	return plogger_format_thumb_nav($thumb_pic_array);
}

function plogger_format_thumb_nav($thumb_nav_array) {
	$thumb_nav_out = "\t<ul id='thumb-nav'>\n";
	foreach($thumb_nav_array as $current_thumb_nav) {
		unset($title);unset($img_path);unset($class);unset($link); // php has problems with reassigning via iteration
		$title = (!empty($current_thumb_nav["caption"])) ? $current_thumb_nav["caption"] : "";
		$img_path = generate_thumb($current_thumb_nav["path"], $thumbnail_config[THUMB_NAV]['prefix'], THUMB_NAV);
		$class = ($current_thumb_nav["id"] == $GLOBALS["current_picture"]["id"]) ? "current" : "";
		$link = generate_url("picture",$current_thumb_nav["id"]);
		$thumb_nav_out .= plogger_thumb_nav_item($link, $title, $img_path, $class);
	}
	$thumb_nav_out .= "\t</ul>\n";
	return $thumb_nav_out;
}

function plogger_thumb_nav_item($link, $title, $img_path, $class = '') {
	return "\t\t<li class='$class'><a href='$link' title='$title'><img src='$img_path' /></a></li>\n";
}

function plogger_get_next_picture_url() {
	$image_list = $GLOBALS["image_list"];
	$row = $GLOBALS["current_picture"];
	$current_picture = array_search($row['id'],$image_list);
	$next_link = '';
	if ($current_picture < sizeof($image_list)-1)
	{
		$next_link = generate_url("picture",$image_list[$current_picture+1]);
	}
	return $next_link;
}

function plogger_get_prev_picture_url() {
	$image_list = $GLOBALS["image_list"];
	$row = $GLOBALS["current_picture"];
	$current_picture = array_search($row['id'],$image_list);
	$prev_link = '';
	if ($current_picture > 0) {
		$prev_link = generate_url("picture",$image_list[$current_picture-1]);
	}
	return $prev_link;
}
/*** End of Picture loop functions ***/


/*** The following functions can only be used inside the Collections loop ***/
function plogger_load_collection() {
	$rv = mysql_fetch_assoc($GLOBALS["collection_dbh"]);
	$GLOBALS["collection_counter"]++;
	$GLOBALS["current_collection"] = $rv;
	return $rv;
}

function plogger_has_collections() {
	return $GLOBALS["collection_counter"] < $GLOBALS["available_collections"];
}

function plogger_get_collection_url() {
	$row = $GLOBALS["current_collection"];
	return generate_url("collection",$row["id"]);
}

function plogger_get_collection_thumb() {
	$rv = $GLOBALS["current_collection"];
	// figure out the thumbnail as well
	$thumb_query = "SELECT * FROM `".TABLE_PREFIX."pictures` WHERE ";
	if ($rv["thumbnail_id"] > 0)
		$thumb_query .= " `id`=".$rv["thumbnail_id"];
	else
		$thumb_query .= " `parent_collection`='".$rv["id"]."' ORDER BY `id` DESC LIMIT 1";
	
	$thumb_result = run_query($thumb_query);
	$thumb_data = mysql_fetch_assoc($thumb_result);
	if ($thumb_data) {
		$rv["thumbnail_id"] = $thumb_data["id"];
		$rv["thumbnail_path"] = $thumb_data["path"];
	}
	return generate_thumb($rv['thumbnail_path'], $rv['thumbnail_id'], THUMB_SMALL);
}


function plogger_collection_album_count() {
	if (isset($GLOBALS["album_count"][$GLOBALS["current_collection"]["id"]])) {
		return $GLOBALS["album_count"][$GLOBALS["current_collection"]["id"]];
	} else {
		return 0;
	}
}

function plogger_get_collection_description() {
	return htmlspecialchars(SmartStripSlashes($GLOBALS["current_collection"]["description"]));

}

function plogger_get_collection_name() {
	return htmlspecialchars(SmartStripSlashes($GLOBALS["current_collection"]["name"]));
}

function plogger_get_collection_id() {
	return $GLOBALS["current_collection"]["id"];
}

function plogger_count_collections() {
	
	$numquery = "SELECT COUNT(*) AS `num_collections` FROM `".TABLE_PREFIX."collections`";
		
	$numresult = run_query($numquery);
	$num_albums = mysql_result($numresult, 'num_collections');
	return $num_albums;
}

/*** End of Collection loop functions ***/

/*** The following functions can only be used inside the Albums loop ***/
function plogger_load_album() {
	$rv = mysql_fetch_assoc($GLOBALS["album_dbh"]);
	$GLOBALS["album_counter"]++;
	$GLOBALS["current_album"] = $rv;
	return $rv;
}

function plogger_has_albums() {
	return $GLOBALS["album_counter"] < $GLOBALS["available_albums"];
}

function plogger_get_album_url() {
	$row = $GLOBALS["current_album"];
	return generate_url("album",$row["id"]);
}

function plogger_get_album_thumb() {
	$rv = $GLOBALS["current_album"];
	// figure out the thumbnail as well
	$thumb_query = "SELECT * FROM `".TABLE_PREFIX."pictures` WHERE ";
	if ($rv["thumbnail_id"] > 0)
		$thumb_query .= " `id`=".$rv["thumbnail_id"];
	else
		$thumb_query .= " `parent_album`='".$rv["id"]."' ORDER BY `date_submitted` DESC LIMIT 1";
	
	$thumb_result = run_query($thumb_query);
	$thumb_data = mysql_fetch_assoc($thumb_result);
	if ($thumb_data) {
		$rv["thumbnail_id"] = $thumb_data["id"];
		$rv["thumbnail_path"] = $thumb_data["path"];
	}
	return generate_thumb($rv['thumbnail_path'], $rv['thumbnail_id'], THUMB_SMALL);
}

function plogger_album_picture_count() {
	$row = $GLOBALS["current_album"];
	// XXX: surely this can be optimized?
	$numquery = "SELECT COUNT(*) AS `num_pictures` FROM `".TABLE_PREFIX."pictures` WHERE `parent_album`='".$row["id"]."'";
	$numresult = run_query($numquery);
	return mysql_result($numresult, 'num_pictures');
}

function plogger_get_album_description() {
	return htmlspecialchars(SmartStripSlashes($GLOBALS["current_album"]["description"]));

}

function plogger_get_album_name() {
	return htmlspecialchars(SmartStripSlashes($GLOBALS["current_album"]["name"]));
}

function plogger_get_album_id() {
	return $GLOBALS["current_album"]["id"];
}

function plogger_get_detail_link() {
		if (!$_SESSION["plogger_details"]){
			return '<a accesskey="d" href="javascript:void(0);" onclick="show_details();">Show details</a>';
		}
		else{
			return '<a accesskey="d" href="javascript:void(0);" onclick="hide_details();">Hide details</a>';
		}
}

function plogger_download_checkbox($id, $label = '') {
	global $config;
	if ($config["allow_dl"])
		return '<input type="checkbox" name="checked[]" value="'.$id.'" /> '. $label;
	else
		return '';
}

function plogger_get_next_picture_link() {
	$next_url = plogger_get_next_picture_url();

	if ($next_url)
		$next_link = '<a accesskey="." href="'.$next_url.'">Next &raquo;</a>';
	else
		$next_link = '';
		
	return $next_link;
}

function plogger_get_prev_picture_link() {
	$prev_url = plogger_get_prev_picture_url();
	
	if ($prev_url) 
		$prev_link = '<a accesskey="," href="'.$prev_url.'">&laquo; Previous</a>';
	else
		$prev_link = '';
		
	return $prev_link;	
		

}
/*** End of Album loop functions ***/
function get_comments($picture_id){
	$query = "SELECT *
		FROM `".TABLE_PREFIX."comments`
		WHERE `parent_id`=".intval($picture_id)."
		ORDER BY `date` DESC";
	$result = run_query($query);
	
	$comments = array();
	
	while ($row = mysql_fetch_assoc($result)){
		$comments[$row["id"]] = $row;
	}
	
	return $comments;
}

?>
