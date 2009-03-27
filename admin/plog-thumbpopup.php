
<?php
require_once("plog-admin.php");
require_once("../plog-load_config.php"); 					// load configuration variables from database
require_once("../plog-functions.php");
// this script will just show a small preview of the thumbnail in admin view if
// you can't differentiate the small pics.
echo '<html><head>

<body>';


$src = $_REQUEST['src'];
$picture = get_picture_by_id($src);
$id = $picture["id"];
$thumbpath = generate_thumb($picture["path"],$picture["id"],THUMB_LARGE);
$thumbdir =  $config["basedir"] . "thumbs/lrg-$id-".basename($picture["path"]);
list($width, $height, $type, $attr) = getimagesize($thumbdir);

// print $thumbdir;

echo '
<script language="JavaScript">
<!--
this.resizeTo('.$width.'+25,'.$height.'+70);
this.moveTo((screen.width-'.$width.')/2, (screen.height-'.$height.')/2);
-->
</script>';

// generate XHTML with thumbnail and link to picture view.
$imgtag = '<img class="photos" src="'.$thumbpath.'" alt="'.$src.'"/>';

$output = $imgtag;

?>

<html>
<body>

<?php echo $output; ?>

</body>
</html>
