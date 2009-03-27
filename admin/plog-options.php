<?php
require("plog-admin.php");
require_once("../plog-load_config.php");
require_once("plog-admin-functions.php");

$output = '';
if (isset($_POST["submit"])){

	if (isset($_POST["allow_dl"])) $allow_dl = 1; else $allow_dl = 0;
	if (isset($_POST["allow_comments"])) $allow_comments = 1; else $allow_comments = 0;
	if (isset($_POST["allow_print"])) $allow_print = 1; else $allow_print = 0;
	if (isset($_POST["square_thumbs"])) $square_thumbs = 1; else $square_thumbs = 0;
	if (isset($_POST["generate_intermediate"])) $generate_intermediate = 1; else $generate_intermediate = 0;
	if (isset($_POST["comments_moderate"])) $comments_moderate = 1; else $comments_moderate = 0;
	if (isset($_POST["allow_fullpic"])) $allow_fullpic = 1; else $allow_fullpic = 0;
	configure_htaccess_fullpic($allow_fullpic);
	$disable_intermediate = ($generate_intermediate == 1) ? 0 : 1;

	// verify that gallery URL contains a trailing slash. if not, add one.
	$str = $_POST["gallery_url"];
	if( $str{strlen($str)-1} != '/') $_POST["gallery_url"] .= '/';

	$query = "UPDATE `".TABLE_PREFIX."config` SET
		`truncate`='".intval($_POST["truncate"])."',
		`feed_title`='".mysql_escape_string($_POST["feed_title"])."',
		`feed_language`='".mysql_escape_string($_POST["feed_language"])."',
		`feed_num_entries`='".intval($_POST["feed_num_entries"])."',
		`allow_dl`='".intval($allow_dl)."',
		`allow_comments`='".intval($allow_comments)."',
		`allow_print`='".intval($allow_print)."',
		`default_sortby`='".mysql_escape_string($_POST["default_sortby"])."',
		`default_sortdir`='".mysql_escape_string($_POST["default_sortdir"])."',
		`album_sortby`='".mysql_escape_string($_POST["album_sortby"])."',
		`album_sortdir`='".mysql_escape_string($_POST["album_sortdir"])."',
		`collection_sortby`='".mysql_escape_string($_POST["collection_sortby"])."',
		`collection_sortdir`='".mysql_escape_string($_POST["collection_sortdir"])."',
		`thumb_num`='".intval($_POST["thumb_num"])."',
		`compression`='".intval($_POST["image_quality"])."',
		`admin_username`='".mysql_escape_string($_POST["admin_username"])."',
		`admin_email`='".mysql_escape_string($_POST["admin_email"])."',
		`date_format`='".mysql_escape_string($_POST["date_format"])."',
		`use_mod_rewrite`='".intval(@$_POST["use_mod_rewrite"])."',
		`square_thumbs`='".intval($square_thumbs)."',
		`comments_notify`='".intval($_POST["comments_notify"])."',
		`comments_moderate`='".intval($comments_moderate)."',
		`gallery_url`='".mysql_escape_string($_POST["gallery_url"])."',
		`gallery_name`='".mysql_escape_string($_POST["gallery_name"])."',
		`thumb_nav_range`='".intval($_POST["thumb_nav_range"])."',
		`enable_thumb_nav`='".intval(@$_POST["enable_thumb_nav"])."',
		`allow_fullpic`='".intval($allow_fullpic)."'";

	if (trim($_POST["admin_password"]) != ''){
		if (trim($_POST["admin_password"]) == trim($_POST["confirm_admin_password"])){
			$query .= ", `admin_password`='".md5(mysql_real_escape_string(trim($_POST["admin_password"])))."'";
		}
		else{
			$error_flag = true;
			$output .= '<p class="errors">' . plog_tr('The passwords you entered did not match.') . '</p>';
			$output .= '<p class="actions">' . plog_tr('Other changes have been applied successfully.') . '</p>';
		}
	}

	run_query($query);

	$max_thumbnail_size = intval($_POST["max_thumbnail_size"]);
	$max_display_size = intval($_POST["max_display_size"]);
	$rss_thumbsize = intval($_POST["rss_thumbsize"]);
	$nav_thumbsize = intval($_POST["nav_thumbsize"]);
	$time = time();

	if ($thumbnail_config[THUMB_SMALL]['size'] != $max_thumbnail_size ||
		$config['square_thumbs'] != $square_thumbs) {

		$query = "UPDATE `".TABLE_PREFIX."thumbnail_config`
				SET max_size = '$max_thumbnail_size',update_timestamp = '$time'
				WHERE id = " . THUMB_SMALL;
		mysql_query($query);
	}

	if ($thumbnail_config[THUMB_LARGE]['size'] != $max_display_size) {

		$query = "UPDATE `".TABLE_PREFIX."thumbnail_config`
				SET max_size = '$max_display_size',update_timestamp = '$time'
				WHERE id = " . THUMB_LARGE;
		mysql_query($query);
	}

	$query = "UPDATE `".TABLE_PREFIX."thumbnail_config`
			SET disabled = '$disable_intermediate'
			WHERE id = " . THUMB_LARGE;
	mysql_query($query);


	if ($thumbnail_config[THUMB_RSS]['size'] != $rss_thumbsize) {

		$query = "UPDATE `".TABLE_PREFIX."thumbnail_config`
				SET max_size = '$rss_thumbsize',update_timestamp = '$time'
				WHERE id = " . THUMB_RSS;
		mysql_query($query);
	}

	if ($thumbnail_config[THUMB_NAV]['size'] != $nav_thumbsize ){
	$query = "UPDATE `".TABLE_PREFIX."thumbnail_config`
			SET max_size = '$nav_thumbsize',update_timestamp = '$time'
			WHERE id = " . THUMB_NAV;
	mysql_query($query);
	}
	
	//	`max_thumbnail_size`='".intval($_POST["max_thumbnail_size"])."',
	//	`max_display_size`='".intval($_POST["max_display_size"])."',
	//	`rss_thumbsize`='".intval($_POST["rss_thumbsize"])."',

	// and read the configuration back again
	$config["use_mod_rewrite"] = intval(@$_POST["use_mod_rewrite"]);
	configure_mod_rewrite($config["use_mod_rewrite"]);

	if (!isset($error_flag)) $output .= '<p class="actions">' . plog_tr("You have updated your settings successfully.") . '</p>';

	$_SESSION["msg"] = $output;
	header("Location: plog-options.php");
	exit;


}

if (isset($_SESSION["msg"])) {
	$output .= $_SESSION["msg"];
	unset($_SESSION["msg"]);
};

$date_formats = array(
	"n.j.Y",
	"j.n.Y",
	"F j, Y",
	"m.d.y",
	"Ymd",
	"j-m-y",
	"d. F Y",
	"D M j Y",
	);

$output .= '
	<h1>' . plog_tr("System Options") . '</h1>
	<form action="'.$_SERVER["PHP_SELF"].'" method="post">
    	<div id="options_section">
	        <table class="option-table">
	            <tr class="alt">
	                <td><label for="gallery_name"><strong>' . plog_tr("Gallery Name:") . '</strong></label> ' . plog_tr("(optional)") . '</td>
	                <td>

	                    <input size="45" type="text" id="gallery_name" name="gallery_name" value="'.stripslashes($config['gallery_name']).'"/>
	                </td>
	            </tr>
	            <tr>
	                <td><label for="gallery_url"><strong>' . plog_tr('Gallery URL:') . '</strong></label> </td>
	                <td>

	                    <input size="45" type="text" id="gallery_url" name="gallery_url" value="'.stripslashes($config['gallery_url']).'"/>
	                </td>
	            </tr>
	            <tr class="alt">
	                <td><strong>' . plog_tr('Administrator Username:') . '</strong></td>
	                <td>
	                    <input type="text" name="admin_username" value="'.$config['admin_username'].'"/>
	                </td>
	            </tr>
	            <tr>
	                <td><strong>' . plog_tr('Administrator E-mail address:') . '</strong></td>
	                <td>
	                    <input type="text" name="admin_email" value="'.$config['admin_email'].'"/>
	                </td>
	            </tr>
	            <tr class="alt">
	                <td><strong>' . plog_tr('Send E-mail Notification for Comments?') . '</strong><br/> ' . plog_tr('(requires valid e-mail address)') . '</td>
	                <td>';

						if ($config['comments_notify'] == 1) $checked = "checked='checked'"; else $checked = "";

	                		$output .=
	                    '<input type="checkbox" name="comments_notify" value="1" '.$checked.' />
	                </td>
	            </tr>
	            <tr>
	                <td><strong>' . plog_tr('Place New Comments into Moderation?') . '</strong><br/></td>
	                <td>';

						if ($config['comments_moderate'] == 1) $checked = "checked='checked'"; else $checked = "";

	                		$output .=
	                    '<input type="checkbox" name="comments_moderate" value="1" '.$checked.' />
	                </td>
	            </tr>
	            <tr class="alt">
	                <td><strong>' . plog_tr("New Administrator Password:") . '</strong></td>
	                <td>
	                    <input type="password" name="admin_password"/>
	                </td>
	            </tr>
	            <tr>
	                <td><strong>' . plog_tr('Confirm New Administrator Password:') . '</strong></td>
	                <td>
	                    <input type="password" name="confirm_admin_password"/>
	                </td>
	            </tr>
	            </table>
	            <h1>' . plog_tr('Thumbnail Options') . '</h1>
	            <table class="option-table">';

	            if ($config['square_thumbs']) $dim = plog_tr("Small Thumbnail Width"); else $dim = plog_tr("Small Thumbnail Height");

		    $generate_intermediate = ($thumbnail_config[THUMB_LARGE]['disabled'] == 0) ? "checked='checked'" : "";

	            $output.='<tr class="alt">
	                <td><strong>'.$dim.'</strong> (pixels):</td>
	                <td>
	                    <input size="3" type="text" name="max_thumbnail_size" value="'.$thumbnail_config[THUMB_SMALL]['size'].'"/>
	                </td>
	            </tr>
	            <tr>
	                <td><strong>' . plog_tr('Generate Intermediate Pictures?') . '</strong>:</td>
	                <td>
	                    <input type="checkbox" name="generate_intermediate" value="1" '.$generate_intermediate.'/>
	                </td>
	            </tr>
	            <tr class="alt">
	                <td><strong>' . plog_tr('Intermediate Picture Width') . '</strong> ' . plog_tr("(pixels):"). '</td>
	                <td>
	                    <input size="4" type="text" name="max_display_size" value="'.$thumbnail_config[THUMB_LARGE]['size'].'"/>
	                </td>
	            </tr>
	            <tr>
	                <td><strong>' . plog_tr('Number of Thumbnails per Page:') . '</strong></td>
	                <td>
	                    <input size="2" type="text" name="thumb_num" value="'.$config['thumb_num'].'"/>
	                </td>
	            </tr>
                <tr class="alt">
	                <td><strong>' . plog_tr('JPEG Image Quality</strong> (1=worst, 95=best, 75=default):') . '</td>
	                <td>
	                    <input size="2" type="text" name="image_quality" value="'.$config['compression'].'"/>
	                </td>
	            </tr>
	            <tr>
	                <td><strong>' . plog_tr('Default Sort Order:') . '</strong></td>
	                <td>';

			$sort_by_fields = array(
				'date' => plog_tr('Date Submitted'),
				'date_taken' => plog_tr('Date Taken'),
				'caption' => plog_tr('Caption'),
				'filename' => plog_tr('Filename'),
				'number_of_comments' => plog_tr('Number of Comments'),
			);

			$sort_by_fields_collection = array(
				'id' => plog_tr('Date Created'),
				'name' => plog_tr('Alphabetical'),
			);

			$sort_dir_fields = array(
				'ASC' => plog_tr('Ascending'),
				'DESC' => plog_tr('Descending'),
			);

	                $output .= '<select style="width: 146px" id="default_sortby" name="default_sortby">';

			foreach($sort_by_fields as $sort_key => $sort_caption) {
				$selected = ($config['default_sortby'] == $sort_key) ? 'selected="selected" ' : '';
				$output .= '<option '.$selected.'value="'.$sort_key.'">'.$sort_caption.'</option>';
			};
			$output .= '</select><select id="default_sortdir" name="default_sortdir">';

			foreach($sort_dir_fields as $sort_key => $sort_caption) {
				$selected = ($config['default_sortdir'] == $sort_key) ? 'selected="selected" ' : '';
				$output .= '<option ' .$selected.'value="'.$sort_key.'">'.$sort_caption.'</option>';
			};
			$output .= '</select>';
			$output .= '
	                </td>
	            </tr>
	            <tr class="alt">

	            <td><strong>' . plog_tr('Album Sort Order:') . '</strong></td>
	                <td>';

	                $output .= '<select id="album_sortby" name="album_sortby">';

					foreach($sort_by_fields_collection as $sort_key => $sort_caption) {
						$selected = ($config['album_sortby'] == $sort_key) ? 'selected="selected" ' : '';
						$output .= '<option '.$selected.'value="'.$sort_key.'">'.$sort_caption.'</option>';
					};
					$output .= '</select><select id="album_sortdir" name="album_sortdir">';

					foreach($sort_dir_fields as $sort_key => $sort_caption) {
						$selected = ($config['album_sortdir'] == $sort_key) ? 'selected="selected" ' : '';
						$output .= '<option ' .$selected.'value="'.$sort_key.'">'.$sort_caption.'</option>';
					};

					$output .= '</select>';
					$output .= '

	            </td></tr>
	            <tr>

	            <td><strong>' . plog_tr('Collection Sort Order:') . '</strong></td>
	                <td>';

	                $output .= '<select id="collection_sortby" name="collection_sortby">';

					foreach($sort_by_fields_collection as $sort_key => $sort_caption) {
						$selected = ($config['collection_sortby'] == $sort_key) ? 'selected="selected" ' : '';
						$output .= '<option '.$selected.'value="'.$sort_key.'">'.$sort_caption.'</option>';
					};
					$output .= '</select><select id="collection_sortdir" name="collection_sortdir">';

					foreach($sort_dir_fields as $sort_key => $sort_caption) {
						$selected = ($config['collection_sortdir'] == $sort_key) ? 'selected="selected" ' : '';
						$output .= '<option ' .$selected.'value="'.$sort_key.'">'.$sort_caption.'</option>';
					};

					$output .= '</select>';
					$output .= '

	            </td></tr>
	            <tr class="alt">
	                <td><strong>' . plog_tr('Use Cropped Square Thumbnails?:') .'</strong></td>
	                <td>';

	                if ($config['square_thumbs'] == 1) $checked = "checked='checked'"; else $checked = "";
	             	$output .= '<input type="checkbox" name="square_thumbs" value="1" '.$checked.' />

	                </td>
	            </tr>
		    <tr>
	                <td><strong>' . plog_tr('Thumbnail Navigation Enabled:') . '</strong></td>
	                <td>';
		    
	            if (!empty($config['enable_thumb_nav'])) $checked = "checked='checked'"; else $checked = "";
	             	$output .= '<input type="checkbox" name="enable_thumb_nav" value="1" '.$checked.' />';
		    
		    $output .= '
	                </td>
	            </tr>
		    <tr class="alt">
	                <td><strong>' . plog_tr('Thumbnail Navigation Range') . '</strong> ' . plog_tr('(0 for whole album):') . '</td>
	                <td>
	                    <input size="2" type="text" name="thumb_nav_range" value="'.$config['thumb_nav_range'].'"/>
	                </td>
	            </tr>
		    <tr>
	                <td><strong>' . plog_tr('Thumbnail Navigation Size:') . '</strong></td>
	                <td>
	                    <input size="4" type="text" name="nav_thumbsize" value="'.$thumbnail_config[THUMB_NAV]['size'].'"/>
	                </td>
	            </tr>
		    <tr class="alt">
		    	<td><strong>' . plog_tr('Allow Full Picture Access:') . '</strong></td>
	                <td>';
		    
	            if (!empty($config['allow_fullpic'])) $checked = "checked='checked'"; else $checked = "";
	             	$output .= '<input type="checkbox" name="allow_fullpic" value="1" '.$checked.' />';
		    
		    $output .= '
	                </td>
			</tr>
	            </table>
	            <h1>' . plog_tr('Interface Options') . '</h1>
	            <table class="option-table">
	            <tr>
	                <td><strong>' . plog_tr('Date Format') . '</strong>:</td>
	                <td>
	                    <select name="date_format">';

foreach ($date_formats as $format){
	$output .= '<option value="'.$format.'"';
	if ($config["date_format"] == $format) $output .= ' selected="selected"';
	$output .= '>'.date($format).'</option>';
}

$output .= '          </select>
	                </td>
	            </tr>
							<tr class="alt">
	                <td><strong>' . plog_tr('Allow Compressed Recursive Downloads?') . '</strong>:</td>
	                <td>';

									if ($config['allow_dl'] == 1) $checked = "checked='checked'"; else $checked = "";

	                $output .= '<input type="checkbox" name="allow_dl" value="1" '.$checked.' />
	                </td>
	            </tr>
							<tr>
	                <td><strong>' . plog_tr('Allow User Comments?') . '</strong> ' .plog_tr('(will override individual settings)') . '</td>
	                <td>';

											if ($config['allow_comments'] == 1) $checked = "checked='checked'"; else $checked = "";

	                		$output .=
	                    '<input type="checkbox" name="allow_comments" value="1" '.$checked.' />
	                </td>
	            </tr>

							<tr class="alt">
	                <td><strong>' . plog_tr('Allow Auto Print?') . ' </strong></td>
	                <td>';

											if ($config['allow_print'] == 1) $checked = "checked='checked'"; else $checked = "";

	                		$output .=
	                    '<input type="checkbox" name="allow_print" value="1" '.$checked.' />
	                </td>
	            </tr>

				<tr>
	                <td><strong>' . plog_tr('Truncate Long Filenames How Long?') . '</strong> ' . plog_tr('(Use zero for no truncation)') . '</td>
	                <td>

							<input size="2" type="text" name="truncate" value="'.$config['truncate'].'"/>
	                </td>
	            </tr>
				<tr class="alt">
	                <td><strong>' . plog_tr('Generate Cruft-Free URLs') . '</strong> ' . plog_tr('(requires mod_rewrite)') . '</td>
	               	<td>';
				$htaccess_file = $config["basedir"] . ".htaccess";
				if ($config['use_mod_rewrite'] == 1) $checked = "checked='checked'"; else $checked = "";

				if (is_writable($htaccess_file)) {
	                		$output .= '<input type="checkbox" name="use_mod_rewrite" value="1" '.$checked.' />';
				} else {
					$output .= plog_tr(".htaccess is not writable, please check permissions");
				};

			$output .= '
	                </td>
	            </tr>
	        </table>

				<h1>' . plog_tr('RSS Syndication Options') . '</h1>
				<table class="option-table">
				<tr>
	            <td><strong>' . plog_tr('RSS Feed Title:') . '</strong></td>
	                <td>
	                    <input size="45" type="text" name="feed_title" value="'.stripslashes($config['feed_title']).'"/>
	                </td>
	            </tr>
	            <tr class="alt">
	                <td><strong>' . plog_tr('RSS Image Thumbnail Width (pixels):') . '</strong></td>
	                <td>
	                    <input size="4" type="text" name="rss_thumbsize" value="'.$thumbnail_config[THUMB_RSS]["size"].'"/>
	                </td>
	            </tr>
	            <tr>
	                <td><strong>' . plog_tr('Language:') . '</strong> <a href="http://www.w3.org/TR/REC-html40/struct/dirlang.html#langcodes">' . plog_tr('(language codes)') . '</a></td>
	                <td>
	                    <input type="text" name="feed_language" value="'.$config['feed_language'].'"/>
	                </td>
	            </tr>
                <tr class="alt">
	                <td><strong>' . plog_tr('Number of Images Per Feed:') . '</strong></td>
	                <td>
	                    <input size="3" type="text" name="feed_num_entries" value="'.$config['feed_num_entries'].'"/>
	                </td>
	            </tr>
							<tr><td></td><td><input class="submit" type="submit" name="submit" value="' . plog_tr('Update Options') . '"/></td></tr>
	    				</table>


	    </div></form>';

display($output, "options");

?>
