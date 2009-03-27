<?php
require_once(PLOGGER_DIR . 'admin/plog-admin-functions.php');
@include(PLOGGER_DIR . 'plog-config.php');

function do_install($form) {
	$form = array_map('stripslashes',$form);
	$form = array_map('trim',$form);
	
	$errors = check_requirements();
	if (sizeof($errors) > 0) {
		print '<p>Plogger wont work until the following problems are resolved</p>';
		print '<ul>';
		foreach($errors as $error) {
			print '<li>' . $error . '</li>';
		};
		print '</ul>';
		print '<form method="GET" action="_install.php"><input type="submit" value="Try again"/></form>';
		return false;
	};

	if (defined('PLOGGER_DB_HOST')) {
		$mysql = check_mysql(PLOGGER_DB_HOST,PLOGGER_DB_USER,PLOGGER_DB_PW,PLOGGER_DB_NAME);
		// looks like we are already configured
		if (empty($mysql)) {
			print '<p>Plogger is already installed!</p>';
			return false;
		};
	};

	$ok = false;
	$errors = array();

	if (!empty($form['action']) && 'install' == $form['action']) {
		if (empty($form['db_host'])) {
			$errors[] = 'Please enter the name of your MySQL host.';
		};

		if (empty($form['db_user'])) {
			$errors[] = 'Please enter the MySQL username.';
		};

		if (empty($form['db_pass'])) {
                	$errors[] = 'Please enter the MySQL password.';
        	}
		
		if (empty($form['db_name'])) {
			$errors[] = 'Please enter the MySQL database name.';
		};
		
		if (empty($form['gallery_name'])) {
			$errors[] = 'Please enter the name for your gallery.';
		};
		
		if (empty($form['admin_email'])) {
			$errors[] = 'Please enter your e-mail address.';
		};

		if (empty($errors)) {
			$errors = check_mysql($form['db_host'],$form['db_user'],$form['db_pass'],$form['db_name']);
			$ok = empty($errors);
		};

		if (!$ok) {
			print '<ul><li>';
			print join("</li>\n<li>",$errors);
			print '</li></ul>';
		} else {
			$password = generate_password();
			$_SESSION["install_values"] = array(
				"gallery_name" => $form["gallery_name"],
				"admin_email" => $form["admin_email"],
				"admin_password" => $password,
				"admin_username" => "admin"
			);
				
			$conf = create_config_file($form['db_host'],$form['db_user'],$form['db_pass'],$form['db_name']);
			// if configuration file is writable, then set the values
			// otherwise serve the config file to user and ask her to
			// upload it to webhost
			if (config_writable()) {
				write_config($conf);
			} else {
				$_SESSION["plogger_config"] = $conf;
			};
			require(PLOGGER_DIR . 'lib/plogger/form_setup_complete.php');
			return true;
		};
	};

	// most of the time it's probably running on localhost
	if (empty($form['db_host'])) {
		$form['db_host'] = 'localhost';
	};

	$init_vars = array('db_user','db_pass','db_name','gallery_name','admin_email');
	foreach($init_vars as $var) {
		if (empty($form[$var])) {
			$form[$var] = "";
		};
	};
	require(PLOGGER_DIR . 'lib/plogger/form_setup.php');
}

function create_tables() {
	// since 4.1 MySQL has support for specifying character encoding for tables
	// and I really want to  use it if available. So we need figure out what version
	// we are running on and to the right thing
	$mysql_version = mysql_get_server_info();
	$mysql_charset_support = "4.1";
	$default_charset = "";

	if (1 == version_compare($mysql_version,$mysql_charset_support)) {
		$default_charset = "DEFAULT CHARACTER SET UTF8";
	};

	maybe_add_table(
		TABLE_PREFIX . 'collections'
		,"`name` varchar(128) NOT NULL default '',
		`description` varchar(255) NOT NULL default '',
		`path` varchar(255) NOT NULL default '',
		`id` int(11) NOT NULL auto_increment,
		`thumbnail_id` int(11) NOT NULL DEFAULT '0',
		PRIMARY KEY  (id)"
		,"Type=MyISAM $default_charset");


	maybe_add_table(
		TABLE_PREFIX . 'albums'
		," `name` varchar(128) NOT NULL default '',
		`id` int(11) NOT NULL auto_increment,
		`description` varchar(255) NOT NULL default '',
		`path` varchar(255) NOT NULL default '',
		`parent_id` int(11) NOT NULL default '0',
		`thumbnail_id` int(11) NOT NULL default '0',
		 PRIMARY KEY  (id),
		 INDEX pid_idx (parent_id)"
		," Type=MyISAM $default_charset");
	
	maybe_add_table(
		TABLE_PREFIX . 'pictures'
		,"`path` varchar(255) NOT NULL default '',
		`parent_album` int(11) NOT NULL default '0',
		`parent_collection` int(11) NOT NULL default '0',
		`caption` mediumtext NOT NULL,
		`description` text NOT NULL,
		`id` int(11) NOT NULL auto_increment,
		`date_modified` timestamp(14) NOT NULL,
		`date_submitted` timestamp(14) NOT NULL,
		`EXIF_date_taken` varchar(64) NOT NULL default '',
		`EXIF_camera` varchar(64) NOT NULL default '',
		`EXIF_shutterspeed` varchar(64) NOT NULL default '',
		`EXIF_focallength` varchar(64) NOT NULL default '',
		`EXIF_flash` varchar(64) NOT NULL default '',
		`EXIF_aperture` varchar(64) NOT NULL default '',
		`allow_comments` int(11) NOT NULL default '1',
		PRIMARY KEY  (id),
		INDEX pa_idx (parent_album),
		INDEX pc_idx (parent_collection)"
		,"Type=MyISAM $default_charset");
	
	maybe_add_table(
		TABLE_PREFIX . 'comments'
		,"`id` int(11) NOT NULL auto_increment,
		`parent_id` int(11) NOT NULL default '0',
		`author` varchar(64) NOT NULL default '',
		`email` varchar(64) NOT NULL default '',
		`url` varchar(64) NOT NULL default '',
		`date` datetime NOT NULL,
		`comment` longtext NOT NULL,
		`ip` char(64),
		`approved` tinyint default '1',
		PRIMARY KEY  (id),
		INDEX pid_idx (parent_id),
		INDEX approved_idx (approved)"
		,"Type=MyISAM $default_charset");

	maybe_add_table(
		TABLE_PREFIX . 'config'
		,"`max_thumbnail_size` int(11) NOT NULL default '0',
		`max_display_size` int(11) NOT NULL default '0',
		`thumb_num` int(11) NOT NULL default '0',
		`admin_username` varchar(64) NOT NULL default '',
		`admin_password` varchar(64) NOT NULL default '',
		`admin_email` varchar(50) NOT NULL default '',
		`date_format` varchar(64) NOT NULL default '',
		`compression` int(11) NOT NULL default '75',
		`default_sortby` varchar(20) NOT NULL default '',
		`default_sortdir` varchar(5) NOT NULL default '',
		`album_sortby` varchar(20) NOT NULL default '',
		`album_sortdir` varchar(5) NOT NULL default '',
		`collection_sortby` varchar(20) NOT NULL default '',
		`collection_sortdir` varchar(5) NOT NULL default '',
		`gallery_name` varchar(255) NOT NULL default '',
		`allow_dl` smallint(1) NOT NULL default '0',
		`allow_comments` smallint(1) NOT NULL default '1',
		`allow_print` smallint(1) NOT NULL default '1',
		`truncate` int(11) NOT NULL default '12',
		`square_thumbs` tinyint default 1,
		`feed_num_entries` int(15) NOT NULL default '15',
		`rss_thumbsize` int(11) NOT NULL default '400',
		`feed_title` text NOT NULL,
		`use_mod_rewrite` tinyint NOT NULL default '0',
		`gallery_url` varchar(255) NOT NULL default '',
		`comments_notify` tinyint NOT NULL default '1',
		`comments_moderate` tinyint NOT NULL default '0',
		`feed_language` varchar(255) NOT NULL default 'en-us',
		`theme_dir` varchar(128) NOT NULL default '',
		`thumb_nav_range` int(11) NOT NULL default '0',
		`enable_thumb_nav` tinyint default '0',
		`allow_fullpic` tinyint default '1'"
		,"Type=MyISAM $default_charset");

	maybe_add_table(
		TABLE_PREFIX . 'thumbnail_config'
		,"`id` int(10) unsigned NOT NULL auto_increment,
		`update_timestamp` int(10) unsigned default NULL,
		`max_size` int(10) unsigned default NULL,
		`disabled` tinyint default 0,
		PRIMARY KEY  (`id`)");
	
	maybe_add_table(
		TABLE_PREFIX . 'tag2picture'
		,"`tag_id` bigint(20) unsigned NOT NULL default '0',
		  `picture_id` bigint(20) unsigned NOT NULL default '0',
		  `tagdate` datetime default NULL,
		  KEY `tag_id` (`tag_id`),
		  KEY `picture_id` (`picture_id`)"
		,"Type=MyISAM $default_charset");
	
	maybe_add_table(
		TABLE_PREFIX . 'tags'
		,"`id` bigint(20) unsigned NOT NULL auto_increment,
		  `tag` char(50) NOT NULL default '',
		  `tagdate` datetime NOT NULL default '0000-00-00 00:00:00',
		  `urlified` char(50) NOT NULL default '',
		  PRIMARY KEY  (`id`),
		  UNIQUE `tag` (`tag`),
		  UNIQUE `urlified` (`urlified`)"
		,"Type=MyISAM $default_charset");

}

function configure_plogger($form) {
	// use a random timestamp from the past to keep the existing thumbnails
	$long_ago = 1096396500;

	$thumbnail_sizes = array(
		THUMB_SMALL => 100,
		THUMB_LARGE => 500,
		THUMB_RSS => 400,
		THUMB_NAV => 60
	);

	foreach($thumbnail_sizes as $key => $size) {
		$sql = "INSERT INTO `".TABLE_PREFIX."thumbnail_config` (id,update_timestamp,max_size)
			VALUES('$key','$long_ago','$size')";
		mysql_query($sql);
	};

	$config['default_theme_dir'] = PLOGGER_DIR . 'themes/default/';
	$config['baseurl'] = "http://".$_SERVER["SERVER_NAME"]. substr($_SERVER["PHP_SELF"],0,strrpos($_SERVER["PHP_SELF"],"/")) . "/";
	$config['admin_username'] = 'admin';
	$config['admin_password'] = $form['admin_password'];
	$config['admin_email'] = $form['admin_email'];
	$config['gallery_name'] = $form['gallery_name'];
	
	$config = array_map('mysql_escape_string',$config);

	$query = "INSERT INTO `".TABLE_PREFIX."config`
		(`theme_dir`,
		`compression`,
		`max_thumbnail_size`,
		`max_display_size`,
		`thumb_num`,
		`admin_username`,
		`admin_email`,
		`admin_password`,
		`date_format`,
		`feed_title`,
		`gallery_name`,
		`gallery_url`)
	VALUES
		('${config['default_theme_dir']}',
		75,
		100,
		500,
		20,
		'${config['admin_username']}',
		'${config['admin_email']}',
		MD5('${config['admin_password']}'),
		'n.j.Y',
		'Plogger Photo Feed',
		'${config['gallery_name']}',
		'${config['baseurl']}')";

	mysql_query($query) or die(mysql_error().'<br /><br />'. $query);

	mail($config['admin_email'],"Your new gallery","You have successfully installed your new Plogger gallery. You can manage it at ${config['baseurl']}/admin Username is ${config['admin_username']} and password ${config['admin_password']}.");
}

function create_config_file($db_host,$db_user,$db_pass,$db_name) {
	$cfg_file = "<?php\n";
	$cfg_file .= '// this is the file used to connect to your database.'."\n";
	$cfg_file .= '// you must change these values in order to run the gallery.'."\n";
	$cfg_file .= 'define("PLOGGER_DB_HOST","'.$db_host.'");'."\n";
	$cfg_file .= 'define("PLOGGER_DB_USER","'.$db_user.'");'."\n";
	$cfg_file .= 'define("PLOGGER_DB_PW","'.$db_pass.'");'."\n";
	$cfg_file .= 'define("PLOGGER_DB_NAME","'.$db_name.'");'."\n";

	$cfg_file .= "?>\n";
	return $cfg_file;
}


function maybe_add_column($table,$column,$add_sql) {
	$sql = "DESCRIBE $table";
	$res = mysql_query($sql);
	$found = false;
	while($row = mysql_fetch_array($res,MYSQL_NUM)) {
		if ($row[0] == $column) $found = true;
	};
	if (!$found) {
		mysql_query("ALTER TABLE $table ADD `$column` ". $add_sql);
		return "<li>Adding new field $column to database.";
	} else {
		return "<li>$column already present in database.";
	};
}

function maybe_drop_column($table,$column) {
	$sql = "DESCRIBE $table";
	$res = mysql_query($sql);
	$found = false;
	while($row = mysql_fetch_array($res,MYSQL_NUM)) {
		if ($row[0] == $column) $found = true;
	};
	if ($found) {
		$sql = "ALTER TABLE $table DROP `$column`";
		mysql_query($sql);
		return "<li>dropping $column";
	} else {
		//print "$column does not exist<br>";
	};
}

function maybe_add_table($table,$add_sql,$options = "") {
	$sql = "DESCRIBE $table";
	$res = mysql_query($sql);
	if (!$res) {
		$q = "CREATE table `$table` ($add_sql) $options";
		mysql_query($q);
		if (mysql_error()) {
			var_dump(mysql_error());
		};
	} else {
		return "<li>Table `$table` already exists, ignoring.";
	};
}

function gd_missing() {
	require_once(dirname(__FILE__) . '/../../lib/phpthumb/phpthumb.functions.php');
	// this is copied over from phpthumb
	return phpthumb_functions::gd_version() < 1;
}

function check_requirements() {
	$errors = array();
	if (gd_missing()) {
		$errors[] = "PHP GD module was not detected.";
	};

	if (!function_exists('mysql_connect')) {
		$errors[] = "PHP MySQL module was not detected.";
	};

	$files_to_read = array("./","./admin","./css","./images","./lib","./thumbs","./uploads");
	foreach($files_to_read as $file){
		if (!is_readable(PLOGGER_DIR . $file)){
			$errors[] = "The path ".realpath(PLOGGER_DIR . $file)." (".$file.") is not readable.";
		}
	}

	$files_to_write = array("./","./thumbs","./images", "./uploads");
		foreach($files_to_write as $file){
			if (!is_writable(PLOGGER_DIR . $file)){
				$errors[] = 'The path '.realpath(PLOGGER_DIR . $file).' is not writable by the Web server.';
			}
		}

	return $errors;
}

function check_mysql($host,$user,$pass,$database) {
	$errors = array();
	if (function_exists('mysql_connect')) {
		$connection = @mysql_connect($host,$user,$pass);
		if (!$connection) {
			$errors[] = "Cannot connect to MySQL with the information provided. MySQL error: " 
					. mysql_error();
		};
	};
	$select = @mysql_select_db($database);
	if (!$select) {
		$errors[] = "Couldn't find the database $database. MySQL error: " . mysql_error();

	};
	return $errors;
}

function generate_password() {
	$src = preg_split("//","abcdefghkmnpqrstuvwxyz23456789",-1,PREG_SPLIT_NO_EMPTY);
	shuffle($src);
	return join("",array_slice($src,0,5));
}

function config_writable() {
	$cf = PLOGGER_DIR . "plog-config.php";
	if (file_exists($cf)) {
		return is_writable($cf);
	};
	return is_writable(PLOGGER_DIR);
}	

function write_config($data) {
	$cf = PLOGGER_DIR . "plog-config.php";
	$handle = fopen($cf,"w");
	fwrite($handle,$data);
	fclose($handle);
}

?>
