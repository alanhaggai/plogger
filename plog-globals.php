<?php
ini_set('include_path', ini_get('include_path')); 
@ini_set("arg_separator.output","&amp;");	
@ini_set("max_execution_time", "300");
@ini_set("memory_limit", "64M");

define('PLOGGER_DIR',dirname(__FILE__) . '/');
define('TABLE_PREFIX','plogger_');
$config_table = TABLE_PREFIX.'config';

define('THUMB_SMALL',1);
define('THUMB_LARGE',2);
define('THUMB_RSS',3);
define('THUMB_NAV',4);

if (!headers_sent())
{
	session_start();
};



require_once("lib/gettext/streams.php");
require_once("lib/gettext/gettext.php");

$locale = "en_US";

$mofile = "../plog-translations/" . $locale . ".mo";

// If the mo file does not exist or is not readable, or if the locale is
// en_US, do not load the mo.
if ( is_readable($mofile) && ($locale != 'en_US') ) {
    $input = new FileReader($mofile);
} else {
    $input = false;
}

$l10n = new gettext_reader($input);

// Return a translated string.    
function plog_tr($text) {
    global $l10n;
    return $l10n->translate($text);
}


?>
