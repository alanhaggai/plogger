<?php

require("plog-admin.php");
require_once("../plog-load_config.php");
require_once("../plog-functions.php");
require_once("plog-admin-functions.php");

global $inHead;

$inHead = '<script src="js/ajax_editing.js" type="text/javascript"></script>';	
	
function generate_pagination_view_menu() {
	
	$java = 'document.location.href = \''.$_SERVER["PHP_SELF"].'?'.
	'&amp;entries_per_page=\'+this.options[this.selectedIndex].value';
	
	$possible_values = array("5"=>5, "10"=>10, "20"=>20, "50"=>50);
	$output= plog_tr('Entries per page') . ' <select onchange="'.$java.'" name="entries_per_page">';
	
	foreach ($possible_values as $key => $value)
		if ($_SESSION['entries_per_page'] == $key)
			$output .= "<option value=\"$value\" selected='selected'>$key</option>";
		else
			$output .= "<option value=\"$value\">$key</option>";
			
	$output.= '</select>';
	
	return $output;

}

$output = '<h1>'. plog_tr("Manage Feedback") . '</h1>';


// here we will determine if we need to perform a move or delete action.
$num_items = 0;

// perform the delete function on the selected items
if (isset($_REQUEST['delete_checked']) || $_REQUEST['action'] == 'delete_checked') {
		
	if (isset($_REQUEST["Selected"])) {
		foreach($_REQUEST["Selected"] as $del_id) {
			// lets build the query string
			$del_id = intval($del_id);
			
			$query = "DELETE FROM ".TABLE_PREFIX."comments WHERE `id`= '$del_id'";
			$result = run_query($query);
			
			$num_items++;
		}
		
		$output .= "<p class=\"actions\">" . sprintf(plog_tr('You have deleted %d comment(s) successfully.'),$num_items) . "</p>";

	} else{
		$output .= "<p class=\"errors\">" . plog_tr('Nothing selected to delete!') . "</p>";
	}
};

if (isset($_REQUEST['approve_checked']) || $_REQUEST['action'] == 'approve_checked') {
	// set the approval bit to 1 for all selected comments
		
	if (isset($_REQUEST["Selected"])) {
		foreach($_REQUEST["Selected"] as $appr_id) {
			// lets build the query string
			$appr_id = intval($appr_id);
			
			$query = "UPDATE ".TABLE_PREFIX."comments SET `approved` = 1 WHERE `id`= '$appr_id'";
			$result = run_query($query);
			
			$num_items++;
		}
			
		$output .= "<p class=\"actions\">" . sprintf(plog_tr('You have approved %d comment(s) successfully.'),$num_items) . "</p>";
	} else {
		$output .= "<p class=\"errors\">". plog_tr('Nothing selected to approve!') . "</p>";
	}
};

if (isset($_REQUEST["action"])) {
	if ($_REQUEST["action"] == "edit-comment") {
		// show the edit form
		$output .= edit_comment_form($_REQUEST["pid"]);
	}

	else if ($_REQUEST["action"] == "update-comment") {
		// update comment in database
		$result = update_comment($_POST["pid"],$_POST["author"],$_POST["email"],$_POST["url"],$_POST["comment"]);
		if (isset($result['errors'])) {
			$output .= '<p class="errors">' . $result['errors'] . '</p>';
		} elseif (isset($result['output'])) {
			$output .= '<p class="actions">' . $result['output'] . '</p>';

		}
	}

}

$output .= '<form id="contentList" action="'.$_SERVER["PHP_SELF"].'" method="get">';


$allowedCommentKeys = array("unix_date", "author", "email", "url", "comment");


// lets iterate through all the content and build a table
// set the default level if nothing is specified

// handle pagination
// lets determine the limit filter based on current page and number of results per page
if (!isset($_REQUEST["plog_page"])) $_REQUEST["plog_page"] = "1"; // we're on the first page

if (isset($_REQUEST['entries_per_page'])) $_SESSION['entries_per_page'] = $_REQUEST['entries_per_page'];

if (!isset($_SESSION['entries_per_page'])) $_SESSION['entries_per_page'] = 20;


#$url = "&amp;entries_per_page=$_SESSION[entries_per_page]&amp;level=$_REQUEST[level]&amp;id=$_REQUEST[id]";
$url = "?entries_per_page=$_SESSION[entries_per_page]";

$first_item = ($_REQUEST['plog_page'] - 1) * $_SESSION['entries_per_page'];
$limit = "LIMIT $first_item, $_SESSION[entries_per_page]";

// lets generate the pagination menu as well
$recordCount = "SELECT count(*) AS num_comments FROM ".TABLE_PREFIX."comments WHERE `approved` = 1";
$totalRowsResult = mysql_query($recordCount);
$num_comments = mysql_result($totalRowsResult,"num_comments");

$query = "SELECT COUNT(*) as in_moderation from ".TABLE_PREFIX."comments WHERE `approved` = 0";
$mod_result = run_query($query);
$num_comments_im = mysql_result($mod_result, "in_moderation");

$page = isset($_GET["plog_page"]) ? $_GET["plog_page"] : 1;

// filter based on whether were looking at approved comments or unmoderated comments
$approved = isset($_GET["moderate"]) ? 0 : 1;

if ($approved)
	$pagination_menu = generate_pagination('plog-feedback.php'.$url,$page,$num_comments,$_SESSION['entries_per_page']);
else
	$pagination_menu = generate_pagination('plog-feedback.php'.$url,$page,$num_comments_im,$_SESSION['entries_per_page'],"&amp;moderate=1");

// generate javascript init function for ajax editing
$query = "SELECT *, UNIX_TIMESTAMP(`date`) AS `unix_date` from ".TABLE_PREFIX."comments WHERE `approved` = $approved ORDER BY `id` DESC $limit";
$result = run_query($query);

if (mysql_num_rows($result) > 0) {
	$output .= '<script type="text/javascript">';
	$output .= "Event.observe(window, 'load', init, false);";
	$output .= "function init() {";
		
	while($row = mysql_fetch_assoc($result)) {
		$output .= "makeEditable('comment-comment-".$row['id']."');
					makeEditable('comment-author-".$row['id']."');
					makeEditable('comment-url-".$row['id']."');
					makeEditable('comment-email-".$row['id']."');";
	}
	
	$output .= "}";
	$output .= '</script>';
}

$query = "SELECT *, UNIX_TIMESTAMP(`date`) AS `unix_date` from ".TABLE_PREFIX."comments WHERE `approved` = $approved ORDER BY `id` DESC $limit";
$result = run_query($query);


$empty = 0;
if ($result) {
	if (mysql_num_rows($result) == 0) {
	 $output.= '<p class="actions">' . plog_tr('You have no user comments on your gallery.') . '</p>';
	 $empty = 1;
	}
	if ($approved) {
		if ($num_comments_im > 0) {
			$output.= '<p class="actions">' . sprintf(plog_tr('You have %d comment(s) waiting for approval.'),$num_comments_im) . '
			<a href="plog-feedback.php?moderate=1">' . plog_tr('Click here') . '</a>.</p>';
		}
	} 
	$counter = 0;

	while($row = mysql_fetch_assoc($result)) {
		// if we're on our first iteration, dump the header
		if ($counter == 0) {
			if ($approved)
				$output .= '<table style="width: 100%"><tr><td>' . sprintf(plog_tr('You have <strong>%d</strong> user comment(s).'),$num_comments) . '</td>';
			else
				$output .= '<table style="width: 100%"><tr><td>' . sprintf(plog_tr('You have <strong>%d</strong> user comment(s) awaiting approval.'),$num_comments_im) . '</td>';

			// output view entries pagination control
			$output .= '<td align="right">'.generate_pagination_view_menu().'</td></tr></table>';
			
			$output .= '<table style="width: 100%" cellpadding="4"><tr class="header"><td class="table-header-left"></td><td class="table-header-middle" width="65">thumb</td>';
		
			foreach ($row as $name => $value) {
				if (in_array($name, $allowedCommentKeys)) $output .= "<td class=\"table-header-middle\">".$name."</td>";
			}
			
			$output .= '<td class="table-header-right">Actions</td></tr>';
		}
		
		if ($counter%2 == 0) $table_row_color = "color-1";
		else $table_row_color = "color-2";
		
		// start a new table row (alternating colors)
		$output .= "<tr class=\"$table_row_color\">";
		
		// give the row a checkbox
		$output .= '<td width="15"><input type="CHECKBOX" name="Selected[]" VALUE="'.$row["id"].'"></td>';
		
		// give the row a thumbnail, we need to look up the parent picture for the comment
		$picture = get_picture_by_id($row["parent_id"]);

		$thumbpath = generate_thumb($picture["path"],$picture["id"],THUMB_SMALL);

		// generate XHTML with thumbnail and link to picture view.
		$imgtag = '<div class="img-shadow"><img src="'.$thumbpath.'" title="'.$picture["caption"].'" alt="'.$picture["caption"].'" /></div>';
		//$target = 'plog-thumbpopup.php?src='.$picture["id"];;
		//$java = "javascript:this.ThumbPreviewPopup('$target')";
		
		$output .= '<td><a href="'.generate_thumb($picture["path"],$picture["id"],THUMB_LARGE).'" rel="lightbox" title="'.plogger_get_picture_caption().'">'.$imgtag.'</a></td>';
		
		
		foreach($row as $key => $value) {
			$value = htmlspecialchars($value);
			$value = SmartStripSlashes($value);
		
			if ($key == "unix_date") {
				$output .= '<td>'.date($config["date_format"], $value).'</td>';
			}
			else if ($key == "allow_comments") {
				if ($value) $output .= "<td>". plog_tr('Yes') . "</td>";
				else $output .= "<td>" . plog_tr('No') . "</td>";
			}
			//else if ($key == "ip") {
			//	$output .= "<td>" . @gethostbyaddr($value) . "</td>";
			//}

			else {
				if (in_array($key, $allowedCommentKeys))
						$output .= "<td><p id=\"comment-$key-" . $row[id] ."\">$value&nbsp;</p></td>";
			}
		}
		
		// $output .= our actions panel
		$query = "?action=edit-comment&amp;pid=$row[id]";

		if (!$approved) {
			$output .= '<td width="80"><noscript><a href="'.$_SERVER["PHP_SELF"]."$query&amp;entries_per_page=$_SESSION[entries_per_page]
		&amp;moderate=1".'"><img src="../graphics/edit.gif" alt="Edit" title="Edit"></a></noscript><a href="'.$_SERVER["PHP_SELF"]."?action=delete_checked&amp;Selected[]=$row[id]&amp;moderate=1".'" 
		onClick="return confirm(\'' . plog_tr('Are you sure you want to delete this comment?') . '\');"><img src="../graphics/x.gif" alt="' . plog_tr('Delete') . '" title="' . plog_tr('Delete') . '"></a><a href="'.$_SERVER["PHP_SELF"]."?action=approve_checked&amp;Selected[]=$row[id]&amp;moderate=1".'" 
			onClick="return confirm(\'' . plog_tr('Are you sure you want to approve this comment?') . '\');"><img src="../graphics/new_file.gif" alt="' . plog_tr('Approve') . '" title="' . plog_tr('Approve') . '"></a></td>';
		}
		else
			$output .= '<td width="80"><noscript><a href="'.$_SERVER["PHP_SELF"]."$query&amp;entries_per_page=$_SESSION[entries_per_page]
		&amp;moderate=$approved".'"><img src="../graphics/edit.gif" alt="' . plog_tr('Edit') . '" title="' . plog_tr('Edit') . '"></a></noscript><a href="'.$_SERVER["PHP_SELF"]."?action=delete_checked&amp;Selected[]=$row[id]".'" 
		onClick="return confirm(\'' . plog_tr('Are you sure you want to delete this comment?') . '\');"><img src="../graphics/x.gif" alt="' . plog_tr('Delete') . '" title="' . plog_tr('Delete') . '"></a></td>';
		
		

		
		$output .= "</tr>";
		$counter++;
	}
	
	if ($counter > 0)
		$output .= '<tr class="header"><td colspan="9"></td></tr></table>';
}

if (!$empty)
	$output .= '
		<table style="width: 100%"><tr><td><a href="#" onclick="checkAll(document.getElementById(\'contentList\')); return false; ">' . plog_tr('Invert Checkbox Selection') . '</a></td><td align="right">'.$pagination_menu.'</td></tr></table>'.
		'<input class="submit" type="submit" name="delete_checked" onClick="return confirm(\''. plog_tr('Are you sure you want to delete selected items?') . '\');" 
		value="' . plog_tr('Delete Checked') . '"><input class="submit" type="submit" name="approve_checked" onClick="return confirm(\'' . plog_tr('Are you sure you want to approve selected items?') . '\');" 
		value="' . plog_tr('Approve Checked') . '">';

$output .= '</form>';

display($output, "feedback");

?>
