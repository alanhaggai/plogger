<?php
require("plog-admin.php");
// load configuration variables from database
require_once("../plog-load_config.php"); 					
require_once("plog-admin-functions.php");

global $inHead;

$inHead = '<script src="js/ajax_editing.js" type="text/javascript"></script>';

function generate_pagination_view_menu() {
	
	$java = 'document.location.href = \''.$_SERVER["PHP_SELF"].'?level='.$_REQUEST["level"].
	'&amp;id='.$_REQUEST["id"].'&amp;entries_per_page=\'+this.options[this.selectedIndex].value';
	
	$possible_values = array("5"=>5, "10"=>10, "20"=>20, "50"=>50);
	$output= '<label accesskey="e" for="entries_on_page"><em>E</em>ntries per page</label><select onchange="'.$java.'" name="entries_per_page">';
	
	foreach ($possible_values as $key => $value)
		if ($_SESSION['entries_per_page'] == $key)
			$output .= "<option value=\"$value\" selected>$key</option>";
		else
			$output .= "<option value=\"$value\">$key</option>";
			
	$output.= '</select>';
	
	return $output;				

}

function generate_move_menu($level) {
  	
	if ($level == "albums") $parent = "collections";
	if ($level == "pictures") $parent = "albums";
	$output .=  '<input class="submit" type="submit" name="move_checked" value="' . plog_tr("Move Checked To") . '"/>';
  	
	if ($level == "pictures") {
		$albums = get_albums();
		$output .= generate_albums_menu($albums);
	} else {
		$output .=  '<select name="group_id">';
		$collections = get_collections();
		foreach($collections as $collection) {
			$output .= '<option value="'.$collection["id"].'">'.SmartStripSlashes($collection["name"]);
			$output .=  '</option>';
		}
		$output .=  '</select>';
	}
			
	return $output;
}

function generate_albums_menu($albums) {
	$output .=  '<select name="group_id">';
	foreach($albums as $album_id => $album) {
		if ($_REQUEST["albums_menu"] == $album_id || $_REQUEST["new_album_name"] == $album['album_name']) 
			$selected = " selected"; else $selected = "";
						
		$output .= "<option value=\"".$album_id."\"$selected>".SmartStripSlashes($album['collection_name'])." : ".SmartStripSlashes($album['album_name'])."" ;
         $output .= "</option>";
    }
	
	$output .=  "</select>";
	return $output;
}

function generate_breadcrumb_admin($level, $id = 0){
	switch ($level){
		case 'collections':
		  $breadcrumbs = '<strong>' . plog_tr('Collections') . '</strong>';
			
			break;
		case 'albums':
			$collection = get_collection_by_id($id);
			$collection_name = SmartStripSlashes($collection["name"]);
  		   $breadcrumbs = '<a href="'.$_SERVER["PHP_SELF"].'">' . plog_tr('Collections') . '</a> &raquo; ' . "<strong>$collection_name</strong>";
			
			break;
		case 'pictures':
			$album = get_album_by_id($id);
			$album_link = SmartStripSlashes($album["name"]);
			$collection_link = '<a href="'.$_SERVER["PHP_SELF"].'?level=albums&amp;id='.$album["parent_id"].'">'.SmartStripSlashes($album["collection_name"]).'</a>';
			$breadcrumbs = '<a href="'.$_SERVER["PHP_SELF"].'">' . plog_tr('Collections') . '</a> &raquo; ' . $collection_link . ' &raquo; ' . '<strong>'.						$album_link.'</strong>';
			break;

		case 'comments':
			
			$query = "SELECT * FROM `".TABLE_PREFIX."pictures` WHERE `id`='".$id."'";
			$result = run_query($query);
			$row = mysql_fetch_assoc($result);
			
			$picture_link = '<strong>'.SmartStripSlashes(basename($row["path"])).'</strong>';
			$album_id = $row["parent_album"];
			$collection_id = $row["parent_collection"];
			
			$query = "SELECT * FROM `".TABLE_PREFIX."albums` WHERE `id`='".$album_id."'";
			$result = run_query($query);
			$row = mysql_fetch_assoc($result);
			
			$album_link = '<a href="'.$_SERVER["PHP_SELF"].'?level=pictures&amp;id='.$album_id.'">'.SmartStripSlashes($row["name"]).'</a>';
			
			$query = "SELECT * FROM `".TABLE_PREFIX."collections` WHERE `id`='".$collection_id."'";
			$result = run_query($query);
			$row = mysql_fetch_assoc($result);
			
			$collection_link = '<a href="'.$_SERVER["PHP_SELF"].'?level=albums&amp;id='.$collection_id.'">'.SmartStripSlashes($row["name"]).'</a>';
			
			$breadcrumbs = '<a href="'.$_SERVER["PHP_SELF"].'">' . plog_tr('Collections') .' </a> &raquo; ' . $collection_link . ' &raquo; '
			.$album_link. ' &raquo; '.$picture_link . ' &raquo;' . " " . plog_tr('Comments');
			
			break;
		default:
			$breadcrumbs = '<strong>' . plog_tr('Collections') . '</strong>';
	}
	
	return '<div id="breadcrumb_links">'.$breadcrumbs.'</div>';
}

$id = isset($_GET['id']) ? intval($_REQUEST['id']) : 0;


if (!isset($_REQUEST["level"]) or $_REQUEST["level"] == '') $level = "collections";	 
else $level = $_REQUEST['level'];

$output = '<h1>' . plog_tr('Manage Content') . '</h1>';

/* if ($level == "collections") { // display some high level statistics on the main page.
	$output .= '<p class="highlight">You have a total of <strong>'. count_pictures().'</strong> images within <strong>'.count_albums() .'</strong> album(s).  Users have posted <strong>'. count_comments().'</strong> comment(s) to your gallery</p>'; } */

global $config;

if (empty($_REQUEST['level'])) {
	$_REQUEST['level'] = '';
};

// here we will determine if we need to perform a move or delete action.
if (isset($_REQUEST["action"])) {
	$num_items = 0;

	$action_result = array();

	if (isset($_REQUEST['delete_checked']) ) {
		// perform the delete function on the selected items
		
		if (isset($_REQUEST["selected"])) {
			foreach($_REQUEST["selected"] as $del_id) {
				// lets build the query string
				if ($level == "pictures") {
					$rv = delete_picture($del_id);
				}
				if ($level == "collections") {
					$rv = delete_collection($del_id);
				}
				if ($level == "albums") {
					$rv = delete_album($del_id);
				}

				if (isset($rv['errors'])) {
					$output .= '<p class="errors">' . $rv['errors'] . '</p>';
				} else {
					$num_items++;
				};
			}
			
			if ($num_items > 0){
				$output .= "<p class=\"actions\">";
				if ($num_items > 1) {
					$output .= sprintf(plog_tr('You have deleted %d entries successfully'),$num_items);
				} else {
					$output .= sprintf(plog_tr('You have deleted %d entry successfully'),$num_items);
				};
				$output .= "</p>";

			}
		}
		else{
			$output .= "<p class=\"errors\">" . plog_tr('Nothing selected to delete!') . "</p>";
		}
	}
	else if (isset($_REQUEST['move_checked'])) {
		if ($level == "albums") $parent = "parent_id";
		if ($level == "pictures") $parent = "parent_album";
		
		// perform the move function on the selected items
		$pid = $_REQUEST["group_id"];
		
		if (isset($_REQUEST["selected"])) {
			foreach($_REQUEST["selected"] as $mov_id) {
				
				// if we are using pictures we need to update the parent_collection as well
				if ($level == "pictures") {
					// lets build the query string
					$result = move_picture($mov_id,$pid);
					if (empty($result['errors'])) {
						$num_items++;
					} else {
						$output .= '<p class="errors">' . $result['errors'] . '</p>';
					};
				}
				else if ($level == "albums") {
					// if we are moving entire albums then we need to rename the folder
					// $pid is our target collection id, $mov_id is our source album
					
					$result = move_album($mov_id,$pid);
					if (empty($result['errors'])) {
						$num_items++;
					} else {
						$output .= '<p class="errors">' . $result['errors'] . '</p>';
					};
				}
					  
			}
			
			$output .= "<p class=\"actions\">" . sprintf(plog_tr('You have moved %d entry(s) successfully.'),$num_items) . "</p>";
		}
		else{
			$output .= "<p class=\"errors\">" . plog_tr('Nothing selected to move!') . "</p>";
		}
	}
	else if (!empty($_GET["action"])){
		if($_GET["action"] == "edit-picture") {
			$level = 'picture';
			// show the edit form
			$photo = get_picture_by_id($_REQUEST["id"]);
			if ($photo['allow_comments'] == 1) $state = "checked"; else $state = "";
			
			$output .= '<form class="edit" action="'.$_SERVER["PHP_SELF"].'?level=pictures&amp;id='.$photo["parent_album"];
			
			if (isset($_GET["entries_per_page"])) $output .= '&amp;entries_per_page=' . intval($_GET["entries_per_page"]);
			if (isset($_GET["plog_page"])) $output .= '&amp;plog_page=' . intval($_GET["plog_page"]);
			
			$output .= '" method="post">';
			
		  $thumbpath = generate_thumb(SmartStripSlashes($photo['path']), $photo['id'],THUMB_SMALL);
		
			$output .= "<div style='float:right'><img src='$thumbpath'/></div>";
		
			$output .= '<label accesskey="c" for="caption"><em>C</em>aption:</label><input size="80" name="caption" id="caption" value="'.SmartStripSlashes($photo['caption']).'"><br />
							<label>Description:</label><br />
							<textarea name="description" id="description" cols="60" rows="5">'.SmartStripSlashes($photo['description']).'</textarea><br />
						
					    <label for="allow_comments" accesskey="w">Allo<em>w</em> Comments?<label><br /><input type="checkbox" id="allow_comments" name="allow_comments" value="1"'." $state>";
						
			$output .= '<input type="hidden" name="pid" value="'.$photo['id'].'"><input type="hidden" 
						name="action" value="update-picture"><button class="submit" type="submit">Update</button>';
			
			$output .= '</form>';
			
		}
		else if ($_GET["action"] == "edit-album") {
			// show the edit form
			$output .= plog_edit_album_form($_REQUEST["id"]);
		}
		else if ($_GET["action"] == "edit-collection") {
			$output .= plog_edit_collection_form($_GET["id"]);
		}
		else if ($_GET["action"] == "edit-comment") {
			// show the edit form
			$output .= edit_comment_form($_GET["pid"]);
		}
	}
	else if (!empty($_POST["action"])){
		if ($_POST['action'] == 'update-picture') {
			$action_result = update_picture($_POST['pid'],$_POST['caption'],$_POST['allow_comments'],$_POST['description']);
		}
		else if ($_POST['action'] == 'update-album') {
			$action_result = update_album($_POST['pid'],$_POST['name'],$_POST['description'],$_POST['thumbnail_id']);
		}
		else if ($_POST["action"] == "update-collection") {
			$action_result = update_collection($_POST["pid"],$_POST["name"],$_POST["description"],$_POST["thumbnail_id"]);
		}
		else if ($_POST["action"] == "update-comment") {
			$action_result = update_comment($_POST["pid"],$_POST["author"],$_POST["email"],$_POST["url"],$_POST["comment"]);
		}
		else if ($_POST["action"] == "add-collection") {
			$action_result = add_collection($_POST["name"],$_POST["description"]);
		}
		else if ($_POST["action"] == "add-album") {
			$action_result = add_album($_POST["name"],$_POST["description"],$_POST["parent_collection"]);
		}
	}
	
	if (!empty($action_result['errors'])) {
		$output .= '<p class="errors">' . $action_result['errors'] . '</p>';
	} elseif (!empty($action_result['output'])) {
		$output .= '<p class="actions">' . $action_result['output'] . '</p>';
	};
	
	if (($_REQUEST["action"] == '1') && isset($_GET["action"])){
		unset($_GET["action"]);
	}
}

if (!isset($_GET["action"])){
	// here we will generate a "add collection/album" header
	if ($level == "collections") {
		$output .= plog_add_collection_form();
	}
	else if ($level == "albums") {
		$output .= plog_add_album_form($id);
	}
	
	// lets iterate through all the content and build a table
	// set the default level if nothing is specified
	
	// handle pagination
	// lets determine the limit filter based on current page and number of results per page
	if (!isset($_REQUEST["page"])) $_REQUEST["page"] = "1"; // we're on the first page
	
	if (isset($_REQUEST['entries_per_page'])) 
		$_SESSION['entries_per_page'] = $_REQUEST['entries_per_page'];
	else
		$_SESSION['entries_per_page'] = 20;
	
	$cond = "";
	
	// determine the filtering conditional based on the level and id number
	if ($level == "albums" or $level == "comments"){
		$cond = "WHERE `parent_id` = '$id'";
	}
	else if ($level == "pictures"){
		$cond = "WHERE `parent_album` = '$id'";
	}
	
	$url = "?entries_per_page=$_SESSION[entries_per_page]&amp;level=$_REQUEST[level]&amp;id=$id";
	
	$plog_page = isset($_REQUEST['plog_page']) ? $_REQUEST['plog_page'] : 0;
	$first_item = ($plog_page - 1) * $_SESSION['entries_per_page'];
	if ($first_item < 0) {
		$first_item = 0;
	};
	$limit = "LIMIT $first_item, $_SESSION[entries_per_page]";
	
	// lets generate the pagination menu as well
	$recordCount = "SELECT COUNT(*) AS num_items FROM ".TABLE_PREFIX."$level $cond";
	$totalRowsResult = mysql_query($recordCount);
	$totalRows = mysql_result($totalRowsResult,'num_items');
	
	$page = isset($_GET["plog_page"]) ? $_GET["plog_page"] : 1;
	$pagination_menu = '<div id="pagination">'.generate_pagination('plog-manage.php'.$url,$page,$totalRows,$_SESSION['entries_per_page']).'</div>';
	
	$output .= '<form id="contentList" action="'.$_SERVER["PHP_SELF"].'" method="get">';
	
	$level = $_REQUEST['level'];
	
	if (empty($level)) {
		$output .= generate_breadcrumb_admin("").$pagination_menu;
		$output .= plog_collection_manager($first_item,$_SESSION['entries_per_page']);
	};
	
	if ($level == "albums") {
		$output .= generate_breadcrumb_admin("albums", $id).$pagination_menu;
		$output .= plog_album_manager($id,$first_item,$_SESSION['entries_per_page']);
	};
	
	if ($level == "pictures") {
		$output .= generate_breadcrumb_admin("pictures", $id).$pagination_menu;
		$output .= plog_picture_manager($id,$first_item,$_SESSION['entries_per_page']);
	
	};
	
	if ($level == "comments") {
		$output .= generate_breadcrumb_admin("comments", $id).$pagination_menu;
		$output .= plog_comment_manager($id,$first_item,$_SESSION['entries_per_page']);
	};
	
	$output .= '
		<a href="#" onclick="checkAll(document.getElementById(\'contentList\')); return false; ">' . plog_tr('Invert Checkbox Selection') . '</a>
		'.$pagination_menu.
		'<input type="hidden" name="level" value="'.$level.'" />
		<input type="hidden" name="id" value="'.$id.'" />
		<input type="hidden" name="action" value="1" />
		<input class="submit" type="submit" name="delete_checked" onClick="return confirm(\'' . plog_tr('Are you sure you want to delete selected items?') . '\');" 
		value="' . plog_tr('Delete Checked') . '"/>';
		if (!empty($level) && $level != "comments"){
			$output .= generate_move_menu($level);
		};
		$output .= '</form>';
}

display($output, "manage");

?>
