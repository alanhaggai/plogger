<?php
error_reporting(E_ALL);
require(dirname(__FILE__) . '/plog-globals.php');
require(PLOGGER_DIR . 'plog-functions.php');
require(PLOGGER_DIR . 'lib/plogger/install_functions.php');

// serve the config file
if (!empty($_POST['dlconfig']) && !empty($_SESSION['plogger_config'])) {
        header('Content-type: application/octet-stream');
        header('Content-Disposition: attachment; filename="plog-config.php"');
        print $_SESSION['plogger_config'];
        die();
};

// try to proceed to the admin interface. Only succeeds if the configuration is set
if (!empty($_POST['proceed']) && defined('PLOGGER_DB_HOST')) {
	$mysql = check_mysql(PLOGGER_DB_HOST,PLOGGER_DB_USER,PLOGGER_DB_PW,PLOGGER_DB_NAME);
	if (empty($mysql)) {
		create_tables();
		configure_plogger($_SESSION["install_values"]);
		require("plog-load_config.php");
		connect_db();
		$col = add_collection("Plogger test collection","feel free to delete it");
		$alb = add_album("Plogger test album","feel free to delete it",$col["id"]);
		unset($_SESSION["plogger_config"]);
		unset($_SESSION["install_values"]);
		header("Location: admin/index.php");
		exit;
	};
};
?>
<html>
	<head>
		<title>Install Plogger</title>
		<link rel="stylesheet" type="text/css" href="css/admin.css">
	</head>
	<body>
		<img src="graphics/plogger.gif" alt="Plogger">
<?php
if (empty($_POST['proceed'])) {
	do_install($_POST);
} else {
	require(PLOGGER_DIR . 'lib/plogger/form_setup_complete.php');
};
?>
</body>
</html>
