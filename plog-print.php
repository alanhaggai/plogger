<?php

include("plog-functions.php");
include("plog-globals.php");
include("plog-load_config.php");

$picture = get_picture_by_id($_GET['id']);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
	<body onload="window.print();">
		<img src="<?php echo $picture["url"]; ?>" alt="<?php echo $picture["caption"]; ?>" />
	</body>
</html>
