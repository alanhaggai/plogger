<?php
require_once("plog-admin.php");
require_once("../plog-load_config.php");
require_once("plog-admin-functions.php");

$action_result = array();

if ($_POST['action'] == "update") {
	
	// What field are we updating?
	$field = $_POST['field'];
	
	// With what?
	$content = trim($_POST['content']);
	
	// Now we parse the field to be updated and the id number from the field variable
	$var = split("-", $field);
	$type = $var[0];
	$field = $var[1];
	$id = $var[2];
	
	//print "debug: field = " . $field . ", content = " . $content . ", id = " . $id;
	
	if ($type == "picture") {
		$result = update_picture_field($id, $field, $content);
		if ($result['output']) {
			 print stripslashes($content);
		} else {
			print "error: " . $result['errors'];
		};
	}
	elseif ($type == "album") {
		$result = update_album_field($id, $field, $content);
		if ($result['output']) {
			 print stripslashes($content);
		} else {
			print "error: " . $result['errors'];
		};
	}
	elseif ($type == "collection") {
		$result = update_collection_field($id, $field, $content);
		if ($result['output']) {
			 print stripslashes($content);
		} else {
			print "error: " . $result['errors'];
		};
	}
	elseif ($type == "comment") {
		$result = update_comment_field($id, $field, $content);
		if ($result['output']) {
			print stripslashes($content);
		} else {
			 print "error: " . $result['errors'];
		};
	}
}

if ($_POST['action'] == "add-collection") {
	$action_result = add_collection($_POST["name"],$_POST["description"]);
	if (empty($action_result['errors'])) {
		$output .= "<script type='text/javascript'>Element.show('add_item_link');Element.hide('add_item_form');Form.reset('add_form');</script>";
	};
};

if ($_POST['action'] == "list-collections") {
	$output .= plog_collection_manager($_POST["page"],$_SESSION['entries_per_page']);
};

if (!empty($action_result['errors'])) {
		$output .= '<p class="errors" id="rpc_message">' . $action_result['errors'] . '</p>';
} elseif (!empty($action_result['output'])) {
		$output .= '<p class="actions" id="rpc_message">' . $action_result['output'] . '</p>';
};

print $output;

?>
