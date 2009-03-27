<?php

//session_start();
header("Content-Type: text/html; charset=utf-8");
global $inHead;

//session_register ("entries_per_page");

require_once("../plog-functions.php");
require_once("../plog-globals.php");

if (isset($_REQUEST['action']) && $_REQUEST["action"] == "log_in"){
	// TODO: leiuta generic login funktsioon

	if (($_REQUEST["username"] == $config["admin_username"]) && (md5($_REQUEST["password"]) == $config["admin_password"])){
		$_SESSION["plogger_logged_in"] = true;
	}
	else{
		header("Location: index.php?errorcode=1");
		exit;
	}
}
elseif(isset($_REQUEST['action']) && $_REQUEST["action"] == "log_out"){
	$_SESSION = array();
	session_destroy();
}

if (!isset($_SESSION["plogger_logged_in"])){ 
	header("Location: index.php");
	exit;
}


function display($string, $current){
	
	global $inHead;
	global $config;
	
	$tabs = array();
	$tabs['upload'] 	= array('url' => 'plog-upload.php','caption' => plog_tr('<em>U</em>pload'));
	$tabs['import'] 	= array('url' => 'plog-import.php','caption' => plog_tr('<em>I</em>mport'));
	$tabs['manage'] 	= array('url' => 'plog-manage.php','caption' => plog_tr('<em>M</em>anage'));
	$tabs['feedback'] 	= array('url' => 'plog-feedback.php','caption' => plog_tr('<em>F</em>eedback'));
	$tabs['options']	= array('url' => 'plog-options.php','caption' => plog_tr('<em>O</em>ptions'));
	$tabs['themes']		= array('url' => 'plog-themes.php','caption' => plog_tr('<em>T</em>hemes'));
	$tabs['view'] 		= array('url' => $config['gallery_url'],'caption' => plog_tr('<em>V</em>iew'), 'onclick' => 'return GB_show(\'Live Gallery\', \''.$config['baseurl'].'\', 600, 800)');
	$tabs['support'] 	= array('url' => 'http://www.plogger.org/forum/','caption' => plog_tr('<em>S</em>upport'), 'onclick' => 'return GB_show(\'Plogger Support Forum\', \'http://www.plogger.org/forum/\', 700, 800)');
	$tabs['logout'] 	= array('url' => $_SERVER["PHP_SELF"].'?action=log_out','caption' => plog_tr('<em>L</em>og out'));
	// get the accesskey from the localization - it should be surrounded by <em> tags
	foreach($tabs as $key => $data) {
		if (preg_match("|<em>(.*)</em>|",$data["caption"],$matches)) {
			$tabs[$key]['accesskey'] = $matches[1];
		};

	};

	$output = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml">
			<head>
				<title>Plogger Administration</title>
				<meta http-equiv="Content-Type" content="txt/html; charset=utf-8" />
				<link href="../css/admin.css" type="text/css" rel="stylesheet" media="all"/>
				<link href="../css/greybox.css" type="text/css" rel="stylesheet" media="all"/>
				<link href="../css/tabs.css" type="text/css" rel="stylesheet" media="all"/>
				<link href="../css/lightbox.css" type="text/css" rel="stylesheet" media="all"/>
				<script type="text/javascript" src="js/prototype.js"></script>
				<script type="text/javascript" src="js/plogger.js"></script>
				<script type="text/javascript" src="js/lightbox.js"></script>
				<script type="text/javascript" src="js/AmiJS.js"></script>
				<script type="text/javascript" src="js/greybox.js"></script>
				'.$inHead.'
			 	<script type="text/javascript">
			      //GreyBox configuration
			      //Use animation?
			      var GB_ANIMATION = true;
			      var GB_IMG_DIR = "../graphics/";
			
			      //Clicking on the transparent overlay closes the GreyBox window?
			      var GB_overlay_click_close = false;
      		</script>
			</head>
			<body onload="focus_first_input(); initLightbox();">
				<div>
				<img src="../graphics/plogger.gif" alt="Plogger" />
				<span id="plogger-version">'.$config['version'].'</span>
				<div id="tab-nav">
					<ul>';
					foreach($tabs as $tab => $data) {
						$output .= '<li';
						if ($current == $tab) $output .= ' id="current"';
						$output .= '><a';
						if (!empty($data['onclick'])) $output .= ' onclick="'.$data['onclick'].'"';
						if (!empty($data['accesskey'])) $output .= ' accesskey="'.$data['accesskey'].'"';
						$output .= ' href="' . $data['url'] . '">' . $data['caption'] . '</a></li>';
					};
					$output .= '
					</ul>
				</div>
				'.$string.'
			</div></body>
		</html>';
	
	echo $output;
	exit;
}


?>
