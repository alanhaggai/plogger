<?php

error_reporting(E_ERROR);

echo '
	<html>
		<head>
			<title>Upgrade Plogger</title>
			<link rel="stylesheet" type="text/css" href="admin/../css/admin.css">
		</head>
		<body>
		<img src="graphics/plogger.gif" alt="Plogger">
		<h1>Performing Upgrade...</h1>';
	
// This is the upgrade file for upgrading your Plogger gallery from Beta 1
$workdir = getcwd();
if (file_exists($workdir.'/plog-connect.php'))
{
	print "Rewriting configuration files...<br/>";
	// this check will also make sure that we can delete plog-connect when done, since deleting
	// is actually _writing_ to a directory
	if (!is_writable($workdir)) {
		print $workdir . " is not writable, but I need to create a new file in it";
		exit;
	};

	// now parse DB connection parameters out of plog-connect
	$src = file_get_contents($workdir.'/plog-connect.php');

	preg_match_all('/^\$DB_(HOST|USER|PW|NAME) = "(.*)".*$/m',$src,$data);
	$db_host = $data[2][0];
	$db_user = $data[2][1];
	$db_pw = $data[2][2];
	$db_name = $data[2][3];

	// write them to the new file
	$cfg_file = '';
	$cfg_file .= '// this is the file used to connect to your database.'."\n";
	$cfg_file .= '// you must change these values in order to run the gallery.'."\n";

	$cfg_file .= 'define("PLOGGER_DB_HOST","'.$db_host.'");'."\n";
	$cfg_file .= 'define("PLOGGER_DB_USER","'.$db_user.'");'."\n";
	$cfg_file .= 'define("PLOGGER_DB_PW","'.$db_pw.'");'."\n";
	$cfg_file .= 'define("PLOGGER_DB_NAME","'.$db_name.'");'."\n";

	$fh = fopen("plog-config.php","w");
	if (!$fh) {
		die("Could not write plog-config.php, please make the file writable and then try running this script again");
	};
	fwrite($fh,"<?php\n");
	fwrite($fh,$cfg_file);
	fwrite($fh,"?>\n");
	fclose($fh);

	unlink($workdir.'/plog-connect.php');
	print "Done!<br/>";

};

function makeDirs($strPath, $mode = 0777) //creates directory tree recursively
{
   return is_dir($strPath) or ( makeDirs(dirname($strPath), $mode) and mkdir($strPath, $mode) );
}

function maybe_add_column($table,$column,$add_sql) {
	$sql = "DESCRIBE $table";
	$res = mysql_query($sql);
	$found = false;
	while($row = mysql_fetch_array($res,MYSQL_NUM)) {
		if ($row[0] == $column) $found = true;
	};
	if (!$found) {
		print "<li>Adding new field $column to database.";
		mysql_query("ALTER TABLE $table ADD `$column` ". $add_sql);
	} else {
		print "<li>$column already present in database.";
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
		print "<li>dropping $column";
		$sql = "ALTER TABLE $table DROP `$column`";
		mysql_query($sql);
	} else {
		//print "$column does not exist<br>";
	};
}

function maybe_add_table($table,$add_sql) {
	$sql = "DESCRIBE $table";
	$res = mysql_query($sql);
	if (!$res)
		mysql_query("CREATE table `$table` ($add_sql)");
	else
		print "<li>Table `$table` already exists, ignoring.";
}

include("plog-functions.php");
include("plog-globals.php");
include("plog-config.php");
connect_db();
$errors = "";

$config_table = TABLE_PREFIX.'config';

print "<p>Upgrading database structure ($config_table)...</p>";
print "<ul>";

maybe_add_table(TABLE_PREFIX."thumbnail_config","
	`id` int(10) unsigned NOT NULL auto_increment,
	`update_timestamp` int(10) unsigned default NULL,
	`max_size` int(10) unsigned default NULL,
	`disabled` tinyint default 0,
	PRIMARY KEY  (`id`)
");

maybe_add_table(TABLE_PREFIX."tag2picture`","
	`tag_id` bigint(20) unsigned NOT NULL default '0',
	`picture_id` bigint(20) unsigned NOT NULL default '0',
	`tagdate` datetime default NULL,
	KEY `tag_id` (`tag_id`),
	KEY `picture_id` (`picture_id`)
");

maybe_add_table(TABLE_PREFIX."tags`","
	`id` bigint(20) unsigned NOT NULL auto_increment,
	`tag` char(50) NOT NULL default '',
	`tagdate` datetime NOT NULL default '0000-00-00 00:00:00',
	`urlified` char(50) NOT NULL default '',
	PRIMARY KEY  (`id`),
	UNIQUE `tag` (`tag`),
	UNIQUE `urlified` (`urlified`)
");

$sql = "INSERT INTO `".TABLE_PREFIX."thumbnail_config` (id,update_timestamp,max_size)
	VALUES('".THUMB_NAV."','".$tambov_constant."','".$config['nav_thumbsize']."')";
mysql_query($sql);

// use a random timestamp from the past to keep the existing thumbnails
$tambov_constant = 1096396500;

if (!isset($config['max_thumbnail_size'])) {
	$config['max_thumbnail_size'] = 100;
};

$sql = "INSERT INTO `".TABLE_PREFIX."thumbnail_config` (id,update_timestamp,max_size)
	VALUES('".THUMB_SMALL."','".$tambov_constant."','".$config['max_thumbnail_size']."')";
mysql_query($sql);

if (!isset($config['max_display_size'])) {
	$config['max_display_size'] = 500;
};
$sql = "INSERT INTO `".TABLE_PREFIX."thumbnail_config` (id,update_timestamp,max_size)
	VALUES('".THUMB_LARGE."','".$tambov_constant."','".$config['max_display_size']."')";
mysql_query($sql);

if (!isset($config['rss_thumbsize'])) {
	$config['rss_thumbsize'] = 400;
};

$sql = "INSERT INTO `".TABLE_PREFIX."thumbnail_config` (id,update_timestamp,max_size)
	VALUES('".THUMB_RSS."','".$tambov_constant."','".$config['rss_thumbsize']."')";
mysql_query($sql);

if (!isset($config['nav_thumbsize'])) {
	$config['nav_thumbsize'] = 60;
};

$sql = "INSERT INTO `".TABLE_PREFIX."thumbnail_config` (id,update_timestamp,max_size)
	VALUES('".THUMB_NAV."','".$tambov_constant."','".$config['nav_thumbsize']."')";
mysql_query($sql);

maybe_add_column(TABLE_PREFIX."thumbnail_config",'disabled',"tinyint default 0");

maybe_drop_column($config_table,"max_thumbnail_size");
maybe_drop_column($config_table,"max_display_size");
maybe_drop_column($config_table,"rss_thumbsize");


maybe_add_column($config_table,'gallery_url',"varchar(255) NOT NULL");

$config['baseurl'] = "http://".$_SERVER["SERVER_NAME"]. substr($_SERVER["PHP_SELF"],0,strrpos($_SERVER["PHP_SELF"],"/")) . "/";

if (empty($config['gallery_url'])) {
	$sql = "UPDATE $config_table SET gallery_url = '" . $config['baseurl'] . "'";
	mysql_query($sql);
};


// RSS config
maybe_add_column($config_table,'feed_num_entries',"int(15) NOT NULL default '15'");
maybe_add_column($config_table,'feed_title',"varchar(255) NOT NULL default 'Plogger Photo Feed'");
maybe_add_column($config_table,'feed_language',"varchar(20) NOT NULL default 'en-us'");

// cruft-free URLs
maybe_add_column($config_table,'use_mod_rewrite',"smallint NOT NULL default '0'");

// default sort order
maybe_add_column($config_table,'default_sortdir',"varchar(5) NOT NULL");
maybe_add_column($config_table,'default_sortby',"varchar(20) NOT NULL");

// add field for admin e-mail
maybe_add_column($config_table,'admin_email',"varchar(50) NOT NULL");

//
maybe_add_column($config_table,'allow_fullpic',"tinyint NOT NULL default '1'");
		
// comment notify
maybe_add_column($config_table,'comments_notify',"tinyint NOT NULL");

// comment moderation
maybe_add_column($config_table,'comments_moderate',"tinyint NOT NULL default 0");

// square thumbs
maybe_add_column($config_table,'square_thumbs',"tinyint default 1");

// selectable thumbnails
maybe_add_column(TABLE_PREFIX.'albums','thumbnail_id',"int(11) NOT NULL DEFAULT 0");
maybe_add_column(TABLE_PREFIX.'collections','thumbnail_id',"int(11) NOT NULL DEFAULT 0");

//
maybe_add_column(TABLE_PREFIX.'albums','path',"varchar(255) NOT NULL");
maybe_add_column(TABLE_PREFIX.'collections','path',"varchar(255) NOT NULL");
		

// add ip and approved fields to comments table
maybe_add_column(TABLE_PREFIX.'comments','ip',"char(64)");
maybe_add_column(TABLE_PREFIX.'comments','approved',"tinyint default 1");

maybe_add_column(TABLE_PREFIX.'pictures','description',"text");

// user definable theme directory
maybe_add_column(TABLE_PREFIX.'config','theme_dir',"varchar(128) NOT NULL");

// add support for user defined sort order for albums and collections
maybe_add_column(TABLE_PREFIX.'config','album_sortby',"varchar(20) NOT NULL default 'id'");
maybe_add_column(TABLE_PREFIX.'config','album_sortdir',"varchar(5) NOT NULL default 'DESC'");
maybe_add_column(TABLE_PREFIX.'config','collection_sortby',"varchar(20) NOT NULL default 'id'");
maybe_add_column(TABLE_PREFIX.'config','collection_sortdir',"varchar(5) NOT NULL default 'DESC'");

// add support for thumbnail configuration

maybe_add_column(TABLE_PREFIX.'config','enable_thumb_nav',"tinyint default 0");
maybe_add_column(TABLE_PREFIX.'config','thumb_nav_range',"int(11) NOT NULL default 0");

// insert default value (default theme directory)
$default_theme_dir = dirname(__FILE__)."/". 'themes/default/';
print "<li>Setting default theme directory to $default_theme_dir</li>";
$sql = 'UPDATE '.TABLE_PREFIX.'config SET `theme_dir` = \''.$default_theme_dir.'\' WHERE 1';
mysql_query($sql);

$sql = 'ALTER TABLE '.TABLE_PREFIX.'comments ADD INDEX approved_idx (`approved`)';
mysql_query($sql);

// add ip and approved fields to comments table
$sql = 'ALTER TABLE '.TABLE_PREFIX.'comments CHANGE `date` `date` datetime';
mysql_query($sql);

/* // add field for timestamp refresh conditions
$sql = 'ALTER TABLE '.TABLE_PREFIX.'config
		ADD  (`small_lastmodified` datetime NOT NULL,
			  `large_lastmodified` datetime NOT NULL,
			  `rss_lastmodified` datetime NOT NULL)';

if (mysql_query($sql))
	echo "<p>Your Plogger database has successfully been upgraded to smart thumbnail caching!</p>";
else
	echo("<p>Database has already been upgraded to support smart thumbnail caching!</p>");
*/
			
echo "</ul>";
echo "<p>Reorganizing your images folder...";

# strip images prefix from pictures table
$sql = "UPDATE ".TABLE_PREFIX."pictures SET path = SUBSTRING(path,8) WHERE SUBSTRING(path,1,7) = 'images/'"; 
$result = mysql_query($sql);

$sql = "SELECT id,name FROM ".TABLE_PREFIX."collections";
$result = mysql_query($sql) or die(mysql_error() . "<br /><br />" . $sql);
while($row = mysql_fetch_assoc($result)) {
	$sql = "UPDATE ".TABLE_PREFIX."collections SET path = '" . strtolower(sanitize_filename($row['name'])) . "' WHERE id = " . $row['id'];
	#print $sql;
	#print "<br>";
	mysql_query($sql);
} 

$sql = "SELECT id,name FROM ".TABLE_PREFIX."albums";
$result = mysql_query($sql) or die(mysql_error() . "<br /><br />" . $sql);
while($row = mysql_fetch_assoc($result)) {
	$sql = "UPDATE ".TABLE_PREFIX."albums SET path = '" . strtolower(sanitize_filename($row['name'])) . "' WHERE id = " . $row['id'];
	#print $sql;
	#print "<br>";
	mysql_query($sql);
} 

// loop through each image from the pictures table, get its parent album name and parent collection
// name, create subdirectories, move the file, and update the PATH field in pictures.

// We need to do a join on the tables to get album names and collection names

$sql = "SELECT p.path AS path, p.id AS pid,c.path AS collection_path, a.path AS album_path
		FROM ".TABLE_PREFIX."albums a, ".TABLE_PREFIX."pictures p, ".TABLE_PREFIX."collections c 
		WHERE p.parent_album = a.id AND p.parent_collection = c.id";
		

$result = mysql_query($sql) or die(mysql_error() . "<br /><br />" . $sql);


echo "<ul>";

while($row = mysql_fetch_assoc($result)) {
	
	$errors = 0;
	$filename = basename($row['path']);
	$directory = $row['collection_path']."/".$row['album_path']."/";
	$new_path = "images/".$directory.$filename;
	if ($row['path'] == $new_path) continue;
	echo "<li>Moving $row[path] -> $new_path</li>";
	
	// move physical file, create directory if necessary and update path in database
	if (!makeDirs("images/".$directory, 0755))
			echo "<ul><li>Error: Could not create directory $directory!</li></ul>";
	
	if (!rename("images/" . $row['path'], $new_path)) {
		echo "<li>Error: could not move file!</li>";
		$errors++; 
		}
	else {	
		$directory = mysql_real_escape_string($directory . $filename);
		// update database
		$sql = "UPDATE ".TABLE_PREFIX."pictures SET path = '$directory' WHERE id = '$row[pid]'";
		mysql_query($sql) or die("<li>Error: ".mysql_error()." in query " . $sql . "</li>");
	}
	
} 
	
echo "</ul>";

if (!$errors)
	echo "Your files were successfully reorganized!";
else
	echo "There were $errors errors, check your permissions settings.";


	

// convert charsets
// since 4.1 MySQL has support for specifying character encoding for tables
// and I really want to  use it if avaiable. So we need figure out what version
// we are running on and to the right hting
$mysql_version = mysql_get_server_info();
$mysql_charset_support = "4.1";
$default_charset = "";

if (1 == version_compare($mysql_version,$mysql_charset_support))
{
	$charset = "utf8";
	print "<br>";
	$tables = array("collections","albums","pictures","comments","config");
	foreach($tables as $table) {
		$tablename = TABLE_PREFIX . $table;
		$sql = "ALTER TABLE $tablename DEFAULT CHARACTER SET $charset";
		if (mysql_query($sql)) {
			print $tablename . " converted to $charset<br>";
		} else {
			print "failed to convert $tablename to $charset<br>";
			print mysql_error();
		};
	}
};

echo "<p>Upgrade has completed!"
?>

		      
		  
